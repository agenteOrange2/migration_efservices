<?php

namespace App\Policies;

use App\Models\AdminMessage;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class AdminMessagePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Admin can view all messages
        if ($user->hasRole('superadmin')) {
            return true;
        }

        // Carriers can view their own messages
        if ($user->hasRole('user_carrier')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, AdminMessage $message): bool
    {
        // Admin can view all messages
        if ($user->hasRole('superadmin')) {
            return true;
        }

        // Carrier can view if they are the sender or a recipient
        if ($user->hasRole('user_carrier')) {
            $carrierDetails = $user->carrierDetails;

            if (!$carrierDetails || !$carrierDetails->carrier) {
                return false;
            }

            $carrier = $carrierDetails->carrier;

            // Check if carrier is the sender
            if ($message->sender_type === 'App\\Models\\Carrier' 
                && $message->sender_id === $carrier->id) {
                return true;
            }

            // Check if carrier is a recipient
            return $message->recipients()
                ->where('recipient_type', 'carrier')
                ->where('recipient_id', $carrier->id)
                ->exists();
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Admin can create messages
        if ($user->hasRole('superadmin')) {
            return true;
        }

        // Carriers can create messages
        if ($user->hasRole('user_carrier')) {
            return true;
        }

        // Drivers cannot create messages (read-only)
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, AdminMessage $message): bool
    {
        // Only draft messages can be updated
        if ($message->status !== 'draft') {
            return false;
        }

        // Admin can update all draft messages
        if ($user->hasRole('superadmin')) {
            return true;
        }

        // Carrier can update only their own draft messages
        if ($user->hasRole('user_carrier')) {
            $carrierDetails = $user->carrierDetails;

            if (!$carrierDetails || !$carrierDetails->carrier) {
                return false;
            }

            $carrier = $carrierDetails->carrier;

            return $message->sender_type === 'App\\Models\\Carrier' 
                && $message->sender_id === $carrier->id;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, AdminMessage $message): bool
    {
        // Only draft messages can be deleted
        if ($message->status !== 'draft') {
            return false;
        }

        // Admin can delete all draft messages
        if ($user->hasRole('superadmin')) {
            return true;
        }

        // Carrier can delete only their own draft messages
        if ($user->hasRole('user_carrier')) {
            $carrierDetails = $user->carrierDetails;

            if (!$carrierDetails || !$carrierDetails->carrier) {
                return false;
            }

            $carrier = $carrierDetails->carrier;

            return $message->sender_type === 'App\\Models\\Carrier' 
                && $message->sender_id === $carrier->id;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, AdminMessage $message): bool
    {
        // Only admin can restore messages
        return $user->hasRole('superadmin');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, AdminMessage $message): bool
    {
        // Only admin can force delete messages
        return $user->hasRole('superadmin');
    }

    /**
     * Determine whether the user can resend the model.
     */
    public function resend(User $user, AdminMessage $message): bool
    {
        // Only sent messages can be resent
        if ($message->status !== 'sent') {
            return false;
        }

        // Admin can resend all sent messages
        if ($user->hasRole('superadmin')) {
            return true;
        }

        // Carrier can resend only their own sent messages
        if ($user->hasRole('user_carrier')) {
            $carrierDetails = $user->carrierDetails;

            if (!$carrierDetails || !$carrierDetails->carrier) {
                return false;
            }

            $carrier = $carrierDetails->carrier;

            return $message->sender_type === 'App\\Models\\Carrier' 
                && $message->sender_id === $carrier->id;
        }

        return false;
    }
}
