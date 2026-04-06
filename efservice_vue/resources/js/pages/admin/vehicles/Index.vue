<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { reactive, ref } from 'vue'
import Lucide from '@/components/Base/Lucide'
import Button from '@/components/Base/Button'
import TomSelect from '@/components/Base/TomSelect/TomSelect.vue'
import { FormInput } from '@/components/Base/Form'
import { Dialog } from '@/components/Base/Headless'
import RazeLayout from '@/layouts/RazeLayout.vue'

declare function route(name: string, params?: any): string

defineOptions({ layout: RazeLayout })

interface PaginationLink { url: string | null; label: string; active: boolean }

interface VehicleRow {
    id: number
    year: number
    make: string
    model: string
    type: string
    company_unit_number: string | null
    vin: string
    carrier: { id: number; name: string } | null
    driver_type_label: string
    status: string
    status_label: string
    registration_expiration_date: string | null
    annual_inspection_expiration_date: string | null
    document_count: number
    expiring_documents_count: number
    assignment_count: number
    current_assignment: { type_label: string; name: string | null; secondary: string | null; status: string } | null
    created_at: string | null
}

const props = defineProps<{
    vehicles: { data: VehicleRow[]; links: PaginationLink[]; total: number; last_page: number }
    filters: { search: string; carrier_id: string; status: string; driver_type: string; vehicle_type: string; vehicle_make: string; sort_field: string; sort_direction: string }
    carriers: { id: number; name: string }[]
    vehicleMakes: string[]
    vehicleTypes: string[]
    driverTypes: Record<string, string>
    statusOptions: Record<string, string>
    stats: { total: number; active: number; pending: number; out_of_service: number; suspended: number; unassigned: number }
    isSuperadmin: boolean
}>()

const filters = reactive({ ...props.filters })
const deleteModalOpen = ref(false)
const selectedVehicle = ref<VehicleRow | null>(null)

function applyFilters() {
    router.get(route('admin.vehicles.index'), {
        search: filters.search || undefined,
        carrier_id: filters.carrier_id || undefined,
        status: filters.status || undefined,
        driver_type: filters.driver_type || undefined,
        vehicle_type: filters.vehicle_type || undefined,
        vehicle_make: filters.vehicle_make || undefined,
        sort_field: props.filters.sort_field || undefined,
        sort_direction: props.filters.sort_direction || undefined,
    }, { preserveState: true, replace: true })
}

function resetFilters() {
    filters.search = ''
    filters.carrier_id = props.isSuperadmin ? '' : props.filters.carrier_id
    filters.status = ''
    filters.driver_type = ''
    filters.vehicle_type = ''
    filters.vehicle_make = ''
    applyFilters()
}

function sortUrl(field: string) {
    const direction = props.filters.sort_field === field && props.filters.sort_direction === 'asc' ? 'desc' : 'asc'
    return route('admin.vehicles.index', {
        search: filters.search || undefined,
        carrier_id: filters.carrier_id || undefined,
        status: filters.status || undefined,
        driver_type: filters.driver_type || undefined,
        vehicle_type: filters.vehicle_type || undefined,
        vehicle_make: filters.vehicle_make || undefined,
        sort_field: field,
        sort_direction: direction,
    })
}

function openDeleteModal(vehicle: VehicleRow) {
    selectedVehicle.value = vehicle
    deleteModalOpen.value = true
}

function confirmDelete() {
    if (!selectedVehicle.value) return

    router.delete(route('admin.vehicles.destroy', selectedVehicle.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            deleteModalOpen.value = false
            selectedVehicle.value = null
        },
    })
}
</script>

