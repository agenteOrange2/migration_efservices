<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { computed } from 'vue'
import RazeLayout from '@/layouts/RazeLayout.vue'
import Lucide from '@/components/Base/Lucide'

// Safe route helper — returns '#' if route doesn't exist yet
function safeRoute(name: string, params?: any): string {
    try {
        return route(name, params)
    } catch {
        return '#'
    }
}

interface DocumentStats {
    total: number
    pending: number
    approved: number
    rejected: number
    in_process: number
}

interface Props {
    carrier: {
        id: number
        name: string
        dot_number: string | null
        mc_number: string | null
        status: number
        documents_completed: boolean
        documents_status: 'none' | 'pending' | 'rejected' | 'complete'
        safety_url: string | null
    }
    stats: {
        drivers: number
        vehicles: number
        documents: DocumentStats
    }
    membershipLimits: {
        maxDrivers: number
        maxVehicles: number
        driversPercentage: number
        vehiclesPercentage: number
    }
    licenseStats: { total: number; valid: number; expiring_soon: number; expired: number }
    medicalStats: { total: number; active: number; expiring_soon: number; expired: number }
    maintenanceStats: { total: number; overdue: number; expiring_soon: number; completed: number }
    advancedMetrics: {
        documentsThisMonth: number
        documentsGrowth: number
        avgApprovalDays: number
        activeDrivers: number
        inactiveDrivers: number
        completionRate: number
        pendingRate: number
    }
    documentTypeCounts: { name: string; count: number }[]
    recentDrivers: { id: number; name: string; email: string; status: number; created_at: string }[]
    recentDocuments: { id: number; name: string; status: number; status_name: string; created_at: string }[]
    trendsData: { month: string; documents: number; drivers: number }[]
    alerts: { type: string; icon: string; title: string; message: string }[]
}

const props = defineProps<Props>()

const docApprovalPct = computed(() =>
    props.stats.documents.total > 0
        ? Math.round((props.stats.documents.approved / props.stats.documents.total) * 100)
        : 0
)

function driverStatusLabel(status: number): string {
    const map: Record<number, string> = { 0: 'Inactive', 1: 'Active', 2: 'Pending' }
    return map[status] ?? 'Unknown'
}

function driverStatusClass(status: number): string {
    const map: Record<number, string> = {
        0: 'text-danger',
        1: 'text-success',
        2: 'text-warning',
    }
    return map[status] ?? 'text-slate-500'
}

function docStatusClass(status: number): string {
    const map: Record<number, string> = {
        0: 'bg-warning/10 text-warning',
        1: 'bg-success/10 text-success',
        2: 'bg-danger/10 text-danger',
        3: 'bg-info/10 text-info',
    }
    return map[status] ?? 'bg-slate-100 text-slate-500'
}

function alertVariantClass(type: string): string {
    const map: Record<string, string> = {
        danger:  'bg-danger/10 border-danger/20 text-danger',
        warning: 'bg-warning/10 border-warning/20 text-warning',
        info:    'bg-info/10 border-info/20 text-info',
        success: 'bg-success/10 border-success/20 text-success',
    }
    return map[type] ?? 'bg-slate-50 border-slate-200 text-slate-600'
}

function alertIconClass(type: string): string {
    const map: Record<string, string> = {
        danger:  'text-danger',
        warning: 'text-warning',
        info:    'text-info',
        success: 'text-success',
    }
    return map[type] ?? 'text-slate-500'
}

const maxTrend = computed(() => {
    const maxD = Math.max(...props.trendsData.map(t => t.documents), 1)
    const maxDr = Math.max(...props.trendsData.map(t => t.drivers), 1)
    return { docs: maxD, drivers: maxDr }
})
</script>

