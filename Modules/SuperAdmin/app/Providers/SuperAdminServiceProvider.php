<?php

namespace Modules\SuperAdmin\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Routing\Router;
use Modules\SuperAdmin\App\Console\Commands\CreateAdminCommand;
use Modules\SuperAdmin\App\Console\Commands\GenerateMonthlyInvoicesCommand;
use Modules\SuperAdmin\App\Console\Commands\MarkOverdueInvoicesCommand;
use Modules\SuperAdmin\App\Console\Commands\UpdateAdminCommand;
use Modules\SuperAdmin\App\Http\Middleware\EnsureAdminRole;
use Nwidart\Modules\Support\ModuleServiceProvider;

class SuperAdminServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'SuperAdmin';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'superadmin';

    /**
     * Command classes to register.
     *
     * @var string[]
     */
    protected array $commands = [
        CreateAdminCommand::class,
        UpdateAdminCommand::class,
        GenerateMonthlyInvoicesCommand::class,
        MarkOverdueInvoicesCommand::class,
    ];

    /**
     * Provider classes to register.
     *
     * @var string[]
     */
    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    /**
     * Define module schedules.
     *
     * @param $schedule
     */
    protected function configureSchedules(Schedule $schedule): void
    {
        $schedule->command('invoices:generate-monthly')->monthlyOn(1, '03:00');
        $schedule->command('invoices:mark-overdue')->dailyAt('01:00');
    }

    public function boot(): void
    {
        parent::boot();

        /** @var Router $router */
        $router = $this->app['router'];

        // Alias usado pra travar rotas do Admin Supremo por papel
        // (admin.role:owner) - funcionarios 'support' recebem 403.
        $router->aliasMiddleware('admin.role', EnsureAdminRole::class);
    }
}
