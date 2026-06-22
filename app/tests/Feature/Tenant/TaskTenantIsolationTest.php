<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\Models\Task\Task;
use App\Models\Tenant\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TaskTenantIsolationTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    private function makeTenant(array $overrides = []): Tenant
    {
        return Tenant::factory()->create($overrides);
    }

    private function makeUser(array $overrides = []): User
    {
        return User::factory()->create($overrides);
    }

    private function makeTask(array $overrides = []): Task
    {
        return Task::factory()->create($overrides);
    }

    #[Test]
    public function a_tenant_can_only_see_its_own_tasks()
    {
        $mainTenant = $this->makeTenant(['id' => 1]);
        $otherTenant = $this->makeTenant(['id' => 2]);

        $user1 = $this->makeUser(['id' => 1, 'tenant_id' => $mainTenant->id]);
        $user2 = $this->makeUser(['id' => 2, 'tenant_id' => $otherTenant->id]);

        $mainTask = $this->makeTask(['id' => 1, 'tenant_id' => $mainTenant->id, 'user_id' => $user1->id]);
        $otherTask = $this->makeTask(['id' => 2, 'tenant_id' => $otherTenant->id, 'user_id' => $user2->id]);

        $response = $this->actingAs($user1, 'sanctum')->getJson('/api/tasks');

        $response->assertStatus(200);

        $response->assertJsonFragment(['title' => $mainTask->title]);
        $response->assertJsonMissing(['title' => $otherTask->title]);
    }

    #[Test]
    public function user_cannot_find_tasks_from_other_tenants()
    {
        $mainTenant = $this->makeTenant(['id' => 1]);
        $otherTenant = $this->makeTenant(['id' => 2]);

        $user1 = $this->makeUser(['id' => 1, 'tenant_id' => $mainTenant->id]);
        $user2 = $this->makeUser(['id' => 2, 'tenant_id' => $otherTenant->id]);

        $mainTask = $this->makeTask(['id' => 1, 'tenant_id' => $mainTenant->id, 'user_id' => $user1->id]);
        $otherTask = $this->makeTask(['id' => 2, 'tenant_id' => $otherTenant->id, 'user_id' => $user2->id]);

        $response = $this->actingAs($user1, 'sanctum')->getJson('/api/tasks/' . $otherTask->id);


        $response->assertStatus(404);
    }
}
