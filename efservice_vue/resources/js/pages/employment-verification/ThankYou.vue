<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import RegistrationLayout from '@/layouts/RegistrationLayout.vue'
import Lucide from '@/components/Base/Lucide'

declare function route(name: string, params?: any): string

defineOptions({ layout: RegistrationLayout })
</script>

<template>
    <Head title="Verification Submitted" />
    <div class="box box--stacked p-10 text-center">
        <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-success/10">
            <Lucide icon="CheckCircle" class="h-10 w-10 text-success" />
        </div>
        <h1 class="mt-6 text-2xl font-bold text-slate-800">Verification Submitted Successfully</h1>
        <p class="mx-auto mt-3 max-w-md text-slate-500">
            Thank you for completing the employment verification. Your response has been recorded and the carrier has been notified.
        </p>
        <p class="mt-2 text-sm text-slate-400">You may now close this window.</p>
    </div>
</template>
