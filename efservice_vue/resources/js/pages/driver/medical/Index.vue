<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { computed } from 'vue'
import Lucide from '@/components/Base/Lucide'
import RazeLayout from '@/layouts/RazeLayout.vue'

declare function route(name: string, params?: any): string

defineOptions({ layout: RazeLayout })

interface FilePayload {
    id: number
    name: string
    url: string
    mime_type: string | null
    size: number | null
    size_label: string
    collection_name: string
    collection_label: string
    file_type: string
    created_at: string | null
}

interface MedicalPayload {
    id: number
    social_security_number: string | null
    hire_date: string | null
    location: string | null
    is_suspended: boolean
    suspension_date: string | null
    is_terminated: boolean
    termination_date: string | null
    medical_examiner_name: string | null
    medical_examiner_registry_number: string | null
    medical_card_expiration_date: string | null
    status: 'not_set' | 'expired' | 'expiring_soon' | 'valid'
    days_remaining: number | null
    medical_card_file: FilePayload | null
    social_security_card_file: FilePayload | null
    documents: FilePayload[]
    document_counts: {
        total: number
        medical_card: number
        social_security_card: number
        medical_documents: number
    }
}

const props = defineProps<{
    driver: {
        id: number
        full_name: string
        carrier_name: string | null
    }
    medical: MedicalPayload | null
}>()

const statusMeta = computed(() => {
    const status = props.medical?.status ?? 'not_set'

    if (status === 'expired') {
        return {
            label: 'Expired',
            className: 'bg-danger/10 text-danger',
            iconBg: 'bg-danger/10',
            iconColor: 'text-danger',
            title: 'Certificate Expired',
            message: 'Please contact your carrier to renew your medical certificate.',
        }
    }

    if (status === 'expiring_soon') {
        return {
            label: 'Expiring Soon',
            className: 'bg-warning/10 text-warning',
            iconBg: 'bg-warning/10',
            iconColor: 'text-warning',
            title: 'Expiring Soon',
            message: props.medical?.days_remaining != null
                ? `${props.medical.days_remaining} days remaining on your medical card.`
                : 'Your medical card will expire soon.',
        }
    }

    if (status === 'valid') {
        return {
            label: 'Valid',
            className: 'bg-success/10 text-success',
            iconBg: 'bg-success/10',
            iconColor: 'text-success',
            title: 'Compliant',
            message: 'Your medical certification appears to be current.',
        }
    }

    return {
        label: 'Not Set',
        className: 'bg-slate-100 text-slate-600',
        iconBg: 'bg-slate-100',
        iconColor: 'text-slate-400',
        title: 'Not Set',
        message: 'Your carrier has not uploaded a medical qualification yet.',
    }
})

function prettyText(value: string | null | undefined, fallback = 'Not provided') {
    return value && String(value).trim() !== '' ? value : fallback
}

function fileIcon(mimeType: string | null) {
    return mimeType?.includes('pdf') ? 'FileText' : 'Image'
}

function fileTone(mimeType: string | null) {
    return mimeType?.includes('pdf') ? 'bg-danger/10 text-danger' : 'bg-info/10 text-info'
}
</script>

