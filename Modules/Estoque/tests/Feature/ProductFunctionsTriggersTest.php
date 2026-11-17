<?php

declare(strict_types=1);

namespace Modules\Estoque\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Core\App\Models\Tenant;
use Modules\Estoque\App\Models\Product;
use Tests\TestCase;

/**
 * Function fn_margem_lucro e trigger trg_produto_historico_preco
 * (Modules/Estoque/database/migrations/2026_11_17_100000_create_estoque_functions_triggers.php).
 * So' rodam no MySQL/MariaDB.
 */
class ProductFunctionsTriggersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (!in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            $this->markTestSkipped('Functions e triggers so existem no MySQL/MariaDB.');
        }
    }

    private function margem(?float $custo, ?float $preco): ?float
    {
        $value = DB::selectOne('SELECT fn_margem_lucro(?, ?) AS m', [$custo, $preco])->m;

        return $value === null ? null : (float) $value;
    }

    /**
     * @return array{0: Tenant, 1: User, 2: Product}
     */
    private function createProduct(): array
    {
        $tenant = Tenant::create(['name' => 'Empresa Estoque', 'document' => '11111111000191', 'is_active' => true]);
        $user = User::factory()->create();
        $tenant->users()->attach($user->id, ['role' => 'manager', 'is_active' => true]);

        $product = Product::create([
            'tenant_id' => $tenant->id,
            'name' => 'Disjuntor 20A',
            'unit' => 'UN',
            'cost_price' => 10.00,
            'price_retail' => 15.00,
            'ncm' => '85362000',
        ]);

        return [$tenant, $user, $product];
    }

    public function test_profit_margin_is_the_markup_over_cost(): void
    {
        $this->assertSame(50.0, $this->margem(100, 150));
        $this->assertSame(45.0, $this->margem(145, 210.25));
        $this->assertSame(-10.0, $this->margem(10, 9));
        $this->assertSame(33.33, $this->margem(3, 4));
    }

    public function test_profit_margin_is_null_without_a_valid_cost_or_price(): void
    {
        $this->assertNull($this->margem(0, 10));
        $this->assertNull($this->margem(-5, 10));
        $this->assertNull($this->margem(null, 10));
        $this->assertNull($this->margem(10, null));
    }

    public function test_price_change_on_the_product_screen_is_recorded_in_history(): void
    {
        [$tenant, $user, $product] = $this->createProduct();

        $this->actingAs($user)->put(route('estoque.produtos.update', $product), [
            'name' => 'Disjuntor 20A',
            'unit' => 'UN',
            'cost_price' => 12.00,
            'price_retail' => 18.00,
            'ncm' => '85362000',
            'origin' => 0,
            'cfop_default' => '5102',
            'cst_pis' => '07',
            'cst_cofins' => '07',
            'is_active' => true,
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseCount('product_price_history', 1);
        $this->assertDatabaseHas('product_price_history', [
            'tenant_id' => $tenant->id,
            'product_id' => $product->id,
            'old_cost_price' => 10.00,
            'new_cost_price' => 12.00,
            'old_price_retail' => 15.00,
            'new_price_retail' => 18.00,
        ]);
    }

    public function test_changes_that_are_not_prices_do_not_write_history(): void
    {
        [, , $product] = $this->createProduct();

        // Estoque (o que toda venda mexe), nome e NCM: nada de historico de preco.
        Product::withoutGlobalScopes()->whereKey($product->id)->update(['current_stock' => 50]);
        Product::withoutGlobalScopes()->whereKey($product->id)->update(['name' => 'Disjuntor DIN 20A', 'ncm' => null]);

        $this->assertDatabaseCount('product_price_history', 0);
    }

    public function test_each_price_change_adds_a_new_row(): void
    {
        [, , $product] = $this->createProduct();

        Product::withoutGlobalScopes()->whereKey($product->id)->update(['price_retail' => 16.00]);
        Product::withoutGlobalScopes()->whereKey($product->id)->update(['price_wholesale' => 13.50]);

        $rows = DB::table('product_price_history')->orderBy('id')->get();
        $this->assertCount(2, $rows);
        $this->assertEquals([15.00, 16.00], [(float) $rows[0]->old_price_retail, (float) $rows[0]->new_price_retail]);
        $this->assertEquals(13.50, (float) $rows[1]->new_price_wholesale);
    }
}
