<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Admin\Driver\DriverTrafficConviction;
use Illuminate\Auth\Access\HandlesAuthorization;

class DriverTrafficConvictionPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any traffic convictions.
     * Only carriers can view traffic convictions.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        // Check if user has carrier role
        return $user->hasRole('user_carrier');
    }

    /**
     * Determine whether the user can view the traffic conviction.
     * User must be a carrier and the conviction must belong to one of their drivers.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Admin\Driver\DriverTrafficConviction  $conviction
     * @return bool
     */
    public function view(User $user, DriverTrafficConviction $conviction): bool
    {
        // Check if user is a carrier
        if (!$user->hasRole('user_carrier')) {
            return false;
        }

        // Check if conviction belongs to a driver of this carrier
        $carrier = $user->carrierDetails->carrier ?? null;
        
        if (!$carrier) {
            return false;
        }

        return (int) $conviction->userDriverDetail->carrier_id === (int) $carrier->id;
    }

    /**
     * Determine whether the user can create traffic convictions.
     * Only carriers can create traffic convictions.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function create(User $user): bool
    {
        // Check if user has carrier role
        return $user->hasRole('user_carrier');
    }

    /**
     * Determine whether the user can update the traffic conviction.
     * User must be a carrier and the conviction must belong to one of their drivers.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Admin\Driver\DriverTrafficConviction  $conviction
     * @return bool
     */
    public function update(User $user, DriverTrafficConviction $conviction): bool
    {
        // Check if user is a carrier
        if (!$user->hasRole('user_carrier')) {
            return false;
        }

        // Check if conviction belongs to a driver of this carrier
        $carrier = $user->carrierDetails->carrier ?? null;
        
        if (!$carrier) {
            return false;
        }

        return (int) $conviction->userDriverDetail->carrier_id === (int) $carrier->id;
    }

    /**
     * Determine whether the user can delete the traffic conviction.
     * User must be a carrier and the conviction must belong to one of their drivers.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Admin\Driver\DriverTrafficConviction  $conviction
     * @return bool
     */
    public function delete(User $user, DriverTrafficConviction $conviction): bool
    {
        // Check if user is a carrier
        if (!$user->hasRole('user_carrier')) {
            return false;
        }

        // Check if conviction belongs to a driver of this carrier
        $carrier = $user->carrierDetails->carrier ?? null;
        
        if (!$carrier) {
            return false;
        }

        return (int) $conviction->userDriverDetail->carrier_id === (int) $carrier->id;
    }
}
