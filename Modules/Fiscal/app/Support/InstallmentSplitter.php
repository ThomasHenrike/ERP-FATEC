<?php

declare(strict_types=1);

namespace Modules\Fiscal\App\Support;

use Illuminate\Support\Carbon;

/**
 * Divide um total em N parcelas sem perder nem sobrar centavo - trabalha
 * em centavos (inteiros) e distribui o resto da divisao entre as
 * primeiras parcelas, garantindo que a soma bate exatamente com o total
 * mesmo quando a divisao nao e' exata (ex: R$100,00 / 3).
 */
class InstallmentSplitter
{
    /**
     * @return array<int, array{numero: int, data_vencimento: Carbon, valor: float}>
     */
    public static function split(float $total, int $parcelas, Carbon $primeiroVencimento, int $diasEntreVencimentos): array
    {
        $parcelas = max(1, $parcelas);
        $totalCentavos = (int) round($total * 100);

        $baseCentavos = intdiv($totalCentavos, $parcelas);
        $resto = $totalCentavos % $parcelas;

        $result = [];
        for ($i = 0; $i < $parcelas; $i++) {
            $valorCentavos = $baseCentavos + ($i < $resto ? 1 : 0);

            $result[] = [
                'numero' => $i + 1,
                'data_vencimento' => $primeiroVencimento->copy()->addDays($i * $diasEntreVencimentos),
                'valor' => round($valorCentavos / 100, 2),
            ];
        }

        return $result;
    }
}
