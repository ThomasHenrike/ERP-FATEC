<?php

declare(strict_types=1);

namespace Modules\Vendas\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Core\App\Models\Tenant;
use Tests\TestCase;

/**
 * "Impressão rápida" e "Imprimir automaticamente ao concluir a venda": o layout expõe
 * window.ERP_QUICK_PRINT e o PDV imprime pelo helper erpPrint (iframe) em vez de abrir aba.
 */
class QuickPrintTest extends TestCase
{
    use RefreshDatabase;

    private function login(array $tenantAttributes, string $document = '50000000000101'): User
    {
        $tenant = Tenant::create(array_merge(['name' => 'Empresa QP', 'document' => $document, 'is_active' => true], $tenantAttributes));
        $user = User::factory()->create();
        $tenant->users()->attach($user->id, ['role' => 'admin', 'is_active' => true]);

        return $user;
    }

    public function test_layout_reflects_the_quick_print_setting(): void
    {
        $on = $this->actingAs($this->login(['quick_print' => true]))->get(route('vendas.pos.index'))->assertOk()->getContent();
        $this->assertStringContainsString('window.ERP_QUICK_PRINT = true', $on);

        $off = $this->actingAs($this->login(['quick_print' => false], '50000000000102'))->get(route('vendas.pos.index'))->assertOk()->getContent();
        $this->assertStringContainsString('window.ERP_QUICK_PRINT = false', $off);
    }

    public function test_pdv_prints_the_receipt_through_the_helper(): void
    {
        $html = $this->actingAs($this->login(['quick_print' => true]))->get(route('vendas.pos.index'))->assertOk()->getContent();

        $this->assertStringContainsString('window.erpPrint("/vendas/recibo/" + this.completedSaleId)', $html);
        $this->assertStringContainsString("window.open(url, '_blank', 'noopener')", $html);
    }

    public function test_pdv_receives_the_print_behavior_setting(): void
    {
        $html = $this->actingAs($this->login(['print_behavior' => 'auto']))->get(route('vendas.pos.index'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression("/printBehavior: ['\"]auto['\"]/", $html);
    }

    public function test_quick_print_is_saved_from_the_settings_screen(): void
    {
        $user = $this->login(['quick_print' => false]);
        $tenant = Tenant::where('document', '50000000000101')->firstOrFail();

        $this->actingAs($user)->get(route('core.settings.edit'))->assertOk()->assertSee('name="quick_print"', false);

        $tenant->update(['quick_print' => true]);
        $this->assertTrue($tenant->fresh()->quick_print);
    }
}
