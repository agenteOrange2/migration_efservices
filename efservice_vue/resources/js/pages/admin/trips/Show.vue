<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import RazeLayout from '@/layouts/RazeLayout.vue'

declare function route(name: string, params?: any): string

defineOptions({ layout: RazeLayout })

const props = defineProps<{
    trip: any
    gpsStats: null | { total_points: number; total_distance_miles: number; average_speed_mph: number; max_speed_mph: number; stationary_periods: number; duration_minutes: number | null }
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

function badgeClass(status: string) {
    if (status === 'completed') return 'bg-success/10 text-success'
    if (status === 'cancelled' || status === 'rejected' || status === 'failed') return 'bg-danger/10 text-danger'
    if (status === 'in_progress' || status === 'accepted' || status === 'paused') return 'bg-warning/10 text-warning'
    if (status === 'pending' || status === 'scheduled') return 'bg-info/10 text-info'
    return 'bg-primary/10 text-primary'
}

function destroyTrip() {
    if (!confirm(`Delete trip ${props.trip.trip_number}?`)) return
    router.delete(namedRoute('destroy', props.trip.id))
}

function emergencyAction(routeName: string, label: string) {
    if (!confirm(`${label} ${props.trip.trip_number}?`)) return
    router.post(namedRoute(routeName as keyof typeof defaultRouteNames, props.trip.id), {}, { preserveScroll: true })
}
</script>

<template>
    <Head :title="trip.trip_number" />

    <div class="grid grid-cols-12 gap-6">
        <div class="col-span-12">
            <div class="box box--stacked p-6">
                <div class="flex flex-col xl:flex-row items-start xl:items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="rounded-xl border border-primary/20 bg-primary/10 p-3">
                            <Lucide icon="Truck" class="h-8 w-8 text-primary" />
                        </div>
                        <div>
                            <div class="flex flex-wrap items-center gap-3">
                                <h1 class="text-2xl font-bold text-slate-800">{{ trip.trip_number }}</h1>
                                <span class="rounded-full px-3 py-1 text-xs font-medium" :class="badgeClass(trip.status)">
                                    {{ trip.status_label }}
                                </span>
                                <span v-if="trip.has_violations" class="rounded-full bg-danger/10 px-3 py-1 text-xs font-medium text-danger">Has Violations</span>
                                <span v-if="trip.forgot_to_close" class="rounded-full bg-warning/10 px-3 py-1 text-xs font-medium text-warning">Ghost Log</span>
                            </div>
                            <p class="mt-1 text-sm text-slate-500">{{ trip.carrier_name || 'N/A' }} - {{ trip.driver_name }}</p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <Button v-if="trip.can_force_start" variant="primary" class="flex items-center gap-2" @click="emergencyAction('forceStart', 'Start')"><Lucide icon="Play" class="h-4 w-4" />Force Start</Button>
                        <Button v-if="trip.can_force_pause" variant="warning" class="flex items-center gap-2" @click="emergencyAction('forcePause', 'Pause')"><Lucide icon="Pause" class="h-4 w-4" />Force Pause</Button>
                        <Button v-if="trip.can_force_resume" variant="success" class="flex items-center gap-2" @click="emergencyAction('forceResume', 'Resume')"><Lucide icon="Play" class="h-4 w-4" />Force Resume</Button>
                        <Button v-if="trip.can_force_end" variant="danger" class="flex items-center gap-2" @click="emergencyAction('forceEnd', 'End')"><Lucide icon="Square" class="h-4 w-4" />Force End</Button>
                        <Link v-if="trip.can_edit" :href="namedRoute('edit', trip.id)"><Button variant="primary" class="flex items-center gap-2"><Lucide icon="PenLine" class="h-4 w-4" />Edit</Button></Link>
                        <Button v-if="trip.can_delete" variant="danger" class="flex items-center gap-2" @click="destroyTrip"><Lucide icon="Trash2" class="h-4 w-4" />Delete</Button>
                        <Link :href="namedRoute('index')"><Button variant="outline-secondary" class="flex items-center gap-2"><Lucide icon="ArrowLeft" class="h-4 w-4" />Back</Button></Link>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12 xl:col-span-8 space-y-6">
            <div class="box box--stacked p-6">
                <h2 class="mb-4 text-base font-semibold text-slate-800">Trip Information</h2>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="rounded-xl bg-slate-50 p-4">
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Origin</p>
                        <p class="mt-2 text-sm text-slate-800">{{ trip.origin_address || 'N/A' }}</p>
                        <a v-if="googleMapsUrls.origin" :href="googleMapsUrls.origin" target="_blank" class="mt-3 inline-flex items-center gap-2 text-xs text-primary hover:underline">
                            <Lucide icon="MapPin" class="h-3 w-3" />
                            Open in Maps
                        </a>
                    </div>
                    <div class="rounded-xl bg-slate-50 p-4">
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Destination</p>
                        <p class="mt-2 text-sm text-slate-800">{{ trip.destination_address || 'N/A' }}</p>
                        <a v-if="googleMapsUrls.destination" :href="googleMapsUrls.destination" target="_blank" class="mt-3 inline-flex items-center gap-2 text-xs text-primary hover:underline">
                            <Lucide icon="Flag" class="h-3 w-3" />
                            Open in Maps
                        </a>
                    </div>
                </div>

                <div class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <div class="rounded-lg border border-slate-200 bg-white p-4"><p class="text-xs text-slate-500">Driver</p><p class="mt-1 text-sm font-medium text-slate-800">{{ trip.driver_name }}</p><p class="text-xs text-slate-500">{{ trip.driver_email || 'No email' }}</p></div>
                    <div class="rounded-lg border border-slate-200 bg-white p-4"><p class="text-xs text-slate-500">Vehicle</p><p class="mt-1 text-sm font-medium text-slate-800">{{ trip.vehicle_label }}</p></div>
                    <div class="rounded-lg border border-slate-200 bg-white p-4"><p class="text-xs text-slate-500">Scheduled Start</p><p class="mt-1 text-sm font-medium text-slate-800">{{ trip.scheduled_start || 'N/A' }}</p></div>
                    <div class="rounded-lg border border-slate-200 bg-white p-4"><p class="text-xs text-slate-500">Scheduled End</p><p class="mt-1 text-sm font-medium text-slate-800">{{ trip.scheduled_end || 'N/A' }}</p></div>
                    <div class="rounded-lg border border-slate-200 bg-white p-4"><p class="text-xs text-slate-500">Actual Start</p><p class="mt-1 text-sm font-medium text-slate-800">{{ trip.actual_start || 'N/A' }}</p></div>
                    <div class="rounded-lg border border-slate-200 bg-white p-4"><p class="text-xs text-slate-500">Actual End</p><p class="mt-1 text-sm font-medium text-slate-800">{{ trip.actual_end || 'N/A' }}</p></div>
                    <div class="rounded-lg border border-slate-200 bg-white p-4"><p class="text-xs text-slate-500">Estimated Duration</p><p class="mt-1 text-sm font-medium text-slate-800">{{ trip.estimated_duration || 'N/A' }}</p></div>
                    <div class="rounded-lg border border-slate-200 bg-white p-4"><p class="text-xs text-slate-500">Actual Duration</p><p class="mt-1 text-sm font-medium text-slate-800">{{ trip.actual_duration || 'N/A' }}</p></div>
                </div>

                <div v-if="trip.description || trip.notes || trip.driver_notes || trip.load_type || trip.load_weight" class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-2">
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

            <div class="box box--stacked p-6">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <h2 class="text-base font-semibold text-slate-800">Timeline</h2>
                    <a v-if="googleMapsUrls.route" :href="googleMapsUrls.route" target="_blank" class="text-sm text-primary hover:underline">Open Full Route</a>
                </div>
                <div v-if="timeline.length" class="space-y-3">
                    <div v-for="event in timeline" :key="`${event.type}-${event.timestamp}-${event.title}`" class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="flex items-start gap-3">
                            <div class="rounded-full bg-primary/10 p-2">
                                <Lucide :icon="event.icon || 'Circle'" class="h-4 w-4 text-primary" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="text-sm font-semibold text-slate-800">{{ event.title }}</p>
                            <span v-if="event.is_active" class="rounded-full bg-info/10 px-2 py-0.5 text-[11px] font-medium text-info">Active</span>
                                </div>
                                <p class="mt-1 text-xs text-slate-500">{{ event.timestamp || 'N/A' }}</p>
                                <p v-if="event.description" class="mt-2 text-sm text-slate-600">{{ event.description }}</p>
                                <p v-if="event.location" class="mt-2 text-xs text-slate-500">Location: {{ event.location }}</p>
                                <p v-if="event.forced_by" class="mt-1 text-xs text-slate-500">Forced by: {{ event.forced_by }}</p>
                            </div>
                        </div>
                    </div>
                </div>
                <p v-else class="text-sm text-slate-500">No timeline data available.</p>
            </div>

            <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
                <div class="box box--stacked p-6">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <h2 class="text-base font-semibold text-slate-800">HOS Entries</h2>
                        <a v-if="hosLogRoute" :href="hosLogRoute" class="text-sm text-primary hover:underline">Open HOS Log</a>
                    </div>
                    <div v-if="hosEntries.length" class="space-y-3">
                        <div v-for="entry in hosEntries" :key="entry.id" class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <div class="flex items-center justify-between gap-3">
                                <p class="text-sm font-semibold text-slate-800">{{ entry.status }}</p>
                                <span v-if="entry.is_active" class="rounded-full bg-info/10 px-2 py-1 text-[11px] font-medium text-info">Open</span>
                            </div>
                            <p class="mt-2 text-xs text-slate-500">{{ entry.start_time }}<span v-if="entry.end_time"> - {{ entry.end_time }}</span></p>
                            <p class="mt-2 text-sm text-slate-600">{{ entry.duration }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ entry.location || 'No location captured' }}</p>
                        </div>
                    </div>
                    <p v-else class="text-sm text-slate-500">No HOS entries available.</p>
                </div>

                <div class="box box--stacked p-6">
                    <h2 class="mb-4 text-base font-semibold text-slate-800">Pauses</h2>
                    <div v-if="pauses.length" class="space-y-3">
                        <div v-for="pause in pauses" :key="pause.id" class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-sm font-semibold text-slate-800">{{ pause.reason || 'Pause' }}</p>
                            <p class="mt-2 text-xs text-slate-500">{{ pause.started_at }}<span v-if="pause.ended_at"> - {{ pause.ended_at }}</span></p>
                            <p class="mt-2 text-sm text-slate-600">Duration: {{ pause.duration }}</p>
                            <p v-if="pause.location" class="mt-1 text-xs text-slate-500">{{ pause.location }}</p>
                            <p v-if="pause.forced_by" class="mt-1 text-xs text-slate-500">Forced by: {{ pause.forced_by }}</p>
                        </div>
                    </div>
                    <p v-else class="text-sm text-slate-500">No pause records found.</p>
                </div>
            </div>

            <div class="box box--stacked p-6">
                <h2 class="mb-4 text-base font-semibold text-slate-800">Violations</h2>
                <div v-if="violations.length" class="space-y-3">
                    <div v-for="violation in violations" :key="violation.id" class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="text-sm font-semibold text-slate-800">{{ violation.type }}</p>
                            <span class="rounded-full bg-danger/10 px-2 py-0.5 text-[11px] font-medium text-danger">{{ violation.severity }}</span>
                            <span v-if="violation.acknowledged" class="rounded-full bg-success/10 px-2 py-0.5 text-[11px] font-medium text-success">Acknowledged</span>
                            <span v-if="violation.forgiven" class="rounded-full bg-warning/10 px-2 py-0.5 text-[11px] font-medium text-warning">Forgiven</span>
                        </div>
                        <p class="mt-2 text-xs text-slate-500">{{ violation.date || 'N/A' }}</p>
                        <p class="mt-2 text-sm text-slate-600">Exceeded: {{ violation.hours_exceeded || 'N/A' }}</p>
                        <p v-if="violation.reference" class="mt-1 text-xs text-slate-500">Rule: {{ violation.reference }}</p>
                    </div>
                </div>
                <p v-else class="text-sm text-slate-500">No violations recorded for this trip.</p>
            </div>
        </div>

        <div class="col-span-12 xl:col-span-4 space-y-6">
            <div class="box box--stacked p-6">
                <h2 class="mb-4 text-base font-semibold text-slate-800">GPS & Destination</h2>
                <div v-if="gpsStats" class="grid grid-cols-2 gap-3">
                    <div class="rounded-lg bg-slate-50 p-4"><p class="text-xs text-slate-500">GPS Points</p><p class="mt-1 text-lg font-semibold text-slate-800">{{ gpsStats.total_points }}</p></div>
                    <div class="rounded-lg bg-slate-50 p-4"><p class="text-xs text-slate-500">Distance</p><p class="mt-1 text-lg font-semibold text-slate-800">{{ gpsStats.total_distance_miles }} mi</p></div>
                    <div class="rounded-lg bg-slate-50 p-4"><p class="text-xs text-slate-500">Avg Speed</p><p class="mt-1 text-lg font-semibold text-slate-800">{{ gpsStats.average_speed_mph }} mph</p></div>
                    <div class="rounded-lg bg-slate-50 p-4"><p class="text-xs text-slate-500">Max Speed</p><p class="mt-1 text-lg font-semibold text-slate-800">{{ gpsStats.max_speed_mph }} mph</p></div>
                </div>
                <p v-else class="text-sm text-slate-500">No GPS stats available yet.</p>

                <div class="mt-5 rounded-xl border border-dashed border-slate-200 p-4">
                    <p class="text-sm font-semibold text-slate-700">Destination Verification</p>
                    <p class="mt-2 text-sm text-slate-600">{{ destinationVerification?.message || 'No verification available.' }}</p>
                    <p v-if="destinationVerification?.distance_formatted" class="mt-2 text-xs text-slate-500">
                        Distance from destination: {{ destinationVerification.distance_formatted }}
                    </p>
                </div>

                <div v-if="recentHosLocations.length" class="mt-5 space-y-3">
                    <p class="text-sm font-semibold text-slate-700">Recent HOS Locations</p>
                    <a v-for="location in recentHosLocations" :key="location.id" :href="location.maps_url || '#'" target="_blank" class="block rounded-xl border border-slate-200 bg-slate-50 p-4 hover:bg-slate-100">
                        <p class="text-sm font-medium text-slate-800">{{ location.status }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ location.start_time }}</p>
                        <p class="mt-2 text-xs text-slate-500">{{ location.formatted_address || location.coordinates }}</p>
                    </a>
                </div>
            </div>

            <div class="box box--stacked p-6">
                <h2 class="mb-4 text-base font-semibold text-slate-800">Generated Reports</h2>
                <div v-if="tripReportPdfs.length || inspectionDocuments.length || tripDocuments.length" class="space-y-3">
                    <a v-for="document in tripReportPdfs" :key="`report-${document.id}`" :href="document.preview_url" target="_blank" class="block rounded-xl border border-slate-200 bg-slate-50 p-4 hover:bg-slate-100">
                        <p class="text-sm font-semibold text-primary">{{ document.label }}</p>
                        <p class="mt-1 text-sm text-slate-700">{{ document.file_name }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ document.size_label }} - {{ document.created_at }}</p>
                    </a>
                    <a v-for="document in inspectionDocuments" :key="`inspection-${document.id}`" :href="document.preview_url" target="_blank" class="block rounded-xl border border-slate-200 bg-slate-50 p-4 hover:bg-slate-100">
                        <p class="text-sm font-semibold text-primary">{{ document.label }}</p>
                        <p class="mt-1 text-sm text-slate-700">{{ document.file_name }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ document.size_label }} - {{ document.created_at }}</p>
                    </a>
                    <a v-for="document in tripDocuments" :key="`doc-${document.id}`" :href="document.preview_url" target="_blank" class="block rounded-xl border border-slate-200 bg-slate-50 p-4 hover:bg-slate-100">
                        <p class="text-sm font-semibold text-primary">{{ document.label }}</p>
                        <p class="mt-1 text-sm text-slate-700">{{ document.file_name }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ document.size_label }} - {{ document.created_at }}</p>
                    </a>
                </div>
                <p v-else class="text-sm text-slate-500">No generated documents available.</p>
            </div>
        </div>
    </div>
</template>
