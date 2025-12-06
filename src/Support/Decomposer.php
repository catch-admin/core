<?php

namespace Catch\Support;

use Catch\CatchAdmin;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class Decomposer
{
    /**
     * 为额外统计信息初始化空数组，可由应用程序或其他包开发者添加
     */
    public static array $laravelExtras = [];

    public static array $serverExtras = [];

    public static array $extraStats = [];

    /**
     * 包名常量
     */
    private const PACKAGE_NAME = 'luochuan/decomposer';

    /**
     * 获取 Decomposer 系统报告作为 PHP 数组
     */
    public static function getReportArray(): array
    {
        $composerArray = self::getComposerArray();

        $reportArray['server'] = self::getServerEnv();
        $reportArray['laravel'] = self::getLaravelEnv();
        $reportArray['packages'] = self::getPackagesArray($composerArray['require']);

        if (! empty($stats = self::getExtraStats())) {
            $reportArray['stats'] = $stats;
        }

        return $reportArray;
    }

    /**
     * 由应用程序或任何其他包开发者添加额外统计信息
     */
    public static function addExtraStats(array $extraStatsArray): void
    {
        self::$extraStats = array_merge(self::$extraStats, $extraStatsArray);
    }

    /**
     * 由应用程序或任何其他包开发者添加 Laravel 特定统计信息
     */
    public static function addLaravelStats(array $laravelStatsArray): void
    {
        self::$laravelExtras = array_merge(self::$laravelExtras, $laravelStatsArray);
    }

    /**
     * 由应用程序或任何其他包开发者添加服务器特定统计信息
     */
    public static function addServerStats(array $serverStatsArray): void
    {
        self::$serverExtras = array_merge(self::$serverExtras, $serverStatsArray);
    }

    /**
     * 获取由应用程序或任何其他包开发者添加的额外统计信息
     */
    public static function getExtraStats(): array
    {
        return self::$extraStats;
    }

    /**
     * 获取由应用程序或任何其他包开发者添加的额外服务器信息
     */
    public static function getServerExtras(): array
    {
        return self::$serverExtras;
    }

    /**
     * 获取由应用程序或任何其他包开发者添加的额外 Laravel 信息
     */
    public static function getLaravelExtras(): array
    {
        return self::$laravelExtras;
    }

    /**
     * 获取 Decomposer 系统报告作为 JSON
     */
    public static function getReportJson(): array
    {
        return self::getReportArray();
    }

    /**
     * 获取 Composer 文件内容作为数组
     */
    public static function getComposerArray(): array
    {
        $json = file_get_contents(base_path('composer.json'));

        return json_decode($json, true);
    }

    /**
     * 获取已安装的包及其依赖项
     */
    public static function getPackagesAndDependencies($packagesArray): array
    {
        $packages = [];

        foreach ($packagesArray as $key => $value) {
            $packageFile = base_path("/vendor/{$key}/composer.json");

            if ($key !== 'php' && file_exists($packageFile)) {
                $json2 = file_get_contents($packageFile);
                $dependenciesArray = json_decode($json2, true);
                $dependencies = array_key_exists('require', $dependenciesArray) ? $dependenciesArray['require'] : 'No dependencies';
                $devDependencies = array_key_exists('require-dev', $dependenciesArray) ? $dependenciesArray['require-dev'] : 'No dependencies';

                $packages[] = [
                    'name' => $key,
                    'version' => $value,
                    'dependencies' => $dependencies,
                    'dev-dependencies' => $devDependencies,
                ];
            }
        }

        return $packages;
    }

    /**
     * 获取 Laravel 环境详情
     */
    public static function getLaravelEnv(): array
    {
        return array_merge([
            'version' => App::version(),
            'catchadmin' => CatchAdmin::version(),
            'timezone' => config('app.timezone'),
            'debug_mode' => config('app.debug'),
            'cache_driver' => config('cache.default'),
            'session_driver' => config('session.driver'),
            'storage_dir_writable' => is_writable(base_path('storage')),
            'cache_dir_writable' => is_writable(base_path('bootstrap/cache')),
            'app_size' => self::sizeFormat(self::folderSize(base_path())),
            'vendor_size' => self::sizeFormat(self::folderSize(base_path('vendor'))),
            'web_size' => self::sizeFormat(self::folderSize(! config('app.debug') ? public_path('admin') : base_path('web'))),
        ], self::getLaravelExtras());
    }

    /**
     * 获取 PHP/服务器环境详情
     */
    public static function getServerEnv(): array
    {
        $version = json_decode(json_encode(DB::select('select version()')), true);

        return array_merge([
            'host' => request()->host(),
            'version' => phpversion(),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? phpversion(),
            'server_os' => PHP_OS_FAMILY,
            'database' => ! is_pgsql() ? $version[0]['version()'] : $version[0]['version'],
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time').'/S',
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'database_connection_name' => config('database.default'),
            'ssl_installed' => self::checkSslIsInstalled(),
            'openssl' => extension_loaded('openssl'),
            'pdo' => extension_loaded('pdo'),
            'mbstring' => extension_loaded('mbstring'),
            'tokenizer' => extension_loaded('tokenizer'),
            'xml' => extension_loaded('xml'),
        ], self::getServerExtras());
    }

    /**
     * 获取已安装的包及其版本号作为关联数组
     */
    private static function getPackagesArray(array $composerRequireArray): array
    {
        $packagesArray = self::getPackagesAndDependencies($composerRequireArray);
        $packages = [];

        foreach ($packagesArray as $packageArray) {
            $packages[$packageArray['name']] = $packageArray['version'];
        }

        return $packages;
    }

    /**
     * 检查是否安装了 SSL
     */
    private static function checkSslIsInstalled(): bool
    {
        return ! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off';
    }

    /**
     * 获取 Laravel 应用程序的大小
     */
    private static function folderSize($dir): int
    {
        $size = 0;
        $excludedFolders = ['node_modules', 'storage', 'tests', '.git'];

        try {
            $directoryIterator = new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS);
            $iterator = new RecursiveIteratorIterator($directoryIterator);

            foreach ($iterator as $file) {
                // Skip symlinks and check if file
                if (! $file->isFile() || $file->isLink()) {
                    continue;
                }

                foreach ($excludedFolders as $excluded) {
                    if (str_contains($file->getPathname(), DIRECTORY_SEPARATOR.$excluded.DIRECTORY_SEPARATOR)) {
                        continue 2;
                    }
                }

                $size += $file->getSize();
            }
        } catch (\Throwable $e) {
        }

        return $size;
    }

    /**
     * 以正确的单位格式化应用程序的大小
     */
    private static function sizeFormat($bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        for ($i = 0; $bytes >= 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2).' '.$units[$i];
    }

    /**
     * 从文件名返回 svg 代码，并向 svg 文件添加类
     */
    public static function svg($name, $class = ''): string
    {
        $path = __DIR__.'/svg/'.$name.'.svg';
        if (! file_exists($path)) {
            return '';
        }
        $svg = file_get_contents($path);

        return str_replace('<svg', "<svg class=\"{$class}\"", $svg);
    }
}
