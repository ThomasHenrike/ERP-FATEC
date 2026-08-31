<?php

declare(strict_types=1);

namespace Modules\Core\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\View\View;
use Modules\Cadastros\App\Models\Customer;
use Modules\Cadastros\App\Models\Employee;
use Modules\Cadastros\App\Models\Supplier;
use Modules\Core\App\Models\Tenant;
use Modules\Estoque\App\Models\Product;
use Modules\SuperAdmin\App\Models\Invoice;
use Modules\Vendas\App\Models\CashShift;
use Modules\Vendas\App\Models\CustomerReceivable;
use Modules\Vendas\App\Models\Sale;

class DashboardController extends Controller
{
    public function index(): View
    {
        /** @var Tenant $tenant */
        $tenant = app('currentTenant');

        $totalProducts = Product::count();
        $lowStockCount = Product::lowStock()->count();

        $todaySalesTotal = (float) Sale::where('status', 'completed')
            ->whereDate('created_at', Carbon::today())
            ->sum('total_amount');
        $todaySalesCount = Sale::where('status', 'completed')
            ->whereDate('created_at', Carbon::today())
            ->count();

        $totalReceivablesPending = (float) CustomerReceivable::where('status', 'pending')->sum('amount');
        
        $activeEmployeesCount = Employee::active()->count();
        $totalCustomers = Customer::active()->count();
        $totalSuppliers = Supplier::active()->count();

        // Identifica sessoes de caixa abertas no tenant e seus operadores
        $openCashShifts = CashShift::where('status', 'open')
            ->with('user')
            ->latest('opened_at')
            ->get();

        // Fatura mais proxima do vencimento (pendente ou ja vencida), pra avisar o admin
        $nextDueInvoice = Invoice::where('tenant_id', $tenant->id)
            ->whereIn('status', ['pending', 'overdue'])
            ->orderBy('due_date')
            ->first();

        return view('core::dashboard', compact(
            'tenant',
            'totalProducts',
            'lowStockCount',
            'todaySalesTotal',
            'todaySalesCount',
            'totalReceivablesPending',
            'activeEmployeesCount',
            'totalCustomers',
            'totalSuppliers',
            'openCashShifts',
            'nextDueInvoice'
        ));
    }
}