<script setup lang="ts">
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import Lucide from '@/components/Base/Lucide'
import { FormSelect, FormLabel } from '@/components/Base/Form'
import Button from '@/components/Base/Button'
import RazeLayout from '@/layouts/RazeLayout.vue'

interface DocumentItem {
    id: number
    type: { id: number; name: string; requirement: boolean }
    status_name: string
    has_file: boolean
    file_url: string | null
    notes: string | null
    updated_at: string
}

interface DocumentTypeItem {
    id: number
    name: string
    requirement: boolean
}

const props = defineProps<{
    carrier: { id: number; name: string; slug: string; mc_number: string | null; dot_number: string | null }
    documents: DocumentItem[]
    progress: { total: number; approved: number; percentage: number; status: string }
    documentTypes: DocumentTypeItem[]
}>()

defineOptions({ layout: RazeLayout })

const uploadingTypeId = ref<number | null>(null)

const uploadForm = useForm({
    document: null as File | null,
    notes: '',
})

function startUpload(typeId: number) {
    uploadingTypeId.value = typeId
    uploadForm.reset()
}

function cancelUpload() {
    uploadingTypeId.value = null
    uploadForm.reset()
}

function handleFile(e: Event) {
    const target = e.target as HTMLInputElement
    if (target.files?.[0]) uploadForm.document = target.files[0]
}

function submitUpload() {
    if (!uploadingTypeId.value) return
    uploadForm.post(route('admin.carriers-documents.upload', { carrier: props.carrier.slug, documentType: uploadingTypeId.value }), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => cancelUpload(),
    })
}

function updateStatus(docId: number, newStatus: string) {
    router.put(route('admin.carriers-documents.update-doc', { carrier: props.carrier.slug, document: docId }), {
        status: parseInt(newStatus),
    }, { preserveScroll: true })
}

function statusColor(name: string): string {
    if (name === 'Approved' || name === 'approved') return 'bg-emerald-100 text-emerald-700'
    if (name === 'In Process' || name === 'in_process') return 'bg-blue-100 text-blue-700'
    if (name === 'Pending' || name === 'pending') return 'bg-amber-100 text-amber-700'
    return 'bg-red-100 text-red-700'
}
</script>

<template>
    <Head :title="`Documents: ${carrier.name}`" />

    <div class="grid grid-cols-12 gap-x-6 gap-y-8">
        <div class="col-span-12">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-3">
                    <Link :href="route('admin.carriers-documents.index')" class="p-2 rounded-lg hover:bg-slate-100 transition">
                        <Lucide icon="ArrowLeft" class="w-5 h-5 text-slate-600" />
                    </Link>
                    <div>
                        <h1 class="text-2xl font-bold text-slate-800">{{ carrier.name }} - Documents</h1>
                        <p class="text-sm text-slate-500">
                            <span v-if="carrier.mc_number">MC: {{ carrier.mc_number }}</span>
                            <span v-if="carrier.dot_number" class="ml-3">DOT: {{ carrier.dot_number }}</span>
                        </p>
                    </div>
                </div>
                <Link :href="route('admin.carriers.show', carrier.slug)" class="inline-flex items-center gap-2 px-4 py-2 border border-slate-300 rounded-lg text-slate-600 hover:bg-slate-50 transition text-sm">
                    <Lucide icon="Building2" class="w-4 h-4" /> View Carrier
                </Link>
            </div>
        </div>

        <!-- Progress -->
        <div class="col-span-12">
            <div class="box box--stacked p-6">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-semibold text-slate-700">Document Completion</h3>
                    <span class="text-sm font-medium" :class="progress.percentage >= 100 ? 'text-emerald-600' : 'text-amber-600'">
                        {{ progress.approved }} / {{ progress.total }} ({{ Math.round(progress.percentage) }}%)
                    </span>
                </div>
                <div class="w-full bg-slate-100 rounded-full h-3">
                    <div class="h-3 rounded-full transition-all" :class="progress.percentage >= 100 ? 'bg-emerald-500' : progress.percentage >= 50 ? 'bg-amber-500' : 'bg-red-500'" :style="{ width: progress.percentage + '%' }"></div>
                </div>
            </div>
        </div>

        <!-- Documents List -->
        <div class="col-span-12">
            <div class="box box--stacked p-0">
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-slate-50/80">
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Document</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Required</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">File</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Status</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Notes</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="doc in documents" :key="doc.id" class="border-b border-slate-100 hover:bg-slate-50/50 transition">
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-2">
                                        <Lucide :icon="doc.has_file ? 'FileCheck' : 'File'" class="w-4 h-4" :class="doc.has_file ? 'text-emerald-500' : 'text-slate-300'" />
                                        <span class="font-medium text-slate-700">{{ doc.type.name }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="text-xs" :class="doc.type.requirement ? 'text-red-600 font-medium' : 'text-slate-400'">
                                        {{ doc.type.requirement ? 'Yes' : 'No' }}
                                    </span>
                                </td>
                                <td class="px-5 py-4">
                                    <a v-if="doc.has_file" :href="doc.file_url ?? '#'" target="_blank" class="inline-flex items-center gap-1 text-sm text-primary hover:underline">
                                        <Lucide icon="ExternalLink" class="w-3 h-3" /> View
                                    </a>
                                    <span v-else class="text-xs text-slate-400">Not uploaded</span>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" :class="statusColor(doc.status_name)">
                                        {{ doc.status_name }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-xs text-slate-500 max-w-[200px] truncate">{{ doc.notes ?? '-' }}</td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center justify-center gap-2">
                                        <button @click="startUpload(doc.type.id)" class="p-1.5 text-slate-400 hover:text-primary transition" title="Upload">
                                            <Lucide icon="Upload" class="w-4 h-4" />
                                        </button>
                                        <select @change="(e) => updateStatus(doc.id, (e.target as HTMLSelectElement).value)" class="text-xs border border-slate-200 rounded px-2 py-1">
                                            <option value="" disabled selected>Change Status</option>
                                            <option value="0">Pending</option>
                                            <option value="1">Approved</option>
                                            <option value="2">In Process</option>
                                            <option value="3">Rejected</option>
                                        </select>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Upload Modal -->
        <Teleport to="body">
            <div v-if="uploadingTypeId !== null" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" @click.self="cancelUpload">
                <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6 space-y-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-slate-800">Upload Document</h3>
                        <button @click="cancelUpload" class="p-1 hover:bg-slate-100 rounded"><Lucide icon="X" class="w-5 h-5" /></button>
                    </div>
                    <form @submit.prevent="submitUpload" class="space-y-4">
                        <div>
                            <FormLabel>File *</FormLabel>
                            <input type="file" accept=".pdf,.jpg,.jpeg,.png" @change="handleFile" class="w-full text-sm text-slate-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-primary/10 file:text-primary hover:file:bg-primary/20 file:cursor-pointer" />
                            <div v-if="uploadForm.errors.document" class="text-red-500 text-xs mt-1">{{ uploadForm.errors.document }}</div>
                        </div>
                        <div>
                            <FormLabel>Notes (optional)</FormLabel>
                            <textarea v-model="uploadForm.notes" rows="2" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm"></textarea>
                        </div>
                        <div class="flex justify-end gap-3">
                            <button type="button" @click="cancelUpload" class="px-4 py-2 border border-slate-300 rounded-lg text-sm">Cancel</button>
                            <Button type="submit" variant="primary" :disabled="uploadForm.processing || !uploadForm.document" class="px-4">
                                <Lucide icon="Upload" class="w-4 h-4 mr-1" /> Upload
                            </Button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </div>
</template>
