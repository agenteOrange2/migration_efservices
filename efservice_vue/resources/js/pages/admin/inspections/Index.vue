<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { reactive, ref } from 'vue'
import Lucide from '@/components/Base/Lucide'
import Litepicker from '@/components/Base/Litepicker/Litepicker.vue'
import TomSelect from '@/components/Base/TomSelect/TomSelect.vue'
import { FormInput } from '@/components/Base/Form'
import { Dialog } from '@/components/Base/Headless'
import RazeLayout from '@/layouts/RazeLayout.vue'

declare function route(name: string, params?: any): string
defineOptions({ layout: RazeLayout })
const lpOptions = { singleMode: true, format: 'M/D/YYYY', autoApply: true }

const props = defineProps<{ inspections: any; drivers: any[]; carriers: any[]; vehicles: any[]; inspectionTypes: string[]; statuses: string[]; filters: any }>()
const filters = reactive({ ...props.filters })
const deleteModalOpen = ref(false)
const selectedInspection = ref<any | null>(null)

function applyFilters() {
    router.get(route('admin.inspections.index'), {
        search_term: filters.search_term || undefined,
        carrier_filter: filters.carrier_filter || undefined,
        driver_filter: filters.driver_filter || undefined,
        vehicle_filter: filters.vehicle_filter || undefined,
        date_from: filters.date_from || undefined,
        date_to: filters.date_to || undefined,
        inspection_type: filters.inspection_type || undefined,
        status: filters.status || undefined,
        sort_field: props.filters.sort_field || undefined,
        sort_direction: props.filters.sort_direction || undefined,
    }, { preserveState: true, replace: true })
}

function resetFilters() {
    Object.assign(filters, { search_term: '', carrier_filter: '', driver_filter: '', vehicle_filter: '', date_from: '', date_to: '', inspection_type: '', status: '' })
    applyFilters()
}

function sortUrl(field: string) {
    const direction = props.filters.sort_field === field && props.filters.sort_direction === 'asc' ? 'desc' : 'asc'
    return route('admin.inspections.index', { ...filters, sort_field: field, sort_direction: direction })
}

function openDeleteModal(inspection: any) {
    selectedInspection.value = inspection
    deleteModalOpen.value = true
}

function confirmDelete() {
    if (!selectedInspection.value) return
    router.delete(route('admin.inspections.destroy', selectedInspection.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            deleteModalOpen.value = false
            selectedInspection.value = null
        },
    })
}
</script>

