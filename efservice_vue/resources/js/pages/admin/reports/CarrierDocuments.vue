<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3'
import { computed, reactive } from 'vue'
import Button from '@/components/Base/Button'
import FormInput from '@/components/Base/Form/FormInput.vue'
import Lucide from '@/components/Base/Lucide'
import TomSelect from '@/components/Base/TomSelect/TomSelect.vue'
import RazeLayout from '@/layouts/RazeLayout.vue'
import ReportHero from './components/ReportHero.vue'
import ReportPagination from './components/ReportPagination.vue'
import ReportStats from './components/ReportStats.vue'
import { type PaginationLink } from './components/reportUtils'

declare function route(name: string, params?: any): string

defineOptions({ layout: RazeLayout })

interface CarrierRow {
    id: number
    slug: string
    carrier_name: string
    total_documents: number
    approved_documents: number
    pending_documents: number
    in_process_documents: number
    completion_rate: number
}

const props = defineProps<{
    filters: { search: string; carrier_id: string; status: string; per_page: number }
    carriers: { data: CarrierRow[]; links: PaginationLink[]; total: number }
    carriersOptions: { id: number; name: string }[]
    stats: { total_documents: number; approved: number; pending: number; in_process: number }
    statusOptions: { value: string; label: string }[]
    canFilterCarriers: boolean
}>()

const filters = reactive({ ...props.filters })

const statCards = computed(() => [
    { label: 'Total Documents', value: props.stats.total_documents, icon: 'Files', tone: 'primary' },
    { label: 'Approved', value: props.stats.approved, icon: 'BadgeCheck', tone: 'success' },
    { label: 'Pending', value: props.stats.pending, icon: 'Clock3', tone: 'warning' },
    { label: 'In Process', value: props.stats.in_process, icon: 'LoaderCircle', tone: 'info' },
])

function applyFilters() {
    router.get(route('admin.reports.carrier-documents'), {
        search: filters.search || undefined,
        carrier_id: filters.carrier_id || undefined,
        status: filters.status || undefined,
    }, { preserveState: true, preserveScroll: true, replace: true })
}
</script>

<template>
    <Head title="Carrier Documents Report" />

    <div class="grid grid-cols-12 gap-6">
        <div class="col-span-12">
            <ReportHero title="Carrier Documents Report" subtitle="Monitor document completion across carriers and jump straight to ZIP downloads." icon="FileArchive">
                <template #actions>
                    <Button as="a" :href="route('admin.reports.carrier-documents-pdf', { ...filters })" variant="outline-secondary" class="gap-2">
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
                    <FormInput v-model="filters.search" type="text" placeholder="Search carrier..." @keyup.enter="applyFilters" />
                    <TomSelect v-if="canFilterCarriers" v-model="filters.carrier_id">
                        <option value="">All Carriers</option>
                        <option v-for="carrier in carriersOptions" :key="carrier.id" :value="String(carrier.id)">{{ carrier.name }}</option>
                    </TomSelect>
                    <TomSelect v-model="filters.status">
                        <option v-for="option in statusOptions" :key="option.value || 'all'" :value="option.value">{{ option.label }}</option>
                    </TomSelect>
                    <div class="flex gap-3">
                        <Button variant="primary" class="w-full" @click="applyFilters">Apply</Button>
                        <Button variant="outline-secondary" class="w-full" @click="filters.search=''; filters.carrier_id=''; filters.status=''; applyFilters()">Reset</Button>
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
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Carrier</th>
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Totals</th>
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Progress</th>
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            <tr v-for="carrier in carriers.data" :key="carrier.id">
                                <td class="px-5 py-4 font-medium text-slate-800">{{ carrier.carrier_name }}</td>
                                <td class="px-5 py-4 text-slate-600">
                                    <div>{{ carrier.total_documents }} total</div>
                                    <div class="text-xs text-slate-500">{{ carrier.approved_documents }} approved / {{ carrier.pending_documents }} pending / {{ carrier.in_process_documents }} in process</div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="mb-2 text-sm font-medium text-slate-700">{{ carrier.completion_rate }}%</div>
                                    <div class="h-2 rounded-full bg-slate-200">
                                        <div class="h-2 rounded-full bg-primary" :style="{ width: `${carrier.completion_rate}%` }" />
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <Button as="a" :href="route('admin.reports.download-carrier-documents', carrier.slug)" variant="outline-secondary" class="gap-2">
                                        <Lucide icon="Archive" class="h-4 w-4" />
                                        Download ZIP
                                    </Button>
                                </td>
                            </tr>
                            <tr v-if="!carriers.data.length">
                                <td colspan="4" class="px-5 py-10 text-center text-slate-500">No carriers matched the current filters.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-slate-100 p-4">
                    <ReportPagination :links="carriers.links" />
                </div>
            </div>
        </div>
    </div>
</template>
