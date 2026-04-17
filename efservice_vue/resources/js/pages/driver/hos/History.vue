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
    driver: {
        id: number
        full_name: string
        carrier_name: string | null
        email: string | null
        current_cycle: string
    }
    date: string
    displayDate: string
    previousDate: string
    nextDate: string
    totals: {
        driving_formatted: string
        on_duty_formatted: string
        off_duty_formatted: string
        total_formatted: string
    }
    entries: any[]
    violations: {
        id: number
        type: string
        severity: string
        hours_exceeded: string
        acknowledged: boolean
    }[]
    statusOptions: { value: string; label: string }[]
}>()

const dateFilter = ref(props.date)
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

function applyDate() {
    router.get(route('driver.hos.history'), { date: dateFilter.value }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    })
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
    router.put(route('driver.hos.entries.update', editForm.id), {
        status: editForm.status,
        start_time: editForm.start_time,
        end_time: editForm.end_time || null,
        formatted_address: editForm.formatted_address || null,
        manual_entry_reason: editForm.manual_entry_reason || null,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            editOpen.value = false
        },
    })
}

function destroyEntry(id: number) {
    if (!confirm('Delete this HOS entry?')) return
    router.delete(route('driver.hos.entries.destroy', id), { preserveScroll: true })
}

function bulkDelete() {
    if (!selectedEntryIds.value.length || !confirm(`Delete ${selectedEntryIds.value.length} selected entries?`)) return

    router.post(route('driver.hos.entries.bulk-destroy'), {
        entry_ids: selectedEntryIds.value,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            selectedEntryIds.value = []
        },
    })
}
</script>

