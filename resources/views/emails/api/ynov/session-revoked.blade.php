{{-- resources/views/emails/api/ynov/session-revoked.blade.php --}}
@extends('emails.api.ynov.layouts.base')

@php
    $bannerColor = '#0f172a';
    $bannerText = $allSessions ? 'Déconnexion générale' : 'Session révoquée';
@endphp

@section('content')
    <h1 style="margin:0 0 16px 0; font-size:20px; color:#0f172a;">Bonjour {{ $fullName }},</h1>

    @if($allSessions)
        <p style="margin:0 0 16px 0; font-size:14px; color:#334155; line-height:1.6;">
            Toutes les sessions actives de votre compte YNOV ont été déconnectées. Vous devrez vous
            reconnecter sur chacun de vos appareils.
        </p>
    @else
        <p style="margin:0 0 16px 0; font-size:14px; color:#334155; line-height:1.6;">
            Une session a été révoquée sur votre compte YNOV.
        </p>

        @include('emails.api.ynov.partials.info-table', ['rows' => [
            'Session révoquée' => $sessionName,
            'Date' => $revokedAt,
        ]])
    @endif

    <p style="margin:16px 0 0 0; font-size:13px; color:#64748b; line-height:1.6;">
        Si vous n'êtes pas à l'origine de cette action, contactez immédiatement votre administrateur.
    </p>

    @include('emails.api.ynov.partials.button', ['url' => $securityUrl, 'label' => 'Voir mes sessions', 'color' => '#0f172a'])
@endsection