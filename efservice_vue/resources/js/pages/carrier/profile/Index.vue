<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import { Dialog } from '@/components/Base/Headless'
import RazeLayout from '@/layouts/RazeLayout.vue'

defineOptions({ layout: RazeLayout })

declare function route(name: string, params?: any): string

interface CarrierInfo {
    id: number
    name: string
    address: string | null
    state: string | null
    zipcode: string | null
    ein_number: string | null
    dot_number: string | null
    mc_number: string | null
    state_dot: string | null
    ifta_account: string | null
    phone: string | null
    status: number
    status_name: string
    logo_url: string | null
    safety_url: string | null
    safety_image_url: string | null
    referrer_token: string | null
    referral_url: string
    created_at: string | null
    updated_at: string | null
}

interface MembershipInfo {
    id: number
    name: string
    description: string | null
    price: number | null
    max_drivers: number | null
    max_vehicles: number | null
    max_users: number | null
}

interface MembershipOption {
    id: number
    name: string
    description: string | null
    price: number | null
    max_drivers: number | null
    max_vehicles: number | null
    max_users: number | null
}

interface PendingDocument {
    id: number
    name: string
    status: number
    status_name: string
    updated_at: string | null
}

interface TeamMember {
    id: number
    name: string
    email: string | null
    phone: string | null
    job_position: string
    status: number
    status_name: string
    profile_photo_url: string | null
}

interface ActivityItem {
    id: string
    type: string
    icon: string
    tone: string
    title: string
    description: string
    time: string | null
}

interface Props {
    carrier: CarrierInfo
    membership: MembershipInfo | null
    availableMemberships: MembershipOption[]
    stats: {
        drivers_total: number
        drivers_active: number
        drivers_inactive: number
        vehicles_total: number
        vehicles_active: number
        licenses: { total: number; expired: number; expiring_soon: number; valid: number }
        medical: { total: number; expired: number; expiring_soon: number; valid: number }
        accidents: { total: number; this_month: number; this_year: number }
        documents: { total: number; pending: number; approved: number; rejected: number; in_process: number }
        documents_required: number
        documents_progress: number
    }
    membershipLimits: {
        drivers: { current: number; max: number; percentage: number }
        vehicles: { current: number; max: number; percentage: number }
        users: { current: number; max: number; percentage: number }
    }
    pendingDocuments: PendingDocument[]
    teamMembers: TeamMember[]
    recentActivity: ActivityItem[]
    bankingDetails: {
        status: string
        account_holder_name: string | null
        country_code: string | null
        updated_at: string | null
    } | null
}

const props = defineProps<Props>()

const upgradeModalOpen = ref(false)
const referralCopied = ref(false)

const companyLocation = computed(() =>
    [props.carrier.address, props.carrier.state, props.carrier.zipcode].filter(Boolean).join(', ')
)

