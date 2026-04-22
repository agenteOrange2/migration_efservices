<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { reactive, ref } from 'vue'
import Lucide from '@/components/Base/Lucide'
import Button from '@/components/Base/Button'
import Litepicker from '@/components/Base/Litepicker/Litepicker.vue'
import TomSelect from '@/components/Base/TomSelect/TomSelect.vue'
import { FormInput } from '@/components/Base/Form'
import { Dialog } from '@/components/Base/Headless'
import RazeLayout from '@/layouts/RazeLayout.vue'

declare function route(name: string, params?: any): string

defineOptions({ layout: RazeLayout })

const lpOptions = { singleMode: true, format: 'M/D/YYYY', autoApply: true }

interface PaginationLink { url: string | null; label: string; active: boolean }
interface ConvictionRow {
    id: number
    created_at_display: string | null
    conviction_date_display: string | null
    location: string | null
    charge: string | null
    penalty: string | null
    document_count: number
    driver: { id: number; name: string; email: string | null } | null
    carrier: { id: number; name: string } | null
}

const props = defineProps<{
    convictions: { data: ConvictionRow[]; links: PaginationLink[]; total: number; last_page: number }
    drivers: { id: number; carrier_id: number | null; name: string }[]
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
        documentsShow: string
        mediaDestroy: string
        driverShow: string
    }
}>()

const filters = reactive({
    search_term: props.filters.search_term ?? '',
    carrier_filter: props.filters.carrier_filter ?? '',
    driver_filter: props.filters.driver_filter ?? '',
    date_from: props.filters.date_from ?? '',
    date_to: props.filters.date_to ?? '',
})
const deleteModalOpen = ref(false)
const selectedConviction = ref<ConvictionRow | null>(null)

