{{-- resources/views/emails/api/ynov/two-factor-disabled.blade.php --}}
@extends('emails.api.ynov.layouts.base')

@php
    $bannerColor = '#b91c1c';
    $bannerText = '2FA désactivée';
@endphp

@section('content')
    <h1 style="margin:0 0 16px 0; font-size:20px; color:#0f172a;">Bonjour {{ $fullName }},</h1>

    <p style="margin:0 0 16px 0; font-size:14px; color:#334155; line-height:1.6;">
        L'authentification à deux facteurs vient d'être <strong>désactivée</strong> sur votre compte
        YNOV. Votre compte est désormais protégé uniquement par votre mot de passe.
    </p>

    @include('emails.api.ynov.partials.info-table', ['rows' => [
        'Désactivée le' => $disabledAt,
        'Adresse IP' => $ipAddress,
    ]])

    <p style="margin:16px 0; font-size:14px; color:#334155; line-height:1.6;">
        Nous vous recommandons de réactiver la 2FA pour renforcer la sécurité de votre compte.
    </p>

    <p style="margin:16px 0 0 0; font-size:13px; color:#64748b; line-height:1.6;">
        Si vous n'êtes pas à l'origine de cette action, changez votre mot de passe immédiatement
        et contactez votre administrateur.
    </p>

    @include('emails.api.ynov.partials.button', ['url' => $securityUrl, 'label' => 'Gérer ma sécurité', 'color' => '#b91c1c'])
@endsection