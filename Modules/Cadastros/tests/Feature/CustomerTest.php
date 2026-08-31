<?php

declare(strict_types=1);

namespace Modules\Cadastros\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Cadastros\App\Models\Customer;
use Modules\Core\App\Models\Tenant;
use Tests\TestCase;

class CustomerTest extends TestCase
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

    private function validCustomerPayload(array $overrides = []): array
    {
        return array_merge([
            'person_type' => 'fisica',
            'name' => 'Cliente Teste',
            'document' => '12345678901',
            'credit_limit' => 500,
            'default_price_table' => 'retail',
            'is_active' => true,
        ], $overrides);
    }

    public function test_operator_can_create_a_customer(): void
    {
        [, $operator] = $this->createTenantWithUser('11111111000191', 'operator');

        $response = $this->actingAs($operator)->post(route('cadastros.clientes.store'), $this->validCustomerPayload());

        $response->assertRedirect(route('cadastros.clientes.index'));
        $this->assertDatabaseHas('customers', ['name' => 'Cliente Teste', 'document' => '12345678901']);
    }

    public function test_cpf_must_have_11_digits_for_pessoa_fisica(): void
    {
        [, $operator] = $this->createTenantWithUser('22222222000191', 'operator');

        $response = $this->actingAs($operator)->post(route('cadastros.clientes.store'), $this->validCustomerPayload(['document' => '123']));

        $response->assertSessionHasErrors('document');
        $this->assertDatabaseMissing('customers', ['name' => 'Cliente Teste']);
    }

    public function test_cnpj_must_have_14_digits_for_pessoa_juridica(): void
    {
        [, $operator] = $this->createTenantWithUser('33333333000191', 'operator');

        $response = $this->actingAs($operator)->post(route('cadastros.clientes.store'), $this->validCustomerPayload([
            'person_type' => 'juridica',
            'document' => '12345678901', // 11 digitos, precisa de 14
        ]));

        $response->assertSessionHasErrors('document');
    }

    public function test_operator_cannot_delete_a_customer(): void
    {
        [$tenant, $operator] = $this->createTenantWithUser('44444444000191', 'operator');
        $customer = Customer::create($this->validCustomerPayload(['tenant_id' => $tenant->id]));

        $response = $this->actingAs($operator)->delete(route('cadastros.clientes.destroy', $customer));

        $response->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('customers', ['id' => $customer->id, 'deleted_at' => null]);
    }

    public function test_manager_can_delete_a_customer(): void
    {
        [$tenant, $manager] = $this->createTenantWithUser('55555555000191', 'manager');
        $customer = Customer::create($this->validCustomerPayload(['tenant_id' => $tenant->id]));

        $response = $this->actingAs($manager)->delete(route('cadastros.clientes.destroy', $customer));

        $response->assertRedirect(route('cadastros.clientes.index'));
        $this->assertSoftDeleted('customers', ['id' => $customer->id]);
    }

    public function test_user_from_one_tenant_cannot_edit_customer_from_another_tenant(): void
    {
        [, $userA] = $this->createTenantWithUser('66666666000191');
        [$tenantB] = $this->createTenantWithUser('77777777000191');

        $customerB = Customer::create($this->validCustomerPayload(['tenant_id' => $tenantB->id]));

        $response = $this->actingAs($userA)->get(route('cadastros.clientes.edit', $customerB));

        $response->assertNotFound();
    }

    public function test_index_filters_by_status_and_search(): void
    {
        [$tenant, $user] = $this->createTenantWithUser('88888888000191');

        Customer::create($this->validCustomerPayload(['tenant_id' => $tenant->id, 'name' => 'Joao Ativo', 'document' => null, 'is_active' => true]));
        Customer::create($this->validCustomerPayload(['tenant_id' => $tenant->id, 'name' => 'Maria Inativa', 'document' => null, 'is_active' => false]));

        $response = $this->actingAs($user)->get(route('cadastros.clientes.index', ['status' => 'inactive']));

        $response->assertOk();
        $response->assertSee('Maria Inativa');
        $response->assertDontSee('Joao Ativo');
    }
}
