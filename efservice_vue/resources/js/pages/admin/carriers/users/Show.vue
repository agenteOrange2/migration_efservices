<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import Lucide from '@/components/Base/Lucide'
import Button from '@/components/Base/Button'
import RazeLayout from '@/layouts/RazeLayout.vue'

declare function route(name: string, params?: any): string

defineOptions({ layout: RazeLayout })

const props = defineProps<{
    carrier: { id: number; name: string; slug: string }
    userCarrier: {
        id: number
        phone: string | null
        job_position: string | null
        status: number
        status_name: string
        created_at: string | null
        updated_at: string | null
        user: {
            id: number
            name: string
            email: string
            status: number
            created_at: string | null
            profile_photo_url: string | null
        } | null
    }
}>()

const statusBadge: Record<number, string> = {
    0: 'bg-danger/10 text-danger',
    1: 'bg-success/10 text-success',
    2: 'bg-warning/10 text-warning',
}
const statusLabel: Record<number, string> = { 0: 'Inactive', 1: 'Active', 2: 'Pending' }
</script>

<template>
    <Head :title="`User: ${userCarrier.user?.name ?? 'Carrier User'}`" />

    <div class="grid grid-cols-12 gap-x-6 gap-y-6">

        <!-- ══ HEADER ═══════════════════════════════════════════════════════════ -->
        <div class="col-span-12">
            <div class="box box--stacked p-5">
                <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                    <!-- Avatar + Identity -->
                    <div class="flex items-center gap-4">
                        <div class="w-16 h-16 rounded-full bg-slate-100 border-2 border-white shadow flex items-center justify-center overflow-hidden flex-shrink-0">
                            <img v-if="userCarrier.user?.profile_photo_url" :src="userCarrier.user.profile_photo_url" :alt="userCarrier.user?.name" class="w-full h-full object-cover" />
                            <Lucide v-else icon="User" class="w-8 h-8 text-slate-400" />
                        </div>
                        <div>
                            <h1 class="text-xl md:text-2xl font-bold text-slate-800">{{ userCarrier.user?.name ?? 'N/A' }}</h1>
                            <p class="text-sm text-slate-500 flex items-center gap-1.5 mt-0.5">
                                <Lucide icon="Mail" class="w-3.5 h-3.5" />
                                {{ userCarrier.user?.email ?? '-' }}
                            </p>
                            <div class="flex flex-wrap items-center gap-3 mt-2">
                                <span v-if="userCarrier.phone" class="text-sm text-slate-500 flex items-center gap-1">
                                    <Lucide icon="Phone" class="w-3.5 h-3.5" /> {{ userCarrier.phone }}
                                </span>
                                <span class="text-sm text-slate-500 flex items-center gap-1">
                                    <Lucide icon="Building2" class="w-3.5 h-3.5" /> {{ carrier.name }}
                                </span>
                                <span :class="[statusBadge[userCarrier.status] ?? 'bg-slate-100 text-slate-500', 'inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium']">
                                    {{ statusLabel[userCarrier.status] ?? 'Unknown' }}
                                </span>
                                <span v-if="userCarrier.job_position" class="text-xs text-slate-400 capitalize">
                                    {{ userCarrier.job_position }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <!-- Actions -->
                    <div class="flex flex-wrap gap-2">
                        <Link :href="route('admin.carriers.show', carrier.slug)">
                            <Button variant="outline-secondary" size="sm" class="flex items-center gap-1.5">
                                <Lucide icon="ArrowLeft" class="w-4 h-4" /> Back
                            </Button>
                        </Link>
                        <Link :href="route('admin.carriers.users.index', carrier.slug)">
                            <Button variant="outline-secondary" size="sm" class="flex items-center gap-1.5">
                                <Lucide icon="Users" class="w-4 h-4" /> All Users
                            </Button>
                        </Link>
                        <Link :href="route('admin.carriers.user-carriers.edit', { carrier: carrier.slug, userCarrierDetail: userCarrier.id })">
                            <Button variant="primary" size="sm" class="flex items-center gap-1.5">
                                <Lucide icon="Pencil" class="w-4 h-4" /> Edit
                            </Button>
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <!-- ══ CONTACT INFO ══════════════════════════════════════════════════════ -->
        <div class="col-span-12 lg:col-span-7">
            <div class="box box--stacked p-6 h-full">
                <h2 class="text-base font-semibold text-slate-800 mb-4 flex items-center gap-2">
                    <Lucide icon="Contact" class="w-4 h-4 text-primary" />
                    Contact Information
                </h2>
                <div class="grid grid-cols-2 gap-3">
                    <div class="bg-slate-50/60 rounded-lg p-3">
                        <p class="text-xs text-slate-500 mb-0.5">Full Name</p>
                        <p class="text-sm font-medium text-slate-800">{{ userCarrier.user?.name ?? 'N/A' }}</p>
                    </div>
                    <div class="bg-slate-50/60 rounded-lg p-3">
                        <p class="text-xs text-slate-500 mb-0.5">Email</p>
                        <p class="text-sm font-medium text-slate-800 truncate">{{ userCarrier.user?.email ?? '-' }}</p>
                    </div>
                    <div class="bg-slate-50/60 rounded-lg p-3">
                        <p class="text-xs text-slate-500 mb-0.5">Phone</p>
                        <p class="text-sm font-medium text-slate-800">{{ userCarrier.phone ?? 'N/A' }}</p>
                    </div>
                    <div class="bg-slate-50/60 rounded-lg p-3">
                        <p class="text-xs text-slate-500 mb-0.5">Job Position</p>
                        <p class="text-sm font-medium text-slate-800 capitalize">{{ userCarrier.job_position ?? 'N/A' }}</p>
                    </div>
                    <div class="bg-slate-50/60 rounded-lg p-3">
                        <p class="text-xs text-slate-500 mb-0.5">Member Since</p>
                        <p class="text-sm font-medium text-slate-800">{{ userCarrier.user?.created_at ?? '-' }}</p>
                    </div>
                    <div class="bg-slate-50/60 rounded-lg p-3">
                        <p class="text-xs text-slate-500 mb-0.5">Status</p>
                        <span :class="[statusBadge[userCarrier.status] ?? 'bg-slate-100 text-slate-500', 'inline-flex px-2 py-0.5 rounded-full text-xs font-medium']">
                            {{ statusLabel[userCarrier.status] ?? 'Unknown' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- ══ CARRIER + RECORD INFO ═════════════════════════════════════════════ -->
        <div class="col-span-12 lg:col-span-5">
            <div class="box box--stacked p-6 h-full">
                <h2 class="text-base font-semibold text-slate-800 mb-4 flex items-center gap-2">
                    <Lucide icon="Building2" class="w-4 h-4 text-primary" />
                    Carrier Information
                </h2>
                <div class="space-y-2">
                    <div class="bg-slate-50/60 rounded-lg p-3">
                        <p class="text-xs text-slate-500 mb-0.5">Carrier</p>
                        <p class="text-sm font-semibold text-slate-800">{{ carrier.name }}</p>
                    </div>
                    <div class="bg-slate-50/60 rounded-lg p-3">
                        <p class="text-xs text-slate-500 mb-0.5">Record Created</p>
                        <p class="text-sm font-medium text-slate-800">{{ userCarrier.created_at ?? '-' }}</p>
                    </div>
                    <div class="bg-slate-50/60 rounded-lg p-3">
                        <p class="text-xs text-slate-500 mb-0.5">Last Updated</p>
                        <p class="text-sm font-medium text-slate-800">{{ userCarrier.updated_at ?? '-' }}</p>
                    </div>
                </div>
            </div>
        </div>

    </div>
</template>
