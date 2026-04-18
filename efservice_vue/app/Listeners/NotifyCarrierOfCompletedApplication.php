<?php

namespace App\Listeners;

use App\Events\DriverApplicationCompleted;
use App\Models\User;
use App\Notifications\Admin\Driver\DriverApplicationCompletedNotification;
use Illuminate\Support\Facades\Log;

/**
 * Notify Carrier Of Completed Application Listener
 * 
 * Notifica al carrier y admins cuando un conductor completa su aplicación.
 */
class NotifyCarrierOfCompletedApplication
{
    /**
     * Handle the event.
     */
    public function handle(DriverApplicationCompleted $event): void
    {
        $driver = $event->driver;
        $carrier = $driver->carrier;
        $user = $driver->user;

        if (!$carrier || !$user) {
            Log::warning('NotifyCarrierOfCompletedApplication: Missing carrier or user', [
                'driver_id' => $driver->id,
            ]);
            return;
        }

        Log::info('Driver application completed - sending notifications', [
            'driver_id' => $driver->id,
            'driver_name' => $user->name,
            'carrier_id' => $carrier->id,
            'carrier_name' => $carrier->name,
        ]);

        try {
            $notification = new DriverApplicationCompletedNotification($user, $carrier, $driver);
            $notificationService = app(\App\Services\NotificationService::class);

            // Notificar a superadmins
            $admins = User::role('superadmin')->get();
            foreach ($admins as $admin) {
                $notificationService->sendWithPreferences($admin, $notification, 'driver_registration');
            }

            // Notificar a usuarios del carrier
            $carrierUsers = $carrier->userCarriers()->with('user')->get();
            foreach ($carrierUsers as $carrierDetail) {
                if ($carrierDetail->user) {
                    $notificationService->sendWithPreferences($carrierDetail->user, $notification, 'driver_registration');
                }
            }

            Log::info('Driver application completed notifications sent', [
                'driver_id' => $driver->id,
                'admins_notified' => $admins->count(),
                'carrier_users_notified' => $carrierUsers->count(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send driver application completed notifications', [
                'driver_id' => $driver->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
