<script setup lang="ts">
import { Head, useForm, Link } from '@inertiajs/vue3'
import AuthLayout from '@/layouts/AuthLayout.vue'
import { ref, computed } from 'vue'
import { Eye, EyeOff, Check, Loader2, Truck, MapPin, FileText, Building2, ShieldCheck, Lock } from 'lucide-vue-next'
import type { Carrier } from '@/types'

interface Props {
    carrier: Carrier
    isIndependent: boolean
    token: string | null
}

const props = defineProps<Props>()

const form = useForm({
    name: '',
    middle_name: '',
    last_name: '',
    email: '',
    phone: '',
    date_of_birth: '',
    license_number: '',
    password: '',
    password_confirmation: '',
    terms_accepted: false,
    carrier_slug: props.carrier.slug,
    token: props.token,
})

const showPassword = ref(false)
const showConfirmPassword = ref(false)

const passwordStrength = computed(() => {
    const p = form.password
    if (!p) return { score: 0, label: '', color: '' }
    let score = 0
    if (p.length >= 8) score++
    if (/[a-z]/.test(p) && /[A-Z]/.test(p)) score++
    if (/\d/.test(p)) score++
    if (/[^a-zA-Z0-9]/.test(p)) score++
    if (p.length >= 12) score++

    const levels = [
        { label: '', color: '' },
        { label: 'Weak', color: 'bg-red-500' },
        { label: 'Fair', color: 'bg-orange-500' },
        { label: 'Good', color: 'bg-yellow-500' },
        { label: 'Strong', color: 'bg-green-500' },
        { label: 'Very Strong', color: 'bg-green-600' },
    ]
    return { score, ...levels[score] }
})

const carrierLogo = computed(() => {
    const media = props.carrier.media ?? []
    const logo = media.find((m: any) => m.collection_name === 'logo_carrier')
    return logo?.original_url ?? null
})

function submit() {
    if (props.isIndependent) {
        form.post(route('driver.register.independent'), { preserveScroll: true })
    } else {
        form.post(route('driver.register.submit', { carrier: props.carrier.slug }), { preserveScroll: true })
    }
}
</script>

