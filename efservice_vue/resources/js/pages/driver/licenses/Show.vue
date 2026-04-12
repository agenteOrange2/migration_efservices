<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import Lucide from '@/components/Base/Lucide'
import Button from '@/components/Base/Button'
import RazeLayout from '@/layouts/RazeLayout.vue'

declare function route(name: string, params?: any): string

defineOptions({ layout: RazeLayout })

interface EndorsementRow {
    id: number
    code: string | null
    name: string | null
    label: string
}

interface DocumentRow {
    id: number
    file_name: string
    preview_url: string
    size_label: string
    mime_type: string | null
    file_type: string
    created_at_display: string | null
}

interface LicensePayload {
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
    created_at: string | null
    updated_at: string | null
    driver: { id: number; name: string; email: string | null } | null
    carrier: { id: number; name: string } | null
    documents: DocumentRow[]
}

defineProps<{
    license: LicensePayload
}>()

function statusBadgeClass(status: LicensePayload['status']) {
    if (status === 'expired') return 'bg-danger/10 text-danger'
    if (status === 'expiring_soon') return 'bg-warning/10 text-warning'
    return 'bg-success/10 text-success'
}

function statusLabel(status: LicensePayload['status']) {
    if (status === 'expired') return 'Expired'
    if (status === 'expiring_soon') return 'Expiring Soon'
    return 'Valid'
}

function fileTypeLabel(fileType: string) {
    return fileType.toUpperCase()
}
</script>

