<?php

declare(strict_types=1);

namespace Modules\Estoque\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Core\App\Models\Tenant;
use Modules\Estoque\App\Models\Product;
use Tests\TestCase;

/**
 * Kardex (Modules/Estoque/app/Services/StockMovementService.php): ver e'
 * liberado pra qualquer papel, ajuste manual exige gerente+. O tipo
 * "adjustment" e' uma definicao absoluta do estoque (nao soma/subtrai como
 * os outros tipos) - vale testar essa diferenca explicitamente.
 */
class StockMovementTest extends TestCase
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

    private function createProduct(int $tenantId, float $currentStock = 0): Product
    {
        $product = Product::create([
            'tenant_id' => $tenantId,
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
        ]);
        $product->current_stock = $currentStock;
        $product->save();

        return $product;
    }

    public function test_operator_can_view_the_kardex_but_not_create_an_adjustment(): void
    {
        [$tenant, $operator] = $this->createTenantWithUser('11111111000191', 'operator');
        $product = $this->createProduct($tenant->id, 10);

        $view = $this->actingAs($operator)->get(route('estoque.movimentacoes.index'));
        $view->assertOk();

        $response = $this->actingAs($operator)->post(route('estoque.movimentacoes.store'), [
            'product_id' => $product->id,
            'type' => 'in',
            'quantity' => 5,
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertSame(10.0, (float) $product->fresh()->current_stock);
    }

    public function test_manager_entry_increases_stock(): void
    {
        [$tenant, $manager] = $this->createTenantWithUser('22222222000191', 'manager');
        $product = $this->createProduct($tenant->id, 10);

        $response = $this->actingAs($manager)->post(route('estoque.movimentacoes.store'), [
            'product_id' => $product->id,
            'type' => 'in',
            'quantity' => 5,
        ]);

        $response->assertRedirect(route('estoque.movimentacoes.index'));
        $this->assertSame(15.0, (float) $product->fresh()->current_stock);
    }

    public function test_manager_exit_decreases_stock(): void
    {
        [$tenant, $manager] = $this->createTenantWithUser('33333333000191', 'manager');
        $product = $this->createProduct($tenant->id, 10);

        $this->actingAs($manager)->post(route('estoque.movimentacoes.store'), [
            'product_id' => $product->id,
            'type' => 'out',
            'quantity' => 4,
        ]);

        $this->assertSame(6.0, (float) $product->fresh()->current_stock);
    }

    public function test_adjustment_sets_stock_to_an_absolute_value_instead_of_adding(): void
    {
        [$tenant, $manager] = $this->createTenantWithUser('44444444000191', 'manager');
        $product = $this->createProduct($tenant->id, 10);

        $this->actingAs($manager)->post(route('estoque.movimentacoes.store'), [
            'product_id' => $product->id,
            'type' => 'adjustment',
            'quantity' => 3,
        ]);

        // Contagem fisica encontrou 3 unidades - o resultado tem que ser 3,
        // nao 13 (10 + 3) nem 7 (10 - 3).
        $this->assertSame(3.0, (float) $product->fresh()->current_stock);
    }

    public function test_stock_movement_is_an_immutable_audit_record_with_previous_and_current_snapshot(): void
    {
        [$tenant, $manager] = $this->createTenantWithUser('55555555000191', 'manager');
        $product = $this->createProduct($tenant->id, 10);

        $this->actingAs($manager)->post(route('estoque.movimentacoes.store'), [
            'product_id' => $product->id,
            'type' => 'loss',
            'quantity' => 2,
            'notes' => 'Produto quebrado no deposito',
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => 'loss',
            'quantity' => 2,
            'previous_stock' => 10,
            'current_stock' => 8,
        ]);
    }

    public function test_cannot_adjust_stock_of_a_product_from_another_tenant(): void
    {
        [, $managerA] = $this->createTenantWithUser('66666666000191', 'manager');
        [$tenantB] = $this->createTenantWithUser('77777777000191', 'manager');
        $productB = $this->createProduct($tenantB->id, 10);

        $response = $this->actingAs($managerA)->post(route('estoque.movimentacoes.store'), [
            'product_id' => $productB->id,
            'type' => 'in',
            'quantity' => 5,
        ]);

        $response->assertSessionHasErrors('product_id');
        $this->assertSame(10.0, (float) $productB->fresh()->current_stock);
    }
}
