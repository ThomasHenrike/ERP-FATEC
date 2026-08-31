<?php

declare(strict_types=1);

namespace Modules\Cadastros\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Cadastros\App\Models\Supplier;
use Modules\Core\App\Models\Tenant;
use Tests\TestCase;

/**
 * Cadastro de Fornecedores e' area de retaguarda: exige papel gerente+ pra
 * tudo (ver, criar, editar, excluir), diferente de Clientes que e' rotina
 * de atendimento liberada pro operador.
 */
class SupplierTest extends TestCase
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

    public function test_operator_cannot_reach_suppliers_screen(): void
    {
        [, $operator] = $this->createTenantWithUser('11111111000191', 'operator');

        $response = $this->actingAs($operator)->get(route('cadastros.fornecedores.index'));

        $response->assertRedirect(route('dashboard'));
    }

    public function test_manager_can_create_a_supplier(): void
    {
        [, $manager] = $this->createTenantWithUser('22222222000191', 'manager');

        $response = $this->actingAs($manager)->post(route('cadastros.fornecedores.store'), [
            'name' => 'Fornecedor Teste',
            'document' => '11222333000144',
            'is_active' => true,
        ]);

        $response->assertRedirect(route('cadastros.fornecedores.index'));
        $this->assertDatabaseHas('suppliers', ['name' => 'Fornecedor Teste']);
    }

    public function test_manager_can_delete_a_supplier(): void
    {
        [$tenant, $manager] = $this->createTenantWithUser('33333333000191', 'manager');
        $supplier = Supplier::create(['tenant_id' => $tenant->id, 'name' => 'Fornecedor X', 'is_active' => true]);

        $response = $this->actingAs($manager)->delete(route('cadastros.fornecedores.destroy', $supplier));

        $response->assertRedirect(route('cadastros.fornecedores.index'));
        $this->assertSoftDeleted('suppliers', ['id' => $supplier->id]);
    }

    public function test_user_from_one_tenant_cannot_edit_supplier_from_another_tenant(): void
    {
        [, $managerA] = $this->createTenantWithUser('44444444000191', 'manager');
        [$tenantB] = $this->createTenantWithUser('55555555000191', 'manager');

        $supplierB = Supplier::create(['tenant_id' => $tenantB->id, 'name' => 'Fornecedor B', 'is_active' => true]);

        $response = $this->actingAs($managerA)->get(route('cadastros.fornecedores.edit', $supplierB));

        $response->assertNotFound();
    }

    public function test_index_search_filters_by_name(): void
    {
        [$tenant, $manager] = $this->createTenantWithUser('66666666000191', 'manager');

        Supplier::create(['tenant_id' => $tenant->id, 'name' => 'Distribuidora Alfa', 'is_active' => true]);
        Supplier::create(['tenant_id' => $tenant->id, 'name' => 'Distribuidora Beta', 'is_active' => true]);

        $response = $this->actingAs($manager)->get(route('cadastros.fornecedores.index', ['search' => 'Alfa']));

        $response->assertOk();
        $response->assertSee('Distribuidora Alfa');
        $response->assertDontSee('Distribuidora Beta');
    }
}
