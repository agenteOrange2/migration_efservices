<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { reactive } from 'vue'
import Button from '@/components/Base/Button'
import { FormInput } from '@/components/Base/Form'
import Lucide from '@/components/Base/Lucide'
import Litepicker from '@/components/Base/Litepicker/Litepicker.vue'
import TomSelect from '@/components/Base/TomSelect/TomSelect.vue'
import RazeLayout from '@/layouts/RazeLayout.vue'

declare function route(name: string, params?: any): string

defineOptions({ layout: RazeLayout })

const pickerOptions = { singleMode: true, format: 'M/D/YYYY', autoApply: true }

interface PaginationLink {
    url: string | null
    label: string
    active: boolean
}

interface DocumentRow {
    id: number
    school_id: number | null
    school_name: string | null
    carrier_name: string | null
    driver_name: string
    file_name: string
    mime_type: string | null
    file_type: string
    size: number
    size_label: string
    created_at_display: string | null
    preview_url: string
}

interface TrainingSchoolDocumentsRouteNames {
    index: string
    show: string
    edit: string
    documentsIndex: string
    documentsShow: string
    mediaDestroy: string
}

const props = withDefaults(defineProps<{
    documents: { data: DocumentRow[]; links: PaginationLink[]; total: number; last_page: number }
    filters: { search_term: string; carrier_filter: string; school_filter: string; date_from: string; date_to: string; file_type: string }
    carriers: { id: number; name: string }[]
    schools: { id: number; school_name: string; carrier_name: string | null }[]
    school: { id: number; school_name: string; driver_name: string; carrier_name: string | null } | null
    stats: { total: number; pdf: number; images: number; docs: number }
    routeNames?: TrainingSchoolDocumentsRouteNames
    isCarrierContext?: boolean
}>(), {
    routeNames: () => ({
        index: 'admin.training-schools.index',
        show: 'admin.training-schools.show',
        edit: 'admin.training-schools.edit',
        documentsIndex: 'admin.training-schools.documents.index',
        documentsShow: 'admin.training-schools.documents.show',
        mediaDestroy: 'admin.training-schools.media.destroy',
    }),
    isCarrierContext: false,
})

const filters = reactive({ ...props.filters })

function namedRoute(name: keyof TrainingSchoolDocumentsRouteNames, params?: any) {
    return route(props.routeNames[name], params)
}

function applyFilters() {
    const target = props.school
        ? namedRoute('documentsShow', props.school.id)
        : namedRoute('documentsIndex')

    router.get(target, {
        search_term: filters.search_term || undefined,
        carrier_filter: !props.school && !props.isCarrierContext ? filters.carrier_filter || undefined : undefined,
        school_filter: !props.school ? filters.school_filter || undefined : undefined,
        date_from: filters.date_from || undefined,
        date_to: filters.date_to || undefined,
        file_type: filters.file_type || undefined,
    }, { preserveState: true, replace: true })
}

function resetFilters() {
    filters.search_term = ''
    filters.carrier_filter = props.isCarrierContext ? props.filters.carrier_filter ?? '' : ''
    filters.school_filter = props.school ? String(props.school.id) : ''
    filters.date_from = ''
    filters.date_to = ''
    filters.file_type = ''
    applyFilters()
}

function deleteDocument(document: DocumentRow) {
    if (!confirm(`Delete "${document.file_name}"?`)) return
    router.delete(namedRoute('mediaDestroy', document.id), { preserveScroll: true })
}
</script>

