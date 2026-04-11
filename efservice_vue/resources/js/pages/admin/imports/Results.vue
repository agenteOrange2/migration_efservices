<script setup lang="ts">
import { computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import RazeLayout from '@/layouts/RazeLayout.vue'

declare function route(name: string, params?: any): string

defineOptions({ layout: RazeLayout })

interface SkippedRow {
    row: number | string
    reason: string
    data?: Record<string, unknown>
}

interface FailureRow {
    row?: number | string
    attribute?: string
    errors?: string[] | string
}

interface ResultPayload {
    success: boolean
    imported_count: number
    updated_count?: number
    skipped_count: number
    failed_count: number
    skipped_rows: SkippedRow[]
    failures: FailureRow[]
}

const props = defineProps<{
    result: ResultPayload
    type: string
    typeName: string
    carrierId: number | null
    carrierName: string | null
}>()

const totalProcessed = computed(() =>
    props.result.imported_count
    + (props.result.updated_count ?? 0)
    + props.result.skipped_count
    + props.result.failed_count,
)

const hadSuccessfulWrites = computed(() => props.result.imported_count + (props.result.updated_count ?? 0) > 0)
const truncatedSkippedRows = computed(() => props.result.skipped_rows.slice(0, 50))
const truncatedFailures = computed(() => props.result.failures.slice(0, 50))

function failureMessage(failure: FailureRow) {
    if (Array.isArray(failure.errors)) {
        return failure.errors.join(', ')
    }

    return failure.errors || 'Unknown error'
}

function skippedPreview(data?: Record<string, unknown>) {
    if (!data) return '-'

    return JSON.stringify(Object.fromEntries(Object.entries(data).slice(0, 5)))
}
</script>

<template>
    <Head :title="`Import Results - ${typeName}`" />

    <div class="grid grid-cols-12 gap-6">
        <div class="col-span-12">
            <div
                class="box box--stacked p-6"
                :class="hadSuccessfulWrites ? 'border border-primary/20 bg-primary/5' : 'border border-slate-200 bg-slate-50'"
            >
                <div class="flex items-start gap-4">
                    <div class="rounded-2xl p-3" :class="hadSuccessfulWrites ? 'bg-primary/10' : 'bg-slate-200/70'">
                        <Lucide :icon="hadSuccessfulWrites ? 'CheckCircle' : 'AlertTriangle'" class="h-8 w-8" :class="hadSuccessfulWrites ? 'text-primary' : 'text-slate-700'" />
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-slate-800">
                            {{ hadSuccessfulWrites ? 'Import Completed' : 'Import Finished With Issues' }}
                        </h1>
                        <p class="mt-1 text-sm text-slate-600">
                            {{ typeName }}
                            <span v-if="carrierName">for {{ carrierName }}</span>
                            processed {{ totalProcessed }} row(s).
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-5">
                <div class="box box--stacked rounded-2xl p-5">
                    <p class="text-sm text-slate-500">Created</p>
                    <p class="mt-2 text-2xl font-semibold text-primary">{{ result.imported_count }}</p>
                </div>
                <div class="box box--stacked rounded-2xl p-5">
                    <p class="text-sm text-slate-500">Updated</p>
                    <p class="mt-2 text-2xl font-semibold text-slate-800">{{ result.updated_count ?? 0 }}</p>
                </div>
                <div class="box box--stacked rounded-2xl p-5">
                    <p class="text-sm text-slate-500">Skipped</p>
                    <p class="mt-2 text-2xl font-semibold text-slate-800">{{ result.skipped_count }}</p>
                </div>
                <div class="box box--stacked rounded-2xl p-5">
                    <p class="text-sm text-slate-500">Failed</p>
                    <p class="mt-2 text-2xl font-semibold text-red-600">{{ result.failed_count }}</p>
                </div>
                <div class="box box--stacked rounded-2xl p-5">
                    <p class="text-sm text-slate-500">Total</p>
                    <p class="mt-2 text-2xl font-semibold text-slate-800">{{ totalProcessed }}</p>
                </div>
            </div>
        </div>

        <div v-if="truncatedSkippedRows.length > 0" class="col-span-12">
            <div class="box box--stacked overflow-hidden">
                <div class="border-b border-slate-200/70 px-5 py-4">
                    <div class="flex items-center gap-3">
                        <Lucide icon="SkipForward" class="h-5 w-5 text-primary" />
                        <div>
                            <h2 class="text-base font-semibold text-slate-800">Skipped Rows</h2>
                            <p class="text-sm text-slate-500">{{ result.skipped_count }} row(s) were skipped during import.</p>
                        </div>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead>
                            <tr class="bg-slate-50/90">
                                <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-slate-500">Row</th>
                                <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-slate-500">Reason</th>
                                <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-slate-500">Data Preview</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="row in truncatedSkippedRows" :key="`skipped-${row.row}`" class="border-t border-slate-100">
                                <td class="px-4 py-3 font-mono text-xs text-slate-500">{{ row.row }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ row.reason }}</td>
                                <td class="max-w-xl px-4 py-3 font-mono text-xs text-slate-500">{{ skippedPreview(row.data) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-if="result.skipped_count > truncatedSkippedRows.length" class="border-t border-slate-200/70 px-5 py-3 text-center text-sm text-slate-500">
                    Showing first {{ truncatedSkippedRows.length }} skipped rows.
                </div>
            </div>
        </div>

        <div v-if="truncatedFailures.length > 0" class="col-span-12">
            <div class="box box--stacked overflow-hidden">
                <div class="border-b border-slate-200/70 px-5 py-4">
                    <div class="flex items-center gap-3">
                        <Lucide icon="XCircle" class="h-5 w-5 text-red-500" />
                        <div>
                            <h2 class="text-base font-semibold text-slate-800">Validation Failures</h2>
                            <p class="text-sm text-slate-500">{{ result.failed_count }} row(s) failed validation or import rules.</p>
                        </div>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead>
                            <tr class="bg-slate-50/90">
                                <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-slate-500">Row</th>
                                <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-slate-500">Field</th>
                                <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-slate-500">Error</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="failure in truncatedFailures" :key="`failure-${failure.row}-${failure.attribute}`" class="border-t border-slate-100">
                                <td class="px-4 py-3 font-mono text-xs text-slate-500">{{ failure.row ?? '-' }}</td>
                                <td class="px-4 py-3">
                                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">
                                        {{ failure.attribute ?? 'General' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-red-700">{{ failureMessage(failure) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-if="result.failed_count > truncatedFailures.length" class="border-t border-slate-200/70 px-5 py-3 text-center text-sm text-slate-500">
                    Showing first {{ truncatedFailures.length }} failures.
                </div>
            </div>
        </div>

        <div class="col-span-12">
            <div class="box box--stacked p-6">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="text-sm text-slate-600">
                        <p>Import Type: <span class="font-semibold text-slate-800">{{ typeName }}</span></p>
                        <p v-if="carrierName">Carrier: <span class="font-semibold text-slate-800">{{ carrierName }}</span></p>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <Link :href="route('admin.imports.index')">
                            <Button variant="primary" class="inline-flex items-center gap-2">
                                <Lucide icon="Upload" class="h-4 w-4" />
                                Import More Data
                            </Button>
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
