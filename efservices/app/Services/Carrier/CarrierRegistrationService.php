<?php

namespace App\Services\Carrier;

use App\Models\User;
use App\Models\Carrier;
use App\Models\Membership;
use App\Models\UserCarrierDetail;
use App\Services\BaseService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Carrier Registration Service
 * 
 * Maneja toda la lógica de negocio relacionada con el registro de carriers.
 */
class CarrierRegistrationService extends BaseService
{
    /**
     * Crear usuario carrier (Paso 1 del wizard)
     *
     * @param array $data
     * @return User
     * @throws \Exception
     */
    public function createCarrierUser(array $data): User
    {
        return $this->executeInTransaction(function () use ($data) {
            // Crear usuario
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'status' => 1,
            ]);

            // Asegurar que el rol exista antes de asignar
            try {
                $user->assignRole('user_carrier');
            } catch (\Throwable $e) {
                Log::warning('Role user_carrier missing, seeding minimal role for tests');
                // Crear rol mínimo si no existe
                \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'user_carrier']);
                $user->assignRole('user_carrier');
            }

            $this->logAction('Carrier user created', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            return $user;
        });
    }

    /**
     * Crear empresa carrier (Paso 2 del wizard)
     *
     * @param User $user
     * @param array $data
     * @return Carrier
     * @throws \Exception
     */
    public function createCarrierCompany(User $user, array $data): Carrier
    {
        return $this->executeInTransaction(function () use ($user, $data) {
            // Crear carrier
            $carrier = Carrier::create([
                'name' => $data['name'],
                'slug' => Str::slug($data['name']),
                'address' => $data['address'],
                'state' => $data['state'],
                'zipcode' => $data['zipcode'],
                'country' => $data['country'] ?? 'US',
                'dot_number' => $data['dot_number'] ?? null,
                'mc_number' => $data['mc_number'] ?? null,
                'ein_number' => $data['ein_number'],
                'state_dot' => $data['state_dot'] ?? null,
                'ifta_account' => $data['ifta_account'] ?? null,
                'ifta' => $data['ifta'] ?? null,
                'business_type' => $data['business_type'] ?? null,
                'years_in_business' => $data['years_in_business'] ?? null,
                'fleet_size' => $data['fleet_size'] ?? null,
                'user_id' => $user->id,
                'status' => Carrier::STATUS_PENDING,
                'referrer_token' => Str::random(16),
                'referrer_token_expires_at' => now()->addDays(30),
            ]);

            // Crear detalle de user carrier
            UserCarrierDetail::create([
                'user_id' => $user->id,
                'carrier_id' => $carrier->id,
                'phone' => $data['phone'] ?? null,
                'job_position' => $data['job_position'] ?? 'Owner',
                'status' => 1,
            ]);

            $this->logAction('Carrier company created', [
                'carrier_id' => $carrier->id,
                'user_id' => $user->id,
                'name' => $carrier->name,
            ]);

            return $carrier;
        });
    }

    /**
     * Asignar membresía al carrier (Paso 3 del wizard)
     *
     * @param Carrier $carrier
     * @param int $membershipId
     * @return Carrier
     * @throws \Exception
     */
    public function assignMembership(Carrier $carrier, int $membershipId): Carrier
    {
        $membership = Membership::find($membershipId);
        $this->ensureModelExists($membership, 'Membership');

        return $this->executeInTransaction(function () use ($carrier, $membership) {
            $carrier->update([
                'id_plan' => $membership->id,
                'membership_id' => $membership->id,
            ]);

            $this->logAction('Membership assigned to carrier', [
                'carrier_id' => $carrier->id,
                'membership_id' => $membership->id,
                'membership_name' => $membership->name,
            ]);

            return $carrier->fresh();
        });
    }

    /**
     * Guardar información bancaria (Paso 4 del wizard)
     *
     * @param Carrier $carrier
     * @param array $data
     * @return Carrier
     * @throws \Exception
     */
    public function saveBankingDetails(Carrier $carrier, array $data): Carrier
    {
        return $this->executeInTransaction(function () use ($carrier, $data) {
            // Crear o actualizar detalles bancarios
            $carrier->bankingDetails()->updateOrCreate(
                ['carrier_id' => $carrier->id],
                [
                    'bank_name' => $data['bank_name'] ?? null,
                    'account_holder_name' => $data['account_holder_name'] ?? null,
                    'account_number' => $data['account_number'] ?? null,
                    'banking_routing_number' => $data['routing_number'] ?? null,
                    'zip_code' => $data['zip_code'] ?? null,
                    'security_code' => $data['security_code'] ?? null,
                    'country_code' => $data['country_code'] ?? 'US',
                    'status' => \App\Models\CarrierBankingDetail::STATUS_PENDING,
                    'rejection_reason' => null,
                ]
            );

            // Actualizar status del carrier
            $carrier->update([
                'status' => Carrier::STATUS_PENDING_VALIDATION,
                'document_status' => Carrier::DOCUMENT_STATUS_PENDING,
            ]);

            $this->logAction('Banking details saved for carrier', [
                'carrier_id' => $carrier->id,
                'bank_name' => $data['bank_name'] ?? null,
            ]);

            return $carrier->fresh();
        });
    }

    /**
     * Aprobar carrier (acción de admin)
     *
     * @param Carrier $carrier
     * @return Carrier
     * @throws \Exception
     */
    public function approveCarrier(Carrier $carrier): Carrier
    {
        return $this->executeInTransaction(function () use ($carrier) {
            $carrier->update([
                'status' => Carrier::STATUS_ACTIVE,
            ]);

            // TODO: Enviar notificación al carrier

            $this->logAction('Carrier approved', [
                'carrier_id' => $carrier->id,
                'approved_by' => auth()->id(),
            ]);

            return $carrier->fresh();
        });
    }

    /**
     * Rechazar carrier (acción de admin)
     *
     * @param Carrier $carrier
     * @param string|null $reason
     * @return Carrier
     * @throws \Exception
     */
    public function rejectCarrier(Carrier $carrier, ?string $reason = null): Carrier
    {
        return $this->executeInTransaction(function () use ($carrier, $reason) {
            $carrier->update([
                'status' => Carrier::STATUS_REJECTED,
            ]);

            // TODO: Enviar notificación al carrier con razón

            $this->logAction('Carrier rejected', [
                'carrier_id' => $carrier->id,
                'rejected_by' => auth()->id(),
                'reason' => $reason,
            ]);

            return $carrier->fresh();
        });
    }

    /**
     * Verificar si el carrier puede agregar más conductores
     *
     * @param Carrier $carrier
     * @return bool
     */
    public function canAddDriver(Carrier $carrier): bool
    {
        if (!$carrier->membership) {
            return false;
        }

        $currentDrivers = $carrier->userDrivers()->count();
        return $currentDrivers < $carrier->membership->max_drivers;
    }

    /**
     * Verificar si el carrier puede agregar más vehículos
     *
     * @param Carrier $carrier
     * @return bool
     */
    public function canAddVehicle(Carrier $carrier): bool
    {
        if (!$carrier->membership) {
            return false;
        }

        $currentVehicles = $carrier->vehicles()->count();
        return $currentVehicles < $carrier->membership->max_vehicles;
    }

    /**
     * Obtener límites disponibles del carrier
     *
     * @param Carrier $carrier
     * @return array
     */
    public function getAvailableLimits(Carrier $carrier): array
    {
        if (!$carrier->membership) {
            return [
                'drivers' => ['current' => 0, 'max' => 0, 'available' => 0],
                'vehicles' => ['current' => 0, 'max' => 0, 'available' => 0],
            ];
        }

        $currentDrivers = $carrier->userDrivers()->count();
        $currentVehicles = $carrier->vehicles()->count();

        return [
            'drivers' => [
                'current' => $currentDrivers,
                'max' => $carrier->membership->max_drivers,
                'available' => max(0, $carrier->membership->max_drivers - $currentDrivers),
                'can_add' => $this->canAddDriver($carrier),
            ],
            'vehicles' => [
                'current' => $currentVehicles,
                'max' => $carrier->membership->max_vehicles,
                'available' => max(0, $carrier->membership->max_vehicles - $currentVehicles),
                'can_add' => $this->canAddVehicle($carrier),
            ],
        ];
    }
}
