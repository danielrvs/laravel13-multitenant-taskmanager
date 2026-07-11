<?php

declare(strict_types=1);

namespace App\Services\Task;

use App\Contracts\Repositories\Task\TaskRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class GetTaskByIdService
{
    public function __construct(
        private readonly TaskRepository $repository
    ) {}

    public function execute(int $id): ?array
    {
        $task = $this->repository->find($id);
        if (! $task) {
            throw new NotFoundHttpException('Task not found');
        }

        return $task;
    }
}
