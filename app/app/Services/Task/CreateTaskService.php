<?php

declare(strict_types=1);

namespace App\Services\Task;

use App\Contracts\Repositories\Task\TaskRepository;
use App\DTOs\Task\CreateTaskDto;
use App\Jobs\NotifyTaskAssignment;

class CreateTaskService
{
    public function __construct(private readonly TaskRepository $repository)
    {
    }

    public function execute(CreateTaskDto $data): array
    {
        $task = $this->repository->create($data->toArray(), $data->getAssignedTo());

        if (!empty($task) && !empty($task['assigned_to'])) {
            NotifyTaskAssignment::dispatch($task['id'], $task['assigned_to']);
        }

        return $task;
    }
}