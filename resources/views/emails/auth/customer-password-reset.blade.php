<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Your Password</title>
</head>
<body style="margin:0;padding:0;background:#fff7ed;font-family:Arial,sans-serif;color:#1f2937;">
    <div style="max-width:640px;margin:0 auto;padding:32px 16px;">
        <div style="overflow:hidden;border:1px solid #fed7aa;border-radius:24px;background:#ffffff;box-shadow:0 12px 30px rgba(249,115,22,0.12);">
            <div style="padding:32px;border-bottom:1px solid #fed7aa;background:linear-gradient(135deg,#fff7ed,#ffffff 55%,#ffedd5);">
                <p style="margin:0 0 8px;font-size:12px;font-weight:700;letter-spacing:0.2em;text-transform:uppercase;color:#ea580c;">AF Home</p>
                <h1 style="margin:0;font-size:28px;line-height:1.2;color:#111827;">Reset your password</h1>
                <p style="margin:16px 0 0;font-size:15px;line-height:1.7;color:#4b5563;">
                    Hello {{ $name }}, we received a request to reset your AF Home account password.
                </p>
            </div>

            <div style="padding:32px;">
                <p style="margin:0 0 20px;font-size:15px;line-height:1.7;color:#4b5563;">
                    Use the button below to choose a new password. This reset link will expire on <strong>{{ $expiresAt }}</strong>.
                </p>

                <p style="margin:0 0 28px;">
                    <a href="{{ $resetUrl }}" style="display:inline-block;padding:14px 22px;border-radius:16px;background:#f97316;color:#ffffff;text-decoration:none;font-size:14px;font-weight:700;">
                        Reset Password
                    </a>
                </p>

                <p style="margin:0 0 12px;font-size:13px;line-height:1.6;color:#6b7280;">
                    If the button does not work, copy and paste this link into your browser:
                </p>
                <p style="margin:0;font-size:13px;line-height:1.8;word-break:break-all;color:#ea580c;">
                    <a href="{{ $resetUrl }}" style="color:#ea580c;text-decoration:underline;">{{ $resetUrl }}</a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>
