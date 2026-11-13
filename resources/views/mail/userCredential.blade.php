<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Bienvenue YNOV</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin:0; padding:0; background:#f5f5f5;">

<div style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 40px auto;">

    <!-- HEADER -->
    <div style="background: linear-gradient(135deg, #076835 0%, #f7a400 100%); padding: 20px; border-radius: 10px 10px 0 0; text-align: center;">
        <h2 style="color: white; margin: 0; font-size: 24px;">🎉 Bienvenue sur YNOV !</h2>
        <p style="color: #e8f0fe; margin: 10px 0 0 0; font-size: 16px;">
            Votre compte a été créé avec succès
        </p>
    </div>

    <!-- BODY -->
    <div style="background: white; padding: 30px; border: 1px solid #e0e0e0; border-top: none;">

        <p style="margin: 0 0 20px 0; font-size: 16px;">Bonjour,</p>

        <p style="margin: 0 0 20px 0; font-size: 16px;">
            Félicitations ! Votre compte YNOV a été créé avec succès.
            Nous sommes ravis de vous accueillir dans notre communauté.
        </p>

        <!-- IDENTIFIANTS -->
        <div style="background: #f8f9fa; border-left: 4px solid #1a73e8; padding: 20px; margin: 20px 0; border-radius: 0 8px 8px 0;">

            <h3 style="margin: 0 0 15px 0; color: #1a73e8; font-size: 18px;">
                🔑 Vos identifiants de connexion
            </h3>

            <div style="background: white; padding: 15px; border-radius: 8px; border: 1px solid #e0e0e0;">

                <p style="margin: 0 0 10px 0; font-size: 16px;">
                    <strong style="color: #1a73e8;">📧 Email :</strong>
                    <span style="background: #f0f0f0; padding: 2px 6px; border-radius: 4px; font-family: monospace;">
                        {{ $email ?? 'user@email.com' }}
                    </span>
                </p>

                <p style="margin: 0; font-size: 16px;">
                    <strong style="color: #1a73e8;">🔐 Mot de passe :</strong>
                    <span style="background: #f0f0f0; padding: 2px 6px; border-radius: 4px; font-family: monospace;">
                        {{ $password ?? '********' }}
                    </span>
                </p>

            </div>
        </div>

        <!-- WARNING -->
        <div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 8px; margin: 20px 0;">
            <p style="margin: 0; font-size: 14px; color: #856404;">
                <strong>⚠️ Important :</strong>
                Pour des raisons de sécurité, changez votre mot de passe dès la première connexion.
            </p>
        </div>

        <!-- CTA -->
        <p style="margin: 20px 0; font-size: 16px; text-align: center;">
            Cliquez sur le bouton ci-dessous pour vous connecter :
        </p>

        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ url('/') }}" style="
                background: #076835;
                color: white;
                padding: 15px 30px;
                text-decoration: none;
                border-radius: 8px;
                font-weight: bold;
                font-size: 16px;
                display: inline-block;
                box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            ">
                🚀 Se connecter maintenant
            </a>
        </div>

        <!-- TIP -->
        <div style="background: #e8f5e8; border: 1px solid #c3e6c3; padding: 15px; border-radius: 8px; margin: 20px 0;">
            <p style="margin: 0; font-size: 14px; color: #155724;">
                <strong>💡 Astuce :</strong>
                Marquez cet email comme favori pour retrouver vos identifiants.
            </p>
        </div>

        <hr style="border: none; border-top: 1px solid #e0e0e0; margin: 30px 0;">

        <p style="font-size: 16px;">
            Si vous avez des questions, notre support est disponible.
        </p>

        <p style="font-size: 16px;">
            Cordialement,<br>
            <strong style="color: #076835;">L'équipe YakoAfrica</strong> 🌍
        </p>

    </div>

    <!-- FOOTER -->
    <div style="background: #f8f9fa; padding: 15px; border-radius: 0 0 10px 10px; text-align: center; border: 1px solid #e0e0e0; border-top: none;">
        <p style="margin: 0; font-size: 12px; color: #666;">
            © 2025 YAKOAFRICA - Tous droits réservés<br>
            <span style="color: #999;">
                Cet email a été envoyé automatiquement, merci de ne pas y répondre.
            </span>
        </p>
    </div>

</div>

</body>
</html>
