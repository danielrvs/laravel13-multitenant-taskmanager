<?php

declare(strict_types=1);

namespace App\Models\Task\Vo;

enum TaskPriority: string
{
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';

    public function isHigherThan(TaskPriority $other): bool
    {
        $order = [self::LOW, self::MEDIUM, self::HIGH];

        return array_search($this, $order) > array_search($other, $order);

    }

    public function updateTo(TaskPriority|string $newPriority): self
    {
        $target = $newPriority instanceof self ? $newPriority : self::from($newPriority);

        return $target;
    }

    public function isUrgent(): bool
    {
        return $this === self::HIGH;
    }
}
