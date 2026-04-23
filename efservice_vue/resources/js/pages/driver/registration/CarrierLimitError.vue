<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import RegistrationLayout from '@/layouts/RegistrationLayout.vue'
import Lucide from '@/components/Base/Lucide'

declare function route(name: string, params?: any): string

defineOptions({ layout: RegistrationLayout })

defineProps<{
    carrier: { id: number; name: string; slug: string; logo_url?: string | null; dot_number?: string | null; mc_number?: string | null }
    driver_count: number
    max_drivers: number
}>()
</script>

<template>
    <Head title="Carrier at Capacity" />

    <div class="box box--stacked mb-6 overflow-hidden">
        <div class="flex flex-col gap-6 p-6 sm:flex-row sm:items-start sm:p-8">
            <div class="flex h-28 w-28 shrink-0 items-center justify-center rounded-xl border-2 border-slate-200 bg-white p-3 shadow-sm">
                <img v-if="carrier.logo_url" :src="carrier.logo_url" :alt="carrier.name" class="h-full w-full object-contain" />
                <Lucide v-else icon="Truck" class="h-14 w-14 text-slate-300" />
            </div>
            <div class="flex-1">
                <h1 class="text-3xl font-bold text-slate-800">{{ carrier.name }}</h1>
                <p class="mt-1 text-base text-slate-500">Driver Registration Application</p>
                <div class="mt-4 flex flex-wrap gap-2">
                    <span v-if="carrier.dot_number" class="inline-flex items-center gap-2 rounded-md bg-slate-800 px-4 py-2 text-sm font-semibold text-white">
                        <Lucide icon="FileText" class="h-4 w-4" /> DOT: {{ carrier.dot_number }}
                    </span>
                    <span v-if="carrier.mc_number" class="inline-flex items-center gap-2 rounded-md bg-slate-800 px-4 py-2 text-sm font-semibold text-white">
                        <Lucide icon="FileText" class="h-4 w-4" /> MC: {{ carrier.mc_number }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="box box--stacked p-8 text-center">
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-warning/10">
            <Lucide icon="Users" class="h-8 w-8 text-warning" />
        </div>
        <h2 class="mt-5 text-xl font-bold text-slate-800">Carrier at Maximum Capacity</h2>
        <p class="mx-auto mt-3 max-w-md text-slate-500">
            <span class="font-semibold text-slate-700">{{ carrier.name }}</span>
            has reached its maximum number of drivers ({{ driver_count }}/{{ max_drivers }}).
            Please contact the carrier directly or select another carrier.
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
