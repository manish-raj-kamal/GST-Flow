<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $appName }} OTP</title>
</head>
<body style="margin:0; padding:24px; background:#f8fafc; color:#0f172a; font-family:'Quicksand', Arial, sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:620px; margin:0 auto; background:#ffffff; border:1px solid #e2e8f0; border-radius:12px;">
        <tr>
            <td style="padding:24px;">
                <p style="margin:0 0 12px; font-size:14px;">Hello,</p>
                <p style="margin:0 0 16px; font-size:14px;">Your {{ $appName }} verification code is:</p>
                <p style="margin:0 0 16px; font-size:28px; font-weight:700; letter-spacing:4px;">{{ $otp }}</p>
                <p style="margin:0 0 8px; font-size:14px;">This code expires in {{ $expiryMinutes }} minutes.</p>
                <p style="margin:0; font-size:12px; color:#475569;">If you did not request this code, you can ignore this email.</p>
            </td>
        </tr>
    </table>
</body>
</html>

