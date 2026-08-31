<?php

declare(strict_types=1);

namespace Modules\Vendas\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Cadastros\App\Models\Customer;
use Modules\Vendas\App\Models\CustomerReceivable;

class ReceivableController extends Controller
{
    public function index(Request $request): View
    {
        $query = CustomerReceivable::query()
            ->with(['customer', 'sale.items.product' => fn($q) => $q->withTrashed(), 'sale.user'])
            ->latest('due_date');

        if ($customerId = $request->input('customer_id')) {
            $query->where('customer_id', $customerId);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($startDate = $request->input('start_date')) {
            $query->whereDate('due_date', '>=', $startDate);
        }

        if ($endDate = $request->input('end_date')) {
            $query->whereDate('due_date', '<=', $endDate);
        }

        $receivables = $query->paginate(20)->withQueryString();
        $customers = Customer::active()->orderBy('name')->get();

        $totalPending = (float) CustomerReceivable::where('status', 'pending')->sum('amount');
        $totalOverdue = (float) CustomerReceivable::where('status', 'pending')
            ->where('due_date', '<', Carbon::today())
            ->sum('amount');

        $totalCustomersInDebt = CustomerReceivable::where('status', 'pending')
            ->distinct('customer_id')
            ->count('customer_id');

        return view('vendas::prazos.index', compact(
            'receivables',
            'customers',
            'totalPending',
            'totalOverdue',
            'totalCustomersInDebt'
        ));
    }

    public function settle(CustomerReceivable $receivable): RedirectResponse
    {
        $receivable->update([
            'status' => 'paid',
            'paid_amount' => $receivable->amount,
            'paid_at' => Carbon::now(),
        ]);

        return redirect()
            ->route('vendas.prazos.index')
            ->with('success', "Parcela #{$receivable->installment_number}/{$receivable->total_installments} do cliente {$receivable->customer?->name} quitada com sucesso.");
    }

}