const statsCards = computed(() => [
    {
        title: 'Drivers',
        value: props.stats.drivers_total,
        detail: `${props.stats.drivers_active} active`,
        extra: props.membership ? `/ ${props.membership.max_drivers ?? 'No limit'} max` : '',
        icon: 'Users',
        iconClass: 'bg-primary/10 text-primary',
        barClass: 'bg-primary',
        progress: props.membershipLimits.drivers.percentage,
    },
    {
        title: 'Vehicles',
        value: props.stats.vehicles_total,
        detail: `${props.stats.vehicles_active} active`,
        extra: props.membership ? `/ ${props.membership.max_vehicles ?? 'No limit'} max` : '',
        icon: 'Truck',
        iconClass: 'bg-info/10 text-info',
        barClass: 'bg-info',
        progress: props.membershipLimits.vehicles.percentage,
    },
    {
        title: 'Documents',
        value: props.stats.documents.approved,
        detail: `${props.stats.documents.pending} pending`,
        extra: `/ ${props.stats.documents_required} required`,
        icon: 'FileCheck',
        iconClass: 'bg-success/10 text-success',
        barClass: 'bg-success',
        progress: props.stats.documents_progress,
    },
    {
        title: 'Licenses',
        value: props.stats.licenses.total,
        detail: props.stats.licenses.expired > 0
            ? `${props.stats.licenses.expired} expired`
            : props.stats.licenses.expiring_soon > 0
                ? `${props.stats.licenses.expiring_soon} expiring soon`
                : 'All valid',
        extra: '',
        icon: 'CreditCard',
        iconClass: props.stats.licenses.expired > 0 ? 'bg-danger/10 text-danger' : 'bg-warning/10 text-warning',
        barClass: props.stats.licenses.expired > 0 ? 'bg-danger' : 'bg-warning',
        progress: props.stats.licenses.total > 0
            ? Math.round((props.stats.licenses.valid / props.stats.licenses.total) * 100)
            : 0,
    },
    {
        title: 'Medical',
        value: props.stats.medical.total,
        detail: props.stats.medical.expired > 0
            ? `${props.stats.medical.expired} expired`
            : props.stats.medical.expiring_soon > 0
                ? `${props.stats.medical.expiring_soon} expiring soon`
                : 'All valid',
        extra: '',
        icon: 'Heart',
        iconClass: props.stats.medical.expired > 0 ? 'bg-danger/10 text-danger' : 'bg-success/10 text-success',
        barClass: props.stats.medical.expired > 0 ? 'bg-danger' : 'bg-success',
        progress: props.stats.medical.total > 0
            ? Math.round((props.stats.medical.valid / props.stats.medical.total) * 100)
            : 0,
    },
    {
        title: 'Accidents',
        value: props.stats.accidents.total,
        detail: `This month: ${props.stats.accidents.this_month}`,
        extra: `YTD: ${props.stats.accidents.this_year}`,
        icon: 'AlertTriangle',
        iconClass: props.stats.accidents.this_month > 0 ? 'bg-danger/10 text-danger' : 'bg-slate-100 text-slate-500',
        barClass: props.stats.accidents.this_month > 0 ? 'bg-danger' : 'bg-slate-300',
        progress: props.stats.accidents.total > 0
            ? Math.min(100, Math.round((props.stats.accidents.this_year / props.stats.accidents.total) * 100))
            : 0,
    },
])

function statusBadgeClass(status: number) {
    if (status === 1) return 'bg-success/10 text-success'
    if (status === 0 || status === 4) return 'bg-danger/10 text-danger'
    return 'bg-warning/10 text-warning'
}

function toneClasses(tone: string) {
    const map: Record<string, string> = {
        primary: 'bg-primary/10 text-primary',
        success: 'bg-success/10 text-success',
        warning: 'bg-warning/10 text-warning',
        danger: 'bg-danger/10 text-danger',
        info: 'bg-info/10 text-info',
    }
    return map[tone] ?? 'bg-slate-100 text-slate-500'
}

function documentStatusClass(status: number) {
    const map: Record<number, string> = {
        0: 'bg-warning/10 text-warning',
        1: 'bg-success/10 text-success',
        2: 'bg-danger/10 text-danger',
        3: 'bg-info/10 text-info',
    }
    return map[status] ?? 'bg-slate-100 text-slate-500'
}

function bankingStatusClass(status: string) {
    const normalized = status?.toLowerCase?.() ?? ''
    if (normalized === 'approved') return 'bg-success/10 text-success'
    if (normalized === 'rejected') return 'bg-danger/10 text-danger'
    return 'bg-warning/10 text-warning'
}

function formatCurrency(value: number | null) {
    if (value === null || value === undefined) return 'Custom'
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', minimumFractionDigits: 2 }).format(value)
}

async function copyReferralUrl() {
    try {
        await navigator.clipboard.writeText(props.carrier.referral_url)
        referralCopied.value = true
        window.setTimeout(() => { referralCopied.value = false }, 1800)
    } catch {
        referralCopied.value = false
    }
}

function memberInitials(name: string) {
    return name.split(' ').filter(Boolean).slice(0, 2).map(part => part[0]?.toUpperCase() ?? '').join('')
}
</script>

