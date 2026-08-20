{{-- resources/views/emails/api/ynov/email-verification.blade.php --}}
@extends('emails.api.ynov.layouts.base')

@php
    $bannerColor = '#1d4ed8';
    $bannerText = 'Vérification de votre adresse email';
@endphp

@section('content')
    <h1 style="margin:0 0 16px 0; font-size:20px; color:#0f172a;">Bonjour {{ $fullName }},</h1>

    <p style="margin:0 0 16px 0; font-size:14px; color:#334155; line-height:1.6;">
        Merci de confirmer votre adresse email en cliquant sur le bouton ci-dessous. Ce lien expire dans {{ $expiresInHours }} heures.
    </p>

    @include('emails.api.ynov.partials.button', ['url' => $verificationUrl, 'label' => 'Vérifier mon email', 'color' => '#1d4ed8'])

    <p style="margin:16px 0 0 0; font-size:13px; color:#64748b; line-height:1.6;">
        Si vous n'êtes pas à l'origine de cette demande, vous pouvez ignorer cet email.
    </p>
@endsection
