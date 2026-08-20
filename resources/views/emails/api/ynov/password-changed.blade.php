{{-- resources/views/emails/api/ynov/password-changed.blade.php --}}
@extends('emails.api.ynov.layouts.base')

@php
    $bannerColor = '#0f766e';
    $bannerText = 'Mot de passe modifié';
@endphp

@section('content')
    <h1 style="margin:0 0 16px 0; font-size:20px; color:#0f172a;">Bonjour {{ $fullName }},</h1>

    <p style="margin:0 0 16px 0; font-size:14px; color:#334155; line-height:1.6;">
        Votre mot de passe YNOV vient d'être modifié avec succès.
    </p>

    @include('emails.api.ynov.partials.info-table', ['rows' => [
        'Date de modification' => $changedAt,
        'Adresse IP' => $ipAddress,
    ]])

    <p style="margin:16px 0 0 0; font-size:13px; color:#64748b; line-height:1.6;">
        Si vous n'êtes pas à l'origine de ce changement, contactez immédiatement votre administrateur : votre compte pourrait être compromis.
    </p>
@endsection