<template>
    <Head title="Inspections" />
    <div class="grid grid-cols-12 gap-x-6 gap-y-10">
        <div class="col-span-12">
            <div class="box box--stacked p-6 mb-6">
                <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-primary/10 rounded-xl border border-primary/20"><Lucide icon="FileCheck" class="w-8 h-8 text-primary" /></div>
                        <div><h1 class="text-2xl font-bold text-slate-800">Driver Inspections</h1><p class="text-slate-500">Manage and track driver inspections in the Vue admin.</p></div>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <Link :href="route('admin.inspections.documents.index')" class="inline-flex items-center gap-2 px-4 py-2 border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-50 transition"><Lucide icon="Files" class="w-4 h-4" /> All Documents</Link>
                        <Link :href="route('admin.inspections.create')" class="inline-flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition"><Lucide icon="Plus" class="w-4 h-4" /> Add Inspection</Link>
                    </div>
                </div>
            </div>

            <div class="box box--stacked p-5 mb-6">
                <div class="grid grid-cols-1 lg:grid-cols-4 gap-4">
                    <div class="relative"><Lucide icon="Search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" /><FormInput v-model="filters.search_term" type="text" class="pl-10" placeholder="Search inspections..." /></div>
                    <TomSelect v-model="filters.carrier_filter"><option value="">All Carriers</option><option v-for="carrier in carriers" :key="carrier.id" :value="String(carrier.id)">{{ carrier.name }}</option></TomSelect>
                    <TomSelect v-model="filters.driver_filter"><option value="">All Drivers</option><option v-for="driver in drivers" :key="driver.id" :value="String(driver.id)">{{ driver.name }}</option></TomSelect>
                    <TomSelect v-model="filters.vehicle_filter"><option value="">All Vehicles</option><option v-for="vehicle in vehicles" :key="vehicle.id" :value="String(vehicle.id)">{{ vehicle.label }}</option></TomSelect>
                    <Litepicker v-model="filters.date_from" :options="lpOptions" />
                    <Litepicker v-model="filters.date_to" :options="lpOptions" />
                    <TomSelect v-model="filters.inspection_type"><option value="">All Types</option><option v-for="type in inspectionTypes" :key="type" :value="type">{{ type }}</option></TomSelect>
                    <TomSelect v-model="filters.status"><option value="">All Statuses</option><option v-for="status in statuses" :key="status" :value="status">{{ status }}</option></TomSelect>
                </div>
                <div class="flex flex-wrap items-center gap-3 mt-4">
                    <button type="button" @click="applyFilters" class="inline-flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition"><Lucide icon="Filter" class="w-4 h-4" /> Apply Filters</button>
                    <button type="button" @click="resetFilters" class="inline-flex items-center gap-2 px-4 py-2 border border-slate-300 rounded-lg text-slate-600 hover:bg-slate-50 transition"><Lucide icon="RotateCcw" class="w-4 h-4" /> Clear</button>
                </div>
            </div>

            <div class="box box--stacked p-0 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead><tr class="bg-slate-50/80">
                            <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Created</th>
                            <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Carrier</th>
                            <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Driver</th>
                            <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Vehicle</th>
                            <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Type</th>
                            <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Status</th>
                            <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase"><Link :href="sortUrl('inspection_date')" class="inline-flex items-center gap-1 hover:text-slate-700">Inspection Date<Lucide v-if="props.filters.sort_field === 'inspection_date'" :icon="props.filters.sort_direction === 'asc' ? 'ChevronUp' : 'ChevronDown'" class="w-3 h-3" /></Link></th>
                            <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase text-center">Actions</th>
                        </tr></thead>
                        <tbody>
                            <tr v-for="inspection in inspections.data" :key="inspection.id" class="border-b border-slate-100 hover:bg-slate-50/50 transition">
                                <td class="px-5 py-4 text-sm text-slate-500">{{ inspection.created_at_display }}</td>
                                <td class="px-5 py-4 text-sm text-slate-600">{{ inspection.carrier?.name ?? 'N/A' }}</td>
                                <td class="px-5 py-4"><div class="font-medium text-slate-700">{{ inspection.driver?.name ?? 'N/A' }}</div><div class="text-xs text-slate-500">{{ inspection.driver?.email ?? 'No email' }}</div></td>
                                <td class="px-5 py-4 text-sm text-slate-600">{{ inspection.vehicle?.label ?? 'N/A' }}</td>
                                <td class="px-5 py-4 text-sm text-slate-700">{{ inspection.inspection_type ?? 'N/A' }}</td>
                                <td class="px-5 py-4 text-sm text-slate-600">{{ inspection.status ?? 'N/A' }}</td>
                                <td class="px-5 py-4 text-sm text-slate-600">{{ inspection.inspection_date_display ?? 'N/A' }}</td>
                                <td class="px-5 py-4"><div class="flex items-center justify-center gap-2">
                                    <Link v-if="inspection.driver" :href="route('admin.inspections.driver-documents', inspection.driver.id)" class="p-1.5 text-slate-400 hover:text-primary transition" title="Driver documents"><Lucide icon="Files" class="w-4 h-4" /></Link>
                                    <Link :href="route('admin.inspections.edit', inspection.id)" class="p-1.5 text-slate-400 hover:text-amber-500 transition" title="Edit"><Lucide icon="PenLine" class="w-4 h-4" /></Link>
                                    <Link v-if="inspection.driver" :href="route('admin.inspections.driver-history', inspection.driver.id)" class="p-1.5 text-slate-400 hover:text-sky-500 transition" title="History"><Lucide icon="History" class="w-4 h-4" /></Link>
                                    <button type="button" @click="openDeleteModal(inspection)" class="p-1.5 text-slate-400 hover:text-red-500 transition" title="Delete"><Lucide icon="Trash2" class="w-4 h-4" /></button>
                                </div></td>
                            </tr>
                            <tr v-if="!inspections.data.length"><td colspan="8" class="px-5 py-12 text-center text-slate-400"><Lucide icon="FileCheck" class="w-12 h-12 mx-auto mb-3 text-slate-300" /><p>No inspection records found</p></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <Dialog :open="deleteModalOpen" @close="deleteModalOpen = false" size="lg">
        <Dialog.Panel class="w-full max-w-[600px] overflow-hidden">
            <div class="px-6 pt-6"><button type="button" class="ml-auto flex rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600" @click="deleteModalOpen = false"><Lucide icon="X" class="h-5 w-5" /></button><div class="pb-2 text-center"><div class="mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-full border-2 border-danger text-danger"><Lucide icon="X" class="h-8 w-8" /></div><h3 class="text-[2.1rem] font-light text-slate-600">Are you sure?</h3><p class="mt-3 text-base leading-7 text-slate-500">Do you really want to delete this inspection?<br>This process cannot be undone.</p></div></div>
            <div class="flex justify-center gap-3 px-6 pb-8 pt-4"><button type="button" class="min-w-24 rounded-lg border border-slate-300 px-6 py-2.5 text-base font-medium text-slate-600 hover:bg-slate-50" @click="deleteModalOpen = false">Cancel</button><button type="button" class="min-w-24 rounded-lg bg-danger px-6 py-2.5 text-base font-medium text-white hover:bg-danger/90" @click="confirmDelete">Delete</button></div>
        </Dialog.Panel>
    </Dialog>
</template>
