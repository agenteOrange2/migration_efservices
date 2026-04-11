<script setup lang="ts">
import { computed, watch } from 'vue'
import { Head, useForm, usePage } from '@inertiajs/vue3'
import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import { FormHelp, FormLabel, FormSelect } from '@/components/Base/Form'
import RazeLayout from '@/layouts/RazeLayout.vue'

declare function route(name: string, params?: any): string

defineOptions({ layout: RazeLayout })

interface ImportType {
    key: string
    name: string
    description: string
    template: string
    icon: string
}

interface CarrierOption {
    id: number
    name: string
    dot_number: string | null
}

const props = defineProps<{
    importTypes: ImportType[]
    carriers: CarrierOption[]
}>()

const page = usePage<any>()
const flash = computed(() => page.props.flash ?? {})

const form = useForm({
    import_type: '',
    carrier_id: '',
    csv_file: null as File | null,
})

const selectedType = computed(() => props.importTypes.find((item) => item.key === form.import_type) ?? null)
const requiresCarrier = computed(() => form.import_type !== '' && form.import_type !== 'carriers')
const fileName = computed(() => form.csv_file?.name ?? '')

watch(() => form.import_type, (value) => {
    if (value === 'carriers') {
        form.carrier_id = ''
    }
})

function setFile(event: Event) {
    const target = event.target as HTMLInputElement
    form.csv_file = target.files?.[0] ?? null
}

function submit() {
    form.post(route('admin.imports.preview'), {
        forceFormData: true,
        preserveScroll: true,
    })
}
</script>

