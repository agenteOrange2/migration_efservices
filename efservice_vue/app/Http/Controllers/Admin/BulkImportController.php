<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Import\BulkImportRequest;
use App\Models\Carrier;
use App\Services\Import\ImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class BulkImportController extends Controller
{
    public function __construct(protected ImportService $importService)
    {
    }

    public function index(): InertiaResponse
    {
        return Inertia::render('admin/imports/Index', [
            'importTypes' => collect($this->importService->getAvailableTypes())
                ->map(fn (array $type, string $key) => [
                    'key' => $key,
                    'name' => $type['name'],
                    'description' => $type['description'],
                    'template' => $type['template'],
                    'icon' => $type['icon'],
                ])
                ->values(),
            'carriers' => Carrier::query()
                ->orderBy('name')
                ->get(['id', 'name', 'dot_number'])
                ->map(fn (Carrier $carrier) => [
                    'id' => $carrier->id,
                    'name' => $carrier->name,
                    'dot_number' => $carrier->dot_number,
                ])
                ->values(),
        ]);
    }

    public function downloadTemplate(string $type)
    {
        try {
            return $this->importService->downloadTemplate($type);
        } catch (\InvalidArgumentException $exception) {
            return back()->with('error', $exception->getMessage());
        }
    }

    public function preview(BulkImportRequest $request): InertiaResponse|RedirectResponse
    {
        $carrierId = $request->input('carrier_id') ? (int) $request->input('carrier_id') : null;
        $type = (string) $request->input('import_type');
        $file = $request->file('csv_file');

        try {
            $preview = $this->importService->preview($type, $file, $carrierId);

            if (! ($preview['success'] ?? false)) {
                return back()->with('error', $preview['error'] ?? 'Failed to preview file.');
            }

            $tempPath = $this->importService->storeTemporarily($file);
            $carrier = $carrierId ? Carrier::query()->find($carrierId) : null;
            $importTypes = $this->importService->getAvailableTypes();

            return Inertia::render('admin/imports/Preview', [
                'preview' => $preview,
                'type' => $type,
                'typeName' => $importTypes[$type]['name'] ?? $type,
                'carrierId' => $carrierId,
                'carrierName' => $carrier?->name,
                'tempPath' => $tempPath,
            ]);
        } catch (\Throwable $exception) {
            Log::error('Import preview failed', [
                'error' => $exception->getMessage(),
                'type' => $type,
                'carrier_id' => $carrierId,
            ]);

            return back()->with('error', 'Failed to preview file: ' . $exception->getMessage());
        }
    }

    public function execute(Request $request): InertiaResponse|RedirectResponse
    {
        $validated = $request->validate([
            'import_type' => ['required', 'string'],
            'carrier_id' => ['nullable', 'integer', 'exists:carriers,id'],
            'temp_path' => ['required', 'string'],
            'duplicate_action' => ['nullable', 'in:skip,update'],
        ]);

        $type = (string) $validated['import_type'];
        $carrierId = ! empty($validated['carrier_id']) ? (int) $validated['carrier_id'] : null;
        $tempPath = (string) $validated['temp_path'];
        $duplicateAction = (string) ($validated['duplicate_action'] ?? 'skip');

        try {
            $result = $this->importService->import(
                $type,
                $tempPath,
                $carrierId,
                (int) auth()->id(),
                $duplicateAction,
            );

            $carrier = $carrierId ? Carrier::query()->find($carrierId) : null;
            $importTypes = $this->importService->getAvailableTypes();

            return Inertia::render('admin/imports/Results', [
                'result' => $result,
                'type' => $type,
                'typeName' => $importTypes[$type]['name'] ?? $type,
                'carrierId' => $carrierId,
                'carrierName' => $carrier?->name,
            ]);
        } catch (\Throwable $exception) {
            Log::error('Import execution failed', [
                'error' => $exception->getMessage(),
                'type' => $type,
                'carrier_id' => $carrierId,
            ]);

            return redirect()
                ->route('admin.imports.index')
                ->with('error', 'Import failed: ' . $exception->getMessage());
        } finally {
            $this->importService->deleteTemporaryFile($tempPath);
        }
    }
}
