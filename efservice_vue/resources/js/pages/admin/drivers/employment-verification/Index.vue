<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import Lucide from '@/components/Base/Lucide'
import RazeLayout from '@/layouts/RazeLayout.vue'

declare function route(name: string, params?: any): string

defineOptions({ layout: RazeLayout })

interface Verification {
    id: number
    driver_id: number
    driver_name: string
    company_name: string
    email: string
    email_sent: boolean
    verification_status: 'verified' | 'rejected' | 'pending'
    attempt_count: number
    updated_at: string
}

interface Driver {
    id: number
    name: string
}

const props = defineProps<{
    verifications: {
        data: Verification[]
        links: { url: string | null; label: string; active: boolean }[]
        current_page: number
        last_page: number
        total: number
    }
    drivers: Driver[]
    filters: { status?: string; driver?: string }
}>()

const statusFilter = ref(props.filters.status ?? '')
const driverFilter = ref(props.filters.driver ?? '')

function applyFilters() {
    router.get(route('admin.drivers.employment-verification.index'), {
        status: statusFilter.value || undefined,
        driver: driverFilter.value || undefined,
    }, { preserveState: true, replace: true })
}

function clearFilters() {
    router.get(route('admin.drivers.employment-verification.index'))
}

watch([statusFilter, driverFilter], applyFilters)

function resendEmail(id: number) {
    if (!confirm('Resend verification email to the employer?')) return
    router.post(route('admin.drivers.employment-verification.resend', id), {}, { preserveScroll: true })
}

function toggleEmailFlag(v: Verification) {
    const msg = v.email_sent ? 'Mark as NOT sent?' : 'Mark as sent?'
    if (!confirm(msg)) return
    router.post(route('admin.drivers.employment-verification.toggle-email-flag', v.id), {}, { preserveScroll: true })
}

function markVerified(id: number) {
    if (!confirm('Mark this verification as Verified?')) return
    router.post(route('admin.drivers.employment-verification.mark-verified', id), {}, { preserveScroll: true })
}

function markRejected(id: number) {
    if (!confirm('Mark this verification as Rejected?')) return
    router.post(route('admin.drivers.employment-verification.mark-rejected', id), {}, { preserveScroll: true })
}

const statusBadge = (status: string) => {
    const map: Record<string, string> = {
        verified: 'bg-emerald-100 text-emerald-700',
        rejected: 'bg-red-100 text-red-700',
        pending:  'bg-amber-100 text-amber-700',
    }
    return map[status] ?? 'bg-slate-100 text-slate-600'
}

const statusIcon = (status: string) => {
    const map: Record<string, string> = {
        verified: 'CheckCircle',
        rejected: 'XCircle',
        pending:  'Clock',
    }
    return map[status] ?? 'Clock'
}
</script>

