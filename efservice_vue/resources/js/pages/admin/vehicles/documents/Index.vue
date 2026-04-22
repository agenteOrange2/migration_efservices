<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { reactive, ref } from 'vue'
import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import TomSelect from '@/components/Base/TomSelect/TomSelect.vue'
import Litepicker from '@/components/Base/Litepicker/Litepicker.vue'
import { FormInput, FormTextarea } from '@/components/Base/Form'
import { Dialog } from '@/components/Base/Headless'
import RazeLayout from '@/layouts/RazeLayout.vue'

declare function route(name: string, params?: any): string

defineOptions({ layout: RazeLayout })

const props = defineProps<{
    vehicle: { id: number; title: string; company_unit_number: string | null; vin: string; carrier_name?: string | null }
    documents: { data: any[]; links: { url: string | null; label: string; active: boolean }[]; total: number; last_page: number }
    filters: { search: string; document_type: string; status: string; sort_field: string; sort_direction: string }
    documentTypes: Record<string, string>
    documentStatuses: Record<string, string>
    stats: { total: number; active: number; expired: number; pending: number }
    maintenanceReports: any[]
    repairReports: any[]
    hasMaintenanceRecords: boolean
    hasRepairRecords: boolean
    hasDocumentsWithFiles: boolean
    isCarrierContext?: boolean
    routeNames?: Partial<{
        show: string
        documentsIndex: string
        documentsStore: string
        documentsUpdate: string
        documentsDestroy: string
        documentsDownloadAll: string
        documentsGenerateMaintenanceReport: string
        documentsGenerateRepairReport: string
    }>
}>()

const filters = reactive({ ...props.filters })
const showModal = ref(false)
const modalMode = ref<'create' | 'edit'>('create')
const saving = ref(false)
const errors = ref<Record<string, string[]>>({})
const lpOptions = { singleMode: true, format: 'M/D/YYYY', autoApply: true }

const defaultRouteNames = {
    show: 'admin.vehicles.show',
    documentsIndex: 'admin.vehicles.documents.index',
    documentsStore: 'admin.vehicles.documents.store',
    documentsUpdate: 'admin.vehicles.documents.update',
    documentsDestroy: 'admin.vehicles.documents.destroy',
    documentsDownloadAll: 'admin.vehicles.documents.download-all',
    documentsGenerateMaintenanceReport: 'admin.vehicles.documents.generate-maintenance-report',
    documentsGenerateRepairReport: 'admin.vehicles.documents.generate-repair-report',
} as const

function namedRoute(name: keyof typeof defaultRouteNames, params?: any) {
    return route(props.routeNames?.[name] ?? defaultRouteNames[name], params)
}

function documentStatusClass(document: any) {
    const normalized = String(document?.status ?? '').trim().toLowerCase().replace(/[_-]+/g, ' ')

    if (document?.is_expired || ['expired', 'rejected'].includes(normalized)) {
        return 'bg-danger/10 text-danger'
    }

    if (document?.is_expiring_soon || ['pending', 'expiring', 'expiring soon'].includes(normalized)) {
        return 'bg-warning/10 text-warning'
    }

    if (['active', 'valid', 'approved'].includes(normalized)) {
        return 'bg-success/10 text-success'
    }

    if (['under review', 'review', 'draft'].includes(normalized)) {
        return 'bg-info/10 text-info'
    }

    return 'bg-slate-100 text-slate-600'
}

function blankForm() {
    return {
        id: 0,
        document_type: '',
        document_number: '',
        issued_date: '',
        expiration_date: '',
        status: 'active',
        notes: '',
        document_file: null as File | null,
        preview_url: '',
    }
}

const form = reactive(blankForm())

const generatingMaintenance = ref(false)
const generatingRepair = ref(false)

function generateMaintenanceReport() {
    generatingMaintenance.value = true
    router.post(namedRoute('documentsGenerateMaintenanceReport', props.vehicle.id), {}, {
        preserveScroll: true,
        onFinish: () => { generatingMaintenance.value = false },
    })
}

function generateRepairReport() {
    generatingRepair.value = true
    router.post(namedRoute('documentsGenerateRepairReport', props.vehicle.id), {}, {
        preserveScroll: true,
        onFinish: () => { generatingRepair.value = false },
    })
}

function applyFilters() {
    router.get(namedRoute('documentsIndex', props.vehicle.id), {
        search: filters.search || undefined,
        document_type: filters.document_type || undefined,
        status: filters.status || undefined,
        sort_field: props.filters.sort_field || undefined,
        sort_direction: props.filters.sort_direction || undefined,
    }, { preserveState: true, replace: true })
}

