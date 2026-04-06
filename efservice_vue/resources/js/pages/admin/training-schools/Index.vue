<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { reactive } from 'vue'
import Button from '@/components/Base/Button'
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

interface SchoolRow {
    id: number
    created_at: string | null
    school_name: string
    city: string | null
    state: string | null
    date_start: string | null
    date_end: string | null
    graduated: boolean
    document_count: number
    driver: { id: number; name: string; email?: string | null } | null
    carrier: { id: number; name: string } | null
}

const props = defineProps<{
    trainingSchools: { data: SchoolRow[]; links: PaginationLink[]; total: number; last_page: number }
    filters: { search_term: string; carrier_filter: string; driver_filter: string; date_from: string; date_to: string; sort_field: string; sort_direction: string }
    drivers: { id: number; carrier_id: number | null; name: string; carrier_name?: string | null }[]
    carriers: { id: number; name: string }[]
    stats: { total: number; graduated: number; in_progress: number; documents: number }
}>()

const filters = reactive({ ...props.filters })

function applyFilters() {
    router.get(route('admin.training-schools.index'), {
        search_term: filters.search_term || undefined,
        carrier_filter: filters.carrier_filter || undefined,
        driver_filter: filters.driver_filter || undefined,
        date_from: filters.date_from || undefined,
        date_to: filters.date_to || undefined,
        sort_field: filters.sort_field || undefined,
        sort_direction: filters.sort_direction || undefined,
    }, { preserveState: true, replace: true })
}

function resetFilters() {
    filters.search_term = ''
    filters.carrier_filter = ''
    filters.driver_filter = ''
    filters.date_from = ''
    filters.date_to = ''
    filters.sort_field = 'created_at'
    filters.sort_direction = 'desc'
    applyFilters()
}

function deleteSchool(school: SchoolRow) {
    if (!confirm(`Delete "${school.school_name}"?`)) return
    router.delete(route('admin.training-schools.destroy', school.id), { preserveScroll: true })
}
</script>

