{{-- resources/views/emails/api/ynov/new-device.blade.php --}}
@extends('emails.api.ynov.layouts.base')

@php
    $bannerColor = '#1d4ed8';
    $bannerText = 'Nouvelle connexion détectée';
@endphp

@section('content')
    <h1 style="margin:0 0 16px 0; font-size:20px; color:#0f172a;">Bonjour {{ $fullName }},</h1>

    <p style="margin:0 0 16px 0; font-size:14px; color:#334155; line-height:1.6;">
        Une connexion à votre compte YNOV vient d'être effectuée depuis un appareil non reconnu.
    </p>

    @include('emails.api.ynov.partials.info-table', ['rows' => [
        'Appareil' => $deviceName,
        'Navigateur / OS' => $browserOs,
        'Adresse IP' => $ipAddress,
        'Localisation' => $location,
        'Date' => $loginAt,
    ]])

    <p style="margin:16px 0; font-size:14px; color:#334155; line-height:1.6;">
        Si c'est bien vous, aucune action n'est nécessaire. Dans le cas contraire, changez immédiatement votre mot de passe et contactez votre administrateur.
    </p>

    @include('emails.api.ynov.partials.button', ['url' => $securityUrl, 'label' => 'Voir mes appareils connectés', 'color' => '#1d4ed8'])
@endsection