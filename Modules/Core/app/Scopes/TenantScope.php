<?php

declare(strict_types=1);

namespace Modules\Core\App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Modules\Core\App\Services\TenantManager;

class TenantScope implements Scope
{
    /**
     * Injeta a clausula WHERE table.tenant_id = active_tenant_id em todas as consultas.
     */
    public function apply(Builder $builder, Model $model): void
    {
        $tenantManager = app(TenantManager::class);

        if ($tenantManager->hasTenant()) {
            $builder->where($model->qualifyColumn('tenant_id'), $tenantManager->getTenantId());
        }
    }
}