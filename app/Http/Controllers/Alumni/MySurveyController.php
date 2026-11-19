<?php

namespace App\Http\Controllers\Alumni;

use App\Http\Controllers\Controller;
use App\Models\Graduate;
use App\Support\GraduateSections;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The logged-in graduate's own page: a thank-you plus every answer they gave
 * (read only), worded the same way as the admin Graduate Details page.
 */
class MySurveyController extends Controller
{
    public function __invoke(Request $request): Response
    {
        /** @var Graduate $graduate */
        $graduate = $request->user('graduate');
        $answered = $graduate->survey_completed_at !== null;

        return Inertia::render('alumni/my-survey', [
            'graduate' => [
                'name' => $graduate->full_name ?: $graduate->last_name,
                'answered' => $answered,
                'completed_at' => $graduate->survey_completed_at?->format('M j, Y'),
                'updated_at' => $graduate->survey_updated_at?->format('M j, Y'),
                'can_edit' => ! empty($graduate->survey),
            ],
            'sections' => $answered ? $this->answeredSections($graduate) : [],
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * The same sections as the admin page, keeping only questions with an answer.
     *
     * @return list<array{key: string, title: string, rows: list<array{label: string, value: string}>}>
     */
    private function answeredSections(Graduate $graduate): array
    {
        $sections = [];

        foreach (GraduateSections::for($graduate) as $section) {
            $rows = array_values(array_filter($section['rows'], fn (array $row) => $row['value'] !== '—'));

            if ($rows !== []) {
                $sections[] = ['key' => $section['key'], 'title' => $section['title'], 'rows' => $rows];
            }
        }

        return $sections;
    }
}
