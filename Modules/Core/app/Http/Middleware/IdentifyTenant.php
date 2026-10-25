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

    //Pelos testes realizado a função abaixo não funciona em um sistema operacional Linux
    //O middleware deteta que o utilizador não está logado (!Auth::check()) e redireciona-o para a rota login
    //Ao tentar abrir a página de login, o middleware volta a ser executado, vê que o utilizador continua sem estar logado e redireciona para o login novamente, gerando o ciclo infinito!
    public function handle(Request $request, Closure $next): Response 
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();
        $sessionTenantId = session('active_tenant_id');

     //Durante os teste foi possivel acessar apenas ajustando o codigo para
     /*public function __construct(
        private readonly TenantManager $tenantManager
    ) {}

        public function handle(Request $request, Closure $next): Response
    {
        Registo de teste para ver o caminho que está a chegar ao middleware
        if ($request->is('login*') || $request->is('admin/login*')) {
            return $next($request);
        }

        if (!Auth::check()) {
            return redirect()->route('login');
        }*/

        $tenant = null;
        if ($sessionTenantId) {
            $tenant = Tenant::where('id', $sessionTenantId)
                ->where('is_active', true)
                ->whereHas('users', fn($q) => $q->where('users.id', $user->id)->where('tenant_user.is_active', true))
                ->first();
        }
        //O if do (!$tenant) também era responsável pelo loop eterno
        if (!$tenant) {
            $tenant = Tenant::where('is_active', true)
                ->whereHas('users', fn($q) => $q->where('users.id', $user->id)->where('tenant_user.is_active', true))
                ->first();
        }
        //Foi possivel continuar com os testes apenas alterando o código para
        /*f (!$tenant) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        
            return redirect()->route('core.tenant.create')
                ->with('error', 'Cadastre uma empresa para iniciar as operacoes.');
        } */

        if (!$tenant) {
            // Usuario sem empresa vinculada e redirecionado para o onboarding
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
