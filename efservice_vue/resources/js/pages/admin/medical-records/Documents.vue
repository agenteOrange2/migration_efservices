<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { reactive } from 'vue'
import Lucide from '@/components/Base/Lucide'
import Litepicker from '@/components/Base/Litepicker/Litepicker.vue'
import TomSelect from '@/components/Base/TomSelect/TomSelect.vue'
import Button from '@/components/Base/Button'
import RazeLayout from '@/layouts/RazeLayout.vue'

declare function route(name: string, params?: any): string

defineOptions({ layout: RazeLayout })

const lpOptions = { singleMode: true, format: 'M/D/YYYY', autoApply: true }

interface PaginationLink {
    url: string | null
    label: string
    active: boolean
}

interface DocumentRow {
    id: number
    file_name: string
    collection_name: string
    collection_label: string
    mime_type: string | null
    file_type: string
    size: number
    size_label: string
    created_at_display: string | null
    preview_url: string
}

const props = defineProps<{
    documents: {
        data: DocumentRow[]
        links: PaginationLink[]
        total: number
        last_page: number
    }
    filters: {
        search_term: string
        date_from: string
        date_to: string
        collection: string
    }
    record: {
        id: number
        driver_name: string
        carrier_name: string | null
        social_security_number: string | null
        medical_examiner_name: string | null
    }
    stats: {
        total: number
        medical_card: number
        social_security_card: number
        medical_documents: number
    }
}>()

const filters = reactive({ ...props.filters })

function applyFilters() {
    router.get(route('admin.medical-records.documents.show', props.record.id), {
        search_term: filters.search_term || undefined,
        date_from: filters.date_from || undefined,
        date_to: filters.date_to || undefined,
        collection: filters.collection || undefined,
    }, { preserveState: true, replace: true })
}

function resetFilters() {
    filters.search_term = ''
    filters.date_from = ''
    filters.date_to = ''
    filters.collection = ''
    applyFilters()
}

function deleteDocument(document: DocumentRow) {
    if (!confirm(`Delete "${document.file_name}"?`)) return

    router.delete(route('admin.medical-records.media.destroy', document.id), {
        preserveScroll: true,
    })
}
</script>

<template>
    <Head title="Medical Record Documents" />

    <div class="grid grid-cols-12 gap-x-6 gap-y-10">
        <div class="col-span-12">
            <div class="box box--stacked p-6 mb-6">
                <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                            <Lucide icon="Files" class="w-8 h-8 text-primary" />
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-slate-800">Medical Record Documents</h1>
                            <p class="text-slate-500">Documents for {{ record.driver_name }}{{ record.social_security_number ? ` · SSN ${record.social_security_number}` : '' }}</p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <Link :href="route('admin.medical-records.edit', record.id)">
                            <Button variant="outline-secondary" class="flex items-center gap-2">
                                <Lucide icon="PenLine" class="w-4 h-4" />
                                Edit Record
                            </Button>
                        </Link>
                        <Link :href="route('admin.medical-records.index')">
                            <Button variant="outline-secondary" class="flex items-center gap-2">
                                <Lucide icon="ArrowLeft" class="w-4 h-4" />
                                Back to Medical Records
                            </Button>
                        </Link>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="box box--stacked rounded-xl border border-dashed border-slate-300/80 p-5">
                    <p class="text-sm text-slate-500">Total Documents</p>
                    <p class="mt-1 text-2xl font-semibold text-slate-800">{{ stats.total }}</p>
                </div>
                <div class="box box--stacked rounded-xl border border-dashed border-slate-300/80 p-5">
                    <p class="text-sm text-slate-500">Medical Card</p>
                    <p class="mt-1 text-2xl font-semibold text-slate-800">{{ stats.medical_card }}</p>
                </div>
                <div class="box box--stacked rounded-xl border border-dashed border-slate-300/80 p-5">
                    <p class="text-sm text-slate-500">Social Security Card</p>
                    <p class="mt-1 text-2xl font-semibold text-slate-800">{{ stats.social_security_card }}</p>
                </div>
            </div>

            <div class="box box--stacked rounded-xl border border-dashed border-slate-300/80 p-5 mb-6">
                <p class="text-sm text-slate-500">Additional Medical Documents</p>
                <p class="mt-1 text-2xl font-semibold text-slate-800">{{ stats.medical_documents }}</p>
            </div>

            <div class="box box--stacked p-5 mb-6">
                <div class="grid grid-cols-1 lg:grid-cols-5 gap-4">
                    <div class="lg:col-span-2 relative">
                        <Lucide icon="Search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                        <input v-model="filters.search_term" type="text" class="w-full rounded-lg border border-slate-200 px-3 py-2 pl-10 text-sm" placeholder="Search document name or type..." />
                    </div>

                    <TomSelect v-model="filters.collection">
                        <option value="">All Collections</option>
                        <option value="medical_card">Medical Card</option>
                        <option value="social_security_card">Social Security Card</option>
                        <option value="medical_documents">Medical Documents</option>
                    </TomSelect>

                    <Litepicker v-model="filters.date_from" :options="lpOptions" />
                    <Litepicker v-model="filters.date_to" :options="lpOptions" />
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
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Created</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Collection</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Document</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="document in documents.data" :key="document.id" class="border-b border-slate-100 hover:bg-slate-50/50 transition">
                                <td class="px-5 py-4 text-sm text-slate-500">{{ document.created_at_display }}</td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">
                                        {{ document.collection_label }}
                                    </span>
                                </td>
                                <td class="px-5 py-4">
                                    <a :href="document.preview_url" target="_blank" class="font-medium text-primary hover:text-primary/80">
                                        {{ document.file_name }}
                                    </a>
                                    <div class="text-xs text-slate-500 mt-1">
                                        {{ document.size_label }} · {{ document.file_type.toUpperCase() }}
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center justify-center gap-2">
                                        <a :href="document.preview_url" target="_blank" class="p-1.5 text-slate-400 hover:text-primary transition" title="Preview">
                                            <Lucide icon="Eye" class="w-4 h-4" />
                                        </a>
                                        <Link :href="route('admin.medical-records.edit', record.id)" class="p-1.5 text-slate-400 hover:text-amber-500 transition" title="Edit record">
                                            <Lucide icon="PenLine" class="w-4 h-4" />
                                        </Link>
                                        <button type="button" @click="deleteDocument(document)" class="p-1.5 text-slate-400 hover:text-red-500 transition" title="Delete">
                                            <Lucide icon="Trash2" class="w-4 h-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="!documents.data.length">
                                <td colspan="4" class="px-5 py-12 text-center text-slate-400">
                                    <Lucide icon="FileText" class="w-12 h-12 mx-auto mb-3 text-slate-300" />
                                    <p>No documents found</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="documents.last_page > 1" class="p-4 border-t border-slate-200/60 flex items-center justify-between">
                    <span class="text-sm text-slate-500">{{ documents.total }} documents</span>
                    <div class="flex gap-1">
                        <template v-for="link in documents.links" :key="link.label">
                            <Link v-if="link.url" :href="link.url" class="px-3 py-1 text-sm rounded" :class="link.active ? 'bg-primary text-white' : 'text-slate-600 hover:bg-slate-100'" v-html="link.label" />
                            <span v-else class="px-3 py-1 text-sm text-slate-300" v-html="link.label" />
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
