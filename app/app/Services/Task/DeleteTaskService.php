<?php

declare(strict_types=1);

namespace App\Services\Task;

use App\Contracts\Repositories\Task\TaskRepository;

class DeleteTaskService
{
    public function __construct(private readonly TaskRepository $repository) {}

    public function execute(int $id): void
    {
        $this->repository->delete($id);
    }
}
