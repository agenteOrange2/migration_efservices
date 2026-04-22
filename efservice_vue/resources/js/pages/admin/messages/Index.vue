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

interface MessageRow {
    id: number
    subject: string
    sender_name: string
    sender_email: string | null
    sender_type: string
    recipients_count: number
    delivered_count: number
    read_count: number
    priority: string
    status: string
    sent_at: string | null
    created_at: string | null
    can_edit: boolean
    can_delete: boolean
    can_resend: boolean
}

const props = defineProps<{
    messages: {
        data: MessageRow[]
        links: PaginationLink[]
        total: number
        last_page: number
    }
    filters: {
        search: string
        status: string
        priority: string
        date_from: string
        date_to: string
    }
    stats: {
        total: number
        sent: number
        draft: number
        failed: number
        sent_today: number
    }
}>()

const filters = reactive({ ...props.filters })

function applyFilters() {
    router.get(route('admin.messages.index'), {
        search: filters.search || undefined,
        status: filters.status || undefined,
        priority: filters.priority || undefined,
        date_from: filters.date_from || undefined,
        date_to: filters.date_to || undefined,
    }, {
        preserveState: true,
        replace: true,
    })
}

function resetFilters() {
    filters.search = ''
    filters.status = ''
    filters.priority = ''
    filters.date_from = ''
    filters.date_to = ''
    applyFilters()
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

function deleteMessage(message: MessageRow) {
    if (!confirm(`Delete "${message.subject}"? This cannot be undone.`)) return
    router.delete(route('admin.messages.destroy', message.id), { preserveScroll: true })
}

function duplicateMessage(message: MessageRow) {
    if (!confirm(`Create a duplicate of "${message.subject}" as a new draft?`)) return
    router.post(route('admin.messages.duplicate', message.id), {}, { preserveScroll: true })
}

function resendMessage(message: MessageRow) {
    if (!confirm(`Resend "${message.subject}" to all recipients?`)) return
    router.post(route('admin.messages.resend', message.id), {}, { preserveScroll: true })
}
</script>

<template>
    <Head title="Messages" />

    <div class="grid grid-cols-12 gap-6">
        <div class="col-span-12">
            <div class="box box--stacked p-6">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex items-center gap-4">
                        <div class="rounded-2xl border border-primary/20 bg-primary/10 p-3">
                            <Lucide icon="Mail" class="h-8 w-8 text-primary" />
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-slate-800">Messages</h1>
                            <p class="text-sm text-slate-500">Browse, resend, duplicate, and manage all admin communications in one place.</p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <Link :href="route('admin.messages.dashboard')">
                            <Button variant="outline-secondary" class="flex items-center gap-2">
                                <Lucide icon="BarChart3" class="h-4 w-4" />
                                Dashboard
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
            <div class="box box--stacked border border-primary/10 p-5">
                <p class="text-sm text-slate-500">Total</p>
                <p class="mt-2 text-3xl font-semibold text-primary">{{ stats.total }}</p>
            </div>
            <div class="box box--stacked border border-success/10 p-5">
                <p class="text-sm text-slate-500">Sent</p>
                <p class="mt-2 text-3xl font-semibold text-success">{{ stats.sent }}</p>
            </div>
            <div class="box box--stacked border border-warning/10 p-5">
                <p class="text-sm text-slate-500">Drafts</p>
                <p class="mt-2 text-3xl font-semibold text-warning">{{ stats.draft }}</p>
            </div>
            <div class="box box--stacked border border-danger/10 p-5">
                <p class="text-sm text-slate-500">Failed</p>
                <p class="mt-2 text-3xl font-semibold text-danger">{{ stats.failed }}</p>
                <p class="mt-2 text-xs text-slate-500">{{ stats.sent_today }} created today</p>
            </div>
        </div>

        <div class="col-span-12">
            <div class="box box--stacked p-6">
                <div class="mb-5 flex items-center gap-3">
                    <Lucide icon="Filter" class="h-5 w-5 text-primary" />
                    <h2 class="text-base font-semibold text-slate-800">Filter Messages</h2>
                </div>

                <div class="grid grid-cols-1 gap-4 lg:grid-cols-5">
                    <div class="lg:col-span-2">
                        <FormInput v-model="filters.search" type="text" placeholder="Search subject or content..." />
                    </div>
                    <div>
                        <FormSelect v-model="filters.status">
                            <option value="">All statuses</option>
                            <option value="draft">Draft</option>
                            <option value="sent">Sent</option>
                            <option value="failed">Failed</option>
                            <option value="delivered">Delivered</option>
                        </FormSelect>
                    </div>
                    <div>
                        <FormSelect v-model="filters.priority">
                            <option value="">All priorities</option>
                            <option value="low">Low</option>
                            <option value="normal">Normal</option>
                            <option value="high">High</option>
                        </FormSelect>
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
                    <div>
                        <FormInput v-model="filters.date_from" type="date" />
                    </div>
                    <div>
                        <FormInput v-model="filters.date_to" type="date" />
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12">
            <div class="box box--stacked overflow-hidden">
                <div class="flex items-center justify-between border-b border-slate-200/60 px-6 py-4">
                    <div>
                        <h2 class="text-base font-semibold text-slate-800">Messages List</h2>
                        <p class="text-sm text-slate-500">{{ messages.total }} total record<span v-if="messages.total !== 1">s</span></p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-slate-50/80">
                                <th class="px-6 py-3 text-xs font-medium uppercase text-slate-500">Subject</th>
                                <th class="px-6 py-3 text-xs font-medium uppercase text-slate-500">Sender</th>
                                <th class="px-6 py-3 text-xs font-medium uppercase text-slate-500">Recipients</th>
                                <th class="px-6 py-3 text-xs font-medium uppercase text-slate-500">Priority</th>
                                <th class="px-6 py-3 text-xs font-medium uppercase text-slate-500">Status</th>
                                <th class="px-6 py-3 text-xs font-medium uppercase text-slate-500">Date</th>
                                <th class="px-6 py-3 text-xs font-medium uppercase text-slate-500 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="message in messages.data" :key="message.id" class="border-t border-slate-100 hover:bg-slate-50/40">
                                <td class="px-6 py-4">
                                    <p class="font-medium text-slate-800">{{ message.subject }}</p>
                                    <p class="mt-1 text-xs text-slate-500">#{{ message.id }} · {{ message.created_at || 'Recently created' }}</p>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-sm font-medium text-slate-800">{{ message.sender_name }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ message.sender_email || 'No email' }}</p>
                                    <p class="mt-1 text-xs text-slate-400">{{ message.sender_type }}</p>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-sm text-slate-700">{{ message.recipients_count }} recipients</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ message.delivered_count }} delivered, {{ message.read_count }} read</p>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="rounded-full px-2.5 py-1 text-xs font-medium" :class="priorityBadgeClass(message.priority)">
                                        {{ titleCase(message.priority) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="rounded-full px-2.5 py-1 text-xs font-medium" :class="statusBadgeClass(message.status)">
                                        {{ titleCase(message.status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-500">
                                    {{ message.sent_at || 'Not sent yet' }}
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-end gap-2">
                                        <Link :href="route('admin.messages.show', message.id)" class="rounded-lg border border-slate-200 p-2 text-slate-500 transition hover:border-primary/30 hover:text-primary">
                                            <Lucide icon="Eye" class="h-4 w-4" />
                                        </Link>
                                        <Link v-if="message.can_edit" :href="route('admin.messages.edit', message.id)" class="rounded-lg border border-slate-200 p-2 text-slate-500 transition hover:border-warning/30 hover:text-warning">
                                            <Lucide icon="PenLine" class="h-4 w-4" />
                                        </Link>
                                        <button type="button" class="rounded-lg border border-slate-200 p-2 text-slate-500 transition hover:border-info/30 hover:text-info" @click="duplicateMessage(message)">
                                            <Lucide icon="Copy" class="h-4 w-4" />
                                        </button>
                                        <button v-if="message.can_resend" type="button" class="rounded-lg border border-slate-200 p-2 text-slate-500 transition hover:border-success/30 hover:text-success" @click="resendMessage(message)">
                                            <Lucide icon="Send" class="h-4 w-4" />
                                        </button>
                                        <button v-if="message.can_delete" type="button" class="rounded-lg border border-slate-200 p-2 text-slate-500 transition hover:border-danger/30 hover:text-danger" @click="deleteMessage(message)">
                                            <Lucide icon="Trash2" class="h-4 w-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="!messages.data.length">
                                <td colspan="7" class="px-6 py-14 text-center">
                                    <Lucide icon="MailX" class="mx-auto mb-3 h-12 w-12 text-slate-300" />
                                    <p class="text-sm text-slate-500">No messages matched the current filters.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="messages.last_page > 1" class="flex items-center justify-between border-t border-slate-200/60 px-6 py-4">
                    <span class="text-sm text-slate-500">{{ messages.total }} total records</span>
                    <div class="flex flex-wrap items-center gap-1">
                        <template v-for="link in messages.links" :key="link.label">
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
