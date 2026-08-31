<?php

declare(strict_types=1);

namespace Modules\Vendas\App\Exceptions;

use Modules\Vendas\App\Models\CashShift;
use RuntimeException;

/**
 * So' pode existir um caixa aberto por vez, por empresa (um unico ponto de
 * venda/gaveta fisica). Lancada quando alguem tenta abrir um segundo
 * enquanto ja' existe um turno aberto por outra pessoa.
 */
class CashShiftAlreadyOpenException extends RuntimeException
{
    public function __construct(public readonly CashShift $openShift)
    {
        parent::__construct('Ja existe um caixa aberto para essa empresa.');
    }
}
