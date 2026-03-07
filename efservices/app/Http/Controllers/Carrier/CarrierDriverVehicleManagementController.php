<?php

namespace App\Http\Controllers\Carrier;

use App\Http\Controllers\Controller;
use App\Traits\ValidatesCarrierOwnership;
use App\Models\UserDriverDetail;
use App\Models\Admin\Vehicle\Vehicle;
use App\Models\Admin\Vehicle\VehicleDriverAssignment;
use App\Models\AdminMessage;
use App\Models\MessageRecipient;
use App\Models\ThirdPartyDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class CarrierDriverVehicleManagementController extends Controller
{
    use ValidatesCarrierOwnership;
    
    /**
     * Display a listing of drivers for the authenticated carrier.
     * 
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $carrier = $this->getAuthenticatedCarrier();
        
        // Start building the query with carrier filtering
        $query = UserDriverDetail::where('carrier_id', $carrier->id)
            ->with([
                'user',
                'carrier',
                'activeVehicleAssignment.vehicle',
                'activeVehicleAssignment.companyDriverDetail',
                'activeVehicleAssignment.ownerOperatorDetail',
                'activeVehicleAssignment.thirdPartyDetail'
            ]);
        
        // Apply search filter (name or email)
        if ($request->filled('search')) {
            $searchTerm = $request->input('search');
            $query->where(function($q) use ($searchTerm) {
                $q->whereHas('user', function($userQuery) use ($searchTerm) {
                    $userQuery->where('name', 'like', "%{$searchTerm}%")
                             ->orWhere('email', 'like', "%{$searchTerm}%");
                })
                ->orWhere('last_name', 'like', "%{$searchTerm}%");
            });
        }
        
        // Apply driver type filter
        if ($request->filled('driver_type')) {
            $driverType = $request->input('driver_type');
            $query->whereHas('activeVehicleAssignment', function($q) use ($driverType) {
                $q->where('driver_type', $driverType);
            });
        }
        
        // Apply assignment status filter
        if ($request->filled('assignment_status')) {
            $assignmentStatus = $request->input('assignment_status');
            
            if ($assignmentStatus === 'assigned') {
                $query->has('activeVehicleAssignment');
            } elseif ($assignmentStatus === 'unassigned') {
                $query->doesntHave('activeVehicleAssignment');
            }
        }
        
        // Paginate results (15 per page)
        $drivers = $query->paginate(15)->withQueryString();
        
        return view('carrier.driver-management.index', compact('drivers', 'carrier'));
    }
    
    /**
     * Display the specified driver with full details.
     * 
     * @param UserDriverDetail $driver
     * @return \Illuminate\View\View
     */
    public function show(UserDriverDetail $driver)
    {
        // Validate carrier ownership - return 403 if driver belongs to different carrier
        $this->validateCarrierOwnership($driver);
        $carrier = $this->getAuthenticatedCarrier();
        
        // Load driver with all necessary relationships
        $driver->load([
            'user',
            'carrier',
            'activeVehicleAssignment.vehicle',
            'activeVehicleAssignment.companyDriverDetail',
            'activeVehicleAssignment.ownerOperatorDetail',
            'activeVehicleAssignment.thirdPartyDetail',
            'vehicleAssignments' => function($query) {
                // Get last 5 assignments ordered by date descending
                $query->with(['vehicle', 'companyDriverDetail', 'ownerOperatorDetail', 'thirdPartyDetail'])
                      ->orderBy('created_at', 'desc')
                      ->limit(5);
            }
        ]);
        
        return view('carrier.driver-management.show', compact('driver', 'carrier'));
    }
    
    /**
     * Show the form for assigning a vehicle to a driver.
     * 
     * @param UserDriverDetail $driver
     * @return \Illuminate\View\View
     */
    public function assignVehicle(UserDriverDetail $driver)
    {
        // Validate carrier ownership
        $this->validateCarrierOwnership($driver);
        $carrier = $this->getAuthenticatedCarrier();
        
        // Load available vehicles (carrier's vehicles without active assignments)
        $availableVehicles = Vehicle::where('carrier_id', $carrier->id)
            ->whereDoesntHave('activeDriverAssignment')
            ->orderBy('company_unit_number')
            ->get();
        
        return view('carrier.driver-management.assign-vehicle', compact('driver', 'carrier', 'availableVehicles'));
    }
    
    /**
     * Store a new vehicle assignment for a driver.
     * 
     * @param Request $request
     * @param UserDriverDetail $driver
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storeVehicleAssignment(Request $request, UserDriverDetail $driver)
    {
        // Validate carrier ownership
        $this->validateCarrierOwnership($driver);
        $carrier = $this->getAuthenticatedCarrier();
        
        // Validate request data
        $validated = $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'assignment_date' => 'required|date',
            'notes' => 'nullable|string|max:500',
        ]);
        
        // Load the vehicle and validate it belongs to the carrier
        $vehicle = Vehicle::findOrFail($validated['vehicle_id']);
        $this->validateCarrierOwnership($vehicle);
        
        // Check if the vehicle already has an active assignment
        $vehicleHasActiveAssignment = VehicleDriverAssignment::where('vehicle_id', $validated['vehicle_id'])
            ->where('status', 'active')
            ->exists();
        
        if ($vehicleHasActiveAssignment) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Vehicle is already assigned to another driver. Please select a different vehicle.');
        }
        
        // Check if the driver already has an active assignment
        $driverHasActiveAssignment = VehicleDriverAssignment::where('user_driver_detail_id', $driver->id)
            ->where('status', 'active')
            ->exists();
        
        if ($driverHasActiveAssignment) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Driver already has an active vehicle assignment. Please cancel the current assignment first.');
        }
        
        try {
            DB::beginTransaction();
            
            // Create a new active assignment
            $newAssignment = VehicleDriverAssignment::create([
                'vehicle_id' => $validated['vehicle_id'],
                'user_driver_detail_id' => $driver->id,
                'driver_type' => 'company_driver', // Default to company_driver like admin
                'start_date' => $validated['assignment_date'],
                'status' => 'active',
                'notes' => $validated['notes'],
            ]);
            
            DB::commit();
            
            return redirect()
                ->route('carrier.driver-vehicle-management.show', $driver->id)
                ->with('success', 'Vehicle assigned successfully.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error assigning vehicle to driver', [
                'error' => $e->getMessage(),
                'driver_id' => $driver->id,
                'vehicle_id' => $validated['vehicle_id'],
            ]);
            
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'An error occurred while assigning the vehicle. Please try again.');
        }
    }

    /**
     * Show the form for editing an existing vehicle assignment.
     * 
     * @param UserDriverDetail $driver
     * @return \Illuminate\View\View
     */
    public function editAssignment(UserDriverDetail $driver)
    {
        // Validate carrier ownership
        $this->validateCarrierOwnership($driver);
        $carrier = $this->getAuthenticatedCarrier();
        
        // Load the current active assignment
        $currentAssignment = $driver->activeVehicleAssignment()
            ->with(['vehicle', 'companyDriverDetail', 'ownerOperatorDetail', 'thirdPartyDetail'])
            ->first();
        
        // If no active assignment, redirect back with error
        if (!$currentAssignment) {
            return redirect()
                ->route('carrier.driver-vehicle-management.show', $driver->id)
                ->with('error', 'No active assignment found to edit.');
        }
        
        // Load available vehicles (carrier's vehicles without active assignments)
        // Include the currently assigned vehicle in the list
        $availableVehicles = Vehicle::where('carrier_id', $carrier->id)
            ->where(function($query) use ($currentAssignment) {
                $query->whereDoesntHave('activeDriverAssignment')
                      ->orWhere('id', $currentAssignment->vehicle_id);
            })
            ->orderBy('company_unit_number')
            ->get();
        
        return view('carrier.driver-management.edit-assignment', compact('driver', 'carrier', 'currentAssignment', 'availableVehicles'));
    }
    
    /**
     * Update an existing vehicle assignment.
     * 
     * @param Request $request
     * @param UserDriverDetail $driver
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateAssignment(Request $request, UserDriverDetail $driver)
    {
        // Validate carrier ownership
        $this->validateCarrierOwnership($driver);
        $carrier = $this->getAuthenticatedCarrier();
        
        // Validate request data
        $validated = $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'driver_type' => 'required|in:company_driver,owner_operator,third_party',
            'start_date' => 'required|date',
            'notes' => 'nullable|string|max:1000',
            // Third party fields (required only if driver_type is third_party)
            'third_party_name' => 'required_if:driver_type,third_party|nullable|string|max:255',
            'third_party_dba' => 'nullable|string|max:255',
            'third_party_address' => 'required_if:driver_type,third_party|nullable|string|max:500',
            'third_party_phone' => 'required_if:driver_type,third_party|nullable|string|max:20',
            'third_party_email' => 'required_if:driver_type,third_party|nullable|email|max:255',
            'third_party_fein' => 'nullable|string|max:20',
            'third_party_contact' => 'nullable|string|max:255',
        ]);
        
        // Load the vehicle and validate it belongs to the carrier
        $vehicle = Vehicle::findOrFail($validated['vehicle_id']);
        $this->validateCarrierOwnership($vehicle);
        
        // Get the current active assignment
        $currentAssignment = $driver->activeVehicleAssignment()->first();
        
        if (!$currentAssignment) {
            return redirect()
                ->route('carrier.driver-vehicle-management.show', $driver->id)
                ->with('error', 'No active assignment found to update.');
        }
        
        // Check if the new vehicle is different and already has an active assignment
        if ($validated['vehicle_id'] != $currentAssignment->vehicle_id) {
            $vehicleHasActiveAssignment = VehicleDriverAssignment::where('vehicle_id', $validated['vehicle_id'])
                ->where('status', 'active')
                ->where('id', '!=', $currentAssignment->id)
                ->exists();
            
            if ($vehicleHasActiveAssignment) {
                return redirect()
                    ->back()
                    ->withInput()
                    ->with('error', 'Vehicle is already assigned to another driver. Please select a different vehicle.');
            }
        }
        
        try {
            DB::beginTransaction();
            
            // Mark the current assignment as inactive with end date
            $currentAssignment->update([
                'status' => 'inactive',
                'end_date' => now()->format('Y-m-d'),
            ]);
            
            // Create a new active assignment with the new vehicle
            $newAssignment = VehicleDriverAssignment::create([
                'vehicle_id' => $validated['vehicle_id'],
                'user_driver_detail_id' => $driver->id,
                'driver_type' => $validated['driver_type'],
                'start_date' => $validated['start_date'],
                'status' => 'active',
                'notes' => $validated['notes'],
            ]);
            
            // If driver type is third_party, create or update ThirdPartyDetail record
            if ($validated['driver_type'] === 'third_party') {
                ThirdPartyDetail::create([
                    'vehicle_driver_assignment_id' => $newAssignment->id,
                    'third_party_name' => $validated['third_party_name'],
                    'third_party_dba' => $validated['third_party_dba'] ?? null,
                    'third_party_address' => $validated['third_party_address'],
                    'third_party_phone' => $validated['third_party_phone'],
                    'third_party_email' => $validated['third_party_email'],
                    'third_party_fein' => $validated['third_party_fein'] ?? null,
                    'third_party_contact' => $validated['third_party_contact'] ?? null,
                ]);
            }
            
            DB::commit();
            
            return redirect()
                ->route('carrier.driver-vehicle-management.show', $driver->id)
                ->with('success', 'Vehicle assignment updated successfully.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error updating vehicle assignment', [
                'error' => $e->getMessage(),
                'driver_id' => $driver->id,
                'current_assignment_id' => $currentAssignment->id,
                'new_vehicle_id' => $validated['vehicle_id'],
            ]);
            
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'An error occurred while updating the assignment. Please try again.');
        }
    }
    
    /**
     * Cancel an active vehicle assignment.
     * 
     * @param Request $request
     * @param UserDriverDetail $driver
     * @return \Illuminate\Http\RedirectResponse
     */
    public function cancelAssignment(Request $request, UserDriverDetail $driver)
    {
        // Validate carrier ownership
        $this->validateCarrierOwnership($driver);
        $carrier = $this->getAuthenticatedCarrier();
        
        // Validate request data
        $validated = $request->validate([
            'termination_date' => 'required|date|before_or_equal:today',
            'termination_reason' => 'required|string|max:1000',
        ]);
        
        // Get the current active assignment
        $currentAssignment = $driver->activeVehicleAssignment()->first();
        
        if (!$currentAssignment) {
            return redirect()
                ->route('carrier.driver-vehicle-management.show', $driver->id)
                ->with('error', 'No active assignment found to cancel.');
        }
        
        // Validate that the vehicle belongs to the carrier
        $this->validateCarrierOwnership($currentAssignment->vehicle);
        
        try {
            DB::beginTransaction();
            
            // Mark the assignment as inactive with termination date and reason
            $currentAssignment->update([
                'status' => 'inactive',
                'end_date' => $validated['termination_date'],
                'notes' => ($currentAssignment->notes ? $currentAssignment->notes . "\n\n" : '') 
                          . "Termination Reason: " . $validated['termination_reason'],
            ]);
            
            // Update vehicle status to available (pending)
            $currentAssignment->vehicle->update([
                'status' => Vehicle::STATUS_PENDING,
            ]);
            
            DB::commit();
            
            return redirect()
                ->route('carrier.driver-vehicle-management.show', $driver->id)
                ->with('success', 'Vehicle assignment cancelled successfully.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error cancelling vehicle assignment', [
                'error' => $e->getMessage(),
                'driver_id' => $driver->id,
                'assignment_id' => $currentAssignment->id,
            ]);
            
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'An error occurred while cancelling the assignment. Please try again.');
        }
    }
    
    /**
     * Display the complete assignment history for a driver.
     * 
     * @param UserDriverDetail $driver
     * @return \Illuminate\View\View
     */
    public function assignmentHistory(UserDriverDetail $driver)
    {
        // Validate carrier ownership
        $this->validateCarrierOwnership($driver);
        $carrier = $this->getAuthenticatedCarrier();
        
        // Load all assignments ordered by date descending
        $assignments = $driver->vehicleAssignments()
            ->with(['vehicle', 'assignedByUser'])
            ->orderBy('created_at', 'desc')
            ->get();
        
        return view('carrier.driver-management.assignment-history', compact('driver', 'carrier', 'assignments'));
    }
    
    /**
     * Show the contact form for sending a message to a driver.
     * 
     * @param UserDriverDetail $driver
     * @return \Illuminate\View\View
     */
    public function contact(UserDriverDetail $driver)
    {
        // Validate carrier ownership
        $this->validateCarrierOwnership($driver);
        $carrier = $this->getAuthenticatedCarrier();
        
        return view('carrier.driver-management.contact', compact('driver', 'carrier'));
    }
    
    /**
     * Send a contact message to a driver.
     * 
     * @param Request $request
     * @param UserDriverDetail $driver
     * @return \Illuminate\Http\RedirectResponse
     */
    public function sendContact(Request $request, UserDriverDetail $driver)
    {
        // Validate carrier ownership
        $this->validateCarrierOwnership($driver);
        $carrier = $this->getAuthenticatedCarrier();
        
        // Validate request data
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:5000',
            'priority' => 'required|in:low,normal,high',
        ]);
        
        try {
            DB::beginTransaction();
            
            // Create AdminMessage record
            $adminMessage = AdminMessage::create([
                'sender_id' => Auth::id(),
                'subject' => $validated['subject'],
                'message' => $validated['message'],
                'priority' => $validated['priority'],
                'status' => 'sent',
                'sent_at' => now(),
            ]);
            
            // Create MessageRecipient record
            $messageRecipient = MessageRecipient::create([
                'message_id' => $adminMessage->id,
                'recipient_type' => 'driver',
                'recipient_id' => $driver->id,
                'email' => $driver->user->email,
                'name' => $driver->user->name . ' ' . ($driver->last_name ?? ''),
                'delivery_status' => 'pending',
            ]);
            
            // Send email to driver
            try {
                Mail::to($driver->user->email)->send(
                    new \App\Mail\DriverContactMail(
                        [
                            'subject' => $validated['subject'],
                            'message' => $validated['message'],
                            'priority' => $validated['priority'],
                        ],
                        Auth::user()->name,
                        Auth::user()->email
                    )
                );
                
                // Update delivery status to delivered
                $messageRecipient->update([
                    'delivery_status' => 'delivered',
                    'delivered_at' => now(),
                ]);
                
            } catch (\Exception $emailException) {
                // Log email error but don't fail the entire operation
                Log::error('Error sending contact email to driver', [
                    'error' => $emailException->getMessage(),
                    'driver_id' => $driver->id,
                    'message_id' => $adminMessage->id,
                ]);
                
                // Update delivery status to failed
                $messageRecipient->update([
                    'delivery_status' => 'failed',
                ]);
                
                // Update admin message status
                $adminMessage->update([
                    'status' => 'failed',
                ]);
            }
            
            DB::commit();
            
            return redirect()
                ->route('carrier.driver-vehicle-management.show', $driver->id)
                ->with('success', 'Message sent successfully to driver.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error sending contact message to driver', [
                'error' => $e->getMessage(),
                'driver_id' => $driver->id,
            ]);
            
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'An error occurred while sending the message. Please try again.');
        }
    }
}
