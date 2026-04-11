<script setup lang="ts">
import { computed } from 'vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import { FormSelect, FormTextarea } from '@/components/Base/Form'
import RazeLayout from '@/layouts/RazeLayout.vue'

declare function route(name: string, params?: any): string

defineOptions({ layout: RazeLayout })

interface AdminOption {
    id: number
    name: string
    email: string | null
}

interface SubmissionDetail {
    id: number
    full_name: string
    company: string | null
    email: string
    phone: string | null
    phone_digits: string
    message: string | null
    status: string
    admin_notes: string | null
    assigned_to: string
    assigned_user: AdminOption | null
    responded_at: string | null
    ip_address: string | null
    created_at: string | null
    updated_at: string | null
}

const props = defineProps<{
    submission: SubmissionDetail
    admins: AdminOption[]
}>()

const form = useForm({
    status: props.submission.status,
    assigned_to: props.submission.assigned_to,
    admin_notes: props.submission.admin_notes ?? '',
})

const whatsappUrl = computed(() => {
    if (!props.submission.phone_digits) return null
    return `https://wa.me/${props.submission.phone_digits}`
})

function submit() {
    form.put(route('admin.contact-submissions.update', props.submission.id), {
        preserveScroll: true,
    })
}

function destroySubmission() {
    if (!confirm(`Delete "${props.submission.full_name}"? This action cannot be undone.`)) return

    router.delete(route('admin.contact-submissions.destroy', props.submission.id), {
        preserveScroll: true,
    })
}

function statusBadgeClass(status: string) {
    if (status === 'new') return 'bg-primary/10 text-primary'
    if (status === 'in_progress') return 'bg-slate-200 text-slate-700'
    if (status === 'contacted') return 'bg-slate-100 text-slate-600'
    return 'bg-slate-100 text-slate-500'
}

function titleCase(value: string) {
    return value.replace(/_/g, ' ').replace(/\b\w/g, (char) => char.toUpperCase())
}
</script>

