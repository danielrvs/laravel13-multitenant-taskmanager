<?php

declare(strict_types=1);

namespace App\Contracts\Repositories\Task;

interface TaskRepository
{
    public function create(array $data, ?int $assigned_to = null): array;

    public function update(int $id, array $data): void;

    public function delete(int $id): void;

    public function find(int $id): ?array;

    public function findAllByUserId(int $userId): array;

    public function index(): array;
}
