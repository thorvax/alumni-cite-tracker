<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Services\InvitationService;
use App\Services\UpdateReminderService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The Reminders section (sidebar), one tab per kind of reminder:
 *  - Survey reminders: people emailed a survey link who have not answered yet
 *  - Update reminders: "please update your answers" (automatic + send now)
 * The Reminder List tab is GraduateRemindersController. The save/send buttons
 * post to GraduateImportController and SettingsController.
 */
class RemindersController extends Controller
{
    public function survey(Request $request, InvitationService $invitations): Response
    {
        return Inertia::render('admin/reminders/survey', [
            'reminders' => [
                'enabled' => AppSetting::flag('reminder_enabled', false),
                'interval_days' => (int) AppSetting::value('reminder_interval_days', 7),
                'max' => (int) AppSetting::value('reminder_max', 3),
                'hour' => (int) AppSetting::value('reminder_hour', 8),
                'waiting' => $invitations->waitingForReminder()->count(),
            ],
            'flash' => $request->session()->get('status'),
        ]);
    }

    public function update(Request $request, UpdateReminderService $updates): Response
    {
        return Inertia::render('admin/reminders/update', [
            // Automatic reminders (saved, sent daily by the scheduler).
            'updateReminders' => [
                'enabled' => AppSetting::flag('update_reminder_enabled', false),
                'months' => UpdateReminderService::months(),
                'target' => UpdateReminderService::target(),
                'staleMonths' => UpdateReminderService::staleMonths(),
                'monthOptions' => UpdateReminderService::MONTH_OPTIONS,
                'hour' => (int) AppSetting::value('reminder_hour', 8),
                'due' => $updates->due()->count(),
            ],
            // Manual "Send now": how many each choice would email.
            'manualCounts' => [
                'all' => $updates->audience(UpdateReminderService::TARGET_ALL, 0)->count(),
                'stale' => collect(UpdateReminderService::MONTH_OPTIONS)
                    ->mapWithKeys(fn (int $m) => [$m => $updates->audience(UpdateReminderService::TARGET_STALE, $m)->count()])
                    ->all(),
            ],
            'flash' => $request->session()->get('status'),
        ]);
    }
}
