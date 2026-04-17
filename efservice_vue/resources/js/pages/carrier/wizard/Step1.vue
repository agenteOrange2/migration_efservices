<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import Button from '@/components/Base/Button'
import { FormInput, FormLabel } from '@/components/Base/Form'
import Lucide from '@/components/Base/Lucide'
import TomSelect from '@/components/Base/TomSelect/TomSelect.vue'
import axios from 'axios'
import { computed, ref, watch } from 'vue'

const form = useForm({
    full_name: '',
    email: '',
    phone: '',
    country_code: 'US',
    job_position: '',
    password: '',
    password_confirmation: '',
    terms_accepted: false,
    marketing_consent: false,
})

const countryOptions = [
    { value: 'US', label: '+1 United States' },
    { value: 'CA', label: '+1 Canada' },
    { value: 'MX', label: '+52 Mexico' },
]

const jobPositions = [
    { value: 'Owner', label: 'Owner' },
    { value: 'Manager', label: 'Manager' },
    { value: 'Dispatcher', label: 'Dispatcher' },
    { value: 'Safety Manager', label: 'Safety Manager' },
    { value: 'Operations Manager', label: 'Operations Manager' },
    { value: 'Other', label: 'Other' },
]

const showPassword = ref(false)
const showPasswordConfirmation = ref(false)
const emailStatus = ref<'idle' | 'checking' | 'available' | 'taken' | 'invalid'>('idle')

let emailTimeout: ReturnType<typeof setTimeout> | null = null
let latestEmailRequestId = 0

const csrfToken =
    (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null)?.content ?? ''

const currentStep = 1
const steps = [
    { number: 1, title: 'Basic Info', description: 'Create your account' },
    { number: 2, title: 'Company', description: 'Business details' },
    { number: 3, title: 'Membership', description: 'Choose your plan' },
    { number: 4, title: 'Banking', description: 'Finalize setup' },
]

const phonePlaceholder = computed(() =>
    form.country_code === 'MX' ? '123 456 7890' : '(123) 456-7890',
)

const emailHint = computed(() => {
    switch (emailStatus.value) {
        case 'checking':
            return { text: 'Checking availability...', tone: 'text-slate-500' }
        case 'available':
            return { text: 'Email is available.', tone: 'text-primary' }
        case 'taken':
            return { text: 'This email is already registered.', tone: 'text-red-500' }
        case 'invalid':
            return { text: 'Enter a valid email address to continue.', tone: 'text-red-500' }
        default:
            return null
    }
})

const passwordChecks = computed(() => [
    {
        label: 'At least 8 characters',
        complete: form.password.length >= 8,
    },
    {
        label: 'Different from your email',
        complete:
            form.password.length > 0 &&
            form.email.trim().length > 0 &&
            form.password.toLowerCase() !== form.email.trim().toLowerCase(),
    },
    {
        label: 'Confirmation matches',
        complete:
            form.password_confirmation.length > 0 &&
            form.password_confirmation === form.password,
    },
])

const passwordStrength = computed(() => {
    const p = form.password

    if (!p) {
        return {
            score: 0,
            label: 'Create a password with at least 8 characters.',
            color: 'bg-slate-200',
        }
    }

    let score = 0
    if (p.length >= 8) score++
    if (/[a-z]/.test(p) && /[A-Z]/.test(p)) score++
    if (/\d/.test(p)) score++
    if (/[^a-zA-Z0-9]/.test(p)) score++

    const levels = [
        { label: 'Very weak', color: 'bg-red-500' },
        { label: 'Weak', color: 'bg-orange-500' },
        { label: 'Fair', color: 'bg-yellow-500' },
        { label: 'Strong', color: 'bg-primary' },
        { label: 'Very strong', color: 'bg-primary' },
    ]

    return { score, ...levels[Math.min(score, levels.length - 1)] }
})

const canSubmit = computed(
    () => !form.processing && emailStatus.value !== 'checking' && emailStatus.value !== 'taken',
)

watch(
    () => form.country_code,
    () => {
        formatPhoneInput()
    },
)

watch(
    () => form.email,
    (value) => {
        emailStatus.value = 'idle'

        if (emailTimeout) {
            clearTimeout(emailTimeout)
        }

        const normalized = value.trim().toLowerCase()

        if (!normalized) return

        if (!isValidEmail(normalized)) {
            emailStatus.value = 'invalid'
            return
        }

        emailStatus.value = 'checking'
        emailTimeout = setTimeout(() => {
            checkEmailAvailability(normalized)
        }, 450)
    },
)

