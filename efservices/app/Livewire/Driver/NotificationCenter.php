<?php

namespace App\Livewire\Driver;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use App\Services\NotificationService;

class NotificationCenter extends Component
{
    use WithPagination;

    public $filterStatus = 'all';
    public $filterType = '';
    public $notificationTypes = [];
    public $unreadCount = 0;

    protected $queryString = [
        'filterStatus' => ['except' => 'all'],
        'filterType' => ['except' => ''],
    ];

    protected $listeners = [
        'refreshNotifications' => '$refresh',
    ];

    public function mount()
    {
        $this->loadNotificationTypes();
        $this->updateUnreadCount();
    }

    public function loadNotificationTypes()
    {
        $notificationService = app(NotificationService::class);
        $this->notificationTypes = $notificationService->getNotificationTypesForUser(Auth::user());
    }

    public function updateUnreadCount()
    {
        $this->unreadCount = Auth::user()->unreadNotifications()->count();
    }

    public function setFilterStatus($status)
    {
        $this->filterStatus = $status;
        $this->resetPage();
    }

    public function setFilterType($type)
    {
        $this->filterType = $type;
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->filterStatus = 'all';
        $this->filterType = '';
        $this->resetPage();
    }

    public function markAsRead($notificationId)
    {
        $notification = Auth::user()->notifications()->find($notificationId);
        
        if ($notification) {
            $notification->markAsRead();
            $this->updateUnreadCount();
            $this->dispatch('refreshNotifications');
        }
    }

    public function markAsUnread($notificationId)
    {
        $notification = Auth::user()->notifications()->find($notificationId);
        
        if ($notification) {
            $notification->update(['read_at' => null]);
            $this->updateUnreadCount();
            $this->dispatch('refreshNotifications');
        }
    }

    public function markAllAsRead()
    {
        Auth::user()->unreadNotifications->markAsRead();
        $this->updateUnreadCount();
        $this->dispatch('refreshNotifications');
    }

    public function deleteNotification($notificationId)
    {
        $notification = Auth::user()->notifications()->find($notificationId);
        
        if ($notification) {
            $notification->delete();
            $this->updateUnreadCount();
        }
    }

    public function deleteAllFiltered()
    {
        $query = Auth::user()->notifications();

        if ($this->filterStatus === 'read') {
            $query->whereNotNull('read_at');
        } elseif ($this->filterStatus === 'unread') {
            $query->whereNull('read_at');
        }

        if (!empty($this->filterType)) {
            $query->where('type', $this->filterType);
        }

        $query->delete();
        $this->updateUnreadCount();
    }

    public function getNotificationsProperty()
    {
        $query = Auth::user()->notifications();

        // Filtrar por estado
        if ($this->filterStatus === 'read') {
            $query->whereNotNull('read_at');
        } elseif ($this->filterStatus === 'unread') {
            $query->whereNull('read_at');
        }

        // Filtrar por tipo
        if (!empty($this->filterType)) {
            $query->where('type', $this->filterType);
        }

        return $query->orderBy('created_at', 'desc')->paginate(15);
    }

    public function getNotificationIcon($notification)
    {
        $type = class_basename($notification->type);
        
        return match (true) {
            str_contains($type, 'Hos') || str_contains($type, 'HOS') => 'Clock',
            str_contains($type, 'Driving') => 'Clock',
            str_contains($type, 'Break') => 'Coffee',
            str_contains($type, 'Cycle') => 'RefreshCw',
            str_contains($type, 'License') => 'CreditCard',
            str_contains($type, 'Medical') => 'Heart',
            str_contains($type, 'Training') => 'GraduationCap',
            str_contains($type, 'Document') => 'FileText',
            str_contains($type, 'Vehicle') => 'Truck',
            str_contains($type, 'Maintenance') => 'Wrench',
            str_contains($type, 'Repair') => 'AlertTriangle',
            str_contains($type, 'Trip') => 'MapPin',
            str_contains($type, 'Message') => 'Mail',
            str_contains($type, 'Inspection') => 'ClipboardCheck',
            str_contains($type, 'Accident') => 'AlertOctagon',
            default => $notification->data['icon'] ?? 'Bell',
        };
    }

    public function formatTypeName($type)
    {
        $className = class_basename($type);
        $name = str_replace('Notification', '', $className);
        $name = preg_replace('/([a-z])([A-Z])/', '$1 $2', $name);
        return trim($name);
    }

    public function render()
    {
        return view('livewire.driver.notification-center', [
            'notifications' => $this->notifications,
        ]);
    }
}
