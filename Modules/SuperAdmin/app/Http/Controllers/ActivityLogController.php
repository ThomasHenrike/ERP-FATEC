<?php

declare(strict_types=1);

namespace Modules\SuperAdmin\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\SuperAdmin\App\Models\AdminActivityLog;

class ActivityLogController extends Controller
{
    private const IMPERSONATION_ACTIONS = ['impersonation_started', 'impersonation_ended', 'impersonated_action'];

    public function index(Request $request): View
    {
        $onlyImpersonation = $request->boolean('impersonation');
        $ticketId = $request->string('ticket')->toString();
        $empresa = $request->string('empresa')->toString();

        $logs = AdminActivityLog::query()
            ->with(['admin', 'tenant', 'ticket'])
            ->when($onlyImpersonation, fn ($q) => $q->whereIn('action', self::IMPERSONATION_ACTIONS))
            ->when($ticketId !== '', fn ($q) => $q->where('ticket_id', $ticketId))
            ->when($empresa !== '', fn ($q) => $q->whereHas('tenant', function ($tenantQuery) use ($empresa) {
                $tenantQuery->where('name', 'like', "%{$empresa}%")
                    ->orWhere('trade_name', 'like', "%{$empresa}%");
            }))
            ->orderByDesc('created_at')
            ->paginate(30)
            ->withQueryString();

        return view('superadmin::activity-logs.index', [
            'logs' => $logs,
            'onlyImpersonation' => $onlyImpersonation,
            'ticketId' => $ticketId,
            'empresa' => $empresa,
        ]);
    }
}
