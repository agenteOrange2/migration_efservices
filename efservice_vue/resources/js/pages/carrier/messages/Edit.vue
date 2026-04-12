<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import CarrierLayout from '@/layouts/CarrierLayout.vue'
import Form from './Form.vue'

declare function route(name: string, params?: any): string

defineOptions({ layout: CarrierLayout })

interface DriverOption {
    id: number
    name: string
    email: string
}

interface AdminContact {
    id: number
    name: string
    email: string
}

interface RecipientRow {
    id: number
    recipient_type: string
    name: string
    email: string
    delivery_status: string
    delivered_at: string | null
    read_at: string | null
}

interface MessageDetail {
    id: number
    subject: string
    message: string
    priority: string
    status: string
    recipients: RecipientRow[]
    stats: {
        total: number
        delivered: number
        pending: number
        failed: number
        read: number
    }
    created_at: string | null
}

const props = defineProps<{
    message: MessageDetail
    drivers: DriverOption[]
    adminContact: AdminContact | null
}>()

const form = useForm({
    subject: props.message.subject,
    message: props.message.message,
    priority: props.message.priority,
    status: props.message.status,
    add_recipient_type: 'specific_drivers',
    add_driver_ids: [] as string[],
})

function submit() {
    form.put(route('carrier.messages.update', props.message.id), {
        preserveScroll: true,
    })
}

function recipientTypeLabel(type: string) {
    if (type === 'driver') return 'Driver'
    if (type === 'carrier') return 'Carrier'
    if (type === 'user') return 'Admin'
    return type
}

function deliveryBadgeClass(status: string) {
    if (status === 'delivered') return 'bg-primary/10 text-primary'
    if (status === 'failed') return 'bg-slate-200 text-slate-700'
    return 'bg-slate-100 text-slate-600'
}

function removeRecipient(recipient: RecipientRow) {
    if (!confirm(`Remove ${recipient.email} from this draft?`)) return

    router.delete(route('carrier.messages.remove-recipient', [props.message.id, recipient.id]), {
        preserveScroll: true,
    })
}
</script>

<template>
    <Head :title="`Edit Message #${message.id}`" />

    <div class="grid grid-cols-12 gap-6">
        <div class="col-span-12">
            <div class="box box--stacked p-6">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex items-center gap-4">
                        <div class="rounded-2xl border border-primary/20 bg-primary/10 p-3">
                            <Lucide icon="PenSquare" class="h-8 w-8 text-primary" />
                        </div>
                        <div>
                            <div class="flex flex-wrap items-center gap-3">
                                <h1 class="text-2xl font-bold text-slate-800">Edit Draft Message</h1>
                                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600">Draft #{{ message.id }}</span>
                            </div>
                            <p class="text-sm text-slate-500">Update the content, add more recipients, or send this draft when it is ready.</p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <Link :href="route('carrier.messages.show', message.id)">
                            <Button variant="outline-secondary" class="flex items-center gap-2">
                                <Lucide icon="Eye" class="h-4 w-4" />
                                View Draft
                            </Button>
                        </Link>
                        <Link :href="route('carrier.messages.index')">
                            <Button variant="outline-secondary" class="flex items-center gap-2">
                                <Lucide icon="ArrowLeft" class="h-4 w-4" />
                                Back
                            </Button>
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12 xl:col-span-8">
            <Form :mode="'edit'" :form="form" :drivers="drivers" :admin-contact="adminContact" />
        </div>

        <div class="col-span-12 xl:col-span-4 space-y-6">
            <div class="box box--stacked p-6">
                <h2 class="text-base font-semibold text-slate-800">Draft Snapshot</h2>
                <div class="mt-4 grid grid-cols-2 gap-3">
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs text-slate-500">Recipients</p>
                        <p class="mt-1 text-2xl font-semibold text-slate-800">{{ message.stats.total }}</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs text-slate-500">Reads</p>
                        <p class="mt-1 text-2xl font-semibold text-slate-800">{{ message.stats.read }}</p>
                    </div>
                </div>
                <p class="mt-4 text-sm text-slate-500">Created {{ message.created_at || 'recently' }}. Sending this draft will attempt delivery to all pending recipients.</p>

                <div class="mt-6 flex flex-col gap-3">
                    <Button variant="primary" class="flex items-center justify-center gap-2" :disabled="form.processing" @click="submit">
                        <Lucide icon="Save" class="h-4 w-4" />
                        {{ form.status === 'sent' ? 'Update and Send' : 'Update Draft' }}
                    </Button>
                </div>
            </div>

            <div class="box box--stacked p-6">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-base font-semibold text-slate-800">Current Recipients</h2>
                    <span class="rounded-full bg-primary/10 px-3 py-1 text-xs font-medium text-primary">{{ message.recipients.length }}</span>
                </div>

                <div v-if="message.recipients.length" class="mt-4 space-y-3">
                    <div
                        v-for="recipient in message.recipients"
                        :key="recipient.id"
                        class="rounded-2xl border border-slate-200 bg-slate-50 p-4"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-slate-800">{{ recipient.name }}</p>
                                <p class="truncate text-xs text-slate-500">{{ recipient.email }}</p>
                                <div class="mt-2 flex flex-wrap items-center gap-2">
                                    <span class="rounded-full bg-slate-200 px-2.5 py-1 text-[11px] font-medium text-slate-700">
                                        {{ recipientTypeLabel(recipient.recipient_type) }}
                                    </span>
                                    <span class="rounded-full px-2.5 py-1 text-[11px] font-medium" :class="deliveryBadgeClass(recipient.delivery_status)">
                                        {{ recipient.delivery_status }}
                                    </span>
                                </div>
                            </div>

                            <button
                                type="button"
                                class="rounded-lg border border-slate-200 p-2 text-slate-400 transition hover:border-slate-300 hover:text-slate-700"
                                @click="removeRecipient(recipient)"
                            >
                                <Lucide icon="Trash2" class="h-4 w-4" />
                            </button>
                        </div>
                    </div>
                </div>

                <div v-else class="mt-4 rounded-2xl border border-dashed border-slate-300 p-6 text-center text-sm text-slate-500">
                    This draft does not have recipients yet.
                </div>
            </div>
        </div>
    </div>
</template>
