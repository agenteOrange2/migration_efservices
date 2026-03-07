<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Models\Admin\Driver\DriverTesting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DriverTestingController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!Auth::user() || !Auth::user()->driverDetail) {
                abort(403, 'Access denied. Driver profile not found.');
            }
            return $next($request);
        });
    }

    private function getDriverDetail()
    {
        return Auth::user()->driverDetail;
    }

    public function index()
    {
        $driver = $this->getDriverDetail();
        
        // Get testings with relationships
        $testings = DriverTesting::where('user_driver_detail_id', $driver->id)
            ->with(['carrier', 'createdBy', 'updatedBy'])
            ->orderBy('test_date', 'desc')
            ->get();

        return view('driver.testing.index', compact('driver', 'testings'));
    }

    public function show($testingId)
    {
        $driver = $this->getDriverDetail();
        
        // Get testing with all relationships
        $testing = DriverTesting::where('id', $testingId)
            ->where('user_driver_detail_id', $driver->id)
            ->with([
                'carrier',
                'userDriverDetail.user',
                'userDriverDetail.licenses' => function($query) {
                    $query->where('status', 'active')
                          ->orderBy('expiration_date', 'desc');
                },
                'createdBy',
                'updatedBy',
                'media'
            ])
            ->first();

        if (!$testing) {
            abort(404, 'Test record not found.');
        }

        // Get test history for this driver (last 5 tests excluding current)
        $testHistory = DriverTesting::where('user_driver_detail_id', $driver->id)
            ->where('id', '!=', $testing->id)
            ->orderBy('test_date', 'desc')
            ->limit(5)
            ->get();

        return view('driver.testing.show', compact('driver', 'testing', 'testHistory'));
    }

    /**
     * Upload test results and change status to Pending Review
     */
    public function uploadResults(Request $request, $testingId)
    {
        $driver = $this->getDriverDetail();
        
        // Get testing and verify ownership
        $testing = DriverTesting::where('id', $testingId)
            ->where('user_driver_detail_id', $driver->id)
            ->first();

        if (!$testing) {
            return redirect()->route('driver.testing.index')
                ->with('error', 'Test record not found.');
        }

        $request->validate([
            'results' => 'required|array|min:1',
            'results.*' => 'file|mimes:pdf,jpg,jpeg,png,doc,docx|max:10240', // 10MB max
        ]);

        try {
            $uploadedCount = 0;
            
            foreach ($request->file('results') as $file) {
                $testing->addMedia($file)
                    ->toMediaCollection('test_results');
                $uploadedCount++;
            }

            // Change status to Pending Review
            $testing->status = 'Pending Review';
            $testing->save();

            Log::info('Driver uploaded test results', [
                'testing_id' => $testing->id,
                'driver_id' => $driver->id,
                'files_count' => $uploadedCount,
                'new_status' => 'Pending Review'
            ]);

            return redirect()->route('driver.testing.show', $testing->id)
                ->with('success', "Successfully uploaded {$uploadedCount} file(s). Status changed to Pending Review.");
                
        } catch (\Exception $e) {
            Log::error('Error uploading driver test results', [
                'testing_id' => $testing->id,
                'driver_id' => $driver->id,
                'error' => $e->getMessage()
            ]);
            
            return redirect()->back()
                ->with('error', 'Error uploading files. Please try again.');
        }
    }
}
