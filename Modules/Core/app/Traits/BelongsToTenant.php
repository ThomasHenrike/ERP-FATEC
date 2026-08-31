<?php

declare(strict_types=1);

namespace Modules\Core\App\Traits;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\App\Models\Tenant;
use Modules\Core\App\Scopes\TenantScope;
use Modules\Core\App\Services\TenantManager;

trait BelongsToTenant
{
    /**
     * Inicializa a trait aplicando o escopo global e preenchendo o tenant_id na criacao.
     */
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope());

        static::creating(function ($model): void {
            $tenantManager = app(TenantManager::class);

            if ($tenantManager->hasTenant() && empty($model->tenant_id)) {
                $model->tenant_id = $tenantManager->getTenantId();
            }
        });
    }

    /**
     * Relacionamento com a empresa proprietaria do registro.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }
}