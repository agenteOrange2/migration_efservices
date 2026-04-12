<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { reactive, ref } from 'vue'
import Lucide from '@/components/Base/Lucide'
import { FormInput, FormSelect } from '@/components/Base/Form'
import { Dialog } from '@/components/Base/Headless'
import RazeLayout from '@/layouts/RazeLayout.vue'

declare function route(name: string, params?: any): string

defineOptions({ layout: RazeLayout })

// ─── Types ────────────────────────────────────────────────────────────────────
interface PaginationLink {
    url: string | null
    label: string
    active: boolean
}

interface TestingRow {
    id: number
    driver_id: number | null
    driver_name: string
    driver_email: string | null
    carrier_name: string
    carrier_id: number | null
    test_type: string
    test_type_label: string
    test_date: string | null
    test_date_raw: string | null
    test_result: string | null
    status: string | null
    administered_by: string
    location: string | null
    next_test_due: string | null
    is_random_test: boolean
    is_post_accident_test: boolean
    is_reasonable_suspicion_test: boolean
    is_pre_employment_test: boolean
    is_follow_up_test: boolean
    is_return_to_duty_test: boolean
    is_other_reason_test: boolean
}

// ─── Props ────────────────────────────────────────────────────────────────────
const props = defineProps<{
    testings: {
        data: TestingRow[]
        links: PaginationLink[]
        total: number
        last_page: number
    }
    carriers: { id: number; name: string }[]
    filters: {
        search: string
        driver_filter?: string
        carrier_filter: string
        test_type: string
        status: string
        test_result: string
        date_from: string
        date_to: string
    }
    testTypes: Record<string, string>
    statuses: Record<string, string>
    testResults: Record<string, string>
    stats: { total: number; positive: number; negative: number; scheduled: number }
    drivers?: { id: number; full_name?: string; name?: string }[]
    carrier?: { id: number; name: string } | null
    isCarrierContext?: boolean
    routeNames?: {
        index: string
        show: string
        create: string
        edit: string
        destroy: string
        driverHistory?: string
        driverShow: string
    }
}>()

// ─── Filters ──────────────────────────────────────────────────────────────────
const filters = reactive({
    search:         props.filters.search ?? '',
    driver_filter:  props.filters.driver_filter ?? '',
    carrier_filter: props.filters.carrier_filter ?? '',
    test_type:      props.filters.test_type ?? '',
    status:         props.filters.status ?? '',
    test_result:    props.filters.test_result ?? '',
    date_from:      props.filters.date_from ?? '',
    date_to:        props.filters.date_to ?? '',
})

function applyFilters() {
    router.get(route(props.routeNames?.index ?? 'admin.driver-testings.index'), {
        search:         filters.search         || undefined,
        driver_filter:  filters.driver_filter  || undefined,
        carrier_filter: filters.carrier_filter || undefined,
        test_type:      filters.test_type      || undefined,
        status:         filters.status         || undefined,
        test_result:    filters.test_result    || undefined,
        date_from:      filters.date_from      || undefined,
        date_to:        filters.date_to        || undefined,
    }, { preserveState: true, replace: true })
}

function resetFilters() {
    filters.search         = ''
    filters.driver_filter  = ''
    filters.carrier_filter = ''
    filters.test_type      = ''
    filters.status         = ''
    filters.test_result    = ''
    filters.date_from      = ''
    filters.date_to        = ''
    applyFilters()
}

// ─── Delete modal ─────────────────────────────────────────────────────────────
const deleteModalOpen = ref(false)
const selectedTesting = ref<TestingRow | null>(null)

function openDeleteModal(testing: TestingRow) {
    selectedTesting.value = testing
    deleteModalOpen.value = true
}

function confirmDelete() {
    if (!selectedTesting.value) return
    router.delete(route(props.routeNames?.destroy ?? 'admin.driver-testings.destroy', selectedTesting.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            deleteModalOpen.value = false
            selectedTesting.value = null
        },
    })
}

// ─── Helpers ──────────────────────────────────────────────────────────────────
function resultBadge(result: string | null) {
    if (!result) return 'inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-500'
    if (result === 'Positive')  return 'inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-700'
    if (result === 'Negative')  return 'inline-flex items-center rounded-full bg-primary/10 px-2.5 py-0.5 text-xs font-medium text-primary'
    if (result === 'Refusal')   return 'inline-flex items-center rounded-full bg-primary/15 px-2.5 py-0.5 text-xs font-medium text-primary'
    return 'inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-500'
}

