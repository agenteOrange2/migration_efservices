{{-- Toast Notification Templates --}}

<!-- Success Notification Content -->
<div id="success-notification-content" class="hidden">
    <div class="flex items-center gap-3 p-4 rounded-lg bg-green-100 border border-green-400 text-green-700">
        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <div class="flex-1">
            <div class="font-medium" id="success-message-text">Success!</div>
        </div>
    </div>
</div>

<!-- Error Notification Content -->
<div id="error-notification-content" class="hidden">
    <div class="flex items-center gap-3 p-4 rounded-lg bg-red-100 border border-red-400 text-red-700">
        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
        </svg>
        <div class="flex-1">
            <div class="font-medium" id="error-message-text">Error!</div>
        </div>
    </div>
</div>

<!-- Warning Notification Content -->
<div id="warning-notification-content" class="hidden">
    <div class="flex items-center gap-3 p-4 rounded-lg bg-yellow-100 border border-yellow-400 text-yellow-700">
        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
        </svg>
        <div class="flex-1">
            <div class="font-medium" id="warning-message-text">Warning!</div>
        </div>
    </div>
</div>

<!-- Info Notification Content -->
<div id="info-notification-content" class="hidden">
    <div class="flex items-center gap-3 p-4 rounded-lg bg-blue-100 border border-blue-400 text-blue-700">
        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
        </svg>
        <div class="flex-1">
            <div class="font-medium" id="info-message-text">Info!</div>
        </div>
    </div>
</div>

<!-- Loading Notification Content -->
<div id="loading-notification-content" class="hidden">
    <div class="flex items-center gap-3 p-4 rounded-lg bg-slate-100 border border-slate-300 text-slate-700">
        <svg class="w-5 h-5 flex-shrink-0 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <div class="flex-1">
            <div class="font-medium" id="loading-message-text">Loading...</div>
        </div>
    </div>
</div>

@pushOnce('scripts')
<script>
// Toast Notification System
window.ToastNotifications = {
    show: function(type, message, options = {}) {
        const defaults = {
            duration: 4000,
            position: 'right',
            stopOnFocus: true,
            close: true,
            gravity: 'top'
        };
        
        const config = { ...defaults, ...options };
        
        // Get the notification content element
        const contentElement = document.getElementById(`${type}-notification-content`);
        if (!contentElement) {
            console.error(`Toast notification type '${type}' not found`);
            return;
        }
        
        // Clone the content and update the message
        const clonedContent = contentElement.cloneNode(true);
        clonedContent.classList.remove('hidden');
        
        // Update the message text
        const messageElement = clonedContent.querySelector(`#${type}-message-text`);
        if (messageElement) {
            messageElement.textContent = message;
        }
        
        // Show the toast using Toastify
        if (window.Toastify) {
            const toast = window.Toastify({
                node: clonedContent,
                duration: config.duration,
                close: config.close,
                gravity: config.gravity,
                position: config.position,
                stopOnFocus: config.stopOnFocus,
                className: 'toast-notification'
            }).showToast();
            
            return toast;
        } else {
            console.error('Toastify is not loaded');
        }
    },
    
    success: function(message, options = {}) {
        return this.show('success', message, options);
    },
    
    error: function(message, options = {}) {
        return this.show('error', message, options);
    },
    
    warning: function(message, options = {}) {
        return this.show('warning', message, options);
    },
    
    info: function(message, options = {}) {
        return this.show('info', message, options);
    },
    
    loading: function(message, options = {}) {
        const loadingOptions = { ...options, duration: -1, close: false }; // Persistent loading
        return this.show('loading', message, loadingOptions);
    }
};

// Auto-show notifications from session flash messages
document.addEventListener('DOMContentLoaded', function() {
    @if(session('success'))
        ToastNotifications.success('{{ session('success') }}');
    @endif
    
    @if(session('error'))
        ToastNotifications.error('{{ session('error') }}');
    @endif
    
    @if(session('warning'))
        ToastNotifications.warning('{{ session('warning') }}');
    @endif
    
    @if(session('info'))
        ToastNotifications.info('{{ session('info') }}');
    @endif
});
</script>
@endPushOnce