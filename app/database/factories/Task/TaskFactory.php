<?php

declare(strict_types=1);

namespace Database\Factories\Task;

use App\Models\Tenant\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tenant_id' => fn () => Tenant::inRandomOrder()->first()?->id ?? Tenant::factory(),
            'user_id' => function (array $attributes) {
                return User::where('tenant_id', $attributes['tenant_id'])->inRandomOrder()->first()?->id
                    ?? User::factory()->create(['tenant_id' => $attributes['tenant_id']])->id;
            },

            'title' => 'Task '.$this->faker->sentence(3),
            'description' => $this->faker->text(),
            'status' => 'pending',
            'priority' => 'medium',
            'due_date' => now()->addDays(7),
            'assigned_to' => null,
        ];
    }

    public function assignedToUser(int $userId): static
    {
        return $this->state([
            'assigned_to' => $userId,
        ]);
    }

    public function completed(?int $userId = null): static
    {
        return $this->state(function (array $attributes) use ($userId) {
            return [
                'status' => 'completed',
                'assigned_to' => $userId ?? User::where('tenant_id', $attributes['tenant_id'])->inRandomOrder()->first()?->id
                    ?? User::factory()->create(['tenant_id' => $attributes['tenant_id']])->id,
            ];
        });
    }
}
