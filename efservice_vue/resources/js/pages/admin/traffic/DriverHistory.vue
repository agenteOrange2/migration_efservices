<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { reactive } from 'vue'
import Lucide from '@/components/Base/Lucide'
import Litepicker from '@/components/Base/Litepicker/Litepicker.vue'
import { FormInput } from '@/components/Base/Form'
import RazeLayout from '@/layouts/RazeLayout.vue'

declare function route(name: string, params?: any): string

defineOptions({ layout: RazeLayout })

const lpOptions = { singleMode: true, format: 'M/D/YYYY', autoApply: true }

interface PaginationLink { url: string | null; label: string; active: boolean }

const props = defineProps<{
    driver: { id: number; name: string; carrier_name: string | null }
    convictions: {
        data: { id: number; conviction_date_display: string | null; location: string | null; charge: string | null; penalty: string | null; document_count: number }[]
        links: PaginationLink[]
        total: number
        last_page: number
    }
    filters: { search_term: string; date_from: string; date_to: string; sort_field: string; sort_direction: string }
}>()

const filters = reactive({
    search_term: props.filters.search_term ?? '',
    date_from: props.filters.date_from ?? '',
    date_to: props.filters.date_to ?? '',
})

function applyFilters() {
    router.get(route('admin.traffic.driver-history', props.driver.id), {
        search_term: filters.search_term || undefined,
        date_from: filters.date_from || undefined,
        date_to: filters.date_to || undefined,
        sort_field: props.filters.sort_field || undefined,
        sort_direction: props.filters.sort_direction || undefined,
    }, { preserveState: true, replace: true })
}

function resetFilters() {
    filters.search_term = ''
    filters.date_from = ''
    filters.date_to = ''
    applyFilters()
}

function sortUrl(field: string) {
    const direction = props.filters.sort_field === field && props.filters.sort_direction === 'asc' ? 'desc' : 'asc'
    return route('admin.traffic.driver-history', {
        driver: props.driver.id,
        search_term: filters.search_term || undefined,
        date_from: filters.date_from || undefined,
        date_to: filters.date_to || undefined,
        sort_field: field,
        sort_direction: direction,
    })
}
</script>

<template>
    <Head title="Traffic History" />

    <div class="grid grid-cols-12 gap-x-6 gap-y-10">
        <div class="col-span-12">
            <div class="box box--stacked p-6 mb-6">
                <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                            <Lucide icon="History" class="w-8 h-8 text-primary" />
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-slate-800">Traffic History</h1>
                            <p class="text-slate-500">All traffic convictions for {{ driver.name }}</p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <Link :href="route('admin.drivers.show', driver.id)" class="inline-flex items-center gap-2 px-4 py-2 border border-slate-300 rounded-lg text-slate-600 hover:bg-slate-50 transition">
                            <Lucide icon="ArrowLeft" class="w-4 h-4" />
                            Back to Driver
                        </Link>
                        <Link :href="route('admin.traffic.create')" class="inline-flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition">
                            <Lucide icon="Plus" class="w-4 h-4" />
                            Add Conviction
                        </Link>
                    </div>
                </div>
            </div>

            <div class="box box--stacked p-5 mb-6">
                <div class="grid grid-cols-1 lg:grid-cols-4 gap-4">
                    <div class="lg:col-span-2 relative">
                        <Lucide icon="Search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                        <FormInput v-model="filters.search_term" type="text" class="pl-10" placeholder="Search charge, location, penalty..." />
                    </div>
                    <Litepicker v-model="filters.date_from" :options="lpOptions" />
                    <Litepicker v-model="filters.date_to" :options="lpOptions" />
                </div>

                <div class="flex flex-wrap items-center gap-3 mt-4">
                    <button type="button" @click="applyFilters" class="inline-flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition">
                        <Lucide icon="Filter" class="w-4 h-4" />
                        Apply Filters
                    </button>
                    <button type="button" @click="resetFilters" class="inline-flex items-center gap-2 px-4 py-2 border border-slate-300 rounded-lg text-slate-600 hover:bg-slate-50 transition">
                        <Lucide icon="RotateCcw" class="w-4 h-4" />
                        Clear
                    </button>
                </div>
            </div>

            <div class="box box--stacked p-0 overflow-hidden">
                <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200/60">
                    <div>
                        <h2 class="text-base font-semibold text-slate-800">Convictions</h2>
                        <p class="text-sm text-slate-500">{{ convictions.total }} records</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-slate-50/80">
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">
                                    <Link :href="sortUrl('conviction_date')" class="inline-flex items-center gap-1 hover:text-slate-700">
                                        Date
                                        <Lucide v-if="props.filters.sort_field === 'conviction_date'" :icon="props.filters.sort_direction === 'asc' ? 'ChevronUp' : 'ChevronDown'" class="w-3 h-3" />
                                    </Link>
                                </th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Location</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">
                                    <Link :href="sortUrl('charge')" class="inline-flex items-center gap-1 hover:text-slate-700">
                                        Charge
                                        <Lucide v-if="props.filters.sort_field === 'charge'" :icon="props.filters.sort_direction === 'asc' ? 'ChevronUp' : 'ChevronDown'" class="w-3 h-3" />
                                    </Link>
                                </th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Penalty</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase text-center">Docs</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="conviction in convictions.data" :key="conviction.id" class="border-b border-slate-100 hover:bg-slate-50/50 transition">
                                <td class="px-5 py-4 text-sm text-slate-600">{{ conviction.conviction_date_display ?? 'N/A' }}</td>
                                <td class="px-5 py-4 text-sm text-slate-600">{{ conviction.location ?? 'N/A' }}</td>
                                <td class="px-5 py-4 text-sm font-medium text-slate-700">{{ conviction.charge ?? 'N/A' }}</td>
                                <td class="px-5 py-4 text-sm text-slate-600">{{ conviction.penalty ?? 'N/A' }}</td>
                                <td class="px-5 py-4 text-center text-sm font-medium text-slate-700">{{ conviction.document_count }}</td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center justify-center gap-2">
                                        <Link :href="route('admin.traffic.documents.show', conviction.id)" class="p-1.5 text-slate-400 hover:text-primary transition" title="Documents"><Lucide icon="Files" class="w-4 h-4" /></Link>
                                        <Link :href="route('admin.traffic.edit', conviction.id)" class="p-1.5 text-slate-400 hover:text-amber-500 transition" title="Edit"><Lucide icon="PenLine" class="w-4 h-4" /></Link>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="!convictions.data.length">
                                <td colspan="6" class="px-5 py-12 text-center text-slate-400">
                                    <Lucide icon="TrafficCone" class="w-12 h-12 mx-auto mb-3 text-slate-300" />
                                    <p>No traffic convictions found for this driver</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="convictions.last_page > 1" class="p-4 border-t border-slate-200/60 flex items-center justify-between">
                    <span class="text-sm text-slate-500">{{ convictions.total }} convictions</span>
                    <div class="flex gap-1">
                        <template v-for="link in convictions.links" :key="link.label">
                            <Link v-if="link.url" :href="link.url" class="px-3 py-1 text-sm rounded" :class="link.active ? 'bg-primary text-white' : 'text-slate-600 hover:bg-slate-100'" v-html="link.label" />
                            <span v-else class="px-3 py-1 text-sm text-slate-300" v-html="link.label" />
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
