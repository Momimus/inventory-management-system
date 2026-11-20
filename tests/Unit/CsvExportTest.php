<?php

namespace Tests\Unit;

use App\Support\CsvExport;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class CsvExportTest extends TestCase
{
    public static function formulaLikeText(): array
    {
        return [
            'equals' => ['=1+1', "'=1+1"],
            'plus' => ['+44 20 7946 0000', "'+44 20 7946 0000"],
            'minus' => ['-2+3', "'-2+3"],
            'at sign' => ['@SUM(A1)', "'@SUM(A1)"],
            'tab' => ["\t=cmd", "'\t=cmd"],
            'carriage return' => ["\r=cmd", "'\r=cmd"],
            'hyperlink' => ['=HYPERLINK("http://evil.test")', '\'=HYPERLINK("http://evil.test")'],
        ];
    }

    #[DataProvider('formulaLikeText')]
    public function test_text_that_starts_a_formula_is_prefixed(string $input, string $expected): void
    {
        $this->assertSame($expected, CsvExport::cell($input));
    }

    public function test_numbers_stay_numeric_even_when_negative(): void
    {
        $this->assertSame(-4, CsvExport::cell(-4));
        $this->assertSame(12.5, CsvExport::cell(12.5));
        $this->assertSame(0, CsvExport::cell(0));
    }

    public function test_ordinary_values_are_unchanged(): void
    {
        $this->assertSame('Plain text', CsvExport::cell('Plain text'));
        $this->assertSame('A=B', CsvExport::cell('A=B'));
        $this->assertSame('', CsvExport::cell(null));
        $this->assertSame('', CsvExport::cell(''));
    }
}
