<?php

namespace App\Http\Controllers\Carrier;

use App\Helpers\Constants;
use App\Http\Controllers\Carrier\Concerns\ResolvesCarrierContext;
use App\Http\Controllers\Controller;
use App\Models\Admin\Driver\DriverAccident;
use App\Models\Admin\Driver\DriverLicense;
use App\Models\Admin\Driver\DriverMedicalQualification;
use App\Models\CarrierDocument;
use App\Models\DocumentType;
use App\Models\Membership;
use App\Models\UserCarrierDetail;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CarrierProfileController extends Controller
{
    use ResolvesCarrierContext;

    public function index(): Response
    {
        $user = auth()->user();
        $carrierDetail = $user?->carrierDetails;
        $carrier = $this->resolveCarrier()->load(['membership', 'bankingDetails']);

        $totalDocuments = DocumentType::query()->count();
        $approvedDocuments = $carrier->documents()
            ->where('status', CarrierDocument::STATUS_APPROVED)
            ->count();
        $documentProgress = $totalDocuments > 0
            ? round(($approvedDocuments / $totalDocuments) * 100)
            : 0;

        $pendingDocuments = $carrier->documents()
            ->with('documentType:id,name')
            ->where('status', '!=', CarrierDocument::STATUS_APPROVED)
            ->latest('updated_at')
            ->take(5)
            ->get()
            ->map(fn (CarrierDocument $document) => [
                'id' => $document->id,
                'name' => $document->documentType?->name ?? 'Unknown document',
                'status' => (int) $document->status,
                'status_name' => $document->status_name,
                'updated_at' => optional($document->updated_at)->format('M d, Y'),
            ])
            ->values();

        $userCarriers = UserCarrierDetail::query()
            ->where('carrier_id', $carrier->id)
            ->with('user:id,name,email')
            ->latest()
            ->get();

        $membership = $carrier->membership;
        $driversCount = $carrier->userDrivers()->count();
        $activeDrivers = $carrier->userDrivers()->where('status', 1)->count();
        $vehiclesCount = $carrier->vehicles()->count();
        $activeVehicles = $carrier->vehicles()->where('out_of_service', false)->count();

        $now = Carbon::now();
        $expiringThreshold = $now->copy()->addDays(30);

        $licenseStats = $this->licenseStats($carrier->id, $now, $expiringThreshold);
        $medicalStats = $this->medicalStats($carrier->id, $now, $expiringThreshold);
        $accidentStats = $this->accidentStats($carrier->id, $now);

        $documentStats = [
            'total' => $carrier->documents()->count(),
            'pending' => $carrier->documents()->where('status', CarrierDocument::STATUS_PENDING)->count(),
            'approved' => $carrier->documents()->where('status', CarrierDocument::STATUS_APPROVED)->count(),
            'rejected' => $carrier->documents()->where('status', CarrierDocument::STATUS_REJECTED)->count(),
            'in_process' => $carrier->documents()->where('status', CarrierDocument::STATUS_IN_PROCESS)->count(),
        ];

        $membershipLimits = [
            'drivers' => [
                'current' => $driversCount,
                'max' => (int) ($membership?->max_drivers ?? 0),
                'percentage' => $membership && $membership->max_drivers > 0
                    ? min(100, (int) round(($driversCount / $membership->max_drivers) * 100))
                    : 0,
            ],
            'vehicles' => [
                'current' => $vehiclesCount,
                'max' => (int) ($membership?->max_vehicles ?? 0),
                'percentage' => $membership && $membership->max_vehicles > 0
                    ? min(100, (int) round(($vehiclesCount / $membership->max_vehicles) * 100))
                    : 0,
            ],
            'users' => [
                'current' => $userCarriers->count(),
                'max' => (int) ($membership?->max_carrier ?? 0),
                'percentage' => $membership && $membership->max_carrier > 0
                    ? min(100, (int) round(($userCarriers->count() / $membership->max_carrier) * 100))
                    : 0,
            ],
        ];

        $availableMemberships = Membership::query()
            ->where('status', 1)
            ->where('id', '!=', $carrier->id_plan)
            ->orderBy('price')
            ->get(['id', 'name', 'description', 'price', 'max_drivers', 'max_vehicles', 'max_carrier'])
            ->map(fn (Membership $plan) => [
                'id' => $plan->id,
                'name' => $plan->name,
                'description' => $plan->description,
                'price' => $plan->price,
                'max_drivers' => $plan->max_drivers,
                'max_vehicles' => $plan->max_vehicles,
                'max_users' => $plan->max_carrier,
            ])
            ->values();

        return Inertia::render('carrier/profile/Index', [
            'carrier' => [
                'id' => $carrier->id,
                'name' => $carrier->name,
                'address' => $carrier->address,
                'state' => $carrier->state,
                'zipcode' => $carrier->zipcode,
                'ein_number' => $carrier->ein_number,
                'dot_number' => $carrier->dot_number,
                'mc_number' => $carrier->mc_number,
                'state_dot' => $carrier->state_dot,
                'ifta_account' => $carrier->ifta_account,
                'phone' => $carrierDetail?->phone,
                'status' => $carrier->status,
                'status_name' => $carrier->status_name,
                'logo_url' => $carrier->getFirstMediaUrl('logo_carrier') ?: null,
                'safety_url' => $carrier->safety_data_system_url,
                'safety_image_url' => $carrier->hasSafetyDataSystemImage() ? $carrier->getSafetyDataSystemImageUrl() : null,
                'referrer_token' => $carrier->referrer_token,
                'referral_url' => url('/driver/register/' . $carrier->slug . '?token=' . $carrier->referrer_token),
                'created_at' => optional($carrier->created_at)->format('M d, Y'),
                'updated_at' => optional($carrier->updated_at)->format('M d, Y'),
            ],
            'membership' => $membership ? [
                'id' => $membership->id,
                'name' => $membership->name,
                'description' => $membership->description,
                'price' => $membership->price,
                'max_drivers' => $membership->max_drivers,
                'max_vehicles' => $membership->max_vehicles,
                'max_users' => $membership->max_carrier,
            ] : null,
            'availableMemberships' => $availableMemberships,
            'stats' => [
                'drivers_total' => $driversCount,
                'drivers_active' => $activeDrivers,
                'drivers_inactive' => max(0, $driversCount - $activeDrivers),
                'vehicles_total' => $vehiclesCount,
                'vehicles_active' => $activeVehicles,
                'licenses' => $licenseStats,
                'medical' => $medicalStats,
                'accidents' => $accidentStats,
                'documents' => $documentStats,
                'documents_required' => $totalDocuments,
                'documents_progress' => $documentProgress,
            ],
            'membershipLimits' => $membershipLimits,
            'pendingDocuments' => $pendingDocuments,
            'teamMembers' => $userCarriers->map(fn (UserCarrierDetail $member) => [
                'id' => $member->id,
                'name' => $member->user?->name ?? 'Unknown user',
                'email' => $member->user?->email,
                'phone' => $member->phone,
                'job_position' => $member->job_position ?: 'Team Member',
                'status' => $member->status,
                'status_name' => $member->status_name,
                'profile_photo_url' => $member->user?->profile_photo_url,
            ])->values(),
            'recentActivity' => $this->recentActivity($carrier),
            'bankingDetails' => $carrier->bankingDetails ? [
                'status' => $carrier->bankingDetails->status,
                'account_holder_name' => $carrier->bankingDetails->account_holder_name,
                'country_code' => $carrier->bankingDetails->country_code,
                'updated_at' => optional($carrier->bankingDetails->updated_at)->diffForHumans(),
            ] : null,
        ]);
    }

    public function edit(): Response
    {
        $user = auth()->user();
        $carrierDetail = $user?->carrierDetails;
        $carrier = $this->resolveCarrier()->load('membership');

        return Inertia::render('carrier/profile/Edit', [
            'carrier' => [
                'id' => $carrier->id,
                'name' => $carrier->name,
                'address' => $carrier->address,
                'state' => $carrier->state,
                'zipcode' => $carrier->zipcode,
                'ein_number' => $carrier->ein_number,
                'dot_number' => $carrier->dot_number,
                'mc_number' => $carrier->mc_number,
                'state_dot' => $carrier->state_dot,
                'ifta_account' => $carrier->ifta_account,
                'phone' => $carrierDetail?->phone,
                'status' => $carrier->status,
                'status_name' => $carrier->status_name,
                'referrer_token' => $carrier->referrer_token,
                'logo_url' => $carrier->getFirstMediaUrl('logo_carrier') ?: null,
                'created_at' => optional($carrier->created_at)->format('M d, Y'),
                'updated_at' => optional($carrier->updated_at)->format('M d, Y'),
                'membership' => $carrier->membership ? [
                    'name' => $carrier->membership->name,
                    'price' => $carrier->membership->price,
                ] : null,
            ],
            'usStates' => Constants::usStates(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = auth()->user();
        $carrierDetail = $user?->carrierDetails;
        $carrier = $this->resolveCarrier();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
            'state' => ['required', 'string', 'max:10'],
            'zipcode' => ['required', 'string', 'max:10'],
            'ein_number' => [
                'required',
                'string',
                'max:255',
                Rule::unique('carriers', 'ein_number')->ignore($carrier->id),
            ],
            'dot_number' => [
                'required',
                'string',
                'max:255',
                Rule::unique('carriers', 'dot_number')->ignore($carrier->id),
            ],
            'mc_number' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('carriers', 'mc_number')->ignore($carrier->id),
            ],
            'state_dot' => ['nullable', 'string', 'max:255'],
            'ifta_account' => ['nullable', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'logo_carrier' => ['nullable', 'image', 'max:2048'],
        ]);

        DB::transaction(function () use ($carrier, $carrierDetail, $request, $validated) {
            $carrier->update([
                'name' => $validated['name'],
                'address' => $validated['address'],
                'state' => $validated['state'],
                'zipcode' => $validated['zipcode'],
                'ein_number' => $validated['ein_number'],
                'dot_number' => $validated['dot_number'],
                'mc_number' => $validated['mc_number'] ?? null,
                'state_dot' => $validated['state_dot'] ?? null,
                'ifta_account' => $validated['ifta_account'] ?? null,
            ]);

            if ($carrierDetail) {
                $carrierDetail->update([
                    'phone' => $validated['phone'],
                ]);
            }

            if ($request->hasFile('logo_carrier')) {
                $carrier->clearMediaCollection('logo_carrier');
                $carrier->addMediaFromRequest('logo_carrier')
                    ->usingFileName(strtolower(str_replace(' ', '_', $carrier->name)) . '.webp')
                    ->toMediaCollection('logo_carrier');
            }
        });

        return redirect()
            ->route('carrier.profile')
            ->with('success', 'Profile updated successfully.');
    }

    private function licenseStats(int $carrierId, Carbon $now, Carbon $expiringThreshold): array
    {
        $total = DriverLicense::query()
            ->whereHas('driverDetail', fn ($query) => $query->where('carrier_id', $carrierId))
            ->count();

        $expired = DriverLicense::query()
            ->whereHas('driverDetail', fn ($query) => $query->where('carrier_id', $carrierId))
            ->whereDate('expiration_date', '<', $now)
            ->count();

        $expiringSoon = DriverLicense::query()
            ->whereHas('driverDetail', fn ($query) => $query->where('carrier_id', $carrierId))
            ->whereDate('expiration_date', '>=', $now)
            ->whereDate('expiration_date', '<=', $expiringThreshold)
            ->count();

        return [
            'total' => $total,
            'expired' => $expired,
            'expiring_soon' => $expiringSoon,
            'valid' => max(0, $total - $expired - $expiringSoon),
        ];
    }

    private function medicalStats(int $carrierId, Carbon $now, Carbon $expiringThreshold): array
    {
        $total = DriverMedicalQualification::query()
            ->whereHas('userDriverDetail', fn ($query) => $query->where('carrier_id', $carrierId))
            ->count();

        $expired = DriverMedicalQualification::query()
            ->whereHas('userDriverDetail', fn ($query) => $query->where('carrier_id', $carrierId))
            ->whereDate('medical_card_expiration_date', '<', $now)
            ->count();

        $expiringSoon = DriverMedicalQualification::query()
            ->whereHas('userDriverDetail', fn ($query) => $query->where('carrier_id', $carrierId))
            ->whereDate('medical_card_expiration_date', '>=', $now)
            ->whereDate('medical_card_expiration_date', '<=', $expiringThreshold)
            ->count();

        return [
            'total' => $total,
            'expired' => $expired,
            'expiring_soon' => $expiringSoon,
            'valid' => max(0, $total - $expired - $expiringSoon),
        ];
    }

    private function accidentStats(int $carrierId, Carbon $now): array
    {
        $monthStart = $now->copy()->subDays(30);
        $yearStart = $now->copy()->startOfYear();

        return [
            'total' => DriverAccident::query()
                ->whereHas('userDriverDetail', fn ($query) => $query->where('carrier_id', $carrierId))
                ->count(),
            'this_month' => DriverAccident::query()
                ->whereHas('userDriverDetail', fn ($query) => $query->where('carrier_id', $carrierId))
                ->whereDate('accident_date', '>=', $monthStart)
                ->count(),
            'this_year' => DriverAccident::query()
                ->whereHas('userDriverDetail', fn ($query) => $query->where('carrier_id', $carrierId))
                ->whereDate('accident_date', '>=', $yearStart)
                ->count(),
        ];
    }

    private function recentActivity($carrier)
    {
        $activities = collect();

        $recentDocuments = $carrier->documents()
            ->with('documentType:id,name')
            ->latest('updated_at')
            ->take(4)
            ->get()
            ->map(function (CarrierDocument $document) {
                return [
                    'id' => 'document-' . $document->id,
                    'type' => 'document',
                    'icon' => 'FileText',
                    'tone' => $document->status === CarrierDocument::STATUS_APPROVED
                        ? 'success'
                        : ($document->status === CarrierDocument::STATUS_REJECTED ? 'danger' : 'warning'),
                    'title' => $document->status === CarrierDocument::STATUS_APPROVED
                        ? 'Document approved'
                        : ($document->status === CarrierDocument::STATUS_REJECTED ? 'Document rejected' : 'Document updated'),
                    'description' => $document->documentType?->name ?? 'Unknown document',
                    'time' => optional($document->updated_at)->diffForHumans(),
                ];
            });

        $recentDrivers = $carrier->userDrivers()
            ->with('user:id,name,email')
            ->latest('created_at')
            ->take(3)
            ->get()
            ->map(function ($driver) {
                return [
                    'id' => 'driver-' . $driver->id,
                    'type' => 'driver',
                    'icon' => 'UserPlus',
                    'tone' => 'primary',
                    'title' => 'New driver added',
                    'description' => $driver->user?->name ?? 'Unknown driver',
                    'time' => optional($driver->created_at)->diffForHumans(),
                ];
            });

        $recentVehicles = $carrier->vehicles()
            ->latest('created_at')
            ->take(3)
            ->get()
            ->map(function ($vehicle) {
                $label = trim(collect([$vehicle->year, $vehicle->make, $vehicle->model])->filter()->join(' '));

                return [
                    'id' => 'vehicle-' . $vehicle->id,
                    'type' => 'vehicle',
                    'icon' => 'Truck',
                    'tone' => 'info',
                    'title' => 'Vehicle added',
                    'description' => trim($label . ' - Unit #' . ($vehicle->company_unit_number ?? 'N/A')),
                    'time' => optional($vehicle->created_at)->diffForHumans(),
                ];
            });

        return $activities
            ->merge($recentDocuments)
            ->merge($recentDrivers)
            ->merge($recentVehicles)
            ->take(10)
            ->values();
    }
}
