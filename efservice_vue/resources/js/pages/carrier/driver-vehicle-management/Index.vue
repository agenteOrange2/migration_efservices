<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { reactive } from 'vue'
import CarrierLayout from '@/layouts/CarrierLayout.vue'
import Lucide from '@/components/Base/Lucide'
import Button from '@/components/Base/Button'
import TomSelect from '@/components/Base/TomSelect/TomSelect.vue'
import { FormInput } from '@/components/Base/Form'

declare function route(name: string, params?: any): string

defineOptions({ layout: CarrierLayout })

interface PaginationLink {
    url: string | null
    label: string
    active: boolean
}

interface AssignmentRow {
    id: number
    driver_type_label: string
    start_date: string | null
    status_label: string
    vehicle: { id: number; unit: string; title: string; vin: string | null } | null
}

interface DriverRow {
    id: number
    name: string
    email: string | null
    phone: string | null
    status: string
    profile_photo_url: string | null
    assignment: AssignmentRow | null
}

const props = defineProps<{
    drivers: { data: DriverRow[]; links: PaginationLink[]; total: number }
    filters: { search: string; driver_type: string; assignment_status: string }
    stats: { total: number; assigned: number; unassigned: number; vehicles_in_use: number }
    driverTypeOptions: Record<string, string>
    assignmentStatusOptions: Record<string, string>
    carrier: { id: number; name: string }
}>()

const filters = reactive({ ...props.filters })

function applyFilters() {
    router.get(route('carrier.driver-vehicle-management.index'), {
        search: filters.search || undefined,
        driver_type: filters.driver_type || undefined,
        assignment_status: filters.assignment_status || undefined,
    }, { preserveState: true, replace: true })
}

function resetFilters() {
    filters.search = ''
    filters.driver_type = ''
    filters.assignment_status = ''
    applyFilters()
}
</script>

