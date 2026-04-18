<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { reactive } from 'vue'
import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import { FormInput, FormSelect } from '@/components/Base/Form'
import RazeLayout from '@/layouts/RazeLayout.vue'

declare function route(name: string, params?: any): string

defineOptions({ layout: RazeLayout })

interface PaginationLink {
    url: string | null
    label: string
    active: boolean
}

interface NotificationItem {
    id: string
    type: string
    type_label: string
    title: string
    message: string
    icon: string
    url: string | null
    category: string | null
    category_label: string | null
    level: string | null
    read_at: string | null
    created_at: string
    created_at_formatted: string | null
    created_at_human: string | null
    is_unread: boolean
    data: Record<string, unknown>
}

const props = defineProps<{
    notifications: {
        data: NotificationItem[]
        links: PaginationLink[]
        total: number
        last_page: number
    }
    filters: {
        search: string
        status: string
        type: string
        date_from: string
        date_to: string
    }
    stats: {
        total: number
        unread: number
        read: number
        today: number
        types: number
    }
    availableTypes: Array<{
        id: string
        name: string
        count: number
    }>
}>()

const filters = reactive({ ...props.filters })

function applyFilters() {
    router.get(route('driver.notifications.index'), {
        search: filters.search || undefined,
        status: filters.status || undefined,
        type: filters.type || undefined,
        date_from: filters.date_from || undefined,
        date_to: filters.date_to || undefined,
    }, {
        preserveState: true,
        replace: true,
    })
}

function resetFilters() {
    filters.search = ''
    filters.status = 'all'
    filters.type = ''
    filters.date_from = ''
    filters.date_to = ''
    applyFilters()
}

function markAsRead(notification: NotificationItem) {
    router.post(route('driver.notifications.mark-as-read', notification.id), {}, {
        preserveScroll: true,
    })
}

function markAsUnread(notification: NotificationItem) {
    router.post(route('driver.notifications.mark-as-unread', notification.id), {}, {
        preserveScroll: true,
    })
}

function markAllAsRead() {
    router.post(route('driver.notifications.mark-all-read'), {}, {
        preserveScroll: true,
    })
}

function deleteNotification(notification: NotificationItem) {
    if (!confirm(`Delete "${notification.title}"? This cannot be undone.`)) return

    router.delete(route('driver.notifications.destroy', notification.id), {
        preserveScroll: true,
    })
}

function deleteFiltered() {
    if (!confirm('Delete every notification that matches the current filters?')) return

    router.visit(route('driver.notifications.delete-all'), {
        method: 'delete',
        data: {
            search: filters.search || undefined,
            status: filters.status || undefined,
            type: filters.type || undefined,
            date_from: filters.date_from || undefined,
            date_to: filters.date_to || undefined,
        },
        preserveScroll: true,
    })
}

function openNotification(notification: NotificationItem) {
    if (!notification.url) return

    if (notification.is_unread) {
        router.post(route('driver.notifications.mark-as-read', notification.id), {}, {
            preserveScroll: true,
            onSuccess: () => router.visit(notification.url as string),
        })
        return
    }

    router.visit(notification.url)
}

function statusBadgeClass(notification: NotificationItem) {
    return notification.is_unread
        ? 'bg-primary/10 text-primary'
        : 'bg-slate-100 text-slate-600'
}

function levelBadgeClass(level?: string | null) {
    if (level === 'success') return 'bg-success/10 text-success'
    if (level === 'warning') return 'bg-warning/10 text-warning'
    if (level === 'error') return 'bg-danger/10 text-danger'
    return 'bg-slate-100 text-slate-600'
}
</script>

