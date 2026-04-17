<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { computed, reactive } from 'vue'
import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import RazeLayout from '@/layouts/RazeLayout.vue'

declare function route(name: string, params?: any): string

defineOptions({ layout: RazeLayout })

interface PaginationLink {
    url: string | null
    label: string
    active: boolean
}

interface TestingRow {
    id: number
    test_date: string | null
    test_type: string
    test_type_label: string
    status: string | null
    test_result: string | null
    location: string | null
    administered_by: string | null
    requester_name: string | null
    next_test_due: string | null
    has_pdf: boolean
    has_results: boolean
    reasons: { active: boolean; label: string }[]
}

const props = defineProps<{
    driver: {
        id: number
        full_name: string
        carrier_name: string | null
    }
    filters: {
        search: string
        status: string
        result: string
    }
    statuses: Record<string, string>
    results: Record<string, string>
    stats: {
        total: number
        completed: number
        pending_review: number
        positive: number
    }
    testings: {
        data: TestingRow[]
        links: PaginationLink[]
        total: number
        last_page: number
    }
}>()

const filters = reactive({
    search: props.filters.search,
    status: props.filters.status,
    result: props.filters.result,
})

const statCards = computed(() => [
    { label: 'Total Tests', value: props.stats.total, icon: 'TestTube', className: 'bg-primary/10 text-primary' },
    { label: 'Completed', value: props.stats.completed, icon: 'BadgeCheck', className: 'bg-primary/10 text-primary' },
    { label: 'Pending Review', value: props.stats.pending_review, icon: 'Clock3', className: 'bg-slate-100 text-slate-700' },
    { label: 'Positive Results', value: props.stats.positive, icon: 'AlertTriangle', className: 'bg-slate-200 text-slate-700' },
])

function applyFilters() {
    router.get(route('driver.testing.index'), {
        search: filters.search || undefined,
        status: filters.status || undefined,
        result: filters.result || undefined,
    }, {
        preserveState: true,
        replace: true,
    })
}

function resetFilters() {
    filters.search = ''
    filters.status = ''
    filters.result = ''
    applyFilters()
}

function statusClass(status: string | null) {
    if (status === 'Completed') return 'bg-primary/10 text-primary'
    if (status === 'Pending Review') return 'bg-slate-100 text-slate-700'
    if (status === 'Cancelled') return 'bg-slate-200 text-slate-700'
    return 'bg-slate-100 text-slate-600'
}

function resultClass(result: string | null) {
    if (result === 'Negative') return 'bg-primary/10 text-primary'
    if (result === 'Positive') return 'bg-slate-200 text-slate-700'
    if (result === 'Refusal') return 'bg-slate-100 text-slate-700'
    return 'bg-slate-100 text-slate-500'
}
</script>

