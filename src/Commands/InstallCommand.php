<?php

// +----------------------------------------------------------------------
// | CatchAdmin [Just Like ～ ]
// +----------------------------------------------------------------------
// | Copyright (c) 2017 ~ now https://catchadmin.vip All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( https://github.com/JaguarJack/catchadmin/blob/master/LICENSE.md )
// +----------------------------------------------------------------------
// | Author: JaguarJack [ njphper@gmail.com ]
// +----------------------------------------------------------------------

namespace Catch\Commands;

use Catch\CatchAdmin;
use Catch\Support\Composer;
use Illuminate\Console\Application;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Connectors\ConnectionFactory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\Process\ExecutableFinder;

use function Laravel\Prompts\progress;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class InstallCommand extends CatchCommand
{
    protected bool $isFinished = false;

    protected $signature = 'catch:install {--prod}';

    protected $description = 'install catch admin';
    protected string $webRepo = 'https://gitee.com/catchadmin/catch-admin-vue.git';

    /**
     * 默认链接 [mysql, pgsql]
     *
     * @var string
     */
    protected string $defaultConnection;

    protected bool $isProd;

    protected string $appUrl = 'http://127.0.0.1:8000';

    protected string $appName = '';

    /**
     * @var array|string[]
     */
    private array $defaultExtensions = ['bcmath', 'ctype', 'intl', 'dom', 'mysqli', 'fileinfo', 'json', 'mbstring', 'openssl', 'pcre', 'pdo', 'tokenizer', 'xml', 'pdo_mysql'];

    /**
     * handle
     */
    public function handle(): void
    {
        $this->detectionEnvironment();

        // 是否是生产环境
        $this->isProd = $this->option('prod');

        // 捕捉退出信号
        if (extension_loaded('pcntl')) {
            $this->trap([SIGTERM, SIGQUIT, SIGINT], function () {
                if (! $this->isFinished) {
                    $this->rollback();
                }

                exit;
            });
        }

        try {
            // 如果没有 .env 文件
            if (! File::exists(app()->environmentFile())) {
                // 创建数据库
                $this->askForCreatingDatabase();
                // 发布配置 && 初始化数据库结构和数据
                $this->publishConfig();
                // 创建软连接
                $this->createStorageLink();
                // 安装前端
                $this->installed();
            }
            // 展示信息
            $this->showInfo();
        } catch (\Throwable $e) {
            $this->rollback();

            $this->error($e->getMessage());
        }
    }

    /**
     * 环境检测
     */
    protected function detectionEnvironment(): void
    {
        $this->checkDependenciesTools();

        $this->checkPHPVersion();

        $this->checkExtensions();
    }

    /**
     * check needed php extensions
     */
    private function checkExtensions(): void
    {
        /* @var  Collection $loadedExtensions */
        $loadedExtensions = Collection::make(get_loaded_extensions())->map(function ($item) {
            return strtolower($item);
        });

        $unLoadedExtensions = [];
        foreach ($this->defaultExtensions as $extension) {
            if (! $loadedExtensions->contains($extension)) {
                $unLoadedExtensions[] = $extension;
            }
        }

        if (count($unLoadedExtensions) > 0) {
            $this->error('PHP 扩展未安装:'.implode(' | ', $unLoadedExtensions));
            exit;
        }
    }

    /**
     * check php version
     */
    private function checkPHPVersion(): void
    {
        if (version_compare(PHP_VERSION, '8.2.0', '<')) {
            $this->error('PHP 版本必须大于 8.2');
        }
    }

    /**
     * 检测依赖
     */
    protected function checkDependenciesTools(): void
    {
        $executeFinder = new ExecutableFinder;
        $composer = $executeFinder->find('composer');
        $git = $executeFinder->find('git');
        $node = $executeFinder->find('node');
        $yarn = $executeFinder->find('yarn');
        if (! $git) {
            $this->error('Git 未安装');
            exit;
        }
        if (! $composer) {
            $this->error('Composer 未安装');
            exit;
        }
        if (! $node) {
            $this->error('Node 未安装');
            $this->info('请先安装 Node(LTS) 环境: https://nodejs.org');
            exit;
        }
        if (! $yarn) {
            $this->error('Yarn 未安装');
            $this->info('请先安装 Yarn 管理工具: https://classic.yarnpkg.com/lang/en/docs/install');
            exit;
        }

        if (! function_exists('exec')) {
            $this->error('exec 函数未开启，请开启 exec 函数');
            exit;
        }
    }

    /**
     * @return void
     */
    private function createStorageLink(): void
    {
        command('storage:link');
    }

    /**
     * create database
     *
     * @throws BindingResolutionException
     */
    private function createDatabase(string $databaseName, string $driver): void
    {
        if ($driver == 'mysql') {
            $databaseConfig = config('database.connections.'.DB::getDefaultConnection());

            $databaseConfig['database'] = null;

            $connection = app(ConnectionFactory::class)->make($databaseConfig);
            try {
                $connection->getPdo();
            } catch (\Throwable $e) {
                if ($e->getCode() === 2002) {
                    $this->error('Mysql 无法连接，请查看 MySQL 服务是否启动');
                } else {
                    $this->error($e->getMessage());
                }
                exit;
            }

            if (! $connection->getDatabaseName()) {
                app(ConnectionFactory::class)->make($databaseConfig)->select(sprintf("create database if not exists $databaseName default charset %s collate %s", 'utf8mb4', 'utf8mb4_general_ci'));
            }
        } else {
            $databaseConfig = config('database.connections.'.$driver);

            $databaseConfig['database'] = null;

            $connection = app(ConnectionFactory::class)->make($databaseConfig);
            try {
                $connection->getPdo();
            } catch (\Throwable $e) {
                if ($e->getCode() === 7) {
                    $this->error('PgSQL 无法连接，请查看 PgSQL 服务是否启动');
                } else {
                    $this->error($e->getMessage());
                }
                exit;
            }

            if (! $connection->getDatabaseName()) {
                app(ConnectionFactory::class)->make($databaseConfig)
                    ->select(sprintf("create database $databaseName WITH ENCODING = '%s' LC_COLLATE = 'en_US.UTF-8' LC_CTYPE = 'en_US.UTF-8' TEMPLATE = template0;", 'UTF-8'));
            }
        }
    }

    /**
     * copy .env
     */
    protected function copyEnvFile(): void
    {
        if (! File::exists(app()->environmentFilePath())) {
            File::copy(app()->environmentFilePath().'.example', app()->environmentFilePath());
        }

        if (! File::exists(app()->environmentFilePath())) {
            $this->error('【.env】创建失败, 请重新尝试或者手动创建！');
        }

        File::put(app()->environmentFile(), implode("\n", explode("\n", $this->getEnvFileContent())));
    }

    /**
     * get env file content
     */
    protected function getEnvFileContent(): string
    {
        return File::get(app()->environmentFile());
    }

    /**
     * publish config
     */
    protected function publishConfig(): void
    {
        try {
            // mac os
            if (Str::of(PHP_OS)->lower()->contains('dar')) {
                exec(Application::formatCommandString('key:generate'));
                exec(Application::formatCommandString('vendor:publish --tag=catch-config'));
                if ($this->isShouldPublishSanctum()) {
                    exec(Application::formatCommandString('vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"'));
                }

                exec(Application::formatCommandString('migrate'));
            } else {
                Process::run(Application::formatCommandString('key:generate'))->throw();
                Process::run(Application::formatCommandString('vendor:publish --tag=catch-config'))->throw();
                if ($this->isShouldPublishSanctum()) {
                    Process::run(Application::formatCommandString('vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"'))->throw();
                }
                Process::run(Application::formatCommandString('migrate'))->throw();
            }

            foreach (['user', 'develop', 'permissions', 'system'] as $name) {
                $this->migrateModule($name);
            }

        } catch (\Exception|\Throwable $e) {
            $this->warn($e->getMessage());
        }
    }

    protected function migrateModule(string $name): void
    {
        $migrationStr = sprintf('catch:migrate %s', $name);
        $seedStr = sprintf('catch:db:seed %s', $name);

        command([$migrationStr, $seedStr]);
    }

    /**
     * create database
     * @throws BindingResolutionException
     */
    protected function askForCreatingDatabase(): void
    {
        $this->appName = text('请输入应用名称', required: '应用名称必须填写');

        $this->appUrl = text(
            label: '请配置应用的 URL',
            placeholder: 'eg. http://127.0.0.1:8000',
            default: $this->isProd ? 'https://' : 'http://127.0.0.1:8000',
            required: '应用的 URL 必须填写',
            validate: fn ($value) => filter_var($value, FILTER_VALIDATE_URL) !== false ? null : '应用URL不符合规则'
        );

        $this->defaultConnection = select(
            label: '选择数据库驱动',
            options: ['mysql', 'pgsql'],
            default: 'mysql',
        );

        if ($this->defaultConnection == 'pgsql' && ! extension_loaded('pdo_pgsql')) {
            $this->error('请先安装 pdo_pgsql 扩展');
            exit;
        }

        $databaseName = text('请输入数据库名称', required: '请输入数据库名称', validate: fn ($value) => preg_match("/[a-zA-Z\_]{1,100}/", $value) ? null : '数据库名称只支持a-z和A-Z以及下划线_');
        $prefix = text('请输入数据库表前缀');
        $dbHost = text('请输入数据库主机地址', 'eg. 127.0.0.1', '127.0.0.1', required: '请输入数据库主机地址');
        $dbPort = text('请输入数据库主机地址', 'eg. 3306', $this->defaultConnection === 'mysql' ? '3306' : '5432', required: '请输入数据库主机地址');
        $dbUsername = text('请输入数据的用户名', 'eg. root', 'root', required: '请输入数据的用户名');
        $dbPassword = text('请输入数据库密码', required: '请输入数据库密码');

        config()->set('database.default', $this->defaultConnection);
        config()->set('database.connections.'.$this->defaultConnection.'.host', $dbHost);
        config()->set('database.connections.'.$this->defaultConnection.'.port', $dbPort);
        config()->set('database.connections.'.$this->defaultConnection.'.database', $databaseName);
        config()->set('database.connections.'.$this->defaultConnection.'.username', $dbUsername);
        config()->set('database.connections.'.$this->defaultConnection.'.password', $dbPassword);
        config()->set('database.connections.'.$this->defaultConnection.'.prefix', $prefix);

        $this->info("正在创建数据库[$databaseName]...");

        $this->createDatabase($databaseName, $this->defaultConnection);

        $this->info("创建数据库[$databaseName] 成功");

        // 写入 .env
        $this->createEnvFile(
            $this->appName,
            $this->appUrl,
            $this->defaultConnection,
            $dbHost,
            $dbPort,
            $databaseName,
            $dbUsername,
            $dbPassword,
            $prefix
        );

        // 设置默认字符串长度
        Schema::connection($this->defaultConnection)->defaultStringLength(191);
    }

    protected function resetEnvValue($originValue, $newValue): string
    {
        if (Str::contains($originValue, '=')) {
            $originValue = explode('=', $originValue);

            $originValue[1] = $newValue;

            return implode('=', $originValue);
        }

        return $originValue;
    }

    /**
     * add prs4 autoload
     */
    protected function addPsr4Autoload(): void
    {
        $composerJson = $this->getComposerJson();

        $composerJson['autoload']['psr-4'][CatchAdmin::getModuleRootNamespace()] = str_replace('\\', '/', CatchAdmin::moduleRoot());

        File::put($this->getComposerFile(), json_encode($composerJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $this->info('composer dump autoload..., 请耐心等待');

        app(Composer::class)->dumpAutoloads();
    }

    protected function getComposerJson(): mixed
    {
        return json_decode(File::get($this->getComposerFile()), true);
    }

    protected function getComposerFile(): string
    {
        return base_path().DIRECTORY_SEPARATOR.'composer.json';
    }

    /**
     * 安装前端项目
     */
    protected function installFrontProject(): void
    {
        try {
            $this->cloneWeb();

            Process::run('yarn config set registry https://registry.npmmirror.com');

            $progress = progress(label: '安装前端依赖', steps: 100);

            $advance = 0;
            $process = Process::path(app()->basePath().DIRECTORY_SEPARATOR.'web')->start('yarn install');
            while ($process->running()) {
                usleep(600 * 1000);
                $step = rand(1, 8);
                $advance += $step;
                if ($advance <= 99) {
                    $progress->advance($step);
                }
            }
            $process->wait();
            $progress->advance(100);
            $progress->finish();

            $this->createWebEnv();
        } catch (\Exception $exception) {
            $this->output->error('安装前端依赖失败，请检查是否已安装了前端 yarn 管理工具，或者因为网络等原因');
        }
    }

    /**
     * admin installed
     */
    public function installed(): void
    {
        $this->installFrontProject();

        $this->addPsr4Autoload();

        $this->info('🎉 CatchAdmin 已安装, 欢迎!');

        $this->isFinished = true;

        // 安装插件管理
        command('catch:plugin-install');
    }

    /**
     * show info
     *
     * @return void
     */
    protected function showInfo()
    {
        $this->output->info(sprintf('
 /------------------------ welcome ----------------------------\
|               __       __       ___       __          _      |
|   _________ _/ /______/ /_     /   | ____/ /___ ___  (_)___  |
|  / ___/ __ `/ __/ ___/ __ \   / /| |/ __  / __ `__ \/ / __ \ |
| / /__/ /_/ / /_/ /__/ / / /  / ___ / /_/ / / / / / / / / / / |
| \___/\__,_/\__/\___/_/ /_/  /_/  |_\__,_/_/ /_/ /_/_/_/ /_/  |
|                                                              |
 \ __ __ __ __ _ __ _ __ enjoy it ! _ __ __ __ __ __ __ ___ _ @
 版本: %s
 初始账号: catch@admin.com
 初始密码: catchadmin', '🚀 5.x'));

        $this->support();
    }

    /**
     * support
     */
    protected function support(): void
    {
        $answer = $this->askFor('非常感谢支持我们! 是否打开文档', 'yes', true);

        if (in_array(strtolower($answer), ['yes', 'y'])) {
            if (PHP_OS_FAMILY == 'Darwin') {
                exec('open https://doc.catchadmin.com/docs/5.0/intro');
            }
            if (PHP_OS_FAMILY == 'Windows') {
                exec('start https://doc.catchadmin.com/docs/5.0/intro');
            }
            if (PHP_OS_FAMILY == 'Linux') {
                exec('xdg-open https://doc.catchadmin.com/docs/5.0/intro');
            }
        }

        $this->info('官 网: https://catchadmin.com');
        $this->info('文 档: https://doc.catchadmin.com/docs/3.0/intro');
        try {
            $this->parseDevServer();
            $this->info('启动 Go: composer run dev');
        } catch (\Throwable $exception) {
            // 丢弃异常，因为这不影响项目启动
        }
    }

    protected function createEnvFile(
        $appName,
        $appUrl,
        $driver,
        $dbHost,
        $dbPort,
        $databaseName,
        $dbUsername,
        $dbPassword,
        $prefix
    ): void {
        // 后端项目 .env
        $this->copyEnvFile();

        $env = explode("\n", $this->getEnvFileContent());

        foreach ($env as &$value) {
            foreach ([
                         'APP_NAME' => $appName,
                         'APP_ENV' => $this->isProd ? 'production' : 'local',
                         'APP_DEBUG' => $this->isProd ? 'false' : 'true',
                         'APP_URL' => $appUrl,
                         'DB_CONNECTION' => $driver,
                         'DB_HOST' => $dbHost,
                         'DB_PORT' => $dbPort,
                         'DB_DATABASE' => $databaseName,
                         'DB_USERNAME' => $dbUsername,
                         'DB_PASSWORD' => $dbPassword,
                         'DB_PREFIX' => $prefix,
                     ] as $key => $newValue) {
                if (Str::contains($value, $key) && ! Str::contains($value, 'VITE_')) {
                    $value = $this->resetEnvValue($value, $newValue);
                }
            }
        }

        File::put(app()->environmentFile(), implode("\n", $env));

        // 如果不是生产环境，创建前端项目 .env
        if (! $this->isProd) {
            $this->createWebEnv();
        }

        $this->appUrl = $appUrl;
    }

    /**
     * @return string
     */
    protected function webEnv(): string
    {
        return app()->basePath('web').DIRECTORY_SEPARATOR.'.env';
    }

    /**
     * @return void
     */
    protected function createWebEnv(): void
    {
        if (! is_dir(base_path('web'))) {
            return;
        }

        File::put($this->webEnv(), implode("\n", [
            'VITE_BASE_URL='.$this->appUrl.'/api/',
            'VITE_APP_NAME='.$this->appName,
        ]));

        // 这里面判断是否成功
        if (! File::exists($this->webEnv())) {
            $this->info('请手动在根目录前端 web 目录下创建 .env 配置文件, 添加以下内容');
            $this->info("VITE_BASE_URL={$this->appUrl}/api/");
            $this->info("VITE_APP_NAME={$this->appName}");
        }
    }

    protected function rollback(): void
    {
        try {
            if (File::exists(app()->environmentFile())) {
                File::delete(app()->environmentFile());
            }

            File::delete($this->webEnv());

            $databaseConfig = config('database.connections.'.$this->defaultConnection);

            $databaseName = $databaseConfig['database'];

            app(ConnectionFactory::class)->make($databaseConfig)->select("drop database $databaseName");
        } catch (\Throwable $e) {
        }
    }

    protected function parseDevServer(): void
    {
        $url = parse_url($this->appUrl);

        $host = $url['host'] ?? '127.0.0.1';
        $port = $url['port'] ?? 80;

        if ($host === '127.0.0.1' && $port == 8000) {
            return;
        }

        $composerJson = $this->getComposerJson();

        $devCommand = $composerJson['scripts']['dev'] ?? [];

        $devCommand[0] = 'Composer\\Config::disableProcessTimeout';

        $devStr = <<<STR
npx concurrently -c \"#93c5fd,#c4b5fd\" \"cd web && yarn dev\" \"php artisan serve --host={host} --port={port}\" --names='vite,server'
STR;

        $devCommand[1] = Str::of($devStr)->replace(['{host}', '{port}'], [$host, $port])->toString();

        $composerJson['scripts']['dev'] = $devCommand;

        File::put($this->getComposerFile(), json_encode($composerJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /**
     * 是否发布 sanctum 配置
     */
    protected function isShouldPublishSanctum(): bool
    {
        return ! ($this->isPersonalTokenTableExist() && $this->isHasSanctumConfig());
    }

    protected function isPersonalTokenTableExist(): bool
    {
        foreach (File::allFiles(database_path('migrations')) as $file) {
            if (Str::of($file->getFilename())->contains('personal_access_tokens')) {
                return true;
            }
        }

        return false;
    }

    protected function isHasSanctumConfig(): bool
    {
        return file_exists(config_path('sanctum.php'));
    }

    protected function cloneWeb(): bool
    {
        $webPath = app()->basePath('web');

        if (is_dir($webPath)) {
            return true;
        }

        $this->info('开始下载前端项目');

        shell_exec("git clone -b v5 {$this->webRepo} web");

        if (is_dir($webPath)) {
            return true;
        } else {
            $this->error('下载前端项目失败, 请到该仓库下载 https://gitee.com/catchadmin/catch-admin-vue v5 分支');
            return false;
        }
    }
}
