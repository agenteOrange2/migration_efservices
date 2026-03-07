<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import RazeLayout from '@/layouts/RazeLayout.vue'

defineOptions({ layout: RazeLayout })

interface UserData {
    id: number
    name: string
    email: string
    status: number
    roles: { id: number; name: string }[]
    profile_photo_url: string | null
    created_at: string
    updated_at: string
}

const props = defineProps<{
    user: UserData
    carrierInfo: {
        carrier_id: number
        carrier_name: string | null
        phone: string
        job_position: string
    } | null
    driverInfo: {
        carrier_name: string | null
        status: number
    } | null
}>()

function deleteUser() {
    if (confirm(`Are you sure you want to delete "${props.user.name}"? This action cannot be undone.`)) {
        router.delete(route('admin.users.destroy', props.user.id))
    }
}

const statusLabel = props.user.status === 1 ? 'Active' : 'Inactive'
const statusClass = props.user.status === 1 ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700'

const roleBadge = (role: string) => {
    const map: Record<string, string> = {
        superadmin: 'bg-purple-100 text-purple-700',
        user_carrier: 'bg-blue-100 text-blue-700',
        user_driver: 'bg-amber-100 text-amber-700',
    }
    return map[role] ?? 'bg-slate-100 text-slate-600'
}

const daysSinceCreation = Math.floor(
    (Date.now() - new Date(props.user.created_at).getTime()) / (1000 * 60 * 60 * 24)
)
</script>

