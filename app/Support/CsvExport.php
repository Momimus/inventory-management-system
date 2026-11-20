<?php

namespace App\Support;

use Symfony\Component\HttpFoundation\StreamedResponse;

class CsvExport
{
    /**
     * Characters that make spreadsheet programs treat a cell as a formula.
     */
    private const FORMULA_PREFIXES = ['=', '+', '-', '@', "\t", "\r"];

    /**
     * Streams rows straight to the browser, so large exports never have to
     * fit in memory.
     *
     * @param  list<string>  $headings
     * @param  iterable<array<int, mixed>>  $rows
     */
    public static function download(string $filename, array $headings, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headings, $rows) {
            $output = fopen('php://output', 'w');

            // Byte order mark so Excel opens the file as UTF-8.
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, $headings);

            foreach ($rows as $row) {
                fputcsv($output, array_map(self::cell(...), $row));
            }

            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Text that would start a formula (e.g. =HYPERLINK(...)) is prefixed with
     * an apostrophe so it is shown as text. Numbers are left as numbers.
     */
    public static function cell(mixed $value): string|int|float
    {
        if (is_int($value) || is_float($value)) {
            return $value;
        }

        $value = (string) $value;

        return $value !== '' && in_array($value[0], self::FORMULA_PREFIXES, true) ? "'".$value : $value;
    }
}
