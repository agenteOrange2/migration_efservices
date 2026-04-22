<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'
import Lucide from '@/components/Base/Lucide'
import Button from '@/components/Base/Button'
import { FormInput, FormLabel } from '@/components/Base/Form'
import { Dialog } from '@/components/Base/Headless'
import RazeLayout from '@/layouts/RazeLayout.vue'

declare function route(name: string, params?: any): string

defineOptions({ layout: RazeLayout })

interface PaginationLink {
    url: string | null
    label: string
    active: boolean
}

interface PermissionItem {
    id: number
    name: string
}

interface RoleRow {
    id: number
    name: string
    guard_name: string
    permissions: string[]
    permissions_count: number
    created_at: string | null
}

const props = defineProps<{
    roles: {
        data: RoleRow[]
        links: PaginationLink[]
        total: number
        last_page: number
    }
    stats: {
        total: number
        permissions: number
    }
    permissions: PermissionItem[]
    filters: { search?: string }
}>()

const page = usePage()
const flash = computed(() => (page.props as any).flash ?? {})

// ─── search ───────────────────────────────────────────────────────────────────
const search = ref(props.filters.search ?? '')
let searchTimer: ReturnType<typeof setTimeout>
watch(search, (val) => {
    clearTimeout(searchTimer)
    searchTimer = setTimeout(() => {
        router.get(route('admin.roles.index'), { search: val || undefined }, {
            preserveState: true,
            replace: true,
        })
    }, 350)
})

// ─── create modal ─────────────────────────────────────────────────────────────
const createOpen = ref(false)
const createForm = useForm({ name: '', permissions: [] as number[] })

function openCreate() {
    createForm.reset()
    createOpen.value = true
}

function submitCreate() {
    createForm.post(route('admin.roles.store'), {
        onSuccess: () => { createOpen.value = false },
    })
}

// ─── edit modal ───────────────────────────────────────────────────────────────
const editOpen = ref(false)
const editingRole = ref<RoleRow | null>(null)
const editForm = useForm({ name: '', permissions: [] as number[] })

function openEdit(role: RoleRow) {
    editingRole.value = role
    editForm.name = role.name
    editForm.permissions = props.permissions
        .filter(p => role.permissions.includes(p.name))
        .map(p => p.id)
    editOpen.value = true
}

function submitEdit() {
    if (!editingRole.value) return
    editForm.put(route('admin.roles.update', editingRole.value.id), {
        onSuccess: () => { editOpen.value = false },
    })
}

// ─── delete modal ─────────────────────────────────────────────────────────────
const deleteOpen = ref(false)
const deletingRole = ref<RoleRow | null>(null)

function openDelete(role: RoleRow) {
    deletingRole.value = role
    deleteOpen.value = true
}

function confirmDelete() {
    if (!deletingRole.value) return
    router.delete(route('admin.roles.destroy', deletingRole.value.id), {
        preserveScroll: true,
        onSuccess: () => { deleteOpen.value = false },
    })
}

// ─── permission toggle helper ─────────────────────────────────────────────────
function togglePermission(form: { permissions: number[] }, id: number) {
    const idx = form.permissions.indexOf(id)
    if (idx === -1) form.permissions.push(id)
    else form.permissions.splice(idx, 1)
}

// ─── group permissions by prefix ─────────────────────────────────────────────
const groupedPermissions = computed(() => {
    const groups: Record<string, PermissionItem[]> = {}
    for (const p of props.permissions) {
        const group = p.name.split('.')[0] ?? 'general'
        if (!groups[group]) groups[group] = []
        groups[group].push(p)
    }
    return Object.entries(groups).sort(([a], [b]) => a.localeCompare(b))
})

function formatDate(value: string | null) {
    if (!value) return '—'
    return new Date(value).toLocaleDateString()
}
</script>

