<?php

namespace App\Services\Notification;

use App\Models\User;
use App\Models\Carrier;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class EmailNotificationService
{
    /**
     * Send email notification to admins about carrier registration
     */
    public function sendCarrierRegistrationEmail(Carrier $carrier, string $eventType, ?string $step, array $data = []): bool
    {
        try {
            $recipients = $this->getAdminRecipients();

            foreach ($recipients as $recipient) {
                Mail::to($recipient->email)->queue(
                    new \App\Mail\NewCarrierAdminNotification($carrier, $eventType, $step, $data)
                );
            }

            Log::info('Carrier registration emails queued', [
                'carrier_id' => $carrier->id,
                'event_type' => $eventType,
                'recipients_count' => count($recipients),
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send carrier registration email', [
                'carrier_id' => $carrier->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Send generic notification email
     */
    public function sendNotificationEmail(User $user, string $subject, string $message, array $data = []): bool
    {
        try {
            Mail::to($user->email)->queue(
                new \App\Mail\AdminNotificationMail($subject, $message, $data)
            );

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send notification email', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Send bulk notification emails
     */
    public function sendBulkEmails(array $recipients, string $subject, string $message, array $data = []): int
    {
        $sent = 0;

        foreach ($recipients as $recipient) {
            try {
                Mail::to($recipient)->queue(
                    new \App\Mail\AdminNotificationMail($subject, $message, $data)
                );
                $sent++;
            } catch (\Exception $e) {
                Log::error('Failed to send bulk email', [
                    'recipient' => $recipient,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $sent;
    }

    /**
     * Get admin recipients for notifications
     */
    private function getAdminRecipients(): \Illuminate\Database\Eloquent\Collection
    {
        return User::role('superadmin')->get();
    }
}
