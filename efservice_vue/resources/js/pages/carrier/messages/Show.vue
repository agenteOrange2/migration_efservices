<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import CarrierLayout from '@/layouts/CarrierLayout.vue'

declare function route(name: string, params?: any): string

defineOptions({ layout: CarrierLayout })

interface RecipientRow {
    id: number
    recipient_type: string
    name: string
    email: string
    delivery_status: string
    delivered_at: string | null
    read_at: string | null
}

interface StatusLogRow {
    id: number
    status: string
    notes: string | null
    created_at: string | null
}

interface MessageDetail {
    id: number
    subject: string
    message: string
    priority: string
    status: string
    direction: string
    sent_at: string | null
    created_at: string | null
    sender: {
        name: string
        email: string | null
        type: string
    }
    recipients: RecipientRow[]
    status_logs: StatusLogRow[]
    stats: {
        total: number
        delivered: number
        pending: number
        failed: number
        read: number
    }
    can_edit: boolean
    can_delete: boolean
    can_resend: boolean
}

const props = defineProps<{
    message: MessageDetail
}>()

function titleCase(value: string) {
    return value.replace(/_/g, ' ').replace(/\b\w/g, (char) => char.toUpperCase())
}

function statusBadgeClass(status: string) {
    if (status === 'sent') return 'bg-primary/10 text-primary'
    if (status === 'draft') return 'bg-slate-100 text-slate-600'
    return 'bg-slate-200 text-slate-700'
}

function priorityBadgeClass(priority: string) {
    if (priority === 'high') return 'bg-slate-200 text-slate-700'
    if (priority === 'normal') return 'bg-primary/10 text-primary'
    return 'bg-slate-100 text-slate-600'
}

function directionBadgeClass(direction: string) {
    return direction === 'sent'
        ? 'bg-primary/10 text-primary'
        : 'bg-slate-100 text-slate-600'
}

function recipientTypeLabel(type: string) {
    if (type === 'driver') return 'Driver'
    if (type === 'carrier') return 'Carrier'
    if (type === 'user') return 'Admin'
    if (type === 'email') return 'Email'
    return titleCase(type)
}

function duplicateMessage() {
    if (!confirm(`Create a duplicate of "${props.message.subject}"?`)) return
    router.post(route('carrier.messages.duplicate', props.message.id), {}, { preserveScroll: true })
}

function resendMessage() {
    if (!confirm(`Resend "${props.message.subject}" to all recipients?`)) return
    router.post(route('carrier.messages.resend', props.message.id), {}, { preserveScroll: true })
}

function deleteMessage() {
    if (!confirm(`Delete "${props.message.subject}"? This cannot be undone.`)) return
    router.delete(route('carrier.messages.destroy', props.message.id), { preserveScroll: true })
}
</script>