<template>
    <Head title="Training Schools" />

    <div class="grid grid-cols-12 gap-x-6 gap-y-10">
        <div class="col-span-12">
            <div class="box box--stacked p-6 mb-6">
                <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                            <Lucide icon="GraduationCap" class="w-8 h-8 text-primary" />
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-slate-800">Training Schools</h1>
                            <p class="text-slate-500">Manage and review commercial driver training school records.</p>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <Link :href="route('admin.training-schools.documents.index')">
                            <Button variant="outline-primary" class="flex items-center gap-2">
                                <Lucide icon="Files" class="w-4 h-4" />
                                All Documents
                            </Button>
                        </Link>
                        <Link :href="route('admin.training-schools.create')">
                            <Button variant="primary" class="flex items-center gap-2">
                                <Lucide icon="Plus" class="w-4 h-4" />
                                Add Training School
                            </Button>
                        </Link>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
                <div class="box box--stacked rounded-xl border border-dashed border-slate-300/80 p-5">
                    <p class="text-sm text-slate-500">Total Records</p>
                    <p class="mt-1 text-2xl font-semibold text-slate-800">{{ stats.total }}</p>
                </div>
                <div class="box box--stacked rounded-xl border border-dashed border-slate-300/80 p-5">
                    <p class="text-sm text-slate-500">Graduated</p>
                    <p class="mt-1 text-2xl font-semibold text-slate-800">{{ stats.graduated }}</p>
                </div>
                <div class="box box--stacked rounded-xl border border-dashed border-slate-300/80 p-5">
                    <p class="text-sm text-slate-500">In Progress</p>
                    <p class="mt-1 text-2xl font-semibold text-slate-800">{{ stats.in_progress }}</p>
                </div>
                <div class="box box--stacked rounded-xl border border-dashed border-slate-300/80 p-5">
                    <p class="text-sm text-slate-500">Documents</p>
                    <p class="mt-1 text-2xl font-semibold text-slate-800">{{ stats.documents }}</p>
                </div>
            </div>

            <div class="box box--stacked p-5 mb-6">
                <div class="grid grid-cols-1 lg:grid-cols-6 gap-4">
                    <div class="lg:col-span-2 relative">
                        <Lucide icon="Search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                        <input v-model="filters.search_term" type="text" class="w-full rounded-lg border border-slate-200 px-3 py-2 pl-10 text-sm" placeholder="Search school, city, driver..." />
                    </div>

                    <TomSelect v-model="filters.carrier_filter">
                        <option value="">All Carriers</option>
                        <option v-for="carrier in carriers" :key="carrier.id" :value="String(carrier.id)">{{ carrier.name }}</option>
                    </TomSelect>

                    <TomSelect v-model="filters.driver_filter">
                        <option value="">All Drivers</option>
                        <option v-for="driver in drivers" :key="driver.id" :value="String(driver.id)">
                            {{ driver.name }}{{ driver.carrier_name ? ` · ${driver.carrier_name}` : '' }}
                        </option>
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
                        <h2 class="text-base font-semibold text-slate-800">Training Schools</h2>
                        <p class="text-sm text-slate-500">{{ trainingSchools.total }} records</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-slate-50/80">
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Created</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Driver</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Carrier</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">School</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">End Date</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Status</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Documents</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="school in trainingSchools.data" :key="school.id" class="border-b border-slate-100 hover:bg-slate-50/50 transition">
                                <td class="px-5 py-4 text-sm text-slate-500">{{ school.created_at }}</td>
                                <td class="px-5 py-4">
                                    <div class="font-medium text-slate-800">{{ school.driver?.name ?? 'N/A' }}</div>
                                    <div v-if="school.driver?.email" class="text-xs text-slate-400">{{ school.driver.email }}</div>
                                </td>
                                <td class="px-5 py-4 text-sm text-slate-600">{{ school.carrier?.name ?? 'N/A' }}</td>
                                <td class="px-5 py-4">
                                    <div class="font-medium text-slate-800">{{ school.school_name }}</div>
                                    <div class="text-xs text-slate-400">{{ [school.city, school.state].filter(Boolean).join(', ') || 'N/A' }}</div>
                                </td>
                                <td class="px-5 py-4 text-sm text-slate-600">{{ school.date_end ?? 'N/A' }}</td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium" :class="school.graduated ? 'bg-primary/10 text-primary' : 'bg-slate-100 text-slate-600'">
                                        {{ school.graduated ? 'Graduated' : 'In Progress' }}
                                    </span>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">
                                        {{ school.document_count }} document<span v-if="school.document_count !== 1">s</span>
                                    </span>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center justify-center gap-2">
                                        <Link :href="route('admin.training-schools.show', school.id)" class="p-1.5 text-slate-400 hover:text-primary transition" title="View">
                                            <Lucide icon="Eye" class="w-4 h-4" />
                                        </Link>
                                        <Link :href="route('admin.training-schools.documents.show', school.id)" class="p-1.5 text-slate-400 hover:text-primary transition" title="Documents">
                                            <Lucide icon="Files" class="w-4 h-4" />
                                        </Link>
                                        <Link :href="route('admin.training-schools.edit', school.id)" class="p-1.5 text-slate-400 hover:text-primary transition" title="Edit">
                                            <Lucide icon="PenLine" class="w-4 h-4" />
                                        </Link>
                                        <button type="button" @click="deleteSchool(school)" class="p-1.5 text-slate-400 hover:text-red-500 transition" title="Delete">
                                            <Lucide icon="Trash2" class="w-4 h-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="!trainingSchools.data.length">
                                <td colspan="8" class="px-5 py-12 text-center text-slate-400">
                                    <Lucide icon="GraduationCap" class="w-12 h-12 mx-auto mb-3 text-slate-300" />
                                    <p>No training school records found</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="trainingSchools.last_page > 1" class="p-4 border-t border-slate-200/60 flex items-center justify-between">
                    <span class="text-sm text-slate-500">{{ trainingSchools.total }} total records</span>
                    <div class="flex gap-1">
                        <template v-for="link in trainingSchools.links" :key="link.label">
                            <Link v-if="link.url" :href="link.url" class="px-3 py-1 text-sm rounded" :class="link.active ? 'bg-primary text-white' : 'text-slate-600 hover:bg-slate-100'" v-html="link.label" />
                            <span v-else class="px-3 py-1 text-sm text-slate-300" v-html="link.label" />
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
