{{-- Plain-text email: nothing here is shown as HTML, so the text is printed as typed. --}}
UCU CITE {{ $isEvent ? 'Event' : 'Announcement' }}: {!! $title !!}

Hello {!! $name !!},

{!! $body !!}
@if ($isEvent)

@if ($eventDate)
When: {{ $eventDate }}
@endif
@if ($eventPlace)
Where: {!! $eventPlace !!}
@endif
@if ($eventLink)
Link: {!! $eventLink !!}
@endif
@endif

{{ $needsLogin ? 'Log in to read it on the site' : 'Read it on the site' }}: {{ $link }}
@if ($needsLogin)
Log in with your Student ID Number and password to open it.
@endif
@if ($activateLink)

Haven't activated your account yet? Use your personal link first to create your password:
{{ $activateLink }}
@endif