<template>
    <Head title="Vehicles" />

    <div class="grid grid-cols-12 gap-x-6 gap-y-10">
        <div class="col-span-12">
            <div class="box box--stacked p-6 mb-6">
                <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                            <Lucide icon="Truck" class="w-8 h-8 text-primary" />
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-slate-800">Vehicle Profile</h1>
                            <p class="text-slate-500">Manage fleet records, assignments and document-ready vehicle data.</p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <Link :href="route('admin.vehicles.unassigned')">
                            <Button variant="outline-secondary" class="flex items-center gap-2">
                                <Lucide icon="Unlink" class="w-4 h-4" />
                                Unassigned
                            </Button>
                        </Link>
                        <Link :href="route('admin.vehicles-documents.index')">
                            <Button variant="outline-primary" class="flex items-center gap-2">
                                <Lucide icon="Files" class="w-4 h-4" />
                                Documents Overview
                            </Button>
                        </Link>
                        <Link :href="route('admin.vehicles.create')">
                            <Button variant="primary" class="flex items-center gap-2">
                                <Lucide icon="Plus" class="w-4 h-4" />
                                Add Vehicle
                            </Button>
                        </Link>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-6 gap-4 mb-6">
                <div class="box box--stacked rounded-xl border border-dashed border-slate-300/80 p-5">
                    <p class="text-sm text-slate-500">Total</p>
                    <p class="mt-1 text-2xl font-semibold text-slate-800">{{ stats.total }}</p>
                </div>
                <div class="box box--stacked rounded-xl border border-dashed border-slate-300/80 p-5">
                    <p class="text-sm text-slate-500">Active</p>
                    <p class="mt-1 text-2xl font-semibold text-slate-800">{{ stats.active }}</p>
                </div>
                <div class="box box--stacked rounded-xl border border-dashed border-slate-300/80 p-5">
                    <p class="text-sm text-slate-500">Pending</p>
                    <p class="mt-1 text-2xl font-semibold text-slate-800">{{ stats.pending }}</p>
                </div>
                <div class="box box--stacked rounded-xl border border-dashed border-slate-300/80 p-5">
                    <p class="text-sm text-slate-500">Out of Service</p>
                    <p class="mt-1 text-2xl font-semibold text-slate-800">{{ stats.out_of_service }}</p>
                </div>
                <div class="box box--stacked rounded-xl border border-dashed border-slate-300/80 p-5">
                    <p class="text-sm text-slate-500">Suspended</p>
                    <p class="mt-1 text-2xl font-semibold text-slate-800">{{ stats.suspended }}</p>
                </div>
                <div class="box box--stacked rounded-xl border border-dashed border-slate-300/80 p-5">
                    <p class="text-sm text-slate-500">Unassigned</p>
                    <p class="mt-1 text-2xl font-semibold text-slate-800">{{ stats.unassigned }}</p>
                </div>
            </div>

            <div class="box box--stacked p-5 mb-6">
                <div class="grid grid-cols-1 lg:grid-cols-6 gap-4">
                    <div class="lg:col-span-2 relative">
                        <Lucide icon="Search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                        <FormInput v-model="filters.search" type="text" class="pl-10" placeholder="Search unit, VIN, make, model..." />
                    </div>

                    <TomSelect v-if="isSuperadmin" v-model="filters.carrier_id">
                        <option value="">All carriers</option>
                        <option v-for="carrier in carriers" :key="carrier.id" :value="String(carrier.id)">{{ carrier.name }}</option>
                    </TomSelect>
                    <div v-else class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-500 flex items-center">
                        Carrier scope locked to your account
                    </div>

                    <TomSelect v-model="filters.status">
                        <option value="">All statuses</option>
                        <option v-for="(label, key) in statusOptions" :key="key" :value="key">{{ label }}</option>
                    </TomSelect>

                    <TomSelect v-model="filters.driver_type">
                        <option value="">All driver types</option>
                        <option v-for="(label, key) in driverTypes" :key="key" :value="key">{{ label }}</option>
                    </TomSelect>

                    <TomSelect v-model="filters.vehicle_make">
                        <option value="">All makes</option>
                        <option v-for="make in vehicleMakes" :key="make" :value="make">{{ make }}</option>
                    </TomSelect>

                    <TomSelect v-model="filters.vehicle_type">
                        <option value="">All types</option>
                        <option v-for="type in vehicleTypes" :key="type" :value="type">{{ type }}</option>
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
                        <h2 class="text-base font-semibold text-slate-800">Fleet Vehicles</h2>
                        <p class="text-sm text-slate-500">{{ vehicles.total }} records</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-slate-50/80">
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">
                                    <Link :href="sortUrl('created_at')" class="inline-flex items-center gap-1 hover:text-slate-700">
                                        Added
                                        <Lucide v-if="filters.sort_field === 'created_at'" :icon="filters.sort_direction === 'asc' ? 'ChevronUp' : 'ChevronDown'" class="w-3 h-3" />
                                    </Link>
                                </th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">
                                    <Link :href="sortUrl('year')" class="inline-flex items-center gap-1 hover:text-slate-700">
                                        Vehicle
                                        <Lucide v-if="filters.sort_field === 'year'" :icon="filters.sort_direction === 'asc' ? 'ChevronUp' : 'ChevronDown'" class="w-3 h-3" />
                                    </Link>
                                </th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Carrier</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Assignment</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Status</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase text-center">Docs</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="vehicle in vehicles.data" :key="vehicle.id" class="border-b border-slate-100 hover:bg-slate-50/50 transition">
                                <td class="px-5 py-4 text-sm text-slate-500">{{ vehicle.created_at }}</td>
                                <td class="px-5 py-4">
                                    <div class="font-medium text-slate-800">{{ vehicle.year }} {{ vehicle.make }} {{ vehicle.model }}</div>
                                    <div class="text-xs text-slate-400">
                                        {{ vehicle.company_unit_number ? `Unit ${vehicle.company_unit_number}` : 'No unit number' }} · {{ vehicle.type }} · VIN {{ vehicle.vin }}
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-sm text-slate-600">{{ vehicle.carrier?.name ?? 'N/A' }}</td>
                                <td class="px-5 py-4">
                                    <div class="font-medium text-slate-700">{{ vehicle.current_assignment?.name ?? vehicle.driver_type_label }}</div>
                                    <div class="text-xs text-slate-500">{{ vehicle.current_assignment?.type_label ?? 'No active assignment' }}</div>
                                    <div v-if="vehicle.current_assignment?.secondary" class="text-xs text-slate-400">{{ vehicle.current_assignment.secondary }}</div>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium" :class="vehicle.status === 'active' ? 'bg-primary/10 text-primary' : vehicle.status === 'pending' ? 'bg-slate-100 text-slate-600' : vehicle.status === 'out_of_service' ? 'bg-danger/10 text-danger' : 'bg-slate-100 text-slate-600'">
                                        {{ vehicle.status_label }}
                                    </span>
                                    <div class="text-xs text-slate-500 mt-2">
                                        Reg: {{ vehicle.registration_expiration_date ?? 'N/A' }}
                                    </div>
                                    <div class="text-xs text-slate-400">
                                        Insp: {{ vehicle.annual_inspection_expiration_date ?? 'N/A' }}
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-center">
                                    <Link :href="route('admin.vehicles.documents.index', vehicle.id)" class="inline-flex flex-col items-center rounded-xl border border-slate-200 px-3 py-2 text-xs font-medium text-slate-600 hover:border-primary/30 hover:text-primary transition">
                                        <span>{{ vehicle.document_count }}</span>
                                        <span v-if="vehicle.expiring_documents_count" class="text-[11px] text-danger">{{ vehicle.expiring_documents_count }} expiring</span>
                                    </Link>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center justify-center gap-2">
                                        <Link :href="route('admin.vehicles.show', vehicle.id)" class="p-1.5 text-slate-400 hover:text-primary transition" title="View"><Lucide icon="Eye" class="w-4 h-4" /></Link>
                                        <Link :href="route('admin.vehicles.driver-assignment-history', vehicle.id)" class="p-1.5 text-slate-400 hover:text-primary transition" title="Assignment history"><Lucide icon="History" class="w-4 h-4" /></Link>
                                        <Link :href="route('admin.vehicles.documents.index', vehicle.id)" class="p-1.5 text-slate-400 hover:text-primary transition" title="Documents"><Lucide icon="Files" class="w-4 h-4" /></Link>
                                        <Link :href="route('admin.vehicles.edit', vehicle.id)" class="p-1.5 text-slate-400 hover:text-primary transition" title="Edit"><Lucide icon="PenLine" class="w-4 h-4" /></Link>
                                        <button type="button" @click="openDeleteModal(vehicle)" class="p-1.5 text-slate-400 hover:text-danger transition" title="Delete"><Lucide icon="Trash2" class="w-4 h-4" /></button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="!vehicles.data.length">
                                <td colspan="7" class="px-5 py-12 text-center text-slate-400">
                                    <Lucide icon="Truck" class="w-12 h-12 mx-auto mb-3 text-slate-300" />
                                    <p>No vehicles found</p>
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

    <Dialog :open="deleteModalOpen" @close="deleteModalOpen = false" size="lg">
        <Dialog.Panel class="w-full max-w-[600px] overflow-hidden">
            <div class="px-6 pt-6">
                <button type="button" class="ml-auto flex rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600" @click="deleteModalOpen = false"><Lucide icon="X" class="h-5 w-5" /></button>
                <div class="pb-2 text-center">
                    <div class="mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-full border-2 border-danger text-danger"><Lucide icon="X" class="h-8 w-8" /></div>
                    <h3 class="text-[2.1rem] font-light text-slate-600">Delete vehicle?</h3>
                    <p class="mt-3 text-base leading-7 text-slate-500">This will remove the vehicle record and its related documents.<br>This process cannot be undone.</p>
                    <p v-if="selectedVehicle" class="mt-4 text-sm font-medium text-slate-700">{{ selectedVehicle.year }} {{ selectedVehicle.make }} {{ selectedVehicle.model }}</p>
                </div>
            </div>

            <div class="flex justify-center gap-3 px-6 pb-8 pt-4">
                <button type="button" class="min-w-24 rounded-lg border border-slate-300 px-6 py-2.5 text-base font-medium text-slate-600 hover:bg-slate-50" @click="deleteModalOpen = false">Cancel</button>
                <button type="button" class="min-w-24 rounded-lg bg-danger px-6 py-2.5 text-base font-medium text-white hover:bg-danger/90" @click="confirmDelete">Delete</button>
            </div>
        </Dialog.Panel>
    </Dialog>
</template>
