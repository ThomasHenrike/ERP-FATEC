<?php

namespace Modules\Orcamento\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;
use Illuminate\Console\Scheduling\Schedule;



class OrcamentoServiceProvider extends ModuleServiceProvider
{
    public function boot(): void
    {
        parent::boot();

        if (file_exists(module_path($this->name, '/routes/web.php'))) {

            $this->loadRoutesFrom(module_path($this->name, '/routes/web.php'));
    }
    }
    /**
     * The name of the module.
     */
    protected string $name = 'Orcamento';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'orcamento';

    /**
     * Command classes to register.
     *
     * @var string[]
     */
    // protected array $commands = [];

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
    // protected function configureSchedules(Schedule $schedule): void
    // {
    //     $schedule->command('inspire')->hourly();
    // }
}
