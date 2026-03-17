<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Interior Booking Request Received - AF Home</title>
</head>
<body style="margin:0;padding:0;background:#f8fafc;font-family:Arial,Helvetica,sans-serif;color:#0f172a;">
  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f8fafc;padding:28px 12px;">
    <tr>
      <td align="center">
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:620px;">
          <tr>
            <td style="background:#111827;padding:20px 28px;border-radius:16px 16px 0 0;">
              <img
                src="{{ $message->embed(public_path('Image/af_home_logo.png')) }}"
                alt="AF Home"
                width="120"
                height="auto"
                style="display:block;height:auto;border:0;max-width:120px;"
              />
            </td>
          </tr>
          <tr>
            <td style="background:linear-gradient(135deg,#ea580c 0%,#fb923c 100%);padding:34px 28px 28px;">
              <p style="margin:0 0 8px;font-size:12px;font-weight:700;letter-spacing:1.2px;text-transform:uppercase;color:#ffedd5;">Interior Services</p>
              <h1 style="margin:0;font-size:28px;line-height:1.2;color:#ffffff;">Your booking request is pending review</h1>
              <p style="margin:12px 0 0;font-size:14px;line-height:1.6;color:#fff7ed;">
                We received your request and our team is now reviewing the details before confirming the next step.
              </p>
            </td>
          </tr>
          <tr>
            <td style="background:#ffffff;padding:28px;">
              <p style="margin:0 0 14px;font-size:15px;line-height:1.6;color:#334155;">
                Hi <strong>{{ $payload['customer_name'] ?? 'Customer' }}</strong>,
              </p>
              <p style="margin:0 0 20px;font-size:14px;line-height:1.7;color:#475569;">
                Thank you for booking with AF Home Interior Services. Your request has been placed in our active queue and is currently waiting for admin review and schedule confirmation.
              </p>

              <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border:1px solid #e2e8f0;border-radius:14px;overflow:hidden;">
                <tr>
                  <td colspan="2" style="background:#f8fafc;padding:12px 16px;border-bottom:1px solid #e2e8f0;">
                    <span style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:1px;">Request Summary</span>
                  </td>
                </tr>
                <tr>
                  <td style="padding:12px 16px;font-size:12px;color:#94a3b8;width:38%;border-bottom:1px solid #f1f5f9;">Reference</td>
                  <td style="padding:12px 16px;font-size:13px;color:#0f172a;font-weight:700;border-bottom:1px solid #f1f5f9;">{{ $payload['reference'] ?? '-' }}</td>
                </tr>
                <tr>
                  <td style="padding:12px 16px;font-size:12px;color:#94a3b8;background:#fafafa;border-bottom:1px solid #f1f5f9;">Service</td>
                  <td style="padding:12px 16px;font-size:13px;color:#334155;background:#fafafa;border-bottom:1px solid #f1f5f9;">{{ $payload['service_type'] ?? '-' }}</td>
                </tr>
                <tr>
                  <td style="padding:12px 16px;font-size:12px;color:#94a3b8;border-bottom:1px solid #f1f5f9;">Project Type</td>
                  <td style="padding:12px 16px;font-size:13px;color:#334155;border-bottom:1px solid #f1f5f9;">{{ $payload['project_type'] ?? '-' }}</td>
                </tr>
                <tr>
                  <td style="padding:12px 16px;font-size:12px;color:#94a3b8;background:#fafafa;border-bottom:1px solid #f1f5f9;">Preferred Schedule</td>
                  <td style="padding:12px 16px;font-size:13px;color:#334155;background:#fafafa;border-bottom:1px solid #f1f5f9;">
                    {{ $payload['preferred_date'] ?? 'TBD' }}{{ !empty($payload['preferred_time']) ? ' at ' . $payload['preferred_time'] : '' }}
                  </td>
                </tr>
                <tr>
                  <td style="padding:12px 16px;font-size:12px;color:#94a3b8;">Status</td>
                  <td style="padding:12px 16px;">
                    <span style="display:inline-block;border-radius:999px;padding:6px 12px;background:#fff7ed;color:#c2410c;font-size:11px;font-weight:700;letter-spacing:0.5px;">
                      {{ $payload['status_label'] ?? 'Pending Review' }}
                    </span>
                  </td>
                </tr>
              </table>

              <table role="presentation" cellspacing="0" cellpadding="0" style="margin:24px 0 12px;">
                <tr>
                  <td align="center" style="border-radius:999px;background:#ea580c;">
                    <a href="{{ $payload['inbox_url'] ?? '#' }}" style="display:inline-block;padding:13px 22px;font-size:13px;font-weight:700;color:#ffffff;text-decoration:none;">
                      Open Project Inbox
                    </a>
                  </td>
                </tr>
              </table>

              <p style="margin:0;font-size:13px;line-height:1.7;color:#64748b;">
                You will receive another email once an admin posts a schedule confirmation, estimate, design note, or follow-up message to your account.
              </p>
            </td>
          </tr>
          <tr>
            <td style="background:#ffffff;padding:0 28px;">
              <hr style="border:none;border-top:1px solid #f1f5f9;margin:0;">
            </td>
          </tr>
          <tr>
            <td style="background:#ffffff;padding:20px 28px;border-radius:0 0 16px 16px;text-align:center;">
              <p style="margin:0 0 4px;font-size:13px;font-weight:700;color:#111827;">AF Home Interior Services</p>
              <p style="margin:0;font-size:11px;color:#94a3b8;">This is an automated email. Please do not reply directly to this message.</p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
