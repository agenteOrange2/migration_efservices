<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Shared\MessagesBaseController;
use App\Models\AdminMessage;
use App\Models\Carrier;
use App\Models\MessageRecipient;
use App\Models\MessageStatusLog;
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

        return [
            'type' => 'App\\Models\\User',
            'id' => (int) $user->id,
            'model' => $user,
        ];
    }

    protected function getAvailableRecipients(): array
    {
        $drivers = UserDriverDetail::query()
            ->with(['user:id,name,email,status', 'carrier:id,name'])
            ->where('application_completed', 1)
            ->whereHas('user', fn ($query) => $query->where('status', 1))
            ->get()
            ->sortBy(fn (UserDriverDetail $driver) => strtolower((string) ($driver->user?->name ?? '')))
            ->values();

        $carriers = Carrier::query()
            ->with(['users' => fn ($query) => $query->select('users.id', 'users.email', 'users.name')])
            ->where('status', 1)
            ->orderBy('name')
            ->get();

        return [
            'drivers' => $drivers,
            'carriers' => $carriers,
        ];
    }

    public function dashboard(): InertiaResponse
    {
        $stats = $this->getMessageStatistics();

        $statusDistribution = AdminMessage::query()
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $priorityDistribution = AdminMessage::query()
            ->select('priority', DB::raw('count(*) as count'))
            ->groupBy('priority')
            ->pluck('count', 'priority')
            ->toArray();

        $senderTypeDistribution = AdminMessage::query()
            ->select('sender_type', DB::raw('count(*) as count'))
            ->groupBy('sender_type')
            ->get()
            ->mapWithKeys(function ($item) {
                $label = match ($item->sender_type) {
                    'App\\Models\\User' => 'Admin',
                    'App\\Models\\Carrier' => 'Carrier',
                    'App\\Models\\UserDriverDetail' => 'Driver',
                    default => 'Other',
                };

                return [$label => (int) $item->count];
            })
            ->toArray();

        $deliveryStats = [
            'total' => MessageRecipient::query()->count(),
            'delivered' => MessageRecipient::query()->where('delivery_status', 'delivered')->count(),
            'pending' => MessageRecipient::query()->where('delivery_status', 'pending')->count(),
            'failed' => MessageRecipient::query()->where('delivery_status', 'failed')->count(),
            'read' => MessageRecipient::query()->whereNotNull('read_at')->count(),
        ];

        $recentMessages = AdminMessage::query()
            ->with(['sender'])
            ->withCount([
                'recipients',
                'recipients as delivered_count' => fn ($query) => $query->where('delivery_status', 'delivered'),
                'recipients as read_count' => fn ($query) => $query->whereNotNull('read_at'),
            ])
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn (AdminMessage $message) => $this->transformMessageRow($message))
            ->values();

        return Inertia::render('admin/messages/Dashboard', [
            'stats' => $stats,
            'statusDistribution' => $statusDistribution,
            'priorityDistribution' => $priorityDistribution,
            'senderTypeDistribution' => $senderTypeDistribution,
            'deliveryStats' => $deliveryStats,
            'recentMessages' => $recentMessages,
        ]);
    }

    public function index(Request $request): InertiaResponse
    {
        $messages = $this->buildMessagesQuery($request)
            ->paginate(15)
            ->through(fn (AdminMessage $message) => $this->transformMessageRow($message))
            ->withQueryString();

        return Inertia::render('admin/messages/Index', [
            'messages' => $messages,
            'filters' => [
                'search' => (string) $request->string('search'),
                'status' => (string) $request->string('status'),
                'priority' => (string) $request->string('priority'),
                'date_from' => (string) $request->string('date_from'),
                'date_to' => (string) $request->string('date_to'),
            ],
            'stats' => $this->getMessageStatistics(),
        ]);
    }

    public function create(): InertiaResponse
    {
        $recipients = $this->getAvailableRecipients();

        return Inertia::render('admin/messages/Create', [
            'drivers' => $this->transformDrivers($recipients['drivers']),
            'carriers' => $this->transformCarriers($recipients['carriers']),
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
            ->route('admin.messages.show', $result['message'])
            ->with('success', $successMessage);
    }

    public function show(AdminMessage $message): InertiaResponse
    {
        $message->load([
            'sender',
            'recipients',
            'statusLogs' => fn ($query) => $query->latest(),
        ]);

        return Inertia::render('admin/messages/Show', [
            'message' => $this->transformMessageDetail($message),
        ]);
    }

    public function edit(AdminMessage $message): InertiaResponse|RedirectResponse
    {
        $sender = $this->getAuthenticatedSender();

        if (! $this->canEditMessage($message, $sender)) {
            return redirect()
                ->route('admin.messages.show', $message)
                ->with('error', 'Only your draft messages can be edited.');
        }

        $message->load(['sender', 'recipients', 'statusLogs' => fn ($query) => $query->latest()]);
        $recipients = $this->getAvailableRecipients();

        return Inertia::render('admin/messages/Edit', [
            'message' => $this->transformMessageDetail($message),
            'drivers' => $this->transformDrivers($recipients['drivers']),
            'carriers' => $this->transformCarriers($recipients['carriers']),
        ]);
    }

    public function update(Request $request, AdminMessage $message): RedirectResponse
    {
        $sender = $this->getAuthenticatedSender();

        if (! $this->canEditMessage($message, $sender)) {
            return redirect()
                ->route('admin.messages.show', $message)
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
                ->route('admin.messages.show', $message)
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
            ->route('admin.messages.index')
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
                ->route('admin.messages.edit', $duplicate)
                ->with('success', 'Message duplicated successfully.');
        } catch (\Throwable $exception) {
            DB::rollBack();

            return back()->with('error', 'Failed to duplicate message: ' . $exception->getMessage());
        }
    }

    public function resend(AdminMessage $message): RedirectResponse
    {
        if ($message->status !== 'sent') {
            return redirect()
                ->route('admin.messages.show', $message)
                ->with('error', 'Only sent messages can be resent.');
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

            if ($successCount === 0) {
                return redirect()
                    ->route('admin.messages.show', $message)
                    ->with('error', 'Failed to resend message to any recipients.');
            }

            $successMessage = $failureCount > 0
                ? "Message resent to {$successCount} recipients. {$failureCount} failed."
                : "Message resent successfully to all {$successCount} recipients.";

            return redirect()
                ->route('admin.messages.show', $message)
                ->with('success', $successMessage);
        } catch (\Throwable $exception) {
            DB::rollBack();

            return redirect()
                ->route('admin.messages.show', $message)
                ->with('error', 'Failed to resend message: ' . $exception->getMessage());
        }
    }

    private function validateCreateRequest(Request $request): array
    {
        return $request->validate([
            'recipient_type' => ['required', Rule::in(['all_drivers', 'specific_drivers', 'specific_carriers', 'custom_emails'])],
            'driver_ids' => ['nullable', 'array', 'required_if:recipient_type,specific_drivers'],
            'driver_ids.*' => ['nullable', 'integer', 'exists:user_driver_details,id'],
            'carrier_ids' => ['nullable', 'array', 'required_if:recipient_type,specific_carriers'],
            'carrier_ids.*' => ['nullable', 'integer', 'exists:carriers,id'],
            'custom_emails' => ['nullable', 'string', 'required_if:recipient_type,custom_emails'],
            'carrier_filter' => ['nullable', 'integer', 'exists:carriers,id'],
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
            'add_recipient_type' => ['nullable', Rule::in(['specific_drivers', 'specific_carriers', 'custom_emails'])],
            'add_driver_ids' => ['nullable', 'array'],
            'add_driver_ids.*' => ['nullable', 'integer', 'exists:user_driver_details,id'],
            'add_carrier_ids' => ['nullable', 'array'],
            'add_carrier_ids.*' => ['nullable', 'integer', 'exists:carriers,id'],
            'add_custom_emails' => ['nullable', 'string'],
        ]);
    }

    private function buildRecipientsForCreate(array $validated): Collection
    {
        return match ($validated['recipient_type']) {
            'all_drivers' => $this->recipientsForAllDrivers($validated['carrier_filter'] ?? null),
            'specific_drivers' => $this->recipientsForDrivers($validated['driver_ids'] ?? []),
            'specific_carriers' => $this->recipientsForCarriers($validated['carrier_ids'] ?? []),
            'custom_emails' => $this->recipientsForEmails($validated['custom_emails'] ?? ''),
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
            'specific_drivers' => $this->recipientsForDrivers($validated['add_driver_ids'] ?? []),
            'specific_carriers' => $this->recipientsForCarriers($validated['add_carrier_ids'] ?? []),
            'custom_emails' => $this->recipientsForEmails($validated['add_custom_emails'] ?? ''),
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

    private function recipientsForAllDrivers(?int $carrierId = null): Collection
    {
        $query = UserDriverDetail::query()
            ->with(['user:id,name,email,status', 'carrier:id,name'])
            ->where('application_completed', 1)
            ->whereHas('user', fn ($userQuery) => $userQuery->where('status', 1));

        if ($carrierId) {
            $query->where('carrier_id', $carrierId);
        }

        return $query->get()->map(function (UserDriverDetail $driver) {
            return $this->buildRecipientRow(
                'driver',
                (int) $driver->id,
                (string) $driver->user->email,
                (string) $driver->user->name,
            );
        })->filter(fn (array $recipient) => ! empty($recipient['email']))
            ->unique(fn (array $recipient) => strtolower($recipient['email']))
            ->values();
    }

    private function recipientsForDrivers(array $driverIds): Collection
    {
        if (empty($driverIds)) {
            return collect();
        }

        return UserDriverDetail::query()
            ->with(['user:id,name,email', 'carrier:id,name'])
            ->whereIn('id', $driverIds)
            ->get()
            ->map(function (UserDriverDetail $driver) {
                return $this->buildRecipientRow(
                    'driver',
                    (int) $driver->id,
                    (string) ($driver->user?->email ?? ''),
                    (string) ($driver->user?->name ?? 'Driver'),
                );
            })
            ->filter(fn (array $recipient) => ! empty($recipient['email']))
            ->unique(fn (array $recipient) => strtolower($recipient['email']))
            ->values();
    }

    private function recipientsForCarriers(array $carrierIds): Collection
    {
        if (empty($carrierIds)) {
            return collect();
        }

        return Carrier::query()
            ->with(['users' => fn ($query) => $query->select('users.id', 'users.email', 'users.name')])
            ->whereIn('id', $carrierIds)
            ->get()
            ->map(function (Carrier $carrier) {
                $user = $carrier->users->first();

                if (! $user?->email) {
                    return null;
                }

                return $this->buildRecipientRow(
                    'carrier',
                    (int) $carrier->id,
                    (string) $user->email,
                    (string) ($carrier->name ?: $user->name),
                );
            })
            ->filter()
            ->unique(fn (array $recipient) => strtolower($recipient['email']))
            ->values();
    }

    private function recipientsForEmails(string $rawEmails): Collection
    {
        $emails = preg_split('/[\s,;]+/', trim($rawEmails)) ?: [];

        return collect($emails)
            ->filter()
            ->map(fn (string $email) => trim($email))
            ->filter(fn (string $email) => filter_var($email, FILTER_VALIDATE_EMAIL))
            ->unique(fn (string $email) => strtolower($email))
            ->map(fn (string $email) => $this->buildRecipientRow('email', null, $email, $email))
            ->values();
    }

    private function transformDrivers(Collection $drivers): array
    {
        return $drivers->map(fn (UserDriverDetail $driver) => [
            'id' => (int) $driver->id,
            'name' => (string) ($driver->user?->name ?? 'Unknown'),
            'email' => (string) ($driver->user?->email ?? ''),
            'carrier_name' => $driver->carrier?->name,
        ])->values()->all();
    }

    private function transformCarriers(Collection $carriers): array
    {
        return $carriers->map(function (Carrier $carrier) {
            $user = $carrier->users->first();

            return [
                'id' => (int) $carrier->id,
                'name' => (string) $carrier->name,
                'email' => $user?->email,
                'contact_name' => $user?->name,
            ];
        })->values()->all();
    }

    private function transformMessageRow(AdminMessage $message): array
    {
        return [
            'id' => (int) $message->id,
            'subject' => $message->subject,
            'sender_name' => $message->sender_name,
            'sender_email' => $message->sender_email,
            'sender_type' => $message->sender_type_label,
            'recipients_count' => (int) ($message->recipients_count ?? 0),
            'delivered_count' => (int) ($message->delivered_count ?? 0),
            'read_count' => (int) ($message->read_count ?? 0),
            'priority' => $message->priority,
            'status' => $message->status,
            'sent_at' => $message->sent_at?->format('M j, Y g:i A'),
            'created_at' => $message->created_at?->format('M j, Y g:i A'),
            'can_edit' => $message->status === 'draft' && (int) $message->sender_id === (int) Auth::id(),
            'can_delete' => $message->status === 'draft' && (int) $message->sender_id === (int) Auth::id(),
            'can_resend' => $message->status === 'sent',
        ];
    }

    private function transformMessageDetail(AdminMessage $message): array
    {
        $message->loadMissing(['sender', 'recipients', 'statusLogs']);

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
            'can_edit' => $message->status === 'draft' && (int) $message->sender_id === (int) Auth::id(),
            'can_delete' => $message->status === 'draft' && (int) $message->sender_id === (int) Auth::id(),
            'can_resend' => $message->status === 'sent',
        ];
    }
}
