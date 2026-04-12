<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import { computed } from 'vue'
import RazeLayout from '@/layouts/RazeLayout.vue'
import Lucide from '@/components/Base/Lucide'

function safeRoute(name: string, params?: any): string {
    try { return route(name, params) } catch { return '#' }
}

interface TripRow {
    id: number
    trip_number: string
    origin: string | null
    destination: string | null
    status: string
    status_name: string
    date: string | null
    vehicle: string | null
}

interface ViolationRow {
    id: number
    type: string
    severity: string
    date: string | null
    acknowledged: boolean
    penalty_type: string | null
}

interface Props {
    driver: {
        id: number
        full_name: string
        status: number
        status_name: string
        hire_date: string | null
        hos_cycle: string
        photo_url: string
    }
    carrier: { id: number; name: string; dot_number: string | null } | null
    tripStats: { total: number; active: number; completed: number; cancelled: number }
    violationStats: { total: number; unacknowledged: number }
    licenseStats: { total: number; valid: number; expiring_soon: number; expired: number }
    medicalStats: { total: number; active: number; expiring_soon: number; expired: number }
    assignedVehicle: {
        id: number
        unit_number: string
        make: string | null
        model: string | null
        year: number | null
        license_plate: string | null
    } | null
    recentTrips: TripRow[]
    recentViolations: ViolationRow[]
    alerts: { type: string; icon: string; title: string; message: string }[]
}

const props = defineProps<Props>()

const hosCycleLabel = computed(() => {
    const map: Record<string, string> = {
        '60_7': '60h / 7 days',
        '70_8': '70h / 8 days',
    }
    return map[props.driver.hos_cycle] ?? props.driver.hos_cycle
})

function tripStatusClass(status: string): string {
    const map: Record<string, string> = {
        pending:     'bg-slate-100 text-slate-600',
        accepted:    'bg-info/10 text-info',
        in_progress: 'bg-warning/10 text-warning',
        paused:      'bg-slate-200 text-slate-600',
        completed:   'bg-success/10 text-success',
        cancelled:   'bg-danger/10 text-danger',
    }
    return map[status] ?? 'bg-slate-100 text-slate-500'
}

function severityClass(severity: string): string {
    const map: Record<string, string> = {
        minor:    'bg-warning/10 text-warning',
        moderate: 'bg-orange-100 text-orange-600',
        critical: 'bg-danger/10 text-danger',
    }
    return map[severity] ?? 'bg-slate-100 text-slate-500'
}

function alertBgClass(type: string): string {
    const map: Record<string, string> = {
        danger:  'bg-danger/10 border-danger/20 text-danger',
        warning: 'bg-warning/10 border-warning/20 text-warning',
        info:    'bg-info/10 border-info/20 text-info',
        success: 'bg-success/10 border-success/20 text-success',
    }
    return map[type] ?? 'bg-slate-50 border-slate-200 text-slate-600'
}

function violationTypeLabel(type: string): string {
    const map: Record<string, string> = {
        driving_limit_exceeded:  'Driving Limit Exceeded',
        duty_limit_exceeded:     'Duty Limit Exceeded',
        duty_period_exceeded:    'Duty Period Exceeded',
        weekly_cycle_exceeded:   'Weekly Cycle Exceeded',
        missing_required_break:  'Missing Required Break',
        forgot_to_close_trip:    'Trip Not Closed',
    }
    return map[type] ?? type.replace(/_/g, ' ')
}
</script>

