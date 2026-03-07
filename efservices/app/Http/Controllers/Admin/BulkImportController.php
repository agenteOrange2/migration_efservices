<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Import\BulkImportRequest;
use App\Services\Import\ImportService;
use App\Models\Carrier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BulkImportController extends Controller
{
    protected ImportService $importService;

    public function __construct(ImportService $importService)
    {
        $this->importService = $importService;
    }

    /**
     * Show import dashboard.
     */
    public function index()
    {
        $importTypes = $this->importService->getAvailableTypes();
        $carriers = Carrier::orderBy('name')->get();

        return view('admin.imports.index', compact('importTypes', 'carriers'));
    }

    /**
     * Download CSV template.
     */
    public function downloadTemplate(string $type)
    {
        try {
            return $this->importService->downloadTemplate($type);
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Preview import data.
     */
    public function preview(BulkImportRequest $request)
    {
        $carrierId = $request->input('carrier_id') ? (int) $request->input('carrier_id') : null;
        $type = $request->input('import_type');
        $file = $request->file('csv_file');

        try {
            $preview = $this->importService->preview($type, $file, $carrierId);

            if (!$preview['success']) {
                return back()->with('error', $preview['error'] ?? 'Failed to preview file.');
            }

            // Store file temporarily for execution
            $tempPath = $this->importService->storeTemporarily($file);

            $carrier = $carrierId ? Carrier::find($carrierId) : null;
            $importTypes = $this->importService->getAvailableTypes();

            return view('admin.imports.preview', [
                'preview' => $preview,
                'type' => $type,
                'typeName' => $importTypes[$type]['name'] ?? $type,
                'carrierId' => $carrierId,
                'carrierName' => $carrier ? $carrier->name : 'N/A',
                'tempPath' => $tempPath,
            ]);
        } catch (\Exception $e) {
            Log::error('Import preview failed', [
                'error' => $e->getMessage(),
                'type' => $type,
                'carrier_id' => $carrierId,
            ]);

            return back()->with('error', 'Failed to preview file: ' . $e->getMessage());
        }
    }

    /**
     * Execute the import.
     */
    public function execute(Request $request)
    {
        $request->validate([
            'import_type' => 'required|string',
            'carrier_id' => 'exclude_if:import_type,carriers|required|exists:carriers,id',
            'temp_path' => 'required|string',
            'duplicate_action' => 'nullable|in:skip,update',
        ]);

        $type = $request->input('import_type');
        $carrierId = $request->input('carrier_id') ? (int) $request->input('carrier_id') : null;
        $tempPath = $request->input('temp_path');
        $duplicateAction = $request->input('duplicate_action', 'skip');

        try {
            $result = $this->importService->import(
                $type,
                $tempPath,
                $carrierId,
                auth()->id(),
                $duplicateAction
            );

            // Clean up temp file
            $this->importService->deleteTemporaryFile($tempPath);

            $carrier = $carrierId ? Carrier::find($carrierId) : null;
            $importTypes = $this->importService->getAvailableTypes();

            return view('admin.imports.results', [
                'result' => $result,
                'type' => $type,
                'typeName' => $importTypes[$type]['name'] ?? $type,
                'carrierId' => $carrierId,
                'carrierName' => $carrier ? $carrier->name : 'N/A',
            ]);
        } catch (\Exception $e) {
            Log::error('Import execution failed', [
                'error' => $e->getMessage(),
                'type' => $type,
                'carrier_id' => $carrierId,
            ]);

            // Clean up temp file on failure
            $this->importService->deleteTemporaryFile($tempPath);

            return redirect()
                ->route('admin.imports.index')
                ->with('error', 'Import failed: ' . $e->getMessage());
        }
    }
}
