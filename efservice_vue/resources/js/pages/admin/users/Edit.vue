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
const displayPhoto = ref(props.user.profile_photo_url)

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

function roleCardClass(selected: boolean) {
    return selected ? 'border-primary bg-primary/5' : 'border-slate-200 hover:border-primary/30'
}
</script>

<template>
    <Head :title="`Edit: ${user.name}`" />

    <div class="grid grid-cols-12 gap-y-10 gap-x-6">
        <div class="col-span-12">
            <div class="box box--stacked p-6">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex items-center gap-4">
                        <div class="rounded-2xl border border-primary/20 bg-primary/10 p-3">
                            <Lucide icon="PenSquare" class="h-8 w-8 text-primary" />
                        </div>
                        <div>
                            <div class="text-base font-medium">
                                <Link :href="route('admin.users.index')" class="text-primary hover:underline">Users</Link>
                                <span class="mx-2 text-slate-400">/</span>
                                {{ user.name }}
                                <span class="mx-2 text-slate-400">/</span>
                                Edit
                            </div>
                            <p class="mt-1 text-sm text-slate-500">Update the account information, profile photo, and assigned roles.</p>
                        </div>
                    </div>

                    <Link :href="route('admin.users.show', user.id)">
                        <Button variant="outline-secondary" class="w-full sm:w-auto">
                            <Lucide icon="Eye" class="mr-2 h-4 w-4" /> View Profile
                        </Button>
                    </Link>
                </div>
            </div>
        </div>

        <div class="col-span-12">
            <form @submit.prevent="submit">
                <div class="grid grid-cols-12 gap-6">
                    <div class="col-span-12 lg:col-span-8">
                        <div class="box box--stacked p-6">
                            <div class="mb-6 flex items-center gap-3">
                                <Lucide icon="PenLine" class="h-5 w-5 text-primary" />
                                <h2 class="text-lg font-semibold text-slate-800">Edit User Information</h2>
                            </div>

                            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                                <div class="md:col-span-2">
                                    <FormLabel>Full Name *</FormLabel>
                                    <FormInput v-model="form.name" type="text" />
                                    <p v-if="form.errors.name" class="mt-1 text-xs text-danger">{{ form.errors.name }}</p>
                                </div>

                                <div class="md:col-span-2">
                                    <FormLabel>Email *</FormLabel>
                                    <FormInput v-model="form.email" type="email" />
                                    <p v-if="form.errors.email" class="mt-1 text-xs text-danger">{{ form.errors.email }}</p>
                                </div>

                                <div>
                                    <FormLabel>New Password (leave empty to keep current)</FormLabel>
                                    <FormInput v-model="form.password" type="password" placeholder="Enter new password" />
                                    <p v-if="form.errors.password" class="mt-1 text-xs text-danger">{{ form.errors.password }}</p>
                                </div>

                                <div>
                                    <FormLabel>Confirm Password</FormLabel>
                                    <FormInput v-model="form.password_confirmation" type="password" placeholder="Confirm new password" />
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

                    <div class="col-span-12 space-y-6 lg:col-span-4">
                        <div class="box box--stacked p-6">
                            <div class="mb-4 flex items-center gap-3">
                                <Lucide icon="Camera" class="h-5 w-5 text-primary" />
                                <h3 class="text-sm font-semibold text-slate-800">Profile Photo</h3>
                            </div>

                            <div class="flex flex-col items-center gap-4">
                                <div class="flex h-24 w-24 items-center justify-center overflow-hidden rounded-full border-2 border-primary/10 bg-primary/5">
                                    <img v-if="photoPreview" :src="photoPreview" class="h-full w-full object-cover" />
                                    <img v-else-if="displayPhoto" :src="displayPhoto" class="h-full w-full object-cover" />
                                    <Lucide v-else icon="User" class="h-10 w-10 text-primary" />
                                </div>

                                <input type="file" accept="image/*" @change="handlePhoto" class="w-full text-sm text-slate-500 file:mr-4 file:rounded-lg file:border-0 file:bg-primary/10 file:px-3 file:py-1.5 file:text-xs file:text-primary" />

                                <button
                                    v-if="displayPhoto && !photoPreview"
                                    type="button"
                                    @click="deletePhoto"
                                    class="inline-flex items-center text-xs text-danger transition hover:text-danger/80"
                                >
                                    <Lucide icon="Trash2" class="mr-1 h-3 w-3" /> Remove Photo
                                </button>
                            </div>
                        </div>

                        <div class="box box--stacked p-6">
                            <div class="mb-4 flex items-center gap-3">
                                <Lucide icon="Shield" class="h-5 w-5 text-primary" />
                                <h3 class="text-sm font-semibold text-slate-800">User Roles</h3>
                            </div>

                            <div class="space-y-3">
                                <label
                                    v-for="r in roles"
                                    :key="r.id"
                                    class="flex cursor-pointer items-center gap-3 rounded-lg border p-3 transition"
                                    :class="roleCardClass(form.roles.includes(r.id))"
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

                        <Button type="submit" variant="primary" class="w-full" :disabled="form.processing">
                            <Lucide icon="Save" class="mr-2 h-4 w-4" /> Update User
                        </Button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</template>
