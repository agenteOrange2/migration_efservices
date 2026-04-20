<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'
import Button from '@/components/Base/Button'
import { FormInput, FormSelect } from '@/components/Base/Form'
import Lucide from '@/components/Base/Lucide'
import RazeLayout from '@/layouts/RazeLayout.vue'
import { useDebounceFn } from '@vueuse/core'

declare function route(name: string, params?: any): string

defineOptions({ layout: RazeLayout })

interface DriverItem {
    id: number
    name: string
    last_name: string
    full_name: string
    email: string
    carrier_name: string
    carrier_id: number | null
    status: number
    effective_status: string
    completion_percentage: number
    created_at: string
    profile_photo_url: string | null
}

interface DriverRouteNames {
    index: string
    show: string
    create: string
    edit?: string
    activate?: string
    deactivate?: string
    destroy?: string
}

const props = withDefaults(defineProps<{
    drivers: {
        data: DriverItem[]
        links: { url: string | null; label: string; active: boolean }[]
        current_page: number
        last_page: number
        per_page: number
        total: number
    }
    carriers: { id: number; name: string }[]
    filters: { search: string; carrier?: string; per_page: number; tab: string }
    stats: { total: number; active: number; inactive: number; new?: number; pending_review?: number; draft?: number }
    driverLimit?: { current: number; max: number; remaining: number; usage_percentage: number; has_limit: boolean }
    routeNames?: DriverRouteNames
    isCarrierContext?: boolean
}>(), {
    routeNames: () => ({
        index: 'admin.drivers.index',
        show: 'admin.drivers.show',
        create: 'admin.drivers.wizard.create',
        activate: 'admin.drivers.activate',
        deactivate: 'admin.drivers.deactivate',
    }),
    isCarrierContext: false,
})

const search = ref(props.filters.search ?? '')
const carrierFilter = ref(props.filters.carrier ?? '')
const tab = ref(props.filters.tab ?? 'all')
const isCarrierContext = computed(() => props.isCarrierContext)
const routeNames = computed(() => props.routeNames)

function namedRoute(name: keyof DriverRouteNames, params?: any) {
    const routeName = props.routeNames[name]

    return routeName ? route(routeName, params) : '#'
}

const statCards = computed(() => {
    if (props.isCarrierContext) {
        return [
            { key: 'total', tab: 'all', label: 'Total Drivers', icon: 'Users', value: props.stats.total, tone: 'text-primary', badgeClass: 'bg-primary/10 text-primary', badgeLabel: 'All' },
            { key: 'active', tab: 'active', label: 'Active', icon: 'UserCheck', value: props.stats.active, tone: 'text-success', badgeClass: 'bg-success/10 text-success', badgeLabel: 'Active' },
            { key: 'pending_review', tab: 'pending_review', label: 'Pending Review', icon: 'Clock3', value: props.stats.pending_review ?? 0, tone: 'text-warning', badgeClass: 'bg-warning/10 text-warning', badgeLabel: 'Pending' },
            { key: 'inactive', tab: 'inactive', label: 'Inactive', icon: 'UserMinus', value: props.stats.inactive, tone: 'text-danger', badgeClass: 'bg-danger/10 text-danger', badgeLabel: 'Inactive' },
        ]
    }

    return [
        { key: 'total', tab: 'all', label: 'Total Approved', icon: 'Users', value: props.stats.total, tone: 'text-primary', badgeClass: 'bg-primary/10 text-primary', badgeLabel: 'All' },
        { key: 'active', tab: 'active', label: 'Active', icon: 'UserCheck', value: props.stats.active, tone: 'text-success', badgeClass: 'bg-success/10 text-success', badgeLabel: 'Active' },
        { key: 'inactive', tab: 'inactive', label: 'Inactive', icon: 'UserMinus', value: props.stats.inactive, tone: 'text-danger', badgeClass: 'bg-danger/10 text-danger', badgeLabel: 'Inactive' },
        { key: 'new', tab: 'new', label: 'New (30 days)', icon: 'UserPlus', value: props.stats.new ?? 0, tone: 'text-info', badgeClass: 'bg-info/10 text-info', badgeLabel: 'New' },
    ]
})

const title = computed(() => props.isCarrierContext ? 'Driver Management' : 'Approved Drivers')
const subtitle = computed(() => props.isCarrierContext
    ? 'Manage the drivers assigned to your carrier account'
    : 'Manage and track approved driver profiles')
const createLabel = computed(() => props.isCarrierContext ? 'Add Driver' : 'Register New Driver')
const showDriverLimit = computed(() => props.isCarrierContext && !!props.driverLimit?.has_limit)

