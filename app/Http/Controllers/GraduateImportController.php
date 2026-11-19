<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\Graduate;
use App\Services\InvitationService;
use App\Support\Import\StudentSheetReader;
use App\Support\Import\StudentSheetValidator;
use App\Support\Import\StudentTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Bulk upload of graduates from Excel/CSV, and everything that follows it:
 * emailing personal survey links, resending, the "no email" list and reminders.
 */
class GraduateImportController extends Controller
{
    private const SESSION_KEY = 'graduate_import';

    private const STATUS_FILTERS = ['pending', 'queued', 'sent', 'failed', 'answered'];

    public function __construct(private readonly InvitationService $invitations) {}

    /**
     * Bulk Upload page: upload a file, review it, import.
     */
    public function index(Request $request): Response
    {
        return Inertia::render('admin/graduates/import', [
            'flash' => $request->session()->get('status'),
            'preview' => $request->session()->get(self::SESSION_KEY.'.preview'),
        ]);
    }

    /**
     * Invitations page: email status of everyone uploaded, Send/Resend, and
     * the "No email address" list.
     */
    public function invitations(Request $request): Response|RedirectResponse
    {
        $filter = $request->query('status');
        $filter = is_string($filter) && in_array($filter, self::STATUS_FILTERS, true) ? $filter : null;

        $perPage = $request->integer('per_page', GraduateController::DEFAULT_PER_PAGE);
        if (! in_array($perPage, GraduateController::PER_PAGE_OPTIONS, true)) {
            $perPage = GraduateController::DEFAULT_PER_PAGE;
        }

        // Counted by the database (not by loading everyone into PHP).
        $counts = ['total' => Graduate::query()->invited()->count()];
        foreach ([...self::STATUS_FILTERS, 'no_email'] as $status) {
            $counts[$status] = Graduate::query()->invited()->invitationStatus($status)->count();
        }

        // Email status list (people with an email), filtered by status.
        $recipients = Graduate::query()
            ->invited()
            ->withEmail()
            ->when($filter, fn ($query, string $status) => $query->invitationStatus($status))
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        // "No email address" list: its own page number, so paging one list does not move the other.
        $noEmail = Graduate::query()
            ->invited()
            ->invitationStatus('no_email')
            ->latest('id')
            ->paginate($perPage, ['*'], 'no_email_page')
            ->withQueryString();

        // A page number past the end goes to the last page.
        if ($recipients->currentPage() > $recipients->lastPage() || $noEmail->currentPage() > $noEmail->lastPage()) {
            return to_route('graduates.invitations', [
                ...$request->query(),
                'page' => min($recipients->currentPage(), $recipients->lastPage()),
                'no_email_page' => min($noEmail->currentPage(), $noEmail->lastPage()),
            ]);
        }

        return Inertia::render('admin/graduates/invitations', [
            'flash' => $request->session()->get('status'),
            'counts' => $counts,
            'filter' => $filter,
            'perPage' => $perPage,
            'perPageOptions' => GraduateController::PER_PAGE_OPTIONS,
            'recipients' => $recipients->through(fn (Graduate $g) => $g->toInvitationRow()),
            'noEmail' => $noEmail->through(fn (Graduate $g) => $g->toInvitationRow()),
        ]);
    }

    public function template(): BinaryFileResponse
    {
        $path = tempnam(sys_get_temp_dir(), 'cite-template-').'.xlsx';
        (new StudentTemplate)->write($path);

        return response()->download($path, 'cite-students-template.xlsx')->deleteFileAfterSend();
    }

