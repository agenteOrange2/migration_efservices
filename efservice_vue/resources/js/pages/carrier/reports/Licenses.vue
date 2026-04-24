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
    licenses: { type: Object, required: true },
    drivers: { type: Array, default: () => [] },
    licenseTypeOptions: { type: Array, default: () => [] },
    statusOptions: { type: Array, default: () => [] },
})
const { filters: initialFilters, stats, licenses, drivers, licenseTypeOptions, statusOptions } = props
const filters = reactive({ ...initialFilters })
const statCards = [
    { label: 'Total Licenses', value: stats?.total ?? 0, icon: 'CreditCard' },
    { label: 'Expiring Soon', value: stats?.expiring_soon ?? 0, icon: 'AlertTriangle' },
]

function applyFilters() {
    router.get(route('carrier.reports.licenses'), { ...filters }, { preserveScroll: true, replace: true })
}

function resetFilters() {
    filters.search = ''
    filters.driver = ''
    filters.license_type = ''
    filters.expiration_status = ''
    filters.date_from = ''
    filters.date_to = ''
    applyFilters()
}
</script>

<template>
    <Head title="License Reports" />

    <div class="grid grid-cols-12 gap-6">
        <div class="col-span-12">
            <ReportHero title="License Reports" subtitle="CDL, primary-license, and expiration monitoring for your drivers." icon="CreditCard">
                <template #actions>
                    <Button as="a" :href="route('carrier.reports.licenses.export-pdf', { ...filters })" variant="outline-secondary" class="gap-2">
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
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-6">
                    <FormInput v-model="filters.search" type="text" placeholder="Search license number, state..." @keyup.enter="applyFilters" />
                    <TomSelect v-model="filters.driver">
                        <option value="">All Drivers</option>
                        <option v-for="driver in drivers" :key="driver.id" :value="String(driver.id)">{{ driver.name }}</option>
                    </TomSelect>
                    <TomSelect v-model="filters.license_type">
                        <option v-for="option in licenseTypeOptions" :key="option.value || 'all'" :value="option.value">{{ option.label }}</option>
                    </TomSelect>
                    <TomSelect v-model="filters.expiration_status">
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
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Driver</th>
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">License Number</th>
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Type</th>
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">State</th>
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Issue Date</th>
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Expiration</th>
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            <tr v-for="license in licenses.data" :key="license.id">
                                <td class="px-5 py-4 text-slate-600">
                                    <div class="font-medium text-slate-800">{{ license.driver_name }}</div>
                                    <div v-if="license.is_primary" class="text-xs text-primary">Primary License</div>
                                </td>
                                <td class="px-5 py-4 text-slate-600">{{ license.license_number || 'N/A' }}</td>
                                <td class="px-5 py-4 text-slate-600">
                                    <div>{{ license.license_type || 'N/A' }}</div>
                                    <div class="text-xs text-slate-500">{{ license.license_class || 'N/A' }}</div>
                                </td>
                                <td class="px-5 py-4 text-slate-600">{{ license.state || 'N/A' }}</td>
                                <td class="px-5 py-4 text-slate-600">{{ license.issue_date || 'N/A' }}</td>
                                <td class="px-5 py-4 text-slate-600">{{ license.expiration_date || 'N/A' }}</td>
                                <td class="px-5 py-4"><span class="rounded-full px-3 py-1 text-xs font-medium" :class="statusTone(license.status)">{{ license.status }}</span></td>
                            </tr>
                            <tr v-if="!licenses.data.length"><td colspan="7" class="px-5 py-10 text-center text-slate-500">No licenses matched the current filters.</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-slate-100 p-4">
                    <ReportPagination :links="licenses.links" />
                </div>
            </div>
        </div>
    </div>
</template>
