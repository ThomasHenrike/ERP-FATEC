<?php

declare(strict_types=1);

namespace Modules\SuperAdmin\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Modules\Core\App\Models\Tenant;
use Modules\SuperAdmin\App\Models\Admin;
use Tests\TestCase;

/**
 * O guard 'admin' (Admin Supremo) precisa ficar totalmente isolado do
 * guard 'web' (usuarios de empresas) - um nunca pode autenticar no outro,
 * e usuarios comuns nunca podem alcancar rotas /admin/*.
 */
class AdminGuardIsolationTest extends TestCase
{
    use RefreshDatabase;

    private function createTenantWithUser(string $document): array
    {
        $tenant = Tenant::create([
            'name' => "Empresa {$document}",
            'document' => $document,
            'is_active' => true,
        ]);

        $user = User::factory()->create();
        $tenant->users()->attach($user->id, ['role' => 'admin', 'is_active' => true]);

        return [$tenant, $user];
    }

    public function test_regular_tenant_user_cannot_reach_admin_routes(): void
    {
        [, $user] = $this->createTenantWithUser('11111111000191');

        $response = $this->actingAs($user)->get('/admin/empresas');

        $response->assertRedirect(route('admin.login'));
    }

    public function test_admin_guard_and_web_guard_are_independent(): void
    {
        $admin = Admin::create([
            'name' => 'Admin Teste',
            'email' => 'admin-teste@erp.local',
            'password' => Hash::make('secret123'),
        ]);

        $this->post('/admin/login', [
            'email' => $admin->email,
            'password' => 'secret123',
        ]);

        $this->assertTrue(Auth::guard('admin')->check());
        $this->assertFalse(Auth::guard('web')->check());

        // Estar logado como admin nao desbloqueia rota de empresa nenhuma.
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_web_login_does_not_authenticate_admin_guard(): void
    {
        [, $user] = $this->createTenantWithUser('22222222000191');

        $this->actingAs($user, 'web');

        $this->assertTrue(Auth::guard('web')->check());
        $this->assertFalse(Auth::guard('admin')->check());
    }
}
