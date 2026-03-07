<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Models\AdminMessage;
use App\Models\MessageRecipient;
use App\Models\MessageStatusLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MessagesController extends Controller
{
    /**
     * Display a listing of messages received by the driver
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $driver = $user->driverDetails;

        if (!$driver) {
            abort(403, 'Driver details not found');
        }
        
        $query = MessageRecipient::where('recipient_type', 'driver')
            ->where('recipient_id', $driver->id)
            ->with(['message.sender', 'message.statusLogs']);

        // Search functionality
        if ($request->filled('search')) {
            $query->whereHas('message', function($q) use ($request) {
                $q->where('subject', 'like', "%{$request->search}%")
                  ->orWhere('message', 'like', "%{$request->search}%");
            });
        }

        // Filter by read/unread
        if ($request->filled('read_status')) {
            if ($request->read_status === 'unread') {
                $query->whereNull('read_at');
            } elseif ($request->read_status === 'read') {
                $query->whereNotNull('read_at');
            }
        }

        // Filter by priority
        if ($request->filled('priority')) {
            $query->whereHas('message', function($q) use ($request) {
                $q->where('priority', $request->priority);
            });
        }

        $messages = $query->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        // Get statistics
        $stats = [
            'total' => MessageRecipient::where('recipient_type', 'driver')
                ->where('recipient_id', $driver->id)
                ->count(),
            'unread' => MessageRecipient::where('recipient_type', 'driver')
                ->where('recipient_id', $driver->id)
                ->whereNull('read_at')
                ->count(),
            'high_priority' => MessageRecipient::where('recipient_type', 'driver')
                ->where('recipient_id', $driver->id)
                ->whereHas('message', function($q) {
                    $q->where('priority', 'high');
                })
                ->count(),
        ];

        return view('driver.messages.index', compact('messages', 'stats'));
    }

    /**
     * Display the specified message
     */
    public function show($id)
    {
        $user = Auth::user();
        $driver = $user->driverDetails;

        if (!$driver) {
            abort(403, 'Driver details not found');
        }
    
        
        // Try to find by MessageRecipient ID first
        $recipient = MessageRecipient::find($id);
                
        // If not found or doesn't belong to this driver, try to find by message_id
        // Use == for type coercion (DB may return strings)
        if (!$recipient || $recipient->recipient_type !== 'driver' || $recipient->recipient_id != $driver->id) {
            // Try to find by message_id
            $recipient = MessageRecipient::where('message_id', $id)
                ->where('recipient_type', 'driver')
                ->where('recipient_id', $driver->id)
                ->first();            
        }
        
        // If still not found, abort
        if (!$recipient) {
            abort(403, 'You do not have permission to view this message.');
        }

        // Load relationships
        $recipient->load(['message.sender', 'message.statusLogs' => function($query) {
            $query->orderBy('created_at', 'desc');
        }]);

        // Mark as read if not already read
        if (!$recipient->read_at) {
            $recipient->markAsRead();
        }

        // Get conversation replies (messages sent by this driver in reply to this message)
        $replies = AdminMessage::where('sender_type', 'App\\Models\\UserDriverDetail')
            ->where('sender_id', $driver->id)
            ->where('subject', 'like', 'Re: ' . $recipient->message->subject . '%')
            ->orderBy('created_at', 'asc')
            ->get();

        return view('driver.messages.show', compact('recipient', 'replies'));
    }

    /**
     * Reply to a message - sends to the driver's carrier
     */
    public function reply(Request $request, $id)
    {
        $user = Auth::user();
        $driver = $user->driverDetails;

        if (!$driver) {
            abort(403, 'Driver details not found');
        }

        // Validate the reply
        $validated = $request->validate([
            'message' => 'required|string|max:2000',
        ]);

        // Find the original message recipient record
        $recipient = MessageRecipient::find($id);
        
        if (!$recipient || $recipient->recipient_type !== 'driver' || $recipient->recipient_id != $driver->id) {
            $recipient = MessageRecipient::where('message_id', $id)
                ->where('recipient_type', 'driver')
                ->where('recipient_id', $driver->id)
                ->first();
        }

        if (!$recipient) {
            abort(403, 'You do not have permission to reply to this message.');
        }

        // Get the driver's carrier
        $carrier = $driver->carrier;
        
        if (!$carrier) {
            return back()->with('error', 'No carrier associated with your account.');
        }

        // Load the original message
        $originalMessage = $recipient->message;

        try {
            DB::beginTransaction();

            // Create the reply message
            $replyMessage = AdminMessage::create([
                'sender_type' => 'App\\Models\\UserDriverDetail',
                'sender_id' => $driver->id,
                'subject' => 'Re: ' . $originalMessage->subject,
                'message' => $validated['message'],
                'priority' => 'normal',
                'status' => 'sent',
                'sent_at' => now(),
            ]);

            // Get carrier email - try carrier's primary user or first assigned user
            $carrierEmail = $carrier->email ?? null;
            if (!$carrierEmail) {
                $carrierUser = $carrier->users()->first();
                $carrierEmail = $carrierUser ? $carrierUser->email : 'no-reply@system.com';
            }

            // Create recipient record for the carrier
            MessageRecipient::create([
                'message_id' => $replyMessage->id,
                'recipient_type' => 'carrier',
                'recipient_id' => $carrier->id,
                'email' => $carrierEmail,
                'name' => $carrier->name,
                'delivery_status' => 'delivered',
                'delivered_at' => now(),
            ]);

            // Log the status
            MessageStatusLog::create([
                'message_id' => $replyMessage->id,
                'status' => 'sent',
                'notes' => 'Reply sent by driver ' . $user->name . ' to carrier ' . $carrier->name,
            ]);

            DB::commit();

            return back()->with('success', 'Your reply has been sent to ' . $carrier->name . '.');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Driver message reply failed', [
                'driver_id' => $driver->id,
                'carrier_id' => $carrier->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->with('error', 'Failed to send reply: ' . $e->getMessage());
        }
    }
}

