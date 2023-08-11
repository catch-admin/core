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

    /**
     * @var array|string[]
     */
    private array $defaultExtensions = ['BCMath', 'Ctype', 'DOM', 'Fileinfo', 'JSON', 'Mbstring', 'OpenSSL', 'PCRE', 'PDO', 'Tokenizer', 'XML'];

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
               // $this->copyEnvFile();
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
        if (version_compare(PHP_VERSION, '8.1.0', '<')) {
            $this->error('php version should >= 8.1');
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

        File::put(app()->environmentFile(), implode("\n", explode("\n", $this->getEnvFileContent())));
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
        if (windows_os()) {
            $isInstallPermissionModule = $this->askFor('是否安装权限模块?', '是');
        } else {
            $isInstallPermissionModule = confirm('是否安装权限模块?', yes: '是', no: '否');
        }

        try {
            // mac os
            if (Str::of(PHP_OS)->lower()->contains('dar')) {
                exec(Application::formatCommandString('key:generate'));
                exec(Application::formatCommandString('vendor:publish --tag=catch-config'));
                exec(Application::formatCommandString('vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"'));
                exec(Application::formatCommandString('catch:migrate user'));
                exec(Application::formatCommandString('catch:migrate develop'));
                exec(Application::formatCommandString('migrate'));
                exec(Application::formatCommandString('catch:db:seed user'));
                if ($isInstallPermissionModule) {
                    exec(Application::formatCommandString('catch:migrate permissions'));
                    exec(Application::formatCommandString('catch:db:seed permissions'));
                }
            } else {
                Process::run(Application::formatCommandString('key:generate'))->throw();
                Process::run(Application::formatCommandString('vendor:publish --tag=catch-config'))->throw();
                Process::run(Application::formatCommandString('vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"'))->throw();
                Process::run(Application::formatCommandString('catch:migrate user'))->throw();
                Process::run(Application::formatCommandString('catch:migrate develop'))->throw();
                Process::run(Application::formatCommandString('migrate'))->throw();
                Process::run(Application::formatCommandString('catch:db:seed user'))->throw();
                if ($isInstallPermissionModule === '是') {
                    Process::run(Application::formatCommandString('catch:migrate permissions'))->throw();
                    Process::run(Application::formatCommandString('catch:db:seed permissions'))->throw();
                }
            }
        }catch (\Exception|\Throwable $e) {
            $this->error($e->getMessage());
            exit;
        }
    }

    /**
     * create database
     */
    protected function askForCreatingDatabase(): void
    {
        if (windows_os()) {
             $appUrl = $this->askFor('请配置应用的 URL');

            if ($appUrl && ! Str::contains($appUrl, 'http://') && ! Str::contains($appUrl, 'https://')) {
                $appUrl = 'http://'.$appUrl;
            }

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
            $appUrl = text(label:'请配置应用的 URL',
                placeholder: 'eg. https://127.0.0.1:8080',
                required: '应用的 URL 必须填写',
                validate: fn($value) => filter_var($value, FILTER_VALIDATE_URL) !== false ? null : '应用URL不符合规则');
            $databaseName = text('请输入数据库名称', required: '请输入数据库名称', validate: fn($value)=> preg_match("/[a-zA-Z\_]{1,100}/", $value) ? null : '数据库名称只支持a-z和A-Z以及下划线_');
            $prefix = text('请输入数据库表前缀', 'eg. catch_');
            $dbHost = text('请输入数据库主机地址', 'eg. 127.0.0.1', '127.0.0.1', required: '请输入数据库主机地址');
            $dbPort = text('请输入数据库主机地址', 'eg. 3306', '3306', required: '请输入数据库主机地址');
            $dbUsername = text('请输入数据的用户名', 'eg. root', 'root', required: '请输入数据的用户名');
            $dbPassword = text('请输入数据库密码', required: '请输入数据库密码');

        }

        // set env
        $env = explode("\n", $this->getEnvFileContent());

        foreach ($env as &$value) {
            foreach ([
                'APP_URL' => $appUrl,
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

        $composerJson['autoload']['psr-4'][CatchAdmin::getModuleRootNamespace()] = str_replace('\\', '/', config('catch.module.root'));

        File::put($composerFile, json_encode($composerJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $this->info('composer dump autoload..., 请耐心等待');

        app(Composer::class)->dumpAutoloads();
    }

    /**
     * admin installed
     */
    public function installed(): void
    {
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
}
