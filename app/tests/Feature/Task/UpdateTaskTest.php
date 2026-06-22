<?php

declare(strict_types=1);

namespace Tests\Feature\Task;

use App\Enums\UserRole;
use App\Models\Tenant\Tenant;
use App\Models\User;
use App\Models\Task\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateTaskTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function a_user_can_update_their_task(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'password' => 'password',
            'role' => UserRole::ADMIN
        ]);

        $task = Task::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id
        ]);

        $response = $this->actingAs($user, 'sanctum')->putJson("/api/tasks/{$task->id}", [
            'title' => 'New Title',
            'description' => 'New Description',
            'status' => 'in_progress',
            'priority' => 'high',
            'due_date' => now()->addDays(14),
        ]);


        $response->assertStatus(204);
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'New Title',
            'description' => 'New Description',
            'status' => 'in_progress',
            'priority' => 'high',
            'due_date' => now()->addDays(14)->toDateTimeString(),
        ]);
    }
}