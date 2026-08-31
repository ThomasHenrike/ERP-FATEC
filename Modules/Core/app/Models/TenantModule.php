<?php

declare(strict_types=1);

namespace Modules\Core\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantModule extends Model
{
    protected $table = 'tenant_modules';

    protected $fillable = [
        'tenant_id',
        'module',
        'is_enabled',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
