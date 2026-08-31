<?php

declare(strict_types=1);

namespace Modules\SuperAdmin\App\Console\Commands;

use Illuminate\Console\Command;
use Modules\Core\App\Models\Tenant;
use Modules\SuperAdmin\App\Models\Invoice;

class GenerateMonthlyInvoicesCommand extends Command
{
    protected $signature = 'invoices:generate-monthly';

    protected $description = 'Gera a fatura do mes corrente para empresas com dia de vencimento configurado (idempotente)';

    public function handle(): int
    {
        $period = now()->format('Y-m');
        $generated = 0;

        Tenant::where('is_active', true)
            ->whereNotNull('billing_due_day')
            ->each(function (Tenant $tenant) use ($period, &$generated) {
                if ($tenant->billing_amount === null) {
                    $this->warn("Empresa #{$tenant->id} ({$tenant->name}) tem dia de vencimento mas nao tem valor configurado - pulando.");

                    return;
                }

                $alreadyExists = Invoice::where('tenant_id', $tenant->id)
                    ->where('reference_period', $period)
                    ->exists();

                if ($alreadyExists) {
                    return;
                }

                $day = min((int) $tenant->billing_due_day, now()->daysInMonth);
                $dueDate = now()->startOfMonth()->addDays($day - 1);

                Invoice::create([
                    'tenant_id' => $tenant->id,
                    'status' => 'pending',
                    'amount' => $tenant->billing_amount,
                    'due_date' => $dueDate,
                    'reference_period' => $period,
                    'reference' => "Mensalidade {$period}",
                ]);

                $generated++;
            });

        $this->info("Faturas geradas: {$generated}");

        return self::SUCCESS;
    }
}
