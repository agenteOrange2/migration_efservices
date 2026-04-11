<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Models\AdminMessage;
use App\Models\MessageRecipient;
use App\Models\MessageStatusLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

abstract class MessagesBaseController extends Controller
{
    abstract protected function getAuthenticatedSender(): array;

    protected function buildMessagesQuery(Request $request)
    {
        $query = AdminMessage::query()
            ->with(['sender'])
            ->withCount([
                'recipients',
                'recipients as delivered_count' => fn ($recipientQuery) => $recipientQuery->where('delivery_status', 'delivered'),
                'recipients as read_count' => fn ($recipientQuery) => $recipientQuery->whereNotNull('read_at'),
            ])
            ->orderByDesc('created_at');

        if ($request->filled('search')) {
            $query->search((string) $request->string('search'));
        }

        if ($request->filled('status')) {
            $query->byStatus((string) $request->string('status'));
        }

        if ($request->filled('priority')) {
            $query->byPriority((string) $request->string('priority'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        return $query;
    }

    protected function storeMessage(array $validated, array $sender, array $recipients): array
    {
        DB::beginTransaction();

        try {
            $message = AdminMessage::query()->create([
                'sender_type' => $sender['type'],
                'sender_id' => $sender['id'],
                'subject' => $validated['subject'],
                'message' => $validated['message'],
                'priority' => $validated['priority'],
                'status' => $validated['status'],
                'sent_at' => $validated['status'] === 'sent' ? now() : null,
            ]);

            if (! empty($recipients)) {
                foreach ($recipients as &$recipient) {
                    $recipient['message_id'] = $message->id;
                }
                unset($recipient);

                MessageRecipient::query()->insert($recipients);
            }

            if ($validated['status'] === 'sent') {
                $messageRecipients = MessageRecipient::query()
                    ->where('message_id', $message->id)
                    ->get();

                foreach ($messageRecipients as $recipient) {
                    $this->sendMessageEmail($message, $recipient);
                }

                MessageStatusLog::createLog($message->id, 'sent', 'Message sent to ' . count($recipients) . ' recipients.');
            } else {
                MessageStatusLog::createLog($message->id, 'draft', 'Message saved as draft.');
            }

            DB::commit();

            return [
                'success' => true,
                'message' => $message,
                'count' => count($recipients),
            ];
        } catch (\Throwable $exception) {
            DB::rollBack();

            return [
                'success' => false,
                'error' => $exception->getMessage(),
            ];
        }
    }

    protected function sendMessageEmail(AdminMessage $message, MessageRecipient $recipient): void
    {
        try {
            Mail::send('emails.admin-message', [
                'adminMessage' => $message,
                'recipient' => $recipient,
            ], function ($mail) use ($message, $recipient) {
                $mail->to($recipient->email, $recipient->name)
                    ->subject($message->subject);
            });

            $recipient->update([
                'delivery_status' => 'delivered',
                'delivered_at' => now(),
            ]);
        } catch (\Throwable $exception) {
            $recipient->update([
                'delivery_status' => 'failed',
            ]);

            MessageStatusLog::createLog($message->id, 'failed', 'Email delivery failed: ' . $exception->getMessage());

            throw $exception;
        }
    }

    protected function getMessageStatistics(array $conditions = []): array
    {
        $query = AdminMessage::query();

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
            'sent_this_month' => (clone $query)->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])->count(),
            'by_status' => $statusDistribution,
            'by_priority' => $priorityDistribution,
        ];
    }

    protected function buildRecipientRow(string $recipientType, ?int $recipientId, string $email, string $name): array
    {
        return [
            'message_id' => 0,
            'recipient_type' => $recipientType,
            'recipient_id' => $recipientId,
            'email' => $email,
            'name' => $name,
            'delivery_status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    protected function canEditMessage(AdminMessage $message, array $sender): bool
    {
        if ($message->status !== 'draft') {
            return false;
        }

        return $message->sender_type === $sender['type']
            && (int) $message->sender_id === (int) $sender['id'];
    }
}
