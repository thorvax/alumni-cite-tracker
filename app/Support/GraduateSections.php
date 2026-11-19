<?php

namespace App\Support;

use App\Models\Graduate;

/**
 * Turns a graduate (and the survey answers saved with it) into the sections
 * shown on the Graduate Details page.
 */
class GraduateSections
{
    private const EXAMS = [
        'let' => 'Licensure Examination for Teachers',
        'csc_professional' => 'CSC Professional',
        'csc_sub_professional' => 'CSC Sub-Professional',
    ];

    private const COURSE_REASONS = [
        'high_grades_course' => 'High grades in the course or subject area(s) related to the course',
        'good_grades_hs' => 'Good grades in high school',
        'parents_relatives' => 'Influence of parents or relatives',
        'peer_influence' => 'Peer Influence',
        'role_model' => 'Inspired by a role model',
        'passion' => 'Strong passion for the profession',
        'immediate_employment' => 'Prospect for immediate employment',
        'prestige' => 'Status or prestige of the profession',
        'course_availability' => 'Availability of course offering in chosen institution',
        'career_advancement' => 'Prospect of career advancement',
        'affordable' => 'Affordable for the family',
        'compensation' => 'Prospect of attractive compensation',
        'abroad' => 'Opportunity for employment abroad',
        'no_choice' => 'No particular choice or no better idea',
    ];

    private const YES_NO = ['yes' => 'Yes', 'no' => 'No', 'never' => 'Never Employed'];

    private const NOT_EMPLOYED = [
        'advance_study' => 'Advance or further study',
        'family_concern' => 'Family concern and decided not to find a job',
        'health' => 'Health-related reason(s)',
        'no_experience' => 'Lack of work experience',
        'no_opportunity' => 'No job opportunity',
        'did_not_look' => 'Did not look for a job',
    ];

    private const EMPLOYMENT_TYPES = [
        'regular' => 'Regular or Permanent',
        'temporary' => 'Temporary',
        'casual' => 'Casual',
        'contractual' => 'Contractual',
        'self_employed' => 'Self-employed',
    ];

    private const SECTORS = [
        'government' => 'Government',
        'private' => 'Private',
        'entrepreneurial' => 'Entrepreneurial / Freelance Work',
    ];

    private const PLACES = ['local' => 'Local', 'abroad' => 'Abroad'];

    private const JOB_REASONS = [
        'salaries' => 'Salaries & benefits',
        'career_challenge' => 'Career challenge',
        'special_skills' => 'Related to special skills',
        'proximity' => 'Proximity to residence',
    ];

    private const COMPETENCIES = [
        'communication' => 'Communication skills',
        'human_relations' => 'Human Relations skills',
        'entrepreneurial' => 'Entrepreneurial skills',
        'it' => 'Information Technology skills',
        'problem_solving' => 'Problem-solving skills',
        'critical_thinking' => 'Critical Thinking skills',
    ];

    private const PURSUE = [
        'promotion' => 'For promotion',
        'professional_development' => 'For professional development',
    ];

