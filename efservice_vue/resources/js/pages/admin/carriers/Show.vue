<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import Lucide from '@/components/Base/Lucide'
import Button from '@/components/Base/Button'

declare function route(name: string, params?: any): string
import { FormLabel, FormSelect } from '@/components/Base/Form'
import RazeLayout from '@/layouts/RazeLayout.vue'

interface Props {
    carrier: Record<string, any>
    userCarriers: any[]
    drivers: any[]
    vehicles: any[]
    documents: any[]
    pendingDocuments: any[]
    approvedDocuments: any[]
    rejectedDocuments: any[]
    missingDocumentTypes: any[]
    stats: Record<string, any>
    bankingDetails: Record<string, any> | null
    dotPolicy: { url: string; generated_at: string; file_name: string } | null
}

const props = defineProps<Props>()

const page = usePage()
const flash = computed(() => (page.props as any).flash ?? {})

const activeTab = ref('overview')
const referralCopied = ref(false)

const statusMap: Record<number, { label: string; color: string; bg: string }> = {
    0: { label: 'Inactive', color: 'text-danger', bg: 'bg-danger/10' },
    1: { label: 'Active', color: 'text-success', bg: 'bg-success/10' },
    2: { label: 'Pending', color: 'text-warning', bg: 'bg-warning/10' },
    3: { label: 'Pending Validation', color: 'text-info', bg: 'bg-info/10' },
    4: { label: 'Rejected', color: 'text-danger', bg: 'bg-danger/10' },
}

function getStatus(status: number) {
    return statusMap[status] ?? { label: 'Unknown', color: 'text-slate-500', bg: 'bg-slate-100' }
}

const bankingStatusMap: Record<string, { label: string; color: string; bg: string }> = {
    approved: { label: 'Approved', color: 'text-success', bg: 'bg-success/10' },
    pending: { label: 'Pending', color: 'text-warning', bg: 'bg-warning/10' },
    rejected: { label: 'Rejected', color: 'text-danger', bg: 'bg-danger/10' },
}

function getBankingStatus(status: string) {
    return bankingStatusMap[status] ?? { label: status, color: 'text-slate-500', bg: 'bg-slate-100' }
}

const rejectionReason = ref('')

function approveBanking() {
    if (confirm('Approve banking information and activate this carrier?')) {
        router.post(route('admin.carriers.banking.approve', props.carrier.slug))
    }
}

function rejectBanking() {
    if (!rejectionReason.value) {
        alert('Please provide a rejection reason.')
        return
    }
    router.post(route('admin.carriers.banking.reject', props.carrier.slug), {
        rejection_reason: rejectionReason.value,
    })
}

function updateDocumentStatus(documentId: number, status: number) {
    router.put(route('admin.carrier-documents.update-status', documentId), { status: Number(status) }, {
        preserveScroll: true,
    })
}

function formatDate(value?: string | null): string {
    if (!value) return '-'
    const d = new Date(value)
    if (isNaN(d.getTime())) return '-'
    return `${d.getMonth() + 1}/${d.getDate()}/${d.getFullYear()}`
}

function generateMissingDocuments() {
    router.post(route('admin.carriers.generate-missing-documents', props.carrier.slug), {}, {
        preserveScroll: true,
    })
}

function regenerateDotPolicy() {
    router.post(route('admin.carriers.generate-dot-policy', props.carrier.slug), {}, {
        preserveScroll: true,
    })
}

async function copyReferralUrl() {
    if (!props.carrier.referral_url) return

    try {
        await navigator.clipboard.writeText(props.carrier.referral_url)
        referralCopied.value = true
        window.setTimeout(() => { referralCopied.value = false }, 1800)
    } catch {
        referralCopied.value = false
    }
}

function regenerateReferrerToken() {
    if (!confirm('Regenerate the referrer token? The current link will stop working immediately.')) return
    router.post(route('admin.carriers.regenerate-referrer-token', props.carrier.slug), {}, { preserveScroll: true })
}

function deleteCarrierUser(userId: number) {
    if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
        router.delete(route('admin.carriers.user-carriers.destroy', { carrier: props.carrier.slug, userCarrier: userId }), {
            preserveScroll: true,
        })
    }
}

function deleteDriver(driverId: number) {
    if (confirm('Are you sure you want to delete this driver? This action cannot be undone.')) {
        router.delete(route('admin.drivers.destroy', driverId), {
            preserveScroll: true,
        })
    }
}

function deleteVehicle(vehicleId: number) {
    if (confirm('Are you sure you want to delete this vehicle? This action cannot be undone.')) {
        router.delete(route('admin.vehicles.destroy', vehicleId), {
            preserveScroll: true,
        })
    }
}

