<?php

namespace App\Exports;

use App\Services\Driver\MigrationReportService;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MigrationReportsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected array $filters;
    protected MigrationReportService $reportService;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
        $this->reportService = app(MigrationReportService::class);
    }

    public function collection()
    {
        return $this->reportService->getMigrationsForExport($this->filters);
    }

    public function headings(): array
    {
        return [
            'ID',
            'Driver Name',
            'Driver Email',
            'Source Carrier',
            'Target Carrier',
            'Migration Date',
            'Migrated By',
            'Reason',
            'Notes',
            'Status',
            'Rolled Back At',
            'Rolled Back By',
            'Rollback Reason',
        ];
    }

    public function map($record): array
    {
        return [
            $record->id,
            $record->driverUser->name ?? 'Unknown',
            $record->driverUser->email ?? 'Unknown',
            $record->sourceCarrier->name ?? 'Unknown',
            $record->targetCarrier->name ?? 'Unknown',
            $record->migrated_at->format('Y-m-d H:i:s'),
            $record->migratedByUser->name ?? 'Unknown',
            $record->reason ?? '',
            $record->notes ?? '',
            ucfirst(str_replace('_', ' ', $record->status)),
            $record->rolled_back_at?->format('Y-m-d H:i:s') ?? '',
            $record->rolledBackByUser->name ?? '',
            $record->rollback_reason ?? '',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E2E8F0'],
                ],
            ],
        ];
    }
}
