<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import TomSelect from '@/components/Base/TomSelect/TomSelect.vue'
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
        verified: 'bg-success/10 text-success',
        rejected: 'bg-danger/10 text-danger',
        pending:  'bg-warning/10 text-warning',
    }
    return map[status] ?? 'bg-slate-100 text-slate-500'
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
        <div class="box box--stacked p-6 mb-6 border border-primary/10 bg-gradient-to-r from-primary/[0.04] via-white to-white">
            <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-primary/10 rounded-xl border border-primary/20 shadow-sm">
                        <Lucide icon="FileCheck" class="w-7 h-7 text-primary" />
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-slate-800">Employment Verifications</h1>
                        <p class="text-slate-500 text-sm mt-0.5">Manage and track employment verification requests</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <Link :href="route('admin.drivers.index')">
                        <Button variant="outline-secondary" size="sm" class="inline-flex items-center gap-2">
                            <Lucide icon="ArrowLeft" class="w-4 h-4" />
                            Back to Drivers
                        </Button>
                    </Link>
                    <Link :href="route('admin.drivers.employment-verification.new')">
                        <Button variant="primary" size="sm" class="inline-flex items-center gap-2 shadow-sm">
                            <Lucide icon="Plus" class="w-4 h-4" />
                            New Verification
                        </Button>
                    </Link>
                </div>
            </div>
        </div>

        <!-- Stats summary -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <div class="box box--stacked p-4 flex items-center gap-3 border border-primary/10 bg-primary/[0.04]">
                <div class="p-2.5 bg-primary/10 rounded-lg border border-primary/10">
                    <Lucide icon="FileCheck" class="w-5 h-5 text-primary" />
                </div>
                <div>
                    <p class="text-xs text-slate-500">Total</p>
                    <p class="text-xl font-bold text-primary">{{ verifications.total }}</p>
                </div>
            </div>
            <div class="box box--stacked p-4 flex items-center gap-3 border border-success/10 bg-success/[0.04]">
                <div class="p-2.5 bg-success/10 rounded-lg border border-success/10">
                    <Lucide icon="CheckCircle" class="w-5 h-5 text-success" />
                </div>
                <div>
                    <p class="text-xs text-slate-500">Verified</p>
                    <p class="text-xl font-bold text-success">
                        {{ verifications.data.filter(v => v.verification_status === 'verified').length }}
                    </p>
                </div>
            </div>
            <div class="box box--stacked p-4 flex items-center gap-3 border border-warning/10 bg-warning/[0.04]">
                <div class="p-2.5 bg-warning/10 rounded-lg border border-warning/10">
                    <Lucide icon="Clock" class="w-5 h-5 text-warning" />
                </div>
                <div>
                    <p class="text-xs text-slate-500">Pending</p>
                    <p class="text-xl font-bold text-warning">
                        {{ verifications.data.filter(v => v.verification_status === 'pending').length }}
                    </p>
                </div>
            </div>
            <div class="box box--stacked p-4 flex items-center gap-3 border border-danger/10 bg-danger/[0.04]">
                <div class="p-2.5 bg-danger/10 rounded-lg border border-danger/10">
                    <Lucide icon="XCircle" class="w-5 h-5 text-danger" />
                </div>
                <div>
                    <p class="text-xs text-slate-500">Rejected</p>
                    <p class="text-xl font-bold text-danger">
                        {{ verifications.data.filter(v => v.verification_status === 'rejected').length }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="box box--stacked p-5 mb-5 border border-primary/10">
            <div class="flex items-center gap-2 mb-4">
                <Lucide icon="Filter" class="w-4 h-4 text-primary" />
                <h2 class="text-sm font-semibold text-slate-700 uppercase tracking-wide">Filters</h2>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1.5">Verification Status</label>
                    <TomSelect v-model="statusFilter">
                        <option value="">All Statuses</option>
                        <option value="verified">Verified</option>
                        <option value="rejected">Rejected</option>
                        <option value="pending">Pending</option>
                    </TomSelect>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1.5">Driver</label>
                    <TomSelect v-model="driverFilter">
                        <option value="">All Drivers</option>
                        <option v-for="d in drivers" :key="d.id" :value="String(d.id)">{{ d.name }}</option>
                    </TomSelect>
                </div>
                <div class="flex gap-2">
                    <Button @click="applyFilters" variant="primary" size="sm" class="inline-flex items-center gap-2 shadow-sm">
                        <Lucide icon="Search" class="w-4 h-4" /> Apply
                    </Button>
                    <Button @click="clearFilters" variant="outline-secondary" size="sm" class="inline-flex items-center gap-2">
                        <Lucide icon="X" class="w-4 h-4" /> Clear
                    </Button>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="box box--stacked">
            <div class="p-5 border-b border-slate-200/60 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <Lucide icon="List" class="w-4 h-4 text-primary" />
                    <h2 class="text-sm font-semibold text-slate-700 uppercase tracking-wide">Verification Requests</h2>
                </div>
                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-primary/10 text-primary text-xs font-semibold">
                    {{ verifications.total }} total
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-slate-50/80">
                            <th class="px-5 py-3 text-xs font-medium uppercase tracking-wide text-slate-500">Driver</th>
                            <th class="px-5 py-3 text-xs font-medium uppercase tracking-wide text-slate-500">Company</th>
                            <th class="px-5 py-3 text-xs font-medium uppercase tracking-wide text-slate-500">Email</th>
                            <th class="px-5 py-3 text-center text-xs font-medium uppercase tracking-wide text-slate-500">Email Status</th>
                            <th class="px-5 py-3 text-center text-xs font-medium uppercase tracking-wide text-slate-500">Verification</th>
                            <th class="px-5 py-3 text-center text-xs font-medium uppercase tracking-wide text-slate-500">Attempts</th>
                            <th class="px-5 py-3 text-xs font-medium uppercase tracking-wide text-slate-500">Date</th>
                            <th class="px-5 py-3 text-center text-xs font-medium uppercase tracking-wide text-slate-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="v in verifications.data"
                            :key="v.id"
                            class="border-b border-slate-100 transition hover:bg-slate-50/50"
                        >
                            <td class="px-5 py-4 align-top">
                                <Link
                                    :href="route('admin.drivers.show', v.driver_id)"
                                    class="font-medium text-primary hover:text-primary/80 transition-colors"
                                >
                                    {{ v.driver_name }}
                                </Link>
                            </td>
                            <td class="px-5 py-4 align-top text-slate-700">{{ v.company_name }}</td>
                            <td class="px-5 py-4 align-top">
                                <div class="flex items-center gap-1.5 text-slate-600">
                                    <Lucide icon="Mail" class="w-3.5 h-3.5 text-slate-400 flex-shrink-0" />
                                    <span class="max-w-[220px] truncate text-sm">{{ v.email }}</span>
                                </div>
                            </td>
                            <td class="px-5 py-4 text-center align-top">
                                <span
                                    class="inline-flex items-center gap-1 text-xs font-medium px-2.5 py-1 rounded-full"
                                    :class="v.email_sent ? 'bg-success/10 text-success' : 'bg-warning/10 text-warning'"
                                >
                                    <span class="w-1.5 h-1.5 rounded-full" :class="v.email_sent ? 'bg-success' : 'bg-warning'" />
                                    {{ v.email_sent ? 'Sent' : 'Pending' }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-center align-top">
                                <span
                                    class="inline-flex items-center gap-1 text-xs font-medium px-2.5 py-1 rounded-full capitalize"
                                    :class="statusBadge(v.verification_status)"
                                >
                                    <Lucide :icon="statusIcon(v.verification_status)" class="w-3 h-3" />
                                    {{ v.verification_status }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-center align-top">
                                <span
                                    class="text-sm font-semibold"
                                    :class="v.attempt_count >= 3 ? 'text-danger' : 'text-slate-700'"
                                >
                                    {{ v.attempt_count }}/3
                                </span>
                            </td>
                            <td class="px-5 py-4 align-top text-sm text-slate-500 whitespace-nowrap">{{ v.updated_at }}</td>
                            <td class="px-5 py-4 align-top">
                                <div class="flex items-center justify-center gap-1.5">
                                    <Link
                                        :href="route('admin.drivers.employment-verification.show', v.id)"
                                        title="View details"
                                        class="btn btn-outline-primary btn-xs"
                                    >
                                        <Lucide icon="Eye" class="w-3.5 h-3.5" />
                                    </Link>

                                    <button
                                        @click="resendEmail(v.id)"
                                        :disabled="v.attempt_count >= 3"
                                        title="Resend verification email"
                                        class="btn btn-outline-secondary btn-xs disabled:opacity-40 disabled:cursor-not-allowed"
                                    >
                                        <Lucide icon="Mail" class="w-3.5 h-3.5" />
                                    </button>

                                    <button
                                        @click="toggleEmailFlag(v)"
                                        :title="v.email_sent ? 'Mark as not sent' : 'Mark as sent'"
                                        class="btn btn-xs"
                                        :class="v.email_sent ? 'btn-outline-secondary' : 'btn-outline-success'"
                                    >
                                        <Lucide :icon="v.email_sent ? 'X' : 'Check'" class="w-3.5 h-3.5" />
                                    </button>

                                    <button
                                        v-if="v.verification_status !== 'verified'"
                                        @click="markVerified(v.id)"
                                        title="Mark as verified"
                                        class="btn btn-outline-success btn-xs"
                                    >
                                        <Lucide icon="CheckCircle" class="w-3.5 h-3.5" />
                                    </button>

                                    <button
                                        v-if="v.verification_status !== 'rejected'"
                                        @click="markRejected(v.id)"
                                        title="Mark as rejected"
                                        class="btn btn-outline-danger btn-xs"
                                    >
                                        <Lucide icon="XCircle" class="w-3.5 h-3.5" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="verifications.data.length === 0">
                            <td colspan="8" class="px-5 py-16 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <div class="p-4 bg-slate-100 rounded-full">
                                        <Lucide icon="FileX" class="w-8 h-8 text-slate-400" />
                                    </div>
                                    <p class="text-slate-600 font-medium">No employment verifications found</p>
                                    <p class="text-sm text-slate-400">Try adjusting your filters</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="verifications.last_page > 1" class="p-5 border-t border-slate-200/60 flex flex-wrap items-center gap-1">
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
