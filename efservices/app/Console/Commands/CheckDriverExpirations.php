<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\UserDriverDetail;
use App\Notifications\Driver\LicenseExpiringNotification;
use App\Notifications\Driver\MedicalExpiringNotification;
use App\Notifications\Driver\TwicExpiringNotification;
use App\Notifications\Driver\CertificationExpiringNotification;
use App\Notifications\Carrier\DriverLicenseExpiringNotification;
use App\Notifications\Carrier\DriverMedicalExpiringNotification;
use App\Notifications\Carrier\DriverTwicExpiringNotification;
use App\Notifications\Carrier\DriverCertificationExpiringNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckDriverExpirations extends Command
{
    protected $signature = 'drivers:check-expirations {--days=30,15,7 : Comma-separated days before expiration to notify}';

    protected $description = 'Check for expiring driver licenses, medical cards, TWIC, endorsements and certifications';

    public function handle(): int
    {
        $daysThresholds = collect(explode(',', $this->option('days')))->map(fn($d) => (int) trim($d))->sort()->values();

        $this->info("Checking driver expirations for thresholds: {$daysThresholds->join(', ')} days");

        $counts = [
            'licenses' => 0,
            'medicals' => 0,
            'twic' => 0,
            'endorsements' => 0,
            'courses' => 0,
        ];

        // Get active drivers with all related expirable data
        $drivers = UserDriverDetail::where('status', UserDriverDetail::STATUS_ACTIVE)
            ->with([
                'user',
                'carrier.userCarriers.user',
                'licenses.endorsements',
                'medicalQualification',
                'courses',
                'application.details',
            ])
            ->get();

        foreach ($drivers as $driver) {
            if (!$driver->user || !$driver->carrier) {
                continue;
            }

            // 1. Check license expirations
            foreach ($driver->licenses as $license) {
                if (!$license->expiration_date) {
                    continue;
                }

                $days = $this->getDaysUntil($license->expiration_date);
                if ($days !== null && $daysThresholds->contains($days)) {
                    $this->sendLicenseExpirationNotifications(
                        $driver, $days, $license->license_number, $license->expiration_date->format('m-d-Y')
                    );
                    $counts['licenses']++;
                }

                // 1b. Check endorsement expirations within each license
                foreach ($license->endorsements as $endorsement) {
                    $endExpDate = $endorsement->pivot->expiration_date ?? null;
                    if (!$endExpDate) {
                        continue;
                    }
                    $endDays = $this->getDaysUntil(Carbon::parse($endExpDate));
                    if ($endDays !== null && $daysThresholds->contains($endDays)) {
                        $this->sendGenericExpirationNotifications(
                            $driver, $endDays, 'License Endorsement',
                            $endorsement->name ?? null,
                            Carbon::parse($endExpDate)->format('m-d-Y')
                        );
                        $counts['endorsements']++;
                    }
                }
            }

            // 2. Check medical card expiration
            if ($driver->medicalQualification && $driver->medicalQualification->medical_card_expiration_date) {
                $medExpDate = $driver->medicalQualification->medical_card_expiration_date;
                $days = $this->getDaysUntil($medExpDate);
                if ($days !== null && $daysThresholds->contains($days)) {
                    $this->sendMedicalExpirationNotifications(
                        $driver, $days, $medExpDate instanceof Carbon ? $medExpDate->format('m-d-Y') : (string) $medExpDate
                    );
                    $counts['medicals']++;
                }
            }

            // 3. Check TWIC card expiration
            $appDetails = $driver->application?->details;
            if ($appDetails && $appDetails->has_twic_card && $appDetails->twic_expiration_date) {
                $days = $this->getDaysUntil($appDetails->twic_expiration_date);
                if ($days !== null && $daysThresholds->contains($days)) {
                    $this->sendTwicExpirationNotifications(
                        $driver, $days, $appDetails->twic_expiration_date->format('m-d-Y')
                    );
                    $counts['twic']++;
                }
            }

            // 4. Check course/certification expirations
            foreach ($driver->courses as $course) {
                if (!$course->expiration_date) {
                    continue;
                }
                $days = $this->getDaysUntil($course->expiration_date);
                if ($days !== null && $daysThresholds->contains($days)) {
                    $this->sendGenericExpirationNotifications(
                        $driver, $days, 'Course/Certification',
                        $course->organization_name ?? null,
                        $course->expiration_date->format('m-d-Y')
                    );
                    $counts['courses']++;
                }
            }
        }

        $summary = collect($counts)->map(fn($v, $k) => ucfirst($k) . ": {$v}")->join(', ');
        $this->info("Notifications sent - {$summary}");
        Log::info('Driver expirations check completed', $counts);

        return self::SUCCESS;
    }

    /**
     * Calculate days until a date. Returns null if date is in the past or invalid.
     */
    private function getDaysUntil($date): ?int
    {
        if (!$date) {
            return null;
        }
        $days = Carbon::today()->diffInDays($date instanceof Carbon ? $date : Carbon::parse($date), false);
        return $days > 0 ? (int) $days : null;
    }

    /**
     * Send notifications to driver, carrier users, and admins about an expiring item.
     */
    private function notifyAll(UserDriverDetail $driver, $driverNotification, $carrierAdminNotification): void
    {
        try {
            // Notify the driver
            $driver->user->notify($driverNotification);

            // Notify carrier users
            foreach ($driver->carrier->userCarriers as $carrierDetail) {
                if ($carrierDetail->user) {
                    $carrierDetail->user->notify($carrierAdminNotification);
                }
            }

            // Notify superadmins
            $admins = User::role('superadmin')->get();
            foreach ($admins as $admin) {
                $admin->notify($carrierAdminNotification);
            }
        } catch (\Exception $e) {
            Log::error('Failed to send expiration notification', [
                'driver_id' => $driver->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function sendLicenseExpirationNotifications(UserDriverDetail $driver, int $days, ?string $licenseNumber, ?string $expDate): void
    {
        $this->notifyAll(
            $driver,
            new LicenseExpiringNotification($days, $expDate),
            new DriverLicenseExpiringNotification($driver, $days, $licenseNumber, $expDate)
        );
    }

    private function sendMedicalExpirationNotifications(UserDriverDetail $driver, int $days, ?string $expDate): void
    {
        $this->notifyAll(
            $driver,
            new MedicalExpiringNotification($days, $expDate),
            new DriverMedicalExpiringNotification($driver, $days, $expDate)
        );
    }

    private function sendTwicExpirationNotifications(UserDriverDetail $driver, int $days, ?string $expDate): void
    {
        $this->notifyAll(
            $driver,
            new TwicExpiringNotification($days, $expDate),
            new DriverTwicExpiringNotification($driver, $days, $expDate)
        );
    }

    private function sendGenericExpirationNotifications(UserDriverDetail $driver, int $days, string $type, ?string $name, ?string $expDate): void
    {
        $this->notifyAll(
            $driver,
            new CertificationExpiringNotification($days, $type, $name, $expDate),
            new DriverCertificationExpiringNotification($driver, $days, $type, $name, $expDate)
        );
    }
}
