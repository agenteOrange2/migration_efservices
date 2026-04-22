<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import { FormInput, FormSelect } from '@/components/Base/Form'
import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import RazeLayout from '@/layouts/RazeLayout.vue'
import { useDebounceFn } from '@vueuse/core'

defineOptions({ layout: RazeLayout })

interface UserItem {
    id: number
    name: string
    email: string
    status: number
    roles: string[]
    profile_photo_url: string | null
    created_at: string
}

const props = defineProps<{
    users: {
        data: UserItem[]
        links: any[]
        current_page: number
        last_page: number
        total: number
    }
    filters: Record<string, string>
    stats: { total: number; active: number; inactive: number }
    roles: { id: number; name: string }[]
}>()

const search = ref(props.filters.search ?? '')
const statusFilter = ref(props.filters.status ?? 'all')
const roleFilter = ref(props.filters.role ?? '')

function applyFilters() {
    router.get(route('admin.users.index'), {
        search: search.value || undefined,
        status: statusFilter.value !== 'all' ? statusFilter.value : undefined,
        role: roleFilter.value || undefined,
    }, { preserveState: true, replace: true })
}

const debouncedSearch = useDebounceFn(applyFilters, 400)
watch(search, debouncedSearch)
watch([statusFilter, roleFilter], applyFilters)

function deleteUser(user: UserItem) {
    if (confirm(`Are you sure you want to delete "${user.name}"? This action cannot be undone.`)) {
        router.delete(route('admin.users.destroy', user.id))
    }
}

function statusBadge(status: number) {
    return {
        label: status === 1 ? 'Active' : 'Inactive',
        class: status === 1 ? 'bg-success/10 text-success' : 'bg-danger/10 text-danger',
    }
}

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
</script>

