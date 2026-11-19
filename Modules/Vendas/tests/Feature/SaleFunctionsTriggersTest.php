<?php

declare(strict_types=1);

namespace Modules\Vendas\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Cadastros\App\Models\Customer;
use Modules\Core\App\Models\Tenant;
use Modules\Estoque\App\Models\Product;
use Modules\Vendas\App\Models\CashShift;
use Modules\Vendas\App\Models\CustomerReceivable;
use Modules\Vendas\App\Models\Sale;
use Tests\TestCase;

/**
 * Function fn_saldo_devedor_cliente e trigger trg_venda_cancelada_log
 * (Modules/Vendas/database/migrations/2026_11_17_100001_create_vendas_functions_triggers.php).
 * As vendas, a quitacao e o cancelamento passam pelas rotas reais do sistema.
 * So' rodam no MySQL/MariaDB.
 */
class SaleFunctionsTriggersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (!in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            $this->markTestSkipped('Functions e triggers so existem no MySQL/MariaDB.');
        }
    }

    /**
     * @return array{0: Tenant, 1: User, 2: Product, 3: Customer}
     */
    private function createStore(string $document): array
    {
        $tenant = Tenant::create(['name' => "Empresa {$document}", 'document' => $document, 'is_active' => true]);
        $user = User::factory()->create();
        $tenant->users()->attach($user->id, ['role' => 'admin', 'is_active' => true]);

        CashShift::create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'status' => 'open',
            'opened_at' => now(),
            'opening_balance' => 100.00,
        ]);

        $product = Product::create([
            'tenant_id' => $tenant->id,
            'name' => 'Cabo Flexivel',
            'cost_price' => 5.00,
            'price_retail' => 10.00,
            'ncm' => '85444900',
        ]);

        $customer = Customer::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Cliente Crediario',
            'credit_limit' => 1000,
        ]);

        return [$tenant, $user, $product, $customer];
    }

    private function sell(User $user, Product $product, float $quantity, array $payments, array $extra = []): Sale
    {
        $total = round($quantity * (float) $product->price_retail, 2);

        $this->actingAs($user)->postJson(route('vendas.pos.store'), array_merge([
            'price_table' => 'retail',
            'subtotal' => $total,
            'items' => [
                ['product_id' => $product->id, 'quantity' => $quantity, 'unit_price' => (float) $product->price_retail, 'total_price' => $total],
            ],
            'payments' => $payments,
        ], $extra))->assertOk();

        return Sale::withoutGlobalScopes()->latest('id')->first();
    }

    private function saldo(int $tenantId, int $customerId): float
    {
        return (float) DB::selectOne('SELECT fn_saldo_devedor_cliente(?, ?) AS saldo', [$tenantId, $customerId])->saldo;
    }

    public function test_balance_follows_installments_settlement_and_cancellation(): void
    {
        [$tenant, $user, $product, $customer] = $this->createStore('11111111000191');

        $this->assertSame(0.0, $this->saldo($tenant->id, $customer->id));

        $sale = $this->sell($user, $product, 10, [['method' => 'on_account', 'amount' => 100.00, 'installments' => 2]], ['customer_id' => $customer->id]);
        $this->assertSame(100.0, $this->saldo($tenant->id, $customer->id));

        // Quita a 1a parcela pela tela de Prazos
        $first = CustomerReceivable::withoutGlobalScopes()->where('sale_id', $sale->id)->orderBy('installment_number')->first();
        $this->actingAs($user)->post(route('vendas.prazos.settle', $first))->assertRedirect();
        $this->assertSame(100.0 - (float) $first->amount, $this->saldo($tenant->id, $customer->id));

        // Cancelar a venda cancela os titulos que faltavam: saldo zera
        $this->actingAs($user)->post(route('vendas.sales.cancel', $sale), ['cancel_reason' => 'Cliente desistiu'])->assertRedirect();
        $this->assertSame(0.0, $this->saldo($tenant->id, $customer->id));
    }

    public function test_balance_is_isolated_per_tenant(): void
    {
        [$tenantA, $userA, $productA, $customerA] = $this->createStore('11111111000191');
        [$tenantB] = $this->createStore('22222222000191');

        $this->sell($userA, $productA, 5, [['method' => 'on_account', 'amount' => 50.00, 'installments' => 1]], ['customer_id' => $customerA->id]);

        $this->assertSame(50.0, $this->saldo($tenantA->id, $customerA->id));
        $this->assertSame(0.0, $this->saldo($tenantB->id, $customerA->id));
    }

    public function test_canceling_a_sale_writes_one_log_row(): void
    {
        [$tenant, $user, $product] = $this->createStore('11111111000191');
        $sale = $this->sell($user, $product, 3, [['method' => 'money', 'amount' => 30.00]]);

        $this->assertDatabaseCount('sale_cancellation_logs', 0);

        $this->actingAs($user)->post(route('vendas.sales.cancel', $sale), ['cancel_reason' => 'Produto com defeito'])->assertRedirect();

        $this->assertDatabaseCount('sale_cancellation_logs', 1);
        $this->assertDatabaseHas('sale_cancellation_logs', [
            'tenant_id' => $tenant->id,
            'sale_id' => $sale->id,
            'sale_code' => $sale->code,
            'total_amount' => 30.00,
            'cancel_reason' => 'Produto com defeito',
        ]);

        // Horario em UTC, igual ao canceled_at gravado pelo sistema (e nao o horario local do MySQL)
        $log = DB::table('sale_cancellation_logs')->first();
        $this->assertLessThan(120, abs(\Illuminate\Support\Carbon::parse($log->logged_at)->diffInSeconds(now())));

        // Mexer de novo numa venda ja' cancelada nao duplica o registro
        Sale::withoutGlobalScopes()->whereKey($sale->id)->update(['notes' => 'observacao depois do cancelamento']);
        $this->assertDatabaseCount('sale_cancellation_logs', 1);
    }

    public function test_updating_a_completed_sale_does_not_write_a_log(): void
    {
        [, $user, $product] = $this->createStore('11111111000191');
        $sale = $this->sell($user, $product, 1, [['method' => 'pix', 'amount' => 10.00]]);

        Sale::withoutGlobalScopes()->whereKey($sale->id)->update(['notes' => 'entregar a tarde']);

        $this->assertDatabaseCount('sale_cancellation_logs', 0);
    }
}
