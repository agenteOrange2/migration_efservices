<?php

namespace App\Notifications\Carrier;

use App\Models\User;
use App\Models\Carrier;
use App\Models\UserDriverDetail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class DriverStatusChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected User $driverUser;
    protected Carrier $carrier;
    protected UserDriverDetail $driverDetail;
    protected string $newStatus;
    protected string $oldStatus;

    public function __construct(User $driverUser, Carrier $carrier, UserDriverDetail $driverDetail, string $newStatus, string $oldStatus)
    {
        $this->driverUser = $driverUser;
        $this->carrier = $carrier;
        $this->driverDetail = $driverDetail;
        $this->newStatus = $newStatus;
        $this->oldStatus = $oldStatus;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Driver Status Changed',
            'message' => $this->driverUser->name . ' status changed from ' . $this->oldStatus . ' to ' . $this->newStatus . '.',
            'type' => 'driver_status_changed',
            'category' => 'drivers',
            'icon' => 'UserCog',
            'driver_id' => $this->driverDetail->id,
            'driver_name' => $this->driverUser->name,
            'carrier_id' => $this->carrier->id,
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'url' => '/carrier/drivers/' . $this->driverDetail->id,
        ];
    }
}
