<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import { FormInput, FormLabel } from '@/components/Base/Form'
import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import RazeLayout from '@/layouts/RazeLayout.vue'

declare function route(name: string, params?: any): string

defineOptions({ layout: RazeLayout })

interface DriverData {
    id: number
    name: string
    middle_name: string
    last_name: string
    email: string
    phone: string
    photo_url: string | null
}

const props = defineProps<{
    driver: DriverData
    updateUrl: string
    backUrl: string
}>()

const page = usePage()
const flash = computed(() => (page.props as any).flash ?? {})

const form = useForm({
    name:                  props.driver.name,
    middle_name:           props.driver.middle_name,
    last_name:             props.driver.last_name,
    email:                 props.driver.email,
    phone:                 props.driver.phone,
    password:              '',
    password_confirmation: '',
    photo:                 null as File | null,
})

const photoPreview = ref<string | null>(null)
const currentPhoto = ref<string | null>(props.driver.photo_url)

function handlePhoto(e: Event) {
    const file = (e.target as HTMLInputElement).files?.[0]
    if (!file) return
    form.photo = file
    const reader = new FileReader()
    reader.onload = (ev) => { photoPreview.value = ev.target?.result as string }
    reader.readAsDataURL(file)
}

function submit() {
    form.transform((data) => ({ ...data, _method: 'PUT' }))
        .post(props.updateUrl, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                form.password = ''
                form.password_confirmation = ''
                form.photo = null
                photoPreview.value = null
            },
        })
}
</script>

