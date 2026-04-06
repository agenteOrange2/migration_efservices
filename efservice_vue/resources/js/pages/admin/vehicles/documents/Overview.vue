<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { reactive } from 'vue'
import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import TomSelect from '@/components/Base/TomSelect/TomSelect.vue'
import { FormInput } from '@/components/Base/Form'
import RazeLayout from '@/layouts/RazeLayout.vue'

declare function route(name: string, params?: any): string

defineOptions({ layout: RazeLayout })

const props = defineProps<{
    vehicles: { data: any[]; links: { url: string | null; label: string; active: boolean }[]; total: number; last_page: number }
    filters: { search: string; carrier_id: string; vehicle_status: string; document_type: string; document_status: string; sort_field: string; sort_direction: string }
    carriers: { id: number; name: string }[]
    documentTypes: Record<string, string>
    documentStatuses: Record<string, string>
    vehicleStatusOptions: Record<string, string>
    stats: { vehicles: number; documents: number; active: number; expired: number; expiring_soon: number }
    isSuperadmin: boolean
}>()

const filters = reactive({ ...props.filters })

function applyFilters() {
    router.get(route('admin.vehicles-documents.index'), {
        search: filters.search || undefined,
        carrier_id: filters.carrier_id || undefined,
        vehicle_status: filters.vehicle_status || undefined,
        document_type: filters.document_type || undefined,
        document_status: filters.document_status || undefined,
        sort_field: props.filters.sort_field || undefined,
        sort_direction: props.filters.sort_direction || undefined,
    }, { preserveState: true, replace: true })
}

function resetFilters() {
    filters.search = ''
    filters.carrier_id = props.isSuperadmin ? '' : props.filters.carrier_id
    filters.vehicle_status = ''
    filters.document_type = ''
    filters.document_status = ''
    applyFilters()
}
</script>

