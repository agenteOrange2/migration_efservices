<?php

namespace App\Http\Controllers\Carrier;

use App\Http\Controllers\Controller;
use App\Models\Admin\Vehicle\VehicleType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class VehicleTypeController extends Controller
{
    public function index(Request $request)
    {
        $carrier = Auth::user()->carrierDetails->carrier;
        
        // Consulta base con conteo de vehículos del carrier
        $query = VehicleType::withCount(['vehicles' => function ($query) use ($carrier) {
            $query->where('carrier_id', $carrier->id);
        }])->orderBy('name');
        
        // Filtro de búsqueda
        if ($request->has('search') && !empty($request->search)) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }
        
        $vehicleTypes = $query->paginate(10)->withQueryString();
        
        return view('carrier.vehicle-types.index', compact('vehicleTypes'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:vehicle_types,name',
        ], [
            'name.required' => 'El nombre del tipo de vehículo es obligatorio',
            'name.unique' => 'Este tipo de vehículo ya existe en el sistema',
        ]);

        if ($validator->fails()) {
            if ($request->wantsJson()) {
                return response()->json(['error' => $validator->errors()->first()], 422);
            }
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $type = VehicleType::create($request->all());
        
        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'type' => $type
            ]);
        }
        
        return redirect()->route('carrier.vehicle-types.index')
            ->with('success', 'Tipo de vehículo creado exitosamente');
    }

    public function update(Request $request, VehicleType $vehicleType)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:vehicle_types,name,' . $vehicleType->id,
        ], [
            'name.required' => 'El nombre del tipo de vehículo es obligatorio',
            'name.unique' => 'Este tipo de vehículo ya existe en el sistema',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $vehicleType->update($request->all());
        
        return redirect()->route('carrier.vehicle-types.index')
            ->with('success', 'Tipo de vehículo actualizado exitosamente');
    }

    public function destroy(VehicleType $vehicleType)
    {
        $carrier = Auth::user()->carrierDetails->carrier;
        
        // Verificar si hay vehículos del carrier que usan este tipo
        $vehicleCount = $vehicleType->vehicles()->where('carrier_id', $carrier->id)->count();
        if ($vehicleCount > 0) {
            return redirect()->route('carrier.vehicle-types.index')
                ->with('error', 'No se puede eliminar este tipo porque está siendo utilizado por vehículos');
        }

        try {
            $vehicleType->delete();
            return redirect()->route('carrier.vehicle-types.index')
                ->with('success', 'Tipo de vehículo eliminado exitosamente');
        } catch (\Exception $e) {
            return redirect()->route('carrier.vehicle-types.index')
                ->with('error', 'Error al eliminar el tipo: ' . $e->getMessage());
        }
    }

    /**
     * API para búsqueda de tipos vía AJAX
     */
    public function search(Request $request)
    {
        $term = $request->input('q', '');
        $types = VehicleType::where('name', 'LIKE', "%{$term}%")
            ->orderBy('name')
            ->limit(10)
            ->get();
            
        return response()->json($types);
    }
}