function normalizeEmail() {
    form.email = form.email.trim().toLowerCase()
}

function isValidEmail(value: string) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)
}

function formatPhoneInput() {
    const digits = form.phone.replace(/\D/g, '').slice(0, 10)

    if (!digits) {
        form.phone = ''
        return
    }

    if (form.country_code === 'MX') {
        if (digits.length <= 3) {
            form.phone = digits
            return
        }

        if (digits.length <= 6) {
            form.phone = `${digits.slice(0, 3)} ${digits.slice(3)}`
            return
        }

        form.phone = `${digits.slice(0, 3)} ${digits.slice(3, 6)} ${digits.slice(6)}`
        return
    }

    if (digits.length <= 3) {
        form.phone = `(${digits}`
        return
    }

    if (digits.length <= 6) {
        form.phone = `(${digits.slice(0, 3)}) ${digits.slice(3)}`
        return
    }

    form.phone = `(${digits.slice(0, 3)}) ${digits.slice(3, 6)}-${digits.slice(6)}`
}

async function checkEmailAvailability(value: string) {
    const requestId = ++latestEmailRequestId

    try {
        const { data } = await axios.post(
            route('carrier.wizard.check.uniqueness'),
            { field: 'email', value },
            {
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    Accept: 'application/json',
                },
            },
        )

        if (requestId !== latestEmailRequestId || value !== form.email.trim().toLowerCase()) {
            return
        }

        emailStatus.value = data.unique ? 'available' : 'taken'
    } catch {
        if (requestId === latestEmailRequestId) {
            emailStatus.value = 'idle'
        }
    }
}

function submit() {
    normalizeEmail()
    form.post(route('carrier.wizard.step1.process'), { preserveScroll: true })
}
</script>

