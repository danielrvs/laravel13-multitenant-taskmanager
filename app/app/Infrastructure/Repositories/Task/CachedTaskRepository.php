<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories\Task;

use App\Contracts\Repositories\Task\TaskRepository;
use App\Services\Tenant\TenantManager;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Cache\Repository as CacheRepository;

class CachedTaskRepository implements TaskRepository
{
    public function __construct(
        private readonly TaskRepository $next,
        private readonly TenantManager $tenantManager,
        private readonly CacheRepository $cache
    ) {}

    private function cacheKey(): string
    {
        $tenantId = $this->tenantManager->getTenantId();

        return "tenant_{$tenantId}_tasks";
    }

    public function index(): array
    {
        $cacheKey = $this->cacheKey();
        $data = $this->cache->get($cacheKey);

        if ($data !== null) {
            return $data;
        }

        if (! $this->cache instanceof LockProvider) {
            return $this->next->index();
        }

        return $this->cache->lock($cacheKey.':lock', 10)->block(5, function () use ($cacheKey) {
            $data = $this->cache->get($cacheKey);
            if ($data !== null) {
                return $data;
            }

            $freshData = $this->next->index();
            $this->cache->put($cacheKey, $freshData, 3600);

            return $freshData;
        });
    }

    public function create(array $data, ?int $assigned_to = null): array
    {
        $cacheKey = $this->cacheKey();
        $this->cache->forget($cacheKey);
        $task = $this->next->create($data, $assigned_to);
        $cacheKeyId = $cacheKey."_{$task['id']}";
        $this->cache->put($cacheKeyId, $task, 3600);

        return $task;
    }

    public function delete(int $id): void
    {
        $this->next->delete($id);
        $cacheKey = $this->cacheKey();
        $cacheKeyId = $cacheKey."_{$id}";
        $this->cache->forget($cacheKey);
        $this->cache->forget($cacheKeyId);
    }

    public function update(int $id, array $data): void
    {
        $this->next->update($id, $data);
        $cacheKey = $this->cacheKey();
        $cacheKeyId = $cacheKey."_{$id}";
        $this->cache->forget($cacheKey);
        $this->cache->forget($cacheKeyId);
    }

    public function find(int $id): ?array
    {
        $cacheKeyId = $this->cacheKey()."_{$id}";

        return $this->cache->remember($cacheKeyId, 3600, function () use ($id) {
            return $this->next->find($id);
        });
    }

    public function findAllByUserId(int $userId): array
    {
        return $this->next->findAllByUserId($userId);
    }
}
