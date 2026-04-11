<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { computed } from 'vue'
import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import RazeLayout from '@/layouts/RazeLayout.vue'

declare function route(name: string, params?: any): string

defineOptions({ layout: RazeLayout })

interface FilePayload {
    name: string
    url: string
    mime_type: string | null
    size: number | null
}

interface DocumentPayload {
    id: number
    file_name: string
    preview_url: string
    size_label: string
    mime_type: string | null
    file_type: string
    created_at_display: string | null
}

interface MedicalRouteNames {
    index: string
    edit: string
    show?: string
    destroy?: string
    documentsShow: string
}

interface MedicalRecordDetail {
    id: number
    driver_name: string
    driver_email?: string | null
    carrier_name: string | null
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
    medical_card_file: FilePayload | null
    social_security_card_file: FilePayload | null
    documents: DocumentPayload[]
    document_counts: {
        total: number
        medical_card: number
        social_security_card: number
        medical_documents: number
    }
}

const props = withDefaults(defineProps<{
    record: MedicalRecordDetail
    routeNames?: MedicalRouteNames
    isCarrierContext?: boolean
}>(), {
    routeNames: () => ({
        index: 'admin.medical-records.index',
        edit: 'admin.medical-records.edit',
        destroy: 'admin.medical-records.destroy',
        documentsShow: 'admin.medical-records.documents.show',
    }),
    isCarrierContext: false,
})

function namedRoute(name: keyof MedicalRouteNames, params?: any) {
    const routeName = props.routeNames[name]

    return routeName ? route(routeName, params) : '#'
}

function formatDate(value: string | null) {
    if (!value) return 'N/A'
    return new Date(`${value}T00:00:00`).toLocaleDateString()
}

function deleteRecord() {
    if (!props.routeNames.destroy) return
    if (!confirm(`Delete medical record for "${props.record.driver_name}"? This action cannot be undone.`)) return

    router.delete(route(props.routeNames.destroy, props.record.id), {
        preserveScroll: true,
    })
}

const statusBadge = computed(() => {
    if (props.record.is_suspended) {
        return { label: 'Suspended', className: 'bg-red-100 text-red-700' }
    }

    if (props.record.is_terminated) {
        return { label: 'Terminated', className: 'bg-amber-100 text-amber-700' }
    }

    return { label: 'Active', className: 'bg-primary/10 text-primary' }
})

const expirationBadge = computed(() => {
    if (!props.record.medical_card_expiration_date) {
        return { label: 'N/A', className: 'bg-slate-100 text-slate-600' }
    }

    const target = new Date(`${props.record.medical_card_expiration_date}T00:00:00`)
    const today = new Date()
    today.setHours(0, 0, 0, 0)
    const diff = Math.ceil((target.getTime() - today.getTime()) / (1000 * 60 * 60 * 24))

    if (diff < 0) return { label: 'Expired', className: 'bg-red-100 text-red-700' }
    if (diff <= 30) return { label: 'Expires Soon', className: 'bg-amber-100 text-amber-700' }
    return { label: 'Valid', className: 'bg-primary/10 text-primary' }
})

function humanSize(size: number | null) {
    if (!size) return 'Unknown size'
    return `${(size / 1024 / 1024).toFixed(2)} MB`
}
</script>

