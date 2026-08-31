<?php

declare(strict_types=1);

namespace Modules\SuperAdmin\App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Trava rotas do Admin Supremo por papel (admin.role:owner). Hoje so existe
 * 'owner' como minimo exigido - funcionarios 'support' recebem 403 nessas
 * rotas (empresas, faturas, gestao de outros funcionarios).
 */
class EnsureAdminRole
{
    public function handle(Request $request, Closure $next, string $minRole): Response
    {
        $admin = $request->user('admin');

        if ($minRole === 'owner' && (!$admin || !$admin->isOwner())) {
            abort(403, 'Voce nao tem permissao para acessar esta area.');
        }

        return $next($request);
    }
}
