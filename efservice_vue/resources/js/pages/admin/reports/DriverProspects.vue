<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3'
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
import { pickerOptions, statusTone, type PaginationLink } from './components/reportUtils'

declare function route(name: string, params?: any): string

defineOptions({ layout: RazeLayout })

interface ProspectRow {
    id: number
    driver_name: string
    email: string | null
    carrier_name: string | null
    status: string
    status_label: string
    created_at: string | null
    verification_count: number
}

const props = defineProps<{
    filters: { search: string; carrier_id: string; status: string; year: string; date_from: string; date_to: string; per_page: number }
    prospects: { data: ProspectRow[]; links: PaginationLink[]; total: number }
    stats: { total: number; draft: number; pending: number; rejected: number }
    carriers: { id: number; name: string }[]
    years: string[]
    statusOptions: { value: string; label: string }[]
    canFilterCarriers: boolean
}>()

const filters = reactive({ ...props.filters })

const statCards = computed(() => [
    { label: 'Total Prospects', value: props.stats.total, icon: 'UsersRound' },
    { label: 'Draft', value: props.stats.draft, icon: 'FilePenLine' },
    { label: 'Pending', value: props.stats.pending, icon: 'Clock3' },
    { label: 'Rejected', value: props.stats.rejected, icon: 'BadgeX' },
])

function applyFilters() {
    router.get(route('admin.reports.driver-prospects'), {
        search: filters.search || undefined,
        carrier_id: filters.carrier_id || undefined,
        status: filters.status || undefined,
        year: filters.year || undefined,
        date_from: filters.date_from || undefined,
        date_to: filters.date_to || undefined,
    }, { preserveState: true, preserveScroll: true, replace: true })
}
</script>

<template>
    <Head title="Driver Prospects Report" />

    <div class="grid grid-cols-12 gap-6">
        <div class="col-span-12">
            <ReportHero title="Driver Prospects Report" subtitle="Follow the recruitment pipeline from draft application to pending and rejected outcomes." icon="BadgePlus">
                <template #actions>
                    <Button as="a" :href="route('admin.reports.driver-prospects-pdf', { ...filters })" variant="outline-secondary" class="gap-2">
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
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <FormInput v-model="filters.search" type="text" placeholder="Search prospect, email, carrier..." @keyup.enter="applyFilters" />
                    <TomSelect v-if="canFilterCarriers" v-model="filters.carrier_id">
                        <option value="">All Carriers</option>
                        <option v-for="carrier in carriers" :key="carrier.id" :value="String(carrier.id)">{{ carrier.name }}</option>
                    </TomSelect>
                    <TomSelect v-model="filters.status">
                        <option v-for="option in statusOptions" :key="option.value || 'all'" :value="option.value">{{ option.label }}</option>
                    </TomSelect>
                    <TomSelect v-model="filters.year">
                        <option value="">All Years</option>
                        <option v-for="year in years" :key="year" :value="year">{{ year }}</option>
                    </TomSelect>
                    <Litepicker v-model="filters.date_from" :options="pickerOptions" />
                    <Litepicker v-model="filters.date_to" :options="pickerOptions" />
                    <div class="flex gap-3 xl:col-span-2">
                        <Button variant="primary" class="w-full" @click="applyFilters">Apply</Button>
                        <Button variant="outline-secondary" class="w-full" @click="filters.search=''; filters.carrier_id=''; filters.status=''; filters.year=''; filters.date_from=''; filters.date_to=''; applyFilters()">Reset</Button>
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
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Prospect</th>
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Carrier</th>
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Created</th>
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Verifications</th>
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            <tr v-for="prospect in prospects.data" :key="prospect.id">
                                <td class="px-5 py-4">
                                    <div class="font-medium text-slate-800">{{ prospect.driver_name }}</div>
                                    <div class="text-xs text-slate-500">{{ prospect.email || 'No email' }}</div>
                                </td>
                                <td class="px-5 py-4 text-slate-600">{{ prospect.carrier_name || 'N/A' }}</td>
                                <td class="px-5 py-4 text-slate-600">{{ prospect.created_at || 'N/A' }}</td>
                                <td class="px-5 py-4 text-slate-600">{{ prospect.verification_count }}</td>
                                <td class="px-5 py-4">
                                    <span class="rounded-full px-3 py-1 text-xs font-medium" :class="statusTone(prospect.status)">
                                        {{ prospect.status_label }}
                                    </span>
                                </td>
                            </tr>
                            <tr v-if="!prospects.data.length">
                                <td colspan="5" class="px-5 py-10 text-center text-slate-500">No driver prospects matched the current filters.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-slate-100 p-4">
                    <ReportPagination :links="prospects.links" />
                </div>
            </div>
        </div>
    </div>
</template>
