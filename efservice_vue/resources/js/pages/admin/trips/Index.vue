<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { reactive } from 'vue'
import Button from '@/components/Base/Button'
import FormInput from '@/components/Base/Form/FormInput.vue'
import Litepicker from '@/components/Base/Litepicker/Litepicker.vue'
import Lucide from '@/components/Base/Lucide'
import TomSelect from '@/components/Base/TomSelect/TomSelect.vue'
import RazeLayout from '@/layouts/RazeLayout.vue'

declare function route(name: string, params?: any): string

defineOptions({ layout: RazeLayout })

type PaginationLink = { url: string | null; label: string; active: boolean }

const props = defineProps<{
    filters: { search: string; carrier_id: string; status: string; start_date: string; end_date: string; per_page: number }
    trips: { data: any[]; links: PaginationLink[]; total: number }
    stats: { total: number; pending: number; accepted: number; in_progress: number; completed: number; violations: number }
    carriers: { id: number; name: string }[]
    statusOptions: { value: string; label: string }[]
    isSuperadmin: boolean
}>()

const filters = reactive({ ...props.filters })

const pickerOptions = {
    autoApply: true,
    singleMode: true,
    numberOfColumns: 1,
    numberOfMonths: 1,
    format: 'M/D/YYYY',
    dropdowns: {
        minYear: 1990,
        maxYear: 2035,
        months: true,
        years: true,
    },
}

function applyFilters() {
    router.get(route('admin.trips.index'), {
        search: filters.search || undefined,
        carrier_id: filters.carrier_id || undefined,
        status: filters.status || undefined,
        start_date: filters.start_date || undefined,
        end_date: filters.end_date || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    })
}

function resetFilters() {
    filters.search = ''
    filters.carrier_id = ''
    filters.status = ''
    filters.start_date = ''
    filters.end_date = ''
    applyFilters()
}

function destroyTrip(trip: any) {
    if (!confirm(`Delete trip ${trip.trip_number}?`)) return
    router.delete(route('admin.trips.destroy', trip.id), { preserveScroll: true })
}

function runAction(routeName: string, trip: any, label: string) {
    if (!confirm(`${label} ${trip.trip_number}?`)) return
    router.post(route(routeName, trip.id), {}, { preserveScroll: true })
}

function badgeClass(status: string) {
    if (status === 'completed') return 'bg-primary/10 text-primary'
    if (status === 'in_progress' || status === 'accepted' || status === 'paused') return 'bg-slate-200 text-slate-700'
    if (status === 'pending') return 'bg-slate-100 text-slate-600'
    return 'bg-slate-100 text-slate-500'
}
</script>

