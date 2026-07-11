<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use App\Services\Tenant\TenantManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TenantScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        $manager = app(TenantManager::class);

        if ($manager->hasTenant()) {
            $tenantId = $manager->getTenantId();
            $builder->where($model->getTable().'.tenant_id', $tenantId);
        }
    }
}
