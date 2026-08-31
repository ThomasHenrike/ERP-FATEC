<?php

declare(strict_types=1);

namespace Modules\Core\App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureModuleEnabled
{
    public function handle(Request $request, Closure $next, string $module): Response
    {
        $tenant = app()->bound('currentTenant') ? app('currentTenant') : null;

        if ($tenant !== null && !$tenant->hasModule($module)) {
            return redirect()->route('dashboard')
                ->with('error', 'Este recurso esta em desenvolvimento nesta versao do sistema.');
        }

        return $next($request);
    }
}
