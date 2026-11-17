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
            'driver'     => 'local',
            'root'       => base_path(env('DOC_PATH', '../public_html/docnumerises/PROD')), // env('DOC_PATH', base_path('../public_html/docnumerises/PROD')),
            'visibility' => 'public',
            'throw'      => true, // ⚠️ true pour voir les erreurs d'écriture
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