function statusBadge(status: string | null) {
    if (!status) return 'inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-500'
    const map: Record<string, string> = {
        'Schedule':       'inline-flex items-center rounded-full bg-primary/10 px-2.5 py-0.5 text-xs font-medium text-primary',
        'In Progress':    'inline-flex items-center rounded-full bg-primary/15 px-2.5 py-0.5 text-xs font-medium text-primary',
        'Pending Review': 'inline-flex items-center rounded-full bg-primary/20 px-2.5 py-0.5 text-xs font-medium text-primary',
        'Completed':      'inline-flex items-center rounded-full bg-primary/10 px-2.5 py-0.5 text-xs font-medium text-primary',
        'Cancelled':      'inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-600',
    }
    return map[status] ?? 'inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-500'
}

const reasonLabels: { key: keyof TestingRow; label: string; color: string }[] = [
    { key: 'is_random_test',               label: 'Random',       color: 'bg-primary/10 text-primary' },
    { key: 'is_post_accident_test',        label: 'Post-Acc.',    color: 'bg-primary/15 text-primary' },
    { key: 'is_reasonable_suspicion_test', label: 'R. Suspicion', color: 'bg-primary/20 text-primary' },
    { key: 'is_pre_employment_test',       label: 'Pre-Employ.',  color: 'bg-primary/10 text-primary' },
    { key: 'is_follow_up_test',            label: 'Follow-Up',    color: 'bg-primary/15 text-primary' },
    { key: 'is_return_to_duty_test',       label: 'RTD',          color: 'bg-primary/20 text-primary' },
    { key: 'is_other_reason_test',         label: 'Other',        color: 'bg-slate-100 text-slate-600' },
]
</script>

