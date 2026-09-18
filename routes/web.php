<?php

// use App\Http\Controllers\Api\ApiController;
// use App\Http\Controllers\Api\JekoPaymentController;
// use App\Http\Controllers\Api\ReceiptController;
use App\Http\Controllers\Api\Ynov\PaymentController;
use App\Http\Controllers\Api\Ynov\ReceiptController;
use App\Http\Controllers\Api\Ynov\SignatureController;
use Illuminate\Support\Facades\Http;
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

// ============================================================
// SIGNATURE ÉLECTRONIQUE - Widget Web
// ============================================================
Route::prefix('signature')->group(function () {
    // Servir le widget avec token (route web pour affichage direct)
    Route::get('widget/{token}', [SignatureController::class, 'serveWidget']);
    
    // Page de démo du widget
    Route::get('demo', function () {
        return view('demo-signature-widget');
    });
    
    // // Interface de test externe complète
    Route::get('test-client', function () {
        return response()->file(public_path('signature-test-client.html'));
    });
    
    // Proxy pour contourner CORS sur les documents externes
    Route::get('proxy-document', function (\Illuminate\Http\Request $request) {
        $url = $request->query('url');
        
        if (!$url) {
            return response()->json(['error' => 'URL parameter required'], 400);
        }
        
        // Valider l'URL
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return response()->json(['error' => 'Invalid URL'], 400);
        }
        
        try {
            // Récupérer le document depuis l'URL externe
            // sans suivre les cookies ni les redirections automatiques
            $response = Http::withoutRedirecting()->get($url);
            
            // Suivre les redirections manuellement si nécessaire
            if ($response->status() >= 300 && $response->status() < 400) {
                $redirectUrl = $response->header('Location');
                if ($redirectUrl) {
                    $response = Http::get($redirectUrl);
                }
            }
            
            if (!$response->successful()) {
                return response()->json([
                    'error' => 'Failed to fetch document',
                    'status' => $response->status(),
                    'url' => $url
                ], $response->status());
            }
            
            // Retourner le document avec headers CORS
            return response($response->body())
                ->header('Content-Type', $response->header('Content-Type') ?: 'application/pdf')
                ->header('Access-Control-Allow-Origin', '*')
                ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
                ->header('Access-Control-Allow-Headers', '*')
                ->header('Cache-Control', 'public, max-age=3600');
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'url' => $url
            ], 500);
        }
    });
});



