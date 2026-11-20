<?php

namespace App\Reports;

use App\Support\CsvExport;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * A report is a filtered query plus the columns used when exporting it.
 * The HTML page and the CSV file come from the same query, so they always
 * contain the same records.
 */
abstract class Report
{
    /**
     * @param  array<string, mixed>  $filters  validated by ReportRequest
     */
    public function __construct(protected array $filters) {}

    abstract public function title(): string;

    abstract public function query(): Builder;

    /**
     * @return list<string>
     */
    abstract public function headings(): array;

    /**
     * @return list<string|int|float|null>
     */
    abstract public function row(Model $record): array;

    public function filename(): string
    {
        return str($this->title())->slug().'-'.now()->format('Y-m-d-His').'.csv';
    }

    public function export(): StreamedResponse
    {
        $rows = $this->query()->lazy(500)->map(fn (Model $record) => $this->row($record));

        return CsvExport::download($this->filename(), $this->headings(), $rows);
    }

    protected function filter(string $key): mixed
    {
        return $this->filters[$key] ?? null;
    }

    protected function date(string $key): ?Carbon
    {
        return $this->filter($key) ? Carbon::parse($this->filter($key)) : null;
    }
}