const tabs = [
    { id: 'overview', label: 'Overview', icon: 'LayoutDashboard' },
    { id: 'documents', label: 'Documents', icon: 'FileText' },
    { id: 'banking', label: 'Banking', icon: 'CreditCard' },
    { id: 'users', label: 'Users', icon: 'Users' },
    { id: 'drivers', label: 'Drivers', icon: 'UserCheck' },
    { id: 'vehicles', label: 'Vehicles', icon: 'Truck' },
]

const vehicleStatusMap: Record<string, { label: string; color: string; bg: string }> = {
    active:         { label: 'Active',          color: 'text-success', bg: 'bg-success/10' },
    inactive:       { label: 'Inactive',         color: 'text-slate-500', bg: 'bg-slate-100' },
    pending:        { label: 'Pending',          color: 'text-warning', bg: 'bg-warning/10' },
    suspended:      { label: 'Suspended',        color: 'text-danger', bg: 'bg-danger/10' },
    out_of_service: { label: 'Out of Service',   color: 'text-danger', bg: 'bg-danger/10' },
}

function getVehicleStatus(status: string) {
    return vehicleStatusMap[status] ?? { label: status ?? 'Unknown', color: 'text-slate-500', bg: 'bg-slate-100' }
}

const docStatusMap: Record<string, { label: string; color: string; bg: string }> = {
    pending:     { label: 'Pending',     color: 'text-warning', bg: 'bg-warning/10' },
    in_progress: { label: 'In Progress', color: 'text-info',    bg: 'bg-info/10'    },
    completed:   { label: 'Completed',   color: 'text-success', bg: 'bg-success/10' },
    skipped:     { label: 'Skipped',     color: 'text-slate-500', bg: 'bg-slate-100' },
}

function getDocStatus(status: string | null) {
    return docStatusMap[status ?? ''] ?? { label: 'N/A', color: 'text-slate-400', bg: 'bg-slate-100' }
}
</script>

