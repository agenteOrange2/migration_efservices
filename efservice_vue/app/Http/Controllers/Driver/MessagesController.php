<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Shared\MessagesBaseController;
use App\Models\AdminMessage;
use App\Models\MessageRecipient;
use App\Models\MessageStatusLog;
use App\Models\UserDriverDetail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class MessagesController extends MessagesBaseController
{
    protected function getAuthenticatedSender(): array
    {
        $driver = $this->resolveDriver();

        return [
            'type' => UserDriverDetail::class,
            'id' => (int) $driver->id,
            'model' => $driver,
        ];
    }

    public function index(Request $request): Response
    {
        $driver = $this->resolveDriver();

        $filters = [
            'search' => trim((string) $request->input('search', '')),
            'read_status' => (string) $request->input('read_status', ''),
            'priority' => (string) $request->input('priority', ''),
        ];

        $query = $this->buildInboxQuery($driver, $filters);

        $messages = $query
            ->paginate(12)
            ->withQueryString();

        $messages->through(fn (MessageRecipient $recipient) => $this->transformInboxRow($recipient));

        $statsQuery = MessageRecipient::query()
            ->where('recipient_type', 'driver')
            ->where('recipient_id', $driver->id);

        return Inertia::render('driver/messages/Index', [
            'driver' => [
                'id' => $driver->id,
                'full_name' => $driver->full_name,
                'carrier_name' => $driver->carrier?->name,
            ],
            'filters' => $filters,
            'messages' => $messages,
            'stats' => [
                'total' => (clone $statsQuery)->count(),
                'unread' => (clone $statsQuery)->whereNull('read_at')->count(),
                'high_priority' => (clone $statsQuery)->whereHas('message', fn ($builder) => $builder->where('priority', 'high'))->count(),
            ],
        ]);
    }

    public function show(int $messageRecipient): Response
    {
        $driver = $this->resolveDriver();
        $recipient = $this->resolveRecipient($driver, $messageRecipient);

        $recipient->load([
            'message.sender',
            'message.statusLogs' => fn ($query) => $query->latest(),
        ]);

        $wasUnread = $recipient->read_at === null;

        if ($wasUnread) {
            $recipient->markAsRead();
        }

        $replySubject = $this->buildReplySubject($recipient->message->subject);

        $replies = AdminMessage::query()
            ->where('sender_type', UserDriverDetail::class)
            ->where('sender_id', $driver->id)
            ->where('subject', 'like', $replySubject . '%')
            ->orderBy('created_at')
            ->get()
            ->map(fn (AdminMessage $message) => [
                'id' => $message->id,
                'subject' => $message->subject,
                'message' => $message->message,
                'status' => $message->status,
                'sent_at' => $this->formatDateTime($message->sent_at ?? $message->created_at),
                'created_at' => $this->formatDateTime($message->created_at),
            ])
            ->values();

        return Inertia::render('driver/messages/Show', [
            'driver' => [
                'id' => $driver->id,
                'full_name' => $driver->full_name,
                'carrier_name' => $driver->carrier?->name,
            ],
            'message' => [
                'recipient_id' => $recipient->id,
                'message_id' => $recipient->message->id,
                'subject' => $recipient->message->subject,
                'body' => $recipient->message->message,
                'priority' => $recipient->message->priority,
                'status' => $recipient->message->status,
                'delivery_status' => $recipient->delivery_status,
                'sent_at' => $this->formatDateTime($recipient->message->sent_at),
                'delivered_at' => $this->formatDateTime($recipient->delivered_at),
                'read_at' => $this->formatDateTime($recipient->read_at),
                'was_unread' => $wasUnread,
                'sender' => [
                    'name' => $recipient->message->sender_name,
                    'email' => $recipient->message->sender_email,
                    'type' => $recipient->message->sender_type_label,
                ],
                'status_logs' => $recipient->message->statusLogs
                    ->map(fn (MessageStatusLog $log) => [
                        'id' => $log->id,
                        'status' => $log->status,
                        'notes' => $log->notes,
                        'created_at' => $this->formatDateTime($log->created_at),
                    ])
                    ->values(),
                'replies' => $replies,
                'reply_target' => $driver->carrier?->name,
                'can_reply' => (bool) $driver->carrier,
            ],
        ]);
    }

    public function reply(Request $request, int $messageRecipient): RedirectResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
        ]);

        $driver = $this->resolveDriver();
        $recipient = $this->resolveRecipient($driver, $messageRecipient);
        $carrier = $driver->carrier;

        if (! $carrier) {
            return back()->with('error', 'No carrier is associated with your account.');
        }

        $carrierUser = $carrier->users()->select('users.id', 'users.name', 'users.email')->first();
        $carrierEmail = $carrierUser?->email ?: 'no-reply@system.com';

        try {
            DB::beginTransaction();

            $reply = AdminMessage::query()->create([
                'sender_type' => UserDriverDetail::class,
                'sender_id' => $driver->id,
                'subject' => $this->buildReplySubject($recipient->message->subject),
                'message' => $validated['message'],
                'priority' => 'normal',
                'status' => 'sent',
                'sent_at' => now(),
            ]);

            MessageRecipient::query()->create([
                'message_id' => $reply->id,
                'recipient_type' => 'carrier',
                'recipient_id' => $carrier->id,
                'email' => $carrierEmail,
                'name' => $carrier->name,
                'delivery_status' => 'delivered',
                'delivered_at' => now(),
            ]);

            MessageStatusLog::createLog(
                $reply->id,
                'sent',
                'Reply sent by driver ' . $driver->full_name . ' to carrier ' . $carrier->name . '.'
            );

            DB::commit();

            return redirect()
                ->route('driver.messages.show', $recipient->id)
                ->with('success', 'Your reply has been sent to ' . $carrier->name . '.');
        } catch (\Throwable $exception) {
            DB::rollBack();

            report($exception);

            return back()->with('error', 'Failed to send reply: ' . $exception->getMessage());
        }
    }

    protected function buildInboxQuery(UserDriverDetail $driver, array $filters)
    {
        $query = MessageRecipient::query()
            ->where('recipient_type', 'driver')
            ->where('recipient_id', $driver->id)
            ->with(['message.sender'])
            ->orderByDesc('created_at');

        if ($filters['search'] !== '') {
            $search = '%' . $filters['search'] . '%';

            $query->whereHas('message', function ($builder) use ($search) {
                $builder
                    ->where('subject', 'like', $search)
                    ->orWhere('message', 'like', $search);
            });
        }

        if ($filters['read_status'] === 'unread') {
            $query->whereNull('read_at');
        } elseif ($filters['read_status'] === 'read') {
            $query->whereNotNull('read_at');
        }

        if ($filters['priority'] !== '') {
            $query->whereHas('message', fn ($builder) => $builder->where('priority', $filters['priority']));
        }

        return $query;
    }

    protected function transformInboxRow(MessageRecipient $recipient): array
    {
        $message = $recipient->message;

        return [
            'id' => $recipient->id,
            'message_id' => $message->id,
            'subject' => $message->subject,
            'preview' => \Illuminate\Support\Str::limit((string) $message->message, 140),
            'sender_name' => $message->sender_name,
            'sender_email' => $message->sender_email,
            'sender_type' => $message->sender_type_label,
            'priority' => $message->priority,
            'status' => $message->status,
            'delivery_status' => $recipient->delivery_status,
            'is_read' => $recipient->read_at !== null,
            'sent_at' => $this->formatDateTime($message->sent_at),
            'sent_at_relative' => $message->sent_at?->diffForHumans(),
            'read_at' => $this->formatDateTime($recipient->read_at),
        ];
    }

    protected function resolveDriver(): UserDriverDetail
    {
        /** @var \App\Models\User|null $user */
        $user = auth()->user();

        $driver = $user?->driverDetails ?? $user?->driverDetail;

        abort_unless($driver, 403, 'No driver profile associated with this account.');

        $driver->loadMissing(['user:id,name,email', 'carrier:id,name']);

        return $driver;
    }

    protected function resolveRecipient(UserDriverDetail $driver, int $value): MessageRecipient
    {
        $recipient = MessageRecipient::query()
            ->whereKey($value)
            ->where('recipient_type', 'driver')
            ->where('recipient_id', $driver->id)
            ->first();

        if (! $recipient) {
            $recipient = MessageRecipient::query()
                ->where('message_id', $value)
                ->where('recipient_type', 'driver')
                ->where('recipient_id', $driver->id)
                ->first();
        }

        abort_unless($recipient, 403, 'You do not have permission to view this message.');

        return $recipient;
    }

    protected function buildReplySubject(string $subject): string
    {
        return str_starts_with($subject, 'Re: ') ? $subject : 'Re: ' . $subject;
    }

    protected function formatDateTime($date): ?string
    {
        return $date?->format('n/j/Y g:i A');
    }
}
