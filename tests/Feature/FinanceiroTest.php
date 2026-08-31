<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Core\App\Models\Tenant;
use Modules\SuperAdmin\App\Models\Invoice;
use Tests\TestCase;

/**
 * Aba Financeiro (tenant-facing): avisa o admin da empresa quando a fatura
 * gerenciada pelo Admin Supremo (Modules\SuperAdmin\App\Models\Invoice) esta
 * proxima do vencimento ou vencida.
 */
class FinanceiroTest extends TestCase
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

    public function test_operator_cannot_reach_financeiro_screen(): void
    {
        [, $operator] = $this->createTenantWithUser('11111111000191', 'operator');

        $response = $this->actingAs($operator)->get(route('core.financeiro.index'));

        $response->assertRedirect(route('dashboard'));
    }

    public function test_admin_sees_only_invoices_from_their_own_tenant(): void
    {
        [$tenant, $admin] = $this->createTenantWithUser('22222222000191');
        [$otherTenant] = $this->createTenantWithUser('33333333000191');

        Invoice::create([
            'tenant_id' => $tenant->id,
            'status' => 'pending',
            'amount' => 199.90,
            'due_date' => now()->addDays(10),
            'reference_period' => '2026-09',
        ]);

        Invoice::create([
            'tenant_id' => $otherTenant->id,
            'status' => 'pending',
            'amount' => 999.90,
            'due_date' => now()->addDays(10),
            'reference_period' => '2026-09',
        ]);

        $response = $this->actingAs($admin)->get(route('core.financeiro.index'));

        $response->assertOk();
        $response->assertSee('199,90');
        $response->assertDontSee('999,90');
    }

    public function test_financeiro_shows_whole_days_remaining_not_a_fraction(): void
    {
        [$tenant, $admin] = $this->createTenantWithUser('66666666000191');

        // now()->addDays(2) tem componente de hora/minuto residual (nao e'
        // meia-noite exata) - diffInDays sem arredondar devolveria um valor
        // fracionado tipo "1.9993 dia(s)" em vez de "2 dia(s)".
        Invoice::create([
            'tenant_id' => $tenant->id,
            'status' => 'pending',
            'amount' => 149.00,
            'due_date' => now()->addDays(2),
            'reference_period' => '2026-09',
        ]);

        $response = $this->actingAs($admin)->get(route('core.financeiro.index'));

        $response->assertOk();
        $response->assertSee('2 dia(s)');
    }

    public function test_financeiro_shows_menos_de_um_dia_when_due_today(): void
    {
        [$tenant, $admin] = $this->createTenantWithUser('77777777000191');

        // due_date e' uma data pura (sem hora) - "vence hoje" e' o unico
        // jeito de representar "menos de um dia restante" nesse modelo.
        Invoice::create([
            'tenant_id' => $tenant->id,
            'status' => 'pending',
            'amount' => 149.00,
            'due_date' => today(),
            'reference_period' => '2026-09',
        ]);

        $response = $this->actingAs($admin)->get(route('core.financeiro.index'));

        $response->assertOk();
        $response->assertSee('menos de um dia');
    }

    public function test_dashboard_warns_admin_about_an_overdue_invoice(): void
    {
        [$tenant, $admin] = $this->createTenantWithUser('44444444000191');

        Invoice::create([
            'tenant_id' => $tenant->id,
            'status' => 'overdue',
            'amount' => 199.90,
            'due_date' => now()->subDays(3),
            'reference_period' => '2026-08',
        ]);

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Fatura vencida');
    }

    public function test_dashboard_does_not_warn_operator_about_invoices(): void
    {
        [$tenant, $operator] = $this->createTenantWithUser('55555555000191', 'operator');

        Invoice::create([
            'tenant_id' => $tenant->id,
            'status' => 'overdue',
            'amount' => 199.90,
            'due_date' => now()->subDays(3),
            'reference_period' => '2026-08',
        ]);

        $response = $this->actingAs($operator)->get(route('dashboard'));

        $response->assertOk();
        $response->assertDontSee('Fatura vencida');
    }
}
