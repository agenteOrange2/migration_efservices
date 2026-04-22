<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import RazeLayout from '@/layouts/RazeLayout.vue'

declare function route(name: string, params?: any): string

defineOptions({ layout: RazeLayout })

interface StatsPayload {
    total: number
    sent: number
    draft: number
    failed: number
    delivered: number
    sent_today: number
    sent_this_week: number
    sent_this_month: number
}

interface RecentMessageRow {
    id: number
    subject: string
    sender_name: string
    sender_type: string
    recipients_count: number
    delivered_count: number
    read_count: number
    priority: string
    status: string
    sent_at: string | null
    can_edit: boolean
}

const props = defineProps<{
    stats: StatsPayload
    statusDistribution: Record<string, number>
    priorityDistribution: Record<string, number>
    senderTypeDistribution: Record<string, number>
    deliveryStats: {
        total: number
        delivered: number
        pending: number
        failed: number
        read: number
    }
    recentMessages: RecentMessageRow[]
}>()

function asEntries(record: Record<string, number>) {
    return Object.entries(record).map(([label, value]) => ({ label, value }))
}

function percentage(value: number, total: number) {
    if (!total) return 0
    return Math.round((value / total) * 100)
}

function titleCase(value: string) {
    return value.replace(/_/g, ' ').replace(/\b\w/g, (char) => char.toUpperCase())
}

function statusBadgeClass(status: string) {
    if (status === 'sent' || status === 'delivered') return 'bg-success/10 text-success'
    if (status === 'draft' || status === 'pending') return 'bg-warning/10 text-warning'
    if (status === 'failed') return 'bg-danger/10 text-danger'
    return 'bg-info/10 text-info'
}

function priorityBadgeClass(priority: string) {
    if (priority === 'high') return 'bg-danger/10 text-danger'
    if (priority === 'normal') return 'bg-primary/10 text-primary'
    return 'bg-info/10 text-info'
}

function statusBarClass(status: string) {
    if (status === 'sent' || status === 'delivered') return 'bg-success'
    if (status === 'draft' || status === 'pending') return 'bg-warning'
    if (status === 'failed') return 'bg-danger'
    return 'bg-info'
}

function priorityCardClass(priority: string) {
    if (priority === 'high') return 'border-danger/10 bg-danger/5'
    if (priority === 'normal') return 'border-primary/10 bg-primary/5'
    return 'border-info/10 bg-info/5'
}

const deliveryRate = percentage(props.deliveryStats.delivered, props.deliveryStats.total)
const readRate = percentage(props.deliveryStats.read, props.deliveryStats.total)
</script>

