<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3'
import AuthLayout from '@/layouts/AuthLayout.vue'
import { AlertTriangle } from 'lucide-vue-next'

const page = usePage()
const errorMessage = (page.props.flash as any)?.error ?? 'An error occurred during registration.'
</script>

<template>
    <Head title="Registration Error" />
    <AuthLayout>
        <div class="mx-auto w-full max-w-lg px-4 py-12">
            <div class="rounded-xl border border-gray-200 bg-white p-8 text-center shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <div class="mx-auto flex size-16 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30">
                    <AlertTriangle class="size-8 text-red-600 dark:text-red-400" />
                </div>

                <h1 class="mt-5 text-2xl font-bold text-gray-900 dark:text-white">Registration Error</h1>
                <p class="mt-3 text-gray-500 dark:text-gray-400">{{ errorMessage }}</p>

                <Link
                    :href="route('login')"
                    class="mt-6 inline-flex rounded-lg bg-primary px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-primary/90"
                >
                    Return to Home
                </Link>
            </div>
        </div>
    </AuthLayout>
</template>
