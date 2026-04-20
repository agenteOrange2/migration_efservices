<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import Lucide from '@/components/Base/Lucide'
import { FormInput, FormLabel, FormSelect } from '@/components/Base/Form'
import Button from '@/components/Base/Button'
import RazeLayout from '@/layouts/RazeLayout.vue'

declare function route(name: string, params?: any): string

defineOptions({ layout: RazeLayout })

const props = defineProps<{
    carrier: { id: number; name: string; slug: string }
    userCarrier: {
        id: number
        phone: string | null
        job_position: string | null
        status: number
        user: {
            id: number
            name: string
            email: string
            profile_photo_url: string | null
        } | null
    }
}>()

const photoPreview = ref<string | null>(null)

const form = useForm({
    name: props.userCarrier.user?.name ?? '',
    email: props.userCarrier.user?.email ?? '',
    password: '',
    password_confirmation: '',
    phone: props.userCarrier.phone ?? '',
    job_position: props.userCarrier.job_position ?? '',
    status: props.userCarrier.status,
    profile_photo: null as File | null,
})

function handlePhoto(e: Event) {
    const input = e.target as HTMLInputElement
    if (photoPreview.value) URL.revokeObjectURL(photoPreview.value)
    if (input.files?.[0]) {
        form.profile_photo = input.files[0]
        photoPreview.value = URL.createObjectURL(input.files[0])
    } else {
        form.profile_photo = null
        photoPreview.value = null
    }
}

function submit() {
    form.transform((data) => ({ ...data, _method: 'PUT' }))
        .post(route('admin.carriers.user-carriers.update', {
            carrier: props.carrier.slug,
            userCarrierDetail: props.userCarrier.id,
        }), {
            forceFormData: true,
            preserveScroll: true,
        })
}
</script>

