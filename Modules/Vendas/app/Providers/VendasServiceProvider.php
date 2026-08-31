<?php

declare(strict_types=1);

namespace Modules\Vendas\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

class VendasServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Vendas';
    protected string $nameLower = 'vendas';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    public function boot(): void
    {
        parent::boot();

        // Carregamento direto e explicito do arquivo de rotas
        if (file_exists(module_path($this->name, '/routes/web.php'))) {
            $this->loadRoutesFrom(module_path($this->name, '/routes/web.php'));
        }
    }
}