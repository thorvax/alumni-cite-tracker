<?php

namespace App\Http\Controllers;

use App\Models\SurveyResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SurveyController extends Controller
{
    /**
     * Step slugs, in order. Each one is also the Inertia page name:
     * resources/js/pages/survey/<slug>.tsx
     */
    public const STEPS = [
        'general-information',
        'educational-background',
        'trainings',
        'employment-data',
        'job-details',
        'first-current-job',
        'referral-submit',
    ];

    // Must match `courseReasons` in resources/js/lib/survey-options.ts
    private const REASON_KEYS = [
        'high_grades_course',
        'good_grades_hs',
        'parents_relatives',
        'peer_influence',
        'role_model',
        'passion',
        'immediate_employment',
        'prestige',
        'course_availability',
        'career_advancement',
        'affordable',
        'compensation',
        'abroad',
        'no_choice',
    ];

    public function start(): RedirectResponse
    {
        return to_route('survey.show', ['step' => self::STEPS[0]]);
    }

    public function show(Request $request, string $step): Response
    {
        return Inertia::render("survey/{$step}", [
            // Restores what the person already typed when they come back to a step.
            'draft' => $request->session()->get($this->sessionKey($step), []),
        ]);
    }

    public function store(Request $request, string $step): RedirectResponse
    {
        $data = $request->validate($this->rules($request, $step), $this->messages());

        $request->session()->put($this->sessionKey($step), $data);

        $next = self::STEPS[array_search($step, self::STEPS, true) + 1] ?? null;

        return $next
            ? to_route('survey.show', ['step' => $next])
            : $this->submit($request);
    }

    private function submit(Request $request): RedirectResponse
    {
        $answers = [];

        foreach (self::STEPS as $slug) {
            $saved = $request->session()->get($this->sessionKey($slug));

            // A step was skipped (for example the session expired): send them back to it.
            if ($saved === null) {
                return to_route('survey.show', ['step' => $slug]);
            }

            $answers[$slug] = $saved;
        }

        SurveyResponse::create([
            'data' => $answers,
            'submitted_at' => now(),
        ]);

        foreach (self::STEPS as $slug) {
            $request->session()->forget($this->sessionKey($slug));
        }

        return redirect('/');
    }

    private function sessionKey(string $step): string
    {
        return "survey.{$step}";
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(Request $request, string $step): array
    {
        $mobile = ['regex:/^(09|\+639)[\d\s-]{9,12}$/'];
        $yesNo = 'in:yes,no';
        $sector = 'in:government,private,entrepreneurial,other';
        $text = ['nullable', 'string', 'max:255'];
        $long = ['nullable', 'string', 'max:2000'];
        $list = ['nullable', 'array'];
        $reasonKeys = 'in:'.implode(',', self::REASON_KEYS);

        return match ($step) {
            'general-information' => [
                'first_name' => ['required', 'string', 'max:100'],
                'middle_name' => ['nullable', 'string', 'max:100'],
                'last_name' => ['required', 'string', 'max:100'],
                'civil_status' => ['required', 'in:Single,Married,Widowed,Separated,Divorced'],
                'sex' => ['required', 'in:male,female'],
                'birth_date' => ['required', 'date', 'before:today'],
                'region' => ['required', 'string', 'max:100'],
                'province' => ['required', 'string', 'max:100'],
                'city' => ['required', 'string', 'max:100'],
                'barangay' => ['required', 'string', 'max:100'],
                'house_no' => ['nullable', 'string', 'max:150'],
                'mobile_primary' => ['required', ...$mobile],
                'mobile_secondary' => ['nullable', ...$mobile],
            ],

            'educational-background' => [
                'course' => ['required', 'string', 'max:150'],
                'year_graduated' => ['required', 'integer', 'between:1966,'.date('Y')],
                'exams' => $list,
                'exams.*' => ['in:let,csc_professional,csc_sub_professional'],
                'exam_date' => ['nullable', 'date', 'before_or_equal:today'],
                'exam_rating' => ['nullable', 'regex:/^\d{1,3}(\.\d{1,2})?%?$/'],
                'reasons_ug' => $list,
                'reasons_ug.*' => [$reasonKeys],
                'reasons_grad' => $list,
                'reasons_grad.*' => [$reasonKeys],
            ],

            'trainings' => [
                'training_title' => $text,
                'training_duration' => $text,
                'training_institution' => $text,
                'pursue_reasons' => $list,
                'pursue_reasons.*' => ['in:promotion,professional_development,other'],
                'pursue_other' => $text,
            ],

            'employment-data' => $this->employmentRules($request, $sector, $text, $list),

            'job-details' => [
                'business_line' => ['required', 'string', 'max:150'],
                'work_line' => ['required', 'string', 'max:150'],
                'place_of_work' => ['required', 'in:local,abroad'],
                'first_job' => ['required', $yesNo],
            ],

            'first-current-job' => $this->jobRules($request, $yesNo, $sector, $text, $long, $list),

            'referral-submit' => [
                'classmates' => $long,
                'certify' => ['accepted'],
            ],

            default => abort(404),
        };
    }

    /**
     * @param  array<int, string>  $text
     * @param  array<int, string>  $list
     * @return array<string, mixed>
     */
    private function employmentRules(Request $request, string $sector, array $text, array $list): array
    {
        $employed = Rule::requiredIf(fn () => $request->input('employed') === 'yes');

        return [
            'employed' => ['required', 'in:yes,no,never'],
            'not_employed_reasons' => $list,
            'not_employed_reasons.*' => ['in:advance_study,family_concern,health,no_experience,no_opportunity,did_not_look,other'],
            'not_employed_other' => $text,
            'employment_status' => [$employed, 'nullable', 'in:regular,temporary,casual,contractual,self_employed'],
            'employment_sector' => [$employed, 'nullable', $sector],
            'employment_sector_other' => $text,
            'self_employed_skills' => [$employed, 'nullable', 'string', 'max:255'],
            'present_occupation' => [$employed, 'nullable', 'string', 'max:255'],
        ];
    }

    /**
     * The first-job block is required when "first job" was answered Yes on the
     * previous step, and the current-job block when it was answered No.
     *
     * @param  array<int, string>  $text
     * @param  array<int, string>  $long
     * @param  array<int, string>  $list
     * @return array<string, mixed>
     */
    private function jobRules(Request $request, string $yesNo, string $sector, array $text, array $long, array $list): array
    {
        $isFirstJob = $request->session()->get($this->sessionKey('job-details').'.first_job');

        $forFirst = Rule::requiredIf($isFirstJob === 'yes');
        $forCurrent = Rule::requiredIf($isFirstJob === 'no');
        $jobReasons = ['in:salaries,career_challenge,special_skills,proximity,other'];

        return [
            'first_related' => [$forFirst, 'nullable', $yesNo],
            'first_reasons' => $list,
            'first_reasons.*' => $jobReasons,
            'first_reason_other' => $text,
            'first_time_to_land' => [$forFirst, 'nullable', 'string', 'max:150'],
            'first_level' => [$forFirst, 'nullable', 'string', 'max:150'],
            'first_sector' => [$forFirst, 'nullable', $sector],
            'first_sector_other' => $text,
            'first_how_found' => [$forFirst, 'nullable', 'string', 'max:150'],
            'first_earning' => [$forFirst, 'nullable', 'string', 'max:150'],
            'curriculum_relevant' => [$forFirst, 'nullable', $yesNo],
            'competencies' => $list,
            'competencies.*' => ['in:communication,human_relations,entrepreneurial,it,problem_solving,critical_thinking,other'],
            'competencies_other' => $text,
            'curriculum_suggestions' => $long,

            'current_related' => [$forCurrent, 'nullable', $yesNo],
            'current_reasons' => $list,
            'current_reasons.*' => $jobReasons,
            'current_reason_other' => $text,
            'current_stay' => [$forCurrent, 'nullable', 'string', 'max:150'],
            'current_level' => [$forCurrent, 'nullable', 'string', 'max:150'],
            'current_sector' => [$forCurrent, 'nullable', $sector],
            'current_sector_other' => $text,
            'current_earning' => [$forCurrent, 'nullable', 'string', 'max:150'],

            'help_needed' => $long,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function messages(): array
    {
        return [
            'mobile_primary.regex' => 'Enter a valid mobile number, like 09xx-xxx-xxxx.',
            'mobile_secondary.regex' => 'Enter a valid mobile number, like 09xx-xxx-xxxx.',
            'exam_rating.regex' => 'Enter a rating like 82 or 82.00%.',
            'certify.accepted' => 'Please certify that your information is true and correct.',
        ];
    }
}
