<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { computed, reactive } from 'vue'
import Button from '@/components/Base/Button'
import Litepicker from '@/components/Base/Litepicker/Litepicker.vue'
import Lucide from '@/components/Base/Lucide'
import TomSelect from '@/components/Base/TomSelect/TomSelect.vue'
import RazeLayout from '@/layouts/RazeLayout.vue'
import ReportHero from './components/ReportHero.vue'
import ReportPagination from './components/ReportPagination.vue'
import ReportStats from './components/ReportStats.vue'
import { pickerOptions, type PaginationLink } from './components/reportUtils'
declare function route(name: string, params?: any): string
defineOptions({ layout: RazeLayout })
interface Row { driver_id: number; driver_name: string; carrier_name: string | null; total_days: number; driving_hours: number; on_duty_hours: number; off_duty_hours: number; days_with_violations: number; first_log_date: string | null; last_log_date: string | null }
const props = defineProps<{ filters: { carrier_id: string; driver_id: string; date_from: string; date_to: string; has_violations: string; per_page: number }; driverSummaries: { data: Row[]; links: PaginationLink[]; total: number }; stats: { total_logs: number; logs_with_violations: number; compliance_percentage: number; average_driving_hours: number; average_on_duty_hours: number }; dateRangeLabel: string; carriers: { id: number; name: string }[]; drivers: { id: number; name: string }[]; canFilterCarriers: boolean }>()
const filters = reactive({ ...props.filters })
const statCards = computed(() => [{ label: 'Total Logs', value: props.stats.total_logs, icon: 'Clock' }, { label: 'Logs with Violations', value: props.stats.logs_with_violations, icon: 'AlertTriangle' }, { label: 'Compliance %', value: `${props.stats.compliance_percentage}%`, icon: 'ShieldCheck' }, { label: 'Avg Driving Hrs', value: props.stats.average_driving_hours, icon: 'Gauge' }])
function applyFilters() { router.get(route('admin.reports.hos'), { carrier_id: filters.carrier_id || undefined, driver_id: filters.driver_id || undefined, date_from: filters.date_from || undefined, date_to: filters.date_to || undefined, has_violations: filters.has_violations || undefined }, { preserveState: true, preserveScroll: true, replace: true }) }
</script>
<template>
    <Head title="HOS Report" />
    <div class="grid grid-cols-12 gap-6">
        <div class="col-span-12"><ReportHero title="HOS Report" :subtitle="`Hours-of-service compliance summary for ${dateRangeLabel}.`" icon="Clock"><template #actions><Button as="a" :href="route('admin.reports.hos-pdf', { ...filters })" variant="outline-secondary" class="gap-2"><Lucide icon="Download" class="h-4 w-4" />Export PDF</Button></template></ReportHero></div>
        <div class="col-span-12"><ReportStats :cards="statCards" /></div>
        <div class="col-span-12"><div class="box box--stacked p-5"><div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4"><TomSelect v-if="canFilterCarriers" v-model="filters.carrier_id"><option value="">All Carriers</option><option v-for="carrier in carriers" :key="carrier.id" :value="String(carrier.id)">{{ carrier.name }}</option></TomSelect><TomSelect v-model="filters.driver_id"><option value="">All Drivers</option><option v-for="driver in drivers" :key="driver.id" :value="String(driver.id)">{{ driver.name }}</option></TomSelect><TomSelect v-model="filters.has_violations"><option value="">All Logs</option><option value="yes">With Violations</option><option value="no">Compliant Only</option></TomSelect><Litepicker v-model="filters.date_from" :options="pickerOptions" /><Litepicker v-model="filters.date_to" :options="pickerOptions" /><div class="flex gap-3 xl:col-span-2"><Button variant="primary" class="w-full" @click="applyFilters">Apply</Button><Button variant="outline-secondary" class="w-full" @click="filters.carrier_id=''; filters.driver_id=''; filters.has_violations=''; filters.date_from=''; filters.date_to=''; applyFilters()">Reset</Button></div></div></div></div>
        <div class="col-span-12"><div class="box box--stacked overflow-hidden"><div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-200 text-sm"><thead class="bg-slate-50"><tr><th class="px-5 py-3 text-left font-semibold text-slate-600">Driver</th><th class="px-5 py-3 text-left font-semibold text-slate-600">Carrier</th><th class="px-5 py-3 text-left font-semibold text-slate-600">Hours</th><th class="px-5 py-3 text-left font-semibold text-slate-600">Violations</th><th class="px-5 py-3 text-left font-semibold text-slate-600">Range</th></tr></thead><tbody class="divide-y divide-slate-100 bg-white"><tr v-for="row in driverSummaries.data" :key="row.driver_id"><td class="px-5 py-4"><Link :href="route('admin.reports.hos-details', row.driver_id)" class="font-medium text-primary hover:underline">{{ row.driver_name }}</Link></td><td class="px-5 py-4 text-slate-600">{{ row.carrier_name || 'N/A' }}</td><td class="px-5 py-4 text-slate-600"><div>Driving: {{ row.driving_hours }}</div><div class="text-xs text-slate-500">On duty: {{ row.on_duty_hours }} · Off duty: {{ row.off_duty_hours }}</div></td><td class="px-5 py-4 text-slate-600">{{ row.days_with_violations }} day(s)</td><td class="px-5 py-4 text-slate-600">{{ row.first_log_date || 'N/A' }} to {{ row.last_log_date || 'N/A' }}</td></tr><tr v-if="!driverSummaries.data.length"><td colspan="5" class="px-5 py-10 text-center text-slate-500">No HOS summaries matched the current filters.</td></tr></tbody></table></div><div class="border-t border-slate-100 p-4"><ReportPagination :links="driverSummaries.links" /></div></div></div>
    </div>
</template>
