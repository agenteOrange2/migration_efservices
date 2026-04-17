<script setup lang="ts">
import { Head, useForm, Link } from '@inertiajs/vue3'
import RegistrationLayout from '@/layouts/RegistrationLayout.vue'
import Button from '@/components/Base/Button'
import FormInput from '@/components/Base/Form/FormInput.vue'
import FormLabel from '@/components/Base/Form/FormLabel.vue'
import FormHelp from '@/components/Base/Form/FormHelp.vue'
import Lucide from '@/components/Base/Lucide'
import { ref, computed } from 'vue'

declare function route(name: string, params?: any): string

defineOptions({ layout: RegistrationLayout })

// ─── Props ────────────────────────────────────────────────────────────────────

interface CarrierProp {
    id: number
    name: string
    slug: string
    status: number
    address?: string | null
    state?: string | null
    dot_number?: string | null
    mc_number?: string | null
    logo_url?: string | null
}

const props = defineProps<{
    carrier: CarrierProp
    isIndependent: boolean
    token: string | null
}>()

// ─── Wizard Steps ─────────────────────────────────────────────────────────────

const STEPS = [
    { id: 1, label: 'Personal Info',    icon: 'User'       },
    { id: 2, label: 'Contact & Access', icon: 'KeyRound'   },
    { id: 3, label: 'Review',           icon: 'ClipboardCheck' },
] as const

const currentStep = ref<1 | 2 | 3>(1)

// ─── Form ─────────────────────────────────────────────────────────────────────

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

// ─── Password Strength ────────────────────────────────────────────────────────

const showPassword = ref(false)
const showConfirmPassword = ref(false)

const passwordStrength = computed(() => {
    const p = form.password
    if (!p) return { score: 0, label: '', color: 'bg-slate-200' }
    let score = 0
    if (p.length >= 8)                          score++
    if (/[a-z]/.test(p) && /[A-Z]/.test(p))    score++
    if (/\d/.test(p))                            score++
    if (/[^a-zA-Z0-9]/.test(p))                score++
    if (p.length >= 12)                          score++

    const levels = [
        { label: '',           color: 'bg-slate-200 dark:bg-darkmode-400' },
        { label: 'Weak',       color: 'bg-danger'   },
        { label: 'Fair',       color: 'bg-warning'  },
        { label: 'Good',       color: 'bg-pending'  },
        { label: 'Strong',     color: 'bg-success'  },
        { label: 'Very Strong', color: 'bg-success' },
    ]
    return { score, ...levels[score] }
})

// ─── Step Validation ──────────────────────────────────────────────────────────

const step1Errors = ref<Record<string, string>>({})
const step2Errors = ref<Record<string, string>>({})

