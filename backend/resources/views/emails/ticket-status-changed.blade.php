<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket Status Updated</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f6f9; margin: 0; padding: 32px 16px; }
        .card { background: #ffffff; border-radius: 8px; max-width: 520px; margin: 0 auto; padding: 40px; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
        h1 { color: #1a2b45; font-size: 22px; margin: 0 0 8px; }
        p { color: #4a5568; font-size: 15px; line-height: 1.6; margin: 0 0 16px; }
        .info-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 20px 24px; margin: 24px 0; }
        .row { display: flex; justify-content: space-between; align-items: flex-start; padding: 8px 0; border-bottom: 1px solid #e2e8f0; }
        .row:last-child { border-bottom: none; padding-bottom: 0; }
        .row-label { font-size: 12px; font-weight: bold; color: #6b7280; text-transform: uppercase; letter-spacing: .05em; min-width: 110px; padding-top: 2px; }
        .row-value { font-size: 15px; color: #1e3a5f; font-weight: 500; text-align: right; flex: 1; }
        .status-old { color: #92400e; background: #fef3c7; padding: 2px 10px; border-radius: 12px; font-size: 13px; display: inline-block; }
        .status-new { color: #065f46; background: #d1fae5; padding: 2px 10px; border-radius: 12px; font-size: 13px; display: inline-block; }
        .arrow { color: #9ca3af; font-size: 18px; margin: 0 6px; }
        .footer { text-align: center; color: #9ca3af; font-size: 12px; margin-top: 32px; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Ticket Status Updated</h1>
        <p>Hi <strong>{{ $recipientName }}</strong>,</p>
        <p>A ticket you are associated with has had its status updated.</p>

        <div class="info-box">
            <div class="row">
                <span class="row-label">Ticket</span>
                <span class="row-value">{{ $ticketRef }}</span>
            </div>
            <div class="row">
                <span class="row-label">Title</span>
                <span class="row-value">{{ $ticketTitle }}</span>
            </div>
            <div class="row">
                <span class="row-label">Changed By</span>
                <span class="row-value">{{ $changedBy }}</span>
            </div>
            <div class="row">
                <span class="row-label">Status</span>
                <span class="row-value">
                    <span class="status-old">{{ $oldStatus }}</span>
                    <span class="arrow">→</span>
                    <span class="status-new">{{ $newStatus }}</span>
                </span>
            </div>
        </div>

        <p>Log in to the IT HelpDesk to view the full ticket details.</p>
    </div>
    <div class="footer">IT HelpDesk System &mdash; This is an automated message, please do not reply.</div>
</body>
</html>
