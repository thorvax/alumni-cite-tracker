<?php

namespace App\Support\Import;

use DateTimeInterface;
use OpenSpout\Reader\CSV\Options as CsvOptions;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\CSV\Sheet as CsvSheet;
use OpenSpout\Reader\XLSX\Options as XlsxOptions;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;
use OpenSpout\Reader\XLSX\Sheet as XlsxSheet;

/**
 * Reads the first sheet of an .xlsx or .csv file with the columns
 * Student ID Number, Last Name, and the optional Email, Course and Year Graduated.
 */
final class StudentSheetReader
{
    public const MAX_ROWS = 5000;

    private const HEADERS = [
        'student_id' => ['studentidnumber', 'studentid', 'idnumber', 'studentno', 'studentnumber'],
        'last_name' => ['lastname', 'surname', 'familyname'],
        'email' => ['email', 'emailaddress'],
        'course' => ['course', 'program', 'courseprogram', 'degree'],
        'year_graduated' => ['yeargraduated', 'graduationyear', 'yearofgraduation', 'year', 'batch'],
    ];

    /** Letters used in messages when an optional column is missing from the file. */
    private const DEFAULT_LETTERS = ['student_id' => 'A', 'last_name' => 'B', 'email' => 'C', 'course' => 'D', 'year_graduated' => 'E'];

    /**
     * @return array{
     *     rows: list<array{row: int, student_id: string, last_name: string, email: string, course: string, year_graduated: string}>,
     *     columns: array{student_id: string, last_name: string, email: string, course: string, year_graduated: string},
     *     error: string|null
     * }
     */
    public function read(string $path, string $extension): array
    {
        // Keep blank rows so the row numbers we report match the numbers shown in Excel.
        if (strtolower($extension) === 'csv') {
            $reader = new CsvReader(new CsvOptions(SHOULD_PRESERVE_EMPTY_ROWS: true));
        } else {
            $reader = new XlsxReader(new XlsxOptions(SHOULD_PRESERVE_EMPTY_ROWS: true));
        }

        $reader->open($path);

        try {
            foreach ($reader->getSheetIterator() as $sheet) {
                return $this->readSheet($sheet);
            }
        } finally {
            $reader->close();
        }

        return $this->failure('The file has no sheets.');
    }

    /**
     * @return array{
     *     rows: list<array{row: int, student_id: string, last_name: string, email: string, course: string, year_graduated: string}>,
     *     columns: array{student_id: string, last_name: string, email: string, course: string, year_graduated: string},
     *     error: string|null
     * }
     */
    private function readSheet(CsvSheet|XlsxSheet $sheet): array
    {
        $positions = null;
        $rows = [];

        foreach ($sheet->getRowIterator() as $rowNumber => $row) {
            $cells = array_map(fn (mixed $value) => $this->clean($value), array_values($row->toArray()));

            if (! array_filter($cells, fn (string $cell) => $cell !== '')) {
                continue;
            }

            // The first row with anything in it holds the column headings.
            if ($positions === null) {
                $positions = $this->findColumns($cells);

                if ($positions === null) {
                    return $this->failure('Could not find the "Student ID Number" and "Last Name" columns in the first row. Download the template and keep its headings.');
                }

                continue;
            }

            if (count($rows) >= self::MAX_ROWS) {
                return $this->failure('The file has more than '.number_format(self::MAX_ROWS).' rows. Split it into smaller files.');
            }

            $rows[] = [
                'row' => (int) $rowNumber,
                'student_id' => $cells[$positions['student_id']] ?? '',
                'last_name' => $cells[$positions['last_name']] ?? '',
                'email' => $this->cell($cells, $positions['email']),
                'course' => $this->cell($cells, $positions['course']),
                'year_graduated' => $this->cell($cells, $positions['year_graduated']),
            ];
        }

        if ($positions === null) {
            return $this->failure('The file is empty.');
        }

        return [
            'rows' => $rows,
            'columns' => [
                'student_id' => $this->letter($positions['student_id']),
                'last_name' => $this->letter($positions['last_name']),
                'email' => $this->columnLetter($positions, 'email'),
                'course' => $this->columnLetter($positions, 'course'),
                'year_graduated' => $this->columnLetter($positions, 'year_graduated'),
            ],
            'error' => null,
        ];
    }

    /**
     * @param  list<string>  $headings
     * @return array{student_id: int, last_name: int, email: int|null, course: int|null, year_graduated: int|null}|null
     */
    private function findColumns(array $headings): ?array
    {
        $found = ['student_id' => null, 'last_name' => null, 'email' => null, 'course' => null, 'year_graduated' => null];

        foreach ($headings as $index => $heading) {
            $key = strtolower((string) preg_replace('/[^a-z0-9]/i', '', $heading));

            foreach (self::HEADERS as $field => $names) {
                if ($found[$field] === null && in_array($key, $names, true)) {
                    $found[$field] = $index;
                }
            }
        }

        if ($found['student_id'] === null || $found['last_name'] === null) {
            return null;
        }

        return [
            'student_id' => $found['student_id'],
            'last_name' => $found['last_name'],
            'email' => $found['email'],
            'course' => $found['course'],
            'year_graduated' => $found['year_graduated'],
        ];
    }

    /**
     * The value of an optional column, or '' when the file does not have that column.
     *
     * @param  list<string>  $cells
     */
    private function cell(array $cells, ?int $position): string
    {
        return $position === null ? '' : ($cells[$position] ?? '');
    }

    /**
     * @param  array{student_id: int, last_name: int, email: int|null, course: int|null, year_graduated: int|null}  $positions
     */
    private function columnLetter(array $positions, string $field): string
    {
        $position = $positions[$field] ?? null;

        return $position === null ? self::DEFAULT_LETTERS[$field] : $this->letter($position);
    }

    private function clean(mixed $value): string
    {
        if ($value === null || is_bool($value)) {
            return '';
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        // Excel stores numbers as floats; 20244012 must not become "2.0244012E+7" or "20244012.0".
        if (is_int($value)) {
            return (string) $value;
        }

        if (is_float($value)) {
            return floor($value) === $value ? number_format($value, 0, '', '') : (string) $value;
        }

        $text = str_replace(["\u{00A0}", "\u{200B}", "\u{FEFF}"], [' ', '', ''], (string) $value);

        return trim((string) preg_replace('/\s+/u', ' ', $text));
    }

    private function letter(int $index): string
    {
        $letters = '';

        for ($n = $index + 1; $n > 0; $n = intdiv($n - 1, 26)) {
            $letters = chr(65 + (($n - 1) % 26)).$letters;
        }

        return $letters;
    }

    /**
     * @return array{rows: list<never>, columns: array{student_id: string, last_name: string, email: string, course: string, year_graduated: string}, error: string}
     */
    private function failure(string $message): array
    {
        return [
            'rows' => [],
            'columns' => self::DEFAULT_LETTERS,
            'error' => $message,
        ];
    }
}
