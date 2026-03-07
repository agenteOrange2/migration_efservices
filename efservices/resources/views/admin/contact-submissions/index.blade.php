@extends('../themes/' . $activeTheme)
@section('title', 'Contact Submissions')
@php
    $breadcrumbLinks = [
        ['label' => 'App', 'url' => route('admin.dashboard')],
        ['label' => 'Contact Submissions', 'active' => true],
    ];
@endphp
@section('subcontent')
    <x-base.notificationtoast.notification-toast :notification="session('notification')" />
    <div class="grid grid-cols-12 gap-x-6 gap-y-10">
        <div class="col-span-12">
            <div class="flex flex-col gap-y-3 md:h-10 md:flex-row md:items-center">
                <div class="text-base font-medium group-[.mode--light]:text-white">
                    Contact Submissions
                </div>
            </div>

            {{-- Stats cards --}}
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mt-5">
                <a href="{{ route('admin.contact-submissions.index') }}" class="box box--stacked p-4 text-center {{ !request('status') ? 'border-primary border-2' : '' }}">
                    <div class="text-2xl font-bold text-slate-700">{{ $counts['all'] }}</div>
                    <div class="text-xs text-slate-500 mt-1">All</div>
                </a>
                <a href="{{ route('admin.contact-submissions.index', ['status' => 'new']) }}" class="box box--stacked p-4 text-center {{ request('status') === 'new' ? 'border-primary border-2' : '' }}">
                    <div class="text-2xl font-bold text-primary">{{ $counts['new'] }}</div>
                    <div class="text-xs text-slate-500 mt-1">New</div>
                </a>
                <a href="{{ route('admin.contact-submissions.index', ['status' => 'in_progress']) }}" class="box box--stacked p-4 text-center {{ request('status') === 'in_progress' ? 'border-warning border-2' : '' }}">
                    <div class="text-2xl font-bold text-warning">{{ $counts['in_progress'] }}</div>
                    <div class="text-xs text-slate-500 mt-1">In Progress</div>
                </a>
                <a href="{{ route('admin.contact-submissions.index', ['status' => 'contacted']) }}" class="box box--stacked p-4 text-center {{ request('status') === 'contacted' ? 'border-success border-2' : '' }}">
                    <div class="text-2xl font-bold text-success">{{ $counts['contacted'] }}</div>
                    <div class="text-xs text-slate-500 mt-1">Contacted</div>
                </a>
                <a href="{{ route('admin.contact-submissions.index', ['status' => 'closed']) }}" class="box box--stacked p-4 text-center {{ request('status') === 'closed' ? 'border-secondary border-2' : '' }}">
                    <div class="text-2xl font-bold text-slate-400">{{ $counts['closed'] }}</div>
                    <div class="text-xs text-slate-500 mt-1">Closed</div>
                </a>
            </div>

            <div class="box box--stacked flex flex-col mt-5">
                <div class="flex flex-col gap-y-2 p-5 sm:flex-row sm:items-center">
                    <form method="GET" action="{{ route('admin.contact-submissions.index') }}" class="relative w-full sm:w-72">
                        @if(request('status'))
                            <input type="hidden" name="status" value="{{ request('status') }}">
                        @endif
                        <x-base.lucide class="absolute inset-y-0 left-0 z-10 my-auto ml-3 h-4 w-4 stroke-[1.3] text-slate-500" icon="Search" />
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search contacts..." class="w-full rounded-[0.5rem] border border-slate-200 bg-slate-50 py-2 pl-9 pr-4 text-sm shadow-sm focus:border-primary focus:ring-primary">
                    </form>
                </div>

                <div class="overflow-auto xl:overflow-visible">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="border-b border-slate-200/60 bg-slate-50">
                                <th class="px-5 py-4 font-medium text-slate-500 text-xs uppercase">Name</th>
                                <th class="px-5 py-4 font-medium text-slate-500 text-xs uppercase">Email</th>
                                <th class="px-5 py-4 font-medium text-slate-500 text-xs uppercase">Company</th>
                                <th class="px-5 py-4 font-medium text-slate-500 text-xs uppercase">Status</th>
                                <th class="px-5 py-4 font-medium text-slate-500 text-xs uppercase">Date</th>
                                <th class="px-5 py-4 font-medium text-slate-500 text-xs uppercase text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($submissions as $submission)
                                <tr class="border-b border-slate-200/60 hover:bg-slate-50/50">
                                    <td class="px-5 py-4">
                                        <div class="font-medium text-slate-700">{{ $submission->full_name }}</div>
                                        @if($submission->phone)
                                            <div class="text-xs text-slate-500 mt-0.5">{{ $submission->phone }}</div>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4 text-sm text-slate-600">{{ $submission->email }}</td>
                                    <td class="px-5 py-4 text-sm text-slate-600">{{ $submission->company ?? '-' }}</td>
                                    <td class="px-5 py-4">
                                        <span class="px-2.5 py-1 rounded-full text-xs font-medium {{ $submission->status_badge }}">
                                            {{ ucfirst(str_replace('_', ' ', $submission->status)) }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 text-sm text-slate-500">{{ $submission->created_at->format('M d, Y H:i') }}</td>
                                    <td class="px-5 py-4 text-center">
                                        <a href="{{ route('admin.contact-submissions.show', $submission->id) }}" class="text-primary hover:underline text-sm font-medium">
                                            View
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-5 py-10 text-center text-slate-500">
                                        No contact submissions found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($submissions->hasPages())
                    <div class="p-5 border-t border-slate-200/60">
                        {{ $submissions->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