<template>
    <Head title="Carrier Registration - Step 1" />
    <div class="container grid lg:h-screen grid-cols-12 lg:max-w-[1550px] 2xl:max-w-[1750px] py-10 px-5 sm:py-14 sm:px-10 md:px-36 lg:py-0 lg:pl-14 lg:pr-12 xl:px-24">
        <div :class="[
            'relative z-50 h-full col-span-12 p-7 sm:p-14 bg-white rounded-2xl lg:bg-transparent lg:pr-10 lg:col-span-5 xl:pr-24 2xl:col-span-4 lg:p-0',
            'before:content-[\'\'] before:absolute before:inset-0 before:-mb-3.5 before:bg-white/40 before:rounded-2xl before:mx-5',
        ]">
            <div class="relative z-10 flex flex-col justify-center w-full h-full py-2 lg:py-16">
                <div class="rounded-[0.8rem] w-[55px] h-[55px] border border-primary/30 flex items-center justify-center">
                    <div class="relative flex items-center justify-center w-[50px] rounded-[0.6rem] h-[50px] bg-linear-to-b from-theme-1/90 to-theme-2/90 bg-white">
                        <Lucide icon="Truck" class="w-8 h-8 text-primary" />
                    </div>
                </div>

                <!-- Progress Steps -->
                <div class="mt-6 flex items-center gap-1.5">
                    <template v-for="step in steps" :key="step.number">
                        <div class="flex items-center gap-1.5">
                            <div class="flex items-center justify-center w-7 h-7 rounded-full text-xs font-bold transition-all"
                                :class="step.number <= currentStep
                                    ? 'bg-linear-to-b from-theme-1 to-theme-2 text-white'
                                    : 'bg-slate-200 text-slate-400'">
                                <Lucide v-if="step.number < currentStep" icon="Check" class="w-3.5 h-3.5" />
                                <span v-else>{{ step.number }}</span>
                            </div>
                            <span class="text-xs font-medium hidden sm:inline"
                                :class="step.number <= currentStep ? 'text-slate-700' : 'text-slate-400'">
                                {{ step.title }}
                            </span>
                        </div>
                        <div v-if="step.number < steps.length" class="w-5 h-px bg-slate-300" />
                    </template>
                </div>

                <div class="mt-6">
                    <div class="text-2xl font-semibold text-slate-800">Create your carrier account</div>
                    <div class="mt-2.5 text-slate-600">
                        Already have an account?
                        <Link :href="route('login')" class="font-medium text-primary">Sign in</Link>
                    </div>

                    <div class="mt-5 rounded-2xl border border-primary/15 bg-primary/[0.04] p-4 text-sm text-slate-600">
                        <div class="flex items-start gap-3">
                            <div class="rounded-xl bg-primary/10 p-2">
                                <Lucide icon="ShieldCheck" class="h-4 w-4 text-primary" />
                            </div>
                            <div>
                                <div class="font-medium text-slate-700">What happens next</div>
                                <div class="mt-1 leading-6">
                                    We create your login first, then you continue with company details,
                                    membership and banking in the next steps.
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-if="form.errors.general" class="mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        {{ form.errors.general }}
                    </div>

                    <form @submit.prevent="submit" class="mt-6 space-y-5">
                        <div class="rounded-2xl border border-slate-200/80 bg-white/80 p-5 shadow-sm">
                            <div class="mb-4 flex items-center gap-3">
                                <div class="rounded-xl bg-slate-100 p-2">
                                    <Lucide icon="UserRound" class="h-4 w-4 text-slate-600" />
                                </div>
                                <div>
                                    <div class="text-sm font-semibold text-slate-800">Account owner information</div>
                                    <div class="text-xs text-slate-500">
                                        This person will manage the carrier registration.
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-4">
                                <div>
                                    <FormLabel>Full Name *</FormLabel>
                                    <FormInput v-model="form.full_name" type="text" required class="block rounded-[0.8rem] border-slate-300/80 px-4 py-3.5" placeholder="John Doe" />
                                    <p v-if="form.errors.full_name" class="mt-1 text-xs text-red-500">{{ form.errors.full_name }}</p>
                                </div>

                                <div>
                                    <FormLabel>Email Address *</FormLabel>
                                    <div class="relative">
                                        <FormInput
                                            v-model="form.email"
                                            type="email"
                                            required
                                            class="block rounded-[0.8rem] border-slate-300/80 px-4 py-3.5 pr-10"
                                            placeholder="john@company.com"
                                            @blur="normalizeEmail"
                                        />
                                        <div class="absolute right-3 top-1/2 -translate-y-1/2">
                                            <Lucide v-if="emailStatus === 'checking'" icon="Loader" class="h-4 w-4 animate-spin text-slate-400" />
                                            <Lucide v-else-if="emailStatus === 'available'" icon="CheckCircle2" class="h-4 w-4 text-primary" />
                                            <Lucide v-else-if="emailStatus === 'taken' || emailStatus === 'invalid'" icon="AlertCircle" class="h-4 w-4 text-red-500" />
                                        </div>
                                    </div>
                                    <p v-if="emailHint" class="mt-1 text-xs" :class="emailHint.tone">{{ emailHint.text }}</p>
                                    <p v-if="form.errors.email" class="mt-1 text-xs text-red-500">{{ form.errors.email }}</p>
                                </div>

                                <div class="grid grid-cols-1 gap-4 sm:grid-cols-[160px_minmax(0,1fr)]">
                                    <div>
                                        <FormLabel>Country *</FormLabel>
                                        <TomSelect v-model="form.country_code">
                                            <option v-for="country in countryOptions" :key="country.value" :value="country.value">
                                                {{ country.label }}
                                            </option>
                                        </TomSelect>
                                    </div>
                                    <div>
                                        <FormLabel>Phone Number *</FormLabel>
                                        <FormInput
                                            v-model="form.phone"
                                            type="tel"
                                            required
                                            class="block rounded-[0.8rem] border-slate-300/80 px-4 py-3.5"
                                            :placeholder="phonePlaceholder"
                                            @input="formatPhoneInput"
                                        />
                                        <p class="mt-1 text-xs text-slate-500">
                                            We use this for onboarding updates and validation follow-up.
                                        </p>
                                        <p v-if="form.errors.phone" class="mt-1 text-xs text-red-500">{{ form.errors.phone }}</p>
                                    </div>
                                </div>

                                <div>
                                    <FormLabel>Job Position *</FormLabel>
                                    <TomSelect v-model="form.job_position">
                                        <option value="">Select your position</option>
                                        <option v-for="pos in jobPositions" :key="pos.value" :value="pos.value">{{ pos.label }}</option>
                                    </TomSelect>
                                    <p v-if="form.errors.job_position" class="mt-1 text-xs text-red-500">{{ form.errors.job_position }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-2xl border border-slate-200/80 bg-white/80 p-5 shadow-sm">
                            <div class="mb-4 flex items-center gap-3">
                                <div class="rounded-xl bg-slate-100 p-2">
                                    <Lucide icon="LockKeyhole" class="h-4 w-4 text-slate-600" />
                                </div>
                                <div>
                                    <div class="text-sm font-semibold text-slate-800">Security</div>
                                    <div class="text-xs text-slate-500">
                                        Use a password you have not used on another account.
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-4">
                                <div>
                                    <FormLabel>Password *</FormLabel>
                                    <div class="relative">
                                        <FormInput v-model="form.password" :type="showPassword ? 'text' : 'password'" required class="block rounded-[0.8rem] border-slate-300/80 px-4 py-3.5 pr-10" placeholder="Create your password" />
                                        <button type="button" @click="showPassword = !showPassword" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                                            <Lucide :icon="showPassword ? 'EyeOff' : 'Eye'" class="h-4 w-4" />
                                        </button>
                                    </div>
                                    <div class="mt-3 flex gap-1">
                                        <div v-for="i in 4" :key="i" class="h-1.5 flex-1 rounded-full transition-all" :class="i <= Math.min(passwordStrength.score, 4) ? passwordStrength.color : 'bg-slate-200'" />
                                    </div>
                                    <div class="mt-2 flex items-center justify-between text-xs">
                                        <span class="text-slate-500">{{ passwordStrength.label }}</span>
                                        <span class="text-slate-400">Minimum 8 characters</span>
                                    </div>
                                    <div class="mt-3 grid gap-2 sm:grid-cols-3">
                                        <div
                                            v-for="check in passwordChecks"
                                            :key="check.label"
                                            class="flex items-center gap-2 rounded-xl border px-3 py-2 text-xs"
                                            :class="check.complete ? 'border-primary/20 bg-primary/[0.06] text-slate-700' : 'border-slate-200 bg-slate-50 text-slate-500'"
                                        >
                                            <Lucide :icon="check.complete ? 'CheckCircle2' : 'CircleDashed'" class="h-3.5 w-3.5" :class="check.complete ? 'text-primary' : 'text-slate-400'" />
                                            <span>{{ check.label }}</span>
                                        </div>
                                    </div>
                                    <p v-if="form.errors.password" class="mt-2 text-xs text-red-500">{{ form.errors.password }}</p>
                                </div>

                                <div>
                                    <FormLabel>Confirm Password *</FormLabel>
                                    <div class="relative">
                                        <FormInput v-model="form.password_confirmation" :type="showPasswordConfirmation ? 'text' : 'password'" required class="block rounded-[0.8rem] border-slate-300/80 px-4 py-3.5 pr-10" placeholder="Repeat your password" />
                                        <button type="button" @click="showPasswordConfirmation = !showPasswordConfirmation" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                                            <Lucide :icon="showPasswordConfirmation ? 'EyeOff' : 'Eye'" class="h-4 w-4" />
                                        </button>
                                    </div>
                                    <p v-if="form.password_confirmation && form.password_confirmation !== form.password" class="mt-1 text-xs text-red-500">
                                        Password confirmation does not match.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-3 rounded-2xl border border-slate-200/80 bg-white/80 p-5 shadow-sm">
                            <label class="flex items-start gap-2.5 cursor-pointer">
                                <input
                                    v-model="form.terms_accepted"
                                    type="checkbox"
                                    :true-value="true"
                                    :false-value="false"
                                    class="mt-1 border shadow-sm border-slate-200 cursor-pointer rounded focus:ring-4 focus:ring-offset-0 focus:ring-primary/20 checked:bg-primary checked:border-primary/10 transition-all duration-100"
                                />
                                <span class="text-sm leading-6 text-slate-600">
                                    I agree to the <a :href="route('terms')" target="_blank" class="text-primary font-medium">Terms of Service</a> and <a :href="route('privacy')" target="_blank" class="text-primary font-medium">Privacy Policy</a> *
                                </span>
                            </label>
                            <p v-if="form.errors.terms_accepted" class="text-xs text-red-500">{{ form.errors.terms_accepted }}</p>
                            <label class="flex items-start gap-2.5 cursor-pointer">
                                <input
                                    v-model="form.marketing_consent"
                                    type="checkbox"
                                    :true-value="true"
                                    :false-value="false"
                                    class="mt-1 border shadow-sm border-slate-200 cursor-pointer rounded focus:ring-4 focus:ring-offset-0 focus:ring-primary/20 checked:bg-primary checked:border-primary/10 transition-all duration-100"
                                />
                                <span class="text-sm leading-6 text-slate-600">
                                    I want updates, product improvements and onboarding tips from EF Services.
                                </span>
                            </label>
                        </div>

                        <div class="pt-1">
                            <Button type="submit" variant="primary" rounded class="bg-linear-to-r from-theme-1/80 to-theme-2/80 w-full py-3.5" :disabled="!canSubmit">
                                <Lucide v-if="form.processing" icon="Loader" class="w-5 h-5 animate-spin mr-2" />
                                {{ form.processing ? 'Creating account...' : 'Continue to company information' }}
                            </Button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- Background -->
    <div class="fixed container grid w-screen inset-0 h-screen grid-cols-12 lg:max-w-[1550px] 2xl:max-w-[1750px] pl-14 pr-12 xl:px-24">
        <div :class="[
            'relative h-screen col-span-12 lg:col-span-5 2xl:col-span-4 z-20',
            'after:bg-white after:hidden after:lg:block after:content-[\'\'] after:absolute after:right-0 after:inset-y-0 after:bg-linear-to-b after:from-white after:to-slate-100/80 after:w-[800%] after:rounded-[0_1.2rem_1.2rem_0/0_1.7rem_1.7rem_0]',
            'before:content-[\'\'] before:hidden before:lg:block before:absolute before:right-0 before:inset-y-0 before:my-6 before:bg-linear-to-b before:from-white/10 before:to-slate-50/10 before:bg-white/50 before:w-[800%] before:-mr-4 before:rounded-[0_1.2rem_1.2rem_0/0_1.7rem_1.7rem_0]',
        ]" />
        <div :class="[
            'h-full col-span-7 2xl:col-span-8 lg:relative',
            'before:content-[\'\'] before:absolute before:lg:-ml-10 before:left-0 before:inset-y-0 before:bg-linear-to-b before:from-theme-1 before:to-theme-2 before:w-screen before:lg:w-[800%]',
            'after:content-[\'\'] after:absolute after:inset-y-0 after:left-0 after:w-screen after:lg:w-[800%] after:bg-texture-white after:bg-fixed after:bg-center after:lg:bg-[25rem_-25rem] after:bg-no-repeat',
        ]">
            <div class="sticky top-0 z-10 flex-col justify-center hidden h-screen ml-16 lg:flex xl:ml-28 2xl:ml-36">
                <div class="leading-[1.25] text-[2.6rem] xl:text-5xl font-medium text-white">
                    Start your carrier<br />registration
                </div>
                <div class="mt-5 text-base leading-relaxed xl:text-lg text-white/70">
                    We rebuilt this first step to make individual carrier sign-up feel faster,
                    clearer and more trustworthy before the rest of onboarding begins.
                </div>
                <div class="mt-8 flex flex-col gap-3">
                    <div v-for="step in steps" :key="step.number" class="flex items-center gap-3">
                        <div class="flex items-center justify-center w-8 h-8 rounded-full text-xs font-bold"
                            :class="step.number <= currentStep ? 'bg-white/20 text-white' : 'bg-white/10 text-white/50'">
                            {{ step.number }}
                        </div>
                        <div>
                            <div class="text-sm" :class="step.number <= currentStep ? 'text-white' : 'text-white/50'">
                                {{ step.title }}
                            </div>
                            <div class="text-xs text-white/50">{{ step.description }}</div>
                        </div>
                    </div>
                </div>

                <div class="mt-10 rounded-3xl border border-white/10 bg-white/10 p-5 backdrop-blur-sm">
                    <div class="flex items-start gap-3">
                        <div class="rounded-2xl bg-white/15 p-2">
                            <Lucide icon="BadgeCheck" class="h-5 w-5 text-white" />
                        </div>
                        <div>
                            <div class="font-medium text-white">Built for owner-operators and fleets</div>
                            <div class="mt-1 text-sm leading-6 text-white/70">
                                Whether you are onboarding a single carrier account or a larger
                                operation, this step keeps the start clean and gets you moving quickly.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
