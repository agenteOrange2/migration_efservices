<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { reactive, ref } from 'vue'
import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import TomSelect from '@/components/Base/TomSelect/TomSelect.vue'
import Litepicker from '@/components/Base/Litepicker/Litepicker.vue'
import { FormInput } from '@/components/Base/Form'
import { Dialog } from '@/components/Base/Headless'
import RazeLayout from '@/layouts/RazeLayout.vue'

declare function route(name: string, params?: any): string

defineOptions({ layout: RazeLayout })

interface PaginationLink { url: string | null; label: string; active: boolean }
interface MaintenanceRow {
    id: number
    vehicle: { id: number; label: string; carrier_name: string | null }
    unit: string
    service_tasks: string
    vendor_mechanic: string | null
    service_date: string | null
    next_service_date: string | null
    cost: string | null
    odometer: string | null
    status: string
    status_label: string
    is_historical: boolean
    attachments_count: number
}

const props = defineProps<{
    maintenances: { data: MaintenanceRow[]; links: PaginationLink[]; total: number; last_page: number }
    filters: { search: string; carrier_id: string; vehicle_id: string; status: string; date_from: string; date_to: string }
    carriers: { id: number; name: string }[]
    vehicles: { id: number; carrier_id: number | null; carrier_name: string | null; label: string }[]
    statusOptions: Record<string, string>
    stats: { total: number; pending: number; completed: number; overdue: number; upcoming: number; historical: number }
    overdueItems: { id: number; title: string; vehicle_label: string; service_date: string | null; next_service_date: string | null; status_label: string; show_url: string }[]
    upcomingItems: { id: number; title: string; vehicle_label: string; service_date: string | null; next_service_date: string | null; status_label: string; show_url: string }[]
    contextVehicle?: { id: number; label: string } | null
    isSuperadmin: boolean
}>()

const pickerOptions = { singleMode: true, format: 'M/D/YYYY', autoApply: true }
const filters = reactive({ ...props.filters })
const deleteModalOpen = ref(false)
const selectedRecord = ref<MaintenanceRow | null>(null)

function applyFilters() {
    router.get(route('admin.maintenance.index'), {
        search: filters.search || undefined,
        carrier_id: filters.carrier_id || undefined,
        vehicle_id: filters.vehicle_id || undefined,
        status: filters.status || undefined,
        date_from: filters.date_from || undefined,
        date_to: filters.date_to || undefined,
    }, { preserveState: true, replace: true })
}

function resetFilters() {
    filters.search = ''
    filters.status = ''
    filters.date_from = ''
    filters.date_to = ''
    if (props.isSuperadmin) filters.carrier_id = ''
    filters.vehicle_id = props.contextVehicle ? String(props.contextVehicle.id) : ''
    applyFilters()
}

function createHref() {
    return props.contextVehicle
        ? route('admin.maintenance.create', { vehicle_id: props.contextVehicle.id })
        : route('admin.maintenance.create')
}

function calendarHref() {
    return route('admin.maintenance.calendar', {
        vehicle_id: props.contextVehicle?.id || undefined,
    })
}

function reportsHref() {
    return route('admin.maintenance.reports', {
        vehicle_id: props.contextVehicle?.id || undefined,
    })
}

function openDeleteModal(record: MaintenanceRow) {
    selectedRecord.value = record
    deleteModalOpen.value = true
}

function confirmDelete() {
    if (!selectedRecord.value) return

    router.delete(route('admin.maintenance.destroy', selectedRecord.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            deleteModalOpen.value = false
            selectedRecord.value = null
        },
    })
}

function statusBadge(status: string) {
    if (status === 'completed') return 'bg-primary/10 text-primary'
    if (status === 'overdue') return 'bg-danger/10 text-danger'
    if (status === 'upcoming') return 'bg-primary/15 text-primary'
    return 'bg-slate-100 text-slate-600'
}
</script>