<template>
    <Head title="Medical Record Details" />

    <div class="grid grid-cols-12 gap-y-8 gap-x-6">
        <div class="col-span-12">
            <div class="box box--stacked p-6">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex items-center gap-4">
                        <div class="rounded-xl border border-primary/20 bg-primary/10 p-3">
                            <Lucide icon="HeartPulse" class="h-8 w-8 text-primary" />
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-slate-800">Medical Record Details</h1>
                            <p class="text-slate-500">
                                Review medical certification and document details for this driver.
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <Link :href="namedRoute('index')">
                            <Button variant="outline-secondary" class="flex items-center gap-2">
                                <Lucide icon="ArrowLeft" class="w-4 h-4" />
                                Back to Medical Records
                            </Button>
                        </Link>
                        <Link :href="namedRoute('edit', record.id)">
                            <Button variant="primary" class="flex items-center gap-2">
                                <Lucide icon="PenLine" class="w-4 h-4" />
                                Edit Record
                            </Button>
                        </Link>
                        <Link :href="namedRoute('documentsShow', record.id)">
                            <Button variant="outline-secondary" class="flex items-center gap-2">
                                <Lucide icon="Files" class="w-4 h-4" />
                                Documents ({{ record.document_counts.total }})
                            </Button>
                        </Link>
                        <button
                            v-if="routeNames.destroy"
                            type="button"
                            class="inline-flex items-center gap-2 rounded-lg border border-red-200 px-4 py-2 text-sm font-medium text-red-600 transition hover:bg-red-50"
                            @click="deleteRecord"
                        >
                            <Lucide icon="Trash2" class="w-4 h-4" />
                            Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12 xl:col-span-6">
            <div class="box box--stacked p-6 h-full">
                <div class="mb-5 flex items-center gap-2 border-b border-dashed border-slate-300/70 pb-5">
                    <Lucide icon="IdCard" class="w-5 h-5 text-primary" />
                    <h2 class="text-sm font-semibold text-slate-700">Social Security Information</h2>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="rounded-xl border border-slate-200 p-4">
                        <p class="text-xs font-medium uppercase tracking-[0.2em] text-slate-400">Driver</p>
                        <p class="mt-2 font-semibold text-slate-800">{{ record.driver_name }}</p>
                        <p class="text-sm text-slate-500">{{ record.driver_email || 'No email' }}</p>
                    </div>

                    <div class="rounded-xl border border-slate-200 p-4">
                        <p class="text-xs font-medium uppercase tracking-[0.2em] text-slate-400">Social Security Number</p>
                        <p class="mt-2 font-semibold text-slate-800">{{ record.social_security_number || 'N/A' }}</p>
                    </div>

                    <div class="rounded-xl border border-slate-200 p-4">
                        <p class="text-xs font-medium uppercase tracking-[0.2em] text-slate-400">Hire Date</p>
                        <p class="mt-2 font-semibold text-slate-800">{{ formatDate(record.hire_date) }}</p>
                    </div>

                    <div class="rounded-xl border border-slate-200 p-4">
                        <p class="text-xs font-medium uppercase tracking-[0.2em] text-slate-400">Location</p>
                        <p class="mt-2 font-semibold text-slate-800">{{ record.location || 'N/A' }}</p>
                    </div>

                    <div class="rounded-xl border border-slate-200 p-4 md:col-span-2">
                        <div class="flex items-center gap-3">
                            <div>
                                <p class="text-xs font-medium uppercase tracking-[0.2em] text-slate-400">Status</p>
                                <p class="mt-2 font-semibold text-slate-800">{{ record.carrier_name || 'N/A' }}</p>
                            </div>
                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium" :class="statusBadge.className">
                                {{ statusBadge.label }}
                            </span>
                        </div>
                        <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div>
                                <p class="text-xs font-medium uppercase tracking-[0.2em] text-slate-400">Suspension Date</p>
                                <p class="mt-1 text-slate-700">{{ formatDate(record.suspension_date) }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-medium uppercase tracking-[0.2em] text-slate-400">Termination Date</p>
                                <p class="mt-1 text-slate-700">{{ formatDate(record.termination_date) }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-5 rounded-xl border border-slate-200 p-4">
                    <div class="mb-3 flex items-center justify-between">
                        <p class="text-sm font-medium text-slate-700">Social Security Card</p>
                        <span class="text-xs text-slate-500">{{ record.document_counts.social_security_card }} file</span>
                    </div>
                    <a
                        v-if="record.social_security_card_file"
                        :href="record.social_security_card_file.url"
                        target="_blank"
                        class="flex items-center justify-between rounded-lg border border-slate-200 px-4 py-3 transition hover:bg-slate-50"
                    >
                        <div>
                            <p class="text-sm font-medium text-slate-700">{{ record.social_security_card_file.name }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ humanSize(record.social_security_card_file.size) }}</p>
                        </div>
                        <Lucide icon="ArrowUpRight" class="w-4 h-4 text-slate-400" />
                    </a>
                    <div v-else class="rounded-lg border border-dashed border-slate-300 bg-slate-50 px-4 py-6 text-center text-sm text-slate-500">
                        No social security card uploaded.
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12 xl:col-span-6">
            <div class="box box--stacked p-6 h-full">
                <div class="mb-5 flex items-center gap-2 border-b border-dashed border-slate-300/70 pb-5">
                    <Lucide icon="Stethoscope" class="w-5 h-5 text-primary" />
                    <h2 class="text-sm font-semibold text-slate-700">Medical Certification Information</h2>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="rounded-xl border border-slate-200 p-4">
                        <p class="text-xs font-medium uppercase tracking-[0.2em] text-slate-400">Medical Examiner</p>
                        <p class="mt-2 font-semibold text-slate-800">{{ record.medical_examiner_name || 'N/A' }}</p>
                    </div>

                    <div class="rounded-xl border border-slate-200 p-4">
                        <p class="text-xs font-medium uppercase tracking-[0.2em] text-slate-400">Registry Number</p>
                        <p class="mt-2 font-semibold text-slate-800">{{ record.medical_examiner_registry_number || 'N/A' }}</p>
                    </div>

                    <div class="rounded-xl border border-slate-200 p-4 md:col-span-2">
                        <div class="flex items-center gap-3">
                            <div>
                                <p class="text-xs font-medium uppercase tracking-[0.2em] text-slate-400">Medical Card Expiration</p>
                                <p class="mt-2 font-semibold text-slate-800">{{ formatDate(record.medical_card_expiration_date) }}</p>
                            </div>
                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium" :class="expirationBadge.className">
                                {{ expirationBadge.label }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="mt-5 rounded-xl border border-slate-200 p-4">
                    <div class="mb-3 flex items-center justify-between">
                        <p class="text-sm font-medium text-slate-700">Medical Card</p>
                        <span class="text-xs text-slate-500">{{ record.document_counts.medical_card }} file</span>
                    </div>
                    <a
                        v-if="record.medical_card_file"
                        :href="record.medical_card_file.url"
                        target="_blank"
                        class="flex items-center justify-between rounded-lg border border-slate-200 px-4 py-3 transition hover:bg-slate-50"
                    >
                        <div>
                            <p class="text-sm font-medium text-slate-700">{{ record.medical_card_file.name }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ humanSize(record.medical_card_file.size) }}</p>
                        </div>
                        <Lucide icon="ArrowUpRight" class="w-4 h-4 text-slate-400" />
                    </a>
                    <div v-else class="rounded-lg border border-dashed border-slate-300 bg-slate-50 px-4 py-6 text-center text-sm text-slate-500">
                        No medical card uploaded.
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12">
            <div class="box box--stacked p-6">
                <div class="mb-5 flex items-center justify-between gap-4 border-b border-dashed border-slate-300/70 pb-5">
                    <div class="flex items-center gap-2">
                        <Lucide icon="Paperclip" class="w-5 h-5 text-primary" />
                        <h2 class="text-sm font-semibold text-slate-700">Additional Documents</h2>
                    </div>
                    <Link :href="namedRoute('documentsShow', record.id)">
                        <Button variant="outline-secondary" class="flex items-center gap-2">
                            <Lucide icon="Files" class="w-4 h-4" />
                            View All
                        </Button>
                    </Link>
                </div>

                <div v-if="record.documents.length" class="space-y-3">
                    <a
                        v-for="document in record.documents"
                        :key="document.id"
                        :href="document.preview_url"
                        target="_blank"
                        class="flex items-start justify-between rounded-xl border border-slate-200 px-4 py-3 transition hover:bg-slate-50"
                    >
                        <div>
                            <p class="text-sm font-medium text-slate-700">{{ document.file_name }}</p>
                            <p class="mt-1 text-xs text-slate-500">
                                {{ document.size_label }} · {{ document.file_type.toUpperCase() }} · {{ document.created_at_display }}
                            </p>
                        </div>
                        <Lucide icon="ArrowUpRight" class="w-4 h-4 text-slate-400 mt-0.5" />
                    </a>
                </div>
                <div v-else class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">
                    No additional medical documents uploaded.
                </div>
            </div>
        </div>
    </div>
</template>
