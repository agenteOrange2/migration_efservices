<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3'
import { reactive } from 'vue'
import CarrierLayout from '@/layouts/CarrierLayout.vue'
import Button from '@/components/Base/Button'
import Litepicker from '@/components/Base/Litepicker/Litepicker.vue'
import TomSelect from '@/components/Base/TomSelect/TomSelect.vue'
import ReportHero from '@/pages/admin/reports/components/ReportHero.vue'
import ReportPagination from '@/pages/admin/reports/components/ReportPagination.vue'
import ReportStats from '@/pages/admin/reports/components/ReportStats.vue'
import { pickerOptions, statusTone } from '@/pages/admin/reports/components/reportUtils'
import Lucide from '@/components/Base/Lucide'

declare function route(name: string, params?: any): string
defineOptions({ layout: CarrierLayout })

const props = defineProps({
    filters: { type: Object, required: true },
    trips: { type: Object, required: true },
    stats: { type: Object, default: () => ({}) },
    drivers: { type: Array, default: () => [] },
    statusOptions: { type: Array, default: () => [] },
})
const { filters: initialFilters, trips, stats, drivers, statusOptions } = props
const filters = reactive({ ...initialFilters })
const statCards = [
    { label: 'Total Trips', value: stats?.total_trips ?? 0, icon: 'MapPin' },
    { label: 'Completed', value: stats?.completed_trips ?? 0, icon: 'BadgeCheck' },
    { label: 'In Progress', value: stats?.in_progress_trips ?? 0, icon: 'LoaderCircle' },
    { label: 'With Violations', value: stats?.trips_with_violations ?? 0, icon: 'AlertTriangle' },
]

function applyFilters() {
    router.get(route('carrier.reports.trips'), { ...filters }, { preserveState: true, preserveScroll: true, replace: true })
}

function resetFilters() {
    filters.driver_id = ''
    filters.status = ''
    filters.date_from = ''
    filters.date_to = ''
    applyFilters()
}
</script>

<template>
    <Head title="Trip Reports" />
    <div class="grid grid-cols-12 gap-6">
        <div class="col-span-12">
            <ReportHero title="Trip Reports" subtitle="Trip activity, driver assignment, and violation markers." icon="MapPin">
                <template #actions>
                    <Button as="a" :href="route('carrier.reports.trips.export-pdf', { ...filters })" variant="outline-secondary" class="gap-2">
                        <Lucide icon="Download" class="h-4 w-4" />Export PDF
                    </Button>
                </template>
            </ReportHero>
        </div>
        <div class="col-span-12"><ReportStats :cards="statCards" /></div>
        <div class="col-span-12">
            <div class="box box--stacked p-5">
                <div class="mb-4 text-sm font-medium text-slate-600">Completion rate: <span class="text-primary">{{ stats?.completion_rate ?? 0 }}%</span></div>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-5">
                    <TomSelect v-model="filters.driver_id">
                        <option value="">All Drivers</option>
                        <option v-for="driver in drivers" :key="driver.id" :value="String(driver.id)">{{ driver.name }}</option>
                    </TomSelect>
                    <TomSelect v-model="filters.status">
                        <option value="">All Statuses</option>
                        <option v-for="status in statusOptions" :key="status.value" :value="status.value">{{ status.label }}</option>
                    </TomSelect>
                    <Litepicker v-model="filters.date_from" :options="pickerOptions" />
                    <Litepicker v-model="filters.date_to" :options="pickerOptions" />
                    <div class="flex gap-3">
                        <Button variant="primary" class="w-full" @click="applyFilters">Apply</Button>
                        <Button variant="outline-secondary" class="w-full" @click="resetFilters">Reset</Button>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-span-12">
            <div class="box box--stacked overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Trip</th>
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Driver</th>
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Vehicle</th>
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Route</th>
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Date</th>
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            <tr v-for="trip in trips.data" :key="trip.id">
                                <td class="px-5 py-4 font-medium text-slate-800">{{ trip.trip_number || 'N/A' }}</td>
                                <td class="px-5 py-4 text-slate-600">{{ trip.driver_name || 'N/A' }}</td>
                                <td class="px-5 py-4 text-slate-600">{{ trip.vehicle_label || 'N/A' }}</td>
                                <td class="px-5 py-4 text-slate-600">
                                    <div>{{ trip.origin || 'N/A' }}</div>
                                    <div class="text-xs text-slate-500">{{ trip.destination || 'N/A' }}</div>
                                </td>
                                <td class="px-5 py-4 text-slate-600">{{ trip.scheduled_start_date || 'N/A' }}</td>
                                <td class="px-5 py-4">
                                    <span class="rounded-full px-3 py-1 text-xs font-medium" :class="statusTone(trip.status)">{{ trip.status }}</span>
                                    <div class="mt-2 text-xs text-slate-500">{{ trip.violations_count }} violations</div>
                                </td>
                            </tr>
                            <tr v-if="!trips.data.length"><td colspan="6" class="px-5 py-10 text-center text-slate-500">No trips matched the current filters.</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-slate-100 p-4"><ReportPagination :links="trips.links" /></div>
            </div>
        </div>
    </div>
</template>
