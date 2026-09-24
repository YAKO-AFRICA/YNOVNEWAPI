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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
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
Route::get('/api/v1/demo-jeko-widget', [PaymentController::class, 'demoJekoWidget'])->name('demo-jeko-widget');

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
    // Route::get('demo', function (SignatureService $signatureService) {
    //     $result = $signatureService->generateSignatureLink(
    //         'https://web.yakoafricassur.com/storage/prestations/etatPrestations/Prestation_PREST-WYP39.pdf',
    //         'Contrat de démonstration à signer',
    //         // Marqueur interne : évite un auto-appel HTTP (Laravel -> Laravel) qui
    //         // se bloque sur un serveur mono-thread (php artisan serve). Voir
    //         // SignatureService::INTERNAL_ECHO_MARKER et deliverToHost().
    //         SignatureService::INTERNAL_ECHO_MARKER,
    //         // SignatureService::INTERNAL_ECHO_MARKER,
    //         // 'demo-' . Str::random(32),
    //         null,
    //         null,
    //         true,
    //         900, // 15 min
    //         [
    //             // --- Paramètres OTP de démonstration ---
    //             // Le but est de montrer le flux OTP sans dépendre d'un vrai
    //             // signataire en base. En production, ces valeurs viennent de
    //             // l'app hôte (identifiant réel de l'utilisateur).
    //             'signer_login'         => '0544970711',
    //             'signer_user_uuid'     => null,
    //             'signer_email'         => 'aziz37ouattara@gmail.com',
    //             'signer_phone'         => '0544970711',
    //             'otp_purpose'          => 'signature demo',
    //             // Template d'URL que le widget utilisera pour encoder le QR
    //             // de preuve. Chaque {placeholder} est remplacé par le widget
    //             // avec les valeurs autoritaires renvoyées par /signature/otp/verify.
    //             'otp_qr_url_template'  => url('/signature/demo/proof')
    //                 . '?user={user_uuid}&login={login}&email={email}'
    //                 . '&nom={nom}&prenoms={prenoms}&mobile={mobile_1}&adresse={adresse_complete}'
    //                 . '&ch={channel}&contact={contact}&purpose={purpose}'
    //                 . '&ip={ip_address}&ua={user_agent}&at={used_at}&lat={lat}&lng={lng}',
    //         ]
    //     );

    //     return view('demo-signature-widget', [
    //         'token'                => $result['token'],
    //         'widget_url'           => $result['widget_url'],
    //         'status_url'           => $result['status_url'],
    //         'backend_webhook_url'  => url('/api/v1/signature/webhook'),
    //         'api_url'              => url('/api/v1/signature'),
    //         'document_url'         => $result['document_url'],
    //         'document_description' => $result['document_description'],
    //         // OTP (démo)
    //         'otp_send_url'         => url('/api/v1/auth/otp/send'),
    //         'otp_verify_url'       => url('/api/v1/signature/otp/verify'),
    //         'signer_login'         => '0544970711',
    //         'signer_email'         => 'aziz37ouattara@gmail.com',
    //         'signer_phone'         => '0544970711',
    //         'otp_purpose'          => 'signature demo',
    //         'otp_qr_url_template'  => $result['otp_qr_url_template'] ?? null,
    //     ]);
    // })->name('signature.demo');

    /*
    |------------------------------------------------------------------
    | Page de démonstration
    |------------------------------------------------------------------
    | Génère un VRAI lien de signature côté serveur (via le service),
    | avec un webhook d'écho local qui enregistre le payload reçu.
    | Le token est à usage unique : une fois signé, rechargez /signature/demo.
    */
    // Route::get('demo', function (SignatureService $signatureService, Request $request) {

    //     // --- Paramètres personnalisables via query string ---
    //     // Ex: /signature/demo?phone=0700000000&email=test@exemple.ci&purpose=contrat
    //     $signerLogin  = $request->query('login',  '0544970711');
    //     $signerEmail  = $request->query('email',  'aziz37ouattara@gmail.com');
    //     $signerPhone  = $request->query('phone',  '0544970711');
    //     $otpPurpose   = $request->query('purpose', 'signature demo');
    //     $expiresIn    = (int) $request->query('expires_in', 900);
    //     $documentUrl  = $request->query(
    //         'document_url',
    //         'https://web.yakoafricassur.com/storage/prestations/etatPrestations/Prestation_PREST-WYP39.pdf'
    //     );
    //     $docDesc      = $request->query('document_description', 'Contrat de démonstration à signer');

    //     // ID unique de session de démo (pour retrouver l'écho du webhook)
    //     $demoId = Str::uuid()->toString();

    //     // Marqueur interne : évite un auto-appel HTTP (Laravel -> Laravel).
    //     // Voir SignatureService::INTERNAL_ECHO_MARKER.
    //     //
    //     // NOTE : en mode "echo interne", le webhook N'EST PAS réellement appelé.
    //     // Pour un test concret bout-en-bout, remplacez cette valeur par
    //     // url('/signature/demo/webhook-echo') et la route associée (plus bas)
    //     // enregistrera le payload reçu.
    //     $webhookUrl = 'http://ynovmigration.test/signature/demo/webhook-echo?demo_id=' . $demoId;
    //     // $webhookUrl = url('/signature/demo/webhook-echo?demo_id=' . $demoId);

    //     $result = $signatureService->generateSignatureLink(
    //         $documentUrl,
    //         $docDesc,
    //         $webhookUrl,               // <- webhook réel de test (echo local)
    //         null,                      // success_redirect_url
    //         null,                      // cancel_redirect_url
    //         true,                      // enable_auto_polling
    //         $expiresIn,                // 15 min par défaut
    //         [
    //             'signer_login'         => $signerLogin,
    //             'signer_user_uuid'     => null,
    //             'signer_email'         => $signerEmail,
    //             'signer_phone'         => $signerPhone,
    //             'otp_purpose'          => $otpPurpose,
    //             'otp_qr_url_template'  => 'http://ynovmigration.test/signature/demo/proof'
    //                 . '?user={user_uuid}&login={login}&email={email}'
    //                 . '&nom={nom}&prenoms={prenoms}&mobile={mobile_1}&adresse={adresse_complete}'
    //                 . '&ch={channel}&contact={contact}&purpose={purpose}'
    //                 . '&ip={ip_address}&ua={user_agent}&at={used_at}&lat={lat}&lng={lng}',
    //         ]
    //     );

    //     // On mémorise le contexte de démo pour la page "proof" et le "webhook-echo"
    //     Cache::put('signature_demo:' . $demoId, [
    //         'token'        => $result['token'],
    //         'signer_login' => $signerLogin,
    //         'signer_email' => $signerEmail,
    //         'signer_phone' => $signerPhone,
    //         'otp_purpose'  => $otpPurpose,
    //         'created_at'   => now()->toIso8601String(),
    //     ], now()->addHours(2));

    //     return view('demo-signature-widget', [
    //         'demo_id'              => $demoId,
    //         'token'                => $result['token'],
    //         'widget_url'           => $result['widget_url'],
    //         'status_url'           => $result['status_url'],
    //         'backend_webhook_url'  => url('/api/v1/signature/webhook'),
    //         'api_url'              => url('/api/v1/signature'),
    //         'document_url'         => $result['document_url'],
    //         'document_description' => $result['document_description'],

    //         // OTP (démo)
    //         'otp_send_url'         => url('/api/v1/auth/otp/send'),
    //         'otp_verify_url'       => url('/api/v1/signature/otp/verify'),
    //         'signer_login'         => $signerLogin,
    //         'signer_email'         => $signerEmail,
    //         'signer_phone'         => $signerPhone,
    //         'otp_purpose'          => $otpPurpose,
    //         'otp_qr_url_template'  => $result['otp_qr_url_template'] ?? null,
    //     ]);
    // })->middleware('throttle:10,1')->name('signature.demo');


    /*
    |------------------------------------------------------------------
    | Page de démonstration
    |------------------------------------------------------------------
    */
    Route::get('demo', function (SignatureService $signatureService, Request $request) {

        $signerLogin = $request->query('login',  '0544970711');
        $signerEmail = $request->query('email',  'aziz37ouattara@gmail.com');
        $signerPhone = $request->query('phone',  '0544970711');
        $otpPurpose  = $request->query('purpose', 'signature demo');
        $expiresIn   = (int) $request->query('expires_in', 900);

        $documentUrl = $request->query(
            'document_url',
            'https://web.yakoafricassur.com/storage/prestations/etatPrestations/Prestation_PREST-WYP39.pdf'
        );
        $docDesc = $request->query('document_description', 'Contrat de démonstration à signer');

        $demoId = (string) Str::uuid();
        Log::info('DEMO ID', ['id' => $demoId]);
        // --- Sélection du webhook ---
        $externalBase = env('DEMO_WEBHOOK_BASE');
        $localBase    = rtrim(config('app.url'), '/');

        // Priorité : DEMO_WEBHOOK_BASE > app.url (local)
        $webhookBase = $externalBase ?: $localBase;

        $webhookUrl = $webhookBase
            . '/signature/demo/webhook-echo?demo_id=' . urlencode($demoId);

        $qrTemplate = $webhookBase
            . '/signature/demo/proof?demo_id=' . urlencode($demoId)
            . '&user={user_uuid}&login={login}&email={email}'
            . '&nom={nom}&prenoms={prenoms}&mobile={mobile_1}&adresse={adresse_complete}'
            . '&ch={channel}&contact={contact}&purpose={purpose}'
            . '&ip={ip_address}&ua={user_agent}&at={used_at}&lat={lat}&lng={lng}';

        $result = $signatureService->generateSignatureLink(
            $documentUrl,
            $docDesc,
            $webhookUrl,
            null,
            null,
            true,
            $expiresIn,
            [
                'signer_login'        => $signerLogin,
                'signer_user_uuid'    => null,
                'signer_email'        => $signerEmail,
                'signer_phone'        => $signerPhone,
                'otp_purpose'         => $otpPurpose,
                'otp_qr_url_template' => $qrTemplate,
            ]
        );

        Cache::put('signature_demo:' . $demoId, [
            'token'                  => $result['token'],
            'signature_request_uuid' => $result['signature_request_uuid'],
            'signer_login'           => $signerLogin,
            'signer_email'           => $signerEmail,
            'signer_phone'           => $signerPhone,
            'otp_purpose'            => $otpPurpose,
            'webhook_base'           => $webhookBase,
            'created_at'             => now()->toIso8601String(),
        ], now()->addHours(2));

        return view('demo-signature-widget', [
            'demo_id'                => $demoId,
            'token'                  => $result['token'],
            'signature_request_uuid' => $result['signature_request_uuid'],
            'widget_url'             => $result['widget_url'],
            'status_url'             => $result['status_url'],
            'backend_webhook_url'    => url('/api/v1/signature/webhook'),
            'api_url'                => url('/api/v1/signature'),
            'document_url'           => $result['document_url'],
            'document_description'   => $result['document_description'],

            'otp_send_url'           => url('/api/v1/auth/otp/send'),
            'otp_verify_url'         => url('/api/v1/signature/otp/verify'),
            'signer_login'           => $signerLogin,
            'signer_email'           => $signerEmail,
            'signer_phone'           => $signerPhone,
            'otp_purpose'            => $otpPurpose,
            'otp_qr_url_template'    => $result['otp_qr_url_template'] ?? null,

            'webhook_base'           => $webhookBase,
        ]);
    })->middleware('throttle:10,1')->name('signature.demo');


    /*
    |------------------------------------------------------------------
    | Écho du webhook — reçoit et STOCKE le payload de signature
    |------------------------------------------------------------------
    | Cette route est appelée par Laravel lui-même (via SignatureService::
    | deliverToHost) au moment où la signature est terminée. Elle
    | enregistre le payload en cache pour que l'inspecteur puisse l'afficher.
    |
    | En production, cette route est REMPLACÉE par le webhook réel de
    | votre app hôte (qui écrit en base de données).
    */
    Route::post('demo/webhook-echo', function (Request $request) {
        $demoId = (string) $request->query('demo_id');

        if (!$demoId) {
            return response()->json(['success' => false, 'error' => 'demo_id manquant'], 400);
        }

        $payload = $request->all();

        Cache::put('signature_demo_webhook:' . $demoId, [
            'received_at'    => now()->toIso8601String(),
            'ip'             => $request->ip(),
            'api_key_header' => $request->header('X-Api-Key'),
            'content_type'   => $request->header('Content-Type'),
            'payload'        => $payload,
        ], now()->addHours(6));

        Log::info('[Signature Demo] Webhook reçu', [
            'demo_id' => $demoId,
            'method'  => $payload['method'] ?? null,
            'uuid'    => $payload['signature_request_uuid'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Webhook reçu et stocké.',
            'demo_id' => $demoId,
            'next'    => url('/signature/demo/webhook-inspector?demo_id=' . $demoId),
        ]);
    })->middleware('throttle:60,1')->name('signature.demo.webhook-echo');


    /*
    |------------------------------------------------------------------
    | Inspecteur du webhook — affiche le payload stocké
    |------------------------------------------------------------------
    */
    Route::get('demo/webhook-inspector', function (Request $request) {
        $demoId = (string) $request->query('demo_id');

        if (!$demoId) {
            abort(400, 'Paramètre demo_id manquant.');
        }

        $context = Cache::get('signature_demo:' . $demoId);
        $webhook = Cache::get('signature_demo_webhook:' . $demoId);

        return view('demo-signature-webhook-inspector', [
            'demo_id'      => $demoId,
            'context'      => $context,
            'webhook'      => $webhook,
            'external_url' => $context['webhook_base'] ?? null,
        ]);
    })->name('signature.demo.webhook-inspector');


    /*
    |------------------------------------------------------------------
    | Page de preuve OTP
    |------------------------------------------------------------------
    */
    Route::get('demo/proof', function (Request $request) {
        return view('demo-signature-proof', [
            'data' => $request->query(),
        ]);
    })->name('signature.demo.proof');


    /*
    |------------------------------------------------------------------
    | Statut d'un token
    |------------------------------------------------------------------
    */
    Route::get('demo/status/{token}', function (string $token, SignatureService $signatureService) {
        $status = $signatureService->checkTokenStatus($token);

        if (!$status) {
            return response()->json([
                'success' => false,
                'code'    => 'INVALID_TOKEN',
                'message' => 'Token inconnu ou expiré.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'code'    => 'TOKEN_STATUS',
            'data'    => $status,
        ])->header('Cache-Control', 'no-store');
    })->where('token', SignatureService::TOKEN_PATTERN)
      ->name('signature.demo.status');

    // Route::get('demo', function (SignatureService $signatureService, Request $request) {

    //     // --- Paramètres personnalisables via query string ---
    //     $signerLogin = $request->query('login',  '0544970711');
    //     $signerEmail = $request->query('email',  'aziz37ouattara@gmail.com');
    //     $signerPhone = $request->query('phone',  '0544970711');
    //     $otpPurpose  = $request->query('purpose', 'signature demo');
    //     $expiresIn   = (int) $request->query('expires_in', 900);

    //     $documentUrl = $request->query(
    //         'document_url',
    //         'https://web.yakoafricassur.com/storage/prestations/etatPrestations/Prestation_PREST-WYP39.pdf'
    //     );
    //     $docDesc = $request->query('document_description', 'Contrat de démonstration à signer');

    //     $demoId = Str::uuid()->toString();

    //     // --- Sélection du webhook selon l'environnement ---
    //     $externalBase = env('DEMO_WEBHOOK_BASE');

    //     if ($externalBase) {
    //         // Mode webhook.site : tout passe par le service externe
    //         $base = rtrim($externalBase, '/');

    //         // webhook.site accepte les query strings, on y met demo_id pour tracer
    //         $webhookUrl = $base . '?demo_id=' . $demoId . '&source=signature_demo';

    //         // QR template : pointe aussi vers webhook.site pour observer le contenu
    //         $qrTemplate = $base
    //             . '?demo_id=' . $demoId
    //             . '&user={user_uuid}&login={login}&email={email}'
    //             . '&nom={nom}&prenoms={prenoms}&mobile={mobile_1}&adresse={adresse_complete}'
    //             . '&ch={channel}&contact={contact}&purpose={purpose}'
    //             . '&ip={ip_address}&ua={user_agent}&at={used_at}&lat={lat}&lng={lng}';

    //         $webhookMode = 'webhook_site';
    //     } else {
    //         // Mode interne : pas d'appel HTTP sortant
    //         $webhookUrl = SignatureService::INTERNAL_ECHO_MARKER;

    //         $qrTemplate = url('/signature/demo/proof')
    //             . '?user={user_uuid}&login={login}&email={email}'
    //             . '&nom={nom}&prenoms={prenoms}&mobile={mobile_1}&adresse={adresse_complete}'
    //             . '&ch={channel}&contact={contact}&purpose={purpose}'
    //             . '&ip={ip_address}&ua={user_agent}&at={used_at}&lat={lat}&lng={lng}';

    //         $webhookMode = 'internal_echo';
    //     }

    //     $result = $signatureService->generateSignatureLink(
    //         $documentUrl,
    //         $docDesc,
    //         $webhookUrl,
    //         null,                       // success_redirect_url
    //         null,                       // cancel_redirect_url
    //         true,                       // enable_auto_polling
    //         $expiresIn,
    //         [
    //             'signer_login'        => $signerLogin,
    //             'signer_user_uuid'    => null,
    //             'signer_email'        => $signerEmail,
    //             'signer_phone'        => $signerPhone,
    //             'otp_purpose'         => $otpPurpose,
    //             'otp_qr_url_template' => $qrTemplate,
    //         ]
    //     );

    //     // Contexte de session (utile pour l'inspecteur, en plus de webhook.site)
    //     Cache::put('signature_demo:' . $demoId, [
    //         'token'         => $result['token'],
    //         'signer_login'  => $signerLogin,
    //         'signer_email'  => $signerEmail,
    //         'signer_phone'  => $signerPhone,
    //         'otp_purpose'   => $otpPurpose,
    //         'webhook_mode'  => $webhookMode,
    //         'webhook_base'  => $externalBase,
    //         'created_at'    => now()->toIso8601String(),
    //     ], now()->addHours(2));

    //     return view('demo-signature-widget', [
    //         'demo_id'              => $demoId,
    //         'token'                => $result['token'],
    //         'widget_url'           => $result['widget_url'],
    //         'status_url'           => $result['status_url'],
    //         'backend_webhook_url'  => url('/api/v1/signature/webhook'),
    //         'api_url'              => url('/api/v1/signature'),
    //         'document_url'         => $result['document_url'],
    //         'document_description' => $result['document_description'],

    //         'otp_send_url'         => url('/api/v1/auth/otp/send'),
    //         'otp_verify_url'       => url('/api/v1/signature/otp/verify'),
    //         'signer_login'         => $signerLogin,
    //         'signer_email'         => $signerEmail,
    //         'signer_phone'         => $signerPhone,
    //         'otp_purpose'          => $otpPurpose,
    //         'otp_qr_url_template'  => $result['otp_qr_url_template'] ?? null,

    //         'webhook_mode'         => $webhookMode,
    //         'webhook_base'         => $externalBase,
    //     ]);
    // })->middleware('throttle:10,1')->name('signature.demo');

    /*
    |------------------------------------------------------------------
    | Écho du webhook (démo)
    |------------------------------------------------------------------
    | Reçoit le POST envoyé par SignatureService::deliverToHost()
    | quand la signature est terminée. Enregistre le payload en cache
    | pour que /signature/demo/webhook-inspector puisse l'afficher.
    |
    | Ce n'est PAS un webhook de production : il sert uniquement à
    | vérifier visuellement que la signature transite correctement.
    */

    // Route::post('demo/webhook-echo', function (Request $request) {
    //     $demoId = (string) $request->query('demo_id');

    //     if (!$demoId) {
    //         return response()->json(['success' => false, 'error' => 'demo_id manquant'], 400);
    //     }

    //     $payload = $request->all();

    //     Cache::put('signature_demo_webhook:' . $demoId, [
    //         'received_at'    => now()->toIso8601String(),
    //         'ip'             => $request->ip(),
    //         'api_key_header' => $request->header('X-Api-Key'),
    //         'content_type'   => $request->header('Content-Type'),
    //         'payload'        => $payload,
    //     ], now()->addHours(2));

    //     Log::info('[Signature Demo] Webhook écho reçu', [
    //         'demo_id' => $demoId,
    //         'method'  => $payload['method'] ?? null,
    //         'uuid'    => $payload['signature_request_uuid'] ?? null,
    //     ]);

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Écho de webhook reçu avec succès.',
    //         'demo_id' => $demoId,
    //         'method'  => $payload['method'] ?? null,
    //         'next'    => url('/signature/demo/webhook-inspector?demo_id=' . $demoId),
    //     ]);
    // })->middleware('throttle:60,1')->name('signature.demo.webhook-echo');

    // Route::get('demo/webhook-inspector', function (Request $request) {
    //     $demoId = (string) $request->query('demo_id');

    //     if (!$demoId) {
    //         abort(400, 'Paramètre demo_id manquant.');
    //     }

    //     $context = Cache::get('signature_demo:' . $demoId);

    //     // --- Récupération du webhook ---
    //     // Priorité 1 : cache local (mode echo interne ou ngrok)
    //     $webhook = Cache::get('signature_demo_webhook:' . $demoId);

    //     // Priorité 2 : API webhook.site si un token API est configuré
    //     if (!$webhook && ($token = env('DEMO_WEBHOOK_TOKEN'))) {
    //         $webhook = fetchLastWebhookFromWebhookSite($demoId, $token);
    //     }

    //     return view('demo-signature-webhook-inspector', [
    //         'demo_id'   => $demoId,
    //         'context'   => $context,
    //         'webhook'   => $webhook,
    //         'is_external' => ($context['webhook_mode'] ?? null) === 'webhook_site',
    //         'external_url' => $context['webhook_base'] ?? env('DEMO_WEBHOOK_BASE'),
    //     ]);
    // })->name('signature.demo.webhook-inspector');

    /**
     * Récupère la dernière requête reçue par webhook.site pour un demo_id donné.
     *
     * webhook.site expose une API REST :
     *   GET https://webhook.site/token/{token}/requests?sorting=newest
     *
     * Le "token" est le UUID présent dans l'URL webhook.site :
     *   https://webhook.site/abc-123 → token = abc-123
     *
     * Le DEMO_WEBHOOK_TOKEN est un token API à créer dans le compte webhook.site
     * (Settings → API token). Sans lui, l'API refuse l'accès.
     */
    // function fetchLastWebhookFromWebhookSite(string $demoId, string $apiToken): ?array
    // {
    //     $base = env('DEMO_WEBHOOK_BASE');
    //     if (!$base) {
    //         return null;
    //     }

    //     // Extraire l'UUID du webhook.site de l'URL
    //     // https://webhook.site/abc-123 → abc-123
    //     $path = parse_url($base, PHP_URL_PATH) ?? '';
    //     $webhookUuid = ltrim($path, '/');
    //     if (!$webhookUuid) {
    //         return null;
    //     }

    //     try {
    //         $response = \Illuminate\Support\Facades\Http::withHeaders([
    //             'Authorization' => 'Bearer ' . $apiToken,
    //             'Accept'        => 'application/json',
    //         ])
    //             ->timeout(5)
    //             ->get("https://webhook.site/token/{$webhookUuid}/requests", [
    //                 'sorting' => 'newest',
    //             ]);

    //         if (!$response->successful()) {
    //             return null;
    //         }

    //         $data = $response->json('data') ?? [];

    //         // Chercher la première requête contenant notre demo_id
    //         // (on l'a mis dans le query string)
    //         foreach ($data as $req) {
    //             $content = $req['content'] ?? '';
    //             $decoded = json_decode($content, true);

    //             // Vérifier si demo_id correspond (dans le query string de l'URL webhook.site)
    //             $url = $req['url'] ?? '';
    //             if (str_contains($url, 'demo_id=' . $demoId)) {
    //                 return [
    //                     'received_at'    => $req['created_at'] ?? now()->toIso8601String(),
    //                     'ip'             => $req['ip'] ?? null,
    //                     'api_key_header' => $req['headers']['x-api-key'][0] ?? null,
    //                     'content_type'   => $req['headers']['content-type'][0] ?? null,
    //                     'payload'        => $decoded ?: ['_raw' => $content],
    //                 ];
    //             }
    //         }

    //         return null;
    //     } catch (\Throwable $e) {
    //         Log::warning('[Signature Demo] Erreur API webhook.site', ['message' => $e->getMessage()]);
    //         return null;
    //     }
    // }


    // /*
    // |------------------------------------------------------------------
    // | Page de preuve OTP (démonstration)
    // |------------------------------------------------------------------
    // | Affiche les paramètres encodés dans le QR de preuve.
    // */
    // Route::get('demo/proof', function (Request $request) {
    //     return view('demo-signature-proof', [
    //         'data' => $request->query(),
    //     ]);
    // })->name('signature.demo.proof');
});

