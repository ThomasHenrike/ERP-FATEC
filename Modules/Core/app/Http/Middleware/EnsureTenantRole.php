<?php

declare(strict_types=1);

namespace Modules\Core\App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Core\App\Services\TenantManager;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantRole
{
    public function __construct(private readonly TenantManager $tenantManager) {}

    public function handle(Request $request, Closure $next, string $minRole): Response
    {
        if (!$this->tenantManager->hasRoleAtLeast($minRole)) {
            $message = 'Voce nao tem permissao para acessar essa acao. Fale com o administrador da sua empresa.';

            // Varias telas (fechar caixa, sangria/suprimento, etc) chamam a
            // rota via fetch() com JSON, nao um form normal - um redirect
            // aqui vira uma resposta HTML que o JS nao consegue tratar e
            // falha calado. Detecta pelo Content-Type do pedido tambem
            // (isJson), nao so pelo Accept (expectsJson), porque fetch()
            // simples costuma so setar o primeiro.
            if ($request->expectsJson() || $request->isJson()) {
                return response()->json(['message' => $message], 403);
            }

            return redirect()->route('dashboard')->with('error', $message);
        }

        return $next($request);
    }
}
