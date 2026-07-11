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

        $response = $this->actingAs($user1, 'sanctum')->getJson('/api/tasks/'.$otherTask->id);

        $response->assertStatus(404);
    }

    #[Test]
    public function tenant_manager_has_clear_method_to_reset_state(): void
    {
        $manager = app(\App\Services\Tenant\TenantManager::class);
        $manager->setTenantId(42);
        
        $this->assertEquals(42, $manager->getTenantId());
        
        $manager->clear();
        
        $this->assertNull($manager->getTenantId());
    }

    #[Test]
    public function octane_configuration_includes_tenant_manager_in_flush_list(): void
    {
        $octaneConfig = config('octane');
        
        $this->assertIsArray($octaneConfig);
        $this->assertArrayHasKey('flush', $octaneConfig);
        $this->assertContains(
            \App\Services\Tenant\TenantManager::class,
            $octaneConfig['flush'],
            'TenantManager must be listed in octane.flush config to prevent state leakage.'
        );
    }

    #[Test]
    public function it_simulates_octane_request_cycle_and_prevents_tenant_leakage(): void
    {
        $tenantA = $this->makeTenant();
        $userA = $this->makeUser(['tenant_id' => $tenantA->id]);

        $tenantB = $this->makeTenant();
        $userB = $this->makeUser(['tenant_id' => $tenantB->id]);

       
        $this->actingAs($userA, 'sanctum')->getJson('/api/tasks');
        
        $managerRequest1 = app(\App\Services\Tenant\TenantManager::class);
        $this->assertEquals($tenantA->id, $managerRequest1->getTenantId());

        $octaneFlushList = config('octane.flush', []);
        foreach ($octaneFlushList as $service) {
            app()->forgetInstance($service);
        }

        $managerRequest2 = app(\App\Services\Tenant\TenantManager::class);
        $this->assertNull(
            $managerRequest2->getTenantId(), 
            'Data leak detected! The previous tenant ID persisted in memory.'
        );
    }
}
