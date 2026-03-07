<?php

namespace App\Http\Controllers\Carrier;

use App\Models\Membership;
use App\Models\DocumentType;
use Illuminate\Http\Request;
use App\Models\CarrierDocument;
use App\Models\UserCarrierDetail;
use App\Models\Admin\Driver\DriverLicense;
use App\Models\Admin\Driver\DriverMedicalQualification;
use App\Models\Admin\Driver\DriverAccident;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class CarrierProfileController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $carrierDetail = $user->carrierDetails;
        $carrier = $carrierDetail->carrier;
        
        // Cargar relaciones necesarias
        $carrier->load(['membership', 'bankingDetails']);
        
        // Cálculos para el perfil y documentos
        $totalDocuments = DocumentType::count();
        $uploadedDocuments = CarrierDocument::where('carrier_id', $carrier->id)
            ->where('status', CarrierDocument::STATUS_APPROVED)
            ->count();
        $documentProgress = $totalDocuments > 0 ? ($uploadedDocuments / $totalDocuments) * 100 : 0;

        $pendingDocuments = CarrierDocument::where('carrier_id', $carrier->id)
            ->where('status', '!=', CarrierDocument::STATUS_APPROVED)
            ->with('documentType')
            ->get();

        // Obtener usuarios asociados
        $userCarriers = UserCarrierDetail::where('carrier_id', $carrier->id)
            ->with('user')
            ->get();

        $membership = $carrier->membership;
        
        // Estadísticas de drivers
        $driversCount = $carrier->userDrivers()->count();
        $activeDrivers = $carrier->userDrivers()->where('status', 1)->count();
        $inactiveDrivers = $driversCount - $activeDrivers;
        
        // Estadísticas de vehículos
        $vehiclesCount = $carrier->vehicles()->count();
        $activeVehicles = $carrier->vehicles()->where('out_of_service', false)->count();
        
        // Estadísticas de licencias
        $now = Carbon::now();
        $expiringThreshold = $now->copy()->addDays(30);
        
        $licenseStats = $this->getLicenseStats($carrier->id, $now, $expiringThreshold);
        $medicalStats = $this->getMedicalStats($carrier->id, $now, $expiringThreshold);
        $accidentStats = $this->getAccidentStats($carrier->id, $now);
        
        // Documentos por estado
        $documentStats = [
            'total' => $carrier->documents()->count(),
            'pending' => $carrier->documents()->where('status', CarrierDocument::STATUS_PENDING)->count(),
            'approved' => $carrier->documents()->where('status', CarrierDocument::STATUS_APPROVED)->count(),
            'rejected' => $carrier->documents()->where('status', CarrierDocument::STATUS_REJECTED)->count(),
        ];
        
        // Límites de membresía
        $membershipLimits = [
            'drivers' => [
                'current' => $driversCount,
                'max' => $membership->max_drivers ?? 0,
                'percentage' => $membership && $membership->max_drivers > 0 
                    ? round(($driversCount / $membership->max_drivers) * 100) 
                    : 0,
            ],
            'vehicles' => [
                'current' => $vehiclesCount,
                'max' => $membership->max_vehicles ?? 0,
                'percentage' => $membership && $membership->max_vehicles > 0 
                    ? round(($vehiclesCount / $membership->max_vehicles) * 100) 
                    : 0,
            ],
            'users' => [
                'current' => $userCarriers->count(),
                'max' => $membership->max_carrier ?? 0,
            ],
        ];
        
        // Membresías disponibles para upgrade
        $availableMemberships = Membership::where('status', 1)
            ->where('id', '!=', $carrier->id_plan)
            ->orderBy('price', 'asc')
            ->get();
        
        // Actividad reciente (últimos 10 eventos)
        $recentActivity = $this->getRecentActivity($carrier);

        return view('carrier.profile.index', compact(
            'user',
            'carrierDetail',
            'carrier',
            'documentProgress',
            'pendingDocuments',
            'userCarriers',
            'membership',
            'totalDocuments',
            'uploadedDocuments',
            'driversCount',
            'activeDrivers',
            'inactiveDrivers',
            'vehiclesCount',
            'activeVehicles',
            'licenseStats',
            'medicalStats',
            'accidentStats',
            'documentStats',
            'membershipLimits',
            'availableMemberships',
            'recentActivity'
        ));
    }

    private function getLicenseStats($carrierId, $now, $expiringThreshold)
    {
        $totalLicenses = DriverLicense::whereHas('driverDetail', function($q) use ($carrierId) {
            $q->where('carrier_id', $carrierId);
        })->count();

        $expiredLicenses = DriverLicense::whereHas('driverDetail', function($q) use ($carrierId) {
            $q->where('carrier_id', $carrierId);
        })->where('expiration_date', '<', $now)->count();

        $expiringSoonLicenses = DriverLicense::whereHas('driverDetail', function($q) use ($carrierId) {
            $q->where('carrier_id', $carrierId);
        })
        ->where('expiration_date', '>=', $now)
        ->where('expiration_date', '<=', $expiringThreshold)
        ->count();

        return [
            'total' => $totalLicenses,
            'expired' => $expiredLicenses,
            'expiring_soon' => $expiringSoonLicenses,
            'valid' => max(0, $totalLicenses - $expiredLicenses - $expiringSoonLicenses),
        ];
    }

    private function getMedicalStats($carrierId, $now, $expiringThreshold)
    {
        $totalRecords = DriverMedicalQualification::whereHas('userDriverDetail', function($q) use ($carrierId) {
            $q->where('carrier_id', $carrierId);
        })->count();

        $expiredRecords = DriverMedicalQualification::whereHas('userDriverDetail', function($q) use ($carrierId) {
            $q->where('carrier_id', $carrierId);
        })->where('medical_card_expiration_date', '<', $now)->count();

        $expiringSoonRecords = DriverMedicalQualification::whereHas('userDriverDetail', function($q) use ($carrierId) {
            $q->where('carrier_id', $carrierId);
        })
        ->where('medical_card_expiration_date', '>=', $now)
        ->where('medical_card_expiration_date', '<=', $expiringThreshold)
        ->count();

        return [
            'total' => $totalRecords,
            'expired' => $expiredRecords,
            'expiring_soon' => $expiringSoonRecords,
            'valid' => max(0, $totalRecords - $expiredRecords - $expiringSoonRecords),
        ];
    }

    private function getAccidentStats($carrierId, $now)
    {
        $thirtyDaysAgo = $now->copy()->subDays(30);
        $yearStart = $now->copy()->startOfYear();

        return [
            'total' => DriverAccident::whereHas('userDriverDetail', function($q) use ($carrierId) {
                $q->where('carrier_id', $carrierId);
            })->count(),
            'this_month' => DriverAccident::whereHas('userDriverDetail', function($q) use ($carrierId) {
                $q->where('carrier_id', $carrierId);
            })->where('accident_date', '>=', $thirtyDaysAgo)->count(),
            'this_year' => DriverAccident::whereHas('userDriverDetail', function($q) use ($carrierId) {
                $q->where('carrier_id', $carrierId);
            })->where('accident_date', '>=', $yearStart)->count(),
        ];
    }

    private function getRecentActivity($carrier)
    {
        $activities = collect();
        
        // Documentos recientes
        $recentDocs = $carrier->documents()
            ->with('documentType')
            ->orderBy('updated_at', 'desc')
            ->take(5)
            ->get()
            ->map(function($doc) {
                return [
                    'type' => 'document',
                    'icon' => 'FileText',
                    'color' => $doc->status == CarrierDocument::STATUS_APPROVED ? 'success' : 
                              ($doc->status == CarrierDocument::STATUS_REJECTED ? 'danger' : 'warning'),
                    'title' => 'Document ' . ($doc->status == CarrierDocument::STATUS_APPROVED ? 'approved' : 
                              ($doc->status == CarrierDocument::STATUS_REJECTED ? 'rejected' : 'uploaded')),
                    'description' => $doc->documentType->name ?? 'Unknown document',
                    'date' => $doc->updated_at,
                ];
            });
        $activities = $activities->merge($recentDocs);
        
        // Drivers recientes
        $recentDrivers = $carrier->userDrivers()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->take(3)
            ->get()
            ->map(function($driver) {
                return [
                    'type' => 'driver',
                    'icon' => 'UserPlus',
                    'color' => 'primary',
                    'title' => 'New driver added',
                    'description' => $driver->user->name ?? $driver->full_name ?? 'Unknown driver',
                    'date' => $driver->created_at,
                ];
            });
        $activities = $activities->merge($recentDrivers);
        
        // Vehículos recientes
        $recentVehicles = $carrier->vehicles()
            ->orderBy('created_at', 'desc')
            ->take(3)
            ->get()
            ->map(function($vehicle) {
                return [
                    'type' => 'vehicle',
                    'icon' => 'Truck',
                    'color' => 'info',
                    'title' => 'Vehicle added',
                    'description' => ($vehicle->make ?? '') . ' ' . ($vehicle->model ?? '') . ' - Unit #' . ($vehicle->company_unit_number ?? 'N/A'),
                    'date' => $vehicle->created_at,
                ];
            });
        $activities = $activities->merge($recentVehicles);
        
        // Ordenar por fecha y tomar los 10 más recientes
        return $activities->sortByDesc('date')->take(10)->values();
    }

    public function edit()
    {
        $user = Auth::user();
        $carrierDetail = $user->carrierDetails;
        $carrier = $carrierDetail->carrier;

        return view('carrier.profile.edit', compact('user', 'carrierDetail', 'carrier'));
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        $carrier = $user->carrierDetails->carrier;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'state' => 'required|string|max:255',
            'zipcode' => 'required|string|max:10',
            'ein_number' => 'required|string|max:255',
            'dot_number' => 'required|string|max:255',
            'mc_number' => 'nullable|string|max:255',
            'state_dot' => 'nullable|string|max:255',
            'ifta_account' => 'nullable|string|max:255',
            'phone' => 'required|string|max:15',
            'logo_carrier' => 'nullable|image|max:2048',
        ]);

        // Actualizar carrier
        $carrier->update($validated);

        // Actualizar carrier details
        $user->carrierDetails->update([
            'phone' => $validated['phone'],
        ]);

        // Manejar la foto/logo si se subió
        if ($request->hasFile('logo_carrier')) {
            $carrier->addMediaFromRequest('logo_carrier')
                ->usingFileName(strtolower(str_replace(' ', '_', $carrier->name)) . '.webp')
                ->toMediaCollection('logo_carrier');
        }

        return redirect()->route('carrier.profile')
            ->with('success', 'Profile updated successfully');
    }
}
