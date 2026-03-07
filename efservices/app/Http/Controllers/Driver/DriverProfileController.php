<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Log;

class DriverProfileController extends Controller
{
    /**
     * Create a new controller instance.
     * Verify user has driver role and driver detail.
     */
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = Auth::user();
            
            if (!$user || !$user->driverDetail) {
                abort(403, 'Access denied. Driver profile not found.');
            }
            
            return $next($request);
        });
    }

    /**
     * Show the form for editing the driver profile.
     */
    public function edit()
    {
        $user = Auth::user();
        $driver = $user->driverDetail;
        
        return view('driver.profile.edit', compact('user', 'driver'));
    }

    /**
     * Update the driver profile information.
     */
    public function update(Request $request)
    {
        $user = Auth::user();
        $driver = $user->driverDetail;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'required|string|max:15',
            'date_of_birth' => 'required|date',
        ]);

        try {
            // Actualizar usuario
            $user->update([
                'name' => $validated['name'],
                'email' => $validated['email'],
            ]);

            // Actualizar detalles del driver
            $driver->update([
                'middle_name' => $validated['middle_name'],
                'last_name' => $validated['last_name'],
                'phone' => $validated['phone'],
                'date_of_birth' => $validated['date_of_birth'],
            ]);

            Log::info('Driver profile updated', [
                'user_id' => $user->id,
                'driver_id' => $driver->id
            ]);

            return redirect()->route('driver.profile.edit')
                ->with('success', 'Profile updated successfully');
        } catch (\Exception $e) {
            Log::error('Error updating driver profile', [
                'error' => $e->getMessage(),
                'user_id' => $user->id
            ]);

            return redirect()->back()
                ->with('error', 'An error occurred while updating the profile')
                ->withInput();
        }
    }

    /**
     * Update the driver's profile photo.
     */
    public function updatePhoto(Request $request)
    {
        $user = Auth::user();
        $driver = $user->driverDetail;

        $request->validate([
            'profile_photo' => 'required|image|mimes:jpeg,jpg,png,webp|max:2048',
        ]);

        try {
            if ($request->hasFile('profile_photo')) {
                // Eliminar la foto anterior si existe
                $driver->clearMediaCollection('profile_photo_driver');

                // Agregar la nueva foto - se guarda en storage/app/public/driver/{id}
                $driver->addMediaFromRequest('profile_photo')
                    ->toMediaCollection('profile_photo_driver');

                Log::info('Driver profile photo updated', [
                    'user_id' => $user->id,
                    'driver_id' => $driver->id,
                    'photo_path' => $driver->getFirstMediaUrl('profile_photo_driver')
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Profile photo updated successfully',
                    'photo_url' => $driver->profile_photo_url
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'No photo file provided'
            ], 400);

        } catch (\Exception $e) {
            Log::error('Error updating driver profile photo', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'driver_id' => $driver->id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the photo'
            ], 500);
        }
    }

    /**
     * Delete the driver's profile photo.
     */
    public function deletePhoto()
    {
        $user = Auth::user();
        $driver = $user->driverDetail;

        try {
            $driver->clearMediaCollection('profile_photo_driver');

            Log::info('Driver profile photo deleted', [
                'user_id' => $user->id,
                'driver_id' => $driver->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Profile photo deleted successfully',
                'photo_url' => asset('build/default_profile.png')
            ]);

        } catch (\Exception $e) {
            Log::error('Error deleting driver profile photo', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'driver_id' => $driver->id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while deleting the photo'
            ], 500);
        }
    }

    /**
     * Update the driver's password.
     */
    public function updatePassword(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'current_password' => 'required|string',
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        // Verificar que la contraseña actual sea correcta
        if (!Hash::check($validated['current_password'], $user->password)) {
            return redirect()->back()
                ->with('error', 'The current password is incorrect')
                ->withInput();
        }

        try {
            $user->update([
                'password' => Hash::make($validated['password']),
            ]);

            Log::info('Driver password updated', [
                'user_id' => $user->id,
            ]);

            return redirect()->route('driver.profile.edit')
                ->with('success', 'Password updated successfully');

        } catch (\Exception $e) {
            Log::error('Error updating driver password', [
                'error' => $e->getMessage(),
                'user_id' => $user->id
            ]);

            return redirect()->back()
                ->with('error', 'An error occurred while updating the password')
                ->withInput();
        }
    }
}

