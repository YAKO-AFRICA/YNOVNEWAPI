{{-- resources/views/emails/api/ynov/login-failed-alert.blade.php --}}
@extends('emails.api.ynov.layouts.base')

@php
    $bannerColor = '#d97706';
    $bannerText = 'Tentatives de connexion échouées';
@endphp

@section('content')
    <h1 style="margin:0 0 16px 0; font-size:20px; color:#0f172a;">Bonjour {{ $fullName }},</h1>

    <p style="margin:0 0 16px 0; font-size:14px; color:#334155; line-height:1.6;">
        {{ $attemptsCount }} tentatives de connexion consécutives ont échoué sur votre compte YNOV.
    </p>

    @include('emails.api.ynov.partials.info-table', ['rows' => [
        'Nombre de tentatives' => $attemptsCount,
        'Adresse IP' => $ipAddress,
        'Localisation' => $location,
        'Date' => $attemptedAt,
    ]])

    <p style="margin:16px 0; font-size:14px; color:#334155; line-height:1.6;">
        Si vous n'êtes pas à l'origine de ces tentatives, nous vous recommandons de changer votre
        mot de passe dès que possible.
    </p>

    @include('emails.api.ynov.partials.button', ['url' => $securityUrl, 'label' => 'Vérifier la sécurité de mon compte', 'color' => '#d97706'])
@endsection