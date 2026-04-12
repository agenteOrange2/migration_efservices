<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class DriverProfileController extends Controller
{
    public function edit(): Response
    {
        $user = auth()->user();
        $driver = $user?->driverDetails?->load('carrier');

        abort_unless($driver, 403, 'No driver profile associated with this account.');

        return Inertia::render('driver/profile/Edit', [
            'driver' => [
                'id' => $driver->id,
                'first_name' => $user->name,
                'middle_name' => $driver->middle_name,
                'last_name' => $driver->last_name,
                'full_name' => $driver->full_name,
                'email' => $user->email,
                'phone' => $driver->phone,
                'date_of_birth' => $driver->date_of_birth?->format('n/j/Y'),
                'status_name' => $driver->status_name,
                'photo_url' => $driver->profile_photo_url,
                'has_custom_photo' => (bool) $driver->getFirstMedia('profile_photo_driver'),
                'created_at' => optional($driver->created_at)->format('M d, Y'),
                'carrier' => $driver->carrier ? [
                    'id' => $driver->carrier->id,
                    'name' => $driver->carrier->name,
                    'dot_number' => $driver->carrier->dot_number,
                    'mc_number' => $driver->carrier->mc_number,
                ] : null,
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = auth()->user();
        $driver = $user?->driverDetails;

        abort_unless($driver, 403, 'No driver profile associated with this account.');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['required', 'string', 'max:20'],
            'date_of_birth' => ['required', 'string', 'max:20'],
        ]);

        $dateOfBirth = $this->normalizeDateInput($validated['date_of_birth']);

        if (!$dateOfBirth) {
            return back()
                ->withErrors(['date_of_birth' => 'Invalid date format. Use M/D/YYYY.'])
                ->withInput();
        }

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        $driver->update([
            'middle_name' => $validated['middle_name'] ?? null,
            'last_name' => $validated['last_name'],
            'phone' => $validated['phone'],
            'date_of_birth' => $dateOfBirth,
        ]);

        return redirect()
            ->route('driver.profile.edit')
            ->with('success', 'Profile updated successfully.');
    }

    public function updatePhoto(Request $request): RedirectResponse|JsonResponse
    {
        $driver = auth()->user()?->driverDetails;

        abort_unless($driver, 403, 'No driver profile associated with this account.');

        $request->validate([
            'profile_photo' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
        ]);

        $driver->clearMediaCollection('profile_photo_driver');
        $driver->addMediaFromRequest('profile_photo')
            ->toMediaCollection('profile_photo_driver');

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Profile photo updated successfully.',
                'photo_url' => $driver->fresh()->profile_photo_url,
            ]);
        }

        return back()->with('success', 'Profile photo updated successfully.');
    }

    public function deletePhoto(Request $request): RedirectResponse|JsonResponse
    {
        $driver = auth()->user()?->driverDetails;

        abort_unless($driver, 403, 'No driver profile associated with this account.');

        $driver->clearMediaCollection('profile_photo_driver');

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Profile photo removed successfully.',
                'photo_url' => $driver->fresh()->profile_photo_url,
            ]);
        }

        return back()->with('success', 'Profile photo removed successfully.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        if (!Hash::check($validated['current_password'], $user->password)) {
            return back()
                ->withErrors(['current_password' => 'The current password is incorrect.'])
                ->withInput();
        }

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()
            ->route('driver.profile.edit')
            ->with('success', 'Password updated successfully.');
    }

    private function normalizeDateInput(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        $value = trim($value);

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1) {
            return $value;
        }

        try {
            return Carbon::createFromFormat('n/j/Y', $value)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }
}
