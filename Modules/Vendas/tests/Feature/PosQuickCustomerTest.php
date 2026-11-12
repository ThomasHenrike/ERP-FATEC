<?php

declare(strict_types=1);

namespace Modules\Vendas\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Cadastros\App\Models\Customer;
use Modules\Core\App\Models\Tenant;
use Tests\TestCase;

/**
 * Cadastro rapido de cliente pelo PDV (vendas.clientes.rapido): verificador de
 * CPF/CNPJ pelos digitos verificadores + trava anti-duplicacao.
 */
class PosQuickCustomerTest extends TestCase
{
    use RefreshDatabase;

    private function createTenantWithUser(string $document, string $role = 'operator'): array
    {
        $tenant = Tenant::create(['name' => "Empresa {$document}", 'document' => $document, 'is_active' => true]);
        $user = User::factory()->create();
        $tenant->users()->attach($user->id, ['role' => $role, 'is_active' => true]);

        return [$tenant, $user];
    }

    public function test_pdv_page_renders_with_cpf_checker_and_without_unsupported_features(): void
    {
        [, $user] = $this->createTenantWithUser('11111111000191');

        $this->actingAs($user)->get(route('vendas.pos.index'))
            ->assertOk()
            ->assertSee('IR PARA PAGAMENTO')
            ->assertSee('isValidCpf', false)
            ->assertDontSee('Haver do cliente')
            ->assertDontSee('Ticket de troca')
            ->assertDontSee('SALVAR COMO ORÇAMENTO')
            ->assertDontSee('NFC-e');
    }

    public function test_accepts_valid_cpf_with_or_without_mask(): void
    {
        [, $user] = $this->createTenantWithUser('11111111000191');

        $this->actingAs($user)->postJson(route('vendas.clientes.rapido'), ['name' => 'Maria', 'document' => '529.982.247-25'])
            ->assertOk()->assertJson(['success' => true]);
        $this->actingAs($user)->postJson(route('vendas.clientes.rapido'), ['name' => 'Joao', 'document' => '11144477735'])
            ->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseHas('customers', ['name' => 'Maria', 'document' => '529.982.247-25']);
    }

    public function test_accepts_valid_cnpj(): void
    {
        [, $user] = $this->createTenantWithUser('11111111000191');

        $this->actingAs($user)->postJson(route('vendas.clientes.rapido'), ['name' => 'Fatec', 'document' => '62.823.257/0001-09'])
            ->assertOk()->assertJson(['success' => true]);
    }

    public function test_document_is_optional(): void
    {
        [, $user] = $this->createTenantWithUser('11111111000191');

        $this->actingAs($user)->postJson(route('vendas.clientes.rapido'), ['name' => 'Sem Documento'])
            ->assertOk()->assertJson(['success' => true]);
    }

    public function test_rejects_invalid_cpf_and_cnpj(): void
    {
        [, $user] = $this->createTenantWithUser('11111111000191');

        foreach (['529.982.247-24', '111.111.111-11', '12345', '62.823.257/0001-00', '000000000000000'] as $document) {
            $this->actingAs($user)->postJson(route('vendas.clientes.rapido'), ['name' => 'Invalido', 'document' => $document])
                ->assertStatus(422)->assertJsonValidationErrors('document');
        }

        $this->assertDatabaseMissing('customers', ['name' => 'Invalido']);
    }

    public function test_rejects_duplicate_document_in_same_company_ignoring_mask(): void
    {
        [$tenant, $user] = $this->createTenantWithUser('11111111000191');
        Customer::withoutGlobalScopes()->create(['tenant_id' => $tenant->id, 'name' => 'Ja Existe', 'document' => '52998224725']);

        $this->actingAs($user)->postJson(route('vendas.clientes.rapido'), ['name' => 'Repetido', 'document' => '529.982.247-25'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['document' => 'Já existe um cliente cadastrado com este CPF/CNPJ.']);
    }

    public function test_same_document_is_allowed_in_another_company(): void
    {
        [$otherTenant] = $this->createTenantWithUser('22222222000191');
        Customer::withoutGlobalScopes()->create(['tenant_id' => $otherTenant->id, 'name' => 'Outra Empresa', 'document' => '52998224725']);

        [, $user] = $this->createTenantWithUser('11111111000191');

        $this->actingAs($user)->postJson(route('vendas.clientes.rapido'), ['name' => 'Cliente Novo', 'document' => '529.982.247-25'])
            ->assertOk()->assertJson(['success' => true]);
    }
}
