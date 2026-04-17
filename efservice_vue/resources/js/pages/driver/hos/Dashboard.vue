<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import RazeLayout from '@/layouts/RazeLayout.vue'

declare function route(name: string, params?: any): string

defineOptions({ layout: RazeLayout })

interface DriverPayload {
    id: number
    full_name: string
    carrier_name: string | null
    email: string | null
    current_cycle: string
}

interface EntryPayload {
    id: number
    status: string
    status_label: string
    start_time: string | null
    end_time: string | null
    duration: string
    location: string
    is_manual_entry: boolean
    is_ghost_log: boolean
}

interface ViolationPayload {
    id: number
    date: string | null
    type: string
    severity: string
    hours_exceeded: number
    acknowledged: boolean
    is_forgiven: boolean
}

const props = defineProps<{
    driver: DriverPayload
    currentStatus: {
        status: string
        status_label: string
        start_time: string | null
        duration: string
        location: string
    } | null
    totals: {
        driving_formatted: string
        on_duty_formatted: string
        off_duty_formatted: string
        total_formatted: string
    }
    remaining: {
        remaining_driving_formatted: string
        remaining_duty_formatted: string
        is_driving_exceeded: boolean
        is_duty_exceeded: boolean
    }
    alerts: {
        type: string
        category: string
        message: string
        remaining_formatted?: string
        exceeded_by_formatted?: string
    }[]
    cycleStatus: {
        cycle_type: string
        cycle_type_name: string
        hours_used: number
        hours_remaining: number
        hours_limit: number
        percentage_used: number
        status_color: string
        is_over_limit: boolean
        is_approaching_limit: boolean
    }
    dailyBreakdown: {
        date: string
        day_name: string
        driving_hours: number
        on_duty_hours: number
        total_duty_minutes: number
        has_violations: boolean
    }[]
    todayEntries: EntryPayload[]
    recentViolations: ViolationPayload[]
    stats: {
        today_entries: number
        today_violations: number
        documents: number
        hours_remaining: number
    }
    documentsSummary: {
        trip_reports: number
        inspection_reports: number
        daily_logs: number
        monthly_summaries: number
        fmcsa_monthly: number
    }
    statusOptions: { value: string; label: string }[]
}>()

const statusForm = useForm({
    status: '',
    latitude: null as number | null,
    longitude: null as number | null,
    address: '',
})

const geolocating = ref(false)

const statCards = computed(() => [
    { label: 'Entries Today', value: props.stats.today_entries, icon: 'Clock3' },
    { label: 'Violations Today', value: props.stats.today_violations, icon: 'AlertTriangle' },
    { label: 'HOS Documents', value: props.stats.documents, icon: 'FileText' },
    { label: 'Duty Hours Left', value: props.stats.hours_remaining, icon: 'Gauge' },
])

function statusTone(status: string | null) {
    if (status === 'on_duty_driving') return 'bg-primary text-white'
    if (status === 'on_duty_not_driving') return 'bg-slate-700 text-white'
    return 'bg-slate-100 text-slate-700'
}

function cycleMessage() {
    if (props.cycleStatus.is_over_limit) return 'You are over your weekly limit and need a reset before driving again.'
    if (props.cycleStatus.is_approaching_limit) return 'You are getting close to the weekly limit. Plan your remaining hours carefully.'
    return 'Your weekly cycle is in a healthy range.'
}

function submitStatus(status: string, location?: { latitude: number; longitude: number }) {
    statusForm.status = status
    statusForm.latitude = location?.latitude ?? null
    statusForm.longitude = location?.longitude ?? null
    statusForm.address = ''

    statusForm.post(route('driver.hos.status.change'), {
        preserveScroll: true,
        onFinish: () => {
            geolocating.value = false
        },
    })
}

function changeStatus(status: string) {
    if (!navigator.geolocation) {
        submitStatus(status)
        return
    }

    geolocating.value = true
    navigator.geolocation.getCurrentPosition(
        (position) => submitStatus(status, {
            latitude: position.coords.latitude,
            longitude: position.coords.longitude,
        }),
        () => submitStatus(status),
        { enableHighAccuracy: true, timeout: 5000, maximumAge: 60000 },
    )
}
</script>

