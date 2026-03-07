<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import Lucide from '@/components/Base/Lucide'
import Button from '@/components/Base/Button'
import type { Carrier, CarrierBankingDetail } from '@/types'

interface Props {
    carrier: Carrier
    bankingDetails: CarrierBankingDetail
}

const props = defineProps<Props>()

function logout() {
    router.post(route('logout'))
}
</script>

<template>
    <Head title="Banking Information Rejected" />
    <div class="container grid lg:h-screen grid-cols-12 lg:max-w-[1550px] 2xl:max-w-[1750px] py-10 px-5 sm:py-14 sm:px-10 md:px-36 lg:py-0 lg:pl-14 lg:pr-12 xl:px-24">
        <div :class="[
            'relative z-50 h-full col-span-12 p-7 sm:p-14 bg-white rounded-2xl lg:bg-transparent lg:pr-10 lg:col-span-5 xl:pr-24 2xl:col-span-4 lg:p-0',
            'before:content-[\'\'] before:absolute before:inset-0 before:-mb-3.5 before:bg-white/40 before:rounded-2xl before:mx-5',
        ]">
            <div class="relative z-10 flex flex-col justify-center w-full h-full py-2 lg:py-32">
                <div class="rounded-[0.8rem] w-[55px] h-[55px] border border-primary/30 flex items-center justify-center">
                    <div class="relative flex items-center justify-center w-[50px] rounded-[0.6rem] h-[50px] bg-linear-to-b from-theme-1/90 to-theme-2/90 bg-white">
                        <Lucide icon="AlertTriangle" class="w-8 h-8 text-white" />
                    </div>
                </div>

                <div class="mt-10">
                    <div class="text-2xl font-medium">Banking Information Rejected</div>
                    <div class="mt-2.5 text-slate-600">
                        Your banking information could not be verified. Please update it to continue.
                    </div>

                    <!-- Rejection Reason -->
                    <div v-if="bankingDetails.rejection_reason" class="mt-6 rounded-[0.6rem] bg-red-50 p-4">
                        <p class="text-sm font-medium text-red-800">Rejection Reason:</p>
                        <p class="mt-1 text-sm text-red-700">{{ bankingDetails.rejection_reason }}</p>
                    </div>

                    <!-- Actions -->
                    <div class="mt-6 flex flex-col gap-3">
                        <Link :href="route('carrier.wizard.step4')">
                            <Button variant="primary" rounded class="bg-linear-to-r from-theme-1/70 to-theme-2/70 w-full py-3.5">
                                <span class="flex items-center justify-center gap-2">
                                    Update Banking Information
                                    <Lucide icon="ArrowRight" class="w-4 h-4" />
                                </span>
                            </Button>
                        </Link>
                        <a href="mailto:support@efservices.com" class="flex items-center justify-center gap-2 rounded-[0.6rem] border border-slate-300/80 px-4 py-3 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                            <Lucide icon="Mail" class="w-4 h-4" />
                            Contact Support
                        </a>
                        <button @click="logout" class="flex items-center justify-center gap-2 px-4 py-3 text-sm text-slate-500 transition hover:text-slate-700">
                            <Lucide icon="LogOut" class="w-4 h-4" />
                            Sign Out
                        </button>
                    </div>
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
                    Action<br />Required
                </div>
                <div class="mt-5 text-base leading-relaxed xl:text-lg text-white/70">
                    Your banking information needs to be updated before we can proceed with your account activation.
                </div>
            </div>
        </div>
    </div>
</template>
