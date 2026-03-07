<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Models\AdminMessage;
use App\Models\MessageRecipient;
use App\Models\MessageStatusLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

abstract class MessagesBaseController extends Controller
{
    /**
     * Get the authenticated sender information
     * Returns: ['type' => 'App\Models\User', 'id' => 1, 'model' => $userModel]
     */
    abstract protected function getAuthenticatedSender(): array;

    /**
     * Get available recipients for the authenticated sender
     * Returns: array of recipients grouped by type
     */
    abstract protected function getAvailableRecipients(): array;

    /**
     * Get the view path prefix for this controller
     * Example: 'admin.messages', 'carrier.messages', etc.
     */
    abstract protected function getViewPath(string $view): string;

    /**
     * Build base query for messages with common filters
     */
    protected function buildMessagesQuery(Request $request)
    {
        $query = AdminMessage::with(['sender', 'recipients'])
            ->orderBy('created_at', 'desc');

        // Search functionality
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->byStatus($request->status);
        }

        // Filter by priority
        if ($request->filled('priority')) {
            $query->byPriority($request->priority);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        return $query;
    }

    /**
     * Store a new message
     */
    protected function storeMessage(array $validated, array $sender, array $recipients)
    {
        DB::beginTransaction();
        
        try {
            // Create the message with polymorphic sender
            $message = AdminMessage::create([
                'sender_type' => $sender['type'],
                'sender_id' => $sender['id'],
                'subject' => $validated['subject'],
                'message' => $validated['message'],
                'priority' => $validated['priority'],
                'status' => $validated['status'],
                'sent_at' => $validated['status'] === 'sent' ? now() : null
            ]);

            // Update recipients array with the actual message_id
            if (!empty($recipients)) {
                foreach ($recipients as &$recipient) {
                    $recipient['message_id'] = $message->id;
                }
                unset($recipient); // Break the reference
                
                // Insert all recipients with the correct message_id
                MessageRecipient::insert($recipients);
            }

            // Send emails if status is 'sent'
            if ($validated['status'] === 'sent') {
                $messageRecipients = MessageRecipient::where('message_id', $message->id)->get();
                
                foreach ($messageRecipients as $recipient) {
                    try {
                        $this->sendMessageEmail($message, $recipient);
                    } catch (\Exception $e) {
                        
                    }
                }

                MessageStatusLog::createLog($message->id, 'sent', 'Message sent to ' . count($recipients) . ' recipients');
            }

            DB::commit();

            return [
                'success' => true,
                'message' => $message,
                'count' => count($recipients)
            ];

        } catch (\Exception $e) {
            DB::rollback();
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Send message via email
     */
    protected function sendMessageEmail(AdminMessage $message, MessageRecipient $recipient)
    {
        try {
            $messageSubject = $message->subject;
            
            Mail::send('emails.admin-message', [
                'adminMessage' => $message,
                'recipient' => $recipient
            ], function ($mail) use ($messageSubject, $recipient) {
                $mail->to($recipient->email, $recipient->name)
                     ->subject($messageSubject);
            });

            $recipient->update([
                'delivery_status' => 'sent',
                'delivered_at' => now()
            ]);

        } catch (\Exception $e) {
            $recipient->update(['delivery_status' => 'failed']);
            MessageStatusLog::createLog($message->id, 'failed', 'Email delivery failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get statistics for messages
     */
    protected function getMessageStatistics(array $conditions = [])
    {
        $query = AdminMessage::query();

        // Apply conditions if provided
        foreach ($conditions as $field => $value) {
            if (is_array($value)) {
                $query->whereIn($field, $value);
            } else {
                $query->where($field, $value);
            }
        }

        $statusDistribution = (clone $query)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
            
        $priorityDistribution = (clone $query)
            ->select('priority', DB::raw('count(*) as count'))
            ->groupBy('priority')
            ->pluck('count', 'priority')
            ->toArray();

        return [
            'total' => $query->count(),
            'sent' => $statusDistribution['sent'] ?? 0,
            'draft' => $statusDistribution['draft'] ?? 0,
            'failed' => $statusDistribution['failed'] ?? 0,
            'delivered' => $statusDistribution['delivered'] ?? 0,
            'sent_today' => (clone $query)->whereDate('created_at', today())->count(),
            'sent_this_week' => (clone $query)->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'sent_this_month' => (clone $query)->whereMonth('created_at', now()->month)->count(),
            'by_status' => $statusDistribution,
            'by_priority' => $priorityDistribution,
        ];
    }

    /**
     * Build recipients array for insertion
     */
    protected function buildRecipientsArray($messageId, $recipientType, $recipientId, $email, $name)
    {
        return [
            'message_id' => $messageId,
            'recipient_type' => $recipientType,
            'recipient_id' => $recipientId,
            'email' => $email,
            'name' => $name,
            'delivery_status' => 'pending',
            'created_at' => now(),
            'updated_at' => now()
        ];
    }

    /**
     * Check if user can edit message
     */
    protected function canEditMessage(AdminMessage $message, array $sender): bool
    {
        // Only draft messages can be edited
        if ($message->status !== 'draft') {
            return false;
        }

        // Check if message belongs to the sender
        return $message->sender_type === $sender['type'] 
            && $message->sender_id === $sender['id'];
    }

    /**
     * Check if user can view message
     */
    protected function canViewMessage(AdminMessage $message, array $sender): bool
    {
        // Check if user is the sender (use == for type coercion, DB may return strings)
        if ($message->sender_type === $sender['type'] 
            && $message->sender_id == $sender['id']) {
            return true;
        }

        // Map sender type to recipient type
        // sender_type is like 'App\Models\Carrier', recipient_type is like 'carrier'
        $recipientType = $this->mapSenderTypeToRecipientType($sender['type']);

        // Check if user is a recipient
        return $message->recipients()
            ->where('recipient_type', $recipientType)
            ->where('recipient_id', $sender['id'])
            ->exists();
    }

    /**
     * Map sender type (full class name) to recipient type (simple string)
     */
    protected function mapSenderTypeToRecipientType(string $senderType): string
    {
        $mapping = [
            'App\\Models\\Carrier' => 'carrier',
            'App\\Models\\UserDriverDetail' => 'driver',
            'App\\Models\\User' => 'user',
        ];

        return $mapping[$senderType] ?? strtolower(class_basename($senderType));
    }
}

