<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { reactive, ref } from 'vue'
import Button from '@/components/Base/Button'
import { FormInput } from '@/components/Base/Form'
import { Dialog } from '@/components/Base/Headless'
import Litepicker from '@/components/Base/Litepicker/Litepicker.vue'
import Lucide from '@/components/Base/Lucide'
import TomSelect from '@/components/Base/TomSelect/TomSelect.vue'
import RazeLayout from '@/layouts/RazeLayout.vue'

declare function route(name: string, params?: any): string

defineOptions({ layout: RazeLayout })

const props = defineProps<{
    driver: { id: number; name: string; email: string | null; carrier_name: string | null; current_cycle: string }
    filters: { start_date: string; end_date: string }
    stats: { current_status: string; entries_count: number; daily_logs_count: number; violations_count: number; documents_count: number }
    statusOptions: { value: string; label: string }[]
    entries: any[]
    dailyLogs: any[]
    violations: any[]
    documents: any[]
    routeNames?: {
        driverLog?: string
        documentsIndex?: string
        violationsIndex?: string
        violationsShow?: string
        entryUpdate?: string
        entryDestroy?: string
        entryBulkDestroy?: string
    }
}>()

import { computed } from 'vue'

const filters = reactive({ ...props.filters })
const selectedEntryIds = ref<number[]>([])
const editOpen = ref(false)
const editForm = reactive({
    id: 0,
    status: '',
    start_time: '',
    end_time: '',
    formatted_address: '',
    manual_entry_reason: '',
})

const pickerOptions = {
    autoApply: true,
    singleMode: true,
    numberOfColumns: 1,
    numberOfMonths: 1,
    format: 'M/D/YYYY',
}

const allSelected = computed(() =>
    props.entries.length > 0 && selectedEntryIds.value.length === props.entries.length
)

function toggleSelectAll() {
    if (allSelected.value) {
        selectedEntryIds.value = []
    } else {
        selectedEntryIds.value = props.entries.map((e: any) => e.id)
    }
}

// Quick date shortcuts
function setRange(days: number) {
    const now = new Date()
    const end = new Date(now)
    const start = new Date(now)
    start.setDate(start.getDate() - days + 1)
    filters.end_date = formatPickerDate(end)
    filters.start_date = formatPickerDate(start)
    applyFilters()
}

function setLastMonth() {
    const now = new Date()
    const first = new Date(now.getFullYear(), now.getMonth() - 1, 1)
    const last = new Date(now.getFullYear(), now.getMonth(), 0)
    filters.start_date = formatPickerDate(first)
    filters.end_date = formatPickerDate(last)
    applyFilters()
}

function setThisMonth() {
    const now = new Date()
    const first = new Date(now.getFullYear(), now.getMonth(), 1)
    filters.start_date = formatPickerDate(first)
    filters.end_date = formatPickerDate(now)
    applyFilters()
}

function formatPickerDate(d: Date): string {
    return `${d.getMonth() + 1}/${d.getDate()}/${d.getFullYear()}`
}

function statusTone(status: string) {
    const value = String(status || '').toLowerCase()
    if (['driving', 'on_duty_driving'].some((item) => value.includes(item))) return 'bg-info/10 text-info'
    if (['on duty', 'on_duty_not_driving', 'yard move'].some((item) => value.includes(item))) return 'bg-warning/10 text-warning'
    if (['sleep', 'off duty', 'off_duty', 'rest'].some((item) => value.includes(item))) return 'bg-success/10 text-success'
    if (['violation', 'exceeded', 'suspended'].some((item) => value.includes(item))) return 'bg-danger/10 text-danger'
    return 'bg-primary/10 text-primary'
}

function applyFilters() {
    router.get(route(props.routeNames?.driverLog ?? 'admin.hos.driver.log', props.driver.id), {
        start_date: filters.start_date || undefined,
        end_date: filters.end_date || undefined,
    }, { preserveState: true, preserveScroll: true, replace: true })
}

function openEdit(entry: any) {
    editForm.id = entry.id
    editForm.status = entry.status
    editForm.start_time = entry.edit_start_time || ''
    editForm.end_time = entry.edit_end_time || ''
    editForm.formatted_address = entry.formatted_address || ''
    editForm.manual_entry_reason = entry.manual_entry_reason || ''
    editOpen.value = true
}

function saveEntry() {
    router.put(route(props.routeNames?.entryUpdate ?? 'admin.hos.entries.update', editForm.id), {
        status: editForm.status,
        start_time: editForm.start_time,
        end_time: editForm.end_time || null,
        formatted_address: editForm.formatted_address || null,
        manual_entry_reason: editForm.manual_entry_reason || null,
    }, {
        preserveScroll: true,
        onSuccess: () => { editOpen.value = false },
    })
}

