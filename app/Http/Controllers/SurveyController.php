<?php

namespace App\Http\Controllers;

use App\Models\Graduate;
use App\Models\SurveyResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\RequiredIf;
use Inertia\Inertia;
use Inertia\Response;

class SurveyController extends Controller
{
    /**
     * Step slugs, in order. Each one is also the Inertia page name:
     * resources/js/pages/survey/<slug>.tsx
     *
     * People who are not employed skip "first-current-job".
     */
    public const STEPS = [
        'general-information',
        'educational-background',
        'trainings',
        'employment-data',
        'first-current-job',
        'need-help',
        'referral-submit',
    ];

    // Must match `courseReasons` in resources/js/lib/survey-options.ts
    private const REASON_KEYS = [
        'high_grades_course', 'good_grades_hs', 'parents_relatives', 'peer_influence',
        'role_model', 'passion', 'immediate_employment', 'prestige',
        'course_availability', 'career_advancement', 'affordable', 'compensation',
        'abroad', 'no_choice',
    ];

    /** Employment Data answers that only apply to people who are employed. */
    private const EMPLOYED_ONLY = [
        'employment_status', 'employment_sector', 'employment_sector_other',
        'self_employed_skills', 'present_occupation', 'business_line',
        'work_line', 'work_line_other', 'place_of_work', 'first_job',
    ];

    private const NOT_EMPLOYED_ONLY = ['not_employed_reasons', 'not_employed_other'];

    private const FIRST_JOB_ONLY = [
        'first_related', 'first_reasons', 'first_reason_other',
        'first_time_to_land', 'first_time_to_land_other', 'first_level', 'first_level_other',
        'first_sector', 'first_sector_other', 'first_how_found', 'first_how_found_other',
        'first_earning', 'curriculum_relevant', 'competencies', 'competencies_other',
        'curriculum_suggestions',
    ];

    private const CURRENT_JOB_ONLY = [
        'current_related', 'current_reasons', 'current_reason_other', 'current_stay',
        'current_level', 'current_sector', 'current_sector_other', 'current_earning',
    ];

    public function start(): RedirectResponse
    {
        return to_route('survey.show', ['step' => self::STEPS[0]]);
    }

    public function show(Request $request, string $step): Response|RedirectResponse
    {
        $redirect = $this->guard($request, $step);

        if ($redirect !== null) {
            return $redirect;
        }

        return Inertia::render("survey/{$step}", [
            // Restores what the person already typed when they come back to a step.
            'draft' => $this->draft($request, $step),
            // Set when a graduate is logged in (shows their Student ID Number on step A).
            'invite' => $step === 'general-information'
                ? $this->graduate($request)?->only('student_id')
                : null,
            // Lets the pages hide the job step and pick the right job form.
            'employed' => $this->answer($request, 'employment-data', 'employed'),
            'firstJob' => $this->answer($request, 'employment-data', 'first_job'),
        ]);
    }

    public function store(Request $request, string $step): RedirectResponse
    {
        $redirect = $this->guard($request, $step);

        if ($redirect !== null) {
            return $redirect;
        }

        $data = $request->validate($this->rules($request, $step), $this->messages());
        $data = $this->prune($request, $step, $data);

        $request->session()->put($this->sessionKey($step), $data);

        $next = $this->nextStep($step, $data);

        return $next !== null
            ? to_route('survey.show', ['step' => $next])
            : $this->submit($request);
    }

    /**
     * The job form needs the answers from Employment Data, and only applies to people who are employed.
     */
    private function guard(Request $request, string $step): ?RedirectResponse
    {
        if ($step !== 'first-current-job') {
            return null;
        }

        $employed = $this->answer($request, 'employment-data', 'employed');

        if ($employed === null || ($employed === 'yes' && $this->answer($request, 'employment-data', 'first_job') === null)) {
            return to_route('survey.show', ['step' => 'employment-data']);
        }

        return $employed === 'yes' ? null : to_route('survey.show', ['step' => 'need-help']);
    }

