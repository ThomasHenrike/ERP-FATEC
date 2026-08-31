<?php

declare(strict_types=1);

namespace Modules\Vendas\App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Cadastros\App\Models\Customer;
use Modules\Cadastros\App\Models\Employee;
use Modules\Core\App\Traits\BelongsToTenant;

class Sale extends Model
{
    use BelongsToTenant;

    protected $table = 'sales';

    protected $fillable = [
        'tenant_id',
        'code',
        'user_id',
        'cash_shift_id',
        'seller_id',
        'customer_id',
        'status',
        'canceled_at',
        'cancel_reason',
        'price_table',
        'subtotal',
        'discount_amount',
        'total_amount',
        'amount_received',
        'change_amount',
        'payment_method',
        'notes',
    ];

    protected $casts = [
        'code' => 'integer',
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'amount_received' => 'decimal:2',
        'change_amount' => 'decimal:2',
        'canceled_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $sale): void {
            if (empty($sale->code) && !empty($sale->tenant_id)) {
                $maxCode = (int) static::withoutGlobalScopes()
                    ->where('tenant_id', $sale->tenant_id)
                    ->max('code');
                
                $sale->code = $maxCode + 1;
            }
        });
    }

    /**
     * Acessor centralizado para formatacao do metodo de pagamento em portugues.
     */
    public function getPaymentMethodFormattedAttribute(): string
    {
        return match ($this->payment_method) {
            'money' => 'Dinheiro',
            'pix' => 'PIX',
            'credit_card' => 'Cartao de Credito',
            'debit_card' => 'Cartao de Debito',
            'on_account' => 'A Prazo (Crediario)',
            'split' => 'Multiplos Pagamentos',
            default => ucfirst($this->payment_method ?? 'Nao informado'),
        };
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class, 'sale_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SalePayment::class, 'sale_id');
    }

    public function receivables(): HasMany
    {
        return $this->hasMany(CustomerReceivable::class, 'sale_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id')->withTrashed();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'seller_id');
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(CashShift::class, 'cash_shift_id');
    }

    public function isCanceled(): bool
    {
        return $this->status === 'canceled';
    }
}