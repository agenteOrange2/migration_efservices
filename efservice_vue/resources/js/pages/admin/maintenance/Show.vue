<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import Litepicker from '@/components/Base/Litepicker/Litepicker.vue'
import { FormTextarea } from '@/components/Base/Form'
import { Dialog } from '@/components/Base/Headless'
import RazeLayout from '@/layouts/RazeLayout.vue'

declare function route(name: string, params?: any): string

defineOptions({ layout: RazeLayout })

const props = defineProps<{
    maintenance: {
        id: number
        vehicle: { id: number; label: string; carrier_name: string | null; year: number; make: string; model: string; company_unit_number: string | null; vin: string }
        unit: string
        service_tasks: string
        vendor_mechanic: string | null
        service_date: string | null
        next_service_date: string | null
        cost: string | null
        odometer: string | null
        description: string | null
        notes: string | null
        status: boolean
        status_label: string
        is_historical: boolean
        attachments: { id: number; name: string; url: string; size: string; mime_type: string | null }[]
        generated_reports: { id: number; document_number: string | null; issued_date: string | null; status_label: string; url: string | null; file_name: string | null }[]
    }
    routeNames?: Partial<{
        edit: string
        toggleStatus: string
        generateReport: string
        deleteReport: string
        attachmentsStore: string
        attachmentsDestroy: string
        reschedule: string
        vehicleShow: string
        vehicleIndex: string
    }>
}>()

const defaultRouteNames = {
    edit: 'admin.maintenance.edit',
    toggleStatus: 'admin.maintenance.toggle-status',
    generateReport: 'admin.maintenance.generate-report',
    deleteReport: 'admin.maintenance.delete-report',
    attachmentsStore: 'admin.maintenance.attachments.store',
    attachmentsDestroy: 'admin.maintenance.attachments.destroy',
    reschedule: 'admin.maintenance.reschedule',
    vehicleShow: 'admin.vehicles.show',
    vehicleIndex: 'admin.vehicles.maintenance.index',
} as const

function namedRoute(name: keyof typeof defaultRouteNames, params?: any) {
    return route(props.routeNames?.[name] ?? defaultRouteNames[name], params)
}

const pickerOptions = { singleMode: true, format: 'M/D/YYYY', autoApply: true }
const rescheduleOpen = ref(false)
const uploadForm = useForm({ attachments: [] as File[] })
const rescheduleForm = useForm({ next_service_date: props.maintenance.next_service_date ?? '', reason: '' })

function backHref() {
    return namedRoute('vehicleIndex', props.maintenance.vehicle.id)
}

function toggleStatus() {
    router.put(namedRoute('toggleStatus', props.maintenance.id), {}, { preserveScroll: true })
}

function generateReport() {
    router.post(namedRoute('generateReport', props.maintenance.id), {}, { preserveScroll: true })
}

function deleteAttachment(id: number) {
    router.delete(namedRoute('attachmentsDestroy', { maintenance: props.maintenance.id, media: id }), { preserveScroll: true })
}

function deleteReport(id: number) {
    router.delete(namedRoute('deleteReport', { maintenance: props.maintenance.id, document: id }), { preserveScroll: true })
}

function onFileChange(event: Event) {
    const input = event.target as HTMLInputElement
    uploadForm.attachments = Array.from(input.files ?? [])
}

function uploadAttachments() {
    uploadForm.post(namedRoute('attachmentsStore', props.maintenance.id), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            uploadForm.reset()
        },
    })
}

function submitReschedule() {
    rescheduleForm.post(namedRoute('reschedule', props.maintenance.id), {
        preserveScroll: true,
        onSuccess: () => {
            rescheduleOpen.value = false
            rescheduleForm.reason = ''
        },
    })
}

function statusBadge(statusLabel: string) {
    if (statusLabel === 'Completed') return 'bg-primary/10 text-primary'
    if (statusLabel === 'Overdue') return 'bg-danger/10 text-danger'
    if (statusLabel === 'Upcoming') return 'bg-primary/15 text-primary'
    return 'bg-slate-100 text-slate-600'
}
</script>

