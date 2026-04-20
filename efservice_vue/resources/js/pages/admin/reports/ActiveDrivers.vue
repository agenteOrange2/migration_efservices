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
import ReportStats from './components/ReportStats.vue'
import ReportPagination from './components/ReportPagination.vue'
import { labelize, pickerOptions, statusTone, type PaginationLink } from './components/reportUtils'

declare function route(name: string, params?: any): string

defineOptions({ layout: RazeLayout })

interface DriverRow {
    id: number
    driver_name: string
    email: string | null
    carrier_name: string | null
    registered_at: string | null
    updated_at: string | null
    termination_date: string | null
    license_number: string | null
    status_label: string
}

const props = defineProps<{
    filters: { tab: string; search: string; carrier_id: string; date_from: string; date_to: string; per_page: number }
    drivers: { data: DriverRow[]; links: PaginationLink[]; total: number }
    stats: { total: number; active: number; inactive: number; new: number }
    carriers: { id: number; name: string }[]
    tabs: { value: string; label: string }[]
    canFilterCarriers: boolean
}>()

const filters = reactive({ ...props.filters })

const statCards = computed(() => [
    { label: 'Total Drivers', value: props.stats.total, icon: 'Users', tone: 'primary' },
    { label: 'Active', value: props.stats.active, icon: 'UserCheck', tone: 'success' },
    { label: 'Inactive', value: props.stats.inactive, icon: 'UserMinus', tone: 'danger' },
    { label: 'New 30 Days', value: props.stats.new, icon: 'UserPlus', tone: 'info' },
])

function applyFilters() {
    router.get(route('admin.reports.active-drivers'), {
        tab: filters.tab || undefined,
        search: filters.search || undefined,
        carrier_id: filters.carrier_id || undefined,
        date_from: filters.date_from || undefined,
        date_to: filters.date_to || undefined,
        per_page: filters.per_page || undefined,
    }, { preserveState: true, preserveScroll: true, replace: true })
}

function resetFilters() {
    filters.tab = 'all'
    filters.search = ''
    filters.carrier_id = ''
    filters.date_from = ''
    filters.date_to = ''
    applyFilters()
}
</script>

<template>
    <Head title="Active Drivers Report" />

    <div class="grid grid-cols-12 gap-6">
        <div class="col-span-12">
            <ReportHero title="Active Drivers Report" subtitle="Track approved drivers by carrier, recent registrations, and roster status." icon="UserCheck">
                <template #actions>
                    <Button as="a" :href="route('admin.reports.active-drivers-pdf', { ...filters })" variant="outline-secondary" class="gap-2">
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
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-5">
                    <FormInput v-model="filters.search" type="text" placeholder="Search driver, email, carrier, license..." @keyup.enter="applyFilters" />
                    <TomSelect v-if="canFilterCarriers" v-model="filters.carrier_id">
                        <option value="">All Carriers</option>
                        <option v-for="carrier in carriers" :key="carrier.id" :value="String(carrier.id)">{{ carrier.name }}</option>
                    </TomSelect>
                    <Litepicker v-model="filters.date_from" :options="pickerOptions" />
                    <Litepicker v-model="filters.date_to" :options="pickerOptions" />
                    <div class="flex gap-3">
                        <Button variant="primary" class="w-full" @click="applyFilters">Apply</Button>
                        <Button variant="outline-secondary" class="w-full" @click="resetFilters">Reset</Button>
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
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Driver</th>
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Carrier</th>
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">License</th>
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Registered</th>
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            <tr v-for="driver in drivers.data" :key="driver.id">
                                <td class="px-5 py-4">
                                    <div class="font-medium text-slate-800">{{ driver.driver_name }}</div>
                                    <div class="text-xs text-slate-500">{{ driver.email || 'No email' }}</div>
                                </td>
                                <td class="px-5 py-4 text-slate-600">{{ driver.carrier_name || 'N/A' }}</td>
                                <td class="px-5 py-4 text-slate-600">{{ driver.license_number || 'N/A' }}</td>
                                <td class="px-5 py-4 text-slate-600">{{ driver.registered_at || 'N/A' }}</td>
                                <td class="px-5 py-4">
                                    <span class="rounded-full px-3 py-1 text-xs font-medium" :class="statusTone(labelize(driver.status_label).toLowerCase())">
                                        {{ driver.status_label }}
                                    </span>
                                </td>
                            </tr>
                            <tr v-if="!drivers.data.length">
                                <td colspan="5" class="px-5 py-10 text-center text-slate-500">No drivers matched the current filters.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-slate-100 p-4">
                    <ReportPagination :links="drivers.links" />
                </div>
            </div>
        </div>
    </div>
</template>
