<?php

declare(strict_types=1);

namespace App\DTOs\Task;

use App\DTOs\AbstractDto;
use App\Models\Task\Vo\TaskPriority;
use App\Models\Task\Vo\TaskStatus;

final readonly class CreateTaskDto extends AbstractDto
{
    public function __construct(
        public readonly ?string $title,
        public readonly ?string $description,
        public readonly TaskStatus|string|null $status,
        public readonly TaskPriority|string|null $priority,
        public readonly ?string $due_date,
        private readonly ?int $assigned_to,
        public array $presentFields = []
    ) {}

    public function getAssignedTo(): ?int
    {
        return $this->assigned_to;
    }
}
