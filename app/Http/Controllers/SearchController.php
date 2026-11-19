<?php

namespace App\Http\Controllers;

use App\Models\Graduate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SearchController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $term = $request->string('q')->toString();
        $year = $request->integer('year');
        $status = $request->string('status')->toString();

        $results = Graduate::query()
            ->search($term)
            ->when($year > 0, fn (Builder $query) => $query->where('year_graduated', $year))
            ->when(
                in_array($status, Graduate::STATUSES, true),
                fn (Builder $query) => $query->where('employment_status', $status),
            )
            ->orderBy('full_name')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Graduate $graduate) => $graduate->toRow());

        return Inertia::render('admin/search', [
            'results' => $results,
            'filters' => [
                'q' => $term,
                'year' => $year > 0 ? (string) $year : '',
                'status' => $status,
            ],
            'years' => Graduate::query()
                ->whereNotNull('year_graduated')
                ->distinct()
                ->orderByDesc('year_graduated')
                ->pluck('year_graduated'),
            'statuses' => Graduate::STATUSES,
        ]);
    }
}
