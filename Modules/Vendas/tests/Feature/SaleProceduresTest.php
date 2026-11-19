<?php

declare(strict_types=1);

namespace Modules\Vendas\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Cadastros\App\Models\Customer;
use Modules\Cadastros\App\Models\Employee;
use Modules\Core\App\Models\Tenant;
use Modules\Core\App\Services\TenantManager;
use Modules\Estoque\App\Models\Product;
use Modules\Vendas\App\Models\CashShift;
use Modules\Vendas\App\Models\Sale;
use Modules\Vendas\App\Repositories\SaleProcedureRepository;
use Modules\Vendas\App\Services\CashShiftService;
use Tests\TestCase;

/**
 * Stored Procedures de consulta de vendas (Modules/Vendas/database/migrations/2026_10_26_100001_create_vendas_procedures.php).
 * As vendas sao geradas pelo proprio PDV (vendas.pos.store), pra que os dados tenham o
 * mesmo formato de producao. So' rodam no MySQL/MariaDB.
 */
class SaleProceduresTest extends TestCase
{
    use RefreshDatabase;

    private SaleProcedureRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        if (!in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            $this->markTestSkipped('Stored Procedures so existem no MySQL/MariaDB.');
        }

        $this->repository = app(SaleProcedureRepository::class);
    }

    /**
     * @return array{0: Tenant, 1: User, 2: CashShift, 3: Product}
     */
    private function createStore(string $document): array
    {
        $tenant = Tenant::create(['name' => "Empresa {$document}", 'document' => $document, 'is_active' => true]);
        $user = User::factory()->create();
        $tenant->users()->attach($user->id, ['role' => 'admin', 'is_active' => true]);

        $shift = CashShift::create([
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

        return [$tenant, $user, $shift, $product];
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

    private function actingAsTenant(Tenant $tenant): void
    {
        app(TenantManager::class)->setTenant($tenant);
    }

    private function today(): string
    {
        return now()->toDateString();
    }

    public function test_sales_by_period_ignores_canceled_sales_and_other_tenants(): void
    {
        [$tenantA, $userA, , $productA] = $this->createStore('11111111000191');
        [, $userB, , $productB] = $this->createStore('22222222000191');

        $this->sell($userA, $productA, 8, [['method' => 'money', 'amount' => 100.00]]);      // total 80, troco 20
        $this->sell($userA, $productA, 5, [['method' => 'pix', 'amount' => 50.00]]);
        $this->sell($userA, $productA, 3, [['method' => 'money', 'amount' => 30.00]])->update(['status' => 'canceled']);
        $this->sell($userB, $productB, 9, [['method' => 'pix', 'amount' => 90.00]]);

        $this->actingAsTenant($tenantA);
        $summary = $this->repository->salesByPeriod($this->today(), $this->today());

        $this->assertEquals([2, 130.00, 65.00, 0.00], [
            $summary->quantidade_vendas, $summary->total_vendido, $summary->ticket_medio, $summary->total_descontos,
        ]);

        $empty = $this->repository->salesByPeriod('2000-01-01', '2000-01-31');
        $this->assertEquals([0, 0.00], [$empty->quantidade_vendas, $empty->total_vendido]);
    }

    public function test_top_selling_products_respects_the_limit(): void
    {
        [$tenant, $user, , $cabo] = $this->createStore('11111111000191');
        $lampada = Product::create(['tenant_id' => $tenant->id, 'name' => 'Lampada', 'price_retail' => 10.00, 'ncm' => '85395000']);

        $this->sell($user, $cabo, 3, [['method' => 'pix', 'amount' => 30.00]]);
        $this->sell($user, $cabo, 2, [['method' => 'pix', 'amount' => 20.00]]);
        $this->sell($user, $lampada, 1, [['method' => 'pix', 'amount' => 10.00]]);

        $this->actingAsTenant($tenant);
        $top = $this->repository->topSellingProducts($this->today(), $this->today(), 1);

        $this->assertCount(1, $top);
        $this->assertEquals($cabo->fresh()->code, $top[0]->codigo);
        $this->assertEquals([5, 50.00, 2], [$top[0]->quantidade_vendida, $top[0]->faturamento, $top[0]->numero_vendas]);

        // Limite zero usa o padrao da procedure (10 linhas)
        $this->assertCount(2, $this->repository->topSellingProducts($this->today(), $this->today(), 0));
    }

    public function test_seller_commissions_only_include_the_active_tenant(): void
    {
        [$tenantA, $userA, , $productA] = $this->createStore('11111111000191');
        [$tenantB, $userB, , $productB] = $this->createStore('22222222000191');

        $sellerA = Employee::create(['tenant_id' => $tenantA->id, 'name' => 'Joao', 'role' => 'vendedor', 'commission_percentage' => 5.00, 'is_active' => true]);
        $sellerB = Employee::create(['tenant_id' => $tenantB->id, 'name' => 'Maria', 'role' => 'vendedor', 'commission_percentage' => 10.00, 'is_active' => true]);

        $this->sell($userA, $productA, 20, [['method' => 'pix', 'amount' => 200.00]], ['seller_id' => $sellerA->id]);
        $this->sell($userB, $productB, 20, [['method' => 'pix', 'amount' => 200.00]], ['seller_id' => $sellerB->id]);

        $this->actingAsTenant($tenantA);
        $rows = $this->repository->sellerCommissions($this->today(), $this->today());

        $this->assertCount(1, $rows);
        $this->assertSame('Joao', $rows[0]->vendedor);
        $this->assertEquals([1, 200.00, 10.00], [$rows[0]->quantidade_vendas, $rows[0]->total_vendido, $rows[0]->comissao]);
    }

    public function test_cash_shift_summary_matches_the_cash_shift_service(): void
    {
        [$tenantA, $userA, $shiftA, $productA] = $this->createStore('11111111000191');
        [, , $shiftB] = $this->createStore('22222222000191');

        $this->sell($userA, $productA, 8, [['method' => 'money', 'amount' => 100.00]]);
        $this->sell($userA, $productA, 5, [['method' => 'pix', 'amount' => 50.00]]);

        $this->actingAsTenant($tenantA);
        $summary = $this->repository->cashShiftSummary($shiftA->id);
        $service = app(CashShiftService::class)->getSummary($shiftA->fresh());

        $this->assertEquals($service['expected_drawer_cash'], $summary->saldo_esperado_gaveta);
        $this->assertEquals([100.00, 50.00, 150.00, 20.00], [
            $summary->vendas_dinheiro, $summary->vendas_pix, $summary->total_vendas, $summary->troco_devolvido,
        ]);

        $this->assertNull($this->repository->cashShiftSummary($shiftB->id));
    }

    public function test_open_receivables_of_a_customer(): void
    {
        [$tenantA, $userA, , $productA] = $this->createStore('11111111000191');
        [$tenantB] = $this->createStore('22222222000191');

        $customer = Customer::create(['tenant_id' => $tenantA->id, 'name' => 'Instalacoes Rio']);
        $customerB = Customer::create(['tenant_id' => $tenantB->id, 'name' => 'Cliente B']);

        $this->sell($userA, $productA, 10, [['method' => 'on_account', 'amount' => 100.00, 'installments' => 2]], ['customer_id' => $customer->id]);

        $this->actingAsTenant($tenantA);
        $rows = $this->repository->openReceivables($customer->id);

        $this->assertCount(2, $rows);
        $this->assertEquals([1, 50.00, 0], [$rows[0]->installment_number, $rows[0]->saldo, $rows[0]->dias_atraso]);
        $this->assertSame([], $this->repository->openReceivables($customerB->id));
    }
}
