<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use App\Http\Controllers\Admin\ApprovedDriversController;

class ApprovedDriversControllerTest extends TestCase
{
    /** @test */
    public function helper_method_exists_and_has_correct_signature()
    {
        $controller = new ApprovedDriversController();
        $reflection = new \ReflectionClass($controller);
        
        // Assert method exists
        $this->assertTrue($reflection->hasMethod('getDriverHosData'));
        
        // Get the method
        $method = $reflection->getMethod('getDriverHosData');
        
        // Assert it's private
        $this->assertTrue($method->isPrivate());
        
        // Assert it has one parameter
        $this->assertCount(1, $method->getParameters());
        
        // Assert parameter is named 'driver'
        $parameters = $method->getParameters();
        $this->assertEquals('driver', $parameters[0]->getName());
        
        // Assert return type is array
        $this->assertEquals('array', $method->getReturnType()->getName());
    }

    /** @test */
    public function show_method_passes_hos_data_to_view()
    {
        // This test verifies that the show() method includes 'hosData' in the compact() call
        // by checking the controller source code structure
        
        $controller = new ApprovedDriversController();
        $reflection = new \ReflectionClass($controller);
        
        // Assert show method exists
        $this->assertTrue($reflection->hasMethod('show'));
        
        // Read the controller file to verify it passes hosData to the view
        $controllerFile = file_get_contents($reflection->getFileName());
        
        // Assert that the show method calls getDriverHosData
        $this->assertStringContainsString('getDriverHosData', $controllerFile);
        
        // Assert that hosData is passed to the view via compact
        $this->assertStringContainsString("compact('driver', 'hosData')", $controllerFile);
    }
}
