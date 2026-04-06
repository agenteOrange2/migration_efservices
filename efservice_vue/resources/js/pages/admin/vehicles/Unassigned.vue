<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import { FormInput } from '@/components/Base/Form'
import RazeLayout from '@/layouts/RazeLayout.vue'

declare function route(name: string, params?: any): string

defineOptions({ layout: RazeLayout })

const props = defineProps<{
    vehicles: { data: any[]; links: { url: string | null; label: string; active: boolean }[]; total: number; last_page: number }
    filters: { search: string }
}>()

const search = ref(props.filters.search ?? '')

function applyFilters() {
    router.get(route('admin.vehicles.unassigned'), {
        search: search.value || undefined,
    }, { preserveState: true, replace: true })
}

function clearFilters() {
    search.value = ''
    applyFilters()
}
</script>

<template>
    <Head title="Unassigned Vehicles" />

    <div class="grid grid-cols-12 gap-x-6 gap-y-10">
        <div class="col-span-12">
            <div class="box box--stacked p-6 mb-6">
                <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-bold text-slate-800">Unassigned Vehicles</h1>
                        <p class="text-slate-500">Vehicles without a linked driver assignment.</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <Link :href="route('admin.vehicles.create')">
                            <Button variant="primary" class="flex items-center gap-2">
                                <Lucide icon="Plus" class="w-4 h-4" />
                                Add Vehicle
                            </Button>
                        </Link>
                        <Link :href="route('admin.vehicles.index')">
                            <Button variant="outline-secondary" class="flex items-center gap-2">
                                <Lucide icon="ArrowLeft" class="w-4 h-4" />
                                Back
                            </Button>
                        </Link>
                    </div>
                </div>
            </div>

            <div class="box box--stacked p-5 mb-6">
                <div class="grid grid-cols-1 lg:grid-cols-4 gap-4">
                    <div class="lg:col-span-3 relative">
                        <Lucide icon="Search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                        <FormInput v-model="search" type="text" class="pl-10" placeholder="Search unit, VIN, make, model..." />
                    </div>
                    <div class="flex gap-3">
                        <button type="button" @click="applyFilters" class="inline-flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition">
                            <Lucide icon="Filter" class="w-4 h-4" />
                            Apply
                        </button>
                        <button type="button" @click="clearFilters" class="inline-flex items-center gap-2 px-4 py-2 border border-slate-300 rounded-lg text-slate-600 hover:bg-slate-50 transition">
                            <Lucide icon="RotateCcw" class="w-4 h-4" />
                            Clear
                        </button>
                    </div>
                </div>
            </div>

            <div class="box box--stacked p-0 overflow-hidden">
                <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200/60">
                    <div>
                        <h2 class="text-base font-semibold text-slate-800">Vehicles Pending Assignment</h2>
                        <p class="text-sm text-slate-500">{{ vehicles.total }} records</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-slate-50/80">
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Added</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Vehicle</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Carrier</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Status</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="vehicle in vehicles.data" :key="vehicle.id" class="border-b border-slate-100 hover:bg-slate-50/50 transition">
                                <td class="px-5 py-4 text-sm text-slate-500">{{ vehicle.created_at }}</td>
                                <td class="px-5 py-4">
                                    <div class="font-medium text-slate-800">{{ vehicle.title }}</div>
                                    <div class="text-xs text-slate-400">
                                        {{ vehicle.company_unit_number ? `Unit ${vehicle.company_unit_number}` : 'No unit number' }} · VIN {{ vehicle.vin }}
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-sm text-slate-600">{{ vehicle.carrier_name ?? 'N/A' }}</td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium" :class="vehicle.status === 'active' ? 'bg-primary/10 text-primary' : 'bg-slate-100 text-slate-600'">
                                        {{ vehicle.status_label }}
                                    </span>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center justify-center gap-2">
                                        <Link :href="route('admin.vehicles.show', vehicle.id)" class="p-1.5 text-slate-400 hover:text-primary transition" title="View"><Lucide icon="Eye" class="w-4 h-4" /></Link>
                                        <Link :href="route('admin.vehicles.edit', vehicle.id)" class="p-1.5 text-slate-400 hover:text-primary transition" title="Assign / Edit"><Lucide icon="PenLine" class="w-4 h-4" /></Link>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="!vehicles.data.length">
                                <td colspan="5" class="px-5 py-12 text-center text-slate-400">
                                    <Lucide icon="Unlink" class="w-12 h-12 mx-auto mb-3 text-slate-300" />
                                    <p>No unassigned vehicles found</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="vehicles.last_page > 1" class="p-4 border-t border-slate-200/60 flex items-center justify-between">
                    <span class="text-sm text-slate-500">{{ vehicles.total }} total records</span>
                    <div class="flex gap-1">
                        <template v-for="link in vehicles.links" :key="link.label">
                            <Link v-if="link.url" :href="link.url" class="px-3 py-1 text-sm rounded" :class="link.active ? 'bg-primary text-white' : 'text-slate-600 hover:bg-slate-100'" v-html="link.label" />
                            <span v-else class="px-3 py-1 text-sm text-slate-300" v-html="link.label" />
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