<template>
    <Head title="Vehicle Documents Overview" />

    <div class="grid grid-cols-12 gap-x-6 gap-y-10">
        <div class="col-span-12">
            <div class="box box--stacked p-6 mb-6">
                <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                            <Lucide icon="Files" class="w-8 h-8 text-primary" />
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-slate-800">Vehicle Documents Overview</h1>
                            <p class="text-slate-500">Review document health across the fleet.</p>
                        </div>
                    </div>
                    <Link :href="route('admin.vehicles.index')">
                        <Button variant="outline-secondary" class="flex items-center gap-2">
                            <Lucide icon="ArrowLeft" class="w-4 h-4" />
                            Back to Vehicles
                        </Button>
                    </Link>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-4 mb-6">
                <div class="box box--stacked rounded-xl border border-dashed border-slate-300/80 p-5"><p class="text-sm text-slate-500">Vehicles</p><p class="mt-1 text-2xl font-semibold text-slate-800">{{ stats.vehicles }}</p></div>
                <div class="box box--stacked rounded-xl border border-dashed border-slate-300/80 p-5"><p class="text-sm text-slate-500">Documents</p><p class="mt-1 text-2xl font-semibold text-slate-800">{{ stats.documents }}</p></div>
                <div class="box box--stacked rounded-xl border border-dashed border-slate-300/80 p-5"><p class="text-sm text-slate-500">Active</p><p class="mt-1 text-2xl font-semibold text-slate-800">{{ stats.active }}</p></div>
                <div class="box box--stacked rounded-xl border border-dashed border-slate-300/80 p-5"><p class="text-sm text-slate-500">Expired</p><p class="mt-1 text-2xl font-semibold text-slate-800">{{ stats.expired }}</p></div>
                <div class="box box--stacked rounded-xl border border-dashed border-slate-300/80 p-5"><p class="text-sm text-slate-500">Expiring Soon</p><p class="mt-1 text-2xl font-semibold text-slate-800">{{ stats.expiring_soon }}</p></div>
            </div>

            <div class="box box--stacked p-5 mb-6">
                <div class="grid grid-cols-1 lg:grid-cols-5 gap-4">
                    <div class="lg:col-span-2 relative">
                        <Lucide icon="Search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                        <FormInput v-model="filters.search" type="text" class="pl-10" placeholder="Search vehicle, carrier, document..." />
                    </div>

                    <TomSelect v-if="isSuperadmin" v-model="filters.carrier_id">
                        <option value="">All carriers</option>
                        <option v-for="carrier in carriers" :key="carrier.id" :value="String(carrier.id)">{{ carrier.name }}</option>
                    </TomSelect>
                    <div v-else class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-500 flex items-center">Carrier scope locked to your account</div>

                    <TomSelect v-model="filters.vehicle_status">
                        <option value="">All vehicle statuses</option>
                        <option v-for="(label, key) in vehicleStatusOptions" :key="key" :value="key">{{ label }}</option>
                    </TomSelect>

                    <TomSelect v-model="filters.document_type">
                        <option value="">All document types</option>
                        <option v-for="(label, key) in documentTypes" :key="key" :value="key">{{ label }}</option>
                    </TomSelect>

                    <TomSelect v-model="filters.document_status">
                        <option value="">All document statuses</option>
                        <option v-for="(label, key) in documentStatuses" :key="key" :value="key">{{ label }}</option>
                    </TomSelect>
                </div>

                <div class="flex flex-wrap items-center gap-3 mt-4">
                    <button type="button" @click="applyFilters" class="inline-flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition">
                        <Lucide icon="Filter" class="w-4 h-4" />
                        Apply Filters
                    </button>
                    <button type="button" @click="resetFilters" class="inline-flex items-center gap-2 px-4 py-2 border border-slate-300 rounded-lg text-slate-600 hover:bg-slate-50 transition">
                        <Lucide icon="RotateCcw" class="w-4 h-4" />
                        Clear
                    </button>
                </div>
            </div>

            <div class="box box--stacked p-0 overflow-hidden">
                <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200/60">
                    <div>
                        <h2 class="text-base font-semibold text-slate-800">Vehicle Document Summary</h2>
                        <p class="text-sm text-slate-500">{{ vehicles.total }} records</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-slate-50/80">
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Vehicle</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Carrier</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Status</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Document Health</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Next Expiration</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="vehicle in vehicles.data" :key="vehicle.id" class="border-b border-slate-100 hover:bg-slate-50/50 transition">
                                <td class="px-5 py-4">
                                    <div class="font-medium text-slate-800">{{ vehicle.title }}</div>
                                    <div class="text-xs text-slate-400">{{ vehicle.company_unit_number ? `Unit ${vehicle.company_unit_number}` : 'No unit number' }} · VIN {{ vehicle.vin }}</div>
                                </td>
                                <td class="px-5 py-4 text-sm text-slate-600">{{ vehicle.carrier_name ?? 'N/A' }}</td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium" :class="vehicle.status === 'active' ? 'bg-primary/10 text-primary' : 'bg-slate-100 text-slate-600'">
                                        {{ vehicle.status_label }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-sm text-slate-600">
                                    <div>Total: {{ vehicle.documents_count }}</div>
                                    <div class="text-xs text-slate-500">Active {{ vehicle.active_documents_count }} · Expired {{ vehicle.expired_documents_count }} · Pending {{ vehicle.pending_documents_count }}</div>
                                </td>
                                <td class="px-5 py-4 text-sm text-slate-600">
                                    <div>{{ vehicle.next_expiring_document?.document_type_label ?? 'No dated documents' }}</div>
                                    <div class="text-xs text-slate-400">{{ vehicle.next_expiring_document?.expiration_date ?? 'N/A' }}</div>
                                </td>
                                <td class="px-5 py-4 text-center">
                                    <Link :href="route('admin.vehicles.documents.index', vehicle.id)" class="inline-flex items-center gap-2 rounded-lg border border-primary/20 bg-primary/5 px-3 py-2 text-sm font-medium text-primary hover:bg-primary/10">
                                        <Lucide icon="Files" class="w-4 h-4" />
                                        Open
                                    </Link>
                                </td>
                            </tr>

                            <tr v-if="!vehicles.data.length">
                                <td colspan="6" class="px-5 py-12 text-center text-slate-400">
                                    <Lucide icon="Files" class="w-12 h-12 mx-auto mb-3 text-slate-300" />
                                    <p>No vehicle document matches found</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="vehicles.last_page > 1" class="p-4 border-t border-slate-200/60 flex items-center justify-between">
                    <span class="text-sm text-slate-500">{{ vehicles.total }} total records</span>
                    <div class="flex gap-1">
                        <template v-for="link in vehicles.links" :key="link.label">
                            <Link v-if="link.url" :href="link.url" class="px-3 py-1 text-sm rounded" :class="link.active ? 'bg-primary text-white' : 'text-slate-600 hover:bg-slate-100'" v-html="link.label" />
                            <span v-else class="px-3 py-1 text-sm text-slate-300" v-html="link.label" />
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
