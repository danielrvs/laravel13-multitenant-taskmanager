<?php

declare(strict_types=1);

namespace App\Services\Tenant;

class TenantManager
{
    private ?int $tenantId = null;

    public function setTenantId(int $tenantId): void
    {
        $this->tenantId = $tenantId;
    }

    public function getTenantId(): ?int
    {
        return $this->tenantId;
    }

    public function hasTenant(): bool
    {
        return $this->tenantId !== null;
    }
}