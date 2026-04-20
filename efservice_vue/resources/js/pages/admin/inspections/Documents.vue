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
const props = defineProps<{
    documents: any
    drivers: any[]
    carriers: any[]
    filters: any
    carrier?: any | null
    isCarrierContext?: boolean
    routeNames?: Record<string, string>
}>()
const filters = reactive({ ...props.filters })

const defaultRouteNames = {
    index: 'admin.inspections.index',
    edit: 'admin.inspections.edit',
    documentsIndex: 'admin.inspections.documents.index',
    driverDocuments: 'admin.inspections.driver-documents',
}

function routeName(key: keyof typeof defaultRouteNames) {
    return props.routeNames?.[key] ?? defaultRouteNames[key]
}

function applyFilters() {
    router.get(route(routeName('documentsIndex')), {
        search_term: filters.search_term || undefined,
        carrier_filter: props.isCarrierContext ? undefined : filters.carrier_filter || undefined,
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
                    <div class="flex items-center gap-4"><div class="p-3 bg-primary/10 rounded-xl border border-primary/20"><Lucide icon="Files" class="w-8 h-8 text-primary" /></div><div><h1 class="text-2xl font-bold text-slate-800">All Inspection Documents</h1><p class="text-slate-500">{{ props.isCarrierContext ? 'View inspection documents for your drivers.' : 'View and manage all documents across inspection records.' }}</p></div></div>
                    <Link :href="route(routeName('index'))" class="inline-flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition"><Lucide icon="ArrowLeft" class="w-4 h-4" /> Back to Inspections</Link>
                </div>
            </div>
            <div class="box box--stacked p-5 mb-6">
                <div class="grid grid-cols-1 lg:grid-cols-5 gap-4">
                    <div class="relative"><Lucide icon="Search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" /><FormInput v-model="filters.search_term" type="text" class="pl-10" placeholder="Search documents..." /></div>
                    <TomSelect v-if="!props.isCarrierContext" v-model="filters.carrier_filter"><option value="">All Carriers</option><option v-for="carrier in carriers" :key="carrier.id" :value="String(carrier.id)">{{ carrier.name }}</option></TomSelect>
                    <TomSelect v-model="filters.driver_filter"><option value="">All Drivers</option><option v-for="driver in drivers" :key="driver.id" :value="String(driver.id)">{{ driver.name }}</option></TomSelect>
                    <Litepicker v-model="filters.date_from" :options="lpOptions" />
                    <Litepicker v-model="filters.date_to" :options="lpOptions" />
                </div>
                <div class="flex flex-wrap items-center gap-3 mt-4"><button type="button" @click="applyFilters" class="inline-flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition"><Lucide icon="Filter" class="w-4 h-4" /> Apply Filters</button><button type="button" @click="resetFilters" class="inline-flex items-center gap-2 px-4 py-2 border border-slate-300 rounded-lg text-slate-600 hover:bg-slate-50 transition"><Lucide icon="RotateCcw" class="w-4 h-4" /> Clear</button></div>
            </div>
            <div class="box box--stacked p-5">
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="border-b border-slate-200/60 bg-slate-50/80">
                                <th class="px-5 py-3 text-xs font-medium uppercase tracking-wide text-slate-500">Uploaded</th>
                                <th class="px-5 py-3 text-xs font-medium uppercase tracking-wide text-slate-500">Document</th>
                                <th class="px-5 py-3 text-xs font-medium uppercase tracking-wide text-slate-500">Inspection</th>
                                <th class="px-5 py-3 text-xs font-medium uppercase tracking-wide text-slate-500">Driver</th>
                                <th class="px-5 py-3 text-xs font-medium uppercase tracking-wide text-slate-500">Carrier</th>
                                <th class="px-5 py-3 text-xs font-medium uppercase tracking-wide text-slate-500">Vehicle</th>
                                <th class="px-5 py-3 text-center text-xs font-medium uppercase tracking-wide text-slate-500">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="document in documents.data"
                                :key="document.id"
                                class="border-b border-slate-100 transition hover:bg-slate-50/60"
                            >
                                <td class="px-5 py-4 text-sm text-slate-500">
                                    {{ document.created_at_display }}
                                </td>
                                <td class="px-5 py-4">
                                    <a
                                        :href="document.preview_url"
                                        target="_blank"
                                        class="font-medium text-primary hover:text-primary/80"
                                    >
                                        {{ document.name }}
                                    </a>
                                    <div class="mt-1 text-xs text-slate-500">
                                        {{ document.size_label }} · {{ document.mime_type ?? 'Unknown type' }}
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-sm text-slate-600">
                                    <Link
                                        v-if="document.inspection_id"
                                        :href="route(routeName('edit'), document.inspection_id)"
                                        class="font-medium text-primary hover:text-primary/80"
                                    >
                                        {{ document.inspection_type ?? 'Inspection' }}
                                    </Link>
                                    <div class="mt-1 text-xs text-slate-500">
                                        {{ document.inspection_date_display ?? 'No inspection date' }}
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-sm text-slate-600">
                                    <Link
                                        v-if="document.driver_id"
                                        :href="route(routeName('driverDocuments'), document.driver_id)"
                                        class="font-medium text-primary hover:text-primary/80"
                                    >
                                        {{ document.driver_name }}
                                    </Link>
                                    <span v-else>{{ document.driver_name }}</span>
                                </td>
                                <td class="px-5 py-4 text-sm text-slate-600">
                                    {{ document.carrier_name ?? props.carrier?.name ?? 'N/A' }}
                                </td>
                                <td class="px-5 py-4 text-sm text-slate-600">
                                    {{ document.vehicle_label }}
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center justify-center gap-2">
                                        <a
                                            :href="document.preview_url"
                                            target="_blank"
                                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-slate-500 transition hover:border-primary/30 hover:bg-primary/5 hover:text-primary"
                                            title="Preview document"
                                        >
                                            <Lucide icon="Eye" class="h-4 w-4" />
                                        </a>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="!documents.data.length">
                                <td colspan="7" class="px-5 py-12 text-center text-slate-400">
                                    <Lucide icon="FileQuestion" class="mx-auto h-12 w-12 text-slate-300" />
                                    <h3 class="mt-2 text-sm font-medium text-slate-900">No documents found</h3>
                                    <p class="mt-1 text-sm text-slate-500">No inspection documents match your search criteria.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div
                    v-if="documents.last_page > 1"
                    class="flex items-center justify-between border-t border-slate-200/60 px-5 py-4"
                >
                    <span class="text-sm text-slate-500">{{ documents.total }} documents</span>
                    <div class="flex flex-wrap items-center gap-1">
                        <template v-for="link in documents.links" :key="link.label">
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                class="rounded px-3 py-1 text-sm transition"
                                :class="link.active ? 'bg-primary text-white' : 'text-slate-600 hover:bg-slate-100'"
                                v-html="link.label"
                            />
                            <span
                                v-else
                                class="px-3 py-1 text-sm text-slate-300"
                                v-html="link.label"
                            />
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
