<?php

declare(strict_types=1);

namespace Modules\Fiscal\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Cadastros\App\Models\Customer;
use Modules\Core\App\Models\Tenant;
use Modules\Estoque\App\Models\Product;
use Modules\Fiscal\App\Models\FiscalDocument;
use Modules\Vendas\App\Models\CashShift;
use Modules\Vendas\App\Models\Sale;
use Modules\Vendas\App\Services\SaleService;
use Tests\TestCase;

/**
 * Fluxo completo de emissao de NF-e: "Emitir NF-e" na venda so' cria um
 * rascunho e leva pra tela de revisao (nunca emite na hora); a emissao de
 * verdade so' acontece quando o operador confirma na tela de revisao. Sem
 * certificado A1 real (Thomas ainda nao tem um), a emissao sempre termina
 * em status "error" de forma controlada - o que estes testes verificam e'
 * exatamente isso, mais a persistencia/edicao do rascunho entre visitas.
 */
class FiscalDocumentControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createTenantWithUser(string $document, string $role = 'admin'): array
    {
        $tenant = Tenant::create([
            'name' => "Empresa {$document}",
            'document' => $document,
            'state_registration' => '123456789',
            'street' => 'Rua das Empresas',
            'number' => '100',
            'neighborhood' => 'Centro',
            'zip_code' => '01000-000',
            'city' => 'Sao Paulo',
            'state' => 'SP',
            'ibge_city_code' => '3550308',
            'is_active' => true,
        ]);

        $user = User::factory()->create();
        $tenant->users()->attach($user->id, ['role' => $role, 'is_active' => true]);

        return [$tenant, $user];
    }

    private function createCompletedSale(Tenant $tenant, User $user, float $price = 25.00): Sale
    {
        $customer = Customer::create([
            'tenant_id' => $tenant->id,
            'person_type' => 'fisica',
            'name' => 'Cliente Teste',
            'street' => 'Rua do Cliente',
            'number' => '50',
            'neighborhood' => 'Bairro Teste',
            'zip_code' => '02000-000',
            'city' => 'Sao Paulo',
            'state' => 'SP',
            'ibge_city_code' => '3550308',
            'credit_limit' => 0,
            'default_price_table' => 'retail',
            'is_active' => true,
        ]);

        $product = Product::create([
            'tenant_id' => $tenant->id,
            'name' => 'Produto Teste',
            'unit' => 'UN',
            'cost_price' => 10.00,
            'price_retail' => $price,
            'ncm' => '12345678',
            'is_active' => true,
        ]);

        CashShift::create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'status' => 'open',
            'opened_at' => now(),
            'opening_balance' => 100,
        ]);

        return app(SaleService::class)->executeSale(
            data: ['tenant_id' => $tenant->id, 'user_id' => $user->id, 'customer_id' => $customer->id, 'subtotal' => $price, 'price_table' => 'retail'],
            items: [['product_id' => $product->id, 'quantity' => 1, 'unit_price' => $price, 'total_price' => $price]],
            payments: [['method' => 'money', 'amount' => $price]]
        );
    }

    /**
     * Monta o payload de PUT fiscal.documents.update a partir dos itens que
     * ja estao no rascunho (copiados da venda por start()) - espelha o que
     * os hidden inputs da tela de revisao mandariam no submit.
     */
    private function updatePayload(FiscalDocument $fiscalDocument, string $action, array $overrides = []): array
    {
        $fiscalDocument->loadMissing('items');

        return array_merge([
            'action' => $action,
            'observacao' => $fiscalDocument->observacao,
            'items' => $fiscalDocument->items->map(fn ($item) => [
                'product_id' => $item->product_id,
                'quantity' => (float) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'discount_amount' => (float) $item->discount_amount,
                'total_price' => (float) $item->total_price,
                'ncm' => $item->ncm,
                'cest' => $item->cest,
                'origin' => $item->origin,
                'cfop' => $item->cfop,
                'csosn' => $item->csosn,
                'cst_icms' => $item->cst_icms,
                'cst_pis' => $item->cst_pis,
                'cst_cofins' => $item->cst_cofins,
                'icms_rate' => $item->icms_rate,
                'pis_rate' => $item->pis_rate,
                'cofins_rate' => $item->cofins_rate,
            ])->all(),
            'forma_pagamento' => 'money',
        ], $overrides);
    }

    public function test_operator_cannot_reach_notas_fiscais_screen(): void
    {
        [, $operator] = $this->createTenantWithUser('11111111000191', 'operator');

        $response = $this->actingAs($operator)->get(route('fiscal.documents.index'));

        $response->assertRedirect(route('dashboard'));
    }

    public function test_manager_can_view_the_empty_index(): void
    {
        [, $manager] = $this->createTenantWithUser('22222222000191', 'manager');

        $response = $this->actingAs($manager)->get(route('fiscal.documents.index'));

        $response->assertOk();
        $response->assertSee('Nenhuma NF-e emitida ainda');
    }

    public function test_starting_emission_creates_a_draft_and_redirects_to_the_review_screen(): void
    {
        [$tenant, $manager] = $this->createTenantWithUser('33333333000191', 'manager');
        $sale = $this->createCompletedSale($tenant, $manager);

        $response = $this->actingAs($manager)->post(route('fiscal.documents.emit', $sale));

        $fiscalDocument = FiscalDocument::where('sale_id', $sale->id)->firstOrFail();
        $response->assertRedirect(route('fiscal.documents.edit', $fiscalDocument));
        $this->assertSame(FiscalDocument::STATUS_DRAFT, $fiscalDocument->status);
        $this->assertNull($fiscalDocument->number, 'Numero da NF-e nao deve ser alocado so por causa do rascunho.');
        $this->assertCount(1, $fiscalDocument->items);
        $this->assertCount(1, $fiscalDocument->payments);
    }

    public function test_starting_emission_twice_for_the_same_sale_reuses_the_draft(): void
    {
        [$tenant, $manager] = $this->createTenantWithUser('39393939000191', 'manager');
        $sale = $this->createCompletedSale($tenant, $manager);

        $this->actingAs($manager)->post(route('fiscal.documents.emit', $sale));
        $this->actingAs($manager)->post(route('fiscal.documents.emit', $sale));

        $this->assertSame(1, FiscalDocument::where('sale_id', $sale->id)->count());
    }

    public function test_manager_can_view_the_review_screen(): void
    {
        [$tenant, $manager] = $this->createTenantWithUser('34343434000191', 'manager');
        $sale = $this->createCompletedSale($tenant, $manager);
        $this->actingAs($manager)->post(route('fiscal.documents.emit', $sale));
        $fiscalDocument = FiscalDocument::where('sale_id', $sale->id)->firstOrFail();

        $response = $this->actingAs($manager)->get(route('fiscal.documents.edit', $fiscalDocument));

        $response->assertOk();
        $response->assertSee('Produto Teste');
    }

    public function test_saving_the_review_screen_replaces_items_and_keeps_it_editable(): void
    {
        [$tenant, $manager] = $this->createTenantWithUser('35353535000191', 'manager');
        $sale = $this->createCompletedSale($tenant, $manager);
        $this->actingAs($manager)->post(route('fiscal.documents.emit', $sale));
        $fiscalDocument = FiscalDocument::where('sale_id', $sale->id)->firstOrFail();

        $payload = $this->updatePayload($fiscalDocument, 'save', [
            'observacao' => 'Garantia de 90 dias.',
        ]);

        $response = $this->actingAs($manager)->put(route('fiscal.documents.update', $fiscalDocument), $payload);

        $response->assertRedirect(route('fiscal.documents.edit', $fiscalDocument));
        $fiscalDocument->refresh();
        $this->assertSame('Garantia de 90 dias.', $fiscalDocument->observacao);
        $this->assertSame(FiscalDocument::STATUS_DRAFT, $fiscalDocument->status);
        $this->assertNull($fiscalDocument->number);
    }

    public function test_saving_with_boleto_splits_installments_without_losing_a_cent(): void
    {
        [$tenant, $manager] = $this->createTenantWithUser('36363636000191', 'manager');
        $sale = $this->createCompletedSale($tenant, $manager, price: 100.00);
        $this->actingAs($manager)->post(route('fiscal.documents.emit', $sale));
        $fiscalDocument = FiscalDocument::where('sale_id', $sale->id)->firstOrFail();

        $payload = $this->updatePayload($fiscalDocument, 'save', [
            'forma_pagamento' => 'boleto',
            'numero_parcelas' => 3,
            'data_primeiro_vencimento' => now()->addDays(30)->format('Y-m-d'),
            'dias_entre_vencimentos' => 30,
        ]);

        $this->actingAs($manager)->put(route('fiscal.documents.update', $fiscalDocument), $payload);

        $valores = $fiscalDocument->fresh()->payments()->orderBy('numero_parcela')->pluck('valor');

        $this->assertCount(3, $valores);
        $this->assertEqualsWithDelta(100.00, $valores->sum(), 0.0001, 'A soma das parcelas deve bater exatamente com o total.');
        $this->assertEqualsCanonicalizing(['33.34', '33.33', '33.33'], $valores->map(fn ($v) => number_format((float) $v, 2, '.', ''))->all());
    }

    public function test_emitting_from_the_review_screen_without_a_certificate_records_a_fiscal_document_with_error_status(): void
    {
        [$tenant, $manager] = $this->createTenantWithUser('33333333000192', 'manager');
        $sale = $this->createCompletedSale($tenant, $manager);
        $this->actingAs($manager)->post(route('fiscal.documents.emit', $sale));
        $fiscalDocument = FiscalDocument::where('sale_id', $sale->id)->firstOrFail();

        $response = $this->actingAs($manager)->put(
            route('fiscal.documents.update', $fiscalDocument),
            $this->updatePayload($fiscalDocument, 'emit')
        );

        $fiscalDocument->refresh();
        $response->assertRedirect(route('fiscal.documents.show', $fiscalDocument));
        $this->assertSame(FiscalDocument::STATUS_ERROR, $fiscalDocument->status);
        $this->assertNotEmpty($fiscalDocument->rejection_reason);
        $this->assertSame(55, $fiscalDocument->model);
        $this->assertSame($tenant->id, $fiscalDocument->tenant_id);
        $this->assertNotNull($fiscalDocument->number, 'O numero deve ser alocado somente na emissao de verdade.');
    }

    public function test_deleting_a_draft_lets_the_operator_start_a_fresh_one_for_the_same_sale(): void
    {
        [$tenant, $manager] = $this->createTenantWithUser('37373737000191', 'manager');
        $sale = $this->createCompletedSale($tenant, $manager);
        $this->actingAs($manager)->post(route('fiscal.documents.emit', $sale));
        $fiscalDocument = FiscalDocument::where('sale_id', $sale->id)->firstOrFail();

        $response = $this->actingAs($manager)->delete(route('fiscal.documents.destroy', $fiscalDocument));

        $response->assertRedirect(route('vendas.sales.index'));
        $this->assertDatabaseMissing('fiscal_documents', ['id' => $fiscalDocument->id]);

        // Emitir de novo pra mesma venda cria um rascunho novo, sem erro de duplicidade.
        $this->actingAs($manager)->post(route('fiscal.documents.emit', $sale))->assertRedirect();
        $this->assertSame(1, FiscalDocument::where('sale_id', $sale->id)->count());
    }

    public function test_deleting_a_draft_never_reuses_its_already_allocated_number(): void
    {
        [$tenant, $manager] = $this->createTenantWithUser('38383838000191', 'manager');
        $saleA = $this->createCompletedSale($tenant, $manager);
        $saleB = $this->createCompletedSale($tenant, $manager);

        // saleA emite (sem certificado, termina em erro) e ja aloca um numero.
        $this->actingAs($manager)->post(route('fiscal.documents.emit', $saleA));
        $docA = FiscalDocument::where('sale_id', $saleA->id)->firstOrFail();
        $this->actingAs($manager)->put(route('fiscal.documents.update', $docA), $this->updatePayload($docA, 'emit'));
        $numeroQueimado = $docA->fresh()->number;

        $this->actingAs($manager)->delete(route('fiscal.documents.destroy', $docA));

        // saleB emite depois - tem que pegar o proximo numero, nunca o que foi excluido.
        $this->actingAs($manager)->post(route('fiscal.documents.emit', $saleB));
        $docB = FiscalDocument::where('sale_id', $saleB->id)->firstOrFail();
        $this->actingAs($manager)->put(route('fiscal.documents.update', $docB), $this->updatePayload($docB, 'emit'));

        $this->assertNotEquals($numeroQueimado, $docB->fresh()->number);
        $this->assertGreaterThan($numeroQueimado, $docB->fresh()->number);
    }

    public function test_deleting_is_blocked_once_the_document_is_authorized(): void
    {
        [$tenant, $manager] = $this->createTenantWithUser('68686868000191', 'manager');
        $sale = $this->createCompletedSale($tenant, $manager);

        $fiscalDocument = FiscalDocument::create([
            'tenant_id' => $tenant->id,
            'sale_id' => $sale->id,
            'customer_id' => $sale->customer_id,
            'model' => FiscalDocument::MODEL_NFE,
            'series' => 1,
            'number' => 1,
            'status' => FiscalDocument::STATUS_AUTHORIZED,
            'environment' => 'homologation',
        ]);

        $this->actingAs($manager)->delete(route('fiscal.documents.destroy', $fiscalDocument))->assertForbidden();
        $this->assertDatabaseHas('fiscal_documents', ['id' => $fiscalDocument->id]);
    }

    public function test_operator_cannot_delete_a_draft(): void
    {
        [$tenant, $manager] = $this->createTenantWithUser('69696969000191', 'manager');
        $operator = User::factory()->create();
        $tenant->users()->attach($operator->id, ['role' => 'operator', 'is_active' => true]);

        $sale = $this->createCompletedSale($tenant, $manager);
        $this->actingAs($manager)->post(route('fiscal.documents.emit', $sale));
        $fiscalDocument = FiscalDocument::where('sale_id', $sale->id)->firstOrFail();

        $response = $this->actingAs($operator)->delete(route('fiscal.documents.destroy', $fiscalDocument));

        $response->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('fiscal_documents', ['id' => $fiscalDocument->id]);
    }

    public function test_the_show_page_explains_the_certificate_gap_without_crashing(): void
    {
        [$tenant, $manager] = $this->createTenantWithUser('44444444000191', 'manager');
        $sale = $this->createCompletedSale($tenant, $manager);
        $this->actingAs($manager)->post(route('fiscal.documents.emit', $sale));
        $fiscalDocument = FiscalDocument::where('sale_id', $sale->id)->firstOrFail();
        $this->actingAs($manager)->put(route('fiscal.documents.update', $fiscalDocument), $this->updatePayload($fiscalDocument, 'emit'));

        $response = $this->actingAs($manager)->get(route('fiscal.documents.show', $fiscalDocument));

        $response->assertOk();
        $response->assertSee('certificado digital A1 valido');
    }

    public function test_two_emission_attempts_never_reuse_the_same_nfe_number(): void
    {
        [$tenant, $manager] = $this->createTenantWithUser('55555555000191', 'manager');
        $saleA = $this->createCompletedSale($tenant, $manager);
        $saleB = $this->createCompletedSale($tenant, $manager);

        $this->actingAs($manager)->post(route('fiscal.documents.emit', $saleA));
        $docA = FiscalDocument::where('sale_id', $saleA->id)->firstOrFail();
        $this->actingAs($manager)->put(route('fiscal.documents.update', $docA), $this->updatePayload($docA, 'emit'));

        $this->actingAs($manager)->post(route('fiscal.documents.emit', $saleB));
        $docB = FiscalDocument::where('sale_id', $saleB->id)->firstOrFail();
        $this->actingAs($manager)->put(route('fiscal.documents.update', $docB), $this->updatePayload($docB, 'emit'));

        $numbers = FiscalDocument::where('tenant_id', $tenant->id)->pluck('number')->all();

        $this->assertCount(2, array_unique($numbers));
    }

    public function test_operator_cannot_trigger_emission(): void
    {
        [$tenant, $operator] = $this->createTenantWithUser('66666666000191', 'operator');
        $sale = $this->createCompletedSale($tenant, $operator);

        $response = $this->actingAs($operator)->post(route('fiscal.documents.emit', $sale));

        $response->assertRedirect(route('dashboard'));
        $this->assertDatabaseCount('fiscal_documents', 0);
    }

    public function test_editing_is_blocked_once_the_document_is_authorized(): void
    {
        [$tenant, $manager] = $this->createTenantWithUser('67676767000191', 'manager');
        $sale = $this->createCompletedSale($tenant, $manager);

        $fiscalDocument = FiscalDocument::create([
            'tenant_id' => $tenant->id,
            'sale_id' => $sale->id,
            'customer_id' => $sale->customer_id,
            'model' => FiscalDocument::MODEL_NFE,
            'series' => 1,
            'number' => 1,
            'status' => FiscalDocument::STATUS_AUTHORIZED,
            'environment' => 'homologation',
        ]);

        $this->actingAs($manager)->get(route('fiscal.documents.edit', $fiscalDocument))->assertForbidden();
        $this->actingAs($manager)->put(
            route('fiscal.documents.update', $fiscalDocument),
            $this->updatePayload($fiscalDocument, 'save')
        )->assertForbidden();
    }

    public function test_user_from_one_tenant_cannot_view_fiscal_document_from_another_tenant(): void
    {
        [$tenantA, $managerA] = $this->createTenantWithUser('77777777000191', 'manager');
        [$tenantB, $managerB] = $this->createTenantWithUser('88888888000191', 'manager');
        $saleB = $this->createCompletedSale($tenantB, $managerB);

        $this->actingAs($managerB)->post(route('fiscal.documents.emit', $saleB));
        $fiscalDocumentB = FiscalDocument::where('tenant_id', $tenantB->id)->firstOrFail();

        $response = $this->actingAs($managerA)->get(route('fiscal.documents.show', $fiscalDocumentB));

        $response->assertNotFound();
    }
}
