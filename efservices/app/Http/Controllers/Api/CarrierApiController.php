<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\CarrierRepositoryInterface;
use App\Services\Carrier\CarrierRegistrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Carrier API Controller
 * 
 * Ejemplo de controlador refactorizado usando Repository Pattern y Service Layer.
 */
class CarrierApiController extends Controller
{
    /**
     * Constructor con inyección de dependencias
     */
    public function __construct(
        protected CarrierRepositoryInterface $carrierRepository,
        protected CarrierRegistrationService $carrierService
    ) {}

    /**
     * Listar carriers activos
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $carriers = $this->carrierRepository
            ->with(['membership'])
            ->orderBy('name')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $carriers,
        ]);
    }

    /**
     * Obtener carriers activos
     *
     * @return JsonResponse
     */
    public function active(): JsonResponse
    {
        $carriers = $this->carrierRepository->findActive();

        return response()->json([
            'success' => true,
            'data' => $carriers,
        ]);
    }

    /**
     * Obtener carriers pendientes de validación
     *
     * @return JsonResponse
     */
    public function pendingValidation(): JsonResponse
    {
        $carriers = $this->carrierRepository->findPendingValidation();

        return response()->json([
            'success' => true,
            'data' => $carriers,
        ]);
    }

    /**
     * Buscar carrier por slug
     *
     * @param string $slug
     * @return JsonResponse
     */
    public function show(string $slug): JsonResponse
    {
        $carrier = $this->carrierRepository->findBySlug($slug);

        if (!$carrier) {
            return response()->json([
                'success' => false,
                'message' => 'Carrier not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $carrier,
        ]);
    }

    /**
     * Buscar carriers
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function search(Request $request): JsonResponse
    {
        $search = $request->input('q', '');
        
        $carriers = $this->carrierRepository->search($search);

        return response()->json([
            'success' => true,
            'data' => $carriers,
        ]);
    }

    /**
     * Obtener límites disponibles de un carrier
     *
     * @param string $slug
     * @return JsonResponse
     */
    public function limits(string $slug): JsonResponse
    {
        $carrier = $this->carrierRepository->findBySlug($slug);

        if (!$carrier) {
            return response()->json([
                'success' => false,
                'message' => 'Carrier not found',
            ], 404);
        }

        $limits = $this->carrierService->getAvailableLimits($carrier);

        return response()->json([
            'success' => true,
            'data' => $limits,
        ]);
    }

    /**
     * Verificar si un carrier puede agregar drivers
     *
     * @param string $slug
     * @return JsonResponse
     */
    public function canAddDriver(string $slug): JsonResponse
    {
        $carrier = $this->carrierRepository->findBySlug($slug);

        if (!$carrier) {
            return response()->json([
                'success' => false,
                'message' => 'Carrier not found',
            ], 404);
        }

        $canAdd = $this->carrierService->canAddDriver($carrier);

        return response()->json([
            'success' => true,
            'data' => [
                'can_add' => $canAdd,
                'current_drivers' => $carrier->userDrivers()->count(),
                'max_drivers' => $carrier->membership->max_drivers ?? 0,
            ],
        ]);
    }

    /**
     * Verificar si un carrier puede agregar vehículos
     *
     * @param string $slug
     * @return JsonResponse
     */
    public function canAddVehicle(string $slug): JsonResponse
    {
        $carrier = $this->carrierRepository->findBySlug($slug);

        if (!$carrier) {
            return response()->json([
                'success' => false,
                'message' => 'Carrier not found',
            ], 404);
        }

        $canAdd = $this->carrierService->canAddVehicle($carrier);

        return response()->json([
            'success' => true,
            'data' => [
                'can_add' => $canAdd,
                'current_vehicles' => $carrier->vehicles()->count(),
                'max_vehicles' => $carrier->membership->max_vehicles ?? 0,
            ],
        ]);
    }
}
