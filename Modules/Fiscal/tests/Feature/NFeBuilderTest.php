<?php

declare(strict_types=1);

namespace Modules\Fiscal\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Cadastros\App\Models\Customer;
use Modules\Core\App\Models\Tenant;
use Modules\Estoque\App\Models\Product;
use Modules\Fiscal\App\Models\FiscalDocument;
use Modules\Fiscal\App\Models\FiscalDocumentItem;
use Modules\Fiscal\App\Models\FiscalDocumentPayment;
use Modules\Fiscal\App\Services\NFeBuilderService;
use Modules\Vendas\App\Models\CashShift;
use Modules\Vendas\App\Models\Sale;
use Modules\Vendas\App\Services\SaleService;
use Tests\TestCase;

/**
 * Builder do XML da NF-e (modelo 55) - 100% offline, sem rede e sem
 * certificado real. So' prova que o pipeline FiscalDocument -> XML
 * estruturalmente valido funciona; autorizacao de verdade na SEFAZ fica
 * pra quando Thomas tiver um certificado ICP-Brasil real.
 */
class NFeBuilderTest extends TestCase
{
    use RefreshDatabase;

    private function createTenant(): Tenant
    {
        return Tenant::create([
            'name' => 'Empresa Teste Fiscal LTDA',
            'document' => '12345678000199',
            'state_registration' => '123456789',
            'street' => 'Rua das Empresas',
            'number' => '100',
            'neighborhood' => 'Centro',
            'city' => 'Sao Paulo',
            'state' => 'SP',
            'zip_code' => '01000-000',
            'ibge_city_code' => '3550308',
            'crt' => 1,
            'nfe_series' => 1,
            'nfe_next_number' => 1,
            'sefaz_environment' => 'homologation',
            'is_active' => true,
        ]);
    }

    private function createCustomer(int $tenantId): Customer
    {
        return Customer::create([
            'tenant_id' => $tenantId,
            'person_type' => 'fisica',
            'name' => 'Cliente Teste',
            'document' => '12345678901',
            'street' => 'Rua do Cliente',
            'number' => '50',
            'neighborhood' => 'Bairro',
            'city' => 'Sao Paulo',
            'state' => 'SP',
            'zip_code' => '02000-000',
            'ibge_city_code' => '3550308',
            'credit_limit' => 0,
            'default_price_table' => 'retail',
            'is_active' => true,
        ]);
    }

