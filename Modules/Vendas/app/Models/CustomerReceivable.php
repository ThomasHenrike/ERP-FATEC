<?php

declare(strict_types=1);

namespace Modules\Vendas\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Cadastros\App\Models\Customer;
use Modules\Core\App\Traits\BelongsToTenant;

class CustomerReceivable extends Model
{
    use BelongsToTenant;

    protected $table = 'customer_receivables';

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'sale_id',
        'installment_number',
        'total_installments',
        'amount',
        'paid_amount',
        'due_date',
        'paid_at',
        'status',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'installment_number' => 'integer',
        'total_installments' => 'integer',
        'due_date' => 'date',
        'paid_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id')->withTrashed();
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class, 'sale_id');
    }
}