<template>
    <Head :title="`Edit Driver: ${driver.name} ${driver.last_name}`" />

    <div class="grid grid-cols-12 gap-y-10 gap-x-6">

        <!-- Header -->
        <div class="col-span-12">
            <div class="box box--stacked p-6">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex items-center gap-4">
                        <div class="rounded-2xl border border-primary/20 bg-primary/10 p-3">
                            <Lucide icon="UserCog" class="h-8 w-8 text-primary" />
                        </div>
                        <div>
                            <div class="flex items-center gap-2 text-sm text-slate-500">
                                <Link :href="backUrl" class="hover:text-primary transition-colors">
                                    {{ driver.name }} {{ driver.last_name }}
                                </Link>
                                <Lucide icon="ChevronRight" class="h-3.5 w-3.5" />
                                <span class="text-slate-700 font-medium">Edit Info</span>
                            </div>
                            <h1 class="mt-1 text-xl font-bold text-slate-800">Edit Driver Info</h1>
                            <p class="text-sm text-slate-500">Update photo, name, contact details and password.</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <Link :href="backUrl">
                            <Button variant="outline-secondary" class="flex items-center gap-2">
                                <Lucide icon="Eye" class="h-4 w-4" />
                                View Profile
                            </Button>
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <!-- Flash success -->
        <div v-if="flash.success" class="col-span-12">
            <div class="flex items-center gap-3 rounded-xl border border-success/20 bg-success/5 px-4 py-3">
                <Lucide icon="CheckCircle2" class="h-5 w-5 flex-shrink-0 text-success" />
                <span class="text-sm font-medium text-success">{{ flash.success }}</span>
            </div>
        </div>

        <!-- Form -->
        <div class="col-span-12">
            <form @submit.prevent="submit">
                <div class="grid grid-cols-12 gap-6">

                    <!-- Left: fields -->
                    <div class="col-span-12 lg:col-span-8 space-y-6">

                        <!-- Basic Info -->
                        <div class="box box--stacked p-6">
                            <div class="mb-6 flex items-center gap-3">
                                <div class="rounded-lg border border-primary/20 bg-primary/10 p-2">
                                    <Lucide icon="User" class="h-4 w-4 text-primary" />
                                </div>
                                <h2 class="text-base font-semibold text-slate-800">Personal Information</h2>
                            </div>

                            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                                <!-- First Name -->
                                <div>
                                    <FormLabel for="name" class="mb-1.5">
                                        First Name <span class="text-danger">*</span>
                                    </FormLabel>
                                    <FormInput
                                        id="name"
                                        v-model="form.name"
                                        type="text"
                                        placeholder="First name"
                                        :class="{ 'border-danger': form.errors.name }"
                                    />
                                    <p v-if="form.errors.name" class="mt-1 text-xs text-danger">{{ form.errors.name }}</p>
                                </div>

                                <!-- Middle Name -->
                                <div>
                                    <FormLabel for="middle_name" class="mb-1.5">Middle Name</FormLabel>
                                    <FormInput
                                        id="middle_name"
                                        v-model="form.middle_name"
                                        type="text"
                                        placeholder="Middle name (optional)"
                                        :class="{ 'border-danger': form.errors.middle_name }"
                                    />
                                    <p v-if="form.errors.middle_name" class="mt-1 text-xs text-danger">{{ form.errors.middle_name }}</p>
                                </div>

                                <!-- Last Name -->
                                <div class="md:col-span-2">
                                    <FormLabel for="last_name" class="mb-1.5">
                                        Last Name <span class="text-danger">*</span>
                                    </FormLabel>
                                    <FormInput
                                        id="last_name"
                                        v-model="form.last_name"
                                        type="text"
                                        placeholder="Last name"
                                        :class="{ 'border-danger': form.errors.last_name }"
                                    />
                                    <p v-if="form.errors.last_name" class="mt-1 text-xs text-danger">{{ form.errors.last_name }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Contact Info -->
                        <div class="box box--stacked p-6">
                            <div class="mb-6 flex items-center gap-3">
                                <div class="rounded-lg border border-info/20 bg-info/10 p-2">
                                    <Lucide icon="Mail" class="h-4 w-4 text-info" />
                                </div>
                                <h2 class="text-base font-semibold text-slate-800">Contact Details</h2>
                            </div>

                            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                                <!-- Email -->
                                <div>
                                    <FormLabel for="email" class="mb-1.5">
                                        Email <span class="text-danger">*</span>
                                    </FormLabel>
                                    <FormInput
                                        id="email"
                                        v-model="form.email"
                                        type="email"
                                        autocomplete="off"
                                        placeholder="driver@example.com"
                                        :class="{ 'border-danger': form.errors.email }"
                                    />
                                    <p v-if="form.errors.email" class="mt-1 text-xs text-danger">{{ form.errors.email }}</p>
                                </div>

                                <!-- Phone -->
                                <div>
                                    <FormLabel for="phone" class="mb-1.5">Phone</FormLabel>
                                    <FormInput
                                        id="phone"
                                        v-model="form.phone"
                                        type="tel"
                                        placeholder="(000) 000-0000"
                                        :class="{ 'border-danger': form.errors.phone }"
                                    />
                                    <p v-if="form.errors.phone" class="mt-1 text-xs text-danger">{{ form.errors.phone }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Password -->
                        <div class="box box--stacked p-6">
                            <div class="mb-6 flex items-center gap-3">
                                <div class="rounded-lg border border-warning/20 bg-warning/10 p-2">
                                    <Lucide icon="Lock" class="h-4 w-4 text-warning" />
                                </div>
                                <div>
                                    <h2 class="text-base font-semibold text-slate-800">Password</h2>
                                    <p class="text-xs text-slate-500">Leave blank to keep the current password.</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                                <div>
                                    <FormLabel for="password" class="mb-1.5">New Password</FormLabel>
                                    <FormInput
                                        id="password"
                                        v-model="form.password"
                                        type="password"
                                        autocomplete="new-password"
                                        placeholder="Min. 8 characters"
                                        :class="{ 'border-danger': form.errors.password }"
                                    />
                                    <p v-if="form.errors.password" class="mt-1 text-xs text-danger">{{ form.errors.password }}</p>
                                </div>

                                <div>
                                    <FormLabel for="password_confirmation" class="mb-1.5">Confirm Password</FormLabel>
                                    <FormInput
                                        id="password_confirmation"
                                        v-model="form.password_confirmation"
                                        type="password"
                                        autocomplete="new-password"
                                        placeholder="Repeat new password"
                                    />
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Right: photo + submit -->
                    <div class="col-span-12 lg:col-span-4 space-y-6">

                        <!-- Photo -->
                        <div class="box box--stacked p-6">
                            <div class="mb-5 flex items-center gap-3">
                                <div class="rounded-lg border border-slate-200 bg-slate-50 p-2">
                                    <Lucide icon="Camera" class="h-4 w-4 text-slate-500" />
                                </div>
                                <h3 class="text-base font-semibold text-slate-800">Profile Photo</h3>
                            </div>

                            <div class="flex flex-col items-center gap-4">
                                <!-- Avatar preview -->
                                <div class="relative">
                                    <div class="flex h-28 w-28 items-center justify-center overflow-hidden rounded-full border-4 border-white bg-slate-100 shadow-md ring-1 ring-slate-200">
                                        <img
                                            v-if="photoPreview"
                                            :src="photoPreview"
                                            class="h-full w-full object-cover"
                                            alt="Preview"
                                        />
                                        <img
                                            v-else-if="currentPhoto"
                                            :src="currentPhoto"
                                            class="h-full w-full object-cover"
                                            alt="Profile photo"
                                        />
                                        <Lucide v-else icon="User" class="h-12 w-12 text-slate-300" />
                                    </div>
                                    <!-- Preview badge -->
                                    <span
                                        v-if="photoPreview"
                                        class="absolute -bottom-1 left-1/2 -translate-x-1/2 rounded-full bg-warning/10 px-2 py-0.5 text-[10px] font-medium text-warning ring-1 ring-warning/20"
                                    >
                                        Preview
                                    </span>
                                </div>

                                <!-- Upload button -->
                                <label class="w-full cursor-pointer">
                                    <span class="flex w-full items-center justify-center gap-2 rounded-lg border border-dashed border-slate-300 bg-slate-50 px-4 py-3 text-sm text-slate-600 transition hover:border-primary hover:bg-primary/5 hover:text-primary dark:border-darkmode-400 dark:bg-darkmode-600 dark:text-slate-400">
                                        <Lucide icon="Upload" class="h-4 w-4" />
                                        {{ photoPreview ? 'Change photo' : 'Upload photo' }}
                                    </span>
                                    <input
                                        type="file"
                                        accept="image/jpeg,image/png,image/gif,image/webp"
                                        class="hidden"
                                        @change="handlePhoto"
                                    />
                                </label>

                                <p v-if="form.errors.photo" class="w-full text-xs text-danger">{{ form.errors.photo }}</p>

                                <p class="text-center text-xs text-slate-400">
                                    JPG, PNG, GIF or WebP &mdash; max 3 MB
                                </p>
                            </div>
                        </div>

                        <!-- Save button -->
                        <Button
                            type="submit"
                            variant="primary"
                            class="flex w-full items-center justify-center gap-2 shadow-sm"
                            :disabled="form.processing"
                        >
                            <Lucide v-if="form.processing" icon="Loader" class="h-4 w-4 animate-spin" />
                            <Lucide v-else icon="Save" class="h-4 w-4" />
                            {{ form.processing ? 'Saving...' : 'Save Changes' }}
                        </Button>

                        <!-- Back link -->
                        <div class="text-center">
                            <Link
                                :href="backUrl"
                                class="text-sm text-slate-400 hover:text-slate-600 transition-colors"
                            >
                                Cancel and go back
                            </Link>
                        </div>
                    </div>

                </div>
            </form>
        </div>

    </div>
</template>
