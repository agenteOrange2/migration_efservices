<?php

namespace App\Services\Driver;

use App\Models\User;
use App\Models\Carrier;
use App\Models\UserDriverDetail;
use App\Models\Admin\Driver\DriverApplication;
use App\Services\BaseService;
use Illuminate\Support\Facades\Hash;

/**
 * Driver Application Service
 * 
 * Maneja toda la lógica de negocio relacionada con las aplicaciones de conductores.
 */
class DriverApplicationService extends BaseService
{
    /**
     * Crear usuario driver inicial
     *
     * @param Carrier $carrier
     * @param array $data
     * @return User
     * @throws \Exception
     */
    public function createDriverUser(Carrier $carrier, array $data): User
    {
        return $this->executeInTransaction(function () use ($carrier, $data) {
            // Crear usuario
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'status' => 0, // Inactivo hasta completar aplicación
            ]);

            // Asignar rol de driver
            $user->assignRole('user_driver');

            // Crear detalle de driver
            $driverDetail = UserDriverDetail::create([
                'user_id' => $user->id,
                'carrier_id' => $carrier->id,
                'middle_name' => $data['middle_name'] ?? null,
                'last_name' => $data['last_name'] ?? null,
                'phone' => $data['phone'] ?? null,
                'date_of_birth' => $data['date_of_birth'] ?? null,
                'status' => UserDriverDetail::STATUS_PENDING,
                'application_completed' => false,
                'current_step' => 1,
                'completion_percentage' => 0,
            ]);

            // Crear aplicación
            DriverApplication::create([
                'user_id' => $user->id,
                'carrier_id' => $carrier->id,
                'status' => 'pending',
            ]);

            $this->logAction('Driver user created', [
                'user_id' => $user->id,
                'carrier_id' => $carrier->id,
                'email' => $user->email,
            ]);

            return $user;
        });
    }

    /**
     * Actualizar progreso de la aplicación
     *
     * @param UserDriverDetail $driver
     * @param int $step
     * @return UserDriverDetail
     */
    public function updateApplicationProgress(UserDriverDetail $driver, int $step): UserDriverDetail
    {
        $driver->update([
            'current_step' => $step,
            'completion_percentage' => $this->calculateCompletionPercentage($driver),
        ]);

        $this->logAction('Application progress updated', [
            'driver_id' => $driver->id,
            'step' => $step,
            'percentage' => $driver->completion_percentage,
        ]);

        return $driver->fresh();
    }

    /**
     * Calcular porcentaje de completitud de la aplicación
     *
     * @param UserDriverDetail $driver
     * @return int
     */
    public function calculateCompletionPercentage(UserDriverDetail $driver): int
    {
        $totalSections = 12; // Total de secciones de la aplicación
        $completedSections = 0;

        // Información personal
        if ($driver->phone && $driver->date_of_birth) {
            $completedSections++;
        }

        // Direcciones
        if ($driver->application && $driver->application->addresses()->count() > 0) {
            $completedSections++;
        }

        // Licencias
        if ($driver->licenses()->count() > 0) {
            $completedSections++;
        }

        // Experiencia
        if ($driver->experiences()->count() > 0) {
            $completedSections++;
        }

        // Historial laboral
        if ($driver->workHistories()->count() > 0) {
            $completedSections++;
        }

        // Escuelas de entrenamiento
        if ($driver->trainingSchools()->count() > 0) {
            $completedSections++;
        }

        // Infracciones de tráfico
        if ($driver->trafficConvictions()->count() >= 0) { // Puede ser 0
            $completedSections++;
        }

        // Accidentes
        if ($driver->accidents()->count() >= 0) { // Puede ser 0
            $completedSections++;
        }

        // Certificación médica
        if ($driver->medicalQualification) {
            $completedSections++;
        }

        // Políticas de la empresa
        if ($driver->companyPolicy) {
            $completedSections++;
        }

        // Antecedentes penales
        if ($driver->criminalHistory) {
            $completedSections++;
        }

        // Certificación final
        if ($driver->certification) {
            $completedSections++;
        }

        return (int) (($completedSections / $totalSections) * 100);
    }

    /**
     * Completar aplicación del driver
     *
     * @param UserDriverDetail $driver
     * @return UserDriverDetail
     * @throws \Exception
     */
    public function completeApplication(UserDriverDetail $driver): UserDriverDetail
    {
        return $this->executeInTransaction(function () use ($driver) {
            // Verificar que la aplicación esté 100% completa
            $percentage = $this->calculateCompletionPercentage($driver);
            
            if ($percentage < 100) {
                throw new \Exception('Application is not complete. Current progress: ' . $percentage . '%');
            }

            // Actualizar driver
            $driver->update([
                'application_completed' => true,
                'completion_percentage' => 100,
            ]);

            // Actualizar aplicación
            if ($driver->application) {
                $driver->application->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                ]);
            }

            // Disparar evento
            event(new \App\Events\DriverApplicationCompleted($driver));

            $this->logAction('Driver application completed', [
                'driver_id' => $driver->id,
                'carrier_id' => $driver->carrier_id,
            ]);

            return $driver->fresh();
        });
    }

    /**
     * Aprobar driver (acción del carrier)
     *
     * @param UserDriverDetail $driver
     * @return UserDriverDetail
     * @throws \Exception
     */
    public function approveDriver(UserDriverDetail $driver): UserDriverDetail
    {
        return $this->executeInTransaction(function () use ($driver) {
            // Verificar que la aplicación esté completa
            if (!$driver->application_completed) {
                throw new \Exception('Cannot approve driver with incomplete application');
            }

            // Actualizar driver
            $driver->update([
                'status' => UserDriverDetail::STATUS_ACTIVE,
            ]);

            // Actualizar usuario
            $driver->user->update([
                'status' => 1,
            ]);

            // Actualizar aplicación
            if ($driver->application) {
                $driver->application->update([
                    'status' => 'approved',
                    'approved_at' => now(),
                    'approved_by' => auth()->id(),
                ]);
            }

            // TODO: Enviar notificación al driver

            $this->logAction('Driver approved', [
                'driver_id' => $driver->id,
                'approved_by' => auth()->id(),
            ]);

            return $driver->fresh();
        });
    }

    /**
     * Rechazar driver (acción del carrier)
     *
     * @param UserDriverDetail $driver
     * @param string|null $reason
     * @return UserDriverDetail
     * @throws \Exception
     */
    public function rejectDriver(UserDriverDetail $driver, ?string $reason = null): UserDriverDetail
    {
        return $this->executeInTransaction(function () use ($driver, $reason) {
            // Actualizar driver
            $driver->update([
                'status' => UserDriverDetail::STATUS_INACTIVE,
            ]);

            // Actualizar aplicación
            if ($driver->application) {
                $driver->application->update([
                    'status' => 'rejected',
                    'rejected_at' => now(),
                    'rejected_by' => auth()->id(),
                    'rejection_reason' => $reason,
                ]);
            }

            // TODO: Enviar notificación al driver con razón

            $this->logAction('Driver rejected', [
                'driver_id' => $driver->id,
                'rejected_by' => auth()->id(),
                'reason' => $reason,
            ]);

            return $driver->fresh();
        });
    }

    /**
     * Verificar si el driver tiene todos los documentos requeridos
     *
     * @param UserDriverDetail $driver
     * @return bool
     */
    public function hasRequiredDocuments(UserDriverDetail $driver): bool
    {
        // Licencia
        if ($driver->licenses()->count() === 0) {
            return false;
        }

        // Certificación médica
        if (!$driver->medicalQualification) {
            return false;
        }

        // Certificación final
        if (!$driver->certification) {
            return false;
        }

        return true;
    }

    /**
     * Obtener documentos faltantes del driver
     *
     * @param UserDriverDetail $driver
     * @return array
     */
    public function getMissingDocuments(UserDriverDetail $driver): array
    {
        $missing = [];

        if ($driver->licenses()->count() === 0) {
            $missing[] = 'Driver License';
        }

        if (!$driver->medicalQualification) {
            $missing[] = 'Medical Certification';
        }

        if (!$driver->certification) {
            $missing[] = 'Final Certification';
        }

        return $missing;
    }
}
