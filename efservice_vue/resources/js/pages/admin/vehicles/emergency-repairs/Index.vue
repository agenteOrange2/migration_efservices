<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { computed, reactive, ref } from 'vue'
import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import Litepicker from '@/components/Base/Litepicker/Litepicker.vue'
import TomSelect from '@/components/Base/TomSelect/TomSelect.vue'
import { FormInput } from '@/components/Base/Form'
import { Dialog } from '@/components/Base/Headless'
import RazeLayout from '@/layouts/RazeLayout.vue'

declare function route(name: string, params?: any): string

defineOptions({ layout: RazeLayout })

interface PaginationLink {
    url: string | null
    label: string
    active: boolean
}

interface RepairRow {
    id: number
    vehicle: { id: number; label: string; carrier_name: string | null }
    driver_name: string | null
    repair_name: string
    repair_date: string | null
    cost: string | null
    odometer: string | null
    status: string
    status_label: string
    attachments_count: number
}

const props = defineProps<{
    repairs: { data: RepairRow[]; links: PaginationLink[]; total: number; last_page: number }
    filters: { search: string; carrier_id: string; vehicle_id: string; driver_id: string; status: string; date_from: string; date_to: string }
    carriers: { id: number; name: string }[]
    vehicles: { id: number; carrier_id: number | null; carrier_name: string | null; label: string }[]
    drivers: { id: number; carrier_id: number | null; name: string; email: string | null }[]
    statusOptions: Record<string, string>
    stats: { total: number; pending: number; in_progress: number; completed: number; total_cost: number }
    contextVehicle?: { id: number; label: string } | null
    isSuperadmin: boolean
}>()

const pickerOptions = { singleMode: true, format: 'M/D/YYYY', autoApply: true }
const filters = reactive({ ...props.filters })
const deleteModalOpen = ref(false)
const selectedRecord = ref<RepairRow | null>(null)

const visibleVehicles = computed(() => {
    if (!filters.carrier_id) return props.vehicles
    return props.vehicles.filter(vehicle => String(vehicle.carrier_id ?? '') === filters.carrier_id)
})

const visibleDrivers = computed(() => {
    if (!filters.carrier_id) return props.drivers
    return props.drivers.filter(driver => String(driver.carrier_id ?? '') === filters.carrier_id)
})

function money(value: number) {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(value || 0)
}

function applyFilters() {
    router.get(route('admin.vehicles.emergency-repairs.index'), {
        search: filters.search || undefined,
        carrier_id: filters.carrier_id || undefined,
        vehicle_id: filters.vehicle_id || undefined,
        driver_id: filters.driver_id || undefined,
        status: filters.status || undefined,
        date_from: filters.date_from || undefined,
        date_to: filters.date_to || undefined,
    }, { preserveState: true, replace: true })
}

function resetFilters() {
    filters.search = ''
    if (props.isSuperadmin) {
        filters.carrier_id = ''
    }
    filters.vehicle_id = props.contextVehicle ? String(props.contextVehicle.id) : ''
    filters.driver_id = ''
    filters.status = ''
    filters.date_from = ''
    filters.date_to = ''
    applyFilters()
}

function createHref() {
    return props.contextVehicle
        ? route('admin.vehicles.emergency-repairs.create', { vehicle_id: props.contextVehicle.id })
        : route('admin.vehicles.emergency-repairs.create')
}

function statusBadge(status: string) {
    if (status === 'completed') return 'bg-primary/10 text-primary'
    if (status === 'in_progress') return 'bg-slate-200 text-slate-700'
    return 'bg-slate-100 text-slate-600'
}

function openDeleteModal(record: RepairRow) {
    selectedRecord.value = record
    deleteModalOpen.value = true
}

function confirmDelete() {
    if (!selectedRecord.value) return

    router.delete(route('admin.vehicles.emergency-repairs.destroy', selectedRecord.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            deleteModalOpen.value = false
            selectedRecord.value = null
        },
    })
}
</script>

