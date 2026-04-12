<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { computed, reactive, ref } from 'vue'
import Button from '@/components/Base/Button'
import FormInput from '@/components/Base/Form/FormInput.vue'
import Lucide from '@/components/Base/Lucide'
import RazeLayout from '@/layouts/RazeLayout.vue'
import { Dialog } from '@/components/Base/Headless'

declare function route(name: string, params?: any): string

defineOptions({ layout: RazeLayout })

interface PaginationLink {
    url: string | null
    label: string
    active: boolean
}

interface TripCard {
    id: number
    trip_number: string
    status: string
    status_label: string
    scheduled_start: string | null
    origin_address: string
    destination_address: string
    vehicle_label: string
    is_quick_trip: boolean
    requires_completion: boolean
    missing_fields: string[]
    has_violations: boolean
    gps_points_count: number
    can_accept: boolean
    can_reject: boolean
    can_start: boolean
    can_pause: boolean
    can_resume: boolean
    can_end: boolean
}

const props = defineProps<{
    driver: {
        id: number
        full_name: string
        carrier_name: string | null
    }
    filters: {
        status: string
        search: string
    }
    stats: {
        total: number
        pending: number
        accepted: number
        in_progress: number
        completed: number
        quick_trips: number
    }
    trips: {
        data: TripCard[]
        links: PaginationLink[]
        total: number
        last_page: number
    }
}>()

const filters = reactive({
    status: props.filters.status,
    search: props.filters.search,
})

const rejectTripId = ref<number | null>(null)
const rejectTripLabel = ref('')
const rejectModalOpen = ref(false)
const rejectForm = useForm({
    reason: '',
})

const tabs = computed(() => [
    { value: 'all', label: 'All', count: props.stats.total, icon: 'List' },
    { value: 'pending', label: 'Pending', count: props.stats.pending, icon: 'Clock3' },
    { value: 'accepted', label: 'Accepted', count: props.stats.accepted, icon: 'BadgeCheck' },
    { value: 'in_progress', label: 'In Progress', count: props.stats.in_progress, icon: 'Route' },
    { value: 'completed', label: 'Completed', count: props.stats.completed, icon: 'CheckCircle2' },
])

function applyFilters() {
    router.get(route('driver.trips.index'), {
        status: filters.status || 'all',
        search: filters.search || undefined,
    }, {
        preserveState: true,
        replace: true,
    })
}

function changeStatus(value: string) {
    filters.status = value
    applyFilters()
}

function clearFilters() {
    filters.status = 'all'
    filters.search = ''
    applyFilters()
}

function acceptTrip(tripId: number) {
    router.post(route('driver.trips.accept', tripId), {}, { preserveScroll: true })
}

function openRejectModal(trip: TripCard) {
    rejectTripId.value = trip.id
    rejectTripLabel.value = trip.trip_number
    rejectForm.reset()
    rejectModalOpen.value = true
}

function submitReject() {
    if (!rejectTripId.value) return

    rejectForm.post(route('driver.trips.reject', rejectTripId.value), {
        preserveScroll: true,
        onSuccess: () => {
            rejectModalOpen.value = false
            rejectTripId.value = null
        },
    })
}

function statusTone(status: string) {
    if (status === 'completed') return 'bg-primary/10 text-primary'
    if (status === 'in_progress') return 'bg-slate-700 text-white'
    if (status === 'paused') return 'bg-slate-200 text-slate-700'
    if (status === 'accepted') return 'bg-slate-100 text-slate-700'
    if (status === 'pending') return 'bg-slate-100 text-slate-600'
    return 'bg-slate-100 text-slate-500'
}

function statCards() {
    return [
        { label: 'Total Trips', value: props.stats.total, icon: 'MapPin', tone: 'bg-primary/10 text-primary' },
        { label: 'Active Right Now', value: props.stats.in_progress, icon: 'Activity', tone: 'bg-slate-200 text-slate-700' },
        { label: 'Completed', value: props.stats.completed, icon: 'CheckCircle2', tone: 'bg-primary/10 text-primary' },
        { label: 'Quick Trips', value: props.stats.quick_trips, icon: 'Zap', tone: 'bg-slate-100 text-slate-700' },
    ]
}
</script>

