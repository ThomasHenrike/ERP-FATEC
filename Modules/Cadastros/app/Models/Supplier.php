<?php

declare(strict_types=1);

namespace Modules\Cadastros\App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\App\Traits\BelongsToTenant;

class Supplier extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $table = 'suppliers';

    protected $fillable = [
        'tenant_id',
        'name',
        'trade_name',
        'document',
        'state_registration',
        'email',
        'phone',
        'whatsapp',
        'contact_name',
        'zip_code',
        'street',
        'number',
        'neighborhood',
        'city',
        'state',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}