<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { computed, reactive } from 'vue'
import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import RazeLayout from '@/layouts/RazeLayout.vue'

declare function route(name: string, params?: any): string

defineOptions({ layout: RazeLayout })

interface PaginationLink {
    url: string | null
    label: string
    active: boolean
}

interface InspectionRow {
    id: number
    inspection_date: string | null
    inspection_type: string
    inspection_level: string | null
    status: string | null
    inspector_name: string | null
    location: string | null
    document_count: number
    has_issues: boolean
    vehicle_label: string | null
}

const props = defineProps<{
    driver: {
        id: number
        full_name: string
        carrier_name: string | null
    }
    filters: {
        search: string
        status: string
        type: string
    }
    statuses: string[]
    inspectionTypes: string[]
    stats: {
        total: number
        passed: number
        issues_found: number
        documents: number
    }
    inspections: {
        data: InspectionRow[]
        links: PaginationLink[]
        total: number
        last_page: number
    }
}>()

const filters = reactive({
    search: props.filters.search,
    status: props.filters.status,
    type: props.filters.type,
})

const statCards = computed(() => [
    { label: 'Total Inspections', value: props.stats.total, icon: 'Search', className: 'bg-primary/10 text-primary' },
    { label: 'Passed / Cleared', value: props.stats.passed, icon: 'BadgeCheck', className: 'bg-primary/10 text-primary' },
    { label: 'Issues Found', value: props.stats.issues_found, icon: 'AlertTriangle', className: 'bg-slate-200 text-slate-700' },
    { label: 'Documents', value: props.stats.documents, icon: 'Files', className: 'bg-slate-100 text-slate-700' },
])

function applyFilters() {
    router.get(route('driver.inspections.index'), {
        search: filters.search || undefined,
        status: filters.status || undefined,
        type: filters.type || undefined,
    }, {
        preserveState: true,
        replace: true,
    })
}

function resetFilters() {
    filters.search = ''
    filters.status = ''
    filters.type = ''
    applyFilters()
}

function statusClass(status: string | null) {
    if (['Pass', 'Passed', 'Conditional Pass'].includes(status || '')) return 'bg-primary/10 text-primary'
    if (['Fail', 'Failed', 'Out of Service'].includes(status || '')) return 'bg-slate-200 text-slate-700'
    return 'bg-slate-100 text-slate-600'
}
</script>

