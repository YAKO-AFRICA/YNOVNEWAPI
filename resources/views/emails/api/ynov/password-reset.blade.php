{{-- resources/views/emails/api/ynov/password-reset.blade.php --}}
@extends('emails.api.ynov.layouts.base')

@php
    $bannerColor = '#1d4ed8';
    $bannerText = 'Réinitialisation de mot de passe';
@endphp

@section('content')
    <h1 style="margin:0 0 16px 0; font-size:20px; color:#0f172a;">Bonjour {{ $fullName }},</h1>

    <p style="margin:0 0 16px 0; font-size:14px; color:#334155; line-height:1.6;">
        Vous avez demandé la réinitialisation de votre mot de passe YNOV. Cliquez sur le bouton
        ci-dessous pour définir un nouveau mot de passe. Ce lien expire dans {{ $expiresInMinutes }} minutes.
    </p>

    @include('emails.api.ynov.partials.button', ['url' => $resetUrl, 'label' => 'Réinitialiser mon mot de passe', 'color' => '#1d4ed8'])

    <p style="margin:16px 0 0 0; font-size:13px; color:#64748b; line-height:1.6;">
        Si vous n'êtes pas à l'origine de cette demande, ignorez cet email : votre mot de passe restera inchangé.
        Par sécurité, ne partagez jamais ce lien.
    </p>
@endsection