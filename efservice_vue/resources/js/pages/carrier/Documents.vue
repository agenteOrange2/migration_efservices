<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import RazeLayout from '@/layouts/RazeLayout.vue'
import Lucide from '@/components/Base/Lucide'

interface MappedDocument {
    type_id: number
    type_name: string
    requirement: string
    document_id: number | null
    status: number | null
    status_name: string
    file_url: string | null
    notes: string | null
    date: string | null
}

interface Props {
    carrier: { id: number; name: string; slug: string }
    mappedDocuments: MappedDocument[]
    stats: {
        total: number
        approved: number
        pending: number
        rejected: number
        percentage: number
    }
}

const props = defineProps<Props>()

// --- Filter state ---
const statusFilter = ref<string>('all')
const requirementFilter = ref<string>('all')
const searchQuery = ref('')

// --- Upload modal state ---
const uploadModalOpen = ref(false)
const selectedDoc = ref<MappedDocument | null>(null)
const uploadForm = useForm({ document: null as File | null })
const dragOver = ref(false)

function openUpload(doc: MappedDocument) {
    selectedDoc.value = doc
    uploadForm.reset()
    uploadModalOpen.value = true
}

function closeUpload() {
    uploadModalOpen.value = false
    selectedDoc.value = null
    uploadForm.reset()
}

function handleFileChange(e: Event) {
    const target = e.target as HTMLInputElement
    uploadForm.document = target.files?.[0] ?? null
}

function handleDrop(e: DragEvent) {
    dragOver.value = false
    const file = e.dataTransfer?.files?.[0]
    if (file) uploadForm.document = file
}

function submitUpload() {
    if (!selectedDoc.value || !uploadForm.document) return
    uploadForm.post(route('carrier.documents.upload', { documentTypeId: selectedDoc.value.type_id }), {
        onSuccess: () => closeUpload(),
    })
}

function deleteDocument(doc: MappedDocument) {
    if (!confirm(`Delete "${doc.type_name}"? This cannot be undone.`)) return
    router.delete(route('carrier.documents.delete', { documentTypeId: doc.type_id }))
}

// --- Filtered documents ---
const filteredDocuments = computed(() => {
    return props.mappedDocuments.filter(doc => {
        const matchStatus = statusFilter.value === 'all'
            || (statusFilter.value === 'uploaded' && doc.status !== null)
            || (statusFilter.value === 'not_uploaded' && doc.status === null)
            || (statusFilter.value === 'approved' && doc.status === 1)
            || (statusFilter.value === 'pending' && doc.status === 0)
            || (statusFilter.value === 'rejected' && doc.status === 2)

        const matchReq = requirementFilter.value === 'all'
            || doc.requirement === requirementFilter.value

        const matchSearch = !searchQuery.value
            || doc.type_name.toLowerCase().includes(searchQuery.value.toLowerCase())

        return matchStatus && matchReq && matchSearch
    })
})

// --- Status helpers ---
function statusBadgeClass(status: number | null): string {
    if (status === null) return 'bg-slate-100 text-slate-500 border-slate-200'
    const map: Record<number, string> = {
        0: 'bg-warning/10 text-warning border-warning/20',
        1: 'bg-success/10 text-success border-success/20',
        2: 'bg-danger/10 text-danger border-danger/20',
        3: 'bg-info/10 text-info border-info/20',
    }
    return map[status] ?? 'bg-slate-100 text-slate-500'
}

function statusIcon(status: number | null): string {
    if (status === null) return 'Upload'
    const map: Record<number, string> = { 0: 'Clock', 1: 'CheckCircle', 2: 'XCircle', 3: 'Loader' }
    return map[status] ?? 'FileText'
}

function cardBorderClass(status: number | null, requirement: string): string {
    if (status === 1) return 'border-success/30 bg-success/5'
    if (status === 2) return 'border-danger/30 bg-danger/5'
    if (status === 0 || status === 3) return 'border-warning/30 bg-warning/5'
    if (requirement === 'mandatory') return 'border-danger/20 bg-slate-50'
    return 'border-slate-200 bg-white'
}

const progressWidth = computed(() => `${props.stats.percentage}%`)
</script>

