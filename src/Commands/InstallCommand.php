<?php

// +----------------------------------------------------------------------
// | CatchAdmin [Just Like ～ ]
// +----------------------------------------------------------------------
// | Copyright (c) 2017 ~ now https://catchadmin.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( https://github.com/JaguarJack/catchadmin/blob/master/LICENSE.md )
// +----------------------------------------------------------------------
// | Author: JaguarJack [ njphper@gmail.com ]
// +----------------------------------------------------------------------

namespace Catch\Commands;

use Catch\CatchAdmin;
use Catch\Exceptions\FailedException;
use Catch\Facade\Module;
use Doctrine\DBAL\Exception;
use Illuminate\Console\Application;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Connectors\ConnectionFactory;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;
use Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use Catch\Support\Composer;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\text;
use Illuminate\Support\Facades\Schema;

class InstallCommand extends CatchCommand
{
    protected $signature = 'catch:install {--reinstall}';

    protected $description = 'install catch admin';

    protected string $webRepo = 'https://gitee.com/catchadmin/catch-admin-vue.git';

    protected string $appUrl = '';

    /**
     * @var array|string[]
     */
    private array $defaultExtensions = ['BCMath', 'Ctype', 'DOM', 'Fileinfo', 'JSON', 'Mbstring', 'OpenSSL', 'PCRE', 'PDO', 'Tokenizer', 'XML', 'pdo_mysql'];

    /**
     * handle
     *
     * @return void
     * @throws Exception
     */
    public function handle(): void
    {
        $this->reinstall();

        try {
            // 如果没有 .env 文件
           if (! File::exists(app()->environmentFile())) {
               $this->detectionEnvironment();
               $this->askForCreatingDatabase();
           }

            $this->publishConfig();
            $this->installed();
        } catch (\Throwable $e) {
            File::delete(app()->environmentFilePath());

            $this->error($e->getMessage());
        }
    }

    /**
     * 环境检测
     *
     * @return void
     */
    protected function detectionEnvironment(): void
    {
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

        Collection::make($this->defaultExtensions)
            ->each(function ($extension) use ($loadedExtensions, &$continue) {
                $extension = strtolower($extension);

                if (! $loadedExtensions->contains($extension)) {
                    $this->error("$extension extension 未安装");
                }
            });
    }

    /**
     * check php version
     */
    private function checkPHPVersion(): void
    {
        if (version_compare(PHP_VERSION, '8.2.0', '<')) {
            $this->error('php version should >= 8.2');
        }
    }


    /**
     * create database
     *
     * @param string $databaseName
     * @return void
     * @throws BindingResolutionException
     */
    private function createDatabase(string $databaseName): void
    {
        $databaseConfig = config('database.connections.'.DB::getDefaultConnection());

        $databaseConfig['database'] = null;

        app(ConnectionFactory::class)->make($databaseConfig)->select(sprintf("create database if not exists $databaseName default charset %s collate %s", 'utf8mb4', 'utf8mb4_general_ci'));
    }

    /**
     * copy .env
     *
     * @return void
     */
    protected function copyEnvFile(): void
    {
        if (! File::exists(app()->environmentFilePath())) {
            File::copy(app()->environmentFilePath().'.example', app()->environmentFilePath());
        }

        if (! File::exists(app()->environmentFilePath())) {
            $this->error('【.env】创建失败, 请重新尝试或者手动创建！');
        }
    }

    /**
     * get env file content
     *
     * @return string
     */
    protected function getEnvFileContent(): string
    {
        return File::get(app()->basePath() . DIRECTORY_SEPARATOR . '.env.example');
    }

    /**
     * publish config
     *
     * @return void
     */
    protected function publishConfig(): void
    {
        try {
            // mac os
            if (Str::of(PHP_OS)->lower()->contains('dar')) {
                exec(Application::formatCommandString('key:generate'));
                exec(Application::formatCommandString('vendor:publish --tag=catch-config --force'));
                exec(Application::formatCommandString('vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"'));
                exec(Application::formatCommandString('catch:migrate user'));
                exec(Application::formatCommandString('catch:migrate develop'));
                exec(Application::formatCommandString('migrate'));
                exec(Application::formatCommandString('catch:db:seed user'));
                exec(Application::formatCommandString('catch:module:install permissions'));
            } else {
                Process::run(Application::formatCommandString('key:generate'))->throw();
                Process::run(Application::formatCommandString('vendor:publish --tag=catch-config --force'))->throw();
                Process::run(Application::formatCommandString('vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"'))->throw();
                Process::run(Application::formatCommandString('catch:migrate user'))->throw();
                Process::run(Application::formatCommandString('catch:migrate develop'))->throw();
                Process::run(Application::formatCommandString('migrate'))->throw();
                Process::run(Application::formatCommandString('catch:db:seed user'))->throw();
                $installer = CatchAdmin::getModuleInstaller('permissions');
                $installer->install();
            }
            $this->info('模块安装成功，模块信息存储在[storage/app/module.json]文件');
        }catch (\Exception|\Throwable $e) {
           throw new FailedException($e->getMessage());
        }
    }

