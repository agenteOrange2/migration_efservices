<?php

namespace App\Http\Controllers\Admin\Driver;

use App\Http\Controllers\Controller;
use App\Mail\EmploymentVerification;
use App\Models\Admin\Driver\DriverEmploymentCompany;
use App\Models\Admin\Driver\EmploymentVerificationToken;
use App\Models\UserDriverDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class EmploymentVerificationAdminController extends Controller
{
    /**
     * Muestra la lista de verificaciones de empleo
     */
    public function index(Request $request)
    {
        $query = DriverEmploymentCompany::query()
            ->with(['userDriverDetail.user', 'masterCompany', 'verificationTokens'])
            ->where('email_sent', true);
        
        // Filtros
        if ($request->has('status') && $request->status !== '') {
            if ($request->status === 'verified') {
                $query->where('verification_status', 'verified');
            } elseif ($request->status === 'rejected') {
                $query->where('verification_status', 'rejected');
            } elseif ($request->status === 'pending') {
                $query->whereNull('verification_status');
            }
        }
        
        if ($request->has('driver') && $request->driver !== '') {
            $driverId = $request->driver;
            $query->whereHas('userDriverDetail', function($q) use ($driverId) {
                $q->where('id', $driverId);
            });
        }
        
        // Order by the latest verification token created_at (most recent first)
        $employmentVerifications = $query
            ->select('driver_employment_companies.*')
            ->addSelect(['latest_token_date' => EmploymentVerificationToken::select('created_at')
                ->whereColumn('employment_company_id', 'driver_employment_companies.id')
                ->orderByDesc('created_at')
                ->limit(1)
            ])
            ->orderByDesc('latest_token_date')
            ->orderByDesc('driver_employment_companies.updated_at')
            ->paginate(15)
            ->withQueryString();
        
        $drivers = UserDriverDetail::with('user')
            ->whereHas('employmentCompanies', function($q) {
                $q->where('email_sent', true);
            })
            ->get();
        
        return view('admin.drivers.employment-verification.index', [
            'employmentVerifications' => $employmentVerifications,
            'drivers' => $drivers
        ]);
    }
    
    /**
     * Muestra los detalles de una verificación de empleo específica
     */
    public function show($id)
    {
        $employmentCompany = DriverEmploymentCompany::with([
            'userDriverDetail.user', 
            'masterCompany', 
            'verificationTokens',
            'media'
        ])->findOrFail($id);
        
        return view('admin.drivers.employment-verification.show', [
            'employmentCompany' => $employmentCompany
        ]);
    }
    
    /**
     * Permite reenviar un correo de verificación de empleo
     * (Método original + mejoras)
     */
    public function resendVerification($id)
    {
        $employmentCompany = DriverEmploymentCompany::with(['userDriverDetail.user', 'masterCompany'])
            ->findOrFail($id);
        
        // Validar que existe email
        if (!$employmentCompany->email) {
            return redirect()->back()
                ->with('error', 'Cannot send verification email: No email address provided.');
        }

        // Check if maximum attempts (3) have been reached
        $attemptCount = EmploymentVerificationToken::where('employment_company_id', $employmentCompany->id)->count();
        if ($attemptCount >= 3) {
            return redirect()->back()
                ->with('error', 'Maximum verification attempts (3) reached for this company. No more emails can be sent.');
        }
        
        // Always create a new token for each attempt
        $token = Str::random(64);
        
        $verificationToken = new EmploymentVerificationToken([
            'token' => $token,
            'driver_id' => $employmentCompany->user_driver_detail_id,
            'employment_company_id' => $employmentCompany->id,
            'email' => $employmentCompany->email,
            'expires_at' => now()->addDays(30),
        ]);
        
        $verificationToken->save();
        
        // Enviar el correo electrónico
        try {
            // Obtener el nombre de la empresa
            $companyName = $employmentCompany->masterCompany ? $employmentCompany->masterCompany->company_name : ($employmentCompany->company_name ?? 'Custom Company');
            
            // Obtener el nombre completo del conductor
            $driver = $employmentCompany->userDriverDetail;
            $driverName = $driver && $driver->user ? $driver->user->name . ' ' . ($driver->last_name ?? '') : 'Driver';
            
            // Preparar los datos de empleo para el correo
            $employmentData = [
                'positions_held' => $employmentCompany->positions_held,
                'employed_from' => $employmentCompany->employed_from,
                'employed_to' => $employmentCompany->employed_to,
                'reason_for_leaving' => $employmentCompany->reason_for_leaving,
                'subject_to_fmcsr' => $employmentCompany->subject_to_fmcsr,
                'safety_sensitive_function' => $employmentCompany->safety_sensitive_function
            ];
            
            Mail::to($employmentCompany->email)
                ->send(new EmploymentVerification(
                    $companyName,
                    $driverName,
                    $employmentData,
                    $token,
                    $employmentCompany->user_driver_detail_id,
                    $employmentCompany->id
                ));
            
            // Actualizar flag de email_sent
            $employmentCompany->update(['email_sent' => true]);

            // Count attempt number (after creating the new token)
            $attemptNumber = EmploymentVerificationToken::where('employment_company_id', $employmentCompany->id)->count();

            // Generate PDF for this attempt
            $pdfData = [
                'attemptNumber' => $attemptNumber,
                'attemptDate' => now()->format('m/d/Y'),
                'attemptTime' => now()->format('h:i:s A'),
                'emailSentTo' => $employmentCompany->email,
                'driverName' => $driverName,
                'driverId' => $employmentCompany->user_driver_detail_id,
                'companyName' => $companyName,
                'companyEmail' => $employmentCompany->email,
                'employedFrom' => $employmentCompany->employed_from ? $employmentCompany->employed_from->format('m/d/Y') : 'Not specified',
                'employedTo' => $employmentCompany->employed_to ? $employmentCompany->employed_to->format('m/d/Y') : 'Not specified',
                'positionsHeld' => $employmentCompany->positions_held ?? 'Not specified',
                'reasonForLeaving' => $employmentCompany->reason_for_leaving ?? 'Not specified',
                'token' => $token,
                'expiresAt' => now()->addDays(30)->format('m/d/Y h:i A'),
                'generatedAt' => now()->format('m/d/Y h:i:s A'),
            ];

            $pdf = PDF::loadView('employment-verification.resend-attempt-pdf', $pdfData);
            
            // Save PDF to temp file
            $companySlug = preg_replace('/[^a-zA-Z0-9]/', '_', $companyName);
            $pdfFileName = 'employment_verification_attempt_' . $attemptNumber . '_' . $companySlug . '_' . time() . '.pdf';
            $tempPath = storage_path('app/temp/' . $pdfFileName);
            
            // Ensure temp directory exists
            if (!file_exists(storage_path('app/temp'))) {
                mkdir(storage_path('app/temp'), 0755, true);
            }
            
            $pdf->save($tempPath);
            
            // Add PDF to driver's media collection
            if ($driver) {
                $driver->addMedia($tempPath)
                    ->usingFileName($pdfFileName)
                    ->usingName('Employment Verification Attempt #' . $attemptNumber . ' - ' . $companyName)
                    ->withCustomProperties([
                        'attempt_number' => $attemptNumber,
                        'company_name' => $companyName,
                        'company_id' => $employmentCompany->id,
                        'email_sent_to' => $employmentCompany->email,
                        'sent_at' => now()->toDateTimeString(),
                    ])
                    ->toMediaCollection('employment_verification_attempts');

                Log::info('Employment verification attempt PDF generated and saved', [
                    'driver_id' => $employmentCompany->user_driver_detail_id,
                    'company_id' => $employmentCompany->id,
                    'attempt_number' => $attemptNumber,
                    'pdf_file' => $pdfFileName
                ]);
            }
            
            Log::info('Admin resent employment verification email', [
                'employment_id' => $employmentCompany->id,
                'driver_id' => $employmentCompany->user_driver_detail_id,
                'email' => $employmentCompany->email,
                'attempt_number' => $attemptNumber,
                'admin_user' => auth()->user()->id ?? 'unknown'
            ]);
            
            return redirect()->back()
                ->with('success', 'Verification email sent successfully (Attempt #' . $attemptNumber . '/3).');
        } catch (\Exception $e) {
            Log::error('Error al reenviar correo de verificación de empleo', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'employment_company_id' => $employmentCompany->id
            ]);
            
            return redirect()->back()
                ->with('error', 'Error sending verification email: ' . $e->getMessage());
        }
    }
    
    /**
     * Resend verification email (alias for new routes)
     */
    public function resendVerificationEmail($employmentId)
    {
        return $this->resendVerification($employmentId);
    }
    
    /**
     * Toggle email_sent flag for a specific employment company
     */
    public function toggleEmailFlag($employmentId)
    {
        try {
            $employment = DriverEmploymentCompany::findOrFail($employmentId);
            
            // Toggle the email_sent flag
            $newStatus = !$employment->email_sent;
            $employment->update(['email_sent' => $newStatus]);
            
            Log::info('Admin toggled email_sent flag', [
                'employment_id' => $employment->id,
                'driver_id' => $employment->user_driver_detail_id,
                'old_status' => !$newStatus,
                'new_status' => $newStatus,
                'admin_user' => auth()->user()->id ?? 'unknown'
            ]);
            
            $message = $newStatus 
                ? 'Employment marked as email sent' 
                : 'Employment marked as email not sent';
            
            return redirect()->back()
                ->with('success', $message);
                
        } catch (\Exception $e) {
            Log::error('Admin failed to toggle email_sent flag', [
                'employment_id' => $employmentId,
                'error' => $e->getMessage(),
                'admin_user' => auth()->user()->id ?? 'unknown'
            ]);
            
            return redirect()->back()
                ->with('error', 'Failed to update email status: ' . $e->getMessage());
        }
    }
    
    /**
     * Permite marcar manualmente una verificación como verificada
     */
    public function markAsVerified(Request $request, $id)
    {
        $employmentCompany = DriverEmploymentCompany::findOrFail($id);
        
        $employmentCompany->verification_status = 'verified';
        $employmentCompany->verification_date = now();
        $employmentCompany->verification_notes = $request->notes ?? 'Verificado manualmente por administrador';
        $employmentCompany->verification_by = Auth::check() ? Auth::user()->name . ' (Admin)' : 'Administrador';
        $employmentCompany->save();
        
        return redirect()->route('admin.drivers.employment-verification.show', $id)
            ->with('success', 'La verificación de empleo ha sido marcada como verificada correctamente.');
    }
    
    /**
     * Permite marcar manualmente una verificación como rechazada
     */
    public function markAsRejected(Request $request, $id)
    {
        $employmentCompany = DriverEmploymentCompany::findOrFail($id);
        
        $employmentCompany->verification_status = 'rejected';
        $employmentCompany->verification_date = now();
        $employmentCompany->verification_notes = $request->notes ?? 'Rechazado manualmente por administrador';
        $employmentCompany->verification_by = Auth::check() ? Auth::user()->name . ' (Admin)' : 'Administrador';
        $employmentCompany->save();
        
        return redirect()->route('admin.drivers.employment-verification.show', $id)
            ->with('success', 'La verificación de empleo ha sido marcada como rechazada correctamente.');
    }
    
    /**
     * Verify employment (alias)
     */
    public function verify($id)
    {
        return $this->markAsVerified(request(), $id);
    }
    
    /**
     * Reject employment verification (alias)
     */
    public function reject($id)
    {
        return $this->markAsRejected(request(), $id);
    }
    
    /**
     * Procesa la subida de documentos de verificación de empleo
     */
    public function uploadDocument(Request $request, $id)
    {
        $employmentCompany = DriverEmploymentCompany::findOrFail($id);
        
        Log::info('Iniciando uploadDocument para verificación de empleo', [
            'employment_company_id' => $id,
            'request_data' => $request->all()
        ]);
        
        try {
            DB::beginTransaction();
            
            $uploadedCount = 0;
            $errors = [];
            
            // Verificar si estamos recibiendo archivos directos o JSON de Livewire
            if ($request->hasFile('documents')) {
                // Método tradicional con archivos directos
                $request->validate([
                    'documents' => 'required|array',
                    'documents.*' => 'file|max:10240|mimes:jpg,jpeg,png,pdf,doc,docx'
                ]);
                
                foreach ($request->file('documents') as $file) {
                    // Subir directamente a Media Library
                    $media = $employmentCompany->addMedia($file)
                        ->usingName('Employment Verification Manual')
                        ->usingFileName('Employment_verification_manual.pdf')
                        ->withCustomProperties([
                            'uploaded_by' => Auth::check() ? Auth::user()->name : 'Administrador',
                            'uploaded_at' => now()->format('Y-m-d H:i:s'),
                            'manual_upload' => true
                        ])
                        ->toMediaCollection('employment_verification_documents');
                    
                    $uploadedCount++;
                    
                    Log::info('Documento de verificación subido correctamente', [
                        'employment_company_id' => $employmentCompany->id,
                        'media_id' => $media->id,
                        'file_name' => $media->file_name
                    ]);
                }
            } elseif ($request->filled('livewire_files')) {
                // Método Livewire con archivos temporales
                $livewireFiles = json_decode($request->input('livewire_files'), true);
                
                Log::info('Procesando archivos de Livewire', [
                    'livewire_files' => $livewireFiles
                ]);
                
                if (!is_array($livewireFiles) || empty($livewireFiles)) {
                    return redirect()->back()->with('error', 'No se recibieron archivos válidos');
                }
                
                // Procesar los archivos temporales de Livewire
                foreach ($livewireFiles as $fileData) {
                    // Verificar que tenemos la información necesaria
                    if (!isset($fileData['path']) && !isset($fileData['tempPath'])) {
                        $errors[] = 'Datos de archivo incompletos';
                        Log::warning('Datos de archivo incompletos', ['file' => $fileData]);
                        continue;
                    }
                    
                    // Obtener la ruta del archivo temporal
                    $tempPath = isset($fileData['path']) ? $fileData['path'] : $fileData['tempPath'];
                    $fullPath = storage_path('app/' . $tempPath);
                    
                    // Verificar que el archivo temporal existe
                    if (!file_exists($fullPath)) {
                        // Intentar con la ruta directa a la carpeta temp
                        $tempPath = 'temp/' . basename($tempPath);
                        $fullPath = storage_path('app/' . $tempPath);
                        
                        if (!file_exists($fullPath)) {
                            $errors[] = "Archivo temporal no encontrado: " . ($fileData['name'] ?? $fileData['originalName'] ?? 'Desconocido');
                            Log::error('Archivo temporal no encontrado', [
                                'temp_path' => $tempPath,
                                'full_path' => $fullPath,
                                'file_data' => $fileData
                            ]);
                            continue;
                        }
                    }
                    
                    $fileName = $fileData['name'] ?? $fileData['originalName'] ?? basename($fullPath);
                    
                    try {
                        // Subir desde el archivo temporal a Media Library
                        $media = $employmentCompany->addMedia($fullPath)
                            ->usingName('Employment Verification Manual')
                            ->usingFileName('Employment_verification_manual.pdf')
                            ->withCustomProperties([
                                'uploaded_by' => Auth::check() ? Auth::user()->name : 'Administrador',
                                'uploaded_at' => now()->format('Y-m-d H:i:s'),
                                'manual_upload' => true,
                                'original_name' => $fileName
                            ])
                            ->toMediaCollection('employment_verification_documents');
                        
                        $uploadedCount++;
                        
                        Log::info('Documento de verificación subido desde Livewire', [
                            'employment_company_id' => $employmentCompany->id,
                            'media_id' => $media->id,
                            'file_name' => $media->file_name,
                            'original_name' => $fileName
                        ]);
                    } catch (\Exception $e) {
                        $errors[] = "Error al procesar {$fileName}: {$e->getMessage()}";
                        Log::error('Error al procesar archivo temporal', [
                            'file' => $fileData,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString()
                        ]);
                    }
                }
            } else {
                DB::rollBack();
                return redirect()->route('admin.drivers.employment-verification.show', $id)
                    ->with('error', 'No se recibieron archivos para subir');
            }
            
            // Si la verificación no está marcada como verificada, marcarla automáticamente
            if ($uploadedCount > 0 && $employmentCompany->verification_status !== 'verified') {
                $employmentCompany->verification_status = 'verified';
                $employmentCompany->verification_date = now();
                $employmentCompany->verification_notes = 'Verificado mediante documento subido manualmente';
                $employmentCompany->verification_by = Auth::check() ? Auth::user()->name . ' (Admin)' : 'Administrador';
                $employmentCompany->save();
                
                Log::info('Estado de verificación actualizado a verified', ['employment_company_id' => $id]);
            }
            
            DB::commit();
            
            $message = "$uploadedCount documentos subidos correctamente";
            if (!empty($errors)) {
                $message .= ", pero hubo errores con algunos archivos: " . implode(", ", $errors);
                return redirect()->route('admin.drivers.employment-verification.show', $id)
                    ->with('warning', $message);
            }
            
            return redirect()->route('admin.drivers.employment-verification.show', $id)
                ->with('success', $message);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error al subir documentos de verificación de empleo', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'employment_company_id' => $id
            ]);
            
            return redirect()->route('admin.drivers.employment-verification.show', $id)
                ->with('error', 'Error al subir los documentos: ' . $e->getMessage());
        }
    }
    
    /**
     * Permite subir manualmente un documento de verificación de empleo digitalizado
     */
    public function uploadManualVerification(Request $request, $id)
    {
        // Validar la solicitud
        $validator = Validator::make($request->all(), [
            'verification_document' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'verification_date' => 'required|date',
            'verification_notes' => 'nullable|string|max:500',
        ], [
            'verification_document.required' => 'Please select a document to upload.',
            'verification_document.file' => 'The selected file is not valid.',
            'verification_document.mimes' => 'The document must be a PDF, JPG, JPEG or PNG file.',
            'verification_document.max' => 'The document must not exceed 10MB.',
            'verification_date.required' => 'The verification date is required.',
            'verification_date.date' => 'The verification date must be a valid date format.',
            'verification_notes.max' => 'Notes cannot exceed 500 characters.'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Obtener la compañía de empleo
        $employmentCompany = DriverEmploymentCompany::findOrFail($id);
        $collection = 'employment_verification_documents';
        
        try {
            // Guardar el archivo usando Spatie Media Library
            if ($request->hasFile('verification_document')) {
                $file = $request->file('verification_document');
                $originalName = $file->getClientOriginalName();
                
                // Use unique filename with timestamp to allow multiple documents
                $uniqueFileName = 'Employment_verification_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                
                // Agregar el documento a la colección de medios
                $media = $employmentCompany->addMedia($file->getRealPath())
                    ->usingName('Employment Verification - ' . $originalName)
                    ->usingFileName($uniqueFileName)
                    ->withCustomProperties([
                        'uploaded_by' => Auth::user()->name,
                        'uploaded_at' => now()->format('Y-m-d H:i:s'),
                        'manual_upload' => true,
                        'original_name' => $originalName,
                        'verification_date' => $request->verification_date,
                        'verification_notes' => $request->verification_notes
                    ])
                    ->toMediaCollection($collection);
                    
                // Actualizar el estado de verificación
                $employmentCompany->verification_status = 'verified';
                $employmentCompany->verification_date = now();
                
                // Preparar las notas de verificación
                $verificationNotes = $request->verification_notes ?? '';
                $adminInfo = "\n\nManually verified by " . Auth::user()->name . ' on ' . now()->format('Y-m-d H:i:s');
                
                // Guardar notas con información de quien verificó
                $employmentCompany->verification_notes = $verificationNotes . $adminInfo;
                $employmentCompany->save();
                
                Log::info('Manual verification document uploaded successfully', [
                    'employment_company_id' => $employmentCompany->id,
                    'file_name' => $originalName,
                    'media_id' => $media->id
                ]);
                
                return redirect()->route('admin.drivers.employment-verification.show', $employmentCompany->id)
                    ->with('success', 'The verification document has been uploaded successfully.');
            }
            
        } catch (\Exception $e) {
            Log::error('Error uploading manual verification document', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'employment_company_id' => $employmentCompany->id
            ]);
            
            return redirect()->back()
                ->with('error', 'An error occurred while uploading the document: ' . $e->getMessage())
                ->withInput();
        }
        
        return redirect()->back()
            ->with('error', 'No document found to upload.')
            ->withInput();
    }
    
    /**
     * Delete a verification document
     */
    public function deleteDocument(Request $request, $id, $mediaId)
    {
        try {
            $employmentCompany = DriverEmploymentCompany::findOrFail($id);
            
            // Find the media item
            $media = $employmentCompany->getMedia('employment_verification_documents')
                ->where('id', $mediaId)
                ->first();
            
            if (!$media) {
                return redirect()->back()
                    ->with('error', 'Document not found.');
            }
            
            $fileName = $media->file_name;
            
            // Delete the media
            $media->delete();
            
            Log::info('Verification document deleted', [
                'employment_company_id' => $employmentCompany->id,
                'media_id' => $mediaId,
                'file_name' => $fileName,
                'deleted_by' => Auth::user()->name ?? 'Unknown'
            ]);
            
            return redirect()->back()
                ->with('success', 'Document deleted successfully.');
                
        } catch (\Exception $e) {
            Log::error('Error deleting verification document', [
                'error' => $e->getMessage(),
                'employment_company_id' => $id,
                'media_id' => $mediaId
            ]);
            
            return redirect()->back()
                ->with('error', 'An error occurred while deleting the document: ' . $e->getMessage());
        }
    }
    
    /**
     * Muestra el formulario wizard de verificación de empleo en una página independiente
     */
    public function createNew()
    {
        return view('admin.drivers.employment-verification.new');
    }

    /**
     * Delete a verification token (attempt)
     */
    public function deleteToken(Request $request, $id, $tokenId)
    {
        try {
            $employmentCompany = DriverEmploymentCompany::findOrFail($id);
            
            // Find the token
            $token = EmploymentVerificationToken::where('id', $tokenId)
                ->where('employment_company_id', $id)
                ->first();
            
            if (!$token) {
                return redirect()->back()
                    ->with('error', 'Verification token not found.');
            }

            // Don't allow deletion of verified tokens
            if ($token->verified_at) {
                return redirect()->back()
                    ->with('error', 'Cannot delete a verified token.');
            }

            // Delete associated PDF from media library if exists
            if ($employmentCompany->userDriverDetail) {
                $driver = $employmentCompany->userDriverDetail;
                $attemptNumber = EmploymentVerificationToken::where('employment_company_id', $id)
                    ->where('created_at', '<=', $token->created_at)
                    ->count();
                
                $attemptPdf = $driver->getMedia('employment_verification_attempts')
                    ->filter(function($media) use ($id, $attemptNumber) {
                        return $media->getCustomProperty('company_id') == $id 
                            && $media->getCustomProperty('attempt_number') == $attemptNumber;
                    })->first();
                
                if ($attemptPdf) {
                    $attemptPdf->delete();
                }
            }
            
            $tokenEmail = $token->email;
            $tokenCreatedAt = $token->created_at;
            
            // Delete the token
            $token->delete();
            
            Log::info('Verification token deleted', [
                'employment_company_id' => $employmentCompany->id,
                'token_id' => $tokenId,
                'token_email' => $tokenEmail,
                'token_created_at' => $tokenCreatedAt,
                'deleted_by' => Auth::user()->name ?? 'Unknown'
            ]);
            
            return redirect()->back()
                ->with('success', 'Verification attempt deleted successfully.');
                
        } catch (\Exception $e) {
            Log::error('Error deleting verification token', [
                'error' => $e->getMessage(),
                'employment_company_id' => $id,
                'token_id' => $tokenId
            ]);
            
            return redirect()->back()
                ->with('error', 'An error occurred while deleting the token: ' . $e->getMessage());
        }
    }
}
