<?php

namespace Modules\Fiscal\Providers;

use Modules\Fiscal\App\Console\Commands\GenerateTestCertificateCommand;
use Nwidart\Modules\Support\ModuleServiceProvider;

class FiscalServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'Fiscal';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'fiscal';

    /**
     * Command classes to register.
     *
     * @var string[]
     */
    protected array $commands = [
        GenerateTestCertificateCommand::class,
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
}
