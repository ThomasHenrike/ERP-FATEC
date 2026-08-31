<?php

declare(strict_types=1);

namespace Modules\Vendas\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\App\Traits\BelongsToTenant;
use Modules\Estoque\App\Models\Product;

class SaleItem extends Model
{
    use BelongsToTenant;

    protected $table = 'sale_items';

    protected $fillable = [
        'tenant_id',
        'sale_id',
        'product_id',
        'quantity',
        'unit_price',
        'discount_amount',
        'total_price',
        // Retrato fiscal do produto no momento da venda (ver migration
        // add_tax_snapshot_to_sale_items_table) - usado pra emitir NF-e
        // fielmente correta mesmo que o cadastro do produto mude depois.
        'ncm',
        'cest',
        'origin',
        'cfop',
        'csosn',
        'cst_icms',
        'cst_pis',
        'cst_cofins',
        'icms_rate',
        'pis_rate',
        'cofins_rate',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_price' => 'decimal:2',
        'origin' => 'integer',
        'icms_rate' => 'decimal:2',
        'pis_rate' => 'decimal:2',
        'cofins_rate' => 'decimal:2',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class, 'sale_id');
    }

    /**
     * Relacionamento com o produto no modulo Estoque incluindo itens arquivados (SoftDeletes).
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id')->withTrashed();
    }
}