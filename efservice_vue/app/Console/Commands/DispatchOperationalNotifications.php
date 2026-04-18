<?php

namespace App\Console\Commands;

use App\Models\Admin\Driver\DriverLicense;
use App\Models\Admin\Driver\DriverMedicalQualification;
use App\Models\Admin\Vehicle\Vehicle;
use App\Models\Admin\Vehicle\VehicleDocument;
use App\Models\Admin\Vehicle\VehicleMaintenance;
use App\Models\Carrier;
use App\Models\User;
use App\Notifications\Admin\Vehicle\MaintenanceDueNotification;
use App\Notifications\Carrier\DriverLicenseExpiringNotification;
use App\Notifications\Carrier\DriverMedicalExpiringNotification;
use App\Notifications\Carrier\VehicleDocumentExpiringNotification;
use App\Notifications\Carrier\VehicleInsuranceExpiringNotification;
use App\Notifications\Carrier\VehicleInspectionExpiringNotification;
use App\Notifications\Carrier\VehicleMaintenanceDueNotification;
use App\Notifications\Carrier\VehicleRegistrationExpiringNotification;
use App\Notifications\CarrierNotification;
use App\Notifications\Driver\LicenseExpiringNotification;
use App\Notifications\Driver\MedicalExpiringNotification;
use App\Services\NotificationService;
use Carbon\CarbonInterface;
use Illuminate\Console\Command;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\Notification;

class DispatchOperationalNotifications extends Command
{
    protected $signature = 'notifications:dispatch-operational-alerts {--dry-run : Show what would be sent without dispatching notifications}';

    protected $description = 'Dispatch registration/compliance notifications for expiring driver and vehicle records.';

    private const DAY_THRESHOLDS = [30, 14, 7, 3, 1, 0];

    private bool $dryRun = false;

    private array $stats = [
        'admin' => 0,
        'carrier' => 0,
        'driver' => 0,
        'skipped' => 0,
    ];

    public function handle(NotificationService $notificationService): int
    {
        $this->dryRun = (bool) $this->option('dry-run');

        $this->dispatchDriverLicenseAlerts($notificationService);
        $this->dispatchMedicalAlerts($notificationService);
        $this->dispatchVehicleRegistrationAlerts($notificationService);
        $this->dispatchVehicleInspectionAlerts($notificationService);
        $this->dispatchVehicleInsuranceAlerts($notificationService);
        $this->dispatchVehicleDocumentAlerts($notificationService);
        $this->dispatchMaintenanceAlerts($notificationService);

        $this->table(
            ['Target', $this->dryRun ? 'Would Send' : 'Sent'],
            [
                ['Admin', $this->stats['admin']],
                ['Carrier', $this->stats['carrier']],
                ['Driver', $this->stats['driver']],
                ['Skipped', $this->stats['skipped']],
            ]
        );

        $this->info($this->dryRun
            ? 'Dry run completed for operational notifications.'
            : 'Operational notifications dispatched successfully.');

        return self::SUCCESS;
    }

    private function dispatchDriverLicenseAlerts(NotificationService $notificationService): void
    {
        DriverLicense::query()
            ->with(['driverDetail.user', 'driverDetail.carrier.userCarriers.user'])
            ->whereNotNull('expiration_date')
            ->whereDate('expiration_date', '>=', today())
            ->whereDate('expiration_date', '<=', today()->addDays(max(self::DAY_THRESHOLDS)))
            ->chunkById(100, function ($licenses) use ($notificationService) {
                foreach ($licenses as $license) {
                    $driver = $license->driverDetail;
                    $user = $driver?->user;
                    $carrier = $driver?->carrier;

                    if (! $driver || ! $user) {
                        continue;
                    }

                    $daysRemaining = $this->daysRemaining($license->expiration_date);
                    if (! $this->shouldNotifyForDays($daysRemaining)) {
                        continue;
                    }

                    $expirationDate = $license->expiration_date?->format('n/j/Y');
                    $criteria = [
                        'type' => 'license_expiring',
                        'driver_id' => $driver->id,
                        'days_remaining' => $daysRemaining,
                    ];

                    $this->notifyAdmins(
                        category: 'driver_compliance',
                        title: 'Driver License Expiring',
                        message: "{$user->name}'s license expires in {$daysRemaining} day(s).",
                        url: route('admin.drivers.show', $driver),
                        icon: 'CreditCard',
                        criteria: $criteria + ['entity_type' => 'driver_license'],
                        level: $daysRemaining <= 7 ? 'warning' : 'info',
                        extra: [
                            'driver_id' => $driver->id,
                            'driver_name' => $user->name,
                            'carrier_id' => $carrier?->id,
                            'license_number' => $license->license_number,
                            'expiration_date' => $expirationDate,
                            'days_remaining' => $daysRemaining,
                        ],
                    );

                    foreach ($this->carrierUsers($carrier) as $carrierUser) {
                        $this->notifyWithPreferences(
                            $notificationService,
                            $carrierUser,
                            new DriverLicenseExpiringNotification($driver, $daysRemaining, $license->license_number, $expirationDate),
                            'driver_compliance',
                            $criteria,
                            'carrier',
                        );
                    }

                    $this->notifyWithPreferences(
                        $notificationService,
                        $user,
                        new LicenseExpiringNotification($daysRemaining, $expirationDate),
                        'personal_compliance',
                        $criteria,
                        'driver',
                    );
                }
            });
    }

