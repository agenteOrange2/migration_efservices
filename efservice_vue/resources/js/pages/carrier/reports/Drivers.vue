<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3'
import { reactive } from 'vue'
import CarrierLayout from '@/layouts/CarrierLayout.vue'
import Button from '@/components/Base/Button'
import { FormInput, FormSelect } from '@/components/Base/Form'
import Litepicker from '@/components/Base/Litepicker/Litepicker.vue'
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
    drivers: { type: Object, required: true },
    statusOptions: { type: Array, default: () => [] },
})
const { filters: initialFilters, stats, drivers, statusOptions } = props
const filters = reactive({ ...initialFilters })
const statCards = [
    { label: 'Total Drivers', value: stats?.total ?? 0, icon: 'Users' },
    { label: 'Active', value: stats?.active ?? 0, icon: 'UserCheck' },
    { label: 'Inactive', value: stats?.inactive ?? 0, icon: 'UserMinus' },
    { label: 'New 30 Days', value: stats?.recent ?? 0, icon: 'Clock3' },
]

function applyFilters() {
    router.get(route('carrier.reports.drivers'), { ...filters }, { preserveState: true, preserveScroll: true, replace: true })
}

function resetFilters() {
    filters.search = ''
    filters.status = ''
    filters.date_from = ''
    filters.date_to = ''
    applyFilters()
}
</script>

<template>
    <Head title="Driver Reports" />

    <div class="grid grid-cols-12 gap-6">
        <div class="col-span-12">
            <ReportHero title="Driver Reports" subtitle="Driver roster, license visibility, and recent onboarding movement." icon="Users">
                <template #actions>
                    <Button as="a" :href="route('carrier.reports.drivers.export-pdf', { ...filters })" variant="outline-secondary" class="gap-2">
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
                    <FormInput v-model="filters.search" type="text" placeholder="Search name, email, phone..." @keyup.enter="applyFilters" />
                    <FormSelect v-model="filters.status">
                        <option v-for="option in statusOptions" :key="option.value || 'all'" :value="option.value">{{ option.label }}</option>
                    </FormSelect>
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
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Contact</th>
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">License</th>
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">License Expiration</th>
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Status</th>
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Registered</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            <tr v-for="driver in drivers.data" :key="driver.id" :class="driver.has_expiring_license ? 'bg-slate-50/70' : ''">
                                <td class="px-5 py-4">
                                    <div class="font-medium text-slate-800">{{ driver.name }}</div>
                                    <div v-if="driver.has_expiring_license" class="text-xs text-slate-500">License expiring soon</div>
                                </td>
                                <td class="px-5 py-4 text-slate-600">
                                    <div>{{ driver.email || 'N/A' }}</div>
                                    <div class="text-xs text-slate-500">{{ driver.phone || 'N/A' }}</div>
                                </td>
                                <td class="px-5 py-4 text-slate-600">
                                    <div>{{ driver.license_number || 'N/A' }}</div>
                                    <div class="text-xs text-slate-500">{{ driver.license_state || 'N/A' }}</div>
                                </td>
                                <td class="px-5 py-4 text-slate-600">{{ driver.license_expiration || 'N/A' }}</td>
                                <td class="px-5 py-4"><span class="rounded-full px-3 py-1 text-xs font-medium" :class="statusTone(driver.status)">{{ driver.status || 'N/A' }}</span></td>
                                <td class="px-5 py-4 text-slate-600">{{ driver.registered_at || 'N/A' }}</td>
                            </tr>
                            <tr v-if="!drivers.data.length"><td colspan="6" class="px-5 py-10 text-center text-slate-500">No drivers matched the current filters.</td></tr>
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
