<?php

namespace App\Livewire\Driver\Hos;

use Livewire\Component;
use Livewire\Attributes\Computed;
use Carbon\Carbon;
use App\Models\Trip;
use App\Models\Hos\HosEntry;
use App\Services\Hos\HosService;
use App\Services\Hos\HosCalculationService;
use App\Services\Hos\HosAlertService;
use App\Services\Hos\HosAutoStopService;
use Illuminate\Support\Facades\Auth;

class Dashboard extends Component
{
    public $currentStatus = null;
    public $todayEntries = [];
    public $dailyTotals = [];
    public $remaining = [];
    public $alerts = [];
    public $driver = null;
    public $activeTrip = null;
    public $autoStopInfo = null;

    // GPS location
    public $latitude = null;
    public $longitude = null;
    public $address = null;

    // System timezone info
    public $serverTime = null;
    public $serverTimezone = null;

    protected $hosService;
    protected $calculationService;
    protected $alertService;
    protected $autoStopService;

    protected $listeners = [
        'locationUpdated' => 'handleLocationUpdated',
        'refreshDashboard' => 'loadDashboardData',
    ];

    public function boot(
        HosService $hosService,
        HosCalculationService $calculationService,
        HosAlertService $alertService,
        HosAutoStopService $autoStopService
    ) {
        $this->hosService = $hosService;
        $this->calculationService = $calculationService;
        $this->alertService = $alertService;
        $this->autoStopService = $autoStopService;
    }

    public function mount()
    {
        $user = Auth::user();
        $this->driver = $user?->driverDetails;

        // Set server time info for display
        $this->serverTime = now()->format('H:i:s');
        $this->serverTimezone = config('app.timezone');

        if ($this->driver) {
            try {
                $this->loadDashboardData();
            } catch (\Exception $e) {
                \Log::error('HOS Dashboard load error: ' . $e->getMessage());
            }
        }
    }

    /**
     * Polling interval based on current status.
     * More frequent polling when driving.
     */
    #[Computed]
    public function pollingInterval(): int
    {
        // If currently driving, poll every 30 seconds
        if ($this->currentStatus && $this->currentStatus['status'] === 'on_duty_driving') {
            return 30000; // 30 seconds
        }
        
        // If on duty but not driving, poll every minute
        if ($this->currentStatus && $this->currentStatus['status'] === 'on_duty_not_driving') {
            return 60000; // 1 minute
        }
        
        // Otherwise, poll every 2 minutes
        return 120000; // 2 minutes
    }