function applyFilters() {
    router.get(route(props.routeNames?.index ?? 'admin.traffic.index'), {
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
    return route(props.routeNames?.index ?? 'admin.traffic.index', {
        search_term: filters.search_term || undefined,
        carrier_filter: props.isCarrierContext ? undefined : (filters.carrier_filter || undefined),
        driver_filter: filters.driver_filter || undefined,
        date_from: filters.date_from || undefined,
        date_to: filters.date_to || undefined,
        sort_field: field,
        sort_direction: direction,
    })
}

function openDeleteModal(conviction: ConvictionRow) {
    selectedConviction.value = conviction
    deleteModalOpen.value = true
}

function confirmDelete() {
    if (!selectedConviction.value) return
    router.delete(route(props.routeNames?.destroy ?? 'admin.traffic.destroy', selectedConviction.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            deleteModalOpen.value = false
            selectedConviction.value = null
        },
    })
}
</script>

<template>
    <Head title="Traffic Convictions" />

    <div class="grid grid-cols-12 gap-x-6 gap-y-10">
        <div class="col-span-12">
            <div class="box box--stacked p-6 mb-6">
                <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                            <Lucide icon="TrafficCone" class="w-8 h-8 text-primary" />
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-slate-800">Traffic Convictions</h1>
                            <p class="text-slate-500">Manage and track driver traffic convictions in the Vue admin.</p>
                        </div>
                    </div>

                    <Link :href="route(props.routeNames?.create ?? 'admin.traffic.create')">
                        <Button variant="primary" class="flex items-center gap-2">
                            <Lucide icon="Plus" class="w-4 h-4" />
                            Add Conviction
                        </Button>
                    </Link>
                </div>
            </div>

            <div class="box box--stacked p-5 mb-6">
                <div class="grid grid-cols-1 lg:grid-cols-5 gap-4">
                    <div class="lg:col-span-2 relative">
                        <Lucide icon="Search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                        <FormInput v-model="filters.search_term" type="text" class="pl-10" placeholder="Search charge, location, penalty..." />
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
                        <h2 class="text-base font-semibold text-slate-800">Traffic Records</h2>
                        <p class="text-sm text-slate-500">{{ convictions.total }} records</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-slate-50/80">
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">
                                    <Link :href="sortUrl('created_at')" class="inline-flex items-center gap-1 hover:text-slate-700">
                                        Registered
                                        <Lucide v-if="props.filters.sort_field === 'created_at'" :icon="props.filters.sort_direction === 'asc' ? 'ChevronUp' : 'ChevronDown'" class="w-3 h-3" />
                                    </Link>
                                </th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Driver</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Carrier</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">
                                    <Link :href="sortUrl('conviction_date')" class="inline-flex items-center gap-1 hover:text-slate-700">
                                        Conviction Date
                                        <Lucide v-if="props.filters.sort_field === 'conviction_date'" :icon="props.filters.sort_direction === 'asc' ? 'ChevronUp' : 'ChevronDown'" class="w-3 h-3" />
                                    </Link>
                                </th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Location</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">
                                    <Link :href="sortUrl('charge')" class="inline-flex items-center gap-1 hover:text-slate-700">
                                        Charge
                                        <Lucide v-if="props.filters.sort_field === 'charge'" :icon="props.filters.sort_direction === 'asc' ? 'ChevronUp' : 'ChevronDown'" class="w-3 h-3" />
                                    </Link>
                                </th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase text-center">Docs</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="conviction in convictions.data" :key="conviction.id" class="border-b border-slate-100 hover:bg-slate-50/50 transition">
                                <td class="px-5 py-4 text-sm text-slate-500">{{ conviction.created_at_display }}</td>
                                <td class="px-5 py-4">
                                    <div class="font-medium text-slate-700">{{ conviction.driver?.name ?? 'N/A' }}</div>
                                    <div class="text-xs text-slate-500">{{ conviction.driver?.email ?? 'No email' }}</div>
                                </td>
                                <td class="px-5 py-4 text-sm text-slate-600">{{ conviction.carrier?.name ?? 'N/A' }}</td>
                                <td class="px-5 py-4 text-sm text-slate-600">{{ conviction.conviction_date_display ?? 'N/A' }}</td>
                                <td class="px-5 py-4 text-sm text-slate-600">{{ conviction.location ?? 'N/A' }}</td>
                                <td class="px-5 py-4">
                                    <div class="font-medium text-slate-700">{{ conviction.charge ?? 'N/A' }}</div>
                                    <div class="text-xs text-slate-500">{{ conviction.penalty ?? 'No penalty info' }}</div>
                                </td>
                                <td class="px-5 py-4 text-center">
                                    <Link :href="route('admin.traffic.documents.show', conviction.id)" class="inline-flex items-center rounded-full bg-info/10 px-2.5 py-1 text-xs font-medium text-info transition hover:bg-info/20">{{ conviction.document_count }}</Link>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center justify-center gap-2">
                                        <Link v-if="conviction.driver" :href="route(props.routeNames?.driverHistory ?? 'admin.traffic.driver-history', conviction.driver.id)" class="p-1.5 text-slate-400 hover:text-info transition" title="Driver history"><Lucide icon="History" class="w-4 h-4" /></Link>
                                        <Link :href="route(props.routeNames?.documentsShow ?? 'admin.traffic.documents.show', conviction.id)" class="p-1.5 text-slate-400 hover:text-primary transition" title="Documents"><Lucide icon="Files" class="w-4 h-4" /></Link>
                                        <Link :href="route(props.routeNames?.edit ?? 'admin.traffic.edit', conviction.id)" class="p-1.5 text-slate-400 hover:text-warning transition" title="Edit"><Lucide icon="PenLine" class="w-4 h-4" /></Link>
                                        <button type="button" @click="openDeleteModal(conviction)" class="p-1.5 text-slate-400 hover:text-danger transition" title="Delete"><Lucide icon="Trash2" class="w-4 h-4" /></button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="!convictions.data.length">
                                <td colspan="8" class="px-5 py-12 text-center text-slate-400">
                                    <Lucide icon="TrafficCone" class="w-12 h-12 mx-auto mb-3 text-slate-300" />
                                    <p>No traffic convictions found</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="convictions.last_page > 1" class="p-4 border-t border-slate-200/60 flex items-center justify-between">
                    <span class="text-sm text-slate-500">{{ convictions.total }} convictions</span>
                    <div class="flex gap-1">
                        <template v-for="link in convictions.links" :key="link.label">
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
            <div class="px-6 pt-6">
                <button type="button" class="ml-auto flex rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600" @click="deleteModalOpen = false"><Lucide icon="X" class="h-5 w-5" /></button>
                <div class="pb-2 text-center">
                    <div class="mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-full border-2 border-danger text-danger"><Lucide icon="X" class="h-8 w-8" /></div>
                    <h3 class="text-[2.1rem] font-light text-slate-600">Are you sure?</h3>
                    <p class="mt-3 text-base leading-7 text-slate-500">Do you really want to delete this traffic conviction?<br>This process cannot be undone.</p>
                    <p v-if="selectedConviction" class="mt-4 text-sm font-medium text-slate-700">{{ selectedConviction.charge }}</p>
                </div>
            </div>

            <div class="flex justify-center gap-3 px-6 pb-8 pt-4">
                <Button type="button" variant="outline-secondary" class="min-w-24" @click="deleteModalOpen = false">Cancel</Button>
                <Button type="button" variant="danger" class="min-w-24" @click="confirmDelete">Delete</Button>
            </div>
        </Dialog.Panel>
    </Dialog>
</template>