    /**
     * @param  array<string, mixed>  $data  The answers just saved for this step.
     */
    private function nextStep(string $step, array $data): ?string
    {
        if ($step === 'employment-data' && ($data['employed'] ?? null) !== 'yes') {
            return 'need-help';
        }

        $position = array_search($step, self::STEPS, true);

        return $position === false ? null : (self::STEPS[$position + 1] ?? null);
    }

    private function submit(Request $request): RedirectResponse
    {
        // The routes already require the alumni login; this is a safety net so
        // an answer is never saved without belonging to an uploaded graduate.
        $graduate = $this->graduate($request);

        if ($graduate === null) {
            return to_route('alumni.login');
        }

        $employed = $this->answer($request, 'employment-data', 'employed');
        $answers = [];

        foreach (self::STEPS as $slug) {
            if ($slug === 'first-current-job' && $employed !== 'yes') {
                continue;
            }

            $saved = $request->session()->get($this->sessionKey($slug));

            // A step was skipped (for example the session expired): send them back to it.
            if ($saved === null) {
                return to_route('survey.show', ['step' => $slug]);
            }

            $answers[$slug] = $saved;
        }

        $response = SurveyResponse::create([
            'data' => $answers,
            'submitted_at' => now(),
        ]);

        // Updates the graduate's own record (the one the upload created), so
        // answering again never adds a second row.
        $saved = Graduate::fromSurvey($answers, $response, $graduate);

        // Step A required "I agree to the Privacy Policy". Record when the
        // answers (the personal data) were actually given to us.
        $saved->forceFill(['privacy_consented_at' => now()])->save();

        foreach (self::STEPS as $slug) {
            $request->session()->forget($this->sessionKey($slug));
        }

        return to_route('alumni.my-survey')->with('status', 'Thank you! Your answers have been saved.');
    }

    /**
     * What the person already typed, or the logged-in graduate's details on a first visit to step A.
     *
     * @return array<string, mixed>
     */
    private function draft(Request $request, string $step): array
    {
        $saved = (array) $request->session()->get($this->sessionKey($step), []);
        $graduate = $this->graduate($request);

        $known = match (true) {
            $graduate === null => [],
            $step === 'general-information' => ['last_name' => $graduate->last_name, 'email' => $graduate->email],
            // Course and Year from the bulk upload (if the admin filled them in).
            $step === 'educational-background' => [
                'course' => $graduate->course,
                'year_graduated' => $graduate->year_graduated !== null ? (string) $graduate->year_graduated : null,
            ],
            default => [],
        };

        return $saved + array_filter($known, fn ($value) => filled($value));
    }

    /** The logged-in graduate (alumni login), or null for the open survey. */
    private function graduate(Request $request): ?Graduate
    {
        $graduate = $request->user('graduate');

        return $graduate instanceof Graduate ? $graduate : null;
    }

    private function sessionKey(string $step): string
    {
        return "survey.{$step}";
    }

    private function answer(Request $request, string $step, string $key): ?string
    {
        $value = $request->session()->get($this->sessionKey($step).".{$key}");

        return is_string($value) ? $value : null;
    }

