<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { reactive } from 'vue'
import Lucide from '@/components/Base/Lucide'
import Button from '@/components/Base/Button'
import { FormInput } from '@/components/Base/Form'
import RazeLayout from '@/layouts/RazeLayout.vue'

declare function route(name: string, params?: any): string

defineOptions({ layout: RazeLayout })

const props = defineProps<{
    driver: { id: number; name: string; carrier_name: string | null }
    accidents: {
        data: any[]
        links: { url: string | null; label: string; active: boolean }[]
        total: number
        last_page: number
    }
    filters: { search_term: string; sort_field: string; sort_direction: string }
    routeNames?: {
        index: string
        create: string
        store: string
        edit: string
        update: string
        destroy: string
        driverHistory: string
        documentsIndex: string
        documentsShow: string
        documentsDestroy: string
        mediaDestroy: string
        driverShow: string
    }
}>()

const filters = reactive({ ...props.filters })

function applyFilters() {
    router.get(route(props.routeNames?.driverHistory ?? 'admin.accidents.driver-history', props.driver.id), {
        search_term: filters.search_term || undefined,
        sort_field: props.filters.sort_field || undefined,
        sort_direction: props.filters.sort_direction || undefined,
    }, { preserveState: true, replace: true })
}

function sortUrl(field: string) {
    const direction = props.filters.sort_field === field && props.filters.sort_direction === 'asc' ? 'desc' : 'asc'

    return route(props.routeNames?.driverHistory ?? 'admin.accidents.driver-history', {
        driver: props.driver.id,
        search_term: filters.search_term || undefined,
        sort_field: field,
        sort_direction: direction,
    })
}
</script>

<template>
    <Head :title="`Accident History - ${driver.name}`" />

    <div class="grid grid-cols-12 gap-x-6 gap-y-10">
        <div class="col-span-12">
            <div class="box box--stacked p-6 mb-6">
                <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                            <Lucide icon="History" class="w-8 h-8 text-primary" />
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-slate-800">{{ driver.name }}</h1>
                            <p class="text-slate-500">Accident history{{ driver.carrier_name ? ` · ${driver.carrier_name}` : '' }}</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <Link :href="route(props.routeNames?.documentsIndex ?? 'admin.accidents.documents.index', { driver_id: driver.id })">
                            <Button variant="outline-primary" class="flex items-center gap-2">
                                <Lucide icon="FileText" class="w-4 h-4" />
                                View Documents
                            </Button>
                        </Link>
                        <Link :href="route(props.routeNames?.index ?? 'admin.accidents.index')">
                            <Button variant="outline-secondary" class="flex items-center gap-2">
                                <Lucide icon="ArrowLeft" class="w-4 h-4" />
                                Back
                            </Button>
                        </Link>
                    </div>
                </div>
            </div>

            <div class="box box--stacked p-5 mb-6">
                <div class="flex gap-3">
                    <FormInput v-model="filters.search_term" type="text" placeholder="Search accidents..." />
                    <Button type="button" variant="primary" class="flex items-center gap-2" @click="applyFilters">
                        <Lucide icon="Search" class="w-4 h-4" />
                        Search
                    </Button>
                </div>
            </div>

            <div class="box box--stacked p-0 overflow-hidden">
                <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200/60">
                    <div>
                        <h2 class="text-base font-semibold text-slate-800">History</h2>
                        <p class="text-sm text-slate-500">{{ accidents.total }} records</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-slate-50/80">
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">
                                    <Link :href="sortUrl('accident_date')" class="inline-flex items-center gap-1 hover:text-slate-700">
                                        Date
                                        <Lucide v-if="props.filters.sort_field === 'accident_date'" :icon="props.filters.sort_direction === 'asc' ? 'ChevronUp' : 'ChevronDown'" class="w-3 h-3" />
                                    </Link>
                                </th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Nature</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Injuries</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Fatalities</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Documents</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="accident in accidents.data" :key="accident.id" class="border-b border-slate-100 hover:bg-slate-50/50 transition">
                                <td class="px-5 py-4 text-sm text-slate-600">{{ accident.accident_date_display }}</td>
                                <td class="px-5 py-4 text-sm text-slate-700">{{ accident.nature_of_accident }}</td>
                                <td class="px-5 py-4 text-sm" :class="accident.had_injuries ? 'text-success' : 'text-danger'">{{ accident.had_injuries ? `Yes (${accident.number_of_injuries})` : 'No' }}</td>
                                <td class="px-5 py-4 text-sm" :class="accident.had_fatalities ? 'text-success' : 'text-danger'">{{ accident.had_fatalities ? `Yes (${accident.number_of_fatalities})` : 'No' }}</td>
                                <td class="px-5 py-4 text-sm text-slate-600">{{ accident.document_count }}</td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center justify-center gap-2">
                                        <Link :href="route(props.routeNames?.edit ?? 'admin.accidents.edit', accident.id)" class="p-1.5 text-slate-400 hover:text-warning transition">
                                            <Lucide icon="PenLine" class="w-4 h-4" />
                                        </Link>
                                        <Link :href="route(props.routeNames?.documentsShow ?? 'admin.accidents.documents.show', accident.id)" class="p-1.5 text-slate-400 hover:text-primary transition">
                                            <Lucide icon="FileText" class="w-4 h-4" />
                                        </Link>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="!accidents.data.length">
                                <td colspan="6" class="px-5 py-12 text-center text-slate-400">
                                    <Lucide icon="History" class="w-12 h-12 mx-auto mb-3 text-slate-300" />
                                    <p>No accident history found</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="accidents.last_page > 1" class="p-4 border-t border-slate-200/60 flex items-center justify-between">
                    <span class="text-sm text-slate-500">{{ accidents.total }} accidents</span>
                    <div class="flex gap-1">
                        <template v-for="link in accidents.links" :key="link.label">
                            <Link v-if="link.url" :href="link.url" class="px-3 py-1 text-sm rounded" :class="link.active ? 'bg-primary text-white' : 'text-slate-600 hover:bg-slate-100'" v-html="link.label" />
                            <span v-else class="px-3 py-1 text-sm text-slate-300" v-html="link.label" />
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