<template>
    <Head title="Notifications" />

    <div class="grid grid-cols-12 gap-6">
        <div class="col-span-12">
            <div class="box box--stacked p-6">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex items-center gap-4">
                        <div class="rounded-2xl border border-primary/20 bg-primary/10 p-3">
                            <Lucide icon="Bell" class="h-8 w-8 text-primary" />
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-slate-800">Notifications</h1>
                            <p class="text-sm text-slate-500">Review compliance alerts, training updates, messages, and important activity tied to your driver account.</p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <Button variant="outline-secondary" class="flex items-center gap-2" :disabled="stats.unread === 0" @click="markAllAsRead">
                            <Lucide icon="CheckCheck" class="h-4 w-4" />
                            Mark All as Read
                        </Button>
                        <Button variant="outline-secondary" class="flex items-center gap-2" :disabled="stats.total === 0" @click="deleteFiltered">
                            <Lucide icon="Trash2" class="h-4 w-4" />
                            Delete Filtered
                        </Button>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-5">
            <div class="box box--stacked p-5">
                <p class="text-sm text-slate-500">Total</p>
                <p class="mt-2 text-3xl font-semibold text-slate-800">{{ stats.total }}</p>
            </div>
            <div class="box box--stacked p-5">
                <p class="text-sm text-slate-500">Unread</p>
                <p class="mt-2 text-3xl font-semibold text-slate-800">{{ stats.unread }}</p>
            </div>
            <div class="box box--stacked p-5">
                <p class="text-sm text-slate-500">Read</p>
                <p class="mt-2 text-3xl font-semibold text-slate-800">{{ stats.read }}</p>
            </div>
            <div class="box box--stacked p-5">
                <p class="text-sm text-slate-500">Today</p>
                <p class="mt-2 text-3xl font-semibold text-slate-800">{{ stats.today }}</p>
            </div>
            <div class="box box--stacked p-5">
                <p class="text-sm text-slate-500">Types</p>
                <p class="mt-2 text-3xl font-semibold text-slate-800">{{ stats.types }}</p>
            </div>
        </div>

        <div class="col-span-12">
            <div class="box box--stacked p-6">
                <div class="mb-5 flex items-center gap-3">
                    <Lucide icon="Filter" class="h-5 w-5 text-primary" />
                    <h2 class="text-base font-semibold text-slate-800">Filter Notifications</h2>
                </div>

                <div class="grid grid-cols-1 gap-4 lg:grid-cols-6">
                    <div class="lg:col-span-2">
                        <FormInput v-model="filters.search" type="text" placeholder="Search title, message, or type..." />
                    </div>
                    <div>
                        <FormSelect v-model="filters.status">
                            <option value="all">All statuses</option>
                            <option value="unread">Unread</option>
                            <option value="read">Read</option>
                        </FormSelect>
                    </div>
                    <div>
                        <FormSelect v-model="filters.type">
                            <option value="">All types</option>
                            <option v-for="item in availableTypes" :key="item.id" :value="item.id">
                                {{ item.name }} ({{ item.count }})
                            </option>
                        </FormSelect>
                    </div>
                    <div>
                        <FormInput v-model="filters.date_from" type="date" />
                    </div>
                    <div>
                        <FormInput v-model="filters.date_to" type="date" />
                    </div>
                    <div class="flex items-center gap-3">
                        <Button variant="primary" class="flex items-center gap-2" @click="applyFilters">
                            <Lucide icon="Search" class="h-4 w-4" />
                            Apply
                        </Button>
                        <Button variant="outline-secondary" class="flex items-center gap-2" @click="resetFilters">
                            <Lucide icon="RotateCcw" class="h-4 w-4" />
                            Clear
                        </Button>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12">
            <div class="box box--stacked overflow-hidden">
                <div class="flex items-center justify-between border-b border-slate-200/60 px-6 py-4">
                    <div>
                        <h2 class="text-base font-semibold text-slate-800">Notification Center</h2>
                        <p class="text-sm text-slate-500">{{ notifications.total }} total record<span v-if="notifications.total !== 1">s</span></p>
                    </div>
                </div>

                <div v-if="notifications.data.length" class="divide-y divide-slate-100">
                    <div v-for="notification in notifications.data" :key="notification.id" class="px-6 py-5 transition hover:bg-slate-50/50">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div class="flex min-w-0 gap-4">
                                <div class="mt-0.5 flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl" :class="notification.is_unread ? 'bg-primary/10 text-primary' : 'bg-slate-100 text-slate-500'">
                                    <Lucide :icon="notification.icon" class="h-5 w-5" />
                                </div>

                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h3 class="text-base font-semibold text-slate-800">{{ notification.title }}</h3>
                                        <span class="rounded-full px-2.5 py-1 text-xs font-medium" :class="statusBadgeClass(notification)">
                                            {{ notification.is_unread ? 'Unread' : 'Read' }}
                                        </span>
                                        <span class="rounded-full px-2.5 py-1 text-xs font-medium" :class="levelBadgeClass(notification.level)">
                                            {{ notification.category_label || notification.type_label }}
                                        </span>
                                    </div>

                                    <p class="mt-2 text-sm leading-6 text-slate-600">{{ notification.message }}</p>

                                    <div class="mt-3 flex flex-wrap items-center gap-3 text-xs text-slate-500">
                                        <span>{{ notification.type_label }}</span>
                                        <span>{{ notification.created_at_formatted || notification.created_at_human }}</span>
                                        <span v-if="notification.read_at">Read</span>
                                    </div>
                                </div>
                            </div>

                            <div class="flex shrink-0 flex-wrap items-center gap-2">
                                <Button
                                    v-if="notification.url"
                                    variant="outline-secondary"
                                    class="flex items-center gap-2"
                                    @click="openNotification(notification)"
                                >
                                    <Lucide icon="ArrowUpRight" class="h-4 w-4" />
                                    Open
                                </Button>
                                <Button
                                    v-if="notification.is_unread"
                                    variant="outline-secondary"
                                    class="flex items-center gap-2"
                                    @click="markAsRead(notification)"
                                >
                                    <Lucide icon="Check" class="h-4 w-4" />
                                    Mark Read
                                </Button>
                                <Button
                                    v-else
                                    variant="outline-secondary"
                                    class="flex items-center gap-2"
                                    @click="markAsUnread(notification)"
                                >
                                    <Lucide icon="RotateCcw" class="h-4 w-4" />
                                    Mark Unread
                                </Button>
                                <Button
                                    variant="outline-secondary"
                                    class="flex items-center gap-2"
                                    @click="deleteNotification(notification)"
                                >
                                    <Lucide icon="Trash2" class="h-4 w-4" />
                                    Delete
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-else class="px-6 py-16 text-center">
                    <Lucide icon="BellOff" class="mx-auto mb-4 h-12 w-12 text-slate-300" />
                    <p class="text-base font-medium text-slate-700">No notifications matched the current filters.</p>
                    <p class="mt-2 text-sm text-slate-500">Try clearing the filters or wait for the next system event.</p>
                </div>

                <div v-if="notifications.last_page > 1" class="flex items-center justify-between border-t border-slate-200/60 px-6 py-4">
                    <span class="text-sm text-slate-500">{{ notifications.total }} total records</span>
                    <div class="flex flex-wrap items-center gap-1">
                        <template v-for="link in notifications.links" :key="link.label">
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                class="rounded-lg px-3 py-1.5 text-sm transition"
                                :class="link.active ? 'bg-primary text-white' : 'text-slate-600 hover:bg-slate-100'"
                                v-html="link.label"
                            />
                            <span v-else class="px-3 py-1.5 text-sm text-slate-300" v-html="link.label" />
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