<template>
    <Head title="Medical Qualification" />

    <div class="grid grid-cols-12 gap-6">
        <div class="col-span-12">
            <div class="box box--stacked p-6">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex items-start gap-4">
                        <div class="rounded-2xl border border-primary/20 bg-primary/10 p-3">
                            <Lucide icon="Heart" class="h-8 w-8 text-primary" />
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-slate-800">Medical Qualification</h1>
                            <p class="mt-1 text-slate-500">
                                Review your DOT medical certificate information and supporting documents.
                            </p>
                            <p class="mt-2 text-sm text-slate-500">
                                Driver: <span class="font-medium text-slate-700">{{ driver.full_name }}</span>
                                <span v-if="driver.carrier_name">· Carrier: <span class="font-medium text-slate-700">{{ driver.carrier_name }}</span></span>
                            </p>
                        </div>
                    </div>

                    <Link
                        :href="route('driver.profile')"
                        class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                    >
                        <Lucide icon="User" class="h-4 w-4" />
                        View Profile
                    </Link>
                </div>
            </div>
        </div>

        <template v-if="medical">
            <div class="col-span-12 lg:col-span-8 space-y-6">
                <div class="box box--stacked p-6">
                    <div class="mb-5 flex items-center gap-2 border-b border-dashed border-slate-300/70 pb-5">
                        <Lucide icon="CreditCard" class="h-5 w-5 text-primary" />
                        <h2 class="text-sm font-semibold text-slate-700">Social Security Information</h2>
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div class="rounded-xl border border-slate-200 p-4">
                            <p class="text-xs font-medium uppercase tracking-[0.2em] text-slate-400">Social Security Number</p>
                            <p class="mt-2 font-semibold text-slate-800">{{ prettyText(medical.social_security_number) }}</p>
                        </div>

                        <div class="rounded-xl border border-slate-200 p-4">
                            <p class="text-xs font-medium uppercase tracking-[0.2em] text-slate-400">Hire Date</p>
                            <p class="mt-2 font-semibold text-slate-800">{{ prettyText(medical.hire_date) }}</p>
                        </div>

                        <div class="rounded-xl border border-slate-200 p-4 md:col-span-2">
                            <p class="text-xs font-medium uppercase tracking-[0.2em] text-slate-400">Location</p>
                            <p class="mt-2 font-semibold text-slate-800">{{ prettyText(medical.location) }}</p>
                        </div>

                        <div class="rounded-xl border border-slate-200 p-4">
                            <p class="text-xs font-medium uppercase tracking-[0.2em] text-slate-400">Suspension</p>
                            <p class="mt-2 font-semibold text-slate-800">{{ medical.is_suspended ? 'Yes' : 'No' }}</p>
                            <p v-if="medical.is_suspended" class="mt-1 text-sm text-slate-500">{{ prettyText(medical.suspension_date) }}</p>
                        </div>

                        <div class="rounded-xl border border-slate-200 p-4">
                            <p class="text-xs font-medium uppercase tracking-[0.2em] text-slate-400">Termination</p>
                            <p class="mt-2 font-semibold text-slate-800">{{ medical.is_terminated ? 'Yes' : 'No' }}</p>
                            <p v-if="medical.is_terminated" class="mt-1 text-sm text-slate-500">{{ prettyText(medical.termination_date) }}</p>
                        </div>
                    </div>

                    <div class="mt-5 rounded-xl border border-slate-200 p-4">
                        <div class="mb-3 flex items-center justify-between">
                            <p class="text-sm font-medium text-slate-700">Social Security Card</p>
                            <span class="text-xs text-slate-500">{{ medical.document_counts.social_security_card }} file</span>
                        </div>
                        <a
                            v-if="medical.social_security_card_file"
                            :href="medical.social_security_card_file.url"
                            target="_blank"
                            class="flex items-center justify-between rounded-lg border border-slate-200 px-4 py-3 transition hover:bg-slate-50"
                        >
                            <div>
                                <p class="text-sm font-medium text-slate-700">{{ medical.social_security_card_file.name }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ medical.social_security_card_file.size_label }}</p>
                            </div>
                            <Lucide icon="ArrowUpRight" class="h-4 w-4 text-slate-400" />
                        </a>
                        <div v-else class="rounded-lg border border-dashed border-slate-300 bg-slate-50 px-4 py-6 text-center text-sm text-slate-500">
                            No social security card uploaded.
                        </div>
                    </div>
                </div>

                <div class="box box--stacked p-6">
                    <div class="mb-5 flex items-center gap-2 border-b border-dashed border-slate-300/70 pb-5">
                        <Lucide icon="Stethoscope" class="h-5 w-5 text-primary" />
                        <h2 class="text-sm font-semibold text-slate-700">Medical Certification Information</h2>
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div class="rounded-xl border border-slate-200 p-4">
                            <p class="text-xs font-medium uppercase tracking-[0.2em] text-slate-400">Medical Examiner Name</p>
                            <p class="mt-2 font-semibold text-slate-800">{{ prettyText(medical.medical_examiner_name) }}</p>
                        </div>

                        <div class="rounded-xl border border-slate-200 p-4">
                            <p class="text-xs font-medium uppercase tracking-[0.2em] text-slate-400">Registry Number</p>
                            <p class="mt-2 font-semibold text-slate-800">{{ prettyText(medical.medical_examiner_registry_number) }}</p>
                        </div>

                        <div class="rounded-xl border border-slate-200 p-4 md:col-span-2">
                            <div class="flex flex-wrap items-center gap-3">
                                <div>
                                    <p class="text-xs font-medium uppercase tracking-[0.2em] text-slate-400">Medical Card Expiration</p>
                                    <p class="mt-2 font-semibold text-slate-800">{{ prettyText(medical.medical_card_expiration_date, 'Not set') }}</p>
                                </div>
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium" :class="statusMeta.className">
                                    {{ statusMeta.label }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-5 rounded-xl border border-slate-200 p-4">
                        <div class="mb-3 flex items-center justify-between">
                            <p class="text-sm font-medium text-slate-700">Medical Card</p>
                            <span class="text-xs text-slate-500">{{ medical.document_counts.medical_card }} file</span>
                        </div>
                        <a
                            v-if="medical.medical_card_file"
                            :href="medical.medical_card_file.url"
                            target="_blank"
                            class="flex items-center justify-between rounded-lg border border-slate-200 px-4 py-3 transition hover:bg-slate-50"
                        >
                            <div>
                                <p class="text-sm font-medium text-slate-700">{{ medical.medical_card_file.name }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ medical.medical_card_file.size_label }}</p>
                            </div>
                            <Lucide icon="ArrowUpRight" class="h-4 w-4 text-slate-400" />
                        </a>
                        <div v-else class="rounded-lg border border-dashed border-slate-300 bg-slate-50 px-4 py-6 text-center text-sm text-slate-500">
                            No medical card uploaded.
                        </div>
                    </div>
                </div>

                <div class="box box--stacked p-6">
                    <div class="mb-5 flex items-center gap-2 border-b border-dashed border-slate-300/70 pb-5">
                        <Lucide icon="Paperclip" class="h-5 w-5 text-primary" />
                        <h2 class="text-sm font-semibold text-slate-700">Medical Documents</h2>
                    </div>

                    <div v-if="medical.documents.length" class="space-y-3">
                        <a
                            v-for="document in medical.documents"
                            :key="document.id"
                            :href="document.url"
                            target="_blank"
                            class="flex items-start justify-between rounded-xl border border-slate-200 px-4 py-3 transition hover:bg-slate-50"
                        >
                            <div class="flex min-w-0 items-start gap-3 pr-4">
                                <div class="rounded-lg p-2" :class="fileTone(document.mime_type)">
                                    <Lucide :icon="fileIcon(document.mime_type)" class="h-5 w-5" />
                                </div>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-slate-700">{{ document.name }}</p>
                                    <p class="mt-1 text-xs text-slate-500">
                                        {{ document.collection_label }} · {{ document.size_label }}<span v-if="document.created_at"> · {{ document.created_at }}</span>
                                    </p>
                                </div>
                            </div>
                            <Lucide icon="ArrowUpRight" class="mt-0.5 h-4 w-4 flex-shrink-0 text-slate-400" />
                        </a>
                    </div>
                    <div v-else class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">
                        No documents uploaded.
                    </div>
                </div>
            </div>

            <div class="col-span-12 lg:col-span-4 space-y-6">
                <div class="box box--stacked p-6">
                    <h3 class="text-sm font-semibold text-slate-700">Compliance Status</h3>
                    <div class="py-6 text-center">
                        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full" :class="statusMeta.iconBg">
                            <Lucide
                                :icon="medical.status === 'expired' ? 'AlertCircle' : medical.status === 'expiring_soon' ? 'AlertTriangle' : medical.status === 'valid' ? 'CheckCircle' : 'HelpCircle'"
                                class="h-8 w-8"
                                :class="statusMeta.iconColor"
                            />
                        </div>
                        <p class="mt-4 font-semibold" :class="statusMeta.iconColor">{{ statusMeta.title }}</p>
                        <p class="mt-1 text-sm text-slate-500">{{ statusMeta.message }}</p>
                    </div>
                </div>

                <div class="box box--stacked p-6">
                    <h3 class="text-sm font-semibold text-slate-700">Read Only</h3>
                    <div class="mt-4 flex items-start gap-3 rounded-xl bg-slate-50 p-4">
                        <Lucide icon="Info" class="mt-0.5 h-5 w-5 text-info" />
                        <p class="text-sm text-slate-500">
                            This information is managed by your carrier or admin team. If anything looks wrong, please contact them so they can update your medical record.
                        </p>
                    </div>
                </div>
            </div>
        </template>

        <div v-else class="col-span-12">
            <div class="box box--stacked p-12 text-center">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-slate-100">
                    <Lucide icon="Heart" class="h-8 w-8 text-slate-400" />
                </div>
                <h2 class="mt-5 text-lg font-semibold text-slate-800">No Medical Record</h2>
                <p class="mx-auto mt-2 max-w-xl text-sm text-slate-500">
                    Your medical information has not been uploaded yet. It will appear here once your carrier or admin team adds it.
                </p>
            </div>
        </div>
    </div>
</template>
