<?php

declare(strict_types=1);

namespace Modules\Orcamento\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Cadastros\App\Models\Customer;
use Modules\Core\App\Models\Tenant;
use Modules\Estoque\App\Models\Product;
use Modules\Orcamento\App\Models\Orcamento;
use Tests\TestCase;

class OrcamentoTest extends TestCase
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

    private function createCustomerAndProduct(int $tenantId): array
    {
        $customer = Customer::create([
            'tenant_id' => $tenantId,
            'person_type' => 'fisica',
            'name' => 'Cliente Teste',
            'credit_limit' => 0,
            'default_price_table' => 'retail',
            'is_active' => true,
        ]);

        $product = Product::create([
            'tenant_id' => $tenantId,
            'name' => 'Produto Teste',
            'unit' => 'UN',
            'cost_price' => 10.00,
            'price_retail' => 25.00,
            'ncm' => '12345678',
            'origin' => 0,
            'cfop_default' => '5102',
            'cst_pis' => '01',
            'cst_cofins' => '01',
            'is_active' => true,
        ]);

        return [$customer, $product];
    }

    public function test_operator_can_create_an_orcamento_with_totals_calculated_from_items(): void
    {
        [$tenant, $operator] = $this->createTenantWithUser('11111111000191', 'operator');
        [$customer, $product] = $this->createCustomerAndProduct($tenant->id);

        $response = $this->actingAs($operator)->post(route('orcamentos.store'), [
            'customer_id' => $customer->id,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2, 'unit_price' => 25.00, 'total_price' => 50.00, 'icms_st_value' => 5.00],
            ],
        ]);

        $orcamento = Orcamento::firstOrFail();
        $response->assertRedirect(route('orcamentos.show', $orcamento));
        $this->assertSame('draft', $orcamento->status);
        $this->assertSame(50.0, (float) $orcamento->subtotal);
        $this->assertSame(5.0, (float) $orcamento->total_icms_st);
        $this->assertSame(55.0, (float) $orcamento->total_amount);
        $this->assertDatabaseHas('orcamento_items', ['orcamento_id' => $orcamento->id, 'product_id' => $product->id, 'quantity' => 2]);
    }

    public function test_cannot_create_an_orcamento_without_items(): void
    {
        [$tenant, $user] = $this->createTenantWithUser('22222222000191');
        [$customer] = $this->createCustomerAndProduct($tenant->id);

        $response = $this->actingAs($user)->post(route('orcamentos.store'), [
            'customer_id' => $customer->id,
            'items' => [],
        ]);

        $response->assertSessionHasErrors('items');
        $this->assertDatabaseCount('orcamentos', 0);
    }

    public function test_status_workflow_send_approve_reject(): void
    {
        [$tenant, $user] = $this->createTenantWithUser('33333333000191');
        [$customer, $product] = $this->createCustomerAndProduct($tenant->id);

        $orcamento = Orcamento::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'user_id' => $user->id,
            'status' => Orcamento::STATUS_DRAFT,
            'subtotal' => 0,
            'total_amount' => 0,
        ]);

        $this->actingAs($user)->post(route('orcamentos.send', $orcamento));
        $this->assertSame('sent', $orcamento->fresh()->status);

        $this->actingAs($user)->post(route('orcamentos.approve', $orcamento));
        $this->assertSame('approved', $orcamento->fresh()->status);

        $this->actingAs($user)->post(route('orcamentos.reject', $orcamento));
        $this->assertSame('rejected', $orcamento->fresh()->status);
    }

    public function test_operator_cannot_delete_an_orcamento(): void
    {
        [$tenant, $operator] = $this->createTenantWithUser('44444444000191', 'operator');
        [$customer] = $this->createCustomerAndProduct($tenant->id);

        $orcamento = Orcamento::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'user_id' => $operator->id,
            'status' => Orcamento::STATUS_DRAFT,
            'subtotal' => 0,
            'total_amount' => 0,
        ]);

        $response = $this->actingAs($operator)->delete(route('orcamentos.destroy', $orcamento));

        $response->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('orcamentos', ['id' => $orcamento->id, 'deleted_at' => null]);
    }

    public function test_converting_redirects_to_pos_with_the_quote_id(): void
    {
        [$tenant, $user] = $this->createTenantWithUser('55555555000191');
        [$customer] = $this->createCustomerAndProduct($tenant->id);

        $orcamento = Orcamento::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'user_id' => $user->id,
            'status' => Orcamento::STATUS_APPROVED,
            'subtotal' => 0,
            'total_amount' => 0,
        ]);

        $response = $this->actingAs($user)->post(route('orcamentos.convert', $orcamento));

        $response->assertRedirect(route('vendas.pos.index', ['from_quote' => $orcamento->id]));
    }

    public function test_cannot_convert_an_already_converted_orcamento_again(): void
    {
        [$tenant, $user] = $this->createTenantWithUser('66666666000191');
        [$customer] = $this->createCustomerAndProduct($tenant->id);

        $orcamento = Orcamento::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'user_id' => $user->id,
            'status' => Orcamento::STATUS_CONVERTED,
            'subtotal' => 0,
            'total_amount' => 0,
        ]);

        $response = $this->actingAs($user)->post(route('orcamentos.convert', $orcamento));

        $response->assertRedirect(route('orcamentos.show', $orcamento));
        $response->assertSessionHas('error');
    }

    public function test_user_from_one_tenant_cannot_view_orcamento_from_another_tenant(): void
    {
        [, $userA] = $this->createTenantWithUser('77777777000191');
        [$tenantB, $userB] = $this->createTenantWithUser('88888888000191');
        [$customerB] = $this->createCustomerAndProduct($tenantB->id);

        $orcamentoB = Orcamento::create([
            'tenant_id' => $tenantB->id,
            'customer_id' => $customerB->id,
            'user_id' => $userB->id,
            'status' => Orcamento::STATUS_DRAFT,
            'subtotal' => 0,
            'total_amount' => 0,
        ]);

        $response = $this->actingAs($userA)->get(route('orcamentos.show', $orcamentoB));

        $response->assertNotFound();
    }
}
