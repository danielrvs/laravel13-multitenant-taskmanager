<?php

declare(strict_types=1);

namespace App\Services\Task;

use App\Contracts\Repositories\Task\TaskRepository;
use App\DTOs\Task\UpdateTaskDto;

class UpdateTaskService
{
    public function __construct(
        private readonly TaskRepository $taskRepository
    ) {}

    public function execute(int $id, UpdateTaskDto $dto): void
    {
        $this->taskRepository->update($id, $dto->toArray());
    }
}
