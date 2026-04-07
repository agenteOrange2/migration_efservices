<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import Lucide from '@/components/Base/Lucide'
import Button from '@/components/Base/Button'
import RazeLayout from '@/layouts/RazeLayout.vue'

declare function route(name: string, params?: any): string

defineOptions({ layout: RazeLayout })

defineProps<{
    stats: { carrier_count: number; active_drivers: number; today_violations: number; month_violations: number }
    carrierSummaries: {
        id: number
        name: string
        active_drivers: number
        today_violations: number
        month_violations: number
        max_driving_hours: number
        max_duty_hours: number
        warning_threshold_minutes: number
    }[]
}>()
</script>

<template>
    <Head title="HOS Overview" />

    <div class="grid grid-cols-12 gap-6">
        <div class="col-span-12">
            <div class="box box--stacked p-6">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex items-center gap-4">
                        <div class="rounded-2xl border border-primary/20 bg-primary/10 p-3">
                            <Lucide icon="Clock" class="h-8 w-8 text-primary" />
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-slate-800">HOS Overview</h1>
                            <p class="mt-1 text-sm text-slate-500">System-wide hours-of-service visibility by carrier, with quick access to violations and documents.</p>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <Button as="a" :href="route('admin.hos.documents.index')" variant="outline-secondary" class="gap-2">
                            <Lucide icon="FileText" class="h-4 w-4" />
                            Documents
                        </Button>
                        <Button as="a" :href="route('admin.hos.violations')" variant="primary" class="gap-2">
                            <Lucide icon="AlertTriangle" class="h-4 w-4" />
                            Violations
                        </Button>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="box box--stacked p-5">
                <div class="text-sm text-slate-500">Carriers</div>
                <div class="mt-2 text-3xl font-semibold text-slate-800">{{ stats.carrier_count }}</div>
            </div>
            <div class="box box--stacked p-5">
                <div class="text-sm text-slate-500">Active Drivers</div>
                <div class="mt-2 text-3xl font-semibold text-slate-800">{{ stats.active_drivers }}</div>
            </div>
            <div class="box box--stacked p-5">
                <div class="text-sm text-slate-500">Today's Violations</div>
                <div class="mt-2 text-3xl font-semibold text-primary">{{ stats.today_violations }}</div>
            </div>
            <div class="box box--stacked p-5">
                <div class="text-sm text-slate-500">Month Violations</div>
                <div class="mt-2 text-3xl font-semibold text-slate-800">{{ stats.month_violations }}</div>
            </div>
        </div>

        <div class="col-span-12">
            <div class="box box--stacked overflow-hidden">
                <div class="border-b border-slate-200 px-5 py-4">
                    <h2 class="text-lg font-semibold text-slate-800">Carrier Summary</h2>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Carrier</th>
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Drivers</th>
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Today's Violations</th>
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Month Violations</th>
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Limits</th>
                                <th class="px-5 py-3 text-right font-semibold text-slate-600">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            <tr v-for="carrier in carrierSummaries" :key="carrier.id">
                                <td class="px-5 py-4">
                                    <div class="font-medium text-slate-800">{{ carrier.name }}</div>
                                    <div class="text-xs text-slate-500">Warning threshold: {{ carrier.warning_threshold_minutes }} min</div>
                                </td>
                                <td class="px-5 py-4 text-slate-600">{{ carrier.active_drivers }}</td>
                                <td class="px-5 py-4">
                                    <span class="rounded-full px-2.5 py-1 text-xs font-medium" :class="carrier.today_violations > 0 ? 'bg-primary/10 text-primary' : 'bg-slate-100 text-slate-600'">
                                        {{ carrier.today_violations }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-slate-600">{{ carrier.month_violations }}</td>
                                <td class="px-5 py-4 text-slate-600">{{ carrier.max_driving_hours }}h driving / {{ carrier.max_duty_hours }}h duty</td>
                                <td class="px-5 py-4 text-right">
                                    <Link :href="route('admin.hos.carrier.detail', carrier.id)" class="inline-flex items-center gap-2 text-primary hover:underline">
                                        <Lucide icon="Eye" class="h-4 w-4" />
                                        Open
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="!carrierSummaries.length">
                                <td colspan="6" class="px-5 py-10 text-center text-slate-500">No carriers with HOS access were found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>
