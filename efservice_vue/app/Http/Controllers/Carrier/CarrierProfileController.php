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

        // Single query for all document counts (replaces 7 separate queries)
        $docRow = DB::table('carrier_documents')
            ->where('carrier_id', $carrier->id)
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as rejected,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as in_process
            ', [
                CarrierDocument::STATUS_PENDING,
                CarrierDocument::STATUS_APPROVED,
                CarrierDocument::STATUS_REJECTED,
                CarrierDocument::STATUS_IN_PROCESS,
            ])
            ->first();

        $documentStats = [
            'total'      => (int) ($docRow->total ?? 0),
            'pending'    => (int) ($docRow->pending ?? 0),
            'approved'   => (int) ($docRow->approved ?? 0),
            'rejected'   => (int) ($docRow->rejected ?? 0),
            'in_process' => (int) ($docRow->in_process ?? 0),
        ];

        $documentProgress = $totalDocuments > 0
            ? round(($documentStats['approved'] / $totalDocuments) * 100)
            : 0;

        $pendingDocuments = $carrier->documents()
            ->with('documentType:id,name')
            ->where('status', '!=', CarrierDocument::STATUS_APPROVED)
            ->latest('updated_at')
            ->take(5)
            ->get()
            ->map(fn (CarrierDocument $document) => [
                'id'         => $document->id,
                'name'       => $document->documentType?->name ?? 'Unknown document',
                'status'     => (int) $document->status,
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

        // Single query for drivers counts
        $driverRow = DB::table('user_driver_details')
            ->where('carrier_id', $carrier->id)
            ->selectRaw('COUNT(*) as total, SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as active')
            ->first();
        $driversCount  = (int) ($driverRow->total ?? 0);
        $activeDrivers = (int) ($driverRow->active ?? 0);

        // Single query for vehicle counts
        $vehicleRow = DB::table('vehicles')
            ->where('carrier_id', $carrier->id)
            ->selectRaw('COUNT(*) as total, SUM(CASE WHEN out_of_service = 0 THEN 1 ELSE 0 END) as active')
            ->first();
        $vehiclesCount  = (int) ($vehicleRow->total ?? 0);
        $activeVehicles = (int) ($vehicleRow->active ?? 0);

        $now = Carbon::now();
        $expiringThreshold = $now->copy()->addDays(30);

        $licenseStats  = $this->licenseStats($carrier->id, $now, $expiringThreshold);
        $medicalStats  = $this->medicalStats($carrier->id, $now, $expiringThreshold);
        $accidentStats = $this->accidentStats($carrier->id, $now);

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
                'referral_url' => route('driver.register', [$carrier->slug, 'token' => $carrier->referrer_token]),
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
                $ext  = $request->file('logo_carrier')->extension();
                $name = strtolower(preg_replace('/[^a-zA-Z0-9]/', '_', $carrier->name));
                $carrier->clearMediaCollection('logo_carrier');
                $carrier->addMediaFromRequest('logo_carrier')
                    ->usingFileName("{$name}.{$ext}")
                    ->toMediaCollection('logo_carrier');
            }
        });

        return redirect()
            ->route('carrier.profile')
            ->with('success', 'Profile updated successfully.');
    }

    private function licenseStats(int $carrierId, Carbon $now, Carbon $expiringThreshold): array
    {
        $row = DB::table('driver_licenses')
            ->join('user_driver_details', 'driver_licenses.user_driver_detail_id', '=', 'user_driver_details.id')
            ->where('user_driver_details.carrier_id', $carrierId)
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN expiration_date < ? THEN 1 ELSE 0 END) as expired,
                SUM(CASE WHEN expiration_date >= ? AND expiration_date <= ? THEN 1 ELSE 0 END) as expiring_soon
            ', [$now->toDateString(), $now->toDateString(), $expiringThreshold->toDateString()])
            ->first();

        $total       = (int) ($row->total ?? 0);
        $expired     = (int) ($row->expired ?? 0);
        $expiringSoon = (int) ($row->expiring_soon ?? 0);

        return [
            'total'        => $total,
            'expired'      => $expired,
            'expiring_soon' => $expiringSoon,
            'valid'        => max(0, $total - $expired - $expiringSoon),
        ];
    }

    private function medicalStats(int $carrierId, Carbon $now, Carbon $expiringThreshold): array
    {
        $row = DB::table('driver_medical_qualifications')
            ->join('user_driver_details', 'driver_medical_qualifications.user_driver_detail_id', '=', 'user_driver_details.id')
            ->where('user_driver_details.carrier_id', $carrierId)
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN medical_card_expiration_date < ? THEN 1 ELSE 0 END) as expired,
                SUM(CASE WHEN medical_card_expiration_date >= ? AND medical_card_expiration_date <= ? THEN 1 ELSE 0 END) as expiring_soon
            ', [$now->toDateString(), $now->toDateString(), $expiringThreshold->toDateString()])
            ->first();

        $total       = (int) ($row->total ?? 0);
        $expired     = (int) ($row->expired ?? 0);
        $expiringSoon = (int) ($row->expiring_soon ?? 0);

        return [
            'total'        => $total,
            'expired'      => $expired,
            'expiring_soon' => $expiringSoon,
            'valid'        => max(0, $total - $expired - $expiringSoon),
        ];
    }

    private function accidentStats(int $carrierId, Carbon $now): array
    {
        $monthStart = $now->copy()->subDays(30)->toDateString();
        $yearStart  = $now->copy()->startOfYear()->toDateString();

        $row = DB::table('driver_accidents')
            ->join('user_driver_details', 'driver_accidents.user_driver_detail_id', '=', 'user_driver_details.id')
            ->where('user_driver_details.carrier_id', $carrierId)
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN accident_date >= ? THEN 1 ELSE 0 END) as this_month,
                SUM(CASE WHEN accident_date >= ? THEN 1 ELSE 0 END) as this_year
            ', [$monthStart, $yearStart])
            ->first();

        return [
            'total'      => (int) ($row->total ?? 0),
            'this_month' => (int) ($row->this_month ?? 0),
            'this_year'  => (int) ($row->this_year ?? 0),
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
