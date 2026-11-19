<?php

namespace App\Http\Controllers;

use App\Models\Graduate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    /**
     * Survey "major line of business" answers (matched by how they start)
     * grouped into the report's sectors. Anything else counts as Others.
     */
    private const SECTORS = [
        'Information Technology' => 'IT / Software',
        'Education' => 'Education',
        'Financial Intermediation' => 'Business / Finance',
        'Wholesale and Retail Trade' => 'Business / Finance',
        'Real Estate, Renting and Business Activities' => 'Business / Finance',
        'Public Administration and Defense' => 'Government',
    ];

    public function __invoke(Request $request): Response
    {
        $years = Graduate::query()
            ->whereNotNull('year_graduated')
            ->distinct()
            ->orderByDesc('year_graduated')
            ->pluck('year_graduated');

        $year = $request->integer('year') ?: $years->first();

        $base = fn (): Builder => Graduate::query()
            ->when($year, fn (Builder $query) => $query->where('year_graduated', $year));

        $total = $base()->count();
        $employed = $base()->whereIn('employment_status', Graduate::EMPLOYED_STATUSES)->count();
        $unemployed = $base()->where('employment_status', 'Unemployed')->count();
        $further = $base()->where('employment_status', 'Further Studies')->count();

        $percent = fn (int $count): int => $total > 0 ? (int) round($count / $total * 100) : 0;

        $sectors = $base()
            ->whereNotNull('survey')
            ->get()
            ->map(function (Graduate $graduate): ?string {
                $line = data_get($graduate->survey, 'employment-data.business_line');

                return is_string($line) && $line !== '' ? $this->sectorFor($line) : null;
            })
            ->filter()
            ->countBy();

        $counted = $sectors->sum();

        $top = $sectors
            ->except('Others')
            ->sortDesc()
            ->take(4)
            ->map(fn (int $count, string $label) => ['label' => $label, 'percent' => (int) round($count / $counted * 100)])
            ->values();

        if ($sectors->has('Others')) {
            $top->push(['label' => 'Others', 'percent' => (int) round($sectors->get('Others', 0) / $counted * 100)]);
        }

        return Inertia::render('admin/reports', [
            'year' => $year ? (string) $year : '',
            'years' => $years,
            'employment' => [
                'total' => $total,
                'employed' => ['count' => $employed, 'percent' => $percent($employed)],
                'unemployed' => ['count' => $unemployed, 'percent' => $percent($unemployed)],
                'further' => ['count' => $further, 'percent' => $percent($further)],
            ],
            'sectors' => $top,
        ]);
    }

    private function sectorFor(string $line): string
    {
        foreach (self::SECTORS as $prefix => $sector) {
            if (str_starts_with($line, $prefix)) {
                return $sector;
            }
        }

        return 'Others';
    }
}