// Route::get('preview/doc/{file}', function ($file) {
//     $doc = Document::where('nom_fichier', $file)->first();
//     if (!$doc) {
//         return response()->json([
//             'success' => false,
//             'message' => 'Document introuvable .',
//         ]);
//     }
//     $path = base_path($doc->chemin);
//     if (!file_exists($path)) {
//         return response()->json([
//             'success' => false,
//             'message' => 'Le fichier nom_fichier ' . $doc->nom_fichier . ' n\'existe pas dans le repertoire.',
//         ]);
//     }
//     $fileContents = file_get_contents($path);
//     $mimeType = mime_content_type($path);
//     return Response::make(
//         $fileContents,
//         200,
//         [
//             'Content-Type' => $mimeType,
//             'Content-Disposition' => 'inline; filename="' . $doc->nom_fichier . '"',
//         ]
//     );
// })->where('file', '.*');

Route::get('storage/doc/{file}', function ($file) {
    // Nettoyer le nom du fichier
    $path = base_path(env('DOC_PATH') . $file);


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

Route::get('preview/doc/{file}', function ($file) {
    $doc = Document::where('nom_fichier', $file)->first();

    if (!$doc) {
        return response()->json([
            'success' => false,
            'message' => 'Document introuvable.',
        ], 404);
    }

    // On utilise le disque configuré + le chemin relatif stocké en BDD
    $disk = Storage::disk(config('documents.disk'));

    if (!$disk->exists($doc->chemin)) {
        return response()->json([
            'success' => false,
            'message' => "Le fichier {$doc->nom_fichier} n'existe pas dans le répertoire.",
            'chemin'  => $doc->chemin,
            'root'    => $disk->path(''),
        ], 404);
    }

    $absolutePath = $disk->path($doc->chemin);

    return response()->file($absolutePath, [
        'Content-Type'        => $doc->mime_type ?: mime_content_type($absolutePath),
        'Content-Disposition' => 'inline; filename="' . $doc->nom_fichier . '"',
    ]);
})->where('file', '.*');

