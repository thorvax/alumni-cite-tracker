<?php

namespace App\Support\Reports;

/**
 * Readable labels for survey answer keys, used by the reports and CSV export.
 * Must match resources/js/lib/survey-options.ts (and the rules in SurveyController).
 */
class SurveyLabels
{
    /** employment-data.employment_sector, first_sector, current_sector (`sectors`). */
    public const SECTORS = [
        'government' => 'Government',
        'private' => 'Private',
        'entrepreneurial' => 'Entrepreneurial / Freelance',
        'other' => 'Other',
    ];

    /** employment-data.place_of_work (`placeOfWork`). */
    public const PLACE_OF_WORK = [
        'local' => 'Local',
        'abroad' => 'Abroad',
    ];

    /** first-current-job.first_earning, in survey order (`earnings`). */
    public const EARNINGS = [
        'Below P5,000',
        'P5,000 to less than P10,000',
        'P10,000 to less than P15,000',
        'P15,000 to less than P20,000',
        'P20,000 to less than P25,000',
        'P25,000 and above',
    ];

    /** first-current-job.first_how_found (`howFound`). */
    public const HOW_FOUND = [
        'Response to an advertisement',
        'As walk-in applicant',
        'Recommended by someone',
        'Information from friends',
        "Arranged by school's job placement officer",
        'Family business',
        'Job Fair or Public Employment Service Office (PESO)',
    ];

    /** first-current-job.competencies (`competencies`). */
    public const COMPETENCIES = [
        'communication' => 'Communication skills',
        'human_relations' => 'Human Relations skills',
        'entrepreneurial' => 'Entrepreneurial skills',
        'it' => 'Information Technology skills',
        'problem_solving' => 'Problem-solving skills',
        'critical_thinking' => 'Critical Thinking skills',
        'other' => 'Other',
    ];

    /** employment-data.not_employed_reasons (`notEmployedReasons`). */
    public const NOT_EMPLOYED_REASONS = [
        'advance_study' => 'Advance or further study',
        'family_concern' => 'Family concern',
        'health' => 'Health-related reason(s)',
        'no_experience' => 'Lack of work experience',
        'no_opportunity' => 'No job opportunity',
        'did_not_look' => 'Did not look for a job',
        'other' => 'Other',
    ];

    /** educational-background.reasons_ug (`courseReasons`). */
    public const COURSE_REASONS = [
        'high_grades_course' => 'High grades in related subjects',
        'good_grades_hs' => 'Good grades in high school',
        'parents_relatives' => 'Influence of parents or relatives',
        'peer_influence' => 'Peer influence',
        'role_model' => 'Inspired by a role model',
        'passion' => 'Strong passion for the profession',
        'immediate_employment' => 'Prospect for immediate employment',
        'prestige' => 'Status or prestige of the profession',
        'course_availability' => 'Course offered in chosen school',
        'career_advancement' => 'Prospect of career advancement',
        'affordable' => 'Affordable for the family',
        'compensation' => 'Prospect of attractive compensation',
        'abroad' => 'Opportunity for employment abroad',
        'no_choice' => 'No particular choice or no better idea',
    ];
}
