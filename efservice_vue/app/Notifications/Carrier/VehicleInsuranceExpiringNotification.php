<?php

namespace App\Notifications\Carrier;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Admin\Vehicle\Vehicle;

class VehicleInsuranceExpiringNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Vehicle $vehicle;
    protected int $daysRemaining;
    protected ?string $policyNumber;
    protected ?string $expirationDate;

    public function __construct(Vehicle $vehicle, int $daysRemaining, ?string $policyNumber = null, ?string $expirationDate = null)
    {
        $this->vehicle = $vehicle;
        $this->daysRemaining = $daysRemaining;
        $this->policyNumber = $policyNumber;
        $this->expirationDate = $expirationDate;
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $urgency = $this->daysRemaining <= 7 ? 'URGENT: ' : '';
        $unit = $this->vehicle->company_unit_number ?? $this->vehicle->id;
        
        return (new MailMessage)
            ->subject($urgency . 'Vehicle Insurance Expiring: ' . $unit)
            ->greeting('Hello,')
            ->line('A vehicle insurance policy is expiring soon.')
            ->line('**Vehicle:** ' . $unit)
            ->when($this->policyNumber, fn($mail) => $mail->line('**Policy Number:** ' . $this->policyNumber))
            ->line('**Expiration Date:** ' . ($this->expirationDate ?? 'N/A'))
            ->line('**Days Remaining:** ' . $this->daysRemaining)
            ->action('View Vehicle', route('carrier.vehicles.show', $this->vehicle->id))
            ->line('Please renew the insurance before expiration.');
    }

    public function toArray(object $notifiable): array
    {
        $unit = $this->vehicle->company_unit_number ?? $this->vehicle->id;

        return [
            'title' => 'Vehicle Insurance Expiring',
            'message' => 'Vehicle ' . $unit . ' insurance expires in ' . $this->daysRemaining . ' days.',
            'type' => 'vehicle_insurance_expiring',
            'category' => 'vehicle_compliance',
            'icon' => 'Shield',
            'urgent' => $this->daysRemaining <= 7,
            'vehicle_id' => $this->vehicle->id,
            'vehicle_unit' => $unit,
            'policy_number' => $this->policyNumber,
            'days_remaining' => $this->daysRemaining,
            'expiration_date' => $this->expirationDate,
            'url' => route('carrier.vehicles.show', $this->vehicle->id),
        ];
    }
}
