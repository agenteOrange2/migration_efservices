<?php

namespace App\Notifications\Admin\Carrier;

use App\Models\Carrier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class NewCarrierNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Carrier $carrier;

    public function __construct(Carrier $carrier)
    {
        $this->carrier = $carrier;

        Log::info('NewCarrierNotification constructor called', [
            'carrier_id' => $carrier->id,
            'carrier_name' => $carrier->name,
        ]);
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $url = route('admin.carriers.show', $this->carrier);

        try {
            Log::info('NewCarrierNotification toMail method called', [
                'admin_email' => config('app.admin_email'),
                'carrier_details' => [
                    'name' => $this->carrier->name,
                    'address' => $this->carrier->address,
                    'state' => $this->carrier->state,
                ],
                'url' => $url,
            ]);

            return (new MailMessage)
                ->subject('New Carrier Registered in the System')
                ->greeting('Hello Administrator!')
                ->line('A new carrier has been registered in the system.')
                ->line('Carrier details:')
                ->line('Name: ' . $this->carrier->name)
                ->line('Address: ' . $this->carrier->address)
                ->line('State: ' . $this->carrier->state)
                ->line('DOT Number: ' . $this->carrier->dot_number)
                ->line('Creation date: ' . $this->carrier->created_at->format('m/d/Y H:i'))
                ->action('View Carrier', $url);
        } catch (\Throwable $e) {
            Log::error('Error generating notification email', [
                'error' => $e->getMessage(),
                'carrier_id' => $this->carrier->id,
            ]);

            return (new MailMessage)
                ->subject('New Carrier Registered in the System')
                ->greeting('Hello Administrator!')
                ->line('A new carrier has been registered in the system.')
                ->line('Carrier details:')
                ->line('Name: ' . $this->carrier->name)
                ->line('Address: ' . $this->carrier->address)
                ->line('State: ' . $this->carrier->state)
                ->line('DOT Number: ' . $this->carrier->dot_number)
                ->line('Creation date: ' . $this->carrier->created_at->format('m/d/Y H:i'));
        }
    }

    public function toDatabase($notifiable): array
    {
        return [
            'carrier_id' => $this->carrier->id,
            'title' => 'New carrier created',
            'message' => "New carrier registered: {$this->carrier->name}",
            'icon' => 'Building2',
            'category' => 'carriers',
            'url' => route('admin.carriers.show', $this->carrier),
        ];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'carrier_id' => $this->carrier->id,
            'carrier_name' => $this->carrier->name,
            'category' => 'carriers',
            'url' => route('admin.carriers.show', $this->carrier),
        ];
    }
}
