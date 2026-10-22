<?php

declare(strict_types=1);

use Catch\Providers\CatchModuleServiceProvider;
use Illuminate\Config\Repository;
use Illuminate\Console\Application as Artisan;
use Illuminate\Console\Command;
use Illuminate\Container\Container;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Facade;

class ModuleCommandRegistrationProvider extends CatchModuleServiceProvider
{
    protected function moduleName(): string
    {
        return 'regression';
    }

    protected function loadModuleRoute(): void
    {
    }

    protected function configPath(): string
    {
        return __DIR__ . '/missing-config';
    }
}

class ModuleCommandRegistrationCommand extends Command
{
    protected $signature = 'module:regression';
}

beforeEach(function (): void {
    Artisan::forgetBootstrappers();
    Facade::clearResolvedInstances();

    $this->app = new Application();
    $this->app->instance('config', new Repository());

    Facade::setFacadeApplication($this->app);
});

afterEach(function (): void {
    Artisan::forgetBootstrappers();
    Facade::clearResolvedInstances();
    Facade::setFacadeApplication(null);
    Container::setInstance(null);
});

test('module providers register their declared commands', function (bool $declaresCommands): void {
    $provider = $declaresCommands
        ? new class($this->app) extends ModuleCommandRegistrationProvider
        {
            protected array $commands = [ModuleCommandRegistrationCommand::class];
        }
    : new ModuleCommandRegistrationProvider($this->app);

    $this->app->register($provider);

    $artisan = new Artisan($this->app, $this->app->make('events'), Application::VERSION);

    expect($artisan->has('module:regression'))->toBe($declaresCommands);
})->with([
    'declared commands' => [true],
    'inherited empty command list' => [false],
]);