function applyFilters() {
    router.get(namedRoute('index'), {
        search: search.value || undefined,
        carrier: props.isCarrierContext ? undefined : (carrierFilter.value || undefined),
        tab: tab.value !== 'all' ? tab.value : undefined,
    }, { preserveState: true, replace: true })
}

const debouncedSearch = useDebounceFn(applyFilters, 400)
watch(search, debouncedSearch)
watch(carrierFilter, () => {
    if (!props.isCarrierContext) applyFilters()
})

function activateDriver(driver: DriverItem) {
    if (props.routeNames.activate && confirm(`Activate driver "${driver.full_name}"?`)) {
        router.put(route(props.routeNames.activate, driver.id), {}, { preserveScroll: true })
    }
}

function deactivateDriver(driver: DriverItem) {
    if (props.routeNames.deactivate && confirm(`Deactivate driver "${driver.full_name}"?`)) {
        router.put(route(props.routeNames.deactivate, driver.id), {}, { preserveScroll: true })
    }
}

function deleteDriver(driver: DriverItem) {
    if (props.routeNames.destroy && confirm(`Delete driver "${driver.full_name}"? This action cannot be undone.`)) {
        router.delete(route(props.routeNames.destroy, driver.id), { preserveScroll: true })
    }
}

const effectiveStatusBadge = (status: string) => {
    const map: Record<string, string> = {
        active: 'bg-success/10 text-success',
        inactive: 'bg-danger/10 text-danger',
        draft: 'bg-slate-100 text-slate-600',
        pending_review: 'bg-warning/10 text-warning',
        approved: 'bg-primary/10 text-primary',
        rejected: 'bg-danger/10 text-danger',
    }
    return map[status] ?? 'bg-slate-100 text-slate-600'
}
</script>