<template>
    <Head :title="`Driver Registration - ${carrier.name}`" />
    <AuthLayout>
        <div class="mx-auto w-full max-w-4xl px-4 py-8">
            <!-- Carrier Header -->
            <div class="mb-6 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <div class="p-6 sm:p-8">
                    <div class="flex flex-col items-start gap-6 lg:flex-row">
                        <div class="flex size-24 shrink-0 items-center justify-center rounded-lg border-2 border-gray-200 bg-white p-2 dark:border-gray-600 dark:bg-gray-800">
                            <img v-if="carrierLogo" :src="carrierLogo" :alt="carrier.name" class="size-full object-contain" />
                            <Truck v-else class="size-10 text-gray-400" />
                        </div>
                        <div class="flex-1">
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white sm:text-3xl">{{ carrier.name }}</h1>
                            <p class="mt-1 text-gray-500 dark:text-gray-400">Driver Registration Application</p>

                            <div class="mt-4 flex flex-wrap gap-2">
                                <span v-if="carrier.dot_number" class="inline-flex items-center gap-1.5 rounded-md bg-primary px-3 py-1.5 text-xs font-semibold text-white">
                                    <FileText class="size-3.5" /> DOT: {{ carrier.dot_number }}
                                </span>
                                <span v-if="carrier.mc_number" class="inline-flex items-center gap-1.5 rounded-md bg-primary px-3 py-1.5 text-xs font-semibold text-white">
                                    <FileText class="size-3.5" /> MC: {{ carrier.mc_number }}
                                </span>
                                <span v-if="carrier.state" class="inline-flex items-center gap-1.5 rounded-md bg-primary px-3 py-1.5 text-xs font-semibold text-white">
                                    <MapPin class="size-3.5" /> {{ carrier.state }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Registration Form -->
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900 sm:p-8">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">Personal Information</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Fill in your details to apply as a driver</p>

                <form @submit.prevent="submit" class="mt-6 space-y-5">
                    <!-- Name Fields -->
                    <div class="grid gap-5 sm:grid-cols-3">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">First Name *</label>
                            <input v-model="form.name" type="text" required class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-white" placeholder="John" />
                            <p v-if="form.errors.name" class="mt-1 text-xs text-red-500">{{ form.errors.name }}</p>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Middle Name</label>
                            <input v-model="form.middle_name" type="text" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-white" placeholder="Michael" />
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Last Name *</label>
                            <input v-model="form.last_name" type="text" required class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-white" placeholder="Doe" />
                            <p v-if="form.errors.last_name" class="mt-1 text-xs text-red-500">{{ form.errors.last_name }}</p>
                        </div>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Email *</label>
                        <input v-model="form.email" type="email" required class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-white" placeholder="john@email.com" />
                        <p v-if="form.errors.email" class="mt-1 text-xs text-red-500">{{ form.errors.email }}</p>
                    </div>

                    <div class="grid gap-5 sm:grid-cols-3">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Phone *</label>
                            <input v-model="form.phone" type="tel" required class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-white" placeholder="(555) 123-4567" />
                            <p v-if="form.errors.phone" class="mt-1 text-xs text-red-500">{{ form.errors.phone }}</p>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Date of Birth *</label>
                            <input v-model="form.date_of_birth" type="date" required class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-white" />
                            <p v-if="form.errors.date_of_birth" class="mt-1 text-xs text-red-500">{{ form.errors.date_of_birth }}</p>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">License Number *</label>
                            <input v-model="form.license_number" type="text" required class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm uppercase dark:border-gray-600 dark:bg-gray-800 dark:text-white" placeholder="ABC123456" />
                            <p v-if="form.errors.license_number" class="mt-1 text-xs text-red-500">{{ form.errors.license_number }}</p>
                        </div>
                    </div>

                    <!-- Password -->
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Password *</label>
                            <div class="relative">
                                <input v-model="form.password" :type="showPassword ? 'text' : 'password'" required class="w-full rounded-lg border border-gray-300 px-3 py-2.5 pr-10 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-white" placeholder="Min. 8 characters" />
                                <button type="button" @click="showPassword = !showPassword" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                    <EyeOff v-if="showPassword" class="size-4" />
                                    <Eye v-else class="size-4" />
                                </button>
                            </div>
                            <div v-if="form.password" class="mt-2">
                                <div class="flex gap-1">
                                    <div v-for="i in 5" :key="i" class="h-1.5 flex-1 rounded-full transition-all" :class="i <= passwordStrength.score ? passwordStrength.color : 'bg-gray-200 dark:bg-gray-700'" />
                                </div>
                                <p class="mt-1 text-xs" :class="passwordStrength.score >= 3 ? 'text-green-600' : 'text-gray-500'">{{ passwordStrength.label }}</p>
                            </div>
                            <p v-if="form.errors.password" class="mt-1 text-xs text-red-500">{{ form.errors.password }}</p>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Confirm Password *</label>
                            <div class="relative">
                                <input v-model="form.password_confirmation" :type="showConfirmPassword ? 'text' : 'password'" required class="w-full rounded-lg border border-gray-300 px-3 py-2.5 pr-10 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-white" placeholder="Repeat password" />
                                <button type="button" @click="showConfirmPassword = !showConfirmPassword" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                    <EyeOff v-if="showConfirmPassword" class="size-4" />
                                    <Eye v-else class="size-4" />
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Terms -->
                    <div>
                        <label class="flex items-start gap-3">
                            <input v-model="form.terms_accepted" type="checkbox" class="mt-0.5 rounded border-gray-300 text-primary focus:ring-primary" />
                            <span class="text-sm text-gray-600 dark:text-gray-400">
                                I agree to the <a href="#" class="text-primary hover:underline">Terms of Service</a> and <a href="#" class="text-primary hover:underline">Privacy Policy</a> *
                            </span>
                        </label>
                        <p v-if="form.errors.terms_accepted" class="mt-1 text-xs text-red-500">{{ form.errors.terms_accepted }}</p>
                    </div>

                    <p v-if="form.errors.error" class="rounded-lg bg-red-50 p-3 text-sm text-red-600 dark:bg-red-900/30 dark:text-red-400">{{ form.errors.error }}</p>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="flex w-full items-center justify-center gap-2 rounded-lg bg-primary px-4 py-3 text-sm font-semibold text-white transition hover:bg-primary/90 disabled:opacity-50"
                    >
                        <Loader2 v-if="form.processing" class="size-4 animate-spin" />
                        Submit Registration
                    </button>

                    <p class="text-center text-sm text-gray-500 dark:text-gray-400">
                        Already have an account?
                        <Link :href="route('login')" class="font-medium text-primary hover:underline">Sign in</Link>
                    </p>
                </form>
            </div>

            <!-- Security Footer -->
            <div class="mt-6 overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
                <div class="flex items-center justify-between gap-4 border-b border-gray-200 bg-gray-50 px-6 py-3 dark:border-gray-700 dark:bg-gray-800">
                    <div class="flex items-center gap-3">
                        <ShieldCheck class="size-5 text-green-600" />
                        <div>
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">Secure & Encrypted</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Your information is protected with industry-standard encryption</p>
                        </div>
                    </div>
                    <div class="hidden items-center gap-4 text-xs text-gray-500 sm:flex">
                        <span class="flex items-center gap-1"><Lock class="size-3.5" /> SSL Protected</span>
                    </div>
                </div>
                <div class="px-6 py-2.5">
                    <p class="text-center text-xs text-gray-500 dark:text-gray-400">
                        By continuing, you agree to our Terms of Service and Privacy Policy.
                    </p>
                </div>
            </div>
        </div>
    </AuthLayout>
</template>
