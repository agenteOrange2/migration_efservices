<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import RazeLayout from '@/layouts/RazeLayout.vue'
import TripRouteMap from '@/components/TripRouteMap.vue'

declare function route(name: string, params?: any): string

defineOptions({ layout: RazeLayout })

interface InspectionMap { [key: string]: string }

const props = defineProps<{
    trip: any
    fmcsaStatus: any | null
    inspection: {
        tractor_items: InspectionMap
        trailer_items: InspectionMap
    }
    gpsRoute?: Array<{ lat: number; lng: number }>
    gpsStats: null | {
        total_points: number
        total_distance_miles: number
        average_speed_mph: number
        max_speed_mph: number
        stationary_periods: number
        duration_minutes: number | null
    }
    timeline: any[]
    hosEntries: any[]
    violations: any[]
    pauses: any[]
    destinationVerification: any
    googleMapsUrls: { origin: string | null; destination: string | null; route: string | null }
    recentHosLocations: any[]
    tripReportPdfs: any[]
    inspectionDocuments: any[]
    tripDocuments: any[]
    hosLogRoute?: string | null
    routeNames?: Partial<{
        destroy: string
        edit: string
        index: string
        forceStart: string
        forcePause: string
        forceResume: string
        forceEnd: string
    }>
}>()

const defaultRouteNames = {
    destroy: 'admin.trips.destroy',
    edit: 'admin.trips.edit',
    index: 'admin.trips.index',
    forceStart: 'admin.trips.force-start',
    forcePause: 'admin.trips.force-pause',
    forceResume: 'admin.trips.force-resume',
    forceEnd: 'admin.trips.force-end',
} as const

function namedRoute(name: keyof typeof defaultRouteNames, params?: any) {
    return route(props.routeNames?.[name] ?? defaultRouteNames[name], params)
}

function statusTone(status: string) {
    if (status === 'completed') return 'bg-primary/10 text-primary'
    if (status === 'in_progress') return 'bg-slate-700 text-white'
    if (status === 'paused') return 'bg-slate-200 text-slate-700'
    if (status === 'accepted') return 'bg-slate-100 text-slate-700'
    if (status === 'pending') return 'bg-slate-100 text-slate-600'
    if (status === 'cancelled' || status === 'rejected' || status === 'failed') return 'bg-danger/10 text-danger'
    return 'bg-slate-100 text-slate-500'
}

function destroyTrip() {
    const status = props.trip.status.replace('_', ' ')
    if (!confirm(`Delete trip ${props.trip.trip_number} (${status})?\n\nThis will permanently remove the trip and all related data.`)) return
    router.delete(namedRoute('destroy', props.trip.id))
}

function emergencyAction(routeName: keyof typeof defaultRouteNames, label: string) {
    if (!confirm(`${label} ${props.trip.trip_number}?`)) return
    router.post(namedRoute(routeName, props.trip.id), {}, { preserveScroll: true })
}
</script>

