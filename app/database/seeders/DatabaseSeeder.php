<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Task\Task;
use App\Models\Tenant\Tenant;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the database with two isolated tenants and realistic task data.
     *
     * This demonstrates the multitenant architecture: each tenant's users
     * and tasks are completely isolated from the other tenant's data.
     *
     * Credentials:
     *   Tenant Acme Corp  → admin@acme.com / password
     *                     → manager@acme.com / password
     *                     → viewer@acme.com  / password
     *   Tenant Globex Inc → admin@globex.com / password
     *                     → manager@globex.com / password
     */
    public function run(): void
    {
        // ─── Tenant 1: Acme Corp ───────────────────────────────────────────
        $acme = Tenant::factory()->create(['name' => 'Acme Corp']);

        $acmeAdmin = User::factory()->create([
            'name' => 'Alice Admin',
            'email' => 'admin@acme.com',
            'role' => UserRole::ADMIN,
            'tenant_id' => $acme->id,
        ]);

        $acmeManager = User::factory()->create([
            'name' => 'Bob Manager',
            'email' => 'manager@acme.com',
            'role' => UserRole::MANAGER,
            'tenant_id' => $acme->id,
        ]);

        $acmeViewer = User::factory()->create([
            'name' => 'Carol Viewer',
            'email' => 'viewer@acme.com',
            'role' => UserRole::VIEWER,
            'tenant_id' => $acme->id,
        ]);

        // Acme tasks — mix of states to show the full domain model
        Task::factory()->create([
            'tenant_id' => $acme->id,
            'user_id' => $acmeAdmin->id,
            'title' => 'Set up CI/CD pipeline',
            'description' => 'Configure GitHub Actions for automated testing and deployment.',
            'priority' => 'high',
            'status' => 'in_progress',
            'assigned_to' => $acmeManager->id,
            'due_date' => now()->addDays(7),
        ]);

        Task::factory()->create([
            'tenant_id' => $acme->id,
            'user_id' => $acmeManager->id,
            'title' => 'Write API documentation',
            'description' => 'Document all REST endpoints using OpenAPI 3.0.',
            'priority' => 'medium',
            'status' => 'pending',
            'due_date' => now()->addDays(14),
        ]);

        Task::factory()->completed($acmeViewer->id)->create([
            'tenant_id' => $acme->id,
            'user_id' => $acmeAdmin->id,
            'title' => 'Migrate database to PostgreSQL',
            'description' => 'Completed migration from MySQL. All indexes verified.',
            'priority' => 'high',
            'due_date' => now()->subDays(3),
        ]);

        // Overdue task — will be purged by tasks:purge-overdue after 1 year
        Task::factory()->create([
            'tenant_id' => $acme->id,
            'user_id' => $acmeAdmin->id,
            'title' => 'Refactor legacy authentication module',
            'description' => 'This task has been overdue for over a year.',
            'priority' => 'low',
            'status' => 'pending',
            'due_date' => now()->subYear()->subDays(10),
        ]);

        // ─── Tenant 2: Globex Inc ─────────────────────────────────────────
        $globex = Tenant::factory()->create(['name' => 'Globex Inc']);

        $globexAdmin = User::factory()->create([
            'name' => 'Dave Admin',
            'email' => 'admin@globex.com',
            'role' => UserRole::ADMIN,
            'tenant_id' => $globex->id,
        ]);

        $globexManager = User::factory()->create([
            'name' => 'Eve Manager',
            'email' => 'manager@globex.com',
            'role' => UserRole::MANAGER,
            'tenant_id' => $globex->id,
        ]);

        Task::factory()->create([
            'tenant_id' => $globex->id,
            'user_id' => $globexAdmin->id,
            'title' => 'Launch Q3 marketing campaign',
            'description' => 'Coordinate with the design team and schedule social media posts.',
            'priority' => 'high',
            'status' => 'pending',
            'assigned_to' => $globexManager->id,
            'due_date' => now()->addDays(30),
        ]);

        Task::factory()->create([
            'tenant_id' => $globex->id,
            'user_id' => $globexManager->id,
            'title' => 'Review vendor contracts',
            'description' => 'Annual review of SLAs with third-party providers.',
            'priority' => 'medium',
            'status' => 'in_progress',
            'due_date' => now()->addDays(5),
        ]);
    }
}
