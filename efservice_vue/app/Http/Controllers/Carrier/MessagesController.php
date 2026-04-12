<?php

namespace App\Http\Controllers\Carrier;

use App\Http\Controllers\Shared\MessagesBaseController;
use App\Models\AdminMessage;
use App\Models\MessageRecipient;
use App\Models\MessageStatusLog;
use App\Models\User;
use App\Models\UserDriverDetail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class MessagesController extends MessagesBaseController
{
    protected function getAuthenticatedSender(): array
    {
        $user = Auth::user();
        $carrier = $user?->carrierDetails?->carrier;

        abort_if(! $carrier, 403, 'Carrier details not found.');

        return [
            'type' => 'App\\Models\\Carrier',
            'id' => (int) $carrier->id,
            'model' => $carrier,
        ];
    }

    protected function getAvailableRecipients(): array
    {
        $sender = $this->getAuthenticatedSender();

        $drivers = UserDriverDetail::query()
            ->with(['user:id,name,email,status'])
            ->where('carrier_id', $sender['id'])
            ->where('application_completed', 1)
            ->whereHas('user', fn ($query) => $query->where('status', 1))
            ->get()
            ->sortBy(fn (UserDriverDetail $driver) => strtolower((string) ($driver->user?->name ?? '')))
            ->values();

        return [
            'drivers' => $drivers,
            'admin_contact' => User::role('superadmin')->first(['id', 'name', 'email']),
        ];
    }

    public function dashboard(): InertiaResponse
    {
        $sender = $this->getAuthenticatedSender();
        $accessibleQuery = $this->buildAccessibleMessagesQuery();
        $sentQuery = $this->buildSentMessagesQuery();

        $accessibleTotal = (clone $accessibleQuery)->count();
        $sentTotal = (clone $sentQuery)->count();
        $receivedTotal = max($accessibleTotal - $sentTotal, 0);

        $statusDistribution = (clone $accessibleQuery)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $priorityDistribution = (clone $accessibleQuery)
            ->select('priority', DB::raw('count(*) as count'))
            ->groupBy('priority')
            ->pluck('count', 'priority')
            ->toArray();

        $deliveryRecipientQuery = MessageRecipient::query()
            ->whereIn('message_id', (clone $sentQuery)->select('id'));

        $deliveryStats = [
            'total' => (clone $deliveryRecipientQuery)->count(),
            'delivered' => (clone $deliveryRecipientQuery)->where('delivery_status', 'delivered')->count(),
            'pending' => (clone $deliveryRecipientQuery)->where('delivery_status', 'pending')->count(),
            'failed' => (clone $deliveryRecipientQuery)->where('delivery_status', 'failed')->count(),
            'read' => (clone $deliveryRecipientQuery)->whereNotNull('read_at')->count(),
        ];

        $recentMessages = (clone $accessibleQuery)
            ->limit(10)
            ->get()
            ->map(fn (AdminMessage $message) => $this->transformMessageRow($message, $sender))
            ->values();

        return Inertia::render('carrier/messages/Dashboard', [
            'stats' => [
                'total' => $accessibleTotal,
                'sent' => $sentTotal,
                'draft' => $statusDistribution['draft'] ?? 0,
                'failed' => $statusDistribution['failed'] ?? 0,
                'received' => $receivedTotal,
                'sent_today' => (clone $sentQuery)->whereDate('created_at', today())->count(),
                'sent_this_week' => (clone $sentQuery)->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
                'sent_this_month' => (clone $sentQuery)->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])->count(),
            ],
            'statusDistribution' => $statusDistribution,
            'priorityDistribution' => $priorityDistribution,
            'directionDistribution' => [
                'sent' => $sentTotal,
                'received' => $receivedTotal,
            ],
            'deliveryStats' => $deliveryStats,
            'recentMessages' => $recentMessages,
        ]);
    }

    public function index(Request $request): InertiaResponse
    {
        $sender = $this->getAuthenticatedSender();
        $messagesQuery = $this->buildAccessibleMessagesQuery($request);
        $overallAccessibleQuery = $this->buildAccessibleMessagesQuery();
        $overallSentQuery = $this->buildSentMessagesQuery();
        $accessibleTotal = (clone $overallAccessibleQuery)->count();
        $sentTotal = (clone $overallSentQuery)->count();

        $messages = $messagesQuery
            ->paginate(15)
            ->through(fn (AdminMessage $message) => $this->transformMessageRow($message, $sender))
            ->withQueryString();

        return Inertia::render('carrier/messages/Index', [
            'messages' => $messages,
            'filters' => [
                'search' => (string) $request->string('search'),
                'status' => (string) $request->string('status'),
                'priority' => (string) $request->string('priority'),
                'date_from' => (string) $request->string('date_from'),
                'date_to' => (string) $request->string('date_to'),
            ],
            'stats' => [
                'total' => $accessibleTotal,
                'sent' => $sentTotal,
                'draft' => (clone $overallSentQuery)->where('status', 'draft')->count(),
                'failed' => (clone $overallSentQuery)->where('status', 'failed')->count(),
                'received' => max($accessibleTotal - $sentTotal, 0),
                'sent_today' => (clone $overallSentQuery)->whereDate('created_at', today())->count(),
            ],
        ]);
    }

    public function create(): InertiaResponse
    {
        $recipients = $this->getAvailableRecipients();

        return Inertia::render('carrier/messages/Create', [
            'drivers' => $this->transformDrivers($recipients['drivers']),
            'adminContact' => $this->transformAdminContact($recipients['admin_contact']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateCreateRequest($request);
        $sender = $this->getAuthenticatedSender();
        $recipients = $this->buildRecipientsForCreate($validated);

        if ($recipients->isEmpty()) {
            return back()->withInput()->with('error', 'Please select at least one valid recipient.');
        }

        $result = $this->storeMessage($validated, $sender, $recipients->values()->all());

        if (! $result['success']) {
            return back()->withInput()->with('error', 'Failed to process message: ' . $result['error']);
        }

        $successMessage = $validated['status'] === 'sent'
            ? 'Message sent successfully to ' . $result['count'] . ' recipients.'
            : 'Message saved as draft with ' . $result['count'] . ' recipients.';

        return redirect()
            ->route('carrier.messages.show', $result['message'])
            ->with('success', $successMessage);
    }

    public function show(AdminMessage $message): InertiaResponse
    {
        $sender = $this->getAuthenticatedSender();
        abort_unless($this->canAccessMessage($message, $sender), 403, 'You do not have permission to view this message.');

        $message->load([
            'sender',
            'recipients',
            'statusLogs' => fn ($query) => $query->latest(),
        ]);

        return Inertia::render('carrier/messages/Show', [
            'message' => $this->transformMessageDetail($message, $sender),
        ]);
    }

    public function edit(AdminMessage $message): InertiaResponse|RedirectResponse
    {
        $sender = $this->getAuthenticatedSender();

        if (! $this->canEditMessage($message, $sender)) {
            return redirect()
                ->route('carrier.messages.show', $message)
                ->with('error', 'Only your draft messages can be edited.');
        }

        $message->load(['sender', 'recipients', 'statusLogs' => fn ($query) => $query->latest()]);
        $recipients = $this->getAvailableRecipients();

        return Inertia::render('carrier/messages/Edit', [
            'message' => $this->transformMessageDetail($message, $sender),
            'drivers' => $this->transformDrivers($recipients['drivers']),
            'adminContact' => $this->transformAdminContact($recipients['admin_contact']),
        ]);
    }

    public function update(Request $request, AdminMessage $message): RedirectResponse
    {
        $sender = $this->getAuthenticatedSender();

        if (! $this->canEditMessage($message, $sender)) {
            return redirect()
                ->route('carrier.messages.show', $message)
                ->with('error', 'Only your draft messages can be updated.');
        }

        $validated = $this->validateUpdateRequest($request);

        DB::beginTransaction();

        try {
            $message->update([
                'subject' => $validated['subject'],
                'message' => $validated['message'],
                'priority' => $validated['priority'],
            ]);

            $addedRecipients = $this->appendRecipientsToDraft($message, $validated);
            $status = $validated['status'] ?? 'draft';
            $sentNow = false;

            if ($status === 'sent' && $message->status === 'draft') {
                $message->update([
                    'status' => 'sent',
                    'sent_at' => now(),
                ]);

                $pendingRecipients = $message->recipients()->where('delivery_status', 'pending')->get();

                foreach ($pendingRecipients as $recipient) {
                    $this->sendMessageEmail($message, $recipient);
                }

                MessageStatusLog::createLog(
                    $message->id,
                    'sent',
                    'Message sent from draft with ' . $message->recipients()->count() . ' recipients.'
                );

                $sentNow = true;
            } else {
                MessageStatusLog::createLog(
                    $message->id,
                    'updated',
                    $addedRecipients > 0
                        ? 'Message updated and ' . $addedRecipients . ' recipients added.'
                        : 'Message details updated.'
                );
            }

            DB::commit();

            return redirect()
                ->route('carrier.messages.show', $message)
                ->with('success', $sentNow ? 'Draft updated and sent successfully.' : 'Message updated successfully.');
        } catch (\Throwable $exception) {
            DB::rollBack();

            return back()
                ->withInput()
                ->with('error', 'Failed to update message: ' . $exception->getMessage());
        }
    }

    public function destroy(AdminMessage $message): RedirectResponse
    {
        $sender = $this->getAuthenticatedSender();

        if (! $this->canEditMessage($message, $sender)) {
            return back()->with('error', 'Only your draft messages can be deleted.');
        }

        $message->delete();

        return redirect()
            ->route('carrier.messages.index')
            ->with('success', 'Message deleted successfully.');
    }

    public function removeRecipient(AdminMessage $message, MessageRecipient $recipient): RedirectResponse
    {
        $sender = $this->getAuthenticatedSender();

        if (! $this->canEditMessage($message, $sender)) {
            return back()->with('error', 'Only recipients from your draft messages can be removed.');
        }

        if ((int) $recipient->message_id !== (int) $message->id) {
            return back()->with('error', 'Recipient does not belong to this message.');
        }

        $recipient->delete();
        MessageStatusLog::createLog($message->id, 'updated', 'A recipient was removed from the draft.');

        return back()->with('success', 'Recipient removed successfully.');
    }

    public function duplicate(AdminMessage $message): RedirectResponse
    {
        $sender = $this->getAuthenticatedSender();

        if (! $this->canAccessMessage($message, $sender)) {
            return back()->with('error', 'You do not have permission to duplicate this message.');
        }

        DB::beginTransaction();

        try {
            $duplicate = AdminMessage::query()->create([
                'sender_type' => $sender['type'],
                'sender_id' => $sender['id'],
                'subject' => 'Copy of ' . $message->subject,
                'message' => $message->message,
                'priority' => $message->priority,
                'status' => 'draft',
                'sent_at' => null,
            ]);

            $recipientRows = $message->recipients()
                ->get()
                ->map(function (MessageRecipient $recipient) use ($duplicate) {
                    return [
                        'message_id' => $duplicate->id,
                        'recipient_type' => $recipient->recipient_type,
                        'recipient_id' => $recipient->recipient_id,
                        'email' => $recipient->email,
                        'name' => $recipient->name,
                        'delivery_status' => 'pending',
                        'delivered_at' => null,
                        'read_at' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                })
                ->all();

            if (! empty($recipientRows)) {
                MessageRecipient::query()->insert($recipientRows);
            }

            MessageStatusLog::createLog($duplicate->id, 'draft', 'Message duplicated from #' . $message->id . '.');

            DB::commit();

            return redirect()
                ->route('carrier.messages.edit', $duplicate)
                ->with('success', 'Message duplicated successfully.');
        } catch (\Throwable $exception) {
            DB::rollBack();

            return back()->with('error', 'Failed to duplicate message: ' . $exception->getMessage());
        }
    }

    public function resend(AdminMessage $message): RedirectResponse
    {
        $sender = $this->getAuthenticatedSender();

        if (! $this->isSender($message, $sender) || $message->status !== 'sent') {
            return redirect()
                ->route('carrier.messages.show', $message)
                ->with('error', 'Only your sent messages can be resent.');
        }

        DB::beginTransaction();

        try {
            $recipients = MessageRecipient::query()
                ->where('message_id', $message->id)
                ->get();

            $successCount = 0;
            $failureCount = 0;

            foreach ($recipients as $recipient) {
                try {
                    $recipient->update([
                        'delivery_status' => 'pending',
                        'delivered_at' => null,
                        'read_at' => null,
                    ]);

                    $this->sendMessageEmail($message, $recipient);
                    $successCount++;
                } catch (\Throwable $exception) {
                    $failureCount++;
                }
            }

            MessageStatusLog::createLog(
                $message->id,
                'resent',
                "Message resent: {$successCount} successful, {$failureCount} failed."
            );

            DB::commit();

            return back()->with('success', "Message resent. {$successCount} deliveries succeeded.");
        } catch (\Throwable $exception) {
            DB::rollBack();

            return back()->with('error', 'Failed to resend message: ' . $exception->getMessage());
        }
    }

    private function buildAccessibleMessagesQuery(?Request $request = null)
    {
        $sender = $this->getAuthenticatedSender();
        $query = $this->buildMessagesQuery($request ?? request());

        return $query->where(function ($messageQuery) use ($sender) {
            $messageQuery->forSender($sender['type'], $sender['id'])
                ->orWhereHas('recipients', function ($recipientQuery) use ($sender) {
                    $recipientQuery->where('recipient_type', 'carrier')
                        ->where('recipient_id', $sender['id']);
                });
        });
    }

    private function buildSentMessagesQuery(?Request $request = null)
    {
        $sender = $this->getAuthenticatedSender();

        return $this->buildMessagesQuery($request ?? request())
            ->forSender($sender['type'], $sender['id']);
    }

    private function validateCreateRequest(Request $request): array
    {
        return $request->validate([
            'recipient_type' => ['required', Rule::in(['all_my_drivers', 'specific_drivers', 'admin'])],
            'driver_ids' => ['nullable', 'array', 'required_if:recipient_type,specific_drivers'],
            'driver_ids.*' => ['nullable', 'integer', 'exists:user_driver_details,id'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:2000'],
            'priority' => ['required', Rule::in(['low', 'normal', 'high'])],
            'status' => ['required', Rule::in(['draft', 'sent'])],
        ]);
    }

    private function validateUpdateRequest(Request $request): array
    {
        return $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:2000'],
            'priority' => ['required', Rule::in(['low', 'normal', 'high'])],
            'status' => ['nullable', Rule::in(['draft', 'sent'])],
            'add_recipient_type' => ['nullable', Rule::in(['all_my_drivers', 'specific_drivers', 'admin'])],
            'add_driver_ids' => ['nullable', 'array'],
            'add_driver_ids.*' => ['nullable', 'integer', 'exists:user_driver_details,id'],
        ]);
    }

    private function buildRecipientsForCreate(array $validated): Collection
    {
        return match ($validated['recipient_type']) {
            'all_my_drivers' => $this->recipientsForAllMyDrivers(),
            'specific_drivers' => $this->recipientsForDrivers($validated['driver_ids'] ?? []),
            'admin' => $this->recipientsForAdmin(),
            default => collect(),
        };
    }

    private function appendRecipientsToDraft(AdminMessage $message, array $validated): int
    {
        $type = $validated['add_recipient_type'] ?? null;

        if (! $type) {
            return 0;
        }

        $newRecipients = match ($type) {
            'all_my_drivers' => $this->recipientsForAllMyDrivers(),
            'specific_drivers' => $this->recipientsForDrivers($validated['add_driver_ids'] ?? []),
            'admin' => $this->recipientsForAdmin(),
            default => collect(),
        };

        if ($newRecipients->isEmpty()) {
            return 0;
        }

        $existingEmails = $message->recipients()
            ->pluck('email')
            ->map(fn ($email) => strtolower((string) $email))
            ->all();

        $insertRows = $newRecipients
            ->reject(fn (array $recipient) => in_array(strtolower($recipient['email']), $existingEmails, true))
            ->map(function (array $recipient) use ($message) {
                $recipient['message_id'] = $message->id;

                return $recipient;
            })
            ->values();

        if ($insertRows->isEmpty()) {
            return 0;
        }

        MessageRecipient::query()->insert($insertRows->all());

        return $insertRows->count();
    }

    private function recipientsForAllMyDrivers(): Collection
    {
        $sender = $this->getAuthenticatedSender();

        return UserDriverDetail::query()
            ->with(['user:id,name,email,status'])
            ->where('carrier_id', $sender['id'])
            ->where('application_completed', 1)
            ->whereHas('user', fn ($query) => $query->where('status', 1))
            ->get()
            ->map(function (UserDriverDetail $driver) {
                return $this->buildRecipientRow(
                    'driver',
                    (int) $driver->id,
                    (string) ($driver->user?->email ?? ''),
                    (string) ($driver->user?->name ?? 'Driver')
                );
            })
            ->filter(fn (array $recipient) => ! empty($recipient['email']))
            ->unique(fn (array $recipient) => strtolower($recipient['email']))
            ->values();
    }

    private function recipientsForDrivers(array $driverIds): Collection
    {
        if (empty($driverIds)) {
            return collect();
        }

        $sender = $this->getAuthenticatedSender();

        return UserDriverDetail::query()
            ->with(['user:id,name,email'])
            ->where('carrier_id', $sender['id'])
            ->whereIn('id', $driverIds)
            ->get()
            ->map(function (UserDriverDetail $driver) {
                return $this->buildRecipientRow(
                    'driver',
                    (int) $driver->id,
                    (string) ($driver->user?->email ?? ''),
                    (string) ($driver->user?->name ?? 'Driver')
                );
            })
            ->filter(fn (array $recipient) => ! empty($recipient['email']))
            ->unique(fn (array $recipient) => strtolower($recipient['email']))
            ->values();
    }

    private function recipientsForAdmin(): Collection
    {
        $admin = $this->getAvailableRecipients()['admin_contact'];

        if (! $admin?->email) {
            return collect();
        }

        return collect([
            $this->buildRecipientRow('user', (int) $admin->id, (string) $admin->email, (string) $admin->name),
        ]);
    }

    private function canAccessMessage(AdminMessage $message, array $sender): bool
    {
        return $this->isSender($message, $sender)
            || $message->recipients()
                ->where('recipient_type', 'carrier')
                ->where('recipient_id', $sender['id'])
                ->exists();
    }

    private function isSender(AdminMessage $message, array $sender): bool
    {
        return $message->sender_type === $sender['type']
            && (int) $message->sender_id === (int) $sender['id'];
    }

    private function transformDrivers(Collection $drivers): array
    {
        return $drivers->map(fn (UserDriverDetail $driver) => [
            'id' => (int) $driver->id,
            'name' => (string) ($driver->user?->name ?? 'Unknown'),
            'email' => (string) ($driver->user?->email ?? ''),
        ])->values()->all();
    }

    private function transformAdminContact(?User $admin): ?array
    {
        if (! $admin) {
            return null;
        }

        return [
            'id' => (int) $admin->id,
            'name' => (string) $admin->name,
            'email' => (string) $admin->email,
        ];
    }

    private function transformMessageRow(AdminMessage $message, array $sender): array
    {
        $isSender = $this->isSender($message, $sender);

        return [
            'id' => (int) $message->id,
            'subject' => $message->subject,
            'sender_name' => $message->sender_name,
            'sender_email' => $message->sender_email,
            'sender_type' => $message->sender_type_label,
            'direction' => $isSender ? 'sent' : 'received',
            'recipients_count' => (int) ($message->recipients_count ?? 0),
            'delivered_count' => (int) ($message->delivered_count ?? 0),
            'read_count' => (int) ($message->read_count ?? 0),
            'priority' => $message->priority,
            'status' => $message->status,
            'sent_at' => $message->sent_at?->format('M j, Y g:i A'),
            'created_at' => $message->created_at?->format('M j, Y g:i A'),
            'can_edit' => $message->status === 'draft' && $isSender,
            'can_delete' => $message->status === 'draft' && $isSender,
            'can_resend' => $message->status === 'sent' && $isSender,
        ];
    }

    private function transformMessageDetail(AdminMessage $message, array $sender): array
    {
        $message->loadMissing(['sender', 'recipients', 'statusLogs']);
        $isSender = $this->isSender($message, $sender);

        $recipients = $message->recipients
            ->sortBy('name')
            ->values()
            ->map(fn (MessageRecipient $recipient) => [
                'id' => (int) $recipient->id,
                'recipient_type' => $recipient->recipient_type,
                'name' => $recipient->name,
                'email' => $recipient->email,
                'delivery_status' => $recipient->delivery_status,
                'delivered_at' => $recipient->delivered_at?->format('M j, Y g:i A'),
                'read_at' => $recipient->read_at?->format('M j, Y g:i A'),
            ])
            ->all();

        $logs = $message->statusLogs
            ->sortByDesc('created_at')
            ->values()
            ->map(fn (MessageStatusLog $log) => [
                'id' => (int) $log->id,
                'status' => $log->status,
                'notes' => $log->notes,
                'created_at' => $log->created_at?->format('M j, Y g:i A'),
            ])
            ->all();

        return [
            'id' => (int) $message->id,
            'subject' => $message->subject,
            'message' => $message->message,
            'priority' => $message->priority,
            'status' => $message->status,
            'direction' => $isSender ? 'sent' : 'received',
            'sent_at' => $message->sent_at?->format('M j, Y g:i A'),
            'created_at' => $message->created_at?->format('M j, Y g:i A'),
            'sender' => [
                'name' => $message->sender_name,
                'email' => $message->sender_email,
                'type' => $message->sender_type_label,
            ],
            'recipients' => $recipients,
            'status_logs' => $logs,
            'stats' => [
                'total' => count($recipients),
                'delivered' => collect($recipients)->where('delivery_status', 'delivered')->count(),
                'pending' => collect($recipients)->where('delivery_status', 'pending')->count(),
                'failed' => collect($recipients)->where('delivery_status', 'failed')->count(),
                'read' => collect($recipients)->filter(fn ($recipient) => ! empty($recipient['read_at']))->count(),
            ],
            'can_edit' => $message->status === 'draft' && $isSender,
            'can_delete' => $message->status === 'draft' && $isSender,
            'can_resend' => $message->status === 'sent' && $isSender,
        ];
    }
}
