{{-- resources/views/emails/api/ynov/account-freeze-warning.blade.php --}}
@extends('emails.api.ynov.layouts.base')

@php
    $bannerColor = '#d97706';
    $bannerText = 'Activité suspecte détectée';
@endphp

@section('content')
    <h1 style="margin:0 0 16px 0; font-size:20px; color:#0f172a;">Bonjour {{ $name }},</h1>

    <p style="margin:0 0 16px 0; font-size:14px; color:#334155; line-height:1.6;">
        Nous avons détecté {{ $attemptCount }} tentative(s) de connexion échouée(s) sur votre
        compte YNOV. Il vous reste <strong>{{ $remainingAttempts }}</strong> tentative(s) avant un gel
        temporaire plus long de votre compte.
    </p>

    @include('emails.api.ynov.partials.info-table', ['rows' => [
        'Niveau d\'alerte' => $level,
        'Tentatives échouées' => $attemptCount,
        'Tentatives restantes' => $remainingAttempts,
    ]])

    <p style="margin:16px 0; font-size:14px; color:#334155; line-height:1.6;">
        Si c'est bien vous qui tentez de vous connecter, vérifiez votre mot de passe. Si ce n'est pas
        le cas, sécurisez votre compte immédiatement.
    </p>

    @include('emails.api.ynov.partials.button', ['url' => $securityUrl, 'label' => 'Vérifier la sécurité de mon compte', 'color' => '#d97706'])
@endsection