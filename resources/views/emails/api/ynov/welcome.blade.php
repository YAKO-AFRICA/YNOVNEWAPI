{{-- resources/views/emails/api/ynov/welcome.blade.php --}}
@extends('emails.api.ynov.layouts.base')

@php
    $bannerColor = '#0f766e';
    $bannerText = 'Bienvenue sur YNOV';
@endphp

@section('content')
    <h1 style="margin:0 0 16px 0; font-size:20px; color:#0f172a;">Bonjour {{ $fullName }},</h1>

    <p style="margin:0 0 16px 0; font-size:14px; color:#334155; line-height:1.6;">
        Votre compte YNOV vient d'être créé par un administrateur. Vous pouvez dès à présent accéder à la plateforme avec les identifiants ci-dessous.
    </p>

    @include('emails.api.ynov.partials.info-table', ['rows' => [
        'Login' => $login,
        'Mot de passe' => $password,
    ]])

    <p style="margin:16px 0; font-size:14px; color:#334155; line-height:1.6;">
        Pour des raisons de sécurité, vous devrez définir votre propre mot de passe dès votre première connexion.
    </p>

    @include('emails.api.ynov.partials.button', ['url' => $loginUrl, 'label' => 'Accéder à YNOV', 'color' => '#0f766e'])

    <p style="margin:16px 0 0 0; font-size:13px; color:#64748b; line-height:1.6;">
        Ce lien est valable pour votre première connexion. Si vous rencontrez un problème, contactez votre administrateur.
    </p>
@endsection
