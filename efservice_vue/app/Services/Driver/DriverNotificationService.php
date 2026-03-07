<?php

namespace App\Services\Driver;

use App\Models\User;
use App\Models\Carrier;
use App\Models\UserDriverDetail;
use App\Notifications\Admin\Driver\NewDriverRegisteredNotification;
use App\Notifications\Admin\Driver\DriverApplicationCompletedNotification;
use App\Notifications\Carrier\DriverDocumentUploadedNotification;
use Illuminate\Support\Facades\Log;

/**
 * Centralized notification service for driver-related events.
 * Handles sending notifications to admins, carrier users, and drivers.
 */
class DriverNotificationService
{
    /**
     * Notify admins and carrier users about a document upload from a driver.
     */
    public static function notifyDocumentUploaded(UserDriverDetail $driver, string $documentType, ?string $documentName = null): void
    {
        try {
            $user = $driver->user;
            $carrier = $driver->carrier;

            if (!$user || !$carrier) {
                return;
            }

            $notification = new \App\Notifications\Carrier\DriverDocumentUploadedNotification(
                $user, $carrier, $driver, $documentType, $documentName
            );

            // Notificar a superadmins
            $admins = User::role('superadmin')->get();
            foreach ($admins as $admin) {
                $admin->notify($notification);
            }

            // Notificar a usuarios del carrier
            $carrierUsers = $carrier->userCarriers()->with('user')->get();
            foreach ($carrierUsers as $carrierDetail) {
                if ($carrierDetail->user) {
                    $carrierDetail->user->notify($notification);
                }
            }

            Log::info('Driver document uploaded notification sent', [
                'driver_id' => $driver->id,
                'document_type' => $documentType,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send driver document uploaded notification', [
                'driver_id' => $driver->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notify admins and carrier users about a driver status change.
     */
    public static function notifyDriverStatusChanged(UserDriverDetail $driver, string $newStatus, string $oldStatus): void
    {
        try {
            $user = $driver->user;
            $carrier = $driver->carrier;

            if (!$user || !$carrier) {
                return;
            }

            $statusLabels = [
                '0' => 'Inactive',
                '1' => 'Active',
                '2' => 'Pending',
            ];

            $newLabel = $statusLabels[$newStatus] ?? $newStatus;
            $oldLabel = $statusLabels[$oldStatus] ?? $oldStatus;

            $notification = new \App\Notifications\Carrier\DriverStatusChangedNotification(
                $user, $carrier, $driver, $newLabel, $oldLabel
            );

            // Notificar a superadmins
            $admins = User::role('superadmin')->get();
            foreach ($admins as $admin) {
                $admin->notify($notification);
            }

            // Notificar a usuarios del carrier
            $carrierUsers = $carrier->userCarriers()->with('user')->get();
            foreach ($carrierUsers as $carrierDetail) {
                if ($carrierDetail->user) {
                    $carrierDetail->user->notify($notification);
                }
            }

            // Notificar al driver si fue activado o desactivado
            $driverNotification = new \App\Notifications\Driver\StatusChangedNotification(
                $driver, $carrier, $newLabel
            );
            $user->notify($driverNotification);

            Log::info('Driver status changed notification sent', [
                'driver_id' => $driver->id,
                'old_status' => $oldLabel,
                'new_status' => $newLabel,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send driver status changed notification', [
                'driver_id' => $driver->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
