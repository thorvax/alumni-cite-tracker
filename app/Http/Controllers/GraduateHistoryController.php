<?php

namespace App\Http\Controllers;

use App\Models\Graduate;
use App\Models\SurveyResponse;
use App\Support\GraduateSections;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Graduate Details > Update History: every survey submission of one graduate
 * (first answer and updates), newest first, with what changed each time
 * ("Present Occupation: Data Encoder -> Web Developer") and the full answers.
 */
class GraduateHistoryController extends Controller
{
    private const PER_PAGE = 10;

    public function __invoke(Request $request, Graduate $graduate): Response
    {
        /** @var list<array{id: int, number: int, kind: string, submitted: string|null, changes: list<array{section: string, label: string, before: string, after: string}>, sections: list<array{key: string, title: string, rows: list<array{label: string, value: string}>}>}> $versions */
        $versions = [];
        $previous = null;

        // Oldest first, so each version can be compared with the one before it.
        foreach ($graduate->surveyResponses()->orderBy('submitted_at')->orderBy('id')->get() as $i => $response) {
            $sections = $this->sectionsFor($response);

            $versions[] = [
                'id' => $response->id,
                'number' => $i + 1,
                'kind' => $previous === null ? 'First answer' : 'Update',
                'submitted' => $response->submitted_at?->format('F j, Y g:i A'),
                'changes' => $previous === null ? [] : $this->differences($previous, $sections),
                'sections' => $sections,
            ];

            $previous = $sections;
        }

        $newestFirst = array_reverse($versions);
        $page = max(1, $request->integer('page', 1));
        $lastPage = max(1, (int) ceil(count($newestFirst) / self::PER_PAGE));
        $page = min($page, $lastPage);

        $paginator = new LengthAwarePaginator(
            array_slice($newestFirst, ($page - 1) * self::PER_PAGE, self::PER_PAGE),
            count($newestFirst),
            self::PER_PAGE,
            $page,
            ['path' => $request->url(), 'query' => $request->query()],
        );

        return Inertia::render('admin/graduates/history', [
            'graduate' => [
                'id' => $graduate->id,
                'name' => $graduate->full_name ?: $graduate->last_name,
                'course' => $graduate->courseCode(),
                'year' => $graduate->year_graduated,
                'answered' => $graduate->survey_completed_at?->format('F j, Y'),
                'updated' => $graduate->survey_updated_at?->format('F j, Y'),
            ],
            'versions' => $paginator,
        ]);
    }

    /**
     * The submission's answers, worded like the Graduate Details page.
     *
     * @return list<array{key: string, title: string, rows: list<array{label: string, value: string}>}>
     */
    private function sectionsFor(SurveyResponse $response): array
    {
        // A temporary (never saved) graduate filled from that submission's answers.
        $snapshot = (new Graduate)->forceFill(Graduate::attributesFromAnswers($response->data ?? []));

        return GraduateSections::for($snapshot);
    }

    /**
     * Questions whose answer is different between two versions.
     *
     * @param  list<array{key: string, title: string, rows: list<array{label: string, value: string}>}>  $before
     * @param  list<array{key: string, title: string, rows: list<array{label: string, value: string}>}>  $after
     * @return list<array{section: string, label: string, before: string, after: string}>
     */
    private function differences(array $before, array $after): array
    {
        $old = [];
        foreach ($before as $section) {
            foreach ($section['rows'] as $row) {
                $old[$section['key'].'|'.$row['label']] = $row['value'];
            }
        }

        $changes = [];
        foreach ($after as $section) {
            foreach ($section['rows'] as $row) {
                $was = $old[$section['key'].'|'.$row['label']] ?? '—';

                if ($was !== $row['value']) {
                    $changes[] = ['section' => $section['title'], 'label' => $row['label'], 'before' => $was, 'after' => $row['value']];
                }
            }
        }

        return $changes;
    }
}
