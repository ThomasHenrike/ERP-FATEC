<?php

declare(strict_types=1);

namespace Modules\Core\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Core\App\Models\Tenant;
use Modules\SuperAdmin\App\Models\Ticket;
use Tests\TestCase;

class AjudaCenterTest extends TestCase
{
    use RefreshDatabase;

    private function createTenantWithUser(string $document, string $role = 'operator'): array
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

    public function test_operator_can_see_the_article_list(): void
    {
        [, $user] = $this->createTenantWithUser('11111111000191');

        $this->actingAs($user)
            ->get(route('core.ajuda.index'))
            ->assertOk()
            ->assertSee('Central de Ajuda')
            ->assertSee('Visão Geral do Sistema');
    }

    public function test_operator_can_open_a_valid_article(): void
    {
        [, $user] = $this->createTenantWithUser('22222222000191');

        $this->actingAs($user)
            ->get(route('core.ajuda.show', 'visao-geral'))
            ->assertOk()
            ->assertSee('Visão Geral do Sistema');
    }

    public function test_invalid_slug_returns_404(): void
    {
        [, $user] = $this->createTenantWithUser('33333333000191');

        $this->actingAs($user)
            ->get(route('core.ajuda.show', 'nao-existe'))
            ->assertNotFound();
    }

    public function test_search_filters_by_title_and_keywords(): void
    {
        [, $user] = $this->createTenantWithUser('44444444000191');

        $response = $this->actingAs($user)->get(route('core.ajuda.index', ['q' => 'nfe']));

        $response->assertOk();
        $response->assertSee('Emissão de Nota Fiscal (NF-e)');
        $response->assertDontSee('Cadastros: Clientes, Fornecedores e Equipe');
    }

    public function test_cta_shows_active_ticket_when_one_exists(): void
    {
        [$tenant, $user] = $this->createTenantWithUser('55555555000191');
        $ticket = Ticket::create(['tenant_id' => $tenant->id, 'user_id' => $user->id]);

        $response = $this->actingAs($user)->get(route('core.ajuda.index'));

        $response->assertSee("Chamado #{$ticket->id}");
    }
}
