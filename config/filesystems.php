<?php
// config/filesystems.php
return [

    'default' => env('FILESYSTEM_DISK', 'docnumerises'),

    'disks' => [

         /*
        |----------------------------------------------------------------------
        | Disque des documents numérisés
        |----------------------------------------------------------------------
        | DOC_PATH est un chemin physique ABSOLU (ex: /home/user/public_html/
        | docnumerises/PROD). On l'utilise directement via 'root'.
        |
        | L'URL publique est reconstruite automatiquement dans DocumentService
        | à partir de APP_URL + le nom du dossier (PROD ou TEST).
        |
        */
        'docnumerises' => [
            'driver'      => 'webdav',
            'base_uri'    => env('WEBDAV_URL'),
            'user_name'   => env('WEBDAV_USERNAME'),
            'password'    => env('WEBDAV_PASSWORD'),
            'prefix'      => env('WEBDAV_PATH', 'DOC_WEB/PROD'),
            'auth_type'   => 'basic',
            'throw'       => true,
        ],

        'local' => [
            'driver' => 'local',
            'root'   => storage_path('app'),
            'throw'  => false,
        ],

        'public' => [
            'driver'     => 'local',
            'root'       => storage_path('app/public'),
            'url'        => env('APP_URL') . '/storage',
            'visibility' => 'public',
            'throw'      => false,
        ],

        's3' => [
            'driver'                  => 's3',
            'key'                     => env('AWS_ACCESS_KEY_ID'),
            'secret'                  => env('AWS_SECRET_ACCESS_KEY'),
            'region'                  => env('AWS_DEFAULT_REGION'),
            'bucket'                  => env('AWS_BUCKET'),
            'url'                     => env('AWS_URL'),
            'endpoint'                => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw'                   => false,
        ],

    ],

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
