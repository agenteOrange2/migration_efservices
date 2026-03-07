<?php

namespace App\Notifications\Driver;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RepairApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected int $repairId;
    protected string $vehicleUnit;
    protected ?string $approvalDetails;

    public function __construct(int $repairId, string $vehicleUnit, ?string $approvalDetails = null)
    {
        $this->repairId = $repairId;
        $this->vehicleUnit = $vehicleUnit;
        $this->approvalDetails = $approvalDetails;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Repair Approved',
            'message' => 'Your repair request for vehicle ' . $this->vehicleUnit . ' has been approved.',
            'type' => 'repair_approved',
            'category' => 'repairs',
            'icon' => 'CheckCircle',
            'repair_id' => $this->repairId,
            'vehicle_unit' => $this->vehicleUnit,
            'approval_details' => $this->approvalDetails,
            'url' => route('driver.emergency-repairs.show', $this->repairId),
        ];
    }
}
