<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { reactive } from 'vue'
import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import TomSelect from '@/components/Base/TomSelect/TomSelect.vue'
import RazeLayout from '@/layouts/RazeLayout.vue'

declare function route(name: string, params?: any): string

defineOptions({ layout: RazeLayout })

const props = defineProps<{
    filters: { carrier_id: string; vehicle_id: string; status: string; month: string }
    carriers: { id: number; name: string }[]
    vehicles: { id: number; carrier_id: number | null; carrier_name: string | null; label: string }[]
    statusOptions: Record<string, string>
    calendar: {
        month_label: string
        previous_month: string
        next_month: string
        days: { date: string; day: number; in_month: boolean; is_today: boolean; items: { id: number; title: string; vehicle_label: string; status: string; show_url: string }[] }[]
    }
    upcomingItems: { id: number; title: string; vehicle_label: string; service_date: string | null; next_service_date: string | null; status_label: string; show_url: string }[]
    contextVehicle?: { id: number; label: string } | null
    isSuperadmin: boolean
}>()

const filters = reactive({ ...props.filters })

function goToMonth(month: string) {
    router.get(route('admin.maintenance.calendar'), {
        carrier_id: filters.carrier_id || undefined,
        vehicle_id: filters.vehicle_id || undefined,
        status: filters.status || undefined,
        month,
    }, { preserveState: true, replace: true })
}

function applyFilters() {
    goToMonth(filters.month)
}
</script>

<template>
    <Head title="Maintenance Calendar" />

    <div class="grid grid-cols-12 gap-y-8 gap-x-6">
        <div class="col-span-12">
            <div class="box box--stacked p-6">
                <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-bold text-slate-800">Maintenance Calendar</h1>
                        <p class="text-slate-500">Visualize upcoming due dates across the fleet.</p>
                        <p v-if="contextVehicle" class="text-xs text-primary mt-2">{{ contextVehicle.label }}</p>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <Link :href="route('admin.maintenance.index', { vehicle_id: contextVehicle?.id || undefined })">
                            <Button variant="outline-secondary" class="flex items-center gap-2"><Lucide icon="List" class="w-4 h-4" /> List</Button>
                        </Link>
                        <Link :href="route('admin.maintenance.reports', { vehicle_id: contextVehicle?.id || undefined })">
                            <Button variant="outline-secondary" class="flex items-center gap-2"><Lucide icon="BarChart3" class="w-4 h-4" /> Reports</Button>
                        </Link>
                        <Link :href="route('admin.maintenance.create', { vehicle_id: contextVehicle?.id || undefined })">
                            <Button variant="primary" class="flex items-center gap-2"><Lucide icon="Plus" class="w-4 h-4" /> New Maintenance</Button>
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12 xl:col-span-9">
            <div class="box box--stacked p-5 mb-6">
                <div class="grid grid-cols-1 lg:grid-cols-4 gap-4">
                    <TomSelect v-if="isSuperadmin" v-model="filters.carrier_id">
                        <option value="">All carriers</option>
                        <option v-for="carrier in carriers" :key="carrier.id" :value="String(carrier.id)">{{ carrier.name }}</option>
                    </TomSelect>
                    <TomSelect v-model="filters.vehicle_id">
                        <option value="">All vehicles</option>
                        <option v-for="vehicle in vehicles" :key="vehicle.id" :value="String(vehicle.id)">{{ vehicle.label }}</option>
                    </TomSelect>
                    <TomSelect v-model="filters.status">
                        <option value="">All statuses</option>
                        <option v-for="(label, key) in statusOptions" :key="key" :value="key">{{ label }}</option>
                    </TomSelect>
                    <button type="button" @click="applyFilters" class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition">
                        <Lucide icon="Filter" class="w-4 h-4" />
                        Apply
                    </button>
                </div>
            </div>

            <div class="box box--stacked overflow-hidden">
                <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200">
                    <button type="button" class="inline-flex items-center gap-2 text-sm text-slate-600 hover:text-primary" @click="goToMonth(calendar.previous_month)">
                        <Lucide icon="ChevronLeft" class="w-4 h-4" />
                        Previous
                    </button>
                    <h2 class="text-lg font-semibold text-slate-800">{{ calendar.month_label }}</h2>
                    <button type="button" class="inline-flex items-center gap-2 text-sm text-slate-600 hover:text-primary" @click="goToMonth(calendar.next_month)">
                        Next
                        <Lucide icon="ChevronRight" class="w-4 h-4" />
                    </button>
                </div>

                <div class="grid grid-cols-7 border-b border-slate-200 bg-slate-50 text-xs font-medium uppercase text-slate-500">
                    <div v-for="day in ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']" :key="day" class="px-3 py-2 text-center">{{ day }}</div>
                </div>

                <div class="grid grid-cols-7">
                    <div v-for="day in calendar.days" :key="day.date" class="min-h-[140px] border-b border-r border-slate-100 p-2" :class="{ 'bg-slate-50/60': !day.in_month }">
                        <div class="flex items-center justify-between mb-2">
                            <span class="inline-flex h-7 w-7 items-center justify-center rounded-full text-sm" :class="day.is_today ? 'bg-primary text-white' : 'text-slate-700'">{{ day.day }}</span>
                            <span v-if="day.items.length" class="text-[10px] text-slate-400">{{ day.items.length }}</span>
                        </div>

                        <div class="space-y-1.5">
                            <Link v-for="item in day.items.slice(0, 3)" :key="item.id" :href="item.show_url" class="block rounded-md px-2 py-1 text-[11px]" :class="item.status === 'completed' ? 'bg-primary/10 text-primary' : item.status === 'overdue' ? 'bg-danger/10 text-danger' : 'bg-slate-100 text-slate-600'">
                                <p class="font-medium truncate">{{ item.title }}</p>
                                <p class="truncate opacity-75">{{ item.vehicle_label }}</p>
                            </Link>
                            <div v-if="day.items.length > 3" class="text-[11px] text-slate-400 px-2">+{{ day.items.length - 3 }} more</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12 xl:col-span-3">
            <div class="box box--stacked p-5">
                <div class="flex items-center gap-2 mb-4">
                    <Lucide icon="Clock3" class="w-4 h-4 text-primary" />
                    <h2 class="text-sm font-semibold text-slate-700">Upcoming</h2>
                </div>

                <div class="space-y-3">
                    <Link v-for="item in upcomingItems" :key="item.id" :href="item.show_url" class="block rounded-lg border border-slate-200 px-4 py-3 hover:bg-slate-50">
                        <p class="text-sm font-medium text-slate-800">{{ item.title }}</p>
                        <p class="text-xs text-slate-500 mt-1">{{ item.vehicle_label }}</p>
                        <p class="text-xs text-primary mt-1">Due {{ item.next_service_date || 'N/A' }}</p>
                    </Link>
                    <div v-if="!upcomingItems.length" class="rounded-lg bg-slate-50 px-4 py-3 text-sm text-slate-500">No upcoming maintenance items.</div>
                </div>
            </div>
        </div>
    </div>
</template>
