<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import Lucide from '@/components/Base/Lucide'
import RazeLayout from '@/layouts/RazeLayout.vue'
import { useDebounceFn } from '@vueuse/core'

declare function route(name: string, params?: any): string

defineOptions({ layout: RazeLayout })

interface Driver {
    id: number
    name: string
    last_name: string
    middle_name: string
    email: string
    phone: string
    carrier_name: string
    profile_photo: string | null
    application_date: string
    status: string
    checklist_pct: number
}

const props = defineProps<{
    drivers: {
        data: Driver[]
        links: { url: string | null; label: string; active: boolean }[]
        current_page: number
        last_page: number
        per_page: number
        total: number
        from: number
        to: number
    }
    carriers: { id: number; name: string }[]
    filters: { search: string; status: string; carrier: string; per_page: number }
    stats: { total: number; pending: number; approved: number; rejected: number }
    applicationStatuses: Record<string, string>
}>()

const search       = ref(props.filters.search ?? '')
const statusFilter = ref(props.filters.status ?? '')
const carrierFilter = ref(props.filters.carrier ?? '')

function applyFilters() {
    router.get(route('admin.driver-recruitment.index'), {
        search:   search.value || undefined,
        status:   statusFilter.value || undefined,
        carrier:  carrierFilter.value || undefined,
    }, { preserveState: true, replace: true })
}

const debouncedSearch = useDebounceFn(applyFilters, 400)
watch(search, debouncedSearch)
watch(statusFilter, applyFilters)
watch(carrierFilter, applyFilters)

const statusConfig: Record<string, { label: string; classes: string; icon: string }> = {
    draft:    { label: 'Draft',    classes: 'bg-slate-100 text-slate-600',    icon: 'FileEdit' },
    pending:  { label: 'Pending',  classes: 'bg-amber-100 text-amber-600',    icon: 'Clock' },
    approved: { label: 'Approved', classes: 'bg-emerald-100 text-emerald-700',icon: 'CheckCircle' },
    rejected: { label: 'Rejected', classes: 'bg-red-100 text-red-600',        icon: 'XCircle' },
}

function statusBadge(status: string) {
    return statusConfig[status] ?? statusConfig.draft
}
</script>