<template>
    <Head title="Carrier Profile" />

    <div class="grid grid-cols-12 gap-6">
        <div class="col-span-12">
            <div class="box box--stacked overflow-hidden">
                <div class="relative h-56 bg-gradient-to-r from-primary via-primary to-primary/80">
                    <div class="absolute inset-0 opacity-20 bg-[radial-gradient(circle_at_top_right,_white,_transparent_35%),radial-gradient(circle_at_bottom_left,_white,_transparent_30%)]" />

                    <div class="absolute right-5 top-5">
                        <Link :href="route('carrier.profile.edit')">
                            <Button variant="primary" class="gap-2 border-white/20 bg-white/15 text-white hover:bg-white/20">
                                <Lucide icon="Edit" class="h-4 w-4" />
                                Edit Profile
                            </Button>
                        </Link>
                    </div>

                    <div class="absolute inset-x-0 -bottom-16 flex justify-center">
                        <div class="relative">
                            <div class="flex h-32 w-32 items-center justify-center overflow-hidden rounded-full border-[6px] border-white bg-white shadow-xl">
                                <img v-if="carrier.logo_url" :src="carrier.logo_url" :alt="carrier.name" class="h-full w-full object-cover">
                                <div v-else class="flex h-full w-full items-center justify-center bg-primary/10">
                                    <Lucide icon="Building2" class="h-12 w-12 text-primary" />
                                </div>
                            </div>
                            <div class="absolute bottom-2 right-2 h-5 w-5 rounded-full border-2 border-white" :class="carrier.status === 1 ? 'bg-success' : carrier.status === 0 || carrier.status === 4 ? 'bg-danger' : 'bg-warning'" />
                        </div>
                    </div>
                </div>

                <div class="bg-slate-50 px-6 pb-8 pt-20">
                    <div class="text-center">
                        <div class="flex items-center justify-center gap-2 text-2xl font-bold text-slate-800">
                            {{ carrier.name }}
                            <Lucide v-if="carrier.status === 1" icon="BadgeCheck" class="h-6 w-6 text-success" />
                        </div>
                        <p class="mt-1 text-sm text-slate-500">{{ companyLocation || 'No address on file' }}</p>

                        <div class="mt-5 flex flex-wrap items-center justify-center gap-3">
                            <div class="rounded-full border border-slate-100 bg-white px-3 py-1.5 text-sm shadow-sm">
                                <span class="text-slate-500">DOT:</span>
                                <span class="ml-1 font-semibold text-slate-800">{{ carrier.dot_number || 'N/A' }}</span>
                            </div>
                            <div v-if="carrier.mc_number" class="rounded-full border border-slate-100 bg-white px-3 py-1.5 text-sm shadow-sm">
                                <span class="text-slate-500">MC:</span>
                                <span class="ml-1 font-semibold text-slate-800">{{ carrier.mc_number }}</span>
                            </div>
                            <div class="rounded-full border border-slate-100 bg-white px-3 py-1.5 text-sm shadow-sm">
                                <span class="font-semibold text-slate-800">{{ carrier.phone || 'Phone not set' }}</span>
                            </div>
                            <div class="rounded-full px-3 py-1.5 text-sm font-medium" :class="statusBadgeClass(carrier.status)">
                                {{ carrier.status_name }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12">
            <div class="grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-6">
                <div v-for="card in statsCards" :key="card.title" class="box box--stacked p-5">
                    <div class="flex items-center gap-3">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl" :class="card.iconClass">
                            <Lucide :icon="card.icon" class="h-6 w-6" />
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-slate-800">{{ card.value }}</div>
                            <div class="text-xs text-slate-500">{{ card.title }}</div>
                        </div>
                    </div>
                    <div class="mt-3 flex items-center justify-between gap-2 text-xs">
                        <span class="text-slate-600">{{ card.detail }}</span>
                        <span class="text-slate-400">{{ card.extra }}</span>
                    </div>
                    <div class="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-slate-100">
                        <div class="h-full rounded-full transition-all" :class="card.barClass" :style="{ width: `${Math.min(100, card.progress)}%` }" />
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12 space-y-6 xl:col-span-8">
            <div class="box box--stacked p-6">
                <div class="mb-5 flex items-center justify-between border-b border-slate-200/60 pb-4">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10">
                            <Lucide icon="Building2" class="h-5 w-5 text-primary" />
                        </div>
                        <h2 class="text-base font-semibold text-slate-800">Company Information</h2>
                    </div>
                    <Link :href="route('carrier.profile.edit')" class="text-sm font-medium text-primary hover:text-primary/80">Edit</Link>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <div class="rounded-xl border border-slate-100 bg-slate-50/70 p-4">
                        <p class="text-xs font-medium uppercase tracking-[0.2em] text-slate-400">Company Name</p>
                        <p class="mt-2 font-semibold text-slate-700">{{ carrier.name }}</p>
                    </div>
                    <div class="rounded-xl border border-slate-100 bg-slate-50/70 p-4">
                        <p class="text-xs font-medium uppercase tracking-[0.2em] text-slate-400">EIN Number</p>
                        <p class="mt-2 font-semibold text-slate-700">{{ carrier.ein_number || 'N/A' }}</p>
                    </div>
                    <div class="rounded-xl border border-slate-100 bg-slate-50/70 p-4">
                        <p class="text-xs font-medium uppercase tracking-[0.2em] text-slate-400">DOT Number</p>
                        <p class="mt-2 font-semibold text-slate-700">{{ carrier.dot_number || 'N/A' }}</p>
                    </div>
                    <div class="rounded-xl border border-slate-100 bg-slate-50/70 p-4">
                        <p class="text-xs font-medium uppercase tracking-[0.2em] text-slate-400">MC Number</p>
                        <p class="mt-2 font-semibold text-slate-700">{{ carrier.mc_number || 'N/A' }}</p>
                    </div>
                    <div class="rounded-xl border border-slate-100 bg-slate-50/70 p-4">
                        <p class="text-xs font-medium uppercase tracking-[0.2em] text-slate-400">State DOT</p>
                        <p class="mt-2 font-semibold text-slate-700">{{ carrier.state_dot || 'N/A' }}</p>
                    </div>
                    <div class="rounded-xl border border-slate-100 bg-slate-50/70 p-4">
                        <p class="text-xs font-medium uppercase tracking-[0.2em] text-slate-400">IFTA Account</p>
                        <p class="mt-2 font-semibold text-slate-700">{{ carrier.ifta_account || 'N/A' }}</p>
                    </div>
                    <div class="rounded-xl border border-slate-100 bg-slate-50/70 p-4 md:col-span-2 lg:col-span-3">
                        <p class="text-xs font-medium uppercase tracking-[0.2em] text-slate-400">Address</p>
                        <p class="mt-2 font-semibold text-slate-700">{{ companyLocation || 'N/A' }}</p>
                    </div>
                </div>
            </div>

            <div class="box box--stacked p-6">
                <div class="mb-5 flex items-center justify-between border-b border-slate-200/60 pb-4">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-info/10">
                            <Lucide icon="Users" class="h-5 w-5 text-info" />
                        </div>
                        <div>
                            <h2 class="text-base font-semibold text-slate-800">Team Members</h2>
                            <p class="text-xs text-slate-500">{{ teamMembers.length }} active records linked to this carrier</p>
                        </div>
                    </div>
                    <div class="rounded-full bg-primary/10 px-3 py-1 text-xs font-semibold text-primary">
                        {{ membershipLimits.users.current }} / {{ membershipLimits.users.max || 'No limit' }}
                    </div>
                </div>

                <div class="space-y-3">
                    <div v-for="member in teamMembers" :key="member.id" class="flex items-center justify-between rounded-xl bg-slate-50/70 px-4 py-3 transition-colors hover:bg-slate-100/80">
                        <div class="flex items-center gap-3">
                            <div class="flex h-11 w-11 items-center justify-center overflow-hidden rounded-full border border-white bg-primary/10 shadow-sm">
                                <img v-if="member.profile_photo_url" :src="member.profile_photo_url" :alt="member.name" class="h-full w-full object-cover">
                                <span v-else class="text-sm font-semibold text-primary">{{ memberInitials(member.name) }}</span>
                            </div>
                            <div>
                                <p class="font-medium text-slate-700">{{ member.name }}</p>
                                <p class="text-xs text-slate-500">{{ member.email || member.phone || 'No contact info' }}</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <span class="rounded-full bg-primary/10 px-2.5 py-1 text-xs font-medium text-primary">{{ member.job_position }}</span>
                            <span class="h-2.5 w-2.5 rounded-full" :class="member.status === 1 ? 'bg-success' : member.status === 0 ? 'bg-danger' : 'bg-warning'" />
                        </div>
                    </div>

                    <div v-if="teamMembers.length === 0" class="flex flex-col items-center rounded-xl border border-dashed border-slate-200 px-6 py-10 text-center">
                        <Lucide icon="Users" class="h-10 w-10 text-slate-300" />
                        <p class="mt-3 font-medium text-slate-500">No team members linked yet</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12 space-y-6 xl:col-span-4">
            <div class="box box--stacked p-6">
                <div class="mb-5 flex items-center gap-3 border-b border-slate-200/60 pb-4">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-slate-100">
                        <Lucide icon="Activity" class="h-5 w-5 text-slate-500" />
                    </div>
                    <h2 class="text-base font-semibold text-slate-800">Recent Activity</h2>
                </div>

                <div class="space-y-4">
                    <div v-for="item in recentActivity" :key="item.id" class="flex gap-3">
                        <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full" :class="toneClasses(item.tone)">
                            <Lucide :icon="item.icon" class="h-4 w-4" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-slate-700">{{ item.title }}</p>
                            <p class="truncate text-xs text-slate-500">{{ item.description }}</p>
                            <p class="mt-1 text-xs text-slate-400">{{ item.time || 'Recently' }}</p>
                        </div>
                    </div>

                    <div v-if="recentActivity.length === 0" class="flex flex-col items-center rounded-xl border border-dashed border-slate-200 px-6 py-10 text-center">
                        <Lucide icon="Activity" class="h-10 w-10 text-slate-300" />
                        <p class="mt-3 font-medium text-slate-500">No recent activity</p>
                    </div>
                </div>
            </div>

            <div v-if="carrier.safety_url" class="box box--stacked overflow-hidden rounded-xl">
                <div class="relative h-48 bg-gradient-to-br from-primary via-primary to-primary/80">
                    <img v-if="carrier.safety_image_url" :src="carrier.safety_image_url" alt="Safety Data System" class="h-full w-full object-cover">
                    <div v-else class="flex h-full items-center justify-center">
                        <Lucide icon="Shield" class="h-16 w-16 text-white/35" />
                    </div>
                    <div class="absolute inset-0 bg-gradient-to-t from-black/35 to-transparent" />
                </div>

                <div class="bg-white p-5">
                    <h3 class="text-lg font-bold text-slate-800">{{ carrier.name }}</h3>
                    <p class="mt-1 flex items-center gap-2 text-xs text-slate-500">
                        <Lucide icon="Shield" class="h-3.5 w-3.5" />
                        Safety Data System
                    </p>
                    <a :href="carrier.safety_url" target="_blank" class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-lg bg-primary px-4 py-2.5 font-medium text-white transition-colors hover:bg-primary/90">
                        <Lucide icon="ExternalLink" class="h-4 w-4" />
                        Consulting Safety
                    </a>
                </div>
            </div>

            <div class="box box--stacked border-2 border-primary/15 bg-gradient-to-br from-primary/5 to-transparent p-5">
                <div class="mb-4 flex items-center gap-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-primary text-white">
                        <Lucide icon="Crown" class="h-6 w-6" />
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-slate-800">{{ membership?.name || 'No Plan Assigned' }}</h3>
                        <p class="text-xs text-slate-500">Current Plan</p>
                    </div>
                </div>

                <div class="space-y-3">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-slate-500">Drivers</span>
                        <span class="font-semibold text-slate-800">{{ membershipLimits.drivers.current }} / {{ membership?.max_drivers ?? 'No limit' }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-slate-500">Vehicles</span>
                        <span class="font-semibold text-slate-800">{{ membershipLimits.vehicles.current }} / {{ membership?.max_vehicles ?? 'No limit' }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-slate-500">Users</span>
                        <span class="font-semibold text-slate-800">{{ membershipLimits.users.current }} / {{ membership?.max_users ?? 'No limit' }}</span>
                    </div>
                    <div v-if="membership?.price !== null && membership?.price !== undefined" class="border-t border-slate-200 pt-3">
                        <div class="flex items-end justify-between">
                            <span class="text-sm text-slate-500">Monthly Cost</span>
                            <span class="text-2xl font-bold text-primary">{{ formatCurrency(membership.price) }}</span>
                        </div>
                    </div>
                </div>

                <Button v-if="availableMemberships.length > 0" variant="primary" class="mt-5 w-full justify-center gap-2" @click="upgradeModalOpen = true">
                    <Lucide icon="Zap" class="h-4 w-4" />
                    Explore Plans
                </Button>
            </div>

            <div class="box box--stacked p-5">
                <div class="mb-4 flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-success/10">
                        <Lucide icon="Share2" class="h-5 w-5 text-success" />
                    </div>
                    <div>
                        <h3 class="text-base font-medium text-slate-800">Driver Registration Link</h3>
                        <p class="text-xs text-slate-500">Share this URL with new drivers.</p>
                    </div>
                </div>

                <div class="rounded-xl border border-slate-200/70 bg-slate-50 p-3">
                    <label class="mb-2 block text-xs font-medium text-slate-500">Registration URL</label>
                    <div class="flex flex-col gap-2 sm:flex-row">
                        <input :value="carrier.referral_url" readonly class="form-control flex-1 bg-white text-xs font-mono">
                        <Button variant="primary" class="gap-2 sm:min-w-28" @click="copyReferralUrl">
                            <Lucide :icon="referralCopied ? 'Check' : 'Copy'" class="h-4 w-4" />
                            {{ referralCopied ? 'Copied' : 'Copy' }}
                        </Button>
                    </div>
                </div>
            </div>

            <div class="box box--stacked p-5">
                <div class="mb-4 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-warning/10">
                            <Lucide icon="FolderOpen" class="h-5 w-5 text-warning" />
                        </div>
                        <h3 class="text-base font-medium text-slate-800">Documents</h3>
                    </div>
                    <Link :href="route('carrier.documents.index')" class="text-sm text-primary hover:text-primary/80">View All</Link>
                </div>

                <div class="mb-4">
                    <div class="mb-2 flex items-center justify-between text-sm">
                        <span class="text-slate-500">Completion</span>
                        <span class="font-semibold text-slate-800">{{ stats.documents_progress }}%</span>
                    </div>
                    <div class="h-2 w-full overflow-hidden rounded-full bg-slate-100">
                        <div class="h-full rounded-full transition-all" :class="stats.documents_progress >= 100 ? 'bg-success' : stats.documents_progress >= 50 ? 'bg-warning' : 'bg-danger'" :style="{ width: `${Math.min(100, stats.documents_progress)}%` }" />
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-2 text-center">
                    <div class="rounded-lg bg-success/10 p-2">
                        <div class="text-lg font-bold text-success">{{ stats.documents.approved }}</div>
                        <div class="text-xs text-slate-500">Approved</div>
                    </div>
                    <div class="rounded-lg bg-warning/10 p-2">
                        <div class="text-lg font-bold text-warning">{{ stats.documents.pending }}</div>
                        <div class="text-xs text-slate-500">Pending</div>
                    </div>
                    <div class="rounded-lg bg-danger/10 p-2">
                        <div class="text-lg font-bold text-danger">{{ stats.documents.rejected }}</div>
                        <div class="text-xs text-slate-500">Rejected</div>
                    </div>
                </div>
            </div>

            <div v-if="bankingDetails" class="box box--stacked p-5">
                <div class="mb-4 flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-info/10">
                        <Lucide icon="Landmark" class="h-5 w-5 text-info" />
                    </div>
                    <h3 class="text-base font-medium text-slate-800">Banking Status</h3>
                </div>

                <div class="space-y-3 text-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">Status</span>
                        <span class="rounded-full px-2.5 py-1 text-xs font-medium capitalize" :class="bankingStatusClass(bankingDetails.status)">
                            {{ bankingDetails.status }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">Account Holder</span>
                        <span class="font-medium text-slate-700">{{ bankingDetails.account_holder_name || 'N/A' }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">Country</span>
                        <span class="font-medium text-slate-700">{{ bankingDetails.country_code || 'N/A' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <Dialog :open="upgradeModalOpen" @close="upgradeModalOpen = false" size="xl">
        <Dialog.Panel class="flex max-h-[90vh] w-[95vw] max-w-[980px] flex-col overflow-hidden sm:w-[980px]">
            <div class="border-b border-slate-200 bg-white px-6 py-4">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-xl font-semibold text-slate-800">Explore Membership Plans</h3>
                        <p class="mt-1 text-sm text-slate-500">Compare current limits with the plans available in EF Services.</p>
                    </div>
                    <button class="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-600" @click="upgradeModalOpen = false">
                        <Lucide icon="X" class="h-5 w-5" />
                    </button>
                </div>
            </div>

            <div class="overflow-y-auto bg-slate-50 px-6 py-6">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <div v-for="plan in availableMemberships" :key="plan.id" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h4 class="text-lg font-semibold text-slate-800">{{ plan.name }}</h4>
                                <p class="mt-1 text-sm text-slate-500">{{ plan.description || 'Membership plan with expanded capacity.' }}</p>
                            </div>
                            <div class="rounded-full bg-primary/10 px-3 py-1 text-xs font-semibold text-primary">
                                {{ formatCurrency(plan.price) }}
                            </div>
                        </div>

                        <div class="mt-5 space-y-3 text-sm">
                            <div class="flex items-center justify-between">
                                <span class="text-slate-500">Drivers</span>
                                <span class="font-semibold text-slate-800">{{ plan.max_drivers ?? 'No limit' }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-slate-500">Vehicles</span>
                                <span class="font-semibold text-slate-800">{{ plan.max_vehicles ?? 'No limit' }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-slate-500">Users</span>
                                <span class="font-semibold text-slate-800">{{ plan.max_users ?? 'No limit' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Dialog.Panel>
    </Dialog>
</template>