<template>
    <Head title="Drug & Alcohol Testing" />

    <div class="grid grid-cols-12 gap-x-6 gap-y-8">

        <!-- ══ HEADER ══ -->
        <div class="col-span-12">
            <div class="box box--stacked p-6">
                <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                            <Lucide icon="BadgeInfo" class="w-8 h-8 text-primary" />
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-slate-800">Drug & Alcohol Testing</h1>
                            <p class="text-slate-500 text-sm">All test records across all drivers and carriers</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ══ STATS ══ -->
        <div class="col-span-12">
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="box box--stacked p-5 flex items-center gap-4">
                    <div class="p-3 bg-slate-100 rounded-xl">
                        <Lucide icon="ClipboardList" class="w-6 h-6 text-slate-500" />
                    </div>
                    <div>
                        <p class="text-xs text-slate-500">Total Records</p>
                        <p class="text-2xl font-bold text-slate-800">{{ stats.total }}</p>
                    </div>
                </div>
                <div class="box box--stacked p-5 flex items-center gap-4">
                    <div class="p-3 bg-primary/10 rounded-xl border border-primary/10">
                        <Lucide icon="CheckCircle" class="w-6 h-6 text-primary" />
                    </div>
                    <div>
                        <p class="text-xs text-slate-500">Negative</p>
                        <p class="text-2xl font-bold text-primary">{{ stats.negative }}</p>
                    </div>
                </div>
                <div class="box box--stacked p-5 flex items-center gap-4">
                    <div class="p-3 bg-red-100 rounded-xl">
                        <Lucide icon="AlertCircle" class="w-6 h-6 text-red-600" />
                    </div>
                    <div>
                        <p class="text-xs text-slate-500">Positive</p>
                        <p class="text-2xl font-bold text-red-700">{{ stats.positive }}</p>
                    </div>
                </div>
                <div class="box box--stacked p-5 flex items-center gap-4">
                    <div class="p-3 bg-primary/10 rounded-xl border border-primary/10">
                        <Lucide icon="Clock" class="w-6 h-6 text-primary" />
                    </div>
                    <div>
                        <p class="text-xs text-slate-500">Pending / Scheduled</p>
                        <p class="text-2xl font-bold text-primary">{{ stats.scheduled }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- ══ FILTERS ══ -->
        <div class="col-span-12">
            <div class="box box--stacked p-5">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Search -->
                    <div class="relative">
                        <Lucide icon="Search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                        <FormInput v-model="filters.search" type="text" class="pl-10" placeholder="Driver, administrator..." @keyup.enter="applyFilters" />
                    </div>

                    <!-- Driver -->
                    <FormSelect v-if="props.drivers?.length" v-model="filters.driver_filter">
                        <option value="">All Drivers</option>
                        <option v-for="d in props.drivers" :key="d.id" :value="String(d.id)">{{ d.full_name ?? d.name ?? `Driver #${d.id}` }}</option>
                    </FormSelect>

                    <!-- Carrier -->
                    <FormSelect v-if="!props.isCarrierContext" v-model="filters.carrier_filter">
                        <option value="">All Carriers</option>
                        <option v-for="c in carriers" :key="c.id" :value="String(c.id)">{{ c.name }}</option>
                    </FormSelect>

                    <!-- Test Type -->
                    <FormSelect v-model="filters.test_type">
                        <option value="">All Test Types</option>
                        <option v-for="(label, key) in testTypes" :key="key" :value="key">{{ label }}</option>
                    </FormSelect>

                    <!-- Status -->
                    <FormSelect v-model="filters.status">
                        <option value="">All Statuses</option>
                        <option v-for="(label, key) in statuses" :key="key" :value="key">{{ label }}</option>
                    </FormSelect>

                    <!-- Result -->
                    <FormSelect v-model="filters.test_result">
                        <option value="">All Results</option>
                        <option v-for="(label, key) in testResults" :key="key" :value="key">{{ label }}</option>
                    </FormSelect>

                    <!-- Date from -->
                    <FormInput v-model="filters.date_from" type="date" />

                    <!-- Date to -->
                    <FormInput v-model="filters.date_to" type="date" />

                    <!-- Buttons -->
                    <div class="flex gap-2">
                        <button type="button" @click="applyFilters"
                            class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition text-sm">
                            <Lucide icon="Filter" class="w-4 h-4" /> Apply
                        </button>
                        <button type="button" @click="resetFilters"
                            class="px-3 py-2 border border-slate-300 rounded-lg text-slate-600 hover:bg-slate-50 transition">
                            <Lucide icon="RotateCcw" class="w-4 h-4" />
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ══ TABLE ══ -->
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
                                <th class="px-4 py-3 text-xs font-medium text-slate-500 uppercase whitespace-nowrap">Driver</th>
                                <th class="px-4 py-3 text-xs font-medium text-slate-500 uppercase whitespace-nowrap">Carrier</th>
                                <th class="px-4 py-3 text-xs font-medium text-slate-500 uppercase whitespace-nowrap">Test Type</th>
                                <th class="px-4 py-3 text-xs font-medium text-slate-500 uppercase whitespace-nowrap">Reason</th>
                                <th class="px-4 py-3 text-xs font-medium text-slate-500 uppercase whitespace-nowrap">Result</th>
                                <th class="px-4 py-3 text-xs font-medium text-slate-500 uppercase whitespace-nowrap">Status</th>
                                <th class="px-4 py-3 text-xs font-medium text-slate-500 uppercase whitespace-nowrap">Administered By</th>
                                <th class="px-4 py-3 text-xs font-medium text-slate-500 uppercase whitespace-nowrap">Next Due</th>
                                <th class="px-4 py-3 text-xs font-medium text-slate-500 uppercase text-center whitespace-nowrap">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="t in testings.data" :key="t.id"
                                class="border-b border-slate-100 hover:bg-slate-50/50 transition">
                                <td class="px-4 py-3 text-sm text-slate-600 whitespace-nowrap">{{ t.test_date ?? '—' }}</td>

                                <td class="px-4 py-3">
                                    <div class="font-medium text-slate-800 whitespace-nowrap">{{ t.driver_name }}</div>
                                    <div v-if="t.driver_email" class="text-xs text-slate-400 truncate max-w-[180px]">{{ t.driver_email }}</div>
                                </td>

                                <td class="px-4 py-3 text-sm text-slate-600 whitespace-nowrap">{{ t.carrier_name }}</td>

                                <td class="px-4 py-3">
                                    <span class="text-xs text-slate-700">{{ t.test_type_label }}</span>
                                </td>

                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap gap-1 max-w-[160px]">
                                        <template v-for="r in reasonLabels" :key="r.key">
                                            <span v-if="t[r.key]"
                                                :class="`inline-flex items-center rounded-full px-1.5 py-0.5 text-[10px] font-medium ${r.color}`">
                                                {{ r.label }}
                                            </span>
                                        </template>
                                    </div>
                                </td>

                                <td class="px-4 py-3">
                                    <span :class="resultBadge(t.test_result)">
                                        {{ t.test_result ?? 'Pending' }}
                                    </span>
                                </td>

                                <td class="px-4 py-3">
                                    <span :class="statusBadge(t.status)">
                                        {{ t.status ?? '—' }}
                                    </span>
                                </td>

                                <td class="px-4 py-3 text-sm text-slate-600 whitespace-nowrap">{{ t.administered_by }}</td>

                                <td class="px-4 py-3 text-sm whitespace-nowrap"
                                    :class="t.next_test_due ? 'text-slate-600' : 'text-slate-400'">
                                    {{ t.next_test_due ?? '—' }}
                                </td>

                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center gap-1">
                                        <!-- View Test -->
                                        <Link :href="route(props.routeNames?.show ?? 'admin.driver-testings.show', t.id)"
                                            class="p-1.5 text-slate-400 hover:text-primary transition" title="View Test">
                                            <Lucide icon="Eye" class="w-4 h-4" />
                                        </Link>
                                        <!-- View Driver -->
                                        <Link v-if="t.driver_id"
                                            :href="route(props.routeNames?.driverShow ?? 'admin.drivers.show', t.driver_id)"
                                            class="p-1.5 text-slate-400 hover:text-primary transition" title="View Driver">
                                            <Lucide icon="User" class="w-4 h-4" />
                                        </Link>
                                        <Link v-if="t.driver_id && props.routeNames?.driverHistory"
                                            :href="route(props.routeNames.driverHistory, t.driver_id)"
                                            class="p-1.5 text-slate-400 hover:text-primary transition" title="Driver history">
                                            <Lucide icon="History" class="w-4 h-4" />
                                        </Link>
                                        <!-- Edit -->
                                        <Link
                                            :href="route(props.routeNames?.edit ?? 'admin.drivers.testings.edit', props.isCarrierContext ? t.id : { driver: t.driver_id, testing: t.id })"
                                            class="p-1.5 text-slate-400 hover:text-primary transition" title="Edit">
                                            <Lucide icon="PenLine" class="w-4 h-4" />
                                        </Link>
                                        <!-- Delete -->
                                        <button type="button" @click="openDeleteModal(t)"
                                            class="p-1.5 text-slate-400 hover:text-red-500 transition" title="Delete">
                                            <Lucide icon="Trash2" class="w-4 h-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="!testings.data.length">
                                <td colspan="10" class="px-5 py-14 text-center text-slate-400">
                                    <Lucide icon="BadgeInfo" class="w-12 h-12 mx-auto mb-3 text-slate-300" />
                                    <p class="text-sm">No test records found</p>
                                    <p class="text-xs mt-1">Try adjusting your filters</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="testings.last_page > 1" class="p-4 border-t border-slate-200/60 flex items-center justify-between">
                    <span class="text-sm text-slate-500">{{ testings.total }} total records</span>
                    <div class="flex gap-1">
                        <template v-for="link in testings.links" :key="link.label">
                            <Link v-if="link.url" :href="link.url"
                                class="px-3 py-1 text-sm rounded"
                                :class="link.active ? 'bg-primary text-white' : 'text-slate-600 hover:bg-slate-100'"
                                v-html="link.label" />
                            <span v-else class="px-3 py-1 text-sm text-slate-300" v-html="link.label" />
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ══ DELETE MODAL ══ -->
    <Dialog :open="deleteModalOpen" @close="deleteModalOpen = false">
        <Dialog.Panel class="w-full max-w-[500px] overflow-hidden">
            <div class="px-6 pt-6">
                <button type="button" @click="deleteModalOpen = false"
                    class="ml-auto flex rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition">
                    <Lucide icon="X" class="h-5 w-5" />
                </button>
                <div class="pb-2 text-center">
                    <div class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-full border-2 border-danger text-danger">
                        <Lucide icon="AlertTriangle" class="h-7 w-7" />
                    </div>
                    <h3 class="text-2xl font-light text-slate-600">Delete Test Record?</h3>
                    <p class="mt-3 text-sm text-slate-500">
                        This will permanently delete the test record and all its attachments.<br />
                        This action cannot be undone.
                    </p>
                    <div v-if="selectedTesting" class="mt-4 rounded-lg bg-slate-50 px-4 py-3 text-sm text-left">
                        <p><span class="text-slate-500">Driver:</span> <span class="font-medium text-slate-700">{{ selectedTesting.driver_name }}</span></p>
                        <p class="mt-1"><span class="text-slate-500">Test Type:</span> <span class="font-medium text-slate-700">{{ selectedTesting.test_type_label }}</span></p>
                        <p class="mt-1"><span class="text-slate-500">Date:</span> <span class="font-medium text-slate-700">{{ selectedTesting.test_date }}</span></p>
                    </div>
                </div>
            </div>
            <div class="flex justify-center gap-3 px-6 pb-8 pt-4">
                <button type="button" @click="deleteModalOpen = false"
                    class="min-w-24 rounded-lg border border-slate-300 px-6 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50">
                    Cancel
                </button>
                <button type="button" @click="confirmDelete"
                    class="min-w-24 rounded-lg bg-danger px-6 py-2.5 text-sm font-medium text-white hover:bg-danger/90">
                    Delete
                </button>
            </div>
        </Dialog.Panel>
    </Dialog>
</template>
