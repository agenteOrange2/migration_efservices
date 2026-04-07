<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3'
import { reactive, ref } from 'vue'
import Button from '@/components/Base/Button'
import { FormInput } from '@/components/Base/Form'
import Litepicker from '@/components/Base/Litepicker/Litepicker.vue'
import Lucide from '@/components/Base/Lucide'
import TomSelect from '@/components/Base/TomSelect/TomSelect.vue'
import RazeLayout from '@/layouts/RazeLayout.vue'

declare function route(name: string, params?: any): string

defineOptions({ layout: RazeLayout })

const props = defineProps<{
    filters: { type: string; carrier_id: string; driver_id: string; start_date: string; end_date: string }
    stats: { total: number; trip_reports: number; inspection_reports: number; daily_logs: number; monthly_summaries: number; fmcsa_monthly: number }
    documents: any[]
    carriers: { id: number; name: string }[]
    drivers: { id: number; name: string }[]
    canFilterCarriers: boolean
}>()

const filters = reactive({ ...props.filters })
const dailyForm = reactive({ driver_id: props.filters.driver_id || '', date: filters.start_date || '' })
const monthlyForm = reactive({ driver_id: props.filters.driver_id || '', year: String(new Date().getFullYear()), month: String(new Date().getMonth() + 1) })
const fmcsaForm = reactive({ driver_id: props.filters.driver_id || '', year: String(new Date().getFullYear()), month: String(new Date().getMonth() + 1) })
const selectedDocuments = ref<number[]>([])

const pickerOptions = {
    autoApply: true,
    singleMode: true,
    numberOfColumns: 1,
    numberOfMonths: 1,
    format: 'M/D/YYYY',
}

function applyFilters() {
    router.get(route('admin.hos.documents.index'), {
        type: filters.type || undefined,
        carrier_id: filters.carrier_id || undefined,
        driver_id: filters.driver_id || undefined,
        start_date: filters.start_date || undefined,
        end_date: filters.end_date || undefined,
    }, { preserveState: true, preserveScroll: true, replace: true })
}

function resetFilters() {
    filters.type = 'all'
    filters.carrier_id = ''
    filters.driver_id = ''
    filters.start_date = ''
    filters.end_date = ''
    applyFilters()
}

function generateDailyLog() {
    router.post(route('admin.hos.documents.generate-daily-log'), dailyForm, { preserveScroll: true })
}

function generateMonthlySummary() {
    router.post(route('admin.hos.documents.generate-monthly-summary'), monthlyForm, { preserveScroll: true })
}

function generateFmcsaMonthly() {
    router.post(route('admin.hos.documents.generate-fmcsa-monthly'), fmcsaForm, { preserveScroll: true })
}

function destroyDocument(id: number) {
    if (!confirm('Delete this HOS document?')) return
    router.delete(route('admin.hos.documents.destroy', id), { preserveScroll: true })
}

function bulkDelete() {
    if (!selectedDocuments.value.length || !confirm(`Delete ${selectedDocuments.value.length} selected documents?`)) return
    router.post(route('admin.hos.documents.bulk-destroy'), { document_ids: selectedDocuments.value }, {
        preserveScroll: true,
        onSuccess: () => {
            selectedDocuments.value = []
        },
    })
}

function bulkDownload() {
    if (!selectedDocuments.value.length) return
    window.location.href = route('admin.hos.documents.bulk-download', { ids: selectedDocuments.value.join(',') })
}
</script>

