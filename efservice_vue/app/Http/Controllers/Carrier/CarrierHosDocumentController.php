<?php

namespace App\Http\Controllers\Carrier;

use App\Http\Controllers\Admin\HosDocumentController as AdminHosDocumentController;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class CarrierHosDocumentController extends AdminHosDocumentController
{
    public function index(Request $request): InertiaResponse
    {
        $scope = $this->scopeContext();
        $filters = [
            'type' => (string) $request->input('type', 'all'),
            'carrier_id' => (string) ($scope['carrier_id'] ?? ''),
            'driver_id' => (string) $request->input('driver_id', ''),
            'start_date' => (string) $request->input('start_date', ''),
            'end_date' => (string) $request->input('end_date', ''),
        ];

        $documents = $this->filteredDocuments($scope, $filters)
            ->map(function (array $row) {
                $row['preview_url'] = route('carrier.hos.documents.preview', $row['id']);
                $row['download_url'] = route('carrier.hos.documents.download', $row['id']);
                return $row;
            })
            ->sortByDesc(fn (array $row) => $row['sort_timestamp'])
            ->values();

        return Inertia::render('carrier/hos/documents/Index', [
            'filters' => $filters,
            'stats' => [
                'total' => $documents->count(),
                'trip_reports' => $documents->where('type_key', 'trip_reports')->count(),
                'inspection_reports' => $documents->where('type_key', 'inspection_reports')->count(),
                'daily_logs' => $documents->where('type_key', 'daily_logs')->count(),
                'monthly_summaries' => $documents->where('type_key', 'monthly_summaries')->count(),
                'fmcsa_monthly' => $documents->where('type_key', 'fmcsa_monthly')->count(),
            ],
            'documents' => $documents->map(function (array $row) {
                unset($row['sort_timestamp']);
                return $row;
            })->values(),
            'carriers' => $this->carrierOptions($scope),
            'drivers' => $this->driverOptions($scope, $filters['carrier_id'], false),
            'canFilterCarriers' => false,
        ]);
    }
}
