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
import { pickerOptions } from '@/pages/admin/reports/components/reportUtils'
import Lucide from '@/components/Base/Lucide'

declare function route(name: string, params?: any): string
defineOptions({ layout: CarrierLayout })

const props = defineProps({
    filters: { type: Object, required: true },
    driverSummaries: { type: Object, required: true },
    stats: { type: Object, default: () => ({}) },
    drivers: { type: Array, default: () => [] },
    dateRangeLabel: { type: String, default: '' },
})
const { filters: initialFilters, driverSummaries, stats, drivers, dateRangeLabel } = props
const filters = reactive({ ...initialFilters })
const statCards = [
    { label: 'Drivers', value: driverSummaries?.total ?? 0, icon: 'Users' },
    { label: 'Compliance Rate', value: `${stats?.compliance_percentage ?? 0}%`, icon: 'BadgeCheck' },
    { label: 'Days With Violations', value: stats?.logs_with_violations ?? 0, icon: 'AlertTriangle' },
    { label: 'Total Log Days', value: stats?.total_logs ?? 0, icon: 'CalendarDays' },
]

function minutesToLabel(total: number) {
    const hours = Math.floor(Number(total || 0) / 60)
    const minutes = Math.round(Number(total || 0) % 60)
    return `${hours}h ${minutes}m`
}

function applyFilters() {
    router.get(route('carrier.reports.hos'), { ...filters }, { preserveState: true, preserveScroll: true, replace: true })
}

function resetFilters() {
    filters.driver_id = ''
    filters.has_violations = ''
    filters.date_from = ''
    filters.date_to = ''
    applyFilters()
}
</script>

<template>
    <Head title="HOS Reports" />
    <div class="grid grid-cols-12 gap-6">
        <div class="col-span-12">
            <ReportHero title="HOS Driver Summary" :subtitle="dateRangeLabel || 'Hours-of-service summary.'" icon="Clock3">
                <template #actions>
                    <Button as="a" :href="route('carrier.reports.hos.export-pdf', { ...filters })" variant="outline-secondary" class="gap-2">
                        <Lucide icon="Download" class="h-4 w-4" />Export PDF
                    </Button>
                </template>
            </ReportHero>
        </div>
        <div class="col-span-12"><ReportStats :cards="statCards" /></div>
        <div class="col-span-12">
            <div class="box box--stacked p-5">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-5">
                    <TomSelect v-model="filters.driver_id">
                        <option value="">All Drivers</option>
                        <option v-for="driver in drivers" :key="driver.id" :value="String(driver.id)">{{ driver.name }}</option>
                    </TomSelect>
                    <TomSelect v-model="filters.has_violations">
                        <option value="">All</option>
                        <option value="no">Compliant</option>
                        <option value="yes">With Violations</option>
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
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Driver</th>
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Days</th>
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Total Driving</th>
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Avg Driving/Day</th>
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">On Duty</th>
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Off Duty</th>
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Violations</th>
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Period</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            <tr v-for="row in driverSummaries.data" :key="row.driver_id">
                                <td class="px-5 py-4 text-slate-600">
                                    <div class="font-medium text-slate-800">{{ row.driver_name }}</div>
                                    <div class="text-xs text-slate-500">{{ row.driver_email || 'N/A' }}</div>
                                </td>
                                <td class="px-5 py-4 text-slate-600">{{ row.total_days }}</td>
                                <td class="px-5 py-4 text-slate-600">{{ minutesToLabel(row.total_driving_minutes) }}</td>
                                <td class="px-5 py-4 text-slate-600">{{ minutesToLabel(row.avg_driving_minutes) }}</td>
                                <td class="px-5 py-4 text-slate-600">{{ minutesToLabel(row.total_on_duty_minutes) }}</td>
                                <td class="px-5 py-4 text-slate-600">{{ minutesToLabel(row.total_off_duty_minutes) }}</td>
                                <td class="px-5 py-4 text-slate-600">{{ row.days_with_violations }}</td>
                                <td class="px-5 py-4 text-slate-600">{{ row.first_log_date || 'N/A' }} to {{ row.last_log_date || 'N/A' }}</td>
                            </tr>
                            <tr v-if="!driverSummaries.data.length"><td colspan="8" class="px-5 py-10 text-center text-slate-500">No HOS summaries matched the current filters.</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-slate-100 p-4"><ReportPagination :links="driverSummaries.links" /></div>
            </div>
        </div>
    </div>
</template>
