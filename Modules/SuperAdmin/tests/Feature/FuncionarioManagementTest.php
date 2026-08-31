<?php

declare(strict_types=1);

namespace Modules\SuperAdmin\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Modules\SuperAdmin\App\Models\Admin;
use Tests\TestCase;

class FuncionarioManagementTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin(string $role = 'owner', bool $isActive = true): Admin
    {
        return Admin::create([
            'name' => ucfirst($role) . ' Teste',
            'email' => $role . '-' . uniqid() . '@erp.local',
            'password' => Hash::make('secret123'),
            'role' => $role,
            'is_active' => $isActive,
        ]);
    }

    public function test_owner_can_list_and_create_funcionarios(): void
    {
        $owner = $this->createAdmin('owner');

        $this->actingAs($owner, 'admin')
            ->get(route('admin.funcionarios.index'))
            ->assertOk();

        $response = $this->actingAs($owner, 'admin')->post(route('admin.funcionarios.store'), [
            'name' => 'Novo Suporte',
            'email' => 'suporte@erp.local',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'support',
        ]);

        $response->assertRedirect(route('admin.funcionarios.index'));
        $this->assertDatabaseHas('admins', [
            'email' => 'suporte@erp.local',
            'role' => 'support',
            'is_active' => true,
        ]);
    }

    public function test_support_role_is_blocked_from_management_routes(): void
    {
        $support = $this->createAdmin('support');

        $this->actingAs($support, 'admin')->get(route('admin.funcionarios.index'))->assertForbidden();
        $this->actingAs($support, 'admin')->get(route('admin.companies.index'))->assertForbidden();
        $this->actingAs($support, 'admin')->get(route('admin.invoices.index'))->assertForbidden();
    }

    public function test_support_role_can_still_see_dashboard(): void
    {
        $support = $this->createAdmin('support');

        $this->actingAs($support, 'admin')
            ->get(route('admin.dashboard'))
            ->assertOk();
    }

    public function test_deactivated_admin_cannot_login(): void
    {
        $admin = $this->createAdmin('support', isActive: false);

        $response = $this->post('/admin/login', [
            'email' => $admin->email,
            'password' => 'secret123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest('admin');
    }

    public function test_owner_cannot_deactivate_own_account(): void
    {
        $owner = $this->createAdmin('owner');

        $response = $this->actingAs($owner, 'admin')
            ->patch(route('admin.funcionarios.toggleActive', $owner));

        $response->assertRedirect();
        $this->assertDatabaseHas('admins', ['id' => $owner->id, 'is_active' => true]);
    }

    public function test_owner_cannot_change_own_role(): void
    {
        $owner = $this->createAdmin('owner');

        $this->actingAs($owner, 'admin')->put(route('admin.funcionarios.update', $owner), [
            'name' => $owner->name,
            'email' => $owner->email,
            'role' => 'support',
        ]);

        $this->assertDatabaseHas('admins', ['id' => $owner->id, 'role' => 'owner']);
    }

    public function test_owner_can_deactivate_another_admin(): void
    {
        $owner = $this->createAdmin('owner');
        $support = $this->createAdmin('support');

        $this->actingAs($owner, 'admin')
            ->patch(route('admin.funcionarios.toggleActive', $support))
            ->assertRedirect(route('admin.funcionarios.index'));

        $this->assertDatabaseHas('admins', ['id' => $support->id, 'is_active' => false]);
    }
}