<template>
    <Head title="Driver Dashboard" />

    <RazeLayout>

        <!-- Alerts -->
        <div v-if="alerts.length" class="mb-6 flex flex-col gap-3">
            <div
                v-for="(alert, i) in alerts"
                :key="i"
                class="flex items-start gap-3 rounded-xl border px-4 py-3"
                :class="alertBgClass(alert.type)"
            >
                <Lucide :icon="alert.icon" class="w-4 h-4 mt-0.5 flex-shrink-0" />
                <div class="flex-1 min-w-0">
                    <span class="text-sm font-semibold">{{ alert.title }}:</span>
                    <span class="text-sm ml-1 opacity-90">{{ alert.message }}</span>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-12 gap-6">

            <!-- ===== DRIVER PROFILE CARD ===== -->
            <div class="col-span-12 lg:col-span-4">
                <div class="box box--stacked p-6 flex flex-col gap-4 h-full">
                    <div class="flex items-center gap-4">
                        <img
                            :src="driver.photo_url"
                            :alt="driver.full_name"
                            class="w-16 h-16 rounded-full object-cover border-2 border-slate-200 flex-shrink-0"
                        />
                        <div class="min-w-0">
                            <div class="font-semibold text-base truncate">{{ driver.full_name }}</div>
                            <div class="text-sm text-slate-500 mt-0.5">
                                {{ carrier?.name ?? '—' }}
                            </div>
                            <span
                                class="inline-block mt-1 text-xs font-medium px-2 py-0.5 rounded-full"
                                :class="driver.status === 1 ? 'bg-success/10 text-success' : 'bg-warning/10 text-warning'"
                            >
                                {{ driver.status_name }}
                            </span>
                        </div>
                    </div>

                    <div class="border-t border-slate-100 pt-4 grid grid-cols-2 gap-3 text-sm">
                        <div>
                            <div class="text-xs text-slate-400 uppercase tracking-wide mb-0.5">DOT #</div>
                            <div class="font-medium">{{ carrier?.dot_number ?? '—' }}</div>
                        </div>
                        <div>
                            <div class="text-xs text-slate-400 uppercase tracking-wide mb-0.5">Hire Date</div>
                            <div class="font-medium">{{ driver.hire_date ?? '—' }}</div>
                        </div>
                        <div>
                            <div class="text-xs text-slate-400 uppercase tracking-wide mb-0.5">HOS Cycle</div>
                            <div class="font-medium">{{ hosCycleLabel }}</div>
                        </div>
                        <div v-if="assignedVehicle">
                            <div class="text-xs text-slate-400 uppercase tracking-wide mb-0.5">Vehicle</div>
                            <div class="font-medium">{{ assignedVehicle.unit_number }}</div>
                        </div>
                        <div v-else>
                            <div class="text-xs text-slate-400 uppercase tracking-wide mb-0.5">Vehicle</div>
                            <div class="text-slate-400 italic text-xs">Not assigned</div>
                        </div>
                    </div>

                    <div class="mt-auto">
                        <a :href="safeRoute('driver.profile')" class="btn btn-outline-secondary w-full text-sm">
                            <Lucide icon="User" class="w-4 h-4 mr-2" />
                            View My Profile
                        </a>
                    </div>
                </div>
            </div>

            <!-- ===== STAT CARDS ===== -->
            <div class="col-span-12 lg:col-span-8">
                <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-4 h-full content-start">

                    <!-- Active trips -->
                    <div class="box box--stacked p-5 flex flex-col gap-2">
                        <div class="p-2.5 rounded-lg bg-primary/10 w-fit">
                            <Lucide icon="MapPin" class="w-5 h-5 text-primary" />
                        </div>
                        <div class="text-2xl font-bold">{{ tripStats.active }}</div>
                        <div class="text-sm text-slate-500">Active Trips</div>
                    </div>

                    <!-- Completed trips -->
                    <div class="box box--stacked p-5 flex flex-col gap-2">
                        <div class="p-2.5 rounded-lg bg-success/10 w-fit">
                            <Lucide icon="CheckCircle" class="w-5 h-5 text-success" />
                        </div>
                        <div class="text-2xl font-bold">{{ tripStats.completed }}</div>
                        <div class="text-sm text-slate-500">Completed Trips</div>
                    </div>

                    <!-- HOS violations -->
                    <div class="box box--stacked p-5 flex flex-col gap-2">
                        <div class="p-2.5 rounded-lg w-fit" :class="violationStats.unacknowledged > 0 ? 'bg-danger/10' : 'bg-slate-100'">
                            <Lucide icon="AlertOctagon" class="w-5 h-5" :class="violationStats.unacknowledged > 0 ? 'text-danger' : 'text-slate-400'" />
                        </div>
                        <div class="text-2xl font-bold" :class="violationStats.unacknowledged > 0 ? 'text-danger' : ''">
                            {{ violationStats.unacknowledged }}
                        </div>
                        <div class="text-sm text-slate-500">Pending Violations</div>
                    </div>

                    <!-- License status -->
                    <div class="box box--stacked p-5 flex flex-col gap-2">
                        <div class="p-2.5 rounded-lg w-fit" :class="licenseStats.expired > 0 ? 'bg-danger/10' : licenseStats.expiring_soon > 0 ? 'bg-warning/10' : 'bg-success/10'">
                            <Lucide icon="CreditCard" class="w-5 h-5"
                                :class="licenseStats.expired > 0 ? 'text-danger' : licenseStats.expiring_soon > 0 ? 'text-warning' : 'text-success'" />
                        </div>
                        <div class="text-2xl font-bold">{{ licenseStats.valid }}</div>
                        <div class="text-sm text-slate-500">Valid Licenses</div>
                        <div v-if="licenseStats.expired > 0" class="text-xs text-danger font-medium">
                            {{ licenseStats.expired }} expired
                        </div>
                        <div v-else-if="licenseStats.expiring_soon > 0" class="text-xs text-warning font-medium">
                            {{ licenseStats.expiring_soon }} expiring soon
                        </div>
                    </div>

                    <!-- Medical -->
                    <div class="box box--stacked p-5 flex flex-col gap-2 col-span-2 sm:col-span-1">
                        <div class="p-2.5 rounded-lg w-fit" :class="medicalStats.expired > 0 ? 'bg-danger/10' : medicalStats.expiring_soon > 0 ? 'bg-warning/10' : 'bg-success/10'">
                            <Lucide icon="Heart" class="w-5 h-5"
                                :class="medicalStats.expired > 0 ? 'text-danger' : medicalStats.expiring_soon > 0 ? 'text-warning' : 'text-success'" />
                        </div>
                        <div class="text-2xl font-bold">{{ medicalStats.active }}</div>
                        <div class="text-sm text-slate-500">Active Medical</div>
                        <div v-if="medicalStats.expired > 0" class="text-xs text-danger font-medium">
                            {{ medicalStats.expired }} expired
                        </div>
                        <div v-else-if="medicalStats.expiring_soon > 0" class="text-xs text-warning font-medium">
                            {{ medicalStats.expiring_soon }} expiring soon
                        </div>
                    </div>

                    <!-- Total trips -->
                    <div class="box box--stacked p-5 flex flex-col gap-2 col-span-2 sm:col-span-1">
                        <div class="p-2.5 rounded-lg bg-slate-100 w-fit">
                            <Lucide icon="BarChart2" class="w-5 h-5 text-slate-500" />
                        </div>
                        <div class="text-2xl font-bold">{{ tripStats.total }}</div>
                        <div class="text-sm text-slate-500">Total Trips</div>
                    </div>

                </div>
            </div>

            <!-- ===== RECENT TRIPS ===== -->
            <div class="col-span-12 lg:col-span-7">
                <div class="box box--stacked p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-semibold text-sm">Recent Trips</h3>
                        <a :href="safeRoute('driver.trips.index')" class="text-xs text-primary hover:underline">
                            View All
                        </a>
                    </div>

                    <div v-if="recentTrips.length === 0" class="py-8 text-center text-slate-400 text-sm">
                        <Lucide icon="MapPin" class="w-8 h-8 mx-auto mb-2 opacity-30" />
                        No trips recorded yet.
                    </div>

                    <div v-else class="divide-y divide-slate-100">
                        <div
                            v-for="trip in recentTrips"
                            :key="trip.id"
                            class="py-3 flex items-start gap-3"
                        >
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-0.5">
                                    <span class="font-medium text-sm">{{ trip.trip_number }}</span>
                                    <span class="text-xs px-1.5 py-0.5 rounded-full font-medium" :class="tripStatusClass(trip.status)">
                                        {{ trip.status_name }}
                                    </span>
                                </div>
                                <div class="text-xs text-slate-500 truncate">
                                    <span v-if="trip.origin">{{ trip.origin }}</span>
                                    <Lucide v-if="trip.origin && trip.destination" icon="ArrowRight" class="inline w-3 h-3 mx-1" />
                                    <span v-if="trip.destination">{{ trip.destination }}</span>
                                    <span v-if="!trip.origin && !trip.destination" class="italic">No route info</span>
                                </div>
                            </div>
                            <div class="text-xs text-slate-400 flex-shrink-0 text-right">
                                <div>{{ trip.date ?? '—' }}</div>
                                <div v-if="trip.vehicle" class="mt-0.5 text-slate-400">{{ trip.vehicle }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ===== RIGHT COLUMN: violations + vehicle ===== -->
            <div class="col-span-12 lg:col-span-5 flex flex-col gap-6">

                <!-- Assigned Vehicle -->
                <div class="box box--stacked p-5">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="font-semibold text-sm">Assigned Vehicle</h3>
                        <a :href="safeRoute('driver.vehicles.index')" class="text-xs text-primary hover:underline">
                            Vehicles
                        </a>
                    </div>

                    <div v-if="!assignedVehicle" class="py-6 text-center text-slate-400 text-sm">
                        <Lucide icon="Truck" class="w-8 h-8 mx-auto mb-2 opacity-30" />
                        No vehicle currently assigned.
                    </div>

                    <div v-else class="flex items-center gap-4">
                        <div class="p-3 rounded-xl bg-primary/10 flex-shrink-0">
                            <Lucide icon="Truck" class="w-7 h-7 text-primary" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="font-semibold text-sm">{{ assignedVehicle.unit_number }}</div>
                            <div class="text-xs text-slate-500 mt-0.5">
                                {{ [assignedVehicle.year, assignedVehicle.make, assignedVehicle.model].filter(Boolean).join(' ') || 'Vehicle details unavailable' }}
                            </div>
                            <div v-if="assignedVehicle.license_plate" class="text-xs text-slate-400 mt-0.5">
                                Plate: {{ assignedVehicle.license_plate }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Violations -->
                <div class="box box--stacked p-5 flex-1">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-semibold text-sm">HOS Violations</h3>
                        <a :href="safeRoute('driver.hos.history')" class="text-xs text-primary hover:underline">
                            View All
                        </a>
                    </div>

                    <div v-if="recentViolations.length === 0" class="py-6 text-center text-slate-400 text-sm">
                        <Lucide icon="CheckCircle" class="w-8 h-8 mx-auto mb-2 opacity-30" />
                        No HOS violations. Keep it up!
                    </div>

                    <div v-else class="divide-y divide-slate-100">
                        <div
                            v-for="v in recentViolations"
                            :key="v.id"
                            class="py-2.5 flex items-center gap-2"
                        >
                            <Lucide
                                :icon="v.acknowledged ? 'CheckCircle' : 'AlertOctagon'"
                                class="w-4 h-4 flex-shrink-0"
                                :class="v.acknowledged ? 'text-success' : 'text-danger'"
                            />
                            <div class="flex-1 min-w-0">
                                <div class="text-xs font-medium truncate">{{ violationTypeLabel(v.type) }}</div>
                                <div class="text-xs text-slate-400">{{ v.date ?? '—' }}</div>
                            </div>
                            <span class="text-xs px-1.5 py-0.5 rounded-full font-medium flex-shrink-0" :class="severityClass(v.severity)">
                                {{ v.severity }}
                            </span>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </RazeLayout>
</template>
