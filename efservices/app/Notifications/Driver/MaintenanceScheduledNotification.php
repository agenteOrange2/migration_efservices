<?php

namespace App\Notifications\Driver;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MaintenanceScheduledNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected int $maintenanceId;
    protected string $vehicleUnit;
    protected string $maintenanceType;
    protected string $scheduledDate;

    public function __construct(int $maintenanceId, string $vehicleUnit, string $maintenanceType, string $scheduledDate)
    {
        $this->maintenanceId = $maintenanceId;
        $this->vehicleUnit = $vehicleUnit;
        $this->maintenanceType = $maintenanceType;
        $this->scheduledDate = $scheduledDate;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Maintenance Scheduled',
            'message' => $this->maintenanceType . ' scheduled for vehicle ' . $this->vehicleUnit . ' on ' . $this->scheduledDate,
            'type' => 'maintenance_scheduled',
            'category' => 'maintenance',
            'icon' => 'Calendar',
            'maintenance_id' => $this->maintenanceId,
            'vehicle_unit' => $this->vehicleUnit,
            'maintenance_type' => $this->maintenanceType,
            'scheduled_date' => $this->scheduledDate,
            'url' => route('driver.maintenance.show', $this->maintenanceId),
        ];
    }
}