<template>
    <Head :title="school ? 'Training School Documents' : 'All Training School Documents'" />

    <div class="grid grid-cols-12 gap-x-6 gap-y-10">
        <div class="col-span-12">
            <div class="box box--stacked p-6 mb-6">
                <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                            <Lucide icon="Files" class="w-8 h-8 text-primary" />
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-slate-800">{{ school ? 'Training School Documents' : 'All Training School Documents' }}</h1>
                            <p class="text-slate-500">
                                {{ school ? `${school.school_name} - ${school.driver_name}` : 'Review every training school document in one place.' }}
                            </p>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <Link v-if="school" :href="namedRoute('show', school.id)">
                            <Button variant="outline-primary" class="flex items-center gap-2">
                                <Lucide icon="Eye" class="w-4 h-4" />
                                View School
                            </Button>
                        </Link>
                        <Link :href="namedRoute('index')">
                            <Button variant="outline-secondary" class="flex items-center gap-2">
                                <Lucide icon="ArrowLeft" class="w-4 h-4" />
                                Back to Training Schools
                            </Button>
                        </Link>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
                <div class="box box--stacked rounded-xl border border-dashed border-slate-300/80 p-5">
                    <p class="text-sm text-slate-500">Total Documents</p>
                    <p class="mt-1 text-2xl font-semibold text-slate-800">{{ stats.total }}</p>
                </div>
                <div class="box box--stacked rounded-xl border border-dashed border-slate-300/80 p-5">
                    <p class="text-sm text-slate-500">PDF Files</p>
                    <p class="mt-1 text-2xl font-semibold text-slate-800">{{ stats.pdf }}</p>
                </div>
                <div class="box box--stacked rounded-xl border border-dashed border-slate-300/80 p-5">
                    <p class="text-sm text-slate-500">Images</p>
                    <p class="mt-1 text-2xl font-semibold text-slate-800">{{ stats.images }}</p>
                </div>
                <div class="box box--stacked rounded-xl border border-dashed border-slate-300/80 p-5">
                    <p class="text-sm text-slate-500">Word Docs</p>
                    <p class="mt-1 text-2xl font-semibold text-slate-800">{{ stats.docs }}</p>
                </div>
            </div>

            <div class="box box--stacked p-5 mb-6">
                <div class="grid grid-cols-1 lg:grid-cols-6 gap-4">
                    <div class="lg:col-span-2 relative">
                        <Lucide icon="Search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                        <FormInput v-model="filters.search_term" type="text" class="pl-10" placeholder="Search document, school, driver..." />
                    </div>

                    <TomSelect v-if="!school && !props.isCarrierContext" v-model="filters.carrier_filter">
                        <option value="">All Carriers</option>
                        <option v-for="carrier in carriers" :key="carrier.id" :value="String(carrier.id)">{{ carrier.name }}</option>
                    </TomSelect>

                    <TomSelect v-if="!school" v-model="filters.school_filter">
                        <option value="">All Schools</option>
                        <option v-for="schoolOption in schools" :key="schoolOption.id" :value="String(schoolOption.id)">
                            {{ schoolOption.school_name }}{{ !props.isCarrierContext && schoolOption.carrier_name ? ` - ${schoolOption.carrier_name}` : '' }}
                        </option>
                    </TomSelect>

                    <TomSelect v-model="filters.file_type">
                        <option value="">All File Types</option>
                        <option value="pdf">PDF</option>
                        <option value="image">Images</option>
                        <option value="doc">Documents</option>
                    </TomSelect>

                    <Litepicker v-model="filters.date_from" :options="pickerOptions" />
                    <Litepicker v-model="filters.date_to" :options="pickerOptions" />
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
                                <th v-if="!props.isCarrierContext" class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Carrier</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Driver</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">School</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Document</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="document in documents.data" :key="document.id" class="border-b border-slate-100 hover:bg-slate-50/50 transition">
                                <td class="px-5 py-4 text-sm text-slate-500">{{ document.created_at_display }}</td>
                                <td v-if="!props.isCarrierContext" class="px-5 py-4 text-sm text-slate-600">{{ document.carrier_name ?? 'N/A' }}</td>
                                <td class="px-5 py-4 text-sm text-slate-700">{{ document.driver_name }}</td>
                                <td class="px-5 py-4 text-sm text-slate-700">{{ document.school_name ?? 'N/A' }}</td>
                                <td class="px-5 py-4">
                                    <a :href="document.preview_url" target="_blank" class="block font-medium text-primary hover:underline">{{ document.file_name }}</a>
                                    <div class="text-xs text-slate-500 mt-1">{{ document.size_label }} - {{ document.file_type.toUpperCase() }}</div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center justify-center gap-2">
                                        <a :href="document.preview_url" target="_blank" class="p-1.5 text-slate-400 hover:text-primary transition" title="Preview">
                                            <Lucide icon="Eye" class="w-4 h-4" />
                                        </a>
                                        <Link v-if="document.school_id" :href="namedRoute('show', document.school_id)" class="p-1.5 text-slate-400 hover:text-primary transition" title="Open school">
                                            <Lucide icon="GraduationCap" class="w-4 h-4" />
                                        </Link>
                                        <Link v-if="document.school_id" :href="namedRoute('edit', document.school_id)" class="p-1.5 text-slate-400 hover:text-primary transition" title="Edit school">
                                            <Lucide icon="PenLine" class="w-4 h-4" />
                                        </Link>
                                        <button type="button" @click="deleteDocument(document)" class="p-1.5 text-slate-400 hover:text-red-500 transition" title="Delete">
                                            <Lucide icon="Trash2" class="w-4 h-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="!documents.data.length">
                                <td :colspan="props.isCarrierContext ? 5 : 6" class="px-5 py-12 text-center text-slate-400">
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
