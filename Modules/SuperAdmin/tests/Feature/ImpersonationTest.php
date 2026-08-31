<?php

declare(strict_types=1);

namespace Modules\SuperAdmin\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Modules\Core\App\Models\Tenant;
use Modules\SuperAdmin\App\Models\Admin;
use Modules\SuperAdmin\App\Models\Ticket;
use Tests\TestCase;

class ImpersonationTest extends TestCase
{
    use RefreshDatabase;

    private function createTenantWithAdminUser(): array
    {
        $tenant = Tenant::create([
            'name' => 'Empresa Teste',
            'document' => '11111111000191',
            'is_active' => true,
        ]);

        $user = User::factory()->create(['name' => 'Dono da Empresa']);
        $tenant->users()->attach($user->id, ['role' => 'admin', 'is_active' => true]);

        $ticket = Ticket::create(['tenant_id' => $tenant->id, 'user_id' => $user->id]);

        return [$tenant, $user, $ticket];
    }

    private function createAdmin(string $role = 'support'): Admin
    {
        return Admin::create([
            'name' => 'Suporte Teste',
            'email' => 'suporte-' . uniqid() . '@erp.local',
            'password' => Hash::make('secret123'),
            'role' => $role,
        ]);
    }

    public function test_starting_impersonation_logs_in_as_tenant_user_without_dropping_admin_guard(): void
    {
        [$tenant, $user, $ticket] = $this->createTenantWithAdminUser();
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin, 'admin')->post(route('admin.tickets.impersonate', $ticket));

        $response->assertRedirect(route('dashboard'));
        $this->assertTrue(Auth::guard('web')->check());
        $this->assertEquals($user->id, Auth::guard('web')->id());
        $this->assertTrue(Auth::guard('admin')->check());
        $this->assertEquals($admin->id, Auth::guard('admin')->id());

        $this->assertDatabaseHas('admin_activity_logs', [
            'admin_id' => $admin->id,
            'action' => 'impersonation_started',
            'tenant_id' => $tenant->id,
            'ticket_id' => $ticket->id,
        ]);
    }

    public function test_starting_impersonation_kills_the_target_users_existing_session(): void
    {
        [, $user, $ticket] = $this->createTenantWithAdminUser();
        $admin = $this->createAdmin();

        DB::table('sessions')->insert([
            'id' => 'fake-session-id',
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'test',
            'payload' => 'irrelevante',
            'last_activity' => time(),
        ]);

        $this->actingAs($admin, 'admin')->post(route('admin.tickets.impersonate', $ticket));

        $this->assertDatabaseMissing('sessions', ['id' => 'fake-session-id']);
    }

    public function test_starting_impersonation_leaves_a_notice_in_the_ticket(): void
    {
        [, , $ticket] = $this->createTenantWithAdminUser();
        $admin = $this->createAdmin();

        $this->actingAs($admin, 'admin')->post(route('admin.tickets.impersonate', $ticket));

        $this->assertDatabaseHas('ticket_messages', [
            'ticket_id' => $ticket->id,
            'admin_id' => $admin->id,
        ]);
    }

    public function test_actions_taken_while_impersonating_are_logged(): void
    {
        [$tenant, , $ticket] = $this->createTenantWithAdminUser();
        $admin = $this->createAdmin();

        $this->actingAs($admin, 'admin')->post(route('admin.tickets.impersonate', $ticket));

        // Enquanto autenticado como o usuario da empresa (sessao web), qualquer
        // acao de escrita (aqui, mandar uma mensagem no proprio chamado) tem
        // que deixar rastro alem de "entrou"/"encerrou".
        $this->post(route('core.suporte.messages.store', $ticket), ['body' => 'Testando durante a simulação.']);

        $this->assertDatabaseHas('admin_activity_logs', [
            'admin_id' => $admin->id,
            'action' => 'impersonated_action',
            'tenant_id' => $tenant->id,
            'ticket_id' => $ticket->id,
        ]);
    }

    public function test_stopping_impersonation_logs_out_web_guard_only_and_logs_the_end(): void
    {
        [, , $ticket] = $this->createTenantWithAdminUser();
        $admin = $this->createAdmin();

        $this->actingAs($admin, 'admin')->post(route('admin.tickets.impersonate', $ticket));

        $response = $this->post(route('admin.impersonation.stop'));

        $response->assertRedirect(route('admin.tickets.show', $ticket));
        $this->assertFalse(Auth::guard('web')->check());
        $this->assertTrue(Auth::guard('admin')->check());

        $this->assertDatabaseHas('admin_activity_logs', [
            'admin_id' => $admin->id,
            'action' => 'impersonation_ended',
        ]);
    }

    public function test_regular_tenant_user_cannot_start_impersonation(): void
    {
        [, $user, $ticket] = $this->createTenantWithAdminUser();

        $response = $this->actingAs($user)->post(route('admin.tickets.impersonate', $ticket));

        $response->assertRedirect(route('admin.login'));
    }

    public function test_admin_supremo_shows_its_own_banner_while_impersonation_is_active(): void
    {
        [$tenant, , $ticket] = $this->createTenantWithAdminUser();
        $admin = $this->createAdmin();

        $this->actingAs($admin, 'admin')->post(route('admin.tickets.impersonate', $ticket));

        $response = $this->actingAs($admin, 'admin')->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('Você tem uma simulação ativa em');
        $response->assertSee($tenant->name);
    }
}
