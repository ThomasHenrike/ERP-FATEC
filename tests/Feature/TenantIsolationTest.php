<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Core\App\Models\Tenant;
use Modules\Estoque\App\Models\Product;
use Tests\TestCase;

/**
 * Cobre o isolamento de dados entre empresas (multi-tenancy), o requisito
 * de seguranca mais critico do sistema: nenhuma empresa pode ver ou
 * acessar registros de outra.
 */
class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private function createTenantWithUser(string $document): array
    {
        $tenant = Tenant::create([
            'name' => "Empresa {$document}",
            'document' => $document,
            'is_active' => true,
        ]);

        $user = User::factory()->create();
        $tenant->users()->attach($user->id, ['role' => 'admin', 'is_active' => true]);

        return [$tenant, $user];
    }

    public function test_product_list_does_not_leak_products_from_another_tenant(): void
    {
        [$tenantA, $userA] = $this->createTenantWithUser('11111111000191');
        [$tenantB] = $this->createTenantWithUser('22222222000191');

        Product::create([
            'tenant_id' => $tenantA->id,
            'name' => 'Produto Da Empresa A',
            'price_retail' => 10.00,
            'ncm' => '12345678',
        ]);

        Product::create([
            'tenant_id' => $tenantB->id,
            'name' => 'Produto Sigiloso Da Empresa B',
            'price_retail' => 20.00,
            'ncm' => '12345678',
        ]);

        $response = $this->actingAs($userA)->get(route('estoque.produtos.index'));

        $response->assertOk();
        $response->assertSee('Produto Da Empresa A');
        $response->assertDontSee('Produto Sigiloso Da Empresa B');
    }

    public function test_user_cannot_open_another_tenants_product_by_direct_id_access(): void
    {
        [, $userA] = $this->createTenantWithUser('11111111000191');
        [$tenantB] = $this->createTenantWithUser('22222222000191');

        $productB = Product::create([
            'tenant_id' => $tenantB->id,
            'name' => 'Produto Sigiloso Da Empresa B',
            'price_retail' => 20.00,
            'ncm' => '12345678',
        ]);

        $response = $this->actingAs($userA)->get(route('estoque.produtos.edit', $productB));

        $response->assertNotFound();
    }
}