    private function dispatchMedicalAlerts(NotificationService $notificationService): void
    {
        DriverMedicalQualification::query()
            ->with(['driverDetail.user', 'driverDetail.carrier.userCarriers.user'])
            ->whereNotNull('medical_card_expiration_date')
            ->whereDate('medical_card_expiration_date', '>=', today())
            ->whereDate('medical_card_expiration_date', '<=', today()->addDays(max(self::DAY_THRESHOLDS)))
            ->chunkById(100, function ($records) use ($notificationService) {
                foreach ($records as $medical) {
                    $driver = $medical->driverDetail;
                    $user = $driver?->user;
                    $carrier = $driver?->carrier;

                    if (! $driver || ! $user) {
                        continue;
                    }

                    $daysRemaining = $this->daysRemaining($medical->medical_card_expiration_date);
                    if (! $this->shouldNotifyForDays($daysRemaining)) {
                        continue;
                    }

                    $expirationDate = $medical->medical_card_expiration_date?->format('n/j/Y');
                    $criteria = [
                        'type' => 'medical_expiring',
                        'driver_id' => $driver->id,
                        'days_remaining' => $daysRemaining,
                    ];

                    $this->notifyAdmins(
                        category: 'driver_compliance',
                        title: 'Driver Medical Card Expiring',
                        message: "{$user->name}'s medical card expires in {$daysRemaining} day(s).",
                        url: route('admin.drivers.show', $driver),
                        icon: 'Heart',
                        criteria: $criteria + ['entity_type' => 'driver_medical'],
                        level: $daysRemaining <= 7 ? 'warning' : 'info',
                        extra: [
                            'driver_id' => $driver->id,
                            'driver_name' => $user->name,
                            'carrier_id' => $carrier?->id,
                            'expiration_date' => $expirationDate,
                            'days_remaining' => $daysRemaining,
                        ],
                    );

                    foreach ($this->carrierUsers($carrier) as $carrierUser) {
                        $this->notifyWithPreferences(
                            $notificationService,
                            $carrierUser,
                            new DriverMedicalExpiringNotification($driver, $daysRemaining, $expirationDate),
                            'driver_compliance',
                            $criteria,
                            'carrier',
                        );
                    }

                    $this->notifyWithPreferences(
                        $notificationService,
                        $user,
                        new MedicalExpiringNotification($daysRemaining, $expirationDate),
                        'personal_compliance',
                        $criteria,
                        'driver',
                    );
                }
            });
    }

    private function dispatchVehicleRegistrationAlerts(NotificationService $notificationService): void
    {
        Vehicle::query()
            ->with(['carrier.userCarriers.user'])
            ->whereNotNull('registration_expiration_date')
            ->whereDate('registration_expiration_date', '>=', today())
            ->whereDate('registration_expiration_date', '<=', today()->addDays(max(self::DAY_THRESHOLDS)))
            ->chunkById(100, function ($vehicles) use ($notificationService) {
                foreach ($vehicles as $vehicle) {
                    $daysRemaining = $this->daysRemaining($vehicle->registration_expiration_date);
                    if (! $this->shouldNotifyForDays($daysRemaining)) {
                        continue;
                    }

                    $criteria = [
                        'type' => 'vehicle_registration_expiring',
                        'vehicle_id' => $vehicle->id,
                        'days_remaining' => $daysRemaining,
                    ];

                    $this->notifyAdmins(
                        category: 'vehicle_compliance',
                        title: 'Vehicle Registration Expiring',
                        message: 'Vehicle ' . $this->vehicleUnit($vehicle) . " registration expires in {$daysRemaining} day(s).",
                        url: route('admin.vehicles.show', $vehicle),
                        icon: 'FileBadge',
                        criteria: $criteria + ['entity_type' => 'vehicle_registration'],
                        level: $daysRemaining <= 7 ? 'warning' : 'info',
                        extra: [
                            'vehicle_id' => $vehicle->id,
                            'vehicle_unit' => $this->vehicleUnit($vehicle),
                            'carrier_id' => $vehicle->carrier_id,
                            'expiration_date' => $vehicle->registration_expiration_date?->format('n/j/Y'),
                            'days_remaining' => $daysRemaining,
                        ],
                    );

                    foreach ($this->carrierUsers($vehicle->carrier) as $carrierUser) {
                        $this->notifyWithPreferences(
                            $notificationService,
                            $carrierUser,
                            new VehicleRegistrationExpiringNotification($vehicle, $daysRemaining, $vehicle->registration_expiration_date?->format('n/j/Y')),
                            'vehicle_compliance',
                            $criteria,
                            'carrier',
                        );
                    }
                }
            });
    }