<template>
    <Head title="Roles Management" />

    <div class="grid grid-cols-12 gap-x-6 gap-y-10">
        <div class="col-span-12">

            <!-- Flash -->
            <div v-if="flash.success" class="mb-4 flex items-center gap-3 rounded-xl border border-success/30 bg-success/10 px-4 py-3 text-sm text-success">
                <Lucide icon="CircleCheck" class="h-4 w-4 shrink-0" />
                {{ flash.success }}
            </div>
            <div v-if="flash.error" class="mb-4 flex items-center gap-3 rounded-xl border border-danger/30 bg-danger/10 px-4 py-3 text-sm text-danger">
                <Lucide icon="CircleX" class="h-4 w-4 shrink-0" />
                {{ flash.error }}
            </div>

            <!-- Header -->
            <div class="box box--stacked mb-6 p-6">
                <div class="flex flex-col items-start justify-between gap-4 lg:flex-row lg:items-center">
                    <div class="flex items-center gap-4">
                        <div class="rounded-xl border border-primary/20 bg-primary/10 p-3">
                            <Lucide icon="ShieldCheck" class="h-8 w-8 text-primary" />
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-slate-800">Roles Management</h1>
                            <p class="text-slate-500">Define roles and assign permissions to control access levels.</p>
                        </div>
                    </div>
                    <Button variant="primary" class="flex items-center gap-2" @click="openCreate">
                        <Lucide icon="Plus" class="h-4 w-4" />
                        New Role
                    </Button>
                </div>
            </div>

            <!-- Stats -->
            <div class="mb-6 grid grid-cols-2 gap-5 lg:grid-cols-4">
                <div class="box box--stacked p-5">
                    <div class="text-2xl font-medium text-primary">{{ stats.total }}</div>
                    <div class="mt-1 text-sm text-slate-500">Total Roles</div>
                </div>
                <div class="box box--stacked p-5">
                    <div class="text-2xl font-medium text-info">{{ stats.permissions }}</div>
                    <div class="mt-1 text-sm text-slate-500">Total Permissions</div>
                </div>
                <div class="box box--stacked p-5">
                    <div class="text-2xl font-medium text-success">{{ roles.total }}</div>
                    <div class="mt-1 text-sm text-slate-500">Roles Shown</div>
                </div>
                <div class="box box--stacked p-5">
                    <div class="text-2xl font-medium text-warning">{{ permissions.length }}</div>
                    <div class="mt-1 text-sm text-slate-500">Available Permissions</div>
                </div>
            </div>

            <!-- Search -->
            <div class="box box--stacked mb-6 p-5">
                <div class="relative max-w-sm">
                    <Lucide icon="Search" class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                    <FormInput v-model="search" type="text" class="pl-10" placeholder="Search roles..." />
                </div>
            </div>

            <!-- Table -->
            <div class="box box--stacked overflow-hidden p-0">
                <div class="flex items-center justify-between border-b border-slate-200/60 px-5 py-4">
                    <div>
                        <h2 class="text-base font-semibold text-slate-800">Roles</h2>
                        <p class="text-sm text-slate-500">{{ roles.total }} total roles</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-slate-50/80">
                                <th class="px-5 py-3 text-xs font-medium uppercase text-slate-500">Role Name</th>
                                <th class="px-5 py-3 text-xs font-medium uppercase text-slate-500">Guard</th>
                                <th class="px-5 py-3 text-xs font-medium uppercase text-slate-500">Permissions</th>
                                <th class="px-5 py-3 text-xs font-medium uppercase text-slate-500">Created</th>
                                <th class="px-5 py-3 text-center text-xs font-medium uppercase text-slate-500">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="role in roles.data"
                                :key="role.id"
                                class="border-b border-slate-100 transition hover:bg-slate-50/50"
                            >
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-2">
                                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-primary/10">
                                            <Lucide icon="Shield" class="h-4 w-4 text-primary" />
                                        </div>
                                        <span class="font-medium text-slate-800">{{ role.name }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-600">
                                        {{ role.guard_name }}
                                    </span>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex flex-wrap gap-1">
                                        <span
                                            v-for="perm in role.permissions.slice(0, 4)"
                                            :key="perm"
                                            class="inline-flex items-center rounded-full bg-info/10 px-2 py-0.5 text-[11px] font-medium text-info"
                                        >
                                            {{ perm }}
                                        </span>
                                        <span
                                            v-if="role.permissions_count > 4"
                                            class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-500"
                                        >
                                            +{{ role.permissions_count - 4 }} more
                                        </span>
                                        <span
                                            v-if="role.permissions_count === 0"
                                            class="text-xs text-slate-400"
                                        >
                                            No permissions
                                        </span>
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-sm text-slate-500">{{ formatDate(role.created_at) }}</td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center justify-center gap-2">
                                        <button
                                            type="button"
                                            class="p-1.5 text-slate-400 transition hover:text-warning"
                                            title="Edit"
                                            @click="openEdit(role)"
                                        >
                                            <Lucide icon="PenLine" class="h-4 w-4" />
                                        </button>
                                        <button
                                            type="button"
                                            class="p-1.5 text-slate-400 transition hover:text-danger"
                                            title="Delete"
                                            @click="openDelete(role)"
                                        >
                                            <Lucide icon="Trash2" class="h-4 w-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="!roles.data.length">
                                <td colspan="5" class="px-5 py-12 text-center text-slate-400">
                                    <Lucide icon="ShieldOff" class="mx-auto mb-3 h-12 w-12 text-slate-300" />
                                    <p>No roles found</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="roles.last_page > 1" class="flex items-center justify-between border-t border-slate-200/60 p-4">
                    <span class="text-sm text-slate-500">{{ roles.total }} roles</span>
                    <div class="flex gap-1">
                        <template v-for="link in roles.links" :key="link.label">
                            <a
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

    <!-- ─── Create Modal ────────────────────────────────────────────────────── -->
    <Dialog :open="createOpen" @close="createOpen = false">
        <Dialog.Panel class="w-full max-w-xl overflow-hidden">
            <div class="px-6 pt-6">
                <div class="mb-5 flex items-start justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-800">Create New Role</h3>
                        <p class="text-sm text-slate-500">Assign a name and select permissions for this role.</p>
                    </div>
                    <button type="button" class="rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600" @click="createOpen = false">
                        <Lucide icon="X" class="h-5 w-5" />
                    </button>
                </div>

                <div class="mb-4">
                    <FormLabel>Role Name <span class="text-danger">*</span></FormLabel>
                    <FormInput v-model="createForm.name" type="text" placeholder="e.g. carrier_manager" :class="{ 'border-danger': createForm.errors.name }" />
                    <p v-if="createForm.errors.name" class="mt-1 text-xs text-danger">{{ createForm.errors.name }}</p>
                </div>

                <div class="mb-5">
                    <FormLabel>Permissions</FormLabel>
                    <div class="max-h-72 overflow-y-auto rounded-xl border border-slate-200 p-3">
                        <div v-for="[group, perms] in groupedPermissions" :key="group" class="mb-4 last:mb-0">
                            <p class="mb-1.5 text-[11px] font-semibold uppercase tracking-wide text-slate-400">{{ group }}</p>
                            <div class="grid grid-cols-1 gap-1.5 sm:grid-cols-2">
                                <label
                                    v-for="perm in perms"
                                    :key="perm.id"
                                    class="flex cursor-pointer items-center gap-2 rounded-lg px-2.5 py-1.5 text-sm transition hover:bg-slate-50"
                                >
                                    <input
                                        type="checkbox"
                                        :value="perm.id"
                                        :checked="createForm.permissions.includes(perm.id)"
                                        class="h-4 w-4 rounded border-slate-300 text-primary accent-primary"
                                        @change="togglePermission(createForm, perm.id)"
                                    />
                                    <span class="text-slate-700">{{ perm.name }}</span>
                                </label>
                            </div>
                        </div>
                        <p v-if="!permissions.length" class="py-4 text-center text-sm text-slate-400">No permissions available</p>
                    </div>
                    <p class="mt-1 text-xs text-slate-400">{{ createForm.permissions.length }} permission(s) selected</p>
                </div>
            </div>

            <div class="flex justify-end gap-3 border-t border-slate-100 px-6 py-4">
                <Button variant="outline-secondary" @click="createOpen = false">Cancel</Button>
                <Button variant="primary" :disabled="createForm.processing" @click="submitCreate">
                    <Lucide v-if="createForm.processing" icon="Loader" class="mr-2 h-4 w-4 animate-spin" />
                    Create Role
                </Button>
            </div>
        </Dialog.Panel>
    </Dialog>

    <!-- ─── Edit Modal ──────────────────────────────────────────────────────── -->
    <Dialog :open="editOpen" @close="editOpen = false">
        <Dialog.Panel class="w-full max-w-xl overflow-hidden">
            <div class="px-6 pt-6">
                <div class="mb-5 flex items-start justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-800">Edit Role</h3>
                        <p class="text-sm text-slate-500">Update name and permissions for <strong>{{ editingRole?.name }}</strong>.</p>
                    </div>
                    <button type="button" class="rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600" @click="editOpen = false">
                        <Lucide icon="X" class="h-5 w-5" />
                    </button>
                </div>

                <div class="mb-4">
                    <FormLabel>Role Name <span class="text-danger">*</span></FormLabel>
                    <FormInput v-model="editForm.name" type="text" :class="{ 'border-danger': editForm.errors.name }" />
                    <p v-if="editForm.errors.name" class="mt-1 text-xs text-danger">{{ editForm.errors.name }}</p>
                </div>

                <div class="mb-5">
                    <FormLabel>Permissions</FormLabel>
                    <div class="max-h-72 overflow-y-auto rounded-xl border border-slate-200 p-3">
                        <div v-for="[group, perms] in groupedPermissions" :key="group" class="mb-4 last:mb-0">
                            <p class="mb-1.5 text-[11px] font-semibold uppercase tracking-wide text-slate-400">{{ group }}</p>
                            <div class="grid grid-cols-1 gap-1.5 sm:grid-cols-2">
                                <label
                                    v-for="perm in perms"
                                    :key="perm.id"
                                    class="flex cursor-pointer items-center gap-2 rounded-lg px-2.5 py-1.5 text-sm transition hover:bg-slate-50"
                                >
                                    <input
                                        type="checkbox"
                                        :value="perm.id"
                                        :checked="editForm.permissions.includes(perm.id)"
                                        class="h-4 w-4 rounded border-slate-300 accent-primary"
                                        @change="togglePermission(editForm, perm.id)"
                                    />
                                    <span class="text-slate-700">{{ perm.name }}</span>
                                </label>
                            </div>
                        </div>
                    </div>
                    <p class="mt-1 text-xs text-slate-400">{{ editForm.permissions.length }} permission(s) selected</p>
                </div>
            </div>

            <div class="flex justify-end gap-3 border-t border-slate-100 px-6 py-4">
                <Button variant="outline-secondary" @click="editOpen = false">Cancel</Button>
                <Button variant="primary" :disabled="editForm.processing" @click="submitEdit">
                    <Lucide v-if="editForm.processing" icon="Loader" class="mr-2 h-4 w-4 animate-spin" />
                    Save Changes
                </Button>
            </div>
        </Dialog.Panel>
    </Dialog>

    <!-- ─── Delete Modal ────────────────────────────────────────────────────── -->
    <Dialog :open="deleteOpen" @close="deleteOpen = false" size="lg">
        <Dialog.Panel class="w-full max-w-[500px] overflow-hidden">
            <div class="px-6 pt-6">
                <button type="button" class="ml-auto flex rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600" @click="deleteOpen = false">
                    <Lucide icon="X" class="h-5 w-5" />
                </button>
                <div class="pb-2 text-center">
                    <div class="mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-full border-2 border-danger text-danger">
                        <Lucide icon="Trash2" class="h-7 w-7" />
                    </div>
                    <h3 class="text-[2rem] font-light text-slate-600">Are you sure?</h3>
                    <p class="mt-3 text-base leading-7 text-slate-500">
                        Do you really want to delete the role
                        <strong class="text-slate-700">"{{ deletingRole?.name }}"</strong>?
                        <br>Users assigned to this role will lose its permissions.
                    </p>
                </div>
            </div>
            <div class="flex justify-center gap-3 px-6 pb-8 pt-4">
                <Button variant="outline-secondary" class="min-w-24" @click="deleteOpen = false">Cancel</Button>
                <Button variant="danger" class="min-w-24" @click="confirmDelete">Delete</Button>
            </div>
        </Dialog.Panel>
    </Dialog>
</template>
