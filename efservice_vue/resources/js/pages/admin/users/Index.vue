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

const statusBadge = (status: number) => ({
    label: status === 1 ? 'Active' : 'Inactive',
    class: status === 1 ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700',
})

const roleBadge = (role: string) => {
    const map: Record<string, string> = {
        superadmin: 'bg-purple-100 text-purple-700',
        user_carrier: 'bg-blue-100 text-blue-700',
        user_driver: 'bg-amber-100 text-amber-700',
    }
    return map[role] ?? 'bg-slate-100 text-slate-600'
}
</script>

<template>
    <Head title="Users Management" />

    <div class="grid grid-cols-12 gap-y-10 gap-x-6">
        <!-- Header -->
        <div class="col-span-12">
            <div class="flex flex-col md:h-10 gap-y-3 md:items-center md:flex-row">
                <div class="text-base font-medium">Users Management</div>
                <div class="flex flex-col sm:flex-row gap-x-3 gap-y-2 md:ml-auto">
                    <Link :href="route('admin.users.create')">
                        <Button variant="primary" class="w-full sm:w-auto">
                            <Lucide icon="UserPlus" class="w-4 h-4 mr-2" /> Add User
                        </Button>
                    </Link>
                </div>
            </div>
        </div>

        <!-- Stats -->
        <div class="col-span-12">
            <div class="grid grid-cols-3 gap-4">
                <div class="box box--stacked p-4 text-center">
                    <div class="text-2xl font-bold text-slate-800">{{ stats.total }}</div>
                    <div class="text-xs text-slate-500 mt-1">Total Users</div>
                </div>
                <div class="box box--stacked p-4 text-center">
                    <div class="text-2xl font-bold text-emerald-600">{{ stats.active }}</div>
                    <div class="text-xs text-slate-500 mt-1">Active</div>
                </div>
                <div class="box box--stacked p-4 text-center">
                    <div class="text-2xl font-bold text-red-500">{{ stats.inactive }}</div>
                    <div class="text-xs text-slate-500 mt-1">Inactive</div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="col-span-12">
            <div class="box box--stacked p-5">
                <div class="flex flex-col lg:flex-row gap-4">
                    <div class="flex-1">
                        <div class="relative">
                            <Lucide icon="Search" class="absolute inset-y-0 left-0 w-4 h-4 my-auto ml-3 text-slate-400" />
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
                    <FormSelect v-model="roleFilter" class="w-full lg:w-44">
                        <option value="">All Roles</option>
                        <option v-for="r in roles" :key="r.id" :value="r.name">{{ r.name }}</option>
                    </FormSelect>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="col-span-12">
            <div class="box box--stacked p-0">
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-slate-50/80">
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">User</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Email</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Roles</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Status</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Created</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="u in users.data" :key="u.id" class="border-b border-slate-100 hover:bg-slate-50/50 transition">
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-full bg-primary/10 flex items-center justify-center flex-shrink-0 overflow-hidden">
                                            <img v-if="u.profile_photo_url" :src="u.profile_photo_url" :alt="u.name" class="w-full h-full object-cover" />
                                            <Lucide v-else icon="User" class="w-4 h-4 text-primary" />
                                        </div>
                                        <Link :href="route('admin.users.show', u.id)" class="font-medium text-slate-700 hover:text-primary transition">
                                            {{ u.name }}
                                        </Link>
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-sm text-slate-600">{{ u.email }}</td>
                                <td class="px-5 py-4">
                                    <div class="flex flex-wrap gap-1">
                                        <span
                                            v-for="role in u.roles"
                                            :key="role"
                                            :class="[roleBadge(role), 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium']"
                                        >
                                            {{ role }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <span :class="[statusBadge(u.status).class, 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium']">
                                        {{ statusBadge(u.status).label }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-sm text-slate-500">
                                    {{ new Date(u.created_at).toLocaleDateString() }}
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center justify-center gap-2">
                                        <Link :href="route('admin.users.show', u.id)" class="p-1.5 text-slate-400 hover:text-primary transition" title="View">
                                            <Lucide icon="Eye" class="w-4 h-4" />
                                        </Link>
                                        <Link :href="route('admin.users.edit', u.id)" class="p-1.5 text-slate-400 hover:text-amber-500 transition" title="Edit">
                                            <Lucide icon="PenLine" class="w-4 h-4" />
                                        </Link>
                                        <button @click="deleteUser(u)" class="p-1.5 text-slate-400 hover:text-red-500 transition" title="Delete">
                                            <Lucide icon="Trash2" class="w-4 h-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!users.data.length">
                                <td colspan="6" class="px-5 py-12 text-center text-slate-400">
                                    <Lucide icon="Users" class="w-12 h-12 mx-auto mb-3 text-slate-300" />
                                    <p>No users found</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="users.last_page > 1" class="p-4 border-t border-slate-200/60 flex items-center justify-between">
                    <span class="text-sm text-slate-500">{{ users.total }} users total</span>
                    <div class="flex gap-1">
                        <template v-for="link in users.links" :key="link.label">
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                class="px-3 py-1 text-sm rounded"
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
