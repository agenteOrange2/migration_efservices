<?php

namespace Tests\Unit\Helpers;

use App\Helpers\FormatHelper;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;
use stdClass;

class FormatHelperTest extends TestCase
{
    /**
     * Test formatDate with Carbon instance
     */
    public function test_format_date_with_carbon_instance(): void
    {
        $date = Carbon::create(2024, 3, 15);
        $result = FormatHelper::formatDate($date);
        
        $this->assertEquals('03/15/2024', $result);
    }
    
    /**
     * Test formatDate with string date
     */
    public function test_format_date_with_string_date(): void
    {
        $result = FormatHelper::formatDate('2024-03-15');
        
        $this->assertEquals('03/15/2024', $result);
    }
    
    /**
     * Test formatDate with custom format
     */
    public function test_format_date_with_custom_format(): void
    {
        $date = Carbon::create(2024, 3, 15);
        $result = FormatHelper::formatDate($date, 'Y-m-d');
        
        $this->assertEquals('2024-03-15', $result);
    }
    
    /**
     * Test formatDate with null value
     */
    public function test_format_date_with_null_value(): void
    {
        $result = FormatHelper::formatDate(null);
        
        $this->assertEquals('Not specified', $result);
    }
    
    /**
     * Test formatDate with empty string
     */
    public function test_format_date_with_empty_string(): void
    {
        $result = FormatHelper::formatDate('');
        
        $this->assertEquals('Not specified', $result);
    }
    
    /**
     * Test formatDate with invalid date string
     */
    public function test_format_date_with_invalid_date(): void
    {
        $result = FormatHelper::formatDate('invalid-date');
        
        $this->assertEquals('Invalid date', $result);
    }
    
    /**
     * Test formatDateTime with Carbon instance
     */
    public function test_format_datetime_with_carbon_instance(): void
    {
        $datetime = Carbon::create(2024, 3, 15, 14, 30, 0);
        $result = FormatHelper::formatDateTime($datetime);
        
        $this->assertEquals('03/15/2024 02:30 PM', $result);
    }
    
    /**
     * Test formatDateTime with string datetime
     */
    public function test_format_datetime_with_string_datetime(): void
    {
        $result = FormatHelper::formatDateTime('2024-03-15 14:30:00');
        
        $this->assertEquals('03/15/2024 02:30 PM', $result);
    }
    
    /**
     * Test formatDateTime with null value
     */
    public function test_format_datetime_with_null_value(): void
    {
        $result = FormatHelper::formatDateTime(null);
        
        $this->assertEquals('Not specified', $result);
    }
    
    /**
     * Test formatDriverName with complete name data
     */
    public function test_format_driver_name_with_complete_data(): void
    {
        $driver = new stdClass();
        $driver->user = new stdClass();
        $driver->user->name = 'John';
        $driver->middle_name = 'Michael';
        $driver->last_name = 'Doe';
        
        $result = FormatHelper::formatDriverName($driver);
        
        $this->assertEquals('John Michael Doe', $result);
    }
    
    /**
     * Test formatDriverName with missing middle name
     */
    public function test_format_driver_name_without_middle_name(): void
    {
        $driver = new stdClass();
        $driver->user = new stdClass();
        $driver->user->name = 'John';
        $driver->middle_name = '';
        $driver->last_name = 'Doe';
        
        $result = FormatHelper::formatDriverName($driver);
        
        $this->assertEquals('John Doe', $result);
    }
    
    /**
     * Test formatDriverName with only first name
     */
    public function test_format_driver_name_with_only_first_name(): void
    {
        $driver = new stdClass();
        $driver->user = new stdClass();
        $driver->user->name = 'John';
        $driver->middle_name = null;
        $driver->last_name = null;
        
        $result = FormatHelper::formatDriverName($driver);
        
        $this->assertEquals('John', $result);
    }
    
    /**
     * Test formatDriverName with null driver
     */
    public function test_format_driver_name_with_null_driver(): void
    {
        $result = FormatHelper::formatDriverName(null);
        
        $this->assertEquals('N/A', $result);
    }
    
    /**
     * Test formatDriverName with missing user relationship
     */
    public function test_format_driver_name_with_missing_user(): void
    {
        $driver = new stdClass();
        $driver->middle_name = 'Michael';
        $driver->last_name = 'Doe';
        
        $result = FormatHelper::formatDriverName($driver);
        
        $this->assertEquals('Michael Doe', $result);
    }
    
    /**
     * Test formatDriverName with all empty fields
     */
    public function test_format_driver_name_with_all_empty_fields(): void
    {
        $driver = new stdClass();
        $driver->user = new stdClass();
        $driver->user->name = '';
        $driver->middle_name = '';
        $driver->last_name = '';
        
        $result = FormatHelper::formatDriverName($driver);
        
        $this->assertEquals('N/A', $result);
    }
    
    /**
     * Test formatCarrierName with complete data and DOT number
     */
    public function test_format_carrier_name_with_dot_number(): void
    {
        $carrier = new stdClass();
        $carrier->name = 'ABC Transport';
        $carrier->dot_number = '123456';
        
        $result = FormatHelper::formatCarrierName($carrier);
        
        $this->assertEquals('ABC Transport (DOT: 123456)', $result);
    }
    
    /**
     * Test formatCarrierName without including DOT number
     */
    public function test_format_carrier_name_without_including_dot(): void
    {
        $carrier = new stdClass();
        $carrier->name = 'ABC Transport';
        $carrier->dot_number = '123456';
        
        $result = FormatHelper::formatCarrierName($carrier, false);
        
        $this->assertEquals('ABC Transport', $result);
    }
    
