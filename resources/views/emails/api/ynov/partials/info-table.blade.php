{{-- resources/views/emails/api/ynov/partials/info-table.blade.php --}}
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f8fafc; border-radius:6px; margin:20px 0; border:1px solid #e2e8f0;">
    @foreach($rows as $label => $value)
    <tr>
        <td style="padding:10px 16px; font-size:13px; color:#64748b; width:40%; border-bottom:1px solid #e2e8f0; background-color:#f8fafc;">
            <span style="color:#096835; font-weight:600;">{{ $label }}</span>
        </td>
        <td style="padding:10px 16px; font-size:13px; color:#0f172a; font-weight:500; border-bottom:1px solid #e2e8f0; background-color:#ffffff;">
            {{ $value ?? '—' }}
        </td>
    </tr>
    @endforeach
    {{-- Dernière ligne sans bordure --}}
    <tr>
        <td style="padding:10px 16px; font-size:13px; color:#64748b; width:40%; border-bottom:none; background-color:#f8fafc;">
            <span style="color:#096835; font-weight:600;">ID Sécurité</span>
        </td>
        <td style="padding:10px 16px; font-size:12px; color:#94a3b8; font-family:monospace; border-bottom:none; background-color:#ffffff;">
            {{ uniqid() }}
        </td>
    </tr>
</table>