<?php

namespace App\Services\Hos;

use Carbon\Carbon;
use InvalidArgumentException;

class HosTimeFormatter
{
    /**
     * Parse time input from various formats to total minutes.
     * Accepts: decimal hours (8.5), HH:MM (8:30), total minutes (510)
     *
     * @param mixed $input
     * @return int Total minutes
     * @throws InvalidArgumentException
     */
    public static function parseTime($input): int
    {
        if (is_null($input)) {
            return 0;
        }

        // If it's already an integer, treat as minutes
        if (is_int($input)) {
            return max(0, $input);
        }

        $input = trim((string) $input);

        if ($input === '') {
            return 0;
        }

        // Check for HH:MM format
        if (preg_match('/^(\d{1,2}):(\d{2})$/', $input, $matches)) {
            $hours = (int) $matches[1];
            $minutes = (int) $matches[2];
            
            if ($minutes >= 60) {
                throw new InvalidArgumentException("Invalid minutes value: {$minutes}. Must be 0-59.");
            }
            
            return ($hours * 60) + $minutes;
        }

        // Check for decimal hours (e.g., 8.5)
        if (preg_match('/^\d+\.?\d*$/', $input)) {
            $value = (float) $input;
            
            // If value is large (> 24), treat as minutes
            if ($value > 24) {
                return (int) round($value);
            }
            
            // Otherwise treat as decimal hours
            return (int) round($value * 60);
        }

        throw new InvalidArgumentException("Unable to parse time value: {$input}");
    }

    /**
     * Format minutes to "Xh Ym" format.
     *
     * @param int $totalMinutes
     * @return string
     */
    public static function formatTime(int $totalMinutes): string
    {
        $totalMinutes = max(0, $totalMinutes);
        $hours = floor($totalMinutes / 60);
        $minutes = $totalMinutes % 60;
        
        return "{$hours}h {$minutes}m";
    }

    /**
     * Format minutes to decimal hours.
     *
     * @param int $totalMinutes
     * @return float
     */
    public static function toDecimalHours(int $totalMinutes): float
    {
        return round($totalMinutes / 60, 2);
    }

    /**
     * Format minutes to HH:MM format.
     *
     * @param int $totalMinutes
     * @return string
     */
    public static function toHoursMinutes(int $totalMinutes): string
    {
        $totalMinutes = max(0, $totalMinutes);
        $hours = floor($totalMinutes / 60);
        $minutes = $totalMinutes % 60;
        
        return sprintf('%d:%02d', $hours, $minutes);
    }

    /**
     * Calculate duration in minutes between two Carbon instances.
     *
     * @param Carbon $start
     * @param Carbon|null $end If null, uses current time
     * @return int
     */
    public static function calculateDuration(Carbon $start, ?Carbon $end = null): int
    {
        $end = $end ?? Carbon::now();
        return (int) $start->diffInMinutes($end);
    }

    /**
     * Calculate duration for an entry, handling midnight crossing.
     * Returns array with minutes for each day if entry spans midnight.
     *
     * @param Carbon $start
     * @param Carbon|null $end
     * @return array ['total' => int, 'by_date' => ['Y-m-d' => int, ...]]
     */
    public static function calculateDurationByDate(Carbon $start, ?Carbon $end = null): array
    {
        $end = $end ?? Carbon::now();
        $result = [
            'total' => 0,
            'by_date' => [],
        ];

        // If same day, simple calculation
        if ($start->isSameDay($end)) {
            $minutes = (int) $start->diffInMinutes($end);
            $result['total'] = $minutes;
            $result['by_date'][$start->format('Y-m-d')] = $minutes;
            return $result;
        }

        // Entry spans multiple days
        $current = $start->copy();
        
        while ($current->lt($end)) {
            $dayEnd = $current->copy()->endOfDay();
            
            if ($dayEnd->gt($end)) {
                $dayEnd = $end;
            }
            
            $minutes = (int) $current->diffInMinutes($dayEnd);
            $dateKey = $current->format('Y-m-d');
            
            $result['by_date'][$dateKey] = ($result['by_date'][$dateKey] ?? 0) + $minutes;
            $result['total'] += $minutes;
            
            // Move to start of next day
            $current = $current->copy()->addDay()->startOfDay();
        }

        return $result;
    }

    /**
     * Get minutes remaining until a limit is reached.
     *
     * @param int $usedMinutes
     * @param int $limitMinutes
     * @return int
     */
    public static function getRemainingMinutes(int $usedMinutes, int $limitMinutes): int
    {
        return max(0, $limitMinutes - $usedMinutes);
    }

    /**
     * Check if used minutes exceed the limit.
     *
     * @param int $usedMinutes
     * @param int $limitMinutes
     * @return bool
     */
    public static function isOverLimit(int $usedMinutes, int $limitMinutes): bool
    {
        return $usedMinutes > $limitMinutes;
    }

    /**
     * Get minutes exceeded over the limit.
     *
     * @param int $usedMinutes
     * @param int $limitMinutes
     * @return int
     */
    public static function getExceededMinutes(int $usedMinutes, int $limitMinutes): int
    {
        return max(0, $usedMinutes - $limitMinutes);
    }

    /**
     * Convert hours to minutes.
     *
     * @param float $hours
     * @return int
     */
    public static function hoursToMinutes(float $hours): int
    {
        return (int) round($hours * 60);
    }

    /**
     * Convert minutes to hours.
     *
     * @param int $minutes
     * @return float
     */
    public static function minutesToHours(int $minutes): float
    {
        return round($minutes / 60, 2);
    }
}
