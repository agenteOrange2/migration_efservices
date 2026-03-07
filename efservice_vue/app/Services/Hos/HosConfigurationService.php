<?php

namespace App\Services\Hos;

use App\Models\Hos\HosConfiguration;
use InvalidArgumentException;

class HosConfigurationService
{
    /**
     * Get configuration for a carrier.
     *
     * @param int $carrierId
     * @return HosConfiguration
     */
    public function getConfiguration(int $carrierId): HosConfiguration
    {
        return HosConfiguration::getForCarrier($carrierId);
    }

    /**
     * Update configuration for a carrier.
     *
     * @param int $carrierId
     * @param array $data
     * @return HosConfiguration
     * @throws InvalidArgumentException
     */
    public function updateConfiguration(int $carrierId, array $data): HosConfiguration
    {
        // Validate the data
        $this->validateConfiguration($data);

        $config = HosConfiguration::getForCarrier($carrierId);

        $updateData = [];

        if (isset($data['max_driving_hours'])) {
            $updateData['max_driving_hours'] = (float) $data['max_driving_hours'];
        }

        if (isset($data['max_duty_hours'])) {
            $updateData['max_duty_hours'] = (float) $data['max_duty_hours'];
        }

        if (isset($data['warning_threshold_minutes'])) {
            $updateData['warning_threshold_minutes'] = (int) $data['warning_threshold_minutes'];
        }

        if (isset($data['violation_threshold_minutes'])) {
            $updateData['violation_threshold_minutes'] = (int) $data['violation_threshold_minutes'];
        }

        if (isset($data['is_active'])) {
            $updateData['is_active'] = (bool) $data['is_active'];
        }

        // Final validation with merged data
        $finalDriving = $updateData['max_driving_hours'] ?? $config->max_driving_hours;
        $finalDuty = $updateData['max_duty_hours'] ?? $config->max_duty_hours;

        if ($finalDriving > $finalDuty) {
            throw new InvalidArgumentException("Driving hours cannot exceed duty hours");
        }

        $config->update($updateData);

        return $config->fresh();
    }

    /**
     * Validate configuration data.
     *
     * @param array $data
     * @throws InvalidArgumentException
     */
    public function validateConfiguration(array $data): void
    {
        // Validate max_driving_hours
        if (isset($data['max_driving_hours'])) {
            $drivingHours = (float) $data['max_driving_hours'];
            if ($drivingHours <= 0) {
                throw new InvalidArgumentException("Maximum driving hours must be a positive value");
            }
            if ($drivingHours > 24) {
                throw new InvalidArgumentException("Maximum driving hours cannot exceed 24 hours");
            }
        }

        // Validate max_duty_hours
        if (isset($data['max_duty_hours'])) {
            $dutyHours = (float) $data['max_duty_hours'];
            if ($dutyHours <= 0) {
                throw new InvalidArgumentException("Maximum duty hours must be a positive value");
            }
            if ($dutyHours > 24) {
                throw new InvalidArgumentException("Maximum duty hours cannot exceed 24 hours");
            }
        }

        // Validate driving <= duty if both provided
        if (isset($data['max_driving_hours']) && isset($data['max_duty_hours'])) {
            if ((float) $data['max_driving_hours'] > (float) $data['max_duty_hours']) {
                throw new InvalidArgumentException("Driving hours cannot exceed duty hours");
            }
        }

        // Validate warning_threshold_minutes
        if (isset($data['warning_threshold_minutes'])) {
            $threshold = (int) $data['warning_threshold_minutes'];
            if ($threshold < 0) {
                throw new InvalidArgumentException("Warning threshold must be a non-negative value");
            }
        }

        // Validate violation_threshold_minutes
        if (isset($data['violation_threshold_minutes'])) {
            $threshold = (int) $data['violation_threshold_minutes'];
            if ($threshold < 0) {
                throw new InvalidArgumentException("Violation threshold must be a non-negative value");
            }
        }
    }

    /**
     * Reset configuration to defaults.
     *
     * @param int $carrierId
     * @return HosConfiguration
     */
    public function resetToDefaults(int $carrierId): HosConfiguration
    {
        $config = HosConfiguration::getForCarrier($carrierId);
        $config->update(HosConfiguration::getDefaults());
        return $config->fresh();
    }

    /**
     * Get default configuration values.
     *
     * @return array
     */
    public function getDefaults(): array
    {
        return HosConfiguration::getDefaults();
    }
}
