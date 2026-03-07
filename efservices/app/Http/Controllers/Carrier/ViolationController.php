<?php

namespace App\Http\Controllers\Carrier;

use Carbon\Carbon;
use App\Http\Controllers\Controller;
use App\Models\Hos\HosViolation;
use App\Models\UserDriverDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\Hos\HosViolationForgivenessService;
use App\Http\Requests\Hos\ForgiveViolationRequest;
use Illuminate\Validation\ValidationException;

class ViolationController extends Controller
{
    protected HosViolationForgivenessService $forgivenessService;

    public function __construct(HosViolationForgivenessService $forgivenessService)
    {
        $this->forgivenessService = $forgivenessService;
    }

    public function index(Request $request)
    {
        $carrier = $this->getCarrier();
        
        $query = HosViolation::whereHas('driver', function ($q) use ($carrier) {
            $q->where('carrier_id', $carrier->id);
        })->with(['driver.user']);

        if ($request->filled('driver_id')) {
            $query->where('user_driver_detail_id', $request->driver_id);
        }

        if ($request->filled('severity')) {
            $query->where('violation_severity', $request->severity);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('violation_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('violation_date', '<=', $request->date_to);
        }

        $violations = $query->orderBy('violation_date', 'desc')->paginate(20);
        
        $drivers = UserDriverDetail::where('carrier_id', $carrier->id)
            ->with('user')
            ->get();

        return view('carrier.violations.index', compact('violations', 'drivers'));
    }

    public function show(HosViolation $violation)
    {
        $carrier = $this->getCarrier();
        
        if ($violation->driver->carrier_id !== $carrier->id) {
            abort(403);
        }

        $violation->load(['driver.user', 'trip']);

        return view('carrier.violations.show', compact('violation'));
    }

    public function acknowledge(HosViolation $violation)
    {
        $carrier = $this->getCarrier();

        if ($violation->driver->carrier_id !== $carrier->id) {
            abort(403);
        }

        $violation->update([
            'acknowledged' => true,
            'acknowledged_by' => Auth::id(),
            'acknowledged_at' => now(),
        ]);

        return back()->with('success', 'Violation acknowledged successfully.');
    }

    /**
     * Show forgiveness form for a violation.
     */
    public function forgiveForm(HosViolation $violation)
    {
        $carrier = $this->getCarrier();

        if ($violation->driver->carrier_id !== $carrier->id) {
            abort(403);
        }

        $violation->load(['driver.user', 'driver.carrier', 'trip', 'carrier', 'vehicle']);

        // Check if violation can be forgiven
        if ($violation->isForgiven()) {
            return redirect()
                ->route('carrier.violations.show', $violation)
                ->with('error', 'This violation has already been forgiven.');
        }

        return view('carrier.violations.forgive', compact('violation'));
    }

    /**
     * Process violation forgiveness.
     */
    public function forgive(ForgiveViolationRequest $request, HosViolation $violation)
    {
        $carrier = $this->getCarrier();

        if ($violation->driver->carrier_id !== $carrier->id) {
            abort(403);
        }

        try {
            $adjustedEndTime = $request->filled('adjusted_end_time')
                ? Carbon::parse($request->adjusted_end_time)
                : null;

            $this->forgivenessService->forgiveViolation(
                $violation,
                Auth::id(),
                $request->forgiveness_reason,
                $adjustedEndTime
            );

            return redirect()
                ->route('carrier.violations.show', $violation)
                ->with('success', 'Violation has been forgiven successfully. The driver can now drive again.');
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to forgive violation: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Get the current carrier.
     */
    protected function getCarrier()
    {
        $user = Auth::user();
        
        if ($user->carrierDetails) {
            return $user->carrierDetails->carrier;
        }

        $carrier = $user->carriers()->first();
        if ($carrier) {
            return $carrier;
        }

        abort(403, 'Carrier not found.');
    }
}
