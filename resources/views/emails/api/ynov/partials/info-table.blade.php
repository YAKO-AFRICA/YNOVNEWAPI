{{-- resources/views/emails/api/ynov/partials/info-table.blade.php --}}
{{-- Usage : @include('emails.api.ynov.partials.info-table', ['rows' => ['IP' => '1.2.3.4', 'Date' => '...']]) --}}
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f8fafc; border-radius:6px; margin:20px 0;">
    @foreach($rows as $label => $value)
    <tr>
        <td style="padding:10px 16px; font-size:13px; color:#64748b; width:40%; border-bottom:1px solid #e2e8f0;">{{ $label }}</td>
        <td style="padding:10px 16px; font-size:13px; color:#0f172a; font-weight:600; border-bottom:1px solid #e2e8f0;">{{ $value ?? '—' }}</td>
    </tr>
    @endforeach
</table>
