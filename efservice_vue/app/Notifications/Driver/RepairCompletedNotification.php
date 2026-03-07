<?php

namespace App\Notifications\Driver;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RepairCompletedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected int $repairId;
    protected string $vehicleUnit;
    protected ?string $repairSummary;

    public function __construct(int $repairId, string $vehicleUnit, ?string $repairSummary = null)
    {
        $this->repairId = $repairId;
        $this->vehicleUnit = $vehicleUnit;
        $this->repairSummary = $repairSummary;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Repair Completed',
            'message' => 'Repair for vehicle ' . $this->vehicleUnit . ' has been completed.',
            'type' => 'repair_completed',
            'category' => 'repairs',
            'icon' => 'CheckCircle',
            'repair_id' => $this->repairId,
            'vehicle_unit' => $this->vehicleUnit,
            'repair_summary' => $this->repairSummary,
            'url' => route('driver.emergency-repairs.show', $this->repairId),
        ];
    }
}
