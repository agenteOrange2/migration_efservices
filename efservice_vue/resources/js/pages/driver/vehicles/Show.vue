<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import RazeLayout from '@/layouts/RazeLayout.vue'

declare function route(name: string, params?: any): string

defineOptions({ layout: RazeLayout })

interface DocumentRow {
    id: number
    document_type_label: string
    document_number: string | null
    status_label: string
    expiration_date: string | null
    is_expired: boolean
    is_expiring_soon: boolean
    file_name: string | null
    preview_url: string | null
    download_url: string | null
    file_size: string | null
    can_preview: boolean
}

interface MaintenanceRow {
    id: number
    service_tasks: string | null
    service_date: string | null
    next_service_date: string | null
    vendor_mechanic: string | null
    cost: string | null
    status_label: string
}

interface RepairRow {
    id: number
    repair_name: string
    repair_date: string | null
    cost: string | null
    status_label: string
}

const props = defineProps<{
    driver: {
        id: number
        full_name: string
        carrier_name: string | null
    }
    vehicle: {
        id: number
        title: string
        carrier_name: string | null
        company_unit_number: string | null
        make: string | null
        model: string | null
        year: number | null
        type: string | null
        vin: string | null
        gvwr: string | null
        fuel_type: string | null
        tire_size: string | null
        location: string | null
        status: string | null
        status_label: string
        driver_type_label: string
        registration_state: string | null
        registration_number: string | null
        registration_expiration_date: string | null
        annual_inspection_expiration_date: string | null
        permanent_tag: boolean
        irp_apportioned_plate: boolean
        notes: string | null
        assignment: {
            status_label: string
            start_date: string | null
            driver_name: string | null
            driver_email: string | null
            owner_operator_name: string | null
            third_party_name: string | null
            notes: string | null
        } | null
        document_stats: {
            total: number
            expired: number
            expiring_soon: number
        }
    }
    documents: DocumentRow[]
    maintenance: {
        overdue: MaintenanceRow[]
        upcoming: MaintenanceRow[]
        recent: MaintenanceRow[]
        count: number
    }
    repairs: {
        recent: RepairRow[]
        count: number
    }
}>()

function documentBadge(document: DocumentRow) {
    if (document.is_expired) return 'bg-slate-200 text-slate-700'
    if (document.is_expiring_soon) return 'bg-slate-100 text-slate-700'
    return 'bg-primary/10 text-primary'
}
</script>

