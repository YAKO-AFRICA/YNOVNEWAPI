<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Preuve de signature OTP — Démo</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
               background: #f3f4f6; margin: 0; padding: 24px; color: #1f2937; }
        .card { max-width: 720px; margin: 0 auto; background: #fff; border-radius: 14px;
                box-shadow: 0 10px 30px rgba(0,0,0,.06); padding: 28px 26px; }
        h1 { margin: 0 0 6px; font-size: 20px; color: #075429; }
        p.sub { margin: 0 0 22px; color: #6b7280; font-size: 13.5px; }
        table { width: 100%; border-collapse: collapse; font-size: 13.5px; }
        th, td { text-align: left; padding: 10px 12px; border-bottom: 1px solid #edf1ee;
                 word-break: break-word; vertical-align: top; }
        th { width: 180px; color: #374151; background: #f7faf7; font-weight: 600; }
        td code { background: #eef3f0; padding: 2px 6px; border-radius: 6px;
                  color: #0f172a; font-size: 12.5px; }
        .empty { color: #9ca3af; font-style: italic; }
        .note { margin-top: 22px; padding: 12px 14px; background: #fef3c7;
                border-left: 4px solid #f59e0b; border-radius: 8px; font-size: 13px;
                color: #92400e; }
        .back { display: inline-block; margin-top: 20px; padding: 10px 16px;
                background: #075429; color: #fff; border-radius: 8px; text-decoration: none;
                font-weight: 600; font-size: 13.5px; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Preuve de signature OTP</h1>
        <p class="sub">Contenu décodé du QR code envoyé par le widget comme preuve de signature.</p>

        <table>
            <tbody>
                @php
                    $labels = [
                        'user'      => 'Utilisateur (user_uuid)',
                        'login'     => 'Login',
                        'email'     => 'Email',
                        'nom'       => 'Nom',
                        'prenoms'   => 'Prénoms',
                        'mobile'    => 'Mobile',
                        'adresse'   => 'Adresse complète',
                        'ch'        => 'Canal OTP (channel)',
                        'contact'   => 'Contact utilisé',
                        'purpose'   => 'Purpose',
                        'ip'        => 'Adresse IP (autoritaire)',
                        'ua'        => 'User-Agent (autoritaire)',
                        'at'        => 'Horodatage (used_at)',
                        'lat'       => 'Latitude (navigateur)',
                        'lng'       => 'Longitude (navigateur)',
                    ];
                @endphp
                @foreach ($labels as $key => $label)
                    <tr>
                        <th>{{ $label }}</th>
                        <td>
                            @if (!empty($data[$key]))
                                <code>{{ $data[$key] }}</code>
                            @else
                                <span class="empty">— non fourni —</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="note">
            <strong>Note :</strong> <code>ip</code>, <code>ua</code> et <code>at</code> sont
            récupérés côté serveur dans <code>SignatureController::verifySignatureOtp</code>.
            <code>lat</code> et <code>lng</code> proviennent du navigateur (déclaratifs).
        </div>

        <a class="back" href="{{ url('/signature/demo') }}">← Nouvelle démo</a>
    </div>
</body>
</html>