<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { ref, onMounted } from 'vue'
import Button from '@/components/Base/Button'
import FormInput from '@/components/Base/Form/FormInput.vue'
import Lucide from '@/components/Base/Lucide'
import RazeLayout from '@/layouts/RazeLayout.vue'
import { Dialog } from '@/components/Base/Headless'
import TripRouteMap from '@/components/TripRouteMap.vue'
import { useGpsTracking } from '@/composables/useGpsTracking'

declare function route(name: string, params?: any): string

defineOptions({ layout: RazeLayout })

const props = defineProps<{
    driver: { id: number; full_name: string; carrier_name: string | null }
    trip: any
    gpsRoute?: Array<{ lat: number; lng: number }>
    fmcsaStatus: any
    isOnBreak: boolean
    gpsStats: any | null
    timeline: any[]
    hosEntries: any[]
    violations: any[]
    pauses: any[]
    destinationVerification: any
    googleMapsUrls: { origin: string | null; destination: string | null; route: string | null }
    tripReportPdfs: any[]
    inspectionDocuments: any[]
    tripDocuments: any[]
    hosLogRoute?: string | null
}>()

const rejectModalOpen = ref(false)
const uploadModalOpen = ref(false)
const pauseModalOpen = ref(false)

const rejectForm = useForm({ reason: '' })
const pauseForm = useForm({ reason: '' })
const uploadForm = useForm({
    document_types: ['other'],
    document_notes: [''],
})

function statusTone(status: string) {
    if (status === 'completed') return 'bg-primary/10 text-primary'
    if (status === 'in_progress') return 'bg-slate-700 text-white'
    if (status === 'paused') return 'bg-slate-200 text-slate-700'
    if (status === 'accepted') return 'bg-slate-100 text-slate-700'
    if (status === 'pending') return 'bg-slate-100 text-slate-600'
    return 'bg-slate-100 text-slate-500'
}

function acceptTrip() {
    router.post(route('driver.trips.accept', props.trip.id), {}, { preserveScroll: true })
}

function rejectTrip() {
    rejectForm.post(route('driver.trips.reject', props.trip.id), {
        preserveScroll: true,
        onSuccess: () => {
            rejectModalOpen.value = false
            rejectForm.reset()
        },
    })
}

function pauseTrip() {
    pauseForm.post(route('driver.trips.pause', props.trip.id), {
        preserveScroll: true,
        onSuccess: () => {
            pauseModalOpen.value = false
            pauseForm.reset()
            gps.stop()
        },
    })
}

function resumeTrip() {
    router.post(route('driver.trips.resume', props.trip.id), {}, {
        preserveScroll: true,
        onSuccess: () => { if (!gps.isTracking.value) gps.start() },
    })
}

// GPS tracking — starts automatically when trip is in_progress, stops on pause/end
const gps = useGpsTracking({ tripId: props.trip.id })

onMounted(() => {
    if (props.trip.status === 'in_progress') {
        gps.start()
    }
})

function deleteDocument(documentId: number) {
    if (!confirm('Delete this document?')) return
    router.delete(route('driver.trips.documents.delete', { trip: props.trip.id, media: documentId }), {
        preserveScroll: true,
    })
}

function addDocumentRow() {
    if (uploadForm.document_types.length >= 10) return
    uploadForm.document_types.push('other')
    uploadForm.document_notes.push('')
}

function removeDocumentRow(index: number) {
    if (uploadForm.document_types.length <= 1) return
    uploadForm.document_types.splice(index, 1)
    uploadForm.document_notes.splice(index, 1)
}

function uploadDocuments() {
    const formData = new FormData()

    uploadForm.document_types.forEach((type, index) => {
        const input = document.getElementById(`trip-doc-file-${index}`) as HTMLInputElement | null
        const file = input?.files?.[0]

        if (file) {
            formData.append(`documents[${index}]`, file)
            formData.append(`document_types[${index}]`, type)
            formData.append(`document_notes[${index}]`, uploadForm.document_notes[index] || '')
        }
    })

    uploadForm.transform(() => formData).post(route('driver.trips.documents.upload', props.trip.id), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            uploadModalOpen.value = false
            uploadForm.reset()
            uploadForm.document_types = ['other']
            uploadForm.document_notes = ['']
        },
    })
}
</script>

