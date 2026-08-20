{{-- resources/views/emails/api/ynov/two-factor-enabled.blade.php --}}
@extends('emails.api.ynov.layouts.base')

@php
    $bannerColor = '#0f766e';
    $bannerText = 'Authentification à deux facteurs activée';
@endphp

@section('content')
    <h1 style="margin:0 0 16px 0; font-size:20px; color:#0f172a;">Bonjour {{ $fullName }},</h1>

    <p style="margin:0 0 16px 0; font-size:14px; color:#334155; line-height:1.6;">
        L'authentification à deux facteurs (2FA) vient d'être activée sur votre compte YNOV. Voici vos codes de récupération à conserver dans un endroit sûr — chacun ne peut être utilisé qu'une seule fois.
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f8fafc; border-radius:6px; margin:20px 0;">
        <tr>
            <td style="padding:16px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                    @foreach(array_chunk($recoveryCodes, 2) as $pair)
                    <tr>
                        <td style="padding:6px 8px; font-family:'Courier New', monospace; font-size:14px; color:#0f172a; width:50%;">{{ $pair[0] }}</td>
                        <td style="padding:6px 8px; font-family:'Courier New', monospace; font-size:14px; color:#0f172a; width:50%;">{{ $pair[1] ?? '' }}</td>
                    </tr>
                    @endforeach
                </table>
            </td>
        </tr>
    </table>

    <p style="margin:16px 0 0 0; font-size:13px; color:#64748b; line-height:1.6;">
        Si vous n'êtes pas à l'origine de cette activation, contactez immédiatement votre administrateur.
    </p>
@endsection
