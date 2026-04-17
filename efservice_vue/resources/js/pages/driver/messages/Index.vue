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
    message_id: number
    subject: string
    preview: string
    sender_name: string
    sender_email: string | null
    sender_type: string
    priority: string
    status: string
    delivery_status: string
    is_read: boolean
    sent_at: string | null
    sent_at_relative: string | null
    read_at: string | null
}

const props = defineProps<{
    driver: {
        id: number
        full_name: string
        carrier_name: string | null
    }
    filters: {
        search: string
        read_status: string
        priority: string
    }
    stats: {
        total: number
        unread: number
        high_priority: number
    }
    messages: {
        data: MessageRow[]
        links: PaginationLink[]
        total: number
        last_page: number
    }
}>()

const filters = reactive({ ...props.filters })

function applyFilters() {
    router.get(route('driver.messages.index'), {
        search: filters.search || undefined,
        read_status: filters.read_status || undefined,
        priority: filters.priority || undefined,
    }, {
        preserveState: true,
        replace: true,
    })
}

function clearFilters() {
    filters.search = ''
    filters.read_status = ''
    filters.priority = ''
    applyFilters()
}

function titleCase(value: string) {
    return value.replace(/_/g, ' ').replace(/\b\w/g, (char) => char.toUpperCase())
}

function priorityClass(priority: string) {
    if (priority === 'high') return 'bg-slate-200 text-slate-700'
    if (priority === 'normal') return 'bg-primary/10 text-primary'
    return 'bg-slate-100 text-slate-600'
}

function senderClass(type: string) {
    if (type === 'Admin') return 'bg-primary/10 text-primary'
    if (type === 'Carrier') return 'bg-slate-100 text-slate-700'
    return 'bg-slate-100 text-slate-600'
}

function openMessage(message: MessageRow) {
    router.visit(route('driver.messages.show', message.id))
}
</script>

<template>
    <Head title="My Messages" />

    <div class="grid grid-cols-12 gap-6">
        <div class="col-span-12">
            <div class="box box--stacked p-6">
                <div class="flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between">
                    <div class="flex items-start gap-4">
                        <div class="rounded-2xl border border-primary/20 bg-primary/10 p-3">
                            <Lucide icon="Mail" class="h-8 w-8 text-primary" />
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-slate-800">My Messages</h1>
                            <p class="mt-1 text-slate-500">Review important messages from admin and your carrier, then open the conversation to reply when needed.</p>
                            <p class="mt-2 text-sm text-slate-500">
                                Driver: <span class="font-medium text-slate-700">{{ driver.full_name }}</span>
                                <span v-if="driver.carrier_name"> · Carrier: <span class="font-medium text-slate-700">{{ driver.carrier_name }}</span></span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12 grid grid-cols-1 gap-4 md:grid-cols-3">
            <div class="box box--stacked p-5">
                <p class="text-sm text-slate-500">Total Messages</p>
                <p class="mt-2 text-3xl font-semibold text-slate-800">{{ stats.total }}</p>
            </div>
            <div class="box box--stacked p-5">
                <p class="text-sm text-slate-500">Unread</p>
                <p class="mt-2 text-3xl font-semibold text-slate-800">{{ stats.unread }}</p>
            </div>
            <div class="box box--stacked p-5">
                <p class="text-sm text-slate-500">High Priority</p>
                <p class="mt-2 text-3xl font-semibold text-slate-800">{{ stats.high_priority }}</p>
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
                        <FormInput
                            v-model="filters.search"
                            type="text"
                            placeholder="Search subject or message..."
                            @keyup.enter="applyFilters"
                        />
                    </div>
                    <div>
                        <FormSelect v-model="filters.read_status">
                            <option value="">All messages</option>
                            <option value="unread">Unread</option>
                            <option value="read">Read</option>
                        </FormSelect>
                    </div>
                    <div>
                        <FormSelect v-model="filters.priority">
                            <option value="">All priorities</option>
                            <option value="high">High</option>
                            <option value="normal">Normal</option>
                            <option value="low">Low</option>
                        </FormSelect>
                    </div>
                    <div class="flex items-center gap-3">
                        <Button variant="primary" class="flex items-center gap-2" @click="applyFilters">
                            <Lucide icon="Search" class="h-4 w-4" />
                            Apply
                        </Button>
                        <Button variant="outline-secondary" class="flex items-center gap-2" @click="clearFilters">
                            <Lucide icon="RotateCcw" class="h-4 w-4" />
                            Clear
                        </Button>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12">
            <div v-if="messages.data.length" class="space-y-4">
                <button
                    v-for="message in messages.data"
                    :key="message.id"
                    type="button"
                    class="box box--stacked w-full border p-5 text-left transition hover:shadow-md"
                    :class="message.is_read ? 'border-slate-200 bg-white' : 'border-primary/20 bg-primary/5'"
                    @click="openMessage(message)"
                >
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="text-lg font-semibold text-slate-800">{{ message.subject }}</h2>
                                <span class="rounded-full px-2.5 py-1 text-xs font-medium" :class="priorityClass(message.priority)">
                                    {{ titleCase(message.priority) }}
                                </span>
                                <span class="rounded-full px-2.5 py-1 text-xs font-medium" :class="senderClass(message.sender_type)">
                                    {{ message.sender_type }}
                                </span>
                                <span v-if="!message.is_read" class="rounded-full bg-primary px-2.5 py-1 text-xs font-medium text-white">
                                    Unread
                                </span>
                            </div>

                            <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-slate-500">
                                <span>From <span class="font-medium text-slate-700">{{ message.sender_name }}</span></span>
                                <span v-if="message.sender_email">· {{ message.sender_email }}</span>
                                <span v-if="message.sent_at">· {{ message.sent_at }}</span>
                                <span v-else-if="message.sent_at_relative">· {{ message.sent_at_relative }}</span>
                            </div>

                            <p class="mt-3 text-sm leading-6 text-slate-600">{{ message.preview }}</p>
                        </div>

                        <div class="flex items-center gap-3 text-sm text-slate-500">
                            <div class="text-right">
                                <p>{{ titleCase(message.delivery_status) }}</p>
                                <p v-if="message.read_at" class="mt-1 text-xs">Read {{ message.read_at }}</p>
                            </div>
                            <Lucide icon="ChevronRight" class="h-5 w-5 text-slate-400" />
                        </div>
                    </div>
                </button>
            </div>

            <div v-else class="box box--stacked p-10 text-center">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-slate-100">
                    <Lucide icon="Mail" class="h-8 w-8 text-slate-400" />
                </div>
                <h2 class="mt-4 text-lg font-semibold text-slate-800">No messages found</h2>
                <p class="mt-2 text-sm text-slate-500">There are no messages matching the current filters.</p>
            </div>
        </div>

        <div v-if="messages.links.length > 3" class="col-span-12">
            <div class="box box--stacked flex flex-wrap items-center justify-center gap-2 p-4">
                <template v-for="(link, index) in messages.links" :key="index">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        class="rounded-lg border px-3 py-2 text-sm transition"
                        :class="link.active ? 'border-primary bg-primary text-white' : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300'"
                        v-html="link.label"
                    />
                    <span
                        v-else
                        class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-400"
                        v-html="link.label"
                    />
                </template>
            </div>
        </div>
    </div>
</template>
