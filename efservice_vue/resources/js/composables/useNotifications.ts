import { computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import axios from 'axios'
import type { AppNotification } from '@/types/models'

export function useNotifications() {
    const page = usePage()

    const notifications = computed(
        () => (page.props.notifications ?? []) as AppNotification[],
    )

    const unreadCount = computed(
        () => (page.props.unreadNotificationsCount ?? 0) as number,
    )

    const hasUnread = computed(() => unreadCount.value > 0)

    async function markAsRead(notificationId: string) {
        await axios.post(`/api/notifications/${notificationId}/read`)
        router.reload({ only: ['notifications', 'unreadNotificationsCount'] })
    }

    async function markAsUnread(notificationId: string) {
        await axios.post(`/api/notifications/${notificationId}/unread`)
        router.reload({ only: ['notifications', 'unreadNotificationsCount'] })
    }

    async function markAllAsRead() {
        await axios.post('/api/notifications/read-all')
        router.reload({ only: ['notifications', 'unreadNotificationsCount'] })
    }

    async function deleteNotification(notificationId: string) {
        await axios.delete(`/api/notifications/${notificationId}`)
        router.reload({ only: ['notifications', 'unreadNotificationsCount'] })
    }

    function refreshNotifications() {
        router.reload({ only: ['notifications', 'unreadNotificationsCount'] })
    }

    return {
        notifications,
        unreadCount,
        hasUnread,
        markAsRead,
        markAsUnread,
        markAllAsRead,
        deleteNotification,
        refreshNotifications,
    }
}
