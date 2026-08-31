<?php

declare(strict_types=1);

namespace Modules\SuperAdmin\App\Console\Commands;

use Illuminate\Console\Command;
use Modules\SuperAdmin\App\Models\Invoice;

class MarkOverdueInvoicesCommand extends Command
{
    protected $signature = 'invoices:mark-overdue';

    protected $description = 'Marca como atrasada toda fatura pendente com vencimento no passado';

    public function handle(): int
    {
        $count = Invoice::where('status', 'pending')
            ->whereDate('due_date', '<', today())
            ->update(['status' => 'overdue']);

        $this->info("Faturas marcadas como atrasadas: {$count}");

        return self::SUCCESS;
    }
}