<template>
    <Head title="Driver & Vehicle Management" />

    <div class="grid grid-cols-12 gap-x-6 gap-y-8">
        <div class="col-span-12">
            <div class="box box--stacked p-6">
                <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                    <div class="flex items-start gap-4">
                        <div class="rounded-2xl border border-primary/20 bg-primary/10 p-3">
                            <Lucide icon="Truck" class="h-8 w-8 text-primary" />
                        </div>
                        <div>
                            <h1 class="text-2xl font-semibold text-slate-800">Driver & Vehicle Management</h1>
                            <p class="mt-1 text-sm text-slate-500">
                                Track who is assigned, switch vehicles cleanly, and keep communication close to operations.
                            </p>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-dashed border-slate-300/80 bg-slate-50/80 px-5 py-4">
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Carrier Scope</div>
                        <div class="mt-1 text-base font-semibold text-slate-800">{{ carrier.name }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div class="box box--stacked rounded-2xl p-5">
                    <p class="text-sm text-slate-500">Total Drivers</p>
                    <p class="mt-1 text-3xl font-semibold text-slate-800">{{ stats.total }}</p>
                </div>
                <div class="box box--stacked rounded-2xl p-5">
                    <p class="text-sm text-slate-500">Assigned</p>
                    <p class="mt-1 text-3xl font-semibold text-slate-800">{{ stats.assigned }}</p>
                </div>
                <div class="box box--stacked rounded-2xl p-5">
                    <p class="text-sm text-slate-500">Unassigned</p>
                    <p class="mt-1 text-3xl font-semibold text-slate-800">{{ stats.unassigned }}</p>
                </div>
                <div class="box box--stacked rounded-2xl p-5">
                    <p class="text-sm text-slate-500">Vehicles in Use</p>
                    <p class="mt-1 text-3xl font-semibold text-slate-800">{{ stats.vehicles_in_use }}</p>
                </div>
            </div>
        </div>

        <div class="col-span-12">
            <div class="box box--stacked p-5">
                <div class="grid grid-cols-1 gap-4 lg:grid-cols-4">
                    <div class="relative lg:col-span-2">
                        <Lucide icon="Search" class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                        <FormInput v-model="filters.search" type="text" class="pl-10" placeholder="Search driver, email or phone..." />
                    </div>

                    <TomSelect v-model="filters.driver_type">
                        <option value="">All driver types</option>
                        <option v-for="(label, key) in driverTypeOptions" :key="key" :value="key">{{ label }}</option>
                    </TomSelect>

                    <TomSelect v-model="filters.assignment_status">
                        <option value="">All assignment statuses</option>
                        <option v-for="(label, key) in assignmentStatusOptions" :key="key" :value="key">{{ label }}</option>
                    </TomSelect>
                </div>

                <div class="mt-4 flex flex-wrap items-center gap-3">
                    <button type="button" class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-white transition hover:bg-primary/90" @click="applyFilters">
                        <Lucide icon="Filter" class="h-4 w-4" />
                        Apply Filters
                    </button>
                    <button type="button" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-4 py-2 text-slate-600 transition hover:bg-slate-50" @click="resetFilters">
                        <Lucide icon="RotateCcw" class="h-4 w-4" />
                        Clear
                    </button>
                </div>
            </div>
        </div>

        <div class="col-span-12">
            <div class="box box--stacked overflow-hidden">
                <div class="border-b border-slate-200/70 px-5 py-4">
                    <h2 class="text-base font-semibold text-slate-800">Driver Assignments</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ drivers.total }} driver records</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50/80">
                            <tr>
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Driver</th>
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Assignment Type</th>
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Assigned Vehicle</th>
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Assignment Status</th>
                                <th class="px-5 py-3 text-right font-semibold text-slate-600">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200/80 bg-white">
                            <tr v-for="driver in drivers.data" :key="driver.id" class="align-top">
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="h-11 w-11 overflow-hidden rounded-xl border border-slate-200 bg-slate-50">
                                            <img :src="driver.profile_photo_url || '/build/default_profile.png'" :alt="driver.name" class="h-full w-full object-cover">
                                        </div>
                                        <div>
                                            <div class="font-semibold text-slate-800">{{ driver.name }}</div>
                                            <div class="mt-1 text-xs text-slate-500">{{ driver.email || 'No email available' }}</div>
                                            <div class="mt-1 text-xs text-slate-400">{{ driver.phone || 'No phone available' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <div v-if="driver.assignment" class="font-medium text-slate-800">{{ driver.assignment.driver_type_label }}</div>
                                    <div v-else class="text-slate-400">Unassigned</div>
                                </td>
                                <td class="px-5 py-4">
                                    <template v-if="driver.assignment?.vehicle">
                                        <div class="font-medium text-slate-800">{{ driver.assignment.vehicle.unit }}</div>
                                        <div class="mt-1 text-xs text-slate-500">{{ driver.assignment.vehicle.title }}</div>
                                        <div v-if="driver.assignment.vehicle.vin" class="mt-1 text-xs text-slate-400">VIN {{ driver.assignment.vehicle.vin }}</div>
                                    </template>
                                    <span v-else class="text-slate-400">No vehicle assigned</span>
                                </td>
                                <td class="px-5 py-4">
                                    <div v-if="driver.assignment" class="space-y-1">
                                        <div class="inline-flex rounded-full bg-primary/10 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-primary">
                                            {{ driver.assignment.status_label }}
                                        </div>
                                        <div class="text-xs text-slate-500">Started {{ driver.assignment.start_date || 'N/A' }}</div>
                                    </div>
                                    <div v-else class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Unassigned
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex flex-wrap justify-end gap-2">
                                        <Link :href="route('carrier.driver-vehicle-management.show', driver.id)">
                                            <Button variant="outline-primary" class="px-3 py-2 text-xs">View</Button>
                                        </Link>
                                        <Link :href="route(driver.assignment ? 'carrier.driver-vehicle-management.edit-assignment' : 'carrier.driver-vehicle-management.assign-vehicle', driver.id)">
                                            <Button variant="outline-secondary" class="px-3 py-2 text-xs">
                                                {{ driver.assignment ? 'Edit Assignment' : 'Assign Vehicle' }}
                                            </Button>
                                        </Link>
                                        <Link :href="route('carrier.driver-vehicle-management.assignment-history', driver.id)">
                                            <Button variant="outline-secondary" class="px-3 py-2 text-xs">History</Button>
                                        </Link>
                                        <Link :href="route('carrier.driver-vehicle-management.contact', driver.id)">
                                            <Button variant="primary" class="px-3 py-2 text-xs">Contact</Button>
                                        </Link>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="drivers.data.length === 0">
                                <td colspan="5" class="px-5 py-12 text-center text-sm text-slate-500">
                                    No drivers matched the current filters.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="flex flex-wrap items-center justify-end gap-2 border-t border-slate-200/70 px-5 py-4">
                    <Link
                        v-for="link in drivers.links"
                        :key="link.label"
                        :href="link.url || '#'"
                        class="rounded-lg px-3 py-2 text-sm transition"
                        :class="[
                            link.active ? 'bg-primary text-white' : 'border border-slate-200 text-slate-600 hover:bg-slate-50',
                            !link.url ? 'pointer-events-none opacity-50' : '',
                        ]"
                        v-html="link.label"
                    />
                </div>
            </div>
        </div>
    </div>
</template>
