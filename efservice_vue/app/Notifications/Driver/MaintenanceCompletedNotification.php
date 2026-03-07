<?php

namespace App\Notifications\Driver;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MaintenanceCompletedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected int $maintenanceId;
    protected string $vehicleUnit;
    protected string $maintenanceType;
    protected ?string $summary;

    public function __construct(int $maintenanceId, string $vehicleUnit, string $maintenanceType, ?string $summary = null)
    {
        $this->maintenanceId = $maintenanceId;
        $this->vehicleUnit = $vehicleUnit;
        $this->maintenanceType = $maintenanceType;
        $this->summary = $summary;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Maintenance Completed',
            'message' => $this->maintenanceType . ' for vehicle ' . $this->vehicleUnit . ' has been completed.',
            'type' => 'maintenance_completed',
            'category' => 'maintenance',
            'icon' => 'CheckCircle',
            'maintenance_id' => $this->maintenanceId,
            'vehicle_unit' => $this->vehicleUnit,
            'maintenance_type' => $this->maintenanceType,
            'summary' => $this->summary,
            'url' => route('driver.maintenance.show', $this->maintenanceId),
        ];
    }
}
