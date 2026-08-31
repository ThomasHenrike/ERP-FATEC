<?php

declare(strict_types=1);

namespace Modules\Vendas\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Cadastros\App\Models\Customer;
use Modules\Core\App\Services\TenantManager;
use Modules\Vendas\App\Models\Sale;
use Modules\Vendas\App\Services\SaleService;

class SaleController extends Controller
{
    public function __construct(
        private readonly SaleService $saleService
    ) {}

    public function index(Request $request): View
    {
        $query = Sale::query()
            ->with(['user', 'customer', 'seller', 'payments', 'items.product' => fn($q) => $q->withTrashed()])
            ->latest('id');

        if ($code = $request->input('code')) {
            $cleanCode = str_replace('#', '', $code);
            $query->where('code', $cleanCode);
        }

        if ($customerId = $request->input('customer_id')) {
            $query->where('customer_id', $customerId);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($startDate = $request->input('start_date')) {
            $query->whereDate('created_at', '>=', $startDate);
        }

        if ($endDate = $request->input('end_date')) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        $sales = $query->paginate(15)->withQueryString();
        $customers = Customer::active()->orderBy('name')->get();

        $totalCompleted = (float) Sale::where('status', 'completed')->sum('total_amount');
        $totalCanceled = (float) Sale::where('status', 'canceled')->sum('total_amount');
        $isManagerOrAbove = app(TenantManager::class)->hasRoleAtLeast('manager');

        return view('vendas::sales.index', compact('sales', 'customers', 'totalCompleted', 'totalCanceled', 'isManagerOrAbove'));
    }

    public function cancel(Request $request, Sale $sale): RedirectResponse
    {
        $request->validate([
            'cancel_reason' => ['required', 'string', 'max:500'],
        ]);

        $userId = (int) $request->user()->id;
        $reason = $request->input('cancel_reason');

        $this->saleService->cancelSale($sale, $reason, $userId);

        return redirect()
            ->route('vendas.sales.index')
            ->with('success', "Venda #{$sale->code} cancelada com sucesso. Estoque e financeiro estornados.");
    }
}