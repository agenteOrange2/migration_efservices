<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'
import Lucide from '@/components/Base/Lucide'
import Button from '@/components/Base/Button'
import { FormInput, FormLabel } from '@/components/Base/Form'
import { Dialog } from '@/components/Base/Headless'
import TomSelect from '@/components/Base/TomSelect/TomSelect.vue'
import RazeLayout from '@/layouts/RazeLayout.vue'

declare function route(name: string, params?: any): string

defineOptions({ layout: RazeLayout })

interface PaginationLink {
    url: string | null
    label: string
    active: boolean
}

interface PermissionRow {
    id: number
    name: string
    guard_name: string
    group: string
    roles: string[]
    roles_count: number
    created_at: string | null
}

const props = defineProps<{
    permissions: {
        data: PermissionRow[]
        links: PaginationLink[]
        total: number
        last_page: number
    }
    stats: {
        total: number
        roles: number
        groups: number
    }
    groups: string[]
    filters: { search?: string; group?: string }
}>()

const page = usePage()
const flash = computed(() => (page.props as any).flash ?? {})

// ─── filters ──────────────────────────────────────────────────────────────────
const search = ref(props.filters.search ?? '')
const groupFilter = ref(props.filters.group ?? '')

let searchTimer: ReturnType<typeof setTimeout>
watch(search, (val) => {
    clearTimeout(searchTimer)
    searchTimer = setTimeout(applyFilters, 350)
})
watch(groupFilter, applyFilters)

function applyFilters() {
    router.get(route('admin.permissions.index'), {
        search: search.value || undefined,
        group: groupFilter.value || undefined,
    }, { preserveState: true, replace: true })
}

function clearFilters() {
    search.value = ''
    groupFilter.value = ''
    router.get(route('admin.permissions.index'), {}, { preserveState: true, replace: true })
}

const hasActiveFilters = computed(() => !!search.value || !!groupFilter.value)

// ─── create modal ─────────────────────────────────────────────────────────────
const createOpen = ref(false)
const createForm = useForm({ name: '', guard_name: 'web' })

function openCreate() {
    createForm.reset()
    createOpen.value = true
}

function submitCreate() {
    createForm.post(route('admin.permissions.store'), {
        onSuccess: () => { createOpen.value = false },
    })
}

// ─── edit modal ───────────────────────────────────────────────────────────────
const editOpen = ref(false)
const editingPermission = ref<PermissionRow | null>(null)
const editForm = useForm({ name: '' })

function openEdit(permission: PermissionRow) {
    editingPermission.value = permission
    editForm.name = permission.name
    editOpen.value = true
}

function submitEdit() {
    if (!editingPermission.value) return
    editForm.put(route('admin.permissions.update', editingPermission.value.id), {
        onSuccess: () => { editOpen.value = false },
    })
}

// ─── delete modal ─────────────────────────────────────────────────────────────
const deleteOpen = ref(false)
const deletingPermission = ref<PermissionRow | null>(null)

function openDelete(permission: PermissionRow) {
    deletingPermission.value = permission
    deleteOpen.value = true
}

function confirmDelete() {
    if (!deletingPermission.value) return
    router.delete(route('admin.permissions.destroy', deletingPermission.value.id), {
        preserveScroll: true,
        onSuccess: () => { deleteOpen.value = false },
    })
}

// ─── group badge color ────────────────────────────────────────────────────────
const groupColors: Record<string, string> = {
    carriers: 'bg-primary/10 text-primary',
    drivers: 'bg-success/10 text-success',
    vehicles: 'bg-warning/10 text-warning',
    admin: 'bg-danger/10 text-danger',
    users: 'bg-info/10 text-info',
    roles: 'bg-purple-100 text-purple-600',
    permissions: 'bg-indigo-100 text-indigo-600',
}

function groupColor(group: string): string {
    return groupColors[group] ?? 'bg-slate-100 text-slate-600'
}

function formatDate(value: string | null) {
    if (!value) return '—'
    return new Date(value).toLocaleDateString()
}
</script>

