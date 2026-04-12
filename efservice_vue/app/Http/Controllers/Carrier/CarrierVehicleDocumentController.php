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
