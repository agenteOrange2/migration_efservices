<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactSubmission;
use App\Models\User;
use Illuminate\Http\Request;

class ContactSubmissionController extends Controller
{
    public function index(Request $request)
    {
        $query = ContactSubmission::query()->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('company', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $submissions = $query->paginate(15)->appends($request->query());

        $counts = [
            'all' => ContactSubmission::count(),
            'new' => ContactSubmission::where('status', 'new')->count(),
            'in_progress' => ContactSubmission::where('status', 'in_progress')->count(),
            'contacted' => ContactSubmission::where('status', 'contacted')->count(),
            'closed' => ContactSubmission::where('status', 'closed')->count(),
        ];

        return view('admin.contact-submissions.index', compact('submissions', 'counts'));
    }

    public function show(ContactSubmission $contactSubmission)
    {
        $admins = User::role('superadmin')->get();
        return view('admin.contact-submissions.show', compact('contactSubmission', 'admins'));
    }

    public function update(Request $request, ContactSubmission $contactSubmission)
    {
        $validated = $request->validate([
            'status' => 'required|in:new,in_progress,contacted,closed',
            'admin_notes' => 'nullable|string|max:5000',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        if ($validated['status'] === 'contacted' && $contactSubmission->status !== 'contacted') {
            $validated['responded_at'] = now();
        }

        $contactSubmission->update($validated);

        return redirect()
            ->route('admin.contact-submissions.show', $contactSubmission->id)
            ->with('notification', [
                'type' => 'success',
                'message' => 'Contact updated',
                'details' => 'Contact submission updated successfully.',
            ]);
    }

    public function destroy(ContactSubmission $contactSubmission)
    {
        $contactSubmission->delete();

        return redirect()
            ->route('admin.contact-submissions.index')
            ->with('notification', [
                'type' => 'success',
                'message' => 'Contact deleted',
                'details' => 'Contact submission deleted successfully.',
            ]);
    }
}
