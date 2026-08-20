@extends('emails.api.ynov.layouts.base')

@section('content')
    @php
        $bannerColor = match ($purpose) {
            'login' => '#076633',
            '2fa' => '#F7A400',
            'reset' => '#dc3545',
            default => '#076633',
        };
        $bannerText = match ($purpose) {
            'login' => '🔐 Code de connexion OTP',
            '2fa' => '🔐 Code de double authentification',
            'reset' => '🔐 Code de réinitialisation',
            default => '🔐 Code de vérification',
        };
    @endphp

    <h2 style="margin:0 0 16px 0; font-size:22px; color:#0f172a;">
        🔑 Votre code de vérification
    </h2>

    <p style="margin:0 0 16px 0; font-size:15px; color:#334155; line-height:1.7;">
        Bonjour <strong>{{ $name }}</strong>,
    </p>

    <p style="margin:0 0 20px 0; font-size:15px; color:#334155; line-height:1.7;">
        Vous avez demandé un code de vérification pour <strong>{{ $purposeLabel }}</strong>.
        Voici votre code à usage unique :
    </p>

    {{-- Code OTP --}}
    <div style="background-color:#f8fafc; border:2px dashed #076633; border-radius:12px; padding:24px; margin:24px 0; text-align:center;">
        <p style="margin:0; font-size:13px; color:#64748b; letter-spacing:2px;">VOTRE CODE</p>
        <p style="margin:8px 0 0 0; font-size:36px; font-weight:700; color:#076633; letter-spacing:8px; font-family: 'Courier New', monospace;">
            {{ $code }}
        </p>
    </div>

    {{-- Informations complémentaires --}}
    @include('emails.api.ynov.partials.info-table', [
        'rows' => [
            '📌 Usage' => ucfirst($purpose),
            '⏱️ Expiration' => $expiresInMinutes . ' minutes',
            '📅 Date' => now()->format('d/m/Y à H:i:s'),
        ]
    ])

    <div style="background-color:#fff3cd; border-left:4px solid #F7A400; padding:12px 16px; border-radius:4px; margin:20px 0;">
        <p style="margin:0; font-size:13px; color:#856404; line-height:1.5;">
            <i class="bi bi-exclamation-triangle" style="margin-right:6px;"></i>
            <strong>Ne partagez jamais ce code avec personne.</strong>
            Ce code expire dans <strong>{{ $expiresInMinutes }} minutes</strong>.
            Si vous n'êtes pas à l'origine de cette demande, ignorez cet email.
        </p>
    </div>

    <p style="margin:20px 0 0 0; font-size:14px; color:#334155; line-height:1.7;">
        Si vous n'avez pas demandé ce code, veuillez <strong>contacter immédiatement</strong> l'administrateur.
    </p>

    <div style="margin-top:24px; padding-top:16px; border-top:1px solid #e2e8f0;">
        <p style="margin:0; font-size:12px; color:#94a3b8; line-height:1.5;">
            <i class="bi bi-info-circle" style="margin-right:4px;"></i>
            Cet email a été envoyé automatiquement. Merci de ne pas y répondre.
        </p>
    </div>
@endsection