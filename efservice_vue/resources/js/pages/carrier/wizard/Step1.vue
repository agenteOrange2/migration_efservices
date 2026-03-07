<script setup lang="ts">
import { Head, useForm, Link } from '@inertiajs/vue3'
import Button from '@/components/Base/Button'
import { FormInput, FormLabel, FormSelect } from '@/components/Base/Form'
import Lucide from '@/components/Base/Lucide'
import { ref, computed } from 'vue'
import axios from 'axios'

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

const jobPositions = [
    { value: 'Owner', label: 'Owner' },
    { value: 'Manager', label: 'Manager' },
    { value: 'Dispatcher', label: 'Dispatcher' },
    { value: 'Safety Manager', label: 'Safety Manager' },
    { value: 'Operations Manager', label: 'Operations Manager' },
    { value: 'Other', label: 'Other' },
]

const showPassword = ref(false)
const emailChecking = ref(false)
const emailAvailable = ref<boolean | null>(null)

const currentStep = 1
const steps = [
    { number: 1, title: 'Basic Info' },
    { number: 2, title: 'Company' },
    { number: 3, title: 'Membership' },
    { number: 4, title: 'Banking' },
]

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

async function checkEmail() {
    if (!form.email || form.errors.email) return
    emailChecking.value = true
    try {
        const { data } = await axios.post(route('carrier.wizard.check.uniqueness'), {
            field: 'email', value: form.email,
        })
        emailAvailable.value = data.unique
    } catch {
        emailAvailable.value = null
    } finally {
        emailChecking.value = false
    }
}

