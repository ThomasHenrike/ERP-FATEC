<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Modules\Core\App\Http\Middleware\IdentifyTenant;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Garante que autenticacao e identificacao de tenant rodem antes do
        // SubstituteBindings, para que o TenantScope ja esteja ativo quando o
        // Laravel resolve {product}, {sale}, {shift} etc. na URL.
        $middleware->priority([
            \Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class,
            \Illuminate\Auth\Middleware\Authenticate::class,
            IdentifyTenant::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);

        // Sem isso, um acesso nao-autenticado a /admin ou /admin/* cai no login
        // das empresas (guard 'web') em vez do login do Admin Supremo, e
        // vice-versa. is('admin/*') sozinho nao bate na raiz "/admin" (sem
        // barra), entao checa os dois.
        $isAdminPath = fn (Request $request) => $request->is('admin') || $request->is('admin/*');

        $middleware->redirectGuestsTo(fn (Request $request) => $isAdminPath($request)
            ? route('admin.login')
            : route('login'));

        $middleware->redirectUsersTo(fn (Request $request) => $isAdminPath($request)
            ? route('admin.dashboard')
            : route('dashboard'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
