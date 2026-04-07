<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import RazeLayout from '@/layouts/RazeLayout.vue'
import ReportHero from './components/ReportHero.vue'
import ReportPagination from './components/ReportPagination.vue'
import type { PaginationLink } from './components/reportUtils'
defineOptions({ layout: RazeLayout })
const props = defineProps<{ driver: { id: number; name: string; carrier_name: string | null; email: string | null }; filters: { date_from: string; date_to: string }; dailyLogs: { data: any[]; links: PaginationLink[] }; stats: Record<string, any> }>()
</script>
<template>
    <Head :title="`HOS Details - ${driver.name}`" />
    <div class="grid grid-cols-12 gap-6">
        <div class="col-span-12"><ReportHero :title="`HOS Details - ${driver.name}`" subtitle="Daily log detail and summary metrics for the selected driver." icon="Clock" /></div>
        <div class="col-span-12"><div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4"><div class="box box--stacked p-5" v-for="(value, key) in stats" :key="key"><div class="text-xs uppercase tracking-wide text-slate-500">{{ String(key).replace(/_/g, ' ') }}</div><div class="mt-2 text-2xl font-semibold text-slate-800">{{ value }}</div></div></div></div>
        <div class="col-span-12"><div class="box box--stacked overflow-hidden"><div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-200 text-sm"><thead class="bg-slate-50"><tr><th class="px-5 py-3 text-left font-semibold text-slate-600">Date</th><th class="px-5 py-3 text-left font-semibold text-slate-600">Driving</th><th class="px-5 py-3 text-left font-semibold text-slate-600">On Duty</th><th class="px-5 py-3 text-left font-semibold text-slate-600">Off Duty</th><th class="px-5 py-3 text-left font-semibold text-slate-600">Duty Window</th><th class="px-5 py-3 text-left font-semibold text-slate-600">Violations</th></tr></thead><tbody class="divide-y divide-slate-100 bg-white"><tr v-for="row in dailyLogs.data" :key="row.id"><td class="px-5 py-4 text-slate-600">{{ row.date }}</td><td class="px-5 py-4 text-slate-600">{{ row.driving_hours }}</td><td class="px-5 py-4 text-slate-600">{{ row.on_duty_hours }}</td><td class="px-5 py-4 text-slate-600">{{ row.off_duty_hours }}</td><td class="px-5 py-4 text-slate-600">{{ row.duty_period_start || 'N/A' }} to {{ row.duty_period_end || 'N/A' }}</td><td class="px-5 py-4 text-slate-600">{{ row.has_violations ? 'Yes' : 'No' }}</td></tr><tr v-if="!dailyLogs.data.length"><td colspan="6" class="px-5 py-10 text-center text-slate-500">No daily logs matched the current filters.</td></tr></tbody></table></div><div class="border-t border-slate-100 p-4"><ReportPagination :links="dailyLogs.links" /></div></div></div>
    </div>
</template>
