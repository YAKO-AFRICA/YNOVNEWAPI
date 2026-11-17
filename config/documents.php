<?php
// config/documents.php
return [

    /*
    |--------------------------------------------------------------------------
    | Disque de stockage des documents
    |--------------------------------------------------------------------------
    */
    'disk' => env('FILESYSTEM_DISK', 'docnumerises'),

    /*
    |--------------------------------------------------------------------------
    | Chemin physique ABSOLU vers le dossier de stockage
    |--------------------------------------------------------------------------
    | Exemple : /home/xxxxxxxx/public_html/docnumerises/PROD
    |
    */
    'path' => env('DOC_PATH', base_path('../public_html/docnumerises/PROD')),

    /*
    |--------------------------------------------------------------------------
    | Préfixe URL publique
    |--------------------------------------------------------------------------
    | Ce préfixe est ajouté à APP_URL pour construire les URLs des documents.
    | Il est déduit automatiquement du nom du dossier (PROD ou TEST).
    |
    | Exemple : /docnumerises/PROD
    |
    */
    'url_prefix' => '/docnumerises/' . (
        str_contains((string) env('WEBDAV_PATH', 'PROD'), '/PROD') ? 'PROD' : 'TEST'
    ),

    /*
    |--------------------------------------------------------------------------
    | Extensions autorisées à l'upload
    |--------------------------------------------------------------------------
    */
    'allowed_extensions' => [
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'ppt', 'pptx',
        'jpg', 'jpeg', 'png', 'gif', 'webp',
        'zip', 'rar', 'txt',
    ],

    /*
    |--------------------------------------------------------------------------
    | Taille maximale par fichier (en Ko)
    |--------------------------------------------------------------------------
    */
    'max_size' => 51200, // 50 Mo

    /*
    |--------------------------------------------------------------------------
    | Nombre maximum de fichiers par upload multiple
    |--------------------------------------------------------------------------
    */
    'max_files' => 10,

    /*
    |--------------------------------------------------------------------------
    | Dossier catégorie par défaut
    |--------------------------------------------------------------------------
    | Utilisé quand aucune source/catégorie n'est fournie à l'upload.
    |
    */
    'default_folder' => 'AUTRES',

    /*
    |--------------------------------------------------------------------------
    | Compression des images
    |--------------------------------------------------------------------------
    */
    'image' => [
        'enabled'     => true,
        'extensions'  => ['jpg', 'jpeg', 'webp'],
        'max_width'   => 1920,
        'max_height'  => 1920,
        'quality'     => 85,
        'seuil_poids' => 500, // Ko
    ],

];