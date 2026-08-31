<?php

declare(strict_types=1);

namespace Modules\Orcamento\App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Cadastros\App\Models\Customer;
use Modules\Core\App\Traits\BelongsToTenant;
use Modules\Vendas\App\Models\Sale;

class Orcamento extends Model
{
    use BelongsToTenant, SoftDeletes;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_SENT = 'sent';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CONVERTED = 'converted';
    public const STATUS_EXPIRED = 'expired';

    public const STATUS_LABELS = [
        self::STATUS_DRAFT => 'Rascunho',
        self::STATUS_SENT => 'Enviado',
        self::STATUS_APPROVED => 'Aprovado',
        self::STATUS_REJECTED => 'Rejeitado',
        self::STATUS_CONVERTED => 'Convertido em Venda',
        self::STATUS_EXPIRED => 'Expirado',
    ];

    protected $table = 'orcamentos';

    protected $fillable = [
        'tenant_id',
        'code',
        'customer_id',
        'user_id',
        'status',
        'payment_condition',
        'delivery_term',
        'freight_info',
        'subtotal',
        'total_icms_st',
        'total_amount',
        'valid_until',
        'notes',
        'converted_sale_id',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'total_icms_st' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'valid_until' => 'date',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $orcamento): void {
            if (empty($orcamento->code) && !empty($orcamento->tenant_id)) {
                $maxCode = (int) static::withoutGlobalScopes()
                    ->where('tenant_id', $orcamento->tenant_id)
                    ->max('code');

                $orcamento->code = $maxCode + 1;
            }
        });
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id')->withTrashed();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrcamentoItem::class, 'orcamento_id');
    }

    public function convertedSale(): BelongsTo
    {
        return $this->belongsTo(Sale::class, 'converted_sale_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function isEditable(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_SENT], true);
    }

    public function isConverted(): bool
    {
        return $this->status === self::STATUS_CONVERTED;
    }
}
