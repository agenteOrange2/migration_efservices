<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3'
import AuthLayout from '@/layouts/AuthLayout.vue'
import { AlertTriangle, Mail } from 'lucide-vue-next'

const page = usePage()
const errorMessage = (page.props.flash as any)?.error ?? 'This carrier is currently unavailable for new driver registrations.'
</script>

<template>
    <Head title="Carrier Status Notice" />
    <AuthLayout>
        <div class="mx-auto w-full max-w-lg px-4 py-12">
            <div class="rounded-xl border border-gray-200 bg-white p-8 text-center shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <div class="mx-auto flex size-16 items-center justify-center rounded-full bg-yellow-100 dark:bg-yellow-900/30">
                    <AlertTriangle class="size-8 text-yellow-600 dark:text-yellow-400" />
                </div>

                <h1 class="mt-5 text-2xl font-bold text-gray-900 dark:text-white">Carrier Status Notice</h1>
                <p class="mt-3 text-gray-500 dark:text-gray-400">{{ errorMessage }}</p>
                <p class="mt-2 text-sm text-gray-400 dark:text-gray-500">
                    Please contact the carrier administrator for more information about when registration will be available.
                </p>

                <div class="mt-6 flex flex-col justify-center gap-3 sm:flex-row">
                    <Link
                        :href="route('login')"
                        class="inline-flex items-center justify-center rounded-lg bg-primary px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-primary/90"
                    >
                        Return to Home
                    </Link>
                    <a
                        href="mailto:support@efservices.com"
                        class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-300 px-6 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800"
                    >
                        <Mail class="size-4" />
                        Contact Support
                    </a>
                </div>
            </div>
        </div>
    </AuthLayout>
</template>