    private function dispatchVehicleInspectionAlerts(NotificationService $notificationService): void
    {
        Vehicle::query()
            ->with(['carrier.userCarriers.user'])
            ->whereNotNull('annual_inspection_expiration_date')
            ->whereDate('annual_inspection_expiration_date', '>=', today())
            ->whereDate('annual_inspection_expiration_date', '<=', today()->addDays(max(self::DAY_THRESHOLDS)))
            ->chunkById(100, function ($vehicles) use ($notificationService) {
                foreach ($vehicles as $vehicle) {
                    $daysRemaining = $this->daysRemaining($vehicle->annual_inspection_expiration_date);
                    if (! $this->shouldNotifyForDays($daysRemaining)) {
                        continue;
                    }

                    $criteria = [
                        'type' => 'vehicle_inspection_expiring',
                        'vehicle_id' => $vehicle->id,
                        'days_remaining' => $daysRemaining,
                    ];

                    $this->notifyAdmins(
                        category: 'vehicle_compliance',
                        title: 'Vehicle Inspection Expiring',
                        message: 'Vehicle ' . $this->vehicleUnit($vehicle) . " inspection expires in {$daysRemaining} day(s).",
                        url: route('admin.vehicles.show', $vehicle),
                        icon: 'ClipboardCheck',
                        criteria: $criteria + ['entity_type' => 'vehicle_inspection'],
                        level: $daysRemaining <= 7 ? 'warning' : 'info',
                        extra: [
                            'vehicle_id' => $vehicle->id,
                            'vehicle_unit' => $this->vehicleUnit($vehicle),
                            'carrier_id' => $vehicle->carrier_id,
                            'expiration_date' => $vehicle->annual_inspection_expiration_date?->format('n/j/Y'),
                            'days_remaining' => $daysRemaining,
                        ],
                    );

                    foreach ($this->carrierUsers($vehicle->carrier) as $carrierUser) {
                        $this->notifyWithPreferences(
                            $notificationService,
                            $carrierUser,
                            new VehicleInspectionExpiringNotification($vehicle, $daysRemaining, $vehicle->annual_inspection_expiration_date?->format('n/j/Y')),
                            'vehicle_compliance',
                            $criteria,
                            'carrier',
                        );
                    }
                }
            });
    }

    private function dispatchVehicleInsuranceAlerts(NotificationService $notificationService): void
    {
        VehicleDocument::query()
            ->with(['vehicle.carrier.userCarriers.user'])
            ->where('document_type', VehicleDocument::DOC_TYPE_INSURANCE)
            ->whereNotNull('expiration_date')
            ->whereDate('expiration_date', '>=', today())
            ->whereDate('expiration_date', '<=', today()->addDays(max(self::DAY_THRESHOLDS)))
            ->chunkById(100, function ($documents) use ($notificationService) {
                foreach ($documents as $document) {
                    $vehicle = $document->vehicle;
                    if (! $vehicle) {
                        continue;
                    }

                    $daysRemaining = $this->daysRemaining($document->expiration_date);
                    if (! $this->shouldNotifyForDays($daysRemaining)) {
                        continue;
                    }

                    $criteria = [
                        'type' => 'vehicle_insurance_expiring',
                        'vehicle_id' => $vehicle->id,
                        'document_id' => $document->id,
                        'days_remaining' => $daysRemaining,
                    ];

                    $this->notifyAdmins(
                        category: 'vehicle_compliance',
                        title: 'Vehicle Insurance Expiring',
                        message: 'Vehicle ' . $this->vehicleUnit($vehicle) . " insurance expires in {$daysRemaining} day(s).",
                        url: route('admin.vehicles.show', $vehicle),
                        icon: 'Shield',
                        criteria: $criteria + ['entity_type' => 'vehicle_insurance'],
                        level: $daysRemaining <= 7 ? 'warning' : 'info',
                        extra: [
                            'vehicle_id' => $vehicle->id,
                            'vehicle_unit' => $this->vehicleUnit($vehicle),
                            'carrier_id' => $vehicle->carrier_id,
                            'policy_number' => $document->document_number,
                            'expiration_date' => $document->expiration_date?->format('n/j/Y'),
                            'days_remaining' => $daysRemaining,
                        ],
                    );

                    foreach ($this->carrierUsers($vehicle->carrier) as $carrierUser) {
                        $this->notifyWithPreferences(
                            $notificationService,
                            $carrierUser,
                            new VehicleInsuranceExpiringNotification($vehicle, $daysRemaining, $document->document_number, $document->expiration_date?->format('n/j/Y')),
                            'vehicle_compliance',
                            $criteria,
                            'carrier',
                        );
                    }
                }
            });
    }

