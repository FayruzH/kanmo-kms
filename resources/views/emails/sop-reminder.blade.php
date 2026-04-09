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
            <table role="presentation" width="640" cellpadding="0" cellspacing="0" style="max-width:640px;width:100%;background:#ffffff;border:1px solid #dbe2ee;border-radius:14px;overflow:hidden;">
                <tr>
                    <td style="background:linear-gradient(135deg,#f68f2d,#f15a0a);padding:22px 24px;color:#ffffff;">
                        <div style="font-size:22px;font-weight:700;line-height:1.2;">{{ $appName }}</div>
                        <div style="font-size:14px;opacity:0.9;margin-top:4px;">SOP Reminder Notification</div>
                    </td>
                </tr>
                <tr>
                    <td style="padding:24px;">
                        <p style="margin:0 0 14px 0;font-size:16px;">Hello {{ $sop->pic?->name ?? 'PIC' }},</p>
                        <p style="margin:0 0 16px 0;font-size:14px;line-height:1.6;">
                            This is an automated reminder that one of your SOP documents needs attention.
                        </p>

                        <div style="display:inline-block;background:{{ $statusBgColor }};color:{{ $statusTextColor }};padding:7px 12px;border-radius:999px;font-size:12px;font-weight:700;letter-spacing:0.02em;margin-bottom:14px;">
                            {{ strtoupper($statusLabel) }}
                        </div>

                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;border:1px solid #e3e8f2;border-radius:10px;overflow:hidden;margin:4px 0 18px 0;">
                            <tr>
                                <td style="padding:10px 12px;background:#f8fafd;font-size:12px;color:#5f6f86;width:36%;">SOP Code</td>
                                <td style="padding:10px 12px;font-size:14px;font-weight:600;">{{ $sopCode }}</td>
                            </tr>
                            <tr>
                                <td style="padding:10px 12px;background:#f8fafd;border-top:1px solid #e3e8f2;font-size:12px;color:#5f6f86;">Title</td>
                                <td style="padding:10px 12px;border-top:1px solid #e3e8f2;font-size:14px;font-weight:600;">{{ $sop->title }}</td>
                            </tr>
                            <tr>
                                <td style="padding:10px 12px;background:#f8fafd;border-top:1px solid #e3e8f2;font-size:12px;color:#5f6f86;">Division</td>
                                <td style="padding:10px 12px;border-top:1px solid #e3e8f2;font-size:14px;">{{ $sop->category?->name ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td style="padding:10px 12px;background:#f8fafd;border-top:1px solid #e3e8f2;font-size:12px;color:#5f6f86;">Department</td>
                                <td style="padding:10px 12px;border-top:1px solid #e3e8f2;font-size:14px;">{{ $sop->department?->name ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td style="padding:10px 12px;background:#f8fafd;border-top:1px solid #e3e8f2;font-size:12px;color:#5f6f86;">Expiry Date</td>
                                <td style="padding:10px 12px;border-top:1px solid #e3e8f2;font-size:14px;">{{ $expiryDateLabel }}</td>
                            </tr>
                            <tr>
                                <td style="padding:10px 12px;background:#f8fafd;border-top:1px solid #e3e8f2;font-size:12px;color:#5f6f86;">Timeline</td>
                                <td style="padding:10px 12px;border-top:1px solid #e3e8f2;font-size:14px;">{{ $dayNote }}</td>
                            </tr>
                        </table>

                        <a href="{{ $sopUrl }}"
                           style="display:inline-block;background:#f26a21;color:#ffffff;text-decoration:none;padding:10px 16px;border-radius:8px;font-size:14px;font-weight:700;">
                            Open SOP in KMS
                        </a>

                        <p style="margin:18px 0 0 0;font-size:13px;line-height:1.6;color:#5f6f86;">
                            Please review and update this SOP if needed to keep the document lifecycle compliant.
                        </p>
                    </td>
                </tr>
                <tr>
                    <td style="padding:14px 24px;background:#f8fafd;border-top:1px solid #e3e8f2;font-size:12px;color:#7a889d;line-height:1.5;">
                        This email was sent automatically by {{ $appName }}.
                        If you are not the intended recipient, please ignore this message.
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
