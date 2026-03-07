<script setup lang="ts">
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import Lucide from '@/components/Base/Lucide'
import { FormInput, FormSelect, FormLabel } from '@/components/Base/Form'
import Button from '@/components/Base/Button'
import RazeLayout from '@/layouts/RazeLayout.vue'

defineOptions({ layout: RazeLayout })

const props = defineProps<{
    carrier: { id: number; name: string; slug: string }
    userCarriers: {
        data: Array<{
            id: number
            name: string
            email: string
            status: number
            profile_photo_url: string | null
            carrier_details: {
                id: number
                phone: string
                job_position: string
                status: number
            } | null
        }>
        links: any[]
        current_page: number
        last_page: number
    }
    exceededLimit: boolean
    maxCarriers: number
    currentCount: number
}>()

const showCreateModal = ref(false)
const showEditModal = ref(false)
const editingUser = ref<any>(null)
const createPhotoPreview = ref<string | null>(null)
const editPhotoPreview = ref<string | null>(null)

const createForm = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    phone: '',
    job_position: '',
    status: 1,
    profile_photo: null as File | null,
})

const editForm = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    phone: '',
    job_position: '',
    status: 1,
    profile_photo: null as File | null,
})

function revokeCreatePreview() {
    if (createPhotoPreview.value) {
        URL.revokeObjectURL(createPhotoPreview.value)
        createPhotoPreview.value = null
    }
}

function revokeEditPreview() {
    if (editPhotoPreview.value) {
        URL.revokeObjectURL(editPhotoPreview.value)
        editPhotoPreview.value = null
    }
}

function openCreate() {
    if (props.exceededLimit) {
        alert(`User limit reached (${props.maxCarriers}). Upgrade the membership plan.`)
        return
    }
    createForm.reset()
    revokeCreatePreview()
    showCreateModal.value = true
}

function openEdit(user: any) {
    editingUser.value = user
    editForm.name = user.name
    editForm.email = user.email
    editForm.password = ''
    editForm.password_confirmation = ''
    editForm.phone = user.carrier_details?.phone ?? ''
    editForm.job_position = user.carrier_details?.job_position ?? ''
    editForm.status = user.carrier_details?.status ?? 1
    editForm.profile_photo = null
    revokeEditPreview()
    showEditModal.value = true
}

function handleCreatePhoto(e: Event) {
    const t = e.target as HTMLInputElement
    revokeCreatePreview()
    if (t.files?.[0]) {
        createForm.profile_photo = t.files[0]
        createPhotoPreview.value = URL.createObjectURL(t.files[0])
    } else {
        createForm.profile_photo = null
    }
}

function handleEditPhoto(e: Event) {
    const t = e.target as HTMLInputElement
    revokeEditPreview()
    if (t.files?.[0]) {
        editForm.profile_photo = t.files[0]
        editPhotoPreview.value = URL.createObjectURL(t.files[0])
    } else {
        editForm.profile_photo = null
    }
}

watch(showCreateModal, (v) => { if (!v) revokeCreatePreview() })
watch(showEditModal, (v) => { if (!v) revokeEditPreview() })

function submitCreate() {
    createForm.post(route('admin.carriers.user-carriers.store', props.carrier.slug), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => { showCreateModal.value = false; createForm.reset() },
    })
}

function submitEdit() {
    if (!editingUser.value?.carrier_details?.id) return
    editForm.transform((data) => ({
        ...data,
        _method: 'PUT',
    })).post(route('admin.carriers.user-carriers.update', { carrier: props.carrier.slug, userCarrierDetail: editingUser.value.carrier_details.id }), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => { showEditModal.value = false },
    })
}

function deleteUser(user: any) {
    if (confirm(`Delete user "${user.name}"?`)) {
        router.delete(route('admin.carriers.user-carriers.destroy', { carrier: props.carrier.slug, userCarrier: user.id }))
    }
}

