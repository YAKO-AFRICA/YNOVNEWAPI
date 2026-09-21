{{-- resources/views/signature/invalid.blade.php --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="robots" content="noindex, nofollow">
    <title>Lien indisponible</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #075429 0%, #0a6b35 100%);
            min-height: 100vh; margin: 0; display: flex; align-items: center;
            justify-content: center; padding: 24px;
        }
        .card {
            background: #fff; border-radius: 12px; padding: 40px 32px; max-width: 460px;
            text-align: center; box-shadow: 0 10px 40px rgba(0,0,0,.2);
        }
        .icon { font-size: 44px; color: #F7A400; margin-bottom: 12px; }
        h1 { font-size: 20px; color: #075429; margin: 0 0 12px; }
        p  { font-size: 14px; color: #4b5563; line-height: 1.6; margin: 0; }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">&#9888;</div>
        <h1>Ce lien n'est plus valable</h1>
        <p>
            Le lien de signature a expiré ou a déjà été utilisé.
            Rapprochez-vous de votre conseiller YAKOA AFRICASSUR pour en recevoir un nouveau.
        </p>
    </div>
</body>
</html>