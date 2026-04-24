<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3'
import { reactive } from 'vue'
import CarrierLayout from '@/layouts/CarrierLayout.vue'
import Button from '@/components/Base/Button'
import FormInput from '@/components/Base/Form/FormInput.vue'
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
    stats: { type: Object, default: () => ({}) },
    accidents: { type: Object, required: true },
    drivers: { type: Array, default: () => [] },
})
const { filters: initialFilters, stats, accidents, drivers } = props
const filters = reactive({ ...initialFilters })
const statCards = [
    { label: 'Total Accidents', value: stats?.total ?? 0, icon: 'AlertTriangle' },
    { label: 'Recent', value: stats?.recent ?? 0, icon: 'Clock3' },
    { label: 'With Fatalities', value: stats?.with_fatalities ?? 0, icon: 'ShieldAlert' },
    { label: 'With Injuries', value: stats?.with_injuries ?? 0, icon: 'Ambulance' },
]

function applyFilters() {
    router.get(route('carrier.reports.accidents'), { ...filters }, { preserveScroll: true, replace: true })
}

function resetFilters() {
    filters.search = ''
    filters.driver = ''
    filters.date_from = ''
    filters.date_to = ''
    applyFilters()
}
</script>

<template>
    <Head title="Accident Reports" />

    <div class="grid grid-cols-12 gap-6">
        <div class="col-span-12">
            <ReportHero title="Accident Reports" subtitle="Incident history, severity, and driver-specific filtering." icon="AlertTriangle">
                <template #actions>
                    <Button as="a" :href="route('carrier.reports.accidents.export-pdf', { ...filters })" variant="outline-secondary" class="gap-2">
                        <Lucide icon="Download" class="h-4 w-4" />Export PDF
                    </Button>
                </template>
            </ReportHero>
        </div>

        <div class="col-span-12">
            <ReportStats :cards="statCards" />
        </div>

        <div class="col-span-12">
            <div class="box box--stacked p-5">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-5">
                    <FormInput v-model="filters.search" type="text" placeholder="Search accident, comments..." @keyup.enter="applyFilters" />
                    <TomSelect v-model="filters.driver">
                        <option value="">All Drivers</option>
                        <option v-for="driver in drivers" :key="driver.id" :value="String(driver.id)">{{ driver.name }}</option>
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
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Date</th>
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Driver</th>
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Nature</th>
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Severity</th>
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Fatalities</th>
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Injuries</th>
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Comments</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            <tr v-for="accident in accidents.data" :key="accident.id">
                                <td class="px-5 py-4 text-slate-600">{{ accident.accident_date || 'N/A' }}</td>
                                <td class="px-5 py-4 text-slate-600">
                                    <div class="font-medium text-slate-800">{{ accident.driver_name }}</div>
                                    <div class="text-xs text-slate-500">{{ accident.driver_email || 'N/A' }}</div>
                                </td>
                                <td class="px-5 py-4 text-slate-600">{{ accident.nature || 'N/A' }}</td>
                                <td class="px-5 py-4"><span class="rounded-full px-3 py-1 text-xs font-medium" :class="statusTone(accident.severity)">{{ accident.severity }}</span></td>
                                <td class="px-5 py-4 text-slate-600">{{ accident.fatalities }}</td>
                                <td class="px-5 py-4 text-slate-600">{{ accident.injuries }}</td>
                                <td class="px-5 py-4 text-slate-600">{{ accident.comments || '-' }}</td>
                            </tr>
                            <tr v-if="!accidents.data.length"><td colspan="7" class="px-5 py-10 text-center text-slate-500">No accidents matched the current filters.</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-slate-100 p-4">
                    <ReportPagination :links="accidents.links" />
                </div>
            </div>
        </div>
    </div>
</template>
