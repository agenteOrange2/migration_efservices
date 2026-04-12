<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import CarrierLayout from '@/layouts/CarrierLayout.vue'
import ReportHero from '@/pages/admin/reports/components/ReportHero.vue'
import ReportStats from '@/pages/admin/reports/components/ReportStats.vue'
import Lucide from '@/components/Base/Lucide'

defineOptions({ layout: CarrierLayout })

const props = defineProps({
    carrier: { type: Object, default: () => ({}) },
    generatedAt: { type: String, default: '' },
    quickLinks: { type: Array, default: () => [] },
    stats: { type: Object, default: () => ({}) },
})
const { carrier, generatedAt, quickLinks, stats } = props

const statCards = [
    { label: 'Drivers', value: stats?.drivers?.total ?? 0, icon: 'Users', hint: `${stats?.drivers?.active ?? 0} active` },
    { label: 'Vehicles', value: stats?.vehicles?.total ?? 0, icon: 'Truck', hint: `${stats?.vehicles?.active ?? 0} active` },
    { label: 'Accidents', value: stats?.accidents?.total ?? 0, icon: 'AlertTriangle', hint: `${stats?.accidents?.recent ?? 0} recent` },
    { label: 'Trips', value: stats?.trips?.total ?? 0, icon: 'MapPin', hint: `${stats?.violations?.total ?? 0} violations tracked` },
]
</script>

<template>
    <Head title="Carrier Reports" />

    <div class="grid grid-cols-12 gap-6">
        <div class="col-span-12">
            <ReportHero
                title="Reports Dashboard"
                :subtitle="`Operational reporting hub for ${carrier?.name || 'your carrier'}. Generated ${generatedAt}.`"
                icon="FileText"
            />
        </div>

        <div class="col-span-12">
            <ReportStats :cards="statCards" />
        </div>

        <div class="col-span-12 xl:col-span-8">
            <div class="box box--stacked p-6">
                <div class="mb-5 flex items-center gap-3">
                    <div class="rounded-2xl border border-primary/15 bg-primary/10 p-3">
                        <Lucide icon="LayoutGrid" class="h-5 w-5 text-primary" />
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-slate-800">Available Reports</h2>
                        <p class="text-sm text-slate-500">Jump into the report you need without bouncing across modules.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <Link
                        v-for="link in quickLinks"
                        :key="link.title"
                        :href="link.route"
                        class="rounded-2xl border border-slate-200 bg-white p-5 transition hover:border-primary/30 hover:bg-primary/5"
                    >
                        <div class="flex items-start gap-4">
                            <div class="rounded-xl border border-primary/15 bg-primary/10 p-3">
                                <Lucide :icon="link.icon || 'BarChart3'" class="h-5 w-5 text-primary" />
                            </div>
                            <div class="min-w-0">
                                <div class="font-semibold text-slate-800">{{ link.title }}</div>
                                <div class="mt-1 text-sm text-slate-500">{{ link.meta }}</div>
                            </div>
                        </div>
                    </Link>
                </div>
            </div>
        </div>

        <div class="col-span-12 xl:col-span-4">
            <div class="box box--stacked p-6">
                <h2 class="text-lg font-semibold text-slate-800">Compliance Snapshot</h2>
                <div class="mt-5 space-y-4 text-sm text-slate-600">
                    <div class="rounded-2xl border border-slate-200 p-4">
                        <div class="font-medium text-slate-800">Medical cards</div>
                        <div class="mt-1">{{ stats?.medical_records?.valid ?? 0 }} valid</div>
                        <div class="text-xs text-slate-500">{{ stats?.medical_records?.expiring_soon ?? 0 }} expiring soon</div>
                    </div>
                    <div class="rounded-2xl border border-slate-200 p-4">
                        <div class="font-medium text-slate-800">Licenses</div>
                        <div class="mt-1">{{ stats?.licenses?.total ?? 0 }} total licenses</div>
                        <div class="text-xs text-slate-500">{{ stats?.licenses?.expiring_soon ?? 0 }} expiring in 30 days</div>
                    </div>
                    <div class="rounded-2xl border border-slate-200 p-4">
                        <div class="font-medium text-slate-800">Maintenance & repairs</div>
                        <div class="mt-1">{{ stats?.maintenance?.total ?? 0 }} maintenance records</div>
                        <div class="text-xs text-slate-500">{{ stats?.repairs?.total ?? 0 }} emergency repairs logged</div>
                    </div>
                    <div class="rounded-2xl border border-slate-200 p-4">
                        <div class="font-medium text-slate-800">HOS</div>
                        <div class="mt-1">{{ stats?.hos?.total_logs ?? 0 }} daily logs</div>
                        <div class="text-xs text-slate-500">{{ stats?.violations?.total ?? 0 }} violations currently registered</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
