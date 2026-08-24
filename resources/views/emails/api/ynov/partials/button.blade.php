{{-- resources/views/emails/api/ynov/partials/button.blade.php --}}
@php
    $buttonColor = $color ?? '#096835';
    $buttonHoverColor = $hoverColor ?? '#06471f';
@endphp

<table role="presentation" cellpadding="0" cellspacing="0" style="margin:24px 0;">
    <tr>
        <td style="border-radius:6px; background-color:{{ $buttonColor }};">
            <a href="{{ $url }}" target="_blank"
               style="display:inline-block; padding:12px 28px; font-size:14px; font-weight:600; color:#ffffff; text-decoration:none; border-radius:6px; background-color:{{ $buttonColor }}; transition:background-color 0.3s;"
               onmouseover="this.style.backgroundColor='{{ $buttonHoverColor }}'"
               onmouseout="this.style.backgroundColor='{{ $buttonColor }}'">
                {{ $label }}
            </a>
        </td>
    </tr>
</table>

{{-- Variantes de couleurs --}}
{{-- Bouton principal : default (vert) --}}
{{-- @include('emails.api.ynov.partials.button', ['url' => $url, 'label' => 'Se connecter']) --}}

{{-- Bouton accent (orange) --}}
{{-- @include('emails.api.ynov.partials.button', ['url' => $url, 'label' => 'Action', 'color' => '#F7A400', 'hoverColor' => '#d68b00']) --}}

{{-- Bouton secondaire (vert clair) --}}
{{-- @include('emails.api.ynov.partials.button', ['url' => $url, 'label' => 'En savoir plus', 'color' => '#0e8a47', 'hoverColor' => '#06471f']) --}}