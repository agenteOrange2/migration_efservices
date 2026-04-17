<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { reactive } from 'vue'
import Button from '@/components/Base/Button'
import { FormInput } from '@/components/Base/Form'
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
    }
    filters: {
        type: string
        start_date: string
        end_date: string
    }
    stats: {
        total: number
        trip_reports: number
        inspection_reports: number
        daily_logs: number
        monthly_summaries: number
        fmcsa_monthly: number
    }
    documents: {
        id: number
        type_key: string
        type_label: string
        file_name: string
        size_label: string
        document_date: string | null
        created_at: string | null
        preview_url: string
        download_url: string
    }[]
}>()

const filters = reactive({ ...props.filters })
const todayString = new Date().toLocaleDateString('en-US')

const dailyForm = useForm({
    date: props.filters.start_date || todayString,
})

const monthlyForm = useForm({
    month: String(new Date().getMonth() + 1),
    year: String(new Date().getFullYear()),
})

const fmcsaForm = useForm({
    month: String(new Date().getMonth() + 1),
    year: String(new Date().getFullYear()),
})

const pickerOptions = {
    autoApply: true,
    singleMode: true,
    numberOfColumns: 1,
    numberOfMonths: 1,
    format: 'M/D/YYYY',
}

function applyFilters() {
    router.get(route('driver.hos.documents.index'), {
        type: filters.type || 'all',
        start_date: filters.start_date || undefined,
        end_date: filters.end_date || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    })
}

function resetFilters() {
    filters.type = 'all'
    filters.start_date = ''
    filters.end_date = ''
    applyFilters()
}

function generateDailyLog() {
    dailyForm.post(route('driver.hos.documents.generate-daily-log'), { preserveScroll: true })
}

function generateMonthlySummary() {
    monthlyForm.post(route('driver.hos.documents.generate-monthly-summary'), { preserveScroll: true })
}

function generateFmcsaMonthly() {
    fmcsaForm.post(route('driver.hos.documents.generate-fmcsa-monthly'), { preserveScroll: true })
}
</script>

<template>
    <Head title="HOS Documents" />

    <div class="grid grid-cols-12 gap-6">
        <div class="col-span-12">
            <div class="box box--stacked p-6">
                <div class="flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between">
                    <div class="flex items-start gap-4">
                        <div class="rounded-2xl border border-primary/20 bg-primary/10 p-3">
                            <Lucide icon="FileText" class="h-8 w-8 text-primary" />
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-slate-800">HOS Documents</h1>
                            <p class="mt-1 text-slate-500">Generate and review daily logs, monthly summaries, FMCSA monthlies, and trip related HOS PDFs.</p>
                            <p class="mt-2 text-sm text-slate-500">
                                Driver: <span class="font-medium text-slate-700">{{ driver.full_name }}</span>
                                <span v-if="driver.carrier_name"> · Carrier: <span class="font-medium text-slate-700">{{ driver.carrier_name }}</span></span>
                            </p>
                        </div>
                    </div>

                    <Link :href="route('driver.hos.dashboard')">
                        <Button variant="outline-secondary" class="gap-2">
                            <Lucide icon="ArrowLeft" class="h-4 w-4" />
                            Back to HOS
                        </Button>
                    </Link>
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
                    <div class="flex gap-3">
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
                    <Litepicker v-model="dailyForm.date" :options="pickerOptions" />
                    <Button variant="primary" class="w-full gap-2" :disabled="dailyForm.processing" @click="generateDailyLog">
                        <Lucide icon="Calendar" class="h-4 w-4" />
                        {{ dailyForm.processing ? 'Generating...' : 'Generate Daily Log' }}
                    </Button>
                </div>
            </div>

            <div class="box box--stacked p-5">
                <h2 class="text-lg font-semibold text-slate-800">Generate Monthly Summary</h2>
                <div class="mt-4 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <FormInput v-model="monthlyForm.month" type="number" min="1" max="12" placeholder="Month" />
                        <FormInput v-model="monthlyForm.year" type="number" min="2020" :max="String(new Date().getFullYear() + 1)" placeholder="Year" />
                    </div>
                    <Button variant="primary" class="w-full gap-2" :disabled="monthlyForm.processing" @click="generateMonthlySummary">
                        <Lucide icon="BarChart3" class="h-4 w-4" />
                        {{ monthlyForm.processing ? 'Generating...' : 'Generate Monthly Summary' }}
                    </Button>
                </div>
            </div>

            <div class="box box--stacked p-5">
                <h2 class="text-lg font-semibold text-slate-800">Generate FMCSA Monthly</h2>
                <div class="mt-4 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <FormInput v-model="fmcsaForm.month" type="number" min="1" max="12" placeholder="Month" />
                        <FormInput v-model="fmcsaForm.year" type="number" min="2020" :max="String(new Date().getFullYear() + 1)" placeholder="Year" />
                    </div>
                    <Button variant="primary" class="w-full gap-2" :disabled="fmcsaForm.processing" @click="generateFmcsaMonthly">
                        <Lucide icon="FileSpreadsheet" class="h-4 w-4" />
                        {{ fmcsaForm.processing ? 'Generating...' : 'Generate FMCSA Monthly' }}
                    </Button>
                </div>
            </div>
        </div>

        <div class="col-span-12">
            <div class="box box--stacked overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Type</th>
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">File</th>
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Document Date</th>
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Created</th>
                                <th class="px-5 py-3 text-right font-semibold text-slate-600">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            <tr v-for="document in documents" :key="document.id">
                                <td class="px-5 py-4 text-slate-700">{{ document.type_label }}</td>
                                <td class="px-5 py-4">
                                    <div class="font-medium text-slate-800">{{ document.file_name }}</div>
                                    <div class="text-xs text-slate-500">{{ document.size_label }}</div>
                                </td>
                                <td class="px-5 py-4 text-slate-600">{{ document.document_date || 'N/A' }}</td>
                                <td class="px-5 py-4 text-slate-600">{{ document.created_at || 'N/A' }}</td>
                                <td class="px-5 py-4 text-right">
                                    <div class="flex justify-end gap-3">
                                        <a :href="document.preview_url" target="_blank" class="text-primary hover:underline">Preview</a>
                                        <a :href="document.download_url" class="text-primary hover:underline">Download</a>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!documents.length"><td colspan="5" class="px-5 py-10 text-center text-slate-500">No HOS documents matched the current filters.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>
