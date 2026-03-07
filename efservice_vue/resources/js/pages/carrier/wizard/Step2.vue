<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'
import Button from '@/components/Base/Button'
import { FormInput, FormLabel, FormSelect } from '@/components/Base/Form'
import Lucide from '@/components/Base/Lucide'
import { ref } from 'vue'
import axios from 'axios'
import type { Membership } from '@/types'

interface Props {
    memberships: Membership[]
    states: Record<string, string>
}

const props = defineProps<Props>()

const form = useForm({
    carrier_name: '',
    address: '',
    state: '',
    zip_code: '',
    ein_number: '',
    dot_number: '',
    mc_number: '',
    state_dot: '',
    ifta_account: '',
    business_type: '',
    years_in_business: '',
    fleet_size: '',
})

const currentStep = 2
const steps = [
    { number: 1, title: 'Basic Info' },
    { number: 2, title: 'Company' },
    { number: 3, title: 'Membership' },
    { number: 4, title: 'Banking' },
]

const dotChecking = ref(false)
const dotAvailable = ref<boolean | null>(null)

async function checkDot() {
    if (!form.dot_number) return
    dotChecking.value = true
    try {
        const { data } = await axios.post(route('carrier.wizard.check.uniqueness'), { field: 'dot', value: form.dot_number })
        dotAvailable.value = data.unique
    } catch { dotAvailable.value = null } finally { dotChecking.value = false }
}

function submit() {
    form.post(route('carrier.wizard.step2.process'), { preserveScroll: true })
}
</script>

