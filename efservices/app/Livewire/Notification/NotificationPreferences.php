<?php

namespace App\Livewire\Notification;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use App\Services\NotificationService;

class NotificationPreferences extends Component
{
    public $preferences = [];
    public $categories = [];
    public $criticalCategories = [];

    protected $listeners = ['preferenceSaved' => '$refresh'];

    public function mount()
    {
        $this->loadCategories();
        $this->loadPreferences();
    }

    public function loadCategories()
    {
        $notificationService = app(NotificationService::class);
        $user = Auth::user();
        
        // Determinar el rol del usuario
        $role = $user->hasRole('carrier') || $user->hasRole('user_carrier') ? 'carrier' : 
               (($user->hasRole('driver') || $user->hasRole('user_driver')) ? 'driver' : null);
        
        $this->categories = $role ? $notificationService->getCategoriesForRole($role) : [];
        $this->criticalCategories = \App\Models\UserNotificationPreference::CRITICAL_CATEGORIES;
    }

    public function loadPreferences()
    {
        $notificationService = app(NotificationService::class);
        $user = Auth::user();
        
        $userPreferences = $notificationService->getPreferencesForUser($user);
        
        // Initialize preferences array with defaults
        foreach ($this->categories as $category => $label) {
            $pref = $userPreferences->firstWhere('category', $category);
            
            $this->preferences[$category] = [
                'in_app_enabled' => $pref ? ($pref['in_app_enabled'] ?? true) : true,
                'email_enabled' => $pref ? ($pref['email_enabled'] ?? true) : true,
                'is_critical' => in_array($category, $this->criticalCategories),
            ];
        }
    }

    public function toggleInApp($category)
    {
        if (in_array($category, $this->criticalCategories)) {
            return; // Cannot disable critical notifications
        }

        $notificationService = app(NotificationService::class);
        $newValue = !$this->preferences[$category]['in_app_enabled'];
        
        $notificationService->updatePreference(
            Auth::user(),
            $category,
            $newValue,
            $this->preferences[$category]['email_enabled']
        );
        
        $this->preferences[$category]['in_app_enabled'] = $newValue;
        
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Preference updated successfully.'
        ]);
    }

    public function toggleEmail($category)
    {
        if (in_array($category, $this->criticalCategories)) {
            return; // Cannot disable critical notifications
        }

        $notificationService = app(NotificationService::class);
        $newValue = !$this->preferences[$category]['email_enabled'];
        
        $notificationService->updatePreference(
            Auth::user(),
            $category,
            $this->preferences[$category]['in_app_enabled'],
            $newValue
        );
        
        $this->preferences[$category]['email_enabled'] = $newValue;
        
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Preference updated successfully.'
        ]);
    }

    public function enableAll()
    {
        $notificationService = app(NotificationService::class);
        $user = Auth::user();
        
        foreach ($this->categories as $category => $label) {
            $notificationService->updatePreference($user, $category, true, true);
            
            $this->preferences[$category]['in_app_enabled'] = true;
            $this->preferences[$category]['email_enabled'] = true;
        }
        
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'All notifications enabled.'
        ]);
    }

    public function getCategoryIcon($category)
    {
        return match ($category) {
            // Carrier categories
            'driver_registration' => 'UserPlus',
            'driver_documents' => 'FileText',
            'driver_compliance' => 'Shield',
            'driver_training' => 'GraduationCap',
            'vehicle_documents' => 'FileText',
            'vehicle_compliance' => 'ClipboardCheck',
            'vehicle_maintenance' => 'Wrench',
            'vehicle_repairs' => 'AlertTriangle',
            'hos_violations' => 'AlertOctagon',
            'hos_limits' => 'Clock',
            'trips' => 'MapPin',
            'accidents' => 'AlertOctagon',
            'messages' => 'Mail',
            // Driver categories
            'personal_documents' => 'FileText',
            'personal_compliance' => 'Shield',
            'training' => 'GraduationCap',
            'vehicle_assignment' => 'Truck',
            'maintenance' => 'Wrench',
            'repairs' => 'AlertTriangle',
            default => 'Bell',
        };
    }

    public function render()
    {
        return view('livewire.notification.notification-preferences');
    }
}
