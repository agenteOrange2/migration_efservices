<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { computed } from 'vue'
import Lucide from '@/components/Base/Lucide'
import RazeLayout from '@/layouts/RazeLayout.vue'

declare function route(name: string, params?: any): string

defineOptions({ layout: RazeLayout })

interface EndorsementRow {
    id: number
    code: string | null
    name: string | null
    label: string
}

interface LicenseRow {
    id: number
    license_number: string | null
    license_class: string | null
    state_of_issue: string | null
    expiration_date: string | null
    is_cdl: boolean
    is_primary: boolean
    restrictions: string | null
    status: 'valid' | 'expiring_soon' | 'expired'
    is_expired: boolean
    is_expiring_soon: boolean
    endorsements: EndorsementRow[]
    front_url: string | null
    back_url: string | null
    document_count: number
}

const props = defineProps<{
    driver: {
        id: number
        full_name: string
        carrier_name: string | null
    }
    stats: {
        total: number
        valid: number
        expiring_soon: number
        expired: number
    }
    licenses: LicenseRow[]
}>()

const statCards = computed(() => [
    { label: 'Total Licenses', value: props.stats.total, icon: 'CreditCard', className: 'bg-primary/10 text-primary' },
    { label: 'Valid', value: props.stats.valid, icon: 'BadgeCheck', className: 'bg-success/10 text-success' },
    { label: 'Expiring Soon', value: props.stats.expiring_soon, icon: 'Clock3', className: 'bg-warning/10 text-warning' },
    { label: 'Expired', value: props.stats.expired, icon: 'AlertTriangle', className: 'bg-danger/10 text-danger' },
])

function statusBadgeClass(status: LicenseRow['status']) {
    if (status === 'expired') return 'bg-danger/10 text-danger'
    if (status === 'expiring_soon') return 'bg-warning/10 text-warning'
    return 'bg-success/10 text-success'
}

function statusLabel(status: LicenseRow['status']) {
    if (status === 'expired') return 'Expired'
    if (status === 'expiring_soon') return 'Expiring Soon'
    return 'Valid'
}

function cardClass(license: LicenseRow) {
    if (license.is_expired) return 'border-danger/25 bg-danger/5'
    if (license.is_expiring_soon) return 'border-warning/25 bg-warning/5'
    return 'border-slate-200 bg-white'
}
</script>

<template>
    <Head title="My Licenses" />

    <div class="grid grid-cols-12 gap-6">
        <div class="col-span-12">
            <div class="box box--stacked p-6">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex items-start gap-4">
                        <div class="rounded-2xl border border-primary/20 bg-primary/10 p-3">
                            <Lucide icon="CreditCard" class="h-8 w-8 text-primary" />
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-slate-800">My Licenses</h1>
                            <p class="mt-1 text-slate-500">
                                Review your driver licenses, endorsements and supporting images.
                            </p>
                            <p class="mt-2 text-sm text-slate-500">
                                Driver: <span class="font-medium text-slate-700">{{ driver.full_name }}</span>
                                <span v-if="driver.carrier_name">· Carrier: <span class="font-medium text-slate-700">{{ driver.carrier_name }}</span></span>
                            </p>
                        </div>
                    </div>

                    <Link
                        :href="route('driver.profile')"
                        class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                    >
                        <Lucide icon="User" class="h-4 w-4" />
                        View Profile
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
            <div v-if="licenses.length" class="grid grid-cols-12 gap-6">
                <div
                    v-for="license in licenses"
                    :key="license.id"
                    class="col-span-12 xl:col-span-6"
                >
                    <div class="box box--stacked h-full border p-6 transition hover:shadow-md" :class="cardClass(license)">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                            <div class="flex items-start gap-3">
                                <div class="rounded-xl bg-primary/10 p-3">
                                    <Lucide icon="BadgeCheck" class="h-5 w-5 text-primary" />
                                </div>
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h2 class="text-lg font-semibold text-slate-800">{{ license.license_number || 'N/A' }}</h2>
                                        <span
                                            v-if="license.is_primary"
                                            class="inline-flex rounded-full bg-primary/10 px-2.5 py-1 text-xs font-medium text-primary"
                                        >
                                            Primary
                                        </span>
                                        <span
                                            v-if="license.is_cdl"
                                            class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700"
                                        >
                                            CDL
                                        </span>
                                    </div>
                                    <p class="mt-1 text-sm text-slate-500">
                                        {{ license.state_of_issue || 'N/A' }} · Class {{ license.license_class || 'N/A' }}
                                    </p>
                                </div>
                            </div>

                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium" :class="statusBadgeClass(license.status)">
                                {{ statusLabel(license.status) }}
                            </span>
                        </div>

                        <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-4">
                                <p class="text-xs font-medium uppercase tracking-[0.2em] text-slate-400">Expiration</p>
                                <p class="mt-2 font-semibold text-slate-800">{{ license.expiration_date || 'N/A' }}</p>
                            </div>
                            <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-4">
                                <p class="text-xs font-medium uppercase tracking-[0.2em] text-slate-400">Endorsements</p>
                                <p class="mt-2 font-semibold text-slate-800">{{ license.endorsements.length }}</p>
                            </div>
                            <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-4">
                                <p class="text-xs font-medium uppercase tracking-[0.2em] text-slate-400">Documents</p>
                                <p class="mt-2 font-semibold text-slate-800">{{ license.document_count }}</p>
                            </div>
                        </div>

                        <div v-if="license.endorsements.length" class="mt-5">
                            <p class="text-xs font-medium uppercase tracking-[0.2em] text-slate-400">Endorsements</p>
                            <div class="mt-2 flex flex-wrap gap-2">
                                <span
                                    v-for="endorsement in license.endorsements"
                                    :key="endorsement.id"
                                    class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700"
                                >
                                    {{ endorsement.label }}
                                </span>
                            </div>
                        </div>

                        <div v-if="license.restrictions" class="mt-5 rounded-xl border border-slate-200 bg-white/80 p-4">
                            <p class="text-xs font-medium uppercase tracking-[0.2em] text-slate-400">Restrictions</p>
                            <p class="mt-2 text-sm text-slate-700">{{ license.restrictions }}</p>
                        </div>

                        <div class="mt-5 flex flex-wrap items-center gap-3 border-t border-slate-200 pt-5">
                            <a
                                v-if="license.front_url"
                                :href="license.front_url"
                                target="_blank"
                                class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-600 transition hover:bg-slate-50"
                            >
                                <Lucide icon="Image" class="h-4 w-4" />
                                Front
                            </a>
                            <a
                                v-if="license.back_url"
                                :href="license.back_url"
                                target="_blank"
                                class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-600 transition hover:bg-slate-50"
                            >
                                <Lucide icon="Image" class="h-4 w-4" />
                                Back
                            </a>
                            <Link
                                :href="route('driver.licenses.show', license.id)"
                                class="ml-auto inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white transition hover:bg-primary/90"
                            >
                                <Lucide icon="Eye" class="h-4 w-4" />
                                View Details
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <div v-else class="box box--stacked p-12 text-center">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-slate-100">
                    <Lucide icon="CreditCard" class="h-8 w-8 text-slate-400" />
                </div>
                <h2 class="mt-5 text-lg font-semibold text-slate-800">No Licenses Found</h2>
                <p class="mx-auto mt-2 max-w-xl text-sm text-slate-500">
                    Your license information is managed by your carrier or admin team. Once it is registered, it will appear here.
                </p>
            </div>
        </div>
    </div>
</template>
