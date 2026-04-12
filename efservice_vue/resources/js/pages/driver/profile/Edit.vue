<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import Button from '@/components/Base/Button/Button.vue'
import { FormInput, FormLabel } from '@/components/Base/Form'
import Litepicker from '@/components/Base/Litepicker/Litepicker.vue'
import Lucide from '@/components/Base/Lucide'
import RazeLayout from '@/layouts/RazeLayout.vue'

interface Props {
    driver: {
        id: number
        first_name: string | null
        middle_name: string | null
        last_name: string | null
        full_name: string
        email: string | null
        phone: string | null
        date_of_birth: string | null
        status_name: string
        photo_url: string
        has_custom_photo: boolean
        created_at: string | null
        carrier: {
            id: number
            name: string
            dot_number: string | null
            mc_number: string | null
        } | null
    }
}

const props = defineProps<Props>()

const defaultPhotoUrl = '/build/default_profile.png'
const previewUrl = ref(props.driver.photo_url || defaultPhotoUrl)
const photoInput = ref<HTMLInputElement | null>(null)

const lpOptions = {
    autoApply: true,
    singleMode: true,
    format: 'M/D/YYYY',
    dropdowns: {
        minYear: 1940,
        maxYear: new Date().getFullYear(),
        months: true,
        years: true,
    },
}

const profileForm = useForm({
    name: props.driver.first_name ?? '',
    middle_name: props.driver.middle_name ?? '',
    last_name: props.driver.last_name ?? '',
    email: props.driver.email ?? '',
    phone: props.driver.phone ?? '',
    date_of_birth: props.driver.date_of_birth ?? '',
})

const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
})

const photoForm = useForm<{
    profile_photo: File | null
}>({
    profile_photo: null,
})

function submitProfile() {
    profileForm.put(route('driver.profile.update'), {
        preserveScroll: true,
    })
}

function submitPassword() {
    passwordForm.put(route('driver.profile.update-password'), {
        preserveScroll: true,
        onSuccess: () => passwordForm.reset(),
        onError: () => passwordForm.reset('password', 'password_confirmation', 'current_password'),
    })
}

function triggerPhotoUpload() {
    photoInput.value?.click()
}

function handlePhotoChange(event: Event) {
    const target = event.target as HTMLInputElement
    const file = target.files?.[0]

    if (!file) return

    previewUrl.value = URL.createObjectURL(file)
    photoForm.profile_photo = file
    photoForm.post(route('driver.profile.update-photo'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            photoForm.reset()
            target.value = ''
        },
    })
}

function removePhoto() {
    router.delete(route('driver.profile.delete-photo'), {
        preserveScroll: true,
        onSuccess: () => {
            previewUrl.value = defaultPhotoUrl
        },
    })
}
</script>