<template>
    <Head :title="carrier.name" />

    <RazeLayout>
        <div class="grid grid-cols-12 gap-y-10 gap-x-6">
            <!-- Header -->
            <div class="col-span-12">
                <div class="flex flex-col md:h-10 gap-y-3 md:items-center md:flex-row">
                    <div class="text-base font-medium">
                        <Link :href="route('admin.carriers.index')" class="text-primary hover:underline">Carriers</Link>
                        <span class="mx-2 text-slate-400">/</span>
                        {{ carrier.name }}
                    </div>
                    <div class="flex flex-col sm:flex-row gap-x-3 gap-y-2 md:ml-auto">
                        <Link :href="route('admin.carriers.safety-data-system', carrier.slug)">
                            <Button variant="outline-warning" class="w-full sm:w-auto">
                                <Lucide icon="Shield" class="w-4 h-4 mr-2" /> Safety Data
                            </Button>
                        </Link>
                        <Link :href="route('admin.carriers.edit', carrier.slug)">
                            <Button variant="outline-primary" class="w-full sm:w-auto">
                                <Lucide icon="PenSquare" class="w-4 h-4 mr-2" /> Edit
                            </Button>
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Flash Messages -->
            <div v-if="flash.success" class="col-span-12">
                <div class="px-5 py-3 border rounded-lg bg-success/10 border-success/20 text-success text-sm flex items-center">
                    <Lucide icon="CheckCircle" class="w-4 h-4 mr-2" /> {{ flash.success }}
                </div>
            </div>
            <div v-if="flash.error" class="col-span-12">
                <div class="px-5 py-3 border rounded-lg bg-danger/10 border-danger/20 text-danger text-sm flex items-center">
                    <Lucide icon="AlertCircle" class="w-4 h-4 mr-2" /> {{ flash.error }}
                </div>
            </div>
            <div v-if="flash.warning" class="col-span-12">
                <div class="px-5 py-3 border rounded-lg bg-warning/10 border-warning/20 text-warning text-sm flex items-center">
                    <Lucide icon="AlertTriangle" class="w-4 h-4 mr-2" /> {{ flash.warning }}
                </div>
            </div>

            <!-- Carrier Header Card -->
            <div class="col-span-12">
                <div class="box box--stacked p-6">
                    <div class="flex flex-col lg:flex-row items-start lg:items-center gap-6">
                        <div class="flex items-center justify-center w-16 h-16 rounded-full bg-primary/10 border border-primary/20 flex-shrink-0 overflow-hidden">
                            <img
                                v-if="carrier.logo_url"
                                :src="carrier.logo_url"
                                :alt="carrier.name"
                                class="h-full w-full object-cover"
                            />
                            <Lucide v-else icon="Truck" class="w-8 h-8 text-primary" />
                        </div>
                        <div class="flex-1">
                            <h2 class="text-2xl font-bold text-slate-800">{{ carrier.name }}</h2>
                            <p class="text-slate-500 mt-1">{{ carrier.address }}, {{ carrier.state }} {{ carrier.zipcode }}</p>
                            <div class="flex flex-wrap gap-4 mt-3 text-sm">
                                <span v-if="carrier.ein_number" class="text-slate-600"><strong>EIN:</strong> {{ carrier.ein_number }}</span>
                                <span v-if="carrier.dot_number" class="text-slate-600"><strong>DOT:</strong> {{ carrier.dot_number }}</span>
                                <span v-if="carrier.mc_number" class="text-slate-600"><strong>MC:</strong> {{ carrier.mc_number }}</span>
                            </div>
                        </div>
                        <div class="flex flex-col items-end gap-2">
                            <span :class="[getStatus(carrier.status).bg, getStatus(carrier.status).color, 'px-3 py-1.5 rounded-full text-sm font-medium']">
                                {{ getStatus(carrier.status).label }}
                            </span>
                            <span class="text-xs text-slate-400">Since {{ formatDate(carrier.created_at) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats -->
            <div class="col-span-12">
                <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
                    <div class="p-4 box box--stacked text-center">
                        <div class="text-xl font-bold">{{ stats.total_users ?? 0 }}</div>
                        <div class="text-xs text-slate-500 mt-1">Users</div>
                    </div>
                    <div class="p-4 box box--stacked text-center">
                        <div class="text-xl font-bold">{{ stats.total_drivers ?? 0 }}</div>
                        <div class="text-xs text-slate-500 mt-1">Drivers</div>
                    </div>
                    <div class="p-4 box box--stacked text-center">
                        <div class="text-xl font-bold text-success">{{ stats.approved_documents_count ?? 0 }}</div>
                        <div class="text-xs text-slate-500 mt-1">Approved Docs</div>
                    </div>
                    <div class="p-4 box box--stacked text-center">
                        <div class="text-xl font-bold text-warning">{{ stats.pending_documents_count ?? 0 }}</div>
                        <div class="text-xs text-slate-500 mt-1">Pending Docs</div>
                    </div>
                    <div class="p-4 box box--stacked text-center">
                        <div class="text-xl font-bold text-info">{{ stats.document_completion_percentage ?? 0 }}%</div>
                        <div class="text-xs text-slate-500 mt-1">Completion</div>
                    </div>
                </div>
            </div>

            <!-- Tabs -->
            <div class="col-span-12">
                <div class="box box--stacked">
                    <div class="border-b border-slate-200/60">
                        <nav class="flex overflow-x-auto">
                            <button
                                v-for="tab in tabs"
                                :key="tab.id"
                                @click="activeTab = tab.id"
                                :class="[
                                    'flex items-center px-5 py-3.5 text-sm font-medium border-b-2 transition whitespace-nowrap',
                                    activeTab === tab.id
                                        ? 'border-primary text-primary'
                                        : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300',
                                ]"
                            >
                                <Lucide :icon="tab.icon as any" class="w-4 h-4 mr-2" />
                                {{ tab.label }}
                            </button>
                        </nav>
                    </div>

                    <!-- Overview Tab -->
                    <div v-show="activeTab === 'overview'" class="p-6">
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-10">
                            <!-- Company Information -->
                            <div class="min-w-0">
                                <h3 class="text-sm font-semibold text-slate-700 uppercase tracking-wide mb-4">Company Information</h3>
                                <dl class="grid gap-0 [&>div]:min-h-[2.5rem] [&>div]:flex [&>div]:items-center [&>div]:gap-4 [&>div]:border-b [&>div]:border-slate-100 [&>div]:py-2.5">
                                    <div>
                                        <dt class="text-sm text-slate-500 w-28 flex-shrink-0">Name</dt>
                                        <dd class="text-sm font-medium text-slate-800 truncate">{{ carrier.name }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm text-slate-500 w-28 flex-shrink-0">Address</dt>
                                        <dd class="text-sm font-medium text-slate-800 truncate">{{ carrier.address }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm text-slate-500 w-28 flex-shrink-0">State</dt>
                                        <dd class="text-sm font-medium text-slate-800">{{ carrier.state }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm text-slate-500 w-28 flex-shrink-0">ZIP Code</dt>
                                        <dd class="text-sm font-medium text-slate-800">{{ carrier.zipcode }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm text-slate-500 w-28 flex-shrink-0">EIN</dt>
                                        <dd class="text-sm font-medium font-mono text-slate-800">{{ carrier.ein_number }}</dd>
                                    </div>
                                    <div v-if="carrier.dot_number">
                                        <dt class="text-sm text-slate-500 w-28 flex-shrink-0">DOT Number</dt>
                                        <dd class="text-sm font-medium text-slate-800">{{ carrier.dot_number }}</dd>
                                    </div>
                                    <div v-if="carrier.mc_number">
                                        <dt class="text-sm text-slate-500 w-28 flex-shrink-0">MC Number</dt>
                                        <dd class="text-sm font-medium text-slate-800">{{ carrier.mc_number }}</dd>
                                    </div>
                                    <div v-if="carrier.state_dot">
                                        <dt class="text-sm text-slate-500 w-28 flex-shrink-0">State DOT</dt>
                                        <dd class="text-sm font-medium text-slate-800">{{ carrier.state_dot }}</dd>
                                    </div>
                                    <div v-if="carrier.ifta_account">
                                        <dt class="text-sm text-slate-500 w-28 flex-shrink-0">IFTA</dt>
                                        <dd class="text-sm font-medium text-slate-800">{{ carrier.ifta_account }}</dd>
                                    </div>
                                </dl>
                            </div>
                            <!-- Plan & Status -->
                            <div class="min-w-0">
                                <h3 class="text-sm font-semibold text-slate-700 uppercase tracking-wide mb-4">Plan & Status</h3>
                                <dl class="grid gap-0 [&>div]:min-h-[2.5rem] [&>div]:flex [&>div]:items-center [&>div]:gap-4 [&>div]:border-b [&>div]:border-slate-100 [&>div]:py-2.5">
                                    <div>
                                        <dt class="text-sm text-slate-500 w-32 flex-shrink-0">Membership</dt>
                                        <dd class="text-sm font-medium text-slate-800">{{ carrier.membership?.name ?? 'N/A' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm text-slate-500 w-32 flex-shrink-0">Status</dt>
                                        <dd :class="[getStatus(carrier.status).color, 'text-sm font-medium']">{{ getStatus(carrier.status).label }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm text-slate-500 w-32 flex-shrink-0">Document Status</dt>
                                        <dd>
                                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold"
                                                  :class="[getDocStatus(carrier.document_status).bg, getDocStatus(carrier.document_status).color]">
                                                {{ getDocStatus(carrier.document_status).label }}
                                            </span>
                                        </dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm text-slate-500 w-32 flex-shrink-0">Created</dt>
                                        <dd class="text-sm font-medium text-slate-800">{{ formatDate(carrier.created_at) }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm text-slate-500 w-32 flex-shrink-0">Last Updated</dt>
                                        <dd class="text-sm font-medium text-slate-800">{{ formatDate(carrier.updated_at) }}</dd>
                                    </div>
                                </dl>
                            </div>
                        </div>
                                                <!-- Driver Registration Referral -->
                                                <div class="mt-6 pt-6 border-t border-slate-200">
                            <div class="p-5 rounded-xl border bg-slate-50 border-slate-200">
                                <div class="flex items-start gap-3 mb-4">
                                    <div class="p-2 rounded-lg bg-success/10 shrink-0">
                                        <Lucide icon="UserPlus" class="w-5 h-5 text-success" />
                                    </div>
                                    <div>
                                        <h4 class="font-semibold text-slate-800">Driver Registration Referral</h4>
                                        <p class="text-xs text-slate-500 mt-0.5">Share this link so drivers can register directly under this carrier</p>
                                    </div>
                                </div>

                                <div class="space-y-3">
                                    <!-- Referrer Token -->
                                    <div class="flex items-center gap-3">
                                        <span class="text-xs font-medium text-slate-500 w-24 shrink-0">Referrer Token</span>
                                        <code v-if="carrier.referrer_token" class="flex-1 rounded-lg bg-white border border-slate-200 px-3 py-1.5 text-xs font-mono text-slate-700 truncate">
                                            {{ carrier.referrer_token }}
                                        </code>
                                        <span v-else class="text-xs text-slate-400 italic">No token assigned</span>
                                        <button
                                            @click="regenerateReferrerToken"
                                            class="shrink-0 inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-600 transition hover:bg-slate-100"
                                            title="Regenerate token — current link will stop working"
                                        >
                                            <Lucide icon="RefreshCw" class="w-3.5 h-3.5" />
                                            Regenerate
                                        </button>
                                    </div>

                                    <!-- Referral URL -->
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-medium text-slate-500 w-24 shrink-0">Referral Link</span>
                                        <div v-if="carrier.referral_url" class="flex flex-1 items-center gap-2 min-w-0">
                                            <input
                                                :value="carrier.referral_url"
                                                readonly
                                                class="flex-1 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-mono text-slate-600 truncate focus:outline-none"
                                            />
                                            <button
                                                @click="copyReferralUrl"
                                                class="shrink-0 inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-600 transition hover:bg-slate-100"
                                            >
                                                <Lucide :icon="referralCopied ? 'Check' : 'Copy'" class="w-3.5 h-3.5" :class="referralCopied ? 'text-success' : ''" />
                                                {{ referralCopied ? 'Copied!' : 'Copy' }}
                                            </button>
                                            <a :href="carrier.referral_url" target="_blank" class="shrink-0 inline-flex items-center gap-1.5 rounded-lg bg-primary px-3 py-1.5 text-xs font-medium text-white transition hover:bg-primary/90">
                                                <Lucide icon="ExternalLink" class="w-3.5 h-3.5" />
                                                Open
                                            </a>
                                        </div>
                                        <span v-else class="text-xs text-slate-400 italic">Generate a token first</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- DOT Drug & Alcohol Policy -->
                        <div class="mt-8 pt-6 border-t border-slate-200">
                            <div class="p-5 rounded-xl border" :class="dotPolicy ? 'bg-slate-50 border-slate-200' : 'bg-amber-50 border-amber-200'">
                                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                                    <div class="flex items-start gap-3">
                                        <div class="p-2 rounded-lg" :class="dotPolicy ? 'bg-primary/10' : 'bg-amber-100'">
                                            <Lucide icon="FileText" class="w-5 h-5" :class="dotPolicy ? 'text-primary' : 'text-amber-600'" />
                                        </div>
                                        <div>
                                            <h4 class="font-semibold text-slate-800">DOT Drug & Alcohol Policy</h4>
                                            <p class="text-xs text-slate-500">FMCSA 49 CFR Part 382 — Auto-filled with carrier data</p>
                                            <p v-if="dotPolicy" class="text-xs text-emerald-600 mt-1 flex items-center gap-1">
                                                <Lucide icon="CheckCircle" class="w-3 h-3" /> Generated {{ dotPolicy.generated_at }}
                                            </p>
                                            <p v-else class="text-xs text-amber-600 mt-1">Not generated yet</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <a v-if="dotPolicy" :href="dotPolicy.url" target="_blank" class="inline-flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition text-sm font-medium">
                                            <Lucide icon="Eye" class="w-4 h-4" /> View PDF
                                        </a>
                                        <Button variant="outline-primary" size="sm" @click="regenerateDotPolicy">
                                            <Lucide icon="RefreshCw" class="w-4 h-4 mr-1" /> Regenerate DOT Policy
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Safety Data System -->
                        <div class="mt-6 pt-6 border-t border-slate-200">
                            <div class="p-5 rounded-xl border bg-slate-50 border-slate-200">
                                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                                    <div class="flex items-start gap-3">
                                        <div class="p-2 rounded-lg bg-primary/10">
                                            <Lucide icon="Shield" class="w-5 h-5 text-primary" />
                                        </div>
                                        <div>
                                            <h4 class="font-semibold text-slate-800">Safety Data System</h4>
                                            <p class="text-xs text-slate-500">FMCSA Safety Monitoring — Manage URL & Image</p>
                                            <p v-if="carrier.dot_number" class="text-xs text-emerald-600 mt-1 flex items-center gap-1">
                                                <Lucide icon="CheckCircle" class="w-3 h-3" /> DOT {{ carrier.dot_number }} linked
                                            </p>
                                            <p v-else class="text-xs text-amber-600 mt-1">DOT Number required</p>
                                        </div>
                                    </div>
                                    <Link :href="route('admin.carriers.safety-data-system', carrier.slug)">
                                        <Button variant="outline-primary" size="sm">
                                            <Lucide icon="Settings" class="w-4 h-4 mr-1" /> Manage
                                        </Button>
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Documents Tab -->
                    <div v-show="activeTab === 'documents'" class="p-6">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="font-medium text-slate-800">Documents ({{ documents.length }})</h3>
                            <Button variant="outline-primary" size="sm" @click="generateMissingDocuments" v-if="missingDocumentTypes.length">
                                <Lucide icon="Plus" class="w-4 h-4 mr-1" /> Generate {{ missingDocumentTypes.length }} Missing
                            </Button>
                        </div>

                        <div class="overflow-auto">
                            <table class="w-full text-left">
                                <thead>
                                    <tr class="[&>th]:px-4 [&>th]:py-3 [&>th]:font-medium [&>th]:text-slate-500 [&>th]:text-sm [&>th]:border-b">
                                        <th>Document Type</th>
                                        <th>Current Status</th>
                                        <th>Change Status</th>
                                        <th>Updated</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="doc in documents" :key="doc.id" class="[&>td]:px-4 [&>td]:py-3 [&>td]:border-b [&>td]:border-slate-100 hover:bg-slate-50/50">
                                        <td>
                                            <div class="flex items-center gap-2">
                                                <span class="text-sm font-medium text-slate-800">{{ doc.document_type?.name ?? 'Unknown' }}</span>
                                                <span v-if="doc.document_type?.requirement" class="text-[10px] font-semibold uppercase tracking-wide text-danger">Required</span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="flex items-center gap-2">
                                                <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium"
                                                      :class="{
                                                          'bg-success/10 text-success':  doc.status === 1,
                                                          'bg-danger/10 text-danger':    doc.status === 2,
                                                          'bg-info/10 text-info':        doc.status === 3,
                                                          'bg-warning/10 text-warning':  doc.status === 0 || doc.status == null,
                                                      }">
                                                    {{ doc.status_name ?? 'Pending' }}
                                                </span>
                                                <Lucide v-if="doc.has_file" icon="Paperclip" class="w-3.5 h-3.5 text-slate-400" title="File attached" />
                                            </div>
                                        </td>
                                        <td>
                                            <FormSelect
                                                :modelValue="String(doc.status)"
                                                @update:modelValue="(val: string) => updateDocumentStatus(doc.id, parseInt(val))"
                                                class="w-36 text-sm"
                                            >
                                                <option value="0">Pending</option>
                                                <option value="1">Approved</option>
                                                <option value="2">Rejected</option>
                                                <option value="3">In Process</option>
                                            </FormSelect>
                                        </td>
                                        <td class="text-sm text-slate-500">{{ formatDate(doc.updated_at ?? doc.date) }}</td>
                                        <td class="text-center">
                                            <div class="flex items-center justify-center gap-1">
                                                <a v-if="doc.file_url" :href="doc.file_url" target="_blank"
                                                   class="p-1 rounded hover:bg-primary/10 text-primary" title="View file">
                                                    <Lucide icon="Eye" class="w-4 h-4" />
                                                </a>
                                                <button @click="updateDocumentStatus(doc.id, 1)" class="p-1 rounded hover:bg-success/10 text-success" title="Approve">
                                                    <Lucide icon="Check" class="w-4 h-4" />
                                                </button>
                                                <button @click="updateDocumentStatus(doc.id, 2)" class="p-1 rounded hover:bg-danger/10 text-danger" title="Reject">
                                                    <Lucide icon="X" class="w-4 h-4" />
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-if="!documents.length">
                                        <td colspan="5" class="px-4 py-8 text-center text-slate-400">No documents found</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Banking Tab -->
                    <div v-show="activeTab === 'banking'" class="p-6">
                        <div v-if="bankingDetails">
                            <div class="flex items-center justify-between mb-6">
                                <h3 class="font-medium text-slate-800">Banking Information</h3>
                                <span :class="[getBankingStatus(bankingDetails.status).bg, getBankingStatus(bankingDetails.status).color, 'px-3 py-1 rounded-full text-sm font-medium']">
                                    {{ getBankingStatus(bankingDetails.status).label }}
                                </span>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <dl class="space-y-3">
                                    <div class="flex justify-between border-b border-dashed border-slate-200 pb-2">
                                        <dt class="text-sm text-slate-500">Account Holder</dt>
                                        <dd class="text-sm font-medium">{{ bankingDetails.account_holder_name }}</dd>
                                    </div>
                                    <div class="flex justify-between border-b border-dashed border-slate-200 pb-2">
                                        <dt class="text-sm text-slate-500">Account Number</dt>
                                        <dd class="text-sm font-medium font-mono">****{{ bankingDetails.account_number?.slice(-4) }}</dd>
                                    </div>
                                    <div class="flex justify-between border-b border-dashed border-slate-200 pb-2">
                                        <dt class="text-sm text-slate-500">Routing Number</dt>
                                        <dd class="text-sm font-medium font-mono">{{ bankingDetails.banking_routing_number }}</dd>
                                    </div>
                                    <div class="flex justify-between border-b border-dashed border-slate-200 pb-2">
                                        <dt class="text-sm text-slate-500">ZIP Code</dt>
                                        <dd class="text-sm font-medium">{{ bankingDetails.zip_code }}</dd>
                                    </div>
                                    <div class="flex justify-between border-b border-dashed border-slate-200 pb-2">
                                        <dt class="text-sm text-slate-500">Country</dt>
                                        <dd class="text-sm font-medium">{{ bankingDetails.country_code }}</dd>
                                    </div>
                                </dl>

                                <div v-if="bankingDetails.status === 'pending'" class="space-y-4">
                                    <h4 class="font-medium text-slate-700">Review Actions</h4>
                                    <Button variant="success" class="w-full" @click="approveBanking">
                                        <Lucide icon="CheckCircle" class="w-4 h-4 mr-2" /> Approve & Activate Carrier
                                    </Button>
                                    <div>
                                        <FormLabel>Rejection Reason</FormLabel>
                                        <textarea v-model="rejectionReason" rows="3" placeholder="Provide a reason for rejection..." class="w-full px-3 py-2 text-sm border border-slate-200 rounded-md focus:ring-2 focus:ring-primary/20 focus:border-primary" />
                                        <Button variant="danger" class="w-full mt-2" @click="rejectBanking" :disabled="!rejectionReason">
                                            <Lucide icon="XCircle" class="w-4 h-4 mr-2" /> Reject Banking
                                        </Button>
                                    </div>
                                </div>

                                <div v-if="bankingDetails.status === 'rejected' && bankingDetails.rejection_reason" class="p-4 bg-danger/5 border border-danger/20 rounded-lg">
                                    <h4 class="text-sm font-medium text-danger mb-2">Rejection Reason</h4>
                                    <p class="text-sm text-slate-600">{{ bankingDetails.rejection_reason }}</p>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center py-12 text-slate-400">
                            <Lucide icon="CreditCard" class="w-12 h-12 mx-auto mb-3 text-slate-300" />
                            <p>No banking information submitted yet</p>
                        </div>
                    </div>

                    <!-- Users Tab -->
                    <div v-show="activeTab === 'users'" class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="font-medium text-slate-800">Carrier Users ({{ userCarriers.length }})</h3>
                            <Link :href="route('admin.carriers.users.index', carrier.slug)" class="inline-flex items-center gap-1 px-3 py-1.5 bg-primary/10 text-primary rounded-lg hover:bg-primary/20 text-sm transition">
                                <Lucide icon="UserPlus" class="w-3 h-3" /> Manage Users
                            </Link>
                        </div>
                        <div class="overflow-auto">
                            <table class="w-full text-left">
                                <thead>
                                    <tr class="[&>th]:px-4 [&>th]:py-3 [&>th]:font-medium [&>th]:text-slate-500 [&>th]:text-sm [&>th]:border-b">
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Position</th>
                                        <th>Phone</th>
                                        <th>Status</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="uc in userCarriers" :key="uc.id" class="[&>td]:px-4 [&>td]:py-3 [&>td]:border-b [&>td]:border-slate-100">
                                        <td class="text-sm font-medium">{{ uc.user?.name ?? 'N/A' }}</td>
                                        <td class="text-sm text-slate-500">{{ uc.user?.email ?? '-' }}</td>
                                        <td class="text-sm capitalize">{{ uc.job_position ?? '-' }}</td>
                                        <td class="text-sm text-slate-500">{{ uc.phone ?? '-' }}</td>
                                        <td>
                                            <span :class="[
                                                'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium',
                                                uc.status == 1 ? 'bg-success/10 text-success' : 'bg-slate-100 text-slate-500',
                                            ]">
                                                {{ uc.status == 1 ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="flex items-center gap-1">
                                                <Link :href="route('admin.carriers.user-carriers.show', { carrier: carrier.slug, userCarrierDetail: uc.id })" class="p-1 rounded hover:bg-primary/10 text-primary" title="View user">
                                                    <Lucide icon="Eye" class="w-4 h-4" />
                                                </Link>
                                                <Link :href="route('admin.carriers.user-carriers.edit', { carrier: carrier.slug, userCarrierDetail: uc.id })" class="p-1 rounded hover:bg-warning/10 text-warning" title="Edit user">
                                                    <Lucide icon="PenLine" class="w-4 h-4" />
                                                </Link>
                                                <button @click="deleteCarrierUser(uc.user_id)" class="p-1 rounded hover:bg-danger/10 text-danger" title="Delete user">
                                                    <Lucide icon="Trash2" class="w-4 h-4" />
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-if="!userCarriers.length">
                                        <td colspan="6" class="px-4 py-8 text-center text-slate-400">No users associated</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Vehicles Tab -->
                    <div v-show="activeTab === 'vehicles'" class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="font-medium text-slate-800">Vehicles ({{ vehicles.length }})</h3>
                            <Link
                                :href="route('admin.vehicles.create', { carrier_id: carrier.id })"
                                class="flex items-center gap-2 px-3 py-1.5 bg-primary text-white rounded-lg text-sm font-medium hover:bg-primary/90 transition"
                            >
                                <Lucide icon="Plus" class="w-4 h-4" />
                                Register Vehicle
                            </Link>
                        </div>
                        <div class="overflow-auto">
                            <table class="w-full text-left">
                                <thead>
                                    <tr class="[&>th]:px-4 [&>th]:py-3 [&>th]:font-medium [&>th]:text-slate-500 [&>th]:text-sm [&>th]:border-b">
                                        <th>Unit #</th>
                                        <th>Year / Make / Model</th>
                                        <th>Type</th>
                                        <th>VIN</th>
                                        <th>Assigned Driver</th>
                                        <th>Status</th>
                                        <th>Added</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="vehicle in vehicles" :key="vehicle.id" class="[&>td]:px-4 [&>td]:py-3 [&>td]:border-b [&>td]:border-slate-100">
                                        <td class="text-sm font-medium">{{ vehicle.company_unit_number ?? '-' }}</td>
                                        <td class="text-sm">{{ [vehicle.year, vehicle.make, vehicle.model].filter(Boolean).join(' ') || '-' }}</td>
                                        <td class="text-sm text-slate-500 capitalize">{{ vehicle.type ?? '-' }}</td>
                                        <td class="text-sm text-slate-500 font-mono text-xs">{{ vehicle.vin ?? '-' }}</td>
                                        <td class="text-sm text-slate-500">{{ vehicle.driver?.name ?? '—' }}</td>
                                        <td>
                                            <span :class="[
                                                'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium',
                                                getVehicleStatus(vehicle.status).bg,
                                                getVehicleStatus(vehicle.status).color,
                                            ]">
                                                {{ getVehicleStatus(vehicle.status).label }}
                                            </span>
                                        </td>
                                        <td class="text-sm text-slate-500">{{ formatDate(vehicle.created_at) }}</td>
                                        <td>
                                            <div class="flex items-center gap-1">
                                                <Link
                                                    :href="route('admin.vehicles.show', vehicle.id)"
                                                    class="p-1.5 rounded hover:bg-primary/10 text-primary"
                                                    title="View"
                                                >
                                                    <Lucide icon="Eye" class="w-4 h-4" />
                                                </Link>
                                                <Link
                                                    :href="route('admin.vehicles.edit', vehicle.id)"
                                                    class="p-1.5 rounded hover:bg-warning/10 text-warning"
                                                    title="Edit"
                                                >
                                                    <Lucide icon="Pencil" class="w-4 h-4" />
                                                </Link>
                                                <button
                                                    @click="deleteVehicle(vehicle.id)"
                                                    class="p-1.5 rounded hover:bg-danger/10 text-danger"
                                                    title="Delete"
                                                >
                                                    <Lucide icon="Trash2" class="w-4 h-4" />
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-if="!vehicles.length">
                                        <td colspan="8" class="px-4 py-10 text-center text-slate-400">
                                            <Lucide icon="Truck" class="w-8 h-8 mx-auto mb-2 text-slate-300" />
                                            No vehicles registered for this carrier
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Drivers Tab -->
                    <div v-show="activeTab === 'drivers'" class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="font-medium text-slate-800">Drivers ({{ drivers.length }})</h3>
                            <Link
                                :href="route('admin.drivers.wizard.create', { carrier_id: carrier.id })"
                                class="flex items-center gap-2 px-3 py-1.5 bg-primary text-white rounded-lg text-sm font-medium hover:bg-primary/90 transition"
                            >
                                <Lucide icon="UserPlus" class="w-4 h-4" />
                                Register Driver
                            </Link>
                        </div>
                        <div class="overflow-auto">
                            <table class="w-full text-left">
                                <thead>
                                    <tr class="[&>th]:px-4 [&>th]:py-3 [&>th]:font-medium [&>th]:text-slate-500 [&>th]:text-sm [&>th]:border-b">
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Status</th>
                                        <th>Joined</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="driver in drivers" :key="driver.id" class="[&>td]:px-4 [&>td]:py-3 [&>td]:border-b [&>td]:border-slate-100">
                                        <td class="text-sm font-medium">{{ driver.user?.name ?? 'N/A' }}</td>
                                        <td class="text-sm text-slate-500">{{ driver.user?.email ?? '-' }}</td>
                                        <td>
                                            <span :class="[
                                                'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium',
                                                driver.status == 1 ? 'bg-success/10 text-success' : 'bg-warning/10 text-warning',
                                            ]">
                                                {{ driver.status == 1 ? 'Active' : 'Pending' }}
                                            </span>
                                        </td>
                                        <td class="text-sm text-slate-500">{{ formatDate(driver.created_at) }}</td>
                                        <td>
                                            <div class="flex items-center gap-2">
                                                <Link
                                                    v-if="driver.id"
                                                    :href="route('admin.drivers.show', driver.id)"
                                                    class="text-xs text-primary hover:underline"
                                                >
                                                    View
                                                </Link>
                                                <Link
                                                    v-if="driver.id"
                                                    :href="route('admin.drivers.wizard.edit', driver.id)"
                                                    class="text-xs text-warning hover:underline"
                                                >
                                                    Edit
                                                </Link>
                                                <button @click="deleteDriver(driver.id)" class="p-1 rounded hover:bg-danger/10 text-danger" title="Delete driver">
                                                    <Lucide icon="Trash2" class="w-4 h-4" />
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-if="!drivers.length">
                                        <td colspan="5" class="px-4 py-8 text-center text-slate-400">No drivers associated</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </RazeLayout>
</template>
