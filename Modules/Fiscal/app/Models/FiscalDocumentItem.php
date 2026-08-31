<?php

declare(strict_types=1);

namespace Modules\Fiscal\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\App\Traits\BelongsToTenant;
use Modules\Estoque\App\Models\Product;

/**
 * Copia independente do item da Sale, editavel na revisao da NF-e sem
 * tocar no pedido/estoque originais.
 */
class FiscalDocumentItem extends Model
{
    use BelongsToTenant;

    protected $table = 'fiscal_document_items';

    protected $fillable = [
        'tenant_id',
        'fiscal_document_id',
        'product_id',
        'quantity',
        'unit_price',
        'discount_amount',
        'total_price',
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

    public function fiscalDocument(): BelongsTo
    {
        return $this->belongsTo(FiscalDocument::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }
}
