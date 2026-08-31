<?php

declare(strict_types=1);

namespace Modules\Core\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\View\View;
use Modules\Core\App\Models\Tenant;
use Modules\SuperAdmin\App\Models\Invoice;

class FinanceiroController extends Controller
{
    public function index(): View
    {
        /** @var Tenant $tenant */
        $tenant = app('currentTenant');

        $invoices = Invoice::where('tenant_id', $tenant->id)
            ->orderByDesc('due_date')
            ->paginate(15);

        $nextDue = Invoice::where('tenant_id', $tenant->id)
            ->whereIn('status', ['pending', 'overdue'])
            ->orderBy('due_date')
            ->first();

        return view('core::financeiro.index', compact('tenant', 'invoices', 'nextDue'));
    }
}
