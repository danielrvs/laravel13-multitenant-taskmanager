<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Models\Task\Task;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class PurgeOverdueTasksTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_purges_overdue_tasks_safely():void
    {
        $this->travelTo(Carbon::parse('2026-06-02'));

        $taskShouldBeDeleted = Task::create([
            'tenant_id' => 1,
            'user_id' => 1,
            'title' => 'Overdue Task',
            'description' => 'This task is overdue and should be deleted.',
            'status' => 'pending',
            'priority' => 'medium',
            'due_date' => now()->subYear()->subDay(),
        ]);

        $taskShouldNotBeDeleted = Task::create([
            'tenant_id' => 1,
            'user_id' => 1,
            'title' => 'Completed Task',
            'description' => 'This task is overdue but completed, should not be deleted.',
            'status' => 'pending',
            'priority' => 'medium',
            'due_date' => now()->subMonths(11),
        ]);

        $taskInTheFuture = Task::create([
            'tenant_id' => 1,
            'user_id' => 1,
            'title' => 'Future Task',
            'description' => 'This task is in the future, should not be deleted.',
            'status' => 'pending',
            'priority' => 'medium',
            'due_date' => now()->addMonths(1),
        ]);

        $this->assertDatabaseCount('tasks', 3);

        $this->artisan('tasks:purge-overdue')
            ->expectsOutput("Deleted batch of 1 overdue tasks. Total deleted: 1")
            ->expectsOutput("Finished purging overdue tasks and cleaning cache.")
            ->assertExitCode(0);
        
        $this->assertDatabaseCount('tasks', 2);
        $this->assertDatabaseMissing('tasks', ['id' => $taskShouldBeDeleted->id]);
        $this->assertDatabaseHas('tasks', ['id' => $taskShouldNotBeDeleted->id]);
        $this->assertDatabaseHas('tasks', ['id' => $taskInTheFuture->id]);
    }
}