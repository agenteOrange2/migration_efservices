<?php

namespace App\Http\Controllers\Carrier;

use App\Http\Controllers\Controller;
use App\Models\UserDriverDetail;
use App\Models\Admin\Driver\DriverTesting;
use Illuminate\Http\Request;
use App\Http\Requests\StoreDriverTestingRequest;
use App\Http\Requests\UpdateDriverTestingRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class CarrierDriverTestingsController extends Controller
{
    /**
     * Mostrar la lista de pruebas de los conductores del carrier.
     */
    public function index(Request $request)
    {
        $carrier = Auth::user()->carrierDetails->carrier;
        
        $query = DriverTesting::query()
            ->with(['userDriverDetail.user'])
            ->whereHas('userDriverDetail', function ($q) use ($carrier) {
                $q->where('carrier_id', $carrier->id);
            });

        // Aplicar filtros
        if ($request->filled('search_term')) {
            $query->where('test_type', 'like', '%' . $request->search_term . '%')
                ->orWhere('notes', 'like', '%' . $request->search_term . '%');
        }

        if ($request->filled('driver_filter')) {
            $query->where('user_driver_detail_id', $request->driver_filter);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('test_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('test_date', '<=', $request->date_to);
        }

        if ($request->filled('test_type')) {
            $query->where('test_type', $request->test_type);
        }

        if ($request->filled('test_result')) {
            $query->where('test_result', $request->test_result);
        }

        // Ordenar resultados
        $sortField = $request->get('sort_field', 'test_date');
        $sortDirection = $request->get('sort_direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        $testings = $query->paginate(15);
        $drivers = UserDriverDetail::where('carrier_id', $carrier->id)
            ->with('user')
            ->get();

        // Obtener valores únicos para los filtros de desplegable
        $testTypes = DriverTesting::whereHas('userDriverDetail', function ($q) use ($carrier) {
                $q->where('carrier_id', $carrier->id);
            })
            ->distinct()
            ->pluck('test_type')
            ->filter()
            ->toArray();
            
        $testResults = DriverTesting::whereHas('userDriverDetail', function ($q) use ($carrier) {
                $q->where('carrier_id', $carrier->id);
            })
            ->distinct()
            ->pluck('test_result')
            ->filter()
            ->toArray();

        return view('carrier.drivers.testings.index', compact('testings', 'drivers', 'carrier', 'testTypes', 'testResults'));
    }

    /**
     * Mostrar el historial de pruebas de un conductor específico.
     */
    public function driverHistory(UserDriverDetail $driver, Request $request)
    {
        $carrier = Auth::user()->carrierDetails->carrier;
        
        // Verificar que el conductor pertenezca al carrier del usuario autenticado
        if ((int) $driver->carrier_id !== (int) $carrier->id) {
            return redirect()->route('carrier.drivers.testings.index')
                ->with('error', 'No tienes acceso a este conductor.');
        }
        
        // Load driver user relationship
        $driver->load('user');
        
        $query = DriverTesting::where('user_driver_detail_id', $driver->id);

        // Aplicar filtros si existen
        if ($request->filled('search_term')) {
            $query->where('test_type', 'like', '%' . $request->search_term . '%')
                ->orWhere('notes', 'like', '%' . $request->search_term . '%');
        }

        if ($request->filled('test_type')) {
            $query->where('test_type', $request->test_type);
        }

        if ($request->filled('test_result')) {
            $query->where('test_result', $request->test_result);
        }

        // Ordenar resultados
        $sortField = $request->get('sort_field', 'test_date');
        $sortDirection = $request->get('sort_direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        $testings = $query->paginate(10);
        
        // Obtener valores únicos para los filtros de desplegable
        $testTypes = DriverTesting::where('user_driver_detail_id', $driver->id)
            ->distinct()
            ->pluck('test_type')
            ->filter()
            ->toArray();
            
        $testResults = DriverTesting::where('user_driver_detail_id', $driver->id)
            ->distinct()
            ->pluck('test_result')
            ->filter()
            ->toArray();

        return view('carrier.drivers.testings.driver_history', compact('driver', 'testings', 'carrier', 'testTypes', 'testResults'));
    }

    /**
     * Mostrar el formulario para crear una nueva prueba.
     */
    public function create()
    {
        $carrier = Auth::user()->carrierDetails->carrier;
        $drivers = UserDriverDetail::where('carrier_id', $carrier->id)
            ->with(['user', 'licenses'])
            ->get();
            
        return view('carrier.drivers.testings.create', compact('drivers', 'carrier'));
    }

    /**
     * Almacenar una nueva prueba.
     */
    public function store(Request $request)
    {
        $carrier = Auth::user()->carrierDetails->carrier;
        
        $validated = $request->validate([
            'user_driver_detail_id' => 'required|exists:user_driver_details,id',
            'test_date' => 'required|date',
            'test_type' => 'required|string|max:255',
            'test_result' => 'nullable|string|max:255',
            'status' => 'nullable|string|max:255',
            'administered_by' => 'required|string|max:255',
            'mro' => 'required|string|max:255',
            'requester_name' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'scheduled_time' => 'required|date_format:Y-m-d\TH:i',
            'bill_to' => 'required|string|max:255',
            'notes' => 'nullable|string',
            'next_test_due' => 'nullable|date',
            'is_random_test' => 'boolean',
            'is_post_accident_test' => 'boolean',
            'is_reasonable_suspicion_test' => 'boolean',
            'is_pre_employment_test' => 'boolean',
            'is_follow_up_test' => 'boolean',
            'is_return_to_duty_test' => 'boolean',
            'is_other_reason_test' => 'boolean',
            'other_reason_description' => 'nullable|string|max:255',
            'attachments.*' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:10240',
        ]);
        
        // Verificar que el conductor pertenezca al carrier del usuario autenticado
        $driver = UserDriverDetail::findOrFail($validated['user_driver_detail_id']);
        if ((int) $driver->carrier_id !== (int) $carrier->id) {
            return redirect()->route('carrier.drivers.testings.index')
                ->with('error', 'No tienes acceso a este conductor.');
        }

        // Convertir checkboxes a valores booleanos
        $validated['is_random_test'] = $request->boolean('is_random_test');
        $validated['is_post_accident_test'] = $request->boolean('is_post_accident_test');
        $validated['is_reasonable_suspicion_test'] = $request->boolean('is_reasonable_suspicion_test');
        $validated['is_pre_employment_test'] = $request->boolean('is_pre_employment_test');
        $validated['is_follow_up_test'] = $request->boolean('is_follow_up_test');
        $validated['is_return_to_duty_test'] = $request->boolean('is_return_to_duty_test');
        $validated['is_other_reason_test'] = $request->boolean('is_other_reason_test');
        
        // Add carrier_id and audit fields
        $validated['carrier_id'] = $carrier->id;
        $validated['created_by'] = Auth::id();

        try {
            $testing = DriverTesting::create($validated);
            
            // Handle file uploads
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $testing->addMedia($file)
                        ->toMediaCollection('document_attachments');
                }
            }
            
            // Generate PDF
            try {
                $pdf = $this->generatePDF($testing);
                $fileName = 'driver_testing_' . $testing->id . '_' . time() . '.pdf';
                $testing->addMediaFromString($pdf->output())
                    ->usingFileName($fileName)
                    ->toMediaCollection('drug_test_pdf');
            } catch (\Exception $e) {
                Log::error('Error al generar PDF durante creación', [
                    'error' => $e->getMessage(),
                    'testing_id' => $testing->id
                ]);
                // Continue without PDF - don't fail the entire operation
            }
            
            Session::flash('success', 'Registro de prueba añadido exitosamente.');
            
            // Redirigir a la página apropiada
            if ($request->has('redirect_to_driver')) {
                return redirect()->route('carrier.drivers.testings.driver_history', $validated['user_driver_detail_id']);
            }
            
            return redirect()->route('carrier.drivers.testings.show', $testing);
            
        } catch (\Exception $e) {
            Log::error('Error al crear registro de prueba', [
                'error' => $e->getMessage(),
                'data' => $validated
            ]);
            
            return redirect()->back()
                ->with('error', 'Error al crear registro de prueba: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Mostrar el formulario para editar una prueba.
     */
    public function edit(DriverTesting $testing)
    {
        $carrier = Auth::user()->carrierDetails->carrier;
        
        // Verificar que el conductor pertenezca al carrier del usuario autenticado
        if (!$this->verifyCarrierOwnership($testing)) {
            return redirect()->route('carrier.drivers.testings.index')
                ->with('error', 'No tienes acceso a este registro de prueba.');
        }
        
        // Load relationships including licenses for driver details display
        $testing->load(['userDriverDetail.user', 'userDriverDetail.licenses', 'carrier']);
        
        $drivers = UserDriverDetail::where('carrier_id', $carrier->id)
            ->with(['user', 'licenses'])
            ->get();
            
        return view('carrier.drivers.testings.edit', compact('testing', 'drivers', 'carrier'));
    }

    /**
     * Actualizar una prueba.
     */
    public function update(Request $request, DriverTesting $testing)
    {
        $carrier = Auth::user()->carrierDetails->carrier;
        
        // Verificar que el conductor pertenezca al carrier del usuario autenticado
        if (!$this->verifyCarrierOwnership($testing)) {
            return redirect()->route('carrier.drivers.testings.index')
                ->with('error', 'No tienes acceso a este registro de prueba.');
        }
        
        $validated = $request->validate([
            'user_driver_detail_id' => 'required|exists:user_driver_details,id',
            'test_date' => 'required|date',
            'test_type' => 'required|string|max:255',
            'test_result' => 'required|string|max:255',
            'status' => 'nullable|string|max:255',
            'administered_by' => 'required|string|max:255',
            'mro' => 'nullable|string|max:255',
            'requester_name' => 'nullable|string|max:255',
            'location' => 'required|string|max:255',
            'scheduled_time' => 'nullable|date_format:Y-m-d\TH:i',
            'bill_to' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'next_test_due' => 'nullable|date',
            'is_random_test' => 'boolean',
            'is_post_accident_test' => 'boolean',
            'is_reasonable_suspicion_test' => 'boolean',
            'is_pre_employment_test' => 'boolean',
            'is_follow_up_test' => 'boolean',
            'is_return_to_duty_test' => 'boolean',
            'is_other_reason_test' => 'boolean',
            'other_reason_description' => 'nullable|string|max:255',
            'attachments.*' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:10240',
        ]);
        
        // Verificar que el conductor pertenezca al carrier del usuario autenticado
        $driver = UserDriverDetail::findOrFail($validated['user_driver_detail_id']);
        if ((int) $driver->carrier_id !== (int) $carrier->id) {
            return redirect()->route('carrier.drivers.testings.index')
                ->with('error', 'No tienes acceso a este conductor.');
        }

        // Convertir checkboxes a valores booleanos
        $validated['is_random_test'] = $request->boolean('is_random_test');
        $validated['is_post_accident_test'] = $request->boolean('is_post_accident_test');
        $validated['is_reasonable_suspicion_test'] = $request->boolean('is_reasonable_suspicion_test');
        $validated['is_pre_employment_test'] = $request->boolean('is_pre_employment_test');
        $validated['is_follow_up_test'] = $request->boolean('is_follow_up_test');
        $validated['is_return_to_duty_test'] = $request->boolean('is_return_to_duty_test');
        $validated['is_other_reason_test'] = $request->boolean('is_other_reason_test');
        
        // Add audit field
        $validated['updated_by'] = Auth::id();

        try {
            $testing->update($validated);
            
            // Handle new file uploads to document_attachments collection
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $testing->addMedia($file)
                        ->toMediaCollection('document_attachments');
                }
            }
            
            // Regenerate PDF with updated information
            try {
                // Delete old PDF before storing new one
                $testing->clearMediaCollection('drug_test_pdf');
                
                // Generate new PDF
                $pdf = $this->generatePDF($testing);
                $fileName = 'driver_testing_' . $testing->id . '_' . time() . '.pdf';
                $testing->addMediaFromString($pdf->output())
                    ->usingFileName($fileName)
                    ->toMediaCollection('drug_test_pdf');
            } catch (\Exception $e) {
                Log::error('Error al regenerar PDF durante actualización', [
                    'error' => $e->getMessage(),
                    'testing_id' => $testing->id
                ]);
                // Continue without PDF regeneration - don't fail the entire operation
            }
            
            Session::flash('success', 'Registro de prueba actualizado exitosamente.');
            
            // Redirigir a la página apropiada
            if ($request->has('redirect_to_driver')) {
                return redirect()->route('carrier.drivers.testings.driver_history', $testing->user_driver_detail_id);
            }
            
            return redirect()->route('carrier.drivers.testings.show', $testing);
            
        } catch (\Exception $e) {
            Log::error('Error al actualizar registro de prueba', [
                'error' => $e->getMessage(),
                'testing_id' => $testing->id
            ]);
            
            return redirect()->back()
                ->with('error', 'Error al actualizar registro de prueba: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Eliminar una prueba.
     */
    public function destroy(DriverTesting $testing)
    {
        $carrier = Auth::user()->carrierDetails->carrier;
        
        // Verificar que el conductor pertenezca al carrier del usuario autenticado
        if (!$this->verifyCarrierOwnership($testing)) {
            return redirect()->route('carrier.drivers.testings.index')
                ->with('error', 'No tienes acceso a este registro de prueba.');
        }
        
        try {
            $driverId = $testing->user_driver_detail_id;
            
            // Delete all media files before deleting the record
            $testing->clearMediaCollection('drug_test_pdf');
            $testing->clearMediaCollection('document_attachments');
            $testing->clearMediaCollection('test_results');
            $testing->clearMediaCollection('test_certificates');
            $testing->clearMediaCollection('test_authorization');
            
            $testing->delete();
            
            Session::flash('success', 'Registro de prueba eliminado exitosamente.');
            
            // Determinar la ruta de retorno basado en la URL de referencia
            $referer = request()->headers->get('referer');
            if (strpos($referer, 'driver_history') !== false) {
                return redirect()->route('carrier.drivers.testings.driver_history', $driverId);
            }
            
            return redirect()->route('carrier.drivers.testings.index');
            
        } catch (\Exception $e) {
            Log::error('Error al eliminar registro de prueba', [
                'error' => $e->getMessage(),
                'testing_id' => $testing->id
            ]);
            
            return redirect()->route('carrier.drivers.testings.index')
                ->with('error', 'Error al eliminar registro de prueba: ' . $e->getMessage());
        }
    }

    /**
     * Mostrar los detalles de una prueba específica.
     */
    public function show(DriverTesting $testing)
    {
        $carrier = Auth::user()->carrierDetails->carrier;
        
        // Verificar que el conductor pertenezca al carrier del usuario autenticado
        if (!$this->verifyCarrierOwnership($testing)) {
            return redirect()->route('carrier.drivers.testings.index')
                ->with('error', 'No tienes acceso a este registro de prueba.');
        }
        
        // Load all relationships
        $testing->load([
            'userDriverDetail.user',
            'userDriverDetail.licenses',
            'carrier',
            'createdBy',
            'updatedBy'
        ]);
        
        // Get related testing history (last 5 tests for same driver)
        $relatedHistory = DriverTesting::where('user_driver_detail_id', $testing->user_driver_detail_id)
            ->where('id', '!=', $testing->id)
            ->orderBy('test_date', 'desc')
            ->limit(5)
            ->get();
        
        // Get PDF URL if exists
        $pdfUrl = null;
        if ($testing->hasMedia('drug_test_pdf')) {
            $pdfUrl = $testing->getFirstMediaUrl('drug_test_pdf');
        }
        
        return view('carrier.drivers.testings.show', compact('testing', 'carrier', 'relatedHistory', 'pdfUrl'));
    }

    /**
     * Descargar el PDF de una prueba.
     */
    public function downloadPdf(DriverTesting $testing)
    {
        $carrier = Auth::user()->carrierDetails->carrier;
        
        // Verificar que el conductor pertenezca al carrier del usuario autenticado
        if (!$this->verifyCarrierOwnership($testing)) {
            abort(403, 'No tienes acceso a este registro de prueba.');
        }
        
        // Get PDF from media collection
        if (!$testing->hasMedia('drug_test_pdf')) {
            abort(404, 'El PDF no existe para este registro.');
        }
        
        $media = $testing->getFirstMedia('drug_test_pdf');
        $pathToFile = $media->getPath();
        
        return response()->download($pathToFile, $media->file_name, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    /**
     * Regenerar el PDF de una prueba.
     */
    public function regeneratePdf(DriverTesting $testing)
    {
        $carrier = Auth::user()->carrierDetails->carrier;
        
        // Verificar que el conductor pertenezca al carrier del usuario autenticado
        if (!$this->verifyCarrierOwnership($testing)) {
            return redirect()->route('carrier.drivers.testings.index')
                ->with('error', 'No tienes acceso a este registro de prueba.');
        }
        
        try {
            // Delete old PDF if exists
            $testing->clearMediaCollection('drug_test_pdf');
            
            // Generate new PDF
            $pdf = $this->generatePDF($testing);
            
            // Store new PDF
            $fileName = 'driver_testing_' . $testing->id . '_' . time() . '.pdf';
            $testing->addMediaFromString($pdf->output())
                ->usingFileName($fileName)
                ->toMediaCollection('drug_test_pdf');
            
            Session::flash('success', 'PDF regenerado exitosamente.');
            
            return redirect()->route('carrier.drivers.testings.show', $testing);
            
        } catch (\Exception $e) {
            Log::error('Error al regenerar PDF', [
                'error' => $e->getMessage(),
                'testing_id' => $testing->id
            ]);
            
            return redirect()->back()
                ->with('error', 'Error al regenerar PDF: ' . $e->getMessage());
        }
    }

    /**
     * Verificar que el registro de prueba pertenece al carrier del usuario autenticado.
     *
     * @param DriverTesting $testing
     * @return bool
     */
    private function verifyCarrierOwnership(DriverTesting $testing): bool
    {
        $carrier = Auth::user()->carrierDetails->carrier;
        return (int) $testing->userDriverDetail->carrier_id === (int) $carrier->id;
    }

    /**
     * Generar PDF para un registro de prueba.
     *
     * @param DriverTesting $testing
     * @return \Barryvdh\DomPDF\PDF
     */
    private function generatePDF(DriverTesting $testing)
    {
        // Load relationships if not already loaded
        $testing->load([
            'userDriverDetail.user',
            'carrier',
            'createdBy',
            'updatedBy'
        ]);
        
        // Generate PDF using DomPDF with pdf.blade.php template
        $pdf = Pdf::loadView('carrier.drivers.testings.pdf', [
            'testing' => $testing,
            'carrier' => $testing->carrier
        ]);
        
        return $pdf;
    }

    /**
     * Enviar email al conductor con el PDF adjunto (opcional - para uso futuro).
     *
     * @param DriverTesting $testing
     * @return void
     */
    private function sendEmailToDriver(DriverTesting $testing): void
    {
        // This method is a placeholder for future email notification functionality
        // Implementation would use Laravel's Mail facade to send emails
        // Example:
        // Mail::to($testing->userDriverDetail->user->email)
        //     ->send(new DriverTestingNotification($testing));
        
        Log::info('Email notification placeholder called', [
            'testing_id' => $testing->id,
            'driver_email' => $testing->userDriverDetail->user->email ?? 'N/A'
        ]);
    }

    /**
     * API endpoint to get active drivers for the authenticated carrier
     * Used by the JavaScript form to populate the driver dropdown
     */
    public function getActiveDrivers()
    {
        try {
            $carrier = Auth::user()->carrierDetails->carrier;
            
            // Get active drivers for this carrier (same logic as admin)
            // Note: Only filters by driver status (1 = active), not user status
            $drivers = UserDriverDetail::where('carrier_id', $carrier->id)
                ->where('status', 1) // Only active drivers
                ->with(['user' => function($query) {
                    $query->select('id', 'name', 'email');
                }, 'licenses'])
                ->get()
                ->map(function($driver) {
                    // Build full name using name from users and middle_name/last_name from user_driver_details
                    $nameParts = array_filter([
                        $driver->user->name ?? '',
                        $driver->middle_name,
                        $driver->last_name
                    ]);
                    $fullName = implode(' ', $nameParts);
                    
                    return [
                        'id' => $driver->id,
                        'full_name' => $fullName,
                        'first_name' => $driver->user->name ?? '',
                        'middle_name' => $driver->middle_name ?? '',
                        'last_name' => $driver->last_name ?? '',
                        'email' => $driver->user->email ?? '',
                        'phone' => $driver->phone ?? '',
                        'licenses' => $driver->licenses->map(function ($license) {
                            return [
                                'license_number' => $license->license_number,
                                'license_class' => $license->license_class,
                                'expiration_date' => $license->expiration_date,
                            ];
                        }),
                        'user' => [
                            'name' => $driver->user->name ?? '',
                            'middle_name' => $driver->middle_name ?? '',
                            'last_name' => $driver->last_name ?? '',
                            'email' => $driver->user->email ?? '',
                        ]
                    ];
                });

            return response()->json($drivers);
        } catch (\Exception $e) {
            Log::error('Error fetching active drivers for carrier', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'carrier_id' => Auth::user()->carrierDetails->carrier->id ?? null
            ]);
            
            return response()->json([
                'error' => 'Unable to load drivers. Please try again.'
            ], 500);
        }
    }

    /**
     * Upload test results files
     */
    public function uploadResults(Request $request, DriverTesting $testing)
    {
        $carrier = Auth::user()->carrierDetails->carrier;
        
        // Verify ownership
        if (!$this->verifyCarrierOwnership($testing)) {
            return redirect()->route('carrier.drivers.testings.index')
                ->with('error', 'No tienes acceso a este registro de prueba.');
        }
        
        $request->validate([
            'results' => 'required|array|min:1',
            'results.*' => 'file|mimes:pdf,jpg,jpeg,png,doc,docx|max:10240', // 10MB max
        ]);

        try {
            $uploadedCount = 0;
            
            foreach ($request->file('results') as $file) {
                $testing->addMedia($file)
                    ->toMediaCollection('test_results');
                $uploadedCount++;
            }

            // Change status to Pending Review when results are uploaded
            $testing->status = 'Pending Review';
            $testing->save();

            Log::info('Test results uploaded by carrier', [
                'testing_id' => $testing->id,
                'files_count' => $uploadedCount,
                'carrier_id' => $carrier->id,
                'uploaded_by' => Auth::id(),
                'new_status' => 'Pending Review'
            ]);

            return redirect()->route('carrier.drivers.testings.show', $testing->id)
                ->with('success', "Successfully uploaded {$uploadedCount} file(s). Status changed to Pending Review.");
                
        } catch (\Exception $e) {
            Log::error('Error uploading test results', [
                'testing_id' => $testing->id,
                'carrier_id' => $carrier->id,
                'error' => $e->getMessage()
            ]);
            
            return redirect()->back()
                ->with('error', 'Error uploading files. Please try again.');
        }
    }
}
