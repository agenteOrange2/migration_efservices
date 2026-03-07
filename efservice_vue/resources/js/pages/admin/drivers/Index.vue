<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
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

const props = defineProps<{
    drivers: {
        data: DriverItem[]
        links: { url: string | null; label: string; active: boolean }[]
        current_page: number
        last_page: number
        per_page: number
        total: number
    }
    carriers: { id: number; name: string }[]
    filters: { search: string; carrier: string; per_page: number; tab: string }
    stats: { total: number; active: number; inactive: number; new: number }
}>()

const search = ref(props.filters.search ?? '')
const carrierFilter = ref(props.filters.carrier ?? '')
const tab = ref(props.filters.tab ?? 'all')

function applyFilters() {
    router.get(route('admin.drivers.index'), {
        search: search.value || undefined,
        carrier: carrierFilter.value || undefined,
        tab: tab.value !== 'all' ? tab.value : undefined,
    }, { preserveState: true, replace: true })
}

const debouncedSearch = useDebounceFn(applyFilters, 400)
watch(search, debouncedSearch)
watch(carrierFilter, applyFilters)

function activateDriver(driver: DriverItem) {
    if (confirm(`Activate driver "${driver.full_name}"?`)) {
        router.put(route('admin.drivers.activate', driver.id), {}, { preserveScroll: true })
    }
}

function deactivateDriver(driver: DriverItem) {
    if (confirm(`Deactivate driver "${driver.full_name}"?`)) {
        router.put(route('admin.drivers.deactivate', driver.id), {}, { preserveScroll: true })
    }
}

const effectiveStatusBadge = (status: string) => {
    const map: Record<string, string> = {
        active: 'bg-emerald-100 text-emerald-700',
        inactive: 'bg-red-100 text-red-700',
        draft: 'bg-slate-100 text-slate-600',
        pending_review: 'bg-amber-100 text-amber-700',
        approved: 'bg-blue-100 text-blue-700',
        rejected: 'bg-red-100 text-red-700',
    }
    return map[status] ?? 'bg-slate-100 text-slate-600'
}
</script>

<template>
    <Head title="Approved Drivers" />

    <div class="grid grid-cols-12 gap-y-10 gap-x-6">
        <!-- Header -->
        <div class="col-span-12">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div class="flex items-center gap-3">
                    <div class="p-3 rounded-xl bg-primary/10 border border-primary/20">
                        <Lucide icon="UserCheck" class="w-8 h-8 text-primary" />
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-slate-800">Approved Drivers</h1>
                        <p class="text-sm text-slate-500">Manage and track approved driver profiles</p>
                    </div>
                </div>
                <Link :href="route('admin.drivers.wizard.create')" class="flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-lg text-sm font-medium hover:bg-primary/90 transition">
                    <Lucide icon="UserPlus" class="w-4 h-4" />
                    Register New Driver
                </Link>
            </div>
        </div>

        <!-- Tab Stats -->
        <div class="col-span-12">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <Link
                    :href="route('admin.drivers.index', { tab: 'all', search: filters.search || undefined, carrier: filters.carrier || undefined })"
                    class="box box--stacked p-5 rounded-xl border-2 transition-all"
                    :class="(filters.tab || 'all') === 'all' ? 'border-primary/60 bg-primary/5' : 'border-slate-200 hover:border-primary/30'"
                >
                    <div class="text-sm text-slate-500">Total Approved</div>
                    <div class="mt-1 text-2xl font-bold text-slate-800">{{ stats.total }}</div>
                    <div class="mt-2 inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-600">
                        <Lucide icon="Users" class="w-3 h-3" /> All
                    </div>
                </Link>
                <Link
                    :href="route('admin.drivers.index', { tab: 'active', search: filters.search || undefined, carrier: filters.carrier || undefined })"
                    class="box box--stacked p-5 rounded-xl border-2 transition-all"
                    :class="(filters.tab || 'all') === 'active' ? 'border-primary/60 bg-primary/5' : 'border-slate-200 hover:border-primary/30'"
                >
                    <div class="text-sm text-slate-500">Active</div>
                    <div class="mt-1 text-2xl font-bold text-emerald-600">{{ stats.active }}</div>
                    <div class="mt-2 inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2 py-0.5 text-xs text-emerald-700">
                        <Lucide icon="UserCheck" class="w-3 h-3" /> Active
                    </div>
                </Link>
                <Link
                    :href="route('admin.drivers.index', { tab: 'inactive', search: filters.search || undefined, carrier: filters.carrier || undefined })"
                    class="box box--stacked p-5 rounded-xl border-2 transition-all"
                    :class="(filters.tab || 'all') === 'inactive' ? 'border-primary/60 bg-primary/5' : 'border-slate-200 hover:border-primary/30'"
                >
                    <div class="text-sm text-slate-500">Inactive</div>
                    <div class="mt-1 text-2xl font-bold text-red-500">{{ stats.inactive }}</div>
                    <div class="mt-2 inline-flex items-center gap-1 rounded-full bg-red-100 px-2 py-0.5 text-xs text-red-700">
                        <Lucide icon="UserMinus" class="w-3 h-3" /> Inactive
                    </div>
                </Link>
                <Link
                    :href="route('admin.drivers.index', { tab: 'new', search: filters.search || undefined, carrier: filters.carrier || undefined })"
                    class="box box--stacked p-5 rounded-xl border-2 transition-all"
                    :class="(filters.tab || 'all') === 'new' ? 'border-primary/60 bg-primary/5' : 'border-slate-200 hover:border-primary/30'"
                >
                    <div class="text-sm text-slate-500">New (30 days)</div>
                    <div class="mt-1 text-2xl font-bold text-blue-600">{{ stats.new }}</div>
                    <div class="mt-2 inline-flex items-center gap-1 rounded-full bg-blue-100 px-2 py-0.5 text-xs text-blue-700">
                        <Lucide icon="UserPlus" class="w-3 h-3" /> New
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
                    <FormSelect v-model="carrierFilter" class="w-full lg:w-56">
                        <option value="">All Carriers</option>
                        <option v-for="c in carriers" :key="c.id" :value="String(c.id)">{{ c.name }}</option>
                    </FormSelect>
                    <Link
                        v-if="search || carrierFilter || (filters.tab && filters.tab !== 'all')"
                        :href="route('admin.drivers.index')"
                        class="flex items-center justify-center gap-2 px-4 py-2 rounded-lg border border-slate-300 text-slate-600 hover:bg-slate-50"
                    >
                        <Lucide icon="X" class="w-4 h-4" /> Clear filters
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
                                            <Link :href="route('admin.drivers.show', d.id)" class="font-medium text-slate-700 hover:text-primary transition">
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
                                        <Link :href="route('admin.drivers.show', d.id)" class="p-1.5 text-slate-400 hover:text-primary transition" title="View">
                                            <Lucide icon="Eye" class="w-4 h-4" />
                                        </Link>
                                        <button
                                            v-if="d.effective_status === 'inactive'"
                                            @click="activateDriver(d)"
                                            class="p-1.5 text-slate-400 hover:text-emerald-500 transition"
                                            title="Activate"
                                        >
                                            <Lucide icon="UserCheck" class="w-4 h-4" />
                                        </button>
                                        <button
                                            v-else-if="d.effective_status === 'active'"
                                            @click="deactivateDriver(d)"
                                            class="p-1.5 text-slate-400 hover:text-red-500 transition"
                                            title="Deactivate"
                                        >
                                            <Lucide icon="UserMinus" class="w-4 h-4" />
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
