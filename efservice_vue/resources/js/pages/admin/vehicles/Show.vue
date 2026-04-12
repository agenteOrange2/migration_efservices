<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import RazeLayout from '@/layouts/RazeLayout.vue'

declare function route(name: string, params?: any): string

defineOptions({ layout: RazeLayout })

const props = defineProps<{
    vehicle: {
        id: number
        title: string
        carrier: { id: number; name: string } | null
        company_unit_number: string | null
        make: string
        model: string
        type: string
        year: number
        vin: string
        gvwr: string | null
        tire_size: string | null
        fuel_type: string | null
        location: string | null
        driver_type_label: string
        status: string
        status_label: string
        status_date: string | null
        registration_state: string
        registration_number: string
        registration_expiration_date: string | null
        annual_inspection_expiration_date: string | null
        permanent_tag: boolean
        irp_apportioned_plate: boolean
        notes: string | null
        registration_is_expired: boolean
        inspection_is_expired: boolean
        documents: { id: number; document_type_label: string; document_number: string | null; expiration_date: string | null; status_label: string; file_name: string | null; preview_url: string | null }[]
        document_stats: { total: number; active: number; expired: number; pending: number }
        current_assignment: any | null
        assignment_preview: any[]
        maintenance_count: number
        repair_count: number
    }
    recentMaintenances: { id: number; service_date: string | null; next_service_date: string | null; service_tasks: string | null; vendor_mechanic: string | null; cost: string | null; odometer: string | null; status: string }[]
    recentRepairs: { id: number; repair_name: string; repair_date: string | null; cost: string | null; status: string; odometer: string | null }[]
    isCarrierContext?: boolean
    routeNames?: Partial<{
        index: string
        show: string
        edit: string
        assignmentHistory: string
        documentsIndex: string
        maintenanceIndexByVehicle: string
        maintenanceCreateByVehicle: string
        maintenanceShow: string
        repairsIndexByVehicle: string
        repairsCreateByVehicle: string
        repairsShow: string
    }>
}>()

const defaultRouteNames = {
    index: 'admin.vehicles.index',
    show: 'admin.vehicles.show',
    edit: 'admin.vehicles.edit',
    assignmentHistory: 'admin.vehicles.driver-assignment-history',
    documentsIndex: 'admin.vehicles.documents.index',
    maintenanceIndexByVehicle: 'admin.vehicles.maintenance.index',
    maintenanceCreateByVehicle: 'admin.vehicles.maintenance.create',
    maintenanceShow: 'admin.maintenance.show',
    repairsIndexByVehicle: 'admin.vehicles.repairs.index',
    repairsCreateByVehicle: 'admin.vehicles.repairs.create',
    repairsShow: 'admin.vehicles.emergency-repairs.show',
} as const

function namedRoute(name: keyof typeof defaultRouteNames, params?: any) {
    return route(props.routeNames?.[name] ?? defaultRouteNames[name], params)
}

const showMaintenanceLinks = !props.isCarrierContext || !!props.routeNames?.maintenanceIndexByVehicle
const showRepairLinks = !props.isCarrierContext || !!props.routeNames?.repairsIndexByVehicle
</script>

