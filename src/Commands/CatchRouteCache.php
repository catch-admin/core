<?php

declare(strict_types=1);

namespace Catch\Commands;

use Catch\CatchAdmin;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Kernel as ConsoleKernelContract;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Routing\RouteCollection;
class CatchRouteCache extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'catch:route:cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a route cache file for faster route registration';

    /**
     * The filesystem instance.
     *
     * @var Filesystem
     */
    protected Filesystem $files;

    /**
     * @var RouteCollection
     */
    protected RouteCollection $adminRoutes;

    /**
     * @var RouteCollection
     */
    protected RouteCollection $appRoutes;

    /**
     * Create a new route command instance.
     *
     * @param  Filesystem  $files
     * @return void
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct();

        $this->files = $files;

        $this->adminRoutes = new RouteCollection();

        $this->appRoutes = new RouteCollection();
    }

    /**
     * Execute the console command.
     *
     * @return void
     * @throws FileNotFoundException
     */
    public function handle(): void
    {
        $this->callSilent('catch:route:clear');

        $routes = $this->getFreshApplicationRoutes();

        foreach ($routes as $route) {
            if (isset($route->action['controller']) && str_starts_with( $route->action['controller'], 'Modules')) {
                $this->adminRoutes->add($route);
            } else {
                $this->appRoutes->add($route);
            }
        }

        $this->cacheAppRoutes();

        $this->cacheAdminRoutes();
    }

    /**
     *
     * @throws FileNotFoundException
     */
    protected function cacheAdminRoutes()
    {
        if (!count($this->adminRoutes)) {
            $this->components->error("Your application doesn't have any routes.");
            exit;
        }

        foreach ($this->adminRoutes as $route) {
            $route->prepareForSerialization();
        }

        $this->files->put(
            $this->getAdminRouteCachePath(), $this->buildRouteCacheFile($this->adminRoutes)
        );

        $this->components->info('Admin Routes cached successfully.');
    }


    /**
     * @throws FileNotFoundException
     */
    protected function cacheAppRoutes()
    {
        if (!count($this->appRoutes)) {
            $this->components->error("Your application doesn't have any routes.");
            exit;
        }

        foreach ($this->appRoutes as $route) {
            $route->prepareForSerialization();
        }

        $this->files->put(
            $this->laravel->getCachedRoutesPath(), $this->buildRouteCacheFile($this->appRoutes)
        );

        $this->components->info('App Routes cached successfully.');
    }


    /**
     * Boot a fresh copy of the application and get the routes.
     *
     * @return RouteCollection
     */
    protected function getFreshApplicationRoutes(): RouteCollection
    {
        return tap($this->getFreshApplication()['router']->getRoutes(), function ($routes) {
            $routes->refreshNameLookups();
            $routes->refreshActionLookups();
        });
    }

    /**
     * Get a fresh application instance.
     *
     * @return Application
     */
    protected function getFreshApplication(): Application
    {
        return tap(require $this->laravel->bootstrapPath('app.php'), function ($app) {
            $app->make(ConsoleKernelContract::class)->bootstrap();
        });
    }

    /**
     * Build the route cache file.
     *
     * @param RouteCollection $routes
     * @param bool $isAdmin
     * @return string
     */
    protected function buildRouteCacheFile(RouteCollection $routes, bool $isAdmin = false): string
    {
        $stub = <<<TEXT
<?php

/*
|--------------------------------------------------------------------------
| Load The Cached Routes
|--------------------------------------------------------------------------
|
| Here we will decode and unserialize the RouteCollection instance that
| holds all of the route information for an application. This allows
| us to instantaneously load the entire route map into the router.
|
*/

app('router')->setCompiledRoutes(
    {{routes}}
);
TEXT;

        return str_replace('{{routes}}', var_export($routes->compile(), true), $stub);
    }


    /**
     *
     * @return mixed
     */
    protected function getAdminRouteCachePath(): string
    {
        return CatchAdmin::getRouteCachePath();
    }
}
