<?php

namespace App\Notifications\Admin\User;

use App\Models\User;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

// Not queued: plain-text password must never be serialized into the job payload.
class NewUserNotification extends Notification
{
    protected $user;
    protected $password;

    public function __construct(User $user, string $password)
    {
        $this->user     = $user;
        $this->password = $password;
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {

        return (new MailMessage)
            ->subject('Welcome to EF Services')
            ->greeting('Hello ' . $this->user->name . '!')
            ->line('Your account has been successfully created.')
            ->line('Your access credentials are:')
            ->line('Email: ' . $this->user->email)
            ->line('Password: ' . $this->password)
            ->action('Log In', url('/login'))
            ->line('Please change your password after logging in for the first time.')
            ->line('Thank you for joining us!');
    }
}
