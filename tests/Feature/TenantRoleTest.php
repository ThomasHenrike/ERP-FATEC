<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Cadastros\App\Models\Employee;
use Modules\Core\App\Models\Tenant;
use Tests\TestCase;

/**
 * Cobre o controle de papeis (admin/manager/operator) dentro de uma
 * empresa: um operador nao pode alcancar telas de administrador, e as
 * travas de "ultimo admin" / "nao mexe na propria conta" funcionam.
 * Login e' concedido via cadastro de Funcionario (Cadastros > Equipe),
 * que unifica o cadastro de RH com o acesso ao sistema.
 */
class TenantRoleTest extends TestCase
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

    public function test_operator_cannot_reach_admin_only_settings(): void
    {
        [, $operator] = $this->createTenantWithUser('11111111000191', 'operator');

        $response = $this->actingAs($operator)->get(route('core.settings.edit'));

        $response->assertRedirect(route('dashboard'));
    }

    public function test_admin_can_reach_settings(): void
    {
        [, $admin] = $this->createTenantWithUser('22222222000191', 'admin');

        $response = $this->actingAs($admin)->get(route('core.settings.edit'));

        $response->assertOk();
    }

    public function test_operator_cannot_reach_funcionarios_screen(): void
    {
        [, $operator] = $this->createTenantWithUser('88888888000191', 'operator');

        $response = $this->actingAs($operator)->get(route('cadastros.funcionarios.index'));

        $response->assertRedirect(route('dashboard'));
    }

    public function test_admin_can_create_employee_with_system_access_up_to_max_users_limit(): void
    {
        [$tenant, $admin] = $this->createTenantWithUser('33333333000191', 'admin');
        $tenant->update(['max_users' => 1]);

        $response = $this->actingAs($admin)->post(route('cadastros.funcionarios.store'), [
            'name' => 'Novo Operador',
            'role' => 'vendedor',
            'commission_percentage' => 0,
            'is_active' => true,
            'has_system_access' => true,
            'login_email' => 'novo-operador@teste.local',
            'login_password' => 'senha12345',
            'login_password_confirmation' => 'senha12345',
            'system_role' => 'operator',
        ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('users', ['email' => 'novo-operador@teste.local']);
    }

    public function test_admin_can_create_employee_with_system_access(): void
    {
        [$tenant, $admin] = $this->createTenantWithUser('11122233000191', 'admin');

        $response = $this->actingAs($admin)->post(route('cadastros.funcionarios.store'), [
            'name' => 'Novo Operador',
            'role' => 'vendedor',
            'commission_percentage' => 0,
            'is_active' => true,
            'has_system_access' => true,
            'login_email' => 'novo-operador2@teste.local',
            'login_password' => 'senha12345',
            'login_password_confirmation' => 'senha12345',
            'system_role' => 'operator',
        ]);

        $response->assertRedirect(route('cadastros.funcionarios.index'));
        $this->assertDatabaseHas('users', ['email' => 'novo-operador2@teste.local']);

        $newUser = User::where('email', 'novo-operador2@teste.local')->firstOrFail();
        $this->assertDatabaseHas('employees', ['name' => 'Novo Operador', 'user_id' => $newUser->id]);
        $this->assertDatabaseHas('tenant_user', ['tenant_id' => $tenant->id, 'user_id' => $newUser->id, 'role' => 'operator', 'is_active' => true]);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('senha12345', $newUser->password));
    }

    public function test_admin_can_change_system_role_and_reset_password_of_existing_login(): void
    {
        [$tenant, $admin] = $this->createTenantWithUser('11133344000191', 'admin');
        $operatorUser = User::factory()->create();
        $tenant->users()->attach($operatorUser->id, ['role' => 'operator', 'is_active' => true]);

        $employee = Employee::create([
            'tenant_id' => $tenant->id,
            'user_id' => $operatorUser->id,
            'name' => $operatorUser->name,
            'role' => 'vendedor',
            'commission_percentage' => 0,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->put(route('cadastros.funcionarios.update', $employee), [
            'name' => $employee->name,
            'role' => 'vendedor',
            'commission_percentage' => 0,
            'is_active' => true,
            'system_role' => 'manager',
            'login_password' => 'novaSenha123',
            'login_password_confirmation' => 'novaSenha123',
        ]);

        $response->assertRedirect(route('cadastros.funcionarios.index'));
        $this->assertDatabaseHas('tenant_user', ['tenant_id' => $tenant->id, 'user_id' => $operatorUser->id, 'role' => 'manager']);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('novaSenha123', $operatorUser->fresh()->password));
    }

    public function test_revoking_access_blocks_login_without_deleting_employee(): void
    {
        [$tenant, $admin] = $this->createTenantWithUser('11144455000191', 'admin');
        $operatorUser = User::factory()->create();
        $tenant->users()->attach($operatorUser->id, ['role' => 'operator', 'is_active' => true]);

        $employee = Employee::create([
            'tenant_id' => $tenant->id,
            'user_id' => $operatorUser->id,
            'name' => $operatorUser->name,
            'role' => 'vendedor',
            'commission_percentage' => 0,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->patch(route('cadastros.funcionarios.revokeAccess', $employee));

        $response->assertRedirect(route('cadastros.funcionarios.edit', $employee));
        $this->assertDatabaseHas('tenant_user', ['tenant_id' => $tenant->id, 'user_id' => $operatorUser->id, 'is_active' => false]);
        $this->assertDatabaseHas('employees', ['id' => $employee->id, 'deleted_at' => null]);

        $dashboard = $this->actingAs($operatorUser)->get(route('dashboard'));
        $dashboard->assertRedirect(route('core.tenant.create'));
    }

    public function test_admin_can_create_employee_without_system_access(): void
    {
        [, $admin] = $this->createTenantWithUser('99999999000191', 'admin');

        $response = $this->actingAs($admin)->post(route('cadastros.funcionarios.store'), [
            'name' => 'Ajudante Sem Login',
            'role' => 'ajudante',
            'commission_percentage' => 0,
            'is_active' => true,
        ]);

        $response->assertRedirect(route('cadastros.funcionarios.index'));
        $this->assertDatabaseHas('employees', ['name' => 'Ajudante Sem Login', 'user_id' => null]);
    }

    public function test_user_from_one_tenant_cannot_edit_employee_from_another_tenant(): void
    {
        [, $adminA] = $this->createTenantWithUser('44444444000191', 'admin');
        [$tenantB] = $this->createTenantWithUser('55555555000191', 'admin');

        $employeeB = Employee::create([
            'tenant_id' => $tenantB->id,
            'name' => 'Funcionario B',
            'role' => 'vendedor',
            'commission_percentage' => 0,
            'is_active' => true,
        ]);

        $response = $this->actingAs($adminA)->get(route('cadastros.funcionarios.edit', $employeeB));

        $response->assertNotFound();
    }

    public function test_cannot_deactivate_the_last_active_admin(): void
    {
        [$tenant, $admin] = $this->createTenantWithUser('66666666000191', 'admin');

        $operator = User::factory()->create();
        $tenant->users()->attach($operator->id, ['role' => 'operator', 'is_active' => true]);

        $employee = Employee::create([
            'tenant_id' => $tenant->id,
            'user_id' => $admin->id,
            'name' => $admin->name,
            'role' => 'gerente',
            'commission_percentage' => 0,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->put(route('cadastros.funcionarios.update', $employee), [
            'name' => $admin->name,
            'role' => 'gerente',
            'commission_percentage' => 0,
            'is_active' => false,
        ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('tenant_user', [
            'tenant_id' => $tenant->id,
            'user_id' => $admin->id,
            'is_active' => true,
        ]);
    }

    public function test_deactivated_user_loses_access_immediately(): void
    {
        [$tenant, $admin] = $this->createTenantWithUser('77777777000191', 'admin');
        $other = User::factory()->create();
        $tenant->users()->attach($other->id, ['role' => 'operator', 'is_active' => true]);

        DB::table('tenant_user')->where('tenant_id', $tenant->id)->where('user_id', $other->id)
            ->update(['is_active' => false]);

        $response = $this->actingAs($other)->get(route('dashboard'));

        $response->assertRedirect(route('core.tenant.create'));
    }
}
