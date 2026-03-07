<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3'
import Lucide from '@/components/Base/Lucide'
import Button from '@/components/Base/Button'
import { FormLabel } from '@/components/Base/Form'
import { ref } from 'vue'
import type { Carrier } from '@/types'

interface Props {
    carrier: Carrier
}

defineProps<Props>()

const showForm = ref(false)

const form = useForm({
    reason: '',
    additional_info: '',
})

function submitReactivation() {
    form.post(route('carrier.request.reactivation'), {
        preserveScroll: true,
        onSuccess: () => {
            showForm.value = false
        },
    })
}

function logout() {
    router.post(route('logout'))
}
</script>

<template>
    <Head title="Account Inactive" />
    <div class="container grid lg:h-screen grid-cols-12 lg:max-w-[1550px] 2xl:max-w-[1750px] py-10 px-5 sm:py-14 sm:px-10 md:px-36 lg:py-0 lg:pl-14 lg:pr-12 xl:px-24">
        <div :class="[
            'relative z-50 h-full col-span-12 p-7 sm:p-14 bg-white rounded-2xl lg:bg-transparent lg:pr-10 lg:col-span-5 xl:pr-24 2xl:col-span-4 lg:p-0',
            'before:content-[\'\'] before:absolute before:inset-0 before:-mb-3.5 before:bg-white/40 before:rounded-2xl before:mx-5',
        ]">
            <div class="relative z-10 flex flex-col justify-center w-full h-full py-2 lg:py-32">
                <div class="rounded-[0.8rem] w-[55px] h-[55px] border border-primary/30 flex items-center justify-center">
                    <div class="relative flex items-center justify-center w-[50px] rounded-[0.6rem] h-[50px] bg-linear-to-b from-theme-1/90 to-theme-2/90 bg-white">
                        <Lucide icon="XCircle" class="w-8 h-8 text-white" />
                    </div>
                </div>

                <div class="mt-10">
                    <div class="text-2xl font-medium">Account Inactive</div>
                    <div class="mt-2.5 text-slate-600">
                        Your account has been deactivated. Please contact support or request reactivation below.
                    </div>

                    <template v-if="!showForm">
                        <div class="mt-6 flex flex-col gap-3">
                            <Button @click="showForm = true" variant="primary" rounded class="bg-linear-to-r from-theme-1/70 to-theme-2/70 w-full py-3.5">
                                Request Reactivation
                            </Button>
                            <a href="mailto:support@efservices.com" class="flex items-center justify-center gap-2 rounded-[0.6rem] border border-slate-300/80 px-4 py-3 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                                <Lucide icon="Mail" class="w-4 h-4" />
                                Contact Support
                            </a>
                            <button @click="logout" class="flex items-center justify-center gap-2 px-4 py-3 text-sm text-slate-500 transition hover:text-slate-700">
                                <Lucide icon="LogOut" class="w-4 h-4" />
                                Sign Out
                            </button>
                        </div>
                    </template>

                    <form v-else @submit.prevent="submitReactivation" class="mt-6 space-y-4">
                        <div>
                            <FormLabel>Reason for reactivation *</FormLabel>
                            <textarea v-model="form.reason" required rows="3" class="w-full rounded-[0.6rem] border border-slate-300/80 px-4 py-3 text-sm shadow-sm focus:ring-4 focus:ring-primary/20 focus:border-primary/40 transition" placeholder="Please explain why you'd like to reactivate your account..." />
                            <p v-if="form.errors.reason" class="mt-1 text-xs text-red-500">{{ form.errors.reason }}</p>
                        </div>
                        <div>
                            <FormLabel>Additional information</FormLabel>
                            <textarea v-model="form.additional_info" rows="2" class="w-full rounded-[0.6rem] border border-slate-300/80 px-4 py-3 text-sm shadow-sm focus:ring-4 focus:ring-primary/20 focus:border-primary/40 transition" />
                        </div>
                        <div class="flex gap-3">
                            <button type="button" @click="showForm = false" class="flex-1 rounded-[0.6rem] border border-slate-300/80 px-4 py-3 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                                Cancel
                            </button>
                            <Button type="submit" variant="primary" rounded :disabled="form.processing" class="flex-1 bg-linear-to-r from-theme-1/70 to-theme-2/70 py-3">
                                <Lucide v-if="form.processing" icon="Loader" class="w-4 h-4 animate-spin mr-2" />
                                Submit Request
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
                    Account<br />Inactive
                </div>
                <div class="mt-5 text-base leading-relaxed xl:text-lg text-white/70">
                    Your account has been deactivated. You can request reactivation or contact our support team.
                </div>
            </div>
        </div>
    </div>
</template>
