<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3'
import RegistrationLayout from '@/layouts/RegistrationLayout.vue'
import Lucide from '@/components/Base/Lucide'

declare function route(name: string, params?: any): string

defineOptions({ layout: RegistrationLayout })

const page = usePage()
const errorMessage = (page.props.flash as any)?.error ?? 'This carrier is currently unavailable for new driver registrations.'
</script>

<template>
    <Head title="Carrier Status Notice" />

    <div class="box box--stacked p-8 text-center">
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-warning/10">
            <Lucide icon="AlertTriangle" class="h-8 w-8 text-warning" />
        </div>
        <h2 class="mt-5 text-xl font-bold text-slate-800">Carrier Status Notice</h2>
        <p class="mx-auto mt-3 max-w-md text-slate-500">{{ errorMessage }}</p>
        <p class="mt-2 text-sm text-slate-400">Please contact the carrier administrator for more information.</p>
        <div class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
            <Link :href="route('home')" class="inline-flex items-center gap-2 rounded-lg bg-primary px-6 py-2.5 text-sm font-semibold text-white hover:bg-primary/90">
                <Lucide icon="Home" class="h-4 w-4" /> Return to Home
            </Link>
            <a href="mailto:support@efservices.com" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-6 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50">
                <Lucide icon="Mail" class="h-4 w-4" /> Contact Support
            </a>
        </div>
    </div>
</template>
