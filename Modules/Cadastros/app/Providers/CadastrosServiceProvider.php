<?php

declare(strict_types=1);

namespace Modules\Cadastros\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

class CadastrosServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Cadastros';
    protected string $nameLower = 'cadastros';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    public function boot(): void
    {
        parent::boot();

        // Carregamento explicito e direto do arquivo de rotas do modulo
        if (file_exists(module_path($this->name, '/routes/web.php'))) {
            $this->loadRoutesFrom(module_path($this->name, '/routes/web.php'));
        }
    }
}