<template>
    <Head title="HOS Dashboard" />

    <div class="grid grid-cols-12 gap-6">
        <div class="col-span-12">
            <div class="box box--stacked p-6">
                <div class="flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between">
                    <div class="flex items-start gap-4">
                        <div class="rounded-2xl border border-primary/20 bg-primary/10 p-3">
                            <Lucide icon="Clock3" class="h-8 w-8 text-primary" />
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-slate-800">Hours of Service</h1>
                            <p class="mt-1 text-slate-500">Track your daily duty time, weekly cycle usage, alerts, and generated logs.</p>
                            <p class="mt-2 text-sm text-slate-500">
                                Driver: <span class="font-medium text-slate-700">{{ driver.full_name }}</span>
                                <span v-if="driver.carrier_name"> · Carrier: <span class="font-medium text-slate-700">{{ driver.carrier_name }}</span></span>
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <Link :href="route('driver.hos.history')"><Button variant="outline-secondary" class="gap-2"><Lucide icon="History" class="h-4 w-4" />History</Button></Link>
                        <Link :href="route('driver.hos.documents.index')"><Button variant="outline-secondary" class="gap-2"><Lucide icon="FileText" class="h-4 w-4" />Documents</Button></Link>
                        <Link :href="route('driver.hos.cycle.index')"><Button variant="primary" class="gap-2"><Lucide icon="Settings" class="h-4 w-4" />Cycle Settings</Button></Link>
                    </div>
                </div>
            </div>
        </div>

        <div v-for="card in statCards" :key="card.label" class="col-span-12 sm:col-span-6 xl:col-span-3">
            <div class="box box--stacked p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-slate-500">{{ card.label }}</p>
                        <p class="mt-2 text-3xl font-semibold text-slate-800">{{ card.value }}</p>
                    </div>
                    <div class="rounded-xl bg-primary/10 p-3 text-primary">
                        <Lucide :icon="card.icon as any" class="h-5 w-5" />
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12 xl:col-span-8 space-y-6">
            <div class="box box--stacked p-6">
                <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <div class="flex items-center gap-3">
                            <h2 class="text-lg font-semibold text-slate-800">Current Status</h2>
                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium" :class="statusTone(currentStatus?.status ?? null)">
                                {{ currentStatus?.status_label ?? 'No active status' }}
                            </span>
                        </div>
                        <p class="mt-3 text-sm text-slate-500">
                            <template v-if="currentStatus">Started {{ currentStatus.start_time }} · Running {{ currentStatus.duration }}</template>
                            <template v-else>No open HOS entry right now. Use the buttons below to record your current duty status.</template>
                        </p>
                        <p class="mt-1 text-sm text-slate-500">{{ currentStatus?.location ?? 'Location will be attached when available.' }}</p>
                    </div>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                        <Button
                            v-for="option in statusOptions"
                            :key="option.value"
                            :variant="currentStatus?.status === option.value ? 'primary' : 'outline-secondary'"
                            class="justify-center gap-2"
                            :disabled="statusForm.processing || geolocating"
                            @click="changeStatus(option.value)"
                        >
                            <Lucide :icon="option.value === 'off_duty' ? 'Moon' : option.value === 'on_duty_driving' ? 'Truck' : 'Briefcase'" class="h-4 w-4" />
                            {{ option.label }}
                        </Button>
                    </div>
                </div>

                <div class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-4">
                    <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-4"><p class="text-xs font-medium uppercase tracking-[0.2em] text-slate-400">Driving Today</p><p class="mt-2 text-lg font-semibold text-slate-800">{{ totals.driving_formatted }}</p></div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-4"><p class="text-xs font-medium uppercase tracking-[0.2em] text-slate-400">On Duty Today</p><p class="mt-2 text-lg font-semibold text-slate-800">{{ totals.on_duty_formatted }}</p></div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-4"><p class="text-xs font-medium uppercase tracking-[0.2em] text-slate-400">Off Duty Today</p><p class="mt-2 text-lg font-semibold text-slate-800">{{ totals.off_duty_formatted }}</p></div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-4"><p class="text-xs font-medium uppercase tracking-[0.2em] text-slate-400">Remaining Duty</p><p class="mt-2 text-lg font-semibold text-slate-800">{{ remaining.remaining_duty_formatted }}</p></div>
                </div>
            </div>

            <div class="box box--stacked p-6">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-800">Weekly Cycle</h2>
                        <p class="mt-1 text-sm text-slate-500">{{ cycleStatus.cycle_type_name }}</p>
                    </div>
                    <span class="inline-flex rounded-full bg-primary/10 px-3 py-1 text-xs font-medium text-primary">{{ cycleStatus.hours_used }} / {{ cycleStatus.hours_limit }} hours</span>
                </div>

                <div class="mt-5">
                    <div class="flex items-center justify-between text-sm text-slate-500">
                        <span>{{ cycleStatus.percentage_used }}% used</span>
                        <span>{{ cycleStatus.hours_remaining }} hours remaining</span>
                    </div>
                    <div class="mt-2 h-3 overflow-hidden rounded-full bg-slate-100">
                        <div class="h-full rounded-full bg-primary transition-all duration-300" :style="{ width: `${Math.min(cycleStatus.percentage_used, 100)}%` }" />
                    </div>
                    <p class="mt-3 text-sm text-slate-500">{{ cycleMessage() }}</p>
                </div>

                <div class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-7">
                    <div v-for="day in dailyBreakdown" :key="day.date" class="rounded-xl border border-slate-200 px-3 py-4 text-center">
                        <p class="text-xs font-medium uppercase tracking-[0.2em] text-slate-400">{{ day.day_name.slice(0, 3) }}</p>
                        <p class="mt-2 text-sm font-semibold text-slate-800">{{ day.driving_hours.toFixed(1) }}h</p>
                        <p class="mt-1 text-xs text-slate-500">{{ day.on_duty_hours.toFixed(1) }}h on duty</p>
                        <span v-if="day.has_violations" class="mt-2 inline-flex rounded-full bg-primary/10 px-2 py-0.5 text-[11px] font-medium text-primary">Violation</span>
                    </div>
                </div>
            </div>

            <div class="box box--stacked p-6">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-slate-800">Today's Entries</h2>
                    <Link :href="route('driver.hos.history')" class="text-sm font-medium text-primary hover:underline">Open full history</Link>
                </div>

                <div v-if="todayEntries.length" class="mt-5 space-y-3">
                    <div v-for="entry in todayEntries" :key="entry.id" class="rounded-xl border border-slate-200 px-4 py-3">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="font-medium text-slate-800">{{ entry.status_label }}</p>
                                    <span v-if="entry.is_manual_entry" class="inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-600">Manual</span>
                                    <span v-if="entry.is_ghost_log" class="inline-flex rounded-full bg-primary/10 px-2 py-0.5 text-[11px] font-medium text-primary">Ghost</span>
                                </div>
                                <p class="mt-1 text-sm text-slate-500">{{ entry.start_time }}<span v-if="entry.end_time"> to {{ entry.end_time }}</span> · {{ entry.duration }}</p>
                            </div>
                            <p class="text-sm text-slate-500">{{ entry.location }}</p>
                        </div>
                    </div>
                </div>
                <div v-else class="mt-5 rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">No HOS entries were recorded today.</div>
            </div>
        </div>

        <div class="col-span-12 xl:col-span-4 space-y-6">
            <div class="box box--stacked p-6">
                <h2 class="text-lg font-semibold text-slate-800">Alerts</h2>
                <div v-if="alerts.length" class="mt-4 space-y-3">
                    <div
                        v-for="(alert, index) in alerts"
                        :key="`${alert.type}-${index}`"
                        class="rounded-xl border px-4 py-3"
                        :class="alert.type === 'violation' ? 'border-primary/20 bg-primary/5' : 'border-slate-200 bg-slate-50'"
                    >
                        <div class="flex items-start gap-3">
                            <div class="rounded-lg p-2" :class="alert.type === 'violation' ? 'bg-primary/10 text-primary' : 'bg-slate-100 text-slate-600'">
                                <Lucide :icon="alert.type === 'violation' ? 'AlertTriangle' : 'Info'" class="h-4 w-4" />
                            </div>
                            <div>
                                <p class="text-sm font-medium text-slate-800">{{ alert.message }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ alert.category }}</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-else class="mt-4 rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">No active HOS alerts right now.</div>
            </div>

            <div class="box box--stacked p-6">
                <h2 class="text-lg font-semibold text-slate-800">Document Summary</h2>
                <div class="mt-4 space-y-3 text-sm">
                    <div class="flex items-center justify-between rounded-xl border border-slate-200 px-4 py-3"><span class="text-slate-600">Trip Reports</span><span class="font-semibold text-slate-800">{{ documentsSummary.trip_reports }}</span></div>
                    <div class="flex items-center justify-between rounded-xl border border-slate-200 px-4 py-3"><span class="text-slate-600">Inspection Reports</span><span class="font-semibold text-slate-800">{{ documentsSummary.inspection_reports }}</span></div>
                    <div class="flex items-center justify-between rounded-xl border border-slate-200 px-4 py-3"><span class="text-slate-600">Daily Logs</span><span class="font-semibold text-slate-800">{{ documentsSummary.daily_logs }}</span></div>
                    <div class="flex items-center justify-between rounded-xl border border-slate-200 px-4 py-3"><span class="text-slate-600">Monthly Summaries</span><span class="font-semibold text-slate-800">{{ documentsSummary.monthly_summaries }}</span></div>
                    <div class="flex items-center justify-between rounded-xl border border-slate-200 px-4 py-3"><span class="text-slate-600">FMCSA Monthly</span><span class="font-semibold text-slate-800">{{ documentsSummary.fmcsa_monthly }}</span></div>
                </div>
            </div>

            <div class="box box--stacked p-6">
                <h2 class="text-lg font-semibold text-slate-800">Recent Violations</h2>
                <div v-if="recentViolations.length" class="mt-4 space-y-3">
                    <div v-for="violation in recentViolations" :key="violation.id" class="rounded-xl border border-slate-200 px-4 py-3">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-medium text-slate-800">{{ violation.type }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ violation.date }} · {{ violation.severity }}</p>
                            </div>
                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-600">{{ violation.hours_exceeded }}h</span>
                        </div>
                    </div>
                </div>
                <div v-else class="mt-4 rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">No recent violations were found.</div>
            </div>
        </div>
    </div>
</template>
