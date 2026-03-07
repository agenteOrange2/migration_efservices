<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import AuthLayout from '@/layouts/AuthLayout.vue'
import { Users, Building2 } from 'lucide-vue-next'
import type { Carrier } from '@/types'

interface Props {
    carrier: Carrier
    driver_count: number
    max_drivers: number
}

defineProps<Props>()
</script>

<template>
    <Head title="Carrier at Capacity" />
    <AuthLayout>
        <div class="mx-auto w-full max-w-lg px-4 py-12">
            <div class="rounded-xl border border-gray-200 bg-white p-8 text-center shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <div class="mx-auto flex size-16 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30">
                    <Users class="size-8 text-red-600 dark:text-red-400" />
                </div>

                <h1 class="mt-5 text-2xl font-bold text-gray-900 dark:text-white">Carrier at Capacity</h1>
                <p class="mt-3 text-gray-500 dark:text-gray-400">
                    <span class="font-semibold text-gray-900 dark:text-white">{{ carrier.name }}</span>
                    has reached the maximum number of drivers allowed ({{ driver_count }}/{{ max_drivers }}).
                </p>

                <div class="mt-6 flex flex-col justify-center gap-3 sm:flex-row">
                    <Link
                        :href="route('driver.register.select')"
                        class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-primary/90"
                    >
                        <Building2 class="size-4" />
                        Select Another Carrier
                    </Link>
                    <Link
                        :href="route('login')"
                        class="inline-flex items-center justify-center rounded-lg border border-gray-300 px-6 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800"
                    >
                        Return to Home
                    </Link>
                </div>
            </div>
        </div>
    </AuthLayout>
</template>
