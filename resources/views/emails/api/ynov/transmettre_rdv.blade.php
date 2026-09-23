@extends('emails.api.ynov.layouts.base')

@section('content')
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td style="padding-bottom:24px;">
                <h1 style="margin:0 0 16px 0; font-size:24px; font-weight:700; color:#096835;">
                    {{ $sujet }}
                </h1>
                <p style="margin:0 0 24px 0; font-size:15px; color:#334155; line-height:1.6;">
                    {!! $messageContent !!}
                </p>
            </td>
        </tr>
    </table>

    {{-- Tableau d'informations --}}
    @include('emails.api.ynov.partials.info-table', [
        'rows' => [
            'Pièce jointe' => $fichierNom,
            'Date d\'envoi' => now()->format('d/m/Y H:i'),
        ]
    ])

    {{-- Action --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:32px 0;">
        <tr>
            <td style="text-align:center;">
                <p style="margin:0 0 12px 0; font-size:14px; color:#64748b;">
                    Veuillez procéder au traitement dans les meilleurs délais.
                </p>
            </td>
        </tr>
    </table>
@endsection
