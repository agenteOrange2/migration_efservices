<!-- Notification Toast Container -->
<div id="hos-notification-container" class="fixed top-4 right-4 z-50 space-y-2" style="max-width: 400px;">
    <!-- Notifications will be dynamically inserted here -->
</div>

@push('scripts')
<script>
    // Notification system
    window.HosNotifications = {
        show: function(message, type = 'info', duration = 5000) {
            const container = document.getElementById('hos-notification-container');
            if (!container) return;

            const id = 'notif-' + Date.now();
            const notification = this.createNotification(id, message, type);

            container.insertAdjacentHTML('beforeend', notification);

            // Auto dismiss
            setTimeout(() => {
                this.dismiss(id);
            }, duration);

            // Play sound if available
            if (type === 'warning' || type === 'error') {
                this.playNotificationSound();
            }

            // Request permission for browser notifications
            if (type === 'warning' || type === 'error') {
                this.showBrowserNotification(message, type);
            }
        },

        createNotification: function(id, message, type) {
            const icons = {
                success: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>',
                error: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>',
                warning: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>',
                info: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>'
            };

            const colors = {
                success: 'bg-green-50 border-green-400 text-green-800',
                error: 'bg-red-50 border-red-400 text-red-800',
                warning: 'bg-yellow-50 border-yellow-400 text-yellow-800',
                info: 'bg-blue-50 border-blue-400 text-blue-800'
            };

            const iconColors = {
                success: 'text-green-500',
                error: 'text-red-500',
                warning: 'text-yellow-500',
                info: 'text-blue-500'
            };

            return `
                <div id="${id}" class="flex items-start p-4 border-l-4 rounded-r shadow-lg ${colors[type]} animate-slide-in">
                    <svg class="w-6 h-6 ${iconColors[type]} mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        ${icons[type]}
                    </svg>
                    <div class="flex-1">
                        <p class="text-sm font-medium">${message}</p>
                    </div>
                    <button onclick="HosNotifications.dismiss('${id}')" class="ml-3 flex-shrink-0 text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            `;
        },

        dismiss: function(id) {
            const element = document.getElementById(id);
            if (element) {
                element.classList.add('animate-slide-out');
                setTimeout(() => {
                    element.remove();
                }, 300);
            }
        },

        playNotificationSound: function() {
            try {
                const audio = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhBSuBzvLZiTYJG2S57OufUw==');
                audio.volume = 0.3;
                audio.play().catch(() => {});
            } catch (e) {}
        },

        showBrowserNotification: function(message, type) {
            if (!("Notification" in window)) return;

            if (Notification.permission === "granted") {
                new Notification("HOS Alert", {
                    body: message,
                    icon: '/favicon.ico',
                    badge: '/favicon.ico',
                    tag: 'hos-alert',
                    requireInteraction: type === 'error'
                });
            } else if (Notification.permission !== "denied") {
                Notification.requestPermission().then(function (permission) {
                    if (permission === "granted") {
                        new Notification("HOS Alert", {
                            body: message,
                            icon: '/favicon.ico'
                        });
                    }
                });
            }
        }
    };

    // Listen for Livewire notify events
    document.addEventListener('livewire:initialized', () => {
        Livewire.on('notify', (event) => {
            const data = Array.isArray(event) ? event[0] : event;
            HosNotifications.show(data.message, data.type || 'info');
        });
    });

    // Request notification permission on page load
    document.addEventListener('DOMContentLoaded', function() {
        if ("Notification" in window && Notification.permission === "default") {
            // Request permission after a short delay to avoid being intrusive
            setTimeout(() => {
                Notification.requestPermission();
            }, 3000);
        }
    });
</script>

<style>
    @keyframes slide-in {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    @keyframes slide-out {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }

    .animate-slide-in {
        animation: slide-in 0.3s ease-out forwards;
    }

    .animate-slide-out {
        animation: slide-out 0.3s ease-in forwards;
    }
</style>
@endpush
