<?php

declare(strict_types=1);

namespace Modules\Vendas\App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Estoque\App\Models\Product;
use Modules\Estoque\App\Models\StockMovement;
use Modules\Estoque\App\Services\StockMovementService;
use Modules\Vendas\App\Models\CustomerReceivable;
use Modules\Vendas\App\Models\Sale;
use Modules\Vendas\App\Models\SaleItem;
use Modules\Vendas\App\Models\SalePayment;

class SaleService
{
    public function __construct(
        private readonly StockMovementService $stockService
    ) {}

    /**
     * Processa a venda com apuracao de troco, split payment, baixa no estoque e vinculo de turno/vendedor.
     */
    public function executeSale(array $data, array $items, array $payments): Sale
    {
        return DB::transaction(function () use ($data, $items, $payments): Sale {
            $subtotal = (float) $data['subtotal'];
            $discountPercent = (float) ($data['discount_percent'] ?? 0);
            $discountFixed = (float) ($data['discount_fixed'] ?? 0);

            $discountFromPercent = round($subtotal * ($discountPercent / 100), 2);
            $totalDiscount = round($discountFromPercent + $discountFixed, 2);
            $totalAmount = max(0.00, round($subtotal - $totalDiscount, 2));

            // Apuracao de Troco
            $totalPaid = array_sum(array_column($payments, 'amount'));
            $changeAmount = max(0.00, round($totalPaid - $totalAmount, 2));

            /** @var Sale $sale */
            $sale = Sale::create([
                'tenant_id' => $data['tenant_id'],
                'user_id' => $data['user_id'],
                'cash_shift_id' => $data['cash_shift_id'] ?? null,
                'seller_id' => $data['seller_id'] ?? null,
                'customer_id' => $data['customer_id'] ?? null,
                'status' => 'completed',
                'price_table' => $data['price_table'] ?? 'retail',
                'subtotal' => $subtotal,
                'discount_amount' => $totalDiscount,
                'total_amount' => $totalAmount,
                'amount_received' => $totalPaid,
                'change_amount' => $changeAmount,
                'payment_method' => count($payments) > 1 ? 'split' : ($payments[0]['method'] ?? 'money'),
                'notes' => $data['notes'] ?? null,
            ]);

            // Carrega os produtos de uma vez so' (evita N+1) pra tirar o
            // retrato fiscal (NCM/CFOP/CSOSN/aliquotas) de cada item no
            // momento exato da venda - necessario pra emitir NF-e fielmente
            // correta mesmo que o cadastro do produto mude depois.
            $products = Product::whereIn('id', array_column($items, 'product_id'))->get()->keyBy('id');

            // 1. Persistencia dos Itens e Baixa no Kardex
            foreach ($items as $item) {
                $product = $products->get($item['product_id']);

                SaleItem::create([
                    'tenant_id' => $data['tenant_id'],
                    'sale_id' => $sale->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'discount_amount' => $item['discount_amount'] ?? 0.00,
                    'total_price' => $item['total_price'],
                    'ncm' => $product?->ncm,
                    'cest' => $product?->cest,
                    'origin' => $product?->origin,
                    'cfop' => $product?->cfop_default,
                    'csosn' => $product?->csosn,
                    'cst_icms' => $product?->cst_icms,
                    'cst_pis' => $product?->cst_pis,
                    'cst_cofins' => $product?->cst_cofins,
                    'icms_rate' => $product?->icms_rate,
                    'pis_rate' => $product?->pis_rate,
                    'cofins_rate' => $product?->cofins_rate,
                ]);

                $this->stockService->recordExit(
                    tenantId: $data['tenant_id'],
                    productId: (int) $item['product_id'],
                    quantity: (float) $item['quantity'],
                    userId: $data['user_id'],
                    referenceType: Sale::class,
                    referenceId: $sale->id,
                    notes: "Venda PDV #{$sale->code}"
                );
            }

            // 2. Persistencia dos Pagamentos e Contas a Receber
            foreach ($payments as $pay) {
                $payAmount = (float) $pay['amount'];
                $installments = (int) ($pay['installments'] ?? 1);
                $method = (string) $pay['method'];

                SalePayment::create([
                    'tenant_id' => $data['tenant_id'],
                    'sale_id' => $sale->id,
                    'payment_method' => $method,
                    'amount' => $payAmount,
                    'installments' => $installments,
                    'due_date' => $method === 'on_account' ? Carbon::now()->addDays(30) : null,
                ]);

                if ($method === 'on_account' && !empty($data['customer_id'])) {
                    $installmentValue = round($payAmount / $installments, 2);
                    $firstDueDate = !empty($pay['first_due_date']) 
                        ? Carbon::parse($pay['first_due_date']) 
                        : Carbon::now()->addDays(30);

                    for ($i = 1; $i <= $installments; $i++) {
                        CustomerReceivable::create([
                            'tenant_id' => $data['tenant_id'],
                            'customer_id' => (int) $data['customer_id'],
                            'sale_id' => $sale->id,
                            'installment_number' => $i,
                            'total_installments' => $installments,
                            'amount' => $installmentValue,
                            'paid_amount' => 0.00,
                            'due_date' => $firstDueDate->copy()->addMonths($i - 1),
                            'status' => 'pending',
                        ]);
                    }
                }
            }

            return $sale;
        });
    }

    /**
     * Cancela a venda com estorno atomico de estoque no Kardex e cancelamento de titulos.
     */
    public function cancelSale(Sale $sale, string $reason, int $userId): Sale
    {
        if ($sale->isCanceled()) {
            throw new \DomainException('Esta venda ja foi cancelada anteriormente.');
        }

        return DB::transaction(function () use ($sale, $reason, $userId): Sale {
            $sale->load('items.product');

            foreach ($sale->items as $item) {
                $this->stockService->recordEntry(
                    tenantId: $sale->tenant_id,
                    productId: $item->product_id,
                    quantity: (float) $item->quantity,
                    unitPrice: (float) $item->unit_price,
                    userId: $userId,
                    referenceType: Sale::class,
                    referenceId: $sale->id,
                    notes: "Cancelamento da Venda #{$sale->code}. Motivo: {$reason}"
                );
            }

            CustomerReceivable::where('tenant_id', $sale->tenant_id)
                ->where('sale_id', $sale->id)
                ->update(['status' => 'canceled']);

            $sale->update([
                'status' => 'canceled',
                'canceled_at' => Carbon::now(),
                'cancel_reason' => $reason,
            ]);

            return $sale;
        });
    }
}