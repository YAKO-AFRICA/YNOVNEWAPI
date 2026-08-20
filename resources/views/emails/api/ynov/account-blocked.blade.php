{{-- resources/views/emails/api/ynov/account-blocked.blade.php --}}
@extends('emails.api.ynov.layouts.base')

@php
    $bannerColor = '#b91c1c';
    $bannerText = 'Compte bloqué';
@endphp

@section('content')
    <h1 style="margin:0 0 16px 0; font-size:20px; color:#0f172a;">Bonjour {{ $fullName }},</h1>

    <p style="margin:0 0 16px 0; font-size:14px; color:#334155; line-height:1.6;">
        Votre compte YNOV a été bloqué par un administrateur. Vous ne pouvez plus vous connecter à la plateforme jusqu'à nouvel ordre.
    </p>

    @include('emails.api.ynov.partials.info-table', ['rows' => [
        'Motif' => $reason,
        'Bloqué le' => $blockedAt,
        'Bloqué par' => $blockedBy,
    ]])

    <p style="margin:16px 0; font-size:14px; color:#334155; line-height:1.6;">
        Si vous pensez qu'il s'agit d'une erreur, veuillez contacter votre administrateur ou le support YNOV.
    </p>
@endsection
