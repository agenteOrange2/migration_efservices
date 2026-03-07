<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import { Menu } from '@/components/Base/Headless'
import Lucide from '@/components/Base/Lucide'
import RazeLayout from '@/layouts/RazeLayout.vue'

const page = usePage()
const auth = computed(() => page.props.auth as any)

interface Props {
    statistics: Record<string, any>
    recentRecords: {
        carriers: any[]
        drivers: any[]
        vehicles: any[]
    }
    systemAlerts: any[]
}

const props = defineProps<Props>()

const carrierStats = computed(() => props.statistics?.carriers ?? {})
const driverStats = computed(() => props.statistics?.drivers ?? {})
const vehicleStats = computed(() => props.statistics?.vehicles ?? {})
const revenueStats = computed(() => props.statistics?.revenue ?? {})
const growthData = computed(() => props.statistics?.growth ?? {})

const stats = computed(() => [
    {
        title: 'Total Carriers',
        value: carrierStats.value.total ?? 0,
        active: carrierStats.value.active ?? 0,
        pending: carrierStats.value.pending ?? 0,
        change: `${carrierStats.value.monthly_growth_rate > 0 ? '+' : ''}${carrierStats.value.monthly_growth_rate ?? 0}%`,
        changeType: (carrierStats.value.monthly_growth_rate ?? 0) >= 0 ? 'up' : 'down',
        icon: 'Truck',
        color: 'primary',
    },
    {
        title: 'Total Drivers',
        value: driverStats.value.total ?? 0,
        active: driverStats.value.active ?? 0,
        pending: driverStats.value.inactive ?? 0,
        change: `${driverStats.value.monthly_growth_rate > 0 ? '+' : ''}${driverStats.value.monthly_growth_rate ?? 0}%`,
        changeType: (driverStats.value.monthly_growth_rate ?? 0) >= 0 ? 'up' : 'down',
        icon: 'Users',
        color: 'success',
    },
    {
        title: 'Total Vehicles',
        value: vehicleStats.value.total ?? 0,
        active: vehicleStats.value.active ?? 0,
        pending: vehicleStats.value.pending ?? 0,
        change: `${vehicleStats.value.monthly_growth_rate > 0 ? '+' : ''}${vehicleStats.value.monthly_growth_rate ?? 0}%`,
        changeType: (vehicleStats.value.monthly_growth_rate ?? 0) >= 0 ? 'up' : 'down',
        icon: 'Car',
        color: 'warning',
    },
])

const quickStats = computed(() => [
    {
        title: 'Active Carriers',
        value: carrierStats.value.active ?? 0,
        change: `${carrierStats.value.activation_rate ?? 0}%`,
        changeType: 'up',
    },
    {
        title: 'Active Drivers',
        value: driverStats.value.active ?? 0,
        change: `${driverStats.value.activation_rate ?? 0}%`,
        changeType: 'up',
    },
    {
        title: 'Monthly Revenue',
        value: `$${Number(revenueStats.value.total_active_revenue ?? 0).toLocaleString()}`,
        change: `${revenueStats.value.revenue_realization_rate ?? 0}%`,
        changeType: 'up',
    },
    {
        title: 'Pending Approvals',
        value: carrierStats.value.pending ?? 0,
        change: `${carrierStats.value.pending ?? 0} pending`,
        changeType: (carrierStats.value.pending ?? 0) > 0 ? 'down' : 'up',
    },
])

function getStatusColor(status: string): string {
    const map: Record<string, string> = {
        'Active': 'text-success',
        'Inactive': 'text-danger',
        'Pending': 'text-warning',
        'Pending Validation': 'text-warning',
        'Suspended': 'text-danger',
        'Rejected': 'text-danger',
    }
    return map[status] ?? 'text-slate-500'
}

function getStatusIcon(status: string): string {
    const map: Record<string, string> = {
        'Active': 'CheckCircle',
        'Inactive': 'XCircle',
        'Pending': 'Clock',
        'Pending Validation': 'Clock',
        'Suspended': 'AlertTriangle',
        'Completed': 'CheckCircle',
    }
    return map[status] ?? 'Circle'
}