    /**
     * Removes answers from a branch the person did not take (for example the
     * "not employed" reasons after switching to Yes).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function prune(Request $request, string $step, array $data): array
    {
        if ($step === 'employment-data') {
            $data = Arr::except($data, ($data['employed'] ?? null) === 'yes' ? self::NOT_EMPLOYED_ONLY : self::EMPLOYED_ONLY);
            $data = $this->dropUnlessOther($data, 'employment_sector', 'employment_sector_other');
            $data = $this->dropUnlessOther($data, 'work_line', 'work_line_other', 'Other');
            $data = $this->dropUnlessListHasOther($data, 'not_employed_reasons', 'not_employed_other');
        }

        if ($step === 'first-current-job') {
            if ($this->answer($request, 'employment-data', 'first_job') === 'yes') {
                $data = Arr::except($data, self::CURRENT_JOB_ONLY);
                $data = Arr::except($data, ($data['first_related'] ?? null) === 'yes' ? ['curriculum_suggestions'] : ['competencies', 'competencies_other']);

                foreach (['first_time_to_land', 'first_level', 'first_sector', 'first_how_found'] as $key) {
                    $data = $this->dropUnlessOther($data, $key, "{$key}_other");
                }

                $data = $this->dropUnlessListHasOther($data, 'first_reasons', 'first_reason_other');
                $data = $this->dropUnlessListHasOther($data, 'competencies', 'competencies_other');
            } else {
                $data = Arr::except($data, self::FIRST_JOB_ONLY);
                $data = $this->dropUnlessOther($data, 'current_sector', 'current_sector_other');
                $data = $this->dropUnlessListHasOther($data, 'current_reasons', 'current_reason_other');
            }
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function dropUnlessOther(array $data, string $key, string $otherKey, string $otherValue = 'other'): array
    {
        return ($data[$key] ?? null) === $otherValue ? $data : Arr::except($data, [$otherKey]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function dropUnlessListHasOther(array $data, string $key, string $otherKey): array
    {
        return in_array('other', (array) ($data[$key] ?? []), true) ? $data : Arr::except($data, [$otherKey]);
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(Request $request, string $step): array
    {
        $mobile = ['regex:/^(09|\+639)[\d\s-]{9,12}$/'];
        $text = ['nullable', 'string', 'max:255'];
        $long = ['nullable', 'string', 'max:2000'];
        $list = ['nullable', 'array'];
        $reasonKeys = 'in:'.implode(',', self::REASON_KEYS);

        return match ($step) {
            'general-information' => [
                'first_name' => ['required', 'string', 'max:100'],
                'middle_name' => ['nullable', 'string', 'max:100'],
                'last_name' => ['required', 'string', 'max:100'],
                'email' => ['required', 'email', 'max:150'],
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
                'privacy' => ['accepted'],
            ],

            'educational-background' => [
                'course' => ['required', 'string', 'max:150'],
                'year_graduated' => ['required', 'integer', 'between:1966,'.date('Y')],
                'exam' => ['nullable', 'in:let,csc_professional,csc_sub_professional'],
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

            'employment-data' => $this->employmentRules($request),

            'first-current-job' => $this->jobRules($request),

            'need-help' => [
                'help_needed' => ['required', 'string', 'max:2000'],
            ],

            'referral-submit' => [
                'classmates' => $long,
                'certify' => ['accepted'],
            ],

            default => abort(404),
        };
    }

    /**
     * Yes shows the employed questions; No and Never Employed show the reasons.
     *
     * @return array<string, mixed>
     */
    private function employmentRules(Request $request): array
    {
        $employed = $request->input('employed');
        $isEmployed = $employed === 'yes';
        $notEmployed = in_array($employed, ['no', 'never'], true);

        $whenEmployed = Rule::requiredIf($isEmployed);
        $text = ['nullable', 'string', 'max:255'];

        return [
            'employed' => ['required', 'in:yes,no,never'],

            'not_employed_reasons' => [Rule::requiredIf($notEmployed), 'nullable', 'array'],
            'not_employed_reasons.*' => ['in:advance_study,family_concern,health,no_experience,no_opportunity,did_not_look,other'],
            'not_employed_other' => [$this->requiredWhenListHas($notEmployed, $request, 'not_employed_reasons'), ...$text],

            'employment_status' => [$whenEmployed, 'nullable', 'in:regular,temporary,casual,contractual,self_employed'],
            'employment_sector' => [$whenEmployed, 'nullable', 'in:government,private,entrepreneurial,other'],
            'employment_sector_other' => [$this->requiredWhenOther($isEmployed, $request, 'employment_sector'), ...$text],
            'self_employed_skills' => [$whenEmployed, ...$text],
            'present_occupation' => [$whenEmployed, ...$text],
            'business_line' => [$whenEmployed, 'nullable', 'string', 'max:200'],
            'work_line' => [$whenEmployed, 'nullable', 'string', 'max:150'],
            'work_line_other' => [$this->requiredWhenOther($isEmployed, $request, 'work_line', 'Other'), ...$text],
            'place_of_work' => [$whenEmployed, 'nullable', 'in:local,abroad'],
            'first_job' => [$whenEmployed, 'nullable', 'in:yes,no'],
        ];
    }