    private function createSale(Tenant $tenant, Customer $customer): Sale
    {
        $user = User::factory()->create();
        $tenant->users()->attach($user->id, ['role' => 'admin', 'is_active' => true]);

        $product = Product::create([
            'tenant_id' => $tenant->id,
            'name' => 'Cabo Flexivel 2.5mm',
            'unit' => 'M',
            'cost_price' => 2.00,
            'price_retail' => 5.00,
            'ncm' => '85444200',
            'is_active' => true,
        ]);

        CashShift::create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'status' => 'open',
            'opened_at' => now(),
            'opening_balance' => 100,
        ]);

        $saleService = app(SaleService::class);

        return $saleService->executeSale(
            data: [
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'customer_id' => $customer->id,
                'subtotal' => 50.00,
                'price_table' => 'retail',
            ],
            items: [
                ['product_id' => $product->id, 'quantity' => 10, 'unit_price' => 5.00, 'total_price' => 50.00],
            ],
            payments: [
                ['method' => 'money', 'amount' => 50.00],
            ]
        );
    }

    /**
     * Copia a venda pra um FiscalDocument em rascunho, do mesmo jeito que
     * FiscalDocumentController::start() faz de verdade - mantem o retrato
     * fiscal ja gravado no SaleItem.
     */
    private function draftFrom(Sale $sale, Tenant $tenant): FiscalDocument
    {
        $sale->loadMissing('items');

        $doc = FiscalDocument::create([
            'tenant_id' => $tenant->id,
            'sale_id' => $sale->id,
            'customer_id' => $sale->customer_id,
            'model' => FiscalDocument::MODEL_NFE,
            'series' => $tenant->nfe_series,
            'number' => null,
            'status' => FiscalDocument::STATUS_DRAFT,
            'environment' => $tenant->sefaz_environment,
        ]);

        foreach ($sale->items as $item) {
            FiscalDocumentItem::create([
                'tenant_id' => $tenant->id,
                'fiscal_document_id' => $doc->id,
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'discount_amount' => $item->discount_amount,
                'total_price' => $item->total_price,
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
            ]);
        }

        FiscalDocumentPayment::create([
            'tenant_id' => $tenant->id,
            'fiscal_document_id' => $doc->id,
            'forma_pagamento' => 'money',
            'valor' => (float) $sale->total_amount,
        ]);

        return $doc->fresh(['items', 'payments']);
    }

    public function test_builds_a_structurally_valid_nfe_xml_from_a_fiscal_document(): void
    {
        $tenant = $this->createTenant();
        $customer = $this->createCustomer($tenant->id);
        $sale = $this->createSale($tenant, $customer);
        $fiscalDocument = $this->draftFrom($sale, $tenant);

        $builder = app(NFeBuilderService::class);
        $make = $builder->build($fiscalDocument, $tenant, 1);

        $xml = $make->getXML();

        $this->assertNotEmpty($xml);
        $this->assertNotFalse(simplexml_load_string($xml), 'XML da NF-e nao e bem formado.');

        $chave = $make->getChave();
        $this->assertSame(44, strlen($chave), 'Chave de acesso deve ter 44 digitos.');
        $this->assertTrue(ctype_digit($chave), 'Chave de acesso deve conter so digitos.');

        $this->assertSame(55, $make->getModelo());
    }

    /**
     * So' prova que a assinatura XML-DSig aplica corretamente com um
     * certificado valido (mesmo autoassinado) - a SEFAZ nunca aceitaria
     * este certificado especifico (sem cadeia ICP-Brasil), isso fica
     * bloqueado ate Thomas conseguir um certificado real.
     */
    public function test_builds_and_signs_the_nfe_xml_with_a_test_certificate(): void
    {
        $tenant = $this->createTenant();
        $this->artisan('fiscal:test-cert', ['tenant' => $tenant->id])->assertSuccessful();

        $customer = $this->createCustomer($tenant->id);
        $sale = $this->createSale($tenant, $customer);
        $fiscalDocument = $this->draftFrom($sale, $tenant->fresh());

        $builder = app(NFeBuilderService::class);
        $signedXml = $builder->buildAndSign($fiscalDocument, $tenant->fresh(), 1);

        $this->assertNotFalse(simplexml_load_string($signedXml), 'XML assinado nao e bem formado.');
        $this->assertStringContainsString('<Signature', $signedXml);
        $this->assertTrue(
            \NFePHP\Common\Signer::signatureCheck($signedXml),
            'A assinatura XML-DSig nao validou contra o proprio XML.'
        );
    }

    public function test_refuses_to_build_when_tenant_has_no_ibge_city_code(): void
    {
        $tenant = $this->createTenant();
        $customer = $this->createCustomer($tenant->id);
        $sale = $this->createSale($tenant, $customer);
        $fiscalDocument = $this->draftFrom($sale, $tenant);

        $tenant->update(['ibge_city_code' => null]);

        $builder = app(NFeBuilderService::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/codigo IBGE/');

        $builder->build($fiscalDocument, $tenant->fresh());
    }

    public function test_refuses_to_build_for_an_item_without_ncm_or_cfop(): void
    {
        $tenant = $this->createTenant();
        $customer = $this->createCustomer($tenant->id);
        $sale = $this->createSale($tenant, $customer);
        $fiscalDocument = $this->draftFrom($sale, $tenant);

        // Simula um item cujo NCM/CFOP precisa ser corrigido na tela de revisao.
        $fiscalDocument->items()->update(['ncm' => null, 'cfop' => null]);

        $builder = app(NFeBuilderService::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/NCM\/CFOP/');

        $builder->build($fiscalDocument->fresh(['items', 'customer', 'payments']), $tenant);
    }

    public function test_refuses_to_build_when_the_document_has_no_items(): void
    {
        $tenant = $this->createTenant();
        $customer = $this->createCustomer($tenant->id);
        $sale = $this->createSale($tenant, $customer);
        $fiscalDocument = $this->draftFrom($sale, $tenant);

        $fiscalDocument->items()->delete();

        $builder = app(NFeBuilderService::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/nao tem nenhum item/');

        $builder->build($fiscalDocument->fresh(['items', 'customer', 'payments']), $tenant);
    }
}