    private function dispatchVehicleDocumentAlerts(NotificationService $notificationService): void
    {
        VehicleDocument::query()
            ->with(['vehicle.carrier.userCarriers.user'])
            ->whereNotIn('document_type', [
                VehicleDocument::DOC_TYPE_INSURANCE,
            ])
            ->whereNotNull('expiration_date')
            ->whereDate('expiration_date', '>=', today())
            ->whereDate('expiration_date', '<=', today()->addDays(max(self::DAY_THRESHOLDS)))
            ->chunkById(100, function ($documents) use ($notificationService) {
                foreach ($documents as $document) {
                    $vehicle = $document->vehicle;
                    if (! $vehicle) {
                        continue;
                    }

                    $daysRemaining = $this->daysRemaining($document->expiration_date);
                    if (! $this->shouldNotifyForDays($daysRemaining)) {
                        continue;
                    }

                    $category = in_array($document->document_type, [
                        VehicleDocument::DOC_TYPE_REGISTRATION,
                        VehicleDocument::DOC_TYPE_ANNUAL_INSPECTION,
                    ], true) ? 'vehicle_compliance' : 'vehicle_documents';

                    $criteria = [
                        'type' => 'vehicle_document_expiring',
                        'vehicle_id' => $vehicle->id,
                        'document_id' => $document->id,
                        'days_remaining' => $daysRemaining,
                    ];

                    $this->notifyAdmins(
                        category: $category,
                        title: 'Vehicle Document Expiring',
                        message: $document->document_type_name . ' for vehicle ' . $this->vehicleUnit($vehicle) . " expires in {$daysRemaining} day(s).",
                        url: route('admin.vehicles.show', $vehicle),
                        icon: 'FileText',
                        criteria: $criteria + ['entity_type' => 'vehicle_document'],
                        level: $daysRemaining <= 7 ? 'warning' : 'info',
                        extra: [
                            'vehicle_id' => $vehicle->id,
                            'vehicle_unit' => $this->vehicleUnit($vehicle),
                            'carrier_id' => $vehicle->carrier_id,
                            'document_id' => $document->id,
                            'document_type' => $document->document_type,
                            'document_number' => $document->document_number,
                            'expiration_date' => $document->expiration_date?->format('n/j/Y'),
                            'days_remaining' => $daysRemaining,
                        ],
                    );

                    foreach ($this->carrierUsers($vehicle->carrier) as $carrierUser) {
                        $this->notifyWithPreferences(
                            $notificationService,
                            $carrierUser,
                            new VehicleDocumentExpiringNotification($vehicle, $daysRemaining, $document->document_type_name, $document->document_number, $document->expiration_date?->format('n/j/Y')),
                            $category,
                            $criteria,
                            'carrier',
                        );
                    }
                }
            });
    }

