<?php

namespace App\Helpers;

use Carbon\Carbon;

/**
 * FormatHelper - Utility class for formatting data for display
 * 
 * This helper provides consistent formatting methods for dates, names,
 * and UI elements like badge classes across the application.
 */
class FormatHelper
{
    /**
     * Format a date consistently across the application
     * 
     * @param mixed $date Date to format (Carbon instance, string, or null)
     * @param string $format Output format (default: 'm/d/Y' for MM/DD/YYYY)
     * @return string Formatted date or fallback text
     */
    public static function formatDate($date, $format = 'm/d/Y'): string
    {
        if (!$date) {
            return 'Not specified';
        }
        
        if ($date instanceof Carbon) {
            return $date->format($format);
        }
        
        try {
            return Carbon::parse($date)->format($format);
        } catch (\Exception $e) {
            return 'Invalid date';
        }
    }
    
    /**
     * Format a datetime consistently
     * 
     * @param mixed $datetime DateTime to format
     * @param string $format Output format (default: 'm/d/Y h:i A' for MM/DD/YYYY HH:MM AM/PM)
     * @return string Formatted datetime or fallback text
     */
    public static function formatDateTime($datetime, $format = 'm/d/Y h:i A'): string
    {
        return self::formatDate($datetime, $format);
    }
    
    /**
     * Format driver full name
     * 
     * @param mixed $driver Driver object with user relationship and name fields
     * @return string Formatted full name or 'N/A'
     */
    public static function formatDriverName($driver): string
    {
        if (!$driver) {
            return 'N/A';
        }
        
        $parts = array_filter([
            $driver->user->name ?? '',
            $driver->middle_name ?? '',
            $driver->last_name ?? ''
        ]);
        
        return !empty($parts) ? implode(' ', $parts) : 'N/A';
    }
    
    /**
     * Format carrier name with optional DOT/MC numbers
     * 
     * @param mixed $carrier Carrier object with name and DOT number
     * @param bool $includeDot Whether to include DOT number in output
     * @return string Formatted carrier name or 'N/A'
     */
    public static function formatCarrierName($carrier, $includeDot = true): string
    {
        if (!$carrier) {
            return 'N/A';
        }
        
        $name = $carrier->name ?? 'Unknown Carrier';
        
        if ($includeDot && isset($carrier->dot_number) && $carrier->dot_number) {
            $name .= " (DOT: {$carrier->dot_number})";
        }
        
        return $name;
    }
    
    /**
     * Get CSS badge class for status values
     * 
     * @param string $status Status value (approved, rejected, pending, active, inactive)
     * @return string Tailwind CSS classes for badge styling
     */
    public static function getStatusBadgeClass($status): string
    {
        $classes = [
            'approved' => 'bg-success text-white',
            'rejected' => 'bg-danger text-white',
            'pending' => 'bg-warning text-white',
            'active' => 'bg-success text-white',
            'inactive' => 'bg-slate-400 text-white',
        ];
        
        return $classes[strtolower($status)] ?? 'bg-slate-400 text-white';
    }
    
    /**
     * Get CSS badge class for test result values
     * 
     * @param string $result Test result value (passed, negative, failed, positive, pending)
     * @return string Tailwind CSS classes for badge styling
     */
    public static function getTestResultBadgeClass($result): string
    {
        $classes = [
            'passed' => 'bg-success text-white',
            'negative' => 'bg-success text-white',
            'failed' => 'bg-danger text-white',
            'positive' => 'bg-danger text-white',
            'pending' => 'bg-warning text-white',
        ];
        
        return $classes[strtolower($result)] ?? 'bg-warning text-white';
    }
}
