<?php

// use App\Http\Controllers\Api\ApiController;
// use App\Http\Controllers\Api\JekoPaymentController;
// use App\Http\Controllers\Api\ReceiptController;
use App\Http\Controllers\Api\Ynov\PaymentController;
use App\Http\Controllers\Api\Ynov\ReceiptController;
use App\Http\Controllers\Api\Ynov\SignatureController;
use App\Models\Api\Ynov\Esouscription\Document;
use App\Services\Api\Ynov\SignatureService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

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
// =====================================================================
// routes/web.php
// =====================================================================
Route::prefix('signature')->group(function () {

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
                ->header('Access-Control-Allow-Origin', '*')
                ->header('Access-Control-Allow-Methods', 'GET')
                ->header('X-Content-Type-Options', 'nosniff')
                ->header('Cache-Control', 'private, max-age=600');
        } catch (\Throwable $e) {
            abort(502, 'Document inaccessible.');
        }
    })->name('signature.proxy')->middleware('throttle:60,1');

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
            'https://web.yakoafricassur.com/storage/prestations/etatPrestations/Prestation_PREST-WYP39.pdf',
            'Contrat de démonstration à signer',
            // Marqueur interne : évite un auto-appel HTTP (Laravel -> Laravel) qui
            // se bloque sur un serveur mono-thread (php artisan serve). Voir
            // SignatureService::INTERNAL_ECHO_MARKER et deliverToHost().
            SignatureService::INTERNAL_ECHO_MARKER,
            'demo-' . Str::random(32),
            null,
            null,
            true,
            900, // 15 min
            [
                // --- Paramètres OTP de démonstration ---
                // Le but est de montrer le flux OTP sans dépendre d'un vrai
                // signataire en base. En production, ces valeurs viennent de
                // l'app hôte (identifiant réel de l'utilisateur).
                'signer_login'         => '0544970711',
                'signer_user_uuid'     => null,
                'signer_email'         => 'aziz37ouattara@gmail.com',
                'signer_phone'         => '0544970711',
                'otp_purpose'          => 'signature demo',
                // Template d'URL que le widget utilisera pour encoder le QR
                // de preuve. Chaque {placeholder} est remplacé par le widget
                // avec les valeurs autoritaires renvoyées par /signature/otp/verify.
                'otp_qr_url_template'  => url('/signature/demo/proof')
                    . '?user={user_uuid}&login={login}&email={email}'
                    . '&nom={nom}&prenoms={prenoms}&mobile={mobile_1}&adresse={adresse_complete}'
                    . '&ch={channel}&contact={contact}&purpose={purpose}'
                    . '&ip={ip_address}&ua={user_agent}&at={used_at}&lat={lat}&lng={lng}',
            ]
        );

        return view('demo-signature-widget', [
            'token'                => $result['token'],
            'widget_url'           => $result['widget_url'],
            'status_url'           => $result['status_url'],
            'backend_webhook_url'  => url('/api/v1/signature/webhook'),
            'api_url'              => url('/api/v1/signature'),
            'document_url'         => $result['document_url'],
            'document_description' => $result['document_description'],
            // OTP (démo)
            'otp_send_url'         => url('/api/v1/auth/otp/send'),
            'otp_verify_url'       => url('/api/v1/signature/otp/verify'),
            'signer_login'         => '0544970711',
            'signer_email'         => 'aziz37ouattara@gmail.com',
            'signer_phone'         => '0544970711',
            'otp_purpose'          => 'signature demo',
            'otp_qr_url_template'  => $result['otp_qr_url_template'] ?? null,
        ]);
    })->name('signature.demo');

    /*
    |------------------------------------------------------------------
    | Page de preuve OTP (démonstration)
    |------------------------------------------------------------------
    | Affiche les paramètres encodés dans le QR de preuve.
    */
    Route::get('demo/proof', function (Request $request) {
        return view('demo-signature-proof', [
            'data' => $request->query(),
        ]);
    })->name('signature.demo.proof');
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



