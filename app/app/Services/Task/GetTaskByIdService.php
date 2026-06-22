<?php

declare(strict_types=1);

namespace App\Services\Task;

use App\Contracts\Repositories\Task\TaskRepository;

class GetTaskByIdService 
{
    public function __construct(
        private readonly TaskRepository $repository
    ) {}

    public function execute(int $id): ?array
    {
        $task = $this->repository->find($id);
        if (!$task) {
            throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException('Task not found');
        }
        return $task;
    }
}