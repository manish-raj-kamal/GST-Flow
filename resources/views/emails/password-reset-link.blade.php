<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $appName }} Password Reset</title>
</head>
<body style="margin:0; padding:24px; background:#f8fafc; color:#0f172a; font-family:'Quicksand', Arial, sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:620px; margin:0 auto; background:#ffffff; border:1px solid #e2e8f0; border-radius:12px;">
        <tr>
            <td style="padding:24px;">
                <p style="margin:0 0 12px; font-size:14px;">Hi {{ $name }},</p>
                <p style="margin:0 0 16px; font-size:14px;">Click the button below to reset your {{ $appName }} password.</p>
                <p style="margin:0 0 18px;">
                    <a href="{{ $url }}" style="display:inline-block; padding:10px 16px; background:#2563eb; color:#ffffff; text-decoration:none; border-radius:8px; font-size:14px; font-weight:700;">Reset Password</a>
                </p>
                <p style="margin:0 0 8px; font-size:14px;">This link expires in {{ $expireMinutes }} minutes.</p>
                <p style="margin:0; font-size:12px; color:#475569;">If you did not request a password reset, no further action is required.</p>
            </td>
        </tr>
    </table>
</body>
</html>