<template>
    <Head :title="vehicle.title" />

    <div class="grid grid-cols-12 gap-y-8 gap-x-6">
        <div class="col-span-12">
            <div class="box box--stacked p-6">
                <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-xl font-bold text-slate-800">{{ vehicle.title }}</h1>
                        <p class="text-sm text-slate-500 mt-0.5">
                            {{ vehicle.company_unit_number ? `Unit ${vehicle.company_unit_number} · ` : '' }}{{ vehicle.vin }}
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <Link :href="namedRoute('documentsIndex', vehicle.id)">
                            <Button variant="outline-primary" class="flex items-center gap-2">
                                <Lucide icon="Files" class="w-4 h-4" />
                                Documents
                            </Button>
                        </Link>
                        <Link :href="namedRoute('assignmentHistory', vehicle.id)">
                            <Button variant="outline-secondary" class="flex items-center gap-2">
                                <Lucide icon="History" class="w-4 h-4" />
                                Assignment History
                            </Button>
                        </Link>
                        <Link :href="namedRoute('edit', vehicle.id)">
                            <Button variant="primary" class="flex items-center gap-2">
                                <Lucide icon="PenLine" class="w-4 h-4" />
                                Edit
                            </Button>
                        </Link>
                        <Link :href="namedRoute('index')">
                            <Button variant="outline-secondary" class="flex items-center gap-2">
                                <Lucide icon="ArrowLeft" class="w-4 h-4" />
                                Back
                            </Button>
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12" v-if="vehicle.status === 'out_of_service' || vehicle.status === 'suspended' || vehicle.registration_is_expired || vehicle.inspection_is_expired">
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                <div v-if="vehicle.status === 'out_of_service'" class="rounded-xl border border-danger/20 bg-danger/5 px-5 py-4 text-sm text-danger">
                    <p class="font-semibold">Out of Service</p>
                    <p class="mt-1">Effective {{ vehicle.status_date ?? 'N/A' }}</p>
                </div>
                <div v-if="vehicle.status === 'suspended'" class="rounded-xl border border-danger/20 bg-danger/5 px-5 py-4 text-sm text-danger">
                    <p class="font-semibold">Suspended</p>
                    <p class="mt-1">Effective {{ vehicle.status_date ?? 'N/A' }}</p>
                </div>
                <div v-if="vehicle.registration_is_expired" class="rounded-xl border border-danger/20 bg-danger/5 px-5 py-4 text-sm text-danger">
                    <p class="font-semibold">Registration Expired</p>
                    <p class="mt-1">Expired {{ vehicle.registration_expiration_date ?? 'N/A' }}</p>
                </div>
                <div v-if="vehicle.inspection_is_expired" class="rounded-xl border border-danger/20 bg-danger/5 px-5 py-4 text-sm text-danger">
                    <p class="font-semibold">Inspection Expired</p>
                    <p class="mt-1">Expired {{ vehicle.annual_inspection_expiration_date ?? 'N/A' }}</p>
                </div>
            </div>
        </div>

        <div class="col-span-12 lg:col-span-8 space-y-6">
            <div class="box box--stacked p-6">
                <h2 class="text-sm font-semibold text-slate-700 mb-5 flex items-center gap-2">
                    <Lucide icon="BadgeInfo" class="w-4 h-4 text-primary" />
                    General Information
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4"><p class="text-xs text-slate-500">Carrier</p><p class="mt-1 text-sm font-medium text-slate-800">{{ vehicle.carrier?.name ?? 'N/A' }}</p></div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4"><p class="text-xs text-slate-500">Driver Type</p><p class="mt-1 text-sm font-medium text-slate-800">{{ vehicle.driver_type_label }}</p></div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4"><p class="text-xs text-slate-500">Status</p><p class="mt-1 text-sm font-medium text-slate-800">{{ vehicle.status_label }}</p></div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4"><p class="text-xs text-slate-500">Unit Number</p><p class="mt-1 text-sm font-medium text-slate-800">{{ vehicle.company_unit_number ?? 'N/A' }}</p></div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4"><p class="text-xs text-slate-500">Fuel Type</p><p class="mt-1 text-sm font-medium text-slate-800">{{ vehicle.fuel_type ?? 'N/A' }}</p></div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4"><p class="text-xs text-slate-500">Location</p><p class="mt-1 text-sm font-medium text-slate-800">{{ vehicle.location ?? 'N/A' }}</p></div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4"><p class="text-xs text-slate-500">GVWR</p><p class="mt-1 text-sm font-medium text-slate-800">{{ vehicle.gvwr ?? 'N/A' }}</p></div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4"><p class="text-xs text-slate-500">Tire Size</p><p class="mt-1 text-sm font-medium text-slate-800">{{ vehicle.tire_size ?? 'N/A' }}</p></div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4"><p class="text-xs text-slate-500">VIN</p><p class="mt-1 text-sm font-medium text-slate-800 break-all">{{ vehicle.vin }}</p></div>
                </div>
            </div>

            <div class="box box--stacked p-6">
                <h2 class="text-sm font-semibold text-slate-700 mb-5 flex items-center gap-2">
                    <Lucide icon="FileBadge" class="w-4 h-4 text-primary" />
                    Registration & Inspection
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4"><p class="text-xs text-slate-500">Registration State</p><p class="mt-1 text-sm font-medium text-slate-800">{{ vehicle.registration_state }}</p></div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4"><p class="text-xs text-slate-500">Registration Number</p><p class="mt-1 text-sm font-medium text-slate-800">{{ vehicle.registration_number }}</p></div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4"><p class="text-xs text-slate-500">Registration Expiration</p><p class="mt-1 text-sm font-medium text-slate-800">{{ vehicle.registration_expiration_date ?? 'N/A' }}</p></div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4"><p class="text-xs text-slate-500">Annual Inspection Expiration</p><p class="mt-1 text-sm font-medium text-slate-800">{{ vehicle.annual_inspection_expiration_date ?? 'N/A' }}</p></div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4"><p class="text-xs text-slate-500">Permanent Tag</p><p class="mt-1 text-sm font-medium text-slate-800">{{ vehicle.permanent_tag ? 'Yes' : 'No' }}</p></div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4"><p class="text-xs text-slate-500">IRP Plate</p><p class="mt-1 text-sm font-medium text-slate-800">{{ vehicle.irp_apportioned_plate ? 'Yes' : 'No' }}</p></div>
                </div>
            </div>

            <div class="box box--stacked p-6">
                <div class="flex items-center justify-between mb-5">
                    <h2 class="text-sm font-semibold text-slate-700 flex items-center gap-2">
                        <Lucide icon="Users" class="w-4 h-4 text-primary" />
                        Current Assignment
                    </h2>
                    <Link :href="namedRoute('assignmentHistory', vehicle.id)" class="text-sm text-primary hover:underline">Open full history</Link>
                </div>

                <div v-if="vehicle.current_assignment" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                        <div class="rounded-lg border border-slate-200 bg-slate-50 p-4"><p class="text-xs text-slate-500">Type</p><p class="mt-1 text-sm font-medium text-slate-800">{{ vehicle.current_assignment.driver_type_label }}</p></div>
                        <div class="rounded-lg border border-slate-200 bg-slate-50 p-4"><p class="text-xs text-slate-500">Status</p><p class="mt-1 text-sm font-medium text-slate-800">{{ vehicle.current_assignment.status_label }}</p></div>
                        <div class="rounded-lg border border-slate-200 bg-slate-50 p-4"><p class="text-xs text-slate-500">Start Date</p><p class="mt-1 text-sm font-medium text-slate-800">{{ vehicle.current_assignment.start_date ?? 'N/A' }}</p></div>
                    </div>

                    <div v-if="vehicle.current_assignment.driver" class="rounded-lg border border-slate-200 p-4">
                        <p class="text-xs text-slate-500">Assigned Driver</p>
                        <p class="mt-1 text-sm font-medium text-slate-800">{{ vehicle.current_assignment.driver.name }}</p>
                        <p class="text-xs text-slate-500">{{ vehicle.current_assignment.driver.email ?? 'No email' }}</p>
                    </div>

                    <div v-if="vehicle.current_assignment.owner_operator" class="rounded-lg border border-slate-200 p-4">
                        <p class="text-xs text-slate-500">Owner Operator</p>
                        <p class="mt-1 text-sm font-medium text-slate-800">{{ vehicle.current_assignment.owner_operator.name }}</p>
                        <p class="text-xs text-slate-500">{{ vehicle.current_assignment.owner_operator.email ?? 'No email' }} · {{ vehicle.current_assignment.owner_operator.phone ?? 'No phone' }}</p>
                    </div>

                    <div v-if="vehicle.current_assignment.third_party" class="rounded-lg border border-slate-200 p-4">
                        <p class="text-xs text-slate-500">Third Party</p>
                        <p class="mt-1 text-sm font-medium text-slate-800">{{ vehicle.current_assignment.third_party.name }}</p>
                        <p class="text-xs text-slate-500">{{ vehicle.current_assignment.third_party.email ?? 'No email' }} · {{ vehicle.current_assignment.third_party.phone ?? 'No phone' }}</p>
                    </div>

                    <div v-if="vehicle.current_assignment.notes" class="rounded-lg border border-slate-200 p-4">
                        <p class="text-xs text-slate-500">Assignment Notes</p>
                        <p class="mt-1 text-sm text-slate-700 whitespace-pre-line">{{ vehicle.current_assignment.notes }}</p>
                    </div>
                </div>

                <p v-else class="text-sm text-slate-400">This vehicle does not have an active assignment.</p>
            </div>

            <div class="box box--stacked p-6">
                <h2 class="text-sm font-semibold text-slate-700 mb-4 flex items-center gap-2">
                    <Lucide icon="StickyNote" class="w-4 h-4 text-primary" />
                    Notes
                </h2>
                <p class="text-sm text-slate-600 whitespace-pre-line">{{ vehicle.notes || 'No notes recorded for this vehicle.' }}</p>
            </div>
        </div>

        <div class="col-span-12 lg:col-span-4 space-y-6">
            <div class="box box--stacked p-6">
                <h2 class="text-sm font-semibold text-slate-700 mb-4 flex items-center gap-2">
                    <Lucide icon="Files" class="w-4 h-4 text-primary" />
                    Documents
                </h2>
                <div class="grid grid-cols-2 gap-3 mb-4">
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4 text-center"><p class="text-xs text-slate-500">Total</p><p class="mt-1 text-xl font-semibold text-slate-800">{{ vehicle.document_stats.total }}</p></div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4 text-center"><p class="text-xs text-slate-500">Active</p><p class="mt-1 text-xl font-semibold text-slate-800">{{ vehicle.document_stats.active }}</p></div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4 text-center"><p class="text-xs text-slate-500">Expired</p><p class="mt-1 text-xl font-semibold text-slate-800">{{ vehicle.document_stats.expired }}</p></div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4 text-center"><p class="text-xs text-slate-500">Pending</p><p class="mt-1 text-xl font-semibold text-slate-800">{{ vehicle.document_stats.pending }}</p></div>
                </div>
                <div v-if="vehicle.documents.length" class="space-y-3">
                    <a v-for="document in vehicle.documents" :key="document.id" :href="document.preview_url || '#'" target="_blank" class="block rounded-lg border border-slate-200 px-4 py-3 hover:bg-slate-50">
                        <p class="text-sm font-medium text-slate-800">{{ document.document_type_label }}</p>
                        <p class="text-xs text-slate-500">{{ document.file_name ?? 'No file linked' }}</p>
                        <p class="text-xs text-slate-400">{{ document.expiration_date ?? 'No expiration' }} · {{ document.status_label }}</p>
                    </a>
                </div>
                <p v-else class="text-sm text-slate-400">No vehicle documents uploaded yet.</p>
            </div>

            <div class="box box--stacked p-6">
                <div class="flex items-center justify-between mb-4 gap-3">
                    <h2 class="text-sm font-semibold text-slate-700 flex items-center gap-2">
                        <Lucide icon="Wrench" class="w-4 h-4 text-primary" />
                        Maintenance Summary
                    </h2>
                    <div v-if="showMaintenanceLinks" class="flex items-center gap-3 text-xs">
                        <Link :href="namedRoute('maintenanceIndexByVehicle', vehicle.id)" class="text-primary hover:underline">View all</Link>
                        <Link :href="namedRoute('maintenanceCreateByVehicle', vehicle.id)" class="text-primary hover:underline">Add new</Link>
                    </div>
                </div>
                <p class="text-sm text-slate-500 mb-3">{{ vehicle.maintenance_count }} maintenance record(s)</p>
                <div v-if="recentMaintenances.length" class="space-y-3">
                    <Link v-if="showMaintenanceLinks" v-for="maintenance in recentMaintenances" :key="maintenance.id" :href="namedRoute('maintenanceShow', maintenance.id)" class="block rounded-lg border border-slate-200 bg-slate-50 p-4 hover:bg-slate-100/70 transition">
                        <p class="text-sm font-medium text-slate-800">{{ maintenance.service_tasks || 'Maintenance item' }}</p>
                        <p class="text-xs text-slate-500">{{ maintenance.service_date ?? 'N/A' }} · {{ maintenance.status }}</p>
                        <p v-if="maintenance.cost" class="text-xs text-slate-400">{{ maintenance.cost }}</p>
                    </Link>
                    <div v-else v-for="maintenance in recentMaintenances" :key="maintenance.id" class="block rounded-lg border border-slate-200 bg-slate-50 p-4">
                        <p class="text-sm font-medium text-slate-800">{{ maintenance.service_tasks || 'Maintenance item' }}</p>
                        <p class="text-xs text-slate-500">{{ maintenance.service_date ?? 'N/A' }} · {{ maintenance.status }}</p>
                        <p v-if="maintenance.cost" class="text-xs text-slate-400">{{ maintenance.cost }}</p>
                    </div>
                </div>
                <div v-else class="space-y-3">
                    <p class="text-sm text-slate-400">No maintenance history available yet.</p>
                    <Link v-if="showMaintenanceLinks" :href="namedRoute('maintenanceCreateByVehicle', vehicle.id)" class="inline-flex text-sm text-primary hover:underline">Create the first maintenance record</Link>
                </div>
            </div>

            <div class="box box--stacked p-6">
                <div class="flex items-center justify-between mb-4 gap-3">
                    <h2 class="text-sm font-semibold text-slate-700 flex items-center gap-2">
                        <Lucide icon="AlertTriangle" class="w-4 h-4 text-primary" />
                        Emergency Repairs
                    </h2>
                    <div v-if="showRepairLinks" class="flex items-center gap-3 text-xs">
                        <Link :href="namedRoute('repairsIndexByVehicle', vehicle.id)" class="text-primary hover:underline">View all</Link>
                        <Link :href="namedRoute('repairsCreateByVehicle', vehicle.id)" class="text-primary hover:underline">Add new</Link>
                    </div>
                </div>
                <p class="text-sm text-slate-500 mb-3">{{ vehicle.repair_count }} repair record(s)</p>
                <div v-if="recentRepairs.length" class="space-y-3">
                    <Link v-if="showRepairLinks" v-for="repair in recentRepairs" :key="repair.id" :href="namedRoute('repairsShow', repair.id)" class="block rounded-lg border border-slate-200 bg-slate-50 p-4 hover:bg-slate-100/70 transition">
                        <p class="text-sm font-medium text-slate-800">{{ repair.repair_name }}</p>
                        <p class="text-xs text-slate-500">{{ repair.repair_date ?? 'N/A' }} · {{ repair.status }}</p>
                        <p v-if="repair.cost" class="text-xs text-slate-400">{{ repair.cost }}</p>
                    </Link>
                    <div v-else v-for="repair in recentRepairs" :key="repair.id" class="block rounded-lg border border-slate-200 bg-slate-50 p-4">
                        <p class="text-sm font-medium text-slate-800">{{ repair.repair_name }}</p>
                        <p class="text-xs text-slate-500">{{ repair.repair_date ?? 'N/A' }} · {{ repair.status }}</p>
                        <p v-if="repair.cost" class="text-xs text-slate-400">{{ repair.cost }}</p>
                    </div>
                </div>
                <div v-else class="space-y-3">
                    <p class="text-sm text-slate-400">No emergency repairs logged for this vehicle.</p>
                    <Link v-if="showRepairLinks" :href="namedRoute('repairsCreateByVehicle', vehicle.id)" class="inline-flex text-sm text-primary hover:underline">Create the first repair record</Link>
                </div>
            </div>
        </div>
    </div>
</template>
