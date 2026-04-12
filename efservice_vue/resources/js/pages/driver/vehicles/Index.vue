<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { computed } from 'vue'
import Lucide from '@/components/Base/Lucide'
import RazeLayout from '@/layouts/RazeLayout.vue'

declare function route(name: string, params?: any): string

defineOptions({ layout: RazeLayout })

interface VehicleRow {
    id: number
    title: string
    unit_number: string | null
    vin: string | null
    type: string | null
    status: string | null
    status_label: string
    carrier_name: string | null
    location: string | null
    registration_expiration_date: string | null
    annual_inspection_expiration_date: string | null
    documents_count: number
    maintenance_count: number
    repair_count: number
    needs_attention: boolean
}

const props = defineProps<{
    driver: {
        id: number
        full_name: string
        carrier_name: string | null
    }
    stats: {
        total: number
        active: number
        attention_needed: number
        documents: number
    }
    vehicles: VehicleRow[]
}>()

const statCards = computed(() => [
    { label: 'Assigned Vehicles', value: props.stats.total, icon: 'Truck', className: 'bg-primary/10 text-primary' },
    { label: 'Active', value: props.stats.active, icon: 'BadgeCheck', className: 'bg-primary/10 text-primary' },
    { label: 'Need Attention', value: props.stats.attention_needed, icon: 'AlertTriangle', className: 'bg-slate-200 text-slate-700' },
    { label: 'Documents', value: props.stats.documents, icon: 'Files', className: 'bg-slate-100 text-slate-700' },
])

function statusClass(vehicle: VehicleRow) {
    if (vehicle.status === 'out_of_service' || vehicle.status === 'suspended') {
        return 'bg-slate-200 text-slate-700'
    }

    return vehicle.needs_attention ? 'bg-slate-200 text-slate-700' : 'bg-primary/10 text-primary'
}
</script>

<template>
    <Head title="My Vehicles" />

    <div class="grid grid-cols-12 gap-6">
        <div class="col-span-12">
            <div class="box box--stacked p-6">
                <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                            <Lucide icon="Truck" class="w-8 h-8 text-primary" />
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-slate-800">My Vehicles</h1>
                            <p class="text-slate-500">Review your assigned vehicles, documents and service activity.</p>
                            <p class="text-xs text-slate-400 mt-2">{{ driver.carrier_name || 'No carrier assigned' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12">
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                <div v-for="card in statCards" :key="card.label" class="box box--stacked p-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-slate-500">{{ card.label }}</p>
                            <p class="mt-1 text-2xl font-semibold text-slate-800">{{ card.value }}</p>
                        </div>
                        <div class="h-11 w-11 rounded-xl flex items-center justify-center" :class="card.className">
                            <Lucide :icon="card.icon as any" class="w-5 h-5" />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="vehicles.length" class="col-span-12">
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                <Link
                    v-for="vehicle in vehicles"
                    :key="vehicle.id"
                    :href="route('driver.vehicles.show', vehicle.id)"
                    class="box box--stacked p-6 hover:-translate-y-0.5 hover:shadow-lg transition"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                            <Lucide icon="Truck" class="w-7 h-7 text-primary" />
                        </div>
                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium" :class="statusClass(vehicle)">
                            {{ vehicle.status_label }}
                        </span>
                    </div>

                    <div class="mt-5">
                        <h2 class="text-lg font-semibold text-slate-800">{{ vehicle.title }}</h2>
                        <p class="text-sm text-slate-500 mt-1">
                            {{ vehicle.unit_number ? `Unit ${vehicle.unit_number}` : 'No unit number' }}
                        </p>
                    </div>

                    <div class="mt-5 grid grid-cols-2 gap-3">
                        <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                            <p class="text-xs text-slate-500">Documents</p>
                            <p class="mt-1 text-sm font-semibold text-slate-800">{{ vehicle.documents_count }}</p>
                        </div>
                        <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                            <p class="text-xs text-slate-500">Maintenance</p>
                            <p class="mt-1 text-sm font-semibold text-slate-800">{{ vehicle.maintenance_count }}</p>
                        </div>
                        <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                            <p class="text-xs text-slate-500">Repairs</p>
                            <p class="mt-1 text-sm font-semibold text-slate-800">{{ vehicle.repair_count }}</p>
                        </div>
                        <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                            <p class="text-xs text-slate-500">Type</p>
                            <p class="mt-1 text-sm font-semibold text-slate-800">{{ vehicle.type || 'N/A' }}</p>
                        </div>
                    </div>

                    <div class="mt-5 space-y-2 text-sm text-slate-600">
                        <p v-if="vehicle.vin"><span class="text-slate-500">VIN:</span> {{ vehicle.vin }}</p>
                        <p v-if="vehicle.location"><span class="text-slate-500">Location:</span> {{ vehicle.location }}</p>
                        <p v-if="vehicle.registration_expiration_date"><span class="text-slate-500">Registration:</span> {{ vehicle.registration_expiration_date }}</p>
                        <p v-if="vehicle.annual_inspection_expiration_date"><span class="text-slate-500">Inspection:</span> {{ vehicle.annual_inspection_expiration_date }}</p>
                    </div>

                    <div class="mt-5 pt-4 border-t border-slate-200 flex items-center justify-between text-sm">
                        <span class="text-primary font-medium">Open Details</span>
                        <Lucide icon="ArrowRight" class="w-4 h-4 text-primary" />
                    </div>
                </Link>
            </div>
        </div>

        <div v-else class="col-span-12">
            <div class="box box--stacked p-12 text-center">
                <Lucide icon="Truck" class="w-16 h-16 text-slate-300 mx-auto" />
                <h2 class="mt-5 text-lg font-semibold text-slate-800">No Vehicles Assigned</h2>
                <p class="mt-2 text-slate-500">You do not have any active vehicle assignment yet.</p>
                <p class="mt-1 text-sm text-slate-400">If this looks wrong, contact your carrier so they can review your assignment.</p>
            </div>
        </div>
    </div>
</template>
