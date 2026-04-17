<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import { FormTextarea } from '@/components/Base/Form'
import RazeLayout from '@/layouts/RazeLayout.vue'

declare function route(name: string, params?: any): string

defineOptions({ layout: RazeLayout })

interface StatusLogRow {
    id: number
    status: string
    notes: string | null
    created_at: string | null
}

interface ReplyRow {
    id: number
    subject: string
    message: string
    status: string
    sent_at: string | null
    created_at: string | null
}

const props = defineProps<{
    driver: {
        id: number
        full_name: string
        carrier_name: string | null
    }
    message: {
        recipient_id: number
        message_id: number
        subject: string
        body: string
        priority: string
        status: string
        delivery_status: string
        sent_at: string | null
        delivered_at: string | null
        read_at: string | null
        was_unread: boolean
        sender: {
            name: string
            email: string | null
            type: string
        }
        status_logs: StatusLogRow[]
        replies: ReplyRow[]
        reply_target: string | null
        can_reply: boolean
    }
}>()

const replyForm = useForm({
    message: '',
})

function titleCase(value: string) {
    return value.replace(/_/g, ' ').replace(/\b\w/g, (char) => char.toUpperCase())
}

function priorityClass(priority: string) {
    if (priority === 'high') return 'bg-slate-200 text-slate-700'
    if (priority === 'normal') return 'bg-primary/10 text-primary'
    return 'bg-slate-100 text-slate-600'
}

function statusClass(status: string) {
    if (status === 'sent') return 'bg-primary/10 text-primary'
    if (status === 'draft') return 'bg-slate-100 text-slate-600'
    if (status === 'delivered') return 'bg-slate-100 text-slate-700'
    return 'bg-slate-200 text-slate-700'
}

function submitReply() {
    replyForm.post(route('driver.messages.reply', props.message.recipient_id), {
        preserveScroll: true,
        onSuccess: () => replyForm.reset(),
    })
}
</script>