<template>
    <Head title="Trips Management" />

    <div class="grid grid-cols-12 gap-6">
        <div class="col-span-12">
            <div class="box box--stacked p-6">
                <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="rounded-xl border border-primary/20 bg-primary/10 p-3">
                            <Lucide icon="Truck" class="h-8 w-8 text-primary" />
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-slate-800">Trips Management</h1>
                            <p class="mt-1 text-sm text-slate-500">Monitor scheduling, active runs, and emergency controls across carriers.</p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <Link :href="route('admin.trips.statistics')">
                            <Button variant="outline-secondary" class="flex items-center gap-2">
                                <Lucide icon="BarChart3" class="h-4 w-4" />
                                Statistics
                            </Button>
                        </Link>
                        <Link :href="route('admin.trips.create')">
                            <Button variant="primary" class="flex items-center gap-2">
                                <Lucide icon="Plus" class="h-4 w-4" />
                                Add Trip
                            </Button>
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-6">
                <div class="box box--stacked border border-primary/10 bg-primary/5 p-5"><p class="text-sm text-slate-500">Total Trips</p><p class="mt-1 text-2xl font-semibold text-slate-800">{{ stats.total }}</p></div>
                <div class="box box--stacked p-5"><p class="text-sm text-slate-500">Pending</p><p class="mt-1 text-2xl font-semibold text-slate-800">{{ stats.pending }}</p></div>
                <div class="box box--stacked p-5"><p class="text-sm text-slate-500">Accepted</p><p class="mt-1 text-2xl font-semibold text-slate-800">{{ stats.accepted }}</p></div>
                <div class="box box--stacked border border-primary/10 bg-primary/5 p-5"><p class="text-sm text-slate-500">In Progress / Paused</p><p class="mt-1 text-2xl font-semibold text-slate-800">{{ stats.in_progress }}</p></div>
                <div class="box box--stacked p-5"><p class="text-sm text-slate-500">Completed</p><p class="mt-1 text-2xl font-semibold text-slate-800">{{ stats.completed }}</p></div>
                <div class="box box--stacked p-5"><p class="text-sm text-slate-500">With Violations</p><p class="mt-1 text-2xl font-semibold text-slate-800">{{ stats.violations }}</p></div>
            </div>
        </div>

        <div class="col-span-12">
            <div class="box box--stacked p-5">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-5">
                    <div>
                        <label class="mb-2 block text-xs font-medium uppercase tracking-wide text-slate-500">Search</label>
                        <FormInput v-model="filters.search" placeholder="Trip number, driver, carrier..." />
                    </div>
                    <div v-if="isSuperadmin">
                        <label class="mb-2 block text-xs font-medium uppercase tracking-wide text-slate-500">Carrier</label>
                        <TomSelect v-model="filters.carrier_id">
                            <option value="">All carriers</option>
                            <option v-for="carrier in carriers" :key="carrier.id" :value="String(carrier.id)">{{ carrier.name }}</option>
                        </TomSelect>
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-medium uppercase tracking-wide text-slate-500">Status</label>
                        <TomSelect v-model="filters.status">
                            <option value="">All statuses</option>
                            <option v-for="status in statusOptions" :key="status.value" :value="status.value">{{ status.label }}</option>
                        </TomSelect>
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-medium uppercase tracking-wide text-slate-500">From Date</label>
                        <Litepicker v-model="filters.start_date" :options="pickerOptions" />
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-medium uppercase tracking-wide text-slate-500">To Date</label>
                        <Litepicker v-model="filters.end_date" :options="pickerOptions" />
                    </div>
                </div>

                <div class="mt-4 flex flex-wrap items-center gap-3">
                    <Button variant="primary" class="flex items-center gap-2" @click="applyFilters">
                        <Lucide icon="Filter" class="h-4 w-4" />
                        Apply Filters
                    </Button>
                    <Button variant="outline-secondary" class="flex items-center gap-2" @click="resetFilters">
                        <Lucide icon="RotateCcw" class="h-4 w-4" />
                        Reset
                    </Button>
                </div>
            </div>
        </div>

        <div class="col-span-12">
            <div class="box box--stacked overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Trip</th>
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Carrier / Driver</th>
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Route</th>
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Scheduled</th>
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Status</th>
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Quick Actions</th>
                                <th class="px-5 py-3 text-right font-semibold text-slate-600">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            <tr v-for="trip in trips.data" :key="trip.id">
                                <td class="px-5 py-4">
                                    <Link :href="route('admin.trips.show', trip.id)" class="font-medium text-primary hover:underline">
                                        {{ trip.trip_number }}
                                    </Link>
                                    <div class="mt-1 text-xs text-slate-500">{{ trip.vehicle_label }}</div>
                                    <div v-if="trip.has_violations || trip.forgot_to_close" class="mt-2 flex flex-wrap gap-2">
                                        <span v-if="trip.has_violations" class="rounded-full bg-slate-200 px-2 py-1 text-[11px] font-medium text-slate-700">Violations</span>
                                        <span v-if="trip.forgot_to_close" class="rounded-full bg-slate-100 px-2 py-1 text-[11px] font-medium text-slate-600">Ghost Log</span>
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-slate-600">
                                    <div class="font-medium text-slate-800">{{ trip.carrier_name || 'N/A' }}</div>
                                    <div class="mt-1 text-xs text-slate-500">{{ trip.driver_name }}</div>
                                </td>
                                <td class="px-5 py-4 text-slate-600">
                                    <div>{{ trip.origin_address }}</div>
                                    <div class="mt-1 text-xs text-slate-500">{{ trip.destination_address }}</div>
                                </td>
                                <td class="px-5 py-4 text-slate-600">{{ trip.scheduled_start || 'N/A' }}</td>
                                <td class="px-5 py-4">
                                    <span class="rounded-full px-3 py-1 text-xs font-medium" :class="badgeClass(trip.status)">
                                        {{ trip.status_label }}
                                    </span>
                                    <div class="mt-2 text-xs text-slate-500">{{ trip.violations_count }} violations</div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex flex-wrap gap-2">
                                        <Button v-if="trip.quick_actions.can_force_start" variant="primary" class="px-3 py-1 text-xs" @click="runAction('admin.trips.force-start', trip, 'Start')">Start</Button>
                                        <Button v-if="trip.quick_actions.can_force_pause" variant="outline-secondary" class="px-3 py-1 text-xs" @click="runAction('admin.trips.force-pause', trip, 'Pause')">Pause</Button>
                                        <Button v-if="trip.quick_actions.can_force_resume" variant="primary" class="px-3 py-1 text-xs" @click="runAction('admin.trips.force-resume', trip, 'Resume')">Resume</Button>
                                        <Button v-if="trip.quick_actions.can_force_end" variant="outline-secondary" class="px-3 py-1 text-xs" @click="runAction('admin.trips.force-end', trip, 'End')">End</Button>
                                        <span v-if="!trip.quick_actions.can_force_start && !trip.quick_actions.can_force_pause && !trip.quick_actions.can_force_resume && !trip.quick_actions.can_force_end" class="text-xs text-slate-400">No quick action</span>
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center justify-end gap-2">
                                        <Link :href="route('admin.trips.show', trip.id)" class="p-1.5 text-slate-400 transition hover:text-primary">
                                            <Lucide icon="Eye" class="h-4 w-4" />
                                        </Link>
                                        <Link v-if="trip.can_edit" :href="route('admin.trips.edit', trip.id)" class="p-1.5 text-slate-400 transition hover:text-primary">
                                            <Lucide icon="PenLine" class="h-4 w-4" />
                                        </Link>
                                        <button v-if="trip.can_delete" type="button" class="p-1.5 text-slate-400 transition hover:text-red-500" @click="destroyTrip(trip)">
                                            <Lucide icon="Trash2" class="h-4 w-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!trips.data.length">
                                <td colspan="7" class="px-5 py-12 text-center text-slate-500">
                                    <Lucide icon="Truck" class="mx-auto h-10 w-10 text-slate-300" />
                                    <p class="mt-3">No trips matched the current filters.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="trips.links?.length" class="border-t border-slate-100 px-5 py-4">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <p class="text-sm text-slate-500">Showing {{ trips.data.length }} of {{ trips.total }} trips</p>
                        <div class="flex flex-wrap items-center gap-1">
                            <Link
                                v-for="link in trips.links"
                                :key="`${link.label}-${link.url}`"
                                :href="link.url || '#'"
                                class="rounded-md px-3 py-1.5 text-sm transition"
                                :class="link.active ? 'bg-primary text-white' : 'text-slate-600 hover:bg-slate-100'"
                                :preserve-scroll="true"
                                :preserve-state="true"
                            >
                                <span v-html="link.label" />
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
