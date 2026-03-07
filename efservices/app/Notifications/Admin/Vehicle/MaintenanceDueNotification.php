<?php

namespace App\Notifications\Admin\Vehicle;

use App\Models\Admin\Vehicle\VehicleMaintenance;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MaintenanceDueNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $maintenance;
    protected $daysRemaining;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(VehicleMaintenance $maintenance, int $daysRemaining)
    {
        $this->maintenance = $maintenance;
        $this->daysRemaining = $daysRemaining;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $vehicle = $this->maintenance->vehicle;
        $url = url('/admin/vehicles/' . $vehicle->id);
        $unitLabel = $vehicle->company_unit_number ?? $vehicle->id;
        $urgency = $this->daysRemaining <= 7 ? 'URGENT: ' : '';
        
        return (new MailMessage)
            ->subject($urgency . 'Vehicle Maintenance Due: Unit #' . $unitLabel)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('A vehicle maintenance service is coming due and requires attention.')
            ->line('**Vehicle:** Unit #' . $unitLabel . ' (' . ($vehicle->make ?? '') . ' ' . ($vehicle->model ?? '') . ' ' . ($vehicle->year ?? '') . ')')
            ->line('**Service:** ' . ($this->maintenance->service_tasks ?? 'N/A'))
            ->line('**Due Date:** ' . $this->maintenance->next_service_date->format('m/d/Y'))
            ->line('**Days Remaining:** ' . $this->daysRemaining)
            ->action('View Vehicle', $url)
            ->line('Please schedule this service before the due date.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        $vehicle = $this->maintenance->vehicle;
        $unitLabel = $vehicle->company_unit_number ?? $vehicle->id;

        return [
            'title' => 'Vehicle Maintenance Due',
            'message' => 'Unit #' . $unitLabel . ' (' . ($vehicle->make ?? '') . ' ' . ($vehicle->model ?? '') . ') maintenance due in ' . $this->daysRemaining . ' days. Service: ' . ($this->maintenance->service_tasks ?? 'N/A'),
            'type' => 'maintenance_due',
            'category' => 'vehicles',
            'icon' => 'Wrench',
            'urgent' => $this->daysRemaining <= 7,
            'url' => '/admin/vehicles/' . $vehicle->id,
            'vehicle_id' => $vehicle->id,
            'vehicle_unit' => $unitLabel,
            'maintenance_id' => $this->maintenance->id,
            'days_remaining' => $this->daysRemaining,
        ];
    }
}