<template>
    <Head title="Users Management" />

    <div class="grid grid-cols-12 gap-y-10 gap-x-6">
        <div class="col-span-12">
            <div class="box box--stacked p-6">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex items-center gap-4">
                        <div class="rounded-2xl border border-primary/20 bg-primary/10 p-3">
                            <Lucide icon="Users" class="h-8 w-8 text-primary" />
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-slate-800">Users Management</div>
                            <p class="text-sm text-slate-500">Manage admin, carrier, and driver accounts using the same theme and patterns as the rest of the dashboard.</p>
                        </div>
                    </div>

                    <Link :href="route('admin.users.create')">
                        <Button variant="primary" class="w-full sm:w-auto">
                            <Lucide icon="UserPlus" class="mr-2 h-4 w-4" /> Add User
                        </Button>
                    </Link>
                </div>
            </div>
        </div>

        <div class="col-span-12">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div class="box box--stacked border border-primary/10 bg-primary/5 p-4 text-center">
                    <div class="text-2xl font-bold text-primary">{{ stats.total }}</div>
                    <div class="mt-1 text-xs text-slate-500">Total Users</div>
                </div>
                <div class="box box--stacked border border-success/10 bg-success/5 p-4 text-center">
                    <div class="text-2xl font-bold text-success">{{ stats.active }}</div>
                    <div class="mt-1 text-xs text-slate-500">Active</div>
                </div>
                <div class="box box--stacked border border-danger/10 bg-danger/5 p-4 text-center">
                    <div class="text-2xl font-bold text-danger">{{ stats.inactive }}</div>
                    <div class="mt-1 text-xs text-slate-500">Inactive</div>
                </div>
            </div>
        </div>

        <div class="col-span-12">
            <div class="box box--stacked p-5">
                <div class="mb-5 flex items-center gap-3">
                    <Lucide icon="Filter" class="h-5 w-5 text-primary" />
                    <h2 class="text-base font-semibold text-slate-800">Filters</h2>
                </div>

                <div class="flex flex-col gap-4 lg:flex-row">
                    <div class="flex-1">
                        <div class="relative">
                            <Lucide icon="Search" class="absolute inset-y-0 left-0 my-auto ml-3 h-4 w-4 text-slate-400" />
                            <FormInput
                                v-model="search"
                                type="text"
                                placeholder="Search by name or email..."
                                class="pl-10"
                            />
                        </div>
                    </div>

                    <FormSelect v-model="statusFilter" class="w-full lg:w-40">
                        <option value="all">All Status</option>
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </FormSelect>

                    <FormSelect v-model="roleFilter" class="w-full lg:w-48">
                        <option value="">All Roles</option>
                        <option v-for="r in roles" :key="r.id" :value="r.name">{{ roleLabel(r.name) }}</option>
                    </FormSelect>
                </div>
            </div>
        </div>

        <div class="col-span-12">
            <div class="box box--stacked overflow-hidden p-0">
                <div class="border-b border-slate-200/60 px-5 py-4">
                    <h2 class="text-base font-semibold text-slate-800">Users Directory</h2>
                    <p class="text-sm text-slate-500">{{ users.total }} total record<span v-if="users.total !== 1">s</span></p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-slate-50/80">
                                <th class="px-5 py-3 text-xs font-medium uppercase text-slate-500">User</th>
                                <th class="px-5 py-3 text-xs font-medium uppercase text-slate-500">Email</th>
                                <th class="px-5 py-3 text-xs font-medium uppercase text-slate-500">Roles</th>
                                <th class="px-5 py-3 text-xs font-medium uppercase text-slate-500">Status</th>
                                <th class="px-5 py-3 text-xs font-medium uppercase text-slate-500">Created</th>
                                <th class="px-5 py-3 text-center text-xs font-medium uppercase text-slate-500">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="u in users.data" :key="u.id" class="border-b border-slate-100 transition hover:bg-slate-50/50">
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center overflow-hidden rounded-full bg-primary/10">
                                            <img v-if="u.profile_photo_url" :src="u.profile_photo_url" :alt="u.name" class="h-full w-full object-cover" />
                                            <Lucide v-else icon="User" class="h-4 w-4 text-primary" />
                                        </div>

                                        <div>
                                            <Link :href="route('admin.users.show', u.id)" class="font-medium text-slate-700 transition hover:text-primary">
                                                {{ u.name }}
                                            </Link>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-5 py-4 text-sm text-slate-600">{{ u.email }}</td>

                                <td class="px-5 py-4">
                                    <div class="flex flex-wrap gap-1.5">
                                        <span
                                            v-for="role in u.roles"
                                            :key="role"
                                            :class="[roleBadge(role), 'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium']"
                                        >
                                            {{ roleLabel(role) }}
                                        </span>
                                    </div>
                                </td>

                                <td class="px-5 py-4">
                                    <span :class="[statusBadge(u.status).class, 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium']">
                                        {{ statusBadge(u.status).label }}
                                    </span>
                                </td>

                                <td class="px-5 py-4 text-sm text-slate-500">
                                    {{ new Date(u.created_at).toLocaleDateString() }}
                                </td>

                                <td class="px-5 py-4">
                                    <div class="flex items-center justify-center gap-2">
                                        <Link :href="route('admin.users.show', u.id)" class="rounded-lg border border-slate-200 p-1.5 text-slate-400 transition hover:border-primary/30 hover:text-primary" title="View">
                                            <Lucide icon="Eye" class="h-4 w-4" />
                                        </Link>
                                        <Link :href="route('admin.users.edit', u.id)" class="rounded-lg border border-slate-200 p-1.5 text-slate-400 transition hover:border-warning/30 hover:text-warning" title="Edit">
                                            <Lucide icon="PenLine" class="h-4 w-4" />
                                        </Link>
                                        <button @click="deleteUser(u)" class="rounded-lg border border-slate-200 p-1.5 text-slate-400 transition hover:border-danger/30 hover:text-danger" title="Delete">
                                            <Lucide icon="Trash2" class="h-4 w-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="!users.data.length">
                                <td colspan="6" class="px-5 py-12 text-center text-slate-400">
                                    <Lucide icon="Users" class="mx-auto mb-3 h-12 w-12 text-slate-300" />
                                    <p>No users found</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="users.last_page > 1" class="flex items-center justify-between border-t border-slate-200/60 p-4">
                    <span class="text-sm text-slate-500">{{ users.total }} users total</span>
                    <div class="flex gap-1">
                        <template v-for="link in users.links" :key="link.label">
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                class="rounded px-3 py-1 text-sm"
                                :class="link.active ? 'bg-primary text-white' : 'text-slate-600 hover:bg-slate-100'"
                                v-html="link.label"
                            />
                            <span v-else class="px-3 py-1 text-sm text-slate-300" v-html="link.label" />
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
