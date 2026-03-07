<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import Lucide from '@/components/Base/Lucide'
import { FormInput, FormSelect } from '@/components/Base/Form'
import RazeLayout from '@/layouts/RazeLayout.vue'

interface DocumentType {
    id: number
    name: string
    requirement: boolean | number
    has_default_file: boolean
    default_file_url: string | null
    carrier_documents_count: number
    created_at: string
}

const props = defineProps<{
    documentTypes: DocumentType[]
}>()

defineOptions({ layout: RazeLayout })

const search = ref('')
const requirementFilter = ref('')

const filtered = computed(() => {
    let result = props.documentTypes
    if (search.value) {
        const q = search.value.toLowerCase()
        result = result.filter(dt => dt.name.toLowerCase().includes(q))
    }
    if (requirementFilter.value !== '') {
        const val = requirementFilter.value === '1'
        result = result.filter(dt => Boolean(dt.requirement) === val)
    }
    return result
})

function deleteType(dt: DocumentType) {
    if (dt.carrier_documents_count > 0) {
        alert('Cannot delete: this document type is in use by carriers.')
        return
    }
    if (confirm(`Delete "${dt.name}"?`)) {
        router.delete(route('admin.document-types.destroy', dt.id))
    }
}
</script>

<template>
    <Head title="Document Types" />

    <div class="grid grid-cols-12 gap-x-6 gap-y-10">
        <div class="col-span-12">
            <div class="box box--stacked p-6 mb-6">
                <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                            <Lucide icon="FileText" class="w-8 h-8 text-primary" />
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-slate-800">Document Types</h1>
                            <p class="text-slate-500">Manage required document types for carriers</p>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <Link :href="route('admin.document-types.default-policy')" class="inline-flex items-center gap-2 px-4 py-2 border border-slate-300 rounded-lg text-slate-600 hover:bg-slate-50 transition">
                            <Lucide icon="Shield" class="w-4 h-4" /> Company Policy
                        </Link>
                        <Link :href="route('admin.document-types.create')" class="inline-flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition">
                            <Lucide icon="Plus" class="w-4 h-4" /> Add Document Type
                        </Link>
                    </div>
                </div>
            </div>

            <div class="box box--stacked p-0">
                <div class="flex flex-col sm:flex-row gap-3 p-5 border-b border-slate-200/60">
                    <div class="relative flex-1">
                        <Lucide icon="Search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                        <FormInput v-model="search" type="text" placeholder="Search document types..." class="pl-10" />
                    </div>
                    <FormSelect v-model="requirementFilter" class="w-full sm:w-40">
                        <option value="">All</option>
                        <option value="1">Required</option>
                        <option value="0">Optional</option>
                    </FormSelect>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-slate-50/80">
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Name</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Required</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Default File</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Carriers</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="dt in filtered" :key="dt.id" class="border-b border-slate-100 hover:bg-slate-50/50 transition">
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="p-2 rounded-lg" :class="dt.requirement ? 'bg-red-50' : 'bg-slate-50'">
                                            <Lucide :icon="dt.requirement ? 'FileWarning' : 'File'" class="w-4 h-4" :class="dt.requirement ? 'text-red-500' : 'text-slate-400'" />
                                        </div>
                                        <span class="font-medium text-slate-700">{{ dt.name }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" :class="dt.requirement ? 'bg-red-100 text-red-700' : 'bg-slate-100 text-slate-500'">
                                        {{ dt.requirement ? 'Yes' : 'No' }}
                                    </span>
                                </td>
                                <td class="px-5 py-4">
                                    <template v-if="dt.has_default_file">
                                        <a :href="dt.default_file_url ?? '#'" target="_blank" class="inline-flex items-center gap-1 text-sm text-primary hover:underline">
                                            <Lucide icon="Download" class="w-3 h-3" /> View File
                                        </a>
                                    </template>
                                    <span v-else class="text-sm text-slate-400">None</span>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="text-sm text-slate-600">{{ dt.carrier_documents_count }}</span>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center justify-center gap-2">
                                        <Link :href="route('admin.document-types.edit', dt.id)" class="p-1.5 text-slate-400 hover:text-amber-500 transition" title="Edit">
                                            <Lucide icon="PenLine" class="w-4 h-4" />
                                        </Link>
                                        <button @click="deleteType(dt)" class="p-1.5 text-slate-400 hover:text-red-500 transition" title="Delete" :disabled="dt.carrier_documents_count > 0">
                                            <Lucide icon="Trash2" class="w-4 h-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="filtered.length === 0">
                                <td colspan="5" class="px-5 py-12 text-center text-slate-400">
                                    <Lucide icon="Inbox" class="w-12 h-12 mx-auto mb-3 text-slate-300" />
                                    <p>No document types found</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>