<template>
    <Head :title="`Maintenance #${maintenance.id}`" />

    <div class="grid grid-cols-12 gap-y-8 gap-x-6">
        <div class="col-span-12">
            <div class="box box--stacked p-6">
                <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-xl font-bold text-slate-800">Maintenance #{{ maintenance.id }}</h1>
                        <p class="text-sm text-slate-500 mt-1">{{ maintenance.vehicle.label }}</p>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <button type="button" class="inline-flex items-center gap-2 px-4 py-2 border border-slate-300 rounded-lg text-slate-600 hover:bg-slate-50" @click="rescheduleOpen = true">
                            <Lucide icon="CalendarClock" class="w-4 h-4" />
                            Reschedule
                        </button>
                        <button type="button" class="inline-flex items-center gap-2 px-4 py-2 border border-slate-300 rounded-lg text-slate-600 hover:bg-slate-50" @click="toggleStatus">
                            <Lucide :icon="maintenance.status ? 'RotateCcw' : 'CheckCircle'" class="w-4 h-4" />
                            {{ maintenance.status ? 'Mark Pending' : 'Mark Completed' }}
                        </button>
                        <button type="button" class="inline-flex items-center gap-2 px-4 py-2 border border-primary text-primary rounded-lg hover:bg-primary/5" @click="generateReport">
                            <Lucide icon="FileText" class="w-4 h-4" />
                            Generate Report
                        </button>
                        <Link :href="namedRoute('edit', maintenance.id)">
                            <Button variant="primary" class="flex items-center gap-2"><Lucide icon="PenLine" class="w-4 h-4" /> Edit</Button>
                        </Link>
                        <Link :href="backHref()">
                            <Button variant="outline-secondary" class="flex items-center gap-2"><Lucide icon="ArrowLeft" class="w-4 h-4" /> Back</Button>
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12 lg:col-span-8 space-y-6">
            <div class="box box--stacked p-6">
                <div class="flex items-center justify-between mb-5">
                    <h2 class="text-sm font-semibold text-slate-700 flex items-center gap-2">
                        <Lucide icon="ClipboardList" class="w-4 h-4 text-primary" />
                        Maintenance Summary
                    </h2>
                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium" :class="statusBadge(maintenance.status_label)">
                        {{ maintenance.status_label }}
                    </span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4"><p class="text-xs text-slate-500">Service</p><p class="mt-1 text-sm font-medium text-slate-800">{{ maintenance.service_tasks }}</p></div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4"><p class="text-xs text-slate-500">Vendor</p><p class="mt-1 text-sm font-medium text-slate-800">{{ maintenance.vendor_mechanic || 'N/A' }}</p></div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4"><p class="text-xs text-slate-500">Unit</p><p class="mt-1 text-sm font-medium text-slate-800">{{ maintenance.unit }}</p></div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4"><p class="text-xs text-slate-500">Service Date</p><p class="mt-1 text-sm font-medium text-slate-800">{{ maintenance.service_date || 'N/A' }}</p></div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4"><p class="text-xs text-slate-500">Next Service</p><p class="mt-1 text-sm font-medium text-slate-800">{{ maintenance.next_service_date || 'N/A' }}</p></div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4"><p class="text-xs text-slate-500">Cost</p><p class="mt-1 text-sm font-medium text-slate-800">{{ maintenance.cost || 'N/A' }}</p></div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4"><p class="text-xs text-slate-500">Odometer</p><p class="mt-1 text-sm font-medium text-slate-800">{{ maintenance.odometer || 'N/A' }}</p></div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4"><p class="text-xs text-slate-500">Historical</p><p class="mt-1 text-sm font-medium text-slate-800">{{ maintenance.is_historical ? 'Yes' : 'No' }}</p></div>
                </div>

                <div class="mt-6 space-y-4">
                    <div class="rounded-lg border border-slate-200 p-4">
                        <p class="text-xs text-slate-500 mb-2">Description</p>
                        <p class="text-sm text-slate-700 whitespace-pre-line">{{ maintenance.description || 'No description provided.' }}</p>
                    </div>
                    <div class="rounded-lg border border-slate-200 p-4">
                        <p class="text-xs text-slate-500 mb-2">Notes</p>
                        <p class="text-sm text-slate-700 whitespace-pre-line">{{ maintenance.notes || 'No notes available.' }}</p>
                    </div>
                </div>
            </div>

            <div class="box box--stacked p-6">
                <div class="flex items-center justify-between mb-5">
                    <h2 class="text-sm font-semibold text-slate-700 flex items-center gap-2">
                        <Lucide icon="Paperclip" class="w-4 h-4 text-primary" />
                        Attachments
                    </h2>
                    <span class="text-xs text-slate-500">{{ maintenance.attachments.length }} file(s)</span>
                </div>

                <div class="space-y-3">
                    <div v-for="file in maintenance.attachments" :key="file.id" class="rounded-lg border border-slate-200 px-4 py-3">
                        <div class="flex items-start justify-between gap-3">
                            <a :href="file.url" target="_blank" class="min-w-0 flex-1">
                                <p class="text-sm font-medium text-primary truncate">{{ file.name }}</p>
                                <p class="text-xs text-slate-500 mt-1">{{ file.size }}</p>
                            </a>
                            <button type="button" class="text-slate-400 hover:text-danger transition" @click="deleteAttachment(file.id)">
                                <Lucide icon="Trash2" class="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                    <div v-if="!maintenance.attachments.length" class="rounded-lg bg-slate-50 px-4 py-3 text-sm text-slate-500">No attachments uploaded yet.</div>
                </div>

                <div class="mt-5 pt-5 border-t border-slate-200">
                    <input type="file" multiple accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.xls,.xlsx" class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2" @change="onFileChange" />
                    <div class="flex justify-end mt-3">
                        <Button variant="primary" type="button" :disabled="uploadForm.processing || !uploadForm.attachments.length" @click="uploadAttachments">
                            {{ uploadForm.processing ? 'Uploading...' : 'Upload Files' }}
                        </Button>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12 lg:col-span-4 space-y-6">
            <div class="box box--stacked p-6">
                <h2 class="text-sm font-semibold text-slate-700 mb-4 flex items-center gap-2">
                    <Lucide icon="Truck" class="w-4 h-4 text-primary" />
                    Vehicle
                </h2>
                <p class="text-sm font-medium text-slate-800">{{ maintenance.vehicle.label }}</p>
                <p class="text-xs text-slate-500 mt-2">{{ maintenance.vehicle.carrier_name || 'No carrier' }}</p>
                <div class="mt-4">
                    <Link :href="namedRoute('vehicleShow', maintenance.vehicle.id)" class="text-sm text-primary hover:underline">Open vehicle profile</Link>
                </div>
            </div>

            <div class="box box--stacked p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-sm font-semibold text-slate-700 flex items-center gap-2">
                        <Lucide icon="FileText" class="w-4 h-4 text-primary" />
                        Generated Reports
                    </h2>
                    <button type="button" class="text-sm text-primary hover:underline" @click="generateReport">Generate</button>
                </div>

                <div class="space-y-3">
                    <div v-for="report in maintenance.generated_reports" :key="report.id" class="rounded-lg border border-slate-200 px-4 py-3">
                        <p class="text-sm font-medium text-slate-800">{{ report.document_number || 'Maintenance Report' }}</p>
                        <p class="text-xs text-slate-500 mt-1">{{ report.issued_date || 'N/A' }} · {{ report.status_label }}</p>
                        <div class="flex items-center gap-3 mt-3">
                            <a v-if="report.url" :href="report.url" target="_blank" class="text-xs text-primary hover:underline">Open</a>
                            <button type="button" class="text-xs text-danger hover:underline" @click="deleteReport(report.id)">Delete</button>
                        </div>
                    </div>
                    <div v-if="!maintenance.generated_reports.length" class="rounded-lg bg-slate-50 px-4 py-3 text-sm text-slate-500">No reports generated yet.</div>
                </div>
            </div>
        </div>
    </div>

    <Dialog :open="rescheduleOpen" @close="rescheduleOpen = false">
        <Dialog.Panel class="w-full max-w-[560px] overflow-hidden">
            <div class="p-6">
                <div class="flex items-center justify-between border-b border-slate-200 pb-4 mb-5">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-800">Reschedule Maintenance</h3>
                        <p class="text-sm text-slate-500">Use format M/D/YYYY.</p>
                    </div>
                    <button type="button" class="text-slate-400 hover:text-slate-600" @click="rescheduleOpen = false">
                        <Lucide icon="X" class="w-5 h-5" />
                    </button>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1.5">New Service Date</label>
                        <Litepicker v-model="rescheduleForm.next_service_date" :options="pickerOptions" />
                        <p v-if="rescheduleForm.errors.next_service_date" class="text-red-500 text-xs mt-1">{{ rescheduleForm.errors.next_service_date }}</p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1.5">Reason</label>
                        <FormTextarea v-model="rescheduleForm.reason" rows="4" placeholder="Why is this maintenance changing?" />
                        <p v-if="rescheduleForm.errors.reason" class="text-red-500 text-xs mt-1">{{ rescheduleForm.errors.reason }}</p>
                    </div>
                </div>

                <div class="flex justify-end gap-3 mt-6">
                    <Button variant="outline-secondary" type="button" @click="rescheduleOpen = false">Cancel</Button>
                    <Button variant="primary" type="button" :disabled="rescheduleForm.processing" @click="submitReschedule">
                        {{ rescheduleForm.processing ? 'Saving...' : 'Reschedule' }}
                    </Button>
                </div>
            </div>
        </Dialog.Panel>
    </Dialog>
</template>