<template>
    <Head title="Document Center" />

    <RazeLayout>
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">Document Center</h1>
                <p class="text-sm text-slate-500 mt-0.5">Manage your carrier documents for <strong>{{ carrier.name }}</strong></p>
            </div>
        </div>

        <div class="grid grid-cols-12 gap-6">

            <!-- ===== SIDEBAR ===== -->
            <div class="col-span-12 lg:col-span-3 flex flex-col gap-5">

                <!-- Progress Card -->
                <div class="box box--stacked p-5">
                    <div class="flex items-center gap-2 mb-4">
                        <Lucide icon="TrendingUp" class="w-4 h-4 text-primary" />
                        <h3 class="font-semibold text-slate-800 text-sm">Completion Progress</h3>
                    </div>
                    <div class="text-3xl font-bold text-slate-800 mb-1">{{ stats.percentage }}%</div>
                    <div class="h-2 w-full bg-slate-100 rounded-full overflow-hidden mb-4">
                        <div class="h-full rounded-full bg-primary transition-all duration-500" :style="{ width: progressWidth }" />
                    </div>
                    <div class="space-y-2">
                        <div class="flex items-center justify-between text-xs">
                            <span class="flex items-center gap-1.5 text-slate-500">
                                <span class="w-2 h-2 rounded-full bg-success inline-block" />
                                Approved
                            </span>
                            <span class="font-semibold text-success">{{ stats.approved }}</span>
                        </div>
                        <div class="flex items-center justify-between text-xs">
                            <span class="flex items-center gap-1.5 text-slate-500">
                                <span class="w-2 h-2 rounded-full bg-warning inline-block" />
                                Pending Review
                            </span>
                            <span class="font-semibold text-warning">{{ stats.pending }}</span>
                        </div>
                        <div class="flex items-center justify-between text-xs">
                            <span class="flex items-center gap-1.5 text-slate-500">
                                <span class="w-2 h-2 rounded-full bg-danger inline-block" />
                                Rejected
                            </span>
                            <span class="font-semibold text-danger">{{ stats.rejected }}</span>
                        </div>
                        <div class="flex items-center justify-between text-xs">
                            <span class="flex items-center gap-1.5 text-slate-500">
                                <span class="w-2 h-2 rounded-full bg-slate-300 inline-block" />
                                Not Uploaded
                            </span>
                            <span class="font-semibold text-slate-500">{{ stats.total - stats.approved - stats.pending - stats.rejected }}</span>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="box box--stacked p-5">
                    <div class="flex items-center gap-2 mb-4">
                        <Lucide icon="Filter" class="w-4 h-4 text-primary" />
                        <h3 class="font-semibold text-slate-800 text-sm">Filters</h3>
                    </div>

                    <!-- Search -->
                    <div class="relative mb-4">
                        <Lucide icon="Search" class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400" />
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Search documents..."
                            class="w-full pl-8 pr-3 py-2 text-xs border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                        />
                    </div>

                    <!-- Status Filter -->
                    <div class="mb-4">
                        <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Status</div>
                        <div class="space-y-1.5">
                            <label v-for="opt in [
                                { value: 'all', label: 'All Documents' },
                                { value: 'uploaded', label: 'Uploaded' },
                                { value: 'approved', label: 'Approved' },
                                { value: 'pending', label: 'Pending Review' },
                                { value: 'rejected', label: 'Rejected' },
                                { value: 'not_uploaded', label: 'Not Uploaded' },
                            ]" :key="opt.value"
                                class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" :value="opt.value" v-model="statusFilter"
                                    class="text-primary focus:ring-primary" />
                                <span class="text-xs text-slate-600">{{ opt.label }}</span>
                            </label>
                        </div>
                    </div>

                    <!-- Requirement Filter -->
                    <div>
                        <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Requirement</div>
                        <div class="space-y-1.5">
                            <label v-for="opt in [
                                { value: 'all', label: 'All' },
                                { value: 'mandatory', label: 'Mandatory' },
                                { value: 'optional', label: 'Optional' },
                            ]" :key="opt.value"
                                class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" :value="opt.value" v-model="requirementFilter"
                                    class="text-primary focus:ring-primary" />
                                <span class="text-xs text-slate-600">{{ opt.label }}</span>
                            </label>
                        </div>
                    </div>

                    <button @click="statusFilter = 'all'; requirementFilter = 'all'; searchQuery = ''"
                        class="mt-4 w-full text-xs text-slate-500 hover:text-primary py-1.5 border border-dashed border-slate-200 rounded-lg transition-colors">
                        Clear Filters
                    </button>
                </div>
            </div>

            <!-- ===== DOCUMENTS GRID ===== -->
            <div class="col-span-12 lg:col-span-9">
                <div class="flex items-center justify-between mb-4">
                    <span class="text-sm text-slate-500">
                        Showing <strong>{{ filteredDocuments.length }}</strong> of {{ mappedDocuments.length }} documents
                    </span>
                </div>

                <!-- Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                    <div
                        v-for="doc in filteredDocuments"
                        :key="doc.type_id"
                        class="rounded-xl border p-4 flex flex-col gap-3 transition-all duration-200 hover:shadow-md"
                        :class="cardBorderClass(doc.status, doc.requirement)"
                    >
                        <!-- Card Header -->
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-semibold text-slate-800 leading-tight truncate">{{ doc.type_name }}</div>
                                <div class="mt-1 flex items-center gap-1.5">
                                    <span
                                        class="text-[10px] font-semibold px-1.5 py-0.5 rounded uppercase tracking-wide"
                                        :class="doc.requirement === 'mandatory' ? 'bg-danger/10 text-danger' : 'bg-slate-100 text-slate-500'"
                                    >
                                        {{ doc.requirement }}
                                    </span>
                                </div>
                            </div>
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold border shrink-0"
                                :class="statusBadgeClass(doc.status)">
                                <Lucide :icon="statusIcon(doc.status)" class="w-3 h-3" />
                                {{ doc.status_name }}
                            </span>
                        </div>

                        <!-- Date if uploaded -->
                        <div v-if="doc.date" class="text-xs text-slate-400 flex items-center gap-1">
                            <Lucide icon="Calendar" class="w-3 h-3" />
                            Uploaded {{ doc.date }}
                        </div>

                        <!-- Notes -->
                        <div v-if="doc.notes" class="text-xs text-slate-500 bg-slate-50 rounded p-2 border border-slate-100">
                            {{ doc.notes }}
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center gap-2 mt-auto pt-2 border-t border-slate-100">
                            <!-- View file -->
                            <a v-if="doc.file_url"
                                :href="doc.file_url"
                                target="_blank"
                                class="flex items-center gap-1.5 text-xs text-primary hover:underline font-medium">
                                <Lucide icon="Eye" class="w-3.5 h-3.5" />
                                View
                            </a>

                            <!-- Upload / Replace -->
                            <button
                                @click="openUpload(doc)"
                                class="flex items-center gap-1.5 text-xs font-medium text-slate-600 hover:text-primary transition-colors">
                                <Lucide :icon="doc.file_url ? 'RefreshCw' : 'Upload'" class="w-3.5 h-3.5" />
                                {{ doc.file_url ? 'Replace' : 'Upload' }}
                            </button>

                            <!-- Delete -->
                            <button
                                v-if="doc.file_url"
                                @click="deleteDocument(doc)"
                                class="flex items-center gap-1.5 text-xs font-medium text-danger/60 hover:text-danger transition-colors ml-auto">
                                <Lucide icon="Trash2" class="w-3.5 h-3.5" />
                                Remove
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Empty state -->
                <div v-if="filteredDocuments.length === 0" class="flex flex-col items-center justify-center py-16 text-slate-400">
                    <Lucide icon="FileX" class="w-12 h-12 mb-3 text-slate-300" />
                    <p class="text-sm font-medium">No documents match your filters</p>
                    <button @click="statusFilter = 'all'; requirementFilter = 'all'; searchQuery = ''"
                        class="mt-3 text-xs text-primary hover:underline">
                        Clear filters
                    </button>
                </div>
            </div>
        </div>

        <!-- ===== UPLOAD MODAL ===== -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="uploadModalOpen"
                    class="fixed inset-0 z-50 flex items-center justify-center p-4"
                    @click.self="closeUpload">
                    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="closeUpload" />

                    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md z-10">
                        <!-- Modal Header -->
                        <div class="flex items-center justify-between p-5 border-b border-slate-100">
                            <div>
                                <h3 class="font-semibold text-slate-800">Upload Document</h3>
                                <p class="text-xs text-slate-500 mt-0.5">{{ selectedDoc?.type_name }}</p>
                            </div>
                            <button @click="closeUpload" class="p-1.5 rounded-lg hover:bg-slate-100 transition-colors">
                                <Lucide icon="X" class="w-4 h-4 text-slate-500" />
                            </button>
                        </div>

                        <!-- Modal Body -->
                        <div class="p-5">
                            <!-- Drop zone -->
                            <div
                                class="relative border-2 border-dashed rounded-xl p-8 text-center transition-colors cursor-pointer"
                                :class="dragOver ? 'border-primary bg-primary/5' : 'border-slate-200 hover:border-primary/50 hover:bg-slate-50'"
                                @dragover.prevent="dragOver = true"
                                @dragleave.prevent="dragOver = false"
                                @drop.prevent="handleDrop"
                                @click="($refs.fileInput as HTMLInputElement).click()"
                            >
                                <input
                                    ref="fileInput"
                                    type="file"
                                    accept=".pdf,.jpg,.jpeg,.png"
                                    class="hidden"
                                    @change="handleFileChange"
                                />
                                <Lucide icon="Upload" class="w-8 h-8 text-slate-300 mx-auto mb-3" />
                                <p class="text-sm font-medium text-slate-600">
                                    {{ uploadForm.document ? uploadForm.document.name : 'Drop file here or click to browse' }}
                                </p>
                                <p class="text-xs text-slate-400 mt-1">PDF, JPG, PNG — Max 10MB</p>
                            </div>

                            <!-- Error -->
                            <p v-if="uploadForm.errors.document" class="mt-2 text-xs text-danger">
                                {{ uploadForm.errors.document }}
                            </p>
                        </div>

                        <!-- Modal Footer -->
                        <div class="flex items-center justify-end gap-3 p-5 border-t border-slate-100">
                            <button @click="closeUpload"
                                class="px-4 py-2 text-sm text-slate-600 border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors">
                                Cancel
                            </button>
                            <button
                                @click="submitUpload"
                                :disabled="!uploadForm.document || uploadForm.processing"
                                class="px-4 py-2 text-sm font-medium text-white bg-primary rounded-lg hover:bg-primary/90 disabled:opacity-50 disabled:cursor-not-allowed transition-colors flex items-center gap-2">
                                <Lucide v-if="uploadForm.processing" icon="Loader" class="w-4 h-4 animate-spin" />
                                {{ uploadForm.processing ? 'Uploading...' : 'Upload' }}
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </RazeLayout>
</template>
