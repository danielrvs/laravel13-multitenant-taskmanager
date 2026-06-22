<?php

declare(strict_types=1);

namespace Tests\Feature\Jobs;

use App\Jobs\NotifyTaskAssignment;
use App\Models\Task\Task;
use App\Models\Tenant\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class NotifyTaskAssignmentTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_dispatches_the_job_when_a_task_is_created(): void
    {
        Queue::fake();

        $tenant = Tenant::factory()->create();
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => \App\Enums\UserRole::MANAGER,
        ]);
        $assignToUser = User::factory()->create(['tenant_id' => $tenant->id]);

        $this->actingAs($user, 'sanctum');
        $response = $this
            ->postJson('/api/tasks', [
                'title' => 'Nueva tarea crítica',
                'description' => 'Revisar logs',
                'assigned_to' => $assignToUser->id,
                'priority' => 'high',
                'status' => 'pending'
            ]);

        $response->assertStatus(201);

        Queue::assertPushed(NotifyTaskAssignment::class, function ($job) use ($assignToUser) {
            return $job->assignedUserId === $assignToUser->id;
        });
    }

    #[Test]
    public function it_is_idempotent_and_does_not_duplicate_audits_or_emails(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $task = Task::create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'title' => 'Tarea de prueba',
            'description' => 'Ejecutando job',
            'assigned_to' => $user->id,
            'priority' => 'high',
            'status' => 'pending'
        ]);

        $job = new NotifyTaskAssignment($task->id, $user->id);

        $job->handle();

        $this->assertDatabaseCount('task_audits', 1);
        $this->assertDatabaseHas('task_audits', [
            'task_id' => $task->id,
            'action' => 'notified_successfully'
        ]);

        $job->handle();

        $this->assertDatabaseCount('task_audits', 1);
    }
}
