<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Task\Task;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NotifyTaskAssignment implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public array $backoff = [30, 120, 300];

    /**
     * Create a new job instance.
     */
    public function __construct(
        public readonly int $taskId,
        public readonly int $assignedUserId
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $task = Task::findOrFail($this->taskId);
        $user = User::findOrFail($this->assignedUserId);

        $status = DB::table('task_audits')
            ->where('task_id', $task->id)
            ->where('user_id', $user->id)
            ->value('action');

        if ($status === 'notified_successfully') {
            Log::info("User {$this->assignedUserId} has already been notified for task {$this->taskId}. Skipping notification.");
            return;
        }

        if (!$status) {
            DB::table('task_audits')->insert([
                'task_id' => $task->id,
                'user_id' => $user->id,
                'action' => 'pending_notification',
                'created_at' => now()
            ]);
        }

        // Simulate sending notification (e.g., email, in-app notification)
        Log::info("Notifying user {$user->id} about assignment to task {$task->id}.");

        DB::table('task_audits')
            ->where('task_id', $task->id)
            ->where('user_id', $user->id)
            ->update([
                'action' => 'notified_successfully',
                'updated_at' => now()
            ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::critical("The job failed after 3 tries", [
            'task_id' => $this->taskId,
            'user_id' => $this->assignedUserId,
            'error' => $exception->getMessage()
        ]);
    }
}