    public function loadDashboardData()
    {
        if (!$this->driver) {
            return;
        }

        try {
            $today = Carbon::today();

            // Update server time
            $this->serverTime = now()->format('H:i:s');

            // Get current status
            $currentEntry = $this->hosService->getDriverCurrentStatus($this->driver->id);
            $this->currentStatus = $currentEntry ? [
                'status' => $currentEntry->status,
                'status_name' => $currentEntry->status_name,
                'start_time' => $currentEntry->start_time->format('H:i'),
                'duration' => $currentEntry->formatted_duration,
            ] : null;

            // Get active trip
            $this->activeTrip = Trip::where('user_driver_detail_id', $this->driver->id)
                ->where('status', Trip::STATUS_IN_PROGRESS)
                ->first();

            // Get auto-stop info if there's an active trip
            if ($this->activeTrip) {
                $this->autoStopInfo = $this->autoStopService->getRemainingTimeBeforeAutoStop($this->activeTrip);
            } else {
                $this->autoStopInfo = null;
            }

            // Get today's entries
            $entries = $this->hosService->getDriverEntriesForDate($this->driver->id, $today);
            $this->todayEntries = $entries->map(function ($entry) {
                return [
                    'id' => $entry->id,
                    'status' => $entry->status,
                    'status_name' => $entry->status_name,
                    'start_time' => $entry->start_time->format('H:i'),
                    'end_time' => $entry->end_time ? $entry->end_time->format('H:i') : 'Current',
                    'duration' => $entry->formatted_duration,
                    'location' => $entry->location_display,
                ];
            })->toArray();

            // Get daily totals
            $this->dailyTotals = $this->calculationService->calculateDailyTotals($this->driver->id, $today);

            // Get remaining hours
            $this->remaining = $this->calculationService->calculateRemainingHours($this->driver->id, $today);

            // Get alerts
            try {
                $this->alerts = $this->alertService->getActiveAlerts($this->driver->id);
                
                // Add auto-stop warning if critical
                if ($this->autoStopInfo && $this->autoStopInfo['is_critical']) {
                    array_unshift($this->alerts, [
                        'type' => 'warning',
                        'category' => 'auto_stop',
                        'message' => "Auto-stop in {$this->autoStopInfo['minutes_remaining']} minutes. Please end your trip soon.",
                    ]);
                }
            } catch (\Exception $e) {
                $this->alerts = [];
            }
        } catch (\Exception $e) {
            \Log::error('HOS Dashboard data load error: ' . $e->getMessage());
            // Set defaults
            $this->currentStatus = null;
            $this->todayEntries = [];
            $this->dailyTotals = [
                'driving_formatted' => '0h 0m',
                'on_duty_formatted' => '0h 0m',
                'off_duty_formatted' => '0h 0m',
            ];
            $this->remaining = [
                'remaining_driving_formatted' => '12h 0m',
                'remaining_duty_formatted' => '14h 0m',
                'remaining_driving_minutes' => 720,
                'remaining_duty_minutes' => 840,
            ];
            $this->alerts = [];
        }
    }

    public function handleLocationUpdated($latitude = null, $longitude = null, $address = null)
    {
        $this->latitude = $latitude;
        $this->longitude = $longitude;
        $this->address = $address;
    }

    public function updateLocation($latitude, $longitude, $address = null)
    {
        $this->latitude = $latitude;
        $this->longitude = $longitude;
        $this->address = $address;
    }

    /**
     * Check if manual status changes are allowed.
     * Manual changes are blocked when there's an active trip.
     */
    public function canChangeStatusManually(): bool
    {
        // If there's an active trip, status must be controlled through trip actions
        return $this->activeTrip === null;
    }

    public function changeStatus($status)
    {
        if (!$this->driver) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Driver profile not found.',
            ]);
            return;
        }

        // Block manual status changes when there's an active trip
        if ($this->activeTrip) {
            $this->dispatch('notify', [
                'type' => 'warning',
                'message' => 'You have an active trip. Use the trip controls to pause, resume, or end your trip. Your HOS status is managed automatically.',
            ]);
            return;
        }

        // Check if trying to change to driving without an active trip
        if ($status === 'on_duty_driving') {
            $this->dispatch('notify', [
                'type' => 'warning',
                'message' => 'To start driving, please start a trip first. Go to Trips and start or accept an assigned trip.',
            ]);
            return;
        }

        // Only allow On Duty (not driving) or Off Duty when no active trip
        if (!in_array($status, ['on_duty_not_driving', 'off_duty', 'sleeper_berth'])) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Invalid status.',
            ]);
            return;
        }

        try {
            $location = null;
            if ($this->latitude && $this->longitude) {
                $location = [
                    'latitude' => $this->latitude,
                    'longitude' => $this->longitude,
                    'address' => $this->address,
                ];
            }

            $this->hosService->createEntry(
                $this->driver->id,
                $status,
                $location,
                Auth::id()
            );

            $this->loadDashboardData();

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Status updated successfully.',
            ]);

            // Check for new alerts
            if (!empty($this->alerts)) {
                foreach ($this->alerts as $alert) {
                    $this->dispatch('notify', [
                        'type' => $alert['type'] === 'violation' ? 'error' : 'warning',
                        'message' => $alert['message'],
                    ]);
                }
            }
        } catch (\InvalidArgumentException $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => $e->getMessage(),
            ]);
        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Failed to update status. Please try again.',
            ]);
        }
    }

    public function render()
    {
        return view('livewire.driver.hos.dashboard');
    }
}
