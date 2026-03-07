<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class SettingsController extends Controller
{
    /**
     * Mostrar la página de configuración
     */
    public function index(Request $request)
    {
        $page = $request->get('page', 'profile-info');
        
        // Validar que la página existe
        $validPages = [
            'profile-info', 
            'email-settings', 
            'security',
            'two-factor-authentication',
            'device-history',
            'notification-settings',
            'account-deactivation'
        ];
        
        if (!in_array($page, $validPages)) {
            $page = 'profile-info';
        }
        
        // Obtener los detalles del conductor si existen
        $driverDetail = auth()->user()->driverDetail;
        
        return view('admin.settings.index', [
            'currentPage' => $page,
            'user' => auth()->user(),
            'driverDetail' => $driverDetail,
        ]);
    }

    /**
     * Actualizar información del perfil
     */
    public function updateProfile(Request $request)
    {
        $user = auth()->user();
        
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'phone' => ['nullable', 'string', 'max:20'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
        ]);
        
        // Actualizar datos del usuario
        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);
        
        // Actualizar o crear detalles del conductor
        if (isset($validated['phone']) || isset($validated['date_of_birth'])) {
            $user->driverDetail()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'phone' => $validated['phone'] ?? null,
                    'date_of_birth' => $validated['date_of_birth'] ?? null,
                ]
            );
        }
        
        return back()->with('success', 'Profile updated successfully!');
    }

    /**
     * Actualizar configuración de email
     */
    public function updateEmailSettings(Request $request)
    {
        $user = auth()->user();
        
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'notification_email' => ['nullable', 'boolean'],
            'marketing_email' => ['nullable', 'boolean'],
        ]);
        
        $user->update([
            'email' => $validated['email'],
        ]);
        
        // Aquí puedes guardar las preferencias de notificaciones si tienes una tabla para eso
        
        return back()->with('success', 'Email settings updated successfully!');
    }

    /**
     * Actualizar contraseña
     */
    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);
        
        auth()->user()->update([
            'password' => Hash::make($validated['password']),
        ]);
        
        return back()->with('success', 'Password updated successfully!');
    }

    /**
     * Actualizar foto de perfil
     */
    public function updateProfilePhoto(Request $request)
    {
        $request->validate([
            'photo' => ['required', 'image', 'max:2048'], // 2MB max
        ]);
        
        $user = auth()->user();
        
        // Eliminar foto anterior si existe
        if ($user->hasMedia('profile_photos')) {
            $user->clearMediaCollection('profile_photos');
        }
        
        // Guardar nueva foto
        $user->addMediaFromRequest('photo')
            ->toMediaCollection('profile_photos');
        
        return back()->with('success', 'Profile photo updated successfully!');
    }

    /**
     * Eliminar foto de perfil
     */
    public function deleteProfilePhoto()
    {
        $user = auth()->user();
        
        if ($user->hasMedia('profile_photos')) {
            $user->clearMediaCollection('profile_photos');
        }
        
        return back()->with('success', 'Profile photo deleted successfully!');
    }
}
