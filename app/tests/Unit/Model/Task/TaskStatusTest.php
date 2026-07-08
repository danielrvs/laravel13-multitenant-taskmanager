<?php

declare(strict_types=1);

namespace Tests\Unit\Model\Task;

use App\Models\Task\Vo\TaskStatus;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class TaskStatusTest extends TestCase
{
    #[Test]
    public function it_can_be_created_from_a_valid_string(): void
    {
        $status = TaskStatus::from('pending');
        $this->assertEquals('pending', $status->value);
    }

    #[Test]
    public function it_throws_exception_for_invalid_string(): void
    {
        $this->expectException(\ValueError::class);
        TaskStatus::from('invalid_status');
    }

    #[Test]
    public function it_can_check_if_it_is_done(): void
    {
        $this->assertTrue(TaskStatus::from('completed')->isCompleted());
        $this->assertFalse(TaskStatus::from('pending')->isCompleted());
    }

    #[Test]
    public function it_can_transition_from_pending_to_in_progress(): void
    {
        $status = TaskStatus::PENDING;
        $next = $status->transitionTo(TaskStatus::IN_PROGRESS);
        $this->assertSame(TaskStatus::IN_PROGRESS, $next);
    }

    #[Test]
    public function it_can_transition_from_in_progress_to_completed(): void
    {
        $status = TaskStatus::IN_PROGRESS;
        $next = $status->transitionTo(TaskStatus::COMPLETED);
        $this->assertSame(TaskStatus::COMPLETED, $next);
    }

    #[Test]
    public function it_can_transition_from_in_progress_back_to_pending(): void
    {
        $status = TaskStatus::IN_PROGRESS;
        $next = $status->transitionTo(TaskStatus::PENDING);
        $this->assertSame(TaskStatus::PENDING, $next);
    }

    #[Test]
    public function it_can_transition_using_a_string_value(): void
    {
        $next = TaskStatus::PENDING->transitionTo('in_progress');
        $this->assertSame(TaskStatus::IN_PROGRESS, $next);
    }


    #[Test]
    public function it_cannot_transition_from_pending_directly_to_completed(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("Cannot transition from 'pending' to 'completed'");
        TaskStatus::PENDING->transitionTo(TaskStatus::COMPLETED);
    }

    #[Test]
    public function it_cannot_transition_from_completed_to_pending(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("Cannot transition from 'completed' to 'pending'");
        TaskStatus::COMPLETED->transitionTo(TaskStatus::PENDING);
    }

    #[Test]
    public function it_cannot_transition_from_completed_to_in_progress(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("Cannot transition from 'completed' to 'in_progress'");
        TaskStatus::COMPLETED->transitionTo(TaskStatus::IN_PROGRESS);
    }

    #[Test]
    public function it_cannot_transition_to_a_nonexistent_status(): void
    {
        $this->expectException(\ValueError::class);
        TaskStatus::PENDING->transitionTo('invalid_status');
    }

    #[Test]
    public function can_transition_to_returns_true_for_valid_transitions(): void
    {
        $this->assertTrue(TaskStatus::PENDING->isPossibleTransition(TaskStatus::IN_PROGRESS));
        $this->assertTrue(TaskStatus::IN_PROGRESS->isPossibleTransition(TaskStatus::COMPLETED));
        $this->assertTrue(TaskStatus::IN_PROGRESS->isPossibleTransition(TaskStatus::PENDING));
    }

    #[Test]
    public function can_transition_to_returns_false_for_invalid_transitions(): void
    {
        $this->assertFalse(TaskStatus::PENDING->isPossibleTransition(TaskStatus::COMPLETED));
        $this->assertFalse(TaskStatus::COMPLETED->isPossibleTransition(TaskStatus::PENDING));
        $this->assertFalse(TaskStatus::COMPLETED->isPossibleTransition(TaskStatus::IN_PROGRESS));
    }
}