<template>
    <Head :title="trip.trip_number" />

    <div class="grid grid-cols-12 gap-6">
        <div class="col-span-12">
            <div class="box box--stacked p-6">
                <div class="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
                    <div class="space-y-4">
                        <Link :href="route('driver.trips.index')" class="inline-flex items-center gap-2 text-sm font-medium text-slate-500 transition hover:text-primary">
                            <Lucide icon="ArrowLeft" class="h-4 w-4" />
                            Back to Trips
                        </Link>

                        <div class="flex flex-wrap items-center gap-3">
                            <h1 class="text-2xl font-bold text-slate-800">{{ trip.trip_number }}</h1>
                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium" :class="statusTone(trip.status)">
                                {{ trip.status_label }}
                            </span>
                            <span v-if="trip.is_quick_trip" class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">Quick Trip</span>
                        </div>

                        <p class="text-sm text-slate-500">
                            Driver: <span class="font-medium text-slate-700">{{ driver.full_name }}</span>
                            <span v-if="driver.carrier_name"> · Carrier: <span class="font-medium text-slate-700">{{ driver.carrier_name }}</span></span>
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <Button v-if="trip.can_accept" variant="primary" class="gap-2" @click="acceptTrip">
                            <Lucide icon="CheckCircle2" class="h-4 w-4" />
                            Accept
                        </Button>
                        <Button v-if="trip.can_reject" variant="outline-secondary" class="gap-2" @click="rejectModalOpen = true">
                            <Lucide icon="XCircle" class="h-4 w-4" />
                            Reject
                        </Button>
                        <Link v-if="trip.can_start" :href="route('driver.trips.start.form', trip.id)">
                            <Button variant="primary" class="gap-2">
                                <Lucide icon="Play" class="h-4 w-4" />
                                Start
                            </Button>
                        </Link>
                        <Button v-if="trip.can_pause" variant="outline-secondary" class="gap-2" @click="pauseModalOpen = true">
                            <Lucide icon="Pause" class="h-4 w-4" />
                            Pause
                        </Button>
                        <Button v-if="trip.can_resume" variant="primary" class="gap-2" @click="resumeTrip">
                            <Lucide icon="Play" class="h-4 w-4" />
                            Resume
                        </Button>
                        <Link v-if="trip.can_end" :href="route('driver.trips.end.form', trip.id)">
                            <Button variant="outline-secondary" class="gap-2">
                                <Lucide icon="Square" class="h-4 w-4" />
                                End
                            </Button>
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12 xl:col-span-8 space-y-6">
            <div v-if="trip.requires_completion && Object.keys(trip.missing_fields || {}).length" class="box box--stacked p-5">
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
                    <p class="font-semibold text-slate-800">This quick trip still needs route details.</p>
                    <p class="mt-1">Missing: {{ Object.values(trip.missing_fields).join(', ') }}</p>
                </div>
            </div>

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
                    <div class="rounded-lg border border-slate-200 bg-white p-4"><p class="text-xs text-slate-500">Vehicle</p><p class="mt-1 text-sm font-medium text-slate-800">{{ trip.vehicle_label }}</p></div>
                    <div class="rounded-lg border border-slate-200 bg-white p-4"><p class="text-xs text-slate-500">License Plate</p><p class="mt-1 text-sm font-medium text-slate-800">{{ trip.vehicle_license_plate || 'N/A' }}</p></div>
                    <div class="rounded-lg border border-slate-200 bg-white p-4"><p class="text-xs text-slate-500">Scheduled Start</p><p class="mt-1 text-sm font-medium text-slate-800">{{ trip.scheduled_start || 'N/A' }}</p></div>
                    <div class="rounded-lg border border-slate-200 bg-white p-4"><p class="text-xs text-slate-500">Actual Start</p><p class="mt-1 text-sm font-medium text-slate-800">{{ trip.actual_start || 'N/A' }}</p></div>
                    <div class="rounded-lg border border-slate-200 bg-white p-4"><p class="text-xs text-slate-500">Actual End</p><p class="mt-1 text-sm font-medium text-slate-800">{{ trip.actual_end || 'N/A' }}</p></div>
                    <div class="rounded-lg border border-slate-200 bg-white p-4"><p class="text-xs text-slate-500">Estimated Duration</p><p class="mt-1 text-sm font-medium text-slate-800">{{ trip.estimated_duration || 'N/A' }}</p></div>
                    <div class="rounded-lg border border-slate-200 bg-white p-4"><p class="text-xs text-slate-500">Actual Duration</p><p class="mt-1 text-sm font-medium text-slate-800">{{ trip.actual_duration || 'N/A' }}</p></div>
                    <div class="rounded-lg border border-slate-200 bg-white p-4"><p class="text-xs text-slate-500">Break Status</p><p class="mt-1 text-sm font-medium text-slate-800">{{ isOnBreak ? 'On Break' : 'Not On Break' }}</p></div>
                </div>
            </div>

            <div v-if="trip.origin_lat || (gpsRoute && gpsRoute.length > 0)" class="box box--stacked p-6">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <Lucide icon="Map" class="h-4 w-4 text-primary" />
                        <h2 class="text-base font-semibold text-slate-800">Route Map</h2>
                        <span v-if="gpsRoute && gpsRoute.length > 0" class="rounded-full bg-primary/10 px-2 py-0.5 text-[11px] font-medium text-primary">
                            {{ gpsRoute.length }} GPS points
                        </span>
                        <span v-else class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] text-slate-500">
                            Estimated route
                        </span>
                    </div>
                    <a v-if="googleMapsUrls.route" :href="googleMapsUrls.route" target="_blank" class="flex items-center gap-1 text-sm text-primary hover:underline">
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
                                    <span v-if="event.is_active" class="rounded-full bg-primary/10 px-2 py-0.5 text-[11px] font-medium text-primary">Active</span>
                                </div>
                                <p class="mt-1 text-xs text-slate-500">{{ event.timestamp || 'N/A' }}</p>
                                <p v-if="event.description" class="mt-2 text-sm text-slate-600">{{ event.description }}</p>
                                <p v-if="event.location" class="mt-1 text-xs text-slate-500">Location: {{ event.location }}</p>
                            </div>
                        </div>
                    </div>
                </div>
                <p v-else class="text-sm text-slate-500">No timeline data available yet.</p>
            </div>
        </div>

        <div class="col-span-12 xl:col-span-4 space-y-6">
            <div class="box box--stacked p-6">
                <h2 class="text-base font-semibold text-slate-800">HOS Snapshot</h2>
                <div class="mt-5 grid grid-cols-2 gap-3">
                    <div class="rounded-lg bg-slate-50 p-4"><p class="text-xs text-slate-500">Driving Left</p><p class="mt-1 text-lg font-semibold text-slate-800">{{ fmcsaStatus?.driving_limit?.remaining_hours ?? 0 }}h</p></div>
                    <div class="rounded-lg bg-slate-50 p-4"><p class="text-xs text-slate-500">Duty Left</p><p class="mt-1 text-lg font-semibold text-slate-800">{{ fmcsaStatus?.duty_period?.remaining_hours ?? 0 }}h</p></div>
                    <div class="rounded-lg bg-slate-50 p-4"><p class="text-xs text-slate-500">Weekly Left</p><p class="mt-1 text-lg font-semibold text-slate-800">{{ fmcsaStatus?.weekly_cycle?.hours_remaining ?? 0 }}h</p></div>
                    <div class="rounded-lg bg-slate-50 p-4"><p class="text-xs text-slate-500">Break Due In</p><p class="mt-1 text-lg font-semibold text-slate-800">{{ Math.round((fmcsaStatus?.break_requirement?.minutes_until_break_required ?? 0) / 60 * 10) / 10 }}h</p></div>
                </div>

                <div class="mt-5 rounded-xl border border-dashed border-slate-200 p-4">
                    <p class="text-sm font-semibold text-slate-700">Destination Verification</p>
                    <p class="mt-2 text-sm text-slate-600">{{ destinationVerification?.message || 'No verification available.' }}</p>
                </div>

                <a v-if="hosLogRoute" :href="hosLogRoute" class="mt-4 inline-flex items-center gap-2 text-sm text-primary hover:underline">
                    <Lucide icon="Clock3" class="h-4 w-4" />
                    Open HOS History
                </a>
            </div>

            <div class="box box--stacked p-6">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <h2 class="text-base font-semibold text-slate-800">Trip Documents</h2>
                    <Button v-if="trip.can_upload_documents" variant="outline-secondary" class="gap-2" @click="uploadModalOpen = true">
                        <Lucide icon="Upload" class="h-4 w-4" />
                        Upload
                    </Button>
                </div>

                <div v-if="tripDocuments.length || tripReportPdfs.length || inspectionDocuments.length" class="space-y-3">
                    <a v-for="document in tripReportPdfs" :key="`report-${document.id}`" :href="document.preview_url" target="_blank" class="block rounded-xl border border-slate-200 bg-slate-50 p-4 hover:bg-slate-100">
                        <p class="text-sm font-semibold text-primary">{{ document.label }}</p>
                        <p class="mt-1 text-sm text-slate-700">{{ document.file_name }}</p>
                    </a>
                    <a v-for="document in inspectionDocuments" :key="`inspection-${document.id}`" :href="document.preview_url" target="_blank" class="block rounded-xl border border-slate-200 bg-slate-50 p-4 hover:bg-slate-100">
                        <p class="text-sm font-semibold text-primary">{{ document.label }}</p>
                        <p class="mt-1 text-sm text-slate-700">{{ document.file_name }}</p>
                    </a>
                    <div v-for="document in tripDocuments" :key="`trip-${document.id}`" class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-sm font-semibold text-slate-800">{{ document.label }}</p>
                        <p class="mt-1 text-sm text-slate-600">{{ document.file_name }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ document.size_label }}<span v-if="document.uploaded_at"> · {{ document.uploaded_at }}</span></p>
                        <p v-if="document.notes" class="mt-2 text-xs text-slate-500">{{ document.notes }}</p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <a :href="document.preview_url" target="_blank" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-600 transition hover:bg-slate-100">
                                <Lucide icon="Eye" class="h-4 w-4" />
                                Preview
                            </a>
                            <a :href="document.download_url" class="inline-flex items-center gap-2 rounded-lg bg-primary px-3 py-2 text-sm font-medium text-white transition hover:bg-primary/90">
                                <Lucide icon="Download" class="h-4 w-4" />
                                Download
                            </a>
                            <Button v-if="document.can_delete" variant="outline-secondary" class="gap-2" @click="deleteDocument(document.id)">
                                <Lucide icon="Trash2" class="h-4 w-4" />
                                Delete
                            </Button>
                        </div>
                    </div>
                </div>
                <p v-else class="text-sm text-slate-500">No trip documents yet.</p>
            </div>

            <div v-if="hosEntries.length" class="box box--stacked p-6">
                <h2 class="mb-4 text-base font-semibold text-slate-800">HOS Entries</h2>
                <div class="space-y-3">
                    <div v-for="entry in hosEntries" :key="entry.id" class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-sm font-semibold text-slate-800">{{ entry.status }}</p>
                            <span v-if="entry.is_active" class="rounded-full bg-primary/10 px-2 py-1 text-[11px] font-medium text-primary">Open</span>
                        </div>
                        <p class="mt-2 text-xs text-slate-500">{{ entry.start_time }}<span v-if="entry.end_time"> - {{ entry.end_time }}</span></p>
                        <p class="mt-1 text-sm text-slate-600">{{ entry.duration }}</p>
                    </div>
                </div>
            </div>

            <div v-if="violations.length" class="box box--stacked p-6">
                <h2 class="mb-4 text-base font-semibold text-slate-800">Violations</h2>
                <div class="space-y-3">
                    <div v-for="violation in violations" :key="violation.id" class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="text-sm font-semibold text-slate-800">{{ violation.type }}</p>
                            <span class="rounded-full bg-slate-200 px-2 py-0.5 text-[11px] font-medium text-slate-700">{{ violation.severity }}</span>
                        </div>
                        <p class="mt-2 text-xs text-slate-500">{{ violation.date || 'N/A' }}</p>
                        <p class="mt-1 text-sm text-slate-600">Exceeded: {{ violation.hours_exceeded || 'N/A' }}</p>
                    </div>
                </div>
            </div>

            <div v-if="pauses.length" class="box box--stacked p-6">
                <h2 class="mb-4 text-base font-semibold text-slate-800">Pause History</h2>
                <div class="space-y-3">
                    <div v-for="pause in pauses" :key="pause.id" class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-sm font-semibold text-slate-800">{{ pause.reason || 'Pause' }}</p>
                        <p class="mt-2 text-xs text-slate-500">{{ pause.started_at }}<span v-if="pause.ended_at"> - {{ pause.ended_at }}</span></p>
                        <p class="mt-1 text-sm text-slate-600">Duration: {{ pause.duration }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <Dialog :open="rejectModalOpen" @close="rejectModalOpen = false" size="lg">
        <Dialog.Panel class="w-full max-w-[640px] overflow-hidden">
            <div class="p-6">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-800">Reject Trip</h3>
                        <p class="mt-1 text-sm text-slate-500">Add the reason for rejecting this trip.</p>
                    </div>
                    <button type="button" class="text-slate-400 transition hover:text-slate-600" @click="rejectModalOpen = false">
                        <Lucide icon="X" class="h-5 w-5" />
                    </button>
                </div>
                <form class="mt-6 space-y-5" @submit.prevent="rejectTrip">
                    <textarea v-model="rejectForm.reason" rows="4" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm text-slate-700 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20" placeholder="Explain why you cannot take this trip..." />
                    <p v-if="rejectForm.errors.reason" class="text-sm text-danger">{{ rejectForm.errors.reason }}</p>
                    <div class="flex flex-col-reverse gap-3 border-t border-slate-200 pt-5 sm:flex-row sm:justify-end">
                        <Button type="button" variant="outline-secondary" @click="rejectModalOpen = false">Cancel</Button>
                        <Button type="submit" variant="primary" :disabled="rejectForm.processing">{{ rejectForm.processing ? 'Saving...' : 'Reject Trip' }}</Button>
                    </div>
                </form>
            </div>
        </Dialog.Panel>
    </Dialog>

    <Dialog :open="pauseModalOpen" @close="pauseModalOpen = false" size="lg">
        <Dialog.Panel class="w-full max-w-[640px] overflow-hidden">
            <div class="p-6">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-800">Pause Trip</h3>
                        <p class="mt-1 text-sm text-slate-500">Add an optional reason for the pause.</p>
                    </div>
                    <button type="button" class="text-slate-400 transition hover:text-slate-600" @click="pauseModalOpen = false">
                        <Lucide icon="X" class="h-5 w-5" />
                    </button>
                </div>
                <form class="mt-6 space-y-5" @submit.prevent="pauseTrip">
                    <FormInput v-model="pauseForm.reason" placeholder="Meal, loading delay, inspection, etc." />
                    <div class="flex flex-col-reverse gap-3 border-t border-slate-200 pt-5 sm:flex-row sm:justify-end">
                        <Button type="button" variant="outline-secondary" @click="pauseModalOpen = false">Cancel</Button>
                        <Button type="submit" variant="primary" :disabled="pauseForm.processing">{{ pauseForm.processing ? 'Saving...' : 'Pause Trip' }}</Button>
                    </div>
                </form>
            </div>
        </Dialog.Panel>
    </Dialog>

    <Dialog :open="uploadModalOpen" @close="uploadModalOpen = false" size="lg">
        <Dialog.Panel class="w-full max-w-[760px] overflow-hidden">
            <div class="p-6">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-800">Upload Trip Documents</h3>
                        <p class="mt-1 text-sm text-slate-500">Attach bills, PODs, receipts, photos, or other trip paperwork.</p>
                    </div>
                    <button type="button" class="text-slate-400 transition hover:text-slate-600" @click="uploadModalOpen = false">
                        <Lucide icon="X" class="h-5 w-5" />
                    </button>
                </div>

                <form class="mt-6 space-y-4" @submit.prevent="uploadDocuments">
                    <div v-for="(_, index) in uploadForm.document_types" :key="index" class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <label class="mb-2 block text-xs font-medium uppercase tracking-wide text-slate-500">Document Type</label>
                                <select v-model="uploadForm.document_types[index]" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm text-slate-700 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20">
                                    <option value="bol">Bill of Lading (BOL)</option>
                                    <option value="pod">Proof of Delivery (POD)</option>
                                    <option value="fuel_receipt">Fuel Receipt</option>
                                    <option value="toll_receipt">Toll Receipt</option>
                                    <option value="load_photos">Load Photos</option>
                                    <option value="delivery_photos">Delivery Photos</option>
                                    <option value="scale_ticket">Scale Ticket</option>
                                    <option value="lumper_receipt">Lumper Receipt</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div>
                                <label class="mb-2 block text-xs font-medium uppercase tracking-wide text-slate-500">File</label>
                                <input :id="`trip-doc-file-${index}`" type="file" accept=".pdf,.jpg,.jpeg,.png,.webp,.gif" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm text-slate-700">
                            </div>
                            <div class="md:col-span-2">
                                <label class="mb-2 block text-xs font-medium uppercase tracking-wide text-slate-500">Notes</label>
                                <FormInput v-model="uploadForm.document_notes[index]" placeholder="Optional description for this file" />
                            </div>
                        </div>
                        <div class="mt-4 flex justify-end">
                            <Button v-if="uploadForm.document_types.length > 1" type="button" variant="outline-secondary" class="gap-2" @click="removeDocumentRow(index)">
                                <Lucide icon="Trash2" class="h-4 w-4" />
                                Remove
                            </Button>
                        </div>
                    </div>

                    <div class="flex items-center justify-between gap-3 border-t border-slate-200 pt-4">
                        <Button type="button" variant="outline-secondary" class="gap-2" @click="addDocumentRow">
                            <Lucide icon="Plus" class="h-4 w-4" />
                            Add Another File
                        </Button>
                        <div class="flex gap-3">
                            <Button type="button" variant="outline-secondary" @click="uploadModalOpen = false">Cancel</Button>
                            <Button type="submit" variant="primary" :disabled="uploadForm.processing">{{ uploadForm.processing ? 'Uploading...' : 'Upload Documents' }}</Button>
                        </div>
                    </div>
                </form>
            </div>
        </Dialog.Panel>
    </Dialog>
</template>
