<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import RegistrationLayout from '@/layouts/RegistrationLayout.vue'
import Lucide from '@/components/Base/Lucide'

declare function route(name: string, params?: any): string

defineOptions({ layout: RegistrationLayout })
</script>

<template>
    <Head title="Maximum Drivers Reached" />

    <div class="box box--stacked p-8 text-center">
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-warning/10">
            <Lucide icon="Users" class="h-8 w-8 text-warning" />
        </div>
        <h2 class="mt-5 text-xl font-bold text-slate-800">Maximum Drivers Reached</h2>
        <p class="mx-auto mt-3 max-w-md text-slate-500">
            This carrier has reached their maximum number of allowed drivers.
            Please contact the carrier administrator for more information.
        </p>
        <div class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
            <Link :href="route('driver.register.select')" class="inline-flex items-center gap-2 rounded-lg bg-primary px-6 py-2.5 text-sm font-semibold text-white hover:bg-primary/90">
                <Lucide icon="Building2" class="h-4 w-4" /> Select Another Carrier
            </Link>
            <Link :href="route('home')" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-6 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50">
                <Lucide icon="Home" class="h-4 w-4" /> Return to Home
            </Link>
        </div>
    </div>
</template>
