<?php

declare(strict_types=1);

namespace Modules\Core\Providers;

use Illuminate\Routing\Router;
use Modules\Core\App\Http\Middleware\EnsureModuleEnabled;
use Modules\Core\App\Http\Middleware\EnsureTenantRole;
use Modules\Core\App\Http\Middleware\IdentifyTenant;
use Modules\Core\App\Services\TenantManager;
use Nwidart\Modules\Support\ModuleServiceProvider;

class CoreServiceProvider extends ModuleServiceProvider
{
    /**
     * Nome do modulo.
     */
    protected string $name = 'Core';

    /**
     * Versao em caixa baixa do nome do modulo.
     */
    protected string $nameLower = 'core';

    /**
     * Providers registrados pelo modulo.
     */
    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    /**
     * Registra servicos e singletons no Container do Laravel.
     */
    public function register(): void
    {
        parent::register();

        // Gerenciador de contexto do tenant ativo como Singleton
        $this->app->singleton(TenantManager::class, function (): TenantManager {
            return new TenantManager();
        });
    }

    /**
     * Executa procedimentos de boot e registro de middlewares.
     */
    public function boot(): void
    {
        parent::boot();

        /** @var Router $router */
        $router = $this->app['router'];

        // Registra o alias 'tenant' para uso simplificado em qualquer modulo
        $router->aliasMiddleware('tenant', IdentifyTenant::class);

        // Alias usado pelos modulos de negocio para travar o acesso quando
        // o Admin Supremo desativa aquele modulo para a empresa
        $router->aliasMiddleware('module.enabled', EnsureModuleEnabled::class);

        // Alias usado pra travar acoes sensiveis por papel dentro da empresa
        // (role:manager ou role:admin)
        $router->aliasMiddleware('role', EnsureTenantRole::class);
    }
}