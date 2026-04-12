<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { reactive } from 'vue'
import Lucide from '@/components/Base/Lucide'
import Litepicker from '@/components/Base/Litepicker/Litepicker.vue'
import TomSelect from '@/components/Base/TomSelect/TomSelect.vue'
import { FormInput } from '@/components/Base/Form'
import RazeLayout from '@/layouts/RazeLayout.vue'

declare function route(name: string, params?: any): string

defineOptions({ layout: RazeLayout })

const lpOptions = { singleMode: true, format: 'M/D/YYYY', autoApply: true }

interface PaginationLink {
    url: string | null
    label: string
    active: boolean
}

const props = defineProps<{
    driver: { id: number; name: string; carrier_name: string | null }
    testings: {
        data: {
            id: number
            test_date: string | null
            test_type: string
            test_type_label: string
            status: string | null
            test_result: string | null
            administered_by: string
            location: string | null
            next_test_due: string | null
        }[]
        links: PaginationLink[]
        total: number
        last_page: number
    }
    filters: {
        search: string
        test_type: string
        status: string
        test_result: string
        date_from: string
        date_to: string
    }
    testTypes: Record<string, string>
    statuses: Record<string, string>
    testResults: Record<string, string>
    routeNames?: {
        index: string
        show: string
        create: string
        edit: string
        driverHistory: string
        driverShow: string
    }
}>()

const filters = reactive({
    search: props.filters.search ?? '',
    test_type: props.filters.test_type ?? '',
    status: props.filters.status ?? '',
    test_result: props.filters.test_result ?? '',
    date_from: props.filters.date_from ?? '',
    date_to: props.filters.date_to ?? '',
})

function applyFilters() {
    router.get(route(props.routeNames?.driverHistory ?? 'carrier.drivers.testings.driver-history', props.driver.id), {
        search: filters.search || undefined,
        test_type: filters.test_type || undefined,
        status: filters.status || undefined,
        test_result: filters.test_result || undefined,
        date_from: filters.date_from || undefined,
        date_to: filters.date_to || undefined,
    }, { preserveState: true, replace: true })
}

function resetFilters() {
    filters.search = ''
    filters.test_type = ''
    filters.status = ''
    filters.test_result = ''
    filters.date_from = ''
    filters.date_to = ''
    applyFilters()
}

function resultBadge(result: string | null) {
    if (!result) return 'inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-500'
    if (result === 'Positive') return 'inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-700'
    if (result === 'Negative') return 'inline-flex items-center rounded-full bg-primary/10 px-2.5 py-0.5 text-xs font-medium text-primary'
    if (result === 'Refusal') return 'inline-flex items-center rounded-full bg-primary/15 px-2.5 py-0.5 text-xs font-medium text-primary'
    return 'inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-500'
}

function statusBadge(status: string | null) {
    if (!status) return 'inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-500'
    const map: Record<string, string> = {
        'Schedule': 'inline-flex items-center rounded-full bg-primary/10 px-2.5 py-0.5 text-xs font-medium text-primary',
        'In Progress': 'inline-flex items-center rounded-full bg-primary/15 px-2.5 py-0.5 text-xs font-medium text-primary',
        'Pending Review': 'inline-flex items-center rounded-full bg-primary/20 px-2.5 py-0.5 text-xs font-medium text-primary',
        'Completed': 'inline-flex items-center rounded-full bg-primary/10 px-2.5 py-0.5 text-xs font-medium text-primary',
        'Cancelled': 'inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-600',
    }
    return map[status] ?? 'inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-500'
}
</script>