<template>
    <Head title="My Inspections" />

    <div class="grid grid-cols-12 gap-6">
        <div class="col-span-12">
            <div class="box box--stacked p-6">
                <div class="flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between">
                    <div class="flex items-start gap-4">
                        <div class="rounded-2xl border border-primary/20 bg-primary/10 p-3">
                            <Lucide icon="Search" class="h-8 w-8 text-primary" />
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-slate-800">Vehicle Inspections</h1>
                            <p class="mt-1 text-slate-500">
                                Review your inspection records, defects, and supporting documents.
                            </p>
                            <p class="mt-2 text-sm text-slate-500">
                                Driver: <span class="font-medium text-slate-700">{{ driver.full_name }}</span>
                                <span v-if="driver.carrier_name"> · Carrier: <span class="font-medium text-slate-700">{{ driver.carrier_name }}</span></span>
                            </p>
                        </div>
                    </div>

                    <Link :href="`${route('driver.profile')}#inspections`">
                        <Button variant="outline-secondary" class="gap-2">
                            <Lucide icon="User" class="h-4 w-4" />
                            View Profile Tab
                        </Button>
                    </Link>
                </div>
            </div>
        </div>

        <div v-for="card in statCards" :key="card.label" class="col-span-12 sm:col-span-6 xl:col-span-3">
            <div class="box box--stacked p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-slate-500">{{ card.label }}</p>
                        <p class="mt-2 text-3xl font-semibold text-slate-800">{{ card.value }}</p>
                    </div>
                    <div class="rounded-xl p-3" :class="card.className">
                        <Lucide :icon="card.icon" class="h-5 w-5" />
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12">
            <div class="box box--stacked p-5">
                <div class="grid grid-cols-1 gap-4 lg:grid-cols-[minmax(0,1fr),220px,220px,auto]">
                    <div class="relative">
                        <Lucide icon="Search" class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                        <input v-model="filters.search" type="text" class="w-full rounded-lg border border-slate-200 px-3 py-2 pl-10 text-sm" placeholder="Search type, inspector, location, notes, or vehicle..." @keyup.enter="applyFilters">
                    </div>

                    <select v-model="filters.status" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                        <option value="">All statuses</option>
                        <option v-for="status in statuses" :key="status" :value="status">{{ status }}</option>
                    </select>

                    <select v-model="filters.type" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                        <option value="">All inspection types</option>
                        <option v-for="type in inspectionTypes" :key="type" :value="type">{{ type }}</option>
                    </select>

                    <div class="flex items-center gap-3">
                        <Button variant="primary" class="gap-2" @click="applyFilters"><Lucide icon="Filter" class="h-4 w-4" />Apply</Button>
                        <Button variant="outline-secondary" class="gap-2" @click="resetFilters"><Lucide icon="RotateCcw" class="h-4 w-4" />Clear</Button>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12">
            <div v-if="inspections.data.length" class="grid grid-cols-12 gap-6">
                <div v-for="inspection in inspections.data" :key="inspection.id" class="col-span-12 xl:col-span-6">
                    <div class="box box--stacked h-full border p-6 transition hover:shadow-md">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                            <div class="space-y-3">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h2 class="text-lg font-semibold text-slate-800">{{ inspection.inspection_type }}</h2>
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium" :class="statusClass(inspection.status)">{{ inspection.status || 'Not Set' }}</span>
                                    <span v-if="inspection.has_issues" class="inline-flex rounded-full bg-slate-200 px-2.5 py-1 text-xs font-medium text-slate-700">Issues Found</span>
                                </div>
                                <p class="text-sm text-slate-500">{{ inspection.inspection_date || 'No date' }}<span v-if="inspection.location"> · {{ inspection.location }}</span></p>
                            </div>

                            <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-center sm:min-w-[150px]">
                                <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Documents</p>
                                <p class="mt-2 text-2xl font-semibold text-slate-800">{{ inspection.document_count }}</p>
                            </div>
                        </div>

                        <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-4"><p class="text-xs font-medium uppercase tracking-[0.2em] text-slate-400">Inspector</p><p class="mt-2 text-sm font-semibold text-slate-800">{{ inspection.inspector_name || 'N/A' }}</p></div>
                            <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-4"><p class="text-xs font-medium uppercase tracking-[0.2em] text-slate-400">Level</p><p class="mt-2 text-sm font-semibold text-slate-800">{{ inspection.inspection_level || 'N/A' }}</p></div>
                            <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-4"><p class="text-xs font-medium uppercase tracking-[0.2em] text-slate-400">Vehicle</p><p class="mt-2 text-sm font-semibold text-slate-800">{{ inspection.vehicle_label || 'Not linked' }}</p></div>
                        </div>

                        <div class="mt-5 flex items-center justify-end border-t border-slate-200 pt-5">
                            <Link :href="route('driver.inspections.show', inspection.id)">
                                <Button variant="outline-secondary" class="gap-2"><Lucide icon="ArrowRight" class="h-4 w-4" />Open Details</Button>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <div v-else class="box box--stacked p-12 text-center">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-slate-100"><Lucide icon="Search" class="h-8 w-8 text-slate-400" /></div>
                <h2 class="mt-5 text-lg font-semibold text-slate-800">No Inspection Records Found</h2>
                <p class="mx-auto mt-2 max-w-xl text-sm text-slate-500">You do not have inspection records matching the current filters yet.</p>
            </div>
        </div>

        <div v-if="inspections.last_page > 1" class="col-span-12">
            <div class="box box--stacked flex flex-col gap-4 p-4 sm:flex-row sm:items-center sm:justify-between">
                <span class="text-sm text-slate-500">{{ inspections.total }} total records</span>
                <div class="flex flex-wrap gap-1">
                    <template v-for="link in inspections.links" :key="link.label">
                        <Link v-if="link.url" :href="link.url" class="rounded-lg px-3 py-1.5 text-sm transition" :class="link.active ? 'bg-primary text-white' : 'text-slate-600 hover:bg-slate-100'" v-html="link.label" />
                        <span v-else class="px-3 py-1.5 text-sm text-slate-300" v-html="link.label" />
                    </template>
                </div>
            </div>
        </div>
    </div>
</template>
