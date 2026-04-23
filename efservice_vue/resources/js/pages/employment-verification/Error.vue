<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import RegistrationLayout from '@/layouts/RegistrationLayout.vue'
import Lucide from '@/components/Base/Lucide'

defineOptions({ layout: RegistrationLayout })
</script>

<template>
    <Head title="Invalid Verification Link" />
    <div class="box box--stacked p-10 text-center">
        <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-danger/10">
            <Lucide icon="AlertTriangle" class="h-10 w-10 text-danger" />
        </div>
        <h1 class="mt-6 text-2xl font-bold text-slate-800">Invalid Verification Link</h1>
        <p class="mx-auto mt-3 max-w-md text-slate-500">
            This verification link is invalid or has already been used. If you believe this is an error, please contact the carrier directly.
        </p>
    </div>
</template>
