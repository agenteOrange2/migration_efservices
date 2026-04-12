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
import { money, pickerOptions, statusTone } from '@/pages/admin/reports/components/reportUtils'
import Lucide from '@/components/Base/Lucide'

declare function route(name: string, params?: any): string
defineOptions({ layout: CarrierLayout })

const props = defineProps({
    filters: { type: Object, required: true },
    stats: { type: Object, default: () => ({}) },
    repairRecords: { type: Object, required: true },
    vehicles: { type: Array, default: () => [] },
    statusOptions: { type: Array, default: () => [] },
})
const { filters: initialFilters, stats, repairRecords, vehicles, statusOptions } = props
const filters = reactive({ ...initialFilters })
const statCards = [
    { label: 'Total Repairs', value: stats?.count ?? 0, icon: 'Wrench' },
    { label: 'Completed', value: stats?.completed ?? 0, icon: 'BadgeCheck' },
    { label: 'In Progress', value: stats?.in_progress ?? 0, icon: 'LoaderCircle' },
    { label: 'Pending', value: stats?.pending ?? 0, icon: 'Clock3' },
]

function applyFilters() {
    router.get(route('carrier.reports.repairs'), { ...filters }, { preserveState: true, preserveScroll: true, replace: true })
}

function resetFilters() {
    filters.search = ''
    filters.vehicle = ''
    filters.repair_type = ''
    filters.status = ''
    filters.date_from = ''
    filters.date_to = ''
    applyFilters()
}
</script>

<template>
    <Head title="Repair Reports" />
    <div class="grid grid-cols-12 gap-6">
        <div class="col-span-12">
            <ReportHero title="Repair Reports" subtitle="Emergency repair visibility with cost and status tracking." icon="Wrench">
                <template #actions>
                    <Button as="a" :href="route('carrier.reports.repairs.export-pdf', { ...filters })" variant="outline-secondary" class="gap-2">
                        <Lucide icon="Download" class="h-4 w-4" />Export PDF
                    </Button>
                </template>
            </ReportHero>
        </div>
        <div class="col-span-12"><ReportStats :cards="statCards" /></div>
        <div class="col-span-12">
            <div class="box box--stacked p-5">
                <div class="mb-4 text-sm font-medium text-slate-600">Total cost: <span class="text-primary">{{ money(stats?.total_cost ?? 0) }}</span></div>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-6">
                    <FormInput v-model="filters.search" type="text" placeholder="Search repair, notes..." @keyup.enter="applyFilters" />
                    <TomSelect v-model="filters.vehicle">
                        <option value="">All Vehicles</option>
                        <option v-for="vehicle in vehicles" :key="vehicle.id" :value="String(vehicle.id)">{{ vehicle.name }}</option>
                    </TomSelect>
                    <FormInput v-model="filters.repair_type" type="text" placeholder="Repair type..." @keyup.enter="applyFilters" />
                    <TomSelect v-model="filters.status">
                        <option v-for="option in statusOptions" :key="option.value || 'all'" :value="option.value">{{ option.label }}</option>
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
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Vehicle</th>
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Repair</th>
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Date</th>
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Cost</th>
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            <tr v-for="row in repairRecords.data" :key="row.id">
                                <td class="px-5 py-4 font-medium text-slate-800">{{ row.vehicle_label || 'N/A' }}</td>
                                <td class="px-5 py-4 text-slate-600">
                                    <div>{{ row.repair_name || 'N/A' }}</div>
                                    <div class="text-xs text-slate-500">{{ row.description || '-' }}</div>
                                </td>
                                <td class="px-5 py-4 text-slate-600">{{ row.repair_date || 'N/A' }}</td>
                                <td class="px-5 py-4 text-slate-600">{{ money(row.cost) }}</td>
                                <td class="px-5 py-4"><span class="rounded-full px-3 py-1 text-xs font-medium" :class="statusTone(row.status)">{{ row.status }}</span></td>
                            </tr>
                            <tr v-if="!repairRecords.data.length"><td colspan="5" class="px-5 py-10 text-center text-slate-500">No repair records matched the current filters.</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-slate-100 p-4"><ReportPagination :links="repairRecords.links" /></div>
            </div>
        </div>
    </div>
</template>
