<?php

namespace App\Support\Import;

use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Writer\XLSX\Writer;

/**
 * Writes the Excel template admins download before a bulk upload.
 */
final class StudentTemplate
{
    public function write(string $path): void
    {
        $options = new Options;
        $options->setColumnWidth(24, 1);
        $options->setColumnWidth(28, 2);
        $options->setColumnWidth(38, 3);

        $writer = new Writer($options);
        $writer->openToFile($path);

        $writer->getCurrentSheet()->setName('Students');
        $writer->addRow(Row::fromValuesWithStyle(
            ['Student ID Number', 'Last Name', 'Email'],
            (new Style)->withFontBold(true),
        ));

        $writer->addNewSheetAndMakeItCurrent()->setName('Instructions');

        foreach ([
            'How to use this template',
            '1. Type one student per row on the "Students" sheet, starting on row 2. Keep the headings in row 1.',
            '2. Student ID Number is required and must be 8 digits, for example 20244012.',
            '3. Last Name is required.',
            '4. Email is optional. Students without one can be given an email on the upload page.',
            '5. Each Student ID Number can appear only once.',
        ] as $line) {
            $writer->addRow(Row::fromValues([$line]));
        }

        $writer->close();
    }
}
