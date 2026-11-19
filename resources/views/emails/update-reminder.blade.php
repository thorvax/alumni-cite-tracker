<!DOCTYPE html>
<html lang="en">
<body style="margin:0;padding:24px;background:#f3f4f6;font-family:Arial,Helvetica,sans-serif;color:#111827;">
    <div style="max-width:520px;margin:0 auto;padding:28px;background:#ffffff;border:1px solid #d1d5db;border-radius:8px;">
        <h1 style="margin:0 0 16px;font-size:18px;">CITE Graduate Tracer Survey</h1>
        <p style="margin:0 0 12px;line-height:1.5;">Hello {{ $name }},</p>
        <p style="margin:0 0 12px;line-height:1.5;">
            Thank you for answering the Graduate Tracer Survey{{ $lastUpdated ? ' (last updated '.$lastUpdated.')' : '' }}.
            Has anything changed since then, such as a new job, a new address or a new contact number?
            Please take a minute to log in and update your answers. It helps the College of Information and Technology Education keep its programs relevant.
        </p>
        <p style="margin:24px 0;">
            <a href="{{ $link }}" style="display:inline-block;padding:12px 22px;background:#111827;color:#ffffff;text-decoration:none;border-radius:6px;font-weight:bold;">Update my answers</a>
        </p>
        <p style="margin:0 0 12px;font-size:13px;line-height:1.5;color:#4b5563;">
            If the button does not work, copy this link into your browser:<br>
            <a href="{{ $link }}" style="color:#1d4ed8;word-break:break-all;">{{ $link }}</a>
        </p>
        <p style="margin:0;font-size:13px;line-height:1.5;color:#4b5563;">After logging in, open My Survey and choose "Update my answers". This link is personal to you.</p>
    </div>
</body>
</html>
