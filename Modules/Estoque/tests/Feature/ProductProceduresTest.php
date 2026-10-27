<?php

declare(strict_types=1);

namespace Modules\Estoque\Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Core\App\Models\Tenant;
use Modules\Core\App\Services\TenantManager;
use Modules\Estoque\App\Models\Category;
use Modules\Estoque\App\Models\Product;
use Modules\Estoque\App\Repositories\ProductProcedureRepository;
use Modules\Estoque\App\Services\StockMovementService;
use Tests\TestCase;

/**
 * Stored Procedures de produto (Modules/Estoque/database/migrations/2026_10_26_100000_create_estoque_procedures.php).
 * So' rodam no MySQL/MariaDB - no SQLite as procedures nao existem e os testes sao pulados.
 */
class ProductProceduresTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenantA;

    private Tenant $tenantB;

    private ProductProcedureRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        if (!in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            $this->markTestSkipped('Stored Procedures so existem no MySQL/MariaDB.');
        }

        $this->tenantA = Tenant::create(['name' => 'Empresa A', 'document' => '11111111000191', 'is_active' => true]);
        $this->tenantB = Tenant::create(['name' => 'Empresa B', 'document' => '22222222000191', 'is_active' => true]);
        $this->repository = app(ProductProcedureRepository::class);

        $this->actingAsTenant($this->tenantA);
    }

    private function actingAsTenant(Tenant $tenant): void
    {
        app(TenantManager::class)->setTenant($tenant);
    }

    private function createProduct(Tenant $tenant, array $attributes = [], float $stock = 0): Product
    {
        $product = Product::create(array_merge([
            'tenant_id' => $tenant->id,
            'name' => 'Produto Teste',
            'cost_price' => 10.00,
            'price_retail' => 20.00,
            'ncm' => '12345678',
        ], $attributes));

        $product->current_stock = $stock;
        $product->save();

        return $product;
    }

    public function test_finds_a_product_by_id_and_returns_nothing_for_unknown_id(): void
    {
        $product = $this->createProduct($this->tenantA, ['name' => 'Cabo Flexivel 2,5mm']);

        $this->assertSame('Cabo Flexivel 2,5mm', $this->repository->findById($product->id)->name);
        $this->assertNull($this->repository->findById(999999));
    }

    public function test_tenant_a_cannot_see_products_of_tenant_b(): void
    {
        $productB = $this->createProduct($this->tenantB, ['name' => 'Disjuntor Exclusivo B', 'barcode' => '7890000000001']);

        $this->assertNull($this->repository->findById($productB->id));
        $this->assertSame([], $this->repository->searchForPos('Disjuntor Exclusivo B'));
        $this->assertSame([], $this->repository->searchForPos('7890000000001'));
        $this->assertSame([], $this->repository->listOrdered());
    }

    public function test_pos_search_matches_code_barcode_sku_and_part_of_the_name_ignoring_case(): void
    {
        $cabo = $this->createProduct($this->tenantA, ['name' => 'Cabo Flexivel 2,5mm', 'sku' => 'CAB-25', 'barcode' => '7891111111111']);
        $this->createProduct($this->tenantA, ['name' => 'Disjuntor 20A']);

        $this->assertSame([$cabo->id], array_column($this->repository->searchForPos('CABO'), 'id'));
        $this->assertSame([$cabo->id], array_column($this->repository->searchForPos('flexivel'), 'id'));
        $this->assertSame([$cabo->id], array_column($this->repository->searchForPos('cab-25'), 'id'));
        $this->assertSame([$cabo->id], array_column($this->repository->searchForPos('7891111111111'), 'id'));
        $this->assertSame([$cabo->id], array_column($this->repository->searchForPos((string) $cabo->code), 'id'));
    }

    public function test_pos_search_returns_the_fields_the_pos_catalog_uses(): void
    {
        $this->createProduct($this->tenantA, ['name' => 'Lampada LED 9W'], 12);

        $row = (array) $this->repository->searchForPos('Lampada')[0];

        $this->assertSame(
            ['id', 'code', 'name', 'sku', 'barcode', 'unit', 'cost_price', 'price_retail',
                'price_resale', 'price_wholesale', 'current_stock', 'min_stock'],
            array_keys($row)
        );
        $this->assertEquals(12, $row['current_stock']);
    }

    public function test_pos_search_puts_exact_matches_first_and_ignores_inactive_and_deleted(): void
    {
        $this->createProduct($this->tenantA, ['name' => 'Tomada 10A codigo 777']);
        $exato = $this->createProduct($this->tenantA, ['name' => 'Tomada 20A', 'sku' => '777']);
        $this->createProduct($this->tenantA, ['name' => 'Tomada Inativa 777', 'is_active' => false]);
        $this->createProduct($this->tenantA, ['name' => 'Tomada Excluida 777'])->delete();

        $result = $this->repository->searchForPos('777');

        $this->assertCount(2, $result);
        $this->assertSame($exato->id, $result[0]->id);
    }

    public function test_pos_search_treats_like_wildcards_as_text_and_ignores_empty_term(): void
    {
        $this->createProduct($this->tenantA, ['name' => 'Cabo']);

        $this->assertSame([], $this->repository->searchForPos('%'));
        $this->assertSame([], $this->repository->searchForPos('_'));
        $this->assertSame([], $this->repository->searchForPos('   '));
    }

    public function test_lists_active_products_of_a_category(): void
    {
        $cabos = Category::create(['tenant_id' => $this->tenantA->id, 'name' => 'Cabos']);
        $outra = Category::create(['tenant_id' => $this->tenantA->id, 'name' => 'Iluminacao']);
        $categoriaB = Category::create(['tenant_id' => $this->tenantB->id, 'name' => 'Cabos B']);

        $cabo = $this->createProduct($this->tenantA, ['name' => 'Cabo 4mm', 'category_id' => $cabos->id]);
        $this->createProduct($this->tenantA, ['name' => 'Cabo Excluido', 'category_id' => $cabos->id])->delete();
        $this->createProduct($this->tenantA, ['name' => 'Lampada', 'category_id' => $outra->id]);
        $this->createProduct($this->tenantB, ['name' => 'Cabo B', 'category_id' => $categoriaB->id]);

        $this->assertSame([$cabo->id], array_column($this->repository->byCategory($cabos->id), 'id'));
        $this->assertSame([], $this->repository->byCategory($categoriaB->id));
    }

    public function test_filters_by_price_range_using_the_chosen_price_table(): void
    {
        $barato = $this->createProduct($this->tenantA, ['name' => 'Barato', 'price_retail' => 10.00]);
        $medio = $this->createProduct($this->tenantA, ['name' => 'Medio', 'price_retail' => 50.00, 'price_resale' => 40.00]);
        $this->createProduct($this->tenantA, ['name' => 'Caro', 'price_retail' => 500.00]);

        $this->assertSame([$barato->id, $medio->id], array_column($this->repository->byPriceRange(5, 60), 'id'));

        // Revenda: o Medio sai por 40; o Barato nao tem revenda e usa o varejo (10).
        $resale = $this->repository->byPriceRange(35, 45, 'resale');
        $this->assertSame([$medio->id], array_column($resale, 'id'));
        $this->assertEquals(40.00, $resale[0]->preco);
    }

    public function test_price_range_rejects_invalid_table_and_inverted_range(): void
    {
        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('Faixa de preco invalida');

        $this->repository->byPriceRange(100, 10);
    }

    public function test_price_range_rejects_unknown_price_table(): void
    {
        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('Tabela de preco invalida');

        $this->repository->byPriceRange(0, 10, 'promocao');
    }

    public function test_lists_products_ordered_by_the_chosen_field(): void
    {
        $a = $this->createProduct($this->tenantA, ['name' => 'Alfa', 'price_retail' => 30.00], 5);
        $b = $this->createProduct($this->tenantA, ['name' => 'Beta', 'price_retail' => 100.00], 1);
        $c = $this->createProduct($this->tenantA, ['name' => 'Gama', 'price_retail' => 9.00], 50);

        $this->assertSame([$a->id, $b->id, $c->id], array_column($this->repository->listOrdered('nome', 'ASC'), 'id'));
        $this->assertSame([$b->id, $a->id, $c->id], array_column($this->repository->listOrdered('preco', 'desc'), 'id'));
        $this->assertSame([$b->id, $a->id, $c->id], array_column($this->repository->listOrdered('estoque', 'ASC'), 'id'));
        $this->assertSame([$c->id, $b->id, $a->id], array_column($this->repository->listOrdered('codigo', 'DESC'), 'id'));
    }

    public function test_ordering_rejects_fields_outside_the_allowed_list(): void
    {
        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('Campo de ordenacao invalido');

        $this->repository->listOrdered('password', 'ASC');
    }

    public function test_lists_products_below_minimum_stock(): void
    {
        $falta = $this->createProduct($this->tenantA, ['name' => 'Em falta', 'min_stock' => 20], 4);
        $this->createProduct($this->tenantA, ['name' => 'Sem minimo', 'min_stock' => 0], 0);
        $this->createProduct($this->tenantA, ['name' => 'Abastecido', 'min_stock' => 5], 30);
        $this->createProduct($this->tenantB, ['name' => 'Em falta B', 'min_stock' => 20], 1);

        $result = $this->repository->belowMinimumStock();

        $this->assertSame([$falta->id], array_column($result, 'id'));
        $this->assertEquals(16, $result[0]->falta);
    }

    public function test_stock_ledger_shows_previous_and_current_stock_in_the_period(): void
    {
        $product = $this->createProduct($this->tenantA);
        $stock = app(StockMovementService::class);
        $stock->recordEntry($this->tenantA->id, $product->id, 10, 5.00);
        $stock->recordExit($this->tenantA->id, $product->id, 3);

        $today = now()->toDateString();
        $ledger = $this->repository->stockLedger($product->id, $today, $today);

        $this->assertCount(2, $ledger);
        $this->assertEquals([0, 10], [$ledger[0]->previous_stock, $ledger[0]->current_stock]);
        $this->assertEquals([10, 7], [$ledger[1]->previous_stock, $ledger[1]->current_stock]);
        $this->assertSame([], $this->repository->stockLedger($product->id, '2000-01-01', '2000-01-31'));

        $this->actingAsTenant($this->tenantB);
        $this->assertSame([], $this->repository->stockLedger($product->id, $today, $today));
    }

    public function test_inserts_a_product_with_the_next_code_and_the_retail_margin(): void
    {
        $existente = $this->createProduct($this->tenantA);
        $this->createProduct($this->tenantB);
        $categoria = Category::create(['tenant_id' => $this->tenantA->id, 'name' => 'Cabos']);

        $novo = $this->repository->create($categoria->id, 'Cabo 6mm', 'MT', '85444900', 10.00, 15.00, 50);
        $outro = $this->repository->create(null, 'Cabo 10mm', 'MT', '85444900', 20.00, 26.00);

        $this->assertSame($existente->code + 1, (int) $novo->code);
        $this->assertSame($existente->code + 2, (int) $outro->code);
        $this->assertEquals(50.00, $novo->margin_retail);
        $this->assertDatabaseHas('products', [
            'id' => $novo->id,
            'tenant_id' => $this->tenantA->id,
            'category_id' => $categoria->id,
            'name' => 'Cabo 6mm',
            'unit' => 'MT',
            'is_active' => 1,
            'deleted_at' => null,
        ]);
    }

    public function test_insert_rejects_a_category_from_another_tenant(): void
    {
        $categoriaB = Category::create(['tenant_id' => $this->tenantB->id, 'name' => 'Cabos B']);

        try {
            $this->repository->create($categoriaB->id, 'Cabo', 'UN', '85444900', 10.00, 15.00);
            $this->fail('A procedure deveria recusar a categoria de outra empresa.');
        } catch (QueryException $e) {
            $this->assertStringContainsString('Categoria nao pertence a esta empresa', $e->getMessage());
        }

        $this->assertDatabaseCount('products', 0);
    }

    public function test_updates_retail_price_and_recalculates_margin(): void
    {
        $product = $this->createProduct($this->tenantA, ['cost_price' => 10.00, 'price_retail' => 20.00, 'margin_retail' => 100.00]);

        $result = $this->repository->updateRetailPrice($product->id, 25.00);

        $this->assertEquals(25.00, $result->price_retail);
        $this->assertEquals(150.00, $result->margin_retail);
        $this->assertEquals(25.00, $product->fresh()->price_retail);
    }

    public function test_cannot_update_the_price_of_another_tenants_product(): void
    {
        $productB = $this->createProduct($this->tenantB, ['price_retail' => 20.00]);

        try {
            $this->repository->updateRetailPrice($productB->id, 1.00);
            $this->fail('A procedure deveria recusar o produto de outra empresa.');
        } catch (QueryException $e) {
            $this->assertStringContainsString('Produto nao encontrado nesta empresa', $e->getMessage());
        }

        $this->assertEquals(20.00, Product::withoutGlobalScopes()->find($productB->id)->price_retail);
    }

    public function test_deleting_a_product_is_logical(): void
    {
        $product = $this->createProduct($this->tenantA);

        $result = $this->repository->softDelete($product->id);

        $this->assertNotNull($result->deleted_at);
        $this->assertSoftDeleted('products', ['id' => $product->id]);
        $this->assertNull($this->repository->findById($product->id));
    }

    public function test_cannot_delete_another_tenants_product_or_delete_twice(): void
    {
        $productB = $this->createProduct($this->tenantB);
        $productA = $this->createProduct($this->tenantA);
        $this->repository->softDelete($productA->id);

        foreach ([$productB->id, $productA->id] as $productId) {
            try {
                $this->repository->softDelete($productId);
                $this->fail('A procedure deveria recusar o produto.');
            } catch (QueryException $e) {
                $this->assertStringContainsString('Produto nao encontrado nesta empresa', $e->getMessage());
            }
        }

        $this->assertNotSoftDeleted('products', ['id' => $productB->id]);
    }
}
