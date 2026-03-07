<?php

namespace App\Http\Controllers\Carrier;

use App\Http\Controllers\Shared\MessagesBaseController;
use App\Models\AdminMessage;
use App\Models\MessageRecipient;
use App\Models\MessageStatusLog;
use App\Models\User;
use App\Models\UserDriverDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MessagesController extends MessagesBaseController
{
    /**
     * Get the authenticated sender (Carrier)
     */
    protected function getAuthenticatedSender(): array
    {
        $user = Auth::user();
        $carrierDetails = $user->carrierDetails;

        if (!$carrierDetails) {
            abort(403, 'Carrier details not found');
        }

        $carrier = $carrierDetails->carrier;

        if (!$carrier) {
            abort(403, 'Carrier not found');
        }
        
        return [
            'type' => 'App\\Models\\Carrier',
            'id' => $carrier->id,
            'model' => $carrier
        ];
    }

    /**
     * Get available recipients for carrier (their drivers and admin)
     */
    protected function getAvailableRecipients(): array
    {
        $user = Auth::user();
        $carrierDetails = $user->carrierDetails;

        if (!$carrierDetails) {
            return [
                'drivers' => collect([]),
                'admin' => User::role('superadmin')->first(),
            ];
        }

        $carrier = $carrierDetails->carrier;

        if (!$carrier) {
            return [
                'drivers' => collect([]),
                'admin' => User::role('superadmin')->first(),
            ];
        }

        return [
            'drivers' => $carrier->userDrivers()
                ->where('application_completed', 1)
                ->where('status', 1)
                ->with('user')
                ->get(),
            'admin' => User::role('superadmin')->first(), // For sending to Admin
        ];
    }

    /**
     * Get view path for carrier messages
     */
    protected function getViewPath(string $view): string
    {
        return "carrier.messages.{$view}";
    }

    /**
     * Display a listing of messages (sent by carrier or received by carrier)
     */
    public function index(Request $request)
    {
        $sender = $this->getAuthenticatedSender();
        
        $query = $this->buildMessagesQuery($request);
        
        // Filter messages: sent by this carrier OR received by this carrier
        $query->where(function($q) use ($sender) {
            $q->forSender($sender['type'], $sender['id'])
              ->orWhereHas('recipients', function($recipientQuery) use ($sender) {
                  $recipientQuery->where('recipient_type', 'carrier')
                                ->where('recipient_id', $sender['id']);
              });
        });

        $messages = $query->paginate(15)->withQueryString();

        // Get statistics for this carrier only
        $stats = $this->getMessageStatistics([
            'sender_type' => $sender['type'],
            'sender_id' => $sender['id']
        ]);

        // Add count of received messages
        $stats['received'] = \App\Models\MessageRecipient::where('recipient_type', 'carrier')
            ->where('recipient_id', $sender['id'])
            ->count();

        return view($this->getViewPath('index'), compact('messages', 'stats'));
    }

    /**
     * Show the form for creating a new message
     */
    public function create()
    {
        $recipients = $this->getAvailableRecipients();
        $drivers = $recipients['drivers'];
        $admin = $recipients['admin'];

        return view($this->getViewPath('create'), compact('drivers', 'admin'));
    }

    /**
     * Store a newly created message
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'recipient_type' => ['required', Rule::in(['specific_drivers', 'all_my_drivers', 'admin'])],
            'driver_ids' => 'nullable|array|required_if:recipient_type,specific_drivers',
            'driver_ids.*' => 'nullable|exists:user_driver_details,id',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:2000',
            'priority' => ['required', Rule::in(['low', 'normal', 'high'])],
            'status' => ['required', Rule::in(['draft', 'sent'])],
        ]);

        $sender = $this->getAuthenticatedSender();
        $recipients = [];

        // Process recipients based on type
        switch ($validated['recipient_type']) {
            case 'all_my_drivers':
                $availableRecipients = $this->getAvailableRecipients();
                $drivers = $availableRecipients['drivers'];
                
                foreach ($drivers as $driver) {
                    // Verify driver belongs to this carrier
                    if ($driver->carrier_id === $sender['id']) {
                        $recipients[] = $this->buildRecipientsArray(
                            0,
                            'driver',
                            $driver->id,
                            $driver->user->email,
                            $driver->user->name
                        );
                    }
                }
                break;

            case 'specific_drivers':
                $drivers = UserDriverDetail::with(['user', 'carrier'])
                    ->whereIn('id', $validated['driver_ids'])
                    ->where('carrier_id', $sender['id']) // Security: only their drivers
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

            case 'admin':
                $availableRecipients = $this->getAvailableRecipients();
                $admin = $availableRecipients['admin'];
                
                if ($admin) {
                    $recipients[] = $this->buildRecipientsArray(
                        0,
                        'user',
                        $admin->id,
                        $admin->email,
                        $admin->name
                    );
                }
                break;
        }

        if (empty($recipients)) {
            return back()->withInput()
                ->with('error', 'No valid recipients selected.');
        }

        $result = $this->storeMessage($validated, $sender, $recipients);

        if ($result['success']) {
            $successMessage = $validated['status'] === 'sent' 
                ? 'Message sent successfully to ' . $result['count'] . ' recipients!'
                : 'Message saved as draft with ' . $result['count'] . ' recipients!';

            return redirect()->route('carrier.messages.show', $result['message'])
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
        $sender = $this->getAuthenticatedSender();
        
        // Check if carrier can view this message
        // Use loose comparison (==) because database may return string IDs
        $isSender = $message->sender_type === $sender['type'] 
            && $message->sender_id == $sender['id']; // Use == for type coercion
        
        $isRecipient = $message->recipients()
            ->where('recipient_type', 'carrier')
            ->where('recipient_id', $sender['id'])
            ->exists();
        
        if (!$isSender && !$isRecipient) {
            abort(403, 'You do not have permission to view this message.');
        }

        $message->load(['sender', 'recipients', 'statusLogs' => function($query) {
            $query->orderBy('created_at', 'desc');
        }]);

        return view($this->getViewPath('show'), compact('message'));
    }

    /**
     * Dashboard with statistics
     */
    public function dashboard()
    {
        $sender = $this->getAuthenticatedSender();
        
        // Get statistics for this carrier only
        $stats = $this->getMessageStatistics([
            'sender_type' => $sender['type'],
            'sender_id' => $sender['id']
        ]);

        // Get received messages count
        $receivedCount = MessageRecipient::where('recipient_type', 'carrier')
            ->where('recipient_id', $sender['id'])
            ->count();

        $stats['received'] = $receivedCount;

        // Calculate delivery statistics for messages sent by this carrier
        $sentMessageIds = AdminMessage::forSender($sender['type'], $sender['id'])
            ->pluck('id');

        $deliveryStats = [
            'total' => MessageRecipient::whereIn('message_id', $sentMessageIds)->count(),
            'delivered' => MessageRecipient::whereIn('message_id', $sentMessageIds)
                ->where('delivery_status', 'delivered')->count(),
            'pending' => MessageRecipient::whereIn('message_id', $sentMessageIds)
                ->where('delivery_status', 'pending')->count(),
            'failed' => MessageRecipient::whereIn('message_id', $sentMessageIds)
                ->where('delivery_status', 'failed')->count(),
            'read' => MessageRecipient::whereIn('message_id', $sentMessageIds)
                ->whereNotNull('read_at')->count(),
        ];

        $recentMessages = AdminMessage::with(['sender', 'recipients'])
            ->where(function($q) use ($sender) {
                $q->forSender($sender['type'], $sender['id'])
                  ->orWhereHas('recipients', function($recipientQuery) use ($sender) {
                      $recipientQuery->where('recipient_type', 'carrier')
                                    ->where('recipient_id', $sender['id']);
                  });
            })
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view($this->getViewPath('dashboard'), compact(
            'stats', 
            'recentMessages', 
            'deliveryStats'
        ));
    }
}

