<?php

declare(strict_types=1);

namespace Modules\Fiscal\App\Support;

/**
 * Traduz o metodo de pagamento do sistema (Modules\Vendas\App\Models\SalePayment)
 * pro codigo numerico tPag que a SEFAZ exige no grupo pag/detPag da NF-e.
 */
class PaymentMethodMapper
{
    private const MAP = [
        'money' => '01',
        'pix' => '17',
        'credit_card' => '03',
        'debit_card' => '04',
        'boleto' => '15',
        // Crediario/a prazo nao tem tPag equivalente direto na tabela da
        // SEFAZ - "99 Outros" e' o mais correto disponivel.
        'on_account' => '99',
        'other' => '99',
    ];

    public const LABELS = [
        'money' => 'Dinheiro',
        'pix' => 'PIX',
        'credit_card' => 'Cartao de Credito',
        'debit_card' => 'Cartao de Debito',
        'boleto' => 'Boleto',
        'other' => 'Outros',
    ];

    public static function toTPag(string $paymentMethod): string
    {
        return self::MAP[$paymentMethod] ?? '99';
    }
}
