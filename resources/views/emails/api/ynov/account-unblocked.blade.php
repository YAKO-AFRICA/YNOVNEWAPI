{{-- resources/views/emails/api/ynov/account-unblocked.blade.php --}}
@extends('emails.api.ynov.layouts.base')

@php
    $bannerColor = '#0f766e';
    $bannerText = 'Compte débloqué';
@endphp

@section('content')
    <h1 style="margin:0 0 16px 0; font-size:20px; color:#0f172a;">Bonjour {{ $fullName }},</h1>

    <p style="margin:0 0 16px 0; font-size:14px; color:#334155; line-height:1.6;">
        Bonne nouvelle : votre compte YNOV vient d'être débloqué. Vous pouvez désormais vous
        reconnecter normalement à la plateforme.
    </p>

    @include('emails.api.ynov.partials.info-table', ['rows' => [
        'Débloqué le' => $unblockedAt,
        'Débloqué par' => $unblockedBy,
    ]])

    @include('emails.api.ynov.partials.button', ['url' => $loginUrl, 'label' => 'Se connecter', 'color' => '#0f766e'])
@endsection