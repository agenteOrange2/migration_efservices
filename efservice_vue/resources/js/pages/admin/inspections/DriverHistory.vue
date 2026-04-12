<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { reactive } from 'vue'
import Lucide from '@/components/Base/Lucide'
import TomSelect from '@/components/Base/TomSelect/TomSelect.vue'
import { FormInput } from '@/components/Base/Form'
import RazeLayout from '@/layouts/RazeLayout.vue'

declare function route(name: string, params?: any): string
defineOptions({ layout: RazeLayout })
const props = defineProps<{
    driver: any
    inspections: any
    vehicles: any[]
    inspectionTypes: string[]
    statuses: string[]
    filters: any
    routeNames?: Record<string, string>
}>()
const filters = reactive({ ...props.filters })

const defaultRouteNames = {
    index: 'admin.inspections.index',
    create: 'admin.inspections.create',
    edit: 'admin.inspections.edit',
    driverHistory: 'admin.inspections.driver-history',
    driverDocuments: 'admin.inspections.driver-documents',
    driverShow: 'admin.drivers.show',
}

function routeName(key: keyof typeof defaultRouteNames) {
    return props.routeNames?.[key] ?? defaultRouteNames[key]
}

function applyFilters() {
    router.get(route(routeName('driverHistory'), props.driver.id), {
        search_term: filters.search_term || undefined,
        vehicle_filter: filters.vehicle_filter || undefined,
        inspection_type: filters.inspection_type || undefined,
        status: filters.status || undefined,
        sort_field: props.filters.sort_field || undefined,
        sort_direction: props.filters.sort_direction || undefined,
    }, { preserveState: true, replace: true })
}
function resetFilters() { Object.assign(filters, { search_term: '', vehicle_filter: '', inspection_type: '', status: '' }); applyFilters() }
</script>

<template>
    <Head title="Inspection History" />
    <div class="grid grid-cols-12 gap-x-6 gap-y-10">
        <div class="col-span-12">
            <div class="box box--stacked p-6 mb-6"><div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4"><div class="flex items-center gap-4"><div class="p-3 bg-primary/10 rounded-xl border border-primary/20"><Lucide icon="History" class="w-8 h-8 text-primary" /></div><div><h1 class="text-2xl font-bold text-slate-800">Inspection History</h1><p class="text-slate-500">All inspections for {{ driver.name }}</p></div></div><div class="flex flex-wrap items-center gap-3"><Link :href="route(routeName('driverShow'), driver.id)" class="inline-flex items-center gap-2 px-4 py-2 border border-slate-300 rounded-lg text-slate-600 hover:bg-slate-50 transition"><Lucide icon="ArrowLeft" class="w-4 h-4" /> Back to Driver</Link><Link :href="route(routeName('create'), { driver_id: driver.id })" class="inline-flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition"><Lucide icon="Plus" class="w-4 h-4" /> Add Inspection</Link></div></div></div>
            <div class="box box--stacked p-5 mb-6"><div class="grid grid-cols-1 lg:grid-cols-4 gap-4"><div class="relative"><Lucide icon="Search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" /><FormInput v-model="filters.search_term" type="text" class="pl-10" placeholder="Search inspections..." /></div><TomSelect v-model="filters.vehicle_filter"><option value="">All Vehicles</option><option v-for="vehicle in vehicles" :key="vehicle.id" :value="String(vehicle.id)">{{ vehicle.label }}</option></TomSelect><TomSelect v-model="filters.inspection_type"><option value="">All Types</option><option v-for="type in inspectionTypes" :key="type" :value="type">{{ type }}</option></TomSelect><TomSelect v-model="filters.status"><option value="">All Statuses</option><option v-for="status in statuses" :key="status" :value="status">{{ status }}</option></TomSelect></div><div class="flex flex-wrap items-center gap-3 mt-4"><button type="button" @click="applyFilters" class="inline-flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition"><Lucide icon="Filter" class="w-4 h-4" /> Apply Filters</button><button type="button" @click="resetFilters" class="inline-flex items-center gap-2 px-4 py-2 border border-slate-300 rounded-lg text-slate-600 hover:bg-slate-50 transition"><Lucide icon="RotateCcw" class="w-4 h-4" /> Clear</button></div></div>
            <div class="box box--stacked p-0 overflow-hidden"><div class="overflow-x-auto"><table class="w-full text-left"><thead><tr class="bg-slate-50/80"><th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Date</th><th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Vehicle</th><th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Type</th><th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Status</th><th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase text-center">Actions</th></tr></thead><tbody><tr v-for="inspection in inspections.data" :key="inspection.id" class="border-b border-slate-100 hover:bg-slate-50/50 transition"><td class="px-5 py-4 text-sm text-slate-600">{{ inspection.inspection_date_display }}</td><td class="px-5 py-4 text-sm text-slate-600">{{ inspection.vehicle?.label ?? 'N/A' }}</td><td class="px-5 py-4 text-sm text-slate-700">{{ inspection.inspection_type }}</td><td class="px-5 py-4 text-sm text-slate-600">{{ inspection.status ?? 'N/A' }}</td><td class="px-5 py-4"><div class="flex items-center justify-center gap-2"><Link :href="route(routeName('edit'), inspection.id)" class="p-1.5 text-slate-400 hover:text-amber-500 transition"><Lucide icon="PenLine" class="w-4 h-4" /></Link><Link :href="route(routeName('driverDocuments'), driver.id)" class="p-1.5 text-slate-400 hover:text-primary transition"><Lucide icon="Files" class="w-4 h-4" /></Link></div></td></tr><tr v-if="!inspections.data.length"><td colspan="5" class="px-5 py-12 text-center text-slate-400"><Lucide icon="FileCheck" class="w-12 h-12 mx-auto mb-3 text-slate-300" /><p>No inspections found for this driver</p></td></tr></tbody></table></div></div>
        </div>
    </div>
</template>
