<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $appName }} - SOP Reminder</title>
</head>
<body style="margin:0;padding:0;background:#f3f5f9;font-family:Arial,Helvetica,sans-serif;color:#1f2a37;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f3f5f9;padding:24px 12px;">
    <tr>
        <td align="center">
            <table role="presentation" width="760" cellpadding="0" cellspacing="0" style="max-width:760px;width:100%;background:#ffffff;border:1px solid #dbe2ee;border-radius:14px;overflow:hidden;">
                <tr>
                    <td style="background:linear-gradient(135deg,#f68f2d,#f15a0a);padding:22px 24px;color:#ffffff;">
                        <div style="font-size:22px;font-weight:700;line-height:1.2;">{{ $appName }}</div>
                        <div style="font-size:14px;opacity:0.9;margin-top:4px;">Grouped SOP Reminder Notification</div>
                    </td>
                </tr>
                <tr>
                    <td style="padding:24px;">
                        <p style="margin:0 0 10px 0;font-size:16px;">Hello {{ $picName }},</p>
                        <p style="margin:0 0 16px 0;font-size:14px;line-height:1.6;">
                            The following SOP documents require your attention.
                        </p>

                        <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:14px;">
                            <span style="display:inline-block;background:#eef2ff;color:#3730a3;padding:6px 10px;border-radius:999px;font-size:12px;font-weight:700;">Total: {{ $totalCount }}</span>
                            <span style="display:inline-block;background:#FDECEC;color:#C81E1E;padding:6px 10px;border-radius:999px;font-size:12px;font-weight:700;">Expired: {{ $expiredCount }}</span>
                            <span style="display:inline-block;background:#FFF6E5;color:#B45309;padding:6px 10px;border-radius:999px;font-size:12px;font-weight:700;">Expiring Soon: {{ $expiringCount }}</span>
                        </div>

                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;border:1px solid #e3e8f2;border-radius:10px;overflow:hidden;">
                            <thead>
                            <tr>
                                <th align="left" style="padding:10px;background:#f8fafd;border-bottom:1px solid #e3e8f2;font-size:12px;color:#5f6f86;">SOP</th>
                                <th align="left" style="padding:10px;background:#f8fafd;border-bottom:1px solid #e3e8f2;font-size:12px;color:#5f6f86;">Status</th>
                                <th align="left" style="padding:10px;background:#f8fafd;border-bottom:1px solid #e3e8f2;font-size:12px;color:#5f6f86;">Expiry Date</th>
                                <th align="left" style="padding:10px;background:#f8fafd;border-bottom:1px solid #e3e8f2;font-size:12px;color:#5f6f86;">Timeline</th>
                                <th align="left" style="padding:10px;background:#f8fafd;border-bottom:1px solid #e3e8f2;font-size:12px;color:#5f6f86;">Link</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($sopRows as $row)
                                <tr>
                                    <td style="padding:10px;border-bottom:1px solid #e3e8f2;font-size:14px;">
                                        <div style="font-weight:600;">{{ $row['title'] }}</div>
                                        <div style="font-size:12px;color:#6b7280;">SOP-{{ str_pad((string) $row['id'], 3, '0', STR_PAD_LEFT) }} | {{ $row['division'] }} | {{ $row['department'] }}</div>
                                    </td>
                                    <td style="padding:10px;border-bottom:1px solid #e3e8f2;font-size:14px;">
                                        <span style="display:inline-block;background:{{ $row['status_bg_color'] }};color:{{ $row['status_text_color'] }};padding:4px 8px;border-radius:999px;font-size:12px;font-weight:700;">
                                            {{ $row['status_label'] }}
                                        </span>
                                    </td>
                                    <td style="padding:10px;border-bottom:1px solid #e3e8f2;font-size:14px;">{{ $row['expiry_date_label'] }}</td>
                                    <td style="padding:10px;border-bottom:1px solid #e3e8f2;font-size:14px;">{{ $row['timeline'] }}</td>
                                    <td style="padding:10px;border-bottom:1px solid #e3e8f2;font-size:14px;">
                                        <a href="{{ $row['sop_url'] }}" style="color:#f26a21;text-decoration:none;font-weight:700;">Open SOP</a>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>

                        <p style="margin:14px 0 0 0;font-size:13px;line-height:1.6;color:#5f6f86;">
                            Please review and update these SOPs as needed.
                        </p>
                    </td>
                </tr>
                <tr>
                    <td style="padding:14px 24px;background:#f8fafd;border-top:1px solid #e3e8f2;font-size:12px;color:#7a889d;line-height:1.5;">
                        Batch ID: {{ $batchId }}<br>
                        Generated at: {{ $generatedAt }}<br>
                        This email was sent automatically by {{ $appName }}.
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
