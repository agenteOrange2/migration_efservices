<?php

namespace App\Notifications\Driver;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RepairRequestConfirmedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected int $repairId;
    protected string $vehicleUnit;
    protected ?string $issueDescription;

    public function __construct(int $repairId, string $vehicleUnit, ?string $issueDescription = null)
    {
        $this->repairId = $repairId;
        $this->vehicleUnit = $vehicleUnit;
        $this->issueDescription = $issueDescription;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Repair Request Confirmed',
            'message' => 'Your repair request for vehicle ' . $this->vehicleUnit . ' has been received.',
            'type' => 'repair_request_confirmed',
            'category' => 'repairs',
            'icon' => 'CheckCircle',
            'repair_id' => $this->repairId,
            'vehicle_unit' => $this->vehicleUnit,
            'issue_description' => $this->issueDescription,
            'url' => route('driver.emergency-repairs.show', $this->repairId),
        ];
    }
}