<template>
    <Head title="Driver Recruitment" />

    <div class="grid grid-cols-12 gap-y-8 gap-x-6">

        <!-- Header -->
        <div class="col-span-12">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div class="flex items-center gap-3">
                    <div class="p-3 rounded-xl bg-primary/10 border border-primary/20">
                        <Lucide icon="ClipboardCheck" class="w-8 h-8 text-primary" />
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-slate-800">Driver Recruitment</h1>
                        <p class="text-sm text-slate-500">Review and manage driver applications</p>
                    </div>
                </div>
                <Link
                    :href="route('admin.drivers.wizard.create')"
                    class="flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-lg text-sm font-medium hover:bg-primary/90 transition"
                >
                    <Lucide icon="UserPlus" class="w-4 h-4" />
                    Register New Driver
                </Link>
            </div>
        </div>

        <!-- Stats -->
        <div class="col-span-12">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="box box--stacked p-5 rounded-xl border border-slate-200">
                    <div class="text-sm text-slate-500">Total Applications</div>
                    <div class="mt-1 text-2xl font-bold text-slate-800">{{ stats.total }}</div>
                    <div class="mt-2 inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-600">
                        <Lucide icon="Users" class="w-3 h-3" /> All
                    </div>
                </div>
                <div class="box box--stacked p-5 rounded-xl border border-amber-200 bg-amber-50/40">
                    <div class="text-sm text-amber-700">Pending Review</div>
                    <div class="mt-1 text-2xl font-bold text-amber-600">{{ stats.pending }}</div>
                    <div class="mt-2 inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 text-xs text-amber-700">
                        <Lucide icon="Clock" class="w-3 h-3" /> Pending
                    </div>
                </div>
                <div class="box box--stacked p-5 rounded-xl border border-emerald-200 bg-emerald-50/40">
                    <div class="text-sm text-emerald-700">Approved</div>
                    <div class="mt-1 text-2xl font-bold text-emerald-600">{{ stats.approved }}</div>
                    <div class="mt-2 inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2 py-0.5 text-xs text-emerald-700">
                        <Lucide icon="CheckCircle" class="w-3 h-3" /> Approved
                    </div>
                </div>
                <div class="box box--stacked p-5 rounded-xl border border-red-200 bg-red-50/40">
                    <div class="text-sm text-red-700">Rejected</div>
                    <div class="mt-1 text-2xl font-bold text-red-600">{{ stats.rejected }}</div>
                    <div class="mt-2 inline-flex items-center gap-1 rounded-full bg-red-100 px-2 py-0.5 text-xs text-red-700">
                        <Lucide icon="XCircle" class="w-3 h-3" /> Rejected
                    </div>
                </div>
            </div>
        </div>

        <!-- Table Box -->
        <div class="col-span-12">
            <div class="box box--stacked flex flex-col">

                <!-- Filters -->
                <div class="flex flex-col gap-y-2 p-5 sm:flex-row sm:items-center border-b border-slate-200/60">
                    <div class="relative">
                        <Lucide icon="Search" class="absolute inset-y-0 left-0 z-10 my-auto ml-3 h-4 w-4 text-slate-400" />
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Search drivers..."
                            class="pl-9 pr-4 py-2 border border-slate-200 rounded-lg text-sm w-full sm:w-64 focus:outline-none focus:ring-2 focus:ring-primary/30"
                        />
                    </div>
                    <div class="flex flex-col gap-x-3 gap-y-2 sm:ml-auto sm:flex-row">
                        <select
                            v-model="statusFilter"
                            class="px-3 py-2 border border-slate-200 rounded-lg text-sm sm:w-36 focus:outline-none focus:ring-2 focus:ring-primary/30"
                        >
                            <option value="">All statuses</option>
                            <option v-for="(label, val) in applicationStatuses" :key="val" :value="val">{{ label }}</option>
                        </select>
                        <select
                            v-model="carrierFilter"
                            class="px-3 py-2 border border-slate-200 rounded-lg text-sm sm:w-48 focus:outline-none focus:ring-2 focus:ring-primary/30"
                        >
                            <option value="">All carriers</option>
                            <option v-for="c in carriers" :key="c.id" :value="c.id">{{ c.name }}</option>
                        </select>
                    </div>
                </div>

                <!-- Table -->
                <div class="overflow-auto xl:overflow-visible">
                    <table class="w-full text-sm border-b border-slate-200/60">
                        <thead>
                            <tr>
                                <td class="border-t border-slate-200/60 bg-slate-50 py-4 px-4 font-medium text-slate-500">Driver</td>
                                <td class="border-t border-slate-200/60 bg-slate-50 py-4 px-4 font-medium text-slate-500">Contact</td>
                                <td class="border-t border-slate-200/60 bg-slate-50 py-4 px-4 font-medium text-slate-500">Carrier</td>
                                <td class="border-t border-slate-200/60 bg-slate-50 py-4 px-4 font-medium text-slate-500 text-center">Verification</td>
                                <td class="border-t border-slate-200/60 bg-slate-50 py-4 px-4 font-medium text-slate-500 text-center">Status</td>
                                <td class="border-t border-slate-200/60 bg-slate-50 py-4 px-4 font-medium text-slate-500 text-center">Actions</td>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="drivers.data.length === 0">
                                <td colspan="6" class="py-12 text-center text-slate-500">
                                    <div class="flex flex-col items-center gap-2">
                                        <Lucide icon="UserX" class="w-12 h-12 text-slate-300" />
                                        <p>No driver applications found</p>
                                    </div>
                                </td>
                            </tr>
                            <tr v-for="driver in drivers.data" :key="driver.id" class="border-t border-slate-100 hover:bg-slate-50/50 transition">
                                <!-- Driver -->
                                <td class="py-4 px-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full overflow-hidden bg-slate-100 flex items-center justify-center flex-shrink-0">
                                            <img v-if="driver.profile_photo" :src="driver.profile_photo" :alt="driver.name" class="w-full h-full object-cover" />
                                            <Lucide v-else icon="User" class="w-5 h-5 text-slate-400" />
                                        </div>
                                        <div>
                                            <div class="font-medium text-slate-800">{{ driver.name }} {{ driver.last_name }}</div>
                                            <div class="text-xs text-slate-500">
                                                <span v-if="driver.middle_name">{{ driver.middle_name }} · </span>
                                                Apply: {{ driver.application_date }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <!-- Contact -->
                                <td class="py-4 px-4">
                                    <div class="flex flex-col gap-0.5">
                                        <span class="text-slate-700">{{ driver.email }}</span>
                                        <span class="text-slate-500 text-xs">{{ driver.phone }}</span>
                                    </div>
                                </td>
                                <!-- Carrier -->
                                <td class="py-4 px-4">
                                    <span class="font-medium text-slate-700">{{ driver.carrier_name }}</span>
                                </td>
                                <!-- Verification progress -->
                                <td class="py-4 px-4 text-center">
                                    <div class="flex items-center justify-center">
                                        <div
                                            class="w-14 h-14 rounded-full flex items-center justify-center"
                                            :style="`background: conic-gradient(#3b82f6 ${driver.checklist_pct}%, #f1f5f9 0)`"
                                        >
                                            <div class="w-10 h-10 rounded-full bg-white flex items-center justify-center text-xs font-semibold text-slate-700">
                                                {{ driver.checklist_pct }}%
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <!-- Status -->
                                <td class="py-4 px-4 text-center">
                                    <div class="flex justify-center">
                                        <span
                                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium"
                                            :class="statusBadge(driver.status).classes"
                                        >
                                            <Lucide :icon="statusBadge(driver.status).icon" class="w-3.5 h-3.5" />
                                            {{ statusBadge(driver.status).label }}
                                        </span>
                                    </div>
                                </td>
                                <!-- Actions -->
                                <td class="py-4 px-4 text-center">
                                    <Link
                                        :href="route('admin.driver-recruitment.show', driver.id)"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-primary text-white rounded-lg text-xs font-medium hover:bg-primary/90 transition"
                                    >
                                        <Lucide icon="ClipboardCheck" class="w-3.5 h-3.5" />
                                        Review
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="drivers.last_page > 1" class="flex items-center justify-between p-5 border-t border-slate-200/60">
                    <div class="text-sm text-slate-500">
                        Showing {{ drivers.from }}–{{ drivers.to }} of {{ drivers.total }} results
                    </div>
                    <div class="flex gap-1">
                        <template v-for="link in drivers.links" :key="link.label">
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                preserve-scroll
                                class="px-3 py-1 rounded text-sm border transition"
                                :class="link.active
                                    ? 'bg-primary text-white border-primary'
                                    : 'border-slate-200 text-slate-600 hover:border-primary/40 hover:text-primary'"
                                v-html="link.label"
                            />
                            <span
                                v-else
                                class="px-3 py-1 rounded text-sm border border-slate-100 text-slate-300 cursor-default"
                                v-html="link.label"
                            />
                        </template>
                    </div>
                </div>
                <div v-else-if="drivers.total > 0" class="p-5 border-t border-slate-200/60 text-sm text-slate-500">
                    Showing {{ drivers.total }} result{{ drivers.total !== 1 ? 's' : '' }}
                </div>

            </div>
        </div>

    </div>
</template>
