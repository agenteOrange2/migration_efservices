<script setup lang="ts">
import { computed } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import RazeLayout from '@/layouts/RazeLayout.vue'

declare function route(name: string, params?: any): string

defineOptions({ layout: RazeLayout })

interface PreviewRow {
    row: number
    data: Record<string, string | number | null>
    status: 'valid' | 'duplicate' | 'error'
    message: string
}

interface PreviewPayload {
    headers: string[]
    rows: PreviewRow[]
    total: number
    valid: number
    duplicates: number
    errors: number
    preview_limited?: boolean
}

const props = defineProps<{
    preview: PreviewPayload
    type: string
    typeName: string
    carrierId: number | null
    carrierName: string | null
    tempPath: string
}>()

const form = useForm({
    import_type: props.type,
    carrier_id: props.carrierId ? String(props.carrierId) : '',
    temp_path: props.tempPath,
    duplicate_action: 'skip',
})

const importableCount = computed(() => {
    if (form.duplicate_action === 'update') {
        return props.preview.valid + props.preview.duplicates
    }

    return props.preview.valid
})

const duplicateSummary = computed(() => {
    if (props.preview.duplicates === 0) {
        return ''
    }

    return form.duplicate_action === 'update'
        ? `${props.preview.duplicates} duplicate row(s) will be updated.`
        : `${props.preview.duplicates} duplicate row(s) will be skipped.`
})

function submit() {
    form.post(route('admin.imports.execute'), {
        preserveScroll: true,
    })
}

function formatHeader(header: string) {
    return header.replace(/_/g, ' ').replace(/\b\w/g, (char) => char.toUpperCase())
}

function previewValue(value: string | number | null | undefined) {
    if (value === null || value === undefined || value === '') {
        return '-'
    }

    const stringValue = String(value)
    return stringValue.length > 30 ? `${stringValue.slice(0, 30)}...` : stringValue
}

function rowClass(status: PreviewRow['status']) {
    if (status === 'error') {
        return 'bg-red-50/60'
    }

    if (status === 'duplicate') {
        return 'bg-slate-50'
    }

    return ''
}

function badgeClass(status: PreviewRow['status']) {
    if (status === 'valid') {
        return 'bg-primary/10 text-primary'
    }

    if (status === 'duplicate') {
        return 'bg-slate-100 text-slate-700'
    }

    return 'bg-red-100 text-red-700'
}

function badgeIcon(status: PreviewRow['status']) {
    if (status === 'valid') return 'Check'
    if (status === 'duplicate') return 'Copy'
    return 'X'
}
</script>

