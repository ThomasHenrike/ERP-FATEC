<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Core\App\Models\Tenant;
use Modules\Estoque\App\Models\Product;
use Modules\Vendas\App\Models\CashShift;
use Tests\TestCase;

/**
 * Cobre o fluxo critico de venda no PDV: baixa de estoque e persistencia
 * da venda dentro da mesma transacao (Modules/Vendas/app/Services/SaleService.php).
 */
class PosSaleFlowTest extends TestCase
{
    use RefreshDatabase;

    private function createTenantWithOpenShift(): array
    {
        $tenant = Tenant::create([
            'name' => 'Empresa Teste',
            'document' => '11111111000191',
            'is_active' => true,
        ]);

        $user = User::factory()->create();
        $tenant->users()->attach($user->id, ['role' => 'admin', 'is_active' => true]);

        CashShift::create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'status' => 'open',
            'opened_at' => now(),
            'opening_balance' => 100.00,
        ]);

        return [$tenant, $user];
    }

    public function test_finalizing_a_sale_deducts_stock_and_persists_the_sale(): void
    {
        [$tenant, $user] = $this->createTenantWithOpenShift();

        $product = Product::create([
            'tenant_id' => $tenant->id,
            'name' => 'Produto Teste',
            'price_retail' => 50.00,
            'cost_price' => 20.00,
            'ncm' => '12345678',
        ]);
        $product->current_stock = 10;
        $product->save();

        $response = $this->actingAs($user)->postJson(route('vendas.pos.store'), [
            'price_table' => 'retail',
            'subtotal' => 100.00,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2, 'unit_price' => 50.00, 'total_price' => 100.00],
            ],
            'payments' => [
                ['method' => 'money', 'amount' => 100.00],
            ],
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $this->assertDatabaseCount('sales', 1);
        $this->assertSame(8.0, (float) $product->fresh()->current_stock);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => 'out',
        ]);
    }

    /**
     * O item da venda precisa guardar o retrato fiscal do produto no momento
     * da venda (NCM/CFOP/CSOSN/aliquotas) - necessario pra emitir NF-e
     * fielmente correta mesmo que o cadastro do produto mude depois.
     */
    public function test_sale_item_snapshots_the_products_fiscal_data_at_the_time_of_sale(): void
    {
        [$tenant, $user] = $this->createTenantWithOpenShift();

        $product = Product::create([
            'tenant_id' => $tenant->id,
            'name' => 'Produto Fiscal',
            'price_retail' => 50.00,
            'cost_price' => 20.00,
            'ncm' => '85444200',
            'cfop_default' => '5405',
            'csosn' => '500',
            'icms_rate' => 18.00,
        ]);
        $product->current_stock = 10;
        $product->save();

        $this->actingAs($user)->postJson(route('vendas.pos.store'), [
            'price_table' => 'retail',
            'subtotal' => 50.00,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 50.00, 'total_price' => 50.00],
            ],
            'payments' => [
                ['method' => 'money', 'amount' => 50.00],
            ],
        ]);

        $this->assertDatabaseHas('sale_items', [
            'product_id' => $product->id,
            'ncm' => '85444200',
            'cfop' => '5405',
            'csosn' => '500',
            'icms_rate' => 18.00,
        ]);

        // Muda o cadastro do produto DEPOIS da venda - o item ja gravado
        // nao pode acompanhar essa mudanca, senao a NF-e emitida depois
        // reflete um dado que nao era verdade na hora da venda.
        $product->update(['ncm' => '99999999', 'cfop_default' => '5101']);

        $this->assertDatabaseHas('sale_items', [
            'product_id' => $product->id,
            'ncm' => '85444200',
            'cfop' => '5405',
        ]);
    }

    public function test_a_different_user_can_see_and_sell_against_a_shift_opened_by_someone_else(): void
    {
        [$tenant, $userA] = $this->createTenantWithOpenShift();
        $userB = User::factory()->create();
        $tenant->users()->attach($userB->id, ['role' => 'operator', 'is_active' => true]);

        $product = Product::create([
            'tenant_id' => $tenant->id,
            'name' => 'Produto Teste',
            'price_retail' => 50.00,
            'cost_price' => 20.00,
            'ncm' => '12345678',
        ]);
        $product->current_stock = 10;
        $product->save();

        $pdvPage = $this->actingAs($userB)->get(route('vendas.pos.index'));
        $pdvPage->assertOk();
        $pdvPage->assertViewHas('activeShift', fn ($shift) => $shift !== null && $shift->user_id === $userA->id);

        $response = $this->actingAs($userB)->postJson(route('vendas.pos.store'), [
            'price_table' => 'retail',
            'subtotal' => 50.00,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 50.00, 'total_price' => 50.00],
            ],
            'payments' => [
                ['method' => 'money', 'amount' => 50.00],
            ],
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);
        $this->assertDatabaseHas('sales', ['user_id' => $userB->id]);
    }

    public function test_sale_cannot_be_finalized_without_an_open_cash_shift(): void
    {
        $tenant = Tenant::create([
            'name' => 'Empresa Sem Caixa',
            'document' => '33333333000191',
            'is_active' => true,
        ]);

        $user = User::factory()->create();
        $tenant->users()->attach($user->id, ['role' => 'admin', 'is_active' => true]);

        $product = Product::create([
            'tenant_id' => $tenant->id,
            'name' => 'Produto Teste',
            'price_retail' => 50.00,
            'ncm' => '12345678',
        ]);
        $product->current_stock = 10;
        $product->save();

        $response = $this->actingAs($user)->postJson(route('vendas.pos.store'), [
            'price_table' => 'retail',
            'subtotal' => 50.00,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 50.00, 'total_price' => 50.00],
            ],
            'payments' => [
                ['method' => 'money', 'amount' => 50.00],
            ],
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('sales', 0);
        $this->assertSame(10.0, (float) $product->fresh()->current_stock);
    }
}
