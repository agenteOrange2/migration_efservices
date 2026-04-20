<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { computed, reactive } from 'vue'
import Button from '@/components/Base/Button'
import FormInput from '@/components/Base/Form/FormInput.vue'
import Litepicker from '@/components/Base/Litepicker/Litepicker.vue'
import Lucide from '@/components/Base/Lucide'
import TomSelect from '@/components/Base/TomSelect/TomSelect.vue'
import RazeLayout from '@/layouts/RazeLayout.vue'
import ReportHero from './components/ReportHero.vue'
import ReportPagination from './components/ReportPagination.vue'
import ReportStats from './components/ReportStats.vue'
import { pickerOptions, type PaginationLink } from './components/reportUtils'

defineOptions({ layout: RazeLayout })

declare function route(name: string, params?: any): string

interface AccidentRow {
    id: number
    driver_name: string | null
    carrier_name: string | null
    accident_date: string | null
    nature: string
    injuries_label: string
    fatalities_label: string
    comments: string | null
}

const props = defineProps<{
    filters: { search: string; carrier_id: string; driver_id: string; date_from: string; date_to: string; per_page: number }
    accidents: { data: AccidentRow[]; links: PaginationLink[]; total: number }
    stats: { total: number; with_injuries: number; with_fatalities: number; last_30_days: number }
    carriers: { id: number; name: string }[]
    drivers: { id: number; name: string }[]
    canFilterCarriers: boolean
    registerRoute: string
    listRoute: string
}>()

const filters = reactive({ ...props.filters })

const statCards = computed(() => [
    { label: 'Total Accidents', value: props.stats.total, icon: 'AlertTriangle', tone: 'danger' },
    { label: 'With Injuries', value: props.stats.with_injuries, icon: 'Ambulance', tone: 'warning' },
    { label: 'With Fatalities', value: props.stats.with_fatalities, icon: 'ShieldAlert', tone: 'danger' },
    { label: 'Last 30 Days', value: props.stats.last_30_days, icon: 'Clock3', tone: 'info' },
])

function applyFilters() {
    router.get(route('admin.reports.accidents'), {
        search: filters.search || undefined,
        carrier_id: filters.carrier_id || undefined,
        driver_id: filters.driver_id || undefined,
        date_from: filters.date_from || undefined,
        date_to: filters.date_to || undefined,
    }, { preserveState: true, preserveScroll: true, replace: true })
}
</script>

<template>
    <Head title="Accidents Report" />

    <div class="grid grid-cols-12 gap-6">
        <div class="col-span-12">
            <ReportHero title="Accidents Report" subtitle="Central report for driver accidents, severity markers, and quick entry into the accident register." icon="AlertTriangle">
                <template #actions>
                    <Button as="a" :href="`${route('admin.reports.accidents')}?export=pdf`" variant="outline-secondary" class="gap-2">
                        <Lucide icon="Download" class="h-4 w-4" />
                        Export PDF
                    </Button>
                    <Button as="a" :href="registerRoute" variant="primary" class="gap-2">
                        <Lucide icon="Plus" class="h-4 w-4" />
                        Register Accident
                    </Button>
                    <Button as="a" :href="listRoute" variant="outline-secondary" class="gap-2">
                        <Lucide icon="List" class="h-4 w-4" />
                        Accident List
                    </Button>
                </template>
            </ReportHero>
        </div>

        <div class="col-span-12">
            <ReportStats :cards="statCards" />
        </div>

        <div class="col-span-12">
            <div class="box box--stacked p-5">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <FormInput v-model="filters.search" type="text" placeholder="Search driver, carrier, accident..." @keyup.enter="applyFilters" />
                    <TomSelect v-if="canFilterCarriers" v-model="filters.carrier_id">
                        <option value="">All Carriers</option>
                        <option v-for="carrier in carriers" :key="carrier.id" :value="String(carrier.id)">{{ carrier.name }}</option>
                    </TomSelect>
                    <TomSelect v-model="filters.driver_id">
                        <option value="">All Drivers</option>
                        <option v-for="driver in drivers" :key="driver.id" :value="String(driver.id)">{{ driver.name }}</option>
                    </TomSelect>
                    <Litepicker v-model="filters.date_from" :options="pickerOptions" />
                    <Litepicker v-model="filters.date_to" :options="pickerOptions" />
                    <div class="flex gap-3 xl:col-span-2">
                        <Button variant="primary" class="w-full" @click="applyFilters">Apply</Button>
                        <Button variant="outline-secondary" class="w-full" @click="filters.search=''; filters.carrier_id=''; filters.driver_id=''; filters.date_from=''; filters.date_to=''; applyFilters()">Reset</Button>
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
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Carrier</th>
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Date</th>
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Nature</th>
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Severity</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            <tr v-for="accident in accidents.data" :key="accident.id">
                                <td class="px-5 py-4 font-medium text-slate-800">{{ accident.driver_name || 'N/A' }}</td>
                                <td class="px-5 py-4 text-slate-600">{{ accident.carrier_name || 'N/A' }}</td>
                                <td class="px-5 py-4 text-slate-600">{{ accident.accident_date || 'N/A' }}</td>
                                <td class="px-5 py-4 text-slate-600">
                                    <div>{{ accident.nature }}</div>
                                    <div v-if="accident.comments" class="text-xs text-slate-500">{{ accident.comments }}</div>
                                </td>
                                <td class="px-5 py-4 text-slate-600">
                                    <div>{{ accident.injuries_label }}</div>
                                    <div class="text-xs text-slate-500">{{ accident.fatalities_label }}</div>
                                </td>
                            </tr>
                            <tr v-if="!accidents.data.length">
                                <td colspan="5" class="px-5 py-10 text-center text-slate-500">No accidents matched the current filters.</td>
                            </tr>
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
