<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Carrier;
use App\Models\UserCarrierDetail;
use App\Notifications\Admin\Carrier\NewUserCarrierNotification;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class UserCarrierController extends Controller
{
    public function index(Carrier $carrier): Response
    {
        $maxCarriers = $carrier->membership->max_carrier ?? 1;
        $currentCarriers = $carrier->users()->count();

        $paginator = $carrier->users()->with('carrierDetails')->paginate(10);

        $paginator->getCollection()->transform(fn ($user) => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'status' => $user->status,
            'created_at' => $user->created_at,
            'profile_photo_url' => $user->getFirstMedia('profile_photo_carrier')?->getUrl() ?: null,
            'carrier_details' => $user->carrierDetails ? [
                'id' => $user->carrierDetails->id,
                'phone' => $user->carrierDetails->phone,
                'job_position' => $user->carrierDetails->job_position,
                'status' => $user->carrierDetails->status,
            ] : null,
        ]);

        return Inertia::render('admin/carriers/users/Index', [
            'carrier' => $carrier->only('id', 'name', 'slug'),
            'userCarriers' => $paginator,
            'exceededLimit' => $currentCarriers >= $maxCarriers,
            'maxCarriers' => $maxCarriers,
            'currentCount' => $currentCarriers,
        ]);
    }

    public function store(Request $request, Carrier $carrier): RedirectResponse
    {
        $maxCarriers = $carrier->membership->max_carrier ?? 1;
        $currentCount = $carrier->users()->count();

        if ($currentCount >= $maxCarriers) {
            return back()->with('error', 'User limit reached for this membership plan.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|min:8|confirmed',
            'phone' => 'required|string|max:15',
            'job_position' => 'required|string|max:255',
            'profile_photo' => 'nullable|image|max:2048',
            'status' => 'nullable|integer|in:0,1,2',
        ]);

        try {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'status' => $validated['status'] ?? 1,
            ]);

            $user->assignRole('user_carrier');
            app(\App\Services\Notification\NotificationPreferenceService::class)->createDefaultPreferences($user);

            $user->carrierDetails()->create([
                'carrier_id' => $carrier->id,
                'phone' => $validated['phone'],
                'job_position' => $validated['job_position'],
                'status' => $validated['status'] ?? 1,
            ]);

            if ($request->hasFile('profile_photo')) {
                $fileName = strtolower(str_replace(' ', '_', $user->name)) . '.webp';
                $user->addMediaFromRequest('profile_photo')
                    ->usingFileName($fileName)
                    ->toMediaCollection('profile_photo_carrier');
            }

            foreach (User::role('superadmin')->get() as $admin) {
                app(\App\Services\NotificationService::class)
                    ->sendWithPreferences($admin, new NewUserCarrierNotification($user, $carrier), 'carrier_registration');
            }

            return back()->with('success', 'User Carrier created successfully.');
        } catch (\Exception $e) {
            Log::error('Error creating UserCarrier: ' . $e->getMessage());
            return back()->withErrors('Error creating user.');
        }
    }

    public function update(Request $request, Carrier $carrier, UserCarrierDetail $userCarrierDetail): RedirectResponse
    {
        $user = $userCarrierDetail->user;

        if (!$user) {
            return back()->withErrors('User not found.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => 'nullable|min:8|confirmed',
            'phone' => 'required|string|max:15',
            'job_position' => 'required|string|max:255',
            'profile_photo' => 'nullable|image|max:2048',
            'status' => 'required|integer|in:0,1,2',
        ]);

        try {
            $user->update([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'] ? Hash::make($validated['password']) : $user->password,
                'status' => $validated['status'],
            ]);

            $userCarrierDetail->update([
                'phone' => $validated['phone'],
                'job_position' => $validated['job_position'],
                'status' => $validated['status'],
            ]);

            if ($request->hasFile('profile_photo')) {
                $fileName = strtolower(str_replace(' ', '_', $user->name)) . '.webp';
                $user->clearMediaCollection('profile_photo_carrier');
                $user->addMediaFromRequest('profile_photo')
                    ->usingFileName($fileName)
                    ->toMediaCollection('profile_photo_carrier');
            }

            return back()->with('success', 'User Carrier updated successfully.');
        } catch (\Exception $e) {
            Log::error('Error updating UserCarrier: ' . $e->getMessage());
            return back()->withErrors('Error updating user.');
        }
    }

    public function destroy(Carrier $carrier, User $userCarrier): RedirectResponse
    {
        try {
            $detail = $userCarrier->carrierDetails;

            if ($detail) {
                $detail->clearMediaCollection('profile_photo_carrier');
                $detail->delete();
            }

            $userCarrier->clearMediaCollection('profile_photo_carrier');
            $userCarrier->delete();

            return back()->with('success', 'User Carrier deleted successfully.');
        } catch (\Exception $e) {
            Log::error('Error deleting UserCarrier: ' . $e->getMessage());
            return back()->withErrors('Error deleting user.');
        }
    }
}
