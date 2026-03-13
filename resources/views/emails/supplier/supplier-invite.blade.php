<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AF Home Supplier Invite</title>
</head>
<body style="margin:0;padding:0;background:#f8fafc;font-family:Arial,Helvetica,sans-serif;color:#0f172a;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f8fafc;padding:24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px;background:#ffffff;border-radius:20px;overflow:hidden;border:1px solid #e2e8f0;">
                    <tr>
                        <td style="padding:32px 32px 16px;background:linear-gradient(135deg,#ecfeff,#ffffff);">
                            <p style="margin:0 0 8px;font-size:12px;font-weight:700;letter-spacing:0.2em;text-transform:uppercase;color:#0f766e;">Supplier Portal</p>
                            <h1 style="margin:0;font-size:28px;line-height:1.2;color:#0f172a;">You're invited to AF Home</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:24px 32px 32px;">
                            <p style="margin:0 0 16px;font-size:15px;line-height:1.7;color:#334155;">Hello {{ $name }},</p>
                            <p style="margin:0 0 16px;font-size:15px;line-height:1.7;color:#334155;">
                                You have been invited to access the AF Home Supplier Portal for
                                <strong>{{ $supplierName }}</strong>.
                            </p>
                            <p style="margin:0 0 24px;font-size:15px;line-height:1.7;color:#334155;">
                                Click the button below to set your password and activate your supplier account.
                            </p>

                            <p style="margin:0 0 28px;">
                                <a href="{{ $setupUrl }}" style="display:inline-block;background:#0891b2;color:#ffffff;text-decoration:none;font-weight:700;font-size:14px;padding:14px 22px;border-radius:14px;">
                                    Set Up Supplier Account
                                </a>
                            </p>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;background:#f8fafc;border:1px solid #e2e8f0;border-radius:16px;">
                                <tr>
                                    <td style="padding:18px 20px;">
                                        <p style="margin:0 0 8px;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#64748b;">Email</p>
                                        <p style="margin:0 0 14px;font-size:14px;color:#0f172a;">{{ $email }}</p>
                                        <p style="margin:0 0 8px;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#64748b;">Expires</p>
                                        <p style="margin:0;font-size:14px;color:#0f172a;">{{ $expiresAt }}</p>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:24px 0 0;font-size:13px;line-height:1.7;color:#64748b;">
                                If the button does not work, copy and paste this link into your browser:<br>
                                <a href="{{ $setupUrl }}" style="color:#0891b2;text-decoration:none;">{{ $setupUrl }}</a>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
