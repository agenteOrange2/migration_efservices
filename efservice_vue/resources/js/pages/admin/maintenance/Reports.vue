<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { computed, reactive } from 'vue'
import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import TomSelect from '@/components/Base/TomSelect/TomSelect.vue'
import Litepicker from '@/components/Base/Litepicker/Litepicker.vue'
import RazeLayout from '@/layouts/RazeLayout.vue'

declare function route(name: string, params?: any): string

defineOptions({ layout: RazeLayout })

interface PaginationLink { url: string | null; label: string; active: boolean }

const props = defineProps<{
    filters: { carrier_id: string; vehicle_id: string; period: string; status: string; start_date: string; end_date: string }
    carriers: { id: number; name: string }[]
    vehicles: { id: number; carrier_id: number | null; carrier_name: string | null; label: string }[]
    statusOptions: Record<string, string>
    periodOptions: Record<string, string>
    records: { data: any[]; links: PaginationLink[]; total: number; last_page: number }
    serviceTypeDistribution: { label: string; count: number }[]
    costByMonth: { label: string; count: number; cost: number }[]
    stats: { total: number; completed: number; pending: number; overdue: number; total_cost: number; avg_cost: number; vehicles_serviced: number }
    contextVehicle?: { id: number; label: string } | null
    isSuperadmin: boolean
}>()

const filters = reactive({ ...props.filters })
const pickerOptions = { singleMode: true, format: 'M/D/YYYY', autoApply: true }
const maxCost = computed(() => Math.max(...props.costByMonth.map(item => item.cost), 1))
const maxType = computed(() => Math.max(...props.serviceTypeDistribution.map(item => item.count), 1))

function applyFilters() {
    router.get(route('admin.maintenance.reports'), {
        carrier_id: filters.carrier_id || undefined,
        vehicle_id: filters.vehicle_id || undefined,
        period: filters.period || undefined,
        status: filters.status || undefined,
        start_date: filters.start_date || undefined,
        end_date: filters.end_date || undefined,
    }, { preserveState: true, replace: true })
}
</script>

