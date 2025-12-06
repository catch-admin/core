<?php

declare(strict_types=1);

namespace Catch\Support\Macros;

use Illuminate\Support\Facades\Cache as LaravelCache;

class Cache
{
    public function boot(): void
    {
        $this->adminRemember();

        $this->adminForever();

        $this->adminDelete();

        $this->adminGet();
    }

    public function adminRemember(): void
    {
        LaravelCache::macro('adminRemember', function (string $key, \Closure|\DateTimeInterface|\DateInterval|int|null $ttl, \Closure $callback) {
            $key = config('catch.admin_cache_key').$key;

            return LaravelCache::remember($key, $ttl, $callback);
        });
    }

    public function adminGet(): void
    {
        LaravelCache::macro('adminGet', function (string $key, mixed $default = null) {
            $key = config('catch.admin_cache_key').$key;

            return LaravelCache::get($key, $default);
        });
    }

    public function adminForever(): void
    {
        LaravelCache::macro('adminForever', function (string $key, mixed $value) {
            return LaravelCache::forever(config('catch.admin_cache_key').$key, $value);
        });
    }

    public function adminDelete(): void
    {
        LaravelCache::macro('adminDelete', function (string $key) {
            return LaravelCache::forget(config('catch.admin_cache_key').$key);
        });
    }
}
