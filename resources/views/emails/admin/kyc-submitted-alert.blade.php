<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>New KYC Verification Request - AF Home</title>
</head>
<body style="margin:0;padding:0;background:#f8fafc;font-family:Arial,Helvetica,sans-serif;color:#0f172a;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f8fafc;padding:28px 12px;">
    <tr>
        <td align="center">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:620px;">
                <tr>
                    <td style="background:#111827;padding:20px 28px;border-radius:16px 16px 0 0;">
                        <img src="{{ $message->embed(public_path('Image/af_home_logo.png')) }}" alt="AF Home" width="120" style="display:block;height:auto;border:0;max-width:120px;">
                    </td>
                </tr>
                <tr>
                    <td style="background:linear-gradient(135deg,#d97706 0%,#f59e0b 100%);padding:34px 28px 28px;">
                        <p style="margin:0 0 8px;font-size:12px;font-weight:700;letter-spacing:1.2px;text-transform:uppercase;color:#fef3c7;">Admin Alert</p>
                        <h1 style="margin:0;font-size:28px;line-height:1.2;color:#ffffff;">New KYC verification request received</h1>
                        <p style="margin:12px 0 0;font-size:14px;line-height:1.6;color:#fff7ed;">A member has submitted documents that need review in the admin KYC queue.</p>
                    </td>
                </tr>
                <tr>
                    <td style="background:#ffffff;padding:28px;">
                        <p style="margin:0 0 14px;font-size:15px;line-height:1.6;color:#334155;">Hi <strong>{{ $payload['recipient_name'] ?? 'Admin' }}</strong>,</p>
                        <p style="margin:0 0 20px;font-size:14px;line-height:1.7;color:#475569;">
                            <strong>{{ $payload['customer_name'] ?? 'A member' }}</strong> submitted a KYC verification request.
                            Please review the documents and take action in the admin portal.
                        </p>

                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border:1px solid #e2e8f0;border-radius:14px;overflow:hidden;">
                            <tr>
                                <td colspan="2" style="background:#f8fafc;padding:12px 16px;border-bottom:1px solid #e2e8f0;">
                                    <span style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:1px;">Verification Summary</span>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding:12px 16px;font-size:12px;color:#94a3b8;width:38%;border-bottom:1px solid #f1f5f9;">Member</td>
                                <td style="padding:12px 16px;font-size:13px;color:#0f172a;font-weight:700;border-bottom:1px solid #f1f5f9;">{{ $payload['customer_name'] ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td style="padding:12px 16px;font-size:12px;color:#94a3b8;background:#fafafa;border-bottom:1px solid #f1f5f9;">Email</td>
                                <td style="padding:12px 16px;font-size:13px;color:#334155;background:#fafafa;border-bottom:1px solid #f1f5f9;">{{ $payload['customer_email'] ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td style="padding:12px 16px;font-size:12px;color:#94a3b8;border-bottom:1px solid #f1f5f9;">Reference</td>
                                <td style="padding:12px 16px;font-size:13px;color:#334155;border-bottom:1px solid #f1f5f9;">{{ $payload['reference_no'] ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td style="padding:12px 16px;font-size:12px;color:#94a3b8;background:#fafafa;">Submitted At</td>
                                <td style="padding:12px 16px;font-size:13px;color:#334155;background:#fafafa;">{{ $payload['submitted_at'] ?? '-' }}</td>
                            </tr>
                        </table>

                        <table role="presentation" cellspacing="0" cellpadding="0" style="margin:24px 0 12px;">
                            <tr>
                                <td align="center" style="border-radius:999px;background:#d97706;">
                                    <a href="{{ $payload['review_url'] ?? '#' }}" style="display:inline-block;padding:13px 22px;font-size:13px;font-weight:700;color:#ffffff;text-decoration:none;">Review KYC Queue</a>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
