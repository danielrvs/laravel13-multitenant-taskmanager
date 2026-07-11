<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories\Task;

use App\Contracts\Repositories\Task\TaskRepository;
use App\Models\Task\Task;

class EloquentTaskRepository implements TaskRepository
{
    public function __construct(private readonly Task $model) {}

    public function create(array $data, ?int $assigned_to = null): array
    {
        $task = $this->model->create($data);
        if ($assigned_to !== null) {
            $task->assignTo($assigned_to);
        }

        return $task->toArray();
    }

    public function update(int $id, array $data): void
    {
        $record = $this->model->findOrFail($id);
        $record->update($data);
    }

    public function delete(int $id): void
    {
        $record = $this->model->findOrFail($id);
        $record->delete();
    }

    public function find(int $id): ?array
    {
        $task = $this->model->find($id);

        return $task ? $task->toArray() : null;
    }

    public function findAllByUserId(int $userId): array
    {
        return $this->model->where('user_id', $userId)->get()->toArray();
    }

    public function index(): array
    {
        return $this->model->all()->toArray();
    }
}
