<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { reactive } from 'vue'
import Lucide from '@/components/Base/Lucide'
import Litepicker from '@/components/Base/Litepicker/Litepicker.vue'
import TomSelect from '@/components/Base/TomSelect/TomSelect.vue'
import { FormInput } from '@/components/Base/Form'
import RazeLayout from '@/layouts/RazeLayout.vue'

declare function route(name: string, params?: any): string
defineOptions({ layout: RazeLayout })
const lpOptions = { singleMode: true, format: 'M/D/YYYY', autoApply: true }
const props = defineProps<{ documents: any; drivers: any[]; carriers: any[]; filters: any }>()
const filters = reactive({ ...props.filters })

function applyFilters() {
    router.get(route('admin.inspections.documents.index'), {
        search_term: filters.search_term || undefined,
        carrier_filter: filters.carrier_filter || undefined,
        driver_filter: filters.driver_filter || undefined,
        date_from: filters.date_from || undefined,
        date_to: filters.date_to || undefined,
        sort_field: props.filters.sort_field || undefined,
        sort_direction: props.filters.sort_direction || undefined,
    }, { preserveState: true, replace: true })
}

function resetFilters() {
    Object.assign(filters, { search_term: '', carrier_filter: '', driver_filter: '', date_from: '', date_to: '' })
    applyFilters()
}
</script>

<template>
    <Head title="Inspection Documents" />
    <div class="grid grid-cols-12 gap-x-6 gap-y-10">
        <div class="col-span-12">
            <div class="box box--stacked p-6 mb-6">
                <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
                    <div class="flex items-center gap-4"><div class="p-3 bg-primary/10 rounded-xl border border-primary/20"><Lucide icon="Files" class="w-8 h-8 text-primary" /></div><div><h1 class="text-2xl font-bold text-slate-800">All Inspection Documents</h1><p class="text-slate-500">View and manage all documents across inspection records.</p></div></div>
                    <Link :href="route('admin.inspections.index')" class="inline-flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition"><Lucide icon="ArrowLeft" class="w-4 h-4" /> Back to Inspections</Link>
                </div>
            </div>
            <div class="box box--stacked p-5 mb-6">
                <div class="grid grid-cols-1 lg:grid-cols-5 gap-4">
                    <div class="relative"><Lucide icon="Search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" /><FormInput v-model="filters.search_term" type="text" class="pl-10" placeholder="Search documents..." /></div>
                    <TomSelect v-model="filters.carrier_filter"><option value="">All Carriers</option><option v-for="carrier in carriers" :key="carrier.id" :value="String(carrier.id)">{{ carrier.name }}</option></TomSelect>
                    <TomSelect v-model="filters.driver_filter"><option value="">All Drivers</option><option v-for="driver in drivers" :key="driver.id" :value="String(driver.id)">{{ driver.name }}</option></TomSelect>
                    <Litepicker v-model="filters.date_from" :options="lpOptions" />
                    <Litepicker v-model="filters.date_to" :options="lpOptions" />
                </div>
                <div class="flex flex-wrap items-center gap-3 mt-4"><button type="button" @click="applyFilters" class="inline-flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition"><Lucide icon="Filter" class="w-4 h-4" /> Apply Filters</button><button type="button" @click="resetFilters" class="inline-flex items-center gap-2 px-4 py-2 border border-slate-300 rounded-lg text-slate-600 hover:bg-slate-50 transition"><Lucide icon="RotateCcw" class="w-4 h-4" /> Clear</button></div>
            </div>
            <div class="box box--stacked p-5">
                <div v-if="documents.data.length" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                    <div v-for="document in documents.data" :key="document.id" class="border rounded-lg overflow-hidden shadow-sm">
                        <div class="p-4 bg-slate-50 border-b">
                            <div class="flex justify-between items-start gap-3">
                                <div><h4 class="font-medium text-slate-900 truncate" :title="document.name">{{ document.name }}</h4><p class="text-xs text-slate-500 mt-1">{{ document.size_label }} · {{ document.mime_type }} · {{ document.created_at_display }}</p></div>
                                <div class="flex"><a :href="document.preview_url" target="_blank" class="text-blue-600 hover:text-blue-800 mr-2"><Lucide icon="Eye" class="w-5 h-5" /></a></div>
                            </div>
                        </div>
                        <div class="p-4 text-sm text-slate-600 space-y-2">
                            <div><span class="text-xs font-medium text-slate-500">Inspection:</span> <Link v-if="document.inspection_id" :href="route('admin.inspections.edit', document.inspection_id)" class="text-primary hover:underline">{{ document.inspection_type }} ({{ document.inspection_date_display }})</Link></div>
                            <div><span class="text-xs font-medium text-slate-500">Driver:</span> <Link v-if="document.driver_id" :href="route('admin.inspections.driver-documents', document.driver_id)" class="text-primary hover:underline">{{ document.driver_name }}</Link></div>
                            <div><span class="text-xs font-medium text-slate-500">Carrier:</span> {{ document.carrier_name }}</div>
                            <div><span class="text-xs font-medium text-slate-500">Vehicle:</span> {{ document.vehicle_label }}</div>
                        </div>
                    </div>
                </div>
                <div v-else class="text-center py-10 text-slate-400"><Lucide icon="FileQuestion" class="h-12 w-12 mx-auto text-slate-300" /><h3 class="mt-2 text-sm font-medium text-slate-900">No documents found</h3><p class="mt-1 text-sm text-slate-500">No inspection documents match your search criteria.</p></div>
            </div>
        </div>
    </div>
</template>