<template>
    <Head :title="vehicle.title" />

    <div class="grid grid-cols-12 gap-6">
        <div class="col-span-12">
            <div class="box box--stacked p-6">
                <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
                    <div class="flex items-start gap-4">
                        <Link :href="route('driver.vehicles.index')" class="mt-1 p-2 rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50">
                            <Lucide icon="ArrowLeft" class="w-4 h-4" />
                        </Link>
                        <div>
                            <h1 class="text-2xl font-bold text-slate-800">{{ vehicle.title }}</h1>
                            <p class="text-slate-500 mt-1">
                                {{ vehicle.company_unit_number ? `Unit ${vehicle.company_unit_number} · ` : '' }}{{ vehicle.vin || 'No VIN' }}
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <Link :href="route('driver.maintenance.index')">
                            <Button variant="outline-secondary" class="flex items-center gap-2">
                                <Lucide icon="Wrench" class="w-4 h-4" />
                                Maintenance
                            </Button>
                        </Link>
                        <Link :href="route('driver.emergency-repairs.index')">
                            <Button variant="outline-secondary" class="flex items-center gap-2">
                                <Lucide icon="AlertTriangle" class="w-4 h-4" />
                                Repairs
                            </Button>
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12 lg:col-span-8 space-y-6">
            <div class="box box--stacked p-6">
                <h2 class="text-sm font-semibold text-slate-700 mb-5 flex items-center gap-2">
                    <Lucide icon="Truck" class="w-4 h-4 text-primary" />
                    Vehicle Information
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4"><p class="text-xs text-slate-500">Carrier</p><p class="mt-1 text-sm font-medium text-slate-800">{{ vehicle.carrier_name || 'N/A' }}</p></div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4"><p class="text-xs text-slate-500">Status</p><p class="mt-1 text-sm font-medium text-slate-800">{{ vehicle.status_label }}</p></div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4"><p class="text-xs text-slate-500">Driver Type</p><p class="mt-1 text-sm font-medium text-slate-800">{{ vehicle.driver_type_label }}</p></div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4"><p class="text-xs text-slate-500">Unit Number</p><p class="mt-1 text-sm font-medium text-slate-800">{{ vehicle.company_unit_number || 'N/A' }}</p></div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4"><p class="text-xs text-slate-500">Type</p><p class="mt-1 text-sm font-medium text-slate-800">{{ vehicle.type || 'N/A' }}</p></div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4"><p class="text-xs text-slate-500">Location</p><p class="mt-1 text-sm font-medium text-slate-800">{{ vehicle.location || 'N/A' }}</p></div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4"><p class="text-xs text-slate-500">Make</p><p class="mt-1 text-sm font-medium text-slate-800">{{ vehicle.make || 'N/A' }}</p></div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4"><p class="text-xs text-slate-500">Model</p><p class="mt-1 text-sm font-medium text-slate-800">{{ vehicle.model || 'N/A' }}</p></div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4"><p class="text-xs text-slate-500">Year</p><p class="mt-1 text-sm font-medium text-slate-800">{{ vehicle.year || 'N/A' }}</p></div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4"><p class="text-xs text-slate-500">VIN</p><p class="mt-1 text-sm font-medium text-slate-800 break-all">{{ vehicle.vin || 'N/A' }}</p></div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4"><p class="text-xs text-slate-500">GVWR</p><p class="mt-1 text-sm font-medium text-slate-800">{{ vehicle.gvwr || 'N/A' }}</p></div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4"><p class="text-xs text-slate-500">Fuel Type</p><p class="mt-1 text-sm font-medium text-slate-800">{{ vehicle.fuel_type || 'N/A' }}</p></div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4"><p class="text-xs text-slate-500">Tire Size</p><p class="mt-1 text-sm font-medium text-slate-800">{{ vehicle.tire_size || 'N/A' }}</p></div>
                </div>
            </div>

            <div class="box box--stacked p-6">
                <h2 class="text-sm font-semibold text-slate-700 mb-5 flex items-center gap-2">
                    <Lucide icon="FileBadge" class="w-4 h-4 text-primary" />
                    Registration & Inspection
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4"><p class="text-xs text-slate-500">Registration State</p><p class="mt-1 text-sm font-medium text-slate-800">{{ vehicle.registration_state || 'N/A' }}</p></div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4"><p class="text-xs text-slate-500">Registration Number</p><p class="mt-1 text-sm font-medium text-slate-800">{{ vehicle.registration_number || 'N/A' }}</p></div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4"><p class="text-xs text-slate-500">Registration Expiration</p><p class="mt-1 text-sm font-medium text-slate-800">{{ vehicle.registration_expiration_date || 'N/A' }}</p></div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4"><p class="text-xs text-slate-500">Annual Inspection</p><p class="mt-1 text-sm font-medium text-slate-800">{{ vehicle.annual_inspection_expiration_date || 'N/A' }}</p></div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4"><p class="text-xs text-slate-500">Permanent Tag</p><p class="mt-1 text-sm font-medium text-slate-800">{{ vehicle.permanent_tag ? 'Yes' : 'No' }}</p></div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4"><p class="text-xs text-slate-500">IRP Plate</p><p class="mt-1 text-sm font-medium text-slate-800">{{ vehicle.irp_apportioned_plate ? 'Yes' : 'No' }}</p></div>
                </div>
            </div>

            <div class="box box--stacked">
                <div class="p-5 border-b border-slate-200/80 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-slate-700 flex items-center gap-2">
                        <Lucide icon="Files" class="w-4 h-4 text-primary" />
                        Vehicle Documents
                    </h2>
                    <span class="text-xs text-slate-500">{{ documents.length }} file(s)</span>
                </div>

                <div class="p-5">
                    <div v-if="documents.length" class="space-y-3">
                        <div v-for="document in documents" :key="document.id" class="rounded-lg border border-slate-200 px-4 py-3">
                            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="text-sm font-medium text-slate-800">{{ document.document_type_label }}</p>
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium" :class="documentBadge(document)">
                                            {{ document.status_label }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-slate-500 mt-1">
                                        {{ document.document_number || 'No number' }}<span v-if="document.expiration_date"> · Exp {{ document.expiration_date }}</span>
                                    </p>
                                    <p class="text-xs text-slate-400 mt-1">{{ document.file_name || 'No file linked' }}<span v-if="document.file_size"> · {{ document.file_size }}</span></p>
                                </div>

                                <div class="flex items-center gap-2">
                                    <a
                                        v-if="document.preview_url"
                                        :href="document.preview_url"
                                        target="_blank"
                                        class="inline-flex items-center gap-2 px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-600 hover:bg-slate-50"
                                    >
                                        <Lucide icon="Eye" class="w-4 h-4" />
                                        Preview
                                    </a>
                                    <a
                                        v-if="document.download_url"
                                        :href="document.download_url"
                                        target="_blank"
                                        class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-primary text-white text-sm hover:bg-primary/90"
                                    >
                                        <Lucide icon="Download" class="w-4 h-4" />
                                        Download
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-else class="rounded-lg bg-slate-50 px-4 py-6 text-center text-sm text-slate-500">
                        No vehicle documents are available right now.
                    </div>
                </div>
            </div>

            <div class="box box--stacked p-6">
                <div class="flex items-center justify-between mb-5">
                    <h2 class="text-sm font-semibold text-slate-700 flex items-center gap-2">
                        <Lucide icon="Wrench" class="w-4 h-4 text-primary" />
                        Vehicle Maintenance
                    </h2>
                    <Link :href="route('driver.maintenance.index')" class="text-sm text-primary hover:underline">View all</Link>
                </div>

                <div class="space-y-5">
                    <div v-if="maintenance.overdue.length">
                        <h3 class="text-sm font-semibold text-slate-800 mb-3">Overdue</h3>
                        <div class="space-y-2">
                            <Link v-for="item in maintenance.overdue" :key="item.id" :href="route('driver.maintenance.show', item.id)" class="block rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 hover:bg-slate-100/80">
                                <p class="text-sm font-medium text-slate-800">{{ item.service_tasks }}</p>
                                <p class="text-xs text-slate-500 mt-1">Due {{ item.next_service_date || 'N/A' }}</p>
                            </Link>
                        </div>
                    </div>

                    <div v-if="maintenance.upcoming.length">
                        <h3 class="text-sm font-semibold text-slate-800 mb-3">Upcoming</h3>
                        <div class="space-y-2">
                            <Link v-for="item in maintenance.upcoming" :key="item.id" :href="route('driver.maintenance.show', item.id)" class="block rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 hover:bg-slate-100/80">
                                <p class="text-sm font-medium text-slate-800">{{ item.service_tasks }}</p>
                                <p class="text-xs text-slate-500 mt-1">Due {{ item.next_service_date || 'N/A' }}</p>
                            </Link>
                        </div>
                    </div>

                    <div v-if="maintenance.recent.length">
                        <h3 class="text-sm font-semibold text-slate-800 mb-3">Recently Completed</h3>
                        <div class="space-y-2">
                            <Link v-for="item in maintenance.recent" :key="item.id" :href="route('driver.maintenance.show', item.id)" class="block rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 hover:bg-slate-100/80">
                                <p class="text-sm font-medium text-slate-800">{{ item.service_tasks }}</p>
                                <p class="text-xs text-slate-500 mt-1">Completed {{ item.service_date || 'N/A' }}</p>
                            </Link>
                        </div>
                    </div>

                    <div v-if="!maintenance.overdue.length && !maintenance.upcoming.length && !maintenance.recent.length" class="rounded-lg bg-slate-50 px-4 py-6 text-center text-sm text-slate-500">
                        No maintenance history is available for this vehicle.
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12 lg:col-span-4 space-y-6">
            <div class="box box--stacked p-6">
                <h2 class="text-sm font-semibold text-slate-700 mb-4 flex items-center gap-2">
                    <Lucide icon="BadgeInfo" class="w-4 h-4 text-primary" />
                    Assignment
                </h2>

                <div v-if="vehicle.assignment" class="space-y-3 text-sm">
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs text-slate-500">Status</p>
                        <p class="mt-1 font-medium text-slate-800">{{ vehicle.assignment.status_label }}</p>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs text-slate-500">Start Date</p>
                        <p class="mt-1 font-medium text-slate-800">{{ vehicle.assignment.start_date || 'N/A' }}</p>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs text-slate-500">Driver</p>
                        <p class="mt-1 font-medium text-slate-800">{{ vehicle.assignment.driver_name || 'Assigned to you' }}</p>
                        <p v-if="vehicle.assignment.driver_email" class="text-xs text-slate-500 mt-1">{{ vehicle.assignment.driver_email }}</p>
                    </div>
                    <div v-if="vehicle.assignment.notes" class="rounded-lg border border-slate-200 p-4">
                        <p class="text-xs text-slate-500">Notes</p>
                        <p class="mt-1 text-sm text-slate-700 whitespace-pre-line">{{ vehicle.assignment.notes }}</p>
                    </div>
                </div>

                <div v-else class="rounded-lg bg-slate-50 px-4 py-6 text-center text-sm text-slate-500">
                    No active assignment details were found.
                </div>
            </div>

            <div class="box box--stacked p-6">
                <h2 class="text-sm font-semibold text-slate-700 mb-4 flex items-center gap-2">
                    <Lucide icon="Files" class="w-4 h-4 text-primary" />
                    Document Summary
                </h2>
                <div class="grid grid-cols-3 gap-3">
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4 text-center"><p class="text-xs text-slate-500">Total</p><p class="mt-1 text-lg font-semibold text-slate-800">{{ vehicle.document_stats.total }}</p></div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4 text-center"><p class="text-xs text-slate-500">Expired</p><p class="mt-1 text-lg font-semibold text-slate-800">{{ vehicle.document_stats.expired }}</p></div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4 text-center"><p class="text-xs text-slate-500">Soon</p><p class="mt-1 text-lg font-semibold text-slate-800">{{ vehicle.document_stats.expiring_soon }}</p></div>
                </div>
            </div>

            <div class="box box--stacked p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-sm font-semibold text-slate-700 flex items-center gap-2">
                        <Lucide icon="AlertTriangle" class="w-4 h-4 text-primary" />
                        Emergency Repairs
                    </h2>
                    <Link :href="route('driver.emergency-repairs.index')" class="text-sm text-primary hover:underline">View all</Link>
                </div>

                <div v-if="repairs.recent.length" class="space-y-3">
                    <Link v-for="repair in repairs.recent" :key="repair.id" :href="route('driver.emergency-repairs.show', repair.id)" class="block rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 hover:bg-slate-100/80">
                        <p class="text-sm font-medium text-slate-800">{{ repair.repair_name }}</p>
                        <p class="text-xs text-slate-500 mt-1">{{ repair.repair_date || 'N/A' }} · {{ repair.status_label }}</p>
                        <p v-if="repair.cost" class="text-xs text-slate-400 mt-1">{{ repair.cost }}</p>
                    </Link>
                </div>

                <div v-else class="rounded-lg bg-slate-50 px-4 py-6 text-center text-sm text-slate-500">
                    No emergency repairs have been logged for this vehicle.
                </div>
            </div>

            <div class="box box--stacked p-6">
                <h2 class="text-sm font-semibold text-slate-700 mb-4 flex items-center gap-2">
                    <Lucide icon="Navigation" class="w-4 h-4 text-primary" />
                    Quick Actions
                </h2>
                <div class="space-y-3">
                    <Link :href="route('driver.maintenance.create', { vehicle_id: vehicle.id })" class="flex items-center justify-between rounded-lg bg-slate-50 border border-slate-200 px-4 py-3 hover:bg-slate-100/80">
                        <span class="text-sm font-medium text-slate-700">Create Maintenance</span>
                        <Lucide icon="ChevronRight" class="w-4 h-4 text-slate-400" />
                    </Link>
                    <Link :href="route('driver.emergency-repairs.create', { vehicle_id: vehicle.id })" class="flex items-center justify-between rounded-lg bg-slate-50 border border-slate-200 px-4 py-3 hover:bg-slate-100/80">
                        <span class="text-sm font-medium text-slate-700">Report Repair</span>
                        <Lucide icon="ChevronRight" class="w-4 h-4 text-slate-400" />
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>