<template>
    <Head title="Carrier Registration - Step 2" />
    <div class="container grid lg:h-screen grid-cols-12 lg:max-w-[1550px] 2xl:max-w-[1750px] py-10 px-5 sm:py-14 sm:px-10 md:px-36 lg:py-0 lg:pl-14 lg:pr-12 xl:px-24">
        <div :class="[
            'relative z-50 h-full col-span-12 p-7 sm:p-14 bg-white rounded-2xl lg:bg-transparent lg:pr-10 lg:col-span-5 xl:pr-24 2xl:col-span-4 lg:p-0',
            'before:content-[\'\'] before:absolute before:inset-0 before:-mb-3.5 before:bg-white/40 before:rounded-2xl before:mx-5',
        ]">
            <div class="relative z-10 flex flex-col justify-center w-full h-full py-2 lg:py-16">
                <div class="rounded-[0.8rem] w-[55px] h-[55px] border border-primary/30 flex items-center justify-center">
                    <div class="relative flex items-center justify-center w-[50px] rounded-[0.6rem] h-[50px] bg-linear-to-b from-theme-1/90 to-theme-2/90 bg-white">
                        <Lucide icon="Building2" class="w-8 h-8 text-primary" />
                    </div>
                </div>

                <div class="mt-6 flex items-center gap-1.5">
                    <template v-for="step in steps" :key="step.number">
                        <div class="flex items-center gap-1.5">
                            <div class="flex items-center justify-center w-7 h-7 rounded-full text-xs font-bold" :class="step.number <= currentStep ? 'bg-linear-to-b from-theme-1 to-theme-2 text-white' : 'bg-slate-200 text-slate-400'">
                                <Lucide v-if="step.number < currentStep" icon="Check" class="w-3.5 h-3.5" />
                                <span v-else>{{ step.number }}</span>
                            </div>
                            <span class="text-xs font-medium hidden sm:inline" :class="step.number <= currentStep ? 'text-slate-700' : 'text-slate-400'">{{ step.title }}</span>
                        </div>
                        <div v-if="step.number < steps.length" class="w-5 h-px bg-slate-300" />
                    </template>
                </div>

                <div class="mt-6">
                    <div class="text-2xl font-medium">Company Information</div>
                    <div class="mt-2.5 text-slate-600">Tell us about your trucking company</div>

                    <div v-if="form.errors.general" class="mt-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded-lg text-sm">{{ form.errors.general }}</div>

                    <form @submit.prevent="submit" class="mt-6 space-y-4">
                        <div>
                            <FormLabel>Company Name *</FormLabel>
                            <FormInput v-model="form.carrier_name" type="text" required class="block px-4 py-3.5 rounded-[0.6rem] border-slate-300/80" placeholder="ABC Trucking LLC" />
                            <p v-if="form.errors.carrier_name" class="mt-1 text-xs text-red-500">{{ form.errors.carrier_name }}</p>
                        </div>
                        <div>
                            <FormLabel>Address *</FormLabel>
                            <FormInput v-model="form.address" type="text" required class="block px-4 py-3.5 rounded-[0.6rem] border-slate-300/80" placeholder="123 Main St" />
                            <p v-if="form.errors.address" class="mt-1 text-xs text-red-500">{{ form.errors.address }}</p>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <FormLabel>State *</FormLabel>
                                <FormSelect v-model="form.state" class="px-4 py-3.5 rounded-[0.6rem] border-slate-300/80">
                                    <option value="">Select State</option>
                                    <option v-for="(name, code) in states" :key="code" :value="code">{{ name }}</option>
                                </FormSelect>
                                <p v-if="form.errors.state" class="mt-1 text-xs text-red-500">{{ form.errors.state }}</p>
                            </div>
                            <div>
                                <FormLabel>ZIP Code *</FormLabel>
                                <FormInput v-model="form.zip_code" type="text" required class="block px-4 py-3.5 rounded-[0.6rem] border-slate-300/80" placeholder="12345" maxlength="10" />
                                <p v-if="form.errors.zip_code" class="mt-1 text-xs text-red-500">{{ form.errors.zip_code }}</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <FormLabel>EIN Number *</FormLabel>
                                <FormInput v-model="form.ein_number" type="text" required class="block px-4 py-3.5 rounded-[0.6rem] border-slate-300/80" placeholder="XX-XXXXXXX" />
                                <p v-if="form.errors.ein_number" class="mt-1 text-xs text-red-500">{{ form.errors.ein_number }}</p>
                            </div>
                            <div>
                                <FormLabel>DOT Number</FormLabel>
                                <div class="relative">
                                    <FormInput v-model="form.dot_number" @blur="checkDot" type="text" class="block px-4 py-3.5 rounded-[0.6rem] border-slate-300/80" placeholder="1234567" />
                                    <div class="absolute right-3 top-1/2 -translate-y-1/2">
                                        <Lucide v-if="dotChecking" icon="Loader" class="w-4 h-4 animate-spin text-slate-400" />
                                        <Lucide v-else-if="dotAvailable === true" icon="Check" class="w-4 h-4 text-green-500" />
                                    </div>
                                </div>
                                <p v-if="form.errors.dot_number" class="mt-1 text-xs text-red-500">{{ form.errors.dot_number }}</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <FormLabel>MC Number</FormLabel>
                                <FormInput v-model="form.mc_number" type="text" class="block px-4 py-3.5 rounded-[0.6rem] border-slate-300/80" placeholder="123456" />
                                <p v-if="form.errors.mc_number" class="mt-1 text-xs text-red-500">{{ form.errors.mc_number }}</p>
                            </div>
                            <div>
                                <FormLabel>State DOT</FormLabel>
                                <FormInput v-model="form.state_dot" type="text" class="block px-4 py-3.5 rounded-[0.6rem] border-slate-300/80" />
                                <p v-if="form.errors.state_dot" class="mt-1 text-xs text-red-500">{{ form.errors.state_dot }}</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-3 gap-3">
                            <div>
                                <FormLabel>Business Type *</FormLabel>
                                <FormSelect v-model="form.business_type" class="px-3 py-3.5 rounded-[0.6rem] border-slate-300/80">
                                    <option value="">Select</option>
                                    <option value="LLC">LLC</option>
                                    <option value="Corporation">Corporation</option>
                                    <option value="Sole Proprietorship">Sole Prop.</option>
                                    <option value="Partnership">Partnership</option>
                                </FormSelect>
                                <p v-if="form.errors.business_type" class="mt-1 text-xs text-red-500">{{ form.errors.business_type }}</p>
                            </div>
                            <div>
                                <FormLabel>Years *</FormLabel>
                                <FormSelect v-model="form.years_in_business" class="px-3 py-3.5 rounded-[0.6rem] border-slate-300/80">
                                    <option value="">Select</option>
                                    <option value="0-1">&lt; 1 year</option>
                                    <option value="1-3">1-3 years</option>
                                    <option value="3-5">3-5 years</option>
                                    <option value="5-10">5-10 years</option>
                                    <option value="10+">10+ years</option>
                                </FormSelect>
                                <p v-if="form.errors.years_in_business" class="mt-1 text-xs text-red-500">{{ form.errors.years_in_business }}</p>
                            </div>
                            <div>
                                <FormLabel>Fleet Size *</FormLabel>
                                <FormSelect v-model="form.fleet_size" class="px-3 py-3.5 rounded-[0.6rem] border-slate-300/80">
                                    <option value="">Select</option>
                                    <option value="1-5">1-5</option>
                                    <option value="6-10">6-10</option>
                                    <option value="11-25">11-25</option>
                                    <option value="26-50">26-50</option>
                                    <option value="50+">50+</option>
                                </FormSelect>
                                <p v-if="form.errors.fleet_size" class="mt-1 text-xs text-red-500">{{ form.errors.fleet_size }}</p>
                            </div>
                        </div>

                        <div class="mt-5 xl:mt-8">
                            <Button type="submit" variant="primary" rounded class="bg-linear-to-r from-theme-1/70 to-theme-2/70 w-full py-3.5" :disabled="form.processing">
                                <Lucide v-if="form.processing" icon="Loader" class="w-5 h-5 animate-spin mr-2" />
                                {{ form.processing ? 'Saving...' : 'Continue to Membership' }}
                            </Button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- Background -->
    <div class="fixed container grid w-screen inset-0 h-screen grid-cols-12 lg:max-w-[1550px] 2xl:max-w-[1750px] pl-14 pr-12 xl:px-24">
        <div :class="['relative h-screen col-span-12 lg:col-span-5 2xl:col-span-4 z-20', 'after:bg-white after:hidden after:lg:block after:content-[\'\'] after:absolute after:right-0 after:inset-y-0 after:bg-linear-to-b after:from-white after:to-slate-100/80 after:w-[800%] after:rounded-[0_1.2rem_1.2rem_0/0_1.7rem_1.7rem_0]', 'before:content-[\'\'] before:hidden before:lg:block before:absolute before:right-0 before:inset-y-0 before:my-6 before:bg-linear-to-b before:from-white/10 before:to-slate-50/10 before:bg-white/50 before:w-[800%] before:-mr-4 before:rounded-[0_1.2rem_1.2rem_0/0_1.7rem_1.7rem_0]']" />
        <div :class="['h-full col-span-7 2xl:col-span-8 lg:relative', 'before:content-[\'\'] before:absolute before:lg:-ml-10 before:left-0 before:inset-y-0 before:bg-linear-to-b before:from-theme-1 before:to-theme-2 before:w-screen before:lg:w-[800%]', 'after:content-[\'\'] after:absolute after:inset-y-0 after:left-0 after:w-screen after:lg:w-[800%] after:bg-texture-white after:bg-fixed after:bg-center after:lg:bg-[25rem_-25rem] after:bg-no-repeat']">
            <div class="sticky top-0 z-10 flex-col justify-center hidden h-screen ml-16 lg:flex xl:ml-28 2xl:ml-36">
                <div class="leading-[1.4] text-[2.6rem] xl:text-5xl font-medium xl:leading-[1.2] text-white">Company<br />Details</div>
                <div class="mt-5 text-base leading-relaxed xl:text-lg text-white/70">Provide your company information to continue with the registration process.</div>
                <div class="mt-8 flex flex-col gap-3">
                    <div v-for="step in steps" :key="step.number" class="flex items-center gap-3">
                        <div class="flex items-center justify-center w-8 h-8 rounded-full text-xs font-bold" :class="step.number <= currentStep ? 'bg-white/20 text-white' : 'bg-white/10 text-white/50'">{{ step.number }}</div>
                        <span class="text-sm" :class="step.number <= currentStep ? 'text-white' : 'text-white/50'">{{ step.title }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
