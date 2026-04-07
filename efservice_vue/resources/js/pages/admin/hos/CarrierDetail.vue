<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import Lucide from '@/components/Base/Lucide'
import RazeLayout from '@/layouts/RazeLayout.vue'

declare function route(name: string, params?: any): string

defineOptions({ layout: RazeLayout })

defineProps<{
    carrier: { id: number; name: string }
    stats: { active_drivers: number; drivers_with_violations: number; drivers_driving_now: number }
    config: {
        max_driving_hours: number
        max_duty_hours: number
        warning_threshold_minutes: number
        weekly_limit_60_minutes: number
        weekly_limit_70_minutes: number
        require_30_min_break: boolean
        break_after_hours: number
    }
    driverSummaries: {
        id: number
        name: string
        email: string | null
        cycle_type: string
        current_status: string
        driving_today: string
        on_duty_today: string
        off_duty_today: string
        remaining_driving: string
        remaining_duty: string
        today_violations: number
    }[]
}>()
</script>

<template>
    <Head :title="`HOS - ${carrier.name}`" />

    <div class="grid grid-cols-12 gap-6">
        <div class="col-span-12">
            <div class="box box--stacked p-6">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex items-center gap-4">
                        <div class="rounded-2xl border border-primary/20 bg-primary/10 p-3">
                            <Lucide icon="Building2" class="h-8 w-8 text-primary" />
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-slate-800">{{ carrier.name }}</h1>
                            <p class="mt-1 text-sm text-slate-500">Live driver-by-driver HOS status for this carrier.</p>
                        </div>
                    </div>

                    <Link :href="route('admin.hos.dashboard')" class="inline-flex items-center gap-2 text-sm font-medium text-primary hover:underline">
                        <Lucide icon="ArrowLeft" class="h-4 w-4" />
                        Back to overview
                    </Link>
                </div>
            </div>
        </div>

        <div class="col-span-12 grid grid-cols-1 gap-4 md:grid-cols-3">
            <div class="box box--stacked p-5">
                <div class="text-sm text-slate-500">Active Drivers</div>
                <div class="mt-2 text-3xl font-semibold text-slate-800">{{ stats.active_drivers }}</div>
            </div>
            <div class="box box--stacked p-5">
                <div class="text-sm text-slate-500">Driving Right Now</div>
                <div class="mt-2 text-3xl font-semibold text-slate-800">{{ stats.drivers_driving_now }}</div>
            </div>
            <div class="box box--stacked p-5">
                <div class="text-sm text-slate-500">Drivers with Violations Today</div>
                <div class="mt-2 text-3xl font-semibold text-primary">{{ stats.drivers_with_violations }}</div>
            </div>
        </div>

        <div class="col-span-12 xl:col-span-4">
            <div class="box box--stacked p-5">
                <h2 class="text-lg font-semibold text-slate-800">Carrier HOS Rules</h2>
                <div class="mt-4 space-y-3 text-sm">
                    <div class="flex items-center justify-between"><span class="text-slate-500">Driving limit</span><span class="font-medium text-slate-800">{{ config.max_driving_hours }} hours</span></div>
                    <div class="flex items-center justify-between"><span class="text-slate-500">Duty limit</span><span class="font-medium text-slate-800">{{ config.max_duty_hours }} hours</span></div>
                    <div class="flex items-center justify-between"><span class="text-slate-500">Warning threshold</span><span class="font-medium text-slate-800">{{ config.warning_threshold_minutes }} minutes</span></div>
                    <div class="flex items-center justify-between"><span class="text-slate-500">60/7 weekly limit</span><span class="font-medium text-slate-800">{{ Math.round(config.weekly_limit_60_minutes / 60) }} hours</span></div>
                    <div class="flex items-center justify-between"><span class="text-slate-500">70/8 weekly limit</span><span class="font-medium text-slate-800">{{ Math.round(config.weekly_limit_70_minutes / 60) }} hours</span></div>
                    <div class="flex items-center justify-between"><span class="text-slate-500">30-minute break</span><span class="font-medium text-slate-800">{{ config.require_30_min_break ? `Required after ${config.break_after_hours}h` : 'Not required' }}</span></div>
                </div>
            </div>
        </div>

        <div class="col-span-12 xl:col-span-8">
            <div class="box box--stacked overflow-hidden">
                <div class="border-b border-slate-200 px-5 py-4">
                    <h2 class="text-lg font-semibold text-slate-800">Driver Status</h2>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Driver</th>
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Status</th>
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Today</th>
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Remaining</th>
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Cycle</th>
                                <th class="px-5 py-3 text-right font-semibold text-slate-600">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            <tr v-for="driver in driverSummaries" :key="driver.id">
                                <td class="px-5 py-4">
                                    <div class="font-medium text-slate-800">{{ driver.name }}</div>
                                    <div class="text-xs text-slate-500">{{ driver.email || 'No email' }}</div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="text-slate-700">{{ driver.current_status }}</div>
                                    <div class="text-xs" :class="driver.today_violations > 0 ? 'text-primary' : 'text-slate-500'">
                                        {{ driver.today_violations > 0 ? `${driver.today_violations} violation(s) today` : 'No violations today' }}
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-slate-600">
                                    <div>Driving: {{ driver.driving_today }}</div>
                                    <div>On duty: {{ driver.on_duty_today }}</div>
                                    <div>Off duty: {{ driver.off_duty_today }}</div>
                                </td>
                                <td class="px-5 py-4 text-slate-600">
                                    <div>Drive: {{ driver.remaining_driving }}</div>
                                    <div>Duty: {{ driver.remaining_duty }}</div>
                                </td>
                                <td class="px-5 py-4 text-slate-600">{{ driver.cycle_type === '60_7' ? '60 / 7' : '70 / 8' }}</td>
                                <td class="px-5 py-4 text-right">
                                    <Link :href="route('admin.hos.driver.log', driver.id)" class="inline-flex items-center gap-2 text-primary hover:underline">
                                        <Lucide icon="Eye" class="h-4 w-4" />
                                        Driver Log
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="!driverSummaries.length">
                                <td colspan="6" class="px-5 py-10 text-center text-slate-500">No active drivers were found for this carrier.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>
