<?php

declare(strict_types=1);

namespace App\Models\Task\Vo;

enum TaskStatus: string
{
    case PENDING = 'pending';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';


    private const TRANSITIONS = [
        'pending' => ['in_progress'],
        'in_progress' => ['completed', 'pending'],
        'completed' => [],
    ];

    public function isCompleted(): bool
    {
        return $this === self::COMPLETED;
    }

    public function isPossibleTransition(TaskStatus $target): bool
    {
        return in_array($target->value, self::TRANSITIONS[$this->value], true);
    }

    public function transitionTo(TaskStatus|string $newStatus): self
    {
        $target = $newStatus instanceof self ? $newStatus : self::from($newStatus);

        if (!$this->isPossibleTransition($target)) {
            throw new \LogicException(
                "Transition not allowed from '{$this->value}' to '{$target->value}'."
            );
        }

        return $target;
    }
}
