<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { reactive } from 'vue'
import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import TomSelect from '@/components/Base/TomSelect/TomSelect.vue'
import RazeLayout from '@/layouts/RazeLayout.vue'

declare function route(name: string, params?: any): string

defineOptions({ layout: RazeLayout })

const props = defineProps<{
    filters: { carrier_id: string }
    stats: {
        total: number
        pending: number
        accepted: number
        in_progress: number
        paused: number
        completed: number
        cancelled: number
        with_violations: number
        ghost_logs: number
    }
    recentTrips: {
        id: number
        trip_number: string
        carrier_name: string | null
        driver_name: string
        vehicle_label: string
        status: string
        scheduled_start: string | null
    }[]
    carriers: { id: number; name: string }[]
    isSuperadmin: boolean
    routeNames?: Partial<{
        statistics: string
        index: string
        show: string
    }>
    pageTitle?: string
    pageDescription?: string
    backLabel?: string
}>()

const defaultRouteNames = {
    statistics: 'admin.trips.statistics',
    index: 'admin.trips.index',
    show: 'admin.trips.show',
} as const

function namedRoute(name: keyof typeof defaultRouteNames, params?: any) {
    return route(props.routeNames?.[name] ?? defaultRouteNames[name], params)
}

const filters = reactive({ ...props.filters })

function applyFilters() {
    router.get(namedRoute('statistics'), {
        carrier_id: filters.carrier_id || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    })
}
</script>

<template>
    <Head title="Trip Statistics" />

    <div class="grid grid-cols-12 gap-6">
        <div class="col-span-12">
            <div class="box box--stacked p-6">
                <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="rounded-xl border border-primary/20 bg-primary/10 p-3">
                            <Lucide icon="BarChart3" class="h-8 w-8 text-primary" />
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-slate-800">{{ pageTitle || 'Trips Statistics' }}</h1>
                            <p class="mt-1 text-sm text-slate-500">{{ pageDescription || 'Operational overview of trip statuses, violations, and ghost logs.' }}</p>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <div v-if="isSuperadmin" class="min-w-64">
                            <TomSelect v-model="filters.carrier_id" @update:modelValue="applyFilters">
                                <option value="">All carriers</option>
                                <option v-for="carrier in carriers" :key="carrier.id" :value="String(carrier.id)">{{ carrier.name }}</option>
                            </TomSelect>
                        </div>
                        <Link :href="namedRoute('index')">
                            <Button variant="outline-secondary" class="flex items-center gap-2">
                                <Lucide icon="ArrowLeft" class="h-4 w-4" />
                                {{ backLabel || 'Back to Trips' }}
                            </Button>
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                <div class="box box--stacked border border-primary/10 bg-primary/5 p-5"><p class="text-sm text-slate-500">Total Trips</p><p class="mt-1 text-3xl font-semibold text-slate-800">{{ stats.total }}</p></div>
                <div class="box box--stacked p-5"><p class="text-sm text-slate-500">Pending</p><p class="mt-1 text-3xl font-semibold text-slate-800">{{ stats.pending }}</p></div>
                <div class="box box--stacked p-5"><p class="text-sm text-slate-500">Accepted</p><p class="mt-1 text-3xl font-semibold text-slate-800">{{ stats.accepted }}</p></div>
                <div class="box box--stacked border border-primary/10 bg-primary/5 p-5"><p class="text-sm text-slate-500">In Progress</p><p class="mt-1 text-3xl font-semibold text-slate-800">{{ stats.in_progress }}</p></div>
                <div class="box box--stacked p-5"><p class="text-sm text-slate-500">Paused</p><p class="mt-1 text-3xl font-semibold text-slate-800">{{ stats.paused }}</p></div>
                <div class="box box--stacked p-5"><p class="text-sm text-slate-500">Completed</p><p class="mt-1 text-3xl font-semibold text-slate-800">{{ stats.completed }}</p></div>
                <div class="box box--stacked p-5"><p class="text-sm text-slate-500">Cancelled</p><p class="mt-1 text-3xl font-semibold text-slate-800">{{ stats.cancelled }}</p></div>
                <div class="box box--stacked p-5"><p class="text-sm text-slate-500">With Violations</p><p class="mt-1 text-3xl font-semibold text-slate-800">{{ stats.with_violations }}</p></div>
                <div class="box box--stacked p-5"><p class="text-sm text-slate-500">Ghost Logs</p><p class="mt-1 text-3xl font-semibold text-slate-800">{{ stats.ghost_logs }}</p></div>
            </div>
        </div>

        <div class="col-span-12">
            <div class="box box--stacked overflow-hidden">
                <div class="border-b border-slate-100 px-5 py-4">
                    <h2 class="text-base font-semibold text-slate-800">Recent Trips</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Trip</th>
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Carrier / Driver</th>
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Vehicle</th>
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Status</th>
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Scheduled Start</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            <tr v-for="trip in recentTrips" :key="trip.id">
                                <td class="px-5 py-4">
                                    <Link :href="namedRoute('show', trip.id)" class="font-medium text-primary hover:underline">{{ trip.trip_number }}</Link>
                                </td>
                                <td class="px-5 py-4 text-slate-600">
                                    <div>{{ trip.carrier_name || 'N/A' }}</div>
                                    <div class="mt-1 text-xs text-slate-500">{{ trip.driver_name }}</div>
                                </td>
                                <td class="px-5 py-4 text-slate-600">{{ trip.vehicle_label }}</td>
                                <td class="px-5 py-4 text-slate-600">{{ trip.status.replaceAll('_', ' ') }}</td>
                                <td class="px-5 py-4 text-slate-600">{{ trip.scheduled_start || 'N/A' }}</td>
                            </tr>
                            <tr v-if="!recentTrips.length">
                                <td colspan="5" class="px-5 py-10 text-center text-slate-500">No recent trips found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>