<template>
    <Head title="Permissions Management" />

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
                        <div class="rounded-xl border border-warning/20 bg-warning/10 p-3">
                            <Lucide icon="KeyRound" class="h-8 w-8 text-warning" />
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-slate-800">Permissions Management</h1>
                            <p class="text-slate-500">Manage granular permissions assigned to roles in the system.</p>
                        </div>
                    </div>
                    <Button variant="primary" class="flex items-center gap-2" @click="openCreate">
                        <Lucide icon="Plus" class="h-4 w-4" />
                        New Permission
                    </Button>
                </div>
            </div>

            <!-- Stats -->
            <div class="mb-6 grid grid-cols-2 gap-5 lg:grid-cols-4">
                <div class="box box--stacked p-5">
                    <div class="text-2xl font-medium text-warning">{{ stats.total }}</div>
                    <div class="mt-1 text-sm text-slate-500">Total Permissions</div>
                </div>
                <div class="box box--stacked p-5">
                    <div class="text-2xl font-medium text-primary">{{ stats.roles }}</div>
                    <div class="mt-1 text-sm text-slate-500">Total Roles</div>
                </div>
                <div class="box box--stacked p-5">
                    <div class="text-2xl font-medium text-info">{{ stats.groups }}</div>
                    <div class="mt-1 text-sm text-slate-500">Permission Groups</div>
                </div>
                <div class="box box--stacked p-5">
                    <div class="text-2xl font-medium text-success">{{ permissions.total }}</div>
                    <div class="mt-1 text-sm text-slate-500">Results Shown</div>
                </div>
            </div>

            <!-- Filters -->
            <div class="box box--stacked mb-6 p-5">
                <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                    <div class="relative lg:col-span-2">
                        <Lucide icon="Search" class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                        <FormInput v-model="search" type="text" class="pl-10" placeholder="Search permissions..." />
                    </div>
                    <TomSelect v-model="groupFilter">
                        <option value="">All Groups</option>
                        <option v-for="g in groups" :key="g" :value="g">{{ g }}</option>
                    </TomSelect>
                </div>
                <div v-if="hasActiveFilters" class="mt-3">
                    <Button variant="outline-secondary" class="flex items-center gap-2" @click="clearFilters">
                        <Lucide icon="RotateCcw" class="h-4 w-4" />
                        Clear Filters
                    </Button>
                </div>
            </div>

            <!-- Table -->
            <div class="box box--stacked overflow-hidden p-0">
                <div class="flex items-center justify-between border-b border-slate-200/60 px-5 py-4">
                    <div>
                        <h2 class="text-base font-semibold text-slate-800">Permissions</h2>
                        <p class="text-sm text-slate-500">{{ permissions.total }} total permissions</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-slate-50/80">
                                <th class="px-5 py-3 text-xs font-medium uppercase text-slate-500">Permission Name</th>
                                <th class="px-5 py-3 text-xs font-medium uppercase text-slate-500">Group</th>
                                <th class="px-5 py-3 text-xs font-medium uppercase text-slate-500">Guard</th>
                                <th class="px-5 py-3 text-xs font-medium uppercase text-slate-500">Assigned Roles</th>
                                <th class="px-5 py-3 text-xs font-medium uppercase text-slate-500">Created</th>
                                <th class="px-5 py-3 text-center text-xs font-medium uppercase text-slate-500">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="permission in permissions.data"
                                :key="permission.id"
                                class="border-b border-slate-100 transition hover:bg-slate-50/50"
                            >
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-2">
                                        <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-warning/10">
                                            <Lucide icon="Key" class="h-3.5 w-3.5 text-warning" />
                                        </div>
                                        <span class="font-mono text-sm font-medium text-slate-800">{{ permission.name }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <span
                                        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium capitalize"
                                        :class="groupColor(permission.group)"
                                    >
                                        {{ permission.group }}
                                    </span>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-600">
                                        {{ permission.guard_name }}
                                    </span>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex flex-wrap gap-1">
                                        <span
                                            v-for="role in permission.roles.slice(0, 3)"
                                            :key="role"
                                            class="inline-flex items-center rounded-full bg-primary/10 px-2 py-0.5 text-[11px] font-medium text-primary"
                                        >
                                            {{ role }}
                                        </span>
                                        <span
                                            v-if="permission.roles_count > 3"
                                            class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-500"
                                        >
                                            +{{ permission.roles_count - 3 }}
                                        </span>
                                        <span v-if="permission.roles_count === 0" class="text-xs text-slate-400">
                                            Unassigned
                                        </span>
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-sm text-slate-500">{{ formatDate(permission.created_at) }}</td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center justify-center gap-2">
                                        <button
                                            type="button"
                                            class="p-1.5 text-slate-400 transition hover:text-warning"
                                            title="Edit"
                                            @click="openEdit(permission)"
                                        >
                                            <Lucide icon="PenLine" class="h-4 w-4" />
                                        </button>
                                        <button
                                            type="button"
                                            class="p-1.5 text-slate-400 transition hover:text-danger"
                                            title="Delete"
                                            @click="openDelete(permission)"
                                        >
                                            <Lucide icon="Trash2" class="h-4 w-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="!permissions.data.length">
                                <td colspan="6" class="px-5 py-12 text-center text-slate-400">
                                    <Lucide icon="KeyRound" class="mx-auto mb-3 h-12 w-12 text-slate-300" />
                                    <p>No permissions found</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="permissions.last_page > 1" class="flex items-center justify-between border-t border-slate-200/60 p-4">
                    <span class="text-sm text-slate-500">{{ permissions.total }} permissions</span>
                    <div class="flex gap-1">
                        <template v-for="link in permissions.links" :key="link.label">
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
        <Dialog.Panel class="w-full max-w-md overflow-hidden">
            <div class="px-6 pt-6">
                <div class="mb-5 flex items-start justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-800">Create New Permission</h3>
                        <p class="text-sm text-slate-500">Use dot notation for grouping, e.g. <code class="rounded bg-slate-100 px-1 text-xs">carriers.edit</code></p>
                    </div>
                    <button type="button" class="rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600" @click="createOpen = false">
                        <Lucide icon="X" class="h-5 w-5" />
                    </button>
                </div>

                <div class="mb-5">
                    <FormLabel>Permission Name <span class="text-danger">*</span></FormLabel>
                    <FormInput
                        v-model="createForm.name"
                        type="text"
                        placeholder="e.g. carriers.view"
                        :class="{ 'border-danger': createForm.errors.name }"
                    />
                    <p v-if="createForm.errors.name" class="mt-1 text-xs text-danger">{{ createForm.errors.name }}</p>
                    <p class="mt-1 text-xs text-slate-400">Group prefix before the dot becomes the permission category.</p>
                </div>
            </div>

            <div class="flex justify-end gap-3 border-t border-slate-100 px-6 py-4">
                <Button variant="outline-secondary" @click="createOpen = false">Cancel</Button>
                <Button variant="primary" :disabled="createForm.processing" @click="submitCreate">
                    <Lucide v-if="createForm.processing" icon="Loader" class="mr-2 h-4 w-4 animate-spin" />
                    Create Permission
                </Button>
            </div>
        </Dialog.Panel>
    </Dialog>

    <!-- ─── Edit Modal ──────────────────────────────────────────────────────── -->
    <Dialog :open="editOpen" @close="editOpen = false">
        <Dialog.Panel class="w-full max-w-md overflow-hidden">
            <div class="px-6 pt-6">
                <div class="mb-5 flex items-start justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-800">Edit Permission</h3>
                        <p class="text-sm text-slate-500">Renaming will affect all roles using this permission.</p>
                    </div>
                    <button type="button" class="rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600" @click="editOpen = false">
                        <Lucide icon="X" class="h-5 w-5" />
                    </button>
                </div>

                <div class="mb-5">
                    <FormLabel>Permission Name <span class="text-danger">*</span></FormLabel>
                    <FormInput
                        v-model="editForm.name"
                        type="text"
                        :class="{ 'border-danger': editForm.errors.name }"
                    />
                    <p v-if="editForm.errors.name" class="mt-1 text-xs text-danger">{{ editForm.errors.name }}</p>
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
                        Do you really want to delete
                        <strong class="font-mono text-slate-700">"{{ deletingPermission?.name }}"</strong>?
                        <br>This will remove it from all roles that currently use it.
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
