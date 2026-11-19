<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Services\UpdateReminderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    private const FLAGS = ['allow_registration', 'email_notifications', 'activity_logs'];

    public function show(Request $request): Response
    {
        $user = $request->user('web');

        return Inertia::render('admin/settings', [
            'account' => [
                'name' => $user?->name,
                'email' => $user?->email,
                'position' => $user?->getAttribute('position'),
            ],
            'system' => collect(self::FLAGS)
                ->mapWithKeys(fn (string $flag) => [$flag => AppSetting::flag($flag)])
                ->all(),
        ]);
    }

    /**
     * Manual "Send update reminders now": everyone who answered (even recently),
     * or only those who have not updated in the past N months. Sent at once,
     * whether or not the automatic reminders are on.
     */
    public function sendUpdateReminders(Request $request, UpdateReminderService $updates): RedirectResponse
    {
        $data = $request->validate([
            'target' => ['required', Rule::in(UpdateReminderService::TARGETS)],
            'stale_months' => ['required_if:target,'.UpdateReminderService::TARGET_STALE, 'nullable', 'integer', Rule::in(UpdateReminderService::MONTH_OPTIONS)],
        ], $this->reminderMessages());

        $count = $updates->queueAll($updates->audience($data['target'], (int) ($data['stale_months'] ?? 0)));

        return back()->with('status', "Queued {$count} update reminder(s).");
    }

    /**
     * Automatic update reminders: on/off, remind every N months, and who.
     */
    public function updateReminders(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'enabled' => ['required', 'boolean'],
            'months' => ['required', 'integer', Rule::in(UpdateReminderService::MONTH_OPTIONS)],
            'target' => ['sometimes', 'required', Rule::in(UpdateReminderService::TARGETS)],
            'stale_months' => ['sometimes', 'required', 'integer', Rule::in(UpdateReminderService::MONTH_OPTIONS)],
        ], $this->reminderMessages());

        AppSetting::set('update_reminder_enabled', (bool) $data['enabled']);
        AppSetting::set('update_reminder_months', (int) $data['months']);

        if (isset($data['target'])) {
            AppSetting::set('update_reminder_target', $data['target']);
        }

        if (isset($data['stale_months'])) {
            AppSetting::set('update_reminder_stale_months', (int) $data['stale_months']);
        }

        return back();
    }

    /**
     * @return array<string, string>
     */
    private function reminderMessages(): array
    {
        return [
            'months.in' => 'Choose every 3, 6, 9 or 12 months.',
            'target.in' => 'Choose who to remind.',
            'stale_months.in' => 'Choose 3, 6, 9 or 12 months.',
            'stale_months.required_if' => 'Choose how many months without an update.',
        ];
    }

    public function updateAccount(Request $request): RedirectResponse
    {
        $user = $request->user('web');

        abort_if($user === null, 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->getKey())],
            'position' => ['nullable', 'string', 'max:100'],
        ]);

        $user->forceFill($data)->save();

        return back();
    }

    public function updateSystem(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'allow_registration' => ['required', 'boolean'],
            'email_notifications' => ['required', 'boolean'],
            'activity_logs' => ['required', 'boolean'],
        ]);

        foreach (self::FLAGS as $flag) {
            AppSetting::put($flag, (bool) $data[$flag]);
        }

        return back();
    }
}
