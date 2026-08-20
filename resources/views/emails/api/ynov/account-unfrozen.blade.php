@extends('emails.api.ynov.layouts.base')

@section('content')
    <h2 style="margin:0 0 16px 0; font-size:22px; color:#0f172a;">
        ✅ Compte dégelé
    </h2>

    <p style="margin:0 0 16px 0; font-size:15px; color:#334155; line-height:1.7;">
        Bonjour <strong>{{ $name ?? 'Utilisateur' }}</strong>,
    </p>

    <p style="margin:0 0 20px 0; font-size:15px; color:#334155; line-height:1.7;">
        Nous vous informons que votre compte a été <strong style="color:#076633;">dégelé</strong> avec succès.
    </p>

    {{-- Carte d'information sur le dégel --}}
    <div style="background-color:#d4edda; border-left:4px solid #076633; padding:16px 20px; border-radius:4px; margin:20px 0;">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
            <tr>
                <td style="font-size:14px; color:#155724;">
                    <strong>Motif du dégel :</strong> {{ $reason ?? 'Dégel manuel par administrateur' }}
                </td>
            </tr>
            @isset($adminName)
            <tr>
                <td style="font-size:14px; color:#155724; padding-top:8px;">
                    <strong>Effectué par :</strong> {{ $adminName }}
                </td>
            </tr>
            @endisset
        </table>
    </div>

    {{-- Informations complémentaires --}}
    @include('emails.api.ynov.partials.info-table', [
        'rows' => [
            'Date du dégel' => now()->format('d/m/Y à H:i:s'),
            'Statut du compte' => 'Actif',
        ]
    ])

    <p style="margin:20px 0 12px 0; font-size:15px; color:#334155; line-height:1.7;">
        Vous pouvez désormais vous reconnecter à votre compte en toute sécurité.
    </p>

    {{-- Bouton d'action --}}
    @include('emails.api.ynov.partials.button', [
        'url' => config('app.frontend_url') . '/',
        'label' => 'Se connecter',
        'color' => '#076633'
    ])

    <p style="margin:24px 0 0 0; font-size:13px; color:#64748b; line-height:1.6;">
        <i class="bi bi-shield-check" style="margin-right:6px;"></i>
        Si vous n'êtes pas à l'origine de cette demande ou si vous avez des questions,
        veuillez contacter immédiatement votre administrateur.
    </p>
@endsection