<template>
    <Head :title="message.subject" />

    <div class="grid grid-cols-12 gap-6">
        <div class="col-span-12">
            <div class="box box--stacked p-6">
                <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                    <div class="flex items-center gap-4">
                        <div class="rounded-2xl border border-primary/20 bg-primary/10 p-3">
                            <Lucide icon="MailOpen" class="h-8 w-8 text-primary" />
                        </div>
                        <div>
                            <div class="flex flex-wrap items-center gap-3">
                                <h1 class="text-2xl font-bold text-slate-800">{{ message.subject }}</h1>
                                <span class="rounded-full px-3 py-1 text-xs font-medium" :class="priorityBadgeClass(message.priority)">
                                    {{ titleCase(message.priority) }} Priority
                                </span>
                                <span class="rounded-full px-3 py-1 text-xs font-medium" :class="statusBadgeClass(message.status)">
                                    {{ titleCase(message.status) }}
                                </span>
                                <span class="rounded-full px-3 py-1 text-xs font-medium" :class="directionBadgeClass(message.direction)">
                                    {{ titleCase(message.direction) }}
                                </span>
                            </div>
                            <p class="mt-1 text-sm text-slate-500">Created {{ message.created_at || 'recently' }} by {{ message.sender.name }}</p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <Link :href="route('carrier.messages.index')">
                            <Button variant="outline-secondary" class="flex items-center gap-2">
                                <Lucide icon="ArrowLeft" class="h-4 w-4" />
                                Back
                            </Button>
                        </Link>
                        <Link v-if="message.can_edit" :href="route('carrier.messages.edit', message.id)">
                            <Button variant="primary" class="flex items-center gap-2">
                                <Lucide icon="PenLine" class="h-4 w-4" />
                                Edit Draft
                            </Button>
                        </Link>
                        <Button variant="outline-secondary" class="flex items-center gap-2" @click="duplicateMessage">
                            <Lucide icon="Copy" class="h-4 w-4" />
                            Duplicate
                        </Button>
                        <Button v-if="message.can_resend" variant="outline-secondary" class="flex items-center gap-2" @click="resendMessage">
                            <Lucide icon="Send" class="h-4 w-4" />
                            Resend
                        </Button>
                        <Button v-if="message.can_delete" variant="outline-secondary" class="flex items-center gap-2" @click="deleteMessage">
                            <Lucide icon="Trash2" class="h-4 w-4" />
                            Delete
                        </Button>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12 xl:col-span-8 space-y-6">
            <div class="box box--stacked p-6">
                <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs uppercase tracking-wide text-slate-500">Sender</p>
                        <p class="mt-2 text-sm font-semibold text-slate-800">{{ message.sender.name }}</p>
                        <p class="mt-1 text-sm text-slate-500">{{ message.sender.email || 'No email' }}</p>
                        <p class="mt-1 text-xs text-slate-400">{{ message.sender.type }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs uppercase tracking-wide text-slate-500">Direction</p>
                        <p class="mt-2 text-sm font-semibold text-slate-800">{{ titleCase(message.direction) }}</p>
                        <p class="mt-1 text-sm text-slate-500">Status: {{ titleCase(message.status) }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs uppercase tracking-wide text-slate-500">Delivery</p>
                        <p class="mt-2 text-sm font-semibold text-slate-800">{{ message.sent_at || 'Not sent yet' }}</p>
                        <p class="mt-1 text-sm text-slate-500">Created {{ message.created_at || 'recently' }}</p>
                    </div>
                </div>

                <div class="mt-5">
                    <p class="text-sm font-semibold text-slate-700">Message Body</p>
                    <div class="mt-3 rounded-2xl border border-slate-200 bg-slate-50 p-5">
                        <p class="whitespace-pre-wrap text-sm leading-6 text-slate-700">{{ message.message }}</p>
                    </div>
                </div>
            </div>

            <div class="box box--stacked overflow-hidden">
                <div class="border-b border-slate-200/60 px-6 py-4">
                    <h2 class="text-base font-semibold text-slate-800">Recipients</h2>
                    <p class="text-sm text-slate-500">{{ message.recipients.length }} total recipient<span v-if="message.recipients.length !== 1">s</span></p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-slate-50/80">
                                <th class="px-6 py-3 text-xs font-medium uppercase text-slate-500">Recipient</th>
                                <th class="px-6 py-3 text-xs font-medium uppercase text-slate-500">Type</th>
                                <th class="px-6 py-3 text-xs font-medium uppercase text-slate-500">Delivery</th>
                                <th class="px-6 py-3 text-xs font-medium uppercase text-slate-500">Delivered</th>
                                <th class="px-6 py-3 text-xs font-medium uppercase text-slate-500">Read</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="recipient in message.recipients" :key="recipient.id" class="border-t border-slate-100">
                                <td class="px-6 py-4">
                                    <p class="font-medium text-slate-800">{{ recipient.name }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ recipient.email }}</p>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">
                                        {{ recipientTypeLabel(recipient.recipient_type) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="rounded-full px-2.5 py-1 text-xs font-medium" :class="statusBadgeClass(recipient.delivery_status)">
                                        {{ titleCase(recipient.delivery_status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-500">{{ recipient.delivered_at || '—' }}</td>
                                <td class="px-6 py-4 text-sm text-slate-500">{{ recipient.read_at || '—' }}</td>
                            </tr>
                            <tr v-if="!message.recipients.length">
                                <td colspan="5" class="px-6 py-12 text-center text-sm text-slate-500">
                                    No recipients were assigned to this message.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-span-12 xl:col-span-4 space-y-6">
            <div class="box box--stacked p-6">
                <h2 class="text-base font-semibold text-slate-800">Message Statistics</h2>
                <div class="mt-4 grid grid-cols-2 gap-3">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs text-slate-500">Total</p>
                        <p class="mt-1 text-2xl font-semibold text-slate-800">{{ message.stats.total }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs text-slate-500">Delivered</p>
                        <p class="mt-1 text-2xl font-semibold text-slate-800">{{ message.stats.delivered }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs text-slate-500">Pending</p>
                        <p class="mt-1 text-2xl font-semibold text-slate-800">{{ message.stats.pending }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs text-slate-500">Failed</p>
                        <p class="mt-1 text-2xl font-semibold text-slate-800">{{ message.stats.failed }}</p>
                    </div>
                </div>
                <div class="mt-3 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <p class="text-xs text-slate-500">Read</p>
                    <p class="mt-1 text-2xl font-semibold text-slate-800">{{ message.stats.read }}</p>
                </div>
            </div>

            <div class="box box--stacked p-6">
                <h2 class="text-base font-semibold text-slate-800">Status History</h2>
                <div v-if="message.status_logs.length" class="mt-4 space-y-4">
                    <div v-for="log in message.status_logs" :key="log.id" class="flex items-start gap-3">
                        <div class="mt-1 rounded-full bg-primary/10 p-2">
                            <Lucide icon="Clock3" class="h-4 w-4 text-primary" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-slate-800">{{ titleCase(log.status) }}</p>
                            <p v-if="log.notes" class="mt-1 text-sm text-slate-600">{{ log.notes }}</p>
                            <p class="mt-1 text-xs text-slate-400">{{ log.created_at || 'Unknown date' }}</p>
                        </div>
                    </div>
                </div>
                <div v-else class="mt-4 rounded-2xl border border-dashed border-slate-300 p-6 text-center text-sm text-slate-500">
                    No status history available.
                </div>
            </div>
        </div>
    </div>
</template>
