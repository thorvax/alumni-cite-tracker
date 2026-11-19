<?php

namespace App\Support\Import;

/**
 * Checks the rows of an uploaded student sheet and says exactly which cell is wrong.
 */
final class StudentSheetValidator
{
    /** Student ID numbers are 8 digits, for example 20244012. */
    public const ID_PATTERN = '/^\d{8}$/';

    /**
     * @param  list<array{row: int, student_id: string, last_name: string, email: string}>  $rows
     * @param  array{student_id: string, last_name: string, email: string}  $columns  Column letters.
     * @return array{
     *     errors: list<array{row: int, cell: string, type: string, message: string}>,
     *     valid: list<array{row: int, student_id: string, last_name: string, email: string|null}>
     * }
     */
    public function validate(array $rows, array $columns): array
    {
        $errors = [];
        $valid = [];
        $firstSeen = [];

        foreach ($rows as $row) {
            $rowErrors = [];
            $number = $row['row'];

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

            if ($rowErrors === []) {
                $valid[] = [
                    'row' => $number,
                    'student_id' => $row['student_id'],
                    'last_name' => $row['last_name'],
                    'email' => $row['email'] === '' ? null : strtolower($row['email']),
                ];
            }

            array_push($errors, ...$rowErrors);
        }

        return ['errors' => $errors, 'valid' => $valid];
    }
}