    /**
     * Reads and checks the file. Nothing is saved until the admin confirms.
     */
    public function preview(Request $request, StudentSheetReader $reader, StudentSheetValidator $validator): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:5120', 'extensions:xlsx,csv'],
        ], [
            'file.required' => 'Choose an Excel (.xlsx) or CSV file.',
            'file.extensions' => 'The file must be an .xlsx or .csv file.',
            'file.max' => 'The file is larger than 5 MB.',
        ]);

        $file = $request->file('file');
        $read = $reader->read($file->getRealPath(), strtolower($file->getClientOriginalExtension()));

        if ($read['error'] !== null) {
            return back()->withErrors(['file' => $read['error']]);
        }

        $result = $validator->validate($read['rows'], $read['columns']);

        $ids = array_column($result['valid'], 'student_id');
        $existing = Graduate::query()->whereIn('student_id', $ids)->pluck('student_id')->all();

        $already = [];
        $fresh = [];

        foreach ($result['valid'] as $row) {
            in_array($row['student_id'], $existing, true) ? $already[] = $row : $fresh[] = $row;
        }

        $request->session()->put(self::SESSION_KEY, [
            'rows' => $result['errors'] === [] ? $fresh : [],
            'preview' => [
                'file' => $file->getClientOriginalName(),
                'total' => count($read['rows']),
                'errors' => $result['errors'],
                'already' => $already,
                'new_count' => count($fresh),
                'with_email' => count(array_filter($fresh, fn (array $r) => filled($r['email']))),
                'without_email' => count(array_filter($fresh, fn (array $r) => blank($r['email']))),
            ],
        ]);

        return to_route('graduates.import');
    }

    /**
     * Saves the new people (skipping IDs that already exist) and queues their emails.
     */
    public function confirm(Request $request): RedirectResponse
    {
        $rows = $request->session()->get(self::SESSION_KEY.'.rows', []);

        if ($rows === []) {
            return to_route('graduates.import')->withErrors(['file' => 'There is nothing to import. Upload a file first.']);
        }

        // Checked again in case someone else imported the same IDs while this preview was open.
        $taken = Graduate::query()->whereIn('student_id', array_column($rows, 'student_id'))->pluck('student_id')->all();

        $toEmail = [];
        $created = 0;

        foreach ($rows as $row) {
            if (in_array($row['student_id'], $taken, true)) {
                continue;
            }

            $graduate = Graduate::create([
                'student_id' => $row['student_id'],
                'full_name' => $row['last_name'],
                'last_name' => $row['last_name'],
                'email' => $row['email'],
                // Optional columns; the survey answers replace them later.
                'course' => $row['course'] ?? null,
                'year_graduated' => $row['year_graduated'] ?? null,
                'invite_token' => Str::random(48),
                'invite_status' => 'pending',
            ]);

            $created++;

            if (filled($row['email'])) {
                $toEmail[] = $graduate;
            }
        }

        $this->invitations->queueMany($toEmail);

        $request->session()->forget(self::SESSION_KEY);

        return to_route('graduates.invitations')->with('status', "Imported {$created} graduate(s). Queued ".count($toEmail).' email(s).');
    }

    public function cancel(Request $request): RedirectResponse
    {
        $request->session()->forget(self::SESSION_KEY);

        return to_route('graduates.import');
    }

    public function resend(Graduate $graduate): RedirectResponse
    {
        abort_if($graduate->invite_token === null || blank($graduate->email) || $graduate->survey_completed_at !== null, 422);

        $this->invitations->queue($graduate);

        return back()->with('status', "Email queued for {$graduate->last_name}.");
    }

    public function resendFailed(): RedirectResponse
    {
        $failed = Graduate::query()->invited()->withEmail()->whereNull('survey_completed_at')->where('invite_status', 'failed')->get();

        $count = $this->invitations->queueMany($failed);

        return back()->with('status', "Queued {$count} email(s) again.");
    }

    /**
     * One person without an email: save the address and send right away.
     */
    public function saveEmail(Request $request, Graduate $graduate): RedirectResponse
    {
        abort_if($graduate->invite_token === null, 404);

        $data = $request->validate(['email' => ['required', 'email', 'max:150']], [
            'email.required' => 'Enter an email address.',
        ]);

        $graduate->forceFill(['email' => Str::lower($data['email'])])->save();
        $this->invitations->queue($graduate);

        return back()->with('status', "Email queued for {$graduate->last_name}.");
    }

    /**
     * The "send all" button: every row that has an address typed in is saved and sent.
     */
    public function saveEmails(Request $request): RedirectResponse
    {
        $request->validate([
            'emails' => ['required', 'array'],
            'emails.*' => ['nullable', 'email', 'max:150'],
        ], [
            'emails.*.email' => 'Enter a valid email address.',
        ]);

        $entered = array_filter((array) $request->input('emails'), fn ($email) => filled($email));

        if ($entered === []) {
            return back()->withErrors(['emails' => 'Type at least one email address first.']);
        }

        $graduates = Graduate::query()->invited()->withoutEmail()->whereIn('id', array_keys($entered))->get();

        foreach ($graduates as $graduate) {
            $graduate->forceFill(['email' => Str::lower((string) $entered[$graduate->id])])->save();
        }

        $count = $this->invitations->queueMany($graduates);

        return back()->with('status', "Saved and queued {$count} email(s).");
    }

    /**
     * Manual reminder: everyone who was emailed and has not answered yet.
     */
    public function remind(): RedirectResponse
    {
        $count = $this->invitations->queueMany($this->invitations->waitingForReminder(), reminder: true);

        return back()->with('status', "Queued {$count} reminder(s).");
    }

    public function updateSchedule(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'enabled' => ['required', 'boolean'],
            'interval_days' => ['required', 'integer', 'between:1,90'],
            'max' => ['required', 'integer', 'between:1,10'],
            'hour' => ['required', 'integer', 'between:0,23'],
        ]);

        AppSetting::set('reminder_enabled', (bool) $data['enabled']);
        AppSetting::set('reminder_interval_days', $data['interval_days']);
        AppSetting::set('reminder_max', $data['max']);
        AppSetting::set('reminder_hour', $data['hour']);

        return back()->with('status', 'Reminder schedule saved.');
    }
}
