<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3'
import { reactive } from 'vue'
import Button from '@/components/Base/Button'
import FormInput from '@/components/Base/Form/FormInput.vue'
import Litepicker from '@/components/Base/Litepicker/Litepicker.vue'
import Lucide from '@/components/Base/Lucide'
import TomSelect from '@/components/Base/TomSelect/TomSelect.vue'
import RazeLayout from '@/layouts/RazeLayout.vue'

declare function route(name: string, params?: any): string

defineOptions({ layout: RazeLayout })

const props = defineProps<{
    filters: { carrier_id: string; start_date: string; end_date: string }
    carriers: { id: number; name: string }[]
    isSuperadmin: boolean
    stats: { total_vehicles: number; active_vehicles: number; out_of_service: number; suspended: number }
    maintenanceStats: { total: number; completed: number; pending: number; overdue: number; upcoming: number }
    emergencyStats: { total: number; completed: number; pending: number; total_cost: number }
    documentStats: { total: number; expiring_soon: number; expired: number }
    expiringRegistrations: number
    expiringInspections: number
    vehiclesByType: { label: string; count: number }[]
    vehiclesByDriverType: { label: string; count: number }[]
    maintenanceTrend: { label: string; count: number }[]
    recentMaintenance: { id: number; title: string; vehicle_label: string; status: string; created_at: string | null }[]
    recentEmergencyRepairs: { id: number; title: string; vehicle_label: string; status: string; cost: number; created_at: string | null }[]
}>()

const filters = reactive({
    carrier_id: props.filters.carrier_id || '',
    start_date: props.filters.start_date || '',
    end_date: props.filters.end_date || '',
})

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

const maxVehicleTypeCount = Math.max(...props.vehiclesByType.map((item) => item.count), 1)
const maxTrendCount = Math.max(...props.maintenanceTrend.map((item) => item.count), 1)

function applyFilters() {
    router.get(route('admin.vehicles.dashboard'), {
        carrier_id: filters.carrier_id || undefined,
        start_date: filters.start_date || undefined,
        end_date: filters.end_date || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    })
}

function resetFilters() {
    filters.carrier_id = ''
    filters.start_date = ''
    filters.end_date = ''
    router.get(route('admin.vehicles.dashboard'))
}

function money(value: number) {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(value || 0)
}

function statusBadge(status: string) {
    const normalized = String(status || '').toLowerCase()
    if (['active', 'completed'].includes(normalized)) return 'bg-primary/10 text-primary'
    if (['pending', 'in_progress', 'upcoming'].includes(normalized)) return 'bg-slate-100 text-slate-700'
    if (['overdue', 'expired', 'out_of_service', 'suspended'].includes(normalized)) return 'bg-slate-200 text-slate-700'
    return 'bg-slate-100 text-slate-600'
}

function barWidth(count: number, max: number) {
    return `${Math.max((count / max) * 100, count > 0 ? 6 : 0)}%`
}

function trendHeight(count: number) {
    if (count <= 0) return '8px'
    return `${Math.max((count / maxTrendCount) * 160, 18)}px`
}
</script>

