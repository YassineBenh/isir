<?php

declare(strict_types=1);

/*
 * This file is part of Laravel GitHub.
 *
 * (c) Graham Campbell <hello@gjcampbell.co.uk>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Default Connection Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the connections below you wish to use as
    | your default connection for all work. Of course, you may use many
    | connections at once using the manager class.
    |
    */

    'default' => 'main',

    /*
    |--------------------------------------------------------------------------
    | GitHub Connections
    |--------------------------------------------------------------------------
    |
    | Here are each of the connections setup for your application. Example
    | configuration has been included, but you may add as many connections as
    | you would like. Note that the 5 supported authentication methods are:
    | "application", "jwt", "none", "private", and "token".
    |
    */

    'connections' => [

        'main' => [
            'method' => env('GITHUB_TOKEN') ? 'token' : 'none',
            'token' => env('GITHUB_TOKEN'),
            'cache' => 'main',
        ],

        'none' => [
            'method' => 'none',
            'cache' => 'main',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | HTTP Cache
    |--------------------------------------------------------------------------
    |
    | Here are each of the cache configurations setup for your application.
    | Only the "illuminate" driver is provided out of the box. Example
    | configuration has been included.
    |
    */

    'cache' => [

        'main' => [
            'driver' => 'illuminate',
            'connector' => null,
            // 'min' => 43200,  // 12 hours
            // 'max' => 172800, // 48 hours
        ],

    ],

];