<template>
    <Head title="Messages Dashboard" />

    <div class="grid grid-cols-12 gap-6">
        <div class="col-span-12">
            <div class="box box--stacked p-6">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex items-center gap-4">
                        <div class="rounded-2xl border border-primary/20 bg-primary/10 p-3">
                            <Lucide icon="MessagesSquare" class="h-8 w-8 text-primary" />
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-slate-800">Messages Dashboard</h1>
                            <p class="text-sm text-slate-500">Monitor delivery health, message volume, and recent activity across the admin message center.</p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <Link :href="route('admin.messages.index')">
                            <Button variant="outline-secondary" class="flex items-center gap-2">
                                <Lucide icon="List" class="h-4 w-4" />
                                All Messages
                            </Button>
                        </Link>
                        <Link :href="route('admin.messages.create')">
                            <Button variant="primary" class="flex items-center gap-2">
                                <Lucide icon="Plus" class="h-4 w-4" />
                                New Message
                            </Button>
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="box box--stacked rounded-2xl border border-primary/10 p-5">
                <p class="text-sm text-slate-500">Total Messages</p>
                <p class="mt-2 text-3xl font-semibold text-primary">{{ stats.total }}</p>
                <p class="mt-2 text-xs text-slate-500">{{ stats.sent_this_month }} created this month</p>
            </div>
            <div class="box box--stacked rounded-2xl border border-success/10 p-5">
                <p class="text-sm text-slate-500">Sent</p>
                <p class="mt-2 text-3xl font-semibold text-success">{{ stats.sent }}</p>
                <p class="mt-2 text-xs text-slate-500">{{ stats.sent_today }} today, {{ stats.sent_this_week }} this week</p>
            </div>
            <div class="box box--stacked rounded-2xl border border-warning/10 p-5">
                <p class="text-sm text-slate-500">Drafts</p>
                <p class="mt-2 text-3xl font-semibold text-warning">{{ stats.draft }}</p>
                <p class="mt-2 text-xs text-slate-500">Messages still editable before delivery.</p>
            </div>
            <div class="box box--stacked rounded-2xl border border-info/10 p-5">
                <p class="text-sm text-slate-500">Delivery Rate</p>
                <p class="mt-2 text-3xl font-semibold text-info">{{ deliveryRate }}%</p>
                <p class="mt-2 text-xs text-slate-500">{{ deliveryStats.delivered }} of {{ deliveryStats.total }} recipient rows delivered</p>
            </div>
        </div>

        <div class="col-span-12 xl:col-span-8 space-y-6">
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <div class="box box--stacked p-6">
                    <div class="flex items-center gap-3">
                        <Lucide icon="PieChart" class="h-5 w-5 text-primary" />
                        <h2 class="text-base font-semibold text-slate-800">Status Distribution</h2>
                    </div>
                    <div class="mt-5 space-y-4">
                        <div v-for="item in asEntries(statusDistribution)" :key="item.label">
                            <div class="mb-2 flex items-center justify-between gap-3">
                                <p class="text-sm font-medium text-slate-700">{{ titleCase(item.label) }}</p>
                                <p class="text-sm text-slate-500">{{ item.value }} / {{ percentage(item.value, stats.total) }}%</p>
                            </div>
                            <div class="h-2 rounded-full bg-slate-100">
                                <div class="h-2 rounded-full" :class="statusBarClass(item.label)" :style="{ width: `${percentage(item.value, stats.total)}%` }" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="box box--stacked p-6">
                    <div class="flex items-center gap-3">
                        <Lucide icon="ShieldCheck" class="h-5 w-5 text-primary" />
                        <h2 class="text-base font-semibold text-slate-800">Delivery Overview</h2>
                    </div>
                    <div class="mt-5 grid grid-cols-2 gap-4">
                        <div class="rounded-2xl border border-success/10 bg-success/5 p-4">
                            <p class="text-xs uppercase tracking-wide text-slate-500">Delivered</p>
                            <p class="mt-2 text-2xl font-semibold text-success">{{ deliveryStats.delivered }}</p>
                        </div>
                        <div class="rounded-2xl border border-warning/10 bg-warning/5 p-4">
                            <p class="text-xs uppercase tracking-wide text-slate-500">Pending</p>
                            <p class="mt-2 text-2xl font-semibold text-warning">{{ deliveryStats.pending }}</p>
                        </div>
                        <div class="rounded-2xl border border-danger/10 bg-danger/5 p-4">
                            <p class="text-xs uppercase tracking-wide text-slate-500">Failed</p>
                            <p class="mt-2 text-2xl font-semibold text-danger">{{ deliveryStats.failed }}</p>
                        </div>
                        <div class="rounded-2xl border border-info/10 bg-info/5 p-4">
                            <p class="text-xs uppercase tracking-wide text-slate-500">Read</p>
                            <p class="mt-2 text-2xl font-semibold text-info">{{ deliveryStats.read }}</p>
                        </div>
                    </div>

                    <div class="mt-5 space-y-4 rounded-2xl border border-dashed border-slate-300 p-4">
                        <div>
                            <div class="mb-2 flex items-center justify-between">
                                <span class="text-sm font-medium text-slate-700">Delivered Rate</span>
                                <span class="text-sm text-slate-500">{{ deliveryRate }}%</span>
                            </div>
                            <div class="h-2 rounded-full bg-slate-100">
                                <div class="h-2 rounded-full bg-primary" :style="{ width: `${deliveryRate}%` }" />
                            </div>
                        </div>
                        <div>
                            <div class="mb-2 flex items-center justify-between">
                                <span class="text-sm font-medium text-slate-700">Read Rate</span>
                                <span class="text-sm text-slate-500">{{ readRate }}%</span>
                            </div>
                            <div class="h-2 rounded-full bg-slate-100">
                                <div class="h-2 rounded-full bg-info" :style="{ width: `${readRate}%` }" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="box box--stacked p-0 overflow-hidden">
                <div class="flex items-center justify-between border-b border-slate-200/60 px-6 py-4">
                    <div>
                        <h2 class="text-base font-semibold text-slate-800">Recent Messages</h2>
                        <p class="text-sm text-slate-500">Latest activity from the admin messaging module.</p>
                    </div>
                    <Link :href="route('admin.messages.index')">
                        <Button variant="outline-secondary" class="flex items-center gap-2">
                            <Lucide icon="ArrowRight" class="h-4 w-4" />
                            View All
                        </Button>
                    </Link>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-slate-50/80">
                                <th class="px-6 py-3 text-xs font-medium uppercase text-slate-500">Subject</th>
                                <th class="px-6 py-3 text-xs font-medium uppercase text-slate-500">Sender</th>
                                <th class="px-6 py-3 text-xs font-medium uppercase text-slate-500">Recipients</th>
                                <th class="px-6 py-3 text-xs font-medium uppercase text-slate-500">Status</th>
                                <th class="px-6 py-3 text-xs font-medium uppercase text-slate-500">Priority</th>
                                <th class="px-6 py-3 text-xs font-medium uppercase text-slate-500 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="message in recentMessages" :key="message.id" class="border-t border-slate-100">
                                <td class="px-6 py-4">
                                    <p class="font-medium text-slate-800">{{ message.subject }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ message.sent_at || 'Created recently' }}</p>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-sm font-medium text-slate-800">{{ message.sender_name }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ message.sender_type }}</p>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-sm text-slate-700">{{ message.recipients_count }} recipients</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ message.delivered_count }} delivered, {{ message.read_count }} read</p>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="rounded-full px-2.5 py-1 text-xs font-medium" :class="statusBadgeClass(message.status)">
                                        {{ titleCase(message.status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="rounded-full px-2.5 py-1 text-xs font-medium" :class="priorityBadgeClass(message.priority)">
                                        {{ titleCase(message.priority) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <Link :href="route('admin.messages.show', message.id)" class="rounded-lg border border-slate-200 p-2 text-slate-500 transition hover:border-primary/30 hover:text-primary">
                                            <Lucide icon="Eye" class="h-4 w-4" />
                                        </Link>
                                        <Link v-if="message.can_edit" :href="route('admin.messages.edit', message.id)" class="rounded-lg border border-slate-200 p-2 text-slate-500 transition hover:border-warning/30 hover:text-warning">
                                            <Lucide icon="PenLine" class="h-4 w-4" />
                                        </Link>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!recentMessages.length">
                                <td colspan="6" class="px-6 py-12 text-center text-sm text-slate-500">
                                    No message activity yet.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-span-12 xl:col-span-4 space-y-6">
            <div class="box box--stacked p-6">
                <h2 class="text-base font-semibold text-slate-800">Priority Mix</h2>
                <div class="mt-4 space-y-4">
                    <div v-for="item in asEntries(priorityDistribution)" :key="item.label" class="rounded-2xl border p-4" :class="priorityCardClass(item.label)">
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-medium text-slate-700">{{ titleCase(item.label) }}</p>
                            <p class="text-sm font-medium" :class="priorityBadgeClass(item.label)">{{ item.value }}</p>
                        </div>
                        <p class="mt-2 text-xs text-slate-500">{{ percentage(item.value, stats.total) }}% of all messages</p>
                    </div>
                </div>
            </div>

            <div class="box box--stacked p-6">
                <h2 class="text-base font-semibold text-slate-800">Sender Types</h2>
                <div class="mt-4 space-y-4">
                    <div v-for="item in asEntries(senderTypeDistribution)" :key="item.label">
                        <div class="mb-2 flex items-center justify-between gap-3">
                            <p class="text-sm font-medium text-slate-700">{{ item.label }}</p>
                            <p class="text-sm text-slate-500">{{ item.value }}</p>
                        </div>
                        <div class="h-2 rounded-full bg-slate-100">
                            <div class="h-2 rounded-full bg-primary" :style="{ width: `${percentage(item.value, stats.total)}%` }" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