    /**
     * create database
     */
    protected function askForCreatingDatabase(): void
    {
        $defaultUrl = 'http://127.0.0.1:8000';
        if (windows_os()) {
             $this->appUrl = $defaultUrl;
             $databaseName = $this->askFor('请输入数据库名称');
             $prefix = $this->askFor('请输入数据库表前缀', '');
             $dbHost = $this->askFor('请输入数据库主机地址', '127.0.0.1');
             $dbPort = $this->askFor('请输入数据的端口号', 3306);
             $dbUsername = $this->askFor('请输入数据的用户名', 'root');
             $dbPassword = $this->askFor('请输入数据库密码');

             if (! $dbPassword) {
                $dbPassword = $this->askFor('确认数据库密码为空吗?');
             }
        } else {
            $this->appUrl = $defaultUrl;
            $databaseName = text('请输入数据库名称', required: '请输入数据库名称', validate: fn($value)=> preg_match("/[a-zA-Z\_]{1,100}/", $value) ? null : '数据库名称只支持a-z和A-Z以及下划线_');
            $prefix = text('请输入数据库表前缀', 'eg. catch_');
            $dbHost = text('请输入数据库主机地址', 'eg. 127.0.0.1', '127.0.0.1', required: '请输入数据库主机地址');
            $dbPort = text('请输入数据的端口号', 'eg. 3306', '3306', required: '请输入数据的端口号');
            $dbUsername = text('请输入数据的用户名', 'eg. root', 'root', required: '请输入数据的用户名');
            $dbPassword = text('请输入数据库密码', required: '请输入数据库密码');

        }

        // set env
        $env = explode("\n", $this->getEnvFileContent());

        foreach ($env as &$value) {
            foreach ([
                'APP_URL' => $defaultUrl,
                'DB_HOST' => $dbHost,
                'DB_PORT' => $dbPort,
                'DB_DATABASE' => $databaseName,
                'DB_USERNAME' => $dbUsername,
                'DB_PASSWORD' => $dbPassword,
                'DB_PREFIX' => $prefix
            ] as $key => $newValue) {
                if (Str::contains($value, $key) && !Str::contains($value, 'VITE_')) {
                    $value = $this->resetEnvValue($value, $newValue);
                }
            }
        }

        $this->copyEnvFile();
        File::put(app()->environmentFile(), implode("\n", $env));

        app()->bootstrapWith([
            LoadEnvironmentVariables::class,
            LoadConfiguration::class
        ]);

        $this->info("正在创建数据库[$databaseName]...");

        $this->createDatabase($databaseName);

        $this->info("创建数据库[$databaseName] 成功");
    }

    /**
     * @param $originValue
     * @param $newValue
     * @return string
     */
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
        $composerFile = base_path().DIRECTORY_SEPARATOR.'composer.json';

        $composerJson = json_decode(File::get(base_path().DIRECTORY_SEPARATOR.'composer.json'), true);

        $composerJson['autoload']['psr-4'][CatchAdmin::getModuleRootNamespace()] = str_replace('\\', '/', CatchAdmin::moduleRoot());

        File::put($composerFile, json_encode($composerJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $this->info('composer dump autoload..., 请耐心等待');

        app(Composer::class)->dumpAutoloads();
    }

    /**
     * admin installed
     */
    public function installed(): void
    {
        $this->cloneWeb();

        $this->addPsr4Autoload();

        $this->info('🎉 CatchAdmin 已安装, 欢迎!');

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
 初始密码: catchadmin', CatchAdmin::VERSION));

        $this->support();
    }

    /**
     * support
     *
     * @return void
     */
    protected function support(): void
    {
        $answer = $this->askFor('支持我们! 感谢在 Github 上 star 该项目', 'yes', true);

        if (in_array(strtolower($answer), ['yes', 'y'])) {
            if (PHP_OS_FAMILY == 'Darwin') {
                exec('open https://github.com/JaguarJack/catch-admin');
            }
            if (PHP_OS_FAMILY == 'Windows') {
                exec('start https://github.com/JaguarJack/catch-admin');
            }
            if (PHP_OS_FAMILY == 'Linux') {
                exec('xdg-open https://github.com/JaguarJack/catch-admin');
            }
        }

        $this->info('支 持: https://github.com/jaguarjack/catchadmin');
        $this->info('文 档: https://catchadmin.com/docs/3.0/intro');
        $this->info('官 网: https://catchadmin.com');
        $this->info('🌤 使用 composer run dev 启动开发之旅');
    }


    /**
     * @return void
     * @throws Exception
     */
    protected function reinstall(): void
    {
        if ($this->option('reinstall')) {
            $database = config('database.connections.mysql.database');

            Schema::getConnection()
                ->getDoctrineSchemaManager()
                ->dropDatabase("`{$database}`");

            File::delete(app()->environmentFile());

            // 删除已安装的模块
            $modules = Module::all();
            foreach ($modules as $module) {
                Module::delete($module['name']);
            }
        }
    }

    protected function cloneWeb(): void
    {
        $packageJson = app()->basePath() .DIRECTORY_SEPARATOR . 'package.json';

        if (File::exists($packageJson)) {
            return;
        }
        $webPath = app()->basePath('web');

        if (! is_dir($webPath)) {
            $this->info('开始下载前端项目');

            shell_exec("git clone {$this->webRepo} web");

            if (is_dir($webPath)) {
                $this->info('下载前端项目成功');
                $this->info('设置镜像源');
                shell_exec('yarn config set registry https://registry.npmmirror.com');
                $this->info('安装前端依赖，如果安装失败，请检查是否已安装了前端 yarn 管理工具，或者因为网络等原因');
                shell_exec('cd ' . $webPath . ' && yarn install');
                $this->info('手动启动使用 yarn dev');
                $this->info('项目启动后不要忘记设置 web/.env 里面的环境变量 VITE_BASE_URL');
                $this->info('安装前端依赖成功，开始启动前端项目');
                file_put_contents($webPath . DIRECTORY_SEPARATOR . '.env', <<<STR
VITE_BASE_URL=$this->appUrl/api
VITE_APP_NAME=后台管理
STR
                );
                // shell_exec("cd {$webPath} && yarn dev");
            } else {
                $this->error('下载前端项目失败, 请到该仓库下载 https://gitee.com/catchadmin/catch-admin-vue');
            }
        }
    }
}
