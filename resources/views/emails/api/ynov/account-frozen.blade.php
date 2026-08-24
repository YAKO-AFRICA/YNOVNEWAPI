{{-- resources/views/emails/api/ynov/account-frozen.blade.php --}}
@extends('emails.api.ynov.layouts.base')

@section('content')
    @php
        $isManual = $level === 4;
        $icon = $isManual ? '🔒' : '❄️';
        $title = $isManual 
            ? 'Compte gelé par un administrateur' 
            : 'Compte temporairement gelé';
        $bannerColor = $isManual ? '#c9372c' : '#F7A400';
        $bannerText = $isManual 
            ? '🔒 GEL MANUEL — Action administrative' 
            : '⏳ GEL TEMPORAIRE — Compte bloqué';
        
        // Niveaux de gel
        $freezeLevels = [
            1 => 'Léger',
            2 => 'Modéré',
            3 => 'Sévère',
            4 => 'Manuel (Administrateur)',
        ];
        $levelLabel = $freezeLevels[$level] ?? 'Inconnu';
    @endphp

    {{-- En-tête --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td style="padding-bottom:16px;">
                <span style="font-size:24px; font-weight:700; color:#096835;">
                    {{ $icon }} {{ $title }}
                </span>
            </td>
        </tr>
        <tr>
            <td style="padding-bottom:4px;">
                <span style="font-size:16px; font-weight:600; color:#0f172a;">Bonjour <strong style="color:#096835;">{{ $name }}</strong>,</span>
            </td>
        </tr>
    </table>

    {{-- Message principal --}}
    @if($isManual)
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
            <tr>
                <td style="padding-bottom:16px;">
                    <span style="font-size:14px; color:#475569; line-height:1.7;">
                        Un administrateur a <strong style="color:#c9372c;">gelé</strong> votre compte YNOV.
                        Vous ne pouvez pas vous connecter jusqu'à nouvel ordre.
                    </span>
                </td>
            </tr>
        </table>

        {{-- Détails du gel manuel --}}
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:8px 0 16px 0; background-color:#fdf1ef; border-left:4px solid #c9372c; border-radius:4px; padding:12px 16px;">
            <tr>
                <td style="padding-bottom:6px;">
                    <span style="font-size:14px; font-weight:600; color:#8a2d24;">📝 Motif du gel :</span>
                </td>
            </tr>
            <tr>
                <td style="padding-bottom:12px;">
                    <span style="font-size:14px; color:#5a1a14; line-height:1.6;">
                        {{ $reason ?? 'Gel manuel par administrateur' }}
                    </span>
                </td>
            </tr>
            <tr>
                <td style="padding-top:8px; border-top:1px solid rgba(201,55,44,0.15);">
                    <span style="font-size:14px; font-weight:600; color:#8a2d24;">👤 Effectué par :</span>
                    <span style="font-size:14px; color:#5a1a14; margin-left:4px;">
                        {{ $adminName ?? 'Administrateur' }}
                    </span>
                </td>
            </tr>
        </table>

    @else
        {{-- Gel automatique --}}
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
            <tr>
                <td style="padding-bottom:16px;">
                    <span style="font-size:14px; color:#475569; line-height:1.7;">
                        Votre compte a été temporairement <strong style="color:#F7A400;">gelé</strong> après 
                        plusieurs tentatives de connexion échouées.
                    </span>
                </td>
            </tr>
        </table>

        {{-- Avertissement sécurité --}}
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:8px 0 16px 0;">
            <tr>
                <td style="background-color:#fff6e5; border-left:4px solid #F7A400; padding:12px 16px; border-radius:4px;">
                    <table role="presentation" cellpadding="0" cellspacing="0">
                        <tr>
                            <td style="vertical-align:middle; padding-right:10px;">
                                <span style="font-size:18px; color:#F7A400;">⚠️</span>
                            </td>
                            <td>
                                <span style="font-size:13px; color:#7c5a00; line-height:1.6;">
                                    <strong>Niveau de gel :</strong> {{ $level }} — {{ $levelLabel }}
                                    <br>
                                    <strong>Durée :</strong> {{ $duration }} secondes ({{ $durationMinutes }} minutes)
                                </span>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    @endif

    {{-- Informations du gel --}}
    @php
        $infoRows = [
            '📅 Date du gel' => now()->format('d/m/Y à H:i:s'),
            '⏱️ Durée totale' => $duration . 's (' . $durationMinutes . ' min)',
            '🔓 Dégel prévu' => now()->addSeconds($duration)->format('d/m/Y à H:i:s'),
        ];
        
        if ($isManual) {
            $infoRows['👤 Administrateur'] = $adminName ?? 'Administrateur';
            $infoRows['📝 Motif'] = $reason ?? 'Non spécifié';
        }
        
        if (!$isManual) {
            $infoRows['📊 Niveau'] = $level . ' — ' . $levelLabel;
            $infoRows['🔄 Statut'] = '⏳ Gelé';
        }
    @endphp

    @include('emails.api.ynov.partials.info-table', ['rows' => $infoRows])

    {{-- Message d'action --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td style="padding:12px 0 8px 0;">
                <span style="font-size:14px; color:#475569; line-height:1.7;">
                    @if($isManual)
                        📧 Si vous avez des questions concernant ce gel, veuillez contacter votre administrateur ou 
                        <a href="mailto:support@ynov.ci" style="color:#096835; font-weight:600; text-decoration:none;">
                            support@ynov.ci
                        </a>
                    @else
                        🔑 Vous pourrez vous reconnecter après l'expiration du gel.
                        <br>
                        ⚠️ Si vous n'êtes pas à l'origine de ces tentatives, contactez immédiatement l'administrateur.
                    @endif
                </span>
            </td>
        </tr>
    </table>

    {{-- Bouton d'action (uniquement pour gel automatique) --}}
    @if(!$isManual)
        @include('emails.api.ynov.partials.button', [
            'url' => config('app.frontend_url') . '/',
            'label' => '🔑 Se connecter',
            'color' => '#096835',
            'hoverColor' => '#06471f'
        ])
    @else
        {{-- Bouton contact support --}}
        @include('emails.api.ynov.partials.button', [
            'url' => 'mailto:support@ynov.ci',
            'label' => '📧 Contacter le support',
            'color' => '#F7A400',
            'hoverColor' => '#d68b00'
        ])
    @endif

    {{-- Note de sécurité --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top:24px; border-top:1px solid #e2e8f0; padding-top:20px;">
        <tr>
            <td style="font-size:12px; color:#94a3b8; line-height:1.6; text-align:center;">
                <span style="color:#096835;">●</span> 
                Cet email a été envoyé automatiquement par la plateforme YNOV.
                <span style="color:#096835;">●</span>
            </td>
        </tr>
        <tr>
            <td style="font-size:11px; color:#cbd5e1; text-align:center; padding-top:4px;">
                YNOV — YAKO AFRICA Assurances Vie | Sécurité des comptes
            </td>
        </tr>
    </table>
@endsection