<template>
    <Head title="Maintenance Records" />

    <div class="grid grid-cols-12 gap-y-8 gap-x-6">
        <div class="col-span-12">
            <div class="box box--stacked p-6">
                <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                            <Lucide icon="Wrench" class="w-8 h-8 text-primary" />
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-slate-800">Maintenance Records</h1>
                            <p class="text-slate-500">Track preventive service, overdue items and supporting files.</p>
                            <p v-if="contextVehicle" class="text-xs text-primary mt-2">{{ contextVehicle.label }}</p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <Link :href="reportsHref()">
                            <Button variant="outline-secondary" class="flex items-center gap-2">
                                <Lucide icon="BarChart3" class="w-4 h-4" />
                                Reports
                            </Button>
                        </Link>
                        <Link :href="calendarHref()">
                            <Button variant="outline-secondary" class="flex items-center gap-2">
                                <Lucide icon="Calendar" class="w-4 h-4" />
                                Calendar
                            </Button>
                        </Link>
                        <Link :href="createHref()">
                            <Button variant="primary" class="flex items-center gap-2">
                                <Lucide icon="Plus" class="w-4 h-4" />
                                New Maintenance
                            </Button>
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12">
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-6 gap-4">
                <div class="box box--stacked p-5"><p class="text-sm text-slate-500">Total</p><p class="mt-1 text-2xl font-semibold text-slate-800">{{ stats.total }}</p></div>
                <div class="box box--stacked p-5"><p class="text-sm text-slate-500">Pending</p><p class="mt-1 text-2xl font-semibold text-slate-800">{{ stats.pending }}</p></div>
                <div class="box box--stacked p-5"><p class="text-sm text-slate-500">Completed</p><p class="mt-1 text-2xl font-semibold text-slate-800">{{ stats.completed }}</p></div>
                <div class="box box--stacked p-5"><p class="text-sm text-slate-500">Overdue</p><p class="mt-1 text-2xl font-semibold text-slate-800">{{ stats.overdue }}</p></div>
                <div class="box box--stacked p-5"><p class="text-sm text-slate-500">Upcoming</p><p class="mt-1 text-2xl font-semibold text-slate-800">{{ stats.upcoming }}</p></div>
                <div class="box box--stacked p-5"><p class="text-sm text-slate-500">Historical</p><p class="mt-1 text-2xl font-semibold text-slate-800">{{ stats.historical }}</p></div>
            </div>
        </div>

        <div class="col-span-12">
            <div class="box box--stacked p-5">
                <div class="grid grid-cols-1 lg:grid-cols-6 gap-4">
                    <div class="lg:col-span-2 relative">
                        <Lucide icon="Search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                        <FormInput v-model="filters.search" type="text" class="pl-10" placeholder="Search by service, unit, vendor or vehicle..." />
                    </div>

                    <TomSelect v-if="isSuperadmin" v-model="filters.carrier_id">
                        <option value="">All carriers</option>
                        <option v-for="carrier in carriers" :key="carrier.id" :value="String(carrier.id)">{{ carrier.name }}</option>
                    </TomSelect>

                    <TomSelect v-model="filters.vehicle_id">
                        <option value="">All vehicles</option>
                        <option v-for="vehicle in vehicles" :key="vehicle.id" :value="String(vehicle.id)">{{ vehicle.label }}</option>
                    </TomSelect>

                    <TomSelect v-model="filters.status">
                        <option value="">All statuses</option>
                        <option v-for="(label, key) in statusOptions" :key="key" :value="key">{{ label }}</option>
                    </TomSelect>

                    <Litepicker v-model="filters.date_from" :options="pickerOptions" />
                    <Litepicker v-model="filters.date_to" :options="pickerOptions" />
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
        </div>

        <div class="col-span-12 xl:col-span-8">
            <div class="box box--stacked overflow-hidden">
                <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200/60">
                    <div>
                        <h2 class="text-base font-semibold text-slate-800">Maintenance List</h2>
                        <p class="text-sm text-slate-500">{{ maintenances.total }} records</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-slate-50/80">
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Service</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Vehicle</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Dates</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Status</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase text-center">Files</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="record in maintenances.data" :key="record.id" class="border-b border-slate-100 hover:bg-slate-50/50 transition">
                                <td class="px-5 py-4">
                                    <p class="font-medium text-slate-800">{{ record.service_tasks }}</p>
                                    <p class="text-xs text-slate-500">{{ record.vendor_mechanic || 'No vendor' }}</p>
                                    <p v-if="record.cost" class="text-xs text-slate-400">{{ record.cost }}</p>
                                </td>
                                <td class="px-5 py-4">
                                    <p class="text-sm font-medium text-slate-800">{{ record.vehicle.label }}</p>
                                    <p class="text-xs text-slate-500">{{ record.vehicle.carrier_name || 'No carrier' }}</p>
                                </td>
                                <td class="px-5 py-4 text-sm text-slate-600">
                                    <p>Service: {{ record.service_date || 'N/A' }}</p>
                                    <p class="text-xs text-slate-500">Next: {{ record.next_service_date || 'N/A' }}</p>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium" :class="statusBadge(record.status)">
                                        {{ record.status_label }}
                                    </span>
                                    <p v-if="record.is_historical" class="text-[11px] text-slate-400 mt-2">Historical</p>
                                </td>
                                <td class="px-5 py-4 text-center text-sm text-slate-600">{{ record.attachments_count }}</td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center justify-center gap-2">
                                        <Link :href="route('admin.maintenance.show', record.id)" class="p-1.5 text-slate-400 hover:text-primary transition"><Lucide icon="Eye" class="w-4 h-4" /></Link>
                                        <Link :href="route('admin.maintenance.edit', record.id)" class="p-1.5 text-slate-400 hover:text-primary transition"><Lucide icon="PenLine" class="w-4 h-4" /></Link>
                                        <button type="button" @click="openDeleteModal(record)" class="p-1.5 text-slate-400 hover:text-danger transition"><Lucide icon="Trash2" class="w-4 h-4" /></button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="!maintenances.data.length">
                                <td colspan="6" class="px-5 py-12 text-center text-slate-400">
                                    <Lucide icon="Wrench" class="w-12 h-12 mx-auto mb-3 text-slate-300" />
                                    <p>No maintenance records found.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="maintenances.last_page > 1" class="p-4 border-t border-slate-200/60 flex items-center justify-between">
                    <span class="text-sm text-slate-500">{{ maintenances.total }} total records</span>
                    <div class="flex gap-1">
                        <template v-for="link in maintenances.links" :key="link.label">
                            <Link v-if="link.url" :href="link.url" class="px-3 py-1 text-sm rounded" :class="link.active ? 'bg-primary text-white' : 'text-slate-600 hover:bg-slate-100'" v-html="link.label" />
                            <span v-else class="px-3 py-1 text-sm text-slate-300" v-html="link.label" />
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12 xl:col-span-4 space-y-6">
            <div class="box box--stacked p-5">
                <div class="flex items-center gap-2 mb-4">
                    <Lucide icon="AlertTriangle" class="w-4 h-4 text-danger" />
                    <h2 class="text-sm font-semibold text-slate-700">Overdue</h2>
                </div>

                <div class="space-y-3">
                    <Link v-for="item in overdueItems" :key="item.id" :href="item.show_url" class="block rounded-lg border border-slate-200 px-4 py-3 hover:bg-slate-50">
                        <p class="text-sm font-medium text-slate-800">{{ item.title }}</p>
                        <p class="text-xs text-slate-500 mt-1">{{ item.vehicle_label }}</p>
                        <p class="text-xs text-danger mt-1">Due {{ item.next_service_date || 'N/A' }}</p>
                    </Link>
                    <div v-if="!overdueItems.length" class="rounded-lg bg-slate-50 px-4 py-3 text-sm text-slate-500">No overdue items right now.</div>
                </div>
            </div>

            <div class="box box--stacked p-5">
                <div class="flex items-center gap-2 mb-4">
                    <Lucide icon="Clock3" class="w-4 h-4 text-primary" />
                    <h2 class="text-sm font-semibold text-slate-700">Upcoming</h2>
                </div>

                <div class="space-y-3">
                    <Link v-for="item in upcomingItems" :key="item.id" :href="item.show_url" class="block rounded-lg border border-slate-200 px-4 py-3 hover:bg-slate-50">
                        <p class="text-sm font-medium text-slate-800">{{ item.title }}</p>
                        <p class="text-xs text-slate-500 mt-1">{{ item.vehicle_label }}</p>
                        <p class="text-xs text-primary mt-1">Due {{ item.next_service_date || 'N/A' }}</p>
                    </Link>
                    <div v-if="!upcomingItems.length" class="rounded-lg bg-slate-50 px-4 py-3 text-sm text-slate-500">No upcoming items scheduled.</div>
                </div>
            </div>
        </div>
    </div>

    <Dialog :open="deleteModalOpen" @close="deleteModalOpen = false">
        <Dialog.Panel class="w-full max-w-[520px] overflow-hidden">
            <div class="px-6 pt-6">
                <button type="button" class="ml-auto flex rounded-lg p-1.5 text-slate-400 hover:bg-slate-100" @click="deleteModalOpen = false">
                    <Lucide icon="X" class="h-5 w-5" />
                </button>
                <div class="pb-2 text-center">
                    <div class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-full border-2 border-danger text-danger">
                        <Lucide icon="AlertTriangle" class="h-7 w-7" />
                    </div>
                    <h3 class="text-2xl font-light text-slate-600">Delete maintenance?</h3>
                    <p class="mt-3 text-sm text-slate-500">This will permanently remove the maintenance record and its attachments.</p>
                </div>
            </div>

            <div class="flex justify-center gap-3 px-6 pb-8 pt-4">
                <button type="button" class="min-w-24 rounded-lg border border-slate-300 px-6 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50" @click="deleteModalOpen = false">Cancel</button>
                <button type="button" class="min-w-24 rounded-lg bg-danger px-6 py-2.5 text-sm font-medium text-white hover:bg-danger/90" @click="confirmDelete">Delete</button>
            </div>
        </Dialog.Panel>
    </Dialog>
</template>