    /**
     * @return list<array{key: string, title: string, rows: list<array{label: string, value: string}>}>
     */
    public static function for(Graduate $graduate): array
    {
        /** @var array<string, array<string, mixed>> $survey */
        $survey = $graduate->survey ?? [];
        $education = $survey['educational-background'] ?? [];
        $trainings = $survey['trainings'] ?? [];
        $employment = $survey['employment-data'] ?? [];
        $jobs = $survey['first-current-job'] ?? [];
        $help = $survey['need-help'] ?? [];
        $referral = $survey['referral-submit'] ?? [];

        return [
            self::section('personal', 'Personal Information', [
                'Full Name' => $graduate->full_name,
                'Date of Birth' => $graduate->birth_date?->format('F j, Y'),
                'Gender' => $graduate->gender,
                'Civil Status' => $graduate->civil_status,
                'Email Address' => $graduate->email,
                'Contact Number' => $graduate->contact_number,
                'Current Address' => $graduate->current_address,
                'Region' => $graduate->region,
                'Province' => $graduate->province,
                'City/Municipality' => $graduate->city,
            ]),

            self::section('education', 'Educational Background', [
                'Course' => $graduate->course,
                'Year Graduated' => $graduate->year_graduated,
                'Professional Examination' => self::label($education, 'exam', self::EXAMS),
                'Date Taken' => self::text($education, 'exam_date'),
                'Rating' => self::text($education, 'exam_rating'),
                'Reasons for Taking the Course (UG)' => self::labels($education, 'reasons_ug', self::COURSE_REASONS),
                'Reasons for Taking the Course (Graduate)' => self::labels($education, 'reasons_grad', self::COURSE_REASONS),
            ]),

            self::section('employment', 'Employment Information', [
                'Employment Status' => $graduate->employment_status,
                'Presently Employed' => self::label($employment, 'employed', self::YES_NO),
                'Type of Employment' => self::label($employment, 'employment_status', self::EMPLOYMENT_TYPES),
                'Employment Sector' => self::sector($employment, 'employment_sector', 'employment_sector_other'),
                'Present Occupation' => self::text($employment, 'present_occupation'),
                'Major Line of Business' => self::text($employment, 'business_line'),
                'Specific Line of Work' => self::choice($employment, 'work_line', 'work_line_other', 'Other'),
                'Place of Work' => self::label($employment, 'place_of_work', self::PLACES),
                'Reasons for Not Being Employed' => self::labels($employment, 'not_employed_reasons', self::NOT_EMPLOYED, 'not_employed_other'),
            ]),

            self::section('career', 'Career Development', [
                'First Job After College' => self::label($employment, 'first_job', self::YES_NO),
                'First Job Related to Course' => self::label($jobs, 'first_related', self::YES_NO),
                'Reasons for Accepting the First Job' => self::labels($jobs, 'first_reasons', self::JOB_REASONS, 'first_reason_other'),
                'Time to Land the First Job' => self::choice($jobs, 'first_time_to_land', 'first_time_to_land_other'),
                'First Job Level Position' => self::choice($jobs, 'first_level', 'first_level_other'),
                'First Job Sector' => self::sector($jobs, 'first_sector', 'first_sector_other'),
                'How the First Job Was Found' => self::choice($jobs, 'first_how_found', 'first_how_found_other'),
                'Initial Gross Monthly Earning' => self::text($jobs, 'first_earning'),
                'Curriculum Relevant to First Job' => self::label($jobs, 'curriculum_relevant', self::YES_NO),
                'Current Job Related to Course' => self::label($jobs, 'current_related', self::YES_NO),
                'Reasons for Changing Job' => self::labels($jobs, 'current_reasons', self::JOB_REASONS, 'current_reason_other'),
                'Time Stayed in First Job' => self::text($jobs, 'current_stay'),
                'Current Job Level Position' => self::text($jobs, 'current_level'),
                'Current Job Sector' => self::sector($jobs, 'current_sector', 'current_sector_other'),
                'Current Gross Monthly Earning' => self::text($jobs, 'current_earning'),
            ]),

            self::section('studies', 'Further Studies', [
                'Title of Training or Advance Study' => self::text($trainings, 'training_title'),
                'Duration and Credits Earned' => self::text($trainings, 'training_duration'),
                'Training Institution' => self::text($trainings, 'training_institution'),
                'Reason for Pursuing' => self::labels($trainings, 'pursue_reasons', self::PURSUE, 'pursue_other'),
            ]),

            self::section('skills', 'Skills and Competencies', [
                'Competencies Found Useful' => self::labels($jobs, 'competencies', self::COMPETENCIES, 'competencies_other'),
                'Skills Applied (Self-employed)' => self::text($employment, 'self_employed_skills'),
            ]),

            self::section('feedback', 'Feedback and Suggestions', [
                'Curriculum Suggestions' => self::text($jobs, 'curriculum_suggestions'),
                'How the College Can Help' => self::text($help, 'help_needed'),
                'Classmates Referred' => self::text($referral, 'classmates'),
            ]),
        ];
    }

    /**
     * @param  array<string, mixed>  $values  Label => value (null or empty shows a dash).
     * @return array{key: string, title: string, rows: list<array{label: string, value: string}>}
     */
    private static function section(string $key, string $title, array $values): array
    {
        $rows = [];

        foreach ($values as $label => $value) {
            $text = is_scalar($value) ? trim((string) $value) : '';

            $rows[] = ['label' => $label, 'value' => $text !== '' ? $text : '—'];
        }

        return ['key' => $key, 'title' => $title, 'rows' => $rows];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function text(array $data, string $key): string
    {
        $value = $data[$key] ?? null;

        return is_scalar($value) && trim((string) $value) !== '' ? trim((string) $value) : '—';
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, string>  $map
     */
    private static function label(array $data, string $key, array $map): string
    {
        $value = $data[$key] ?? null;

        if (! is_string($value) || $value === '') {
            return '—';
        }

        return $map[$value] ?? $value;
    }

    /**
     * A single answer that may be "Other" with typed text.
     *
     * @param  array<string, mixed>  $data
     */
    private static function choice(array $data, string $key, string $otherKey, string $otherValue = 'other'): string
    {
        $value = self::text($data, $key);

        if ($value !== $otherValue) {
            return $value;
        }

        $other = self::text($data, $otherKey);

        return $other === '—' ? 'Other' : 'Other: '.$other;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function sector(array $data, string $key, string $otherKey): string
    {
        $value = $data[$key] ?? null;

        if ($value === 'other') {
            $other = self::text($data, $otherKey);

            return $other === '—' ? 'Other' : 'Other: '.$other;
        }

        return self::label($data, $key, self::SECTORS);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, string>  $map
     */
    private static function labels(array $data, string $key, array $map, ?string $otherKey = null): string
    {
        $codes = (array) ($data[$key] ?? []);

        if ($codes === []) {
            return '—';
        }

        $labels = array_map(function ($code) use ($data, $map, $otherKey) {
            $code = (string) $code;

            if ($code === 'other') {
                $other = $otherKey === null ? '—' : self::text($data, $otherKey);

                return $other === '—' ? 'Other' : 'Other: '.$other;
            }

            return $map[$code] ?? $code;
        }, $codes);

        return implode(', ', $labels);
    }
}
