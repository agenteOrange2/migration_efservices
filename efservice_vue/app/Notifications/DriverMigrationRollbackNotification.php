<?php

namespace App\Notifications;

use App\Models\MigrationRecord;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notification sent when a driver migration is rolled back.
 * Only sent to system admins - carriers should not be aware of driver migrations.
 */
class DriverMigrationRollbackNotification extends Notification implements ShouldQueue
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
     * Only sent to system admins.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $driverName = $this->getDriverFullName();
        $sourceCarrier = $this->migrationRecord->sourceCarrier->name;
        $targetCarrier = $this->migrationRecord->targetCarrier->name;
        $rollbackDate = $this->migrationRecord->rolled_back_at?->format('F j, Y') ?? now()->format('F j, Y');
        $rollbackReason = $this->migrationRecord->rollback_reason ?? 'No reason provided';

        $subject = "Driver Migration Rollback: {$driverName} returned to {$sourceCarrier}";

        return (new MailMessage)
            ->subject($subject)
            ->greeting("Hello {$notifiable->name},")
            ->line("A driver migration has been rolled back on {$rollbackDate}.")
            ->line("**Driver:** {$driverName}")
            ->line("**Returned To:** {$sourceCarrier}")
            ->line("**From Carrier:** {$targetCarrier}")
            ->line("**Reason:** {$rollbackReason}")
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
            'rollback_reason' => $this->migrationRecord->rollback_reason,
        ];
    }
}
