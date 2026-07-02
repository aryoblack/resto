<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
            'report' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        /*
        |----------------------------------------------------------------------
        | Menu Images Disk (Local)
        |----------------------------------------------------------------------
        | Used for storing optimized menu images in development/local
        | environments. Files are stored under storage/app/public/menu-images
        | and served via the public symlink at public/storage/menu-images.
        |
        | Switch to the 's3-menu-images' disk in production by setting:
        |   FILESYSTEM_DISK=s3
        | or by referencing this disk explicitly in the application code.
        */
        'menu-images' => [
            'driver' => 'local',
            'root' => storage_path('app/public/menu-images'),
            'url' => '/storage/menu-images',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
            'report' => false,
        ],

        /*
        |----------------------------------------------------------------------
        | S3 Menu Images Disk (Production)
        |----------------------------------------------------------------------
        | Used for storing menu images on AWS S3 in production. Files are
        | stored under the 'menu-images/' prefix inside the configured bucket.
        | Set MENU_IMAGE_DISK=s3-menu-images in production .env to activate.
        */
        's3-menu-images' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'root' => 'menu-images',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    | Running `php artisan storage:link` will create:
    |   public/storage  →  storage/app/public
    |
    | This makes all files in storage/app/public (including menu-images/)
    | accessible at: {APP_URL}/storage/{filename}
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
