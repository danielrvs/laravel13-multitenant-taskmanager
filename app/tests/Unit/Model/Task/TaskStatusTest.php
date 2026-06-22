<?php

namespace Tests\Unit\Model\Task;

use App\Models\Task\Vo\TaskStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

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
    public function it_can_transition_to_a_valid_next_status(): void
    {
        $status = TaskStatus::from('pending');
        $target = $status->transitionTo(TaskStatus::IN_PROGRESS);
        $this->assertEquals('in_progress', $target->value);

        $target = $target->transitionTo(TaskStatus::COMPLETED);
        $this->assertEquals('completed', $target->value);
    }

    #[Test]
    public function it_cannot_transition_to_an_invalid_status(): void
    {
        $this->expectException(\ValueError::class);
        $status = TaskStatus::from('pending');
        $status->transitionTo('invalid_status');
    }
}
