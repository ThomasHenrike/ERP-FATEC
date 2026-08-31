<?php

declare(strict_types=1);

namespace Modules\SuperAdmin\App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketMessage extends Model
{
    protected $fillable = [
        'ticket_id',
        'user_id',
        'admin_id',
        'body',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    public function isFromAdmin(): bool
    {
        return $this->admin_id !== null;
    }
}
