<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import { FormInput, FormLabel, FormSelect } from '@/components/Base/Form'
import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import RazeLayout from '@/layouts/RazeLayout.vue'

defineOptions({ layout: RazeLayout })

const props = defineProps<{
    roles: { id: number; name: string }[]
}>()

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    status: true,
    profile_photo: null as File | null,
    roles: [] as number[],
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
    form.post(route('admin.users.store'), {
        forceFormData: true,
    })
}

function roleCardClass(selected: boolean) {
    return selected ? 'border-primary bg-primary/5' : 'border-slate-200 hover:border-primary/30'
}
</script>

<template>
    <Head title="Create User" />

    <div class="grid grid-cols-12 gap-y-10 gap-x-6">
        <div class="col-span-12">
            <div class="box box--stacked p-6">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex items-center gap-4">
                        <div class="rounded-2xl border border-primary/20 bg-primary/10 p-3">
                            <Lucide icon="UserPlus" class="h-8 w-8 text-primary" />
                        </div>
                        <div>
                            <div class="text-base font-medium">
                                <Link :href="route('admin.users.index')" class="text-primary hover:underline">Users</Link>
                                <span class="mx-2 text-slate-400">/</span>
                                Create User
                            </div>
                            <p class="mt-1 text-sm text-slate-500">Create a new user account and assign the roles that match the access level required.</p>
                        </div>
                    </div>

                    <Link :href="route('admin.users.index')">
                        <Button variant="outline-secondary" class="w-full sm:w-auto">
                            <Lucide icon="ArrowLeft" class="mr-2 h-4 w-4" /> Back to Users
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
                                <Lucide icon="UserPlus" class="h-5 w-5 text-primary" />
                                <h2 class="text-lg font-semibold text-slate-800">User Information</h2>
                            </div>

                            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                                <div class="md:col-span-2">
                                    <FormLabel>Full Name *</FormLabel>
                                    <FormInput v-model="form.name" type="text" placeholder="John Doe" />
                                    <p v-if="form.errors.name" class="mt-1 text-xs text-danger">{{ form.errors.name }}</p>
                                </div>

                                <div class="md:col-span-2">
                                    <FormLabel>Email *</FormLabel>
                                    <FormInput v-model="form.email" type="email" placeholder="john@example.com" />
                                    <p v-if="form.errors.email" class="mt-1 text-xs text-danger">{{ form.errors.email }}</p>
                                </div>

                                <div>
                                    <FormLabel>Password *</FormLabel>
                                    <FormInput v-model="form.password" type="password" />
                                    <p v-if="form.errors.password" class="mt-1 text-xs text-danger">{{ form.errors.password }}</p>
                                </div>

                                <div>
                                    <FormLabel>Confirm Password *</FormLabel>
                                    <FormInput v-model="form.password_confirmation" type="password" />
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
                                    <Lucide v-else icon="User" class="h-10 w-10 text-primary" />
                                </div>

                                <input type="file" accept="image/*" @change="handlePhoto" class="w-full text-sm text-slate-500 file:mr-4 file:rounded-lg file:border-0 file:bg-primary/10 file:px-3 file:py-1.5 file:text-xs file:text-primary" />
                                <p v-if="form.errors.profile_photo" class="text-xs text-danger">{{ form.errors.profile_photo }}</p>
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

                            <p v-if="form.errors.roles" class="mt-2 text-xs text-danger">{{ form.errors.roles }}</p>
                            <p class="mt-3 text-xs text-slate-500">If no role is selected, "superadmin" will be assigned by default.</p>
                        </div>

                        <Button type="submit" variant="primary" class="w-full" :disabled="form.processing">
                            <Lucide icon="Save" class="mr-2 h-4 w-4" /> Create User
                        </Button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</template>
