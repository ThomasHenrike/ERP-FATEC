<?php

declare(strict_types=1);

namespace Modules\SuperAdmin\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Modules\Core\App\Models\Tenant;
use Modules\SuperAdmin\App\Models\Invoice;
use Modules\SuperAdmin\App\Support\DataVolumeCalculator;

class DashboardController extends Controller
{
    private const MONTH_LABELS = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];

    private const MONTHS_BACK = 6;

    public function index(): View
    {
        $mrr = (float) Tenant::where('is_active', true)->whereNotNull('billing_amount')->sum('billing_amount');

        $activeTenants = Tenant::where('is_active', true)->count();
        $totalTenants = Tenant::count();

        $invoiceTotals = Invoice::selectRaw('status, SUM(amount) as total, COUNT(*) as qty')
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        $paidAmount = (float) ($invoiceTotals->get('paid')->total ?? 0);
        $pendingAmount = (float) ($invoiceTotals->get('pending')->total ?? 0);
        $overdueAmount = (float) ($invoiceTotals->get('overdue')->total ?? 0);

        $revenueByMonth = $this->monthlySeries(
            Invoice::where('status', 'paid')->whereNotNull('paid_at')
                ->where('paid_at', '>=', now()->subMonths(self::MONTHS_BACK - 1)->startOfMonth())
                ->get(),
            fn ($invoice) => Carbon::parse($invoice->paid_at),
            fn ($invoices) => (float) $invoices->sum('amount')
        );

        $tenantsByMonth = $this->monthlySeries(
            Tenant::where('created_at', '>=', now()->subMonths(self::MONTHS_BACK - 1)->startOfMonth())->get(),
            fn ($tenant) => Carbon::parse($tenant->created_at),
            fn ($tenants) => $tenants->count()
        );

        $totalDbBytes = DataVolumeCalculator::totalDatabaseBytes();
        $totalStorageBytes = DataVolumeCalculator::totalStorageBytes();

        return view('superadmin::dashboard.index', [
            'mrr' => $mrr,
            'activeTenants' => $activeTenants,
            'totalTenants' => $totalTenants,
            'paidAmount' => $paidAmount,
            'pendingAmount' => $pendingAmount,
            'overdueAmount' => $overdueAmount,
            'revenueByMonth' => $revenueByMonth,
            'tenantsByMonth' => $tenantsByMonth,
            'totalDbBytes' => $totalDbBytes,
            'totalStorageBytes' => $totalStorageBytes,
        ]);
    }

    /**
     * Monta uma serie mensal continua (ultimos MONTHS_BACK meses, preenchendo
     * com zero o mes sem registro) a partir de uma coleção ja carregada,
     * agrupando por Y-m no PHP - evita SQL especifico de driver (o projeto
     * roda SQLite tanto local quanto em producao).
     *
     * @return array<int, array{label: string, value: float|int}>
     */
    private function monthlySeries(\Illuminate\Support\Collection $items, \Closure $dateResolver, \Closure $aggregator): array
    {
        $grouped = $items->groupBy(fn ($item) => $dateResolver($item)->format('Y-m'));

        $series = [];
        for ($i = self::MONTHS_BACK - 1; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $key = $month->format('Y-m');
            $bucket = $grouped->get($key, collect());

            $series[] = [
                'label' => self::MONTH_LABELS[$month->month - 1] . '/' . $month->format('y'),
                'value' => $aggregator($bucket),
            ];
        }

        return $series;
    }
}
