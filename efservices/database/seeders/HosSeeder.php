<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use App\Models\Carrier;
use App\Models\UserDriverDetail;
use App\Models\Hos\HosEntry;
use App\Models\Hos\HosConfiguration;
use App\Models\Hos\HosDailyLog;
use App\Models\Hos\HosViolation;

class HosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding HOS data...');

        // Get all carriers
        $carriers = Carrier::all();

        if ($carriers->isEmpty()) {
            $this->command->warn('No carriers found. Skipping HOS seeding.');
            return;
        }

        foreach ($carriers as $carrier) {
            $this->seedCarrierHosData($carrier);
        }

        $this->command->info('HOS seeding completed!');
    }

    /**
     * Seed HOS data for a specific carrier.
     */
    protected function seedCarrierHosData(Carrier $carrier): void
    {
        // Create HOS configuration for carrier
        $config = HosConfiguration::updateOrCreate(
            ['carrier_id' => $carrier->id],
            [
                'max_driving_hours' => 12,
                'max_duty_hours' => 14,
                'warning_threshold_minutes' => 60, // Alert 1 hour before limit
                'violation_threshold_minutes' => 0,
                'is_active' => true,
            ]
        );

        $this->command->info("Created HOS configuration for carrier: {$carrier->name}");

        // Get drivers for this carrier
        $drivers = UserDriverDetail::where('carrier_id', $carrier->id)
            ->whereHas('activeVehicleAssignment')
            ->get();

        if ($drivers->isEmpty()) {
            $this->command->warn("No drivers with vehicle assignments for carrier: {$carrier->name}");
            return;
        }

        foreach ($drivers as $driver) {
            $this->seedDriverHosEntries($driver, $config);
        }
    }


    /**
     * Seed HOS entries for a specific driver.
     */
    protected function seedDriverHosEntries(UserDriverDetail $driver, HosConfiguration $config): void
    {
        $vehicleAssignment = $driver->activeVehicleAssignment;
        
        if (!$vehicleAssignment) {
            return;
        }

        $this->command->info("Seeding HOS entries for driver ID: {$driver->id}");

        // Create entries for the last 7 days
        for ($daysAgo = 6; $daysAgo >= 0; $daysAgo--) {
            $date = Carbon::today()->subDays($daysAgo);
            
            // Skip weekends randomly (50% chance)
            if ($date->isWeekend() && rand(0, 1) === 0) {
                continue;
            }

            $this->createDayEntries($driver, $vehicleAssignment, $date);
        }
    }

    /**
     * Create HOS entries for a single day.
     */
    protected function createDayEntries(
        UserDriverDetail $driver,
        $vehicleAssignment,
        Carbon $date
    ): void {
        // Random start time between 5:00 and 8:00 AM
        $startHour = rand(5, 8);
        $currentTime = $date->copy()->setTime($startHour, rand(0, 59));

        $totalDrivingMinutes = 0;
        $totalOnDutyMinutes = 0;
        $totalOffDutyMinutes = 0;

        // Simulate a work day with multiple status changes
        $statuses = [
            HosEntry::STATUS_ON_DUTY_NOT_DRIVING, // Pre-trip inspection
            HosEntry::STATUS_ON_DUTY_DRIVING,     // Morning drive
            HosEntry::STATUS_OFF_DUTY,            // Break
            HosEntry::STATUS_ON_DUTY_DRIVING,     // Afternoon drive
            HosEntry::STATUS_ON_DUTY_NOT_DRIVING, // Loading/unloading
            HosEntry::STATUS_ON_DUTY_DRIVING,     // Final drive
            HosEntry::STATUS_OFF_DUTY,            // End of day
        ];

        $durations = [
            rand(15, 30),   // Pre-trip: 15-30 min
            rand(180, 240), // Morning drive: 3-4 hours
            rand(30, 60),   // Break: 30-60 min
            rand(120, 180), // Afternoon drive: 2-3 hours
            rand(30, 60),   // Loading: 30-60 min
            rand(60, 120),  // Final drive: 1-2 hours
            rand(30, 60),   // End of day
        ];

        foreach ($statuses as $index => $status) {
            $duration = $durations[$index];
            $endTime = $currentTime->copy()->addMinutes($duration);

            // Generate random GPS coordinates (example: Texas area)
            $latitude = 29.7604 + (rand(-100, 100) / 1000);
            $longitude = -95.3698 + (rand(-100, 100) / 1000);

            HosEntry::create([
                'user_driver_detail_id' => $driver->id,
                'vehicle_id' => $vehicleAssignment->vehicle_id,
                'carrier_id' => $driver->carrier_id,
                'status' => $status,
                'start_time' => $currentTime,
                'end_time' => $endTime,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'formatted_address' => $this->generateRandomAddress(),
                'location_available' => true,
                'is_manual_entry' => false,
                'manual_entry_reason' => null,
                'created_by' => $driver->user_id,
                'date' => $date->format('Y-m-d'),
            ]);

            // Track totals
            switch ($status) {
                case HosEntry::STATUS_ON_DUTY_DRIVING:
                    $totalDrivingMinutes += $duration;
                    break;
                case HosEntry::STATUS_ON_DUTY_NOT_DRIVING:
                    $totalOnDutyMinutes += $duration;
                    break;
                case HosEntry::STATUS_OFF_DUTY:
                    $totalOffDutyMinutes += $duration;
                    break;
            }

            $currentTime = $endTime;
        }

        // Create daily log
        HosDailyLog::updateOrCreate(
            [
                'user_driver_detail_id' => $driver->id,
                'date' => $date->format('Y-m-d'),
            ],
            [
                'carrier_id' => $driver->carrier_id,
                'vehicle_id' => $vehicleAssignment->vehicle_id,
                'total_driving_minutes' => $totalDrivingMinutes,
                'total_on_duty_minutes' => $totalOnDutyMinutes,
                'total_off_duty_minutes' => $totalOffDutyMinutes,
                'has_violations' => $totalDrivingMinutes > (12 * 60), // Over 12 hours
            ]
        );
    }

    /**
     * Generate a random address for testing.
     */
    protected function generateRandomAddress(): string
    {
        $streets = [
            'Main St', 'Oak Ave', 'Highway 10', 'Industrial Blvd',
            'Commerce Dr', 'Warehouse Rd', 'Trucking Lane', 'Freight Way'
        ];
        
        $cities = [
            'Houston, TX', 'Dallas, TX', 'San Antonio, TX', 'Austin, TX',
            'Fort Worth, TX', 'El Paso, TX', 'Arlington, TX', 'Corpus Christi, TX'
        ];

        $number = rand(100, 9999);
        $street = $streets[array_rand($streets)];
        $city = $cities[array_rand($cities)];

        return "{$number} {$street}, {$city}";
    }
}
