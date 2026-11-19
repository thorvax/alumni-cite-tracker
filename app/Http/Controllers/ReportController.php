<?php

namespace App\Http\Controllers;

use App\Models\Graduate;
use App\Support\Reports\GraduateReport;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Admin Reports page and its CSV export. The numbers come from GraduateReport;
 * both use the same Course and Year filters (?course=BSIT&year=2023).
 */
class ReportController extends Controller
{
    public function index(Request $request): Response
    {
        [$course, $year] = $this->filters($request);
        $report = new GraduateReport($course, $year);

        return Inertia::render('admin/reports', [
            'filters' => [
                'course' => $course !== null ? Graduate::COURSE_CODES[$course] ?? $course : '',
                'year' => $year !== null ? (string) $year : '',
            ],
            'options' => [
                'courses' => array_values(Graduate::COURSE_CODES),
                'years' => $this->years(),
            ],
            'status' => $report->employmentStatus(),
            'byCourse' => $report->employmentByCourse(),
            'byYear' => $report->employmentByYear(),
            'related' => $report->jobRelated(),
            'timeToLand' => $report->timeToLand(),
            'sector' => $report->sectorAndPlace(),
            'earning' => $report->firstJobEarning(),
            'howFound' => $report->howFound(),
            'competencies' => $report->competencies(),
            'curriculum' => $report->curriculum(),
            'notEmployed' => $report->notEmployedReasons(),
            'helpNeeded' => $report->helpNeeded(),
            'courseReasons' => $report->courseReasons(),
            'funnel' => $report->responseFunnel(),
        ]);
    }

    /**
     * The same numbers as a CSV file (opens in Excel). Counts only, no names.
     */
    public function export(Request $request): StreamedResponse
    {
        [$course, $year] = $this->filters($request);
        $report = new GraduateReport($course, $year);

        $rows = [];
        $add = function (string $report, string $group, array $items) use (&$rows) {
            foreach ($items as $item) {
                $rows[] = [$report, $group, $item['label'], $item['count'], $item['percent'].'%'];
            }
        };

        $status = $report->employmentStatus();
        $add('Employment status', 'All', $status['items']);
        $rows[] = ['Employment status', 'All', 'Employment rate', $status['rate']['count'], $status['rate']['percent'].'%'];

        foreach ($report->employmentByCourse() as $row) {
            $add('Employment by course', $row['label'], $row['items']);
        }

        foreach ($report->employmentByYear() as $row) {
            $rows[] = ['Employment by year graduated', (string) $row['year'], 'Employed', $row['employed'], $row['percent'].'%'];
        }

        $related = $report->jobRelated();
        $add('Job related to course', 'All', $related['overall']['items']);
        foreach ($related['courses'] as $row) {
            $add('Job related to course', $row['label'], $row['items']);
        }

        $add('Time to land first job', 'All', $report->timeToLand()['items']);

        $sector = $report->sectorAndPlace();
        $add('Sector of present job', 'All', $sector['sector']['items']);
        $add('Place of work', 'All', $sector['place']['items']);
        $add('First job earning', 'All', $report->firstJobEarning()['items']);
        $add('How they found their first job', 'All', $report->howFound()['items']);
        $add('Competencies used on the job (can pick more than one)', 'All', $report->competencies()['items']);
        $add('Curriculum relevant to first job', 'All', $report->curriculum()['relevant']['items']);
        $add('Reasons for not being employed (can pick more than one)', 'All', $report->notEmployedReasons()['items']);
        $add('Why they chose the course (can pick more than one)', 'All', $report->courseReasons()['items']);
        // Written answers (suggestions, help needed) are left out on purpose:
        // people may have typed personal details in them.

        $funnel = $report->responseFunnel();
        $add('Survey progress', 'Uploaded graduates', $funnel['stages']);
        // Response rate: Count = answered, Percent = answered / uploaded in that group.
        foreach (['byCourse' => 'Response rate by course', 'byYear' => 'Response rate by year graduated'] as $key => $title) {
            foreach ($funnel[$key] as $group) {
                $rows[] = [$title, $group['label'], "Answered of {$group['uploaded']} uploaded", $group['answered'], $group['percent'].'%'];
            }
        }

        $name = 'cite-report'
            .($course !== null ? '-'.(Graduate::COURSE_CODES[$course] ?? 'course') : '')
            .($year !== null ? '-'.$year : '')
            .'.csv';

        return response()->streamDownload(function () use ($rows, $course, $year) {
            $out = fopen('php://output', 'w');

            if ($out === false) {
                return;
            }

            fwrite($out, "\xEF\xBB\xBF"); // lets Excel read the file as UTF-8
            fputcsv($out, ['CITE Tracker report', 'Course: '.($course ?? 'All'), 'Year graduated: '.($year ?? 'All'), 'Generated: '.now()->format('Y-m-d H:i')]);
            fputcsv($out, ['Report', 'Group', 'Label', 'Count', 'Percent']);

            foreach ($rows as $row) {
                fputcsv($out, $row);
            }

            fclose($out);
        }, $name, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Course comes in as a code (ACT, BLIS, BSIT) and year as a number; anything else means "All".
     *
     * @return array{0: string|null, 1: int|null}
     */
    private function filters(Request $request): array
    {
        $code = strtoupper($request->string('course')->toString());
        $course = array_search($code, Graduate::COURSE_CODES, true);

        $year = $request->integer('year');

        return [
            is_string($course) ? $course : null,
            in_array($year, $this->years(), true) ? $year : null,
        ];
    }

    /**
     * @return list<int>
     */
    private function years(): array
    {
        return array_values(Graduate::query()
            ->whereNotNull('year_graduated')
            ->distinct()
            ->orderByDesc('year_graduated')
            ->pluck('year_graduated')
            ->map(fn ($year) => (int) $year)
            ->all());
    }
}
