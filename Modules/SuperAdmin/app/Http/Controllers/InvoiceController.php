<?php

declare(strict_types=1);

namespace Modules\SuperAdmin\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Core\App\Models\Tenant;
use Modules\SuperAdmin\App\Models\AdminActivityLog;
use Modules\SuperAdmin\App\Models\Invoice;

class InvoiceController extends Controller
{
    public function index(Request $request): View
    {
        $invoices = Invoice::query()
            ->with('tenant')
            ->when($request->string('status')->toString(), fn ($q, $status) => $q->where('status', $status))
            ->orderByDesc('due_date')
            ->paginate(20)
            ->withQueryString();

        return view('superadmin::invoices.index', [
            'invoices' => $invoices,
            'status' => $request->string('status')->toString(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tenant_id' => ['required', 'exists:tenants,id'],
            'amount' => ['required', 'numeric', 'min:0'],
            'due_date' => ['required', 'date'],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $tenant = Tenant::findOrFail($validated['tenant_id']);

        $invoice = Invoice::create([
            'tenant_id' => $tenant->id,
            'status' => 'pending',
            'amount' => $validated['amount'],
            'due_date' => $validated['due_date'],
            'reference' => $validated['reference'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        AdminActivityLog::record(
            $request->user('admin'),
            'invoice_created',
            $tenant,
            "Fatura de R$ {$invoice->amount} criada, vencimento {$invoice->due_date->format('d/m/Y')}."
        );

        return back()->with('success', 'Fatura criada.');
    }

    public function markPaid(Request $request, Invoice $invoice): RedirectResponse
    {
        $invoice->markAsPaid();

        AdminActivityLog::record(
            $request->user('admin'),
            'invoice_marked_paid',
            $invoice->tenant,
            "Fatura #{$invoice->id} (R$ {$invoice->amount}) marcada como paga."
        );

        return back()->with('success', 'Fatura marcada como paga.');
    }
}
