<?php

declare(strict_types=1);

namespace Modules\Estoque\App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\Core\App\Traits\BelongsToTenant;

class StockMovement extends Model
{
    use BelongsToTenant;

    protected $table = 'stock_movements';

    public const TYPE_IN = 'in';
    public const TYPE_OUT = 'out';
    public const TYPE_ADJUSTMENT = 'adjustment';
    public const TYPE_LOSS = 'loss';
    public const TYPE_RETURN = 'return';

    protected $fillable = [
        'tenant_id',
        'product_id',
        'user_id',
        'type',
        'quantity',
        'unit_price',
        'previous_stock',
        'current_stock',
        'reference_type',
        'reference_id',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_price' => 'decimal:2',
        'previous_stock' => 'decimal:3',
        'current_stock' => 'decimal:3',
        'reference_id' => 'integer',
    ];

    /**
     * Produto vinculado incluindo itens desativados (SoftDeletes).
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id')->withTrashed();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function reference(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'reference_type', 'reference_id');
    }
}