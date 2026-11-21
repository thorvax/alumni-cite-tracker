<?php

namespace App\Http\Controllers;

use App\Http\Requests\AnnouncementRequest;
use App\Models\Announcement;
use App\Models\AnnouncementRecipient;
use App\Models\Graduate;
use App\Services\AnnouncementService;
use App\Support\Announcements\Audience;
use App\Support\Announcements\AudienceResolver;
use App\Support\Import\StudentSheetValidator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin: post events and announcements, email them, and see the history
 * with the email status of each recipient.
 */
class AnnouncementController extends Controller
{
    private const PER_PAGE = 15;

    /** How many not-activated people the preview lists by name. */
    private const NAMES_SHOWN = 50;

    private const DATE_FORMAT = 'M j, Y g:i A';

    public function __construct(
        private readonly AnnouncementService $announcements,
        private readonly AudienceResolver $resolver,
    ) {}

    /**
     * History: everything posted, newest first, with email and read counts.
     */
    public function index(Request $request): Response
    {
        $page = Announcement::query()
            ->withCount([
                'recipients',
                'recipients as sent_count' => fn (Builder $q) => $q->where('email_status', 'sent'),
                'recipients as failed_count' => fn (Builder $q) => $q->where('email_status', 'failed'),
                'recipients as skipped_count' => fn (Builder $q) => $q->where('email_status', 'skipped'),
                'recipients as read_count' => fn (Builder $q) => $q->whereNotNull('read_at'),
            ])
            ->latest('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return Inertia::render('admin/announcements/index', [
            'flash' => $request->session()->get('status'),
            'announcements' => $page->through(fn (Announcement $a) => [
                'id' => $a->id,
                'type' => $a->type,
                'title' => $a->title,
                'audience' => $a->audienceLabel(),
                'visibility' => $a->visibility,
                'status' => $a->status,
                'date' => match ($a->status) {
                    'sent' => $a->published_at?->format(self::DATE_FORMAT),
                    'scheduled' => $a->publish_at?->format(self::DATE_FORMAT),
                    default => $a->updated_at?->format(self::DATE_FORMAT),
                },
                'counts' => [
                    'recipients' => $a->getAttribute('recipients_count'),
                    'sent' => $a->getAttribute('sent_count'),
                    'failed' => $a->getAttribute('failed_count'),
                    'no_email' => $a->getAttribute('skipped_count'),
                    'read' => $a->getAttribute('read_count'),
                ],
            ]),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/announcements/create', $this->formOptions());
    }

    public function store(AnnouncementRequest $request): RedirectResponse
    {
        $announcement = new Announcement($request->announcementAttributes());
        $announcement->forceFill([
            'created_by' => $request->user()?->id,
            'status' => $request->input('action') === 'schedule' ? 'scheduled' : 'draft',
        ]);

        if ($request->input('action') === 'send' && ($error = $this->emptyAudienceError($announcement)) !== null) {
            return back()->withErrors(['audience_type' => $error])->withInput();
        }

        $announcement->save();

        return $this->afterSave($announcement, (string) $request->input('action'));
    }

    public function show(Request $request, Announcement $announcement): Response
    {
        $filter = $request->query('status');
        $filter = is_string($filter) && in_array($filter, AnnouncementRecipient::EMAIL_STATUSES, true) ? $filter : null;

        $counts = ['total' => $announcement->recipients()->count(), 'read' => $announcement->recipients()->whereNotNull('read_at')->count()];
        foreach (AnnouncementRecipient::EMAIL_STATUSES as $status) {
            $counts[$status] = $announcement->recipients()->where('email_status', $status)->count();
        }

        $recipients = $announcement->recipients()
            ->with('graduate')
            ->when($filter, fn (Builder $q, string $status) => $q->where('email_status', $status))
            ->orderBy('id')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('admin/announcements/show', [
            'flash' => $request->session()->get('status'),
            'announcement' => [
                ...$announcement->toFormData(),
                'id' => $announcement->id,
                'status' => $announcement->status,
                'audience' => $announcement->audienceLabel(),
                'event_date' => $announcement->event_starts_at?->format('l, M j, Y g:i A'),
                'show_until_label' => $announcement->show_until?->format('M j, Y'),
                'publish_at_label' => $announcement->publish_at?->format(self::DATE_FORMAT),
                'published_at' => $announcement->published_at?->format(self::DATE_FORMAT),
                'created_by' => $announcement->creator?->name,
            ],
            'counts' => $counts,
            'filter' => $filter,
            'recipients' => $recipients->through(fn (AnnouncementRecipient $r) => [
                'id' => $r->id,
                // Always set: deleting a graduate also deletes their recipient rows.
                'student_id' => $r->graduate->student_id,
                'name' => $r->graduate->last_name ?? $r->graduate->full_name,
                'email' => $r->graduate->email,
                'activated' => $r->graduate->isActivated(),
                'status' => $r->email_status,
                'error' => $r->email_error,
                'emailed_at' => $r->emailed_at?->format(self::DATE_FORMAT),
                'read_at' => $r->read_at?->format(self::DATE_FORMAT),
            ]),
            // Not sent yet: who it would reach today.
            'preview' => $announcement->isSent() ? null : $this->previewData($this->resolver->forAnnouncement($announcement)),
        ]);
    }

    public function edit(Announcement $announcement): Response
    {
        return Inertia::render('admin/announcements/edit', [
            ...$this->formOptions(),
            'announcement' => [
                ...$announcement->toFormData(),
                'id' => $announcement->id,
                'status' => $announcement->status,
            ],
        ]);
    }

    public function update(AnnouncementRequest $request, Announcement $announcement): RedirectResponse
    {
        // Sent: only the title, message and "show until" change, on the site only.
        if ($announcement->isSent()) {
            $announcement->update($request->announcementAttributes());

            return to_route('admin.announcements.show', $announcement)
                ->with('status', 'Saved. The email was already sent, so only the site shows the change.');
        }

        $announcement->fill($request->announcementAttributes());
        $announcement->status = $request->input('action') === 'schedule' ? 'scheduled' : 'draft';

        if ($request->input('action') === 'send' && ($error = $this->emptyAudienceError($announcement)) !== null) {
            return back()->withErrors(['audience_type' => $error])->withInput();
        }

        $announcement->save();

        return $this->afterSave($announcement, (string) $request->input('action'));
    }

    /**
     * Only drafts and scheduled ones (nothing sent yet) can be deleted.
     */
    public function destroy(Announcement $announcement): RedirectResponse
    {
        abort_if($announcement->isSent(), 422, 'A sent announcement cannot be deleted.');

        $announcement->delete();

        return to_route('admin.announcements.index')->with('status', 'Deleted.');
    }

    /**
     * "Send now" for a draft or scheduled announcement (from its detail page).
     */
    public function send(Announcement $announcement): RedirectResponse
    {
        abort_if($announcement->isSent(), 422, 'This announcement was already sent.');

        if (($error = $this->emptyAudienceError($announcement)) !== null) {
            return back()->with('status', $error);
        }

        return $this->afterSave($announcement, 'send');
    }

    /**
     * "This will reach N alumni..." for the form, before saving.
     */
    public function preview(Request $request): JsonResponse
    {
        $data = $request->validate([
            ...AnnouncementRequest::audienceRules(),
            // The preview only reads the IDs; the form checks their format on save.
            'student_ids' => ['nullable', 'string', 'max:40000'],
        ]);

        $audience = $this->resolver->resolve($data['audience_type'], AnnouncementRequest::audienceFilter($data) ?? []);

        return response()->json($this->previewData($audience));
    }

    public function resendFailed(Announcement $announcement): RedirectResponse
    {
        abort_unless($announcement->isSent(), 422);

        $count = $this->announcements->resendFailed($announcement);

        return back()->with('status', "Queued {$count} email(s) again.");
    }

    public function resend(Announcement $announcement, AnnouncementRecipient $recipient): RedirectResponse
    {
        $graduate = $recipient->graduate;

        abort_if(
            ! $announcement->isSent()
            || in_array($recipient->email_status, ['pending', 'queued'], true)
            || $graduate === null || $graduate->is_demo || blank($graduate->email),
            422,
        );

        $this->announcements->queue($recipient);

        return back()->with('status', 'Email queued for '.($graduate->last_name ?? $graduate->full_name).'.');
    }

    private function afterSave(Announcement $announcement, string $action): RedirectResponse
    {
        $message = match ($action) {
            'send' => 'Sent. Queued '.$this->announcements->publish($announcement).' email(s).',
            'schedule' => 'Scheduled for '.$announcement->publish_at?->format(self::DATE_FORMAT).'.',
            default => 'Draft saved.',
        };

        return to_route('admin.announcements.show', $announcement)->with('status', $message);
    }

    /**
     * A "selected" announcement with nobody in its audience would reach no one.
     * (A public one still shows on the public page.)
     */
    private function emptyAudienceError(Announcement $announcement): ?string
    {
        if ($announcement->visibility === 'public' || $this->resolver->forAnnouncement($announcement)->count() > 0) {
            return null;
        }

        return 'Nobody matches this audience, so there is no one to send it to.';
    }

    /**
     * @return array<string, mixed>
     */
    private function previewData(Audience $audience): array
    {
        $notActivated = $audience->notActivated();

        return [
            'reach' => $audience->count(),
            'withoutEmail' => $audience->withoutEmail()->count(),
            'unknownIds' => $audience->unknownIds,
            'withoutYear' => $audience->withoutYear,
            'notActivated' => $notActivated->count(),
            'notActivatedNames' => $notActivated->take(self::NAMES_SHOWN)->map(fn (Graduate $g) => [
                'id' => $g->id,
                'student_id' => $g->student_id,
                'name' => $g->last_name ?? $g->full_name,
            ])->values(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'firstYear' => StudentSheetValidator::FIRST_YEAR,
            'lastYear' => (int) date('Y'),
        ];
    }
}
