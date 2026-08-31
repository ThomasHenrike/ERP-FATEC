<?php

declare(strict_types=1);

namespace Modules\Core\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Core\App\Models\Tenant;
use Modules\SuperAdmin\App\Models\Ticket;
use Modules\SuperAdmin\App\Models\TicketMessage;
use Tests\TestCase;

class SupportTicketTenantTest extends TestCase
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

    public function test_operator_can_open_a_ticket(): void
    {
        [, $user] = $this->createTenantWithUser('11111111000191');

        $response = $this->actingAs($user)->post(route('core.suporte.store'), [
            'body' => 'Preciso de ajuda com o modulo fiscal.',
        ]);

        $response->assertRedirect(route('core.suporte.index'));
        $this->assertDatabaseHas('tickets', ['status' => 'open']);
        $this->assertDatabaseHas('ticket_messages', ['body' => 'Preciso de ajuda com o modulo fiscal.']);
    }

    public function test_user_can_send_a_message_on_their_own_open_ticket(): void
    {
        [$tenant, $user] = $this->createTenantWithUser('22222222000191');

        $ticket = Ticket::create(['tenant_id' => $tenant->id, 'user_id' => $user->id]);

        $response = $this->actingAs($user)->post(route('core.suporte.messages.store', $ticket), [
            'body' => 'Alguma novidade?',
        ]);

        $response->assertRedirect(route('core.suporte.index'));
        $this->assertDatabaseHas('ticket_messages', ['ticket_id' => $ticket->id, 'user_id' => $user->id]);
    }

    public function test_user_cannot_access_another_tenants_ticket(): void
    {
        [$tenantA] = $this->createTenantWithUser('33333333000191');
        [, $userB] = $this->createTenantWithUser('44444444000191');

        $ticket = Ticket::create(['tenant_id' => $tenantA->id]);

        $response = $this->actingAs($userB)->post(route('core.suporte.messages.store', $ticket), [
            'body' => 'Tentando invadir.',
        ]);

        $response->assertNotFound();
    }

    public function test_poll_only_returns_messages_after_given_id(): void
    {
        [$tenant, $user] = $this->createTenantWithUser('55555555000191');

        $ticket = Ticket::create(['tenant_id' => $tenant->id, 'user_id' => $user->id]);
        $first = TicketMessage::create(['ticket_id' => $ticket->id, 'user_id' => $user->id, 'body' => 'Primeira']);
        $second = TicketMessage::create(['ticket_id' => $ticket->id, 'admin_id' => null, 'user_id' => $user->id, 'body' => 'Segunda']);

        $response = $this->actingAs($user)->getJson(route('core.suporte.messages.poll', $ticket) . '?after_id=' . $first->id);

        $response->assertOk();
        $response->assertJsonCount(1, 'messages');
        $response->assertJsonPath('messages.0.id', $second->id);
    }
}
