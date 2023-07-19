<?php

// +----------------------------------------------------------------------
// | CatchAdmin
// +----------------------------------------------------------------------
// | Copyright (c) 2017~2021 https://catchadmin.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( https://github.com/JaguarJack/catchadmin/blob/master/LICENSE.md )
// +----------------------------------------------------------------------
// | Author: JaguarJack [ njphper@gmail.com ]
// +----------------------------------------------------------------------

return [
    /*
    |--------------------------------------------------------------------------
    | catch-admin default middleware
    |--------------------------------------------------------------------------
    |
    | where you can set default middlewares
    |
    */
    'middleware_group' => [

    ],

    /*
    |--------------------------------------------------------------------------
    | catch-admin catch_auth_middleware_alias
    |--------------------------------------------------------------------------
    |
    | where you can set default middlewares
    |
    */
    'catch_auth_middleware_alias' => [

    ],

    /*
    |--------------------------------------------------------------------------
    | catch-admin super admin id
    |--------------------------------------------------------------------------
    |
    | where you can set super admin id
    |
    */
    'super_admin' => 1,

    'request_allowed' => true,

    /*
    |--------------------------------------------------------------------------
    | catch-admin module setting
    |--------------------------------------------------------------------------
    |
    | the root where module generate
    | the namespace is module root namespace
    | the default dirs is module generate default dirs
    */
    'module' => [
        'root' => 'modules',

        'namespace' => 'Modules',

        'default' => ['develop', 'user', 'common'],

        'default_dirs' => [
            'Http'.DIRECTORY_SEPARATOR,

            'Http'.DIRECTORY_SEPARATOR.'Requests'.DIRECTORY_SEPARATOR,

            'Http'.DIRECTORY_SEPARATOR.'Controllers'.DIRECTORY_SEPARATOR,

            'Models'.DIRECTORY_SEPARATOR,

            'views'.DIRECTORY_SEPARATOR,
        ],

        // storage module information
        // which driver should be used?
        'driver' => [
            // currently, catchadmin support file and database
            // the default is driver
            'default' => 'file',

            // use database driver
            'table_name' => 'admin_modules'
        ],

        /**
         * module routes collection
         *
         */
        'routes' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | catch-admin response
    |--------------------------------------------------------------------------
    */
    'response' => [
        // it's a controller middleware, it's set in CatchController
        // if you not need json response, don't extend CatchController
        'always_json' => \Catch\Middleware\JsonResponseMiddleware::class,

        // response listener
        // it  listens [RequestHandled] event, if you don't need this
        // you can change this config
        'request_handled_listener' => \Catch\Listeners\RequestHandledListener::class
    ],

    /*
   |--------------------------------------------------------------------------
   | database sql log
   |--------------------------------------------------------------------------
   */
    'listen_db_log' => true,

    /*
   |--------------------------------------------------------------------------
   | admin auth model
   |--------------------------------------------------------------------------
   */
    'auth_model' => \Modules\User\Models\User::class,

    /*
   |--------------------------------------------------------------------------
   | route config
   |--------------------------------------------------------------------------
   */
    'route' => [
        'prefix' => 'api',

        'middlewares' => [
            \Catch\Middleware\AuthMiddleware::class,
            \Catch\Middleware\JsonResponseMiddleware::class
        ],

        // 'cache_path' => base_path('bootstrap/cache') . DIRECTORY_SEPARATOR . 'admin_route_cache.php'
    ],

    'excel' => [
        'export' => [
            'csv_limit' => 20000,

            'path' => 'excel/export/'
        ]
    ]
];
