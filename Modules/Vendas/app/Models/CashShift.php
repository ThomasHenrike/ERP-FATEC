<?php

declare(strict_types=1);

namespace Modules\Vendas\App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\App\Traits\BelongsToTenant;

class CashShift extends Model
{
    use BelongsToTenant;

    protected $table = 'cash_shifts';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'status',
        'opened_at',
        'closed_at',
        'opening_balance',
        'closing_balance',
        'expected_balance',
        'difference_amount',
        'notes',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'closing_balance' => 'decimal:2',
        'expected_balance' => 'decimal:2',
        'difference_amount' => 'decimal:2',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(CashMovement::class, 'cash_shift_id');
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class, 'cash_shift_id');
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }
}