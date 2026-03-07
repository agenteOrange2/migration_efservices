<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3'
import AuthLayout from '@/layouts/AuthLayout.vue'
import { CheckCircle } from 'lucide-vue-next'

const page = usePage()
const carrierName = (page.props.flash as any)?.carrier_name ?? 'the carrier'
</script>

<template>
    <Head title="Registration Successful" />
    <AuthLayout>
        <div class="mx-auto w-full max-w-lg px-4 py-12">
            <div class="rounded-xl border border-gray-200 bg-white p-8 text-center shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <div class="mx-auto flex size-16 items-center justify-center rounded-full bg-green-100 dark:bg-green-900/30">
                    <CheckCircle class="size-8 text-green-600 dark:text-green-400" />
                </div>

                <h1 class="mt-5 text-2xl font-bold text-gray-900 dark:text-white">Your Driver Account Has Been Created!</h1>

                <p class="mt-3 text-gray-500 dark:text-gray-400">
                    Thank you for registering as a driver with
                    <span class="font-semibold text-primary">{{ carrierName }}</span>.
                    Your account is under review, and you will receive an email notification once it is activated.
                </p>

                <p class="mt-4 text-sm text-gray-400 dark:text-gray-500">
                    Please allow up to 48 hours for our team to review your details.
                </p>

                <Link
                    :href="route('login')"
                    class="mt-6 inline-flex rounded-lg bg-primary px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-primary/90"
                >
                    Go to Login
                </Link>
            </div>
        </div>
    </AuthLayout>
</template>
