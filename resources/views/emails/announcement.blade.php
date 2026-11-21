<!DOCTYPE html>
<html lang="en">
<body style="margin:0;padding:24px;background:#f3f4f6;font-family:Arial,Helvetica,sans-serif;color:#111827;">
    <div style="max-width:520px;margin:0 auto;padding:28px;background:#ffffff;border:1px solid #d1d5db;border-radius:8px;">
        <p style="margin:0 0 6px;font-size:12px;font-weight:bold;letter-spacing:0.04em;text-transform:uppercase;color:#4b5563;">
            UCU CITE {{ $isEvent ? 'Event' : 'Announcement' }}
        </p>
        <h1 style="margin:0 0 16px;font-size:18px;">{{ $title }}</h1>
        <p style="margin:0 0 12px;line-height:1.5;">Hello {{ $name }},</p>
        {{-- The message is plain text: escaped first, then line breaks kept. --}}
        <p style="margin:0 0 16px;line-height:1.5;">{!! nl2br(e($body)) !!}</p>

        @if ($isEvent)
            <table role="presentation" style="margin:0 0 16px;font-size:14px;line-height:1.5;border-collapse:collapse;">
                @if ($eventDate)
                    <tr><td style="padding:2px 12px 2px 0;color:#4b5563;">When</td><td style="padding:2px 0;font-weight:bold;">{{ $eventDate }}</td></tr>
                @endif
                @if ($eventPlace)
                    <tr><td style="padding:2px 12px 2px 0;color:#4b5563;">Where</td><td style="padding:2px 0;font-weight:bold;">{{ $eventPlace }}</td></tr>
                @endif
                @if ($eventLink)
                    <tr><td style="padding:2px 12px 2px 0;color:#4b5563;">Link</td><td style="padding:2px 0;"><a href="{{ $eventLink }}" style="color:#1d4ed8;word-break:break-all;">{{ $eventLink }}</a></td></tr>
                @endif
            </table>
        @endif

        <p style="margin:24px 0;">
            <a href="{{ $link }}" style="display:inline-block;padding:12px 22px;background:#111827;color:#ffffff;text-decoration:none;border-radius:6px;font-weight:bold;">
                {{ $needsLogin ? 'Log in to read it on the site' : 'Read it on the site' }}
            </a>
        </p>
        <p style="margin:0 0 12px;font-size:13px;line-height:1.5;color:#4b5563;">
            @if ($needsLogin)
                Log in with your Student ID Number and password to open it.
            @endif
            If the button does not work, copy this link into your browser:<br>
            <a href="{{ $link }}" style="color:#1d4ed8;word-break:break-all;">{{ $link }}</a>
        </p>
        @if ($activateLink)
            <p style="margin:0;font-size:13px;line-height:1.5;color:#4b5563;">
                Haven't activated your account yet? Use your personal link first to create your password:<br>
                <a href="{{ $activateLink }}" style="color:#1d4ed8;word-break:break-all;">{{ $activateLink }}</a>
            </p>
        @endif
    </div>
</body>
</html>
