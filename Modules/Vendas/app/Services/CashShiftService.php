<?php

declare(strict_types=1);

namespace Modules\Vendas\App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Vendas\App\Exceptions\CashShiftAlreadyOpenException;
use Modules\Vendas\App\Models\CashMovement;
use Modules\Vendas\App\Models\CashShift;
use Modules\Vendas\App\Models\SalePayment;

class CashShiftService
{
    /**
     * Abre uma nova sessao de caixa com fundo de troco inicial.
     *
     * @throws CashShiftAlreadyOpenException se outra pessoa ja tiver um turno aberto
     */
    public function openShift(int $tenantId, int $userId, float $openingBalance, ?string $notes = null): CashShift
    {
        // So' um caixa aberto por vez, por empresa (uma gaveta fisica) - nao
        // e' por operador, senao dois logins conseguem abrir 2 caixas ao
        // mesmo tempo sem perceber.
        $activeShift = CashShift::where('tenant_id', $tenantId)
            ->where('status', 'open')
            ->first();

        if ($activeShift) {
            if ($activeShift->user_id === $userId) {
                return $activeShift;
            }

            throw new CashShiftAlreadyOpenException($activeShift);
        }

        return CashShift::create([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'status' => 'open',
            'opened_at' => Carbon::now(),
            'opening_balance' => $openingBalance,
            'expected_balance' => $openingBalance,
            'difference_amount' => 0.00,
            'notes' => $notes,
        ]);
    }

    /**
     * Registra sangria (retirada) ou suprimento (reforco de troco) na gaveta.
     */
    public function recordMovement(
        int $tenantId,
        int $userId,
        int $shiftId,
        string $type,
        float $amount,
        string $reason,
        ?string $authorizedBy = null
    ): CashMovement {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('O valor da movimentacao de gaveta deve ser positivo.');
        }

        return CashMovement::create([
            'tenant_id' => $tenantId,
            'cash_shift_id' => $shiftId,
            'user_id' => $userId,
            'type' => $type,
            'amount' => $amount,
            'reason' => $reason,
            'authorized_by' => $authorizedBy,
        ]);
    }

    /**
     * Encerra o turno de caixa realizando a conciliacao contabil de valores.
     */
    public function closeShift(CashShift $shift, float $countedBalance, ?string $notes = null): CashShift
    {
        return DB::transaction(function () use ($shift, $countedBalance, $notes): CashShift {
            $summary = $this->getSummary($shift);
            $expectedCashInDrawer = $summary['expected_drawer_cash'];
            $difference = round($countedBalance - $expectedCashInDrawer, 2);

            $shift->update([
                'status' => 'closed',
                'closed_at' => Carbon::now(),
                'closing_balance' => $countedBalance,
                'expected_balance' => $expectedCashInDrawer,
                'difference_amount' => $difference,
                'notes' => $notes,
            ]);

            return $shift;
        });
    }

    /**
     * Gera o resumo financeiro detalhado do turno por forma de pagamento.
     */
    public function getSummary(CashShift $shift): array
    {
        // 1. Total em Dinheiro das Vendas concluídas
        $cashSales = (float) SalePayment::where('tenant_id', $shift->tenant_id)
            ->whereHas('sale', fn($q) => $q->where('cash_shift_id', $shift->id)->where('status', 'completed'))
            ->where('payment_method', 'money')
            ->sum('amount');

        // 2. Vendas em PIX
        $pixSales = (float) SalePayment::where('tenant_id', $shift->tenant_id)
            ->whereHas('sale', fn($q) => $q->where('cash_shift_id', $shift->id)->where('status', 'completed'))
            ->where('payment_method', 'pix')
            ->sum('amount');

        // 3. Vendas em Cartao (Debito e Credito)
        $cardSales = (float) SalePayment::where('tenant_id', $shift->tenant_id)
            ->whereHas('sale', fn($q) => $q->where('cash_shift_id', $shift->id)->where('status', 'completed'))
            ->whereIn('payment_method', ['credit_card', 'debit_card'])
            ->sum('amount');

        // 4. Vendas A Prazo (Crediario)
        $onAccountSales = (float) SalePayment::where('tenant_id', $shift->tenant_id)
            ->whereHas('sale', fn($q) => $q->where('cash_shift_id', $shift->id)->where('status', 'completed'))
            ->where('payment_method', 'on_account')
            ->sum('amount');

        // 5. Suprimentos e Sangrias da Gaveta
        $totalInflows = (float) $shift->movements()->where('type', 'inflow')->sum('amount');
        $totalOutflows = (float) $shift->movements()->where('type', 'outflow')->sum('amount');

        $openingBalance = (float) $shift->opening_balance;
        $expectedDrawerCash = round($openingBalance + $cashSales + $totalInflows - $totalOutflows, 2);
        $totalSales = round($cashSales + $pixSales + $cardSales + $onAccountSales, 2);

        return [
            'opening_balance' => $openingBalance,
            'cash_sales' => $cashSales,
            'pix_sales' => $pixSales,
            'card_sales' => $cardSales,
            'on_account_sales' => $onAccountSales,
            'total_sales' => $totalSales,
            'total_inflows' => $totalInflows,
            'total_outflows' => $totalOutflows,
            'expected_drawer_cash' => $expectedDrawerCash,
        ];
    }
}