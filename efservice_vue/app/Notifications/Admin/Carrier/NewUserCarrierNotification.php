<?php

namespace App\Notifications\Admin\Carrier;

use App\Models\Carrier;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class NewUserCarrierNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected User $user;
    protected Carrier $carrier;

    public function __construct(User $user, Carrier $carrier)
    {
        $this->user = $user;
        $this->carrier = $carrier;

        Log::info('NewUserCarrierNotification constructor called', [
            'user_id' => $user->id,
            'carrier_id' => $carrier->id,
        ]);
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $url = route('admin.carriers.users.index', $this->carrier);

        try {
            Log::info('NewUserCarrierNotification toMail method called', [
                'carrier_name' => $this->carrier->name,
                'user_details' => [
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                ],
                'url' => $url,
            ]);

            return (new MailMessage)
                ->subject('New Carrier User Created')
                ->greeting('Hello!')
                ->line('A new carrier user has been created in the system.')
                ->line('User details:')
                ->line('Name: ' . $this->user->name)
                ->line('Email: ' . $this->user->email)
                ->line('Carrier: ' . $this->carrier->name)
                ->line('Creation date: ' . $this->user->created_at->format('m/d/Y H:i'))
                ->action('View User', $url)
                ->line('Thank you for using our application.');
        } catch (\Throwable $e) {
            Log::error('Error in NewUserCarrierNotification::toMail', [
                'error' => $e->getMessage(),
                'carrier_id' => $this->carrier->id,
                'user_id' => $this->user->id,
            ]);

            return (new MailMessage)
                ->subject('New Carrier User Created')
                ->greeting('Hello!')
                ->line('A new carrier user has been created in the system.')
                ->line('Name: ' . $this->user->name)
                ->line('Email: ' . $this->user->email)
                ->line('Carrier: ' . $this->carrier->name);
        }
    }

    public function toDatabase($notifiable): array
    {
        return [
            'user_id' => $this->user->id,
            'carrier_id' => $this->carrier->id,
            'title' => 'New carrier user created',
            'message' => "New user registered for carrier {$this->carrier->name}: {$this->user->name}",
            'icon' => 'UserPlus',
            'category' => 'carrier_registration',
            'url' => route('admin.carriers.users.index', $this->carrier),
        ];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'user_id' => $this->user->id,
            'carrier_id' => $this->carrier->id,
            'title' => 'New carrier user created',
            'message' => "New user registered for carrier {$this->carrier->name}: {$this->user->name}",
            'icon' => 'UserPlus',
            'category' => 'carrier_registration',
            'url' => route('admin.carriers.users.index', $this->carrier),
        ];
    }
}
