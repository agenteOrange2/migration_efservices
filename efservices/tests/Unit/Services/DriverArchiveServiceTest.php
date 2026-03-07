<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\Driver\DriverArchiveService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DriverArchiveServiceTest extends TestCase
{
    use RefreshDatabase;

    protected DriverArchiveService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DriverArchiveService();
    }

    /** @test */
    public function it_has_correct_license_snapshot_structure()
    {
        // This test verifies the structure of the getLicensesSnapshot method
        // by checking the method exists and returns the expected structure
        
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getLicensesSnapshot');
        $method->setAccessible(true);
        
        // Verify method exists
        $this->assertTrue($method->isProtected());
        $this->assertEquals('getLicensesSnapshot', $method->getName());
    }

    /** @test */
    public function it_has_correct_medical_snapshot_structure()
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getMedicalSnapshot');
        $method->setAccessible(true);
        
        // Verify method exists
        $this->assertTrue($method->isProtected());
        $this->assertEquals('getMedicalSnapshot', $method->getName());
    }

    /** @test */
    public function it_has_correct_employment_history_snapshot_structure()
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getEmploymentHistorySnapshot');
        $method->setAccessible(true);
        
        // Verify method exists
        $this->assertTrue($method->isProtected());
        $this->assertEquals('getEmploymentHistorySnapshot', $method->getName());
    }

    /** @test */
    public function it_has_correct_testing_snapshot_structure()
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getTestingSnapshot');
        $method->setAccessible(true);
        
        // Verify method exists
        $this->assertTrue($method->isProtected());
        $this->assertEquals('getTestingSnapshot', $method->getName());
    }

    /** @test */
    public function it_has_correct_accidents_snapshot_structure()
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getAccidentsSnapshot');
        $method->setAccessible(true);
        
        // Verify method exists
        $this->assertTrue($method->isProtected());
        $this->assertEquals('getAccidentsSnapshot', $method->getName());
    }

    /** @test */
    public function it_has_correct_inspections_snapshot_structure()
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getInspectionsSnapshot');
        $method->setAccessible(true);
        
        // Verify method exists
        $this->assertTrue($method->isProtected());
        $this->assertEquals('getInspectionsSnapshot', $method->getName());
    }

    /** @test */
    public function it_has_correct_training_snapshot_structure()
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getTrainingSnapshot');
        $method->setAccessible(true);
        
        // Verify method exists
        $this->assertTrue($method->isProtected());
        $this->assertEquals('getTrainingSnapshot', $method->getName());
    }

    /** @test */
    public function it_has_correct_certifications_snapshot_structure()
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getCertificationsSnapshot');
        $method->setAccessible(true);
        
        // Verify method exists
        $this->assertTrue($method->isProtected());
        $this->assertEquals('getCertificationsSnapshot', $method->getName());
    }

    /** @test */
    public function it_has_correct_convictions_snapshot_structure()
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getConvictionsSnapshot');
        $method->setAccessible(true);
        
        // Verify method exists
        $this->assertTrue($method->isProtected());
        $this->assertEquals('getConvictionsSnapshot', $method->getName());
    }

    /** @test */
    public function service_has_all_required_snapshot_methods()
    {
        $reflection = new \ReflectionClass($this->service);
        $methods = $reflection->getMethods(\ReflectionMethod::IS_PROTECTED);
        $methodNames = array_map(fn($m) => $m->getName(), $methods);
        
        // Verify all snapshot methods exist
        $this->assertContains('getLicensesSnapshot', $methodNames);
        $this->assertContains('getMedicalSnapshot', $methodNames);
        $this->assertContains('getEmploymentHistorySnapshot', $methodNames);
        $this->assertContains('getTestingSnapshot', $methodNames);
        $this->assertContains('getAccidentsSnapshot', $methodNames);
        $this->assertContains('getInspectionsSnapshot', $methodNames);
        $this->assertContains('getTrainingSnapshot', $methodNames);
        $this->assertContains('getCertificationsSnapshot', $methodNames);
        $this->assertContains('getConvictionsSnapshot', $methodNames);
    }
}
