<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { reactive } from 'vue'
import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import { FormInput } from '@/components/Base/Form'
import TomSelect from '@/components/Base/TomSelect/TomSelect.vue'
import RazeLayout from '@/layouts/RazeLayout.vue'

declare function route(name: string, params?: any): string

defineOptions({ layout: RazeLayout })

interface PaginationLink { url: string | null; label: string; active: boolean }

const props = defineProps<{
    vehicle: { id: number; title: string; company_unit_number: string | null; vin: string; carrier_name?: string | null }
    assignments: {
        data: any[]
        links: PaginationLink[]
        total: number
        last_page: number
    }
    filters: { search: string; status: string }
    statusOptions: Record<string, string>
    isCarrierContext?: boolean
    routeNames?: Partial<{
        index: string
        show: string
        assignmentHistory: string
    }>
}>()

const filters = reactive({ ...props.filters })

const defaultRouteNames = {
    index: 'admin.vehicles.index',
    show: 'admin.vehicles.show',
    assignmentHistory: 'admin.vehicles.driver-assignment-history',
} as const

function namedRoute(name: keyof typeof defaultRouteNames, params?: any) {
    return route(props.routeNames?.[name] ?? defaultRouteNames[name], params)
}

function applyFilters() {
    router.get(namedRoute('assignmentHistory', props.vehicle.id), {
        search: filters.search || undefined,
        status: filters.status || undefined,
    }, { preserveState: true, replace: true })
}

function resetFilters() {
    filters.search = ''
    filters.status = ''
    applyFilters()
}
</script>

<template>
    <Head title="Vehicle Assignment History" />

    <div class="grid grid-cols-12 gap-x-6 gap-y-10">
        <div class="col-span-12">
            <div class="box box--stacked p-6 mb-6">
                <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-bold text-slate-800">Vehicle Assignment History</h1>
                        <p class="text-slate-500">{{ vehicle.title }}{{ vehicle.company_unit_number ? ` · Unit ${vehicle.company_unit_number}` : '' }} · {{ vehicle.vin }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <Link :href="namedRoute('show', vehicle.id)">
                            <Button variant="outline-primary" class="flex items-center gap-2">
                                <Lucide icon="Eye" class="w-4 h-4" />
                                Vehicle Detail
                            </Button>
                        </Link>
                        <Link :href="namedRoute('index')">
                            <Button variant="outline-secondary" class="flex items-center gap-2">
                                <Lucide icon="ArrowLeft" class="w-4 h-4" />
                                Back
                            </Button>
                        </Link>
                    </div>
                </div>
            </div>

            <div class="box box--stacked p-5 mb-6">
                <div class="grid grid-cols-1 lg:grid-cols-4 gap-4">
                    <div class="lg:col-span-3 relative">
                        <Lucide icon="Search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                        <FormInput v-model="filters.search" type="text" class="pl-10" placeholder="Search driver, owner, third party..." />
                    </div>
                    <TomSelect v-model="filters.status">
                        <option value="">All statuses</option>
                        <option v-for="(label, key) in statusOptions" :key="key" :value="key">{{ label }}</option>
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

            <div class="box box--stacked p-0 overflow-hidden">
                <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200/60">
                    <div>
                        <h2 class="text-base font-semibold text-slate-800">Assignments</h2>
                        <p class="text-sm text-slate-500">{{ assignments.total }} records</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-slate-50/80">
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Type</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Assigned Party</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Dates</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Status</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="assignment in assignments.data" :key="assignment.id" class="border-b border-slate-100 hover:bg-slate-50/50 transition">
                                <td class="px-5 py-4">
                                    <div class="font-medium text-slate-800">{{ assignment.driver_type_label }}</div>
                                </td>
                                <td class="px-5 py-4">
                                    <div v-if="assignment.driver" class="font-medium text-slate-800">{{ assignment.driver.name }}</div>
                                    <div v-if="assignment.driver?.email" class="text-xs text-slate-500">{{ assignment.driver.email }}</div>
                                    <div v-if="assignment.owner_operator" class="mt-1 text-sm text-slate-700">{{ assignment.owner_operator.name }}</div>
                                    <div v-if="assignment.third_party" class="mt-1 text-sm text-slate-700">{{ assignment.third_party.name }}</div>
                                    <div v-if="assignment.company?.carrier_name" class="mt-1 text-xs text-slate-500">{{ assignment.company.carrier_name }}</div>
                                    <div v-if="!assignment.driver && !assignment.owner_operator && !assignment.third_party && !assignment.company?.carrier_name" class="text-sm text-slate-400">No linked party</div>
                                </td>
                                <td class="px-5 py-4 text-sm text-slate-600">
                                    <div>{{ assignment.start_date ?? 'N/A' }}</div>
                                    <div class="text-xs text-slate-400">to {{ assignment.end_date ?? 'Open' }}</div>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium" :class="assignment.status === 'active' ? 'bg-primary/10 text-primary' : assignment.status === 'pending' ? 'bg-slate-100 text-slate-600' : 'bg-slate-100 text-slate-600'">
                                        {{ assignment.status_label }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-sm text-slate-600">{{ assignment.notes ?? 'No notes' }}</td>
                            </tr>

                            <tr v-if="!assignments.data.length">
                                <td colspan="5" class="px-5 py-12 text-center text-slate-400">
                                    <Lucide icon="History" class="w-12 h-12 mx-auto mb-3 text-slate-300" />
                                    <p>No assignment history found</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="assignments.last_page > 1" class="p-4 border-t border-slate-200/60 flex items-center justify-between">
                    <span class="text-sm text-slate-500">{{ assignments.total }} total records</span>
                    <div class="flex gap-1">
                        <template v-for="link in assignments.links" :key="link.label">
                            <Link v-if="link.url" :href="link.url" class="px-3 py-1 text-sm rounded" :class="link.active ? 'bg-primary text-white' : 'text-slate-600 hover:bg-slate-100'" v-html="link.label" />
                            <span v-else class="px-3 py-1 text-sm text-slate-300" v-html="link.label" />
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