<template>
    <Head title="License Details" />

    <div class="grid grid-cols-12 gap-6">
        <div class="col-span-12">
            <div class="box box--stacked p-6">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex items-start gap-4">
                        <div class="rounded-2xl border border-primary/20 bg-primary/10 p-3">
                            <Lucide icon="CreditCard" class="h-8 w-8 text-primary" />
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-slate-800">License Details</h1>
                            <p class="mt-1 text-slate-500">
                                Review your license information, images and supporting documents.
                            </p>
                            <div class="mt-3 flex flex-wrap items-center gap-2">
                                <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">
                                    {{ license.license_number || 'N/A' }}
                                </span>
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
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium" :class="statusBadgeClass(license.status)">
                                    {{ statusLabel(license.status) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <Link :href="route('driver.licenses.index')">
                            <Button variant="outline-secondary" class="flex items-center gap-2">
                                <Lucide icon="ArrowLeft" class="h-4 w-4" />
                                Back to Licenses
                            </Button>
                        </Link>
                        <Link :href="route('driver.profile')">
                            <Button variant="primary" class="flex items-center gap-2">
                                <Lucide icon="User" class="h-4 w-4" />
                                My Profile
                            </Button>
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12 xl:col-span-8">
            <div class="box box--stacked p-6">
                <div class="mb-5 flex items-center gap-2 border-b border-dashed border-slate-300/70 pb-5">
                    <Lucide icon="BadgeInfo" class="h-5 w-5 text-primary" />
                    <h2 class="text-sm font-semibold text-slate-700">License Information</h2>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="rounded-xl border border-slate-200 p-4">
                        <p class="text-xs font-medium uppercase tracking-[0.2em] text-slate-400">Driver</p>
                        <p class="mt-2 font-semibold text-slate-800">{{ license.driver?.name ?? 'N/A' }}</p>
                        <p class="mt-1 text-sm text-slate-500">{{ license.driver?.email ?? 'No email' }}</p>
                    </div>

                    <div class="rounded-xl border border-slate-200 p-4">
                        <p class="text-xs font-medium uppercase tracking-[0.2em] text-slate-400">Carrier</p>
                        <p class="mt-2 font-semibold text-slate-800">{{ license.carrier?.name ?? 'N/A' }}</p>
                    </div>

                    <div class="rounded-xl border border-slate-200 p-4">
                        <p class="text-xs font-medium uppercase tracking-[0.2em] text-slate-400">License Number</p>
                        <p class="mt-2 font-mono font-semibold text-slate-800">{{ license.license_number || 'N/A' }}</p>
                    </div>

                    <div class="rounded-xl border border-slate-200 p-4">
                        <p class="text-xs font-medium uppercase tracking-[0.2em] text-slate-400">Class / State</p>
                        <p class="mt-2 font-semibold text-slate-800">{{ license.license_class || 'N/A' }} / {{ license.state_of_issue || 'N/A' }}</p>
                    </div>

                    <div class="rounded-xl border border-slate-200 p-4">
                        <p class="text-xs font-medium uppercase tracking-[0.2em] text-slate-400">Expiration</p>
                        <p class="mt-2 font-semibold text-slate-800">{{ license.expiration_date || 'N/A' }}</p>
                    </div>

                    <div class="rounded-xl border border-slate-200 p-4">
                        <p class="text-xs font-medium uppercase tracking-[0.2em] text-slate-400">Supporting Files</p>
                        <p class="mt-2 font-semibold text-slate-800">{{ license.document_count }}</p>
                    </div>

                    <div class="rounded-xl border border-slate-200 p-4 md:col-span-2">
                        <p class="text-xs font-medium uppercase tracking-[0.2em] text-slate-400">Restrictions</p>
                        <p class="mt-2 text-slate-700">{{ license.restrictions || 'None' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12 xl:col-span-4">
            <div class="box box--stacked p-6">
                <div class="mb-5 flex items-center gap-2 border-b border-dashed border-slate-300/70 pb-5">
                    <Lucide icon="Award" class="h-5 w-5 text-primary" />
                    <h2 class="text-sm font-semibold text-slate-700">Endorsements</h2>
                </div>

                <div v-if="license.endorsements.length" class="space-y-3">
                    <div
                        v-for="endorsement in license.endorsements"
                        :key="endorsement.id"
                        class="flex items-center justify-between rounded-xl border border-slate-200 px-4 py-3"
                    >
                        <div>
                            <p class="text-sm font-medium text-slate-700">{{ endorsement.name || endorsement.code || 'Endorsement' }}</p>
                            <p v-if="endorsement.code" class="mt-1 text-xs text-slate-500">Code: {{ endorsement.code }}</p>
                        </div>
                        <span class="inline-flex rounded-full bg-primary/10 px-2.5 py-1 text-xs font-medium text-primary">Active</span>
                    </div>
                </div>
                <div v-else class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">
                    No endorsements registered for this license.
                </div>
            </div>
        </div>

        <div class="col-span-12 xl:col-span-7">
            <div class="box box--stacked p-6">
                <div class="mb-5 flex items-center gap-2 border-b border-dashed border-slate-300/70 pb-5">
                    <Lucide icon="Image" class="h-5 w-5 text-primary" />
                    <h2 class="text-sm font-semibold text-slate-700">License Images</h2>
                </div>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div class="space-y-3">
                        <p class="text-sm font-medium text-slate-700">Front Image</p>
                        <a
                            v-if="license.front_url"
                            :href="license.front_url"
                            target="_blank"
                            class="group block overflow-hidden rounded-xl border border-slate-200 bg-slate-50"
                        >
                            <img :src="license.front_url" alt="Front license image" class="h-64 w-full object-cover transition duration-200 group-hover:scale-[1.02]" />
                        </a>
                        <div v-else class="flex h-64 items-center justify-center rounded-xl border border-dashed border-slate-300 bg-slate-50 text-sm text-slate-500">
                            No front image uploaded
                        </div>
                    </div>

                    <div class="space-y-3">
                        <p class="text-sm font-medium text-slate-700">Back Image</p>
                        <a
                            v-if="license.back_url"
                            :href="license.back_url"
                            target="_blank"
                            class="group block overflow-hidden rounded-xl border border-slate-200 bg-slate-50"
                        >
                            <img :src="license.back_url" alt="Back license image" class="h-64 w-full object-cover transition duration-200 group-hover:scale-[1.02]" />
                        </a>
                        <div v-else class="flex h-64 items-center justify-center rounded-xl border border-dashed border-slate-300 bg-slate-50 text-sm text-slate-500">
                            No back image uploaded
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12 xl:col-span-5">
            <div class="box box--stacked p-6">
                <div class="mb-5 flex items-center gap-2 border-b border-dashed border-slate-300/70 pb-5">
                    <Lucide icon="Files" class="h-5 w-5 text-primary" />
                    <h2 class="text-sm font-semibold text-slate-700">Additional Documents</h2>
                </div>

                <div v-if="license.documents.length" class="space-y-3">
                    <a
                        v-for="document in license.documents"
                        :key="document.id"
                        :href="document.preview_url"
                        target="_blank"
                        class="flex items-start justify-between rounded-xl border border-slate-200 px-4 py-3 transition hover:bg-slate-50"
                    >
                        <div class="min-w-0 pr-4">
                            <p class="truncate text-sm font-medium text-slate-700">{{ document.file_name }}</p>
                            <p class="mt-1 text-xs text-slate-500">
                                {{ document.size_label }} · {{ fileTypeLabel(document.file_type) }} · {{ document.created_at_display }}
                            </p>
                        </div>
                        <Lucide icon="ArrowUpRight" class="mt-0.5 h-4 w-4 flex-shrink-0 text-slate-400" />
                    </a>
                </div>
                <div v-else class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">
                    No additional documents uploaded.
                </div>
            </div>
        </div>

        <div class="col-span-12">
            <div class="box box--stacked p-6">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <div class="rounded-xl border border-slate-200 p-4">
                        <p class="text-xs font-medium uppercase tracking-[0.2em] text-slate-400">Created</p>
                        <p class="mt-2 text-sm font-medium text-slate-700">{{ license.created_at || 'N/A' }}</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 p-4">
                        <p class="text-xs font-medium uppercase tracking-[0.2em] text-slate-400">Last Updated</p>
                        <p class="mt-2 text-sm font-medium text-slate-700">{{ license.updated_at || 'N/A' }}</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 p-4">
                        <p class="text-xs font-medium uppercase tracking-[0.2em] text-slate-400">Status</p>
                        <p class="mt-2 text-sm font-medium text-slate-700">{{ statusLabel(license.status) }}</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 p-4">
                        <p class="text-xs font-medium uppercase tracking-[0.2em] text-slate-400">CDL</p>
                        <p class="mt-2 text-sm font-medium text-slate-700">{{ license.is_cdl ? 'Yes' : 'No' }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
