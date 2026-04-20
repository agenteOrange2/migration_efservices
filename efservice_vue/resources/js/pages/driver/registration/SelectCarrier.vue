<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import RegistrationLayout from '@/layouts/RegistrationLayout.vue'
import Lucide from '@/components/Base/Lucide'
import Button from '@/components/Base/Button'
import type { Carrier } from '@/types'

interface Props {
    carriers: (Carrier & { driver_count: number; max_drivers: number; is_full: boolean; logo_url?: string | null })[]
    states: string[]
}

defineOptions({ layout: RegistrationLayout })

declare function route(name: string, params?: any): string

const props = defineProps<Props>()
const page = usePage()
const branding = computed(() => ((page.props as any).branding ?? {}) as Record<string, any>)

const searchQuery = ref('')
const selectedState = ref('')
const showAvailableOnly = ref(false)
const viewMode = ref<'cards' | 'table'>('cards')

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

function getCarrierLogo(carrier: Carrier & { logo_url?: string | null }): string | null {
    return carrier.logo_url ?? null
}
</script>

<template>
    <Head :title="`Select a Carrier — ${branding.appName ?? 'EF Services'} Driver Registration`" />

    <div class="box box--stacked mb-6 p-6">
        <div class="flex items-center gap-4">
            <div class="rounded-2xl bg-primary/10 p-3">
                <Lucide icon="Truck" class="h-7 w-7 text-primary" />
            </div>
            <div>
                <h1 class="text-2xl font-bold text-slate-800 dark:text-slate-100">Select a Carrier</h1>
                <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">Choose the company you want to work with as a driver</p>
            </div>
        </div>
    </div>

    <div class="box box--stacked mb-5 p-4">
        <div class="flex flex-col gap-3 xl:flex-row xl:items-center">
            <div class="relative flex-1">
                <Lucide icon="Search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                <input
                    v-model="searchQuery"
                    type="text"
                    class="w-full rounded-md border border-slate-200 py-2.5 pl-9 pr-3 text-sm shadow-sm transition focus:border-primary/40 focus:outline-none focus:ring-4 focus:ring-primary/20 dark:border-darkmode-400 dark:bg-darkmode-800 dark:text-slate-200"
                    placeholder="Search by name, DOT, MC..."
                />
            </div>

            <select
                v-model="selectedState"
                class="rounded-md border border-slate-200 px-3 py-2.5 text-sm shadow-sm transition focus:border-primary/40 focus:outline-none focus:ring-4 focus:ring-primary/20 dark:border-darkmode-400 dark:bg-darkmode-800 dark:text-slate-200"
            >
                <option value="">All States</option>
                <option v-for="state in states" :key="state" :value="state">{{ state }}</option>
            </select>

            <label class="flex cursor-pointer items-center gap-2 text-sm text-slate-600 dark:text-slate-400">
                <input v-model="showAvailableOnly" type="checkbox" class="rounded border-slate-300 text-primary focus:ring-primary" />
                Available only
            </label>

            <div class="flex items-center gap-2">
                <Button
                    :variant="viewMode === 'cards' ? 'primary' : 'outline-secondary'"
                    size="sm"
                    class="gap-1.5"
                    @click="viewMode = 'cards'"
                >
                    <Lucide icon="LayoutGrid" class="h-3.5 w-3.5" />
                    Cards
                </Button>
                <Button
                    :variant="viewMode === 'table' ? 'primary' : 'outline-secondary'"
                    size="sm"
                    class="gap-1.5"
                    @click="viewMode = 'table'"
                >
                    <Lucide icon="Rows3" class="h-3.5 w-3.5" />
                    Table
                </Button>
            </div>

            <Button
                v-if="searchQuery || selectedState || showAvailableOnly"
                variant="outline-secondary"
                size="sm"
                class="gap-1.5"
                @click="clearFilters"
            >
                <Lucide icon="X" class="h-3.5 w-3.5" />
                Clear
            </Button>
        </div>
    </div>

    <p class="mb-4 text-sm text-slate-500 dark:text-slate-400">
        {{ filteredCarriers.length }} carrier{{ filteredCarriers.length !== 1 ? 's' : '' }} found
    </p>

    <div v-if="filteredCarriers.length && viewMode === 'cards'" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <div
            v-for="carrier in filteredCarriers"
            :key="carrier.id"
            class="box box--stacked overflow-hidden transition hover:shadow-md"
            :class="carrier.is_full ? 'opacity-60' : ''"
        >
            <div class="p-5">
                <div class="flex items-start gap-3">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-slate-50 p-1.5 dark:border-darkmode-400 dark:bg-darkmode-700">
                        <img v-if="getCarrierLogo(carrier)" :src="getCarrierLogo(carrier)!" :alt="carrier.name" class="h-full w-full rounded-lg object-contain" />
                        <Lucide v-else icon="Building2" class="h-6 w-6 text-slate-400" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <h3 class="truncate font-semibold text-slate-800 dark:text-slate-100">{{ carrier.name }}</h3>
                        <div class="mt-1 flex items-center gap-1 text-xs text-slate-500 dark:text-slate-400">
                            <Lucide icon="MapPin" class="h-3 w-3" />
                            <span>{{ carrier.state ?? 'N/A' }}</span>
                        </div>
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-2 gap-2 text-xs">
                    <div v-if="carrier.dot_number" class="rounded-lg bg-slate-50 px-2.5 py-2 dark:bg-darkmode-700">
                        <span class="text-slate-500">DOT</span>
                        <p class="mt-0.5 font-semibold text-slate-700 dark:text-slate-200">{{ carrier.dot_number }}</p>
                    </div>
                    <div class="rounded-lg bg-slate-50 px-2.5 py-2 dark:bg-darkmode-700">
                        <span class="text-slate-500">Drivers</span>
                        <p class="mt-0.5 font-semibold" :class="carrier.is_full ? 'text-danger' : 'text-slate-700 dark:text-slate-200'">
                            {{ carrier.driver_count }} / {{ carrier.max_drivers }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="border-t border-slate-200 px-5 py-3 dark:border-darkmode-400">
                <Link
                    v-if="!carrier.is_full"
                    :href="route('driver.register.form', { carrier_slug: carrier.slug })"
                    class="block"
                >
                    <Button variant="primary" class="w-full gap-2">
                        <Lucide icon="Truck" class="h-4 w-4" />
                        Apply as Driver
                    </Button>
                </Link>
                <Button v-else variant="secondary" class="w-full cursor-not-allowed gap-2" disabled>
                    <Lucide icon="Users" class="h-4 w-4" />
                    Carrier is Full
                </Button>
            </div>
        </div>
    </div>

    <div v-else-if="filteredCarriers.length && viewMode === 'table'" class="box box--stacked overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-darkmode-400">
                <thead class="bg-slate-50 dark:bg-darkmode-700/60">
                    <tr>
                        <th class="px-5 py-3 text-left font-semibold text-slate-500 dark:text-slate-300">Carrier</th>
                        <th class="px-5 py-3 text-left font-semibold text-slate-500 dark:text-slate-300">State</th>
                        <th class="px-5 py-3 text-left font-semibold text-slate-500 dark:text-slate-300">DOT</th>
                        <th class="px-5 py-3 text-left font-semibold text-slate-500 dark:text-slate-300">Drivers</th>
                        <th class="px-5 py-3 text-right font-semibold text-slate-500 dark:text-slate-300">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-darkmode-400">
                    <tr
                        v-for="carrier in filteredCarriers"
                        :key="carrier.id"
                        class="transition hover:bg-slate-50/80 dark:hover:bg-darkmode-700/30"
                        :class="carrier.is_full ? 'opacity-60' : ''"
                    >
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-slate-200 bg-slate-50 p-1.5 dark:border-darkmode-400 dark:bg-darkmode-700">
                                    <img v-if="getCarrierLogo(carrier)" :src="getCarrierLogo(carrier)!" :alt="carrier.name" class="h-full w-full object-contain" />
                                    <Lucide v-else icon="Building2" class="h-5 w-5 text-slate-400" />
                                </div>
                                <div>
                                    <div class="font-semibold text-slate-800 dark:text-slate-100">{{ carrier.name }}</div>
                                    <div class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">{{ carrier.address || 'No address provided' }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-5 py-4 text-slate-600 dark:text-slate-300">{{ carrier.state || 'N/A' }}</td>
                        <td class="px-5 py-4 text-slate-600 dark:text-slate-300">{{ carrier.dot_number || 'N/A' }}</td>
                        <td class="px-5 py-4">
                            <span class="rounded-lg bg-slate-100 px-2.5 py-1 font-medium dark:bg-darkmode-700" :class="carrier.is_full ? 'text-danger' : 'text-slate-700 dark:text-slate-200'">
                                {{ carrier.driver_count }} / {{ carrier.max_drivers }}
                            </span>
                        </td>
                        <td class="px-5 py-4 text-right">
                            <Link
                                v-if="!carrier.is_full"
                                :href="route('driver.register.form', { carrier_slug: carrier.slug })"
                                class="inline-block"
                            >
                                <Button variant="primary" size="sm" class="gap-2">
                                    <Lucide icon="Truck" class="h-4 w-4" />
                                    Apply as Driver
                                </Button>
                            </Link>
                            <Button v-else variant="secondary" size="sm" class="cursor-not-allowed gap-2" disabled>
                                <Lucide icon="Users" class="h-4 w-4" />
                                Carrier is Full
                            </Button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div v-else class="box box--stacked py-14 text-center">
        <Lucide icon="Truck" class="mx-auto h-12 w-12 text-slate-300 dark:text-slate-600" />
        <p class="mt-3 text-slate-500 dark:text-slate-400">No carriers found matching your search</p>
        <button @click="clearFilters" class="mt-2 text-sm font-medium text-primary hover:underline">Clear filters</button>
    </div>

    <p class="mt-6 text-center text-sm text-slate-500 dark:text-slate-400">
        Already have an account?
        <Link :href="route('login')" class="font-medium text-primary hover:underline">Sign in</Link>
    </p>
</template>
