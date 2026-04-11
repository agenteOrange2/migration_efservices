<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactSubmission;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class ContactSubmissionController extends Controller
{
    public function index(Request $request): InertiaResponse
    {
        $query = ContactSubmission::query()
            ->with('assignedUser:id,name,email')
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->string('search'));

            $query->where(function ($builder) use ($search) {
                $builder->where('full_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('company', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%");
            });
        }

        $submissions = $query
            ->paginate(15)
            ->through(fn (ContactSubmission $submission) => $this->transformSubmissionRow($submission))
            ->withQueryString();

        return Inertia::render('admin/contact-submissions/Index', [
            'submissions' => $submissions,
            'counts' => [
                'all' => ContactSubmission::query()->count(),
                'new' => ContactSubmission::query()->where('status', 'new')->count(),
                'in_progress' => ContactSubmission::query()->where('status', 'in_progress')->count(),
                'contacted' => ContactSubmission::query()->where('status', 'contacted')->count(),
                'closed' => ContactSubmission::query()->where('status', 'closed')->count(),
            ],
            'filters' => [
                'status' => (string) $request->string('status'),
                'search' => (string) $request->string('search'),
            ],
        ]);
    }

    public function show(ContactSubmission $contactSubmission): InertiaResponse
    {
        $contactSubmission->load('assignedUser:id,name,email');

        $admins = User::query()
            ->role('superadmin')
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get()
            ->map(fn (User $user) => [
                'id' => (int) $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ])
            ->values()
            ->all();

        return Inertia::render('admin/contact-submissions/Show', [
            'submission' => $this->transformSubmissionDetail($contactSubmission),
            'admins' => $admins,
        ]);
    }

    public function update(Request $request, ContactSubmission $contactSubmission): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['new', 'in_progress', 'contacted', 'closed'])],
            'admin_notes' => ['nullable', 'string', 'max:5000'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        if ($validated['status'] === 'contacted' && $contactSubmission->status !== 'contacted') {
            $validated['responded_at'] = now();
        }

        $contactSubmission->update($validated);

        return redirect()
            ->route('admin.contact-submissions.show', $contactSubmission)
            ->with('success', 'Contact submission updated successfully.');
    }

    public function destroy(ContactSubmission $contactSubmission): RedirectResponse
    {
        $contactSubmission->delete();

        return redirect()
            ->route('admin.contact-submissions.index')
            ->with('success', 'Contact submission deleted successfully.');
    }

    private function transformSubmissionRow(ContactSubmission $submission): array
    {
        return [
            'id' => (int) $submission->id,
            'full_name' => $submission->full_name,
            'company' => $submission->company,
            'email' => $submission->email,
            'phone' => $submission->phone,
            'message_preview' => str($submission->message ?? '')->limit(90)->toString(),
            'status' => $submission->status,
            'assigned_user_name' => $submission->assignedUser?->name,
            'created_at' => $submission->created_at?->format('M j, Y g:i A'),
            'responded_at' => $submission->responded_at?->format('M j, Y g:i A'),
        ];
    }

    private function transformSubmissionDetail(ContactSubmission $submission): array
    {
        $phoneDigits = preg_replace('/\D+/', '', (string) ($submission->phone ?? ''));

        return [
            'id' => (int) $submission->id,
            'full_name' => $submission->full_name,
            'company' => $submission->company,
            'email' => $submission->email,
            'phone' => $submission->phone,
            'phone_digits' => $phoneDigits,
            'message' => $submission->message,
            'status' => $submission->status,
            'admin_notes' => $submission->admin_notes,
            'assigned_to' => $submission->assigned_to ? (string) $submission->assigned_to : '',
            'assigned_user' => $submission->assignedUser ? [
                'id' => (int) $submission->assignedUser->id,
                'name' => $submission->assignedUser->name,
                'email' => $submission->assignedUser->email,
            ] : null,
            'responded_at' => $submission->responded_at?->format('M j, Y g:i A'),
            'ip_address' => $submission->ip_address,
            'created_at' => $submission->created_at?->format('M j, Y g:i A'),
            'updated_at' => $submission->updated_at?->format('M j, Y g:i A'),
        ];
    }
}
