<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your IT HelpDesk Account</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f6f9; margin: 0; padding: 32px 16px; }
        .card { background: #ffffff; border-radius: 8px; max-width: 520px; margin: 0 auto; padding: 40px; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
        h1 { color: #1a2b45; font-size: 22px; margin: 0 0 8px; }
        p { color: #4a5568; font-size: 15px; line-height: 1.6; margin: 0 0 16px; }
        .cred-box { background: #f0f4ff; border: 1px solid #c7d7ff; border-radius: 6px; padding: 20px 24px; margin: 24px 0; }
        .cred-label { font-size: 11px; font-weight: bold; color: #6b7280; text-transform: uppercase; letter-spacing: .05em; margin-bottom: 2px; }
        .cred-value { font-size: 16px; font-weight: 600; color: #1e3a5f; margin-bottom: 16px; word-break: break-all; }
        .cred-value:last-child { margin-bottom: 0; }
        .notice { background: #fffbeb; border: 1px solid #fcd34d; border-radius: 6px; padding: 14px 18px; font-size: 13px; color: #92400e; margin-top: 24px; }
        .footer { text-align: center; color: #9ca3af; font-size: 12px; margin-top: 32px; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Welcome to IT HelpDesk</h1>
        <p>Hi <strong>{{ $fullName }}</strong>,</p>
        <p>An administrator has created an account for you on the IT HelpDesk system. Your temporary login credentials are below.</p>

        <div class="cred-box">
            <div class="cred-label">Login Email</div>
            <div class="cred-value">{{ $email }}</div>

            <div class="cred-label">Temporary Password</div>
            <div class="cred-value">{{ $temporaryPassword }}</div>
        </div>

        <div class="notice">
            You will be required to set a new password immediately after your first login. Your temporary password will stop working once you do.
        </div>

        <p style="margin-top:24px;">If you have any questions, contact your IT administrator.</p>
    </div>
    <div class="footer">IT HelpDesk System &mdash; This is an automated message, please do not reply.</div>
</body>
</html>
