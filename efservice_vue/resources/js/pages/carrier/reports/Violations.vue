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
    violations: { type: Object, required: true },
    stats: { type: Object, default: () => ({}) },
    drivers: { type: Array, default: () => [] },
    violationTypes: { type: Array, default: () => [] },
    severities: { type: Array, default: () => [] },
})
const { filters: initialFilters, violations, stats, drivers, violationTypes, severities } = props
const filters = reactive({ ...initialFilters })
const statCards = [
    { label: 'Total Violations', value: stats?.total_violations ?? 0, icon: 'AlertOctagon' },
    { label: 'Acknowledged', value: stats?.acknowledged_count ?? 0, icon: 'BadgeCheck' },
    { label: 'Unacknowledged', value: stats?.unacknowledged_count ?? 0, icon: 'Clock3' },
    { label: 'Ack Rate', value: `${stats?.acknowledgment_rate ?? 0}%`, icon: 'BarChart3' },
]

function applyFilters() {
    router.get(route('carrier.reports.violations'), { ...filters }, { preserveState: true, preserveScroll: true, replace: true })
}

function resetFilters() {
    filters.driver_id = ''
    filters.violation_type = ''
    filters.severity = ''
    filters.date_from = ''
    filters.date_to = ''
    filters.acknowledged = ''
    applyFilters()
}
</script>

<template>
    <Head title="Violations Reports" />
    <div class="grid grid-cols-12 gap-6">
        <div class="col-span-12">
            <ReportHero title="HOS Violations Reports" subtitle="Violation tracking by type, severity, and acknowledgment state." icon="AlertOctagon">
                <template #actions>
                    <Button as="a" :href="route('carrier.reports.violations.export-pdf', { ...filters })" variant="outline-secondary" class="gap-2">
                        <Lucide icon="Download" class="h-4 w-4" />Export PDF
                    </Button>
                </template>
            </ReportHero>
        </div>
        <div class="col-span-12"><ReportStats :cards="statCards" /></div>
        <div class="col-span-12">
            <div class="box box--stacked p-5">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-6">
                    <TomSelect v-model="filters.driver_id">
                        <option value="">All Drivers</option>
                        <option v-for="driver in drivers" :key="driver.id" :value="String(driver.id)">{{ driver.name }}</option>
                    </TomSelect>
                    <TomSelect v-model="filters.violation_type">
                        <option value="">All Types</option>
                        <option v-for="type in violationTypes" :key="type" :value="type">{{ type }}</option>
                    </TomSelect>
                    <TomSelect v-model="filters.severity">
                        <option value="">All Severities</option>
                        <option v-for="severity in severities" :key="severity" :value="severity">{{ severity }}</option>
                    </TomSelect>
                    <TomSelect v-model="filters.acknowledged">
                        <option value="">All</option>
                        <option value="yes">Acknowledged</option>
                        <option value="no">Pending</option>
                    </TomSelect>
                    <Litepicker v-model="filters.date_from" :options="pickerOptions" />
                    <Litepicker v-model="filters.date_to" :options="pickerOptions" />
                    <div class="flex gap-3 xl:col-span-2">
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
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Trip</th>
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Violation</th>
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Severity</th>
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Date</th>
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Acknowledged</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            <tr v-for="row in violations.data" :key="row.id">
                                <td class="px-5 py-4 font-medium text-slate-800">{{ row.driver_name || 'N/A' }}</td>
                                <td class="px-5 py-4 text-slate-600">{{ row.trip_number || 'N/A' }}</td>
                                <td class="px-5 py-4 text-slate-600">{{ row.violation_type }}</td>
                                <td class="px-5 py-4"><span class="rounded-full px-3 py-1 text-xs font-medium" :class="statusTone(row.severity)">{{ row.severity }}</span></td>
                                <td class="px-5 py-4 text-slate-600">{{ row.violation_date || 'N/A' }}</td>
                                <td class="px-5 py-4 text-slate-600">{{ row.acknowledged ? 'Yes' : 'No' }}</td>
                            </tr>
                            <tr v-if="!violations.data.length"><td colspan="6" class="px-5 py-10 text-center text-slate-500">No violations matched the current filters.</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-slate-100 p-4"><ReportPagination :links="violations.links" /></div>
            </div>
        </div>
    </div>
</template>