<template>
    <Head title="Edit Profile" />

    <RazeLayout>
        <div class="space-y-6">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-slate-900">Edit Profile</h1>
                    <p class="mt-1 text-sm text-slate-500">Update your personal information, profile photo and password.</p>
                </div>
                <Link :href="route('driver.profile')">
                    <Button variant="outline-secondary" class="gap-2">
                        <Lucide icon="ArrowLeft" class="h-4 w-4" />
                        Back to Profile
                    </Button>
                </Link>
            </div>

            <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
                <div class="space-y-6 xl:col-span-2">
                    <div class="box box--stacked p-6">
                        <div class="mb-5">
                            <h2 class="text-lg font-semibold text-slate-900">Personal Information</h2>
                            <p class="mt-1 text-sm text-slate-500">Keep your profile details current so your carrier always has the right information.</p>
                        </div>

                        <form class="space-y-5" @submit.prevent="submitProfile">
                            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                                <div>
                                    <FormLabel for="name">First Name</FormLabel>
                                    <FormInput id="name" v-model="profileForm.name" type="text" class="mt-1" />
                                    <div v-if="profileForm.errors.name" class="mt-1 text-xs text-danger">{{ profileForm.errors.name }}</div>
                                </div>
                                <div>
                                    <FormLabel for="middle_name">Middle Name</FormLabel>
                                    <FormInput id="middle_name" v-model="profileForm.middle_name" type="text" class="mt-1" />
                                    <div v-if="profileForm.errors.middle_name" class="mt-1 text-xs text-danger">{{ profileForm.errors.middle_name }}</div>
                                </div>
                                <div>
                                    <FormLabel for="last_name">Last Name</FormLabel>
                                    <FormInput id="last_name" v-model="profileForm.last_name" type="text" class="mt-1" />
                                    <div v-if="profileForm.errors.last_name" class="mt-1 text-xs text-danger">{{ profileForm.errors.last_name }}</div>
                                </div>
                                <div>
                                    <FormLabel for="email">Email</FormLabel>
                                    <FormInput id="email" v-model="profileForm.email" type="email" class="mt-1" />
                                    <div v-if="profileForm.errors.email" class="mt-1 text-xs text-danger">{{ profileForm.errors.email }}</div>
                                </div>
                                <div>
                                    <FormLabel for="phone">Phone</FormLabel>
                                    <FormInput id="phone" v-model="profileForm.phone" type="text" class="mt-1" />
                                    <div v-if="profileForm.errors.phone" class="mt-1 text-xs text-danger">{{ profileForm.errors.phone }}</div>
                                </div>
                                <div>
                                    <FormLabel for="date_of_birth">Date of Birth</FormLabel>
                                    <Litepicker v-model="profileForm.date_of_birth" :options="lpOptions" />
                                    <div v-if="profileForm.errors.date_of_birth" class="mt-1 text-xs text-danger">{{ profileForm.errors.date_of_birth }}</div>
                                </div>
                            </div>

                            <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-4">
                                <div class="text-xs uppercase tracking-wide text-slate-400">Carrier Information</div>
                                <div class="mt-2 text-sm font-semibold text-slate-900">{{ props.driver.carrier?.name ?? 'No carrier assigned' }}</div>
                                <div class="mt-2 text-xs text-slate-500">
                                    DOT: {{ props.driver.carrier?.dot_number ?? 'N/A' }} | MC: {{ props.driver.carrier?.mc_number ?? 'N/A' }}
                                </div>
                            </div>

                            <div class="flex justify-end">
                                <Button variant="primary" type="submit" :disabled="profileForm.processing" class="gap-2">
                                    <Lucide icon="Save" class="h-4 w-4" />
                                    Save Changes
                                </Button>
                            </div>
                        </form>
                    </div>

                    <div class="box box--stacked p-6">
                        <div class="mb-5">
                            <h2 class="text-lg font-semibold text-slate-900">Change Password</h2>
                            <p class="mt-1 text-sm text-slate-500">Use a strong password you do not use anywhere else.</p>
                        </div>

                        <form class="space-y-5" @submit.prevent="submitPassword">
                            <div>
                                <FormLabel for="current_password">Current Password</FormLabel>
                                <FormInput id="current_password" v-model="passwordForm.current_password" type="password" class="mt-1" />
                                <div v-if="passwordForm.errors.current_password" class="mt-1 text-xs text-danger">{{ passwordForm.errors.current_password }}</div>
                            </div>
                            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                                <div>
                                    <FormLabel for="password">New Password</FormLabel>
                                    <FormInput id="password" v-model="passwordForm.password" type="password" class="mt-1" />
                                    <div v-if="passwordForm.errors.password" class="mt-1 text-xs text-danger">{{ passwordForm.errors.password }}</div>
                                </div>
                                <div>
                                    <FormLabel for="password_confirmation">Confirm Password</FormLabel>
                                    <FormInput id="password_confirmation" v-model="passwordForm.password_confirmation" type="password" class="mt-1" />
                                </div>
                            </div>

                            <div class="flex justify-end">
                                <Button variant="primary" type="submit" :disabled="passwordForm.processing" class="gap-2">
                                    <Lucide icon="Key" class="h-4 w-4" />
                                    Update Password
                                </Button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="box box--stacked p-6">
                        <div class="mb-5">
                            <h2 class="text-lg font-semibold text-slate-900">Profile Photo</h2>
                            <p class="mt-1 text-sm text-slate-500">Upload a clear recent photo. Max size 2MB.</p>
                        </div>

                        <div class="flex flex-col items-center gap-4">
                            <img :src="previewUrl" :alt="driver.full_name" class="h-32 w-32 rounded-full border-4 border-white object-cover shadow-lg" />
                            <input ref="photoInput" type="file" accept="image/jpeg,image/jpg,image/png,image/webp" class="hidden" @change="handlePhotoChange" />
                            <div class="flex w-full flex-col gap-3">
                                <Button variant="primary" type="button" class="w-full gap-2" :disabled="photoForm.processing" @click="triggerPhotoUpload">
                                    <Lucide icon="Upload" class="h-4 w-4" />
                                    Upload New Photo
                                </Button>
                                <Button
                                    v-if="driver.has_custom_photo"
                                    variant="outline-danger"
                                    type="button"
                                    class="w-full gap-2"
                                    @click="removePhoto"
                                >
                                    <Lucide icon="Trash2" class="h-4 w-4" />
                                    Remove Photo
                                </Button>
                            </div>
                            <div v-if="photoForm.errors.profile_photo" class="text-xs text-danger">{{ photoForm.errors.profile_photo }}</div>
                        </div>
                    </div>

                    <div class="box box--stacked p-6">
                        <h2 class="text-lg font-semibold text-slate-900">Current Summary</h2>
                        <div class="mt-4 space-y-4 text-sm">
                            <div>
                                <div class="text-xs uppercase tracking-wide text-slate-400">Driver</div>
                                <div class="mt-1 font-semibold text-slate-900">{{ driver.full_name }}</div>
                            </div>
                            <div>
                                <div class="text-xs uppercase tracking-wide text-slate-400">Status</div>
                                <div class="mt-1 font-semibold text-slate-900">{{ driver.status_name }}</div>
                            </div>
                            <div>
                                <div class="text-xs uppercase tracking-wide text-slate-400">Carrier</div>
                                <div class="mt-1 font-semibold text-slate-900">{{ driver.carrier?.name ?? 'N/A' }}</div>
                            </div>
                            <div>
                                <div class="text-xs uppercase tracking-wide text-slate-400">Member Since</div>
                                <div class="mt-1 font-semibold text-slate-900">{{ driver.created_at ?? 'N/A' }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </RazeLayout>
</template>
