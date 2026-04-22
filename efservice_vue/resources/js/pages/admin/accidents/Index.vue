<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { reactive, ref } from 'vue'
import Lucide from '@/components/Base/Lucide'
import Button from '@/components/Base/Button'
import Litepicker from '@/components/Base/Litepicker/Litepicker.vue'
import TomSelect from '@/components/Base/TomSelect/TomSelect.vue'
import { Dialog } from '@/components/Base/Headless'
import { FormInput } from '@/components/Base/Form'
import RazeLayout from '@/layouts/RazeLayout.vue'

declare function route(name: string, params?: any): string

defineOptions({ layout: RazeLayout })

const lpOptions = { singleMode: true, format: 'M/D/YYYY', autoApply: true }

const props = defineProps<{
    accidents: {
        data: any[]
        links: { url: string | null; label: string; active: boolean }[]
        total: number
        last_page: number
    }
    drivers: { id: number; name: string }[]
    carriers: { id: number; name: string }[]
    filters: {
        search_term: string
        carrier_filter: string
        driver_filter: string
        date_from: string
        date_to: string
        sort_field: string
        sort_direction: string
    }
    carrier?: { id: number; name: string } | null
    isCarrierContext?: boolean
    routeNames?: {
        index: string
        create: string
        store: string
        edit: string
        update: string
        destroy: string
        driverHistory: string
        documentsIndex: string
        documentsShow: string
        documentsDestroy: string
        mediaDestroy: string
        driverShow: string
    }
}>()

const filters = reactive({ ...props.filters })
const deleteModalOpen = ref(false)
const selectedAccident = ref<any | null>(null)

function applyFilters() {
    router.get(route(props.routeNames?.index ?? 'admin.accidents.index'), {
        ...filters,
        search_term: filters.search_term || undefined,
        carrier_filter: props.isCarrierContext ? undefined : (filters.carrier_filter || undefined),
        driver_filter: filters.driver_filter || undefined,
        date_from: filters.date_from || undefined,
        date_to: filters.date_to || undefined,
        sort_field: props.filters.sort_field || undefined,
        sort_direction: props.filters.sort_direction || undefined,
    }, { preserveState: true, replace: true })
}

function resetFilters() {
    filters.search_term = ''
    filters.carrier_filter = props.isCarrierContext && props.carrier?.id ? String(props.carrier.id) : ''
    filters.driver_filter = ''
    filters.date_from = ''
    filters.date_to = ''
    applyFilters()
}

function sortUrl(field: string) {
    const direction = props.filters.sort_field === field && props.filters.sort_direction === 'asc' ? 'desc' : 'asc'

    return route(props.routeNames?.index ?? 'admin.accidents.index', {
        ...filters,
        search_term: filters.search_term || undefined,
        carrier_filter: props.isCarrierContext ? undefined : (filters.carrier_filter || undefined),
        driver_filter: filters.driver_filter || undefined,
        date_from: filters.date_from || undefined,
        date_to: filters.date_to || undefined,
        sort_field: field,
        sort_direction: direction,
    })
}

function openDeleteModal(accident: any) {
    selectedAccident.value = accident
    deleteModalOpen.value = true
}

function confirmDelete() {
    if (!selectedAccident.value) return

    router.delete(route(props.routeNames?.destroy ?? 'admin.accidents.destroy', selectedAccident.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            deleteModalOpen.value = false
            selectedAccident.value = null
        },
    })
}
</script>

