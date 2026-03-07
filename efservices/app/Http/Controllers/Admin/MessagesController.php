<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Shared\MessagesBaseController;
use App\Models\AdminMessage;
use App\Models\MessageRecipient;
use App\Models\MessageStatusLog;
use App\Models\User;
use App\Models\Carrier;
use App\Models\UserDriverDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MessagesController extends MessagesBaseController
{
    /**
     * Get the authenticated sender (Admin User)
     */
    protected function getAuthenticatedSender(): array
    {
        $user = Auth::user();
        
        return [
            'type' => 'App\\Models\\User',
            'id' => $user->id,
            'model' => $user
        ];
    }

    /**
     * Get available recipients for admin (all drivers, carriers, users, emails)
     */
    protected function getAvailableRecipients(): array
    {
        return [
            'drivers' => UserDriverDetail::with(['user', 'carrier'])
                ->where('application_completed', 1)
                ->whereHas('user', function($query) {
                    $query->where('status', 1);
                })
                ->get(),
            'carriers' => Carrier::where('status', 1)
                ->orderBy('name')
                ->get(),
        ];
    }

    /**
     * Get view path for admin messages
     */
    protected function getViewPath(string $view): string
    {
        return "admin.messages.{$view}";
    }

    /**
     * Display a listing of messages
     */
    public function index(Request $request)
    {
        $query = $this->buildMessagesQuery($request);

        $messages = $query->paginate(15)->withQueryString();

        // Get statistics
        $stats = $this->getMessageStatistics();

        return view($this->getViewPath('index'), compact('messages', 'stats'));
    }

    /**
     * Show the form for creating a new message
     */
    public function create()
    {
        $recipients = $this->getAvailableRecipients();
        $drivers = $recipients['drivers'];
        $carriers = $recipients['carriers'];

        return view($this->getViewPath('create'), compact('drivers', 'carriers'));
    }

    /**
     * Store a newly created message
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'recipient_type' => ['required', Rule::in(['all_drivers', 'specific_drivers', 'specific_carriers', 'custom_emails'])],
            'driver_ids' => 'nullable|array|required_if:recipient_type,specific_drivers',
            'driver_ids.*' => 'nullable|exists:user_driver_details,id',
            'carrier_ids' => 'nullable|array|required_if:recipient_type,specific_carriers',
            'carrier_ids.*' => 'nullable|exists:carriers,id',
            'custom_emails' => 'nullable|string|required_if:recipient_type,custom_emails',
            'carrier_filter' => 'nullable|exists:carriers,id',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:2000',
            'priority' => ['required', Rule::in(['low', 'normal', 'high'])],
            'status' => ['required', Rule::in(['draft', 'sent'])],
        ]);

        $sender = $this->getAuthenticatedSender();
        $recipients = [];

        // Process recipients based on type
        switch ($validated['recipient_type']) {
            case 'all_drivers':
                $driversQuery = UserDriverDetail::with(['user', 'carrier'])
                    ->where('application_completed', 1)
                    ->whereHas('user', function($query) {
                        $query->where('status', 1);
                    });
                
                // Apply carrier filter if specified
                if (!empty($validated['carrier_filter'])) {
                    $driversQuery->where('carrier_id', $validated['carrier_filter']);
                }
                
                $drivers = $driversQuery->get();
                
                foreach ($drivers as $driver) {
                    $recipients[] = $this->buildRecipientsArray(
                        0, // Will be set after message creation
                        'driver',
                        $driver->id,
                        $driver->user->email,
                        $driver->user->name
                    );
                }
                break;

            case 'specific_drivers':
                $drivers = UserDriverDetail::with(['user', 'carrier'])
                    ->whereIn('id', $validated['driver_ids'])
                    ->get();
                
                foreach ($drivers as $driver) {
                    $recipients[] = $this->buildRecipientsArray(
                        0,
                        'driver',
                        $driver->id,
                        $driver->user->email,
                        $driver->user->name
                    );
                }
                break;

            case 'specific_carriers':
                $carriers = Carrier::with('users')
                    ->whereIn('id', $validated['carrier_ids'])
                    ->get();
                
                foreach ($carriers as $carrier) {
                    // Get the primary user for the carrier
                    $carrierUser = $carrier->users->first();
                    if ($carrierUser) {
                        $recipients[] = $this->buildRecipientsArray(
                            0,
                            'carrier',
                            $carrier->id,
                            $carrierUser->email,
                            $carrier->name
                        );
                    }
                }
                break;

            case 'custom_emails':
                $emails = array_filter(array_map('trim', preg_split('/[,\n\r]+/', $validated['custom_emails'])));
                
                foreach ($emails as $email) {
                    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $recipients[] = $this->buildRecipientsArray(
                            0,
                            'email',
                            null,
                            $email,
                            $email
                        );
                    }
                }
                break;
        }

        $result = $this->storeMessage($validated, $sender, $recipients);

        if ($result['success']) {
            $successMessage = $validated['status'] === 'sent' 
                ? 'Message sent successfully to ' . $result['count'] . ' recipients!'
                : 'Message saved as draft with ' . $result['count'] . ' recipients!';

            return redirect()->route('admin.messages.show', $result['message'])
                ->with('success', $successMessage);
        } else {
            return back()->withInput()
                ->with('error', 'Failed to process message: ' . $result['error']);
        }
    }

    /**
     * Display the specified message
     */
    public function show(AdminMessage $message)
    {
        $message->load(['sender', 'recipients', 'statusLogs' => function($query) {
            $query->orderBy('created_at', 'desc');
        }]);

        return view($this->getViewPath('show'), compact('message'));
    }

    /**
     * Show the form for editing a message
     */
    public function edit(AdminMessage $message)
    {
        $sender = $this->getAuthenticatedSender();
        
        // Only allow editing of draft messages
        if (!$this->canEditMessage($message, $sender)) {
            return redirect()->route('admin.messages.show', $message)
                ->with('error', 'Only draft messages can be edited.');
        }

        $recipients = $this->getAvailableRecipients();
        $availableDrivers = $recipients['drivers'];
        $carriers = $recipients['carriers'];

        return view($this->getViewPath('edit'), compact('message', 'availableDrivers', 'carriers'));
    }

    /**
     * Update the specified message
     */
    public function update(Request $request, AdminMessage $message)
    {
        $sender = $this->getAuthenticatedSender();
        
        // Only allow updating of draft messages
        if (!$this->canEditMessage($message, $sender)) {
            return redirect()->route('admin.messages.show', $message)
                ->with('error', 'Only draft messages can be updated.');
        }

        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:2000',
            'priority' => ['required', Rule::in(['low', 'normal', 'high'])]
        ]);

        $message->update($validated);

        MessageStatusLog::createLog($message->id, 'updated', 'Message content updated');

        return redirect()->route('admin.messages.show', $message)
            ->with('success', 'Message updated successfully!');
    }

    /**
     * Remove the specified message
     */
    public function destroy(AdminMessage $message)
    {
        $sender = $this->getAuthenticatedSender();
        
        // Only allow deletion of draft messages by owner
        if (!$this->canEditMessage($message, $sender)) {
            return back()->with('error', 'Only draft messages can be deleted.');
        }

        $message->delete();

        return redirect()->route('admin.messages.index')
            ->with('success', 'Message deleted successfully!');
    }

    /**
     * Remove a recipient from a message
     */
    public function removeRecipient(AdminMessage $message, MessageRecipient $recipient)
    {
        $sender = $this->getAuthenticatedSender();
        
        // Only allow removing recipients from draft messages
        if (!$this->canEditMessage($message, $sender)) {
            return response()->json([
                'success' => false,
                'message' => 'Only recipients from draft messages can be removed.'
            ], 400);
        }

        // Verify the recipient belongs to this message
        if ($recipient->message_id !== $message->id) {
            return response()->json([
                'success' => false,
                'message' => 'Recipient does not belong to this message.'
            ], 400);
        }

        $recipient->delete();

        return response()->json([
            'success' => true,
            'message' => 'Recipient removed successfully!'
        ]);
    }

    /**
     * Dashboard with statistics
     */
    public function dashboard()
    {
        $stats = $this->getMessageStatistics();

        // Get status and priority distributions
        $statusDistribution = AdminMessage::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
            
        $priorityDistribution = AdminMessage::select('priority', DB::raw('count(*) as count'))
            ->groupBy('priority')
            ->pluck('count', 'priority')
            ->toArray();

        // Get sender type distribution
        $senderTypeDistribution = AdminMessage::select('sender_type', DB::raw('count(*) as count'))
            ->groupBy('sender_type')
            ->get()
            ->mapWithKeys(function($item) {
                $label = match($item->sender_type) {
                    'App\\Models\\User' => 'Admin',
                    'App\\Models\\Carrier' => 'Carrier',
                    'App\\Models\\UserDriverDetail' => 'Driver',
                    default => 'Other'
                };
                return [$label => $item->count];
            })
            ->toArray();

        // Calculate delivery statistics
        $deliveryStats = [
            'total' => MessageRecipient::count(),
            'delivered' => MessageRecipient::where('delivery_status', 'delivered')->count(),
            'pending' => MessageRecipient::where('delivery_status', 'pending')->count(),
            'failed' => MessageRecipient::where('delivery_status', 'failed')->count(),
            'read' => MessageRecipient::whereNotNull('read_at')->count(),
        ];

        $recentMessages = AdminMessage::with(['sender', 'recipients'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view($this->getViewPath('dashboard'), compact(
            'stats', 
            'recentMessages', 
            'statusDistribution', 
            'priorityDistribution', 
            'senderTypeDistribution', 
            'deliveryStats'
        ));
    }

    /**
     * Resend a message to all its recipients
     */
    public function resend(AdminMessage $message)
    {
        // Only allow resending of sent messages
        if ($message->status !== 'sent') {
            return redirect()->route('admin.messages.show', $message)
                ->with('error', 'Only sent messages can be resent.');
        }

        DB::beginTransaction();
        
        try {
            $recipients = MessageRecipient::where('message_id', $message->id)->get();
            $successCount = 0;
            $failureCount = 0;

            foreach ($recipients as $recipient) {
                try {
                    // Reset recipient status before resending
                    $recipient->update([
                        'delivery_status' => 'pending',
                        'delivered_at' => null,
                        'read_at' => null
                    ]);

                    $this->sendMessageEmail($message, $recipient);
                    $successCount++;
                } catch (\Exception $e) {
                    $failureCount++;
                    \Log::error('Failed to resend email to ' . $recipient->email . ': ' . $e->getMessage());
                }
            }

            // Create status log
            $logMessage = "Message resent: {$successCount} successful, {$failureCount} failed";
            MessageStatusLog::createLog($message->id, 'resent', $logMessage);

            DB::commit();

            if ($successCount > 0) {
                $successMessage = $failureCount > 0 
                    ? "Message resent successfully to {$successCount} recipients. {$failureCount} failed."
                    : "Message resent successfully to all {$successCount} recipients!";
                
                return redirect()->route('admin.messages.show', $message)
                    ->with('success', $successMessage);
            } else {
                return redirect()->route('admin.messages.show', $message)
                    ->with('error', 'Failed to resend message to any recipients.');
            }

        } catch (\Exception $e) {
            DB::rollback();
            
            return redirect()->route('admin.messages.show', $message)
                ->with('error', 'Failed to resend message: ' . $e->getMessage());
        }
    }
}
