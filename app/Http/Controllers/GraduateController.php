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
    public function index(Request $request): Response
    {
        $term = $request->string('q')->toString();

        return Inertia::render('admin/graduates/index', [
            'graduates' => Graduate::query()
                ->search($term)
                ->latest('id')
                ->paginate(8)
                ->withQueryString()
                ->through(fn (Graduate $graduate) => $graduate->toRow()),
            'filters' => ['q' => $term],
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
