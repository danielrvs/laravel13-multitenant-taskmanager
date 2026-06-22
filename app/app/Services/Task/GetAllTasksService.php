<?php

declare(strict_types=1);

namespace App\Services\Task;

use App\Contracts\Repositories\Task\TaskRepository;

class GetAllTasksService
{

    public function __construct(
        private readonly TaskRepository $repository
    ) {}
    public function execute(): array
    {
        return $this->repository->index();
    }
}
