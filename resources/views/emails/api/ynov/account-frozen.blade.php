@extends('emails.api.ynov.layouts.base')

@section('content')
    @php
        $isManual = $level === 4;
        $icon = $isManual ? '🔒' : '❄️';
        $title = $isManual 
            ? 'Compte gelé par un administrateur' 
            : 'Compte temporairement gelé';
        $bannerColor = $isManual ? '#dc3545' : '#F7A400';
        $bannerText = $isManual 
            ? '⚠️ GEL MANUEL — Action administrative' 
            : '⏳ GEL TEMPORAIRE — Compte bloqué';
    @endphp

    <h2 style="margin:0 0 16px 0; font-size:22px; color:#0f172a;">
        {{ $icon }} {{ $title }}
    </h2>

    <p style="margin:0 0 16px 0; font-size:15px; color:#334155; line-height:1.7;">
        Bonjour <strong>{{ $name }}</strong>,
    </p>

    @if($isManual)
        <p style="margin:0 0 20px 0; font-size:15px; color:#334155; line-height:1.7;">
            Un administrateur a <strong style="color:#dc3545;">gelé</strong> votre compte.
        </p>
        
        <div style="background-color:#f8d7da; border-left:4px solid #dc3545; padding:16px 20px; border-radius:4px; margin:20px 0;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    <td style="font-size:14px; color:#721c24; padding-bottom:6px;">
                        <strong>📝 Motif du gel :</strong><br>
                        {{ $reason ?? 'Gel manuel par administrateur' }}
                    </td>
                </tr>
                <tr>
                    <td style="font-size:14px; color:#721c24; padding-top:8px; border-top:1px solid rgba(220,53,69,0.15);">
                        <strong>👤 Effectué par :</strong> {{ $adminName ?? 'Administrateur' }}
                    </td>
                </tr>
            </table>
        </div>
    @else
        <p style="margin:0 0 20px 0; font-size:15px; color:#334155; line-height:1.7;">
            Votre compte a été temporairement <strong style="color:#F7A400;">gelé</strong> après plusieurs tentatives de connexion échouées.
        </p>
        
        <div style="background-color:#fff3cd; border-left:4px solid #F7A400; padding:16px 20px; border-radius:4px; margin:20px 0;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    <td style="font-size:14px; color:#856404; padding-bottom:6px;">
                        <strong>📊 Niveau de gel :</strong> {{ $level }} - {{ $level === 3 ? 'Sévère' : ($level === 2 ? 'Modéré' : 'Léger') }}
                    </td>
                </tr>
                <tr>
                    <td style="font-size:14px; color:#856404; padding-top:8px; border-top:1px solid rgba(247,164,0,0.15);">
                        <strong>⏱️ Durée :</strong> {{ $duration }} secondes ({{ $durationMinutes }} minutes)
                    </td>
                </tr>
            </table>
        </div>
    @endif

    @include('emails.api.ynov.partials.info-table', [
        'rows' => [
            '📅 Date du gel' => now()->format('d/m/Y à H:i:s'),
            '⏱️ Durée totale' => $duration . 's (' . $durationMinutes . ' min)',
            '🔓 Dégel prévu' => now()->addSeconds($duration)->format('d/m/Y à H:i:s'),
        ]
    ])

    <p style="margin:20px 0 12px 0; font-size:15px; color:#334155; line-height:1.7;">
        @if($isManual)
            Si vous avez des questions concernant ce gel, veuillez contacter votre administrateur.
        @else
            Vous pourrez vous reconnecter après l'expiration du gel.
            Si vous n'êtes pas à l'origine de ces tentatives, contactez immédiatement l'administrateur.
        @endif
    </p>

    @if(!$isManual)
        @include('emails.api.ynov.partials.button', [
            'url' => config('app.frontend_url') . '/',
            'label' => '🔑 Se connecter',
            'color' => '#076633'
        ])
    @endif

    <div style="margin-top:24px; padding-top:16px; border-top:1px solid #e2e8f0;">
        <p style="margin:0; font-size:13px; color:#64748b; line-height:1.6;">
            <i class="bi bi-shield-check" style="margin-right:6px;"></i>
            <strong>Besoin d'aide ?</strong>
        </p>
        <p style="margin:0; font-size:13px; color:#64748b; line-height:1.6;">
            Contactez le support technique ou votre administrateur pour plus d'informations.
        </p>
    </div>
@endsection