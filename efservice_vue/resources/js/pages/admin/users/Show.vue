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
const statusClass = props.user.status === 1 ? 'bg-success/10 text-success' : 'bg-danger/10 text-danger'

function roleBadge(role: string) {
    const map: Record<string, string> = {
        superadmin: 'bg-danger/10 text-danger',
        admin: 'bg-primary/10 text-primary',
        user_carrier: 'bg-info/10 text-info',
        user_driver: 'bg-warning/10 text-warning',
    }

    return map[role] ?? 'bg-pending/10 text-pending'
}

function roleLabel(role: string) {
    return role.replace(/_/g, ' ').replace(/\b\w/g, (char) => char.toUpperCase())
}

function driverStatusClass(status: number) {
    return status === 1 ? 'bg-success/10 text-success' : 'bg-danger/10 text-danger'
}

const daysSinceCreation = Math.floor(
    (Date.now() - new Date(props.user.created_at).getTime()) / (1000 * 60 * 60 * 24)
)
</script>

<template>
    <Head :title="user.name" />

    <div class="grid grid-cols-12 gap-y-10 gap-x-6">
        <div class="col-span-12">
            <div class="box box--stacked p-6">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex items-center gap-4">
                        <div class="rounded-2xl border border-primary/20 bg-primary/10 p-3">
                            <Lucide icon="User" class="h-8 w-8 text-primary" />
                        </div>
                        <div>
                            <div class="text-base font-medium">
                                <Link :href="route('admin.users.index')" class="text-primary hover:underline">Users</Link>
                                <span class="mx-2 text-slate-400">/</span>
                                {{ user.name }}
                            </div>
                            <p class="mt-1 text-sm text-slate-500">Profile details, role assignments, and linked carrier or driver information.</p>
                        </div>
                    </div>

                    <div class="flex flex-col gap-2 sm:flex-row sm:gap-3">
                        <Link :href="route('admin.users.edit', user.id)">
                            <Button variant="outline-primary" class="w-full sm:w-auto">
                                <Lucide icon="PenSquare" class="mr-2 h-4 w-4" /> Edit User
                            </Button>
                        </Link>
                        <Button variant="outline-danger" class="w-full sm:w-auto" @click="deleteUser">
                            <Lucide icon="Trash2" class="mr-2 h-4 w-4" /> Delete
                        </Button>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12 lg:col-span-4">
            <div class="box box--stacked p-6">
                <div class="flex flex-col items-center text-center">
                    <div class="mb-4 flex h-24 w-24 items-center justify-center overflow-hidden rounded-full border-4 border-white bg-primary/10 shadow-lg">
                        <img v-if="user.profile_photo_url" :src="user.profile_photo_url" :alt="user.name" class="h-full w-full object-cover" />
                        <Lucide v-else icon="User" class="h-12 w-12 text-primary" />
                    </div>

                    <h2 class="text-xl font-bold text-slate-800">{{ user.name }}</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ user.email }}</p>

                    <span :class="[statusClass, 'mt-3 inline-flex items-center rounded-full px-3 py-1 text-xs font-medium']">
                        {{ statusLabel }}
                    </span>

                    <div class="mt-4 flex flex-wrap justify-center gap-2">
                        <span
                            v-for="r in user.roles"
                            :key="r.id"
                            :class="[roleBadge(r.name), 'inline-flex items-center rounded-full px-3 py-1 text-xs font-medium']"
                        >
                            {{ roleLabel(r.name) }}
                        </span>
                    </div>
                </div>

                <div class="mt-6 space-y-3 border-t border-slate-200 pt-6">
                    <div class="flex items-center gap-3 text-sm">
                        <Lucide icon="Calendar" class="h-4 w-4 text-slate-400" />
                        <span class="text-slate-600">Joined {{ new Date(user.created_at).toLocaleDateString() }}</span>
                    </div>
                    <div class="flex items-center gap-3 text-sm">
                        <Lucide icon="Clock" class="h-4 w-4 text-slate-400" />
                        <span class="text-slate-600">{{ daysSinceCreation }} days since creation</span>
                    </div>
                    <div class="flex items-center gap-3 text-sm">
                        <Lucide icon="RefreshCw" class="h-4 w-4 text-slate-400" />
                        <span class="text-slate-600">Last updated {{ new Date(user.updated_at).toLocaleDateString() }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12 space-y-6 lg:col-span-8">
            <div class="box box--stacked p-6">
                <div class="mb-6 flex items-center gap-3">
                    <Lucide icon="Info" class="h-5 w-5 text-primary" />
                    <h3 class="text-lg font-semibold text-slate-800">Main Information</h3>
                </div>

                <dl class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="rounded-lg border border-primary/10 bg-primary/5 p-4">
                        <dt class="mb-1 text-xs font-medium uppercase tracking-wide text-slate-500">Full Name</dt>
                        <dd class="text-sm font-semibold text-slate-800">{{ user.name }}</dd>
                    </div>
                    <div class="rounded-lg border border-info/10 bg-info/5 p-4">
                        <dt class="mb-1 text-xs font-medium uppercase tracking-wide text-slate-500">Email</dt>
                        <dd class="text-sm font-semibold text-slate-800">{{ user.email }}</dd>
                    </div>
                    <div class="rounded-lg border p-4" :class="user.status === 1 ? 'border-success/10 bg-success/5' : 'border-danger/10 bg-danger/5'">
                        <dt class="mb-1 text-xs font-medium uppercase tracking-wide text-slate-500">Status</dt>
                        <dd>
                            <span :class="[statusClass, 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium']">
                                {{ statusLabel }}
                            </span>
                        </dd>
                    </div>
                    <div class="rounded-lg border border-pending/10 bg-pending/5 p-4">
                        <dt class="mb-1 text-xs font-medium uppercase tracking-wide text-slate-500">Created At</dt>
                        <dd class="text-sm font-semibold text-slate-800">{{ new Date(user.created_at).toLocaleString() }}</dd>
                    </div>
                </dl>
            </div>

            <div class="box box--stacked p-6">
                <div class="mb-6 flex items-center gap-3">
                    <Lucide icon="Shield" class="h-5 w-5 text-primary" />
                    <h3 class="text-lg font-semibold text-slate-800">Roles & Permissions</h3>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="rounded-lg border border-primary/10 bg-primary/5 p-4 text-center">
                        <div class="text-2xl font-bold text-primary">{{ user.roles.length }}</div>
                        <div class="mt-1 text-xs text-slate-500">Assigned Roles</div>
                    </div>
                    <div class="rounded-lg border border-info/10 bg-info/5 p-4 text-center">
                        <div class="text-2xl font-bold text-info">{{ daysSinceCreation }}</div>
                        <div class="mt-1 text-xs text-slate-500">Days Active</div>
                    </div>
                </div>

                <div class="mt-4 space-y-2">
                    <div
                        v-for="r in user.roles"
                        :key="r.id"
                        class="flex items-center gap-3 rounded-lg border p-3"
                        :class="roleBadge(r.name)"
                    >
                        <div class="flex h-8 w-8 items-center justify-center rounded-full bg-white/80">
                            <Lucide icon="Shield" class="h-4 w-4" />
                        </div>
                        <span class="text-sm font-medium">{{ roleLabel(r.name) }}</span>
                    </div>
                </div>
            </div>

            <div v-if="carrierInfo || driverInfo" class="box box--stacked p-6">
                <div class="mb-6 flex items-center gap-3">
                    <Lucide icon="Link" class="h-5 w-5 text-primary" />
                    <h3 class="text-lg font-semibold text-slate-800">Associated Details</h3>
                </div>

                <div v-if="carrierInfo" class="mb-4 rounded-lg border border-info/10 bg-info/5 p-4">
                    <h4 class="mb-2 text-xs font-medium uppercase tracking-wide text-info">Carrier Association</h4>
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

                <div v-if="driverInfo" class="rounded-lg border border-warning/10 bg-warning/5 p-4">
                    <h4 class="mb-2 text-xs font-medium uppercase tracking-wide text-warning">Driver Association</h4>
                    <dl class="grid grid-cols-2 gap-2">
                        <div>
                            <dt class="text-xs text-slate-500">Carrier</dt>
                            <dd class="text-sm font-medium">{{ driverInfo.carrier_name ?? 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-slate-500">Status</dt>
                            <dd>
                                <span :class="[driverStatusClass(driverInfo.status), 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium']">
                                    {{ driverInfo.status === 1 ? 'Active' : 'Inactive' }}
                                </span>
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</template>
