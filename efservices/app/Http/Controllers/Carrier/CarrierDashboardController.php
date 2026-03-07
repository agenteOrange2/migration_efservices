<?php

namespace App\Http\Controllers\Carrier;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\Carrier;

class CarrierDashboardController extends Controller
{
    /**
     * Mostrar el dashboard del carrier
     */
    public function index()
    {
        $user = Auth::user();
        
        // Log inicial de acceso para auditoría
        Log::info('CarrierDashboardController: Intento de acceso al dashboard', [
            'user_id' => $user ? $user->id : null,
            'user_email' => $user ? $user->email : null,
            'has_carrier_details' => $user && $user->carrierDetails ? 'yes' : 'no',
            'carrier_id' => $user && $user->carrierDetails ? $user->carrierDetails->carrier_id : null,
            'session_id' => request()->session()->getId(),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'referer' => request()->header('referer'),
            'timestamp' => now()->toISOString()
        ]);
        
        // Validación 1: Verificar si el usuario tiene carrierDetails
        if (!$user->carrierDetails) {
            Log::warning('CarrierDashboardController: Acceso denegado - Usuario sin carrierDetails', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'redirect_to' => 'carrier.wizard.step2',
                'timestamp' => now()->toISOString()
            ]);
            
            return redirect()->route('carrier.wizard.step2')
                ->with('error', 'Debe completar el proceso de registro primero.');
        }
        
        // Validación 2: Verificar si tiene carrier_id
        if (!$user->carrierDetails->carrier_id) {
            Log::warning('CarrierDashboardController: Acceso denegado - CarrierDetails sin carrier_id', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'carrier_details_id' => $user->carrierDetails->id,
                'redirect_to' => 'carrier.wizard.step2',
                'timestamp' => now()->toISOString()
            ]);
            
            return redirect()->route('carrier.wizard.step2')
                ->with('error', 'Debe completar el proceso de registro primero.');
        }
        
        // Validación 3: Obtener el carrier y verificar que existe
        $carrier = $user->carrierDetails->carrier;
        
        if (!$carrier) {
            Log::error('CarrierDashboardController: Acceso denegado - Carrier no encontrado', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'carrier_id' => $user->carrierDetails->carrier_id,
                'redirect_to' => 'carrier.wizard.step2',
                'timestamp' => now()->toISOString()
            ]);
            
            return redirect()->route('carrier.wizard.step2')
                ->with('error', 'No se encontró información del carrier.');
        }
        
        // Validación 4: Verificar el estado del carrier y redirigir según corresponda
        switch ($carrier->status) {
            case Carrier::STATUS_PENDING:
                Log::info('CarrierDashboardController: Acceso denegado - Carrier con estado PENDING', [
                    'user_id' => $user->id,
                    'carrier_id' => $carrier->id,
                    'carrier_slug' => $carrier->slug,
                    'carrier_status' => $carrier->status,
                    'carrier_status_label' => $carrier->getStatusLabelAttribute(),
                    'redirect_to' => 'carrier.pending.validation',
                    'timestamp' => now()->toISOString()
                ]);
                
                return redirect()->route('carrier.pending.validation')
                    ->with('info', 'Su solicitud está pendiente de validación.');
                    
            case Carrier::STATUS_PENDING_VALIDATION:
                Log::info('CarrierDashboardController: Acceso denegado - Carrier con estado PENDING_VALIDATION', [
                    'user_id' => $user->id,
                    'carrier_id' => $carrier->id,
                    'carrier_slug' => $carrier->slug,
                    'carrier_status' => $carrier->status,
                    'carrier_status_label' => $carrier->getStatusLabelAttribute(),
                    'redirect_to' => 'carrier.pending.validation',
                    'timestamp' => now()->toISOString()
                ]);
                
                return redirect()->route('carrier.pending.validation')
                    ->with('info', 'Su solicitud está pendiente de validación.');
                    
            case Carrier::STATUS_REJECTED:
                Log::warning('CarrierDashboardController: Acceso denegado - Carrier con estado REJECTED', [
                    'user_id' => $user->id,
                    'carrier_id' => $carrier->id,
                    'carrier_slug' => $carrier->slug,
                    'carrier_status' => $carrier->status,
                    'carrier_status_label' => $carrier->getStatusLabelAttribute(),
                    'redirect_to' => 'carrier.confirmation',
                    'timestamp' => now()->toISOString()
                ]);
                
                return redirect()->route('carrier.confirmation')
                    ->with('error', 'Su solicitud ha sido rechazada.');
                    
            case Carrier::STATUS_INACTIVE:
                Log::warning('CarrierDashboardController: Acceso denegado - Carrier con estado INACTIVE', [
                    'user_id' => $user->id,
                    'carrier_id' => $carrier->id,
                    'carrier_slug' => $carrier->slug,
                    'carrier_status' => $carrier->status,
                    'carrier_status_label' => $carrier->getStatusLabelAttribute(),
                    'redirect_to' => 'carrier.wizard.step2',
                    'timestamp' => now()->toISOString()
                ]);
                
                return redirect()->route('carrier.wizard.step2')
                    ->with('error', 'Su cuenta de carrier está inactiva. Por favor contacte al administrador.');
                    
            case Carrier::STATUS_ACTIVE:
                // Validación 5: Verificar si los documentos están completos O si tiene sesión de skip
                $hasSkipSession = session()->has('skip_documents_' . $carrier->id);
                
                if (!$carrier->documents_completed && !$hasSkipSession) {
                    Log::info('CarrierDashboardController: Acceso denegado - Documentos no completados sin sesión skip', [
                        'user_id' => $user->id,
                        'carrier_id' => $carrier->id,
                        'carrier_slug' => $carrier->slug,
                        'documents_completed' => $carrier->documents_completed,
                        'documents_completed_at' => $carrier->documents_completed_at,
                        'has_skip_session' => $hasSkipSession,
                        'skip_session_key' => 'skip_documents_' . $carrier->id,
                        'redirect_to' => 'carrier.documents.index',
                        'timestamp' => now()->toISOString()
                    ]);
                    
                    return redirect()->route('carrier.documents.index', $carrier->slug)
                        ->with('warning', 'Debe completar la carga de documentos.');
                }
                
                // Validación 6: Acceso permitido - Log de éxito
                Log::info('CarrierDashboardController: Acceso permitido al dashboard', [
                    'user_id' => $user->id,
                    'carrier_id' => $carrier->id,
                    'carrier_slug' => $carrier->slug,
                    'carrier_status' => $carrier->status,
                    'carrier_status_label' => $carrier->status_name,
                    'documents_completed' => $carrier->documents_completed,
                    'documents_completed_at' => $carrier->documents_completed_at,
                    'has_skip_session' => $hasSkipSession,
                    'access_reason' => $carrier->documents_completed ? 'Documents completed' : 'Has skip session',
                    'timestamp' => now()->toISOString()
                ]);
                break;
                
            default:
                Log::error('CarrierDashboardController: Acceso denegado - Estado del carrier no válido', [
                    'user_id' => $user->id,
                    'carrier_id' => $carrier->id,
                    'carrier_slug' => $carrier->slug,
                    'carrier_status' => $carrier->status,
                    'redirect_to' => 'carrier.wizard.step2',
                    'timestamp' => now()->toISOString()
                ]);
                
                return redirect()->route('carrier.wizard.step2')
                    ->with('error', 'Estado del carrier no válido.');
        }
        
        // Si llegamos aquí, el carrier está activo y puede ver el dashboard
        return view('carrier.dashboard', compact('carrier'));
    }
}