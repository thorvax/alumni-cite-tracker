<?php

namespace App\Support\Import;

use App\Models\Graduate;

/**
 * Checks the rows of an uploaded student sheet and says exactly which cell is wrong.
 */
final class StudentSheetValidator
{
    /** Student ID numbers are 8 digits, for example 20244012. */
    public const ID_PATTERN = '/^\d{8}$/';

    /** Same range as the survey's "Year Graduated" question. */
    public const FIRST_YEAR = 1966;

    /**
     * @param  list<array{row: int, student_id: string, last_name: string, email: string, course?: string, year_graduated?: string}>  $rows
     * @param  array{student_id: string, last_name: string, email: string, course?: string, year_graduated?: string}  $columns  Column letters.
     * @return array{
     *     errors: list<array{row: int, cell: string, type: string, message: string}>,
     *     valid: list<array{row: int, student_id: string, last_name: string, email: string|null, course: string|null, year_graduated: int|null}>
     * }
     */
    public function validate(array $rows, array $columns): array
    {
        $columns += ['course' => 'D', 'year_graduated' => 'E'];
        $lastYear = (int) date('Y');
        $errors = [];
        $valid = [];
        $firstSeen = [];

        foreach ($rows as $row) {
            $rowErrors = [];
            $number = $row['row'];
            $courseText = $row['course'] ?? '';
            $yearText = $row['year_graduated'] ?? '';

            $add = function (string $column, string $type, string $message) use (&$rowErrors, $columns, $number): void {
                $rowErrors[] = ['row' => $number, 'cell' => $columns[$column].$number, 'type' => $type, 'message' => $message];
            };

            if ($row['student_id'] === '') {
                $add('student_id', 'empty', 'Student ID Number is empty.');
            } elseif (! preg_match(self::ID_PATTERN, $row['student_id'])) {
                $add('student_id', 'invalid_id', "Student ID Number must be 8 digits, like 20244012 (found \"{$row['student_id']}\").");
            } elseif (isset($firstSeen[$row['student_id']])) {
                $first = $firstSeen[$row['student_id']];
                $add('student_id', 'duplicate', "Duplicate Student ID Number {$row['student_id']}: it is already on row {$first} ({$columns['student_id']}{$first}).");
            } else {
                $firstSeen[$row['student_id']] = $number;
            }

            if ($row['last_name'] === '') {
                $add('last_name', 'empty', 'Last Name is empty.');
            } elseif (mb_strlen($row['last_name']) > 100) {
                $add('last_name', 'too_long', 'Last Name is longer than 100 characters.');
            }

            if ($row['email'] !== '' && ! filter_var($row['email'], FILTER_VALIDATE_EMAIL)) {
                $add('email', 'invalid_email', "\"{$row['email']}\" is not a valid email address.");
            }

            // Optional: ACT, BLIS, BSIT or the full course name.
            $course = self::course($courseText);
            if ($courseText !== '' && $course === null) {
                $add('course', 'invalid_course', 'Course must be ACT, BLIS or BSIT (found "'.$courseText.'").');
            }

            // Optional: a 4-digit year from 1966 up to this year.
            $year = preg_match('/^\d{4}$/', $yearText) ? (int) $yearText : null;
            if ($yearText !== '' && ($year === null || $year < self::FIRST_YEAR || $year > $lastYear)) {
                $add('year_graduated', 'invalid_year', 'Year Graduated must be a year from '.self::FIRST_YEAR." to {$lastYear} (found \"{$yearText}\").");
            }

            if ($rowErrors === []) {
                $valid[] = [
                    'row' => $number,
                    'student_id' => $row['student_id'],
                    'last_name' => $row['last_name'],
                    'email' => $row['email'] === '' ? null : strtolower($row['email']),
                    'course' => $course,
                    'year_graduated' => $year,
                ];
            }

            array_push($errors, ...$rowErrors);
        }

        return ['errors' => $errors, 'valid' => $valid];
    }

    /**
     * "bsit" or "Bachelor of Science in Information and Technology" -> the full name; anything else -> null.
     */
    public static function course(string $value): ?string
    {
        foreach (Graduate::COURSE_CODES as $name => $code) {
            if (strcasecmp($value, $code) === 0 || strcasecmp($value, $name) === 0) {
                return $name;
            }
        }

        return null;
    }
}
