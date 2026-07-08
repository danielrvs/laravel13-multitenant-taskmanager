<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Support\Facades\Cache;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('tasks:purge-overdue')]
#[Description('Permanently delete overdue tasks')]
class TasksPurgeOverdue extends Command
{


    /**
     * Execute the console command.
     */


    public function handle(): int
    {
        $oneYearAgo = now()->subYear();
        $batchSize = 1000;
        $deletedTotal = 0;
        $clearedTenants = [];

        $this->info("Starting purge of overdue tasks created before {$oneYearAgo->toDateString()}...");

        DB::table('tasks')->select('id', 'tenant_id')
            ->where('due_date', '<', $oneYearAgo)
            ->where('status', '!=', 'completed')
            ->chunkById($batchSize, function ($tasks) use (&$clearedTenants, &$deletedTotal) {
                $idsToDelete = $tasks->pluck('id')->toArray();

                $affected = DB::table('tasks')
                    ->whereIn('id', $idsToDelete)
                    ->delete();

                $deletedTotal += $affected;

                foreach ($tasks as $task) {
                    Cache::forget("tenant_{$task->tenant_id}_tasks_{$task->id}");

                    if (!in_array($task->tenant_id, $clearedTenants)) {
                        Cache::forget("tenant_{$task->tenant_id}_tasks");
                        $clearedTenants[] = $task->tenant_id;
                    }
                }

                if ($affected > 0) {
                    $this->info("Deleted batch of {$affected} overdue tasks. Total deleted: {$deletedTotal}");
                    usleep(100000);
                }
            });

        $this->info("Finished purging overdue tasks and cleaning cache.");

        $peakMemory = memory_get_peak_usage(true) / 1024 / 1024; // Convert bytes to MB
        $this->info(sprintf('Max RAM used: %.2f MB', $peakMemory));

        return Command::SUCCESS;
    }
}
