<?php

declare(strict_types=1);

namespace App\DTOs\Task;

use App\DTOs\AbstractDto;
use App\Models\Task\Vo\TaskPriority;

final readonly class UpdateTaskDto extends AbstractDto
{
    public function __construct(
        public readonly ?string $title,
        public readonly ?string $description,
        public readonly TaskPriority|string|null $priority,
        public readonly ?string $status,
        public readonly ?string $due_date,
        public readonly ?int $assigned_to,
        public array $presentFields = []
    ) {}
}
