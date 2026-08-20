{{-- resources/views/emails/api/ynov/partials/button.blade.php --}}
{{-- Usage : @include('emails.api.ynov.partials.button', ['url' => $url, 'label' => 'Se connecter']) --}}
<table role="presentation" cellpadding="0" cellspacing="0" style="margin:24px 0;">
    <tr>
        <td style="border-radius:6px; background-color:{{ $color ?? '#0f172a' }};">
            <a href="{{ $url }}" target="_blank"
               style="display:inline-block; padding:12px 28px; font-size:14px; font-weight:600; color:#ffffff; text-decoration:none; border-radius:6px;">
                {{ $label }}
            </a>
        </td>
    </tr>
</table>
