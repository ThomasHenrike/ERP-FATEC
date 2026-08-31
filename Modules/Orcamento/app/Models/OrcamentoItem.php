<?php

declare(strict_types=1);

namespace Modules\Orcamento\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\App\Traits\BelongsToTenant;
use Modules\Estoque\App\Models\Product;

class OrcamentoItem extends Model
{
    use BelongsToTenant;

    protected $table = 'orcamento_items';

    protected $fillable = [
        'tenant_id',
        'orcamento_id',
        'product_id',
        'quantity',
        'unit_price',
        'total_price',
        'icms_percent',
        'icms_st_value',
        'lead_time_days',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'icms_percent' => 'decimal:2',
        'icms_st_value' => 'decimal:2',
    ];

    public function orcamento(): BelongsTo
    {
        return $this->belongsTo(Orcamento::class, 'orcamento_id');
    }

    /**
     * Relacionamento com o produto incluindo itens arquivados (SoftDeletes).
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id')->withTrashed();
    }
}
