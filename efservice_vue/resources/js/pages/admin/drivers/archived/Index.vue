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

interface ArchiveRow {
    id: number
    full_name: string
    email: string | null
    carrier_name: string
    archived_at_display: string | null
    archived_time_display: string | null
    archive_reason: string
    status: string
    migration_target_name: string | null
    document_count: number
    initials: string
}

const props = defineProps<{
    archives: { data: ArchiveRow[]; links: PaginationLink[]; total: number; last_page: number }
    filters: {
        search: string
        carrier_id: string
        date_from: string
        date_to: string
        archive_reason: string
        sort_field: string
        sort_direction: string
    }
    carriers: { id: number; name: string }[]
    archiveReasons: { value: string; label: string }[]
    stats: { total: number; migration: number; termination: number; restored: number }
    isSuperadmin: boolean
}>()

const filters = reactive({ ...props.filters })

function applyFilters() {
    router.get(route('admin.drivers.archived.index'), {
        search: filters.search || undefined,
        carrier_id: filters.carrier_id || undefined,
        date_from: filters.date_from || undefined,
        date_to: filters.date_to || undefined,
        archive_reason: filters.archive_reason || undefined,
        sort_field: filters.sort_field || undefined,
        sort_direction: filters.sort_direction || undefined,
    }, { preserveState: true, replace: true })
}

function resetFilters() {
    filters.search = ''
    filters.carrier_id = ''
    filters.date_from = ''
    filters.date_to = ''
    filters.archive_reason = ''
    filters.sort_field = 'archived_at'
    filters.sort_direction = 'desc'
    applyFilters()
}

function reasonBadge(reason: string) {
    if (reason === 'migration') return 'bg-primary/10 text-primary'
    if (reason === 'termination') return 'bg-red-100 text-red-600'
    return 'bg-slate-100 text-slate-600'
}

function statusBadge(status: string) {
    return status === 'archived' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'
}
</script>

<template>
    <Head title="Archived Drivers" />

    <div class="grid grid-cols-12 gap-x-6 gap-y-10">
        <div class="col-span-12">
            <div class="box box--stacked p-6">
                <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                            <Lucide icon="Archive" class="w-8 h-8 text-primary" />
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-slate-800">Archived Drivers</h1>
                            <p class="text-slate-500">Historical driver records preserved after migration, termination, or manual archive.</p>
                        </div>
                    </div>
                    <Link :href="route('admin.drivers.index')">
                        <Button variant="outline-secondary" class="flex items-center gap-2">
                            <Lucide icon="ArrowLeft" class="w-4 h-4" />
                            Active Drivers
                        </Button>
                    </Link>
                </div>
            </div>
        </div>

        <div class="col-span-12">
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                <div class="box box--stacked p-5 border border-primary/10 bg-primary/5">
                    <p class="text-sm text-slate-500">Total Archives</p>
                    <p class="mt-1 text-2xl font-semibold text-slate-800">{{ stats.total }}</p>
                </div>
                <div class="box box--stacked p-5 border border-slate-200">
                    <p class="text-sm text-slate-500">Migration</p>
                    <p class="mt-1 text-2xl font-semibold text-slate-800">{{ stats.migration }}</p>
                </div>
                <div class="box box--stacked p-5 border border-slate-200">
                    <p class="text-sm text-slate-500">Termination</p>
                    <p class="mt-1 text-2xl font-semibold text-slate-800">{{ stats.termination }}</p>
                </div>
                <div class="box box--stacked p-5 border border-amber-200 bg-amber-50/60">
                    <p class="text-sm text-slate-500">Restored</p>
                    <p class="mt-1 text-2xl font-semibold text-slate-800">{{ stats.restored }}</p>
                </div>
            </div>
        </div>

        <div class="col-span-12">
            <div class="box box--stacked p-5">
                <div class="grid grid-cols-1 lg:grid-cols-6 gap-4">
                    <div class="lg:col-span-2 relative">
                        <Lucide icon="Search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                        <input v-model="filters.search" type="text" class="w-full rounded-lg border border-slate-200 px-3 py-2 pl-10 text-sm" placeholder="Search by driver name..." />
                    </div>
                    <TomSelect v-if="isSuperadmin" v-model="filters.carrier_id">
                        <option value="">All Carriers</option>
                        <option v-for="carrier in carriers" :key="carrier.id" :value="String(carrier.id)">{{ carrier.name }}</option>
                    </TomSelect>
                    <Litepicker v-model="filters.date_from" :options="pickerOptions" />
                    <Litepicker v-model="filters.date_to" :options="pickerOptions" />
                    <TomSelect v-model="filters.archive_reason">
                        <option value="">All Reasons</option>
                        <option v-for="reason in archiveReasons" :key="reason.value" :value="reason.value">{{ reason.label }}</option>
                    </TomSelect>
                </div>

                <div class="mt-4 flex flex-wrap items-center gap-3">
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
        </div>

        <div class="col-span-12">
            <div class="box box--stacked p-0 overflow-hidden">
                <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200/60">
                    <div>
                        <h2 class="text-base font-semibold text-slate-800">Archived Records</h2>
                        <p class="text-sm text-slate-500">{{ archives.total }} records</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-slate-50/80">
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Driver</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Carrier</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Archived</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Reason</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Migrated To</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Documents</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Status</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="archive in archives.data" :key="archive.id" class="border-b border-slate-100 hover:bg-slate-50/50 transition">
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full bg-primary/10 text-primary font-semibold flex items-center justify-center">
                                            {{ archive.initials || 'AR' }}
                                        </div>
                                        <div>
                                            <div class="font-medium text-slate-800">{{ archive.full_name }}</div>
                                            <div class="text-xs text-slate-400">{{ archive.email || 'No email' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-sm text-slate-600">{{ archive.carrier_name }}</td>
                                <td class="px-5 py-4">
                                    <div class="text-sm text-slate-700">{{ archive.archived_at_display || 'N/A' }}</div>
                                    <div class="text-xs text-slate-400">{{ archive.archived_time_display || '' }}</div>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium capitalize" :class="reasonBadge(archive.archive_reason)">
                                        {{ archive.archive_reason.replace(/_/g, ' ') }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-sm text-slate-600">{{ archive.migration_target_name || 'N/A' }}</td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">
                                        {{ archive.document_count }} file<span v-if="archive.document_count !== 1">s</span>
                                    </span>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium capitalize" :class="statusBadge(archive.status)">
                                        {{ archive.status }}
                                    </span>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center justify-center">
                                        <Link :href="route('admin.drivers.archived.show', archive.id)" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-primary text-white text-sm font-medium hover:bg-primary/90 transition">
                                            <Lucide icon="Eye" class="w-4 h-4" />
                                            View
                                        </Link>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!archives.data.length">
                                <td colspan="8" class="px-5 py-12 text-center text-slate-400">
                                    <Lucide icon="Archive" class="w-12 h-12 mx-auto mb-3 text-slate-300" />
                                    <p>No archived drivers found</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="archives.last_page > 1" class="p-4 border-t border-slate-200/60 flex items-center justify-between">
                    <span class="text-sm text-slate-500">{{ archives.total }} total records</span>
                    <div class="flex gap-1">
                        <template v-for="link in archives.links" :key="link.label">
                            <Link v-if="link.url" :href="link.url" class="px-3 py-1 text-sm rounded" :class="link.active ? 'bg-primary text-white' : 'text-slate-600 hover:bg-slate-100'" v-html="link.label" />
                            <span v-else class="px-3 py-1 text-sm text-slate-300" v-html="link.label" />
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
