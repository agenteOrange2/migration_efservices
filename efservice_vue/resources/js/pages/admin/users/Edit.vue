<script setup lang="ts">
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import { FormInput, FormLabel, FormSelect } from '@/components/Base/Form'
import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import RazeLayout from '@/layouts/RazeLayout.vue'

defineOptions({ layout: RazeLayout })

interface UserData {
    id: number
    name: string
    email: string
    status: number
    roles: number[]
    profile_photo_url: string | null
    created_at: string
}

const props = defineProps<{
    user: UserData
    roles: { id: number; name: string }[]
}>()

const form = useForm({
    name: props.user.name,
    email: props.user.email,
    password: '',
    password_confirmation: '',
    status: props.user.status === 1,
    profile_photo: null as File | null,
    roles: [...props.user.roles],
})

const photoPreview = ref<string | null>(null)

function handlePhoto(e: Event) {
    const target = e.target as HTMLInputElement
    const file = target.files?.[0]
    if (file) {
        form.profile_photo = file
        const reader = new FileReader()
        reader.onload = (ev) => { photoPreview.value = ev.target?.result as string }
        reader.readAsDataURL(file)
    }
}

function toggleRole(roleId: number) {
    const idx = form.roles.indexOf(roleId)
    if (idx > -1) {
        form.roles.splice(idx, 1)
    } else {
        form.roles.push(roleId)
    }
}

function submit() {
    form.transform((data) => ({
        ...data,
        _method: 'PUT',
    })).post(route('admin.users.update', props.user.id), {
        forceFormData: true,
        preserveScroll: true,
    })
}

function deletePhoto() {
    if (confirm('Delete profile photo?')) {
        router.post(route('admin.users.delete-photo', props.user.id), {}, {
            preserveScroll: true,
        })
    }
}

const displayPhoto = ref(props.user.profile_photo_url)
</script>

<template>
    <Head :title="`Edit: ${user.name}`" />

    <div class="grid grid-cols-12 gap-y-10 gap-x-6">
        <!-- Header -->
        <div class="col-span-12">
            <div class="flex flex-col md:h-10 gap-y-3 md:items-center md:flex-row">
                <div class="text-base font-medium">
                    <Link :href="route('admin.users.index')" class="text-primary hover:underline">Users</Link>
                    <span class="mx-2 text-slate-400">/</span>
                    {{ user.name }}
                    <span class="mx-2 text-slate-400">/</span>
                    Edit
                </div>
                <div class="flex flex-col sm:flex-row gap-x-3 gap-y-2 md:ml-auto">
                    <Link :href="route('admin.users.show', user.id)">
                        <Button variant="secondary" class="w-full sm:w-auto">
                            <Lucide icon="Eye" class="w-4 h-4 mr-2" /> View Profile
                        </Button>
                    </Link>
                </div>
            </div>
        </div>

        <div class="col-span-12">
            <form @submit.prevent="submit">
                <div class="grid grid-cols-12 gap-6">
                    <!-- Main Info -->
                    <div class="col-span-12 lg:col-span-8">
                        <div class="box box--stacked p-6">
                            <div class="flex items-center gap-3 mb-6">
                                <Lucide icon="PenSquare" class="w-5 h-5 text-primary" />
                                <h2 class="text-lg font-semibold text-slate-800">Edit User Information</h2>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div class="md:col-span-2">
                                    <FormLabel>Full Name *</FormLabel>
                                    <FormInput v-model="form.name" type="text" />
                                    <p v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</p>
                                </div>

                                <div class="md:col-span-2">
                                    <FormLabel>Email *</FormLabel>
                                    <FormInput v-model="form.email" type="email" />
                                    <p v-if="form.errors.email" class="text-red-500 text-xs mt-1">{{ form.errors.email }}</p>
                                </div>

                                <div>
                                    <FormLabel>New Password (leave empty to keep current)</FormLabel>
                                    <FormInput v-model="form.password" type="password" placeholder="••••••••" />
                                    <p v-if="form.errors.password" class="text-red-500 text-xs mt-1">{{ form.errors.password }}</p>
                                </div>

                                <div>
                                    <FormLabel>Confirm Password</FormLabel>
                                    <FormInput v-model="form.password_confirmation" type="password" placeholder="••••••••" />
                                </div>

                                <div class="md:col-span-2">
                                    <FormLabel>Status</FormLabel>
                                    <FormSelect v-model="form.status">
                                        <option :value="true">Active</option>
                                        <option :value="false">Inactive</option>
                                    </FormSelect>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar -->
                    <div class="col-span-12 lg:col-span-4 space-y-6">
                        <!-- Photo -->
                        <div class="box box--stacked p-6">
                            <div class="flex items-center gap-3 mb-4">
                                <Lucide icon="Camera" class="w-5 h-5 text-primary" />
                                <h3 class="text-sm font-semibold text-slate-800">Profile Photo</h3>
                            </div>

                            <div class="flex flex-col items-center gap-4">
                                <div class="w-24 h-24 rounded-full bg-slate-100 flex items-center justify-center overflow-hidden border-2 border-slate-200">
                                    <img v-if="photoPreview" :src="photoPreview" class="w-full h-full object-cover" />
                                    <img v-else-if="displayPhoto" :src="displayPhoto" class="w-full h-full object-cover" />
                                    <Lucide v-else icon="User" class="w-10 h-10 text-slate-400" />
                                </div>
                                <input type="file" accept="image/*" @change="handlePhoto" class="w-full text-sm text-slate-500 file:mr-4 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-primary/10 file:text-primary file:text-xs" />
                                <button
                                    v-if="displayPhoto && !photoPreview"
                                    type="button"
                                    @click="deletePhoto"
                                    class="text-xs text-red-500 hover:text-red-700 transition"
                                >
                                    <Lucide icon="Trash2" class="w-3 h-3 inline mr-1" /> Remove Photo
                                </button>
                            </div>
                        </div>

                        <!-- Roles -->
                        <div class="box box--stacked p-6">
                            <div class="flex items-center gap-3 mb-4">
                                <Lucide icon="Shield" class="w-5 h-5 text-primary" />
                                <h3 class="text-sm font-semibold text-slate-800">User Roles</h3>
                            </div>

                            <div class="space-y-3">
                                <label
                                    v-for="r in roles"
                                    :key="r.id"
                                    class="flex items-center gap-3 p-3 rounded-lg border cursor-pointer transition"
                                    :class="form.roles.includes(r.id) ? 'border-primary bg-primary/5' : 'border-slate-200 hover:border-slate-300'"
                                >
                                    <input
                                        type="checkbox"
                                        :checked="form.roles.includes(r.id)"
                                        @change="toggleRole(r.id)"
                                        class="rounded border-slate-300 text-primary focus:ring-primary"
                                    />
                                    <span class="text-sm font-medium text-slate-700">{{ r.name }}</span>
                                </label>
                            </div>
                        </div>

                        <!-- Actions -->
                        <Button type="submit" variant="primary" class="w-full" :disabled="form.processing">
                            <Lucide icon="Save" class="w-4 h-4 mr-2" /> Update User
                        </Button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</template>
