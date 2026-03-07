@extends('../themes/' . $activeTheme)
@section('title', 'My Messages')
@php
$breadcrumbLinks = [
['label' => 'App', 'url' => route('driver.dashboard')],
['label' => 'Messages', 'active' => true],
];
@endphp

@section('subcontent')
<div class="max-w-4xl mx-auto">
    <!-- Header -->
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-slate-900">My Messages</h2>
        <p class="text-slate-600 text-sm mt-1">View important messages from admin and your carrier</p>
    </div>

    <!-- Stats Cards (Mobile Optimized) -->
    <div class="grid grid-cols-3 gap-3 mb-6">
        <div class="bg-white rounded-lg p-4 shadow-sm border border-slate-200">
            <div class="text-2xl font-bold text-blue-600">{{ $stats['total'] }}</div>
            <div class="text-xs text-slate-600 mt-1">Total</div>
        </div>
        <div class="bg-white rounded-lg p-4 shadow-sm border border-slate-200">
            <div class="text-2xl font-bold text-orange-600">{{ $stats['unread'] }}</div>
            <div class="text-xs text-slate-600 mt-1">Unread</div>
        </div>
        <div class="bg-white rounded-lg p-4 shadow-sm border border-slate-200">
            <div class="text-2xl font-bold text-red-600">{{ $stats['high_priority'] }}</div>
            <div class="text-xs text-slate-600 mt-1">High Priority</div>
        </div>
    </div>

    <!-- Filters (Mobile Friendly) -->
    <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4 mb-4">
        <form action="{{ route('driver.messages.index') }}" method="GET" class="space-y-3">
            <div>
                <x-base.form-input 
                    type="text" 
                    name="search" 
                    value="{{ request('search') }}" 
                    placeholder="Search messages..." 
                    class="w-full"
                />
            </div>
            <div class="grid grid-cols-2 gap-3">
                <x-base.form-select name="read_status" class="w-full">
                    <option value="">All Messages</option>
                    <option value="unread" {{ request('read_status') == 'unread' ? 'selected' : '' }}>Unread</option>
                    <option value="read" {{ request('read_status') == 'read' ? 'selected' : '' }}>Read</option>
                </x-base.form-select>
                <x-base.form-select name="priority" class="w-full">
                    <option value="">All Priorities</option>
                    <option value="high" {{ request('priority') == 'high' ? 'selected' : '' }}>High</option>
                    <option value="normal" {{ request('priority') == 'normal' ? 'selected' : '' }}>Normal</option>
                    <option value="low" {{ request('priority') == 'low' ? 'selected' : '' }}>Low</option>
                </x-base.form-select>
            </div>
            <div class="flex gap-2">
                <x-base.button type="submit" variant="primary" class="flex-1">
                    <x-base.lucide class="w-4 h-4 mr-2" icon="filter" />
                    Apply
                </x-base.button>
                <x-base.button as="a" href="{{ route('driver.messages.index') }}" variant="outline-secondary">
                    <x-base.lucide class="w-4 h-4" icon="x" />
                </x-base.button>
            </div>
        </form>
    </div>

    <!-- Messages List -->
    <div class="space-y-3">
        @forelse($messages as $recipient)
        <a href="{{ route('driver.messages.show', $recipient) }}" class="block">
            <div class="bg-white rounded-lg shadow-sm border {{ $recipient->read_at ? 'border-slate-200' : 'border-l-4 border-l-blue-500 border-t-slate-200 border-r-slate-200 border-b-slate-200' }} p-4 hover:shadow-md transition-shadow">
                <!-- Header Row -->
                <div class="flex justify-between items-start mb-2">
                    <div class="flex-1">
                        <h3 class="font-semibold text-slate-900 {{ $recipient->read_at ? '' : 'font-bold' }}">
                            {{ $recipient->message->subject }}
                        </h3>
                        <div class="flex items-center gap-2 mt-1">
                            <span class="text-xs text-slate-600">
                                From: {{ $recipient->message->sender_name }}
                            </span>
                            @if($recipient->message->sender_type === 'App\\Models\\User')
                                <span class="px-2 py-0.5 rounded-full text-xs bg-blue-100 text-blue-800">
                                    Admin
                                </span>
                            @elseif($recipient->message->sender_type === 'App\\Models\\Carrier')
                                <span class="px-2 py-0.5 rounded-full text-xs bg-green-100 text-green-800">
                                    Carrier
                                </span>
                            @endif
                        </div>
                    </div>
                    <div class="text-right ml-2">
                        <span class="text-xs text-slate-500">
                            {{ $recipient->message->sent_at ? $recipient->message->sent_at->diffForHumans() : 'Not sent' }}
                        </span>
                    </div>
                </div>

                <!-- Message Preview -->
                <p class="text-sm text-slate-600 line-clamp-2 mb-2">
                    {{ Str::limit($recipient->message->message, 120) }}
                </p>

                <!-- Footer Row -->
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        @if($recipient->message->priority === 'high')
                            <span class="px-2 py-1 rounded-full text-xs bg-red-100 text-red-800 font-medium">
                                <x-base.lucide class="w-3 h-3 inline mr-1" icon="alert-circle" />
                                High Priority
                            </span>
                        @elseif($recipient->message->priority === 'normal')
                            <span class="px-2 py-1 rounded-full text-xs bg-blue-100 text-blue-800">
                                Normal
                            </span>
                        @else
                            <span class="px-2 py-1 rounded-full text-xs bg-gray-100 text-gray-800">
                                Low
                            </span>
                        @endif
                        
                        @if(!$recipient->read_at)
                            <span class="px-2 py-1 rounded-full text-xs bg-orange-100 text-orange-800 font-medium">
                                Unread
                            </span>
                        @endif
                    </div>
                    <x-base.lucide class="w-5 h-5 text-slate-400" icon="chevron-right" />
                </div>
            </div>
        </a>
        @empty
        <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-10 text-center">
            <x-base.lucide class="w-16 h-16 text-slate-300 mx-auto mb-4" icon="mail" />
            <h3 class="text-lg font-medium text-slate-600 mb-2">No Messages</h3>
            <p class="text-slate-500 text-sm">You don't have any messages yet.</p>
        </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($messages->hasPages())
    <div class="mt-6">
        {{ $messages->appends(request()->query())->links() }}
    </div>
    @endif
</div>
@endsection