function destroyEntry(id: number) {
    if (!confirm('Delete this HOS entry?')) return
    router.delete(route(props.routeNames?.entryDestroy ?? 'admin.hos.entries.destroy', id), { preserveScroll: true })
}

function bulkDelete() {
    if (!selectedEntryIds.value.length || !confirm(`Delete ${selectedEntryIds.value.length} selected entries?`)) return
    router.post(route(props.routeNames?.entryBulkDestroy ?? 'admin.hos.entries.bulk-destroy'), {
        entry_ids: selectedEntryIds.value,
    }, {
        preserveScroll: true,
        onSuccess: () => { selectedEntryIds.value = [] },
    })
}
</script>

<template>
    <Head :title="`HOS Log - ${driver.name}`" />

    <div class="grid grid-cols-12 gap-6">
        <div class="col-span-12">
            <div class="box box--stacked p-6">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex items-center gap-4">
                        <div class="rounded-2xl border border-primary/20 bg-primary/10 p-3">
                            <Lucide icon="Clock3" class="h-8 w-8 text-primary" />
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-slate-800">{{ driver.name }}</h1>
                            <p class="mt-1 text-sm text-slate-500">{{ driver.carrier_name || 'Carrier N/A' }} · {{ driver.email || 'No email' }} · Cycle {{ driver.current_cycle === '60_7' ? '60 / 7' : '70 / 8' }}</p>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <Link :href="route(props.routeNames?.documentsIndex ?? 'admin.hos.documents.index', { driver_id: driver.id })" class="inline-flex items-center gap-2 text-sm font-medium text-primary hover:underline">
                            <Lucide icon="FileText" class="h-4 w-4" />
                            Open Documents
                        </Link>
                        <Link :href="route(props.routeNames?.violationsIndex ?? 'admin.hos.violations', { driver_id: driver.id })" class="inline-flex items-center gap-2 text-sm font-medium text-primary hover:underline">
                            <Lucide icon="AlertTriangle" class="h-4 w-4" />
                            Driver Violations
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-5">
            <div class="box box--stacked border border-primary/10 bg-primary/[0.04] p-5"><div class="text-sm text-slate-500">Current Status</div><div class="mt-2"><span class="rounded-full px-2.5 py-1 text-sm font-semibold" :class="statusTone(stats.current_status)">{{ stats.current_status }}</span></div></div>
            <div class="box box--stacked border border-primary/10 bg-primary/[0.04] p-5"><div class="text-sm text-slate-500">Entries</div><div class="mt-2 text-3xl font-semibold text-primary">{{ stats.entries_count }}</div></div>
            <div class="box box--stacked border border-info/10 bg-info/[0.04] p-5"><div class="text-sm text-slate-500">Daily Logs</div><div class="mt-2 text-3xl font-semibold text-info">{{ stats.daily_logs_count }}</div></div>
            <div class="box box--stacked border border-danger/10 bg-danger/[0.04] p-5"><div class="text-sm text-slate-500">Violations</div><div class="mt-2 text-3xl font-semibold text-danger">{{ stats.violations_count }}</div></div>
            <div class="box box--stacked border border-success/10 bg-success/[0.04] p-5"><div class="text-sm text-slate-500">Documents</div><div class="mt-2 text-3xl font-semibold text-success">{{ stats.documents_count }}</div></div>
        </div>

        <div class="col-span-12">
            <div class="box box--stacked p-5">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <Litepicker v-model="filters.start_date" :options="pickerOptions" placeholder="From date" />
                    <Litepicker v-model="filters.end_date" :options="pickerOptions" placeholder="To date" />
                    <div class="flex gap-2">
                        <Button variant="primary" class="flex-1" @click="applyFilters">Apply</Button>
                        <Button variant="outline-secondary" class="flex-1" @click="filters.start_date=''; filters.end_date=''; applyFilters()">Reset</Button>
                    </div>
                </div>
                <div class="mt-3 flex flex-wrap gap-2">
                    <button class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs text-slate-600 hover:bg-slate-100" @click="setRange(7)">Last 7 days</button>
                    <button class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs text-slate-600 hover:bg-slate-100" @click="setRange(14)">Last 14 days</button>
                    <button class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs text-slate-600 hover:bg-slate-100" @click="setRange(30)">Last 30 days</button>
                    <button class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs text-slate-600 hover:bg-slate-100" @click="setThisMonth()">This month</button>
                    <button class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs text-slate-600 hover:bg-slate-100" @click="setLastMonth()">Last month</button>
                    <button class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs text-slate-600 hover:bg-slate-100" @click="setRange(90)">Last 3 months</button>
                </div>
            </div>
        </div>

        <div class="col-span-12 xl:col-span-8">
            <div class="box box--stacked overflow-hidden">
                <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-800">Detailed Entries</h2>
                        <p class="mt-0.5 text-xs text-slate-500">{{ entries.length }} entries in selected range</p>
                    </div>
                    <Button v-if="selectedEntryIds.length" variant="danger" class="gap-2" @click="bulkDelete">
                        <Lucide icon="Trash2" class="h-4 w-4" />
                        Delete ({{ selectedEntryIds.length }})
                    </Button>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">
                                    <input type="checkbox" :checked="allSelected" class="form-check-input" @change="toggleSelectAll" />
                                </th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Date</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Status</th>
                                <th class="px-4 py-3 text-center font-semibold text-slate-600">Start</th>
                                <th class="px-4 py-3 text-center font-semibold text-slate-600">End</th>
                                <th class="px-4 py-3 text-center font-semibold text-slate-600">Duration</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Location</th>
                                <th class="px-4 py-3 text-right font-semibold text-slate-600">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            <tr v-for="entry in entries" :key="entry.id" class="hover:bg-slate-50">
                                <td class="px-4 py-3">
                                    <input v-model="selectedEntryIds" :value="entry.id" type="checkbox" class="form-check-input" />
                                </td>
                                <td class="px-4 py-3 font-medium text-slate-800">{{ entry.date || '—' }}</td>
                                <td class="px-4 py-3">
                                    <span class="rounded-full px-2.5 py-1 text-xs font-medium" :class="statusTone(entry.status)">{{ entry.status_label }}</span>
                                    <div v-if="entry.is_ghost_log || entry.is_manual_entry" class="mt-1 text-[11px]" :class="entry.is_ghost_log ? 'text-warning' : 'text-info'">
                                        {{ entry.is_ghost_log ? '⚠ Ghost log' : '✎ Manual' }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-center font-medium text-slate-800">{{ entry.start_time_short || '—' }}</td>
                                <td class="px-4 py-3 text-center text-slate-600">{{ entry.end_time_short || '—' }}</td>
                                <td class="px-4 py-3 text-center font-medium text-slate-800">{{ entry.duration || '—' }}</td>
                                <td class="px-4 py-3">
                                    <a v-if="entry.maps_url" :href="entry.maps_url" target="_blank" class="flex items-center gap-1.5 text-xs text-primary hover:underline">
                                        <Lucide icon="MapPin" class="h-3 w-3 flex-shrink-0" />
                                        <span class="max-w-[180px] truncate">{{ entry.formatted_address || (entry.latitude ? `${entry.latitude}, ${entry.longitude}` : 'View') }}</span>
                                        <Lucide icon="ExternalLink" class="h-3 w-3 flex-shrink-0" />
                                    </a>
                                    <span v-else class="flex items-center gap-1.5 text-xs text-slate-400">
                                        <Lucide icon="MapPin" class="h-3 w-3" />
                                        No location
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex justify-end gap-1.5">
                                        <button class="rounded-lg p-1.5 text-primary hover:bg-primary/10" title="Edit" @click="openEdit(entry)">
                                            <Lucide icon="Pencil" class="h-4 w-4" />
                                        </button>
                                        <button class="rounded-lg p-1.5 text-danger hover:bg-danger/10" title="Delete" @click="destroyEntry(entry.id)">
                                            <Lucide icon="Trash2" class="h-4 w-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!entries.length">
                                <td colspan="8" class="px-5 py-12 text-center">
                                    <Lucide icon="Clock" class="mx-auto mb-3 h-10 w-10 text-slate-300" />
                                    <p class="text-slate-500">No entries found for the selected period.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-span-12 xl:col-span-4 space-y-6">
            <div class="box box--stacked overflow-hidden">
                <div class="border-b border-slate-200 px-5 py-4"><h2 class="text-lg font-semibold text-slate-800">Daily Logs</h2></div>
                <div class="divide-y divide-slate-100">
                    <div v-for="log in dailyLogs" :key="log.id" class="px-5 py-4 text-sm">
                        <div class="font-medium text-slate-800">{{ log.date }}</div>
                        <div class="mt-1 text-slate-600">Driving: {{ log.driving_time }}</div>
                        <div class="text-slate-600">On Duty: {{ log.on_duty_time }}</div>
                        <div class="text-slate-600">Off Duty: {{ log.off_duty_time }}</div>
                        <div class="mt-1 text-xs" :class="log.has_violations ? 'text-danger' : 'text-success'">
                            {{ log.has_violations ? 'Violations detected' : 'No violations' }}
                        </div>
                    </div>
                    <div v-if="!dailyLogs.length" class="px-5 py-8 text-center text-sm text-slate-500">No daily logs in this range.</div>
                </div>
            </div>

            <div class="box box--stacked overflow-hidden">
                <div class="border-b border-slate-200 px-5 py-4"><h2 class="text-lg font-semibold text-slate-800">Violations</h2></div>
                <div class="divide-y divide-slate-100">
                    <div v-for="violation in violations" :key="violation.id" class="px-5 py-4 text-sm">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <div class="font-medium text-slate-800">{{ violation.type }}</div>
                                <div class="mt-1 text-slate-600">
                                    {{ violation.date }} ·
                                    <span :class="String(violation.severity || '').toLowerCase().includes('high') || String(violation.severity || '').toLowerCase().includes('critical') ? 'text-danger' : 'text-warning'">{{ violation.severity }}</span>
                                </div>
                                <div class="text-xs text-slate-500">{{ violation.hours_exceeded }}h exceeded</div>
                            </div>
                            <Link :href="route(props.routeNames?.violationsShow ?? 'admin.hos.violations.show', violation.id)" class="text-primary hover:underline">Open</Link>
                        </div>
                    </div>
                    <div v-if="!violations.length" class="px-5 py-8 text-center text-sm text-slate-500">No violations in this range.</div>
                </div>
            </div>

            <div class="box box--stacked overflow-hidden">
                <div class="border-b border-slate-200 px-5 py-4"><h2 class="text-lg font-semibold text-slate-800">Documents</h2></div>
                <div class="divide-y divide-slate-100">
                    <div v-for="document in documents" :key="document.id" class="px-5 py-4 text-sm">
                        <div class="font-medium text-slate-800">{{ document.type }}</div>
                        <div class="mt-1 text-slate-600">{{ document.file_name }}</div>
                        <div class="text-xs text-slate-500">{{ document.document_date || document.created_at }} · {{ document.size_label }}</div>
                        <div class="mt-2 flex gap-3">
                            <a :href="document.preview_url" target="_blank" class="text-primary hover:underline">Preview</a>
                            <a :href="document.download_url" class="text-primary hover:underline">Download</a>
                        </div>
                    </div>
                    <div v-if="!documents.length" class="px-5 py-8 text-center text-sm text-slate-500">No HOS documents available.</div>
                </div>
            </div>
        </div>
    </div>

    <Dialog :open="editOpen" @close="editOpen = false" size="xl">
        <Dialog.Panel class="w-full max-w-[780px] overflow-hidden">
            <div class="border-b border-slate-200 px-6 py-5">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-800">Edit HOS Entry</h3>
                        <p class="mt-1 text-sm text-slate-500">Adjust manual corrections without leaving the driver log.</p>
                    </div>
                    <button class="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-600" @click="editOpen = false">
                        <Lucide icon="X" class="h-5 w-5" />
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 bg-slate-50/50 px-6 py-6 md:grid-cols-2">
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">Status</label>
                    <TomSelect v-model="editForm.status">
                        <option v-for="option in statusOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                    </TomSelect>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">Start Time</label>
                    <FormInput v-model="editForm.start_time" type="datetime-local" />
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">End Time</label>
                    <FormInput v-model="editForm.end_time" type="datetime-local" />
                </div>
                <div class="md:col-span-2">
                    <label class="mb-2 block text-sm font-medium text-slate-700">Location</label>
                    <FormInput v-model="editForm.formatted_address" type="text" placeholder="Formatted address or note about location..." />
                </div>
                <div class="md:col-span-2">
                    <label class="mb-2 block text-sm font-medium text-slate-700">Manual Entry Reason</label>
                    <FormInput v-model="editForm.manual_entry_reason" type="text" placeholder="Why is this entry being changed?" />
                </div>
            </div>

            <div class="border-t border-slate-200 px-6 py-4">
                <div class="flex justify-end gap-3">
                    <Button variant="outline-secondary" @click="editOpen = false">Cancel</Button>
                    <Button variant="primary" @click="saveEntry">Save Changes</Button>
                </div>
            </div>
        </Dialog.Panel>
    </Dialog>
</template>
