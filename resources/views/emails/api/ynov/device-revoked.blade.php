{{-- resources/views/emails/api/ynov/device-revoked.blade.php --}}
@extends('emails.api.ynov.layouts.base')

@php
    $bannerColor = '#0f172a';
    $bannerText = 'Appareil retiré';
@endphp

@section('content')
    <h1 style="margin:0 0 16px 0; font-size:20px; color:#0f172a;">Bonjour {{ $fullName }},</h1>

    <p style="margin:0 0 16px 0; font-size:14px; color:#334155; line-height:1.6;">
        L'appareil suivant a été retiré de la liste des appareils de confiance de votre compte YNOV.
        Il devra repasser par la vérification (2FA/OTP) lors de sa prochaine connexion.
    </p>

    @include('emails.api.ynov.partials.info-table', ['rows' => [
        'Appareil' => $deviceName,
        'Navigateur / OS' => $browserOs,
        'Retiré le' => $revokedAt,
    ]])

    <p style="margin:16px 0 0 0; font-size:13px; color:#64748b; line-height:1.6;">
        Si vous n'êtes pas à l'origine de cette action, contactez immédiatement votre administrateur.
    </p>

    @include('emails.api.ynov.partials.button', ['url' => $securityUrl, 'label' => 'Voir mes appareils', 'color' => '#0f172a'])
@endsection