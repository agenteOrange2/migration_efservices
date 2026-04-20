<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3'
import { computed, reactive } from 'vue'
import Button from '@/components/Base/Button'
import FormInput from '@/components/Base/Form/FormInput.vue'
import Lucide from '@/components/Base/Lucide'
import TomSelect from '@/components/Base/TomSelect/TomSelect.vue'
import RazeLayout from '@/layouts/RazeLayout.vue'
import ReportHero from './components/ReportHero.vue'
import ReportPagination from './components/ReportPagination.vue'
import ReportStats from './components/ReportStats.vue'
import { statusTone, type PaginationLink } from './components/reportUtils'

declare function route(name: string, params?: any): string

defineOptions({ layout: RazeLayout })

interface VehicleRow {
    id: number
    unit_number: string
    vehicle_label: string
    carrier_name: string | null
    driver_name: string | null
    type: string
    vin: string
    driver_type: string
    status: string
    status_label: string
    registration_expiration: string | null
    inspection_expiration: string | null
}

const props = defineProps<{
    filters: { tab: string; search: string; carrier_id: string; per_page: number }
    vehicles: { data: VehicleRow[]; links: PaginationLink[]; total: number }
    stats: { total: number; active: number; out_of_service: number; suspended: number }
    tabs: { value: string; label: string }[]
    carriers: { id: number; name: string }[]
    canFilterCarriers: boolean
}>()

const filters = reactive({ ...props.filters })

const statCards = computed(() => [
    { label: 'Fleet Units', value: props.stats.total, icon: 'Truck', tone: 'primary' },
    { label: 'Active', value: props.stats.active, icon: 'CheckCircle2', tone: 'success' },
    { label: 'Out of Service', value: props.stats.out_of_service, icon: 'CircleOff', tone: 'danger' },
    { label: 'Suspended', value: props.stats.suspended, icon: 'PauseCircle', tone: 'danger' },
])

function applyFilters() {
    router.get(route('admin.reports.equipment-list'), {
        tab: filters.tab || undefined,
        search: filters.search || undefined,
        carrier_id: filters.carrier_id || undefined,
    }, { preserveState: true, preserveScroll: true, replace: true })
}
</script>

<template>
    <Head title="Equipment List Report" />

    <div class="grid grid-cols-12 gap-6">
        <div class="col-span-12">
            <ReportHero title="Equipment List Report" subtitle="Fleet visibility by unit, assignment, compliance dates, and operational status." icon="Truck">
                <template #actions>
                    <Button as="a" :href="route('admin.reports.equipment-list-pdf', { ...filters })" variant="outline-secondary" class="gap-2">
                        <Lucide icon="Download" class="h-4 w-4" />
                        Export PDF
                    </Button>
                </template>
            </ReportHero>
        </div>

        <div class="col-span-12">
            <ReportStats :cards="statCards" />
        </div>

        <div class="col-span-12">
            <div class="box box--stacked p-5">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <FormInput v-model="filters.search" type="text" placeholder="Search unit, VIN, make, model, driver..." @keyup.enter="applyFilters" />
                    <TomSelect v-if="canFilterCarriers" v-model="filters.carrier_id">
                        <option value="">All Carriers</option>
                        <option v-for="carrier in carriers" :key="carrier.id" :value="String(carrier.id)">{{ carrier.name }}</option>
                    </TomSelect>
                    <div class="flex gap-3">
                        <Button variant="primary" class="w-full" @click="applyFilters">Apply</Button>
                        <Button variant="outline-secondary" class="w-full" @click="filters.search=''; filters.carrier_id=''; filters.tab='all'; applyFilters()">Reset</Button>
                    </div>
                </div>
                <div class="mt-4 flex flex-wrap gap-2">
                    <button
                        v-for="tab in tabs"
                        :key="tab.value"
                        type="button"
                        class="rounded-full px-4 py-2 text-sm font-medium transition"
                        :class="filters.tab === tab.value ? 'bg-primary text-white' : 'bg-slate-100 text-slate-600 hover:bg-primary/10 hover:text-primary'"
                        @click="filters.tab = tab.value; applyFilters()"
                    >
                        {{ tab.label }}
                    </button>
                </div>
            </div>
        </div>

        <div class="col-span-12">
            <div class="box box--stacked overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Unit</th>
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Carrier / Driver</th>
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Type</th>
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Compliance</th>
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            <tr v-for="vehicle in vehicles.data" :key="vehicle.id">
                                <td class="px-5 py-4">
                                    <div class="font-medium text-slate-800">{{ vehicle.unit_number }}</div>
                                    <div v-if="vehicle.vehicle_label !== vehicle.unit_number" class="text-xs text-slate-500">{{ vehicle.vehicle_label }}</div>
                                    <div class="text-xs text-slate-400">VIN: {{ vehicle.vin }}</div>
                                </td>
                                <td class="px-5 py-4 text-slate-600">
                                    <div>{{ vehicle.carrier_name || 'N/A' }}</div>
                                    <div class="text-xs text-slate-500">{{ vehicle.driver_name || 'Unassigned' }}</div>
                                </td>
                                <td class="px-5 py-4 text-slate-600">
                                    <div>{{ vehicle.type }}</div>
                                    <div class="text-xs text-slate-500">{{ vehicle.driver_type }}</div>
                                </td>
                                <td class="px-5 py-4 text-slate-600">
                                    <div>Registration: {{ vehicle.registration_expiration || 'N/A' }}</div>
                                    <div class="text-xs text-slate-500">Inspection: {{ vehicle.inspection_expiration || 'N/A' }}</div>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="rounded-full px-3 py-1 text-xs font-medium" :class="statusTone(vehicle.status)">
                                        {{ vehicle.status_label }}
                                    </span>
                                </td>
                            </tr>
                            <tr v-if="!vehicles.data.length">
                                <td colspan="5" class="px-5 py-10 text-center text-slate-500">No vehicles matched the current filters.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-slate-100 p-4">
                    <ReportPagination :links="vehicles.links" />
                </div>
            </div>
        </div>
    </div>
</template>
