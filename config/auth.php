<?php

use App\Models\CmsUser;

return [

    'defaults' => [
        'guard' => 'cms',
        'passwords' => 'cms_users',
    ],

    'guards' => [
        'cms' => [
            'driver' => 'session',
            'provider' => 'cms_users',
        ],

        // MCP token guard (Phase 2)
        'cms_mcp' => [
            'driver' => 'token',
            'provider' => 'cms_users',
            'hash' => true,
        ],
    ],

    'providers' => [
        'cms_users' => [
            'driver' => 'eloquent',
            'model' => CmsUser::class,
        ],
    ],

    'passwords' => [
        'cms_users' => [
            'provider' => 'cms_users',
            'table' => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),

];
