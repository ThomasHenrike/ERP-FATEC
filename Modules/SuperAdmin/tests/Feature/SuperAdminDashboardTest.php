<?php

declare(strict_types=1);

namespace Modules\SuperAdmin\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Modules\Core\App\Models\Tenant;
use Modules\SuperAdmin\App\Models\Admin;
use Modules\SuperAdmin\App\Models\Invoice;
use Tests\TestCase;

/**
 * Dashboard do Admin Supremo (fase 3 do roadmap pos-RBAC): MRR, faturamento
 * por mes, crescimento de empresas e status das faturas.
 */
class SuperAdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): Admin
    {
        $admin = Admin::create([
            'name' => 'Admin Teste',
            'email' => 'admin-teste@erp.local',
            'password' => Hash::make('secret123'),
        ]);

        $this->actingAs($admin, 'admin');

        return $admin;
    }

    public function test_regular_tenant_user_cannot_reach_the_admin_dashboard(): void
    {
        $response = $this->get(route('admin.dashboard'));

        $response->assertRedirect(route('admin.login'));
    }

    public function test_dashboard_computes_mrr_from_active_tenants_with_billing_configured(): void
    {
        $this->actingAsAdmin();

        Tenant::create(['name' => 'Ativa Com Cobranca', 'document' => '11111111000191', 'is_active' => true, 'billing_amount' => 199.90]);
        Tenant::create(['name' => 'Ativa Sem Cobranca', 'document' => '22222222000191', 'is_active' => true]);
        Tenant::create(['name' => 'Inativa Com Cobranca', 'document' => '33333333000191', 'is_active' => false, 'billing_amount' => 500.00]);

        $response = $this->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('199,90');
        $response->assertDontSee('500,00');
    }

    public function test_dashboard_shows_invoice_status_breakdown(): void
    {
        $this->actingAsAdmin();

        $tenant = Tenant::create(['name' => 'Empresa X', 'document' => '44444444000191', 'is_active' => true]);

        Invoice::create(['tenant_id' => $tenant->id, 'status' => 'paid', 'amount' => 100, 'due_date' => now()->subMonth(), 'paid_at' => now()->subDays(20)]);
        Invoice::create(['tenant_id' => $tenant->id, 'status' => 'pending', 'amount' => 50, 'due_date' => now()->addDays(10)]);
        Invoice::create(['tenant_id' => $tenant->id, 'status' => 'overdue', 'amount' => 30, 'due_date' => now()->subDays(5)]);

        $response = $this->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('100,00');
        $response->assertSee('50,00');
        $response->assertSee('30,00');
    }

    public function test_dashboard_shows_empty_state_when_there_are_no_invoices(): void
    {
        $this->actingAsAdmin();

        $response = $this->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('Nenhuma fatura registrada ainda.');
    }
}