<template>
    <Head :title="`Edit: ${userCarrier.user?.name ?? 'Carrier User'}`" />

    <div class="grid grid-cols-12 gap-x-6 gap-y-6">
        <!-- Header -->
        <div class="col-span-12">
            <div class="box box--stacked p-5">
                <div class="flex items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <Link :href="route('admin.carriers.user-carriers.show', { carrier: carrier.slug, userCarrierDetail: userCarrier.id })" class="p-2 rounded-lg hover:bg-slate-100 transition">
                            <Lucide icon="ArrowLeft" class="w-5 h-5 text-slate-600" />
                        </Link>
                        <div>
                            <h1 class="text-xl font-bold text-slate-800">Edit Carrier User</h1>
                            <p class="text-sm text-slate-500">{{ carrier.name }} · {{ userCarrier.user?.name ?? '' }}</p>
                        </div>
                    </div>
                    <Link :href="route('admin.carriers.users.index', carrier.slug)">
                        <Button variant="outline-secondary" class="flex items-center gap-2">
                            <Lucide icon="Users" class="w-4 h-4" />
                            All Users
                        </Button>
                    </Link>
                </div>
            </div>
        </div>

        <!-- Form -->
        <div class="col-span-12">
            <form @submit.prevent="submit" class="space-y-6">
                <!-- Profile Photo -->
                <div class="box box--stacked p-6">
                    <h2 class="text-sm font-semibold text-slate-700 mb-5 flex items-center gap-2">
                        <Lucide icon="Image" class="w-4 h-4 text-primary" />
                        Profile Photo
                    </h2>
                    <div class="flex items-center gap-6">
                        <div class="w-24 h-24 rounded-full bg-slate-100 border-4 border-white shadow-md overflow-hidden flex-shrink-0 flex items-center justify-center">
                            <img v-if="photoPreview" :src="photoPreview" alt="New photo" class="w-full h-full object-cover" />
                            <img v-else-if="userCarrier.user?.profile_photo_url" :src="userCarrier.user.profile_photo_url" :alt="userCarrier.user?.name" class="w-full h-full object-cover" />
                            <Lucide v-else icon="User" class="w-10 h-10 text-slate-400" />
                        </div>
                        <div class="flex-1">
                            <input type="file" accept="image/*" @change="handlePhoto"
                                class="w-full text-sm text-slate-600 file:mr-4 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-primary/10 file:text-primary file:text-xs cursor-pointer" />
                            <p class="text-xs text-slate-400 mt-2">Leave empty to keep the current photo. Max 2MB.</p>
                            <p v-if="form.errors.profile_photo" class="text-danger text-xs mt-1">{{ form.errors.profile_photo }}</p>
                        </div>
                    </div>
                </div>

                <!-- Account Info -->
                <div class="box box--stacked p-6">
                    <h2 class="text-sm font-semibold text-slate-700 mb-5 flex items-center gap-2">
                        <Lucide icon="User" class="w-4 h-4 text-primary" />
                        Account Information
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div class="md:col-span-2">
                            <FormLabel>Full Name <span class="text-danger">*</span></FormLabel>
                            <FormInput v-model="form.name" type="text" placeholder="Full name" />
                            <p v-if="form.errors.name" class="text-danger text-xs mt-1">{{ form.errors.name }}</p>
                        </div>
                        <div class="md:col-span-2">
                            <FormLabel>Email <span class="text-danger">*</span></FormLabel>
                            <FormInput v-model="form.email" type="email" placeholder="email@example.com" />
                            <p v-if="form.errors.email" class="text-danger text-xs mt-1">{{ form.errors.email }}</p>
                        </div>
                        <div>
                            <FormLabel>New Password <span class="text-slate-400 text-xs font-normal">(leave empty to keep)</span></FormLabel>
                            <FormInput v-model="form.password" type="password" placeholder="••••••••" autocomplete="new-password" />
                            <p v-if="form.errors.password" class="text-danger text-xs mt-1">{{ form.errors.password }}</p>
                        </div>
                        <div>
                            <FormLabel>Confirm Password</FormLabel>
                            <FormInput v-model="form.password_confirmation" type="password" placeholder="••••••••" autocomplete="new-password" />
                        </div>
                    </div>
                </div>

                <!-- Carrier Details -->
                <div class="box box--stacked p-6">
                    <h2 class="text-sm font-semibold text-slate-700 mb-5 flex items-center gap-2">
                        <Lucide icon="Briefcase" class="w-4 h-4 text-primary" />
                        Carrier Details
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                        <div>
                            <FormLabel>Phone <span class="text-danger">*</span></FormLabel>
                            <FormInput v-model="form.phone" type="text" placeholder="(555) 000-0000" />
                            <p v-if="form.errors.phone" class="text-danger text-xs mt-1">{{ form.errors.phone }}</p>
                        </div>
                        <div>
                            <FormLabel>Job Position <span class="text-danger">*</span></FormLabel>
                            <FormInput v-model="form.job_position" type="text" placeholder="e.g. Admin, Manager" />
                            <p v-if="form.errors.job_position" class="text-danger text-xs mt-1">{{ form.errors.job_position }}</p>
                        </div>
                        <div>
                            <FormLabel>Status</FormLabel>
                            <FormSelect v-model="form.status">
                                <option :value="1">Active</option>
                                <option :value="0">Inactive</option>
                                <option :value="2">Pending</option>
                            </FormSelect>
                            <p v-if="form.errors.status" class="text-danger text-xs mt-1">{{ form.errors.status }}</p>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex justify-end gap-3">
                    <Link :href="route('admin.carriers.user-carriers.show', { carrier: carrier.slug, userCarrierDetail: userCarrier.id })">
                        <Button type="button" variant="outline-secondary" class="flex items-center gap-2">
                            <Lucide icon="X" class="w-4 h-4" />
                            Cancel
                        </Button>
                    </Link>
                    <Button type="submit" variant="primary" :disabled="form.processing" class="flex items-center gap-2">
                        <Lucide icon="Save" class="w-4 h-4" />
                        {{ form.processing ? 'Saving...' : 'Save Changes' }}
                    </Button>
                </div>
            </form>
        </div>
    </div>
</template>
