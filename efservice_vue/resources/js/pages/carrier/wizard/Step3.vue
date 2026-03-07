<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'
import Button from '@/components/Base/Button'
import { FormCheck } from '@/components/Base/Form'
import Lucide from '@/components/Base/Lucide'
import type { Membership, Carrier } from '@/types'

interface Props {
    memberships: Membership[]
    carrier: Carrier
}

const props = defineProps<Props>()

const form = useForm({
    membership_id: props.carrier.id_plan ?? '',
    terms_accepted: false,
})

const currentStep = 3
const steps = [
    { number: 1, title: 'Basic Info' },
    { number: 2, title: 'Company' },
    { number: 3, title: 'Membership' },
    { number: 4, title: 'Banking' },
]

function selectPlan(id: number) {
    form.membership_id = id
}

function submit() {
    form.post(route('carrier.wizard.step3.process'), { preserveScroll: true })
}
</script>

<template>
    <Head title="Carrier Registration - Step 3" />
    <div class="container grid lg:h-screen grid-cols-12 lg:max-w-[1550px] 2xl:max-w-[1750px] py-10 px-5 sm:py-14 sm:px-10 md:px-36 lg:py-0 lg:pl-14 lg:pr-12 xl:px-24">
        <div :class="[
            'relative z-50 h-full col-span-12 p-7 sm:p-14 bg-white rounded-2xl lg:bg-transparent lg:pr-10 lg:col-span-5 xl:pr-24 2xl:col-span-4 lg:p-0',
            'before:content-[\'\'] before:absolute before:inset-0 before:-mb-3.5 before:bg-white/40 before:rounded-2xl before:mx-5',
        ]">
            <div class="relative z-10 flex flex-col justify-center w-full h-full py-2 lg:py-16">
                <div class="rounded-[0.8rem] w-[55px] h-[55px] border border-primary/30 flex items-center justify-center">
                    <div class="relative flex items-center justify-center w-[50px] rounded-[0.6rem] h-[50px] bg-linear-to-b from-theme-1/90 to-theme-2/90 bg-white">
                        <Lucide icon="CreditCard" class="w-8 h-8 text-primary" />
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
                    <div class="text-2xl font-medium">Choose Your Plan</div>
                    <div class="mt-2.5 text-slate-600">Select the membership that fits your needs</div>

                    <div v-if="form.errors.general" class="mt-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded-lg text-sm">{{ form.errors.general }}</div>

                    <form @submit.prevent="submit" class="mt-6">
                        <div class="space-y-3">
                            <button v-for="plan in memberships" :key="plan.id" type="button" @click="selectPlan(plan.id)"
                                class="w-full text-left rounded-[0.8rem] border-2 p-4 transition-all hover:shadow-sm"
                                :class="form.membership_id === plan.id ? 'border-primary bg-primary/5 ring-1 ring-primary/30' : 'border-slate-200 hover:border-slate-300'">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <div class="font-semibold text-slate-800">{{ plan.name }}</div>
                                        <div v-if="plan.description" class="text-xs text-slate-500 mt-0.5">{{ plan.description }}</div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-xl font-bold text-primary">${{ plan.price ?? 0 }}</div>
                                        <div class="text-xs text-slate-400">/ {{ plan.pricing_type ?? 'month' }}</div>
                                    </div>
                                </div>
                                <div class="mt-3 flex gap-4 text-xs text-slate-500">
                                    <span class="flex items-center gap-1"><Lucide icon="Users" class="w-3.5 h-3.5" /> {{ plan.max_drivers ?? '∞' }} drivers</span>
                                    <span class="flex items-center gap-1"><Lucide icon="Truck" class="w-3.5 h-3.5" /> {{ plan.max_vehicles ?? '∞' }} vehicles</span>
                                </div>
                                <div v-if="form.membership_id === plan.id" class="absolute top-3 right-3">
                                    <div class="w-5 h-5 rounded-full bg-primary flex items-center justify-center">
                                        <Lucide icon="Check" class="w-3 h-3 text-white" />
                                    </div>
                                </div>
                            </button>
                        </div>

                        <p v-if="form.errors.membership_id" class="mt-2 text-xs text-red-500">{{ form.errors.membership_id }}</p>

                        <div class="mt-5">
                            <label class="flex items-start gap-2.5 cursor-pointer">
                                <FormCheck.Input v-model="form.terms_accepted" type="checkbox" class="mt-0.5 border" />
                                <span class="text-sm text-slate-600">
                                    I agree to the <a href="#" class="text-primary font-medium">Terms of Service</a> and <a href="#" class="text-primary font-medium">Privacy Policy</a> *
                                </span>
                            </label>
                            <p v-if="form.errors.terms_accepted" class="mt-1 text-xs text-red-500">{{ form.errors.terms_accepted }}</p>
                        </div>

                        <div class="mt-5 xl:mt-8">
                            <Button type="submit" variant="primary" rounded class="bg-linear-to-r from-theme-1/70 to-theme-2/70 w-full py-3.5" :disabled="form.processing || !form.membership_id">
                                <Lucide v-if="form.processing" icon="Loader" class="w-5 h-5 animate-spin mr-2" />
                                {{ form.processing ? 'Saving...' : 'Continue to Banking' }}
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
                <div class="leading-[1.4] text-[2.6rem] xl:text-5xl font-medium xl:leading-[1.2] text-white">Membership<br />Selection</div>
                <div class="mt-5 text-base leading-relaxed xl:text-lg text-white/70">Choose the plan that best fits your fleet management needs.</div>
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
