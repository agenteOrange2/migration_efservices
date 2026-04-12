<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import RazeLayout from '@/layouts/RazeLayout.vue'

declare function route(name: string, params?: any): string

defineOptions({ layout: RazeLayout })

const props = defineProps<{
    repair: {
        id: number
        vehicle: { id: number; label: string; carrier_name: string | null; year: number; make: string; model: string; company_unit_number: string | null; vin: string }
        driver_name: string | null
        repair_name: string
        repair_date: string | null
        cost: string | null
        odometer: string | null
        status: string
        status_label: string
        description: string | null
        notes: string | null
        attachments: { id: number; name: string; url: string; size: string; mime_type: string | null }[]
        generated_reports: { id: number; document_number: string | null; issued_date: string | null; status_label: string; url: string | null; file_name: string | null }[]
    }
    routeNames?: Partial<{
        edit: string
        generateReport: string
        deleteReport: string
        attachmentsStore: string
        attachmentsDestroy: string
        vehicleShow: string
        vehicleIndex: string
    }>
}>()

const defaultRouteNames = {
    edit: 'admin.vehicles.emergency-repairs.edit',
    generateReport: 'admin.vehicles.emergency-repairs.generate-report',
    deleteReport: 'admin.vehicles.emergency-repairs.delete-report',
    attachmentsStore: 'admin.vehicles.emergency-repairs.attachments.store',
    attachmentsDestroy: 'admin.vehicles.emergency-repairs.attachments.destroy',
    vehicleShow: 'admin.vehicles.show',
    vehicleIndex: 'admin.vehicles.repairs.index',
} as const

function namedRoute(name: keyof typeof defaultRouteNames, params?: any) {
    return route(props.routeNames?.[name] ?? defaultRouteNames[name], params)
}

const uploadForm = useForm({ attachments: [] as File[] })

function backHref() {
    return namedRoute('vehicleIndex', props.repair.vehicle.id)
}

function generateReport() {
    router.post(namedRoute('generateReport', props.repair.id), {}, { preserveScroll: true })
}

function deleteAttachment(id: number) {
    router.delete(namedRoute('attachmentsDestroy', {
        emergencyRepair: props.repair.id,
        media: id,
    }), { preserveScroll: true })
}

function deleteReport(id: number) {
    router.delete(namedRoute('deleteReport', {
        emergencyRepair: props.repair.id,
        document: id,
    }), { preserveScroll: true })
}

function onFileChange(event: Event) {
    const input = event.target as HTMLInputElement
    uploadForm.attachments = Array.from(input.files ?? [])
}

function uploadAttachments() {
    uploadForm.post(namedRoute('attachmentsStore', props.repair.id), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            uploadForm.reset()
        },
    })
}

function statusBadge(status: string) {
    if (status === 'completed') return 'bg-primary/10 text-primary'
    if (status === 'in_progress') return 'bg-slate-200 text-slate-700'
    return 'bg-slate-100 text-slate-600'
}
</script>

<template>
    <Head :title="`Emergency Repair #${repair.id}`" />

    <div class="grid grid-cols-12 gap-y-8 gap-x-6">
        <div class="col-span-12">
            <div class="box box--stacked p-6">
                <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-xl font-bold text-slate-800">Emergency Repair #{{ repair.id }}</h1>
                        <p class="text-sm text-slate-500 mt-1">{{ repair.vehicle.label }}</p>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <button type="button" class="inline-flex items-center gap-2 px-4 py-2 border border-primary text-primary rounded-lg hover:bg-primary/5" @click="generateReport">
                            <Lucide icon="FileText" class="w-4 h-4" />
                            Generate Report
                        </button>
                        <Link :href="namedRoute('edit', repair.id)">
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
                        Repair Summary
                    </h2>
                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium" :class="statusBadge(repair.status)">
                        {{ repair.status_label }}
                    </span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4"><p class="text-xs text-slate-500">Repair</p><p class="mt-1 text-sm font-medium text-slate-800">{{ repair.repair_name }}</p></div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4"><p class="text-xs text-slate-500">Repair Date</p><p class="mt-1 text-sm font-medium text-slate-800">{{ repair.repair_date || 'N/A' }}</p></div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4"><p class="text-xs text-slate-500">Current Driver</p><p class="mt-1 text-sm font-medium text-slate-800">{{ repair.driver_name || 'No driver assigned' }}</p></div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4"><p class="text-xs text-slate-500">Cost</p><p class="mt-1 text-sm font-medium text-slate-800">{{ repair.cost || 'N/A' }}</p></div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4"><p class="text-xs text-slate-500">Odometer</p><p class="mt-1 text-sm font-medium text-slate-800">{{ repair.odometer || 'N/A' }}</p></div>
                </div>

                <div class="mt-6 space-y-4">
                    <div class="rounded-lg border border-slate-200 p-4">
                        <p class="text-xs text-slate-500 mb-2">Description</p>
                        <p class="text-sm text-slate-700 whitespace-pre-line">{{ repair.description || 'No description provided.' }}</p>
                    </div>
                    <div class="rounded-lg border border-slate-200 p-4">
                        <p class="text-xs text-slate-500 mb-2">Notes</p>
                        <p class="text-sm text-slate-700 whitespace-pre-line">{{ repair.notes || 'No notes available.' }}</p>
                    </div>
                </div>
            </div>

            <div class="box box--stacked p-6">
                <div class="flex items-center justify-between mb-5">
                    <h2 class="text-sm font-semibold text-slate-700 flex items-center gap-2">
                        <Lucide icon="Paperclip" class="w-4 h-4 text-primary" />
                        Attachments
                    </h2>
                    <span class="text-xs text-slate-500">{{ repair.attachments.length }} file(s)</span>
                </div>

                <div class="space-y-3">
                    <div v-for="file in repair.attachments" :key="file.id" class="rounded-lg border border-slate-200 px-4 py-3">
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
                    <div v-if="!repair.attachments.length" class="rounded-lg bg-slate-50 px-4 py-3 text-sm text-slate-500">No attachments uploaded yet.</div>
                </div>

                <div class="mt-5 pt-5 border-t border-slate-200">
                    <input type="file" multiple accept=".jpg,.jpeg,.png,.pdf,.doc,.docx" class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2" @change="onFileChange" />
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
                <p class="text-sm font-medium text-slate-800">{{ repair.vehicle.label }}</p>
                <p class="text-xs text-slate-500 mt-2">{{ repair.vehicle.carrier_name || 'No carrier' }}</p>
                <div class="mt-4">
                    <Link :href="namedRoute('vehicleShow', repair.vehicle.id)" class="text-sm text-primary hover:underline">Open vehicle profile</Link>
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
                    <div v-for="report in repair.generated_reports" :key="report.id" class="rounded-lg border border-slate-200 px-4 py-3">
                        <p class="text-sm font-medium text-slate-800">{{ report.document_number || 'Repair Report' }}</p>
                        <p class="text-xs text-slate-500 mt-1">{{ report.issued_date || 'N/A' }} - {{ report.status_label }}</p>
                        <div class="flex items-center gap-3 mt-3">
                            <a v-if="report.url" :href="report.url" target="_blank" class="text-xs text-primary hover:underline">Open</a>
                            <button type="button" class="text-xs text-danger hover:underline" @click="deleteReport(report.id)">Delete</button>
                        </div>
                    </div>
                    <div v-if="!repair.generated_reports.length" class="rounded-lg bg-slate-50 px-4 py-3 text-sm text-slate-500">No reports generated yet.</div>
                </div>
            </div>
        </div>
    </div>
</template>
