<?php

declare(strict_types=1);

namespace Modules\Core\App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Core\App\Models\Tenant;
use Modules\Core\App\Services\TenantManager;
use Modules\SuperAdmin\App\Models\Admin;
use Modules\SuperAdmin\App\Models\AdminActivityLog;
use Modules\SuperAdmin\App\Models\Ticket;
use Symfony\Component\HttpFoundation\Response;

class IdentifyTenant
{
    public function __construct(
        private readonly TenantManager $tenantManager
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();
        $sessionTenantId = session('active_tenant_id');

        // Busca a empresa na sessao ou a primeira empresa vinculada e ativa do usuario
        // (so conta vinculo com tenant_user.is_active = true - usuario desativado
        // pela propria empresa nao consegue mais acessar)
        $tenant = null;
        if ($sessionTenantId) {
            $tenant = Tenant::where('id', $sessionTenantId)
                ->where('is_active', true)
                ->whereHas('users', fn($q) => $q->where('users.id', $user->id)->where('tenant_user.is_active', true))
                ->first();
        }

        if (!$tenant) {
            $tenant = Tenant::where('is_active', true)
                ->whereHas('users', fn($q) => $q->where('users.id', $user->id)->where('tenant_user.is_active', true))
                ->first();
        }

        if (!$tenant) {
            // Usuario sem empresa vinculada e redirecionado para o onboarding.
            // Precisa deslogar antes: 'cadastrar-empresa' fica no grupo 'guest',
            // que manda usuario logado de volta pro dashboard, que passa por
            // aqui de novo - redirecionamento infinito (reportado pelo Matheus).
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('core.tenant.create')
                ->with('error', 'Cadastre uma empresa para iniciar as operacoes.');
        }

        $role = DB::table('tenant_user')
            ->where('tenant_id', $tenant->id)
            ->where('user_id', $user->id)
            ->value('role') ?? 'operator';

        // Persiste o tenant ativo e o papel do usuario na sessao e no container
        session(['active_tenant_id' => $tenant->id]);
        $this->tenantManager->setTenant($tenant);
        $this->tenantManager->setRole($role);
        app()->instance('currentTenant', $tenant);

        $response = $next($request);

        // Se essa requisicao foi feita durante uma simulacao de acesso
        // ("entrar como empresa", ver Modules\SuperAdmin\App\Http\Controllers\ImpersonationController),
        // qualquer acao de escrita dentro do sistema da empresa (venda, edicao
        // de cadastro, etc) tambem precisa deixar rastro no log de auditoria -
        // sem isso, so "entrou"/"encerrou" ficavam gravados, e o que aconteceu
        // no meio do caminho (ex: uma venda) ficava invisivel pro dono da
        // plataforma.
        $this->logImpersonatedActivity($request, $response, $tenant);

        return $response;
    }

    private function logImpersonatedActivity(Request $request, Response $response, Tenant $tenant): void
    {
        $impersonation = session('impersonation');

        if (!$impersonation || $request->isMethod('GET') || $response->getStatusCode() >= 400) {
            return;
        }

        $admin = Admin::find($impersonation['admin_id']);

        if (!$admin) {
            return;
        }

        $routeName = $request->route()?->getName();

        AdminActivityLog::record(
            $admin,
            'impersonated_action',
            $tenant,
            sprintf('%s %s%s', $request->method(), $request->path(), $routeName ? " ({$routeName})" : ''),
            !empty($impersonation['ticket_id']) ? Ticket::find($impersonation['ticket_id']) : null
        );
    }
}