<template>
    <Head title="Maintenance Reports" />

    <div class="grid grid-cols-12 gap-y-8 gap-x-6">
        <div class="col-span-12">
            <div class="box box--stacked p-6">
                <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-bold text-slate-800">Maintenance Reports</h1>
                        <p class="text-slate-500">Analyze cost, completion and service distribution trends.</p>
                        <p v-if="contextVehicle" class="text-xs text-primary mt-2">{{ contextVehicle.label }}</p>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <Link :href="route('admin.maintenance.index', { vehicle_id: contextVehicle?.id || undefined })">
                            <Button variant="outline-secondary" class="flex items-center gap-2"><Lucide icon="List" class="w-4 h-4" /> List</Button>
                        </Link>
                        <Link :href="route('admin.maintenance.calendar', { vehicle_id: contextVehicle?.id || undefined })">
                            <Button variant="outline-secondary" class="flex items-center gap-2"><Lucide icon="Calendar" class="w-4 h-4" /> Calendar</Button>
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12">
            <div class="box box--stacked p-5">
                <div class="grid grid-cols-1 lg:grid-cols-6 gap-4">
                    <TomSelect v-if="isSuperadmin" v-model="filters.carrier_id">
                        <option value="">All carriers</option>
                        <option v-for="carrier in carriers" :key="carrier.id" :value="String(carrier.id)">{{ carrier.name }}</option>
                    </TomSelect>
                    <TomSelect v-model="filters.vehicle_id">
                        <option value="">All vehicles</option>
                        <option v-for="vehicle in vehicles" :key="vehicle.id" :value="String(vehicle.id)">{{ vehicle.label }}</option>
                    </TomSelect>
                    <TomSelect v-model="filters.period">
                        <option v-for="(label, key) in periodOptions" :key="key" :value="key">{{ label }}</option>
                    </TomSelect>
                    <TomSelect v-model="filters.status">
                        <option value="">All statuses</option>
                        <option v-for="(label, key) in statusOptions" :key="key" :value="key">{{ label }}</option>
                    </TomSelect>
                    <Litepicker v-model="filters.start_date" :options="pickerOptions" />
                    <Litepicker v-model="filters.end_date" :options="pickerOptions" />
                </div>

                <div class="flex justify-end mt-4">
                    <button type="button" @click="applyFilters" class="inline-flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition">
                        <Lucide icon="Filter" class="w-4 h-4" />
                        Apply Filters
                    </button>
                </div>
            </div>
        </div>

        <div class="col-span-12">
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-6 gap-4">
                <div class="box box--stacked p-5"><p class="text-sm text-slate-500">Total</p><p class="mt-1 text-2xl font-semibold text-slate-800">{{ stats.total }}</p></div>
                <div class="box box--stacked p-5"><p class="text-sm text-slate-500">Completed</p><p class="mt-1 text-2xl font-semibold text-slate-800">{{ stats.completed }}</p></div>
                <div class="box box--stacked p-5"><p class="text-sm text-slate-500">Pending</p><p class="mt-1 text-2xl font-semibold text-slate-800">{{ stats.pending }}</p></div>
                <div class="box box--stacked p-5"><p class="text-sm text-slate-500">Overdue</p><p class="mt-1 text-2xl font-semibold text-slate-800">{{ stats.overdue }}</p></div>
                <div class="box box--stacked p-5"><p class="text-sm text-slate-500">Vehicles Serviced</p><p class="mt-1 text-2xl font-semibold text-slate-800">{{ stats.vehicles_serviced }}</p></div>
                <div class="box box--stacked p-5"><p class="text-sm text-slate-500">Total Cost</p><p class="mt-1 text-2xl font-semibold text-slate-800">${{ stats.total_cost.toFixed(2) }}</p><p class="text-xs text-slate-500 mt-1">Avg ${{ stats.avg_cost.toFixed(2) }}</p></div>
            </div>
        </div>

        <div class="col-span-12 xl:col-span-6">
            <div class="box box--stacked p-6">
                <h2 class="text-sm font-semibold text-slate-700 mb-5 flex items-center gap-2">
                    <Lucide icon="BarChart3" class="w-4 h-4 text-primary" />
                    Cost by Month
                </h2>

                <div class="grid grid-cols-6 gap-3 items-end min-h-[220px]">
                    <div v-for="item in costByMonth" :key="item.label" class="flex flex-col items-center gap-2">
                        <div class="w-full rounded-t-lg bg-primary/15 flex items-end justify-center text-[11px] text-primary font-medium" :style="{ height: `${Math.max((item.cost / maxCost) * 160, item.cost ? 20 : 6)}px` }">
                            <span class="pb-2">{{ item.count }}</span>
                        </div>
                        <div class="text-center">
                            <p class="text-xs text-slate-500">{{ item.label }}</p>
                            <p class="text-[11px] text-slate-400">${{ item.cost.toFixed(0) }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12 xl:col-span-6">
            <div class="box box--stacked p-6">
                <h2 class="text-sm font-semibold text-slate-700 mb-5 flex items-center gap-2">
                    <Lucide icon="PieChart" class="w-4 h-4 text-primary" />
                    Service Type Distribution
                </h2>

                <div class="space-y-3">
                    <div v-for="item in serviceTypeDistribution" :key="item.label">
                        <div class="flex items-center justify-between text-sm mb-1">
                            <span class="text-slate-700">{{ item.label }}</span>
                            <span class="text-slate-500">{{ item.count }}</span>
                        </div>
                        <div class="h-2 rounded-full bg-slate-100 overflow-hidden">
                            <div class="h-full rounded-full bg-primary" :style="{ width: `${(item.count / maxType) * 100}%` }"></div>
                        </div>
                    </div>
                    <div v-if="!serviceTypeDistribution.length" class="rounded-lg bg-slate-50 px-4 py-3 text-sm text-slate-500">No distribution data for the selected filters.</div>
                </div>
            </div>
        </div>

        <div class="col-span-12">
            <div class="box box--stacked overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-200">
                    <h2 class="text-base font-semibold text-slate-800">Maintenance Records</h2>
                    <p class="text-sm text-slate-500">{{ records.total }} matching records</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-slate-50/80">
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Service</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Vehicle</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Dates</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Status</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Cost</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="record in records.data" :key="record.id" class="border-b border-slate-100 hover:bg-slate-50/50 transition">
                                <td class="px-5 py-4">
                                    <p class="font-medium text-slate-800">{{ record.service_tasks }}</p>
                                    <p class="text-xs text-slate-500">{{ record.vendor_mechanic || 'No vendor' }}</p>
                                </td>
                                <td class="px-5 py-4 text-sm text-slate-700">{{ record.vehicle.label }}</td>
                                <td class="px-5 py-4 text-sm text-slate-600">
                                    <p>{{ record.service_date || 'N/A' }}</p>
                                    <p class="text-xs text-slate-500">{{ record.next_service_date || 'N/A' }}</p>
                                </td>
                                <td class="px-5 py-4 text-sm text-slate-600">{{ record.status_label }}</td>
                                <td class="px-5 py-4 text-sm text-slate-600">{{ record.cost || 'N/A' }}</td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center justify-center gap-2">
                                        <Link :href="route('admin.maintenance.show', record.id)" class="p-1.5 text-slate-400 hover:text-primary transition"><Lucide icon="Eye" class="w-4 h-4" /></Link>
                                        <Link :href="route('admin.maintenance.edit', record.id)" class="p-1.5 text-slate-400 hover:text-primary transition"><Lucide icon="PenLine" class="w-4 h-4" /></Link>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="records.last_page > 1" class="p-4 border-t border-slate-200/60 flex items-center justify-between">
                    <span class="text-sm text-slate-500">{{ records.total }} total records</span>
                    <div class="flex gap-1">
                        <template v-for="link in records.links" :key="link.label">
                            <Link v-if="link.url" :href="link.url" class="px-3 py-1 text-sm rounded" :class="link.active ? 'bg-primary text-white' : 'text-slate-600 hover:bg-slate-100'" v-html="link.label" />
                            <span v-else class="px-3 py-1 text-sm text-slate-300" v-html="link.label" />
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
