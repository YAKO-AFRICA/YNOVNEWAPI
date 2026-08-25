<?php

// use App\Http\Controllers\Api\ApiController;
// use App\Http\Controllers\Api\JekoPaymentController;
// use App\Http\Controllers\Api\ReceiptController;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('storage/documents/{file}', function ($file) {
    // Nettoyer le nom du fichier
    $file = ltrim($file, '/');
    
    // Construire le chemin complet avec UPLOADS_PATH
    $uploadPath = rtrim(env('UPLOADS_PATH', '../public_html/upload/documents-test/'), '/');
    $fullPath = base_path($uploadPath . '/' . $file);
    
    // Alternative: si le fichier n'existe pas, essayer dans storage/app
    if (!file_exists($fullPath)) {
        $fullPath = storage_path('app/' . $file);
    }
    
    // Si toujours pas trouvé
    if (!file_exists($fullPath)) {
        abort(404, 'Fichier non trouvé: ' . $file);
    }

    $fileContents = file_get_contents($fullPath);
    $mimeType = mime_content_type($fullPath) ?: 'application/octet-stream';

    return Response::make($fileContents, 200, [
        'Content-Type' => $mimeType,
        'Cache-Control' => 'public, max-age=86400',
    ]);

})->where('file', '.*')->name('storage.documents');

Route::get('/api/documentation', function () {
    return view('documentation.index',);
});

// Route::get('/', function () {
//     return view('welcome');
// });

