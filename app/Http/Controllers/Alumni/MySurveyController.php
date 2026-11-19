<?php

namespace App\Http\Controllers\Alumni;

use App\Http\Controllers\Controller;
use App\Models\Graduate;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The logged-in graduate's own page: a thank-you plus a summary of what they answered.
 */
class MySurveyController extends Controller
{
    public function __invoke(Request $request): Response
    {
        /** @var Graduate $graduate */
        $graduate = $request->user('graduate');

        return Inertia::render('alumni/my-survey', [
            'graduate' => [
                'name' => $graduate->full_name ?: $graduate->last_name,
                'student_id' => $graduate->student_id,
                'email' => $graduate->email,
                'contact_number' => $graduate->contact_number,
                'address' => $graduate->current_address,
                'course' => $graduate->course,
                'year_graduated' => $graduate->year_graduated,
                'employment_status' => $graduate->employment_status,
                'answered' => $graduate->survey_completed_at !== null,
                'completed_at' => $graduate->survey_completed_at?->format('M j, Y'),
                'updated_at' => $graduate->survey_updated_at?->format('M j, Y'),
                'can_edit' => ! empty($graduate->survey),
            ],
            'status' => $request->session()->get('status'),
        ]);
    }
}
