<?php

declare(strict_types=1);

namespace Modules\SuperAdmin\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\App\Models\Tenant;

class AdminActivityLog extends Model
{
    protected $fillable = [
        'admin_id',
        'action',
        'tenant_id',
        'ticket_id',
        'description',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public static function record(Admin $admin, string $action, ?Tenant $tenant, string $description, ?Ticket $ticket = null): void
    {
        static::create([
            'admin_id' => $admin->id,
            'action' => $action,
            'tenant_id' => $tenant?->id,
            'ticket_id' => $ticket?->id,
            'description' => $description,
        ]);
    }
}
