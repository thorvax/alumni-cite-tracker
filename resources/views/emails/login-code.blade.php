<!DOCTYPE html>
<html lang="en">
<body style="margin:0;padding:24px;background:#f3f4f6;font-family:Arial,Helvetica,sans-serif;color:#111827;">
    <div style="max-width:520px;margin:0 auto;padding:28px;background:#ffffff;border:1px solid #d1d5db;border-radius:8px;">
        <h1 style="margin:0 0 16px;font-size:18px;">CITE Tracker</h1>
        <p style="margin:0 0 12px;line-height:1.5;">Hello {{ $name }},</p>
        <p style="margin:0 0 12px;line-height:1.5;">
            @if ($reset)
                Use this code to reset your CITE Tracker password:
            @else
                Use this code to activate your CITE Tracker account:
            @endif
        </p>
        <p style="margin:20px 0;font-size:30px;font-weight:bold;letter-spacing:8px;">{{ $code }}</p>
        <p style="margin:0 0 12px;font-size:13px;line-height:1.5;color:#4b5563;">The code expires in {{ $minutes }} minutes and works only once.</p>
        <p style="margin:0;font-size:13px;line-height:1.5;color:#4b5563;">If you did not ask for this code, you can ignore this email. Never share this code with anyone.</p>
    </div>
</body>
</html>