function getAlertColor(type: string): string {
    const map: Record<string, string> = {
        'error': 'text-danger',
        'warning': 'text-warning',
        'info': 'text-info',
        'opportunity': 'text-success',
    }
    return map[type] ?? 'text-slate-500'
}

function getAlertIcon(type: string): string {
    const map: Record<string, string> = {
        'error': 'AlertOctagon',
        'warning': 'AlertTriangle',
        'info': 'Info',
        'opportunity': 'TrendingUp',
    }
    return map[type] ?? 'Bell'
}

const monthlyData = computed(() => growthData.value?.monthly_data ?? [])
</script>

<template>
    <Head title="Admin Dashboard" />

    <RazeLayout>
        <div class="grid grid-cols-12 gap-y-10 gap-x-6">
            <!-- Header -->
            <div class="col-span-12">
                <div class="flex flex-col md:h-10 gap-y-3 md:items-center md:flex-row">
                    <div class="text-base font-medium">Admin Dashboard</div>
                    <div class="flex flex-col sm:flex-row gap-x-3 gap-y-2 md:ml-auto">
                        <div class="text-slate-500 text-sm">
                            Welcome, {{ auth.user?.name }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="col-span-12">
                <div class="grid grid-cols-12 gap-5">
                    <!-- Featured Card -->
                    <div class="col-span-12 p-1 md:col-span-6 2xl:col-span-3 box box--stacked">
                        <div class="overflow-hidden relative flex flex-col w-full h-full p-5 rounded-[0.5rem] bg-linear-to-b from-theme-2/90 to-theme-1/[0.85] min-h-[244px]">
                            <Lucide icon="Medal" class="absolute top-0 right-0 w-36 h-36 -mt-5 -mr-5 text-white/20 fill-white/[0.03] transform rotate-[-10deg] stroke-[0.3]" />
                            <div class="mt-12 mb-9">
                                <div class="text-2xl font-medium leading-snug text-white">
                                    EFService
                                    <br />
                                    Admin Panel
                                </div>
                                <div class="mt-1.5 text-lg text-white/70">
                                    Fleet Management System
                                </div>
                            </div>
                            <div class="flex items-center gap-4 text-white/80 text-sm">
                                <span>{{ carrierStats.total ?? 0 }} Carriers</span>
                                <span>&bull;</span>
                                <span>{{ driverStats.total ?? 0 }} Drivers</span>
                                <span>&bull;</span>
                                <span>{{ vehicleStats.total ?? 0 }} Vehicles</span>
                            </div>
                        </div>
                    </div>

                    <!-- Stat Cards -->
                    <template v-for="(stat, index) in stats" :key="index">
                        <div class="flex flex-col col-span-12 p-5 md:col-span-6 2xl:col-span-3 box box--stacked">
                            <Menu class="absolute top-0 right-0 mt-5 mr-5">
                                <Menu.Button class="w-5 h-5 text-slate-500">
                                    <Lucide icon="MoreVertical" class="w-6 h-6 stroke-slate-400/70 fill-slate-400/70" />
                                </Menu.Button>
                                <Menu.Items class="w-40">
                                    <Menu.Item>
                                        <Lucide icon="Copy" class="w-4 h-4 mr-2" /> Copy
                                    </Menu.Item>
                                    <Menu.Item>
                                        <Lucide icon="FileText" class="w-4 h-4 mr-2" /> Export
                                    </Menu.Item>
                                </Menu.Items>
                            </Menu>
                            <div class="flex items-center">
                                <div :class="[
                                    'flex items-center justify-center w-12 h-12 border rounded-full',
                                    stat.color === 'primary' ? 'border-primary/10 bg-primary/10' : '',
                                    stat.color === 'success' ? 'border-success/10 bg-success/10' : '',
                                    stat.color === 'warning' ? 'border-warning/10 bg-warning/10' : '',
                                ]">
                                    <Lucide :icon="stat.icon" :class="[
                                        'w-6 h-6',
                                        stat.color === 'primary' ? 'text-primary fill-primary/10' : '',
                                        stat.color === 'success' ? 'text-success fill-success/10' : '',
                                        stat.color === 'warning' ? 'text-warning fill-warning/10' : '',
                                    ]" />
                                </div>
                                <div class="ml-4">
                                    <div class="text-2xl font-medium">{{ stat.value }}</div>
                                    <div class="text-slate-500 mt-0.5">{{ stat.title }}</div>
                                </div>
                            </div>
                            <div class="mt-5 mb-3">
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-slate-500">Active: {{ stat.active }}</span>
                                    <span class="text-slate-500">Pending: {{ stat.pending }}</span>
                                </div>
                                <div class="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-slate-100">
                                    <div class="h-full rounded-full bg-linear-to-r from-theme-1 to-theme-2 transition-all" :style="{ width: `${stat.value > 0 ? (stat.active / stat.value) * 100 : 0}%` }" />
                                </div>
                            </div>
                            <div class="flex flex-wrap items-center justify-center gap-y-3 gap-x-5 mt-auto">
                                <div class="flex items-center">
                                    <div :class="[
                                        'w-2 h-2 rounded-full',
                                        stat.color === 'primary' ? 'bg-primary/70' : '',
                                        stat.color === 'success' ? 'bg-success/70' : '',
                                        stat.color === 'warning' ? 'bg-warning/70' : '',
                                    ]" />
                                    <div class="ml-2.5 text-sm">{{ stat.title }}</div>
                                </div>
                                <div class="flex items-center">
                                    <Lucide :icon="stat.changeType === 'up' ? 'TrendingUp' : 'TrendingDown'" :class="[
                                        'w-4 h-4 mr-1',
                                        stat.changeType === 'up' ? 'text-success' : 'text-danger',
                                    ]" />
                                    <div class="text-sm">{{ stat.change }}</div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Quick Stats Row -->
            <div class="col-span-12">
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-5">
                    <template v-for="(qs, index) in quickStats" :key="'quick-' + index">
                        <div class="p-5 box box--stacked">
                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="text-2xl font-medium">{{ qs.value }}</div>
                                    <div class="text-slate-500 mt-1 text-sm">{{ qs.title }}</div>
                                </div>
                                <div :class="[
                                    'flex items-center text-sm font-medium',
                                    qs.changeType === 'up' ? 'text-success' : 'text-danger',
                                ]">
                                    <Lucide :icon="qs.changeType === 'up' ? 'ChevronUp' : 'ChevronDown'" class="w-4 h-4 mr-0.5" />
                                    {{ qs.change }}
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Recent Carriers Table -->
            <div class="col-span-12 xl:col-span-8">
                <div class="flex flex-col md:h-10 gap-y-3 md:items-center md:flex-row">
                    <div class="text-base font-medium">Recent Carriers</div>
                    <div class="md:ml-auto">
                        <Link :href="route('admin.carriers.index')" class="text-sm text-primary hover:underline flex items-center">
                            View All <Lucide icon="ArrowRight" class="w-3.5 h-3.5 ml-1" />
                        </Link>
                    </div>
                </div>
                <div class="mt-3.5 overflow-auto">
                    <table class="w-full border-spacing-y-[10px] border-separate">
                        <tbody>
                            <tr v-for="carrier in recentRecords.carriers" :key="carrier.id">
                                <td class="box shadow-[5px_3px_5px_#00000005] first:border-l last:border-r first:rounded-l-[0.6rem] last:rounded-r-[0.6rem] rounded-l-none rounded-r-none border-x-0 dark:bg-darkmode-600 px-5 py-4">
                                    <div class="flex items-center">
                                        <Lucide icon="Truck" class="w-5 h-5 text-theme-1 fill-primary/10 stroke-[1.3]" />
                                        <div class="ml-3.5">
                                            <div class="font-medium whitespace-nowrap">{{ carrier.name }}</div>
                                            <div class="mt-0.5 text-xs text-slate-500 whitespace-nowrap">{{ carrier.plan ?? 'No Plan' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="w-44 box shadow-[5px_3px_5px_#00000005] first:border-l last:border-r first:rounded-l-[0.6rem] last:rounded-r-[0.6rem] rounded-l-none rounded-r-none border-x-0 dark:bg-darkmode-600 px-5 py-4">
                                    <div class="mb-1 text-xs text-slate-500 whitespace-nowrap">Location</div>
                                    <div class="whitespace-nowrap text-sm">{{ carrier.state ?? 'N/A' }}</div>
                                </td>
                                <td class="w-36 box shadow-[5px_3px_5px_#00000005] first:border-l last:border-r first:rounded-l-[0.6rem] last:rounded-r-[0.6rem] rounded-l-none rounded-r-none border-x-0 dark:bg-darkmode-600 px-5 py-4">
                                    <div class="mb-1 text-xs text-slate-500 whitespace-nowrap">Status</div>
                                    <div :class="['flex items-center', getStatusColor(carrier.status)]">
                                        <Lucide :icon="getStatusIcon(carrier.status)" class="w-3.5 h-3.5 stroke-[1.7]" />
                                        <div class="ml-1.5 whitespace-nowrap">{{ carrier.status }}</div>
                                    </div>
                                </td>
                                <td class="w-36 box shadow-[5px_3px_5px_#00000005] first:border-l last:border-r first:rounded-l-[0.6rem] last:rounded-r-[0.6rem] rounded-l-none rounded-r-none border-x-0 dark:bg-darkmode-600 px-5 py-4">
                                    <div class="mb-1 text-xs text-slate-500 whitespace-nowrap">Registered</div>
                                    <div class="whitespace-nowrap text-sm">{{ carrier.created_at }}</div>
                                </td>
                            </tr>
                            <tr v-if="!recentRecords.carriers?.length">
                                <td colspan="4" class="box px-5 py-8 text-center text-slate-500">No carriers found</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Recent Drivers -->
                <div class="flex flex-col md:h-10 gap-y-3 md:items-center md:flex-row mt-8">
                    <div class="text-base font-medium">Recent Drivers</div>
                </div>
                <div class="mt-3.5 overflow-auto">
                    <table class="w-full border-spacing-y-[10px] border-separate">
                        <tbody>
                            <tr v-for="driver in recentRecords.drivers" :key="driver.id">
                                <td class="box shadow-[5px_3px_5px_#00000005] first:border-l last:border-r first:rounded-l-[0.6rem] last:rounded-r-[0.6rem] rounded-l-none rounded-r-none border-x-0 dark:bg-darkmode-600 px-5 py-4">
                                    <div class="flex items-center">
                                        <Lucide icon="User" class="w-5 h-5 text-theme-1 fill-primary/10 stroke-[1.3]" />
                                        <div class="ml-3.5">
                                            <div class="font-medium whitespace-nowrap">{{ driver.full_name }}</div>
                                            <div class="mt-0.5 text-xs text-slate-500 whitespace-nowrap">{{ driver.email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="w-44 box shadow-[5px_3px_5px_#00000005] first:border-l last:border-r first:rounded-l-[0.6rem] last:rounded-r-[0.6rem] rounded-l-none rounded-r-none border-x-0 dark:bg-darkmode-600 px-5 py-4">
                                    <div class="mb-1 text-xs text-slate-500 whitespace-nowrap">Carrier</div>
                                    <div class="flex items-center text-primary">
                                        <Lucide icon="ExternalLink" class="w-3.5 h-3.5 stroke-[1.7]" />
                                        <div class="ml-1.5 whitespace-nowrap text-sm">{{ driver.carrier_name }}</div>
                                    </div>
                                </td>
                                <td class="w-36 box shadow-[5px_3px_5px_#00000005] first:border-l last:border-r first:rounded-l-[0.6rem] last:rounded-r-[0.6rem] rounded-l-none rounded-r-none border-x-0 dark:bg-darkmode-600 px-5 py-4">
                                    <div class="mb-1 text-xs text-slate-500 whitespace-nowrap">Status</div>
                                    <div :class="['flex items-center', getStatusColor(driver.status)]">
                                        <Lucide :icon="getStatusIcon(driver.status)" class="w-3.5 h-3.5 stroke-[1.7]" />
                                        <div class="ml-1.5 whitespace-nowrap">{{ driver.status }}</div>
                                    </div>
                                </td>
                                <td class="w-36 box shadow-[5px_3px_5px_#00000005] first:border-l last:border-r first:rounded-l-[0.6rem] last:rounded-r-[0.6rem] rounded-l-none rounded-r-none border-x-0 dark:bg-darkmode-600 px-5 py-4">
                                    <div class="mb-1 text-xs text-slate-500 whitespace-nowrap">Registered</div>
                                    <div class="whitespace-nowrap text-sm">{{ driver.created_at }}</div>
                                </td>
                            </tr>
                            <tr v-if="!recentRecords.drivers?.length">
                                <td colspan="4" class="box px-5 py-8 text-center text-slate-500">No drivers found</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Recent Vehicles -->
                <div class="flex flex-col md:h-10 gap-y-3 md:items-center md:flex-row mt-8">
                    <div class="text-base font-medium">Recent Vehicles</div>
                </div>
                <div class="mt-3.5 overflow-auto">
                    <table class="w-full border-spacing-y-[10px] border-separate">
                        <tbody>
                            <tr v-for="vehicle in recentRecords.vehicles" :key="vehicle.id">
                                <td class="box shadow-[5px_3px_5px_#00000005] first:border-l last:border-r first:rounded-l-[0.6rem] last:rounded-r-[0.6rem] rounded-l-none rounded-r-none border-x-0 dark:bg-darkmode-600 px-5 py-4">
                                    <div class="flex items-center">
                                        <Lucide icon="Car" class="w-5 h-5 text-theme-1 fill-primary/10 stroke-[1.3]" />
                                        <div class="ml-3.5">
                                            <div class="font-medium whitespace-nowrap">{{ vehicle.make_model }}</div>
                                            <div class="mt-0.5 text-xs text-slate-500 whitespace-nowrap">{{ vehicle.assignment_type }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="w-44 box shadow-[5px_3px_5px_#00000005] first:border-l last:border-r first:rounded-l-[0.6rem] last:rounded-r-[0.6rem] rounded-l-none rounded-r-none border-x-0 dark:bg-darkmode-600 px-5 py-4">
                                    <div class="mb-1 text-xs text-slate-500 whitespace-nowrap">Carrier</div>
                                    <div class="whitespace-nowrap text-sm">{{ vehicle.carrier_name }}</div>
                                </td>
                                <td class="w-36 box shadow-[5px_3px_5px_#00000005] first:border-l last:border-r first:rounded-l-[0.6rem] last:rounded-r-[0.6rem] rounded-l-none rounded-r-none border-x-0 dark:bg-darkmode-600 px-5 py-4">
                                    <div class="mb-1 text-xs text-slate-500 whitespace-nowrap">Status</div>
                                    <div :class="['flex items-center', getStatusColor(vehicle.status)]">
                                        <Lucide :icon="getStatusIcon(vehicle.status)" class="w-3.5 h-3.5 stroke-[1.7]" />
                                        <div class="ml-1.5 whitespace-nowrap">{{ vehicle.status }}</div>
                                    </div>
                                </td>
                                <td class="w-36 box shadow-[5px_3px_5px_#00000005] first:border-l last:border-r first:rounded-l-[0.6rem] last:rounded-r-[0.6rem] rounded-l-none rounded-r-none border-x-0 dark:bg-darkmode-600 px-5 py-4">
                                    <div class="mb-1 text-xs text-slate-500 whitespace-nowrap">Registered</div>
                                    <div class="whitespace-nowrap text-sm">{{ vehicle.registration_date }}</div>
                                </td>
                            </tr>
                            <tr v-if="!recentRecords.vehicles?.length">
                                <td colspan="4" class="box px-5 py-8 text-center text-slate-500">No vehicles found</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Sidebar: Alerts + Growth -->
            <div class="col-span-12 xl:col-span-4">
                <!-- System Alerts -->
                <div class="flex flex-col md:h-10 gap-y-3 md:items-center md:flex-row">
                    <div class="text-base font-medium">System Alerts</div>
                </div>
                <div class="p-5 mt-3.5 box box--stacked">
                    <div v-if="systemAlerts?.length" class="flex flex-col gap-5">
                        <div
                            v-for="(alert, index) in systemAlerts"
                            :key="index"
                            class="flex items-start"
                            :class="{ 'pb-5 border-b border-dashed dark:border-darkmode-400': index < systemAlerts.length - 1 }"
                        >
                            <div :class="[
                                'flex items-center justify-center flex-shrink-0 w-10 h-10 rounded-full bg-slate-100 dark:bg-darkmode-400',
                                getAlertColor(alert.type),
                            ]">
                                <Lucide :icon="getAlertIcon(alert.type)" class="w-4 h-4" />
                            </div>
                            <div class="ml-3.5">
                                <div class="text-sm font-medium">{{ alert.title }}</div>
                                <div class="text-xs text-slate-500 mt-1">{{ alert.message }}</div>
                                <div v-if="alert.count" class="mt-1">
                                    <span :class="[
                                        'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium',
                                        alert.type === 'error' ? 'bg-danger/10 text-danger' : '',
                                        alert.type === 'warning' ? 'bg-warning/10 text-warning' : '',
                                        alert.type === 'info' ? 'bg-info/10 text-info' : '',
                                        alert.type === 'opportunity' ? 'bg-success/10 text-success' : '',
                                    ]">
                                        {{ alert.count }} items
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-center py-8 text-slate-400">
                        <Lucide icon="CheckCircle" class="w-10 h-10 mx-auto mb-3 text-success/50" />
                        <p class="text-sm">All systems operational</p>
                    </div>
                </div>

                <!-- Monthly Growth -->
                <div class="flex flex-col md:h-10 gap-y-3 md:items-center md:flex-row mt-8">
                    <div class="text-base font-medium">Monthly Growth</div>
                </div>
                <div class="p-5 mt-3.5 box box--stacked">
                    <div v-if="monthlyData.length" class="flex flex-col gap-4">
                        <div
                            v-for="(month, index) in monthlyData.slice(-4)"
                            :key="index"
                            class="flex items-center justify-between"
                            :class="{ 'pb-4 border-b border-dashed dark:border-darkmode-400': index < Math.min(monthlyData.length, 4) - 1 }"
                        >
                            <div>
                                <div class="text-sm font-medium">{{ month.month_name }}</div>
                                <div class="text-xs text-slate-500 mt-0.5">
                                    {{ month.carriers }} carriers &bull; {{ month.drivers }} drivers
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-sm font-medium">${{ Number(month.revenue ?? 0).toLocaleString() }}</div>
                                <div class="text-xs text-slate-500">revenue</div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-center py-8 text-slate-400">
                        <Lucide icon="BarChart3" class="w-10 h-10 mx-auto mb-3 text-slate-300" />
                        <p class="text-sm">No growth data yet</p>
                    </div>
                </div>

                <!-- Quick Summary -->
                <div class="p-5 mt-5 box box--stacked">
                    <div class="text-sm font-medium mb-4">Platform Summary</div>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-slate-500">Total Users</span>
                            <span class="text-sm font-medium">{{ statistics?.users?.total ?? 0 }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-slate-500">Email Verified</span>
                            <span class="text-sm font-medium">{{ statistics?.users?.email_verification_rate ?? 0 }}%</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-slate-500">Carrier Activation</span>
                            <span class="text-sm font-medium">{{ carrierStats.activation_rate ?? 0 }}%</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-slate-500">Active Subscribers</span>
                            <span class="text-sm font-medium">{{ revenueStats.total_active_subscribers ?? 0 }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </RazeLayout>
</template>
