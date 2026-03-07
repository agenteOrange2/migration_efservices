<?php

namespace App\Http\Controllers\Admin;

use App\Models\Carrier;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use App\Traits\SendsCustomNotifications;

class SafetyDataSystemController extends Controller
{
    use SendsCustomNotifications;

    /**
     * Mostrar formulario para gestionar Safety Data System
     */
    public function edit(Carrier $carrier)
    {
        return view('admin.carrier.safety-data-system', compact('carrier'));
    }

    /**
     * Actualizar configuración del Safety Data System
     */
    public function update(Request $request, Carrier $carrier)
    {
        $request->validate([
            'custom_safety_url' => 'nullable|url|max:500',
        ]);

        try {
            $carrier->update([
                'custom_safety_url' => $request->custom_safety_url,
            ]);

            Log::info('Safety Data System URL updated', [
                'carrier_id' => $carrier->id,
                'custom_url' => $request->custom_safety_url,
                'admin_user_id' => auth()->id(),
            ]);

            return back()->with($this->sendNotification(
                'success',
                'URL del Safety Data System actualizada exitosamente.'
            ));
        } catch (\Exception $e) {
            Log::error('Error updating Safety Data System URL', [
                'carrier_id' => $carrier->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->with('error', 'Error al actualizar la URL: ' . $e->getMessage());
        }
    }

    /**
     * Subir imagen de Safety Data System
     */
    public function uploadImage(Request $request, Carrier $carrier)
    {
        $request->validate([
            'safety_image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        try {
            // Eliminar imagen anterior si existe
            if ($carrier->hasMedia('safety_data_system')) {
                $carrier->clearMediaCollection('safety_data_system');
            }

            // Subir nueva imagen
            $carrier->addMediaFromRequest('safety_image')
                ->toMediaCollection('safety_data_system');

            Log::info('Safety Data System image uploaded', [
                'carrier_id' => $carrier->id,
                'admin_user_id' => auth()->id(),
            ]);

            return back()->with($this->sendNotification(
                'success',
                'Imagen del Safety Data System subida exitosamente.',
                'La imagen ahora está disponible para el carrier.'
            ));
        } catch (\Exception $e) {
            Log::error('Error uploading Safety Data System image', [
                'carrier_id' => $carrier->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->with('error', 'Error al subir la imagen: ' . $e->getMessage());
        }
    }

    /**
     * Eliminar imagen de Safety Data System
     */
    public function deleteImage(Carrier $carrier)
    {
        try {
            if ($carrier->hasMedia('safety_data_system')) {
                $carrier->clearMediaCollection('safety_data_system');

                Log::info('Safety Data System image deleted', [
                    'carrier_id' => $carrier->id,
                    'admin_user_id' => auth()->id(),
                ]);

                return back()->with($this->sendNotification(
                    'success',
                    'Imagen del Safety Data System eliminada exitosamente.'
                ));
            }

            return back()->with('info', 'No hay imagen para eliminar.');
        } catch (\Exception $e) {
            Log::error('Error deleting Safety Data System image', [
                'carrier_id' => $carrier->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->with('error', 'Error al eliminar la imagen: ' . $e->getMessage());
        }
    }
}

