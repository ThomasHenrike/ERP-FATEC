<?php

declare(strict_types=1);

namespace Modules\SuperAdmin\App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\App\Models\Tenant;

class Invoice extends Model
{
    protected $fillable = [
        'tenant_id',
        'status',
        'amount',
        'due_date',
        'paid_at',
        'reference_period',
        'reference',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'due_date' => 'date',
        'paid_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('status', 'overdue');
    }

    public function markAsPaid(): void
    {
        $this->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);
    }
}
