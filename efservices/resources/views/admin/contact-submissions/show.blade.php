@extends('../themes/' . $activeTheme)
@section('title', 'Contact Details')
@php
    $breadcrumbLinks = [
        ['label' => 'App', 'url' => route('admin.dashboard')],
        ['label' => 'Contact Submissions', 'url' => route('admin.contact-submissions.index')],
        ['label' => $contactSubmission->full_name, 'active' => true],
    ];
@endphp
@section('subcontent')
    <x-base.notificationtoast.notification-toast :notification="session('notification')" />
    <div class="grid grid-cols-12 gap-x-6 gap-y-10">
        <div class="col-span-12 lg:col-span-8">
            <div class="box box--stacked p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-lg font-bold text-slate-700">Contact Information</h2>
                    <span class="px-3 py-1.5 rounded-full text-xs font-medium {{ $contactSubmission->status_badge }}">
                        {{ ucfirst(str_replace('_', ' ', $contactSubmission->status)) }}
                    </span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="text-xs font-medium text-slate-500 uppercase tracking-wider">Full Name</label>
                        <p class="mt-1 text-sm font-medium text-slate-700">{{ $contactSubmission->full_name }}</p>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-slate-500 uppercase tracking-wider">Email</label>
                        <p class="mt-1 text-sm">
                            <a href="mailto:{{ $contactSubmission->email }}" class="text-primary hover:underline">{{ $contactSubmission->email }}</a>
                        </p>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-slate-500 uppercase tracking-wider">Phone</label>
                        <p class="mt-1 text-sm text-slate-700">
                            @if($contactSubmission->phone)
                                <a href="tel:{{ $contactSubmission->phone }}" class="text-primary hover:underline">{{ $contactSubmission->phone }}</a>
                            @else
                                <span class="text-slate-400">Not provided</span>
                            @endif
                        </p>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-slate-500 uppercase tracking-wider">Company</label>
                        <p class="mt-1 text-sm text-slate-700">{{ $contactSubmission->company ?? 'Not provided' }}</p>
                    </div>
                </div>

                @if($contactSubmission->message)
                    <div class="mt-6 pt-6 border-t border-slate-200/60">
                        <label class="text-xs font-medium text-slate-500 uppercase tracking-wider">Message</label>
                        <div class="mt-2 p-4 bg-slate-50 rounded-lg text-sm text-slate-700 leading-relaxed">
                            {{ $contactSubmission->message }}
                        </div>
                    </div>
                @endif

                <div class="mt-6 pt-6 border-t border-slate-200/60 grid grid-cols-1 md:grid-cols-3 gap-4 text-xs text-slate-500">
                    <div>
                        <span class="font-medium">Submitted:</span> {{ $contactSubmission->created_at->format('M d, Y H:i') }}
                    </div>
                    <div>
                        <span class="font-medium">IP Address:</span> {{ $contactSubmission->ip_address ?? 'N/A' }}
                    </div>
                    @if($contactSubmission->responded_at)
                        <div>
                            <span class="font-medium">Responded:</span> {{ $contactSubmission->responded_at->format('M d, Y H:i') }}
                        </div>
                    @endif
                </div>
            </div>

            {{-- Quick actions --}}
            <div class="box box--stacked p-6 mt-5">
                <h3 class="text-sm font-bold text-slate-700 mb-4">Quick Actions</h3>
                <div class="flex flex-wrap gap-3">
                    <a href="mailto:{{ $contactSubmission->email }}" class="inline-flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-lg text-sm font-medium hover:bg-primary/90 transition">
                        <x-base.lucide class="h-4 w-4" icon="Mail" />
                        Send Email
                    </a>
                    @if($contactSubmission->phone)
                        <a href="tel:{{ $contactSubmission->phone }}" class="inline-flex items-center gap-2 px-4 py-2 bg-success text-white rounded-lg text-sm font-medium hover:bg-success/90 transition">
                            <x-base.lucide class="h-4 w-4" icon="Phone" />
                            Call
                        </a>
                        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $contactSubmission->phone) }}" target="_blank" class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg text-sm font-medium hover:bg-green-700 transition">
                            <x-base.lucide class="h-4 w-4" icon="MessageCircle" />
                            WhatsApp
                        </a>
                    @endif
                </div>
            </div>
        </div>

        {{-- Right sidebar: update form --}}
        <div class="col-span-12 lg:col-span-4">
            <form action="{{ route('admin.contact-submissions.update', $contactSubmission->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="box box--stacked p-6">
                    <h3 class="text-sm font-bold text-slate-700 mb-4">Manage Submission</h3>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1">Status</label>
                            <select name="status" class="w-full rounded-lg border-slate-200 text-sm">
                                <option value="new" {{ $contactSubmission->status === 'new' ? 'selected' : '' }}>New</option>
                                <option value="in_progress" {{ $contactSubmission->status === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                                <option value="contacted" {{ $contactSubmission->status === 'contacted' ? 'selected' : '' }}>Contacted</option>
                                <option value="closed" {{ $contactSubmission->status === 'closed' ? 'selected' : '' }}>Closed</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1">Assigned To</label>
                            <select name="assigned_to" class="w-full rounded-lg border-slate-200 text-sm">
                                <option value="">Unassigned</option>
                                @foreach($admins as $admin)
                                    <option value="{{ $admin->id }}" {{ $contactSubmission->assigned_to == $admin->id ? 'selected' : '' }}>
                                        {{ $admin->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1">Admin Notes</label>
                            <textarea name="admin_notes" rows="5" class="w-full rounded-lg border-slate-200 text-sm" placeholder="Add internal notes...">{{ $contactSubmission->admin_notes }}</textarea>
                        </div>

                        <button type="submit" class="w-full bg-primary text-white py-2.5 rounded-lg text-sm font-medium hover:bg-primary/90 transition">
                            Update Submission
                        </button>
                    </div>
                </div>
            </form>

            {{-- Delete --}}
            <form action="{{ route('admin.contact-submissions.destroy', $contactSubmission->id) }}" method="POST" class="mt-4" onsubmit="return confirm('Are you sure you want to delete this submission?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="w-full bg-danger/10 text-danger py-2.5 rounded-lg text-sm font-medium hover:bg-danger/20 transition">
                    Delete Submission
                </button>
            </form>
        </div>
    </div>
@endsection
