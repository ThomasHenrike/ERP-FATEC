<?php

declare(strict_types=1);

namespace Modules\Estoque\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Core\App\Models\Tenant;
use Modules\Estoque\App\Models\Product;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    private function createTenantWithUser(string $document, string $role = 'admin'): array
    {
        $tenant = Tenant::create([
            'name' => "Empresa {$document}",
            'document' => $document,
            'is_active' => true,
        ]);

        $user = User::factory()->create();
        $tenant->users()->attach($user->id, ['role' => $role, 'is_active' => true]);

        return [$tenant, $user];
    }

    private function validProductPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Produto Teste',
            'unit' => 'UN',
            'cost_price' => 10.00,
            'price_retail' => 20.00,
            'ncm' => '12345678',
            'origin' => 0,
            'cfop_default' => '5102',
            'cst_pis' => '01',
            'cst_cofins' => '01',
            'is_active' => true,
        ], $overrides);
    }

    public function test_operator_cannot_create_a_product(): void
    {
        [, $operator] = $this->createTenantWithUser('11111111000191', 'operator');

        $response = $this->actingAs($operator)->post(route('estoque.produtos.store'), $this->validProductPayload());

        $response->assertRedirect(route('dashboard'));
        $this->assertDatabaseMissing('products', ['name' => 'Produto Teste']);
    }

    public function test_manager_can_create_a_product_with_initial_stock(): void
    {
        [, $manager] = $this->createTenantWithUser('22222222000191', 'manager');

        $response = $this->actingAs($manager)->post(route('estoque.produtos.store'), $this->validProductPayload(['initial_stock' => 15]));

        $response->assertRedirect(route('estoque.produtos.index'));
        $this->assertDatabaseHas('products', ['name' => 'Produto Teste', 'current_stock' => 15]);
        $this->assertDatabaseHas('stock_movements', ['type' => 'in', 'quantity' => 15]);
    }

    public function test_manager_can_create_a_product_without_initial_stock(): void
    {
        [, $manager] = $this->createTenantWithUser('33333333000191', 'manager');

        $response = $this->actingAs($manager)->post(route('estoque.produtos.store'), $this->validProductPayload());

        $response->assertRedirect(route('estoque.produtos.index'));
        $this->assertDatabaseHas('products', ['name' => 'Produto Teste', 'current_stock' => 0]);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_sku_must_be_unique_within_the_same_tenant_only(): void
    {
        [$tenantA, $managerA] = $this->createTenantWithUser('44444444000191', 'manager');
        [, $managerB] = $this->createTenantWithUser('55555555000191', 'manager');

        Product::create($this->validProductPayload(['tenant_id' => $tenantA->id, 'sku' => 'ABC-1']));

        // Mesmo SKU, tenant diferente: deve passar
        $response = $this->actingAs($managerB)->post(route('estoque.produtos.store'), $this->validProductPayload(['sku' => 'ABC-1']));
        $response->assertRedirect(route('estoque.produtos.index'));

        // Mesmo SKU, mesmo tenant: deve falhar
        $response = $this->actingAs($managerA)->post(route('estoque.produtos.store'), $this->validProductPayload(['sku' => 'ABC-1']));
        $response->assertSessionHasErrors('sku');
    }

    public function test_operator_cannot_deactivate_a_product(): void
    {
        [$tenant, $operator] = $this->createTenantWithUser('66666666000191', 'operator');
        $product = Product::create($this->validProductPayload(['tenant_id' => $tenant->id]));

        $response = $this->actingAs($operator)->delete(route('estoque.produtos.destroy', $product));

        $response->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('products', ['id' => $product->id, 'is_active' => true]);
    }

    public function test_manager_deactivating_a_product_keeps_the_row_soft_visible(): void
    {
        [$tenant, $manager] = $this->createTenantWithUser('77777777000191', 'manager');
        $product = Product::create($this->validProductPayload(['tenant_id' => $tenant->id]));

        $response = $this->actingAs($manager)->delete(route('estoque.produtos.destroy', $product));

        $response->assertRedirect(route('estoque.produtos.index'));
        $this->assertDatabaseHas('products', ['id' => $product->id, 'is_active' => false]);
    }

    public function test_user_from_one_tenant_cannot_edit_product_from_another_tenant(): void
    {
        [, $managerA] = $this->createTenantWithUser('88888888000191', 'manager');
        [$tenantB] = $this->createTenantWithUser('99999999000191', 'manager');

        $productB = Product::create($this->validProductPayload(['tenant_id' => $tenantB->id]));

        $response = $this->actingAs($managerA)->get(route('estoque.produtos.edit', $productB));

        $response->assertNotFound();
    }

    public function test_low_stock_filter_only_returns_products_below_minimum(): void
    {
        [$tenant, $user] = $this->createTenantWithUser('10101010000191');

        // current_stock nao e' fillable (so' muda via StockMovementService),
        // entao precisa atribuir a propriedade direto e salvar, como o servico faz.
        $low = Product::create($this->validProductPayload(['tenant_id' => $tenant->id, 'name' => 'Estoque Baixo', 'min_stock' => 10]));
        $low->current_stock = 2;
        $low->save();

        $ok = Product::create($this->validProductPayload(['tenant_id' => $tenant->id, 'name' => 'Estoque Ok', 'min_stock' => 10]));
        $ok->current_stock = 50;
        $ok->save();

        $response = $this->actingAs($user)->get(route('estoque.produtos.index', ['low_stock' => 1]));

        $response->assertOk();
        $response->assertSee('Estoque Baixo');
        $response->assertDontSee('Estoque Ok');
    }
}
