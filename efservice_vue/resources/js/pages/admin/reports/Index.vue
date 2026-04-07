<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import Lucide from '@/components/Base/Lucide'
import RazeLayout from '@/layouts/RazeLayout.vue'
import ReportHero from './components/ReportHero.vue'
import ReportStats from './components/ReportStats.vue'

defineOptions({ layout: RazeLayout })

const props = defineProps<{
    stats: Record<string, any>
    quickLinks: { title: string; route: string; icon: string; meta: string }[]
    generatedAt: string
}>()

const cards = [
    { label: 'Total Carriers', value: props.stats.carriers?.total ?? 0, icon: 'Truck', hint: `${props.stats.carriers?.percentage_active ?? 0}% active` },
    { label: 'Total Drivers', value: props.stats.drivers?.total ?? 0, icon: 'Users', hint: `${props.stats.drivers?.percentage_active ?? 0}% active` },
    { label: 'Total Vehicles', value: props.stats.vehicles?.total ?? 0, icon: 'CarFront', hint: `${props.stats.vehicles?.percentage_active ?? 0}% active` },
    { label: 'Carrier Documents', value: props.stats.documents?.total ?? 0, icon: 'FileText', hint: `${props.stats.documents?.percentage_approved ?? 0}% approved` },
    { label: 'Maintenances', value: props.stats.maintenances?.total ?? 0, icon: 'Wrench', hint: `${props.stats.maintenances?.completion_rate ?? 0}% completion` },
    { label: 'Emergency Repairs', value: props.stats.emergency_repairs?.total ?? 0, icon: 'AlertCircle', hint: 'Fleet repairs tracked' },
    { label: 'Trainings', value: props.stats.trainings?.total ?? 0, icon: 'GraduationCap', hint: `${props.stats.trainings?.completion_rate ?? 0}% completion` },
    { label: 'Accidents', value: props.stats.accidents?.total ?? 0, icon: 'AlertTriangle', hint: `${props.stats.accidents?.recent ?? 0} in last 30 days` },
]
</script>

<template>
    <Head title="Reports Dashboard" />

    <div class="grid grid-cols-12 gap-6">
        <div class="col-span-12">
            <ReportHero
                title="Reports Dashboard"
                subtitle="Centralized access to driver, fleet, document, compliance, and migration reporting."
                icon="BarChart3"
            >
                <template #actions>
                    <div class="rounded-full bg-primary/10 px-4 py-2 text-sm font-medium text-primary">
                        Updated {{ generatedAt }}
                    </div>
                </template>
            </ReportHero>
        </div>

        <div class="col-span-12">
            <ReportStats :cards="cards" />
        </div>

        <div class="col-span-12">
            <div class="box box--stacked p-6">
                <div class="mb-5 flex items-center gap-3">
                    <Lucide icon="LayoutGrid" class="h-5 w-5 text-primary" />
                    <h2 class="text-lg font-semibold text-slate-800">Quick Access Reports</h2>
                </div>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <Link
                        v-for="item in quickLinks"
                        :key="item.title"
                        :href="item.route"
                        class="rounded-2xl border border-slate-200 bg-white p-5 transition hover:border-primary/40 hover:bg-primary/5"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <div class="mb-2 inline-flex rounded-xl bg-primary/10 p-3">
                                    <Lucide :icon="item.icon" class="h-5 w-5 text-primary" />
                                </div>
                                <h3 class="text-base font-semibold text-slate-800">{{ item.title }}</h3>
                                <p class="mt-2 text-sm text-slate-500">{{ item.meta }}</p>
                            </div>
                            <Lucide icon="ChevronRight" class="mt-1 h-5 w-5 text-slate-400" />
                        </div>
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>
