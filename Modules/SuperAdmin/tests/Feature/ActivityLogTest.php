<?php

declare(strict_types=1);

namespace Modules\SuperAdmin\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Modules\Core\App\Models\Tenant;
use Modules\SuperAdmin\App\Models\Admin;
use Modules\SuperAdmin\App\Models\AdminActivityLog;
use Modules\SuperAdmin\App\Models\Ticket;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin(string $role): Admin
    {
        return Admin::create([
            'name' => ucfirst($role) . ' Teste',
            'email' => $role . '-' . uniqid() . '@erp.local',
            'password' => Hash::make('secret123'),
            'role' => $role,
        ]);
    }

    public function test_owner_can_see_the_activity_log(): void
    {
        $owner = $this->createAdmin('owner');
        $tenant = Tenant::create(['name' => 'Empresa Teste', 'document' => '11111111000191', 'is_active' => true]);

        AdminActivityLog::record($owner, 'impersonation_started', $tenant, 'Entrou como "Empresa Teste".');

        $response = $this->actingAs($owner, 'admin')->get(route('admin.activity-logs.index'));

        $response->assertOk();
        $response->assertSee('Entrou como "Empresa Teste".');
    }

    public function test_support_cannot_see_the_activity_log(): void
    {
        $support = $this->createAdmin('support');

        $this->actingAs($support, 'admin')
            ->get(route('admin.activity-logs.index'))
            ->assertForbidden();
    }

    public function test_impersonation_filter_only_shows_impersonation_actions(): void
    {
        $owner = $this->createAdmin('owner');
        $tenant = Tenant::create(['name' => 'Empresa Teste', 'document' => '22222222000191', 'is_active' => true]);

        AdminActivityLog::record($owner, 'impersonation_started', $tenant, 'Entrou como a empresa.');
        AdminActivityLog::record($owner, 'company_created', $tenant, 'Empresa criada.');

        $response = $this->actingAs($owner, 'admin')->get(route('admin.activity-logs.index', ['impersonation' => 1]));

        $response->assertSee('impersonation_started');
        $response->assertDontSee('company_created');
    }

    public function test_filters_by_ticket_number(): void
    {
        $owner = $this->createAdmin('owner');
        $tenantA = Tenant::create(['name' => 'Empresa A', 'document' => '33333333000191', 'is_active' => true]);
        $tenantB = Tenant::create(['name' => 'Empresa B', 'document' => '44444444000191', 'is_active' => true]);
        $ticketA = Ticket::create(['tenant_id' => $tenantA->id]);
        $ticketB = Ticket::create(['tenant_id' => $tenantB->id]);

        AdminActivityLog::record($owner, 'ticket_replied', $tenantA, 'Respondeu A', $ticketA);
        AdminActivityLog::record($owner, 'ticket_replied', $tenantB, 'Respondeu B', $ticketB);

        $response = $this->actingAs($owner, 'admin')->get(route('admin.activity-logs.index', ['ticket' => $ticketA->id]));

        $response->assertSee('Respondeu A');
        $response->assertDontSee('Respondeu B');
    }

    public function test_filters_by_company_name(): void
    {
        $owner = $this->createAdmin('owner');
        $tenantA = Tenant::create(['name' => 'Padaria Central', 'document' => '55555555000191', 'is_active' => true]);
        $tenantB = Tenant::create(['name' => 'Mercado Silva', 'document' => '66666666000191', 'is_active' => true]);

        AdminActivityLog::record($owner, 'company_updated', $tenantA, 'Atualizou A');
        AdminActivityLog::record($owner, 'company_updated', $tenantB, 'Atualizou B');

        $response = $this->actingAs($owner, 'admin')->get(route('admin.activity-logs.index', ['empresa' => 'Padaria']));

        $response->assertSee('Atualizou A');
        $response->assertDontSee('Atualizou B');
    }
}
