
<?php

return [

    /*
    |--------------------------------------------------------------------------
    | JWT Authentication Secret
    |--------------------------------------------------------------------------
    |
    | Used to sign JWT tokens.
    | Generate using: php artisan jwt:secret
    |
    */

    'secret' => env('JWT_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | JWT Authentication Keys
    |--------------------------------------------------------------------------
    */

    'keys' => [

        'public' => env('JWT_PUBLIC_KEY'),

        'private' => env('JWT_PRIVATE_KEY'),

        'passphrase' => env('JWT_PASSPHRASE'),

    ],

    /*
    |--------------------------------------------------------------------------
    | JWT Time To Live (60 Days)
    |--------------------------------------------------------------------------
    |
    | JWT TTL is measured in minutes.
    |
    | 60 days x 24 hours x 60 minutes = 86400 minutes.
    |
    | The token will expire after 60 days.
    |
    */

    'ttl' => (int) env('JWT_TTL', 86400),

    /*
    |--------------------------------------------------------------------------
    | JWT Refresh Configuration
    |--------------------------------------------------------------------------
    |
    | Refresh window: 60 days.
    | Refreshing does not reset the original issued-at time
    | unless refresh_iat is enabled.
    |
    */

    'refresh_iat' => env('JWT_REFRESH_IAT', false),

    'refresh_ttl' => (int) env('JWT_REFRESH_TTL', 86400),

    /*
    |--------------------------------------------------------------------------
    | JWT Hashing Algorithm
    |--------------------------------------------------------------------------
    */

    'algo' => env('JWT_ALGO', 'HS256'),

    /*
    |--------------------------------------------------------------------------
    | Required JWT Claims
    |--------------------------------------------------------------------------
    */

    'required_claims' => [
        'iss',
        'iat',
        'exp',
        'nbf',
        'sub',
        'jti',
    ],

    /*
    |--------------------------------------------------------------------------
    | Persistent Claims
    |--------------------------------------------------------------------------
    */

    'persistent_claims' => [],

    /*
    |--------------------------------------------------------------------------
    | Lock Subject
    |--------------------------------------------------------------------------
    */

    'lock_subject' => true,

    /*
    |--------------------------------------------------------------------------
    | JWT Leeway (Seconds)
    |--------------------------------------------------------------------------
    */

    'leeway' => (int) env('JWT_LEEWAY', 0),

    /*
    |--------------------------------------------------------------------------
    | JWT Blacklist
    |--------------------------------------------------------------------------
    |
    | Enable token invalidation on logout.
    |
    */

    'blacklist_enabled' => env('JWT_BLACKLIST_ENABLED', true),

    'blacklist_grace_period' => (int) env(
        'JWT_BLACKLIST_GRACE_PERIOD',
        0
    ),

    'show_black_list_exception' => env(
        'JWT_SHOW_BLACKLIST_EXCEPTION',
        true
    ),

    /*
    |--------------------------------------------------------------------------
    | JWT Cookie Configuration
    |--------------------------------------------------------------------------
    */

    'decrypt_cookies' => false,

    'cookie_key_name' => 'token',

    /*
    |--------------------------------------------------------------------------
    | JWT Providers
    |--------------------------------------------------------------------------
    */

    'providers' => [

        'jwt' => PHPOpenSourceSaver\JWTAuth\Providers\JWT\Lcobucci::class,

        'auth' => PHPOpenSourceSaver\JWTAuth\Providers\Auth\Illuminate::class,

        'storage' => PHPOpenSourceSaver\JWTAuth\Providers\Storage\Illuminate::class,

    ],

];
