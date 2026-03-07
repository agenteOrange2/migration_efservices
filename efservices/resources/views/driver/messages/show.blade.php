@extends('../themes/' . $activeTheme)
@section('title', 'Message')
@php
$breadcrumbLinks = [
['label' => 'App', 'url' => route('driver.dashboard')],
['label' => 'Messages', 'url' => route('driver.messages.index')],
['label' => 'Message Details', 'active' => true],
];
@endphp

@section('subcontent')
<div class="max-w-4xl mx-auto">
    <!-- Flash Messages -->
    @if(session('success'))
    <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg flex items-center gap-2">
        <x-base.lucide class="w-5 h-5" icon="check-circle" />
        {{ session('success') }}
    </div>
    @endif
    
    @if(session('error'))
    <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg flex items-center gap-2">
        <x-base.lucide class="w-5 h-5" icon="alert-circle" />
        {{ session('error') }}
    </div>
    @endif

    <!-- Back Button (Mobile Friendly) -->
    <div class="mb-4">
        <x-base.button as="a" href="{{ route('driver.messages.index') }}" variant="outline-secondary" class="w-full sm:w-auto">
            <x-base.lucide class="w-4 h-4 mr-2" icon="arrow-left" />
            Back to Messages
        </x-base.button>
    </div>

    <!-- Message Card -->
    <div class="box box--stacked">
        <!-- Header -->
        <div class="bg-slate-50 border-b border-slate-200 p-4">
            <div class="flex items-start justify-between mb-3">
                <div class="flex-1">
                    <h1 class="text-xl font-bold text-slate-900 mb-2">
                        {{ $recipient->message->subject }}
                    </h1>
                    <div class="flex flex-wrap items-center gap-2">
                        @if($recipient->message->priority === 'high')
                            <span class="px-2 py-1 rounded-full text-xs bg-red-100 text-red-800 font-medium">
                                <x-base.lucide class="w-3 h-3 inline mr-1" icon="alert-circle" />
                                High Priority
                            </span>
                        @elseif($recipient->message->priority === 'normal')
                            <span class="px-2 py-1 rounded-full text-xs bg-blue-100 text-blue-800">
                                Normal Priority
                            </span>
                        @else
                            <span class="px-2 py-1 rounded-full text-xs bg-gray-100 text-gray-800">
                                Low Priority
                            </span>
                        @endif
                        
                        <span class="px-2 py-1 rounded-full text-xs {{ $recipient->message->status_color }}">
                            {{ ucfirst($recipient->message->status) }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Sender Info -->
            <div class="bg-white rounded-lg p-3 border border-slate-200">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-slate-200 rounded-full flex items-center justify-center flex-shrink-0">
                        <x-base.lucide class="w-5 h-5 text-slate-600" icon="user" />
                    </div>
                    <div class="flex-1">
                        <div class="font-medium text-slate-900">{{ $recipient->message->sender_name }}</div>
                        <div class="text-xs text-slate-600">{{ $recipient->message->sender_email ?? 'N/A' }}</div>
                    </div>
                    @if($recipient->message->sender_type === 'App\\Models\\User')
                        <span class="px-2 py-1 rounded-full text-xs bg-blue-100 text-blue-800 font-medium">
                            Admin
                        </span>
                    @elseif($recipient->message->sender_type === 'App\\Models\\Carrier')
                        <span class="px-2 py-1 rounded-full text-xs bg-green-100 text-green-800 font-medium">
                            Carrier
                        </span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Message Content -->
        <div class="p-4">
            <div class="prose prose-sm max-w-none">
                <div class="text-slate-700 whitespace-pre-wrap">{{ $recipient->message->message }}</div>
            </div>
        </div>

        <!-- Footer Info -->
        <div class="bg-slate-50 border-t border-slate-200 p-4 space-y-3">
            <div class="flex items-center justify-between text-sm">
                <span class="text-slate-600">Sent:</span>
                <span class="font-medium text-slate-900">
                    @if($recipient->message->sent_at)
                        {{ $recipient->message->sent_at->format('M d, Y H:i') }}
                        <span class="text-slate-500 text-xs ml-1">({{ $recipient->message->sent_at->diffForHumans() }})</span>
                    @else
                        Not sent yet
                    @endif
                </span>
            </div>

            @if($recipient->delivered_at)
            <div class="flex items-center justify-between text-sm">
                <span class="text-slate-600">Delivered:</span>
                <span class="font-medium text-slate-900">
                    {{ $recipient->delivered_at->format('M d, Y H:i') }}
                    <span class="text-slate-500 text-xs ml-1">({{ $recipient->delivered_at->diffForHumans() }})</span>
                </span>
            </div>
            @endif

            @if($recipient->read_at)
            <div class="flex items-center justify-between text-sm">
                <span class="text-slate-600">Read:</span>
                <span class="font-medium text-slate-900">
                    {{ $recipient->read_at->format('M d, Y H:i') }}
                    <span class="text-slate-500 text-xs ml-1">({{ $recipient->read_at->diffForHumans() }})</span>
                </span>
            </div>
            @else
            <div class="flex items-center gap-2 text-sm">
                <x-base.lucide class="w-4 h-4 text-orange-500" icon="info" />
                <span class="text-orange-600 font-medium">Just read for the first time</span>
            </div>
            @endif

            <div class="flex items-center justify-between text-sm">
                <span class="text-slate-600">Delivery Status:</span>
                <span class="px-2 py-1 rounded-full text-xs font-medium {{ $recipient->delivery_status_color }}">
                    {{ ucfirst($recipient->delivery_status) }}
                </span>
            </div>
        </div>
    </div>

    <!-- Status History (if available) -->
    @if($recipient->message->statusLogs->count() > 0)
    <div class="box box--stacked mt-4">
        <div class="bg-slate-50 border-b border-slate-200 p-4">
            <h3 class="font-semibold text-slate-900">Message History</h3>
        </div>
        <div class="p-4">
            <div class="space-y-3">
                @foreach($recipient->message->statusLogs->sortByDesc('created_at') as $log)
                <div class="flex items-start gap-3">
                    <div class="w-2 h-2 bg-blue-500 rounded-full mt-2 flex-shrink-0"></div>
                    <div class="flex-1">
                        <div class="font-medium text-sm text-slate-900">{{ ucfirst($log->status) }}</div>
                        @if($log->notes)
                        <div class="text-slate-600 text-xs mt-1">{{ $log->notes }}</div>
                        @endif
                        <div class="text-slate-400 text-xs mt-1">
                            {{ $log->created_at->format('M d, Y H:i') }} 
                            <span class="ml-1">({{ $log->created_at->diffForHumans() }})</span>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    <!-- Conversation Replies -->
    @if(isset($replies) && $replies->count() > 0)
    <div class="box box--stacked mt-4">
        <div class="bg-slate-50 border-b border-slate-200 p-4">
            <h3 class="font-semibold text-slate-900 flex items-center gap-2">
                <x-base.lucide class="w-5 h-5 text-primary" icon="message-circle" />
                Your Replies ({{ $replies->count() }})
            </h3>
        </div>
        <div class="p-4 space-y-4">
            @foreach($replies as $reply)
            <div class="bg-blue-50 rounded-lg p-4 border border-blue-200">
                <div class="flex items-start gap-3">
                    <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center flex-shrink-0">
                        <x-base.lucide class="w-4 h-4 text-white" icon="user" />
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center justify-between mb-2">
                            <span class="font-medium text-slate-900">You</span>
                            <span class="text-xs text-slate-500">
                                {{ $reply->sent_at ? $reply->sent_at->format('M d, Y H:i') : $reply->created_at->format('M d, Y H:i') }}
                                <span class="ml-1">({{ $reply->sent_at ? $reply->sent_at->diffForHumans() : $reply->created_at->diffForHumans() }})</span>
                            </span>
                        </div>
                        <div class="text-slate-700 whitespace-pre-wrap text-sm">{{ $reply->message }}</div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Reply Form -->
    <div class="box box--stacked mt-4">
        <div class="bg-slate-50 border-b border-slate-200 p-4">
            <h3 class="font-semibold text-slate-900 flex items-center gap-2">
                <x-base.lucide class="w-5 h-5 text-primary" icon="reply" />
                Reply to this message
            </h3>
        </div>
        <div class="p-4">
            <form action="{{ route('driver.messages.reply', $recipient->id) }}" method="POST">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-medium text-slate-700 mb-2">
                        Your reply will be sent to your carrier
                    </label>
                    <textarea 
                        name="message" 
                        rows="4" 
                        class="w-full rounded-lg border-slate-300 shadow-sm focus:border-primary focus:ring-primary @error('message') border-red-500 @enderror"
                        placeholder="Type your reply here..."
                        required
                    >{{ old('message') }}</textarea>
                    @error('message')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div class="flex justify-end">
                    <x-base.button type="submit" variant="primary">
                        <x-base.lucide class="w-4 h-4 mr-2" icon="send" />
                        Send Reply
                    </x-base.button>
                </div>
            </form>
        </div>
    </div>

    <!-- Back to List Button (Bottom) -->
    <div class="mt-6">
        <x-base.button as="a" href="{{ route('driver.messages.index') }}" variant="outline-secondary" class="w-full">
            <x-base.lucide class="w-4 h-4 mr-2" icon="arrow-left" />
            Back to All Messages
        </x-base.button>
    </div>
</div>
@endsection


