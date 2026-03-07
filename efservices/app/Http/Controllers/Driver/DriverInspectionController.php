<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class DriverInspectionController extends Controller
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
        $driver = $this->getDriverDetail()->load(['inspections']);
        $inspections = $driver->inspections ?? collect();

        return view('driver.inspections.index', compact('driver', 'inspections'));
    }

    public function show($inspectionId)
    {
        $driver = $this->getDriverDetail()->load(['inspections']);
        $inspection = $driver->inspections->find($inspectionId);

        if (!$inspection) {
            abort(404, 'Inspection record not found.');
        }

        return view('driver.inspections.show', compact('driver', 'inspection'));
    }
}