<template>
    <Head title="Carrier Dashboard" />

    <RazeLayout>

        <!-- ===== DOCUMENTS BANNER ===== -->
        <!-- none: no documents uploaded yet -->
        <div v-if="carrier.documents_status === 'none'"
            class="mb-6 flex items-center gap-3 rounded-xl border border-warning/30 bg-warning/10 px-5 py-4">
            <Lucide icon="AlertTriangle" class="w-5 h-5 text-warning flex-shrink-0" />
            <div class="flex-1">
                <span class="text-sm font-medium text-warning">Action Required:</span>
                <span class="text-sm text-warning/80 ml-1">
                    Please complete your carrier documentation to unlock all features.
                </span>
            </div>
            <Link :href="safeRoute('carrier.documents.index')" class="text-xs font-semibold text-warning underline underline-offset-2">
                Complete Now
            </Link>
        </div>

        <!-- pending: documents uploaded, waiting for admin approval -->
        <div v-else-if="carrier.documents_status === 'pending'"
            class="mb-6 flex items-center gap-3 rounded-xl border border-info/30 bg-info/10 px-5 py-4">
            <Lucide icon="Clock" class="w-5 h-5 text-info flex-shrink-0" />
            <div class="flex-1">
                <span class="text-sm font-medium text-info">Under Review:</span>
                <span class="text-sm text-info/80 ml-1">
                    Your documents have been submitted and are pending admin approval.
                </span>
            </div>
            <Link :href="safeRoute('carrier.documents.index')" class="text-xs font-semibold text-info underline underline-offset-2">
                View Documents
            </Link>
        </div>

        <!-- rejected: some documents were rejected -->
        <div v-else-if="carrier.documents_status === 'rejected'"
            class="mb-6 flex items-center gap-3 rounded-xl border border-danger/30 bg-danger/10 px-5 py-4">
            <Lucide icon="XCircle" class="w-5 h-5 text-danger flex-shrink-0" />
            <div class="flex-1">
                <span class="text-sm font-medium text-danger">Action Required:</span>
                <span class="text-sm text-danger/80 ml-1">
                    Some documents were rejected. Please review and resubmit.
                </span>
            </div>
            <Link :href="safeRoute('carrier.documents.index')" class="text-xs font-semibold text-danger underline underline-offset-2">
                Review Now
            </Link>
        </div>

        <div class="grid grid-cols-12 gap-6">

            <!-- ===== TOP STAT CARDS ===== -->
            <div class="col-span-12">
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-5">
                    <!-- Drivers -->
                    <div class="box box--stacked p-5 flex flex-col gap-3">
                        <div class="flex items-center justify-between">
                            <div class="p-2.5 rounded-lg bg-primary/10">
                                <Lucide icon="Users" class="w-5 h-5 text-primary" />
                            </div>
                            <span class="text-xs font-medium text-slate-400">
                                {{ membershipLimits.maxDrivers > 0 ? `${membershipLimits.driversPercentage}% used` : '&mdash;' }}
                            </span>
                        </div>
                        <div>
                            <div class="text-2xl font-bold">{{ stats.drivers }}</div>
                            <div class="text-sm text-slate-500 mt-0.5">Total Drivers</div>
                        </div>
                        <div v-if="membershipLimits.maxDrivers > 0" class="mt-auto">
                            <div class="flex justify-between text-xs text-slate-400 mb-1">
                                <span>{{ stats.drivers }} / {{ membershipLimits.maxDrivers }}</span>
                            </div>
                            <div class="h-1.5 w-full bg-slate-100 rounded-full overflow-hidden">
                                <div class="h-full rounded-full bg-primary transition-all"
                                    :class="membershipLimits.driversPercentage >= 90 ? 'bg-danger' : 'bg-primary'"
                                    :style="{ width: `${membershipLimits.driversPercentage}%` }" />
                            </div>
                        </div>
                    </div>

                    <!-- Vehicles -->
                    <div class="box box--stacked p-5 flex flex-col gap-3">
                        <div class="flex items-center justify-between">
                            <div class="p-2.5 rounded-lg bg-warning/10">
                                <Lucide icon="Truck" class="w-5 h-5 text-warning" />
                            </div>
                            <span class="text-xs font-medium text-slate-400">
                                {{ membershipLimits.maxVehicles > 0 ? `${membershipLimits.vehiclesPercentage}% used` : '&mdash;' }}
                            </span>
                        </div>
                        <div>
                            <div class="text-2xl font-bold">{{ stats.vehicles }}</div>
                            <div class="text-sm text-slate-500 mt-0.5">Total Vehicles</div>
                        </div>
                        <div v-if="membershipLimits.maxVehicles > 0" class="mt-auto">
                            <div class="flex justify-between text-xs text-slate-400 mb-1">
                                <span>{{ stats.vehicles }} / {{ membershipLimits.maxVehicles }}</span>
                            </div>
                            <div class="h-1.5 w-full bg-slate-100 rounded-full overflow-hidden">
                                <div class="h-full rounded-full transition-all"
                                    :class="membershipLimits.vehiclesPercentage >= 90 ? 'bg-danger' : 'bg-warning'"
                                    :style="{ width: `${membershipLimits.vehiclesPercentage}%` }" />
                            </div>
                        </div>
                    </div>

                    <!-- Approved Docs -->
                    <div class="box box--stacked p-5 flex flex-col gap-3">
                        <div class="flex items-center justify-between">
                            <div class="p-2.5 rounded-lg bg-success/10">
                                <Lucide icon="FileCheck" class="w-5 h-5 text-success" />
                            </div>
                            <span class="text-xs font-medium text-success">{{ docApprovalPct }}%</span>
                        </div>
                        <div>
                            <div class="text-2xl font-bold">{{ stats.documents.approved }}</div>
                            <div class="text-sm text-slate-500 mt-0.5">Approved Docs</div>
                        </div>
                        <div class="mt-auto">
                            <div class="h-1.5 w-full bg-slate-100 rounded-full overflow-hidden">
                                <div class="h-full rounded-full bg-success transition-all"
                                    :style="{ width: `${docApprovalPct}%` }" />
                            </div>
                        </div>
                    </div>

                    <!-- Pending Docs -->
                    <div class="box box--stacked p-5 flex flex-col gap-3">
                        <div class="flex items-center justify-between">
                            <div class="p-2.5 rounded-lg bg-danger/10">
                                <Lucide icon="FileClock" class="w-5 h-5 text-danger" />
                            </div>
                            <span class="text-xs font-medium text-danger">
                                {{ stats.documents.total > 0 ? Math.round((stats.documents.pending / stats.documents.total) * 100) : 0 }}%
                            </span>
                        </div>
                        <div>
                            <div class="text-2xl font-bold">{{ stats.documents.pending }}</div>
                            <div class="text-sm text-slate-500 mt-0.5">Pending Docs</div>
                        </div>
                        <div class="mt-auto flex gap-1.5 flex-wrap">
                            <span v-if="stats.documents.rejected > 0"
                                class="text-xs px-2 py-0.5 rounded-full bg-danger/10 text-danger font-medium">
                                {{ stats.documents.rejected }} Rejected
                            </span>
                            <span v-if="stats.documents.in_process > 0"
                                class="text-xs px-2 py-0.5 rounded-full bg-info/10 text-info font-medium">
                                {{ stats.documents.in_process }} In Progress
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ===== MEMBERSHIP PLAN LIMITS ===== -->
            <div v-if="membershipLimits.maxDrivers > 0 || membershipLimits.maxVehicles > 0"
                class="col-span-12">
                <div class="box box--stacked p-5">
                    <div class="flex items-center gap-2 mb-5">
                        <Lucide icon="Crown" class="w-4 h-4 text-warning" />
                        <h3 class="font-semibold text-slate-800">Membership Plan Limits</h3>
                    </div>
                    <div class="space-y-5">
                        <!-- Drivers -->
                        <div v-if="membershipLimits.maxDrivers > 0">
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center gap-2 text-sm text-slate-600">
                                    <Lucide icon="Users" class="w-4 h-4 text-primary" />
                                    Drivers
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-semibold text-slate-700">
                                        {{ stats.drivers }} / {{ membershipLimits.maxDrivers }}
                                    </span>
                                    <span class="text-xs font-semibold px-2 py-0.5 rounded-full"
                                        :class="membershipLimits.driversPercentage >= 90
                                            ? 'bg-danger/10 text-danger'
                                            : membershipLimits.driversPercentage >= 70
                                            ? 'bg-warning/10 text-warning'
                                            : 'bg-success/10 text-success'">
                                        {{ membershipLimits.driversPercentage }}%
                                    </span>
                                </div>
                            </div>
                            <div class="h-2.5 w-full bg-slate-100 rounded-full overflow-hidden">
                                <div class="h-full rounded-full transition-all duration-500"
                                    :class="membershipLimits.driversPercentage >= 90 ? 'bg-danger' : membershipLimits.driversPercentage >= 70 ? 'bg-warning' : 'bg-primary'"
                                    :style="{ width: `${membershipLimits.driversPercentage}%` }" />
                            </div>
                            <div v-if="membershipLimits.driversPercentage >= 90"
                                class="mt-2 flex items-center gap-1.5 text-xs text-danger">
                                <Lucide icon="AlertTriangle" class="w-3.5 h-3.5" />
                                Approaching driver limit — consider upgrading your plan
                            </div>
                        </div>
                        <!-- Vehicles -->
                        <div v-if="membershipLimits.maxVehicles > 0">
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center gap-2 text-sm text-slate-600">
                                    <Lucide icon="Truck" class="w-4 h-4 text-warning" />
                                    Vehicles
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-semibold text-slate-700">
                                        {{ stats.vehicles }} / {{ membershipLimits.maxVehicles }}
                                    </span>
                                    <span class="text-xs font-semibold px-2 py-0.5 rounded-full"
                                        :class="membershipLimits.vehiclesPercentage >= 90
                                            ? 'bg-danger/10 text-danger'
                                            : membershipLimits.vehiclesPercentage >= 70
                                            ? 'bg-warning/10 text-warning'
                                            : 'bg-success/10 text-success'">
                                        {{ membershipLimits.vehiclesPercentage }}%
                                    </span>
                                </div>
                            </div>
                            <div class="h-2.5 w-full bg-slate-100 rounded-full overflow-hidden">
                                <div class="h-full rounded-full transition-all duration-500"
                                    :class="membershipLimits.vehiclesPercentage >= 90 ? 'bg-danger' : membershipLimits.vehiclesPercentage >= 70 ? 'bg-warning' : 'bg-warning'"
                                    :style="{ width: `${membershipLimits.vehiclesPercentage}%` }" />
                            </div>
                            <div v-if="membershipLimits.vehiclesPercentage >= 90"
                                class="mt-2 flex items-center gap-1.5 text-xs text-danger">
                                <Lucide icon="AlertTriangle" class="w-3.5 h-3.5" />
                                Approaching vehicle limit — consider upgrading your plan
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ===== QUICK ACTIONS ===== -->
            <div class="col-span-12">
                <div class="flex flex-wrap gap-3">
                    <Link :href="safeRoute('carrier.drivers.index')"
                        class="flex items-center gap-2 px-4 py-2.5 rounded-lg bg-primary text-white text-sm font-medium hover:bg-primary/90 transition-colors">
                        <Lucide icon="UserPlus" class="w-4 h-4" />
                        Manage Drivers
                    </Link>
                    <Link :href="safeRoute('carrier.vehicles.index')"
                        class="flex items-center gap-2 px-4 py-2.5 rounded-lg border border-slate-200 bg-white text-slate-700 text-sm font-medium hover:bg-slate-50 transition-colors">
                        <Lucide icon="Truck" class="w-4 h-4" />
                        Manage Vehicles
                    </Link>
                    <Link :href="safeRoute('carrier.licenses.index')"
                        class="flex items-center gap-2 px-4 py-2.5 rounded-lg border border-slate-200 bg-white text-slate-700 text-sm font-medium hover:bg-slate-50 transition-colors">
                        <Lucide icon="CreditCard" class="w-4 h-4" />
                        Licenses
                    </Link>
                    <Link :href="safeRoute('carrier.medical-records.index')"
                        class="flex items-center gap-2 px-4 py-2.5 rounded-lg border border-slate-200 bg-white text-slate-700 text-sm font-medium hover:bg-slate-50 transition-colors">
                        <Lucide icon="FileHeart" class="w-4 h-4" />
                        Medical Records
                    </Link>
                    <Link :href="safeRoute('carrier.documents.index')"
                        class="flex items-center gap-2 px-4 py-2.5 rounded-lg border border-slate-200 bg-white text-slate-700 text-sm font-medium hover:bg-slate-50 transition-colors">
                        <Lucide icon="FileText" class="w-4 h-4" />
                        Documents
                    </Link>
                    <Link :href="safeRoute('carrier.maintenance.index')"
                        class="flex items-center gap-2 px-4 py-2.5 rounded-lg border border-slate-200 bg-white text-slate-700 text-sm font-medium hover:bg-slate-50 transition-colors">
                        <Lucide icon="Wrench" class="w-4 h-4" />
                        Maintenance
                    </Link>
                    <a v-if="carrier.safety_url" :href="carrier.safety_url" target="_blank"
                        class="flex items-center gap-2 px-4 py-2.5 rounded-lg border border-slate-200 bg-white text-slate-700 text-sm font-medium hover:bg-slate-50 transition-colors">
                        <Lucide icon="ExternalLink" class="w-4 h-4" />
                        FMCSA Safety Data
                    </a>
                </div>
            </div>

            <!-- ===== COMPLIANCE CARDS: Licenses / Medical / Maintenance ===== -->
            <div class="col-span-12">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                    <!-- Licenses -->
                    <div class="box box--stacked p-5">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="p-2 rounded-lg bg-primary/10">
                                <Lucide icon="CreditCard" class="w-4 h-4 text-primary" />
                            </div>
                            <h3 class="font-semibold text-slate-800">Driver Licenses</h3>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="bg-slate-50 rounded-lg p-3 text-center">
                                <div class="text-xl font-bold text-slate-800">{{ licenseStats.total }}</div>
                                <div class="text-xs text-slate-500 mt-0.5">Total</div>
                            </div>
                            <div class="bg-success/5 rounded-lg p-3 text-center">
                                <div class="text-xl font-bold text-success">{{ licenseStats.valid }}</div>
                                <div class="text-xs text-slate-500 mt-0.5">Valid</div>
                            </div>
                            <div class="bg-warning/5 rounded-lg p-3 text-center">
                                <div class="text-xl font-bold text-warning">{{ licenseStats.expiring_soon }}</div>
                                <div class="text-xs text-slate-500 mt-0.5">Expiring Soon</div>
                            </div>
                            <div class="bg-danger/5 rounded-lg p-3 text-center">
                                <div class="text-xl font-bold text-danger">{{ licenseStats.expired }}</div>
                                <div class="text-xs text-slate-500 mt-0.5">Expired</div>
                            </div>
                        </div>
                    </div>

                    <!-- Medical Records -->
                    <div class="box box--stacked p-5">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="p-2 rounded-lg bg-success/10">
                                <Lucide icon="FileHeart" class="w-4 h-4 text-success" />
                            </div>
                            <h3 class="font-semibold text-slate-800">Medical Records</h3>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="bg-slate-50 rounded-lg p-3 text-center">
                                <div class="text-xl font-bold text-slate-800">{{ medicalStats.total }}</div>
                                <div class="text-xs text-slate-500 mt-0.5">Total</div>
                            </div>
                            <div class="bg-success/5 rounded-lg p-3 text-center">
                                <div class="text-xl font-bold text-success">{{ medicalStats.active }}</div>
                                <div class="text-xs text-slate-500 mt-0.5">Active</div>
                            </div>
                            <div class="bg-warning/5 rounded-lg p-3 text-center">
                                <div class="text-xl font-bold text-warning">{{ medicalStats.expiring_soon }}</div>
                                <div class="text-xs text-slate-500 mt-0.5">Expiring Soon</div>
                            </div>
                            <div class="bg-danger/5 rounded-lg p-3 text-center">
                                <div class="text-xl font-bold text-danger">{{ medicalStats.expired }}</div>
                                <div class="text-xs text-slate-500 mt-0.5">Expired</div>
                            </div>
                        </div>
                    </div>

                    <!-- Vehicle Maintenance -->
                    <div class="box box--stacked p-5">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="p-2 rounded-lg bg-warning/10">
                                <Lucide icon="Wrench" class="w-4 h-4 text-warning" />
                            </div>
                            <h3 class="font-semibold text-slate-800">Vehicle Maintenance</h3>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="bg-slate-50 rounded-lg p-3 text-center">
                                <div class="text-xl font-bold text-slate-800">{{ maintenanceStats.total }}</div>
                                <div class="text-xs text-slate-500 mt-0.5">Total</div>
                            </div>
                            <div class="bg-success/5 rounded-lg p-3 text-center">
                                <div class="text-xl font-bold text-success">{{ maintenanceStats.completed }}</div>
                                <div class="text-xs text-slate-500 mt-0.5">Completed</div>
                            </div>
                            <div class="bg-warning/5 rounded-lg p-3 text-center">
                                <div class="text-xl font-bold text-warning">{{ maintenanceStats.expiring_soon }}</div>
                                <div class="text-xs text-slate-500 mt-0.5">Due Soon</div>
                            </div>
                            <div class="bg-danger/5 rounded-lg p-3 text-center">
                                <div class="text-xl font-bold text-danger">{{ maintenanceStats.overdue }}</div>
                                <div class="text-xs text-slate-500 mt-0.5">Overdue</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ===== MAIN CONTENT + SIDEBAR ===== -->
            <div class="col-span-12 xl:col-span-8 flex flex-col gap-6">

                <!-- Recent Drivers -->
                <div class="box box--stacked">
                    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200/60">
                        <div class="flex items-center gap-2">
                            <Lucide icon="Users" class="w-4 h-4 text-primary" />
                            <h3 class="font-semibold text-slate-800">Recent Drivers</h3>
                        </div>
                        <Link :href="safeRoute('carrier.drivers.index')"
                            class="text-xs text-primary font-medium flex items-center gap-1 hover:underline">
                            View All <Lucide icon="ArrowRight" class="w-3 h-3" />
                        </Link>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-slate-100">
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Driver</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Joined</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="driver in recentDrivers" :key="driver.id" class="hover:bg-slate-50/50 transition-colors">
                                    <td class="px-6 py-3.5">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center flex-shrink-0">
                                                <Lucide icon="User" class="w-4 h-4 text-primary" />
                                            </div>
                                            <div>
                                                <div class="text-sm font-medium text-slate-800">{{ driver.name }}</div>
                                                <div class="text-xs text-slate-500">{{ driver.email }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-3.5">
                                        <div class="flex items-center gap-1.5" :class="driverStatusClass(driver.status)">
                                            <div class="w-1.5 h-1.5 rounded-full"
                                                :class="driver.status === 1 ? 'bg-success' : driver.status === 2 ? 'bg-warning' : 'bg-danger'" />
                                            <span class="text-xs font-medium">{{ driverStatusLabel(driver.status) }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-3.5 text-sm text-slate-500">{{ driver.created_at }}</td>
                                </tr>
                                <tr v-if="!recentDrivers.length">
                                    <td colspan="3" class="px-6 py-8 text-center text-slate-400 text-sm">No drivers yet</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Recent Documents -->
                <div class="box box--stacked">
                    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200/60">
                        <div class="flex items-center gap-2">
                            <Lucide icon="FileText" class="w-4 h-4 text-primary" />
                            <h3 class="font-semibold text-slate-800">Recent Documents</h3>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-slate-100">
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Document</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Uploaded</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="doc in recentDocuments" :key="doc.id" class="hover:bg-slate-50/50 transition-colors">
                                    <td class="px-6 py-3.5">
                                        <div class="flex items-center gap-3">
                                            <Lucide icon="FileText" class="w-4 h-4 text-slate-400 flex-shrink-0" />
                                            <span class="text-sm font-medium text-slate-800">{{ doc.name }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-3.5">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                            :class="docStatusClass(doc.status)">
                                            {{ doc.status_name }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-3.5 text-sm text-slate-500">{{ doc.created_at }}</td>
                                </tr>
                                <tr v-if="!recentDocuments.length">
                                    <td colspan="3" class="px-6 py-8 text-center text-slate-400 text-sm">No documents yet</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <!-- Document Status Summary -->
                    <div class="px-6 py-4 border-t border-slate-100 grid grid-cols-2 sm:grid-cols-4 gap-4">
                        <div>
                            <div class="flex justify-between text-xs mb-1">
                                <span class="text-slate-500">Approved</span>
                                <span class="font-medium text-success">{{ stats.documents.approved }}</span>
                            </div>
                            <div class="h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                <div class="h-full bg-success rounded-full"
                                    :style="{ width: `${stats.documents.total > 0 ? (stats.documents.approved / stats.documents.total) * 100 : 0}%` }" />
                            </div>
                        </div>
                        <div>
                            <div class="flex justify-between text-xs mb-1">
                                <span class="text-slate-500">Pending</span>
                                <span class="font-medium text-warning">{{ stats.documents.pending }}</span>
                            </div>
                            <div class="h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                <div class="h-full bg-warning rounded-full"
                                    :style="{ width: `${stats.documents.total > 0 ? (stats.documents.pending / stats.documents.total) * 100 : 0}%` }" />
                            </div>
                        </div>
                        <div>
                            <div class="flex justify-between text-xs mb-1">
                                <span class="text-slate-500">Rejected</span>
                                <span class="font-medium text-danger">{{ stats.documents.rejected }}</span>
                            </div>
                            <div class="h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                <div class="h-full bg-danger rounded-full"
                                    :style="{ width: `${stats.documents.total > 0 ? (stats.documents.rejected / stats.documents.total) * 100 : 0}%` }" />
                            </div>
                        </div>
                        <div>
                            <div class="flex justify-between text-xs mb-1">
                                <span class="text-slate-500">In Progress</span>
                                <span class="font-medium text-info">{{ stats.documents.in_process }}</span>
                            </div>
                            <div class="h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                <div class="h-full bg-info rounded-full"
                                    :style="{ width: `${stats.documents.total > 0 ? (stats.documents.in_process / stats.documents.total) * 100 : 0}%` }" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 6-Month Trends (mini bar chart) -->
                <div class="box box--stacked p-6">
                    <div class="flex items-center gap-2 mb-5">
                        <Lucide icon="BarChart3" class="w-4 h-4 text-primary" />
                        <h3 class="font-semibold text-slate-800">6-Month Activity</h3>
                    </div>
                    <div class="flex items-end gap-3 h-32">
                        <div v-for="(trend, i) in trendsData" :key="i" class="flex-1 flex flex-col items-center gap-1">
                            <!-- Docs bar -->
                            <div class="w-full flex flex-col justify-end" style="height: 90px;">
                                <div class="w-full rounded-t bg-primary/30 transition-all"
                                    :style="{ height: `${maxTrend.docs > 0 ? Math.max((trend.documents / maxTrend.docs) * 90, trend.documents > 0 ? 4 : 0) : 0}px` }"
                                    :title="`${trend.documents} docs`" />
                            </div>
                            <span class="text-[10px] text-slate-400 text-center leading-tight">{{ trend.month.split(' ')[0] }}</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-4 mt-3">
                        <div class="flex items-center gap-1.5">
                            <div class="w-3 h-3 rounded-sm bg-primary/30" />
                            <span class="text-xs text-slate-500">Documents uploaded</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ===== SIDEBAR ===== -->
            <div class="col-span-12 xl:col-span-4 flex flex-col gap-6">

                <!-- Carrier Info Card -->
                <div class="box box--stacked p-5">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center">
                            <Lucide icon="Building2" class="w-5 h-5 text-primary" />
                        </div>
                        <div>
                            <div class="font-semibold text-slate-800">{{ carrier.name }}</div>
                            <div class="text-xs text-slate-500">Carrier Account</div>
                        </div>
                    </div>
                    <div class="space-y-2.5">
                        <div v-if="carrier.dot_number" class="flex items-center justify-between">
                            <span class="text-xs text-slate-500">DOT Number</span>
                            <span class="text-xs font-semibold text-slate-700">{{ carrier.dot_number }}</span>
                        </div>
                        <div v-if="carrier.mc_number" class="flex items-center justify-between">
                            <span class="text-xs text-slate-500">MC Number</span>
                            <span class="text-xs font-semibold text-slate-700">{{ carrier.mc_number }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-slate-500">Active Drivers</span>
                            <span class="text-xs font-semibold text-success">{{ advancedMetrics.activeDrivers }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-slate-500">Approval Rate</span>
                            <span class="text-xs font-semibold text-slate-700">{{ advancedMetrics.completionRate }}%</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-slate-500">Docs This Month</span>
                            <span class="text-xs font-semibold text-slate-700">
                                {{ advancedMetrics.documentsThisMonth }}
                                <span v-if="advancedMetrics.documentsGrowth !== 0"
                                    :class="advancedMetrics.documentsGrowth > 0 ? 'text-success' : 'text-danger'"
                                    class="ml-1">
                                    {{ advancedMetrics.documentsGrowth > 0 ? '+' : '' }}{{ advancedMetrics.documentsGrowth }}%
                                </span>
                            </span>
                        </div>
                        <div v-if="advancedMetrics.avgApprovalDays > 0" class="flex items-center justify-between">
                            <span class="text-xs text-slate-500">Avg. Approval Time</span>
                            <span class="text-xs font-semibold text-slate-700">{{ advancedMetrics.avgApprovalDays }} days</span>
                        </div>
                    </div>
                </div>

                <!-- Alerts -->
                <div class="box box--stacked p-5">
                    <div class="flex items-center gap-2 mb-4">
                        <Lucide icon="Bell" class="w-4 h-4 text-primary" />
                        <h3 class="font-semibold text-slate-800">Alerts</h3>
                        <span v-if="alerts.length"
                            class="ml-auto text-xs font-semibold px-2 py-0.5 rounded-full bg-danger/10 text-danger">
                            {{ alerts.length }}
                        </span>
                    </div>
                    <div v-if="alerts.length" class="flex flex-col gap-3">
                        <div v-for="(alert, i) in alerts" :key="i"
                            class="flex items-start gap-3 p-3 rounded-lg border"
                            :class="alertVariantClass(alert.type)">
                            <Lucide :icon="alert.icon" class="w-4 h-4 flex-shrink-0 mt-0.5"
                                :class="alertIconClass(alert.type)" />
                            <div>
                                <div class="text-xs font-semibold">{{ alert.title }}</div>
                                <div class="text-xs mt-0.5 opacity-80">{{ alert.message }}</div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="flex flex-col items-center py-6 text-slate-400">
                        <Lucide icon="CheckCircle" class="w-10 h-10 text-success/40 mb-2" />
                        <p class="text-sm">All systems operational</p>
                    </div>
                </div>

                <!-- Document Types Breakdown -->
                <div v-if="documentTypeCounts.length" class="box box--stacked p-5">
                    <div class="flex items-center gap-2 mb-4">
                        <Lucide icon="PieChart" class="w-4 h-4 text-primary" />
                        <h3 class="font-semibold text-slate-800">Documents by Type</h3>
                    </div>
                    <div class="space-y-2.5">
                        <div v-for="item in documentTypeCounts" :key="item.name"
                            class="flex items-center justify-between">
                            <span class="text-xs text-slate-600 truncate max-w-[60%]">{{ item.name }}</span>
                            <span class="text-xs font-semibold text-slate-700 bg-slate-100 px-2 py-0.5 rounded-full">
                                {{ item.count }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </RazeLayout>
</template>
