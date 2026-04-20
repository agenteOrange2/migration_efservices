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
interface Row { id: number; driver_name: string; carrier_name: string | null; training_title: string; assigned_date: string | null; due_date: string | null; completed_date: string | null; status: string }
const props = defineProps<{ filters: { search: string; carrier_id: string; status: string; date_from: string; date_to: string; per_page: number }; assignments: { data: Row[]; links: PaginationLink[]; total: number }; stats: { total: number; completed: number; assigned: number; in_progress: number; overdue: number; completion_rate: number }; carriers: { id: number; name: string }[]; statusOptions: { value: string; label: string }[]; canFilterCarriers: boolean }>()
const filters = reactive({ ...props.filters })
const statCards = computed(() => [
    { label: 'Assignments', value: props.stats.total, icon: 'GraduationCap', tone: 'primary' },
    { label: 'Completed', value: props.stats.completed, icon: 'BadgeCheck', tone: 'success' },
    { label: 'In Progress', value: props.stats.in_progress, icon: 'LoaderCircle', tone: 'warning' },
    { label: 'Overdue', value: props.stats.overdue, icon: 'AlarmClock', tone: 'danger' },
])
function applyFilters() { router.get(route('admin.reports.trainings'), { search: filters.search || undefined, carrier_id: filters.carrier_id || undefined, status: filters.status || undefined, date_from: filters.date_from || undefined, date_to: filters.date_to || undefined }, { preserveState: true, preserveScroll: true, replace: true }) }
</script>

<template>
    <Head title="Trainings Report" />
    <div class="grid grid-cols-12 gap-6">
        <div class="col-span-12"><ReportHero title="Trainings Report" subtitle="Assignment, due-date, and completion visibility for driver training records." icon="GraduationCap"><template #actions><Button as="a" :href="route('admin.reports.trainings-pdf', { ...filters })" variant="outline-secondary" class="gap-2"><Lucide icon="Download" class="h-4 w-4" />Export PDF</Button></template></ReportHero></div>
        <div class="col-span-12"><ReportStats :cards="statCards" /></div>
        <div class="col-span-12"><div class="box box--stacked p-5"><div class="mb-4 text-sm font-medium text-slate-600">Completion rate: <span class="text-primary">{{ stats.completion_rate }}%</span></div><div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4"><FormInput v-model="filters.search" type="text" placeholder="Search training or driver..." @keyup.enter="applyFilters" /><TomSelect v-if="canFilterCarriers" v-model="filters.carrier_id"><option value="">All Carriers</option><option v-for="carrier in carriers" :key="carrier.id" :value="String(carrier.id)">{{ carrier.name }}</option></TomSelect><TomSelect v-model="filters.status"><option v-for="option in statusOptions" :key="option.value || 'all'" :value="option.value">{{ option.label }}</option></TomSelect><Litepicker v-model="filters.date_from" :options="pickerOptions" /><Litepicker v-model="filters.date_to" :options="pickerOptions" /><div class="flex gap-3 xl:col-span-2"><Button variant="primary" class="w-full" @click="applyFilters">Apply</Button><Button variant="outline-secondary" class="w-full" @click="filters.search=''; filters.carrier_id=''; filters.status=''; filters.date_from=''; filters.date_to=''; applyFilters()">Reset</Button></div></div></div></div>
        <div class="col-span-12"><div class="box box--stacked overflow-hidden"><div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-200 text-sm"><thead class="bg-slate-50"><tr><th class="px-5 py-3 text-left font-semibold text-slate-600">Driver</th><th class="px-5 py-3 text-left font-semibold text-slate-600">Carrier</th><th class="px-5 py-3 text-left font-semibold text-slate-600">Training</th><th class="px-5 py-3 text-left font-semibold text-slate-600">Dates</th><th class="px-5 py-3 text-left font-semibold text-slate-600">Status</th></tr></thead><tbody class="divide-y divide-slate-100 bg-white"><tr v-for="row in assignments.data" :key="row.id"><td class="px-5 py-4 font-medium text-slate-800">{{ row.driver_name }}</td><td class="px-5 py-4 text-slate-600">{{ row.carrier_name || 'N/A' }}</td><td class="px-5 py-4 text-slate-600">{{ row.training_title }}</td><td class="px-5 py-4 text-slate-600"><div>Assigned: {{ row.assigned_date || 'N/A' }}</div><div class="text-xs text-slate-500">Due: {{ row.due_date || 'N/A' }} | Completed: {{ row.completed_date || 'N/A' }}</div></td><td class="px-5 py-4"><span class="rounded-full px-3 py-1 text-xs font-medium" :class="statusTone(row.status)">{{ row.status }}</span></td></tr><tr v-if="!assignments.data.length"><td colspan="5" class="px-5 py-10 text-center text-slate-500">No training assignments matched the current filters.</td></tr></tbody></table></div><div class="border-t border-slate-100 p-4"><ReportPagination :links="assignments.links" /></div></div></div>
    </div>
</template>
