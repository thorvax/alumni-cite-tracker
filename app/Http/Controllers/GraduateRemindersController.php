<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\Graduate;
use App\Services\UpdateReminderService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Graduates > Reminder List: who is getting "please update your answers" emails.
 *  - due:    who the next automatic run will email (saved settings)
 *  - manual: who "Send Update Reminders Now" would email for a chosen group
 *  - sent:   everyone who has been sent an update reminder, newest first
 */
class GraduateRemindersController extends Controller
{
    public const LISTS = ['due', 'manual', 'sent'];

    public function __invoke(Request $request, UpdateReminderService $updates): Response|RedirectResponse
    {
        $list = $request->query('list');
        $list = is_string($list) && in_array($list, self::LISTS, true) ? $list : 'due';

        // The manual group (same choices as the "Send Update Reminders Now" card).
        $target = $request->query('target') === UpdateReminderService::TARGET_ALL
            ? UpdateReminderService::TARGET_ALL
            : UpdateReminderService::TARGET_STALE;
        $staleMonths = $request->integer('stale_months', UpdateReminderService::staleMonths());
        if (! in_array($staleMonths, UpdateReminderService::MONTH_OPTIONS, true)) {
            $staleMonths = UpdateReminderService::staleMonths();
        }

        $perPage = $request->integer('per_page', GraduateController::DEFAULT_PER_PAGE);
        if (! in_array($perPage, GraduateController::PER_PAGE_OPTIONS, true)) {
            $perPage = GraduateController::DEFAULT_PER_PAGE;
        }

        $lists = [
            'due' => fn (): Builder => $updates->due(),
            'manual' => fn (): Builder => $updates->audience($target, $staleMonths),
            'sent' => fn (): Builder => Graduate::query()->whereNotNull('last_update_reminder_at'),
        ];

        $query = $lists[$list]();
        $query = $list === 'sent'
            ? $query->orderByDesc('last_update_reminder_at')
            : $query->orderBy('full_name');

        $page = $query->orderBy('id')->paginate($perPage)->withQueryString();

        // A page number past the end goes to the last page.
        if ($page->currentPage() > $page->lastPage()) {
            return to_route('graduates.reminders', [...$request->query(), 'page' => $page->lastPage()]);
        }

        return Inertia::render('admin/graduates/reminders', [
            'graduates' => $page->through(fn (Graduate $graduate) => [
                'id' => $graduate->id,
                'name' => $graduate->full_name ?: $graduate->last_name,
                'course' => $graduate->courseCode(),
                'year' => $graduate->year_graduated,
                'email' => $graduate->email,
                'lastAnswer' => ($graduate->survey_updated_at ?? $graduate->survey_completed_at)?->format('M j, Y'),
                'reminded' => $graduate->last_update_reminder_at?->format('M j, Y g:i A'),
                // Not activated: the email also includes their activation link.
                'activated' => $graduate->isActivated(),
            ]),
            'filters' => [
                'list' => $list,
                'target' => $target,
                'stale_months' => $staleMonths,
                'per_page' => $perPage,
            ],
            'counts' => collect($lists)->map(fn ($query) => $query()->count())->all(),
            'automatic' => [
                'enabled' => AppSetting::flag('update_reminder_enabled', false),
                'months' => UpdateReminderService::months(),
                'target' => UpdateReminderService::target(),
                'staleMonths' => UpdateReminderService::staleMonths(),
            ],
            'monthOptions' => UpdateReminderService::MONTH_OPTIONS,
            'perPageOptions' => GraduateController::PER_PAGE_OPTIONS,
        ]);
    }
}
