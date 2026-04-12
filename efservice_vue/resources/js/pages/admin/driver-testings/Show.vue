<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import Lucide from '@/components/Base/Lucide'
import Button from '@/components/Base/Button'
import { Dialog } from '@/components/Base/Headless'
import RazeLayout from '@/layouts/RazeLayout.vue'

declare function route(name: string, params?: any): string

defineOptions({ layout: RazeLayout })

// ─── Types ────────────────────────────────────────────────────────────────────
interface TestingDetail {
    id: number
    test_type: string
    test_type_label: string
    test_date: string | null
    scheduled_time: string | null
    test_result: string | null
    status: string | null
    administered_by: string
    mro: string | null
    requester_name: string
    location: string | null
    next_test_due: string | null
    bill_to: string | null
    notes: string | null
    is_random_test: boolean
    is_post_accident_test: boolean
    is_reasonable_suspicion_test: boolean
    is_pre_employment_test: boolean
    is_follow_up_test: boolean
    is_return_to_duty_test: boolean
    is_other_reason_test: boolean
    other_reason_description: string | null
    created_at: string | null
    updated_at: string | null
    created_by: string | null
    updated_by: string | null
    pdf_url: string | null
    pdf_size: string | null
    has_pdf: boolean
    attachments: {
        id: number; name: string; url: string; size: string; mime_type: string; extension: string
    }[]
}

interface DriverInfo {
    id: number | null
    full_name: string
    email: string | null
    phone: string | null
    license: { number: string; class: string | null; state: string | null; expires: string | null } | null
}

interface CarrierInfo {
    id: number; name: string; dot_number: string | null; mc_number: string | null
}

// ─── Props ────────────────────────────────────────────────────────────────────
const props = defineProps<{
    testing: TestingDetail
    driver: DriverInfo
    carrier: CarrierInfo | null
    routeNames?: {
        index: string
        show: string
        edit: string
        destroy: string
        downloadPdf: string
        regeneratePdf: string
        uploadAttachment: string
        deleteAttachment: string
        driverShow: string
    }
    isCarrierContext?: boolean
}>()

// ─── Delete modal ─────────────────────────────────────────────────────────────
const deleteModalOpen = ref(false)

function confirmDelete() {
    router.delete(route(props.routeNames?.destroy ?? 'admin.driver-testings.destroy', props.testing.id), {
        onSuccess: () => { deleteModalOpen.value = false },
    })
}

// ─── Regenerate PDF ───────────────────────────────────────────────────────────
const regenerating = ref(false)

function regeneratePdf() {
    regenerating.value = true
    router.post(route(props.routeNames?.regeneratePdf ?? 'admin.driver-testings.regenerate-pdf', props.testing.id), {}, {
        onFinish: () => { regenerating.value = false },
    })
}

// ─── Attachment upload ────────────────────────────────────────────────────────
const uploadFiles = ref<File[]>([])
const uploading = ref(false)
const uploadInput = ref<HTMLInputElement | null>(null)

function onFilesChange(e: Event) {
    const input = e.target as HTMLInputElement
    if (input.files) uploadFiles.value = [...uploadFiles.value, ...Array.from(input.files)]
}

function removeUploadFile(i: number) {
    uploadFiles.value.splice(i, 1)
}

function formatBytes(bytes: number) {
    if (bytes < 1024) return bytes + ' B'
    if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB'
    return (bytes / 1048576).toFixed(1) + ' MB'
}

function submitUpload() {
    if (!uploadFiles.value.length) return
    uploading.value = true
    const data = new FormData()
    uploadFiles.value.forEach(f => data.append('attachments[]', f))
    router.post(route(props.routeNames?.uploadAttachment ?? 'admin.driver-testings.upload-attachment', props.testing.id), data, {
        forceFormData: true,
        onFinish: () => {
            uploading.value = false
            uploadFiles.value = []
            if (uploadInput.value) uploadInput.value.value = ''
        },
    })
}

function deleteAttachment(mediaId: number) {
    router.delete(route(props.routeNames?.deleteAttachment ?? 'admin.driver-testings.delete-attachment', { testing: props.testing.id, media: mediaId }), {
        preserveScroll: true,
    })
}