<template>
    <Head :title="title" />

    <div class="grid grid-cols-12 gap-y-10 gap-x-6">
        <!-- Header -->
        <div class="col-span-12">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div class="flex items-center gap-3">
                    <div class="p-3 rounded-xl bg-primary/10 border border-primary/20">
                        <Lucide icon="UserCheck" class="w-8 h-8 text-primary" />
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-slate-800">{{ title }}</h1>
                        <p class="text-sm text-slate-500">{{ subtitle }}</p>
                    </div>
                </div>
                <Link :href="namedRoute('create')">
                    <Button variant="primary" class="flex items-center gap-2 shadow-sm">
                        <Lucide icon="UserPlus" class="w-4 h-4" />
                        {{ createLabel }}
                    </Button>
                </Link>
            </div>
        </div>

        <div v-if="showDriverLimit" class="col-span-12">
            <div class="box box--stacked p-5 md:p-6">
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-800">Driver Limit</h2>
                        <p class="mt-1 text-sm text-slate-500">
                            Your current plan allows up to {{ driverLimit?.max }} drivers.
                            <span class="font-medium text-slate-700">{{ driverLimit?.remaining }} slot<span v-if="driverLimit?.remaining !== 1">s</span> remaining.</span>
                        </p>
                    </div>
                    <div class="flex items-center gap-3 md:min-w-[220px]">
                        <span class="text-sm font-semibold text-slate-700 whitespace-nowrap">
                            {{ driverLimit?.current }} / {{ driverLimit?.max }}
                        </span>
                        <div class="h-2 flex-1 rounded-full bg-slate-200 overflow-hidden">
                            <div
                                class="h-full rounded-full bg-primary transition-all"
                                :style="{ width: `${driverLimit?.usage_percentage ?? 0}%` }"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab Stats -->
        <div class="col-span-12">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <Link
                    v-for="card in statCards"
                    :key="card.key"
                    :href="namedRoute('index', { tab: card.tab === 'all' ? undefined : card.tab, search: filters.search || undefined, carrier: !isCarrierContext ? (filters.carrier || undefined) : undefined })"
                        class="box box--stacked p-5 rounded-xl border-2 transition-all"
                    :class="(filters.tab || 'all') === card.tab ? 'border-primary/60 bg-primary/5 shadow-sm' : 'border-slate-200 hover:border-primary/30'"
                >
                    <div class="text-sm text-slate-500">{{ card.label }}</div>
                    <div class="mt-1 text-2xl font-bold" :class="card.tone">{{ card.value }}</div>
                    <div class="mt-2 inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs" :class="card.badgeClass">
                        <Lucide :icon="card.icon" class="w-3 h-3" /> {{ card.badgeLabel }}
                    </div>
                </Link>
            </div>
        </div>

        <!-- Filters -->
        <div class="col-span-12">
            <div class="box box--stacked p-5">
                <div class="flex flex-col lg:flex-row gap-4">
                    <div class="flex-1 relative">
                        <Lucide icon="Search" class="absolute inset-y-0 left-0 w-4 h-4 my-auto ml-3 text-slate-400" />
                        <FormInput
                            v-model="search"
                            type="text"
                            placeholder="Search by name, email, phone..."
                            class="pl-10"
                        />
                    </div>
                    <FormSelect v-if="!isCarrierContext" v-model="carrierFilter" class="w-full lg:w-56">
                        <option value="">All Carriers</option>
                        <option v-for="c in carriers" :key="c.id" :value="String(c.id)">{{ c.name }}</option>
                    </FormSelect>
                    <Link
                        v-if="search || (!isCarrierContext && carrierFilter) || (filters.tab && filters.tab !== 'all')"
                        :href="namedRoute('index')"
                    >
                        <Button variant="outline-secondary" class="flex items-center justify-center gap-2">
                            <Lucide icon="X" class="w-4 h-4" /> Clear filters
                        </Button>
                    </Link>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="col-span-12">
            <div class="box box--stacked p-0 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-slate-50/80">
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Driver</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Carrier</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase w-40">Profile</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase text-center">Status</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Joined</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="d in drivers.data" :key="d.id" class="border-b border-slate-100 hover:bg-slate-50/50 transition">
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-full bg-slate-100 flex items-center justify-center flex-shrink-0 overflow-hidden">
                                            <img v-if="d.profile_photo_url" :src="d.profile_photo_url" :alt="d.full_name" class="w-full h-full object-cover" />
                                            <Lucide v-else icon="User" class="w-4 h-4 text-slate-400" />
                                        </div>
                                        <div>
                                            <Link :href="namedRoute('show', d.id)" class="font-medium text-slate-700 hover:text-primary transition">
                                                {{ d.full_name }}
                                            </Link>
                                            <div class="text-xs text-slate-500">{{ d.email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-sm text-slate-600">{{ d.carrier_name }}</td>
                                <td class="px-5 py-4">
                                    <div class="w-32">
                                        <div class="text-xs text-slate-500">{{ d.completion_percentage }}%</div>
                                        <div class="mt-1 h-1.5 rounded-full bg-slate-100 overflow-hidden">
                                            <div
                                                class="h-full rounded-full bg-primary/60"
                                                :style="{ width: Math.min(d.completion_percentage, 100) + '%' }"
                                            />
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-center">
                                    <span
                                        :class="[effectiveStatusBadge(d.effective_status), 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize']"
                                    >
                                        {{ d.effective_status.replace('_', ' ') }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-sm text-slate-500">
                                    {{ new Date(d.created_at).toLocaleDateString() }}
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center justify-center gap-2">
                                        <Link :href="namedRoute('show', d.id)" class="p-1.5 text-slate-400 hover:text-primary transition" title="View">
                                            <Lucide icon="Eye" class="w-4 h-4" />
                                        </Link>
                                        <Link v-if="routeNames.edit" :href="namedRoute('edit', d.id)" class="p-1.5 text-slate-400 hover:text-primary transition" title="Edit">
                                            <Lucide icon="Pencil" class="w-4 h-4" />
                                        </Link>
                                        <button
                                            v-if="routeNames.activate && d.effective_status === 'inactive'"
                                            @click="activateDriver(d)"
                                            class="p-1.5 text-slate-400 hover:text-success transition"
                                            title="Activate"
                                        >
                                            <Lucide icon="UserCheck" class="w-4 h-4" />
                                        </button>
                                        <button
                                            v-else-if="routeNames.deactivate && d.effective_status === 'active'"
                                            @click="deactivateDriver(d)"
                                            class="p-1.5 text-slate-400 hover:text-danger transition"
                                            title="Deactivate"
                                        >
                                            <Lucide icon="UserMinus" class="w-4 h-4" />
                                        </button>
                                        <button
                                            v-if="routeNames.destroy"
                                            @click="deleteDriver(d)"
                                            class="p-1.5 text-slate-400 hover:text-danger transition"
                                            title="Delete"
                                        >
                                            <Lucide icon="Trash2" class="w-4 h-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!drivers.data.length">
                                <td colspan="6" class="px-5 py-12 text-center text-slate-400">
                                    <Lucide icon="Users" class="w-12 h-12 mx-auto mb-3 text-slate-300" />
                                    <p>No approved drivers found</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="drivers.last_page > 1" class="p-4 border-t border-slate-200/60 flex items-center justify-between">
                    <span class="text-sm text-slate-500">{{ drivers.total }} drivers</span>
                    <div class="flex gap-1">
                        <template v-for="link in drivers.links" :key="link.label">
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                class="px-3 py-1 text-sm rounded"
                                :class="link.active ? 'bg-primary text-white' : 'text-slate-600 hover:bg-slate-100'"
                                v-html="link.label"
                            />
                            <span v-else class="px-3 py-1 text-sm text-slate-300" v-html="link.label" />
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