<template>
    <Head title="Driver Testing History" />

    <div class="grid grid-cols-12 gap-x-6 gap-y-8">
        <div class="col-span-12">
            <div class="box box--stacked p-6">
                <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                            <Lucide icon="History" class="w-8 h-8 text-primary" />
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-slate-800">Testing History</h1>
                            <p class="text-slate-500">All drug and alcohol tests for {{ driver.name }}</p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <Link :href="route(props.routeNames?.driverShow ?? 'admin.drivers.show', driver.id)" class="inline-flex items-center gap-2 px-4 py-2 border border-slate-300 rounded-lg text-slate-600 hover:bg-slate-50 transition">
                            <Lucide icon="ArrowLeft" class="w-4 h-4" />
                            Back to Driver
                        </Link>
                        <Link :href="route(props.routeNames?.create ?? 'admin.driver-testings.create', { driver_id: driver.id })" class="inline-flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition">
                            <Lucide icon="Plus" class="w-4 h-4" />
                            Add Test
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12">
            <div class="box box--stacked p-5">
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-6 gap-4">
                    <div class="xl:col-span-2 relative">
                        <Lucide icon="Search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                        <FormInput v-model="filters.search" type="text" class="pl-10" placeholder="Search administrator, notes..." />
                    </div>
                    <TomSelect v-model="filters.test_type">
                        <option value="">All Test Types</option>
                        <option v-for="(label, key) in testTypes" :key="key" :value="key">{{ label }}</option>
                    </TomSelect>
                    <TomSelect v-model="filters.status">
                        <option value="">All Statuses</option>
                        <option v-for="(label, key) in statuses" :key="key" :value="key">{{ label }}</option>
                    </TomSelect>
                    <TomSelect v-model="filters.test_result">
                        <option value="">All Results</option>
                        <option v-for="(label, key) in testResults" :key="key" :value="key">{{ label }}</option>
                    </TomSelect>
                    <Litepicker v-model="filters.date_from" :options="lpOptions" />
                    <Litepicker v-model="filters.date_to" :options="lpOptions" />
                </div>

                <div class="flex flex-wrap items-center gap-3 mt-4">
                    <button type="button" @click="applyFilters" class="inline-flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition">
                        <Lucide icon="Filter" class="w-4 h-4" />
                        Apply Filters
                    </button>
                    <button type="button" @click="resetFilters" class="inline-flex items-center gap-2 px-4 py-2 border border-slate-300 rounded-lg text-slate-600 hover:bg-slate-50 transition">
                        <Lucide icon="RotateCcw" class="w-4 h-4" />
                        Clear
                    </button>
                </div>
            </div>
        </div>

        <div class="col-span-12">
            <div class="box box--stacked p-0 overflow-hidden">
                <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200/60">
                    <div>
                        <h2 class="text-base font-semibold text-slate-800">Test Records</h2>
                        <p class="text-sm text-slate-500">{{ testings.total }} records found</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-slate-50/80">
                                <th class="px-4 py-3 text-xs font-medium text-slate-500 uppercase whitespace-nowrap">Date</th>
                                <th class="px-4 py-3 text-xs font-medium text-slate-500 uppercase whitespace-nowrap">Type</th>
                                <th class="px-4 py-3 text-xs font-medium text-slate-500 uppercase whitespace-nowrap">Result</th>
                                <th class="px-4 py-3 text-xs font-medium text-slate-500 uppercase whitespace-nowrap">Status</th>
                                <th class="px-4 py-3 text-xs font-medium text-slate-500 uppercase whitespace-nowrap">Administered By</th>
                                <th class="px-4 py-3 text-xs font-medium text-slate-500 uppercase whitespace-nowrap">Location</th>
                                <th class="px-4 py-3 text-xs font-medium text-slate-500 uppercase whitespace-nowrap">Next Due</th>
                                <th class="px-4 py-3 text-xs font-medium text-slate-500 uppercase text-center whitespace-nowrap">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="testing in testings.data" :key="testing.id" class="border-b border-slate-100 hover:bg-slate-50/50 transition">
                                <td class="px-4 py-3 text-sm text-slate-600 whitespace-nowrap">{{ testing.test_date ?? 'N/A' }}</td>
                                <td class="px-4 py-3 text-sm font-medium text-slate-700">{{ testing.test_type_label }}</td>
                                <td class="px-4 py-3">
                                    <span :class="resultBadge(testing.test_result)">{{ testing.test_result ?? 'Pending' }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <span :class="statusBadge(testing.status)">{{ testing.status ?? 'N/A' }}</span>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ testing.administered_by }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ testing.location ?? 'N/A' }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ testing.next_test_due ?? 'N/A' }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center gap-2">
                                        <Link :href="route(props.routeNames?.show ?? 'admin.driver-testings.show', testing.id)" class="p-1.5 text-slate-400 hover:text-primary transition" title="View">
                                            <Lucide icon="Eye" class="w-4 h-4" />
                                        </Link>
                                        <Link :href="route(props.routeNames?.edit ?? 'admin.drivers.testings.edit', testing.id)" class="p-1.5 text-slate-400 hover:text-primary transition" title="Edit">
                                            <Lucide icon="PenLine" class="w-4 h-4" />
                                        </Link>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="!testings.data.length">
                                <td colspan="8" class="px-5 py-12 text-center text-slate-400">
                                    <Lucide icon="BadgeInfo" class="w-12 h-12 mx-auto mb-3 text-slate-300" />
                                    <p>No testing records found for this driver</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="testings.last_page > 1" class="p-4 border-t border-slate-200/60 flex items-center justify-between">
                    <span class="text-sm text-slate-500">{{ testings.total }} total records</span>
                    <div class="flex gap-1">
                        <template v-for="link in testings.links" :key="link.label">
                            <Link v-if="link.url" :href="link.url" class="px-3 py-1 text-sm rounded" :class="link.active ? 'bg-primary text-white' : 'text-slate-600 hover:bg-slate-100'" v-html="link.label" />
                            <span v-else class="px-3 py-1 text-sm text-slate-300" v-html="link.label" />
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