<template>
    <Head title="HOS History" />

    <div class="grid grid-cols-12 gap-6">
        <div class="col-span-12">
            <div class="box box--stacked p-6">
                <div class="flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between">
                    <div class="flex items-start gap-4">
                        <div class="rounded-2xl border border-primary/20 bg-primary/10 p-3">
                            <Lucide icon="History" class="h-8 w-8 text-primary" />
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-slate-800">HOS History</h1>
                            <p class="mt-1 text-slate-500">Review, correct, and clean up your daily HOS entries.</p>
                            <p class="mt-2 text-sm text-slate-500">
                                Driver: <span class="font-medium text-slate-700">{{ driver.full_name }}</span>
                                <span v-if="driver.carrier_name"> · Carrier: <span class="font-medium text-slate-700">{{ driver.carrier_name }}</span></span>
                            </p>
                        </div>
                    </div>

                    <Link :href="route('driver.hos.dashboard')">
                        <Button variant="outline-secondary" class="gap-2">
                            <Lucide icon="ArrowLeft" class="h-4 w-4" />
                            Back to Dashboard
                        </Button>
                    </Link>
                </div>
            </div>
        </div>

        <div class="col-span-12">
            <div class="box box--stacked p-5">
                <div class="grid grid-cols-1 gap-4 lg:grid-cols-[220px,auto,auto,1fr] lg:items-end">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">Date</label>
                        <Litepicker v-model="dateFilter" :options="pickerOptions" />
                    </div>
                    <Link :href="route('driver.hos.history', { date: previousDate })"><Button variant="outline-secondary" class="w-full gap-2"><Lucide icon="ChevronLeft" class="h-4 w-4" />Previous Day</Button></Link>
                    <Link :href="route('driver.hos.history', { date: nextDate })"><Button variant="outline-secondary" class="w-full gap-2">Next Day<Lucide icon="ChevronRight" class="h-4 w-4" /></Button></Link>
                    <div class="flex justify-end">
                        <Button variant="primary" class="gap-2" @click="applyDate"><Lucide icon="Filter" class="h-4 w-4" />Apply Date</Button>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12 grid grid-cols-1 gap-4 md:grid-cols-4">
            <div class="box box--stacked p-5"><div class="text-sm text-slate-500">Driving</div><div class="mt-2 text-3xl font-semibold text-slate-800">{{ totals.driving_formatted }}</div></div>
            <div class="box box--stacked p-5"><div class="text-sm text-slate-500">On Duty</div><div class="mt-2 text-3xl font-semibold text-slate-800">{{ totals.on_duty_formatted }}</div></div>
            <div class="box box--stacked p-5"><div class="text-sm text-slate-500">Off Duty</div><div class="mt-2 text-3xl font-semibold text-slate-800">{{ totals.off_duty_formatted }}</div></div>
            <div class="box box--stacked p-5"><div class="text-sm text-slate-500">Total Logged</div><div class="mt-2 text-3xl font-semibold text-primary">{{ totals.total_formatted }}</div></div>
        </div>

        <div class="col-span-12 xl:col-span-8">
            <div class="box box--stacked overflow-hidden">
                <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-800">{{ displayDate }}</h2>
                        <p class="mt-1 text-sm text-slate-500">{{ entries.length }} entries for this day</p>
                    </div>
                    <Button v-if="selectedEntryIds.length" variant="outline-secondary" class="gap-2" @click="bulkDelete"><Lucide icon="Trash2" class="h-4 w-4" />Delete Selected</Button>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-5 py-3 text-left font-semibold text-slate-600"></th>
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Status</th>
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Time Window</th>
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Location</th>
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Context</th>
                                <th class="px-5 py-3 text-right font-semibold text-slate-600">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            <tr v-for="entry in entries" :key="entry.id">
                                <td class="px-5 py-4"><input v-model="selectedEntryIds" :value="entry.id" type="checkbox" class="form-check-input" /></td>
                                <td class="px-5 py-4">
                                    <div class="font-medium text-slate-800">{{ entry.status_label }}</div>
                                    <div class="mt-1 flex flex-wrap gap-2 text-xs text-slate-500">
                                        <span v-if="entry.is_manual_entry" class="rounded-full bg-slate-100 px-2 py-0.5 font-medium text-slate-600">Manual</span>
                                        <span v-if="entry.is_ghost_log" class="rounded-full bg-primary/10 px-2 py-0.5 font-medium text-primary">Ghost Log</span>
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-slate-600">
                                    <div>{{ entry.start_time || 'N/A' }}</div>
                                    <div class="text-xs text-slate-500">{{ entry.end_time || 'Open entry' }} · {{ entry.duration }}</div>
                                </td>
                                <td class="px-5 py-4 text-slate-600">{{ entry.location || 'N/A' }}</td>
                                <td class="px-5 py-4 text-slate-600">
                                    <div>{{ entry.trip_number ? `Trip ${entry.trip_number}` : 'No trip linked' }}</div>
                                    <div class="text-xs text-slate-500">{{ entry.vehicle_label || 'Vehicle N/A' }}</div>
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <div class="flex justify-end gap-2">
                                        <Button variant="outline-secondary" class="gap-2" @click="openEdit(entry)"><Lucide icon="Pencil" class="h-4 w-4" />Edit</Button>
                                        <Button variant="outline-secondary" class="gap-2" @click="destroyEntry(entry.id)"><Lucide icon="Trash2" class="h-4 w-4" />Delete</Button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!entries.length"><td colspan="6" class="px-5 py-10 text-center text-slate-500">No HOS entries were found for this date.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-span-12 xl:col-span-4 space-y-6">
            <div class="box box--stacked p-6">
                <h2 class="text-lg font-semibold text-slate-800">Violations for This Day</h2>
                <div v-if="violations.length" class="mt-4 space-y-3">
                    <div v-for="violation in violations" :key="violation.id" class="rounded-xl border border-slate-200 px-4 py-3">
                        <p class="text-sm font-medium text-slate-800">{{ violation.type }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ violation.severity }} · {{ violation.hours_exceeded }}</p>
                        <span class="mt-2 inline-flex rounded-full px-2 py-0.5 text-[11px] font-medium" :class="violation.acknowledged ? 'bg-slate-100 text-slate-600' : 'bg-primary/10 text-primary'">{{ violation.acknowledged ? 'Acknowledged' : 'Unacknowledged' }}</span>
                    </div>
                </div>
                <div v-else class="mt-4 rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">No violations were recorded for this day.</div>
            </div>

            <div class="box box--stacked p-6">
                <h2 class="text-lg font-semibold text-slate-800">Need a full log?</h2>
                <p class="mt-3 text-sm text-slate-500">If you need signed PDFs or monthly documents, open the HOS documents area and generate them from there.</p>
                <div class="mt-4">
                    <Link :href="route('driver.hos.documents.index')"><Button variant="primary" class="w-full gap-2"><Lucide icon="FileText" class="h-4 w-4" />Open Documents</Button></Link>
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
                        <p class="mt-1 text-sm text-slate-500">Update the entry values for {{ displayDate }}.</p>
                    </div>
                    <button class="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-600" @click="editOpen = false"><Lucide icon="X" class="h-5 w-5" /></button>
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
                    <FormInput v-model="editForm.formatted_address" type="text" placeholder="Houston, TX or current address..." />
                </div>
                <div class="md:col-span-2">
                    <label class="mb-2 block text-sm font-medium text-slate-700">Reason</label>
                    <FormInput v-model="editForm.manual_entry_reason" type="text" placeholder="Why are you correcting this entry?" />
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
