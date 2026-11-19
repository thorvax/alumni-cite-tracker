<?php

namespace App\Http\Controllers\Alumni;

use App\Http\Controllers\Controller;
use App\Http\Controllers\SurveyController;
use App\Models\Graduate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

/**
 * "Update my answers": loads the logged-in graduate's saved answers into the
 * survey session, so every step opens already filled in. Submitting the survey
 * then updates the same record (see Graduate::fromSurvey).
 */
class EditSurveyController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        /** @var Graduate $graduate */
        $graduate = $request->user('graduate');

        if (empty($graduate->survey)) {
            return to_route('survey.start');
        }

        // Saved answers are keyed by step slug, the same shape the survey keeps in the session.
        $answers = Arr::only($graduate->survey, SurveyController::STEPS);

        // They tick these again: the information is true, and they agree to the
        // Privacy Policy (the consent time saved on submit is then a real, fresh one).
        unset($answers['referral-submit']['certify'], $answers['general-information']['privacy']);

        $request->session()->put('survey', $answers);

        return to_route('survey.show', ['step' => SurveyController::STEPS[0]]);
    }
}
