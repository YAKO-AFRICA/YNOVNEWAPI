<?php

// use App\Http\Controllers\Api\ApiController;
// use App\Http\Controllers\Api\JekoPaymentController;
// use App\Http\Controllers\Api\ReceiptController;
use App\Http\Controllers\Api\Ynov\PaymentController;
use App\Http\Controllers\Api\Ynov\ReceiptController;
use App\Models\Api\Ynov\Esouscription\Document;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Route;
use RealRashid\SweetAlert\Facades\Alert;

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

Route::get('/', function () {
    return view('documentation.index');
});

Route::get('storage/documents/{file}', function ($file) {
    // Nettoyer le nom du fichier
    $path = base_path(env('UPLOADS_PATH', '../public_html/upload/documents-test') . $file);
    
    
    // Si toujours pas trouvé
    if (!file_exists($path)) {
        abort(404, 'Fichier non trouvé: ' . $file);
    }

    $fileContents = file_get_contents($path);
    $mimeType = mime_content_type($path);

    return Response::make($fileContents, 200, [
        'Content-Type' => $mimeType,
        'Cache-Control' => 'public, max-age=86400',
    ]);

})->where('file', '.*')->name('storage.documents');

Route::get('get-document-contrat/{file}', function ($file) {
    $path = base_path(env('GET_CUSTOMER_CP') . $file);

    if (!file_exists($path)) {
        abort(404);
    }

    $fileContents = file_get_contents($path);
    $mimeType = mime_content_type($path);

    return Response::make($fileContents, 200, ['Content-Type' => $mimeType]);
    
})->where('file', '.*');


Route::prefix('paiement')->name('paiement.')->group(function () {
    Route::get('recu/{referenceInterne}', [ReceiptController::class, 'show'])->name('recu');
    Route::get('recu/{referenceInterne}/download', [ReceiptController::class, 'download'])->name('recu.download');
});
Route::get('/api/v1/demo-jeko-widget',[PaymentController::class, 'demoJekoWidget'])->name('demo-jeko-widget');

Route::get('/api/documentation', function () {
    return view('documentation.index',);
});

Route::get('preview/doc/{file}', function ($file) {

    $doc = Document::where('nom_fichier', $file)->first();

    if (!$doc) {
        return response ()->json([
            'success' => false,
            'message' => 'Document introuvable .',
        ]);
    }

    $path = base_path($doc->chemin);

    if (!file_exists($path)) {
        return response ()->json([
            'success' => false,
            'message' => 'Le fichier nom_fichier ' . $doc->nom_fichier . ' n\'existe pas dans le repertoire.',
        ]);
    }

    $fileContents = file_get_contents($path);
    $mimeType = mime_content_type($path);

    return Response::make(
        $fileContents,
        200,
        [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . $doc->nom_fichier . '"',
        ]
    );

})->where('file', '.*');



