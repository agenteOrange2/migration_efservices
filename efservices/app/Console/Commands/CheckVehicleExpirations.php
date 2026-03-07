<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Admin\Vehicle\Vehicle;
use App\Models\Admin\Vehicle\VehicleDocument;
use App\Notifications\Carrier\VehicleRegistrationExpiringNotification;
use App\Notifications\Carrier\VehicleInspectionExpiringNotification;
use App\Notifications\Carrier\VehicleDocumentExpiringNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckVehicleExpirations extends Command
{
    protected $signature = 'vehicles:check-expirations {--days=30,15,7 : Comma-separated days before expiration to notify}';

    protected $description = 'Check for expiring vehicle registrations, inspections, and documents and send notifications';

    public function handle(): int
    {
        $daysThresholds = collect(explode(',', $this->option('days')))->map(fn($d) => (int) trim($d))->sort()->values();

        $this->info("Checking vehicle expirations for thresholds: {$daysThresholds->join(', ')} days");

        $counts = [
            'registrations' => 0,
            'inspections' => 0,
            'documents' => 0,
        ];

        // Get active vehicles with their carrier, documents
        $vehicles = Vehicle::where('status', Vehicle::STATUS_ACTIVE)
            ->with(['carrier.userCarriers.user', 'documents'])
            ->get();

        foreach ($vehicles as $vehicle) {
            if (!$vehicle->carrier) {
                continue;
            }

            // 1. Check registration expiration
            if ($vehicle->registration_expiration_date) {
                $days = $this->getDaysUntil($vehicle->registration_expiration_date);
                if ($days !== null && $daysThresholds->contains($days)) {
                    $this->notifyAll(
                        $vehicle,
                        new VehicleRegistrationExpiringNotification($vehicle, $days, $vehicle->registration_expiration_date->format('m-d-Y'))
                    );
                    $counts['registrations']++;
                }
            }

            // 2. Check annual inspection expiration
            if ($vehicle->annual_inspection_expiration_date) {
                $days = $this->getDaysUntil($vehicle->annual_inspection_expiration_date);
                if ($days !== null && $daysThresholds->contains($days)) {
                    $this->notifyAll(
                        $vehicle,
                        new VehicleInspectionExpiringNotification($vehicle, $days, $vehicle->annual_inspection_expiration_date->format('m-d-Y'))
                    );
                    $counts['inspections']++;
                }
            }

            // 3. Check vehicle document expirations (insurance, IRP, IFTA, etc.)
            foreach ($vehicle->documents as $doc) {
                if (!$doc->expiration_date || $doc->status === VehicleDocument::STATUS_EXPIRED) {
                    continue;
                }
                $days = $this->getDaysUntil($doc->expiration_date);
                if ($days !== null && $daysThresholds->contains($days)) {
                    $this->notifyAll(
                        $vehicle,
                        new VehicleDocumentExpiringNotification(
                            $vehicle, $days, $doc->document_type_name,
                            $doc->document_number, $doc->expiration_date->format('m-d-Y')
                        )
                    );
                    $counts['documents']++;
                }
            }
        }

        $summary = collect($counts)->map(fn($v, $k) => ucfirst($k) . ": {$v}")->join(', ');
        $this->info("Notifications sent - {$summary}");
        Log::info('Vehicle expirations check completed', $counts);

        return self::SUCCESS;
    }

    private function getDaysUntil($date): ?int
    {
        if (!$date) {
            return null;
        }
        $days = Carbon::today()->diffInDays($date instanceof Carbon ? $date : Carbon::parse($date), false);
        return $days > 0 ? (int) $days : null;
    }

    private function notifyAll(Vehicle $vehicle, $notification): void
    {
        try {
            // Notify carrier users
            foreach ($vehicle->carrier->userCarriers as $carrierDetail) {
                if ($carrierDetail->user) {
                    $carrierDetail->user->notify($notification);
                }
            }

            // Notify superadmins
            $admins = User::role('superadmin')->get();
            foreach ($admins as $admin) {
                $admin->notify($notification);
            }
        } catch (\Exception $e) {
            Log::error('Failed to send vehicle expiration notification', [
                'vehicle_id' => $vehicle->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