<template>
    <Head :title="trip.trip_number" />

    <div class="grid grid-cols-12 gap-4 sm:gap-6">

        <!-- Header -->
        <div class="col-span-12">
            <div class="box box--stacked p-4 sm:p-6">
                <div class="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
                    <div class="space-y-4 min-w-0">
                        <Link :href="namedRoute('index')" class="inline-flex items-center gap-2 text-sm font-medium text-slate-500 transition hover:text-primary">
                            <Lucide icon="ArrowLeft" class="h-4 w-4" />
                            Back to Trips
                        </Link>

                        <div class="flex flex-wrap items-center gap-2 sm:gap-3">
                            <h1 class="text-xl sm:text-2xl font-bold text-slate-800 break-all">{{ trip.trip_number }}</h1>
                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium" :class="statusTone(trip.status)">
                                {{ trip.status_label }}
                            </span>
                            <span v-if="trip.has_violations" class="inline-flex rounded-full bg-danger/10 px-2.5 py-1 text-xs font-medium text-danger">Has Violations</span>
                            <span v-if="trip.forgot_to_close" class="inline-flex rounded-full bg-warning/10 px-2.5 py-1 text-xs font-medium text-warning">Ghost Log</span>
                        </div>

                        <p class="text-sm text-slate-500">
                            Driver: <span class="font-medium text-slate-700">{{ trip.driver_name }}</span>
                            <span v-if="trip.carrier_name"> · Carrier: <span class="font-medium text-slate-700">{{ trip.carrier_name }}</span></span>
                        </p>
                    </div>

                    <div class="grid grid-cols-2 gap-2 sm:flex sm:flex-wrap sm:items-center sm:gap-3">
                        <Button v-if="trip.can_force_start" variant="primary" class="flex items-center justify-center gap-2" @click="emergencyAction('forceStart', 'Start')">
                            <Lucide icon="Play" class="h-4 w-4" />Force Start
                        </Button>
                        <Button v-if="trip.can_force_pause" variant="warning" class="flex items-center justify-center gap-2" @click="emergencyAction('forcePause', 'Pause')">
                            <Lucide icon="Pause" class="h-4 w-4" />Force Pause
                        </Button>
                        <Button v-if="trip.can_force_resume" variant="success" class="flex items-center justify-center gap-2" @click="emergencyAction('forceResume', 'Resume')">
                            <Lucide icon="Play" class="h-4 w-4" />Force Resume
                        </Button>
                        <Button v-if="trip.can_force_end" variant="danger" class="flex items-center justify-center gap-2" @click="emergencyAction('forceEnd', 'End')">
                            <Lucide icon="Square" class="h-4 w-4" />Force End
                        </Button>
                        <Link v-if="trip.can_edit" :href="namedRoute('edit', trip.id)">
                            <Button variant="primary" class="w-full flex items-center justify-center gap-2 sm:w-auto">
                                <Lucide icon="PenLine" class="h-4 w-4" />Edit
                            </Button>
                        </Link>
                        <Button v-if="trip.can_delete" variant="danger" class="flex items-center justify-center gap-2" @click="destroyTrip">
                            <Lucide icon="Trash2" class="h-4 w-4" />Delete
                        </Button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main column -->
        <div class="col-span-12 xl:col-span-8 space-y-4 sm:space-y-6">

            <!-- Trip Information -->
            <div class="box box--stacked p-4 sm:p-6">
                <h2 class="mb-4 text-base font-semibold text-slate-800">Trip Information</h2>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="rounded-xl bg-slate-50 p-4">
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Origin</p>
                        <p class="mt-2 text-sm text-slate-800 break-words">{{ trip.origin_address || 'N/A' }}</p>
                        <a v-if="googleMapsUrls.origin" :href="googleMapsUrls.origin" target="_blank" class="mt-3 inline-flex items-center gap-2 text-xs text-primary hover:underline">
                            <Lucide icon="MapPin" class="h-3 w-3" />Open in Maps
                        </a>
                    </div>
                    <div class="rounded-xl bg-slate-50 p-4">
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Destination</p>
                        <p class="mt-2 text-sm text-slate-800 break-words">{{ trip.destination_address || 'N/A' }}</p>
                        <a v-if="googleMapsUrls.destination" :href="googleMapsUrls.destination" target="_blank" class="mt-3 inline-flex items-center gap-2 text-xs text-primary hover:underline">
                            <Lucide icon="Flag" class="h-3 w-3" />Open in Maps
                        </a>
                    </div>
                </div>

                <div class="mt-5 grid grid-cols-2 gap-3 md:gap-4 xl:grid-cols-4">
                    <div class="rounded-lg border border-slate-200 bg-white p-3 sm:p-4">
                        <p class="text-xs text-slate-500">Driver</p>
                        <p class="mt-1 text-sm font-medium text-slate-800 break-words">{{ trip.driver_name }}</p>
                        <p class="text-xs text-slate-500 break-words">{{ trip.driver_email || '' }}</p>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-white p-3 sm:p-4">
                        <p class="text-xs text-slate-500">Vehicle</p>
                        <p class="mt-1 text-sm font-medium text-slate-800 break-words">{{ trip.vehicle_label }}</p>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-white p-3 sm:p-4">
                        <p class="text-xs text-slate-500">License Plate</p>
                        <p class="mt-1 text-sm font-medium text-slate-800">{{ trip.vehicle_license_plate || 'N/A' }}</p>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-white p-3 sm:p-4">
                        <p class="text-xs text-slate-500">Scheduled Start</p>
                        <p class="mt-1 text-sm font-medium text-slate-800">{{ trip.scheduled_start || 'N/A' }}</p>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-white p-3 sm:p-4">
                        <p class="text-xs text-slate-500">Scheduled End</p>
                        <p class="mt-1 text-sm font-medium text-slate-800">{{ trip.scheduled_end || 'N/A' }}</p>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-white p-3 sm:p-4">
                        <p class="text-xs text-slate-500">Actual Start</p>
                        <p class="mt-1 text-sm font-medium text-slate-800">{{ trip.actual_start || 'N/A' }}</p>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-white p-3 sm:p-4">
                        <p class="text-xs text-slate-500">Actual End</p>
                        <p class="mt-1 text-sm font-medium text-slate-800">{{ trip.actual_end || 'N/A' }}</p>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-white p-3 sm:p-4">
                        <p class="text-xs text-slate-500">Actual Duration</p>
                        <p class="mt-1 text-sm font-medium text-slate-800">{{ trip.actual_duration || 'N/A' }}</p>
                    </div>
                </div>

                <div v-if="trip.load_type || trip.load_weight || trip.description || trip.notes || trip.driver_notes" class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div v-if="trip.load_type || trip.load_weight" class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <h3 class="text-sm font-semibold text-slate-700">Load Information</h3>
                        <p class="mt-3 text-sm text-slate-600"><span class="font-medium text-slate-800">Type:</span> {{ trip.load_type || 'N/A' }}</p>
                        <p class="mt-2 text-sm text-slate-600"><span class="font-medium text-slate-800">Weight:</span> {{ trip.load_weight || 'N/A' }}</p>
                    </div>
                    <div v-if="trip.description || trip.notes || trip.driver_notes" class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <h3 class="text-sm font-semibold text-slate-700">Notes</h3>
                        <p v-if="trip.description" class="mt-3 text-sm text-slate-600"><span class="font-medium text-slate-800">Description:</span> {{ trip.description }}</p>
                        <p v-if="trip.notes" class="mt-2 text-sm text-slate-600"><span class="font-medium text-slate-800">Admin Notes:</span> {{ trip.notes }}</p>
                        <p v-if="trip.driver_notes" class="mt-2 text-sm text-slate-600"><span class="font-medium text-slate-800">Driver Notes:</span> {{ trip.driver_notes }}</p>
                    </div>
                </div>
            </div>

            <!-- Pre-Trip Inspection -->
            <div v-if="trip.pre_trip_inspection_data" class="box box--stacked p-4 sm:p-6">
                <div class="mb-4 flex items-center gap-2">
                    <Lucide icon="ClipboardCheck" class="h-4 w-4 text-primary" />
                    <h2 class="text-base font-semibold text-slate-800">Pre-Trip Inspection</h2>
                    <span class="rounded-full bg-primary/10 px-2 py-0.5 text-[11px] font-medium text-primary">Completed</span>
                </div>

                <div class="grid grid-cols-2 gap-2 sm:grid-cols-3 md:grid-cols-4">
                    <template v-for="(checked, key) in trip.pre_trip_inspection_data.tractor" :key="key">
                        <div class="flex items-center gap-2 rounded-lg border px-3 py-2 text-xs"
                            :class="checked ? 'border-primary/30 bg-primary/5 text-primary' : 'border-slate-200 bg-slate-50 text-slate-400 line-through'">
                            <Lucide :icon="checked ? 'CheckCircle2' : 'XCircle'" class="h-3.5 w-3.5 shrink-0" />
                            <span>{{ inspection.tractor_items[key] ?? key }}</span>
                        </div>
                    </template>
                </div>

                <template v-if="trip.pre_trip_inspection_data.trailer">
                    <p class="mt-4 text-xs font-semibold uppercase tracking-wide text-slate-500">Trailer</p>
                    <div class="mt-2 grid grid-cols-2 gap-2 sm:grid-cols-3 md:grid-cols-4">
                        <template v-for="(checked, key) in trip.pre_trip_inspection_data.trailer" :key="key">
                            <div class="flex items-center gap-2 rounded-lg border px-3 py-2 text-xs"
                                :class="checked ? 'border-primary/30 bg-primary/5 text-primary' : 'border-slate-200 bg-slate-50 text-slate-400 line-through'">
                                <Lucide :icon="checked ? 'CheckCircle2' : 'XCircle'" class="h-3.5 w-3.5 shrink-0" />
                                <span>{{ inspection.trailer_items[key] ?? key }}</span>
                            </div>
                        </template>
                    </div>
                </template>

                <div class="mt-4 space-y-2">
                    <div v-if="trip.pre_trip_remarks" class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Remarks / Defects</p>
                        <p class="mt-1 text-sm text-slate-700">{{ trip.pre_trip_remarks }}</p>
                    </div>
                    <div v-if="trip.pre_trip_defects_corrected_notes" class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Defects Corrected</p>
                        <p class="mt-1 text-sm text-slate-700">{{ trip.pre_trip_defects_corrected_notes }}</p>
                    </div>
                    <div v-if="trip.pre_trip_defects_not_need_correction_notes" class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-400">No Correction Needed</p>
                        <p class="mt-1 text-sm text-slate-700">{{ trip.pre_trip_defects_not_need_correction_notes }}</p>
                    </div>
                </div>

                <div v-if="trip.pre_trip_driver_signature" class="mt-4">
                    <p class="mb-2 text-xs font-medium uppercase tracking-wide text-slate-400">Driver Signature</p>
                    <img v-if="trip.pre_trip_driver_signature.startsWith('data:image')"
                        :src="trip.pre_trip_driver_signature"
                        alt="Driver signature"
                        class="max-h-20 rounded-lg border border-slate-200 bg-white p-2" />
                    <p v-else class="text-sm font-medium italic text-slate-700">{{ trip.pre_trip_driver_signature }}</p>
                </div>
            </div>

            <!-- Post-Trip Inspection -->
            <div v-if="trip.post_trip_inspection_data" class="box box--stacked p-4 sm:p-6">
                <div class="mb-4 flex items-center gap-2">
                    <Lucide icon="ClipboardList" class="h-4 w-4 text-primary" />
                    <h2 class="text-base font-semibold text-slate-800">Post-Trip Inspection</h2>
                    <span class="rounded-full bg-primary/10 px-2 py-0.5 text-[11px] font-medium text-primary">Completed</span>
                </div>

                <div class="grid grid-cols-2 gap-2 sm:grid-cols-3 md:grid-cols-4">
                    <template v-for="(checked, key) in trip.post_trip_inspection_data.tractor" :key="key">
                        <div class="flex items-center gap-2 rounded-lg border px-3 py-2 text-xs"
                            :class="checked ? 'border-primary/30 bg-primary/5 text-primary' : 'border-slate-200 bg-slate-50 text-slate-400 line-through'">
                            <Lucide :icon="checked ? 'CheckCircle2' : 'XCircle'" class="h-3.5 w-3.5 shrink-0" />
                            <span>{{ inspection.tractor_items[key] ?? key }}</span>
                        </div>
                    </template>
                </div>

                <template v-if="trip.post_trip_inspection_data.trailer">
                    <p class="mt-4 text-xs font-semibold uppercase tracking-wide text-slate-500">Trailer</p>
                    <div class="mt-2 grid grid-cols-2 gap-2 sm:grid-cols-3 md:grid-cols-4">
                        <template v-for="(checked, key) in trip.post_trip_inspection_data.trailer" :key="key">
                            <div class="flex items-center gap-2 rounded-lg border px-3 py-2 text-xs"
                                :class="checked ? 'border-primary/30 bg-primary/5 text-primary' : 'border-slate-200 bg-slate-50 text-slate-400 line-through'">
                                <Lucide :icon="checked ? 'CheckCircle2' : 'XCircle'" class="h-3.5 w-3.5 shrink-0" />
                                <span>{{ inspection.trailer_items[key] ?? key }}</span>
                            </div>
                        </template>
                    </div>
                </template>

                <div class="mt-4 space-y-2">
                    <div v-if="trip.post_trip_remarks" class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Remarks / Defects</p>
                        <p class="mt-1 text-sm text-slate-700">{{ trip.post_trip_remarks }}</p>
                    </div>
                    <div v-if="trip.post_trip_defects_corrected_notes" class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Defects Corrected</p>
                        <p class="mt-1 text-sm text-slate-700">{{ trip.post_trip_defects_corrected_notes }}</p>
                    </div>
                    <div v-if="trip.post_trip_defects_not_need_correction_notes" class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-400">No Correction Needed</p>
                        <p class="mt-1 text-sm text-slate-700">{{ trip.post_trip_defects_not_need_correction_notes }}</p>
                    </div>
                    <div v-if="trip.driver_notes" class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Driver Notes</p>
                        <p class="mt-1 text-sm text-slate-700">{{ trip.driver_notes }}</p>
                    </div>
                </div>

                <div v-if="trip.post_trip_driver_signature" class="mt-4">
                    <p class="mb-2 text-xs font-medium uppercase tracking-wide text-slate-400">Driver Signature</p>
                    <img v-if="trip.post_trip_driver_signature.startsWith('data:image')"
                        :src="trip.post_trip_driver_signature"
                        alt="Driver signature"
                        class="max-h-20 rounded-lg border border-slate-200 bg-white p-2" />
                    <p v-else class="text-sm font-medium italic text-slate-700">{{ trip.post_trip_driver_signature }}</p>
                </div>
            </div>

            <!-- Route Map -->
            <div v-if="trip.origin_address" class="box box--stacked p-4 sm:p-6">
                <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex flex-wrap items-center gap-2">
                        <Lucide icon="Map" class="h-4 w-4 text-primary" />
                        <h2 class="text-base font-semibold text-slate-800">Route Map</h2>
                        <span v-if="gpsRoute && gpsRoute.length > 0" class="rounded-full bg-primary/10 px-2 py-0.5 text-[11px] font-medium text-primary">
                            {{ gpsRoute.length }} GPS points
                        </span>
                        <span v-else-if="trip.origin_lat" class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] text-slate-500">
                            Estimated route
                        </span>
                        <span v-else class="rounded-full bg-amber-50 px-2 py-0.5 text-[11px] text-amber-600 border border-amber-200">
                            GPS not started
                        </span>
                    </div>
                    <a v-if="googleMapsUrls.route" :href="googleMapsUrls.route" target="_blank" class="inline-flex items-center gap-1 text-sm text-primary hover:underline">
                        <Lucide icon="ExternalLink" class="h-3 w-3" />
                        Google Maps
                    </a>
                </div>
                <TripRouteMap
                    :gps-route="gpsRoute"
                    :origin-lat="trip.origin_lat"
                    :origin-lng="trip.origin_lng"
                    :destination-lat="trip.destination_lat"
                    :destination-lng="trip.destination_lng"
                    :origin-address="trip.origin_address"
                    :destination-address="trip.destination_address"
                />
                <div class="mt-3 flex items-center gap-4 text-xs text-slate-500">
                    <span class="flex items-center gap-1.5">
                        <span class="inline-block h-3 w-3 rounded-full bg-green-500"></span> Start
                    </span>
                    <span class="flex items-center gap-1.5">
                        <span class="inline-block h-3 w-3 rounded-full" :class="gpsRoute && gpsRoute.length > 0 ? 'bg-red-500' : 'bg-orange-500'"></span>
                        {{ gpsRoute && gpsRoute.length > 0 ? 'End' : 'Destination' }}
                    </span>
                </div>
            </div>

            <!-- Timeline -->
            <div class="box box--stacked p-4 sm:p-6">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <h2 class="text-base font-semibold text-slate-800">Timeline</h2>
                    <a v-if="googleMapsUrls.route" :href="googleMapsUrls.route" target="_blank" class="text-sm text-primary hover:underline">Open Full Route</a>
                </div>
                <div v-if="timeline.length" class="space-y-3">
                    <div v-for="event in timeline" :key="`${event.type}-${event.timestamp}-${event.title}`" class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="flex items-start gap-3">
                            <div class="rounded-full bg-primary/10 p-2 flex-shrink-0">
                                <Lucide :icon="event.icon || 'Circle'" class="h-4 w-4 text-primary" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="text-sm font-semibold text-slate-800">{{ event.title }}</p>
                                    <span v-if="event.is_active" class="rounded-full bg-info/10 px-2 py-0.5 text-[11px] font-medium text-info">Active</span>
                                </div>
                                <p class="mt-1 text-xs text-slate-500">{{ event.timestamp || 'N/A' }}</p>
                                <p v-if="event.description" class="mt-2 text-sm text-slate-600">{{ event.description }}</p>
                                <p v-if="event.location" class="mt-1 text-xs text-slate-500">Location: {{ event.location }}</p>
                                <p v-if="event.forced_by" class="mt-1 text-xs text-slate-500">Forced by: {{ event.forced_by }}</p>
                            </div>
                        </div>
                    </div>
                </div>
                <p v-else class="text-sm text-slate-500">No timeline data available.</p>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-span-12 xl:col-span-4 space-y-4 sm:space-y-6">

            <!-- HOS Snapshot -->
            <div class="box box--stacked p-4 sm:p-6">
                <h2 class="text-base font-semibold text-slate-800">HOS Snapshot</h2>
                <div v-if="fmcsaStatus" class="mt-5 grid grid-cols-2 gap-3">
                    <div class="rounded-lg bg-slate-50 p-4">
                        <p class="text-xs text-slate-500">Driving Left</p>
                        <p class="mt-1 text-lg font-semibold text-slate-800">{{ fmcsaStatus?.driving_limit?.remaining_hours ?? 0 }}h</p>
                    </div>
                    <div class="rounded-lg bg-slate-50 p-4">
                        <p class="text-xs text-slate-500">Duty Left</p>
                        <p class="mt-1 text-lg font-semibold text-slate-800">{{ fmcsaStatus?.duty_period?.remaining_hours ?? 0 }}h</p>
                    </div>
                    <div class="rounded-lg bg-slate-50 p-4">
                        <p class="text-xs text-slate-500">Weekly Left</p>
                        <p class="mt-1 text-lg font-semibold text-slate-800">{{ fmcsaStatus?.weekly_cycle?.hours_remaining ?? 0 }}h</p>
                    </div>
                    <div class="rounded-lg bg-slate-50 p-4">
                        <p class="text-xs text-slate-500">Break Due In</p>
                        <p class="mt-1 text-lg font-semibold text-slate-800">{{ Math.round((fmcsaStatus?.break_requirement?.minutes_until_break_required ?? 0) / 60 * 10) / 10 }}h</p>
                    </div>
                </div>
                <p v-else class="mt-4 text-sm text-slate-500">HOS data not available.</p>

                <div class="mt-5 rounded-xl border border-dashed border-slate-200 p-4">
                    <p class="text-sm font-semibold text-slate-700">Destination Verification</p>
                    <p class="mt-2 text-sm text-slate-600">{{ destinationVerification?.message || 'No verification available.' }}</p>
                    <p v-if="destinationVerification?.distance_formatted" class="mt-2 text-xs text-slate-500">
                        Distance: {{ destinationVerification.distance_formatted }}
                    </p>
                </div>

                <a v-if="hosLogRoute" :href="hosLogRoute" class="mt-4 inline-flex items-center gap-2 text-sm text-primary hover:underline">
                    <Lucide icon="Clock3" class="h-4 w-4" />
                    Open HOS History
                </a>
            </div>

            <!-- GPS Stats -->
            <div class="box box--stacked p-4 sm:p-6">
                <h2 class="mb-4 text-base font-semibold text-slate-800">GPS Stats</h2>
                <div v-if="gpsStats" class="grid grid-cols-2 gap-3">
                    <div class="rounded-lg bg-slate-50 p-4">
                        <p class="text-xs text-slate-500">GPS Points</p>
                        <p class="mt-1 text-lg font-semibold text-slate-800">{{ gpsStats.total_points }}</p>
                    </div>
                    <div class="rounded-lg bg-slate-50 p-4">
                        <p class="text-xs text-slate-500">Distance</p>
                        <p class="mt-1 text-lg font-semibold text-slate-800">{{ gpsStats.total_distance_miles }} mi</p>
                    </div>
                    <div class="rounded-lg bg-slate-50 p-4">
                        <p class="text-xs text-slate-500">Avg Speed</p>
                        <p class="mt-1 text-lg font-semibold text-slate-800">{{ gpsStats.average_speed_mph }} mph</p>
                    </div>
                    <div class="rounded-lg bg-slate-50 p-4">
                        <p class="text-xs text-slate-500">Max Speed</p>
                        <p class="mt-1 text-lg font-semibold text-slate-800">{{ gpsStats.max_speed_mph }} mph</p>
                    </div>
                </div>
                <p v-else class="text-sm text-slate-500">No GPS data recorded yet.</p>
            </div>

            <!-- Trip Documents -->
            <div class="box box--stacked p-4 sm:p-6">
                <h2 class="mb-4 text-base font-semibold text-slate-800">Trip Documents</h2>
                <div v-if="tripDocuments.length || tripReportPdfs.length || inspectionDocuments.length" class="space-y-3">
                    <a v-for="document in tripReportPdfs" :key="`report-${document.id}`" :href="document.preview_url" target="_blank"
                        class="block rounded-xl border border-slate-200 bg-slate-50 p-4 hover:bg-slate-100">
                        <p class="text-sm font-semibold text-primary">{{ document.label }}</p>
                        <p class="mt-1 text-sm text-slate-700">{{ document.file_name }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ document.size_label }} · {{ document.created_at }}</p>
                    </a>
                    <a v-for="document in inspectionDocuments" :key="`inspection-${document.id}`" :href="document.preview_url" target="_blank"
                        class="block rounded-xl border border-slate-200 bg-slate-50 p-4 hover:bg-slate-100">
                        <p class="text-sm font-semibold text-primary">{{ document.label }}</p>
                        <p class="mt-1 text-sm text-slate-700">{{ document.file_name }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ document.size_label }} · {{ document.created_at }}</p>
                    </a>
                    <a v-for="document in tripDocuments" :key="`doc-${document.id}`" :href="document.preview_url" target="_blank"
                        class="block rounded-xl border border-slate-200 bg-slate-50 p-4 hover:bg-slate-100">
                        <p class="text-sm font-semibold text-primary">{{ document.label }}</p>
                        <p class="mt-1 text-sm text-slate-700">{{ document.file_name }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ document.size_label }} · {{ document.created_at }}</p>
                    </a>
                </div>
                <p v-else class="text-sm text-slate-500">No documents available.</p>
            </div>

            <!-- Recent HOS Locations (admin only) -->
            <div v-if="recentHosLocations.length" class="box box--stacked p-4 sm:p-6">
                <h2 class="mb-4 text-base font-semibold text-slate-800">Recent HOS Locations</h2>
                <div class="space-y-3">
                    <a v-for="location in recentHosLocations" :key="location.id"
                        :href="location.maps_url || '#'" target="_blank"
                        class="block rounded-xl border border-slate-200 bg-slate-50 p-4 hover:bg-slate-100">
                        <p class="text-sm font-medium text-slate-800">{{ location.status }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ location.start_time }}</p>
                        <p class="mt-2 text-xs text-slate-500">{{ location.formatted_address || location.coordinates }}</p>
                    </a>
                </div>
            </div>

            <!-- HOS Entries -->
            <div v-if="hosEntries.length" class="box box--stacked p-4 sm:p-6">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <h2 class="text-base font-semibold text-slate-800">HOS Entries</h2>
                    <a v-if="hosLogRoute" :href="hosLogRoute" class="text-sm text-primary hover:underline">View Log</a>
                </div>
                <div class="space-y-3">
                    <div v-for="entry in hosEntries" :key="entry.id" class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-sm font-semibold text-slate-800">{{ entry.status }}</p>
                            <span v-if="entry.is_active" class="rounded-full bg-info/10 px-2 py-1 text-[11px] font-medium text-info">Open</span>
                        </div>
                        <p class="mt-2 text-xs text-slate-500">{{ entry.start_time }}<span v-if="entry.end_time"> – {{ entry.end_time }}</span></p>
                        <p class="mt-1 text-sm text-slate-600">{{ entry.duration }}</p>
                        <p v-if="entry.location" class="mt-1 text-xs text-slate-500">{{ entry.location }}</p>
                    </div>
                </div>
            </div>

            <!-- Violations -->
            <div v-if="violations.length" class="box box--stacked p-4 sm:p-6">
                <h2 class="mb-4 text-base font-semibold text-slate-800">Violations</h2>
                <div class="space-y-3">
                    <div v-for="violation in violations" :key="violation.id" class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="text-sm font-semibold text-slate-800">{{ violation.type }}</p>
                            <span class="rounded-full bg-danger/10 px-2 py-0.5 text-[11px] font-medium text-danger">{{ violation.severity }}</span>
                            <span v-if="violation.acknowledged" class="rounded-full bg-success/10 px-2 py-0.5 text-[11px] font-medium text-success">Acknowledged</span>
                            <span v-if="violation.forgiven" class="rounded-full bg-warning/10 px-2 py-0.5 text-[11px] font-medium text-warning">Forgiven</span>
                        </div>
                        <p class="mt-2 text-xs text-slate-500">{{ violation.date || 'N/A' }}</p>
                        <p class="mt-1 text-sm text-slate-600">Exceeded: {{ violation.hours_exceeded || 'N/A' }}</p>
                        <p v-if="violation.reference" class="mt-1 text-xs text-slate-500">Rule: {{ violation.reference }}</p>
                    </div>
                </div>
            </div>

            <!-- Pauses -->
            <div v-if="pauses.length" class="box box--stacked p-4 sm:p-6">
                <h2 class="mb-4 text-base font-semibold text-slate-800">Pause History</h2>
                <div class="space-y-3">
                    <div v-for="pause in pauses" :key="pause.id" class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-sm font-semibold text-slate-800">{{ pause.reason || 'Pause' }}</p>
                        <p class="mt-2 text-xs text-slate-500">{{ pause.started_at }}<span v-if="pause.ended_at"> – {{ pause.ended_at }}</span></p>
                        <p class="mt-1 text-sm text-slate-600">Duration: {{ pause.duration }}</p>
                        <p v-if="pause.location" class="mt-1 text-xs text-slate-500">{{ pause.location }}</p>
                        <p v-if="pause.forced_by" class="mt-1 text-xs text-slate-500">Forced by: {{ pause.forced_by }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
