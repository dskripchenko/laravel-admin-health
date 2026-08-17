<?php

declare(strict_types=1);

use Dskripchenko\LaravelAdminHealth\Checks\CacheCheck;
use Dskripchenko\LaravelAdminHealth\Checks\DatabaseConnectionCheck;
use Dskripchenko\LaravelAdminHealth\Checks\DiskSpaceCheck;
use Dskripchenko\LaravelAdminHealth\Checks\QueueCheck;

return [
    /*
    |--------------------------------------------------------------------------
    | The registered checks
    |--------------------------------------------------------------------------
    | Every key is a class-string<HealthCheck>. The value is the config array
    | passed into the check's constructor as the `$config` parameter. To add a
    | check of your own: implement the HealthCheck contract, then add the class
    | to the array together with its config.
    */

    'checks' => [
        DatabaseConnectionCheck::class => [
            'connections' => [env('DB_CONNECTION', 'mysql')],
            'frequency' => '1m',
            'timeout' => 5,
        ],

        CacheCheck::class => [
            'stores' => [env('CACHE_STORE', 'redis')],
            'frequency' => '5m',
            'timeout' => 5,
        ],

        QueueCheck::class => [
            'queues' => ['default'],
            'depth_warning' => 100,
            'depth_failing' => 1000,
            'failed_jobs_warn' => 10,
            'frequency' => '1m',
            'timeout' => 5,
        ],

        DiskSpaceCheck::class => [
            'paths' => [storage_path()],
            'warn_below_pct' => 15,
            'fail_below_pct' => 5,
            'frequency' => '5m',
            'timeout' => 3,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | The TTL of the results history (in days)
    |--------------------------------------------------------------------------
    | The cleanup command deletes the rows older than this threshold. It is
    | `admin:health:cleanup` (run it once a day from the scheduler).
    */

    'history_days' => 7,

    /*
    |--------------------------------------------------------------------------
    | The topbar indicator
    |--------------------------------------------------------------------------
    | When true the panel shows the overall status in its top bar — a dot with
    | a word, on every page, and nothing at all while every check passes.
    | Requires dskripchenko/laravel-admin ^1.30; on anything older the flag has
    | no effect, since there was nowhere to put the indicator.
    */

    'topbar_indicator' => true,
];
