<?php

declare(strict_types=1);

namespace Tests\Unit\Model\Task;

use App\Models\Task\Vo\TaskPriority;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TaskPriorityTest extends TestCase
{
    #[Test]
    public function it_can_be_created_from_a_valid_string(): void
    {
        $priotiry = TaskPriority::from('high');
        $this->assertEquals('high', $priotiry->value);
    }

    #[Test]
    public function it_throws_exception_for_invalid_string(): void
    {
        $this->expectException(\ValueError::class);
        TaskPriority::from('invalid_priority');
    }

    #[Test]
    public function it_can_compare_priorities(): void
    {
        $this->assertTrue(
            TaskPriority::HIGH->isHigherThan(TaskPriority::MEDIUM)
        );
        $this->assertTrue(
            TaskPriority::MEDIUM->isHigherThan(TaskPriority::LOW)
        );
        $this->assertFalse(
            TaskPriority::LOW->isHigherThan(TaskPriority::HIGH)
        );
    }

    #[Test]
    public function it_can_update_to_a_higher_priority(): void
    {
        $priority = TaskPriority::LOW;
        $priority = $priority->updateTo(TaskPriority::MEDIUM);
        $this->assertEquals('medium', $priority->value);
    }

    #[Test]
    public function it_knows_if_it_is_urgent(): void
    {
        $this->assertTrue(TaskPriority::HIGH->isUrgent());
        $this->assertFalse(TaskPriority::MEDIUM->isUrgent());
        $this->assertFalse(TaskPriority::LOW->isUrgent());
    }
}
