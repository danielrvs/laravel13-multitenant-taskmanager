<?php

declare(strict_types=1);

namespace Tests\Feature\Task;

use App\Enums\UserRole;
use App\Models\Task\Task;
use App\Models\Tenant\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class TaskPolicyTest extends TestCase
{
    use RefreshDatabase;

    private function route(int $id = 0): string
    {
        return sprintf("/api/tasks/%s", $id);
    }

    #[Test]
    public function an_admin_can_delete_tasks_but_a_viewer_cannot_update_them(): void
    {

        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id, 'role' => UserRole::ADMIN]);
        $viewer = User::factory()->create(['tenant_id' => $tenant->id, 'role' => UserRole::VIEWER]);

        $task = Task::factory()->create([
            'tenant_id' => $tenant->id,
            'title' => 'Protected task',
            'description' => 'stricted security',
            'user_id' => $admin->id
        ]);

        $this->actingAs($viewer, 'sanctum');
        $response = $this->putJson($this->route($task->id), ['title' => 'Cambio prohibido']);
        $response->assertStatus(403);

        $this->actingAs($admin, 'sanctum');
        $this->deleteJson($this->route($task->id))->assertStatus(204);

        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }
}