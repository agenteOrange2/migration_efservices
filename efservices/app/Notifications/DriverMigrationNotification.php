<?php

namespace App\Notifications;

use App\Models\MigrationRecord;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notification sent when a driver migration is completed.
 */
class DriverMigrationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Number of times to retry the notification.
     */
    public int $tries = 3;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public MigrationRecord $migrationRecord,
        public string $recipientType
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     * Only sent to system admins - carriers should not be aware of driver migrations.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $driverName = $this->getDriverFullName();
        $sourceCarrier = $this->migrationRecord->sourceCarrier->name;
        $targetCarrier = $this->migrationRecord->targetCarrier->name;
        $migrationDate = $this->migrationRecord->migrated_at->format('F j, Y');

        $subject = "Driver Migration: {$driverName} transferred from {$sourceCarrier} to {$targetCarrier}";

        return (new MailMessage)
            ->subject($subject)
            ->greeting($this->getGreeting($notifiable))
            ->line("A driver migration has been completed on {$migrationDate}.")
            ->line("**Driver:** {$driverName}")
            ->line("**From Carrier:** {$sourceCarrier}")
            ->line("**To Carrier:** {$targetCarrier}")
            ->line('A complete archive of the driver\'s records has been preserved for compliance.')
            ->action('View Migration Records', url('/admin/drivers/migration-reports'));
    }

    /**
     * Get the driver's full name from the snapshot.
     */
    protected function getDriverFullName(): string
    {
        $snapshot = $this->migrationRecord->driver_snapshot['personal_info'] ?? [];
        $name = $snapshot['name'] ?? '';
        $lastName = $snapshot['last_name'] ?? '';
        return trim("{$name} {$lastName}");
    }

    /**
     * Get greeting based on recipient.
     */
    protected function getGreeting(object $notifiable): string
    {
        return "Hello {$notifiable->name},";
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'migration_record_id' => $this->migrationRecord->id,
            'recipient_type' => $this->recipientType,
            'driver_name' => $this->getDriverFullName(),
            'source_carrier' => $this->migrationRecord->sourceCarrier->name,
            'target_carrier' => $this->migrationRecord->targetCarrier->name,
        ];
    }
}
