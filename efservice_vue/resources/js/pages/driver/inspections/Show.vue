<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import RazeLayout from '@/layouts/RazeLayout.vue'

declare function route(name: string, params?: any): string

defineOptions({ layout: RazeLayout })

interface DocumentItem {
    id: number
    name: string
    url: string
    mime_type: string | null
    size_label: string
    created_at: string | null
    extension: string
}

const props = defineProps<{
    driver: {
        id: number
        full_name: string
        carrier_name: string | null
    }
    inspection: {
        id: number
        inspection_date: string | null
        inspection_type: string | null
        inspection_level: string | null
        status: string | null
        inspector_name: string | null
        inspector_number: string | null
        location: string | null
        defects_found: string | null
        corrective_actions: string | null
        is_defects_corrected: boolean
        defects_corrected_date: string | null
        corrected_by: string | null
        is_vehicle_safe_to_operate: boolean
        notes: string | null
        created_at: string | null
        vehicle: {
            id: number
            label: string
            vin: string | null
        } | null
        documents: DocumentItem[]
    }
    recentInspections: {
        id: number
        inspection_date: string | null
        inspection_type: string
        inspection_level: string | null
        status: string | null
        inspector_name: string | null
        location: string | null
        document_count: number
        has_issues: boolean
        vehicle_label: string | null
    }[]
}>()

function statusClass(status: string | null) {
    if (['Pass', 'Passed', 'Conditional Pass'].includes(status || '')) return 'bg-primary/10 text-primary'
    if (['Fail', 'Failed', 'Out of Service'].includes(status || '')) return 'bg-slate-200 text-slate-700'
    return 'bg-slate-100 text-slate-600'
}

function fileIcon(extension: string) {
    if (extension === 'pdf') return 'FileText'
    if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(extension)) return 'Image'
    return 'Files'
}
</script>

