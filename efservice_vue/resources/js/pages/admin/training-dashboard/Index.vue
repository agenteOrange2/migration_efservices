<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import RazeLayout from '@/layouts/RazeLayout.vue'

declare function route(name: string, params?: any): string

defineOptions({ layout: RazeLayout })

const props = defineProps<{
    overview: {
        total_trainings: number
        active_trainings: number
        inactive_trainings: number
        total_assignments: number
        completed_assignments: number
        in_progress_assignments: number
        pending_assignments: number
        overdue_assignments: number
        completion_rate: number
    }
    trainingStats: { id: number; name: string; total: number; completed: number; rate: number; show_url: string }[]
    carrierStats: { id: number; name: string; total: number; completed: number; rate: number }[]
    recentCompletions: { id: number; driver_name: string; driver_email?: string | null; carrier_name?: string | null; training_title: string; completed_date?: string | null; completed_relative?: string | null; assignment_url: string }[]
    upcomingDue: { id: number; driver_name: string; carrier_name?: string | null; training_title: string; due_date?: string | null; due_relative?: string | null; assignment_url: string }[]
    trend: { date: string; label: string; count: number }[]
}>()

const maxTrendValue = Math.max(...props.trend.map((item) => item.count), 1)

function trendHeight(count: number) {
    return `${Math.max((count / maxTrendValue) * 100, count > 0 ? 12 : 6)}%`
}
</script>

