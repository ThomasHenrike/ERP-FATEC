<?php

declare(strict_types=1);

namespace Modules\Cadastros\App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\App\Traits\BelongsToTenant;
use Modules\Vendas\App\Models\CustomerReceivable;
use Modules\Vendas\App\Models\Sale;

class Customer extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $table = 'customers';

    protected $fillable = [
        'tenant_id',
        'person_type',
        'name',
        'trade_name',
        'document',
        'state_registration',
        'email',
        'phone',
        'whatsapp',
        'zip_code',
        'street',
        'number',
        'neighborhood',
        'city',
        'state',
        'ibge_city_code',
        'credit_limit',
        'default_price_table',
        'is_active',
    ];

    protected $casts = [
        'credit_limit' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public const PRICE_TABLE_LABELS = [
        'retail' => 'Varejo',
        'resale' => 'Revenda',
        'wholesale' => 'Atacado',
    ];

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class, 'customer_id');
    }

    public function receivables(): HasMany
    {
        return $this->hasMany(CustomerReceivable::class, 'customer_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function isJuridica(): bool
    {
        return $this->person_type === 'juridica';
    }

    public function getPriceTableLabelAttribute(): string
    {
        return self::PRICE_TABLE_LABELS[$this->default_price_table] ?? 'Varejo';
    }

    public function getDocumentLabelAttribute(): string
    {
        return $this->isJuridica() ? 'CNPJ' : 'CPF';
    }
}