<template>
    <Head title="My Trips" />

    <div class="grid grid-cols-12 gap-6">
        <div class="col-span-12">
            <div class="box box--stacked p-6">
                <div class="flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between">
                    <div class="flex items-start gap-4">
                        <div class="rounded-2xl border border-primary/20 bg-primary/10 p-3">
                            <Lucide icon="MapPinned" class="h-8 w-8 text-primary" />
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-slate-800">My Trips</h1>
                            <p class="mt-1 text-slate-500">Review assigned trips, create quick trips, and manage the full start to finish workflow.</p>
                            <p class="mt-2 text-sm text-slate-500">
                                Driver: <span class="font-medium text-slate-700">{{ driver.full_name }}</span>
                                <span v-if="driver.carrier_name"> · Carrier: <span class="font-medium text-slate-700">{{ driver.carrier_name }}</span></span>
                            </p>
                        </div>
                    </div>

                    <Link :href="route('driver.trips.create')">
                        <Button variant="primary" class="gap-2">
                            <Lucide icon="Plus" class="h-4 w-4" />
                            New Trip
                        </Button>
                    </Link>
                </div>
            </div>
        </div>

        <div
            v-for="card in statCards()"
            :key="card.label"
            class="col-span-12 sm:col-span-6 xl:col-span-3"
        >
            <div class="box box--stacked p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-slate-500">{{ card.label }}</p>
                        <p class="mt-2 text-3xl font-semibold text-slate-800">{{ card.value }}</p>
                    </div>
                    <div class="rounded-xl p-3" :class="card.tone">
                        <Lucide :icon="card.icon as any" class="h-5 w-5" />
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12">
            <div class="box box--stacked p-5">
                <div class="flex flex-wrap gap-3">
                    <button
                        v-for="tab in tabs"
                        :key="tab.value"
                        type="button"
                        class="inline-flex items-center gap-2 rounded-full border px-4 py-2 text-sm font-medium transition"
                        :class="filters.status === tab.value ? 'border-primary bg-primary text-white' : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300'"
                        @click="changeStatus(tab.value)"
                    >
                        <Lucide :icon="tab.icon as any" class="h-4 w-4" />
                        {{ tab.label }}
                        <span class="rounded-full px-2 py-0.5 text-xs" :class="filters.status === tab.value ? 'bg-white/15' : 'bg-slate-100 text-slate-500'">
                            {{ tab.count }}
                        </span>
                    </button>
                </div>
            </div>
        </div>

        <div class="col-span-12">
            <div class="box box--stacked p-5">
                <div class="grid grid-cols-1 gap-4 lg:grid-cols-[minmax(0,1fr),auto]">
                    <div class="relative">
                        <Lucide icon="Search" class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                        <FormInput
                            v-model="filters.search"
                            class="pl-10"
                            placeholder="Search by trip number, origin, destination, or vehicle..."
                            @keyup.enter="applyFilters"
                        />
                    </div>

                    <div class="flex items-center gap-3">
                        <Button variant="primary" class="gap-2" @click="applyFilters">
                            <Lucide icon="Filter" class="h-4 w-4" />
                            Apply
                        </Button>
                        <Button variant="outline-secondary" class="gap-2" @click="clearFilters">
                            <Lucide icon="RotateCcw" class="h-4 w-4" />
                            Clear
                        </Button>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12">
            <div v-if="trips.data.length" class="grid grid-cols-12 gap-6">
                <div
                    v-for="trip in trips.data"
                    :key="trip.id"
                    class="col-span-12 xl:col-span-6"
                >
                    <div class="box box--stacked h-full border p-6 transition hover:shadow-md" :class="trip.has_violations ? 'border-slate-300 bg-slate-50/60' : 'border-slate-200 bg-white'">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                            <div class="space-y-3">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h2 class="text-lg font-semibold text-slate-800">{{ trip.trip_number }}</h2>
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium" :class="statusTone(trip.status)">
                                        {{ trip.status_label }}
                                    </span>
                                    <span v-if="trip.is_quick_trip" class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">
                                        Quick Trip
                                    </span>
                                    <span v-if="trip.has_violations" class="inline-flex rounded-full bg-slate-200 px-2.5 py-1 text-xs font-medium text-slate-700">
                                        Has Violations
                                    </span>
                                </div>

                                <div class="space-y-2 text-sm text-slate-500">
                                    <p><span class="font-medium text-slate-700">Origin:</span> {{ trip.origin_address }}</p>
                                    <p><span class="font-medium text-slate-700">Destination:</span> {{ trip.destination_address }}</p>
                                    <p><span class="font-medium text-slate-700">Vehicle:</span> {{ trip.vehicle_label }}</p>
                                    <p><span class="font-medium text-slate-700">Scheduled:</span> {{ trip.scheduled_start || 'N/A' }}</p>
                                </div>
                            </div>

                            <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-center sm:min-w-[120px]">
                                <p class="text-xs uppercase tracking-[0.2em] text-slate-400">GPS Points</p>
                                <p class="mt-2 text-2xl font-semibold text-slate-800">{{ trip.gps_points_count }}</p>
                            </div>
                        </div>

                        <div v-if="trip.requires_completion && trip.missing_fields.length" class="mt-5 rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
                            <p class="font-semibold text-slate-800">This quick trip still needs route details.</p>
                            <p class="mt-1">Missing: {{ trip.missing_fields.join(', ') }}</p>
                        </div>

                        <div class="mt-5 flex flex-wrap items-center gap-3 border-t border-slate-200 pt-5">
                            <Button
                                v-if="trip.can_accept"
                                variant="primary"
                                class="gap-2"
                                @click="acceptTrip(trip.id)"
                            >
                                <Lucide icon="CheckCircle2" class="h-4 w-4" />
                                Accept
                            </Button>

                            <Button
                                v-if="trip.can_reject"
                                variant="outline-secondary"
                                class="gap-2"
                                @click="openRejectModal(trip)"
                            >
                                <Lucide icon="XCircle" class="h-4 w-4" />
                                Reject
                            </Button>

                            <Link v-if="trip.can_start" :href="route('driver.trips.start.form', trip.id)">
                                <Button variant="primary" class="gap-2">
                                    <Lucide icon="Play" class="h-4 w-4" />
                                    Start Trip
                                </Button>
                            </Link>

                            <Link v-if="trip.can_end" :href="route('driver.trips.end.form', trip.id)">
                                <Button variant="outline-secondary" class="gap-2">
                                    <Lucide icon="Square" class="h-4 w-4" />
                                    End Trip
                                </Button>
                            </Link>

                            <Link :href="route('driver.trips.show', trip.id)" class="ml-auto">
                                <Button variant="outline-secondary" class="gap-2">
                                    <Lucide icon="ArrowRight" class="h-4 w-4" />
                                    Open
                                </Button>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <div v-else class="box box--stacked p-12 text-center">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-slate-100">
                    <Lucide icon="MapPinned" class="h-8 w-8 text-slate-400" />
                </div>
                <h2 class="mt-5 text-lg font-semibold text-slate-800">No Trips Found</h2>
                <p class="mx-auto mt-2 max-w-xl text-sm text-slate-500">
                    No trips matched your current filters. You can create a full trip or use Quick Trip when you need to get moving fast.
                </p>
            </div>
        </div>

        <div v-if="trips.last_page > 1" class="col-span-12">
            <div class="box box--stacked flex flex-col gap-4 p-4 sm:flex-row sm:items-center sm:justify-between">
                <span class="text-sm text-slate-500">{{ trips.total }} total trips</span>
                <div class="flex flex-wrap gap-1">
                    <template v-for="link in trips.links" :key="link.label">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            class="rounded-lg px-3 py-1.5 text-sm transition"
                            :class="link.active ? 'bg-primary text-white' : 'text-slate-600 hover:bg-slate-100'"
                            v-html="link.label"
                        />
                        <span v-else class="px-3 py-1.5 text-sm text-slate-300" v-html="link.label" />
                    </template>
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
                        <p class="mt-1 text-sm text-slate-500">
                            Add the reason for rejecting <span class="font-medium text-slate-700">{{ rejectTripLabel }}</span>.
                        </p>
                    </div>
                    <button type="button" class="text-slate-400 transition hover:text-slate-600" @click="rejectModalOpen = false">
                        <Lucide icon="X" class="h-5 w-5" />
                    </button>
                </div>

                <form class="mt-6 space-y-5" @submit.prevent="submitReject">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">Reason</label>
                        <textarea
                            v-model="rejectForm.reason"
                            rows="4"
                            class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm text-slate-700 shadow-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                            placeholder="Explain why you cannot take this trip..."
                        />
                        <p v-if="rejectForm.errors.reason" class="mt-2 text-sm text-danger">{{ rejectForm.errors.reason }}</p>
                    </div>

                    <div class="flex flex-col-reverse gap-3 border-t border-slate-200 pt-5 sm:flex-row sm:justify-end">
                        <Button type="button" variant="outline-secondary" class="justify-center gap-2" @click="rejectModalOpen = false">
                            Cancel
                        </Button>
                        <Button type="submit" variant="primary" class="justify-center gap-2" :disabled="rejectForm.processing">
                            <Lucide icon="Send" class="h-4 w-4" />
                            {{ rejectForm.processing ? 'Saving...' : 'Reject Trip' }}
                        </Button>
                    </div>
                </form>
            </div>
        </Dialog.Panel>
    </Dialog>
</template>
