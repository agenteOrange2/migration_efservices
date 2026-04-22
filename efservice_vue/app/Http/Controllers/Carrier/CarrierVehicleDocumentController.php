<?php

namespace App\Http\Controllers\Carrier;

use App\Http\Controllers\Admin\Vehicles\VehicleDocumentController;
use App\Http\Controllers\Carrier\Concerns\ResolvesCarrierContext;
use App\Models\Admin\Vehicle\Vehicle;
use App\Models\Admin\Vehicle\VehicleDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class CarrierVehicleDocumentController extends VehicleDocumentController
{
    use ResolvesCarrierContext;

    public function overview(Request $request): InertiaResponse
    {
        $response = parent::overview($request);

        return Inertia::render('carrier/vehicles/documents/Overview', [
            ...(fn() => $this->props)->call($response),
            'carrier' => $this->carrierOption(),
            'isCarrierContext' => true,
            'routeNames' => $this->routeNames(),
        ]);
    }

    public function index(Vehicle $vehicle, Request $request): InertiaResponse
    {
        $response = parent::index($vehicle, $request);

        return Inertia::render('carrier/vehicles/documents/Index', [
            ...(fn() => $this->props)->call($response),
            'carrier' => $this->carrierOption(),
            'isCarrierContext' => true,
            'routeNames' => $this->routeNames(),
        ]);
    }

    public function store(Request $request, Vehicle $vehicle): RedirectResponse
    {
        $this->authorizeVehicle($vehicle);
        $validated = $this->validateDocumentPayload($request, true);

        DB::transaction(function () use ($request, $validated, $vehicle) {
            $document = VehicleDocument::create($this->documentPayload($request, $validated, $vehicle));

            $document->addMediaFromRequest('document_file')
                ->usingName(pathinfo($request->file('document_file')->getClientOriginalName(), PATHINFO_FILENAME))
                ->toMediaCollection('document_files');
        });

        return redirect()
            ->route('carrier.vehicles.documents.index', $vehicle)
            ->with('success', 'Vehicle document uploaded successfully.');
    }

    public function update(Request $request, Vehicle $vehicle, VehicleDocument $document): RedirectResponse
    {
        $this->authorizeVehicle($vehicle);
        $this->assertVehicleDocument($vehicle, $document);
        $validated = $this->validateDocumentPayload($request, false);

        DB::transaction(function () use ($request, $validated, $vehicle, $document) {
            $document->update($this->documentPayload($request, $validated, $vehicle));

            if ($request->hasFile('document_file')) {
                $document->clearMediaCollection('document_files');
                $document->addMediaFromRequest('document_file')
                    ->usingName(pathinfo($request->file('document_file')->getClientOriginalName(), PATHINFO_FILENAME))
                    ->toMediaCollection('document_files');
            }
        });

        return redirect()
            ->route('carrier.vehicles.documents.index', $vehicle)
            ->with('success', 'Vehicle document updated successfully.');
    }

    public function destroy(Vehicle $vehicle, VehicleDocument $document): RedirectResponse
    {
        $this->authorizeVehicle($vehicle);
        $this->assertVehicleDocument($vehicle, $document);

        DB::transaction(function () use ($document) {
            $document->clearMediaCollection('document_files');
            $document->delete();
        });

        return redirect()
            ->route('carrier.vehicles.documents.index', $vehicle)
            ->with('success', 'Vehicle document deleted successfully.');
    }

    public function generateMaintenanceReport(\App\Models\Admin\Vehicle\Vehicle $vehicle): \Illuminate\Http\RedirectResponse
    {
        $this->authorizeVehicle($vehicle);
        $vehicle->load('carrier');

        $maintenances = \App\Models\Admin\Vehicle\VehicleMaintenance::where('vehicle_id', $vehicle->id)
            ->orderBy('service_date', 'asc')
            ->get();

        abort_if($maintenances->isEmpty(), 404, 'No maintenance records found for this vehicle.');

        $fileName = 'maintenance-report-' . $vehicle->id . '-' . now()->format('YmdHis') . '.pdf';
        $tempDir = storage_path('app/temp');
        $tempPath = $tempDir . DIRECTORY_SEPARATOR . $fileName;

        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.vehicles.maintenance.full-report-pdf', [
            'vehicle' => $vehicle,
            'maintenances' => $maintenances,
        ])->setPaper('letter', 'portrait')->save($tempPath);

        try {
            $document = \App\Models\Admin\Vehicle\VehicleDocument::create([
                'vehicle_id' => $vehicle->id,
                'document_type' => \App\Models\Admin\Vehicle\VehicleDocument::DOC_TYPE_MAINTENANCE_RECORD,
                'document_number' => 'MR-' . $vehicle->id . '-' . now()->format('Ymd'),
                'issued_date' => now()->toDateString(),
                'status' => \App\Models\Admin\Vehicle\VehicleDocument::STATUS_ACTIVE,
                'notes' => 'Auto-generated Vehicle Service Due Status Report (49 C.F.R. 396.3). Generated on ' . now()->format('m/d/Y h:i A') . '. Contains ' . $maintenances->count() . ' maintenance record(s).',
            ]);

            $document->addMedia($tempPath)
                ->usingFileName($fileName)
                ->toMediaCollection('document_files');
        } finally {
            if (file_exists($tempPath)) {
                @unlink($tempPath);
            }
        }

        return redirect()
            ->route('carrier.vehicles.documents.index', $vehicle)
            ->with('success', 'Maintenance report generated and saved to vehicle documents.');
    }

    public function generateRepairReport(\App\Models\Admin\Vehicle\Vehicle $vehicle): \Illuminate\Http\RedirectResponse
    {
        $this->authorizeVehicle($vehicle);
        $vehicle->load('carrier');

        $repairs = \App\Models\EmergencyRepair::where('vehicle_id', $vehicle->id)
            ->orderBy('repair_date', 'asc')
            ->get();

        abort_if($repairs->isEmpty(), 404, 'No repair records found for this vehicle.');

        $fileName = 'repair-report-' . $vehicle->id . '-' . now()->format('YmdHis') . '.pdf';
        $tempDir = storage_path('app/temp');
        $tempPath = $tempDir . DIRECTORY_SEPARATOR . $fileName;

        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.vehicles.emergency-repairs.full-report-pdf', [
            'vehicle' => $vehicle,
            'repairs' => $repairs,
        ])->setPaper('letter', 'portrait')->save($tempPath);

        try {
            $document = \App\Models\Admin\Vehicle\VehicleDocument::create([
                'vehicle_id' => $vehicle->id,
                'document_type' => \App\Models\Admin\Vehicle\VehicleDocument::DOC_TYPE_REPAIR_RECORD,
                'document_number' => 'RR-' . $vehicle->id . '-' . now()->format('Ymd'),
                'issued_date' => now()->toDateString(),
                'status' => \App\Models\Admin\Vehicle\VehicleDocument::STATUS_ACTIVE,
                'notes' => 'Auto-generated Inspection, Repair & Maintenance Record (49 C.F.R. 396.3). Generated on ' . now()->format('m/d/Y h:i A') . '. Contains ' . $repairs->count() . ' repair record(s).',
            ]);

            $document->addMedia($tempPath)
                ->usingFileName($fileName)
                ->toMediaCollection('document_files');
        } finally {
            if (file_exists($tempPath)) {
                @unlink($tempPath);
            }
        }

        return redirect()
            ->route('carrier.vehicles.documents.index', $vehicle)
            ->with('success', 'Repair report generated and saved to vehicle documents.');
    }

    protected function isSuperadmin(): bool
    {
        return false;
    }

    protected function currentCarrierId(): ?int
    {
        return $this->resolveCarrierId();
    }

    protected function routeNames(): array
    {
        return [
            'index' => 'carrier.vehicles.index',
            'show' => 'carrier.vehicles.show',
            'documentsOverview' => 'carrier.vehicles-documents.index',
            'documentsIndex' => 'carrier.vehicles.documents.index',
            'documentsStore' => 'carrier.vehicles.documents.store',
            'documentsUpdate' => 'carrier.vehicles.documents.update',
            'documentsDestroy' => 'carrier.vehicles.documents.destroy',
            'documentsDownloadAll' => 'carrier.vehicles.documents.download-all',
            'documentsGenerateMaintenanceReport' => 'carrier.vehicles.documents.generate-maintenance-report',
            'documentsGenerateRepairReport' => 'carrier.vehicles.documents.generate-repair-report',
        ];
    }

    protected function carrierOption(): array
    {
        $carrier = $this->resolveCarrier();

        return [
            'id' => $carrier->id,
            'name' => $carrier->name,
        ];
    }
}
