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

        // They certify again that the updated information is true. (Older answers
        // may still hold the step A "privacy" tick; the agreement is now on the letter page.)
        unset($answers['referral-submit']['certify'], $answers['general-information']['privacy']);

        // Replacing the survey session also clears the old agreement, so the
        // letter page asks them to agree to the Data Privacy Notice again.
        $request->session()->put('survey', $answers);

        return to_route('survey.start');
    }
}
