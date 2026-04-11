<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { computed } from 'vue'
import Lucide from '@/components/Base/Lucide'
import Button from '@/components/Base/Button'
import RazeLayout from '@/layouts/RazeLayout.vue'

declare function route(name: string, params?: any): string

defineOptions({ layout: RazeLayout })

interface LicenseRouteNames {
    index: string
    edit: string
    destroy?: string
    documentsShow: string
    driverShow?: string
}

interface LicenseShowPayload {
    id: number
    license_number: string
    license_class: string | null
    state_of_issue: string | null
    expiration_date: string | null
    restrictions: string | null
    is_cdl: boolean
    is_primary: boolean
    front_url: string | null
    back_url: string | null
    document_count: number
    driver: { id: number; name: string; email: string | null } | null
    carrier: { id: number; name: string } | null
    endorsements: { id: number; code: string; name: string }[]
    documents: {
        id: number
        file_name: string
        preview_url: string
        size_label: string
        mime_type: string | null
        file_type: string
        created_at_display: string | null
    }[]
}

const props = withDefaults(defineProps<{
    license: LicenseShowPayload
    routeNames?: LicenseRouteNames
    isCarrierContext?: boolean
}>(), {
    routeNames: () => ({
        index: 'admin.licenses.index',
        edit: 'admin.licenses.edit',
        destroy: 'admin.licenses.destroy',
        documentsShow: 'admin.licenses.documents.show',
        driverShow: 'admin.drivers.show',
    }),
    isCarrierContext: false,
})

function namedRoute(name: keyof LicenseRouteNames, params?: any) {
    const routeName = props.routeNames[name]

    return routeName ? route(routeName, params) : '#'
}

const expirationStatus = computed(() => {
    if (!props.license.expiration_date) {
        return { label: 'N/A', className: 'bg-slate-100 text-slate-600' }
    }

    const target = new Date(`${props.license.expiration_date}T00:00:00`)
    const today = new Date()
    today.setHours(0, 0, 0, 0)

    const diff = Math.ceil((target.getTime() - today.getTime()) / (1000 * 60 * 60 * 24))

    if (diff < 0) {
        return { label: 'Expired', className: 'bg-red-100 text-red-700' }
    }

    if (diff <= 30) {
        return { label: 'Expires Soon', className: 'bg-amber-100 text-amber-700' }
    }

    return { label: 'Valid', className: 'bg-primary/10 text-primary' }
})

function formatDate(value: string | null) {
    if (!value) return 'N/A'
    return new Date(`${value}T00:00:00`).toLocaleDateString()
}

function deleteLicense() {
    if (!props.routeNames.destroy) return
    if (!confirm(`Delete license "${props.license.license_number}"? This action cannot be undone.`)) return

    router.delete(route(props.routeNames.destroy, props.license.id), {
        preserveScroll: true,
    })
}
</script>

