<?php

declare(strict_types=1);

namespace Modules\Core\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Modules\Core\App\Models\Tenant;
use Modules\SuperAdmin\App\Models\Ticket;
use Modules\SuperAdmin\App\Models\TicketMessage;
use Symfony\Component\HttpFoundation\Response;

class SupportTicketController extends Controller
{
    public function index(): View
    {
        /** @var Tenant $tenant */
        $tenant = app('currentTenant');

        $activeTicket = $tenant->tickets()->open()->latest()->with('messages')->first();

        $history = $tenant->tickets()
            ->closed()
            ->orderByDesc('closed_at')
            ->limit(5)
            ->get();

        return view('core::suporte.index', [
            'activeTicket' => $activeTicket,
            'history' => $history,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        /** @var Tenant $tenant */
        $tenant = app('currentTenant');

        if ($tenant->tickets()->open()->exists()) {
            return redirect()->route('core.suporte.index');
        }

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:4000'],
        ]);

        DB::transaction(function () use ($tenant, $validated) {
            $ticket = Ticket::create([
                'tenant_id' => $tenant->id,
                'user_id' => Auth::id(),
                'last_message_at' => now(),
            ]);

            TicketMessage::create([
                'ticket_id' => $ticket->id,
                'user_id' => Auth::id(),
                'body' => $validated['body'],
            ]);
        });

        return redirect()->route('core.suporte.index')->with('success', 'Chamado aberto com sucesso.');
    }

    public function storeMessage(Request $request, Ticket $ticket): RedirectResponse
    {
        $this->authorizeTicket($ticket);

        if ($ticket->status !== 'open') {
            return back()->with('error', 'Este chamado ja foi fechado. Abra um novo chamado.');
        }

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:4000'],
        ]);

        TicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => Auth::id(),
            'body' => $validated['body'],
        ]);

        $ticket->update(['last_message_at' => now()]);

        return redirect()->route('core.suporte.index');
    }

    public function pollMessages(Request $request, Ticket $ticket): JsonResponse
    {
        $this->authorizeTicket($ticket);

        $afterId = (int) $request->integer('after_id');

        $messages = $ticket->messages()
            ->where('id', '>', $afterId)
            ->orderBy('created_at')
            ->get()
            ->map(fn (TicketMessage $message) => [
                'id' => $message->id,
                'body' => $message->body,
                'from' => $message->isFromAdmin() ? 'admin' : 'you',
                'created_at' => $message->created_at->format('d/m H:i'),
            ]);

        return response()->json([
            'messages' => $messages,
            'status' => $ticket->status,
        ]);
    }

    private function authorizeTicket(Ticket $ticket): void
    {
        /** @var Tenant $tenant */
        $tenant = app('currentTenant');

        abort_unless($ticket->tenant_id === $tenant->id, Response::HTTP_NOT_FOUND);
    }
}
