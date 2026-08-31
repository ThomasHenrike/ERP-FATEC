<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Core\App\Models\Tenant;
use Modules\Vendas\App\Models\CashShift;
use Tests\TestCase;

/**
 * So' pode existir um caixa aberto por vez, por empresa (uma gaveta
 * fisica) - impede que duas pessoas diferentes abram turnos
 * simultaneamente sem perceber (bug encontrado em 2026-08-29: duas
 * contas admin do mesmo tenant tinham cada uma seu proprio turno aberto).
 */
class CashShiftTest extends TestCase
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

    public function test_second_user_cannot_open_a_shift_while_another_is_open(): void
    {
        [$tenant, $userA] = $this->createTenantWithUser('11111111000191');
        $userB = User::factory()->create();
        $tenant->users()->attach($userB->id, ['role' => 'admin', 'is_active' => true]);

        $this->actingAs($userA)->postJson(route('vendas.caixa.abrir'), ['opening_balance' => 100]);

        $response = $this->actingAs($userB)->postJson(route('vendas.caixa.abrir'), ['opening_balance' => 50]);

        $response->assertStatus(409);
        $response->assertJson(['success' => false]);
        $this->assertDatabaseCount('cash_shifts', 1);
    }

    public function test_same_user_reopening_returns_the_existing_shift(): void
    {
        [, $user] = $this->createTenantWithUser('22222222000191');

        $this->actingAs($user)->postJson(route('vendas.caixa.abrir'), ['opening_balance' => 100]);
        $response = $this->actingAs($user)->postJson(route('vendas.caixa.abrir'), ['opening_balance' => 100]);

        $response->assertOk();
        $response->assertJson(['success' => true]);
        $this->assertDatabaseCount('cash_shifts', 1);
    }

    public function test_can_open_a_new_shift_after_the_previous_one_is_closed(): void
    {
        [$tenant, $userA] = $this->createTenantWithUser('33333333000191');
        $userB = User::factory()->create();
        $tenant->users()->attach($userB->id, ['role' => 'admin', 'is_active' => true]);

        $shift = CashShift::create([
            'tenant_id' => $tenant->id,
            'user_id' => $userA->id,
            'status' => 'closed',
            'opened_at' => now()->subHour(),
            'closed_at' => now(),
            'opening_balance' => 100,
        ]);

        $response = $this->actingAs($userB)->postJson(route('vendas.caixa.abrir'), ['opening_balance' => 100]);

        $response->assertOk();
        $response->assertJson(['success' => true]);
        $this->assertDatabaseCount('cash_shifts', 2);
    }
}