<template>
    <Head :title="submission.full_name" />

    <div class="grid grid-cols-12 gap-6">
        <div class="col-span-12">
            <div class="box box--stacked p-6">
                <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                    <div class="flex items-center gap-4">
                        <div class="rounded-2xl border border-primary/20 bg-primary/10 p-3">
                            <Lucide icon="BadgeHelp" class="h-8 w-8 text-primary" />
                        </div>
                        <div>
                            <div class="flex flex-wrap items-center gap-3">
                                <h1 class="text-2xl font-bold text-slate-800">{{ submission.full_name }}</h1>
                                <span class="rounded-full px-3 py-1 text-xs font-medium" :class="statusBadgeClass(submission.status)">
                                    {{ titleCase(submission.status) }}
                                </span>
                            </div>
                            <p class="mt-1 text-sm text-slate-500">Submitted {{ submission.created_at || 'recently' }}{{ submission.company ? ` · ${submission.company}` : '' }}</p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <Link :href="route('admin.contact-submissions.index')">
                            <Button variant="outline-secondary" class="flex items-center gap-2">
                                <Lucide icon="ArrowLeft" class="h-4 w-4" />
                                Back
                            </Button>
                        </Link>
                        <a :href="`mailto:${submission.email}`">
                            <Button variant="primary" class="flex items-center gap-2">
                                <Lucide icon="Mail" class="h-4 w-4" />
                                Email
                            </Button>
                        </a>
                        <a v-if="submission.phone" :href="`tel:${submission.phone}`">
                            <Button variant="outline-secondary" class="flex items-center gap-2">
                                <Lucide icon="Phone" class="h-4 w-4" />
                                Call
                            </Button>
                        </a>
                        <a v-if="whatsappUrl" :href="whatsappUrl" target="_blank" rel="noopener noreferrer">
                            <Button variant="outline-secondary" class="flex items-center gap-2">
                                <Lucide icon="MessageCircle" class="h-4 w-4" />
                                WhatsApp
                            </Button>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12 xl:col-span-8 space-y-6">
            <div class="box box--stacked p-6">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs uppercase tracking-wide text-slate-500">Email</p>
                        <p class="mt-2 text-sm font-semibold text-slate-800">{{ submission.email }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs uppercase tracking-wide text-slate-500">Phone</p>
                        <p class="mt-2 text-sm font-semibold text-slate-800">{{ submission.phone || 'Not provided' }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs uppercase tracking-wide text-slate-500">Company</p>
                        <p class="mt-2 text-sm font-semibold text-slate-800">{{ submission.company || 'Not provided' }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs uppercase tracking-wide text-slate-500">Assigned To</p>
                        <p class="mt-2 text-sm font-semibold text-slate-800">{{ submission.assigned_user?.name || 'Unassigned' }}</p>
                        <p v-if="submission.assigned_user?.email" class="mt-1 text-xs text-slate-500">{{ submission.assigned_user.email }}</p>
                    </div>
                </div>

                <div class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs uppercase tracking-wide text-slate-500">Submitted</p>
                        <p class="mt-2 text-sm font-semibold text-slate-800">{{ submission.created_at || '—' }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs uppercase tracking-wide text-slate-500">Responded</p>
                        <p class="mt-2 text-sm font-semibold text-slate-800">{{ submission.responded_at || 'Not yet' }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs uppercase tracking-wide text-slate-500">IP Address</p>
                        <p class="mt-2 text-sm font-semibold text-slate-800">{{ submission.ip_address || 'N/A' }}</p>
                    </div>
                </div>

                <div class="mt-6">
                    <p class="text-sm font-semibold text-slate-700">Message</p>
                    <div class="mt-3 rounded-2xl border border-slate-200 bg-slate-50 p-5">
                        <p class="whitespace-pre-wrap text-sm leading-6 text-slate-700">{{ submission.message || 'No message provided.' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12 xl:col-span-4 space-y-6">
            <div class="box box--stacked p-6">
                <div class="flex items-center gap-3">
                    <Lucide icon="Settings2" class="h-5 w-5 text-primary" />
                    <h2 class="text-base font-semibold text-slate-800">Manage Submission</h2>
                </div>

                <div class="mt-5 space-y-4">
                    <div>
                        <label class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-slate-500">Status</label>
                        <FormSelect v-model="form.status">
                            <option value="new">New</option>
                            <option value="in_progress">In Progress</option>
                            <option value="contacted">Contacted</option>
                            <option value="closed">Closed</option>
                        </FormSelect>
                        <p v-if="form.errors.status" class="mt-1 text-xs text-red-500">{{ form.errors.status }}</p>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-slate-500">Assigned To</label>
                        <FormSelect v-model="form.assigned_to">
                            <option value="">Unassigned</option>
                            <option v-for="admin in admins" :key="admin.id" :value="String(admin.id)">
                                {{ admin.name }}
                            </option>
                        </FormSelect>
                        <p v-if="form.errors.assigned_to" class="mt-1 text-xs text-red-500">{{ form.errors.assigned_to }}</p>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-slate-500">Admin Notes</label>
                        <FormTextarea v-model="form.admin_notes" rows="7" placeholder="Internal notes for this contact..." />
                        <p v-if="form.errors.admin_notes" class="mt-1 text-xs text-red-500">{{ form.errors.admin_notes }}</p>
                    </div>

                    <Button variant="primary" class="flex w-full items-center justify-center gap-2" :disabled="form.processing" @click="submit">
                        <Lucide icon="Save" class="h-4 w-4" />
                        Update Submission
                    </Button>
                </div>
            </div>

            <div class="box box--stacked p-6">
                <h2 class="text-base font-semibold text-slate-800">Danger Zone</h2>
                <p class="mt-2 text-sm text-slate-500">Delete this lead if it should no longer remain in the admin queue.</p>
                <Button variant="outline-secondary" class="mt-4 flex w-full items-center justify-center gap-2" @click="destroySubmission">
                    <Lucide icon="Trash2" class="h-4 w-4" />
                    Delete Submission
                </Button>
            </div>
        </div>
    </div>
</template>