    /**
     * Test formatCarrierName with missing DOT number
     */
    public function test_format_carrier_name_with_missing_dot_number(): void
    {
        $carrier = new stdClass();
        $carrier->name = 'ABC Transport';
        $carrier->dot_number = null;
        
        $result = FormatHelper::formatCarrierName($carrier);
        
        $this->assertEquals('ABC Transport', $result);
    }
    
    /**
     * Test formatCarrierName with empty DOT number
     */
    public function test_format_carrier_name_with_empty_dot_number(): void
    {
        $carrier = new stdClass();
        $carrier->name = 'ABC Transport';
        $carrier->dot_number = '';
        
        $result = FormatHelper::formatCarrierName($carrier);
        
        $this->assertEquals('ABC Transport', $result);
    }
    
    /**
     * Test formatCarrierName with null carrier
     */
    public function test_format_carrier_name_with_null_carrier(): void
    {
        $result = FormatHelper::formatCarrierName(null);
        
        $this->assertEquals('N/A', $result);
    }
    
    /**
     * Test formatCarrierName with missing name
     */
    public function test_format_carrier_name_with_missing_name(): void
    {
        $carrier = new stdClass();
        $carrier->dot_number = '123456';
        
        $result = FormatHelper::formatCarrierName($carrier);
        
        $this->assertEquals('Unknown Carrier (DOT: 123456)', $result);
    }
    
    /**
     * Test getStatusBadgeClass with approved status
     */
    public function test_get_status_badge_class_approved(): void
    {
        $result = FormatHelper::getStatusBadgeClass('approved');
        
        $this->assertEquals('bg-success text-white', $result);
    }
    
    /**
     * Test getStatusBadgeClass with rejected status
     */
    public function test_get_status_badge_class_rejected(): void
    {
        $result = FormatHelper::getStatusBadgeClass('rejected');
        
        $this->assertEquals('bg-danger text-white', $result);
    }
    
    /**
     * Test getStatusBadgeClass with pending status
     */
    public function test_get_status_badge_class_pending(): void
    {
        $result = FormatHelper::getStatusBadgeClass('pending');
        
        $this->assertEquals('bg-warning text-white', $result);
    }
    
    /**
     * Test getStatusBadgeClass with active status
     */
    public function test_get_status_badge_class_active(): void
    {
        $result = FormatHelper::getStatusBadgeClass('active');
        
        $this->assertEquals('bg-success text-white', $result);
    }
    
    /**
     * Test getStatusBadgeClass with inactive status
     */
    public function test_get_status_badge_class_inactive(): void
    {
        $result = FormatHelper::getStatusBadgeClass('inactive');
        
        $this->assertEquals('bg-slate-400 text-white', $result);
    }
    
    /**
     * Test getStatusBadgeClass with uppercase status
     */
    public function test_get_status_badge_class_uppercase(): void
    {
        $result = FormatHelper::getStatusBadgeClass('APPROVED');
        
        $this->assertEquals('bg-success text-white', $result);
    }
    
    /**
     * Test getStatusBadgeClass with mixed case status
     */
    public function test_get_status_badge_class_mixed_case(): void
    {
        $result = FormatHelper::getStatusBadgeClass('Pending');
        
        $this->assertEquals('bg-warning text-white', $result);
    }
    
    /**
     * Test getStatusBadgeClass with unknown status
     */
    public function test_get_status_badge_class_unknown(): void
    {
        $result = FormatHelper::getStatusBadgeClass('unknown');
        
        $this->assertEquals('bg-slate-400 text-white', $result);
    }
    
    /**
     * Test getTestResultBadgeClass with passed result
     */
    public function test_get_test_result_badge_class_passed(): void
    {
        $result = FormatHelper::getTestResultBadgeClass('passed');
        
        $this->assertEquals('bg-success text-white', $result);
    }
    
    /**
     * Test getTestResultBadgeClass with negative result
     */
    public function test_get_test_result_badge_class_negative(): void
    {
        $result = FormatHelper::getTestResultBadgeClass('negative');
        
        $this->assertEquals('bg-success text-white', $result);
    }
    
    /**
     * Test getTestResultBadgeClass with failed result
     */
    public function test_get_test_result_badge_class_failed(): void
    {
        $result = FormatHelper::getTestResultBadgeClass('failed');
        
        $this->assertEquals('bg-danger text-white', $result);
    }
    
    /**
     * Test getTestResultBadgeClass with positive result
     */
    public function test_get_test_result_badge_class_positive(): void
    {
        $result = FormatHelper::getTestResultBadgeClass('positive');
        
        $this->assertEquals('bg-danger text-white', $result);
    }
    
    /**
     * Test getTestResultBadgeClass with pending result
     */
    public function test_get_test_result_badge_class_pending(): void
    {
        $result = FormatHelper::getTestResultBadgeClass('pending');
        
        $this->assertEquals('bg-warning text-white', $result);
    }
    
    /**
     * Test getTestResultBadgeClass with uppercase result
     */
    public function test_get_test_result_badge_class_uppercase(): void
    {
        $result = FormatHelper::getTestResultBadgeClass('PASSED');
        
        $this->assertEquals('bg-success text-white', $result);
    }
    
    /**
     * Test getTestResultBadgeClass with unknown result
     */
    public function test_get_test_result_badge_class_unknown(): void
    {
        $result = FormatHelper::getTestResultBadgeClass('unknown');
        
        $this->assertEquals('bg-warning text-white', $result);
    }
}
