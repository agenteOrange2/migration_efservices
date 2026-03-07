<div>
    <div class="intro-y flex flex-col sm:flex-row items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Notifications
            @if($unreadCount > 0)
                <span class="ml-2 px-2 py-1 bg-primary/10 text-primary text-sm rounded-full">
                    {{ $unreadCount }} unread
                </span>
            @endif
        </h2>
        <div class="w-full sm:w-auto flex mt-4 sm:mt-0 gap-2">
            @if($unreadCount > 0)
                <button wire:click="markAllAsRead" class="btn btn-primary shadow-md">
                    <x-base.lucide class="w-4 h-4 mr-2" icon="CheckCheck" />
                    Mark All as Read
                </button>
            @endif
            
            @if($this->notifications->count() > 0)
                <button wire:click="deleteAllFiltered" 
                        onclick="return confirm('Are you sure you want to delete all filtered notifications?');"
                        class="btn btn-danger shadow-md">
                    <x-base.lucide class="w-4 h-4 mr-2" icon="Trash2" />
                    Delete All
                </button>
            @endif
        </div>
    </div>
    
    <div class="grid grid-cols-12 gap-6 mt-5">
        <!-- Filter Panel -->
        <div class="col-span-12 md:col-span-3 xl:col-span-2">
            <div class="box p-5 intro-y">
                <div class="flex flex-col">
                    <div class="text-base font-medium mb-3">Filter</div>
                    
                    <div class="space-y-1">
                        <button wire:click="setFilterStatus('all')" 
                           class="w-full flex items-center px-3 py-2 rounded-md text-left {{ $filterStatus === 'all' ? 'bg-primary text-white' : 'hover:bg-slate-100' }}">
                            <x-base.lucide class="w-4 h-4 mr-2" icon="Bell" />
                            <span class="truncate">All Notifications</span>
                        </button>
                        <button wire:click="setFilterStatus('unread')" 
                           class="w-full flex items-center px-3 py-2 rounded-md text-left {{ $filterStatus === 'unread' ? 'bg-primary text-white' : 'hover:bg-slate-100' }}">
                            <x-base.lucide class="w-4 h-4 mr-2" icon="BellDot" />
                            <span class="truncate">Unread</span>
                        </button>
                        <button wire:click="setFilterStatus('read')" 
                           class="w-full flex items-center px-3 py-2 rounded-md text-left {{ $filterStatus === 'read' ? 'bg-primary text-white' : 'hover:bg-slate-100' }}">
                            <x-base.lucide class="w-4 h-4 mr-2" icon="BellOff" />
                            <span class="truncate">Read</span>
                        </button>
                    </div>
                    
                    @if(count($notificationTypes) > 0)
                        <div class="border-t border-slate-200 dark:border-darkmode-400 mt-5 pt-5">
                            <div class="text-base font-medium mb-3">Notification Types</div>
                            <div class="space-y-1 max-h-64 overflow-y-auto">
                                <button wire:click="setFilterType('')" 
                                   class="w-full flex items-center px-3 py-2 rounded-md text-left {{ empty($filterType) ? 'bg-primary text-white' : 'hover:bg-slate-100' }}">
                                    <span class="truncate">All Types</span>
                                </button>
                                @foreach($notificationTypes as $type)
                                    <button wire:click="setFilterType('{{ $type['id'] }}')" 
                                       class="w-full flex items-center px-3 py-2 rounded-md text-left {{ $filterType === $type['id'] ? 'bg-primary text-white' : 'hover:bg-slate-100' }}">
                                        <span class="truncate text-sm">{{ $type['name'] }}</span>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if($filterStatus !== 'all' || !empty($filterType))
                        <div class="mt-4">
                            <button wire:click="clearFilters" class="btn btn-outline-secondary w-full">
                                <x-base.lucide class="w-4 h-4 mr-2" icon="X" />
                                Clear Filters
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        
        <!-- Notifications List -->
        <div class="col-span-12 md:col-span-9 xl:col-span-10">
            <div class="box intro-y">
                <div class="p-5">
                    @forelse($this->notifications as $notification)
                        <div class="intro-x" wire:key="notification-{{ $notification->id }}">
                            <div class="flex items-center px-5 py-3 mb-3 box {{ $notification->read_at ? 'bg-slate-50' : 'bg-primary/5 border-primary/10' }} hover:bg-slate-100 rounded-md transition">
                                <div class="flex-none mr-4">
                                    <x-base.lucide 
                                        class="w-6 h-6 {{ $notification->read_at ? 'text-slate-500' : 'text-primary' }}" 
                                        icon="{{ $this->getNotificationIcon($notification) }}" 
                                    />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center">
                                        <div class="mr-auto min-w-0">
                                            <div class="font-medium {{ $notification->read_at ? '' : 'text-primary' }} truncate">
                                                {{ $notification->data['title'] ?? $this->formatTypeName($notification->type) }}
                                            </div>
                                            <div class="text-slate-500 text-xs mt-0.5 truncate">
                                                {{ $notification->data['message'] ?? '' }}
                                            </div>
                                        </div>
                                        <div class="text-xs text-slate-500 ml-4 whitespace-nowrap">
                                            {{ $notification->created_at->diffForHumans() }}
                                        </div>
                                    </div>
                                </div>
                                <div class="flex-none ml-4 flex gap-1">
                                    @if($notification->read_at)
                                        <button wire:click="markAsUnread('{{ $notification->id }}')" 
                                                class="text-slate-500 hover:text-primary p-1" 
                                                title="Mark as unread">
                                            <x-base.lucide class="w-5 h-5" icon="MessageSquare" />
                                        </button>
                                    @else
                                        <button wire:click="markAsRead('{{ $notification->id }}')" 
                                                class="text-slate-500 hover:text-primary p-1" 
                                                title="Mark as read">
                                            <x-base.lucide class="w-5 h-5" icon="CheckSquare" />
                                        </button>
                                    @endif
                                    <button wire:click="deleteNotification('{{ $notification->id }}')" 
                                            onclick="return confirm('Are you sure you want to delete this notification?');"
                                            class="text-slate-500 hover:text-danger p-1" 
                                            title="Delete">
                                        <x-base.lucide class="w-5 h-5" icon="Trash2" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center p-10">
                            <x-base.lucide class="w-16 h-16 mx-auto mt-3 text-slate-300" icon="BellOff" />
                            <div class="mt-5 text-slate-600">
                                <p class="text-lg font-medium mb-1">No notifications found</p>
                                <p>
                                    @if($filterStatus === 'unread')
                                        You have no unread notifications.
                                    @elseif($filterStatus === 'read')
                                        You have no read notifications.
                                    @else
                                        You have no notifications.
                                    @endif
                                </p>
                            </div>
                        </div>
                    @endforelse
                    
                    <!-- Pagination -->
                    @if($this->notifications->hasPages())
                        <div class="mt-5">
                            {{ $this->notifications->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