function validateStep1(): boolean {
    const errs: Record<string, string> = {}
    if (!form.name.trim())      errs.name      = 'First name is required.'
    else if (!/^[a-zA-Z\s\-'.]+$/.test(form.name)) errs.name = 'Only letters, spaces, hyphens, apostrophes, and periods.'
    if (!form.last_name.trim()) errs.last_name  = 'Last name is required.'
    else if (!/^[a-zA-Z\s\-'.]+$/.test(form.last_name)) errs.last_name = 'Only letters, spaces, hyphens, apostrophes, and periods.'
    if (form.middle_name && !/^[a-zA-Z\s\-'.]+$/.test(form.middle_name)) errs.middle_name = 'Only letters, spaces, hyphens, apostrophes, and periods.'
    step1Errors.value = errs
    return Object.keys(errs).length === 0
}

function validateStep2(): boolean {
    const errs: Record<string, string> = {}
    if (!form.email.trim())                              errs.email        = 'Email is required.'
    else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email)) errs.email  = 'Please provide a valid email address.'
    if (!form.phone.trim())                              errs.phone        = 'Phone number is required.'
    else if (!/^\+?1?\d{10}$/.test(form.phone.replace(/\D/g, ''))) errs.phone = 'Please provide a valid 10-digit phone number.'
    if (!form.date_of_birth)                             errs.date_of_birth = 'Date of birth is required.'
    else {
        const dob = new Date(form.date_of_birth)
        const age = (Date.now() - dob.getTime()) / (365.25 * 24 * 3600 * 1000)
        if (age < 18) errs.date_of_birth = 'You must be at least 18 years old to register.'
    }
    if (!form.license_number.trim())                     errs.license_number = 'License number is required.'
    else if (!/^[A-Z0-9\-]+$/i.test(form.license_number)) errs.license_number = 'Only letters, numbers, and hyphens.'
    if (!form.password)                                  errs.password = 'Password is required.'
    else if (form.password.length < 8)                   errs.password = 'Password must be at least 8 characters.'
    else if (passwordStrength.value.score < 3)           errs.password = 'Please choose a stronger password.'
    if (!form.password_confirmation)                     errs.password_confirmation = 'Please confirm your password.'
    else if (form.password !== form.password_confirmation) errs.password_confirmation = 'Passwords do not match.'
    step2Errors.value = errs
    return Object.keys(errs).length === 0
}

function goNext() {
    if (currentStep.value === 1 && validateStep1()) currentStep.value = 2
    else if (currentStep.value === 2 && validateStep2()) currentStep.value = 3
}

function goBack() {
    if (currentStep.value > 1) currentStep.value = (currentStep.value - 1) as 1 | 2 | 3
}

// ─── Submit ───────────────────────────────────────────────────────────────────

function submit() {
    if (!form.terms_accepted) return

    if (props.isIndependent) {
        form.post(route('driver.register.independent'), { preserveScroll: true })
    } else {
        form.post(route('driver.register.submit', { carrier: props.carrier.slug }), { preserveScroll: true })
    }
}

// ─── Helpers ──────────────────────────────────────────────────────────────────

function formatPhone(val: string) {
    // Display formatting only — actual submission uses prepareForValidation server-side
    return val
}

const fullName = computed(() => {
    const parts = [form.name, form.middle_name, form.last_name].filter(Boolean)
    return parts.join(' ')
})

const modeLabel = computed(() => props.isIndependent ? 'Independent Registration' : 'Referred by Carrier')
</script>

<template>
    <Head :title="`Driver Registration — ${carrier.name}`" />

    <!-- ─── Carrier Header ──────────────────────────────────────────────────── -->
    <div class="box box--stacked mb-6 overflow-hidden">
        <div class="flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:p-6">
            <!-- Logo -->
            <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white p-2 shadow-sm dark:border-darkmode-400 dark:bg-darkmode-800">
                <img v-if="carrier.logo_url" :src="carrier.logo_url" :alt="carrier.name" class="h-full w-full object-contain" />
                <Lucide v-else icon="Truck" class="h-8 w-8 text-slate-400" />
            </div>
            <!-- Info -->
            <div class="flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <h1 class="text-xl font-bold text-slate-800 dark:text-slate-100">{{ carrier.name }}</h1>
                    <span
                        class="rounded-full px-2.5 py-0.5 text-xs font-medium"
                        :class="isIndependent ? 'bg-primary/10 text-primary' : 'bg-success/10 text-success'"
                    >
                        {{ modeLabel }}
                    </span>
                </div>
                <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">Driver Registration Application</p>
                <div class="mt-2 flex flex-wrap gap-2">
                    <span v-if="carrier.dot_number" class="inline-flex items-center gap-1 rounded-md bg-slate-100 px-2 py-1 text-xs font-medium text-slate-600 dark:bg-darkmode-400 dark:text-slate-300">
                        <Lucide icon="FileText" class="h-3 w-3" /> DOT: {{ carrier.dot_number }}
                    </span>
                    <span v-if="carrier.mc_number" class="inline-flex items-center gap-1 rounded-md bg-slate-100 px-2 py-1 text-xs font-medium text-slate-600 dark:bg-darkmode-400 dark:text-slate-300">
                        <Lucide icon="FileText" class="h-3 w-3" /> MC: {{ carrier.mc_number }}
                    </span>
                    <span v-if="carrier.state" class="inline-flex items-center gap-1 rounded-md bg-slate-100 px-2 py-1 text-xs font-medium text-slate-600 dark:bg-darkmode-400 dark:text-slate-300">
                        <Lucide icon="MapPin" class="h-3 w-3" /> {{ carrier.state }}
                    </span>
                    <span v-if="!isIndependent && token" class="inline-flex items-center gap-1 rounded-md bg-success/10 px-2 py-1 text-xs font-medium text-success">
                        <Lucide icon="ShieldCheck" class="h-3 w-3" /> Verified Invite
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- ─── Step Indicator ──────────────────────────────────────────────────── -->
    <div class="box box--stacked mb-6 p-5">
        <div class="relative flex items-center justify-between">
            <!-- Connecting line -->
            <div class="absolute left-0 right-0 top-5 h-0.5 bg-slate-200 dark:bg-darkmode-400" aria-hidden="true">
                <div
                    class="h-full bg-primary transition-all duration-500"
                    :style="{ width: currentStep === 1 ? '0%' : currentStep === 2 ? '50%' : '100%' }"
                />
            </div>
            <!-- Steps -->
            <div
                v-for="step in STEPS"
                :key="step.id"
                class="relative z-10 flex flex-col items-center gap-2"
            >
                <div
                    class="flex h-10 w-10 items-center justify-center rounded-full border-2 transition-all duration-300"
                    :class="{
                        'border-primary bg-primary text-white shadow-md shadow-primary/30': currentStep === step.id,
                        'border-primary bg-primary/10 text-primary':                        currentStep > step.id,
                        'border-slate-300 bg-white text-slate-400 dark:border-darkmode-400 dark:bg-darkmode-600': currentStep < step.id,
                    }"
                >
                    <Lucide v-if="currentStep > step.id" icon="Check" class="h-4 w-4" />
                    <Lucide v-else :icon="step.icon as any" class="h-4 w-4" />
                </div>
                <span
                    class="hidden text-xs font-medium sm:block"
                    :class="currentStep >= step.id ? 'text-slate-700 dark:text-slate-200' : 'text-slate-400'"
                >
                    {{ step.label }}
                </span>
            </div>
        </div>
    </div>

    <!-- ─── Step Content ────────────────────────────────────────────────────── -->
    <div class="box box--stacked">

        <!-- ── STEP 1: Personal Information ──────────────────────────────── -->
        <Transition name="slide" mode="out-in">
            <div v-if="currentStep === 1" key="step1">
                <div class="border-b border-slate-200 px-6 py-5 dark:border-darkmode-400">
                    <div class="flex items-center gap-3">
                        <div class="rounded-xl bg-primary/10 p-2">
                            <Lucide icon="User" class="h-5 w-5 text-primary" />
                        </div>
                        <div>
                            <h2 class="text-base font-semibold text-slate-800 dark:text-slate-100">Personal Information</h2>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Enter your full legal name as it appears on your license</p>
                        </div>
                    </div>
                </div>

                <div class="p-6">
                    <div class="grid gap-5 sm:grid-cols-3">
                        <!-- First Name -->
                        <div>
                            <FormLabel for="name">
                                First Name <span class="text-danger">*</span>
                            </FormLabel>
                            <FormInput
                                id="name"
                                v-model="form.name"
                                type="text"
                                placeholder="John"
                                :class="(step1Errors.name || form.errors.name) && 'border-danger'"
                                autocomplete="given-name"
                            />
                            <FormHelp v-if="step1Errors.name || form.errors.name" class="text-danger">
                                {{ step1Errors.name || form.errors.name }}
                            </FormHelp>
                        </div>

                        <!-- Middle Name -->
                        <div>
                            <FormLabel for="middle_name">Middle Name</FormLabel>
                            <FormInput
                                id="middle_name"
                                v-model="form.middle_name"
                                type="text"
                                placeholder="Michael (optional)"
                                :class="step1Errors.middle_name && 'border-danger'"
                                autocomplete="additional-name"
                            />
                            <FormHelp v-if="step1Errors.middle_name" class="text-danger">{{ step1Errors.middle_name }}</FormHelp>
                        </div>

                        <!-- Last Name -->
                        <div>
                            <FormLabel for="last_name">
                                Last Name <span class="text-danger">*</span>
                            </FormLabel>
                            <FormInput
                                id="last_name"
                                v-model="form.last_name"
                                type="text"
                                placeholder="Doe"
                                :class="(step1Errors.last_name || form.errors.last_name) && 'border-danger'"
                                autocomplete="family-name"
                            />
                            <FormHelp v-if="step1Errors.last_name || form.errors.last_name" class="text-danger">
                                {{ step1Errors.last_name || form.errors.last_name }}
                            </FormHelp>
                        </div>
                    </div>

                    <!-- Info box -->
                    <div class="mt-6 flex gap-3 rounded-xl border border-primary/20 bg-primary/5 p-4">
                        <Lucide icon="Info" class="mt-0.5 h-4 w-4 shrink-0 text-primary" />
                        <p class="text-xs text-slate-600 dark:text-slate-400">
                            Please use your full legal name exactly as it appears on your driver's license or government-issued ID.
                            This information will be used to verify your identity.
                        </p>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- ── STEP 2: Contact & Security ─────────────────────────────────── -->
        <Transition name="slide" mode="out-in">
            <div v-if="currentStep === 2" key="step2">
                <div class="border-b border-slate-200 px-6 py-5 dark:border-darkmode-400">
                    <div class="flex items-center gap-3">
                        <div class="rounded-xl bg-primary/10 p-2">
                            <Lucide icon="KeyRound" class="h-5 w-5 text-primary" />
                        </div>
                        <div>
                            <h2 class="text-base font-semibold text-slate-800 dark:text-slate-100">Contact & Access</h2>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Your contact details and account credentials</p>
                        </div>
                    </div>
                </div>

                <div class="space-y-5 p-6">
                    <!-- Email -->
                    <div>
                        <FormLabel for="email">
                            Email Address <span class="text-danger">*</span>
                        </FormLabel>
                        <FormInput
                            id="email"
                            v-model="form.email"
                            type="email"
                            placeholder="john.doe@example.com"
                            :class="(step2Errors.email || form.errors.email) && 'border-danger'"
                            autocomplete="email"
                        />
                        <FormHelp v-if="step2Errors.email || form.errors.email" class="text-danger">
                            {{ step2Errors.email || form.errors.email }}
                        </FormHelp>
                        <FormHelp v-else>You'll receive a confirmation link at this address.</FormHelp>
                    </div>

                    <!-- Phone + DOB + License -->
                    <div class="grid gap-5 sm:grid-cols-3">
                        <div>
                            <FormLabel for="phone">
                                Phone Number <span class="text-danger">*</span>
                            </FormLabel>
                            <FormInput
                                id="phone"
                                v-model="form.phone"
                                type="tel"
                                placeholder="(555) 123-4567"
                                :class="(step2Errors.phone || form.errors.phone) && 'border-danger'"
                                autocomplete="tel"
                            />
                            <FormHelp v-if="step2Errors.phone || form.errors.phone" class="text-danger">
                                {{ step2Errors.phone || form.errors.phone }}
                            </FormHelp>
                        </div>

                        <div>
                            <FormLabel for="date_of_birth">
                                Date of Birth <span class="text-danger">*</span>
                            </FormLabel>
                            <FormInput
                                id="date_of_birth"
                                v-model="form.date_of_birth"
                                type="date"
                                :class="(step2Errors.date_of_birth || form.errors.date_of_birth) && 'border-danger'"
                            />
                            <FormHelp v-if="step2Errors.date_of_birth || form.errors.date_of_birth" class="text-danger">
                                {{ step2Errors.date_of_birth || form.errors.date_of_birth }}
                            </FormHelp>
                            <FormHelp v-else>Must be 18+ years old</FormHelp>
                        </div>

                        <div>
                            <FormLabel for="license_number">
                                License Number <span class="text-danger">*</span>
                            </FormLabel>
                            <FormInput
                                id="license_number"
                                v-model="form.license_number"
                                type="text"
                                placeholder="ABC123456"
                                class="uppercase"
                                :class="(step2Errors.license_number || form.errors.license_number) && 'border-danger'"
                                autocomplete="off"
                            />
                            <FormHelp v-if="step2Errors.license_number || form.errors.license_number" class="text-danger">
                                {{ step2Errors.license_number || form.errors.license_number }}
                            </FormHelp>
                        </div>
                    </div>

                    <!-- Password -->
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <FormLabel for="password">
                                Password <span class="text-danger">*</span>
                            </FormLabel>
                            <div class="relative">
                                <FormInput
                                    id="password"
                                    v-model="form.password"
                                    :type="showPassword ? 'text' : 'password'"
                                    placeholder="Min. 8 characters"
                                    class="pr-10"
                                    :class="(step2Errors.password || form.errors.password) && 'border-danger'"
                                    autocomplete="new-password"
                                />
                                <button
                                    type="button"
                                    @click="showPassword = !showPassword"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 transition hover:text-slate-600"
                                    tabindex="-1"
                                >
                                    <Lucide :icon="showPassword ? 'EyeOff' : 'Eye'" class="h-4 w-4" />
                                </button>
                            </div>
                            <!-- Strength meter -->
                            <div v-if="form.password" class="mt-2">
                                <div class="flex gap-1">
                                    <div
                                        v-for="i in 5"
                                        :key="i"
                                        class="h-1.5 flex-1 rounded-full transition-all duration-300"
                                        :class="i <= passwordStrength.score ? passwordStrength.color : 'bg-slate-200 dark:bg-darkmode-400'"
                                    />
                                </div>
                                <p
                                    class="mt-1 text-xs font-medium"
                                    :class="passwordStrength.score >= 3 ? 'text-success' : passwordStrength.score >= 2 ? 'text-warning' : 'text-danger'"
                                >
                                    {{ passwordStrength.label }}
                                </p>
                            </div>
                            <FormHelp v-if="step2Errors.password || form.errors.password" class="text-danger">
                                {{ step2Errors.password || form.errors.password }}
                            </FormHelp>
                            <FormHelp v-else>Use uppercase, lowercase, numbers and symbols.</FormHelp>
                        </div>

                        <div>
                            <FormLabel for="password_confirmation">
                                Confirm Password <span class="text-danger">*</span>
                            </FormLabel>
                            <div class="relative">
                                <FormInput
                                    id="password_confirmation"
                                    v-model="form.password_confirmation"
                                    :type="showConfirmPassword ? 'text' : 'password'"
                                    placeholder="Repeat password"
                                    class="pr-10"
                                    :class="(step2Errors.password_confirmation || form.errors.password_confirmation) && 'border-danger'"
                                    autocomplete="new-password"
                                />
                                <button
                                    type="button"
                                    @click="showConfirmPassword = !showConfirmPassword"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 transition hover:text-slate-600"
                                    tabindex="-1"
                                >
                                    <Lucide :icon="showConfirmPassword ? 'EyeOff' : 'Eye'" class="h-4 w-4" />
                                </button>
                            </div>
                            <!-- Match indicator -->
                            <div v-if="form.password_confirmation" class="mt-2 flex items-center gap-1.5">
                                <Lucide
                                    :icon="form.password === form.password_confirmation ? 'CheckCircle' : 'XCircle'"
                                    class="h-3.5 w-3.5"
                                    :class="form.password === form.password_confirmation ? 'text-success' : 'text-danger'"
                                />
                                <span class="text-xs" :class="form.password === form.password_confirmation ? 'text-success' : 'text-danger'">
                                    {{ form.password === form.password_confirmation ? 'Passwords match' : 'Passwords do not match' }}
                                </span>
                            </div>
                            <FormHelp v-if="step2Errors.password_confirmation || form.errors.password_confirmation" class="text-danger">
                                {{ step2Errors.password_confirmation || form.errors.password_confirmation }}
                            </FormHelp>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- ── STEP 3: Review & Submit ─────────────────────────────────────── -->
        <Transition name="slide" mode="out-in">
            <div v-if="currentStep === 3" key="step3">
                <div class="border-b border-slate-200 px-6 py-5 dark:border-darkmode-400">
                    <div class="flex items-center gap-3">
                        <div class="rounded-xl bg-primary/10 p-2">
                            <Lucide icon="ClipboardCheck" class="h-5 w-5 text-primary" />
                        </div>
                        <div>
                            <h2 class="text-base font-semibold text-slate-800 dark:text-slate-100">Review Your Application</h2>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Please confirm your information before submitting</p>
                        </div>
                    </div>
                </div>

                <div class="p-6">
                    <!-- Summary Grid -->
                    <div class="grid gap-4 sm:grid-cols-2">
                        <!-- Personal -->
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 dark:border-darkmode-400 dark:bg-darkmode-700">
                            <div class="mb-3 flex items-center gap-2">
                                <Lucide icon="User" class="h-4 w-4 text-primary" />
                                <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Personal</h3>
                            </div>
                            <dl class="space-y-2 text-sm">
                                <div class="flex justify-between gap-2">
                                    <dt class="text-slate-500">Full Name</dt>
                                    <dd class="font-medium text-slate-800 dark:text-slate-100">{{ fullName || '—' }}</dd>
                                </div>
                                <div class="flex justify-between gap-2">
                                    <dt class="text-slate-500">Date of Birth</dt>
                                    <dd class="font-medium text-slate-800 dark:text-slate-100">{{ form.date_of_birth || '—' }}</dd>
                                </div>
                                <div class="flex justify-between gap-2">
                                    <dt class="text-slate-500">License #</dt>
                                    <dd class="font-medium uppercase text-slate-800 dark:text-slate-100">{{ form.license_number || '—' }}</dd>
                                </div>
                            </dl>
                        </div>

                        <!-- Contact -->
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 dark:border-darkmode-400 dark:bg-darkmode-700">
                            <div class="mb-3 flex items-center gap-2">
                                <Lucide icon="Contact" class="h-4 w-4 text-primary" />
                                <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Contact</h3>
                            </div>
                            <dl class="space-y-2 text-sm">
                                <div class="flex justify-between gap-2">
                                    <dt class="text-slate-500">Email</dt>
                                    <dd class="truncate font-medium text-slate-800 dark:text-slate-100">{{ form.email || '—' }}</dd>
                                </div>
                                <div class="flex justify-between gap-2">
                                    <dt class="text-slate-500">Phone</dt>
                                    <dd class="font-medium text-slate-800 dark:text-slate-100">{{ form.phone || '—' }}</dd>
                                </div>
                            </dl>
                        </div>

                        <!-- Carrier -->
                        <div class="rounded-xl border border-primary/20 bg-primary/5 p-4 sm:col-span-2">
                            <div class="mb-3 flex items-center gap-2">
                                <Lucide icon="Truck" class="h-4 w-4 text-primary" />
                                <h3 class="text-xs font-semibold uppercase tracking-wider text-primary">Carrier</h3>
                            </div>
                            <dl class="grid grid-cols-2 gap-2 text-sm sm:grid-cols-3">
                                <div class="flex flex-col gap-0.5">
                                    <dt class="text-xs text-slate-500">Company</dt>
                                    <dd class="font-medium text-slate-800 dark:text-slate-100">{{ carrier.name }}</dd>
                                </div>
                                <div v-if="carrier.state" class="flex flex-col gap-0.5">
                                    <dt class="text-xs text-slate-500">State</dt>
                                    <dd class="font-medium text-slate-800 dark:text-slate-100">{{ carrier.state }}</dd>
                                </div>
                                <div class="flex flex-col gap-0.5">
                                    <dt class="text-xs text-slate-500">Registration Type</dt>
                                    <dd class="font-medium text-slate-800 dark:text-slate-100">{{ modeLabel }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    <!-- Security note -->
                    <div class="mt-4 flex gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4 dark:border-darkmode-400 dark:bg-darkmode-700">
                        <Lucide icon="Lock" class="mt-0.5 h-4 w-4 shrink-0 text-slate-400" />
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            Your password has been entered but is not shown for security. Make sure you save it in a safe place.
                            Once submitted, you'll receive an email to verify your account.
                        </p>
                    </div>

                    <!-- Terms -->
                    <div class="mt-5">
                        <label class="flex cursor-pointer items-start gap-3">
                            <input
                                v-model="form.terms_accepted"
                                type="checkbox"
                                class="mt-0.5 rounded border-slate-300 text-primary focus:ring-primary"
                            />
                            <span class="text-sm text-slate-600 dark:text-slate-400">
                                I confirm all the information above is accurate and I agree to the
                                <a href="#" class="font-medium text-primary hover:underline">Terms of Service</a>
                                and
                                <a href="#" class="font-medium text-primary hover:underline">Privacy Policy</a>.
                                <span class="text-danger">*</span>
                            </span>
                        </label>
                        <FormHelp v-if="form.errors.terms_accepted" class="mt-1 text-danger">{{ form.errors.terms_accepted }}</FormHelp>
                        <FormHelp v-else-if="!form.terms_accepted && form.wasSuccessful === false" class="mt-1 text-danger">
                            You must accept the terms and conditions to register.
                        </FormHelp>
                    </div>

                    <!-- Server error -->
                    <div v-if="form.errors.error" class="mt-4 rounded-xl border border-danger/30 bg-danger/10 p-4">
                        <div class="flex gap-3">
                            <Lucide icon="AlertCircle" class="h-5 w-5 shrink-0 text-danger" />
                            <p class="text-sm text-danger">{{ form.errors.error }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- ─── Navigation Footer ─────────────────────────────────────────── -->
        <div class="flex items-center justify-between border-t border-slate-200 px-6 py-5 dark:border-darkmode-400">
            <!-- Back / Sign In -->
            <div class="flex items-center gap-3">
                <Button
                    v-if="currentStep > 1"
                    variant="outline-secondary"
                    class="gap-2"
                    @click="goBack"
                >
                    <Lucide icon="ChevronLeft" class="h-4 w-4" />
                    Back
                </Button>
                <Link
                    v-else
                    :href="route('login')"
                    class="text-sm text-slate-500 transition hover:text-primary dark:text-slate-400"
                >
                    Already have an account?
                </Link>
            </div>

            <!-- Next / Submit -->
            <div>
                <!-- Steps 1 & 2 -->
                <Button
                    v-if="currentStep < 3"
                    variant="primary"
                    class="gap-2"
                    @click="goNext"
                >
                    Continue
                    <Lucide icon="ChevronRight" class="h-4 w-4" />
                </Button>

                <!-- Step 3: Submit -->
                <Button
                    v-else
                    variant="primary"
                    class="gap-2"
                    :disabled="form.processing || !form.terms_accepted"
                    @click="submit"
                >
                    <Lucide v-if="form.processing" icon="Loader" class="h-4 w-4 animate-spin" />
                    <Lucide v-else icon="Send" class="h-4 w-4" />
                    {{ form.processing ? 'Submitting…' : 'Submit Registration' }}
                </Button>
            </div>
        </div>
    </div>

    <!-- Already have an account — shown on step 1 only -->
    <p v-if="currentStep === 1" class="mt-4 text-center text-sm text-slate-500 dark:text-slate-400">
        Already have an account?
        <Link :href="route('login')" class="font-medium text-primary hover:underline">Sign In</Link>
    </p>
</template>

<style scoped>
.slide-enter-active,
.slide-leave-active {
    transition: opacity 0.2s ease, transform 0.2s ease;
}
.slide-enter-from {
    opacity: 0;
    transform: translateX(16px);
}
.slide-leave-to {
    opacity: 0;
    transform: translateX(-16px);
}
</style>