<template>
    <Head title="Driver Accidents Management" />

    <div class="grid grid-cols-12 gap-x-6 gap-y-10">
        <div class="col-span-12">
            <div class="box box--stacked p-6 mb-6">
                <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                            <Lucide icon="AlertTriangle" class="w-8 h-8 text-primary" />
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-slate-800">Driver Accidents Management</h1>
                            <p class="text-slate-500">Manage and track driver accident records in the Vue admin.</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <Link :href="route(props.routeNames?.documentsIndex ?? 'admin.accidents.documents.index')">
                            <Button variant="outline-primary" class="flex items-center gap-2">
                                <Lucide icon="FileText" class="w-4 h-4" />
                                View All Documents
                            </Button>
                        </Link>
                        <Link :href="route(props.routeNames?.create ?? 'admin.accidents.create')">
                            <Button variant="primary" class="flex items-center gap-2">
                                <Lucide icon="Plus" class="w-4 h-4" />
                                Add Accident
                            </Button>
                        </Link>
                    </div>
                </div>
            </div>

            <div class="box box--stacked p-5 mb-6">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <div class="relative">
                        <Lucide icon="Search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                        <FormInput v-model="filters.search_term" type="text" class="pl-10" placeholder="Search accidents..." />
                    </div>

                    <TomSelect v-if="!props.isCarrierContext" v-model="filters.carrier_filter">
                        <option value="">All Carriers</option>
                        <option v-for="carrier in carriers" :key="carrier.id" :value="String(carrier.id)">{{ carrier.name }}</option>
                    </TomSelect>

                    <div v-else class="rounded-lg border border-primary/15 bg-primary/5 px-4 py-2.5 text-sm text-slate-700">
                        <span class="font-medium text-primary">Carrier:</span>
                        <span class="ml-2">{{ props.carrier?.name ?? 'Assigned carrier' }}</span>
                    </div>

                    <TomSelect v-model="filters.driver_filter">
                        <option value="">All Drivers</option>
                        <option v-for="driver in drivers" :key="driver.id" :value="String(driver.id)">{{ driver.name }}</option>
                    </TomSelect>

                    <div class="grid grid-cols-2 gap-3">
                        <Litepicker v-model="filters.date_from" :options="lpOptions" />
                        <Litepicker v-model="filters.date_to" :options="lpOptions" />
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3 mt-4">
                    <Button type="button" variant="primary" class="flex items-center gap-2" @click="applyFilters">
                        <Lucide icon="Filter" class="w-4 h-4" />
                        Apply Filters
                    </Button>

                    <Button type="button" variant="outline-secondary" class="flex items-center gap-2" @click="resetFilters">
                        <Lucide icon="RotateCcw" class="w-4 h-4" />
                        Clear
                    </Button>
                </div>
            </div>

            <div class="box box--stacked p-0 overflow-hidden">
                <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200/60">
                    <div>
                        <h2 class="text-base font-semibold text-slate-800">Accidents</h2>
                        <p class="text-sm text-slate-500">{{ accidents.total }} records</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-slate-50/80">
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">
                                    <Link :href="sortUrl('created_at')" class="inline-flex items-center gap-1 hover:text-slate-700">
                                        Registration Date
                                        <Lucide v-if="props.filters.sort_field === 'created_at'" :icon="props.filters.sort_direction === 'asc' ? 'ChevronUp' : 'ChevronDown'" class="w-3 h-3" />
                                    </Link>
                                </th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Carrier</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Driver</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Nature</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">
                                    <Link :href="sortUrl('accident_date')" class="inline-flex items-center gap-1 hover:text-slate-700">
                                        Date
                                        <Lucide v-if="props.filters.sort_field === 'accident_date'" :icon="props.filters.sort_direction === 'asc' ? 'ChevronUp' : 'ChevronDown'" class="w-3 h-3" />
                                    </Link>
                                </th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Injuries</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Fatalities</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="accident in accidents.data" :key="accident.id" class="border-b border-slate-100 hover:bg-slate-50/50 transition">
                                <td class="px-5 py-4 text-sm text-slate-500">{{ accident.created_at_display }}</td>
                                <td class="px-5 py-4 text-sm text-slate-600">{{ accident.carrier?.name ?? 'N/A' }}</td>
                                <td class="px-5 py-4">
                                    <div class="font-medium text-slate-700">{{ accident.driver?.name ?? 'N/A' }}</div>
                                </td>
                                <td class="px-5 py-4 text-sm text-slate-600">{{ accident.nature_of_accident }}</td>
                                <td class="px-5 py-4 text-sm text-slate-600">{{ accident.accident_date_display }}</td>
                                <td class="px-5 py-4 text-sm">
                                    <span :class="accident.had_injuries ? 'text-success' : 'text-danger'">
                                        {{ accident.had_injuries ? `Yes (${accident.number_of_injuries})` : 'No' }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-sm">
                                    <span :class="accident.had_fatalities ? 'text-success' : 'text-danger'">
                                        {{ accident.had_fatalities ? `Yes (${accident.number_of_fatalities})` : 'No' }}
                                    </span>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center justify-center gap-2">
                                        <Link v-if="accident.driver" :href="route(props.routeNames?.driverHistory ?? 'admin.accidents.driver-history', accident.driver.id)" class="p-1.5 text-slate-400 hover:text-primary transition" title="View history">
                                            <Lucide icon="Eye" class="w-4 h-4" />
                                        </Link>
                                        <Link :href="route(props.routeNames?.documentsShow ?? 'admin.accidents.documents.show', accident.id)" class="p-1.5 text-slate-400 hover:text-info transition" title="View documents">
                                            <Lucide icon="FileText" class="w-4 h-4" />
                                        </Link>
                                        <Link :href="route(props.routeNames?.edit ?? 'admin.accidents.edit', accident.id)" class="p-1.5 text-slate-400 hover:text-warning transition" title="Edit">
                                            <Lucide icon="PenLine" class="w-4 h-4" />
                                        </Link>
                                        <button type="button" @click="openDeleteModal(accident)" class="p-1.5 text-slate-400 hover:text-danger transition" title="Delete">
                                            <Lucide icon="Trash2" class="w-4 h-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="!accidents.data.length">
                                <td colspan="8" class="px-5 py-12 text-center text-slate-400">
                                    <Lucide icon="AlertTriangle" class="w-12 h-12 mx-auto mb-3 text-slate-300" />
                                    <p>No accident records found</p>
                                    <Link :href="route(props.routeNames?.create ?? 'admin.accidents.create')">
                                        <Button variant="outline-primary" class="mt-4 flex items-center gap-2">
                                            <Lucide icon="Plus" class="w-4 h-4" />
                                            Add First Accident
                                        </Button>
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="accidents.last_page > 1" class="p-4 border-t border-slate-200/60 flex items-center justify-between">
                    <span class="text-sm text-slate-500">{{ accidents.total }} accidents</span>
                    <div class="flex gap-1">
                        <template v-for="link in accidents.links" :key="link.label">
                            <Link v-if="link.url" :href="link.url" class="px-3 py-1 text-sm rounded" :class="link.active ? 'bg-primary text-white' : 'text-slate-600 hover:bg-slate-100'" v-html="link.label" />
                            <span v-else class="px-3 py-1 text-sm text-slate-300" v-html="link.label" />
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <Dialog :open="deleteModalOpen" @close="deleteModalOpen = false" size="lg">
        <Dialog.Panel class="w-full max-w-[600px] overflow-hidden">
            <div class="px-6 pt-6 text-center">
                <div class="mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-full border-2 border-danger text-danger">
                    <Lucide icon="X" class="h-8 w-8" />
                </div>
                <h3 class="text-[2.1rem] font-light text-slate-600">Are you sure?</h3>
                <p class="mt-3 text-base leading-7 text-slate-500">
                    Do you really want to delete this accident record?
                    <br>
                    This process cannot be undone.
                </p>
                <p v-if="selectedAccident" class="mt-4 text-sm font-medium text-slate-700">{{ selectedAccident.nature_of_accident }}</p>
            </div>
            <div class="flex justify-center gap-3 px-6 pb-8 pt-4">
                <Button type="button" variant="outline-secondary" class="min-w-24" @click="deleteModalOpen = false">Cancel</Button>
                <Button type="button" variant="danger" class="min-w-24" @click="confirmDelete">Delete</Button>
            </div>
        </Dialog.Panel>
    </Dialog>
</template>