<template>
    <Head title="License Details" />

    <div class="grid grid-cols-12 gap-y-8 gap-x-6">
        <div class="col-span-12">
            <div class="box box--stacked p-6">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex items-center gap-4">
                        <div class="rounded-xl border border-primary/20 bg-primary/10 p-3">
                            <Lucide icon="CreditCard" class="h-8 w-8 text-primary" />
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-slate-800">License Details</h1>
                            <p class="text-slate-500">
                                Review license information, images and supporting documents.
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <Link :href="namedRoute('index')">
                            <Button variant="outline-secondary" class="flex items-center gap-2">
                                <Lucide icon="ArrowLeft" class="w-4 h-4" />
                                Back to Licenses
                            </Button>
                        </Link>
                        <Link :href="namedRoute('edit', license.id)">
                            <Button variant="primary" class="flex items-center gap-2">
                                <Lucide icon="PenLine" class="w-4 h-4" />
                                Edit License
                            </Button>
                        </Link>
                        <Link :href="namedRoute('documentsShow', license.id)">
                            <Button variant="outline-secondary" class="flex items-center gap-2">
                                <Lucide icon="Files" class="w-4 h-4" />
                                Documents ({{ license.document_count }})
                            </Button>
                        </Link>
                        <button
                            v-if="routeNames.destroy"
                            type="button"
                            class="inline-flex items-center gap-2 rounded-lg border border-red-200 px-4 py-2 text-sm font-medium text-red-600 transition hover:bg-red-50"
                            @click="deleteLicense"
                        >
                            <Lucide icon="Trash2" class="w-4 h-4" />
                            Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12 xl:col-span-8">
            <div class="box box--stacked p-6">
                <div class="mb-5 flex items-center gap-2 border-b border-dashed border-slate-300/70 pb-5">
                    <Lucide icon="BadgeInfo" class="w-5 h-5 text-primary" />
                    <h2 class="text-sm font-semibold text-slate-700">License Information</h2>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="rounded-xl border border-slate-200 p-4">
                        <p class="text-xs font-medium uppercase tracking-[0.2em] text-slate-400">Driver</p>
                        <div class="mt-2">
                            <p class="font-semibold text-slate-800">{{ license.driver?.name ?? 'N/A' }}</p>
                            <p class="text-sm text-slate-500">{{ license.driver?.email ?? 'No email' }}</p>
                        </div>
                    </div>

                    <div class="rounded-xl border border-slate-200 p-4">
                        <p class="text-xs font-medium uppercase tracking-[0.2em] text-slate-400">Carrier</p>
                        <p class="mt-2 font-semibold text-slate-800">{{ license.carrier?.name ?? 'N/A' }}</p>
                    </div>

                    <div class="rounded-xl border border-slate-200 p-4">
                        <p class="text-xs font-medium uppercase tracking-[0.2em] text-slate-400">License Number</p>
                        <div class="mt-2 flex flex-wrap items-center gap-2">
                            <span class="font-mono font-semibold text-slate-800">{{ license.license_number }}</span>
                            <span v-if="license.is_primary" class="inline-flex rounded-full bg-primary/10 px-2.5 py-1 text-xs font-medium text-primary">
                                Primary
                            </span>
                            <span v-if="license.is_cdl" class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">
                                CDL
                            </span>
                        </div>
                    </div>

                    <div class="rounded-xl border border-slate-200 p-4">
                        <p class="text-xs font-medium uppercase tracking-[0.2em] text-slate-400">Class / State</p>
                        <p class="mt-2 font-semibold text-slate-800">
                            {{ license.license_class || 'N/A' }} / {{ license.state_of_issue || 'N/A' }}
                        </p>
                    </div>

                    <div class="rounded-xl border border-slate-200 p-4 md:col-span-2">
                        <div class="flex flex-wrap items-center gap-3">
                            <div>
                                <p class="text-xs font-medium uppercase tracking-[0.2em] text-slate-400">Expiration</p>
                                <p class="mt-2 font-semibold text-slate-800">{{ formatDate(license.expiration_date) }}</p>
                            </div>
                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium" :class="expirationStatus.className">
                                {{ expirationStatus.label }}
                            </span>
                        </div>
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
                    <Lucide icon="Award" class="w-5 h-5 text-primary" />
                    <h2 class="text-sm font-semibold text-slate-700">Endorsements</h2>
                </div>

                <div v-if="license.is_cdl && license.endorsements.length" class="space-y-3">
                    <div
                        v-for="endorsement in license.endorsements"
                        :key="endorsement.id"
                        class="flex items-center justify-between rounded-xl border border-slate-200 px-4 py-3"
                    >
                        <span class="text-sm font-medium text-slate-700">{{ endorsement.code }} - {{ endorsement.name }}</span>
                        <span class="inline-flex rounded-full bg-primary/10 px-2.5 py-1 text-xs font-medium text-primary">Active</span>
                    </div>
                </div>

                <div v-else class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">
                    No endorsements registered for this license.
                </div>

                <Link
                    v-if="routeNames.driverShow && license.driver"
                    :href="namedRoute('driverShow', license.driver.id)"
                    class="mt-4 flex items-center justify-between rounded-xl border border-slate-200 px-4 py-3 text-sm text-slate-700 hover:bg-slate-50"
                >
                    <span>Open driver profile</span>
                    <Lucide icon="ArrowUpRight" class="w-4 h-4 text-slate-400" />
                </Link>
            </div>
        </div>

        <div class="col-span-12 xl:col-span-7">
            <div class="box box--stacked p-6">
                <div class="mb-5 flex items-center gap-2 border-b border-dashed border-slate-300/70 pb-5">
                    <Lucide icon="Image" class="w-5 h-5 text-primary" />
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
                    <Lucide icon="Files" class="w-5 h-5 text-primary" />
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
                        <div>
                            <p class="text-sm font-medium text-slate-700">{{ document.file_name }}</p>
                            <p class="mt-1 text-xs text-slate-500">
                                {{ document.size_label }} · {{ document.file_type.toUpperCase() }} · {{ document.created_at_display }}
                            </p>
                        </div>
                        <Lucide icon="ArrowUpRight" class="mt-0.5 w-4 h-4 text-slate-400" />
                    </a>
                </div>
                <div v-else class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">
                    No additional documents uploaded.
                </div>
            </div>
        </div>
    </div>
</template>