function submit() {
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
                    <div class="text-2xl font-medium">Carrier Registration</div>
                    <div class="mt-2.5 text-slate-600">
                        Already have an account?
                        <Link :href="route('login')" class="font-medium text-primary">Sign in</Link>
                    </div>

                    <div v-if="form.errors.general" class="mt-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded-lg text-sm">
                        {{ form.errors.general }}
                    </div>

                    <form @submit.prevent="submit" class="mt-6 space-y-4">
                        <div>
                            <FormLabel>Full Name *</FormLabel>
                            <FormInput v-model="form.full_name" type="text" required class="block px-4 py-3.5 rounded-[0.6rem] border-slate-300/80" placeholder="John Doe" />
                            <p v-if="form.errors.full_name" class="mt-1 text-xs text-red-500">{{ form.errors.full_name }}</p>
                        </div>

                        <div>
                            <FormLabel>Email *</FormLabel>
                            <div class="relative">
                                <FormInput v-model="form.email" @blur="checkEmail" type="email" required class="block px-4 py-3.5 rounded-[0.6rem] border-slate-300/80" placeholder="john@company.com" />
                                <div class="absolute right-3 top-1/2 -translate-y-1/2">
                                    <Lucide v-if="emailChecking" icon="Loader" class="w-4 h-4 animate-spin text-slate-400" />
                                    <Lucide v-else-if="emailAvailable === true" icon="Check" class="w-4 h-4 text-green-500" />
                                    <Lucide v-else-if="emailAvailable === false" icon="X" class="w-4 h-4 text-red-500" />
                                </div>
                            </div>
                            <p v-if="emailAvailable === false" class="mt-1 text-xs text-red-500">This email is already registered</p>
                            <p v-if="form.errors.email" class="mt-1 text-xs text-red-500">{{ form.errors.email }}</p>
                        </div>

                        <div>
                            <FormLabel>Phone Number *</FormLabel>
                            <div class="flex gap-2">
                                <FormSelect v-model="form.country_code" class="w-[100px] px-3 py-3.5 rounded-[0.6rem] border-slate-300/80">
                                    <option value="US">+1 (US)</option>
                                    <option value="CA">+1 (CA)</option>
                                    <option value="MX">+52 (MX)</option>
                                </FormSelect>
                                <FormInput v-model="form.phone" type="tel" required class="block flex-1 px-4 py-3.5 rounded-[0.6rem] border-slate-300/80" placeholder="(___) ___-____" />
                            </div>
                            <p v-if="form.errors.phone" class="mt-1 text-xs text-red-500">{{ form.errors.phone }}</p>
                        </div>

                        <div>
                            <FormLabel>Job Position *</FormLabel>
                            <FormSelect v-model="form.job_position" required class="px-4 py-3.5 rounded-[0.6rem] border-slate-300/80">
                                <option value="">Select your position</option>
                                <option v-for="pos in jobPositions" :key="pos.value" :value="pos.value">{{ pos.label }}</option>
                            </FormSelect>
                            <p v-if="form.errors.job_position" class="mt-1 text-xs text-red-500">{{ form.errors.job_position }}</p>
                        </div>

                        <div>
                            <FormLabel>Password *</FormLabel>
                            <div class="relative">
                                <FormInput v-model="form.password" :type="showPassword ? 'text' : 'password'" required class="block px-4 py-3.5 rounded-[0.6rem] border-slate-300/80" placeholder="Min. 8 characters" />
                                <button type="button" @click="showPassword = !showPassword" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                                    <Lucide :icon="showPassword ? 'EyeOff' : 'Eye'" class="w-4 h-4" />
                                </button>
                            </div>
                            <div v-if="form.password" class="mt-2 flex gap-1">
                                <div v-for="i in 5" :key="i" class="h-1.5 flex-1 rounded-full transition-all" :class="i <= passwordStrength.score ? passwordStrength.color : 'bg-slate-200'" />
                            </div>
                            <p v-if="form.password && passwordStrength.label" class="mt-1 text-xs" :class="passwordStrength.score >= 3 ? 'text-green-600' : 'text-slate-500'">{{ passwordStrength.label }}</p>
                            <p v-if="form.errors.password" class="mt-1 text-xs text-red-500">{{ form.errors.password }}</p>
                        </div>

                        <div>
                            <FormLabel>Confirm Password *</FormLabel>
                            <FormInput v-model="form.password_confirmation" type="password" required class="block px-4 py-3.5 rounded-[0.6rem] border-slate-300/80" placeholder="Repeat password" />
                        </div>

                        <div class="space-y-2.5">
                            <label class="flex items-start gap-2.5 cursor-pointer">
                                <input
                                    v-model="form.terms_accepted"
                                    type="checkbox"
                                    :true-value="true"
                                    :false-value="false"
                                    class="mt-0.5 border shadow-sm border-slate-200 cursor-pointer rounded focus:ring-4 focus:ring-offset-0 focus:ring-primary/20 checked:bg-primary checked:border-primary/10 transition-all duration-100"
                                />
                                <span class="text-sm text-slate-600">
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
                                    class="mt-0.5 border shadow-sm border-slate-200 cursor-pointer rounded focus:ring-4 focus:ring-offset-0 focus:ring-primary/20 checked:bg-primary checked:border-primary/10 transition-all duration-100"
                                />
                                <span class="text-sm text-slate-600">I'd like to receive updates and promotions</span>
                            </label>
                        </div>

                        <div class="mt-5 xl:mt-8">
                            <Button type="submit" variant="primary" rounded class="bg-linear-to-r from-theme-1/70 to-theme-2/70 w-full py-3.5" :disabled="form.processing">
                                <Lucide v-if="form.processing" icon="Loader" class="w-5 h-5 animate-spin mr-2" />
                                {{ form.processing ? 'Creating Account...' : 'Continue to Company Information' }}
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
                <div class="leading-[1.4] text-[2.6rem] xl:text-5xl font-medium xl:leading-[1.2] text-white">
                    Carrier<br />Registration
                </div>
                <div class="mt-5 text-base leading-relaxed xl:text-lg text-white/70">
                    Join our fleet management platform. Register your carrier company in just a few steps.
                </div>
                <div class="mt-8 flex flex-col gap-3">
                    <div v-for="step in steps" :key="step.number" class="flex items-center gap-3">
                        <div class="flex items-center justify-center w-8 h-8 rounded-full text-xs font-bold"
                            :class="step.number <= currentStep ? 'bg-white/20 text-white' : 'bg-white/10 text-white/50'">
                            {{ step.number }}
                        </div>
                        <span class="text-sm" :class="step.number <= currentStep ? 'text-white' : 'text-white/50'">
                            {{ step.title }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
