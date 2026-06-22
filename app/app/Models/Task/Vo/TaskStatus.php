<?php

declare(strict_types=1);

namespace App\Models\Task\Vo;

enum TaskStatus: string
{
    case PENDING = 'pending';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';

    public function isCompleted(): bool
    {
        return $this === self::COMPLETED;
    }

    public function transitionTo(TaskStatus|string $newStatus):self
    {
        $target = $newStatus instanceof self ? $newStatus : self::from($newStatus);
        return $target;
    }
}
