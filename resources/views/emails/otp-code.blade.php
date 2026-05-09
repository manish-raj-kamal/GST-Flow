<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $appName }} OTP</title>
</head>
<body style="margin:0; padding:24px; background:#ecfdf3; color:#0f172a; font-family:'Quicksand', Arial, sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:620px; margin:0 auto; background:#ffffff; border:1px solid #d1fae5; border-radius:16px; overflow:hidden;">
        <tr>
            <td style="padding:0; background:linear-gradient(90deg,#f0fdf4 0%,#ffffff 45%,#dcfce7 100%);">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="padding:16px 24px 8px;">
                            <img src="{{ url('/gst-flow-logo.svg') }}" alt="{{ $appName }} logo" width="56" height="52" style="display:block; width:56px; height:auto;">
                        </td>
                        <td style="padding:16px 24px 8px; text-align:right;">
                            <span style="display:inline-block; width:14px; height:14px; border-radius:999px; background:#a7f3d0;"></span>
                            <span style="display:inline-block; width:10px; height:10px; border-radius:999px; background:#34d399; margin-left:6px;"></span>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td style="padding:8px 24px 20px;">
                <p style="margin:0 0 12px; font-size:14px;">Hello,</p>
                <p style="margin:0 0 16px; font-size:14px;">Your {{ $appName }} verification code is:</p>
                <div style="margin:0 0 16px; display:inline-block; padding:12px 16px; border:1px solid #86efac; background:#f0fdf4; border-radius:10px;">
                    <span style="font-size:30px; font-weight:700; letter-spacing:5px; color:#064e3b;">{{ $otp }}</span>
                </div>
                <p style="margin:0 0 8px; font-size:14px;">This code expires in {{ $expiryMinutes }} minutes.</p>
                <p style="margin:0; font-size:12px; color:#475569;">If you did not request this code, you can ignore this email.</p>
            </td>
        </tr>
        <tr>
            <td style="padding:0 24px 18px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="text-align:left;">
                            <span style="display:inline-block; width:22px; height:22px; border-radius:999px; background:#dcfce7;"></span>
                        </td>
                        <td style="text-align:right;">
                            <span style="display:inline-block; width:30px; height:12px; border-radius:999px; background:#bbf7d0;"></span>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>

