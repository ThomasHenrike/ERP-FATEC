<?php

declare(strict_types=1);

namespace Modules\SuperAdmin\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Core\App\Models\Tenant;
use Modules\SuperAdmin\App\Models\Admin;
use Modules\SuperAdmin\App\Models\AdminActivityLog;
use Modules\SuperAdmin\App\Models\Ticket;
use Modules\SuperAdmin\App\Models\TicketMessage;

/**
 * "Entrar como empresa" - o admin continua logado no guard 'admin' (guards
 * usam chaves de sessao separadas) e passa a autenticar tambem no guard
 * 'web' como um usuario real da empresa, pra investigar um chamado. Sempre
 * logado via AdminActivityLog, nunca silencioso - ver
 * Modules\Core\App\Http\Middleware\IdentifyTenant (nao precisa de nenhuma
 * mudanca, resolve o tenant normalmente a partir do usuario logado).
 */
class ImpersonationController extends Controller
{
    public function start(Request $request, Ticket $ticket): RedirectResponse
    {
        $tenant = $ticket->tenant;

        $targetUser = $tenant->users()
            ->wherePivot('role', 'admin')
            ->wherePivot('is_active', true)
            ->first()
            ?? $tenant->users()->wherePivot('is_active', true)->first();

        if (!$targetUser) {
            return back()->with('error', 'Esta empresa nao tem nenhum usuario ativo pra simular.');
        }

        // Pedido explicito: a sessao ativa da empresa precisa cair quando o
        // suporte entra como ela - evita duas pessoas mexendo ao mesmo tempo
        // sem saber, e e' o sinal (via redirect pro login) de que algo
        // aconteceu, junto com o aviso deixado no chamado logo abaixo.
        // SESSION_DRIVER e' 'database' em todos os ambientes (ver
        // config/session.php), entao a tabela sessions tem 'user_id'.
        DB::table('sessions')->where('user_id', $targetUser->id)->delete();

        Auth::guard('web')->loginUsingId($targetUser->id);

        session(['impersonation' => [
            'admin_id' => $request->user('admin')->id,
            'admin_name' => $request->user('admin')->name,
            'tenant_id' => $tenant->id,
            'ticket_id' => $ticket->id,
            'started_at' => now()->toIso8601String(),
        ]]);

        // Aviso que fica salvo na propria conversa do chamado - e' o jeito
        // da empresa "saber" mesmo depois de ser desconectada, sem precisar
        // de um sistema de notificacao novo.
        TicketMessage::create([
            'ticket_id' => $ticket->id,
            'admin_id' => $request->user('admin')->id,
            'body' => "Um atendente de suporte entrou na sua conta agora para investigar este chamado (por isso sua sessão pode ter caído). Se não reconhece essa ação, responda aqui imediatamente.",
        ]);
        $ticket->update(['last_message_at' => now()]);

        AdminActivityLog::record(
            $request->user('admin'),
            'impersonation_started',
            $tenant,
            "Entrou como \"{$tenant->name}\" (usuario {$targetUser->name}) a partir do chamado #{$ticket->id}.",
            $ticket
        );

        return redirect()->route('dashboard');
    }

    public function stop(Request $request): RedirectResponse
    {
        $data = session('impersonation');

        if ($data) {
            $tenant = Tenant::find($data['tenant_id']);
            $admin = Admin::find($data['admin_id']);
            $ticket = !empty($data['ticket_id']) ? Ticket::find($data['ticket_id']) : null;

            if ($admin) {
                $duration = Carbon::parse($data['started_at'])->diffForHumans(now(), true);

                AdminActivityLog::record(
                    $admin,
                    'impersonation_ended',
                    $tenant,
                    "Encerrou simulacao como \"" . ($tenant->name ?? '?') . "\" apos {$duration}.",
                    $ticket
                );
            }
        }

        Auth::guard('web')->logout();
        session()->forget('impersonation');

        if (!empty($data['ticket_id'])) {
            return redirect()->route('admin.tickets.show', $data['ticket_id']);
        }

        return redirect()->route('admin.dashboard');
    }
}