<template>
    <Head title="My Tests" />

    <div class="grid grid-cols-12 gap-6">
        <div class="col-span-12">
            <div class="box box--stacked p-6">
                <div class="flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between">
                    <div class="flex items-start gap-4">
                        <div class="rounded-2xl border border-primary/20 bg-primary/10 p-3">
                            <Lucide icon="TestTube" class="h-8 w-8 text-primary" />
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-slate-800">Drug & Alcohol Tests</h1>
                            <p class="mt-1 text-slate-500">
                                Review your scheduled, completed, and pending test records in one place.
                            </p>
                            <p class="mt-2 text-sm text-slate-500">
                                Driver: <span class="font-medium text-slate-700">{{ driver.full_name }}</span>
                                <span v-if="driver.carrier_name"> · Carrier: <span class="font-medium text-slate-700">{{ driver.carrier_name }}</span></span>
                            </p>
                        </div>
                    </div>

                    <Link :href="`${route('driver.profile')}#testing`">
                        <Button variant="outline-secondary" class="gap-2">
                            <Lucide icon="User" class="h-4 w-4" />
                            View Profile Tab
                        </Button>
                    </Link>
                </div>
            </div>
        </div>

        <div
            v-for="card in statCards"
            :key="card.label"
            class="col-span-12 sm:col-span-6 xl:col-span-3"
        >
            <div class="box box--stacked p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-slate-500">{{ card.label }}</p>
                        <p class="mt-2 text-3xl font-semibold text-slate-800">{{ card.value }}</p>
                    </div>
                    <div class="rounded-xl p-3" :class="card.className">
                        <Lucide :icon="card.icon" class="h-5 w-5" />
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12">
            <div class="box box--stacked p-5">
                <div class="grid grid-cols-1 gap-4 lg:grid-cols-[minmax(0,1fr),220px,220px,auto]">
                    <div class="relative">
                        <Lucide icon="Search" class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                        <input
                            v-model="filters.search"
                            type="text"
                            class="w-full rounded-lg border border-slate-200 px-3 py-2 pl-10 text-sm"
                            placeholder="Search administrator, requester, MRO, or location..."
                            @keyup.enter="applyFilters"
                        >
                    </div>

                    <select v-model="filters.status" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                        <option value="">All statuses</option>
                        <option v-for="(label, key) in statuses" :key="key" :value="key">{{ label }}</option>
                    </select>

                    <select v-model="filters.result" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                        <option value="">All results</option>
                        <option v-for="(label, key) in results" :key="key" :value="key">{{ label }}</option>
                    </select>

                    <div class="flex items-center gap-3">
                        <Button variant="primary" class="gap-2" @click="applyFilters">
                            <Lucide icon="Filter" class="h-4 w-4" />
                            Apply
                        </Button>
                        <Button variant="outline-secondary" class="gap-2" @click="resetFilters">
                            <Lucide icon="RotateCcw" class="h-4 w-4" />
                            Clear
                        </Button>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12">
            <div v-if="testings.data.length" class="grid grid-cols-12 gap-6">
                <div
                    v-for="testing in testings.data"
                    :key="testing.id"
                    class="col-span-12 xl:col-span-6"
                >
                    <div class="box box--stacked h-full border p-6 transition hover:shadow-md">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                            <div class="space-y-3">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h2 class="text-lg font-semibold text-slate-800">{{ testing.test_type_label }}</h2>
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium" :class="statusClass(testing.status)">
                                        {{ testing.status ?? 'Not Set' }}
                                    </span>
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium" :class="resultClass(testing.test_result)">
                                        {{ testing.test_result ?? 'Pending' }}
                                    </span>
                                </div>
                                <p class="text-sm text-slate-500">
                                    {{ testing.test_date || 'No date' }}
                                    <span v-if="testing.location"> · {{ testing.location }}</span>
                                </p>
                                <div class="flex flex-wrap gap-2">
                                    <span
                                        v-for="reason in testing.reasons"
                                        :key="reason.label"
                                        class="inline-flex rounded-full bg-primary/10 px-2.5 py-1 text-xs font-medium text-primary"
                                    >
                                        {{ reason.label }}
                                    </span>
                                    <span
                                        v-if="!testing.reasons.length"
                                        class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-500"
                                    >
                                        No reason selected
                                    </span>
                                </div>
                            </div>

                            <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-center sm:min-w-[150px]">
                                <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Documents</p>
                                <div class="mt-3 space-y-2 text-sm text-slate-600">
                                    <p>{{ testing.has_pdf ? 'Authorization PDF ready' : 'No authorization PDF' }}</p>
                                    <p>{{ testing.has_results ? 'Results uploaded' : 'No results uploaded' }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-4">
                                <p class="text-xs font-medium uppercase tracking-[0.2em] text-slate-400">Administered By</p>
                                <p class="mt-2 text-sm font-semibold text-slate-800">{{ testing.administered_by || 'N/A' }}</p>
                            </div>
                            <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-4">
                                <p class="text-xs font-medium uppercase tracking-[0.2em] text-slate-400">Requested By</p>
                                <p class="mt-2 text-sm font-semibold text-slate-800">{{ testing.requester_name || 'N/A' }}</p>
                            </div>
                            <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-4">
                                <p class="text-xs font-medium uppercase tracking-[0.2em] text-slate-400">Next Test Due</p>
                                <p class="mt-2 text-sm font-semibold text-slate-800">{{ testing.next_test_due || 'Not scheduled' }}</p>
                            </div>
                        </div>

                        <div class="mt-5 flex items-center justify-end border-t border-slate-200 pt-5">
                            <Link :href="route('driver.testing.show', testing.id)">
                                <Button variant="outline-secondary" class="gap-2">
                                    <Lucide icon="ArrowRight" class="h-4 w-4" />
                                    Open Details
                                </Button>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <div v-else class="box box--stacked p-12 text-center">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-slate-100">
                    <Lucide icon="TestTube" class="h-8 w-8 text-slate-400" />
                </div>
                <h2 class="mt-5 text-lg font-semibold text-slate-800">No Test Records Found</h2>
                <p class="mx-auto mt-2 max-w-xl text-sm text-slate-500">
                    You do not have testing records matching the current filters yet.
                </p>
            </div>
        </div>

        <div v-if="testings.last_page > 1" class="col-span-12">
            <div class="box box--stacked flex flex-col gap-4 p-4 sm:flex-row sm:items-center sm:justify-between">
                <span class="text-sm text-slate-500">{{ testings.total }} total records</span>
                <div class="flex flex-wrap gap-1">
                    <template v-for="link in testings.links" :key="link.label">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            class="rounded-lg px-3 py-1.5 text-sm transition"
                            :class="link.active ? 'bg-primary text-white' : 'text-slate-600 hover:bg-slate-100'"
                            v-html="link.label"
                        />
                        <span v-else class="px-3 py-1.5 text-sm text-slate-300" v-html="link.label" />
                    </template>
                </div>
            </div>
        </div>
    </div>
</template>
