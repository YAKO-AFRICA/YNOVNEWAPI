<?php
// config/documents.php
// return [

//     /*
//     |--------------------------------------------------------------------------
//     | Disque de stockage des documents
//     |--------------------------------------------------------------------------
//     |
//     | Ce disque est défini dans config/filesystems.php.
//     | Il pointe vers ../public_html/docnumerises/{TEST|PROD}.
//     |
//     */
//     'disk' => env('FILESYSTEM_DISK', 'docnumerises'),

//     /*
//     |--------------------------------------------------------------------------
//     | Chemin physique (relatif à la racine du projet Laravel)
//     |--------------------------------------------------------------------------
//     |
//     | Utilisé uniquement si on a besoin du chemin absolu (debug, etc.).
//     |
//     */
//     'path' => env('DOC_PATH', '../public_html/docnumerises/PROD'),

//     /*
//     |--------------------------------------------------------------------------
//     | URL publique (préfixe utilisé pour construire les URLs stockées en BDD)
//     |--------------------------------------------------------------------------
//     |
//     | Exemple : /docnumerises/TEST
//     |
//     */
//     'url' => env('DOC_URL', '/docnumerises/PROD'),

//     /*
//     |--------------------------------------------------------------------------
//     | Extensions autorisées à l'upload
//     |--------------------------------------------------------------------------
//     |
//     | Toute extension hors de cette liste sera rejetée (422).
//     |
//     */
//     'allowed_extensions' => [
//         'pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'ppt', 'pptx',
//         'jpg', 'jpeg', 'png', 'gif', 'webp',
//         'zip', 'rar', 'txt',
//     ],

//     /*
//     |--------------------------------------------------------------------------
//     | Taille maximale par fichier (en Ko)
//     |--------------------------------------------------------------------------
//     */
//     'max_size' => 51200, // 50 Mo

//     /*
//     |--------------------------------------------------------------------------
//     | Nombre maximum de fichiers par upload multiple
//     |--------------------------------------------------------------------------
//     */
//     'max_files' => 10,

//     /*
//     |--------------------------------------------------------------------------
//     | Compression des images
//     |--------------------------------------------------------------------------
//     |
//     | La compression s'applique UNIQUEMENT si :
//     |   1. L'extension est dans "extensions"
//     |   2. ET (largeur > max_width OU hauteur > max_height OU poids > seuil_poids)
//     |
//     | Les PNG et GIF sont volontairement exclus :
//     |   - PNG : préserve la transparence
//     |   - GIF : préserve l'animation
//     |
//     */
//     'image' => [
//         'enabled'     => true,

//         // Extensions compressibles
//         'extensions'  => ['jpg', 'jpeg', 'webp'],

//         // Dimensions maximales (ratio préservé via scaleDown)
//         'max_width'   => 1920,
//         'max_height'  => 1920,

//         // Qualité d'encodage (0-100)
//         'quality'     => 85,

//         // Seuil de poids (en Ko) : ne compresse pas en dessous
//         'seuil_poids' => 500,
//     ],

// ];

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
        str_contains((string) env('DOC_PATH', 'PROD'), '/PROD') ? 'PROD' : 'TEST'
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