<template>
    <Head title="Training Dashboard" />

    <div class="grid grid-cols-12 gap-x-6 gap-y-10">
        <div class="col-span-12">
            <div class="box box--stacked p-6">
                <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                            <Lucide icon="BarChart3" class="w-8 h-8 text-primary" />
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-slate-800">Training Dashboard</h1>
                            <p class="text-slate-500">Overview and analytics for trainings and driver assignments.</p>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <a :href="route('admin.training-dashboard.export', { type: 'assignments' })">
                            <Button variant="outline-secondary" class="flex items-center gap-2">
                                <Lucide icon="FileText" class="w-4 h-4" />
                                Assignments CSV
                            </Button>
                        </a>
                        <a :href="route('admin.training-dashboard.export', { type: 'trainings' })">
                            <Button variant="outline-secondary" class="flex items-center gap-2">
                                <Lucide icon="BookOpen" class="w-4 h-4" />
                                Trainings CSV
                            </Button>
                        </a>
                        <a :href="route('admin.training-dashboard.export', { type: 'analytics' })">
                            <Button variant="outline-secondary" class="flex items-center gap-2">
                                <Lucide icon="TrendingUp" class="w-4 h-4" />
                                Analytics CSV
                            </Button>
                        </a>
                        <Link :href="route('admin.trainings.index')">
                            <Button variant="primary" class="flex items-center gap-2">
                                <Lucide icon="GraduationCap" class="w-4 h-4" />
                                Trainings
                            </Button>
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12">
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                <div class="box box--stacked p-5 border border-primary/10 bg-primary/5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-slate-500">Total Trainings</p>
                            <p class="mt-1 text-2xl font-semibold text-slate-800">{{ overview.total_trainings }}</p>
                        </div>
                        <Lucide icon="BookOpen" class="w-8 h-8 text-primary" />
                    </div>
                    <p class="mt-3 text-sm text-slate-600">{{ overview.active_trainings }} active • {{ overview.inactive_trainings }} inactive</p>
                </div>

                <div class="box box--stacked p-5 border border-slate-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-slate-500">Assignments</p>
                            <p class="mt-1 text-2xl font-semibold text-slate-800">{{ overview.total_assignments }}</p>
                        </div>
                        <Lucide icon="ClipboardList" class="w-8 h-8 text-primary" />
                    </div>
                    <p class="mt-3 text-sm text-slate-600">All driver training assignments</p>
                </div>

                <div class="box box--stacked p-5 border border-primary/10 bg-primary/5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-slate-500">Completion Rate</p>
                            <p class="mt-1 text-2xl font-semibold text-slate-800">{{ overview.completion_rate }}%</p>
                        </div>
                        <Lucide icon="CheckCircle2" class="w-8 h-8 text-primary" />
                    </div>
                    <p class="mt-3 text-sm text-slate-600">{{ overview.completed_assignments }} completed assignments</p>
                </div>

                <div class="box box--stacked p-5 border border-red-200 bg-red-50/60">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-slate-500">Overdue</p>
                            <p class="mt-1 text-2xl font-semibold text-slate-800">{{ overview.overdue_assignments }}</p>
                        </div>
                        <Lucide icon="AlertTriangle" class="w-8 h-8 text-red-500" />
                    </div>
                    <p class="mt-3 text-sm text-slate-600">Assignments that need attention</p>
                </div>
            </div>
        </div>

        <div class="col-span-12">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="box box--stacked p-5">
                    <p class="text-sm text-slate-500">Completed</p>
                    <p class="mt-1 text-2xl font-semibold text-slate-800">{{ overview.completed_assignments }}</p>
                </div>
                <div class="box box--stacked p-5">
                    <p class="text-sm text-slate-500">In Progress</p>
                    <p class="mt-1 text-2xl font-semibold text-slate-800">{{ overview.in_progress_assignments }}</p>
                </div>
                <div class="box box--stacked p-5">
                    <p class="text-sm text-slate-500">Pending</p>
                    <p class="mt-1 text-2xl font-semibold text-slate-800">{{ overview.pending_assignments }}</p>
                </div>
            </div>
        </div>

        <div class="col-span-12 xl:col-span-6">
            <div class="box box--stacked p-6">
                <h2 class="text-base font-semibold text-slate-800 mb-4">Top Trainings by Assignments</h2>
                <div v-if="trainingStats.length" class="space-y-4">
                    <div v-for="training in trainingStats" :key="training.id">
                        <div class="flex items-center justify-between gap-3 mb-1">
                            <Link :href="training.show_url" class="text-sm font-medium text-slate-700 hover:text-primary truncate">
                                {{ training.name }}
                            </Link>
                            <span class="text-sm font-semibold text-slate-800">{{ training.rate }}%</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="flex-1 h-2 rounded-full bg-slate-200 overflow-hidden">
                                <div class="h-full rounded-full bg-primary" :style="{ width: `${training.rate}%` }"></div>
                            </div>
                            <span class="text-xs text-slate-500 whitespace-nowrap">{{ training.completed }}/{{ training.total }}</span>
                        </div>
                    </div>
                </div>
                <div v-else class="text-sm text-slate-500">No training analytics available yet.</div>
            </div>
        </div>

        <div class="col-span-12 xl:col-span-6">
            <div class="box box--stacked p-6">
                <h2 class="text-base font-semibold text-slate-800 mb-4">Top Carriers by Performance</h2>
                <div v-if="carrierStats.length" class="space-y-4">
                    <div v-for="carrier in carrierStats" :key="carrier.id">
                        <div class="flex items-center justify-between gap-3 mb-1">
                            <span class="text-sm font-medium text-slate-700 truncate">{{ carrier.name }}</span>
                            <span class="text-sm font-semibold text-slate-800">{{ carrier.rate }}%</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="flex-1 h-2 rounded-full bg-slate-200 overflow-hidden">
                                <div class="h-full rounded-full bg-primary/80" :style="{ width: `${carrier.rate}%` }"></div>
                            </div>
                            <span class="text-xs text-slate-500 whitespace-nowrap">{{ carrier.completed }}/{{ carrier.total }}</span>
                        </div>
                    </div>
                </div>
                <div v-else class="text-sm text-slate-500">No carrier analytics available yet.</div>
            </div>
        </div>

        <div class="col-span-12">
            <div class="box box--stacked p-6">
                <h2 class="text-base font-semibold text-slate-800 mb-4">Completions Trend - Last 30 Days</h2>
                <div class="h-64 flex items-end gap-2 overflow-x-auto">
                    <div v-for="point in trend" :key="point.date" class="min-w-[24px] flex-1 flex flex-col items-center justify-end gap-2">
                        <div class="w-full rounded-t-md bg-primary/80 hover:bg-primary transition-all" :style="{ height: trendHeight(point.count) }" :title="`${point.label}: ${point.count}`"></div>
                        <span class="text-[11px] text-slate-500 rotate-[-45deg] origin-top-left whitespace-nowrap">{{ point.label }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12 xl:col-span-6">
            <div class="box box--stacked p-6">
                <h2 class="text-base font-semibold text-slate-800 mb-4">Recent Completions</h2>
                <div v-if="recentCompletions.length" class="space-y-3">
                    <Link v-for="item in recentCompletions" :key="item.id" :href="item.assignment_url" class="block rounded-lg border border-slate-200 bg-slate-50 p-4 hover:border-primary/30 hover:bg-primary/5 transition">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-slate-800 truncate">{{ item.driver_name }}</p>
                                <p class="text-xs text-slate-500 truncate">{{ item.training_title }}</p>
                                <p class="text-xs text-slate-500 truncate">{{ item.carrier_name || 'No carrier' }}</p>
                            </div>
                            <div class="text-right shrink-0">
                                <p class="text-xs font-medium text-slate-700">{{ item.completed_date || 'N/A' }}</p>
                                <p class="text-xs text-slate-500">{{ item.completed_relative || '' }}</p>
                            </div>
                        </div>
                    </Link>
                </div>
                <div v-else class="text-sm text-slate-500">No recent completions.</div>
            </div>
        </div>

        <div class="col-span-12 xl:col-span-6">
            <div class="box box--stacked p-6">
                <h2 class="text-base font-semibold text-slate-800 mb-4">Due Soon - Next 7 Days</h2>
                <div v-if="upcomingDue.length" class="space-y-3">
                    <Link v-for="item in upcomingDue" :key="item.id" :href="item.assignment_url" class="block rounded-lg border border-slate-200 bg-slate-50 p-4 hover:border-primary/30 hover:bg-primary/5 transition">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-slate-800 truncate">{{ item.driver_name }}</p>
                                <p class="text-xs text-slate-500 truncate">{{ item.training_title }}</p>
                                <p class="text-xs text-slate-500 truncate">{{ item.carrier_name || 'No carrier' }}</p>
                            </div>
                            <div class="text-right shrink-0">
                                <p class="text-xs font-medium text-red-600">{{ item.due_date || 'N/A' }}</p>
                                <p class="text-xs text-slate-500">{{ item.due_relative || '' }}</p>
                            </div>
                        </div>
                    </Link>
                </div>
                <div v-else class="text-sm text-slate-500">No upcoming due dates.</div>
            </div>
        </div>
    </div>
</template>
