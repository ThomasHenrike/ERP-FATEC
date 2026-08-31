<?php

declare(strict_types=1);

namespace Modules\Vendas\App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\App\Traits\BelongsToTenant;

class CashMovement extends Model
{
    use BelongsToTenant;

    protected $table = 'cash_movements';

    protected $fillable = [
        'tenant_id',
        'cash_shift_id',
        'user_id',
        'type',
        'amount',
        'reason',
        'authorized_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function shift(): BelongsTo
    {
        return $this->belongsTo(CashShift::class, 'cash_shift_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}