<template>
    <Head :title="user.name" />

    <div class="grid grid-cols-12 gap-y-10 gap-x-6">
        <!-- Header -->
        <div class="col-span-12">
            <div class="flex flex-col md:h-10 gap-y-3 md:items-center md:flex-row">
                <div class="text-base font-medium">
                    <Link :href="route('admin.users.index')" class="text-primary hover:underline">Users</Link>
                    <span class="mx-2 text-slate-400">/</span>
                    {{ user.name }}
                </div>
                <div class="flex flex-col sm:flex-row gap-x-3 gap-y-2 md:ml-auto">
                    <Link :href="route('admin.users.edit', user.id)">
                        <Button variant="outline-primary" class="w-full sm:w-auto">
                            <Lucide icon="PenSquare" class="w-4 h-4 mr-2" /> Edit User
                        </Button>
                    </Link>
                    <Button variant="outline-danger" class="w-full sm:w-auto" @click="deleteUser">
                        <Lucide icon="Trash2" class="w-4 h-4 mr-2" /> Delete
                    </Button>
                </div>
            </div>
        </div>

        <!-- Profile Card -->
        <div class="col-span-12 lg:col-span-4">
            <div class="box box--stacked p-6">
                <div class="flex flex-col items-center text-center">
                    <div class="w-24 h-24 rounded-full bg-primary/10 flex items-center justify-center overflow-hidden border-4 border-white shadow-lg mb-4">
                        <img v-if="user.profile_photo_url" :src="user.profile_photo_url" :alt="user.name" class="w-full h-full object-cover" />
                        <Lucide v-else icon="User" class="w-12 h-12 text-primary" />
                    </div>
                    <h2 class="text-xl font-bold text-slate-800">{{ user.name }}</h2>
                    <p class="text-sm text-slate-500 mt-1">{{ user.email }}</p>

                    <span :class="[statusClass, 'inline-flex items-center px-3 py-1 rounded-full text-xs font-medium mt-3']">
                        {{ statusLabel }}
                    </span>

                    <div class="flex flex-wrap justify-center gap-2 mt-4">
                        <span
                            v-for="r in user.roles"
                            :key="r.id"
                            :class="[roleBadge(r.name), 'inline-flex items-center px-3 py-1 rounded-full text-xs font-medium']"
                        >
                            {{ r.name }}
                        </span>
                    </div>
                </div>

                <div class="mt-6 pt-6 border-t border-slate-200 space-y-3">
                    <div class="flex items-center gap-3 text-sm">
                        <Lucide icon="Calendar" class="w-4 h-4 text-slate-400" />
                        <span class="text-slate-600">Joined {{ new Date(user.created_at).toLocaleDateString() }}</span>
                    </div>
                    <div class="flex items-center gap-3 text-sm">
                        <Lucide icon="Clock" class="w-4 h-4 text-slate-400" />
                        <span class="text-slate-600">{{ daysSinceCreation }} days since creation</span>
                    </div>
                    <div class="flex items-center gap-3 text-sm">
                        <Lucide icon="RefreshCw" class="w-4 h-4 text-slate-400" />
                        <span class="text-slate-600">Last updated {{ new Date(user.updated_at).toLocaleDateString() }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Details -->
        <div class="col-span-12 lg:col-span-8 space-y-6">
            <!-- Main Info -->
            <div class="box box--stacked p-6">
                <div class="flex items-center gap-3 mb-6">
                    <Lucide icon="Info" class="w-5 h-5 text-primary" />
                    <h3 class="text-lg font-semibold text-slate-800">Main Information</h3>
                </div>

                <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                        <dt class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1">Full Name</dt>
                        <dd class="text-sm font-semibold text-slate-800">{{ user.name }}</dd>
                    </div>
                    <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                        <dt class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1">Email</dt>
                        <dd class="text-sm font-semibold text-slate-800">{{ user.email }}</dd>
                    </div>
                    <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                        <dt class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1">Status</dt>
                        <dd>
                            <span :class="[statusClass, 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium']">
                                {{ statusLabel }}
                            </span>
                        </dd>
                    </div>
                    <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                        <dt class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1">Created At</dt>
                        <dd class="text-sm font-semibold text-slate-800">{{ new Date(user.created_at).toLocaleString() }}</dd>
                    </div>
                </dl>
            </div>

            <!-- Roles -->
            <div class="box box--stacked p-6">
                <div class="flex items-center gap-3 mb-6">
                    <Lucide icon="Shield" class="w-5 h-5 text-primary" />
                    <h3 class="text-lg font-semibold text-slate-800">Roles & Permissions</h3>
                </div>

                <div class="grid grid-cols-2 lg:grid-cols-3 gap-4">
                    <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100 text-center">
                        <div class="text-2xl font-bold text-primary">{{ user.roles.length }}</div>
                        <div class="text-xs text-slate-500 mt-1">Assigned Roles</div>
                    </div>
                    <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100 text-center">
                        <div class="text-2xl font-bold text-slate-700">{{ daysSinceCreation }}</div>
                        <div class="text-xs text-slate-500 mt-1">Days Active</div>
                    </div>
                </div>

                <div class="mt-4 space-y-2">
                    <div
                        v-for="r in user.roles"
                        :key="r.id"
                        class="flex items-center gap-3 p-3 rounded-lg border border-slate-100 bg-slate-50/50"
                    >
                        <div :class="[roleBadge(r.name), 'w-8 h-8 rounded-full flex items-center justify-center']">
                            <Lucide icon="Shield" class="w-4 h-4" />
                        </div>
                        <span class="text-sm font-medium text-slate-700">{{ r.name }}</span>
                    </div>
                </div>
            </div>

            <!-- Carrier/Driver Info -->
            <div v-if="carrierInfo || driverInfo" class="box box--stacked p-6">
                <div class="flex items-center gap-3 mb-6">
                    <Lucide icon="Link" class="w-5 h-5 text-primary" />
                    <h3 class="text-lg font-semibold text-slate-800">Associated Details</h3>
                </div>

                <div v-if="carrierInfo" class="bg-blue-50/50 rounded-lg p-4 border border-blue-100 mb-4">
                    <h4 class="text-xs font-medium text-blue-600 uppercase tracking-wide mb-2">Carrier Association</h4>
                    <dl class="grid grid-cols-2 gap-2">
                        <div>
                            <dt class="text-xs text-slate-500">Carrier</dt>
                            <dd class="text-sm font-medium">{{ carrierInfo.carrier_name ?? 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-slate-500">Phone</dt>
                            <dd class="text-sm font-medium">{{ carrierInfo.phone }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-slate-500">Position</dt>
                            <dd class="text-sm font-medium">{{ carrierInfo.job_position }}</dd>
                        </div>
                    </dl>
                </div>

                <div v-if="driverInfo" class="bg-amber-50/50 rounded-lg p-4 border border-amber-100">
                    <h4 class="text-xs font-medium text-amber-600 uppercase tracking-wide mb-2">Driver Association</h4>
                    <dl class="grid grid-cols-2 gap-2">
                        <div>
                            <dt class="text-xs text-slate-500">Carrier</dt>
                            <dd class="text-sm font-medium">{{ driverInfo.carrier_name ?? 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-slate-500">Status</dt>
                            <dd class="text-sm font-medium">{{ driverInfo.status === 1 ? 'Active' : 'Inactive' }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</template>
