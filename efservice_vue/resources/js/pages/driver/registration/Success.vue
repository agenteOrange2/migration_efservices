<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3'
import RegistrationLayout from '@/layouts/RegistrationLayout.vue'
import Lucide from '@/components/Base/Lucide'

declare function route(name: string, params?: any): string

defineOptions({ layout: RegistrationLayout })

const page = usePage()
const carrierName = (page.props.flash as any)?.carrier_name ?? 'the carrier'
</script>

<template>
    <Head title="Registration Successful" />

    <div class="box box--stacked p-8 text-center">
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-success/10">
            <Lucide icon="CheckCircle" class="h-8 w-8 text-success" />
        </div>

        <h2 class="mt-5 text-2xl font-bold text-slate-800">Account Created Successfully!</h2>

        <p class="mx-auto mt-3 max-w-md text-slate-500">
            Thank you for registering with
            <span class="font-semibold text-primary">{{ carrierName }}</span>.
            Your account is under review — you will receive an email once it is activated.
        </p>

        <p class="mt-3 text-sm text-slate-400">Please allow up to 48 hours for our team to review your details.</p>

        <div class="mt-8">
            <Link :href="route('login')" class="inline-flex items-center gap-2 rounded-lg bg-primary px-8 py-2.5 text-sm font-semibold text-white hover:bg-primary/90">
                <Lucide icon="LogIn" class="h-4 w-4" /> Go to Login
            </Link>
        </div>
    </div>
</template>