const statusLabels: Record<number, string> = { 0: 'Inactive', 1: 'Active', 2: 'Pending' }
const statusClasses: Record<number, string> = {
    0: 'bg-red-100 text-red-700',
    1: 'bg-emerald-100 text-emerald-700',
    2: 'bg-amber-100 text-amber-700',
}
</script>

<template>
    <Head :title="`Users: ${carrier.name}`" />

    <div class="grid grid-cols-12 gap-x-6 gap-y-8">
        <div class="col-span-12">
            <div class="box box--stacked p-6 mb-6">
                <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <Link :href="route('admin.carriers.show', carrier.slug)" class="p-2 rounded-lg hover:bg-slate-100 transition">
                            <Lucide icon="ArrowLeft" class="w-5 h-5 text-slate-600" />
                        </Link>
                        <div>
                            <h1 class="text-2xl font-bold text-slate-800">{{ carrier.name }} - Users</h1>
                            <p class="text-sm text-slate-500">{{ currentCount }} / {{ maxCarriers }} users</p>
                        </div>
                    </div>
                    <button @click="openCreate" class="inline-flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition" :class="{ 'opacity-50 cursor-not-allowed': exceededLimit }">
                        <Lucide icon="UserPlus" class="w-4 h-4" /> Add User
                    </button>
                </div>
                <div v-if="exceededLimit" class="mt-3 p-3 bg-amber-50 border border-amber-200 rounded-lg text-sm text-amber-700 flex items-center gap-2">
                    <Lucide icon="AlertTriangle" class="w-4 h-4" /> User limit reached. Upgrade the membership plan to add more users.
                </div>
            </div>

            <div class="box box--stacked p-0">
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-slate-50/80">
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Name</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Email</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Phone</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Position</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Status</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="u in userCarriers.data" :key="u.id" class="border-b border-slate-100 hover:bg-slate-50/50 transition">
                                <td class="px-5 py-4 font-medium text-slate-700">{{ u.name }}</td>
                                <td class="px-5 py-4 text-sm text-slate-600">{{ u.email }}</td>
                                <td class="px-5 py-4 text-sm text-slate-600">{{ u.carrier_details?.phone ?? '-' }}</td>
                                <td class="px-5 py-4 text-sm text-slate-600">{{ u.carrier_details?.job_position ?? '-' }}</td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" :class="statusClasses[u.carrier_details?.status ?? 0]">
                                        {{ statusLabels[u.carrier_details?.status ?? 0] }}
                                    </span>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center justify-center gap-2">
                                        <button @click="openEdit(u)" class="p-1.5 text-slate-400 hover:text-amber-500 transition" title="Edit">
                                            <Lucide icon="PenLine" class="w-4 h-4" />
                                        </button>
                                        <button @click="deleteUser(u)" class="p-1.5 text-slate-400 hover:text-red-500 transition" title="Delete">
                                            <Lucide icon="Trash2" class="w-4 h-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="userCarriers.data.length === 0">
                                <td colspan="6" class="px-5 py-12 text-center text-slate-400">
                                    <Lucide icon="Users" class="w-12 h-12 mx-auto mb-3 text-slate-300" />
                                    <p>No users assigned to this carrier</p>
                                    <button @click="openCreate" class="mt-3 text-sm text-primary hover:underline">Add the first user</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-if="userCarriers.last_page > 1" class="p-4 border-t border-slate-200/60 flex justify-center gap-1">
                    <template v-for="link in userCarriers.links" :key="link.label">
                        <Link v-if="link.url" :href="link.url" class="px-3 py-1 text-sm rounded" :class="link.active ? 'bg-primary text-white' : 'text-slate-600 hover:bg-slate-100'" v-html="link.label" />
                        <span v-else class="px-3 py-1 text-sm text-slate-300" v-html="link.label" />
                    </template>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Modal -->
    <Teleport to="body">
        <div v-if="showCreateModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" @click.self="showCreateModal = false">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-lg p-6 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between mb-5">
                    <h3 class="text-lg font-semibold text-slate-800">Add User Carrier</h3>
                    <button @click="showCreateModal = false" class="p-1 hover:bg-slate-100 rounded"><Lucide icon="X" class="w-5 h-5" /></button>
                </div>
                <form @submit.prevent="submitCreate" class="space-y-4">
                    <div class="flex justify-center mb-4">
                        <div class="relative w-24 h-24 rounded-full bg-slate-100 border-2 border-slate-200 overflow-hidden flex items-center justify-center">
                            <img v-if="createPhotoPreview" :src="createPhotoPreview" alt="Preview" class="w-full h-full object-cover" />
                            <Lucide v-else icon="User" class="w-12 h-12 text-slate-400" />
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-2">
                            <FormLabel>Name *</FormLabel>
                            <FormInput v-model="createForm.name" type="text" />
                            <div v-if="createForm.errors.name" class="text-red-500 text-xs mt-1">{{ createForm.errors.name }}</div>
                        </div>
                        <div class="col-span-2">
                            <FormLabel>Email *</FormLabel>
                            <FormInput v-model="createForm.email" type="email" />
                            <div v-if="createForm.errors.email" class="text-red-500 text-xs mt-1">{{ createForm.errors.email }}</div>
                        </div>
                        <div>
                            <FormLabel>Password *</FormLabel>
                            <FormInput v-model="createForm.password" type="password" />
                            <div v-if="createForm.errors.password" class="text-red-500 text-xs mt-1">{{ createForm.errors.password }}</div>
                        </div>
                        <div>
                            <FormLabel>Confirm Password *</FormLabel>
                            <FormInput v-model="createForm.password_confirmation" type="password" />
                        </div>
                        <div>
                            <FormLabel>Phone *</FormLabel>
                            <FormInput v-model="createForm.phone" type="text" />
                            <div v-if="createForm.errors.phone" class="text-red-500 text-xs mt-1">{{ createForm.errors.phone }}</div>
                        </div>
                        <div>
                            <FormLabel>Job Position *</FormLabel>
                            <FormInput v-model="createForm.job_position" type="text" />
                            <div v-if="createForm.errors.job_position" class="text-red-500 text-xs mt-1">{{ createForm.errors.job_position }}</div>
                        </div>
                        <div>
                            <FormLabel>Status</FormLabel>
                            <FormSelect v-model="createForm.status">
                                <option :value="1">Active</option>
                                <option :value="0">Inactive</option>
                                <option :value="2">Pending</option>
                            </FormSelect>
                        </div>
                        <div class="col-span-2">
                            <FormLabel>Profile Photo</FormLabel>
                            <div class="flex items-center gap-4 mt-2">
                                <div class="w-20 h-20 rounded-full bg-slate-100 border-2 border-slate-200 overflow-hidden flex-shrink-0 flex items-center justify-center">
                                    <img v-if="createPhotoPreview" :src="createPhotoPreview" alt="Vista previa" class="w-full h-full object-cover" />
                                    <Lucide v-else icon="User" class="w-10 h-10 text-slate-400" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <input type="file" accept="image/*" @change="handleCreatePhoto" class="w-full text-sm text-slate-600 file:mr-4 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-primary/10 file:text-primary file:text-xs" />
                                    <p class="text-xs text-slate-500 mt-1.5">Opcional. Selecciona una imagen para la foto de perfil.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="flex justify-end gap-3 pt-3 border-t">
                        <button type="button" @click="showCreateModal = false" class="px-4 py-2 border border-slate-300 rounded-lg text-sm">Cancel</button>
                        <Button type="submit" variant="primary" :disabled="createForm.processing" class="px-4">
                            <Lucide icon="UserPlus" class="w-4 h-4 mr-1" /> Create User
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>

    <!-- Edit Modal -->
    <Teleport to="body">
        <div v-if="showEditModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" @click.self="showEditModal = false">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-lg p-6 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between mb-5">
                    <h3 class="text-lg font-semibold text-slate-800">Edit User Carrier</h3>
                    <button @click="showEditModal = false" class="p-1 hover:bg-slate-100 rounded"><Lucide icon="X" class="w-5 h-5" /></button>
                </div>
                <form @submit.prevent="submitEdit" class="space-y-4">
                    <div class="flex justify-center mb-4">
                        <div class="relative w-24 h-24 rounded-full bg-slate-100 border-2 border-slate-200 overflow-hidden flex items-center justify-center ring-2 ring-white shadow-md">
                            <img v-if="editPhotoPreview" :src="editPhotoPreview" alt="New photo" class="w-full h-full object-cover" />
                            <img v-else-if="editingUser?.profile_photo_url" :src="editingUser.profile_photo_url" :alt="editingUser?.name" class="w-full h-full object-cover" />
                            <Lucide v-else icon="User" class="w-12 h-12 text-slate-400" />
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-2">
                            <FormLabel>Name *</FormLabel>
                            <FormInput v-model="editForm.name" type="text" />
                            <div v-if="editForm.errors.name" class="text-red-500 text-xs mt-1">{{ editForm.errors.name }}</div>
                        </div>
                        <div class="col-span-2">
                            <FormLabel>Email *</FormLabel>
                            <FormInput v-model="editForm.email" type="email" />
                            <div v-if="editForm.errors.email" class="text-red-500 text-xs mt-1">{{ editForm.errors.email }}</div>
                        </div>
                        <div>
                            <FormLabel>Password (leave empty to keep)</FormLabel>
                            <FormInput v-model="editForm.password" type="password" />
                            <div v-if="editForm.errors.password" class="text-red-500 text-xs mt-1">{{ editForm.errors.password }}</div>
                        </div>
                        <div>
                            <FormLabel>Confirm Password</FormLabel>
                            <FormInput v-model="editForm.password_confirmation" type="password" />
                        </div>
                        <div>
                            <FormLabel>Phone *</FormLabel>
                            <FormInput v-model="editForm.phone" type="text" />
                        </div>
                        <div>
                            <FormLabel>Job Position *</FormLabel>
                            <FormInput v-model="editForm.job_position" type="text" />
                        </div>
                        <div>
                            <FormLabel>Status</FormLabel>
                            <FormSelect v-model="editForm.status">
                                <option :value="1">Active</option>
                                <option :value="0">Inactive</option>
                                <option :value="2">Pending</option>
                            </FormSelect>
                        </div>
                        <div class="col-span-2">
                            <FormLabel>Profile Photo</FormLabel>
                            <div class="flex items-center gap-4 mt-2">
                                <div class="w-20 h-20 rounded-full bg-slate-100 border-2 border-slate-200 overflow-hidden flex-shrink-0 flex items-center justify-center ring-2 ring-white shadow">
                                    <img v-if="editPhotoPreview" :src="editPhotoPreview" alt="Nueva foto" class="w-full h-full object-cover" />
                                    <img v-else-if="editingUser?.profile_photo_url" :src="editingUser.profile_photo_url" :alt="editingUser?.name" class="w-full h-full object-cover" />
                                    <Lucide v-else icon="User" class="w-10 h-10 text-slate-400" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <input type="file" accept="image/*" @change="handleEditPhoto" class="w-full text-sm text-slate-600 file:mr-4 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-primary/10 file:text-primary file:text-xs" />
                                    <p class="text-xs text-slate-500 mt-1.5">Selecciona un archivo para cambiar la foto</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="flex justify-end gap-3 pt-3 border-t">
                        <button type="button" @click="showEditModal = false" class="px-4 py-2 border border-slate-300 rounded-lg text-sm">Cancel</button>
                        <Button type="submit" variant="primary" :disabled="editForm.processing" class="px-4">
                            <Lucide icon="Save" class="w-4 h-4 mr-1" /> Update User
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>
</template>