function resetFilters() {
    filters.search = ''
    filters.document_type = ''
    filters.status = ''
    applyFilters()
}

function openCreate() {
    Object.assign(form, blankForm())
    errors.value = {}
    modalMode.value = 'create'
    showModal.value = true
}

function openEdit(document: any) {
    Object.assign(form, {
        id: document.id,
        document_type: document.document_type,
        document_number: document.document_number ?? '',
        issued_date: document.issued_date ?? '',
        expiration_date: document.expiration_date ?? '',
        status: document.status ?? 'active',
        notes: document.notes ?? '',
        document_file: null,
        preview_url: document.preview_url ?? '',
    })
    errors.value = {}
    modalMode.value = 'edit'
    showModal.value = true
}

function onFileChange(event: Event) {
    const input = event.target as HTMLInputElement
    form.document_file = input.files?.[0] ?? null
}

function saveForm() {
    saving.value = true
    errors.value = {}

    const payload: Record<string, any> = {
        document_type: form.document_type,
        document_number: form.document_number,
        issued_date: form.issued_date,
        expiration_date: form.expiration_date,
        status: form.status,
        notes: form.notes,
    }

    if (form.document_file) {
        payload.document_file = form.document_file
    }

    if (modalMode.value === 'create') {
        router.post(namedRoute('documentsStore', props.vehicle.id), payload, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                showModal.value = false
                saving.value = false
            },
            onError: (e) => {
                errors.value = e as any
                saving.value = false
            },
        })
        return
    }

    router.post(namedRoute('documentsUpdate', { vehicle: props.vehicle.id, document: form.id }), {
        ...payload,
        _method: 'put',
    }, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            showModal.value = false
            saving.value = false
        },
        onError: (e) => {
            errors.value = e as any
            saving.value = false
        },
    })
}

function deleteDocument(document: any) {
    if (!confirm(`Delete "${document.document_type_label}"?`)) return

    router.delete(namedRoute('documentsDestroy', { vehicle: props.vehicle.id, document: document.id }), {
        preserveScroll: true,
    })
}
</script>