    private function dispatchMaintenanceAlerts(NotificationService $notificationService): void
    {
        VehicleMaintenance::query()
            ->with(['vehicle.carrier.userCarriers.user'])
            ->whereNotNull('next_service_date')
            ->where('status', false)
            ->whereDate('next_service_date', '>=', today())
            ->whereDate('next_service_date', '<=', today()->addDays(max(self::DAY_THRESHOLDS)))
            ->chunkById(100, function ($maintenances) use ($notificationService) {
                foreach ($maintenances as $maintenance) {
                    $vehicle = $maintenance->vehicle;

                    if (! $vehicle) {
                        continue;
                    }

                    $daysRemaining = $this->daysRemaining($maintenance->next_service_date);
                    if (! $this->shouldNotifyForDays($daysRemaining)) {
                        continue;
                    }

                    $criteria = [
                        'type' => 'vehicle_maintenance_due',
                        'vehicle_id' => $vehicle->id,
                        'maintenance_id' => $maintenance->id,
                        'days_remaining' => $daysRemaining,
                    ];

                    $this->notifyAdminUsersWithPreferences(
                        $notificationService,
                        new MaintenanceDueNotification($maintenance, $daysRemaining),
                        'vehicle_maintenance',
                        $criteria,
                    );

                    $maintenanceType = trim((string) ($maintenance->service_tasks ?: $maintenance->description ?: 'Scheduled service'));
                    $dueDate = $maintenance->next_service_date?->format('n/j/Y');

                    foreach ($this->carrierUsers($vehicle->carrier) as $carrierUser) {
                        $this->notifyWithPreferences(
                            $notificationService,
                            $carrierUser,
                            new VehicleMaintenanceDueNotification($vehicle, $maintenanceType, $daysRemaining, $dueDate),
                            'vehicle_maintenance',
                            $criteria,
                            'carrier',
                        );
                    }
                }
            });
    }

    private function notifyAdmins(
        string $category,
        string $title,
        string $message,
        string $url,
        string $icon,
        array $criteria,
        string $level = 'info',
        array $extra = [],
    ): void {
        $admins = User::role('superadmin')->get();
        $adminCriteria = $criteria;

        if (isset($adminCriteria['type'])) {
            $adminCriteria['notification_key'] = $adminCriteria['type'];
            unset($adminCriteria['type']);
        }

        foreach ($admins as $admin) {
            if (! $admin->isNotificationInAppEnabled($category)) {
                $this->stats['skipped']++;
                continue;
            }

            if ($this->wasRecentlySent($admin, $adminCriteria)) {
                $this->stats['skipped']++;
                continue;
            }

            if (! $this->dryRun) {
                $admin->notify(new CarrierNotification(
                    $title,
                    $message,
                    $level,
                    array_merge($extra, $adminCriteria, [
                        'category' => $category,
                        'icon' => $icon,
                        'url' => $url,
                        'recipient_type' => 'admin',
                    ])
                ));
            }

            $this->stats['admin']++;
        }
    }

    private function notifyAdminUsersWithPreferences(
        NotificationService $notificationService,
        Notification $notification,
        string $category,
        array $criteria,
    ): void {
        foreach (User::role('superadmin')->get() as $admin) {
            if ($this->wasRecentlySent($admin, $criteria)) {
                $this->stats['skipped']++;
                continue;
            }

            if (! $this->dryRun) {
                $sent = $notificationService->sendWithPreferences($admin, $notification, $category);

                if (! $sent) {
                    $this->stats['skipped']++;
                    continue;
                }
            }

            $this->stats['admin']++;
        }
    }

    private function notifyWithPreferences(
        NotificationService $notificationService,
        User $user,
        Notification $notification,
        string $category,
        array $criteria,
        string $bucket,
    ): void {
        if ($this->wasRecentlySent($user, $criteria)) {
            $this->stats['skipped']++;
            return;
        }

        if (! $this->dryRun) {
            $sent = $notificationService->sendWithPreferences($user, $notification, $category);

            if (! $sent) {
                $this->stats['skipped']++;
                return;
            }
        }

        $this->stats[$bucket]++;
    }

    private function carrierUsers(?Carrier $carrier)
    {
        if (! $carrier) {
            return collect();
        }

        return $carrier->userCarriers
            ->pluck('user')
            ->filter()
            ->values();
    }

    private function daysRemaining(?CarbonInterface $date): int
    {
        if (! $date) {
            return -1;
        }

        return today()->diffInDays($date, false);
    }

    private function shouldNotifyForDays(int $daysRemaining): bool
    {
        return in_array($daysRemaining, self::DAY_THRESHOLDS, true);
    }

    private function wasRecentlySent(User $user, array $criteria, int $hours = 26): bool
    {
        return $user->notifications()
            ->where('created_at', '>=', now()->subHours($hours))
            ->get()
            ->contains(function (DatabaseNotification $notification) use ($criteria) {
                $payload = $this->normalizePayload($notification->data ?? []);

                foreach ($criteria as $key => $value) {
                    if ((string) ($payload[$key] ?? '') !== (string) $value) {
                        return false;
                    }
                }

                return true;
            });
    }

    private function normalizePayload(array $payload): array
    {
        $nested = $payload['data'] ?? [];
        $nested = is_array($nested) ? $nested : [];

        return array_merge($nested, array_diff_key($payload, ['data' => true]));
    }

    private function vehicleUnit(Vehicle $vehicle): string
    {
        return (string) ($vehicle->company_unit_number ?: $vehicle->id);
    }
}
