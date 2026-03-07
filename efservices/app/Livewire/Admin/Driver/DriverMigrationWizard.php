<?php

namespace App\Livewire\Admin\Driver;

use App\Models\Carrier;
use App\Models\UserDriverDetail;
use App\Services\Driver\DriverMigrationService;
use App\Services\Driver\MigrationNotificationService;
use Livewire\Component;
use Livewire\Attributes\Layout;

/**
 * Livewire component for the driver migration wizard.
 * Guides admins through the process of migrating a driver between carriers.
 */
#[Layout('layouts.admin')]
class DriverMigrationWizard extends Component
{
    // Driver being migrated
    public ?int $driverId = null;
    public ?UserDriverDetail $driver = null;
    
    // Target carrier selection
    public ?int $targetCarrierId = null;
    public string $carrierSearch = '';
    
    // Migration details
    public string $reason = '';
    public string $notes = '';
    
    // Wizard state
    public int $currentStep = 1;
    public array $validationErrors = [];
    public array $validationWarnings = [];
    public bool $acknowledgedWarnings = false;
    
    // Result
    public bool $migrationCompleted = false;
    public ?int $migrationRecordId = null;
    public bool $canRollback = false;

    protected $listeners = ['driverSelected' => 'setDriver'];

    public function mount(?int $driverId = null): void
    {
        if ($driverId) {
            $this->setDriver($driverId);
        }
    }

    public function setDriver(int $driverId): void
    {
        $this->driverId = $driverId;
        $this->driver = UserDriverDetail::with(['user', 'carrier'])->find($driverId);
        
        if (!$this->driver) {
            session()->flash('error', 'Driver not found.');
            return;
        }

        $this->reset(['targetCarrierId', 'reason', 'notes', 'validationErrors', 
                      'validationWarnings', 'acknowledgedWarnings', 'migrationCompleted']);
        $this->currentStep = 1;
    }

    public function getAvailableCarriersProperty()
    {
        if (!$this->driver) {
            return collect();
        }

        $service = app(DriverMigrationService::class);
        $carriers = $service->getAvailableTargetCarriers($this->driver);

        if ($this->carrierSearch) {
            $search = strtolower($this->carrierSearch);
            $carriers = $carriers->filter(function ($carrier) use ($search) {
                return str_contains(strtolower($carrier->name), $search) ||
                       str_contains(strtolower($carrier->dot_number ?? ''), $search);
            });
        }

        return $carriers;
    }

    public function selectTargetCarrier(int $carrierId): void
    {
        $this->targetCarrierId = $carrierId;
        $this->validationErrors = [];
        $this->validationWarnings = [];
    }

    public function goToStep(int $step): void
    {
        if ($step < $this->currentStep) {
            $this->currentStep = $step;
            return;
        }

        // Validate before moving forward
        if ($this->currentStep === 1 && $step > 1) {
            if (!$this->targetCarrierId) {
                $this->validationErrors = ['Please select a target carrier.'];
                return;
            }
        }

        $this->currentStep = $step;
    }

    public function validateMigration(): void
    {
        if (!$this->driver || !$this->targetCarrierId) {
            return;
        }

        $targetCarrier = Carrier::find($this->targetCarrierId);
        if (!$targetCarrier) {
            $this->validationErrors = ['Target carrier not found.'];
            return;
        }

        $service = app(DriverMigrationService::class);
        $result = $service->validateMigrationEligibility($this->driver, $targetCarrier);

        $this->validationErrors = $result->errors;
        $this->validationWarnings = $result->warnings;

        if ($result->isValid) {
            $this->currentStep = 2;
        }
    }

    public function proceedToConfirmation(): void
    {
        if (!empty($this->validationWarnings) && !$this->acknowledgedWarnings) {
            return;
        }

        $this->currentStep = 3;
    }

    public function confirmMigration(): void
    {
        if (!$this->driver || !$this->targetCarrierId) {
            return;
        }

        $targetCarrier = Carrier::find($this->targetCarrierId);
        if (!$targetCarrier) {
            $this->validationErrors = ['Target carrier not found.'];
            return;
        }

        $service = app(DriverMigrationService::class);
        $notificationService = app(MigrationNotificationService::class);

        $result = $service->migrate(
            $this->driver,
            $targetCarrier,
            auth()->user(),
            $this->reason ?: null,
            $this->notes ?: null
        );

        if ($result->success) {
            // Send notifications
            $notificationService->sendMigrationNotifications(
                $result->migrationRecord,
                $result->archive
            );

            $this->migrationCompleted = true;
            $this->migrationRecordId = $result->migrationRecord->id;
            $this->canRollback = $result->canRollback();
            $this->currentStep = 4;

            session()->flash('success', 'Driver migration completed successfully.');
        } else {
            $this->validationErrors = $result->errors;
        }
    }

    public function rollbackMigration(): void
    {
        if (!$this->migrationRecordId) {
            return;
        }

        $record = \App\Models\MigrationRecord::find($this->migrationRecordId);
        if (!$record || !$record->canRollback()) {
            session()->flash('error', 'Migration cannot be rolled back.');
            return;
        }

        $service = app(DriverMigrationService::class);
        $notificationService = app(MigrationNotificationService::class);

        $result = $service->rollback($record, auth()->user(), 'Rolled back via wizard');

        if ($result->success) {
            $notificationService->sendRollbackNotifications($record, auth()->user());
            
            session()->flash('success', 'Migration rolled back successfully.');
            $this->redirect(route('admin.drivers.index'));
        } else {
            session()->flash('error', $result->error);
        }
    }

    public function getTargetCarrierProperty(): ?Carrier
    {
        if (!$this->targetCarrierId) {
            return null;
        }
        return Carrier::find($this->targetCarrierId);
    }

    public function render()
    {
        return view('livewire.admin.driver.driver-migration-wizard');
    }
}
