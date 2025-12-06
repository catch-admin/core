<?php

namespace Catch\Support\Module;

use Catch\CatchAdmin;
use Catch\Contracts\ModuleRepositoryInterface;
use Catch\Facade\Module;
use Catch\Support\Composer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;

/**
 * installer
 */
abstract class Installer
{
    /**
     * construct
     */
    public function __construct(protected ModuleRepositoryInterface $moduleRepository)
    {
    }

    /**
     * module info
     */
    abstract protected function info(): array;

    /**
     * 获取模块信息
     *
     * @return array
     */
    public function getInfo()
    {
        return $this->info();
    }

    /**
     * migrate
     */
    protected function migrate(): void
    {
        if (app()->runningInConsole()) {
            $migrationStr = sprintf('catch:migrate %s', $this->info()['name']);
            command($migrationStr);
        } else {
            Artisan::call('catch:migrate', [
                'module' => $this->info()['name'],
            ]);
        }
    }

    /**
     * seed
     */
    protected function seed(): void
    {
        if (app()->runningInConsole()) {
            $seedStr = sprintf('catch:db:seed %s', $this->info()['name']);
            command($seedStr);
        } else {
            Artisan::call('catch:db:seed', [
                'module' => $this->info()['name'],
            ]);
        }
    }

    /**
     * require packages
     */
    abstract protected function requirePackages(): void;

    /**
     * remove packages
     */
    abstract protected function removePackages(): void;

    /**
     * uninstall
     */
    public function uninstall(): void
    {
        $this->moduleRepository->delete($this->info()['name']);

        $this->removePackages();
    }

    /**
     * invoke
     */
    public function install(): void
    {
        // TODO: Implement __invoke() method.
        $this->moduleRepository->create($this->info());

        $this->migrate();

        $this->seed();

        $this->requirePackages();

        // 获取依赖的模块
        if (method_exists($this, 'dependencies')) {
            $dependencies = $this->dependencies();

            $enabledModules = Module::getEnabled()->pluck('name')->merge(Collection::make(config('catch.module.default')));

            foreach ($dependencies as $dependency) {
                if ($enabledModules->contains($dependency)) {
                    continue;
                }
                $moduleInstaller = CatchAdmin::getModuleInstaller($dependency);
                $moduleInstaller->install();
            }
        }
    }

    /**
     * composer installer
     */
    protected function composer(): Composer
    {
        return app(Composer::class);
    }
}