<template>
    <Head :title="`Inspection #${inspection.id}`" />

    <div class="grid grid-cols-12 gap-6">
        <div class="col-span-12">
            <div class="box box--stacked p-6">
                <div class="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
                    <div class="space-y-4">
                        <Link :href="route('driver.inspections.index')" class="inline-flex items-center gap-2 text-sm font-medium text-slate-500 transition hover:text-primary">
                            <Lucide icon="ArrowLeft" class="h-4 w-4" />
                            Back to Inspections
                        </Link>

                        <div class="flex flex-wrap items-center gap-3">
                            <h1 class="text-2xl font-bold text-slate-800">{{ inspection.inspection_type || 'Vehicle Inspection' }}</h1>
                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium" :class="statusClass(inspection.status)">{{ inspection.status || 'Not Set' }}</span>
                            <span v-if="inspection.is_vehicle_safe_to_operate" class="inline-flex rounded-full bg-primary/10 px-2.5 py-1 text-xs font-medium text-primary">Safe to Operate</span>
                            <span v-else class="inline-flex rounded-full bg-slate-200 px-2.5 py-1 text-xs font-medium text-slate-700">Not Safe to Operate</span>
                        </div>

                        <p class="text-sm text-slate-500">
                            Driver: <span class="font-medium text-slate-700">{{ driver.full_name }}</span>
                            <span v-if="driver.carrier_name"> · Carrier: <span class="font-medium text-slate-700">{{ driver.carrier_name }}</span></span>
                        </p>
                    </div>

                    <Link :href="route('driver.inspections.index')">
                        <Button variant="outline-secondary" class="gap-2"><Lucide icon="List" class="h-4 w-4" />All Inspections</Button>
                    </Link>
                </div>
            </div>
        </div>

        <div class="col-span-12 xl:col-span-8 space-y-6">
            <div class="box box--stacked p-6">
                <h2 class="text-base font-semibold text-slate-800">Inspection Details</h2>

                <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-4"><p class="text-xs uppercase tracking-[0.2em] text-slate-400">Inspection Date</p><p class="mt-2 text-sm font-semibold text-slate-800">{{ inspection.inspection_date || 'N/A' }}</p></div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-4"><p class="text-xs uppercase tracking-[0.2em] text-slate-400">Inspection Level</p><p class="mt-2 text-sm font-semibold text-slate-800">{{ inspection.inspection_level || 'N/A' }}</p></div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-4"><p class="text-xs uppercase tracking-[0.2em] text-slate-400">Location</p><p class="mt-2 text-sm font-semibold text-slate-800">{{ inspection.location || 'N/A' }}</p></div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-4"><p class="text-xs uppercase tracking-[0.2em] text-slate-400">Inspector</p><p class="mt-2 text-sm font-semibold text-slate-800">{{ inspection.inspector_name || 'N/A' }}</p></div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-4"><p class="text-xs uppercase tracking-[0.2em] text-slate-400">Inspector Number</p><p class="mt-2 text-sm font-semibold text-slate-800">{{ inspection.inspector_number || 'N/A' }}</p></div>
                    <div v-if="inspection.vehicle" class="rounded-xl border border-slate-200 bg-slate-50/70 p-4"><p class="text-xs uppercase tracking-[0.2em] text-slate-400">Vehicle</p><p class="mt-2 text-sm font-semibold text-slate-800">{{ inspection.vehicle.label }}</p><p v-if="inspection.vehicle.vin" class="mt-1 text-xs text-slate-500">VIN: {{ inspection.vehicle.vin }}</p></div>
                </div>

                <div class="mt-5 grid grid-cols-1 gap-4 lg:grid-cols-2">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5"><p class="text-xs uppercase tracking-[0.2em] text-slate-400">Defects Found</p><p class="mt-3 whitespace-pre-line text-sm leading-6 text-slate-600">{{ inspection.defects_found || 'No defects were reported.' }}</p></div>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5"><p class="text-xs uppercase tracking-[0.2em] text-slate-400">Corrective Actions</p><p class="mt-3 whitespace-pre-line text-sm leading-6 text-slate-600">{{ inspection.corrective_actions || 'No corrective action recorded.' }}</p></div>
                </div>

                <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-4"><p class="text-xs uppercase tracking-[0.2em] text-slate-400">Defects Corrected</p><p class="mt-2 text-sm font-semibold text-slate-800">{{ inspection.is_defects_corrected ? 'Yes' : 'No' }}</p></div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-4"><p class="text-xs uppercase tracking-[0.2em] text-slate-400">Corrected Date</p><p class="mt-2 text-sm font-semibold text-slate-800">{{ inspection.defects_corrected_date || 'N/A' }}</p></div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-4"><p class="text-xs uppercase tracking-[0.2em] text-slate-400">Corrected By</p><p class="mt-2 text-sm font-semibold text-slate-800">{{ inspection.corrected_by || 'N/A' }}</p></div>
                </div>

                <div class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 p-5">
                    <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Notes</p>
                    <p class="mt-3 whitespace-pre-line text-sm leading-6 text-slate-600">{{ inspection.notes || 'No notes were added to this inspection.' }}</p>
                </div>
            </div>

            <div class="box box--stacked p-6">
                <h2 class="text-base font-semibold text-slate-800">Inspection Documents</h2>

                <div v-if="inspection.documents.length" class="mt-5 space-y-3">
                    <div v-for="document in inspection.documents" :key="document.id" class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <Lucide :icon="fileIcon(document.extension)" class="h-4 w-4 text-primary" />
                        <div class="min-w-0 flex-1"><p class="truncate text-sm font-medium text-slate-700">{{ document.name }}</p><p class="text-xs text-slate-400">{{ document.size_label }}<span v-if="document.created_at"> · {{ document.created_at }}</span></p></div>
                        <a :href="document.url" target="_blank" class="text-sm font-medium text-primary hover:underline">Open</a>
                    </div>
                </div>

                <p v-else class="mt-5 text-sm text-slate-500">No inspection documents are attached to this record.</p>
            </div>
        </div>

        <div class="col-span-12 xl:col-span-4 space-y-6">
            <div class="box box--stacked p-6">
                <h2 class="text-base font-semibold text-slate-800">Record Summary</h2>
                <div class="mt-5 space-y-4">
                    <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-4"><p class="text-xs uppercase tracking-[0.2em] text-slate-400">Status</p><p class="mt-2 text-sm font-semibold text-slate-800">{{ inspection.status || 'N/A' }}</p></div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-4"><p class="text-xs uppercase tracking-[0.2em] text-slate-400">Created</p><p class="mt-2 text-sm font-semibold text-slate-800">{{ inspection.created_at || 'N/A' }}</p></div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-4"><p class="text-xs uppercase tracking-[0.2em] text-slate-400">Documents</p><p class="mt-2 text-sm font-semibold text-slate-800">{{ inspection.documents.length }}</p></div>
                </div>
            </div>

            <div class="box box--stacked p-6">
                <h2 class="text-base font-semibold text-slate-800">Recent Inspections</h2>
                <div class="mt-5 space-y-3">
                    <div v-for="item in recentInspections" :key="item.id" class="rounded-xl border border-slate-200 bg-slate-50/70 p-4">
                        <p class="text-sm font-semibold text-slate-800">{{ item.inspection_type }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ item.inspection_date || 'No date' }}</p>
                        <p v-if="item.vehicle_label" class="mt-1 text-xs text-slate-500">{{ item.vehicle_label }}</p>
                        <div class="mt-2 flex flex-wrap gap-2">
                            <span class="inline-flex rounded-full bg-slate-100 px-2 py-1 text-xs font-medium text-slate-600">{{ item.status || 'Not set' }}</span>
                            <span v-if="item.has_issues" class="inline-flex rounded-full bg-slate-200 px-2 py-1 text-xs font-medium text-slate-700">Issues Found</span>
                        </div>
                        <Link :href="route('driver.inspections.show', item.id)" class="mt-3 inline-flex text-xs font-medium text-primary hover:underline">Open record</Link>
                    </div>

                    <p v-if="!recentInspections.length" class="text-sm text-slate-500">No additional inspections on file yet.</p>
                </div>
            </div>
        </div>
    </div>
</template>
