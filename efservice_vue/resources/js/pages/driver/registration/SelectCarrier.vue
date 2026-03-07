<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import AuthLayout from '@/layouts/AuthLayout.vue'
import { ref, computed } from 'vue'
import { Search, MapPin, Users, Truck, Building2, X } from 'lucide-vue-next'
import type { Carrier } from '@/types'

interface Props {
    carriers: (Carrier & { driver_count: number; max_drivers: number; is_full: boolean })[]
    states: string[]
}

const props = defineProps<Props>()

const searchQuery = ref('')
const selectedState = ref('')
const showAvailableOnly = ref(false)

const filteredCarriers = computed(() => {
    let result = props.carriers

    if (searchQuery.value) {
        const q = searchQuery.value.toLowerCase()
        result = result.filter(c =>
            c.name.toLowerCase().includes(q) ||
            c.dot_number?.toLowerCase().includes(q) ||
            c.mc_number?.toLowerCase().includes(q) ||
            c.address?.toLowerCase().includes(q),
        )
    }

    if (selectedState.value) {
        result = result.filter(c => c.state === selectedState.value)
    }

    if (showAvailableOnly.value) {
        result = result.filter(c => !c.is_full)
    }

    return result
})

function clearFilters() {
    searchQuery.value = ''
    selectedState.value = ''
    showAvailableOnly.value = false
}

function getCarrierLogo(carrier: Carrier): string | null {
    const media = (carrier as any).media ?? []
    const logo = media.find((m: any) => m.collection_name === 'logo_carrier')
    return logo?.original_url ?? null
}
</script>

<template>
    <Head title="Select a Carrier - Driver Registration" />
    <AuthLayout>
        <div class="mx-auto w-full max-w-5xl px-4 py-8">
            <div class="mb-6 text-center">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Select a Carrier</h1>
                <p class="mt-2 text-gray-500 dark:text-gray-400">Choose a carrier to register with as a driver</p>
            </div>

            <!-- Filters -->
            <div class="mb-6 flex flex-col gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900 sm:flex-row sm:items-center">
                <div class="relative flex-1">
                    <Search class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-gray-400" />
                    <input v-model="searchQuery" type="text" class="w-full rounded-lg border border-gray-300 py-2.5 pl-10 pr-3 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-white" placeholder="Search by name, DOT, MC..." />
                </div>
                <select v-model="selectedState" class="rounded-lg border border-gray-300 px-3 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                    <option value="">All States</option>
                    <option v-for="state in states" :key="state" :value="state">{{ state }}</option>
                </select>
                <label class="flex cursor-pointer items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                    <input v-model="showAvailableOnly" type="checkbox" class="rounded border-gray-300 text-primary focus:ring-primary" />
                    Available only
                </label>
                <button v-if="searchQuery || selectedState || showAvailableOnly" @click="clearFilters" class="flex items-center gap-1 rounded-lg px-3 py-2 text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400">
                    <X class="size-3.5" /> Clear
                </button>
            </div>

            <!-- Results Count -->
            <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">
                {{ filteredCarriers.length }} carrier{{ filteredCarriers.length !== 1 ? 's' : '' }} found
            </p>

            <!-- Carriers Grid -->
            <div v-if="filteredCarriers.length" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div
                    v-for="carrier in filteredCarriers"
                    :key="carrier.id"
                    class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm transition hover:shadow-md dark:border-gray-700 dark:bg-gray-900"
                    :class="carrier.is_full ? 'opacity-60' : ''"
                >
                    <div class="p-5">
                        <div class="flex items-start gap-3">
                            <div class="flex size-12 shrink-0 items-center justify-center rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-600 dark:bg-gray-800">
                                <img v-if="getCarrierLogo(carrier)" :src="getCarrierLogo(carrier)!" :alt="carrier.name" class="size-full rounded-lg object-contain" />
                                <Building2 v-else class="size-6 text-gray-400" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <h3 class="truncate font-semibold text-gray-900 dark:text-white">{{ carrier.name }}</h3>
                                <div class="mt-1 flex items-center gap-1 text-xs text-gray-500">
                                    <MapPin class="size-3" />
                                    <span>{{ carrier.state ?? 'N/A' }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 grid grid-cols-2 gap-3 text-xs">
                            <div v-if="carrier.dot_number" class="rounded-md bg-gray-50 px-2 py-1.5 dark:bg-gray-800">
                                <span class="text-gray-500">DOT</span>
                                <p class="font-medium text-gray-900 dark:text-white">{{ carrier.dot_number }}</p>
                            </div>
                            <div class="rounded-md bg-gray-50 px-2 py-1.5 dark:bg-gray-800">
                                <span class="text-gray-500">Drivers</span>
                                <p class="font-medium" :class="carrier.is_full ? 'text-red-600' : 'text-gray-900 dark:text-white'">
                                    {{ carrier.driver_count }} / {{ carrier.max_drivers }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="border-t border-gray-200 px-5 py-3 dark:border-gray-700">
                        <Link
                            v-if="!carrier.is_full"
                            :href="route('driver.register.form', { carrier_slug: carrier.slug })"
                            class="flex w-full items-center justify-center gap-1.5 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white transition hover:bg-primary/90"
                        >
                            <Truck class="size-4" />
                            Apply as Driver
                        </Link>
                        <span v-else class="flex items-center justify-center gap-1.5 rounded-lg bg-gray-100 px-4 py-2 text-sm font-medium text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                            <Users class="size-4" />
                            Carrier is Full
                        </span>
                    </div>
                </div>
            </div>

            <div v-else class="rounded-xl border border-gray-200 bg-white py-12 text-center dark:border-gray-700 dark:bg-gray-900">
                <Truck class="mx-auto size-12 text-gray-300 dark:text-gray-600" />
                <p class="mt-3 text-gray-500 dark:text-gray-400">No carriers found matching your search</p>
                <button @click="clearFilters" class="mt-2 text-sm font-medium text-primary hover:underline">Clear filters</button>
            </div>

            <p class="mt-6 text-center text-sm text-gray-500 dark:text-gray-400">
                Already have an account?
                <Link :href="route('login')" class="font-medium text-primary hover:underline">Sign in</Link>
            </p>
        </div>
    </AuthLayout>
</template>
