<?php

declare(strict_types=1);

namespace Modules\Orcamento\App\Services;

use Illuminate\Support\Facades\DB;
use Modules\Orcamento\App\Models\Orcamento;
use Modules\Orcamento\App\Models\OrcamentoItem;

class OrcamentoService
{
    /**
     * Cria um orcamento com seus itens, apurando os totais a partir das linhas.
     */
    public function createOrcamento(array $data, array $items): Orcamento
    {
        return DB::transaction(function () use ($data, $items): Orcamento {
            $totals = $this->calculateTotals($items);

            /** @var Orcamento $orcamento */
            $orcamento = Orcamento::create([
                ...$data,
                'subtotal' => $totals['subtotal'],
                'total_icms_st' => $totals['total_icms_st'],
                'total_amount' => $totals['total_amount'],
            ]);

            $this->syncItems($orcamento, $items);

            return $orcamento;
        });
    }

    /**
     * Atualiza um orcamento existente, recriando os itens e reapurando os totais.
     */
    public function updateOrcamento(Orcamento $orcamento, array $data, array $items): Orcamento
    {
        return DB::transaction(function () use ($orcamento, $data, $items): Orcamento {
            $totals = $this->calculateTotals($items);

            $orcamento->update([
                ...$data,
                'subtotal' => $totals['subtotal'],
                'total_icms_st' => $totals['total_icms_st'],
                'total_amount' => $totals['total_amount'],
            ]);

            $orcamento->items()->delete();
            $this->syncItems($orcamento, $items);

            return $orcamento;
        });
    }

    /**
     * @return array{subtotal: float, total_icms_st: float, total_amount: float}
     */
    private function calculateTotals(array $items): array
    {
        $subtotal = 0.0;
        $totalIcmsSt = 0.0;

        foreach ($items as $item) {
            $subtotal += (float) $item['total_price'];
            $totalIcmsSt += (float) ($item['icms_st_value'] ?? 0);
        }

        return [
            'subtotal' => round($subtotal, 2),
            'total_icms_st' => round($totalIcmsSt, 2),
            'total_amount' => round($subtotal + $totalIcmsSt, 2),
        ];
    }

    private function syncItems(Orcamento $orcamento, array $items): void
    {
        foreach ($items as $item) {
            OrcamentoItem::create([
                'tenant_id' => $orcamento->tenant_id,
                'orcamento_id' => $orcamento->id,
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'total_price' => $item['total_price'],
                'icms_percent' => $item['icms_percent'] ?? 0,
                'icms_st_value' => $item['icms_st_value'] ?? 0,
                'lead_time_days' => $item['lead_time_days'] ?? null,
            ]);
        }
    }
}