<template>
    <Head :title="message.subject" />

    <div class="grid grid-cols-12 gap-6">
        <div class="col-span-12">
            <div class="box box--stacked p-6">
                <div class="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
                    <div class="space-y-4">
                        <Link
                            :href="route('driver.messages.index')"
                            class="inline-flex items-center gap-2 text-sm font-medium text-slate-500 transition hover:text-primary"
                        >
                            <Lucide icon="ArrowLeft" class="h-4 w-4" />
                            Back to Messages
                        </Link>

                        <div class="flex flex-wrap items-center gap-3">
                            <h1 class="text-2xl font-bold text-slate-800">{{ message.subject }}</h1>
                            <span class="rounded-full px-2.5 py-1 text-xs font-medium" :class="priorityClass(message.priority)">
                                {{ titleCase(message.priority) }} Priority
                            </span>
                            <span class="rounded-full px-2.5 py-1 text-xs font-medium" :class="statusClass(message.status)">
                                {{ titleCase(message.status) }}
                            </span>
                        </div>

                        <p class="text-sm text-slate-500">
                            Driver: <span class="font-medium text-slate-700">{{ driver.full_name }}</span>
                            <span v-if="driver.carrier_name"> · Carrier: <span class="font-medium text-slate-700">{{ driver.carrier_name }}</span></span>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12 xl:col-span-8 space-y-6">
            <div class="box box--stacked p-6">
                <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs uppercase tracking-wide text-slate-500">From</p>
                        <p class="mt-2 text-sm font-semibold text-slate-800">{{ message.sender.name }}</p>
                        <p class="mt-1 text-sm text-slate-500">{{ message.sender.email || 'No email on file' }}</p>
                        <p class="mt-1 text-xs text-slate-400">{{ message.sender.type }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs uppercase tracking-wide text-slate-500">Sent</p>
                        <p class="mt-2 text-sm font-semibold text-slate-800">{{ message.sent_at || 'Not sent yet' }}</p>
                        <p class="mt-1 text-sm text-slate-500">Delivered: {{ message.delivered_at || 'Pending' }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs uppercase tracking-wide text-slate-500">Read</p>
                        <p class="mt-2 text-sm font-semibold text-slate-800">{{ message.read_at || 'Just opened now' }}</p>
                        <p class="mt-1 text-sm text-slate-500">Delivery: {{ titleCase(message.delivery_status) }}</p>
                    </div>
                </div>

                <div class="mt-5">
                    <p class="text-sm font-semibold text-slate-700">Message Body</p>
                    <div class="mt-3 rounded-2xl border border-slate-200 bg-slate-50 p-5">
                        <p class="whitespace-pre-wrap text-sm leading-6 text-slate-700">{{ message.body }}</p>
                    </div>
                </div>
            </div>

            <div v-if="message.replies.length" class="box box--stacked p-6">
                <div class="flex items-center gap-3">
                    <Lucide icon="MessagesSquare" class="h-5 w-5 text-primary" />
                    <h2 class="text-base font-semibold text-slate-800">Your Replies</h2>
                </div>

                <div class="mt-5 space-y-4">
                    <div
                        v-for="reply in message.replies"
                        :key="reply.id"
                        class="rounded-2xl border border-primary/20 bg-primary/5 p-4"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-sm font-semibold text-slate-800">You</p>
                                <p class="mt-1 text-xs text-slate-500">{{ reply.sent_at || reply.created_at }}</p>
                            </div>
                            <span class="rounded-full px-2.5 py-1 text-xs font-medium" :class="statusClass(reply.status)">
                                {{ titleCase(reply.status) }}
                            </span>
                        </div>

                        <p class="mt-3 whitespace-pre-wrap text-sm leading-6 text-slate-700">{{ reply.message }}</p>
                    </div>
                </div>
            </div>

            <div class="box box--stacked p-6">
                <div class="flex items-center gap-3">
                    <Lucide icon="Reply" class="h-5 w-5 text-primary" />
                    <h2 class="text-base font-semibold text-slate-800">Reply to This Message</h2>
                </div>

                <div v-if="message.can_reply" class="mt-5">
                    <p class="mb-3 text-sm text-slate-500">
                        Your reply will be sent to <span class="font-medium text-slate-700">{{ message.reply_target }}</span>.
                    </p>

                    <form class="space-y-4" @submit.prevent="submitReply">
                        <div>
                            <FormTextarea
                                v-model="replyForm.message"
                                rows="5"
                                placeholder="Type your reply here..."
                            />
                            <p v-if="replyForm.errors.message" class="mt-2 text-sm text-danger">
                                {{ replyForm.errors.message }}
                            </p>
                        </div>

                        <div class="flex justify-end">
                            <Button type="submit" variant="primary" class="gap-2" :disabled="replyForm.processing">
                                <Lucide icon="Send" class="h-4 w-4" />
                                {{ replyForm.processing ? 'Sending...' : 'Send Reply' }}
                            </Button>
                        </div>
                    </form>
                </div>

                <div v-else class="mt-5 rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-6 text-sm text-slate-500">
                    We could not find an active carrier to receive your reply from this account.
                </div>
            </div>
        </div>

        <div class="col-span-12 xl:col-span-4">
            <div class="box box--stacked p-6">
                <div class="flex items-center gap-3">
                    <Lucide icon="History" class="h-5 w-5 text-primary" />
                    <h2 class="text-base font-semibold text-slate-800">Message History</h2>
                </div>

                <div v-if="message.status_logs.length" class="mt-5 space-y-4">
                    <div
                        v-for="log in message.status_logs"
                        :key="log.id"
                        class="flex items-start gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4"
                    >
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

                <div v-else class="mt-5 rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-6 text-sm text-slate-500">
                    No history is available for this message yet.
                </div>
            </div>
        </div>
    </div>
</template>