<template>
    <Head title="Vehicles Dashboard" />

    <div class="grid grid-cols-12 gap-x-6 gap-y-10">
        <div class="col-span-12">
            <div class="box box--stacked p-6">
                <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                            <Lucide icon="Truck" class="w-8 h-8 text-primary" />
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-slate-800">Vehicles Dashboard</h1>
                            <p class="text-slate-500">Overview of fleet status, compliance, maintenance, and repair activity.</p>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-3 text-sm text-slate-500">
                        <span class="inline-flex items-center gap-2 rounded-full bg-primary/5 px-3 py-2 text-primary">
                            <Lucide icon="CalendarRange" class="w-4 h-4" />
                            {{ filters.start_date || 'Start' }} to {{ filters.end_date || 'Today' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12">
            <div class="box box--stacked p-5">
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                    <div v-if="isSuperadmin">
                        <label class="block text-xs font-medium uppercase tracking-wide text-slate-500 mb-2">Carrier</label>
                        <TomSelect v-model="filters.carrier_id">
                            <option value="">All Carriers</option>
                            <option v-for="carrier in carriers" :key="carrier.id" :value="String(carrier.id)">{{ carrier.name }}</option>
                        </TomSelect>
                    </div>
                    <div v-else>
                        <label class="block text-xs font-medium uppercase tracking-wide text-slate-500 mb-2">Carrier</label>
                        <FormInput :model-value="carriers[0]?.name || 'Current Carrier'" disabled />
                    </div>
                    <div>
                        <label class="block text-xs font-medium uppercase tracking-wide text-slate-500 mb-2">Start Date</label>
                        <Litepicker v-model="filters.start_date" :options="pickerOptions" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium uppercase tracking-wide text-slate-500 mb-2">End Date</label>
                        <Litepicker v-model="filters.end_date" :options="pickerOptions" />
                    </div>
                    <div class="flex items-end gap-3">
                        <Button variant="primary" class="w-full flex items-center justify-center gap-2" @click="applyFilters">
                            <Lucide icon="Filter" class="w-4 h-4" />
                            Apply Filters
                        </Button>
                        <Button variant="outline-secondary" class="w-full flex items-center justify-center gap-2" @click="resetFilters">
                            <Lucide icon="RotateCcw" class="w-4 h-4" />
                            Reset
                        </Button>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12">
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                <div class="box box--stacked p-5 border border-primary/10 bg-primary/5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-slate-500">Total Vehicles</p>
                            <p class="mt-1 text-2xl font-semibold text-slate-800">{{ stats.total_vehicles }}</p>
                        </div>
                        <Lucide icon="Truck" class="w-8 h-8 text-primary" />
                    </div>
                </div>
                <div class="box box--stacked p-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-slate-500">Active</p>
                            <p class="mt-1 text-2xl font-semibold text-slate-800">{{ stats.active_vehicles }}</p>
                        </div>
                        <Lucide icon="CheckCircle2" class="w-8 h-8 text-primary" />
                    </div>
                </div>
                <div class="box box--stacked p-5 border border-primary/10 bg-primary/5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-slate-500">Out of Service</p>
                            <p class="mt-1 text-2xl font-semibold text-slate-800">{{ stats.out_of_service }}</p>
                        </div>
                        <Lucide icon="CircleOff" class="w-8 h-8 text-primary" />
                    </div>
                </div>
                <div class="box box--stacked p-5 border border-primary/10 bg-primary/5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-slate-500">Suspended</p>
                            <p class="mt-1 text-2xl font-semibold text-slate-800">{{ stats.suspended }}</p>
                        </div>
                        <Lucide icon="AlertTriangle" class="w-8 h-8 text-primary" />
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12">
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
                <div class="box box--stacked p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-base font-semibold text-slate-800">Maintenance Overview</h2>
                        <Lucide icon="Wrench" class="w-5 h-5 text-primary" />
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="rounded-lg bg-slate-50 p-4"><p class="text-xs uppercase tracking-wide text-slate-500">Total</p><p class="mt-1 text-xl font-semibold text-slate-800">{{ maintenanceStats.total }}</p></div>
                        <div class="rounded-lg bg-primary/5 p-4"><p class="text-xs uppercase tracking-wide text-primary">Completed</p><p class="mt-1 text-xl font-semibold text-slate-800">{{ maintenanceStats.completed }}</p></div>
                        <div class="rounded-lg bg-slate-50 p-4"><p class="text-xs uppercase tracking-wide text-slate-600">Pending</p><p class="mt-1 text-xl font-semibold text-slate-800">{{ maintenanceStats.pending }}</p></div>
                        <div class="rounded-lg bg-slate-100 p-4"><p class="text-xs uppercase tracking-wide text-slate-700">Overdue</p><p class="mt-1 text-xl font-semibold text-slate-800">{{ maintenanceStats.overdue }}</p></div>
                    </div>
                    <div class="mt-4 rounded-lg border border-dashed border-slate-200 p-4">
                        <p class="text-sm text-slate-500">Upcoming maintenance within 15 days</p>
                        <p class="mt-1 text-xl font-semibold text-slate-800">{{ maintenanceStats.upcoming }}</p>
                    </div>
                </div>

                <div class="box box--stacked p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-base font-semibold text-slate-800">Emergency Repairs</h2>
                        <Lucide icon="Siren" class="w-5 h-5 text-primary" />
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="rounded-lg bg-slate-50 p-4"><p class="text-xs uppercase tracking-wide text-slate-500">Total</p><p class="mt-1 text-xl font-semibold text-slate-800">{{ emergencyStats.total }}</p></div>
                        <div class="rounded-lg bg-primary/5 p-4"><p class="text-xs uppercase tracking-wide text-primary">Completed</p><p class="mt-1 text-xl font-semibold text-slate-800">{{ emergencyStats.completed }}</p></div>
                        <div class="rounded-lg bg-slate-50 p-4"><p class="text-xs uppercase tracking-wide text-slate-600">Pending</p><p class="mt-1 text-xl font-semibold text-slate-800">{{ emergencyStats.pending }}</p></div>
                        <div class="rounded-lg bg-primary/5 p-4"><p class="text-xs uppercase tracking-wide text-primary">Total Cost</p><p class="mt-1 text-xl font-semibold text-slate-800">{{ money(emergencyStats.total_cost) }}</p></div>
                    </div>
                </div>

                <div class="box box--stacked p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-base font-semibold text-slate-800">Documents & Compliance</h2>
                        <Lucide icon="FileText" class="w-5 h-5 text-primary" />
                    </div>
                    <div class="space-y-3">
                        <div class="rounded-lg bg-slate-50 p-4 flex items-center justify-between"><span class="text-slate-600">Total Documents</span><span class="font-semibold text-slate-800">{{ documentStats.total }}</span></div>
                        <div class="rounded-lg bg-primary/5 p-4 flex items-center justify-between"><span class="text-primary">Docs Expiring Soon</span><span class="font-semibold text-slate-800">{{ documentStats.expiring_soon }}</span></div>
                        <div class="rounded-lg bg-slate-100 p-4 flex items-center justify-between"><span class="text-slate-700">Expired Docs</span><span class="font-semibold text-slate-800">{{ documentStats.expired }}</span></div>
                        <div class="rounded-lg border border-dashed border-slate-200 p-4 flex items-center justify-between"><span class="text-slate-600">Registrations expiring in 30 days</span><span class="font-semibold text-slate-800">{{ expiringRegistrations }}</span></div>
                        <div class="rounded-lg border border-dashed border-slate-200 p-4 flex items-center justify-between"><span class="text-slate-600">Annual inspections expiring in 30 days</span><span class="font-semibold text-slate-800">{{ expiringInspections }}</span></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12 xl:col-span-6">
            <div class="box box--stacked p-6">
                <h2 class="text-base font-semibold text-slate-800 mb-4">Vehicles by Type</h2>
                <div v-if="vehiclesByType.length" class="space-y-4">
                    <div v-for="item in vehiclesByType" :key="item.label">
                        <div class="flex items-center justify-between gap-3 mb-1">
                            <span class="text-sm font-medium text-slate-700">{{ item.label }}</span>
                            <span class="text-sm font-semibold text-slate-800">{{ item.count }}</span>
                        </div>
                        <div class="h-2 rounded-full bg-slate-200 overflow-hidden">
                            <div class="h-full rounded-full bg-primary" :style="{ width: barWidth(item.count, maxVehicleTypeCount) }"></div>
                        </div>
                    </div>
                </div>
                <div v-else class="text-sm text-slate-500">No vehicle type analytics available yet.</div>
            </div>
        </div>

        <div class="col-span-12 xl:col-span-6">
            <div class="box box--stacked p-6">
                <h2 class="text-base font-semibold text-slate-800 mb-4">Vehicles by Driver Type</h2>
                <div v-if="vehiclesByDriverType.length" class="space-y-3">
                    <div v-for="item in vehiclesByDriverType" :key="item.label" class="rounded-lg bg-slate-50 p-4 flex items-center justify-between">
                        <span class="text-sm font-medium text-slate-700">{{ item.label }}</span>
                        <span class="text-sm font-semibold text-slate-800">{{ item.count }}</span>
                    </div>
                </div>
                <div v-else class="text-sm text-slate-500">No driver type distribution available yet.</div>
            </div>
        </div>

        <div class="col-span-12">
            <div class="box box--stacked p-6">
                <h2 class="text-base font-semibold text-slate-800 mb-4">Maintenance Trend - Last 6 Months</h2>
                <div class="h-64 flex items-end gap-3 overflow-x-auto">
                    <div v-for="item in maintenanceTrend" :key="item.label" class="min-w-[60px] flex-1 flex flex-col items-center justify-end gap-2">
                        <span class="text-xs font-medium text-slate-600">{{ item.count }}</span>
                        <div class="w-full rounded-t-md bg-primary/80 hover:bg-primary transition-all" :style="{ height: trendHeight(item.count) }"></div>
                        <span class="text-xs text-slate-500 whitespace-nowrap">{{ item.label }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12 xl:col-span-6">
            <div class="box box--stacked p-6">
                <h2 class="text-base font-semibold text-slate-800 mb-4">Recent Maintenance</h2>
                <div v-if="recentMaintenance.length" class="space-y-3">
                    <div v-for="item in recentMaintenance" :key="item.id" class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-slate-800 truncate">{{ item.title }}</p>
                                <p class="text-xs text-slate-500 truncate">{{ item.vehicle_label }}</p>
                            </div>
                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium capitalize" :class="statusBadge(item.status)">{{ item.status.replace('_', ' ') }}</span>
                        </div>
                        <p class="mt-2 text-xs text-slate-400">{{ item.created_at || 'N/A' }}</p>
                    </div>
                </div>
                <div v-else class="text-sm text-slate-500">No recent maintenance activity found.</div>
            </div>
        </div>

        <div class="col-span-12 xl:col-span-6">
            <div class="box box--stacked p-6">
                <h2 class="text-base font-semibold text-slate-800 mb-4">Recent Emergency Repairs</h2>
                <div v-if="recentEmergencyRepairs.length" class="space-y-3">
                    <div v-for="item in recentEmergencyRepairs" :key="item.id" class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-slate-800 truncate">{{ item.title }}</p>
                                <p class="text-xs text-slate-500 truncate">{{ item.vehicle_label }}</p>
                            </div>
                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium capitalize" :class="statusBadge(item.status)">{{ item.status.replace('_', ' ') }}</span>
                        </div>
                        <div class="mt-2 flex items-center justify-between gap-3 text-xs">
                            <span class="text-primary font-medium">{{ money(item.cost) }}</span>
                            <span class="text-slate-400">{{ item.created_at || 'N/A' }}</span>
                        </div>
                    </div>
                </div>
                <div v-else class="text-sm text-slate-500">No recent emergency repair activity found.</div>
            </div>
        </div>
    </div>
</template>
