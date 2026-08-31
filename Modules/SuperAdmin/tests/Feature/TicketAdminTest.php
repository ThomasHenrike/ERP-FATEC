<?php

declare(strict_types=1);

namespace Modules\SuperAdmin\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Modules\Core\App\Models\Tenant;
use Modules\SuperAdmin\App\Models\Admin;
use Modules\SuperAdmin\App\Models\Ticket;
use Modules\SuperAdmin\App\Models\TicketMessage;
use Tests\TestCase;

class TicketAdminTest extends TestCase
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

    private function createTenantWithTicket(): array
    {
        $tenant = Tenant::create([
            'name' => 'Empresa Teste',
            'document' => '11111111000191',
            'is_active' => true,
        ]);

        $user = User::factory()->create();
        $tenant->users()->attach($user->id, ['role' => 'operator', 'is_active' => true]);

        $ticket = Ticket::create(['tenant_id' => $tenant->id, 'user_id' => $user->id, 'last_message_at' => now()]);
        TicketMessage::create(['ticket_id' => $ticket->id, 'user_id' => $user->id, 'body' => 'Preciso de ajuda.']);

        return [$tenant, $ticket];
    }

    public function test_owner_and_support_can_see_the_ticket_queue(): void
    {
        $this->createTenantWithTicket();

        foreach (['owner', 'support'] as $role) {
            $admin = $this->createAdmin($role);

            $this->actingAs($admin, 'admin')
                ->get(route('admin.tickets.index'))
                ->assertOk();
        }
    }

    public function test_support_can_reply_and_close_a_ticket(): void
    {
        [, $ticket] = $this->createTenantWithTicket();
        $support = $this->createAdmin('support');

        $this->actingAs($support, 'admin')
            ->post(route('admin.tickets.reply', $ticket), ['body' => 'Ja estamos verificando.'])
            ->assertRedirect(route('admin.tickets.show', $ticket));

        $this->assertDatabaseHas('ticket_messages', ['ticket_id' => $ticket->id, 'admin_id' => $support->id]);
        $this->assertDatabaseHas('admin_activity_logs', ['admin_id' => $support->id, 'action' => 'ticket_replied']);

        $this->actingAs($support, 'admin')
            ->patch(route('admin.tickets.close', $ticket))
            ->assertRedirect(route('admin.tickets.index'));

        $this->assertDatabaseHas('tickets', ['id' => $ticket->id, 'status' => 'closed']);
    }

    public function test_needs_reply_flips_after_admin_answers(): void
    {
        [, $ticket] = $this->createTenantWithTicket();

        $this->assertTrue($ticket->fresh()->needsReply());

        $admin = $this->createAdmin('support');
        $this->actingAs($admin, 'admin')->post(route('admin.tickets.reply', $ticket), ['body' => 'Respondido.']);

        $this->assertFalse($ticket->fresh()->needsReply());
    }
}