// ─── Helpers ──────────────────────────────────────────────────────────────────
function resultBadge(result: string | null) {
    if (result === 'Positive') return 'inline-flex items-center rounded-md bg-red-100 px-3 py-1.5 text-sm font-medium text-red-700'
    if (result === 'Negative') return 'inline-flex items-center rounded-md bg-primary/10 px-3 py-1.5 text-sm font-medium text-primary'
    if (result === 'Refusal')  return 'inline-flex items-center rounded-md bg-primary/15 px-3 py-1.5 text-sm font-medium text-primary'
    return 'inline-flex items-center rounded-md bg-slate-100 px-3 py-1.5 text-sm font-medium text-slate-600'
}

function statusBadge(status: string | null) {
    const map: Record<string, string> = {
        'Schedule':       'inline-flex items-center rounded-md bg-primary/10 px-3 py-1.5 text-sm font-medium text-primary',
        'In Progress':    'inline-flex items-center rounded-md bg-primary/15 px-3 py-1.5 text-sm font-medium text-primary',
        'Pending Review': 'inline-flex items-center rounded-md bg-primary/20 px-3 py-1.5 text-sm font-medium text-primary',
        'Completed':      'inline-flex items-center rounded-md bg-primary/10 px-3 py-1.5 text-sm font-medium text-primary',
        'Cancelled':      'inline-flex items-center rounded-md bg-red-100 px-3 py-1.5 text-sm font-medium text-red-600',
    }
    return map[status ?? ''] ?? 'inline-flex items-center rounded-md bg-slate-100 px-3 py-1.5 text-sm font-medium text-slate-600'
}

const testReasons = [
    { key: 'is_random_test'               as const, label: 'Random',               color: 'bg-primary/10 text-primary border-primary/20' },
    { key: 'is_post_accident_test'        as const, label: 'Post Accident',        color: 'bg-primary/15 text-primary border-primary/20' },
    { key: 'is_reasonable_suspicion_test' as const, label: 'Reasonable Suspicion', color: 'bg-primary/20 text-primary border-primary/20' },
    { key: 'is_pre_employment_test'       as const, label: 'Pre-Employment',       color: 'bg-primary/10 text-primary border-primary/20' },
    { key: 'is_follow_up_test'            as const, label: 'Follow-Up',            color: 'bg-primary/15 text-primary border-primary/20' },
    { key: 'is_return_to_duty_test'       as const, label: 'Return-To-Duty',       color: 'bg-primary/20 text-primary border-primary/20' },
    { key: 'is_other_reason_test'         as const, label: 'Other',                 color: 'bg-slate-100 text-slate-700 border-slate-200' },
]