<template>
    <Head title="Emergency Repairs" />

    <div class="grid grid-cols-12 gap-y-8 gap-x-6">
        <div class="col-span-12">
            <div class="box box--stacked p-6">
                <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                            <Lucide icon="Siren" class="w-8 h-8 text-primary" />
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-slate-800">Emergency Repairs</h1>
                            <p class="text-slate-500">Track urgent vehicle repairs, costs, files and current repair status.</p>
                            <p v-if="contextVehicle" class="text-xs text-primary mt-2">{{ contextVehicle.label }}</p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <Link v-if="contextVehicle" :href="route('admin.vehicles.show', contextVehicle.id)">
                            <Button variant="outline-secondary" class="flex items-center gap-2">
                                <Lucide icon="Truck" class="w-4 h-4" />
                                Vehicle
                            </Button>
                        </Link>
                        <Link :href="createHref()">
                            <Button variant="primary" class="flex items-center gap-2">
                                <Lucide icon="Plus" class="w-4 h-4" />
                                New Repair
                            </Button>
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12">
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-4">
                <div class="box box--stacked p-5"><p class="text-sm text-slate-500">Total</p><p class="mt-1 text-2xl font-semibold text-slate-800">{{ stats.total }}</p></div>
                <div class="box box--stacked p-5"><p class="text-sm text-slate-500">Pending</p><p class="mt-1 text-2xl font-semibold text-slate-800">{{ stats.pending }}</p></div>
                <div class="box box--stacked p-5"><p class="text-sm text-slate-500">In Progress</p><p class="mt-1 text-2xl font-semibold text-slate-800">{{ stats.in_progress }}</p></div>
                <div class="box box--stacked p-5"><p class="text-sm text-slate-500">Completed</p><p class="mt-1 text-2xl font-semibold text-slate-800">{{ stats.completed }}</p></div>
                <div class="box box--stacked p-5"><p class="text-sm text-slate-500">Total Cost</p><p class="mt-1 text-2xl font-semibold text-slate-800">{{ money(stats.total_cost) }}</p></div>
            </div>
        </div>

        <div class="col-span-12">
            <div class="box box--stacked p-5">
                <div class="grid grid-cols-1 lg:grid-cols-7 gap-4">
                    <div class="lg:col-span-2 relative">
                        <Lucide icon="Search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                        <FormInput v-model="filters.search" type="text" class="pl-10" placeholder="Search by repair, vehicle, VIN or notes..." />
                    </div>

                    <TomSelect v-if="isSuperadmin" v-model="filters.carrier_id">
                        <option value="">All carriers</option>
                        <option v-for="carrier in carriers" :key="carrier.id" :value="String(carrier.id)">{{ carrier.name }}</option>
                    </TomSelect>

                    <TomSelect v-model="filters.vehicle_id">
                        <option value="">All vehicles</option>
                        <option v-for="vehicle in visibleVehicles" :key="vehicle.id" :value="String(vehicle.id)">{{ vehicle.label }}</option>
                    </TomSelect>

                    <TomSelect v-model="filters.driver_id">
                        <option value="">All drivers</option>
                        <option v-for="driver in visibleDrivers" :key="driver.id" :value="String(driver.id)">{{ driver.name }}</option>
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

        <div class="col-span-12">
            <div class="box box--stacked overflow-hidden">
                <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200/60">
                    <div>
                        <h2 class="text-base font-semibold text-slate-800">Repair List</h2>
                        <p class="text-sm text-slate-500">{{ repairs.total }} records</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-slate-50/80">
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Repair</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Vehicle</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Driver</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Date</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Status</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Cost</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase text-center">Files</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="repair in repairs.data" :key="repair.id" class="border-b border-slate-100 hover:bg-slate-50/50 transition">
                                <td class="px-5 py-4">
                                    <p class="font-medium text-slate-800">{{ repair.repair_name }}</p>
                                    <p class="text-xs text-slate-500">Odometer: {{ repair.odometer || 'N/A' }}</p>
                                </td>
                                <td class="px-5 py-4">
                                    <p class="text-sm font-medium text-slate-800">{{ repair.vehicle.label }}</p>
                                    <p class="text-xs text-slate-500">{{ repair.vehicle.carrier_name || 'No carrier' }}</p>
                                </td>
                                <td class="px-5 py-4 text-sm text-slate-600">{{ repair.driver_name || 'No driver assigned' }}</td>
                                <td class="px-5 py-4 text-sm text-slate-600">{{ repair.repair_date || 'N/A' }}</td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium" :class="statusBadge(repair.status)">
                                        {{ repair.status_label }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-sm text-slate-600">{{ repair.cost || 'N/A' }}</td>
                                <td class="px-5 py-4 text-center text-sm text-slate-600">{{ repair.attachments_count }}</td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center justify-center gap-2">
                                        <Link :href="route('admin.vehicles.emergency-repairs.show', repair.id)" class="p-1.5 text-slate-400 hover:text-primary transition"><Lucide icon="Eye" class="w-4 h-4" /></Link>
                                        <Link :href="route('admin.vehicles.emergency-repairs.edit', repair.id)" class="p-1.5 text-slate-400 hover:text-primary transition"><Lucide icon="PenLine" class="w-4 h-4" /></Link>
                                        <button type="button" @click="openDeleteModal(repair)" class="p-1.5 text-slate-400 hover:text-danger transition"><Lucide icon="Trash2" class="w-4 h-4" /></button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="!repairs.data.length">
                                <td colspan="8" class="px-5 py-12 text-center">
                                    <Lucide icon="SirenOff" class="w-10 h-10 mx-auto text-slate-300" />
                                    <p class="mt-3 text-sm text-slate-500">No repair records matched your current filters.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="repairs.last_page > 1" class="p-4 border-t border-slate-200/60 flex items-center justify-between">
                    <span class="text-sm text-slate-500">{{ repairs.total }} total records</span>
                    <div class="flex gap-1">
                        <template v-for="link in repairs.links" :key="link.label">
                            <Link v-if="link.url" :href="link.url" class="px-3 py-1 text-sm rounded" :class="link.active ? 'bg-primary text-white' : 'text-slate-600 hover:bg-slate-100'" v-html="link.label" />
                            <span v-else class="px-3 py-1 text-sm text-slate-300" v-html="link.label" />
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <Dialog :open="deleteModalOpen" @close="deleteModalOpen = false">
        <Dialog.Panel class="w-full max-w-md overflow-hidden">
            <div class="p-6">
                <div class="flex items-start gap-4">
                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-danger/10 text-danger">
                        <Lucide icon="Trash2" class="w-5 h-5" />
                    </div>
                    <div class="flex-1">
                        <h3 class="text-lg font-semibold text-slate-800">Delete Repair</h3>
                        <p class="mt-2 text-sm text-slate-500">
                            This will permanently delete <span class="font-medium text-slate-700">{{ selectedRecord?.repair_name }}</span>.
                        </p>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <Button variant="outline-secondary" type="button" @click="deleteModalOpen = false">Cancel</Button>
                    <Button variant="danger" type="button" @click="confirmDelete">Delete</Button>
                </div>
            </div>
        </Dialog.Panel>
    </Dialog>
</template>
