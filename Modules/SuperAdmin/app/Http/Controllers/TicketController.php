<?php

declare(strict_types=1);

namespace Modules\SuperAdmin\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\SuperAdmin\App\Models\AdminActivityLog;
use Modules\SuperAdmin\App\Models\Ticket;
use Modules\SuperAdmin\App\Models\TicketMessage;

class TicketController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->string('status')->toString();

        $tickets = Ticket::query()
            ->with(['tenant', 'latestMessage'])
            ->when($status, fn ($q, $status) => $q->where('status', $status))
            ->orderByDesc('last_message_at')
            ->paginate(20)
            ->withQueryString();

        return view('superadmin::tickets.index', [
            'tickets' => $tickets,
            'status' => $status,
        ]);
    }

    public function show(Ticket $ticket): View
    {
        $ticket->load(['messages', 'tenant']);

        return view('superadmin::tickets.show', [
            'ticket' => $ticket,
        ]);
    }

    public function reply(Request $request, Ticket $ticket): RedirectResponse
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:4000'],
        ]);

        TicketMessage::create([
            'ticket_id' => $ticket->id,
            'admin_id' => $request->user('admin')->id,
            'body' => $validated['body'],
        ]);

        $ticket->update(['last_message_at' => now()]);

        AdminActivityLog::record(
            $request->user('admin'),
            'ticket_replied',
            $ticket->tenant,
            "Respondeu o chamado #{$ticket->id}.",
            $ticket
        );

        return redirect()->route('admin.tickets.show', $ticket)->with('success', 'Resposta enviada.');
    }

    public function close(Request $request, Ticket $ticket): RedirectResponse
    {
        $ticket->close();

        AdminActivityLog::record(
            $request->user('admin'),
            'ticket_closed',
            $ticket->tenant,
            "Fechou o chamado #{$ticket->id}.",
            $ticket
        );

        return redirect()->route('admin.tickets.index')->with('success', 'Chamado fechado.');
    }
}
