<?php

declare(strict_types=1);

namespace Modules\Estoque\App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Cadastros\App\Models\Supplier;
use Modules\Core\App\Traits\BelongsToTenant;

class Product extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $table = 'products';

    protected $fillable = [
        'tenant_id',
        'code',
        'category_id',
        'supplier_id',
        'name',
        'brand',
        'sku',
        'barcode',
        'unit',
        'cost_price',
        'margin_retail',
        'price_retail',
        'margin_resale',
        'price_resale',
        'margin_wholesale',
        'price_wholesale',
        'min_stock',
        'location',
        'warranty_months',
        'ncm',
        'cest',
        'origin',
        'cfop_default',
        'csosn',
        'cst_icms',
        'cst_pis',
        'cst_cofins',
        'icms_rate',
        'pis_rate',
        'cofins_rate',
        'is_active',
    ];

    protected $casts = [
        'code' => 'integer',
        'cost_price' => 'decimal:2',
        'margin_retail' => 'decimal:2',
        'price_retail' => 'decimal:2',
        'margin_resale' => 'decimal:2',
        'price_resale' => 'decimal:2',
        'margin_wholesale' => 'decimal:2',
        'price_wholesale' => 'decimal:2',
        'min_stock' => 'decimal:3',
        'current_stock' => 'decimal:3',
        'origin' => 'integer',
        'warranty_months' => 'integer',
        'icms_rate' => 'decimal:2',
        'pis_rate' => 'decimal:2',
        'cofins_rate' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $product): void {
            if (empty($product->code) && !empty($product->tenant_id)) {
                $maxCode = (int) static::withoutGlobalScopes()
                    ->where('tenant_id', $product->tenant_id)
                    ->max('code');
                
                $product->code = $maxCode + 1;
            }
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    /**
     * Relacionamento com o Fornecedor Padrao do modulo Cadastros.
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'product_id');
    }

    public function supplierProducts(): HasMany
    {
        return $this->hasMany(SupplierProduct::class, 'product_id');
    }

    /**
     * Retorna o preco correspondente a tabela solicitada.
     * Se Revenda ou Atacado forem nulos ou zero, adota o Preco de Varejo (nunca o Custo).
     */
    public function getPriceForTable(string $priceTable = 'retail'): float
    {
        $retailPrice = (float) $this->price_retail;
        $resalePrice = (float) $this->price_resale;
        $wholesalePrice = (float) $this->price_wholesale;

        return match ($priceTable) {
            'resale' => $resalePrice > 0 ? $resalePrice : $retailPrice,
            'wholesale' => $wholesalePrice > 0 ? $wholesalePrice : $retailPrice,
            default => $retailPrice,
        };
    }

    public function isLowStock(): bool
    {
        $minStock = (float) $this->min_stock;
        $currentStock = (float) $this->current_stock;

        return $minStock > 0 && $currentStock <= $minStock;
    }

    public function isOutOfStock(): bool
    {
        return (float) $this->current_stock <= 0;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeLowStock(Builder $query): Builder
    {
        return $query->whereColumn('current_stock', '<=', 'min_stock');
    }
}