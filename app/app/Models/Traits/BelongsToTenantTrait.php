<?php

declare(strict_types=1);

namespace App\Models\Traits;

use App\Models\Scopes\TenantScope;
use App\Services\Tenant\TenantManager;

trait BelongsToTenantTrait
{
    public static function bootBelongsToTenantTrait(): void
    {
        static::addGlobalScope(new TenantScope());

        static::creating(function($model) {
            $manager = app(TenantManager::class);
            if($manager->hasTenant() && !$model->tenant_id) {
                $model->tenant_id = $manager->getTenantId();
            }
        });
    }
}
