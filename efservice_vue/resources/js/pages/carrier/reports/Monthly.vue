<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3'
import { computed, reactive } from 'vue'
import CarrierLayout from '@/layouts/CarrierLayout.vue'
import Button from '@/components/Base/Button'
import Litepicker from '@/components/Base/Litepicker/Litepicker.vue'
import ReportHero from '@/pages/admin/reports/components/ReportHero.vue'
import ReportStats from '@/pages/admin/reports/components/ReportStats.vue'
import { money, pickerOptions } from '@/pages/admin/reports/components/reportUtils'
import Lucide from '@/components/Base/Lucide'

declare function route(name: string, params?: any): string
defineOptions({ layout: CarrierLayout })

const props = defineProps({
    filters: { type: Object, required: true },
    monthlyData: { type: Array, default: () => [] },
})
const { filters: initialFilters, monthlyData } = props
const filters = reactive({ ...initialFilters })
const totals = computed(() => ({
    drivers: monthlyData.reduce((sum: number, row: any) => sum + Number(row.drivers || 0), 0),
    vehicles: monthlyData.reduce((sum: number, row: any) => sum + Number(row.vehicles || 0), 0),
    accidents: monthlyData.reduce((sum: number, row: any) => sum + Number(row.accidents || 0), 0),
    maintenanceCost: monthlyData.reduce((sum: number, row: any) => sum + Number(row.maintenance?.total_cost || 0), 0),
    repairCost: monthlyData.reduce((sum: number, row: any) => sum + Number(row.repairs?.total_cost || 0), 0),
}))
const statCards = computed(() => [
    { label: 'New Drivers', value: totals.value.drivers, icon: 'Users' },
    { label: 'New Vehicles', value: totals.value.vehicles, icon: 'Truck' },
    { label: 'Accidents', value: totals.value.accidents, icon: 'AlertTriangle' },
    { label: 'Maintenance Cost', value: money(totals.value.maintenanceCost), icon: 'Wrench' },
])

function applyFilters() {
    router.get(route('carrier.reports.monthly'), { ...filters }, { preserveScroll: true, replace: true })
}

function resetFilters() {
    filters.date_from = ''
    filters.date_to = ''
    applyFilters()
}
</script>

<template>
    <Head title="Monthly Summary" />
    <div class="grid grid-cols-12 gap-6">
        <div class="col-span-12">
            <ReportHero title="Monthly Summary Report" subtitle="Month-by-month operational breakdown across drivers, vehicles, accidents, maintenance, and repairs." icon="CalendarDays">
                <template #actions>
                    <Button as="a" :href="route('carrier.reports.monthly.export-pdf', { ...filters })" variant="outline-secondary" class="gap-2">
                        <Lucide icon="Download" class="h-4 w-4" />Export PDF
                    </Button>
                </template>
            </ReportHero>
        </div>
        <div class="col-span-12"><ReportStats :cards="statCards" /></div>
        <div class="col-span-12">
            <div class="box box--stacked p-5">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
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
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Month</th>
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Drivers</th>
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Vehicles</th>
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Accidents</th>
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Maintenance</th>
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Maint. Cost</th>
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Repairs</th>
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Repair Cost</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            <tr v-for="row in monthlyData" :key="row.month">
                                <td class="px-5 py-4 font-medium text-slate-800">{{ row.month_name }}</td>
                                <td class="px-5 py-4 text-slate-600">{{ row.drivers }}</td>
                                <td class="px-5 py-4 text-slate-600">{{ row.vehicles }}</td>
                                <td class="px-5 py-4 text-slate-600">{{ row.accidents }}</td>
                                <td class="px-5 py-4 text-slate-600">{{ row.maintenance?.count ?? 0 }}</td>
                                <td class="px-5 py-4 text-slate-600">{{ money(row.maintenance?.total_cost ?? 0) }}</td>
                                <td class="px-5 py-4 text-slate-600">{{ row.repairs?.count ?? 0 }}</td>
                                <td class="px-5 py-4 text-slate-600">{{ money(row.repairs?.total_cost ?? 0) }}</td>
                            </tr>
                            <tr v-if="!monthlyData.length"><td colspan="8" class="px-5 py-10 text-center text-slate-500">No monthly data matched the selected range.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>