<template>
    <Head title="Bulk Import" />

    <div class="grid grid-cols-12 gap-6">
        <div class="col-span-12">
            <div class="box box--stacked p-6">
                <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex items-start gap-4">
                        <div class="rounded-2xl border border-primary/20 bg-primary/10 p-3">
                            <Lucide icon="Upload" class="h-8 w-8 text-primary" />
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-slate-800">Bulk Import</h1>
                            <p class="mt-1 max-w-3xl text-sm text-slate-500">
                                Import carriers, drivers, vehicles, HOS, maintenance, repairs, and supporting driver history from CSV templates.
                            </p>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                        <div class="rounded-2xl border border-dashed border-slate-300/80 px-4 py-3">
                            <p class="text-xs uppercase tracking-wide text-slate-400">Templates</p>
                            <p class="mt-1 text-xl font-semibold text-slate-800">{{ importTypes.length }}</p>
                        </div>
                        <div class="rounded-2xl border border-dashed border-slate-300/80 px-4 py-3">
                            <p class="text-xs uppercase tracking-wide text-slate-400">Carriers</p>
                            <p class="mt-1 text-xl font-semibold text-slate-800">{{ carriers.length }}</p>
                        </div>
                        <div class="rounded-2xl border border-dashed border-slate-300/80 px-4 py-3">
                            <p class="text-xs uppercase tracking-wide text-slate-400">Flow</p>
                            <p class="mt-1 text-sm font-semibold text-slate-800">Preview then import</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="flash.success" class="col-span-12">
            <div class="rounded-2xl border border-primary/20 bg-primary/5 px-4 py-3 text-sm text-primary">
                <div class="flex items-center gap-2">
                    <Lucide icon="CheckCircle" class="h-4 w-4" />
                    <span>{{ flash.success }}</span>
                </div>
            </div>
        </div>

        <div v-if="flash.error" class="col-span-12">
            <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <div class="flex items-center gap-2">
                    <Lucide icon="AlertCircle" class="h-4 w-4" />
                    <span>{{ flash.error }}</span>
                </div>
            </div>
        </div>

        <div class="col-span-12 xl:col-span-8">
            <form @submit.prevent="submit" class="space-y-6">
                <div class="box box--stacked p-6">
                    <div class="mb-6 flex items-center gap-3 border-b border-slate-200/70 pb-4">
                        <Lucide icon="FileUp" class="h-5 w-5 text-primary" />
                        <div>
                            <h2 class="text-lg font-semibold text-slate-800">Upload File</h2>
                            <p class="text-sm text-slate-500">Choose the import type, target carrier when needed, and the CSV or Excel file to preview.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
                        <div class="lg:col-span-2">
                            <FormLabel>Import Type *</FormLabel>
                            <FormSelect v-model="form.import_type">
                                <option value="">Select an import type</option>
                                <option v-for="type in importTypes" :key="type.key" :value="type.key">
                                    {{ type.name }}
                                </option>
                            </FormSelect>
                            <div v-if="form.errors.import_type" class="mt-1 text-xs text-red-500">{{ form.errors.import_type }}</div>
                        </div>

                        <div v-if="requiresCarrier" class="lg:col-span-2">
                            <FormLabel>Select Carrier *</FormLabel>
                            <FormSelect v-model="form.carrier_id">
                                <option value="">Select a carrier</option>
                                <option v-for="carrier in carriers" :key="carrier.id" :value="String(carrier.id)">
                                    {{ carrier.name }}{{ carrier.dot_number ? ` (${carrier.dot_number})` : '' }}
                                </option>
                            </FormSelect>
                            <FormHelp>Use the carrier that owns the records you are importing.</FormHelp>
                            <div v-if="form.errors.carrier_id" class="mt-1 text-xs text-red-500">{{ form.errors.carrier_id }}</div>
                        </div>

                        <div class="lg:col-span-2">
                            <FormLabel>CSV / Excel File *</FormLabel>
                            <label class="block cursor-pointer rounded-2xl border-2 border-dashed border-slate-300 bg-slate-50/70 p-8 transition hover:border-primary/40 hover:bg-primary/5">
                                <input
                                    type="file"
                                    accept=".csv,.xlsx,.xls"
                                    class="hidden"
                                    @change="setFile"
                                >
                                <div class="flex flex-col items-center justify-center text-center">
                                    <div class="mb-4 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
                                        <Lucide icon="UploadCloud" class="h-8 w-8 text-primary" />
                                    </div>
                                    <p class="text-sm font-medium text-slate-700">
                                        Click to choose a file or replace the current one
                                    </p>
                                    <p class="mt-1 text-xs text-slate-500">
                                        Accepted formats: `.csv`, `.xlsx`, `.xls`
                                    </p>
                                    <div
                                        v-if="fileName"
                                        class="mt-4 inline-flex items-center gap-2 rounded-full bg-primary/10 px-4 py-2 text-sm font-medium text-primary"
                                    >
                                        <Lucide icon="FileSpreadsheet" class="h-4 w-4" />
                                        {{ fileName }}
                                    </div>
                                </div>
                            </label>
                            <div v-if="form.errors.csv_file" class="mt-1 text-xs text-red-500">{{ form.errors.csv_file }}</div>
                        </div>
                    </div>
                </div>

                <div v-if="selectedType" class="box box--stacked p-6">
                    <div class="mb-4 flex items-center gap-3">
                        <div class="rounded-xl bg-primary/10 p-3">
                            <Lucide :icon="selectedType.icon" class="h-5 w-5 text-primary" />
                        </div>
                        <div>
                            <h2 class="text-base font-semibold text-slate-800">{{ selectedType.name }}</h2>
                            <p class="text-sm text-slate-500">Template selected for this import run.</p>
                        </div>
                    </div>
                    <p class="text-sm leading-6 text-slate-600">{{ selectedType.description }}</p>
                    <div class="mt-5 flex flex-wrap items-center gap-3">
                        <a
                            :href="route('admin.imports.template', selectedType.key)"
                            class="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-700 transition hover:border-primary/30 hover:bg-primary/5 hover:text-primary"
                        >
                            <Lucide icon="Download" class="h-4 w-4" />
                            Download {{ selectedType.template }}
                        </a>
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-500">
                            Preview step will validate duplicates and required fields.
                        </span>
                    </div>
                </div>

                <div class="flex justify-end gap-3">
                    <Button type="submit" variant="primary" :disabled="form.processing" class="inline-flex items-center gap-2 px-6">
                        <Lucide icon="Eye" class="h-4 w-4" />
                        {{ form.processing ? 'Preparing preview...' : 'Preview Import' }}
                    </Button>
                </div>
            </form>
        </div>

        <div class="col-span-12 xl:col-span-4">
            <div class="space-y-6">
                <div class="box box--stacked p-6">
                    <div class="mb-5 flex items-center gap-3 border-b border-slate-200/70 pb-4">
                        <Lucide icon="Download" class="h-5 w-5 text-primary" />
                        <h2 class="text-lg font-semibold text-slate-800">Templates</h2>
                    </div>
                    <div class="space-y-3">
                        <a
                            v-for="type in importTypes"
                            :key="type.key"
                            :href="route('admin.imports.template', type.key)"
                            class="flex items-start gap-3 rounded-2xl border border-slate-200 p-4 transition hover:border-primary/30 hover:bg-primary/5"
                        >
                            <div class="rounded-xl bg-slate-100 p-2">
                                <Lucide :icon="type.icon" class="h-4 w-4 text-slate-700" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-semibold text-slate-800">{{ type.name }}</p>
                                <p class="mt-1 truncate text-xs text-slate-500">{{ type.template }}</p>
                            </div>
                            <Lucide icon="ChevronRight" class="mt-1 h-4 w-4 text-slate-400" />
                        </a>
                    </div>
                </div>

                <div class="box box--stacked p-6">
                    <div class="mb-5 flex items-center gap-3 border-b border-slate-200/70 pb-4">
                        <Lucide icon="Info" class="h-5 w-5 text-primary" />
                        <h2 class="text-lg font-semibold text-slate-800">Guidelines</h2>
                    </div>
                    <div class="space-y-4 text-sm text-slate-600">
                        <div class="flex items-start gap-3">
                            <Lucide icon="Check" class="mt-0.5 h-4 w-4 text-primary" />
                            <p>Download the correct template first and keep the header row unchanged.</p>
                        </div>
                        <div class="flex items-start gap-3">
                            <Lucide icon="Check" class="mt-0.5 h-4 w-4 text-primary" />
                            <p>Preview catches validation errors and duplicates before anything is written.</p>
                        </div>
                        <div class="flex items-start gap-3">
                            <Lucide icon="Check" class="mt-0.5 h-4 w-4 text-primary" />
                            <p>Use consistent identifiers such as driver email, VIN, or unit number.</p>
                        </div>
                        <div class="flex items-start gap-3">
                            <Lucide icon="Check" class="mt-0.5 h-4 w-4 text-primary" />
                            <p>Date parsing supports common formats, but ISO `YYYY-MM-DD` is still the safest option.</p>
                        </div>
                        <div class="flex items-start gap-3">
                            <Lucide icon="AlertTriangle" class="mt-0.5 h-4 w-4 text-red-500" />
                            <p>If duplicates are found, you can choose to skip them or update existing records in the preview step.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
