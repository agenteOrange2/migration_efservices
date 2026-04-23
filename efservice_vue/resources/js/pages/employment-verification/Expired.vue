<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import RegistrationLayout from '@/layouts/RegistrationLayout.vue'
import Lucide from '@/components/Base/Lucide'

defineOptions({ layout: RegistrationLayout })
</script>

<template>
    <Head title="Link Expired" />
    <div class="box box--stacked p-10 text-center">
        <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-warning/10">
            <Lucide icon="Clock" class="h-10 w-10 text-warning" />
        </div>
        <h1 class="mt-6 text-2xl font-bold text-slate-800">Verification Link Expired</h1>
        <p class="mx-auto mt-3 max-w-md text-slate-500">
            This verification link has expired (valid for 30 days). Please contact the carrier or the applicant to request a new verification link.
        </p>
    </div>
</template>
