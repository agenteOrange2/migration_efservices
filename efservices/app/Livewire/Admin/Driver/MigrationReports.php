<?php

namespace App\Livewire\Admin\Driver;

use App\Exports\MigrationReportsExport;
use App\Exports\MigrationReportsPdfExport;
use App\Models\Carrier;
use App\Services\Driver\MigrationReportService;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Livewire component for migration reports and statistics.
 */
#[Layout('layouts.admin')]
class MigrationReports extends Component
{
    use WithPagination;

    // Filters
    public string $search = '';
    public string $dateFrom = '';
    public string $dateTo = '';
    public ?int $sourceCarrierId = null;
    public ?int $targetCarrierId = null;
    public string $status = '';

    // Pagination
    public int $perPage = 15;

    protected MigrationReportService $reportService;

    protected $queryString = [
        'search' => ['except' => ''],
        'dateFrom' => ['except' => ''],
        'dateTo' => ['except' => ''],
        'sourceCarrierId' => ['except' => null],
        'targetCarrierId' => ['except' => null],
        'status' => ['except' => ''],
    ];

    public function boot(MigrationReportService $reportService): void
    {
        $this->reportService = $reportService;
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatingDateTo(): void
    {
        $this->resetPage();
    }

    public function updatingSourceCarrierId(): void
    {
        $this->resetPage();
    }

    public function updatingTargetCarrierId(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    protected function getFilters(): array
    {
        return array_filter([
            'search' => $this->search,
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
            'source_carrier_id' => $this->sourceCarrierId,
            'target_carrier_id' => $this->targetCarrierId,
            'status' => $this->status,
        ]);
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'dateFrom', 'dateTo', 'sourceCarrierId', 'targetCarrierId', 'status']);
        $this->resetPage();
    }

    public function getCarriersProperty()
    {
        return Carrier::orderBy('name')->get(['id', 'name']);
    }

    public function getStatusOptionsProperty(): array
    {
        return [
            'completed' => 'Completed',
            'rolled_back' => 'Rolled Back',
        ];
    }

    public function getStatisticsProperty(): array
    {
        return $this->reportService->getMigrationStatistics($this->getFilters());
    }

    public function exportExcel()
    {
        $filename = 'migration-report-' . now()->format('Y-m-d') . '.xlsx';
        return Excel::download(new MigrationReportsExport($this->getFilters()), $filename);
    }

    public function exportPdf()
    {
        $exporter = new MigrationReportsPdfExport($this->getFilters());
        return $exporter->download('migration-report-' . now()->format('Y-m-d') . '.pdf');
    }

    public function render()
    {
        $migrations = $this->reportService->getMigrations($this->getFilters(), $this->perPage);

        return view('livewire.admin.driver.migration-reports', [
            'migrations' => $migrations,
            'statistics' => $this->statistics,
            'carriers' => $this->carriers,
            'statusOptions' => $this->statusOptions,
        ]);
    }
}