    /**
     * People who answered Yes to "first job?" fill the first-job form; the rest fill the current/new-job form.
     *
     * @return array<string, mixed>
     */
    private function jobRules(Request $request): array
    {
        $firstJob = $this->answer($request, 'employment-data', 'first_job');
        $forFirst = $firstJob === 'yes';
        $forCurrent = $firstJob === 'no';
        $related = $request->input('first_related');

        $first = Rule::requiredIf($forFirst);
        $current = Rule::requiredIf($forCurrent);
        $yesNo = 'in:yes,no';
        $text = ['nullable', 'string', 'max:255'];
        $short = ['nullable', 'string', 'max:150'];
        $jobReasons = ['in:salaries,career_challenge,special_skills,proximity,other'];

        return [
            'first_related' => [$first, 'nullable', $yesNo],
            'first_reasons' => ['nullable', 'array'],
            'first_reasons.*' => $jobReasons,
            'first_reason_other' => [$this->requiredWhenListHas($forFirst, $request, 'first_reasons'), ...$text],
            'first_time_to_land' => [$first, ...$short],
            'first_time_to_land_other' => [$this->requiredWhenOther($forFirst, $request, 'first_time_to_land'), ...$short],
            'first_level' => [$first, ...$short],
            'first_level_other' => [$this->requiredWhenOther($forFirst, $request, 'first_level'), ...$short],
            'first_sector' => [$first, 'nullable', 'in:government,private,entrepreneurial,other'],
            'first_sector_other' => [$this->requiredWhenOther($forFirst, $request, 'first_sector'), ...$text],
            'first_how_found' => [$first, ...$short],
            'first_how_found_other' => [$this->requiredWhenOther($forFirst, $request, 'first_how_found'), ...$short],
            'first_earning' => [$first, ...$short],
            'curriculum_relevant' => [$first, 'nullable', $yesNo],
            'competencies' => [Rule::requiredIf($forFirst && $related === 'yes'), 'nullable', 'array'],
            'competencies.*' => ['in:communication,human_relations,entrepreneurial,it,problem_solving,critical_thinking,other'],
            'competencies_other' => [$this->requiredWhenListHas($forFirst && $related === 'yes', $request, 'competencies'), ...$text],
            'curriculum_suggestions' => [Rule::requiredIf($forFirst && $related === 'no'), 'nullable', 'string', 'max:2000'],

            'current_related' => [$current, 'nullable', $yesNo],
            'current_reasons' => ['nullable', 'array'],
            'current_reasons.*' => $jobReasons,
            'current_reason_other' => [$this->requiredWhenListHas($forCurrent, $request, 'current_reasons'), ...$text],
            'current_stay' => [$current, ...$short],
            'current_level' => [$current, ...$short],
            'current_sector' => [$current, 'nullable', 'in:government,private,entrepreneurial,other'],
            'current_sector_other' => [$this->requiredWhenOther($forCurrent, $request, 'current_sector'), ...$text],
            'current_earning' => [$current, ...$short],
        ];
    }

    private function requiredWhenOther(bool $applies, Request $request, string $field, string $otherValue = 'other'): RequiredIf
    {
        return Rule::requiredIf($applies && $request->input($field) === $otherValue);
    }

    private function requiredWhenListHas(bool $applies, Request $request, string $field): RequiredIf
    {
        return Rule::requiredIf($applies && in_array('other', (array) $request->input($field, []), true));
    }

    /**
     * @return array<string, string>
     */
    private function messages(): array
    {
        return [
            '*_other.required' => 'Please specify.',
            'email.required' => 'Enter your email address.',
            'email.email' => 'Enter a valid email address, like juan@gmail.com.',
            'mobile_primary.regex' => 'Enter a valid mobile number, like 09xx-xxx-xxxx.',
            'mobile_secondary.regex' => 'Enter a valid mobile number, like 09xx-xxx-xxxx.',
            'exam_rating.regex' => 'Enter a rating like 82 or 82.00%.',
            'not_employed_reasons.required' => 'Select at least one reason.',
            'competencies.required' => 'Select at least one competency.',
            'curriculum_suggestions.required' => 'Please list your suggestions.',
            'certify.accepted' => 'Please certify that your information is true and correct.',
            'privacy.accepted' => 'Please confirm that you have read and agree to the Privacy Policy.',
        ];
    }
}
