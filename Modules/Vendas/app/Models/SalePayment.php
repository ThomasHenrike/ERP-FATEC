<?php

declare(strict_types=1);

namespace Modules\Vendas\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\App\Traits\BelongsToTenant;

class SalePayment extends Model
{
    use BelongsToTenant;

    protected $table = 'sale_payments';

    protected $fillable = [
        'tenant_id',
        'sale_id',
        'payment_method',
        'amount',
        'installments',
        'due_date',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'installments' => 'integer',
        'due_date' => 'date',
    ];

    /**
     * Traducao centralizada do metodo de pagamento individual.
     */
    public function getMethodFormattedAttribute(): string
    {
        return match ($this->payment_method) {
            'money' => 'Dinheiro',
            'pix' => 'PIX',
            'credit_card' => 'Cartao de Credito',
            'debit_card' => 'Cartao de Debito',
            'on_account' => 'A Prazo (Crediario)',
            default => ucfirst($this->payment_method ?? 'Nao informado'),
        };
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class, 'sale_id');
    }
}