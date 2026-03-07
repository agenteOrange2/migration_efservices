<?php

namespace App\Exports;

use App\Services\Driver\MigrationReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;

class MigrationReportsPdfExport
{
    protected array $filters;
    protected MigrationReportService $reportService;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
        $this->reportService = app(MigrationReportService::class);
    }

    public function download(string $filename = 'migration-report.pdf')
    {
        $migrations = $this->reportService->getMigrationsForExport($this->filters);
        $statistics = $this->reportService->getMigrationStatistics($this->filters);

        $pdf = Pdf::loadView('exports.migration-report-pdf', [
            'migrations' => $migrations,
            'statistics' => $statistics,
            'filters' => $this->filters,
            'generatedAt' => now()->format('F j, Y g:i A'),
        ]);

        return $pdf->download($filename);
    }

    public function stream(string $filename = 'migration-report.pdf')
    {
        $migrations = $this->reportService->getMigrationsForExport($this->filters);
        $statistics = $this->reportService->getMigrationStatistics($this->filters);

        $pdf = Pdf::loadView('exports.migration-report-pdf', [
            'migrations' => $migrations,
            'statistics' => $statistics,
            'filters' => $this->filters,
            'generatedAt' => now()->format('F j, Y g:i A'),
        ]);

        return $pdf->stream($filename);
    }
}