<template>
    <Head title="Vehicle Documents" />

    <div class="grid grid-cols-12 gap-x-6 gap-y-10">
        <div class="col-span-12">
            <div class="box box--stacked p-6 mb-6">
                <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-bold text-slate-800">Vehicle Documents</h1>
                        <p class="text-slate-500">{{ vehicle.title }}{{ vehicle.company_unit_number ? ` · Unit ${vehicle.company_unit_number}` : '' }} · {{ vehicle.vin }}</p>
                    </div>
                    <div class="flex items-center gap-3 flex-wrap">
                        <Button variant="primary" class="flex items-center gap-2" @click="openCreate">
                            <Lucide icon="Plus" class="w-4 h-4" />
                            Add Document
                        </Button>
                        <a :href="namedRoute('documentsDownloadAll', vehicle.id)" :class="['inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition', hasDocumentsWithFiles ? 'bg-success text-white hover:bg-success/90' : 'bg-slate-100 text-slate-400 cursor-not-allowed pointer-events-none']" :aria-disabled="!hasDocumentsWithFiles">
                            <Lucide icon="Download" class="w-4 h-4" />
                            Download All
                        </a>
                        <Link :href="namedRoute('show', vehicle.id)">
                            <Button variant="outline-secondary" class="flex items-center gap-2">
                                <Lucide icon="ArrowLeft" class="w-4 h-4" />
                                Back
                            </Button>
                        </Link>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
                <div class="box box--stacked rounded-xl border border-primary/20 bg-primary/5 p-5"><p class="text-sm text-slate-500">Total</p><p class="mt-1 text-2xl font-semibold text-primary">{{ stats.total }}</p></div>
                <div class="box box--stacked rounded-xl border border-success/20 bg-success/5 p-5"><p class="text-sm text-slate-500">Active</p><p class="mt-1 text-2xl font-semibold text-success">{{ stats.active }}</p></div>
                <div class="box box--stacked rounded-xl border border-danger/20 bg-danger/5 p-5"><p class="text-sm text-slate-500">Expired</p><p class="mt-1 text-2xl font-semibold text-danger">{{ stats.expired }}</p></div>
                <div class="box box--stacked rounded-xl border border-warning/20 bg-warning/5 p-5"><p class="text-sm text-slate-500">Pending</p><p class="mt-1 text-2xl font-semibold text-warning">{{ stats.pending }}</p></div>
            </div>

            <div class="box box--stacked p-5 mb-6">
                <div class="grid grid-cols-1 lg:grid-cols-4 gap-4">
                    <div class="lg:col-span-2 relative">
                        <Lucide icon="Search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                        <FormInput v-model="filters.search" type="text" class="pl-10" placeholder="Search type, number, note or file..." />
                    </div>
                    <TomSelect v-model="filters.document_type">
                        <option value="">All document types</option>
                        <option v-for="(label, key) in documentTypes" :key="key" :value="key">{{ label }}</option>
                    </TomSelect>
                    <TomSelect v-model="filters.status">
                        <option value="">All statuses</option>
                        <option v-for="(label, key) in documentStatuses" :key="key" :value="key">{{ label }}</option>
                    </TomSelect>
                </div>

                <div class="flex flex-wrap items-center gap-3 mt-4">
                    <button type="button" @click="applyFilters" class="inline-flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition">
                        <Lucide icon="Filter" class="w-4 h-4" />
                        Apply Filters
                    </button>
                    <button type="button" @click="resetFilters" class="inline-flex items-center gap-2 px-4 py-2 border border-slate-300 rounded-lg text-slate-600 hover:bg-slate-50 transition">
                        <Lucide icon="RotateCcw" class="w-4 h-4" />
                        Clear
                    </button>
                </div>
            </div>

            <div v-if="hasMaintenanceRecords || hasRepairRecords" class="box box--stacked p-5 mb-6">
                <h2 class="text-base font-semibold text-slate-800 mb-4 flex items-center gap-2">
                    <Lucide icon="FileText" class="w-4 h-4 text-primary" />
                    Generate Reports
                </h2>
                <div class="flex flex-wrap gap-3">
                    <button
                        v-if="hasMaintenanceRecords"
                        type="button"
                        :disabled="generatingMaintenance"
                        @click="generateMaintenanceReport"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium bg-primary text-white hover:bg-primary/90 transition disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <Lucide v-if="generatingMaintenance" icon="Loader" class="w-4 h-4 animate-spin" />
                        <Lucide v-else icon="FileOutput" class="w-4 h-4" />
                        {{ generatingMaintenance ? 'Generating…' : 'Maintenance Report' }}
                    </button>

                    <button
                        v-if="hasRepairRecords"
                        type="button"
                        :disabled="generatingRepair"
                        @click="generateRepairReport"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium bg-warning text-white hover:bg-warning/90 transition disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <Lucide v-if="generatingRepair" icon="Loader" class="w-4 h-4 animate-spin" />
                        <Lucide v-else icon="FileOutput" class="w-4 h-4" />
                        {{ generatingRepair ? 'Generating…' : 'Repair Report' }}
                    </button>
                </div>
                <p class="text-xs text-slate-400 mt-3">Generates a PDF with all records for this vehicle and saves it to the documents list above.</p>
            </div>

            <div class="box box--stacked p-0 overflow-hidden">
                <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200/60">
                    <div>
                        <h2 class="text-base font-semibold text-slate-800">Documents</h2>
                        <p class="text-sm text-slate-500">{{ documents.total }} files</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-slate-50/80">
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Type</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Document</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Dates</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Status</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="document in documents.data" :key="document.id" class="border-b border-slate-100 hover:bg-slate-50/50 transition">
                                <td class="px-5 py-4">
                                    <div class="font-medium text-slate-800">{{ document.document_type_label }}</div>
                                    <div class="text-xs text-slate-500">{{ document.document_number ?? 'No document number' }}</div>
                                </td>
                                <td class="px-5 py-4">
                                    <a v-if="document.preview_url" :href="document.preview_url" target="_blank" class="font-medium text-primary hover:text-primary/80">
                                        {{ document.file_name ?? 'Open file' }}
                                    </a>
                                    <div v-else class="font-medium text-slate-700">No file linked</div>
                                    <div class="text-xs text-slate-500 mt-1">{{ document.size_label ?? 'Unknown size' }} · {{ (document.file_type ?? 'file').toUpperCase() }}</div>
                                </td>
                                <td class="px-5 py-4 text-sm text-slate-600">
                                    <div>Issued: {{ document.issued_date ?? 'N/A' }}</div>
                                    <div class="text-xs text-slate-400">Expires: {{ document.expiration_date ?? 'N/A' }}</div>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium" :class="documentStatusClass(document)">
                                        {{ document.status_label }}
                                    </span>
                                    <div v-if="document.is_expiring_soon" class="text-xs text-warning mt-1">Expiring soon</div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center justify-center gap-2">
                                        <a v-if="document.preview_url" :href="document.preview_url" target="_blank" class="p-1.5 text-slate-400 hover:text-primary transition" title="Preview"><Lucide icon="Eye" class="w-4 h-4" /></a>
                                        <button type="button" @click="openEdit(document)" class="p-1.5 text-slate-400 hover:text-primary transition" title="Edit"><Lucide icon="PenLine" class="w-4 h-4" /></button>
                                        <button type="button" @click="deleteDocument(document)" class="p-1.5 text-slate-400 hover:text-danger transition" title="Delete"><Lucide icon="Trash2" class="w-4 h-4" /></button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="!documents.data.length">
                                <td colspan="5" class="px-5 py-12 text-center text-slate-400">
                                    <Lucide icon="Files" class="w-12 h-12 mx-auto mb-3 text-slate-300" />
                                    <p>No documents found</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="documents.last_page > 1" class="p-4 border-t border-slate-200/60 flex items-center justify-between">
                    <span class="text-sm text-slate-500">{{ documents.total }} total records</span>
                    <div class="flex gap-1">
                        <template v-for="link in documents.links" :key="link.label">
                            <Link v-if="link.url" :href="link.url" class="px-3 py-1 text-sm rounded" :class="link.active ? 'bg-primary text-white' : 'text-slate-600 hover:bg-slate-100'" v-html="link.label" />
                            <span v-else class="px-3 py-1 text-sm text-slate-300" v-html="link.label" />
                        </template>
                    </div>
                </div>
            </div>

            <!-- Maintenance Reports Table -->
            <div v-if="maintenanceReports.length" class="box box--stacked p-0 overflow-hidden mt-6">
                <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200/60 bg-primary/5">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-primary/10 rounded-lg">
                            <Lucide icon="ClipboardList" class="w-4 h-4 text-primary" />
                        </div>
                        <div>
                            <h2 class="text-base font-semibold text-slate-800">Maintenance Reports</h2>
                            <p class="text-sm text-slate-500">{{ maintenanceReports.length }} generated report{{ maintenanceReports.length !== 1 ? 's' : '' }}</p>
                        </div>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-slate-50/80">
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Report</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">File</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Generated</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="doc in maintenanceReports" :key="doc.id" class="border-b border-slate-100 hover:bg-slate-50/50 transition">
                                <td class="px-5 py-4">
                                    <div class="font-medium text-slate-800">{{ doc.document_number ?? 'Maintenance Report' }}</div>
                                    <div class="text-xs text-slate-500 mt-0.5 max-w-xs truncate">{{ doc.notes }}</div>
                                </td>
                                <td class="px-5 py-4">
                                    <a v-if="doc.preview_url" :href="doc.preview_url" target="_blank" class="font-medium text-primary hover:text-primary/80">
                                        {{ doc.file_name ?? 'Open PDF' }}
                                    </a>
                                    <span v-else class="text-slate-400 text-sm">No file</span>
                                    <div v-if="doc.size_label" class="text-xs text-slate-400 mt-0.5">{{ doc.size_label }} · PDF</div>
                                </td>
                                <td class="px-5 py-4 text-sm text-slate-600">{{ doc.created_at }}</td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center justify-center gap-2">
                                        <a v-if="doc.preview_url" :href="doc.preview_url" target="_blank" class="p-1.5 text-slate-400 hover:text-primary transition" title="Preview"><Lucide icon="Eye" class="w-4 h-4" /></a>
                                        <button type="button" @click="deleteDocument(doc)" class="p-1.5 text-slate-400 hover:text-danger transition" title="Delete"><Lucide icon="Trash2" class="w-4 h-4" /></button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Repair Reports Table -->
            <div v-if="repairReports.length" class="box box--stacked p-0 overflow-hidden mt-6">
                <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200/60 bg-warning/5">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-warning/10 rounded-lg">
                            <Lucide icon="Wrench" class="w-4 h-4 text-warning" />
                        </div>
                        <div>
                            <h2 class="text-base font-semibold text-slate-800">Repair Reports</h2>
                            <p class="text-sm text-slate-500">{{ repairReports.length }} generated report{{ repairReports.length !== 1 ? 's' : '' }}</p>
                        </div>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-slate-50/80">
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Report</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">File</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Generated</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="doc in repairReports" :key="doc.id" class="border-b border-slate-100 hover:bg-slate-50/50 transition">
                                <td class="px-5 py-4">
                                    <div class="font-medium text-slate-800">{{ doc.document_number ?? 'Repair Report' }}</div>
                                    <div class="text-xs text-slate-500 mt-0.5 max-w-xs truncate">{{ doc.notes }}</div>
                                </td>
                                <td class="px-5 py-4">
                                    <a v-if="doc.preview_url" :href="doc.preview_url" target="_blank" class="font-medium text-primary hover:text-primary/80">
                                        {{ doc.file_name ?? 'Open PDF' }}
                                    </a>
                                    <span v-else class="text-slate-400 text-sm">No file</span>
                                    <div v-if="doc.size_label" class="text-xs text-slate-400 mt-0.5">{{ doc.size_label }} · PDF</div>
                                </td>
                                <td class="px-5 py-4 text-sm text-slate-600">{{ doc.created_at }}</td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center justify-center gap-2">
                                        <a v-if="doc.preview_url" :href="doc.preview_url" target="_blank" class="p-1.5 text-slate-400 hover:text-primary transition" title="Preview"><Lucide icon="Eye" class="w-4 h-4" /></a>
                                        <button type="button" @click="deleteDocument(doc)" class="p-1.5 text-slate-400 hover:text-danger transition" title="Delete"><Lucide icon="Trash2" class="w-4 h-4" /></button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    <Dialog :open="showModal" @close="showModal = false" size="xl" static-backdrop>
        <Dialog.Panel class="w-full max-w-[860px] max-h-[90vh] overflow-y-auto">
            <div class="sticky top-0 bg-white px-6 pt-6 pb-4 border-b border-slate-200 z-10">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-primary/10 rounded-lg">
                            <Lucide :icon="modalMode === 'create' ? 'Plus' : 'PenLine'" class="w-5 h-5 text-primary" />
                        </div>
                        <h3 class="text-lg font-bold text-slate-800">{{ modalMode === 'create' ? 'Add Vehicle Document' : 'Edit Vehicle Document' }}</h3>
                    </div>
                    <button @click="showModal = false" class="p-1.5 rounded-lg hover:bg-slate-100 text-slate-400">
                        <Lucide icon="X" class="w-5 h-5" />
                    </button>
                </div>
            </div>

            <div class="px-6 py-5 space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Document Type <span class="text-red-500">*</span></label>
                        <TomSelect v-model="form.document_type">
                            <option value="">Select type</option>
                            <option v-for="(label, key) in documentTypes" :key="key" :value="key">{{ label }}</option>
                        </TomSelect>
                        <p v-if="errors.document_type" class="text-xs text-red-500 mt-1">{{ errors.document_type[0] }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Document Number</label>
                        <FormInput v-model="form.document_number" type="text" placeholder="Optional number or identifier" />
                        <p v-if="errors.document_number" class="text-xs text-red-500 mt-1">{{ errors.document_number[0] }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Issued Date</label>
                        <Litepicker v-model="form.issued_date" :options="lpOptions" />
                        <p v-if="errors.issued_date" class="text-xs text-red-500 mt-1">{{ errors.issued_date[0] }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Expiration Date</label>
                        <Litepicker v-model="form.expiration_date" :options="lpOptions" />
                        <p v-if="errors.expiration_date" class="text-xs text-red-500 mt-1">{{ errors.expiration_date[0] }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Status</label>
                        <TomSelect v-model="form.status">
                            <option v-for="(label, key) in documentStatuses" :key="key" :value="key">{{ label }}</option>
                        </TomSelect>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Replace / Upload File <span v-if="modalMode === 'create'" class="text-red-500">*</span></label>
                        <input type="file" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" @change="onFileChange" />
                        <p v-if="errors.document_file" class="text-xs text-red-500 mt-1">{{ errors.document_file[0] }}</p>
                        <a v-if="modalMode === 'edit' && form.preview_url" :href="form.preview_url" target="_blank" class="inline-flex items-center gap-2 text-xs text-primary hover:underline mt-2">
                            <Lucide icon="Eye" class="w-3 h-3" />
                            Open current file
                        </a>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-slate-700 mb-1">Notes</label>
                        <FormTextarea v-model="form.notes" rows="4" placeholder="Additional document notes..." />
                        <p v-if="errors.notes" class="text-xs text-red-500 mt-1">{{ errors.notes[0] }}</p>
                    </div>
                </div>
            </div>

            <div class="sticky bottom-0 bg-white px-6 py-4 border-t border-slate-200 flex justify-end gap-3">
                <button @click="showModal = false" class="px-4 py-2 text-sm rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 transition-colors font-medium">Cancel</button>
                <button @click="saveForm" :disabled="saving" class="px-4 py-2 text-sm rounded-lg bg-primary text-white hover:bg-primary/90 transition-colors font-medium disabled:opacity-60 flex items-center gap-2">
                    <Lucide v-if="saving" icon="Loader" class="w-4 h-4 animate-spin" />
                    {{ saving ? 'Saving…' : (modalMode === 'create' ? 'Create Document' : 'Save Changes') }}
                </button>
            </div>
        </Dialog.Panel>
    </Dialog>
</template>
