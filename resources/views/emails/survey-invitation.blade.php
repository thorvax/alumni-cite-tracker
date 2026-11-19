<!DOCTYPE html>
<html lang="en">
<body style="margin:0;padding:24px;background:#f3f4f6;font-family:Arial,Helvetica,sans-serif;color:#111827;">
    <div style="max-width:520px;margin:0 auto;padding:28px;background:#ffffff;border:1px solid #d1d5db;border-radius:8px;">
        <h1 style="margin:0 0 16px;font-size:18px;">CITE Graduate Tracer Survey</h1>
        <p style="margin:0 0 12px;line-height:1.5;">Hello {{ $name }},</p>
        @if ($reminder)
            <p style="margin:0 0 12px;line-height:1.5;">This is a friendly reminder: we have not received your answers to the Graduate Tracer Survey yet.</p>
        @else
            <p style="margin:0 0 12px;line-height:1.5;">The College of Information and Technology Education, Urdaneta City University, would like to know how you are doing since graduation. Please take a few minutes to answer our Graduate Tracer Survey.</p>
        @endif
        <p style="margin:24px 0;">
            <a href="{{ $link }}" style="display:inline-block;padding:12px 22px;background:#111827;color:#ffffff;text-decoration:none;border-radius:6px;font-weight:bold;">Answer the survey</a>
        </p>
        <p style="margin:0 0 12px;font-size:13px;line-height:1.5;color:#4b5563;">
            If the button does not work, copy this link into your browser:<br>
            <a href="{{ $link }}" style="color:#1d4ed8;word-break:break-all;">{{ $link }}</a>
        </p>
        <p style="margin:0;font-size:13px;line-height:1.5;color:#4b5563;">This link is personal to you. Thank you for your participation.</p>
    </div>
</body>
</html>
