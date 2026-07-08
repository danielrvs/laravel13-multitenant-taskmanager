<?php

declare(strict_types=1);

namespace Tests\Unit\Model\Task;

use App\Models\Task\Task;
use App\Models\Task\Vo\TaskStatus;
use App\Models\Tenant\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use ValueError;

class TaskTest extends TestCase
{

    use RefreshDatabase;
    use WithFaker;

    public function setUp(): void
    {
        parent::setUp();
        $this->makeTenant(['id' => 1]);
    }

    private function makeTenant(array $overrides = []): Tenant
    {
        return Tenant::factory()->create($overrides);
    }

    private function makeTask(array $overrides = []): Task
    {
        return Task::factory()->create($overrides);
    }

    private function makeUser(array $overrides = []): User
    {
        return User::factory()->create($overrides);
    }


    #[Test]
    public function it_can_be_created(): void
    {
        $task = $this->makeTask(['title' => 'New Task', 'status' => 'pending', 'priority' => 'high']);
        $this->assertEquals('New Task', $task->title);
        $this->assertEquals('pending', $task->status->value);
        $this->assertEquals('high', $task->priority->value);
    }

    #[Test]
    public function title_cannot_be_empty(): void
    {
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->makeTask(['title' => '']);
    }

    #[Test]
    public function title_cannot_exceed_255_characters(): void
    {
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->makeTask(['title' => str_repeat('a', 256)]);
    }

    #[Test]
    public function it_can_be_assigned_to_a_user(): void
    {

        $this->makeUser(['id' => 1, 'tenant_id' => 1]);
        $this->makeUser(['id' => 2, 'tenant_id' => 1]);

        $task = $this->makeTask(['tenant_id' => 1, 'user_id' => 1]);

        $task->assignTo(2);
        $this->assertEquals(2, $task->assigned_to);
    }

    #[Test]
    public function it_cannot_be_assigned_to_a_user_from_a_different_tenant(): void
    {
        $this->makeTenant(['id' => 2]);
        $this->makeUser(['id' => 999, 'tenant_id' => 2]);
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $task = $this->makeTask();
        $task->assignTo(999);
    }

    #[Test]
    public function it_cannot_be_assigned_to_the_same_user_that_created_it(): void
    {
        $this->makeUser(['id' => 1, 'tenant_id' => 1]);
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $task = $this->makeTask(['tenant_id' => 1, 'user_id' => 1]);
        $task->assignTo(1);
    }

    #[Test]
    public function it_can_transition_status(): void
    {
        $task = $this->makeTask();
        $task->transitionStatus('in_progress');
        $this->assertEquals('in_progress', $task->status->value);

        $task->transitionStatus(TaskStatus::COMPLETED);
        $this->assertEquals('completed', $task->status->value);
    }

    #[Test]
    public function it_cannot_transition_to_an_invalid_status(): void
    {
        $this->expectException(ValueError::class);
        $task = $this->makeTask();
        $task->transitionStatus('invalid_status');
    }

    #[Test]
    public function it_cannot_transition_from_completed_to_pending(): void
    {
        $this->expectException(\LogicException::class);
        $task = $this->makeTask(['status' => 'in_progress']);
        $task->transitionStatus(TaskStatus::COMPLETED);
        $task->transitionStatus(TaskStatus::PENDING);
    }

    #[Test]
    public function it_is_overdue_when_due_date_has_passed(): void
    {
        $task = $this->makeTask(['due_date' => now()->subDay()]);
        $this->assertTrue($task->isOverdue());
    }

    #[Test]
    public function it_is_not_overdue_when_status_is_completed(): void
    {
        $task = $this->makeTask(['status' => 'completed', 'due_date' => now()->subDay()]);
        $this->assertFalse($task->isOverdue());
    }
}
