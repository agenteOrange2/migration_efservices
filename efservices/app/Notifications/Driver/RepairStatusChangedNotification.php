<?php

namespace App\Notifications\Driver;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RepairStatusChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected int $repairId;
    protected string $vehicleUnit;
    protected string $newStatus;
    protected ?string $notes;

    public function __construct(int $repairId, string $vehicleUnit, string $newStatus, ?string $notes = null)
    {
        $this->repairId = $repairId;
        $this->vehicleUnit = $vehicleUnit;
        $this->newStatus = $newStatus;
        $this->notes = $notes;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Repair Status Updated',
            'message' => 'Repair for vehicle ' . $this->vehicleUnit . ' is now: ' . $this->newStatus,
            'type' => 'repair_status_changed',
            'category' => 'repairs',
            'icon' => 'RefreshCw',
            'repair_id' => $this->repairId,
            'vehicle_unit' => $this->vehicleUnit,
            'new_status' => $this->newStatus,
            'notes' => $this->notes,
            'url' => route('driver.emergency-repairs.show', $this->repairId),
        ];
    }
}
