<?php

namespace App\Http\Controllers;

use App\Models\Graduate;
use App\Services\UpdateReminderService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Graduates > Update Status: everyone who answered the survey, whether their
 * data is up to date (answered/updated within the reminder period) and what
 * changed in their last update. Paginated, with a status filter.
 */
class GraduateUpdatesController extends Controller
{
    public const FILTERS = ['up_to_date', 'needs_update', 'changed', 'no_changes', 'never_updated'];

    public function __invoke(Request $request): Response|RedirectResponse
    {
        $filter = $request->query('status');
        $filter = is_string($filter) && in_array($filter, self::FILTERS, true) ? $filter : null;

        $perPage = $request->integer('per_page', GraduateController::DEFAULT_PER_PAGE);
        if (! in_array($perPage, GraduateController::PER_PAGE_OPTIONS, true)) {
            $perPage = GraduateController::DEFAULT_PER_PAGE;
        }

        $page = $this->filtered($filter)
            ->orderByRaw('coalesce(survey_updated_at, survey_completed_at) desc')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        // A page number past the end goes to the last page.
        if ($page->currentPage() > $page->lastPage()) {
            return to_route('graduates.updates', [...$request->query(), 'page' => $page->lastPage()]);
        }

        $cutoff = now()->subMonths(UpdateReminderService::staleMonths());

        return Inertia::render('admin/graduates/updates', [
            'graduates' => $page->through(function (Graduate $graduate) use ($cutoff) {
                $last = $graduate->survey_updated_at ?? $graduate->survey_completed_at;

                return [
                    'id' => $graduate->id,
                    'name' => $graduate->full_name ?: $graduate->last_name,
                    'course' => $graduate->courseCode(),
                    'year' => $graduate->year_graduated,
                    'answered' => $graduate->survey_completed_at?->format('M j, Y'),
                    'updated' => $graduate->survey_updated_at?->format('M j, Y'),
                    'reminded' => $graduate->last_update_reminder_at?->format('M j, Y'),
                    'status' => $last !== null && $last->greaterThan($cutoff) ? 'up_to_date' : 'needs_update',
                    // null = never updated (or updated before changes were recorded); [] = no changes.
                    'changes' => $graduate->survey_updated_at !== null ? $graduate->survey_changes : null,
                ];
            }),
            'filters' => ['status' => $filter ?? '', 'per_page' => $perPage],
            'perPageOptions' => GraduateController::PER_PAGE_OPTIONS,
            'months' => UpdateReminderService::staleMonths(),
            'counts' => [
                'all' => $this->filtered(null)->count(),
                ...collect(self::FILTERS)->mapWithKeys(fn (string $f) => [$f => $this->filtered($f)->count()])->all(),
            ],
        ]);
    }

    /**
     * Everyone who answered, narrowed by one status filter.
     *
     * @return Builder<Graduate>
     */
    private function filtered(?string $filter): Builder
    {
        $query = Graduate::query()->whereNotNull('survey_completed_at');

        return match ($filter) {
            'up_to_date' => UpdateReminderService::upToDate($query),
            'needs_update' => UpdateReminderService::needsUpdate($query),
            'changed' => $query->whereNotNull('survey_updated_at')->whereJsonLength('survey_changes', '>', 0),
            'no_changes' => $query->whereNotNull('survey_updated_at')->whereJsonLength('survey_changes', 0),
            'never_updated' => $query->whereNull('survey_updated_at'),
            default => $query,
        };
    }
}
