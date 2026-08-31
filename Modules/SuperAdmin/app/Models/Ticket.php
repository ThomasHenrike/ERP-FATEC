<?php

declare(strict_types=1);

namespace Modules\SuperAdmin\App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Core\App\Models\Tenant;

class Ticket extends Model
{
    protected $fillable = [
        'tenant_id',
        'user_id',
        'subject',
        'status',
        'last_message_at',
        'closed_at',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(TicketMessage::class)->orderBy('created_at');
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(TicketMessage::class)->latestOfMany();
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', 'open');
    }

    public function scopeClosed(Builder $query): Builder
    {
        return $query->where('status', 'closed');
    }

    /**
     * Precisa de resposta do admin quando a ultima mensagem foi de um
     * usuario da empresa - nao e' um status a parte, e' derivado.
     */
    public function needsReply(): bool
    {
        $last = $this->relationLoaded('latestMessage') ? $this->latestMessage : $this->latestMessage()->first();

        return $last !== null && $last->admin_id === null;
    }

    public function close(): void
    {
        $this->update([
            'status' => 'closed',
            'closed_at' => now(),
        ]);
    }

    /**
     * Contagem simples de chamados abertos aguardando resposta - varre so os
     * chamados abertos (volume baixo no estagio atual do produto), nao vale
     * a pena uma query mais elaborada antes disso realmente crescer.
     */
    public static function countNeedingReply(): int
    {
        return static::open()
            ->with('latestMessage')
            ->get()
            ->filter(fn (self $ticket) => $ticket->needsReply())
            ->count();
    }
}