<template>
    <Head title="HOS Documents" />

    <div class="grid grid-cols-12 gap-6">
        <div class="col-span-12">
            <div class="box box--stacked p-6">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex items-center gap-4">
                        <div class="rounded-2xl border border-primary/20 bg-primary/10 p-3">
                            <Lucide icon="FileText" class="h-8 w-8 text-primary" />
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-slate-800">HOS Documents</h1>
                            <p class="mt-1 text-sm text-slate-500">Generate and manage daily logs, monthly summaries, FMCSA monthlies, and trip reports.</p>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <Button v-if="selectedDocuments.length" variant="outline-secondary" class="gap-2" @click="bulkDownload">
                            <Lucide icon="Download" class="h-4 w-4" />
                            Download Selected
                        </Button>
                        <Button v-if="selectedDocuments.length" variant="outline-secondary" class="gap-2" @click="bulkDelete">
                            <Lucide icon="Trash2" class="h-4 w-4" />
                            Delete Selected
                        </Button>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-6">
            <div class="box box--stacked p-5"><div class="text-sm text-slate-500">Total</div><div class="mt-2 text-3xl font-semibold text-slate-800">{{ stats.total }}</div></div>
            <div class="box box--stacked p-5"><div class="text-sm text-slate-500">Trip Reports</div><div class="mt-2 text-3xl font-semibold text-slate-800">{{ stats.trip_reports }}</div></div>
            <div class="box box--stacked p-5"><div class="text-sm text-slate-500">Inspection Reports</div><div class="mt-2 text-3xl font-semibold text-slate-800">{{ stats.inspection_reports }}</div></div>
            <div class="box box--stacked p-5"><div class="text-sm text-slate-500">Daily Logs</div><div class="mt-2 text-3xl font-semibold text-slate-800">{{ stats.daily_logs }}</div></div>
            <div class="box box--stacked p-5"><div class="text-sm text-slate-500">Monthly Summaries</div><div class="mt-2 text-3xl font-semibold text-slate-800">{{ stats.monthly_summaries }}</div></div>
            <div class="box box--stacked p-5"><div class="text-sm text-slate-500">FMCSA Monthly</div><div class="mt-2 text-3xl font-semibold text-primary">{{ stats.fmcsa_monthly }}</div></div>
        </div>

        <div class="col-span-12">
            <div class="box box--stacked p-5">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <TomSelect v-if="canFilterCarriers" v-model="filters.carrier_id">
                        <option value="">All Carriers</option>
                        <option v-for="carrier in carriers" :key="carrier.id" :value="String(carrier.id)">{{ carrier.name }}</option>
                    </TomSelect>
                    <TomSelect v-model="filters.driver_id">
                        <option value="">All Drivers</option>
                        <option v-for="driver in drivers" :key="driver.id" :value="String(driver.id)">{{ driver.name }}</option>
                    </TomSelect>
                    <TomSelect v-model="filters.type">
                        <option value="all">All Documents</option>
                        <option value="trip_reports">Trip Reports</option>
                        <option value="inspection_reports">Inspection Reports</option>
                        <option value="daily_logs">Daily Logs</option>
                        <option value="monthly_summaries">Monthly Summaries</option>
                        <option value="fmcsa_monthly">FMCSA Monthly</option>
                    </TomSelect>
                    <Litepicker v-model="filters.start_date" :options="pickerOptions" />
                    <Litepicker v-model="filters.end_date" :options="pickerOptions" />
                    <div class="flex gap-3 xl:col-span-2">
                        <Button variant="primary" class="w-full" @click="applyFilters">Apply</Button>
                        <Button variant="outline-secondary" class="w-full" @click="resetFilters">Reset</Button>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12 grid grid-cols-1 gap-6 xl:grid-cols-3">
            <div class="box box--stacked p-5">
                <h2 class="text-lg font-semibold text-slate-800">Generate Daily Log</h2>
                <div class="mt-4 space-y-4">
                    <TomSelect v-model="dailyForm.driver_id">
                        <option value="">Select Driver</option>
                        <option v-for="driver in drivers" :key="driver.id" :value="String(driver.id)">{{ driver.name }}</option>
                    </TomSelect>
                    <Litepicker v-model="dailyForm.date" :options="pickerOptions" />
                    <Button variant="primary" class="w-full" @click="generateDailyLog">Generate</Button>
                </div>
            </div>

            <div class="box box--stacked p-5">
                <h2 class="text-lg font-semibold text-slate-800">Generate Monthly Summary</h2>
                <div class="mt-4 space-y-4">
                    <TomSelect v-model="monthlyForm.driver_id">
                        <option value="">Select Driver</option>
                        <option v-for="driver in drivers" :key="driver.id" :value="String(driver.id)">{{ driver.name }}</option>
                    </TomSelect>
                    <div class="grid grid-cols-2 gap-4">
                        <FormInput v-model="monthlyForm.month" type="number" min="1" max="12" placeholder="Month" />
                        <FormInput v-model="monthlyForm.year" type="number" min="2020" :max="String(new Date().getFullYear() + 1)" placeholder="Year" />
                    </div>
                    <Button variant="primary" class="w-full" @click="generateMonthlySummary">Generate</Button>
                </div>
            </div>

            <div class="box box--stacked p-5">
                <h2 class="text-lg font-semibold text-slate-800">Generate FMCSA Monthly</h2>
                <div class="mt-4 space-y-4">
                    <TomSelect v-model="fmcsaForm.driver_id">
                        <option value="">Select Driver</option>
                        <option v-for="driver in drivers" :key="driver.id" :value="String(driver.id)">{{ driver.name }}</option>
                    </TomSelect>
                    <div class="grid grid-cols-2 gap-4">
                        <FormInput v-model="fmcsaForm.month" type="number" min="1" max="12" placeholder="Month" />
                        <FormInput v-model="fmcsaForm.year" type="number" min="2020" :max="String(new Date().getFullYear() + 1)" placeholder="Year" />
                    </div>
                    <Button variant="primary" class="w-full" @click="generateFmcsaMonthly">Generate</Button>
                </div>
            </div>
        </div>

        <div class="col-span-12">
            <div class="box box--stacked overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-5 py-3 text-left font-semibold text-slate-600"></th>
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Driver</th>
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Carrier</th>
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Type</th>
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">File</th>
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Document Date</th>
                                <th class="px-5 py-3 text-right font-semibold text-slate-600">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            <tr v-for="document in documents" :key="document.id">
                                <td class="px-5 py-4"><input v-model="selectedDocuments" :value="document.id" type="checkbox" class="form-check-input" /></td>
                                <td class="px-5 py-4 text-slate-700">{{ document.driver_name || 'N/A' }}</td>
                                <td class="px-5 py-4 text-slate-600">{{ document.carrier_name || 'N/A' }}</td>
                                <td class="px-5 py-4 text-slate-600">{{ document.type_label }}</td>
                                <td class="px-5 py-4">
                                    <div class="font-medium text-slate-800">{{ document.file_name }}</div>
                                    <div class="text-xs text-slate-500">{{ document.size_label }} · {{ document.created_at }}</div>
                                </td>
                                <td class="px-5 py-4 text-slate-600">{{ document.document_date || 'N/A' }}</td>
                                <td class="px-5 py-4 text-right">
                                    <div class="flex justify-end gap-3">
                                        <a :href="document.preview_url" target="_blank" class="text-primary hover:underline">Preview</a>
                                        <a :href="document.download_url" class="text-primary hover:underline">Download</a>
                                        <button class="text-primary hover:underline" @click="destroyDocument(document.id)">Delete</button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!documents.length">
                                <td colspan="7" class="px-5 py-10 text-center text-slate-500">No HOS documents matched the current filters.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>