<template>
    <Head title="Employment Verifications" />

    <div class="p-5 sm:p-8 max-w-screen-2xl mx-auto">
        <!-- Header -->
        <div class="box box--stacked p-8 mb-8">
            <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                        <Lucide icon="FileCheck" class="w-8 h-8 text-primary" />
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-slate-800 mb-1">Employment Verifications</h1>
                        <p class="text-slate-500 text-sm">Manage and track employment verification requests</p>
                    </div>
                </div>
                <Link
                    :href="route('admin.drivers.index')"
                    class="inline-flex items-center gap-2 bg-white border border-slate-300 text-slate-700 px-4 py-2 rounded-lg hover:bg-slate-50 transition-colors font-medium text-sm"
                >
                    <Lucide icon="ArrowLeft" class="w-4 h-4" />
                    Back to Drivers
                </Link>
            </div>
            <div class="mt-5 flex justify-end">
                <Link
                    :href="route('admin.drivers.employment-verification.new')"
                    class="inline-flex items-center gap-2 bg-primary text-white px-4 py-2 rounded-lg hover:bg-primary/90 transition-colors font-medium text-sm"
                >
                    <Lucide icon="Plus" class="w-4 h-4" />
                    New Verification
                </Link>
            </div>
        </div>

        <!-- Filters -->
        <div class="box box--stacked p-6 mb-6">
            <div class="flex items-center gap-3 mb-4">
                <Lucide icon="Filter" class="w-5 h-5 text-primary" />
                <h2 class="text-lg font-semibold text-slate-800">Filters</h2>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Verification Status</label>
                    <select v-model="statusFilter" class="w-full text-sm border-slate-200 shadow-sm rounded-lg py-2.5 px-3 border focus:ring-primary focus:border-primary">
                        <option value="">All Statuses</option>
                        <option value="verified">Verified</option>
                        <option value="rejected">Rejected</option>
                        <option value="pending">Pending</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Driver</label>
                    <select v-model="driverFilter" class="w-full text-sm border-slate-200 shadow-sm rounded-lg py-2.5 px-3 border focus:ring-primary focus:border-primary">
                        <option value="">All Drivers</option>
                        <option v-for="d in drivers" :key="d.id" :value="String(d.id)">{{ d.name }}</option>
                    </select>
                </div>
                <div class="flex gap-2">
                    <button @click="applyFilters" class="inline-flex items-center gap-2 bg-primary text-white px-4 py-2 rounded-lg hover:bg-primary/90 transition-colors text-sm font-medium">
                        <Lucide icon="Search" class="w-4 h-4" /> Apply
                    </button>
                    <button @click="clearFilters" class="inline-flex items-center gap-2 bg-white border border-slate-300 text-slate-700 px-4 py-2 rounded-lg hover:bg-slate-50 transition-colors text-sm font-medium">
                        <Lucide icon="X" class="w-4 h-4" /> Clear
                    </button>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="box box--stacked">
            <div class="p-6 border-b border-slate-200/60 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <Lucide icon="List" class="w-5 h-5 text-primary" />
                    <h2 class="text-lg font-semibold text-slate-800">Verification Requests</h2>
                </div>
                <span class="bg-primary/10 text-primary text-xs font-semibold px-3 py-1.5 rounded-full">
                    {{ verifications.total }} Total
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200/60">
                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Driver</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Company</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Email</th>
                            <th class="px-6 py-4 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Email Status</th>
                            <th class="px-6 py-4 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Verification</th>
                            <th class="px-6 py-4 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Attempts</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-4 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200/60">
                        <tr v-for="v in verifications.data" :key="v.id" class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-6 py-4">
                                <Link
                                    :href="route('admin.drivers.show', v.driver_id)"
                                    class="font-medium text-primary hover:text-primary/80 transition-colors text-sm"
                                >
                                    {{ v.driver_name }}
                                </Link>
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-700">{{ v.company_name }}</td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-1.5 text-sm text-slate-700">
                                    <Lucide icon="Mail" class="w-3 h-3 text-slate-400 flex-shrink-0" />
                                    <span class="truncate max-w-[180px]">{{ v.email }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span
                                    class="inline-flex items-center gap-1 text-xs font-semibold px-2.5 py-1 rounded"
                                    :class="v.email_sent ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'"
                                >
                                    <span class="w-1.5 h-1.5 rounded-full inline-block" :class="v.email_sent ? 'bg-emerald-500' : 'bg-amber-500'" />
                                    {{ v.email_sent ? 'Sent' : 'Not Sent' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span
                                    class="inline-flex items-center gap-1 text-xs font-semibold px-2.5 py-1 rounded capitalize"
                                    :class="statusBadge(v.verification_status)"
                                >
                                    <Lucide :icon="statusIcon(v.verification_status)" class="w-3 h-3" />
                                    {{ v.verification_status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="text-sm font-medium" :class="v.attempt_count >= 3 ? 'text-red-600' : 'text-slate-700'">
                                    {{ v.attempt_count }}/3
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-600">{{ v.updated_at }}</td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-center gap-1.5 flex-wrap">
                                    <!-- View details -->
                                    <Link
                                        :href="route('admin.drivers.employment-verification.show', v.id)"
                                        title="View details"
                                        class="p-1.5 rounded-lg border border-primary/30 text-primary hover:bg-primary/10 transition-colors"
                                    >
                                        <Lucide icon="Eye" class="w-4 h-4" />
                                    </Link>

                                    <!-- Resend email -->
                                    <button
                                        @click="resendEmail(v.id)"
                                        :disabled="v.attempt_count >= 3"
                                        title="Resend verification email"
                                        class="p-1.5 rounded-lg border border-blue-200 text-blue-600 hover:bg-blue-50 transition-colors disabled:opacity-40 disabled:cursor-not-allowed"
                                    >
                                        <Lucide icon="Mail" class="w-4 h-4" />
                                    </button>

                                    <!-- Toggle email flag -->
                                    <button
                                        @click="toggleEmailFlag(v)"
                                        :title="v.email_sent ? 'Mark as not sent' : 'Mark as sent'"
                                        class="p-1.5 rounded-lg border transition-colors"
                                        :class="v.email_sent
                                            ? 'border-slate-200 text-slate-500 hover:bg-slate-50'
                                            : 'border-emerald-200 text-emerald-600 hover:bg-emerald-50'"
                                    >
                                        <Lucide :icon="v.email_sent ? 'X' : 'Check'" class="w-4 h-4" />
                                    </button>

                                    <!-- Mark verified -->
                                    <button
                                        v-if="v.verification_status !== 'verified'"
                                        @click="markVerified(v.id)"
                                        title="Mark as verified"
                                        class="p-1.5 rounded-lg border border-emerald-200 text-emerald-600 hover:bg-emerald-50 transition-colors"
                                    >
                                        <Lucide icon="CheckCircle" class="w-4 h-4" />
                                    </button>

                                    <!-- Mark rejected -->
                                    <button
                                        v-if="v.verification_status !== 'rejected'"
                                        @click="markRejected(v.id)"
                                        title="Mark as rejected"
                                        class="p-1.5 rounded-lg border border-red-200 text-red-600 hover:bg-red-50 transition-colors"
                                    >
                                        <Lucide icon="XCircle" class="w-4 h-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="verifications.data.length === 0">
                            <td colspan="8" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <Lucide icon="FileX" class="w-12 h-12 text-slate-300" />
                                    <p class="text-slate-500 font-medium">No employment verifications found</p>
                                    <p class="text-sm text-slate-400">Try adjusting your filters</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="verifications.last_page > 1" class="p-6 border-t border-slate-200/60 flex flex-wrap items-center gap-1">
                <template v-for="link in verifications.links" :key="link.label">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        class="px-3 py-1.5 text-sm rounded-md border transition-colors"
                        :class="link.active
                            ? 'bg-primary text-white border-primary'
                            : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50'"
                        v-html="link.label"
                        preserve-scroll
                    />
                    <span
                        v-else
                        class="px-3 py-1.5 text-sm rounded-md border bg-white text-slate-300 border-slate-200 cursor-default"
                        v-html="link.label"
                    />
                </template>
            </div>
        </div>
    </div>
</template>
