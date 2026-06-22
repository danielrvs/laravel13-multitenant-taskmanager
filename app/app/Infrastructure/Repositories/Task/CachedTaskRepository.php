<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories\Task;

use App\Contracts\Repositories\Task\TaskRepository;
use App\Services\Tenant\TenantManager;
use Illuminate\Support\Facades\Cache;

class CachedTaskRepository implements TaskRepository
{
    public function __construct(
        private readonly EloquentTaskRepository $next,
        private readonly TenantManager $tenantManager
    ) {
    }

    private function cacheKey(): string
    {
        $tenantId = $this->tenantManager->getTenantId();
        return "tenant_{$tenantId}_tasks";
    }

    public function index(): array
    {
        $cacheKey = $this->cacheKey();
        return Cache::remember($cacheKey, 3600, function () {
            return $this->next->index();
        });
    }

    public function create(array $data, ?int $assigned_to = null): array
    {
        $cacheKey = $this->cacheKey();
        Cache::forget($cacheKey);
        $task = $this->next->create($data, $assigned_to);
        $cacheKeyId = $cacheKey . "_{$task['id']}";
        Cache::put($cacheKeyId, $task, 3600);
        return $task;
    }

    public function delete(int $id): void
    {
        $this->next->delete($id);
        $cacheKey = $this->cacheKey();
        $cacheKeyId = $cacheKey . "_{$id}";
        Cache::forget($cacheKey);
        Cache::forget($cacheKeyId);
    }

    public function update(int $id, array $data): void
    {
        $this->next->update($id, $data);
        $cacheKey = $this->cacheKey();
        $cacheKeyId = $cacheKey . "_{$id}";
        Cache::forget($cacheKey);
        Cache::forget($cacheKeyId);
    }

    public function find(int $id): ?array
    {
        $cacheKeyId = $this->cacheKey() . "_{$id}";
        return Cache::remember($cacheKeyId, 3600, function () use ($id) {
            return $this->next->find($id);
        });
    }

    public function findAllByUserId(int $userId): array
    {
        return $this->next->findAllByUserId($userId);
    }
}
