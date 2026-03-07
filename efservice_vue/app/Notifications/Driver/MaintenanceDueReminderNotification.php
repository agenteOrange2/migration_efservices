<?php

namespace App\Notifications\Driver;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MaintenanceDueReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected int $maintenanceId;
    protected string $vehicleUnit;
    protected string $maintenanceType;
    protected int $daysRemaining;

    public function __construct(int $maintenanceId, string $vehicleUnit, string $maintenanceType, int $daysRemaining)
    {
        $this->maintenanceId = $maintenanceId;
        $this->vehicleUnit = $vehicleUnit;
        $this->maintenanceType = $maintenanceType;
        $this->daysRemaining = $daysRemaining;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Maintenance Due Soon',
            'message' => $this->maintenanceType . ' for vehicle ' . $this->vehicleUnit . ' is due in ' . $this->daysRemaining . ' days.',
            'type' => 'maintenance_due_reminder',
            'category' => 'maintenance',
            'icon' => 'Wrench',
            'urgent' => $this->daysRemaining <= 3,
            'maintenance_id' => $this->maintenanceId,
            'vehicle_unit' => $this->vehicleUnit,
            'maintenance_type' => $this->maintenanceType,
            'days_remaining' => $this->daysRemaining,
            'url' => route('driver.maintenance.show', $this->maintenanceId),
        ];
    }
}
