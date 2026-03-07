<?php

namespace App\Http\Controllers\Admin\Driver;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\StoreDriverTestingRequest;
use App\Http\Requests\UpdateDriverTestingRequest;
use App\Models\Admin\Driver\DriverTesting;
use App\Models\UserDriverDetail;
use App\Models\Carrier;
use App\Mail\DriverTestCompleted;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Carbon\Carbon;

class DriverTestingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = DriverTesting::query()
            ->with(['userDriverDetail.user', 'userDriverDetail.carrier']);
            
        // Apply filters if they exist
        if ($request->has('status') && $request->status != '') {
            $query->where('status', $request->status);
        }
        
        if ($request->has('location') && $request->location != '') {
            $query->where('location', $request->location);
        }
        
        if ($request->has('test_date_from') && $request->test_date_from != '') {
            try {
                $formattedDateFrom = Carbon::createFromFormat('m/d/Y', $request->test_date_from)->format('Y-m-d');
                $query->whereDate('test_date', '>=', $formattedDateFrom);
            } catch (\Exception $e) {
                // Log error pero no interrumpir el flujo
                Log::error('Error parsing test_date_from: ' . $e->getMessage(), [
                    'input' => $request->test_date_from
                ]);
            }
        }
        
        if ($request->has('test_date_to') && $request->test_date_to != '') {
            try {
                $formattedDateTo = Carbon::createFromFormat('m/d/Y', $request->test_date_to)->format('Y-m-d');
                $query->whereDate('test_date', '<=', $formattedDateTo);
            } catch (\Exception $e) {
                // Log error pero no interrumpir el flujo
                Log::error('Error parsing test_date_to: ' . $e->getMessage(), [
                    'input' => $request->test_date_to
                ]);
            }
        }
        
        if ($request->has('carrier_id') && $request->carrier_id != '') {
            $query->whereHas('userDriverDetail', function($q) use($request) {
                $q->where('carrier_id', $request->carrier_id);
            });
        }
        
        $driverTestings = $query->latest()->paginate(15);
        
        $locations = DriverTesting::getLocations();
        $statuses = DriverTesting::getStatuses();
        
        // Cache carrier list for 30 minutes (1800 seconds)
        $carriers = Cache::remember('driver_testing_carriers_list', 1800, function () {
            return Carrier::orderBy('name')->pluck('name', 'id');
        });
        
        // Log filter parameters for debugging
        Log::info('Driver Testing Filter Parameters', [
            'test_date_from' => $request->test_date_from,
            'test_date_to' => $request->test_date_to,
            'status' => $request->status,
            'location' => $request->location,
            'carrier_id' => $request->carrier_id,
            'search_term' => $request->search_term,
            'total_results' => $driverTestings->total()
        ]);
        
        // Log any records with missing relationships for debugging
        foreach ($driverTestings as $test) {
            if (!$test->userDriverDetail) {
                Log::warning("Driver Testing ID {$test->id} has null userDriverDetail");
            } elseif (!$test->userDriverDetail->user) {
                Log::warning("Driver Testing ID {$test->id} has null user relationship");
            } elseif (!$test->userDriverDetail->carrier) {
                Log::warning("Driver Testing ID {$test->id} has null carrier relationship");
            }
        }
        
        return view('admin.driver-testings.index', compact('driverTestings', 'locations', 'statuses', 'carriers'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $locations = DriverTesting::getLocations();
        $testTypes = DriverTesting::getDrugTestTypes();
        $billOptions = DriverTesting::getBillOptions();
        
        // Cache active carrier list for 30 minutes (1800 seconds)
        $carriers = Cache::remember('driver_testing_active_carriers', 1800, function () {
            return Carrier::where('status', 1)->orderBy('name')->get();
        });
        
        return view('admin.driver-testings.create', compact('locations', 'testTypes', 'billOptions', 'carriers'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreDriverTestingRequest $request)
    {
        // Log para debug
        Log::info('Driver Testing Store - Request data', ['files' => $request->driver_testing_files]);
        
        // Crear registro en la base de datos
        $driverTesting = new DriverTesting($request->all());
        $driverTesting->test_result = $request->test_result ?? 'Pending';
        $driverTesting->status = 'pending';
        $driverTesting->created_by = Auth::id();
        $driverTesting->updated_by = Auth::id();
        $driverTesting->save();
        
        // Asegurar que tenemos el objeto con el ID asignado
        $driverTestingId = $driverTesting->getKey();
        $driverTesting = DriverTesting::findOrFail($driverTestingId);
        
        // Procesar archivos adjuntos si existen
        if ($request->has('driver_testing_files') && !empty($request->driver_testing_files)) {
            $filesData = json_decode($request->driver_testing_files, true);
            
            Log::info('Driver Testing Store - Files data decoded', ['filesData' => $filesData]);
            
            if (is_array($filesData) && count($filesData) > 0) {
                foreach ($filesData as $fileData) {
                    // Si el archivo tiene un path temporal, es un archivo recién subido
                    if (isset($fileData['path']) && !empty($fileData['path'])) {
                        // Obtener el archivo temporal del disco temporal
                        $tempPath = storage_path('app/' . $fileData['path']);
                        
                        Log::info('Driver Testing Store - Processing file', [
                            'tempPath' => $tempPath, 
                            'originalName' => $fileData['original_name']
                        ]);
                        
                        if (file_exists($tempPath)) {
                            // Guardar en la colección de media para este registro
                            $driverTesting->addMedia($tempPath)
                                ->usingName($fileData['original_name'])
                                ->preservingOriginal()
                                ->toMediaCollection('document_attachments');
                                
                            Log::info('Driver Testing Store - File successfully added to media collection', [
                                'filename' => $fileData['original_name']
                            ]);
                        } else {
                            Log::error('Driver Testing Store - Temp file does not exist', [
                                'tempPath' => $tempPath
                            ]);
                        }
                    } else {
                        Log::warning('Driver Testing Store - File data missing path', ['fileData' => $fileData]);
                    }
                }
            } else {
                Log::warning('Driver Testing Store - No valid files data found in JSON');
            }
        } else {
            Log::info('Driver Testing Store - No files to process');
        }
        
        // Generate PDF
        $pdf = $this->generatePDF($driverTesting);
        $testingId = $driverTesting->getKey(); // Usar getKey() en lugar de acceder directamente a ->id
        $pdfPath = storage_path('app/public/driver_testings/driver_testing_' . $testingId . '.pdf');
        
        // Make sure directory exists
        $directory = storage_path('app/public/driver_testings');
        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }
        
        // Save PDF
        file_put_contents($pdfPath, $pdf->output());
        
        // Attach PDF to media library
        $driverTesting->addMedia($pdfPath)
            ->toMediaCollection('drug_test_pdf');
        
        // Send email to driver with PDF attachment
        $this->sendEmailToDriver($driverTesting);
        
        // Obtener el ID numérico del objeto
        $testingId = $driverTesting->getKey();
        
        return redirect()->route('admin.driver-testings.show', ['driverTesting' => $testingId])
            ->with('success', 'Drug test created successfully. PDF has been generated and emailed to the driver.');
    }

    /**
     * Display the specified resource.
     */
    public function show(DriverTesting $driverTesting)
    {
        // Eager load all necessary relationships
        $driverTesting->load([
            'carrier',
            'userDriverDetail.user',
            'userDriverDetail.licenses' => function($query) {
                $query->where('status', 'active')
                      ->orderBy('expiration_date', 'desc');
            },
            'createdBy',
            'updatedBy',
            'media'
        ]);
        
        // Log warnings for missing relationships
        if (!$driverTesting->carrier) {
            Log::warning("Testing {$driverTesting->id} has no carrier relationship");
        }
        
        if (!$driverTesting->userDriverDetail) {
            Log::warning("Testing {$driverTesting->id} has no driver relationship");
        }
        
        // Query related testings for history display (last 5 tests for same driver)
        $testHistory = collect();
        if ($driverTesting->userDriverDetail) {
            $testHistory = DriverTesting::where('user_driver_detail_id', $driverTesting->user_driver_detail_id)
                ->where('id', '!=', $driverTesting->id)
                ->orderBy('test_date', 'desc')
                ->limit(5)
                ->get();
        }
        
        // Ruta relativa para el PDF si existe
        $pdfUrl = $driverTesting->getFirstMediaUrl('drug_test_pdf');
        
        // Debug logs para diagnosticar el problema del iframe
        Log::info('DriverTesting Show - Debug Info', [
            'testing_id' => $driverTesting->id,
            'pdf_url' => $pdfUrl,
            'media_count' => $driverTesting->getMedia('drug_test_pdf')->count(),
            'has_pdf_media' => $driverTesting->hasMedia('drug_test_pdf'),
            'pdf_media_info' => $driverTesting->getFirstMedia('drug_test_pdf') ? [
                'id' => $driverTesting->getFirstMedia('drug_test_pdf')->id,
                'file_name' => $driverTesting->getFirstMedia('drug_test_pdf')->file_name,
                'mime_type' => $driverTesting->getFirstMedia('drug_test_pdf')->mime_type,
                'size' => $driverTesting->getFirstMedia('drug_test_pdf')->size,
                'disk' => $driverTesting->getFirstMedia('drug_test_pdf')->disk,
                'path' => $driverTesting->getFirstMedia('drug_test_pdf')->getPath(),
                'url' => $driverTesting->getFirstMedia('drug_test_pdf')->getUrl()
            ] : null
        ]);
        
        return view('admin.driver-testings.show', compact('driverTesting', 'pdfUrl', 'testHistory'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(DriverTesting $driverTesting)
    {
        $driverTesting->load('userDriverDetail.user', 'userDriverDetail.carrier');
        $locations = DriverTesting::getLocations();
        $statuses = DriverTesting::getStatuses();
        $testTypes = DriverTesting::getDrugTestTypes();
        $testResults = DriverTesting::getTestResults();
        $billOptions = DriverTesting::getBillOptions();
        
        // Cache active carrier list for 30 minutes (1800 seconds)
        $carriers = Cache::remember('driver_testing_active_carriers', 1800, function () {
            return Carrier::where('status', 1)->orderBy('name')->get();
        });
        
        return view('admin.driver-testings.edit', compact('driverTesting', 'locations', 'statuses', 'testTypes', 'testResults', 'billOptions', 'carriers'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateDriverTestingRequest $request, DriverTesting $driverTesting)
    {
        // Log para debug
        Log::info('Driver Testing Update - Request data', ['files' => $request->driver_testing_files]);
        
        // Actualizar los campos básicos
        $driverTesting->fill($request->all());
        $driverTesting->updated_by = Auth::id();
        $driverTesting->save();

        // Procesar archivos adjuntos si existen
        if ($request->has('driver_testing_files') && !empty($request->driver_testing_files)) {
            $filesData = json_decode($request->driver_testing_files, true);

            Log::info('Driver Testing Update - Files data decoded', ['filesData' => $filesData]);

            if (is_array($filesData) && count($filesData) > 0) {
                $driverId = $driverTesting->userDriverDetail->id;
                $testingId = $driverTesting->getKey();

                // Crear el directorio de destino si no existe
                $destinationDir = "public/driver/{$driverId}/testing/{$testingId}";

                if (!Storage::exists($destinationDir)) {
                    Storage::makeDirectory($destinationDir);
                }

                foreach ($filesData as $fileData) {
                    // Si el archivo tiene un path temporal, es un archivo recién subido
                    if (isset($fileData['path']) && !empty($fileData['path'])) {
                        // Obtener el archivo temporal del disco temporal
                        $tempPath = storage_path('app/' . $fileData['path']);

                        Log::info('Driver Testing Update - Processing file', [
                            'tempPath' => $tempPath,
                            'originalName' => $fileData['original_name']
                        ]);

                        if (file_exists($tempPath)) {
                            // Verificar si el archivo ya existe para evitar duplicados
                            $fileExists = $driverTesting->getMedia('document_attachments')
                                ->where('name', $fileData['original_name'])
                                ->first();

                            if (!$fileExists) {
                                // Guardar en la colección de media para este registro
                                $driverTesting->addMedia($tempPath)
                                    ->usingName($fileData['original_name'])
                                    ->preservingOriginal()
                                    ->toMediaCollection('document_attachments');

                                // Log de éxito
                                Log::info('Driver Testing Update - File successfully added to media collection', [
                                    'testing_id' => $testingId,
                                    'filename' => $fileData['original_name']
                                ]);
                            } else {
                                Log::info('Driver Testing Update - File already exists, skipping', [
                                    'filename' => $fileData['original_name']
                                ]);
                            }
                        } else {
                            // Log de error si el archivo no existe
                            Log::error('Driver Testing Update - Temp file does not exist', [
                                'tempPath' => $tempPath,
                                'filename' => $fileData['original_name']
                            ]);
                        }
                    } else {
                        Log::warning('Driver Testing Update - File data missing path', ['fileData' => $fileData]);
                    }
                }
            } else {
                Log::warning('Driver Testing Update - No valid files data found in JSON');
            }
        } else {
            Log::info('Driver Testing Update - No files to process');
        }

        // Verificar si ha cambiado el estatus para notificar
        if ($request->status != 'pending' && $request->status != $driverTesting->getOriginal('status')) {
            // Status changed, might want to notify relevant parties here
            // TODO: Implementar notificación por cambio de estatus
        }
        
        // Refrescar el modelo desde la base de datos para asegurar que tiene los datos más recientes
        $driverTesting->refresh();
        
        // Regenerar el PDF con la información actualizada
        $pdf = $this->generatePDF($driverTesting);
        $testingId = $driverTesting->getKey();
        $pdfPath = storage_path('app/public/driver_testings/driver_testing_' . $testingId . '.pdf');
        
        // Asegurar que el directorio existe
        $directory = storage_path('app/public/driver_testings');
        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }
        
        // Guardar el PDF
        file_put_contents($pdfPath, $pdf->output());
        
        // Eliminar PDF anterior si existe
        $driverTesting->clearMediaCollection('drug_test_pdf');
        
        // Adjuntar el nuevo PDF a la biblioteca de medios en la colección correcta
        $driverTesting->addMedia($pdfPath)
            ->toMediaCollection('drug_test_pdf');
        
        // Enviar email al conductor con el PDF adjunto actualizado
        $this->sendEmailToDriver($driverTesting);
        
        return redirect()->route('admin.driver-testings.show', ['driverTesting' => $driverTesting->getKey()])
            ->with('success', 'Drug test updated successfully. PDF has been regenerated and emailed to the driver.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DriverTesting $driverTesting)
    {
        try {
            // Primero eliminar los archivos de media manualmente para evitar problemas de cascada
            $mediaItems = $driverTesting->getMedia('document_attachments');
            foreach ($mediaItems as $media) {
                // Usar DB::table para eliminar directamente el registro de media sin afectar al modelo principal
                DB::table('media')->where('id', $media->id)->delete();
            }
            
            // Eliminar el PDF generado si existe
            $pdfMedia = $driverTesting->getMedia('drug_test_pdf');
            foreach ($pdfMedia as $media) {
                DB::table('media')->where('id', $media->id)->delete();
            }
            
            // Ahora eliminar el registro principal
            $driverTesting->delete();
            
            return redirect()->route('admin.driver-testings.index')
                ->with('success', 'Drug test deleted successfully.');
        } catch (\Exception $e) {
            Log::error('Error deleting driver testing: ' . $e->getMessage());
            return redirect()->route('admin.driver-testings.index')
                ->with('error', 'Error deleting drug test. Please try again.');
        }
    }

    /**
     * Generate a PDF for the driver testing record
     */
    private function generatePDF(DriverTesting $driverTesting)
    {
        $driverTesting->load('userDriverDetail.user', 'userDriverDetail.carrier');
        
        $pdf = PDF::loadView('admin.driver-testings.pdf', [
            'driverTesting' => $driverTesting,
        ]);
        
        return $pdf;
    }

    /**
     * Send email to driver with PDF attachment
     */
    private function sendEmailToDriver(DriverTesting $driverTesting)
    {
        $driverTesting->load('userDriverDetail.user');
        $driverEmail = $driverTesting->userDriverDetail->user->email;
        $driverName = $driverTesting->userDriverDetail->user->name;
        
        $pdfMedia = $driverTesting->getFirstMedia('drug_test_pdf');
        
        if ($pdfMedia) {
            // TODO: Implementar envío de email con el PDF adjunto
            // Mail::to($driverEmail)->send(new DriverTestingNotification($driverTesting, $pdfMedia->getPath()));
        }
    }

    /**
     * Download PDF for a driver testing record
     */
    public function downloadPdf(DriverTesting $driverTesting): BinaryFileResponse
    {
        $media = $driverTesting->getFirstMedia('drug_test_pdf');
        if (!$media) {
            abort(404, 'PDF not found');
        }

        return response()->download($media->getPath(), $media->file_name);
    }
    
    /**
     * Regenerate PDF for a driver testing record
     */
    public function regeneratePdf(DriverTesting $driverTesting)
    {
        // Refrescar el modelo desde la base de datos para asegurar que tiene los datos más recientes
        $driverTesting->refresh();
        
        // Regenerar el PDF con la información actualizada
        $pdf = $this->generatePDF($driverTesting);
        $testingId = $driverTesting->getKey();
        $pdfPath = storage_path('app/public/driver_testings/driver_testing_' . $testingId . '.pdf');
        
        // Asegurar que el directorio existe
        $directory = storage_path('app/public/driver_testings');
        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }
        
        // Guardar el PDF
        file_put_contents($pdfPath, $pdf->output());
        
        // Eliminar PDF anterior si existe
        $driverTesting->clearMediaCollection('drug_test_pdf');
        
        // Adjuntar el nuevo PDF a la biblioteca de medios en la colección correcta
        $driverTesting->addMedia($pdfPath)
            ->toMediaCollection('drug_test_pdf');
        
        return redirect()->route('admin.driver-testings.show', ['driverTesting' => $driverTesting->getKey()])
            ->with('success', 'PDF has been regenerated successfully with the updated template.');
    }
    

    
    /**
     * Get drivers for a specific carrier (API endpoint)
     */
    public function getCarrierDrivers($carrierId)
    {
        $drivers = UserDriverDetail::where('carrier_id', $carrierId)
            ->with('user:id,name,email')
            ->get();
            
        return response()->json([
            'status' => 'success',
            'drivers' => $drivers
        ]);
    }
    
    /**
     * Search carriers by name (API endpoint)
     */
    public function searchCarriers(Request $request)
    {
        $query = $request->input('query', '');
        
        $carriers = Carrier::where('name', 'like', "%{$query}%")
            ->orWhere('usdot', 'like', "%{$query}%")
            ->orWhere('mc', 'like', "%{$query}%")
            ->select('id', 'name', 'usdot', 'mc', 'dot_pin')
            ->orderBy('name')
            ->limit(10)
            ->get();
            
        return response()->json([
            'status' => 'success',
            'carriers' => $carriers
        ]);
    }
    
    /**
     * Get drivers by carrier (API endpoint)
     */
    public function getDriversByCarrier(Carrier $carrier)
    {
        // Generate cache key based on carrier ID
        $cacheKey = "driver_testing_drivers_carrier_{$carrier->id}";
        
        // Cache driver list for 15 minutes (900 seconds)
        $processedDrivers = Cache::remember($cacheKey, 900, function () use ($carrier) {
            // Obtener conductores activos para el carrier especificado
            $drivers = UserDriverDetail::where('carrier_id', $carrier->id)
                ->where('status', 1) // Solo conductores activos
                ->with([
                    'user' => function($query) {
                        $query->select('id', 'name', 'middle_name', 'last_name', 'email', 'phone', 'date_of_birth');
                    },
                    'licenses' => function($query) {
                        $query->where('status', 'active')
                              ->select('id', 'user_driver_detail_id', 'license_number', 'license_class', 'license_state', 'expiration_date')
                              ->orderBy('created_at', 'desc');
                    }
                ])
                ->get();
                
            // Crear un array con los datos procesados
            $processedDrivers = [];
            
            foreach ($drivers as $driver) {
                // Obtener la licencia activa (la primera en la colección)
                $license = $driver->licenses->first();
                
                // Formatear nombre completo
                $fullName = trim(($driver->user->name ?? '') . ' ' . ($driver->user->middle_name ?? '') . ' ' . ($driver->user->last_name ?? ''));
                
                // Asegurar que todos los campos existan en el formato que espera el JavaScript
                $formattedDriver = [
                    'id' => $driver->id,
                    'full_name' => $fullName,
                    'first_name' => $driver->user->name ?? '',
                    'middle_name' => $driver->user->middle_name ?? '',
                    'last_name' => $driver->user->last_name ?? '',
                    'email' => $driver->user->email ?? 'N/A',
                    'phone' => $driver->user->phone ?? 'N/A',
                    'license_number' => $license ? $license->license_number : 'N/A',
                    'license_class' => $license ? $license->license_class : 'N/A',
                    'license_expiration_formatted' => $license && $license->expiration_date 
                        ? date('m/d/Y', strtotime($license->expiration_date)) 
                        : 'N/A'
                ];
                
                $processedDrivers[] = $formattedDriver;
            }
            
            return $processedDrivers;
        });

        // Devolver la respuesta en el formato que espera el JavaScript
        return response()->json([
            'status' => 'success',
            'drivers' => $processedDrivers
        ]);
    }
    
    /**
     * Get driver details (API endpoint)
     */
    public function getDriverDetails(UserDriverDetail $driverDetail)
    {
        $driverDetail->load([
            'user:id,name,middle_name,last_name,email,phone', 
            'carrier:id,name,usdot', 
            'licenses' => function($query) {
                $query->where('status', 'active')
                      ->select('id', 'user_driver_detail_id', 'license_number', 'license_class', 'license_state', 'expiration_date')
                      ->orderBy('created_at', 'desc');
            }
        ]);
        
        // Obtener la licencia activa (la primera en la colección, ya que están ordenadas por created_at desc)
        $license = $driverDetail->licenses->first();
        
        $driver = [
            'id' => $driverDetail->id,
            'first_name' => $driverDetail->user->name,
            'middle_name' => $driverDetail->user->middle_name ?? '',
            'last_name' => $driverDetail->user->last_name ?? '',
            'name' => trim($driverDetail->user->name . ' ' . ($driverDetail->user->middle_name ?? '') . ' ' . ($driverDetail->user->last_name ?? '')),
            'email' => $driverDetail->user->email,
            'phone' => $driverDetail->user->phone ?? 'N/A',
            'license' => $license ? $license->license_number : 'N/A',
            'license_class' => $license ? $license->license_class : '',
            'license_state' => $license ? $license->license_state : 'N/A',
            'license_expiration' => $license && $license->expiration_date 
                ? date('m/d/Y', strtotime($license->expiration_date)) 
                : 'N/A',
            'carrier' => [
                'id' => $driverDetail->carrier->id,
                'name' => $driverDetail->carrier->name,
                'usdot' => $driverDetail->carrier->usdot,
            ],
        ];
        
        // Registrar en el log para depuración
        \Illuminate\Support\Facades\Log::debug('Driver details requested', [
            'driver_id' => $driverDetail->id,
            'driver_data' => $driver,
            'has_license' => $license ? true : false
        ]);
        
        return response()->json([
            'status' => 'success',
            'driver' => $driver
        ]);
    }

    /**
     * Display test history for a specific driver
     */
    public function driverHistory(Request $request, UserDriverDetail $userDriverDetail)
    {
        $userDriverDetail->load('user', 'carrier');
        
        // Debug
        \Log::info('Driver History accessed', [
            'driver_id' => $userDriverDetail->id,
            'driver_name' => $userDriverDetail->user->name ?? 'N/A'
        ]);
        
        // Build query for driver's tests
        $query = DriverTesting::where('user_driver_detail_id', $userDriverDetail->id)
            ->with(['carrier', 'createdBy', 'updatedBy']);
        
        // Apply filters
        if ($request->filled('test_result')) {
            $query->where('test_result', $request->test_result);
        }
        
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->filled('test_type')) {
            $query->where('test_type', $request->test_type);
        }
        
        if ($request->filled('date_from')) {
            $query->whereDate('test_date', '>=', $request->date_from);
        }
        
        if ($request->filled('date_to')) {
            $query->whereDate('test_date', '<=', $request->date_to);
        }
        
        // Order by test date descending
        $tests = $query->orderBy('test_date', 'desc')->paginate(15);
        
        // Get filter options
        $testResults = DriverTesting::getTestResults();
        $statuses = DriverTesting::getStatuses();
        $testTypes = DriverTesting::getDrugTestTypes();
        
        return view('admin.driver-testings.history', compact(
            'userDriverDetail',
            'tests',
            'testResults',
            'statuses',
            'testTypes'
        ));
    }

    /**
     * Upload test results files
     */
    public function uploadResults(Request $request, DriverTesting $driverTesting)
    {
        $request->validate([
            'results' => 'required|array|min:1',
            'results.*' => 'file|mimes:pdf,jpg,jpeg,png,doc,docx|max:10240', // 10MB max
        ]);

        try {
            $uploadedCount = 0;
            
            foreach ($request->file('results') as $file) {
                $driverTesting->addMedia($file)
                    ->toMediaCollection('test_results');
                $uploadedCount++;
            }

            // Change status to Pending Review when results are uploaded
            $driverTesting->status = 'Pending Review';
            $driverTesting->save();

            Log::info('Test results uploaded', [
                'testing_id' => $driverTesting->id,
                'files_count' => $uploadedCount,
                'uploaded_by' => Auth::id(),
                'new_status' => 'Pending Review'
            ]);

            return redirect()->route('admin.driver-testings.show', $driverTesting->id)
                ->with('success', "Successfully uploaded {$uploadedCount} file(s). Status changed to Pending Review.");
                
        } catch (\Exception $e) {
            Log::error('Error uploading test results', [
                'testing_id' => $driverTesting->id,
                'error' => $e->getMessage()
            ]);
            
            return redirect()->back()
                ->with('error', 'Error uploading files. Please try again.');
        }
    }
}