function isImage(ext: string) {
    return ['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext.toLowerCase())
}
function isPdf(ext: string) {
    return ext.toLowerCase() === 'pdf'
}
</script>

<template>
    <Head :title="`Test #${testing.id} – ${driver.full_name}`" />

    <div class="grid grid-cols-12 gap-y-8 gap-x-6">

        <!-- ══ HEADER ══ -->
        <div class="col-span-12">
            <div class="box box--stacked p-6">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-xl font-bold text-slate-800">Drug & Alcohol Test #{{ testing.id }}</h1>
                        <p class="text-sm text-slate-500 mt-0.5">
                            <span class="font-medium text-slate-700">{{ driver.full_name }}</span>
                            <span v-if="carrier"> · {{ carrier.name }}</span>
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <!-- Download PDF -->
                        <a v-if="testing.has_pdf"
                            :href="route(props.routeNames?.downloadPdf ?? 'admin.driver-testings.download-pdf', testing.id)"
                            target="_blank"
                            class="inline-flex items-center gap-1.5 px-3 py-2 border border-primary text-primary rounded-lg hover:bg-primary/5 transition text-sm">
                            <Lucide icon="FileDown" class="w-4 h-4" /> Download PDF
                        </a>
                        <!-- Regenerate PDF -->
                        <button type="button" @click="regeneratePdf" :disabled="regenerating"
                            class="inline-flex items-center gap-1.5 px-3 py-2 border border-primary text-primary rounded-lg hover:bg-primary/5 transition text-sm disabled:opacity-60">
                            <Lucide icon="RefreshCw" class="w-4 h-4" :class="regenerating ? 'animate-spin' : ''" />
                            {{ regenerating ? 'Generating...' : 'Regenerate PDF' }}
                        </button>
                        <!-- Edit -->
                        <Link v-if="driver.id"
                            :href="route(props.routeNames?.edit ?? 'admin.drivers.testings.edit', props.isCarrierContext ? testing.id : { driver: driver.id, testing: testing.id })">
                            <Button variant="outline-primary" class="flex items-center gap-1.5 text-sm">
                                <Lucide icon="PenLine" class="w-4 h-4" /> Edit
                            </Button>
                        </Link>
                        <!-- Delete -->
                        <button type="button" @click="deleteModalOpen = true"
                            class="inline-flex items-center gap-1.5 px-3 py-2 border border-red-400 text-red-500 rounded-lg hover:bg-red-50 transition text-sm">
                            <Lucide icon="Trash2" class="w-4 h-4" /> Delete
                        </button>
                        <!-- Back -->
                        <Link :href="route(props.routeNames?.index ?? 'admin.driver-testings.index')">
                            <Button variant="outline-secondary" class="flex items-center gap-1.5 text-sm">
                                <Lucide icon="ArrowLeft" class="w-4 h-4" /> Back to List
                            </Button>
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <!-- ══ MAIN CONTENT ══ -->
        <div class="col-span-12 lg:col-span-8 space-y-6">

            <!-- ── Test Details card ── -->
            <div class="box box--stacked overflow-hidden">
                <div class="bg-gradient-to-r from-primary/10 to-primary/5 px-6 py-4 border-b border-primary/10 flex items-center justify-between">
                    <h2 class="text-base font-semibold text-slate-800 flex items-center gap-2">
                        <Lucide icon="ClipboardCheck" class="w-5 h-5 text-primary" />
                        Test Details
                    </h2>
                    <div class="flex items-center gap-3">
                        <div class="text-right">
                            <p class="text-xs text-slate-500">Status</p>
                            <span :class="statusBadge(testing.status)">{{ testing.status ?? '—' }}</span>
                        </div>
                        <div class="text-right">
                            <p class="text-xs text-slate-500">Result</p>
                            <span :class="resultBadge(testing.test_result)">{{ testing.test_result ?? 'Pending' }}</span>
                        </div>
                    </div>
                </div>

                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-4">
                            <div class="flex items-start gap-3">
                                <Lucide icon="Calendar" class="w-4 h-4 text-slate-400 mt-0.5 flex-shrink-0" />
                                <div>
                                    <p class="text-xs font-medium text-slate-900">Test Date</p>
                                    <p class="text-sm text-slate-600 mt-0.5">{{ testing.test_date ?? '—' }}</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3">
                                <Lucide icon="FlaskConical" class="w-4 h-4 text-slate-400 mt-0.5 flex-shrink-0" />
                                <div>
                                    <p class="text-xs font-medium text-slate-900">Test Type</p>
                                    <p class="text-sm text-slate-600 mt-0.5">{{ testing.test_type_label }}</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3">
                                <Lucide icon="MapPin" class="w-4 h-4 text-slate-400 mt-0.5 flex-shrink-0" />
                                <div>
                                    <p class="text-xs font-medium text-slate-900">Location</p>
                                    <p class="text-sm text-slate-600 mt-0.5">{{ testing.location ?? '—' }}</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3">
                                <Lucide icon="UserCheck" class="w-4 h-4 text-slate-400 mt-0.5 flex-shrink-0" />
                                <div>
                                    <p class="text-xs font-medium text-slate-900">Administered By</p>
                                    <p class="text-sm text-slate-600 mt-0.5">{{ testing.administered_by ?? '—' }}</p>
                                </div>
                            </div>
                            <div v-if="testing.mro" class="flex items-start gap-3">
                                <Lucide icon="Stethoscope" class="w-4 h-4 text-slate-400 mt-0.5 flex-shrink-0" />
                                <div>
                                    <p class="text-xs font-medium text-slate-900">MRO</p>
                                    <p class="text-sm text-slate-600 mt-0.5">{{ testing.mro }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div class="flex items-start gap-3">
                                <Lucide icon="UserTie" class="w-4 h-4 text-slate-400 mt-0.5 flex-shrink-0" />
                                <div>
                                    <p class="text-xs font-medium text-slate-900">Requested By</p>
                                    <p class="text-sm text-slate-600 mt-0.5">{{ testing.requester_name ?? '—' }}</p>
                                </div>
                            </div>
                            <div v-if="testing.scheduled_time" class="flex items-start gap-3">
                                <Lucide icon="Clock" class="w-4 h-4 text-slate-400 mt-0.5 flex-shrink-0" />
                                <div>
                                    <p class="text-xs font-medium text-slate-900">Scheduled Time</p>
                                    <p class="text-sm text-slate-600 mt-0.5">{{ testing.scheduled_time }}</p>
                                </div>
                            </div>
                            <div v-if="testing.next_test_due" class="flex items-start gap-3">
                                <Lucide icon="CalendarPlus" class="w-4 h-4 text-slate-400 mt-0.5 flex-shrink-0" />
                                <div>
                                    <p class="text-xs font-medium text-slate-900">Next Test Due</p>
                                    <p class="text-sm text-slate-600 mt-0.5">{{ testing.next_test_due }}</p>
                                </div>
                            </div>
                            <div v-if="testing.bill_to" class="flex items-start gap-3">
                                <Lucide icon="Receipt" class="w-4 h-4 text-slate-400 mt-0.5 flex-shrink-0" />
                                <div>
                                    <p class="text-xs font-medium text-slate-900">Bill To</p>
                                    <p class="text-sm text-slate-600 mt-0.5 capitalize">{{ testing.bill_to }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Test Categories -->
                    <div class="mt-6 pt-5 border-t border-slate-100">
                        <p class="text-xs font-medium text-slate-900 mb-3">Test Categories</p>
                        <div class="flex flex-wrap gap-2">
                            <template v-for="r in testReasons" :key="r.key">
                                <span v-if="testing[r.key]"
                                    :class="`inline-flex items-center gap-1.5 rounded-md border px-2.5 py-1 text-xs font-medium ${r.color}`">
                                    <Lucide icon="Info" class="w-3.5 h-3.5" />
                                    {{ r.label }}
                                </span>
                            </template>
                            <span v-if="!testReasons.some(r => testing[r.key])" class="text-sm text-slate-400">
                                No categories specified
                            </span>
                        </div>
                        <div v-if="testing.is_other_reason_test && testing.other_reason_description"
                            class="mt-3 rounded-lg bg-slate-50 px-3 py-2 text-sm text-slate-600">
                            <span class="font-medium">Other reason:</span> {{ testing.other_reason_description }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Notes ── -->
            <div class="box box--stacked overflow-hidden">
                <div class="bg-gradient-to-r from-slate-50 to-gray-50 px-6 py-4 border-b border-slate-200">
                    <h2 class="text-base font-semibold text-slate-800 flex items-center gap-2">
                        <Lucide icon="StickyNote" class="w-5 h-5 text-slate-500" />
                        Notes & Record Info
                    </h2>
                </div>
                <div class="p-6">
                    <div class="bg-slate-50 rounded-lg p-4 mb-5 min-h-[60px]">
                        <p class="text-sm text-slate-700 leading-relaxed">
                            {{ testing.notes || 'No notes available for this test.' }}
                        </p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs text-slate-500 border-t border-slate-100 pt-4">
                        <div class="flex items-center gap-2">
                            <Lucide icon="PlusCircle" class="w-3.5 h-3.5" />
                            <span><strong class="text-slate-700">Created:</strong> {{ testing.created_at ?? '—' }} by {{ testing.created_by ?? 'System' }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <Lucide icon="Edit" class="w-3.5 h-3.5" />
                            <span><strong class="text-slate-700">Updated:</strong> {{ testing.updated_at ?? '—' }} by {{ testing.updated_by ?? 'System' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── PDF Preview ── -->
            <div class="box box--stacked overflow-hidden">
                <div class="bg-gradient-to-r from-slate-50 to-gray-50 px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                    <h2 class="text-base font-semibold text-slate-800 flex items-center gap-2">
                        <Lucide icon="FileText" class="w-5 h-5 text-slate-500" />
                        Authorization Sheet PDF
                    </h2>
                    <div v-if="testing.has_pdf" class="flex items-center gap-2">
                        <span class="text-xs text-slate-400">{{ testing.pdf_size }}</span>
                        <a :href="testing.pdf_url!" target="_blank"
                            class="inline-flex items-center gap-1 px-2.5 py-1 text-xs bg-primary/10 text-primary rounded hover:bg-primary/15 transition">
                            <Lucide icon="ExternalLink" class="w-3 h-3" /> Open
                        </a>
                        <a :href="route(props.routeNames?.downloadPdf ?? 'admin.driver-testings.download-pdf', testing.id)"
                            class="inline-flex items-center gap-1 px-2.5 py-1 text-xs bg-primary/15 text-primary rounded hover:bg-primary/20 transition">
                            <Lucide icon="Download" class="w-3 h-3" /> Download
                        </a>
                    </div>
                </div>

                <div class="p-0">
                    <div v-if="testing.has_pdf && testing.pdf_url">
                        <iframe
                            :src="`${testing.pdf_url}#toolbar=1&navpanes=1&scrollbar=1&view=FitH`"
                            class="w-full border-0"
                            style="height: 680px;"
                            title="PDF Preview"
                        />
                    </div>
                    <div v-else class="p-12 text-center text-slate-400">
                        <Lucide icon="FileQuestion" class="w-16 h-16 mx-auto mb-4 text-slate-300" />
                        <p class="text-base font-medium text-slate-600 mb-2">No PDF Report Available</p>
                        <p class="text-sm mb-5">No PDF has been generated for this test yet.</p>
                        <button type="button" @click="regeneratePdf" :disabled="regenerating"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition text-sm disabled:opacity-60">
                            <Lucide icon="RefreshCw" class="w-4 h-4" :class="regenerating ? 'animate-spin' : ''" />
                            {{ regenerating ? 'Generating...' : 'Generate PDF Report' }}
                        </button>
                    </div>
                </div>
            </div>

        </div>

        <!-- ══ SIDEBAR ══ -->
        <div class="col-span-12 lg:col-span-4 space-y-6">

            <!-- ── Carrier Info ── -->
            <div class="box box--stacked overflow-hidden">
                <div class="bg-gradient-to-r from-primary/10 to-primary/5 px-5 py-4 border-b border-primary/10">
                    <h2 class="text-sm font-semibold text-slate-800 flex items-center gap-2">
                        <Lucide icon="Building2" class="w-4 h-4 text-primary" />
                        Carrier Information
                    </h2>
                </div>
                <div class="p-5">
                    <div v-if="carrier">
                        <div class="bg-primary/10 rounded-lg p-4 border border-primary/20 mb-4">
                            <h3 class="font-semibold text-primary">{{ carrier.name }}</h3>
                            <p class="text-xs text-primary/80 mt-1">Carrier ID: {{ carrier.id }}</p>
                        </div>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-slate-500">DOT Number</span>
                                <span class="font-medium text-slate-800">{{ carrier.dot_number ?? 'N/A' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500">MC Number</span>
                                <span class="font-medium text-slate-800">{{ carrier.mc_number ? 'MC-' + carrier.mc_number : 'N/A' }}</span>
                            </div>
                        </div>
                    </div>
                    <div v-else class="rounded-lg bg-primary/10 border border-primary/20 p-4 text-sm text-primary flex items-start gap-2">
                        <Lucide icon="AlertTriangle" class="w-4 h-4 flex-shrink-0 mt-0.5" />
                        No carrier information available.
                    </div>
                </div>
            </div>

            <!-- ── Driver Info ── -->
            <div class="box box--stacked overflow-hidden">
                <div class="bg-gradient-to-r from-primary/10 to-primary/5 px-5 py-4 border-b border-primary/10">
                    <h2 class="text-sm font-semibold text-slate-800 flex items-center gap-2">
                        <Lucide icon="IdCard" class="w-4 h-4 text-primary" />
                        Driver Information
                    </h2>
                </div>
                <div class="p-5">
                    <div class="bg-primary/10 rounded-lg p-4 border border-primary/20 mb-4">
                        <h3 class="font-semibold text-primary">{{ driver.full_name }}</h3>
                        <p class="text-xs text-primary/80 mt-1">Driver ID: {{ driver.id }}</p>
                    </div>
                    <div class="space-y-3">
                        <div v-if="driver.email" class="flex items-center gap-2 text-sm text-slate-600">
                            <Lucide icon="Mail" class="w-4 h-4 text-slate-400 flex-shrink-0" />
                            <span class="truncate">{{ driver.email }}</span>
                        </div>
                        <div v-if="driver.phone" class="flex items-center gap-2 text-sm text-slate-600">
                            <Lucide icon="Phone" class="w-4 h-4 text-slate-400 flex-shrink-0" />
                            {{ driver.phone }}
                        </div>
                        <div v-if="driver.license" class="mt-3 bg-slate-50 rounded-lg p-3 border border-slate-200">
                            <p class="text-xs font-medium text-slate-500 mb-1">License on File</p>
                            <p class="font-mono font-medium text-slate-800 text-sm">{{ driver.license.number }}</p>
                            <p class="text-xs text-slate-500">
                                Class {{ driver.license.class ?? 'N/A' }} · {{ driver.license.state ?? 'N/A' }}
                            </p>
                            <p v-if="driver.license.expires" class="text-xs text-slate-500">Exp: {{ driver.license.expires }}</p>
                        </div>
                        <div v-if="driver.id" class="mt-3">
                            <Link :href="route(props.routeNames?.driverShow ?? 'admin.drivers.show', driver.id)"
                                class="inline-flex items-center gap-1.5 text-xs text-primary hover:underline">
                                <Lucide icon="User" class="w-3.5 h-3.5" /> View Driver Profile
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Documents Attached ── -->
            <div class="box box--stacked overflow-hidden">
                <div class="bg-gradient-to-r from-primary/10 to-primary/5 px-5 py-4 border-b border-primary/10">
                    <h2 class="text-sm font-semibold text-slate-800 flex items-center gap-2">
                        <Lucide icon="Paperclip" class="w-4 h-4 text-primary" />
                        Documents Attached
                        <span class="ml-auto inline-flex items-center rounded-full bg-primary/15 px-2 py-0.5 text-xs font-medium text-primary">
                            {{ testing.attachments.length }}
                        </span>
                    </h2>
                </div>
                <div class="p-5 space-y-4">

                    <!-- Existing attachments -->
                    <div v-if="testing.attachments.length" class="space-y-2">
                        <div v-for="att in testing.attachments" :key="att.id"
                            class="flex items-center gap-3 rounded-lg border border-slate-200 p-3 hover:bg-slate-50 transition">
                            <div class="flex-shrink-0 w-8 h-8 rounded flex items-center justify-center"
                                :class="isPdf(att.extension) ? 'bg-primary/15' : isImage(att.extension) ? 'bg-primary/10' : 'bg-slate-100'">
                                <Lucide
                                    :icon="isPdf(att.extension) ? 'FileText' : isImage(att.extension) ? 'Image' : 'File'"
                                    class="w-4 h-4"
                                    :class="isPdf(att.extension) ? 'text-primary' : isImage(att.extension) ? 'text-primary' : 'text-slate-500'" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <a :href="att.url" target="_blank"
                                    class="text-sm font-medium text-primary hover:underline truncate block">
                                    {{ att.name }}
                                </a>
                                <p class="text-xs text-slate-400">{{ att.size }}</p>
                            </div>
                            <div class="flex items-center gap-1 flex-shrink-0">
                                <a :href="att.url" download
                                    class="p-1.5 text-slate-400 hover:text-primary transition" title="Download">
                                    <Lucide icon="Download" class="w-4 h-4" />
                                </a>
                                <button type="button" @click="deleteAttachment(att.id)"
                                    class="p-1.5 text-slate-400 hover:text-red-500 transition" title="Delete">
                                    <Lucide icon="Trash2" class="w-4 h-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-center py-4 text-slate-400">
                        <Lucide icon="Paperclip" class="w-8 h-8 mx-auto mb-1.5 text-slate-300" />
                        <p class="text-sm">No documents attached</p>
                    </div>

                    <!-- Upload new files -->
                    <div class="border-t border-slate-100 pt-4">
                        <p class="text-xs font-medium text-slate-600 mb-2">Upload Documents</p>
                        <div class="border-2 border-dashed border-primary/20 rounded-lg p-4 text-center hover:border-primary/40 transition cursor-pointer bg-primary/5"
                            @click="uploadInput?.click()">
                            <Lucide icon="Upload" class="w-6 h-6 text-primary mx-auto mb-1" />
                            <p class="text-xs text-slate-500">Click to select files</p>
                            <p class="text-[10px] text-slate-400 mt-0.5">PDF, JPG, PNG, DOC (max 10MB)</p>
                        </div>
                        <input ref="uploadInput" type="file" multiple
                            accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                            class="hidden" @change="onFilesChange" />

                        <!-- Files staged -->
                        <div v-if="uploadFiles.length" class="mt-3 space-y-1.5">
                            <div v-for="(f, i) in uploadFiles" :key="i"
                                class="flex items-center justify-between bg-primary/10 rounded-lg px-3 py-2 border border-primary/20">
                                <div class="flex items-center gap-2 min-w-0">
                                    <Lucide icon="FileCheck" class="w-3.5 h-3.5 text-primary flex-shrink-0" />
                                    <span class="text-xs text-slate-700 truncate">{{ f.name }}</span>
                                    <span class="text-[10px] text-slate-400 flex-shrink-0">{{ formatBytes(f.size) }}</span>
                                </div>
                                <button type="button" @click="removeUploadFile(i)" class="text-red-400 hover:text-red-600 flex-shrink-0 ml-1">
                                    <Lucide icon="X" class="w-3.5 h-3.5" />
                                </button>
                            </div>
                            <button type="button" @click="submitUpload" :disabled="uploading"
                                class="w-full mt-2 inline-flex items-center justify-center gap-2 px-3 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition text-xs font-medium disabled:opacity-60">
                                <Lucide icon="Upload" class="w-3.5 h-3.5" :class="uploading ? 'animate-pulse' : ''" />
                                {{ uploading ? 'Uploading...' : `Upload ${uploadFiles.length} file${uploadFiles.length > 1 ? 's' : ''}` }}
                            </button>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>

    <!-- ══ DELETE MODAL ══ -->
    <Dialog :open="deleteModalOpen" @close="deleteModalOpen = false">
        <Dialog.Panel class="w-full max-w-[480px] overflow-hidden">
            <div class="px-6 pt-6">
                <button type="button" @click="deleteModalOpen = false"
                    class="ml-auto flex rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 transition">
                    <Lucide icon="X" class="h-5 w-5" />
                </button>
                <div class="pb-2 text-center">
                    <div class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-full border-2 border-danger text-danger">
                        <Lucide icon="AlertTriangle" class="h-7 w-7" />
                    </div>
                    <h3 class="text-2xl font-light text-slate-600">Delete Test Record?</h3>
                    <p class="mt-3 text-sm text-slate-500">
                        This will permanently delete test record #{{ testing.id }}<br>
                        and all its attachments. This cannot be undone.
                    </p>
                </div>
            </div>
            <div class="flex justify-center gap-3 px-6 pb-8 pt-4">
                <button type="button" @click="deleteModalOpen = false"
                    class="min-w-24 rounded-lg border border-slate-300 px-6 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50">
                    Cancel
                </button>
                <button type="button" @click="confirmDelete"
                    class="min-w-24 rounded-lg bg-danger px-6 py-2.5 text-sm font-medium text-white hover:bg-danger/90">
                    Delete
                </button>
            </div>
        </Dialog.Panel>
    </Dialog>
</template>
