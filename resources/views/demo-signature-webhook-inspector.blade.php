<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Inspecteur Webhook — Démo Signature</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
               background: #f3f4f6; margin: 0; padding: 24px; color: #1f2937; }
        .container { max-width: 900px; margin: 0 auto; background: #fff;
                     border-radius: 14px; padding: 24px; box-shadow: 0 8px 24px rgba(0,0,0,.06); }
        h1 { font-size: 20px; margin: 0 0 8px; color: #075429; }
        p.sub { margin: 0 0 20px; color: #6b7280; font-size: 13.5px; }
        .banner { padding: 12px 14px; border-radius: 10px; font-size: 14px;
                  display: flex; align-items: center; gap: 10px; margin-bottom: 16px; }
        .banner.ok { background: #dcfce7; color: #166534; }
        .banner.wait { background: #fef3c7; color: #92400e; }
        .banner a { color: inherit; text-decoration: underline; font-weight: 600; }
        .spinner { width: 14px; height: 14px; border-radius: 50%;
                   border: 2px solid currentColor; border-top-color: transparent;
                   animation: spin .8s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }
        .meta { display: grid; grid-template-columns: 200px 1fr; gap: 6px 12px;
                font-size: 13px; margin: 16px 0; background: #f9fafb;
                border-radius: 10px; padding: 14px; }
        .meta dt { color: #6b7280; font-weight: 600; }
        .meta dd { margin: 0; color: #111827; word-break: break-all; }
        .code { background: #0c1f15; color: #d7ecdf; border-radius: 12px;
                padding: 16px; overflow-x: auto; font-size: 12px; line-height: 1.6; }
        .code pre { margin: 0; white-space: pre-wrap; word-break: break-word;
                    font-family: SFMono-Regular, Consolas, monospace; }
        img.preview { max-width: 320px; border: 1px solid #e5e7eb; border-radius: 8px;
                      margin-top: 12px; display: block; background: #fff; }
        .actions { margin-top: 20px; display: flex; gap: 10px; flex-wrap: wrap; }
        .btn { display: inline-block; padding: 10px 16px; border-radius: 8px;
               background: #075429; color: #fff; text-decoration: none;
               font-size: 13.5px; font-weight: 600; }
        .btn.secondary { background: #f3f4f6; color: #374151; }
        .btn:hover { opacity: .92; }
        .section { margin-top: 22px; }
        .section h2 { font-size: 15px; color: #111827; margin: 0 0 10px;
                      padding-bottom: 6px; border-bottom: 1px solid #edf1ee; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Inspecteur Webhook — Démo</h1>
        <p class="sub">Session : <code>{{ $demo_id }}</code></p>

        @if ($webhook)
            <div class="banner ok">
                ✅ Webhook reçu le {{ $webhook['received_at'] ?? '—' }}
                @if (!empty($webhook['ip'])) (source : {{ $webhook['ip'] }}) @endif
            </div>

            <div class="section">
                <h2>Métadonnées</h2>
                <dl class="meta">
                    <dt>Method</dt>
                    <dd>{{ $webhook['payload']['method'] ?? '—' }}</dd>

                    <dt>Signature UUID</dt>
                    <dd>{{ $webhook['payload']['signature_request_uuid'] ?? '—' }}</dd>

                    <dt>Token</dt>
                    <dd>{{ $webhook['payload']['token'] ?? '—' }}</dd>

                    <dt>Document</dt>
                    <dd>{{ $webhook['payload']['document_url'] ?? '—' }}</dd>

                    <dt>Signed at</dt>
                    <dd>{{ $webhook['payload']['signed_at'] ?? '—' }}</dd>

                    <dt>Timestamp</dt>
                    <dd>{{ $webhook['payload']['timestamp'] ?? '—' }}</dd>

                    <dt>X-Api-Key reçu</dt>
                    <dd>{{ $webhook['api_key_header'] ?? '—' }}</dd>

                    <dt>Content-Type</dt>
                    <dd>{{ $webhook['content_type'] ?? '—' }}</dd>
                </dl>
            </div>

            @if (($webhook['payload']['method'] ?? '') === 'otp_qr' && isset($webhook['payload']['otp']))
                <div class="section">
                    <h2>Données OTP (autoritaires)</h2>
                    <dl class="meta">
                        <dt>Canal</dt>      <dd>{{ $webhook['payload']['otp']['channel']    ?? '—' }}</dd>
                        <dt>Contact</dt>    <dd>{{ $webhook['payload']['otp']['contact']    ?? '—' }}</dd>
                        <dt>Purpose</dt>    <dd>{{ $webhook['payload']['otp']['purpose']    ?? '—' }}</dd>
                        <dt>IP</dt>         <dd>{{ $webhook['payload']['otp']['ip_address'] ?? '—' }}</dd>
                        <dt>User-Agent</dt> <dd>{{ $webhook['payload']['otp']['user_agent'] ?? '—' }}</dd>
                        <dt>Used at</dt>    <dd>{{ $webhook['payload']['otp']['used_at']    ?? '—' }}</dd>
                        <dt>User UUID</dt>  <dd>{{ $webhook['payload']['otp']['user_uuid']  ?? '—' }}</dd>
                        <dt>Login</dt>      <dd>{{ $webhook['payload']['otp']['login']      ?? '—' }}</dd>
                        <dt>Email</dt>      <dd>{{ $webhook['payload']['otp']['email']      ?? '—' }}</dd>
                        <dt>Nom</dt>        <dd>{{ $webhook['payload']['otp']['nom']        ?? '—' }}</dd>
                        <dt>Prénoms</dt>    <dd>{{ $webhook['payload']['otp']['prenoms']    ?? '—' }}</dd>
                        <dt>Mobile</dt>     <dd>{{ $webhook['payload']['otp']['mobile_1']   ?? '—' }}</dd>
                        <dt>Adresse</dt>    <dd>{{ $webhook['payload']['otp']['adresse_complete'] ?? '—' }}</dd>
                    </dl>
                </div>
            @endif

            @if (isset($webhook['payload']['geo']))
                <div class="section">
                    <h2>Géolocalisation (déclarative)</h2>
                    <dl class="meta">
                        <dt>Latitude</dt>  <dd>{{ $webhook['payload']['geo']['lat'] ?? '—' }}</dd>
                        <dt>Longitude</dt> <dd>{{ $webhook['payload']['geo']['lng'] ?? '—' }}</dd>
                    </dl>
                </div>
            @endif

            <div class="section">
                <h2>Signature reçue</h2>
                @if (!empty($webhook['payload']['signature']))
                    <img class="preview" src="{{ $webhook['payload']['signature'] }}" alt="Signature">
                    <p class="sub" style="margin-top: 8px;">
                        <a download="signature.png" href="{{ $webhook['payload']['signature'] }}"
                           class="btn secondary" style="font-size: 12.5px; padding: 8px 12px;">
                            ⬇ Télécharger la signature (PNG)
                        </a>
                    </p>
                @else
                    <p class="sub">Aucune image de signature dans le payload.</p>
                @endif
            </div>

            <div class="section">
                <h2>Payload JSON brut</h2>
                <div class="code">
                    <pre>{{ json_encode($webhook['payload'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre>
                </div>
            </div>
        @else
            <div class="banner wait">
                <span class="spinner"></span>
                En attente du webhook… Cette page se rafraîchit automatiquement.
            </div>
            <p class="sub" style="margin-top: 16px;">
                Signez le document depuis le widget. Dès que la signature sera terminée,
                le payload apparaîtra ici automatiquement.
            </p>
        @endif

        <div class="actions">
            <a class="btn" href="{{ url('/signature/demo') }}">← Nouvelle démo</a>
            <a class="btn secondary" href="{{ url('/signature/demo/webhook-inspector?demo_id=' . $demo_id) }}">Rafraîchir</a>
            @if ($context)
                <a class="btn secondary" href="{{ url('/signature/widget/' . $context['token']) }}">Ouvrir le widget</a>
            @endif
        </div>
    </div>

    @if (!$webhook)
        <script>
            setTimeout(function () {
                window.location.href = '{{ url('/signature/demo/webhook-inspector?demo_id=' . $demo_id) }}';
            }, 3000);
        </script>
    @endif
</body>
</html>