<template>
    <Head :title="`Preview Import - ${typeName}`" />

    <div class="grid grid-cols-12 gap-6">
        <div class="col-span-12">
            <div class="box box--stacked p-6">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex items-start gap-4">
                        <div class="rounded-2xl border border-primary/20 bg-primary/10 p-3">
                            <Lucide icon="Eye" class="h-8 w-8 text-primary" />
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-slate-800">Preview Import</h1>
                            <p class="mt-1 text-sm text-slate-500">
                                {{ typeName }}
                                <span v-if="carrierName">for {{ carrierName }}</span>
                            </p>
                        </div>
                    </div>
                    <Link :href="route('admin.imports.index')">
                        <Button variant="outline-secondary" class="inline-flex items-center gap-2">
                            <Lucide icon="ArrowLeft" class="h-4 w-4" />
                            Back to Imports
                        </Button>
                    </Link>
                </div>
            </div>
        </div>

        <div class="col-span-12">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div class="box box--stacked rounded-2xl p-5">
                    <p class="text-sm text-slate-500">Total Rows</p>
                    <p class="mt-2 text-2xl font-semibold text-slate-800">{{ preview.total }}</p>
                </div>
                <div class="box box--stacked rounded-2xl p-5">
                    <p class="text-sm text-slate-500">Valid Rows</p>
                    <p class="mt-2 text-2xl font-semibold text-primary">{{ preview.valid }}</p>
                </div>
                <div class="box box--stacked rounded-2xl p-5">
                    <p class="text-sm text-slate-500">Duplicates</p>
                    <p class="mt-2 text-2xl font-semibold text-slate-800">{{ preview.duplicates }}</p>
                </div>
                <div class="box box--stacked rounded-2xl p-5">
                    <p class="text-sm text-slate-500">Errors</p>
                    <p class="mt-2 text-2xl font-semibold text-red-600">{{ preview.errors }}</p>
                </div>
            </div>
        </div>

        <div v-if="preview.preview_limited" class="col-span-12">
            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                Showing the first 100 rows of {{ preview.total }} total rows. The import job will still process the full file.
            </div>
        </div>

        <div class="col-span-12">
            <div class="box box--stacked overflow-hidden">
                <div class="border-b border-slate-200/70 px-5 py-4">
                    <h2 class="text-base font-semibold text-slate-800">Data Preview</h2>
                    <p class="mt-1 text-sm text-slate-500">Review row status, duplicate messages, and a sample of each field before confirming the import.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead>
                            <tr class="bg-slate-50/90">
                                <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-slate-500">Row</th>
                                <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-slate-500">Status</th>
                                <th
                                    v-for="header in preview.headers"
                                    :key="header"
                                    class="whitespace-nowrap px-4 py-3 text-xs font-medium uppercase tracking-wide text-slate-500"
                                >
                                    {{ formatHeader(header) }}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="row in preview.rows"
                                :key="row.row"
                                :class="[rowClass(row.status), 'border-t border-slate-100 align-top']"
                            >
                                <td class="px-4 py-3 font-mono text-xs text-slate-500">{{ row.row }}</td>
                                <td class="px-4 py-3">
                                    <div class="space-y-2">
                                        <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-medium" :class="badgeClass(row.status)">
                                            <Lucide :icon="badgeIcon(row.status)" class="h-3 w-3" />
                                            {{ row.status }}
                                        </span>
                                        <p v-if="row.status !== 'valid'" class="max-w-xs text-xs leading-5 text-slate-500">
                                            {{ row.message }}
                                        </p>
                                    </div>
                                </td>
                                <td
                                    v-for="header in preview.headers"
                                    :key="`${row.row}-${header}`"
                                    class="max-w-xs whitespace-nowrap px-4 py-3 text-slate-600"
                                    :title="String(row.data[header] ?? '')"
                                >
                                    {{ previewValue(row.data[header]) }}
                                </td>
                            </tr>
                            <tr v-if="preview.rows.length === 0">
                                <td :colspan="preview.headers.length + 2" class="px-4 py-10 text-center text-sm text-slate-500">
                                    No rows were available to preview.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-span-12">
            <form @submit.prevent="submit" class="box box--stacked p-6">
                <div v-if="preview.duplicates > 0" class="mb-6 rounded-2xl border border-slate-200 bg-slate-50 p-5">
                    <div class="mb-4 flex items-center gap-3">
                        <Lucide icon="Copy" class="h-5 w-5 text-primary" />
                        <div>
                            <h2 class="text-base font-semibold text-slate-800">Duplicate Handling</h2>
                            <p class="text-sm text-slate-500">{{ preview.duplicates }} duplicate row(s) were detected in the system.</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                        <label class="cursor-pointer rounded-2xl border border-slate-200 bg-white p-4 transition hover:border-primary/30" :class="form.duplicate_action === 'skip' ? 'border-primary bg-primary/5' : ''">
                            <input v-model="form.duplicate_action" type="radio" value="skip" class="sr-only">
                            <div class="flex items-start gap-3">
                                <div class="rounded-full bg-slate-100 p-2">
                                    <Lucide icon="SkipForward" class="h-4 w-4 text-slate-700" />
                                </div>
                                <div>
                                    <p class="font-semibold text-slate-800">Skip duplicates</p>
                                    <p class="mt-1 text-sm text-slate-500">Only new rows will be created. Existing records stay untouched.</p>
                                </div>
                            </div>
                        </label>

                        <label class="cursor-pointer rounded-2xl border border-slate-200 bg-white p-4 transition hover:border-primary/30" :class="form.duplicate_action === 'update' ? 'border-primary bg-primary/5' : ''">
                            <input v-model="form.duplicate_action" type="radio" value="update" class="sr-only">
                            <div class="flex items-start gap-3">
                                <div class="rounded-full bg-slate-100 p-2">
                                    <Lucide icon="RefreshCcw" class="h-4 w-4 text-slate-700" />
                                </div>
                                <div>
                                    <p class="font-semibold text-slate-800">Update duplicates</p>
                                    <p class="mt-1 text-sm text-slate-500">Existing matches will be updated with the new data from this file.</p>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="space-y-1 text-sm text-slate-600">
                        <p class="flex items-center gap-2">
                            <Lucide icon="CheckCircle" class="h-4 w-4 text-primary" />
                            {{ importableCount }} row(s) will be processed on execution.
                        </p>
                        <p v-if="duplicateSummary" class="flex items-center gap-2">
                            <Lucide icon="Copy" class="h-4 w-4 text-slate-500" />
                            {{ duplicateSummary }}
                        </p>
                        <p v-if="preview.errors > 0" class="flex items-center gap-2">
                            <Lucide icon="AlertCircle" class="h-4 w-4 text-red-500" />
                            {{ preview.errors }} row(s) with validation issues will be skipped.
                        </p>
                    </div>

                    <div class="flex flex-wrap justify-end gap-3">
                        <Link :href="route('admin.imports.index')">
                            <Button type="button" variant="outline-secondary" class="inline-flex items-center gap-2">
                                <Lucide icon="X" class="h-4 w-4" />
                                Cancel
                            </Button>
                        </Link>
                        <Button
                            type="submit"
                            variant="primary"
                            :disabled="form.processing || importableCount === 0"
                            class="inline-flex items-center gap-2"
                        >
                            <Lucide icon="Upload" class="h-4 w-4" />
                            {{ form.processing ? 'Running import...' : `Import ${importableCount} Row(s)` }}
                        </Button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</template>
