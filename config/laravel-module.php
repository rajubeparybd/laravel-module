<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Debug Hooks
    |--------------------------------------------------------------------------
    |
    | When enabled, this will render HTML badges in the UI for actions and
    | HTML comments for filters to help developers visualize hook locations.
    | This only applies when the request expects HTML.
    |
    */
    'debug_hooks' => env('LARAVEL_MODULE_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Master Layout
    |--------------------------------------------------------------------------
    |
    | The layout view that the module management view should extend.
    |
    */
    'layout' => 'layouts.app',

    /*
    |--------------------------------------------------------------------------
    | Route Prefix
    |--------------------------------------------------------------------------
    |
    | The prefix for the module management routes.
    |
    */
    'route_prefix' => 'modules',

    /*
    |--------------------------------------------------------------------------
    | Command Prefix
    |--------------------------------------------------------------------------
    |
    | The prefix used for artisan commands.
    |
    */
    'command_prefix' => 'lm',

    /*
    |--------------------------------------------------------------------------
    | Modules Table Name
    |--------------------------------------------------------------------------
    |
    | The table name used to store the modules in the database.
    |
    */
    'table_name' => 'laravel_modules',

    /*
    |--------------------------------------------------------------------------
    | Modules Path
    |--------------------------------------------------------------------------
    |
    | The path where the modules are stored.
    |
    */
    'path' => app_path('Modules'),

    /*
    |--------------------------------------------------------------------------
    | Navigation Hook
    |--------------------------------------------------------------------------
    |
    | The hooks and filters to inject the module navigation.
    |
    */
    'navigation' => [
        'enabled' => true,
        'places' => [
            'topnav' => [
                'filter' => 'admin.topnav',
                'priority' => 10,
            ],
            'sidebar' => [
                'filter' => 'admin.navigation',
                'priority' => 10,
            ],
        ],
    ],

];
