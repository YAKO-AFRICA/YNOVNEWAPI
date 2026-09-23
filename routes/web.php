<?php

// use App\Http\Controllers\Api\ApiController;
// use App\Http\Controllers\Api\JekoPaymentController;
// use App\Http\Controllers\Api\ReceiptController;
use App\Http\Controllers\Api\Ynov\PaymentController;
use App\Http\Controllers\Api\Ynov\ReceiptController;
use App\Http\Controllers\Api\Ynov\SignatureController;
use App\Models\Api\Ynov\Esouscription\Document;
use App\Services\Api\Ynov\SignatureService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
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

// ============================================================
// SIGNATURE ÉLECTRONIQUE - Widget Web dans web.php
// ============================================================
// Route::prefix('signature')->group(function () {
//     // Servir le widget avec token (route web pour affichage direct)
//     Route::get('widget/{token}', [SignatureController::class, 'serveWidget']);
    
//     // Page de démo du widget
//     Route::get('demo', function () {
//         return view('demo-signature-widget');
//     });
    
//     // // Interface de test externe complète
//     Route::get('test-client', function () {
//         return response()->file(public_path('signature-test-client.html'));
//     });
    
//     // Proxy pour contourner CORS sur les documents externes
//     Route::get('proxy-document', function (\Illuminate\Http\Request $request) {
//         $url = $request->query('url');
        
//         if (!$url) {
//             return response()->json(['error' => 'URL parameter required'], 400);
//         }
        
//         // Valider l'URL
//         if (!filter_var($url, FILTER_VALIDATE_URL)) {
//             return response()->json(['error' => 'Invalid URL'], 400);
//         }
        
//         try {
//             // Récupérer le document depuis l'URL externe
//             // sans suivre les cookies ni les redirections automatiques
//             $response = Http::withoutRedirecting()->get($url);
            
//             // Suivre les redirections manuellement si nécessaire
//             if ($response->status() >= 300 && $response->status() < 400) {
//                 $redirectUrl = $response->header('Location');
//                 if ($redirectUrl) {
//                     $response = Http::get($redirectUrl);
//                 }
//             }
            
//             if (!$response->successful()) {
//                 return response()->json([
//                     'error' => 'Failed to fetch document',
//                     'status' => $response->status(),
//                     'url' => $url
//                 ], $response->status());
//             }
            
//             // Retourner le document avec headers CORS
//             return response($response->body())
//                 ->header('Content-Type', $response->header('Content-Type') ?: 'application/pdf')
//                 ->header('Access-Control-Allow-Origin', '*')
//                 ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
//                 ->header('Access-Control-Allow-Headers', '*')
//                 ->header('Cache-Control', 'public, max-age=3600');
//         } catch (\Exception $e) {
//             return response()->json([
//                 'error' => $e->getMessage(),
//                 'url' => $url
//             ], 500);
//         }
//     });
// });

// =====================================================================
// routes/web.php
// =====================================================================
Route::prefix('signature')->group(function () {
 
    // Page publique de signature
    Route::get('widget/{token}', [SignatureController::class, 'serveWidget'])
        ->where('token', SignatureService::TOKEN_PATTERN)
        ->name('signature.widget');


    /*
    |------------------------------------------------------------------
    | Page de démonstration
    |------------------------------------------------------------------
    | Génère un VRAI lien de signature côté serveur (via le service),
    | avec un webhook d'écho local qui tient lieu d'app hôte. Les boutons
    | « Tester » exercent donc le flux complet (génération -> signature ->
    | relais -> polling), pas une simulation déconnectée du backend.
    |
    | Le token est à usage unique : une fois la démo réellement signée,
    | rechargez /signature/demo pour en obtenir un nouveau.
    |
    | À restreindre ou désactiver en production (throttle ci-dessous,
    | ou condition sur config('app.env') si vous préférez la couper
    | totalement hors environnement de démonstration).
    */
    Route::get('demo', function (SignatureService $signatureService) {
        $result = $signatureService->generateSignatureLink(
            'https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf',
            'Contrat de démonstration à signer',
            // Marqueur interne : évite un auto-appel HTTP (Laravel -> Laravel) qui
            // se bloque sur un serveur mono-thread (php artisan serve). Voir
            // SignatureService::INTERNAL_ECHO_MARKER et deliverToHost().
            SignatureService::INTERNAL_ECHO_MARKER,
            'demo-' . Str::random(32),
            null,
            null,
            true,
            900 // 15 min, largement suffisant pour une démo
        );
 
        return view('demo-signature-widget', [
            'token'                => $result['token'],
            'widget_url'           => $result['widget_url'],
            'status_url'           => $result['status_url'],
            'backend_webhook_url'  => url('/api/v1/signature/webhook'),
            'api_url'              => url('/api/v1/signature'),
            'document_url'         => $result['document_url'],
            'document_description' => $result['document_description'],
        ]);
    })->middleware('throttle:10,1')->name('signature.demo');
 
    /*
    |------------------------------------------------------------------
    | Proxy documents — durci contre le SSRF
    |------------------------------------------------------------------
    | L'ancienne version acceptait n'importe quelle URL passant
    | FILTER_VALIDATE_URL, y compris http://169.254.169.254/ (métadonnées
    | cloud) ou vos services internes, et renvoyait le corps avec
    | Access-Control-Allow-Origin: *.
    |
    | Ici : schémas http/https uniquement, résolution DNS vérifiée,
    | plages IP privées et loopback bloquées, taille et type limités.
    | Configurez la liste blanche dans config/services.php :
    |   'signature' => ['allowed_document_hosts' => ['docs.yakoafrica.ci']]
    */
    Route::get('proxy-document', function (Request $request) {
        $url = (string) $request->query('url');
 
        if (!$url || !filter_var($url, FILTER_VALIDATE_URL)) {
            abort(400, 'URL invalide.');
        }
 
        $parts  = parse_url($url);
        $scheme = strtolower($parts['scheme'] ?? '');
        $host   = $parts['host'] ?? '';
 
        if (!in_array($scheme, ['http', 'https'], true) || $host === '') {
            abort(400, 'Schéma non autorisé.');
        }
 
        // Liste blanche (recommandé en production)
        $allowedHosts = (array) config('services.signature.allowed_document_hosts', []);
        if ($allowedHosts && !in_array($host, $allowedHosts, true)) {
            abort(403, 'Domaine non autorisé.');
        }
 
        // Blocage des cibles internes
        $ip = gethostbyname($host);
        if (
            !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)
            && !filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)
        ) {
            abort(403, 'Cible non autorisée.');
        }
 
        try {
            $response = Http::timeout(15)->withOptions(['allow_redirects' => false])->get($url);
 
            if (!$response->successful()) {
                abort(502, 'Document inaccessible.');
            }
 
            $contentType = $response->header('Content-Type') ?: 'application/octet-stream';
            $allowedTypes = ['application/pdf', 'image/png', 'image/jpeg', 'image/jpg'];
 
            if (!in_array(strtolower(trim(explode(';', $contentType)[0])), $allowedTypes, true)) {
                abort(415, 'Type de document non pris en charge.');
            }
 
            if (strlen($response->body()) > 25 * 1024 * 1024) {
                abort(413, 'Document trop volumineux.');
            }
 
            return response($response->body())
                ->header('Content-Type', $contentType)
                ->header('X-Content-Type-Options', 'nosniff')
                ->header('Cache-Control', 'private, max-age=600');
        } catch (\Throwable $e) {
            abort(502, 'Document inaccessible.');
        }
    })->name('signature.proxy');
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



