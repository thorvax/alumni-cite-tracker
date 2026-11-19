<?php

namespace App\Http\Controllers;

use App\Http\Requests\GraduateRequest;
use App\Models\Graduate;
use App\Support\GraduateSections;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GraduateController extends Controller
{
    /** Choices for "Show [n] per page" on the graduates list. */
    public const PER_PAGE_OPTIONS = [5, 10, 25, 50, 100];

    public const DEFAULT_PER_PAGE = 10;

    public function index(Request $request): Response|RedirectResponse
    {
        $term = $request->string('q')->toString();

        // Only the listed sizes are allowed, so nobody can ask for 100000 rows.
        $perPage = $request->integer('per_page', self::DEFAULT_PER_PAGE);
        if (! in_array($perPage, self::PER_PAGE_OPTIONS, true)) {
            $perPage = self::DEFAULT_PER_PAGE;
        }

        $graduates = Graduate::query()
            ->search($term)
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        // A page number past the end (typed in, or after showing more per page) goes to the last page.
        if ($graduates->currentPage() > $graduates->lastPage()) {
            return to_route('graduates.index', [...$request->query(), 'page' => $graduates->lastPage()]);
        }

        return Inertia::render('admin/graduates/index', [
            'graduates' => $graduates->through(fn (Graduate $graduate) => $graduate->toRow()),
            'filters' => ['q' => $term, 'per_page' => $perPage],
            'perPageOptions' => self::PER_PAGE_OPTIONS,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/graduates/create');
    }

    public function store(GraduateRequest $request): RedirectResponse
    {
        $graduate = Graduate::create($request->validated());

        return to_route('graduates.show', $graduate);
    }

    public function show(Graduate $graduate): Response
    {
        return Inertia::render('admin/graduates/show', [
            'graduate' => [
                'id' => $graduate->id,
                'name' => $graduate->full_name,
                'course' => $graduate->courseCode(),
                'year' => $graduate->year_graduated,
            ],
            'sections' => GraduateSections::for($graduate),
        ]);
    }

    public function edit(Graduate $graduate): Response
    {
        return Inertia::render('admin/graduates/edit', [
            'graduate' => [
                'id' => $graduate->id,
                'full_name' => $graduate->full_name,
                'birth_date' => $graduate->birth_date?->format('Y-m-d'),
                'gender' => $graduate->gender,
                'civil_status' => $graduate->civil_status,
                'email' => $graduate->email,
                'contact_number' => $graduate->contact_number,
                'current_address' => $graduate->current_address,
                'region' => $graduate->region,
                'province' => $graduate->province,
                'city' => $graduate->city,
            ],
        ]);
    }

    public function update(GraduateRequest $request, Graduate $graduate): RedirectResponse
    {
        $graduate->update($request->validated());

        return to_route('graduates.show', $graduate);
    }
}
