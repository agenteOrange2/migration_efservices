<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { reactive } from 'vue'
import Lucide from '@/components/Base/Lucide'
import Litepicker from '@/components/Base/Litepicker/Litepicker.vue'
import TomSelect from '@/components/Base/TomSelect/TomSelect.vue'
import Button from '@/components/Base/Button'
import RazeLayout from '@/layouts/RazeLayout.vue'

declare function route(name: string, params?: any): string

defineOptions({ layout: RazeLayout })

const lpOptions = { singleMode: true, format: 'M/D/YYYY', autoApply: true }

const props = defineProps<{
    documents: {
        data: any[]
        links: { url: string | null; label: string; active: boolean }[]
        total: number
        last_page: number
    }
    drivers: { id: number; name: string }[]
    filters: { driver_id: string; start_date: string; end_date: string; file_type: string }
    accident: { id: number; nature_of_accident: string; accident_date_display: string } | null
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
    const baseRoute = props.accident
        ? route(props.routeNames?.documentsShow ?? 'admin.accidents.documents.show', props.accident.id)
        : route(props.routeNames?.documentsIndex ?? 'admin.accidents.documents.index')

    router.get(baseRoute, {
        driver_id: filters.driver_id || undefined,
        start_date: filters.start_date || undefined,
        end_date: filters.end_date || undefined,
        file_type: filters.file_type || undefined,
    }, { preserveState: true, replace: true })
}

function deleteDocument(document: any) {
    if (!confirm(`Delete "${document.original_name}"?`)) return

    const target = document.source === 'document'
        ? route(props.routeNames?.documentsDestroy ?? 'admin.accidents.documents.destroy', document.id)
        : route(props.routeNames?.mediaDestroy ?? 'admin.accidents.media.destroy', document.id)

    router.delete(target, { preserveScroll: true })
}
</script>

<template>
    <Head title="Accident Documents" />

    <div class="grid grid-cols-12 gap-x-6 gap-y-10">
        <div class="col-span-12">
            <div class="box box--stacked p-6 mb-6">
                <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                            <Lucide icon="FileText" class="w-8 h-8 text-primary" />
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-slate-800">Accident Documents</h1>
                            <p class="text-slate-500">
                                {{ accident ? `Documents for ${accident.nature_of_accident} (${accident.accident_date_display})` : 'View and manage all accident-related documents.' }}
                            </p>
                        </div>
                    </div>

                    <Link :href="route(props.routeNames?.index ?? 'admin.accidents.index')">
                        <Button variant="outline-secondary" class="flex items-center gap-2">
                            <Lucide icon="ArrowLeft" class="w-4 h-4" />
                            Back to Accidents
                        </Button>
                    </Link>
                </div>
            </div>

            <div class="box box--stacked p-5 mb-6">
                <div class="grid grid-cols-1 lg:grid-cols-4 gap-4">
                    <TomSelect v-model="filters.driver_id">
                        <option value="">All Drivers</option>
                        <option v-for="driver in drivers" :key="driver.id" :value="String(driver.id)">{{ driver.name }}</option>
                    </TomSelect>

                    <Litepicker v-model="filters.start_date" :options="lpOptions" />
                    <Litepicker v-model="filters.end_date" :options="lpOptions" />

                    <TomSelect v-model="filters.file_type">
                        <option value="">All Types</option>
                        <option value="image">Images</option>
                        <option value="pdf">PDFs</option>
                        <option value="document">Documents</option>
                    </TomSelect>
                </div>

                <div class="flex flex-wrap items-center gap-3 mt-4">
                    <button type="button" @click="applyFilters" class="inline-flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition">
                        <Lucide icon="Filter" class="w-4 h-4" />
                        Apply Filters
                    </button>
                </div>
            </div>

            <div class="box box--stacked p-0 overflow-hidden">
                <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200/60">
                    <div>
                        <h2 class="text-base font-semibold text-slate-800">Documents</h2>
                        <p class="text-sm text-slate-500">{{ documents.total }} files</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-slate-50/80">
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Created</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Carrier</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Driver</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Accident Date</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Nature</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Document</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="document in documents.data" :key="`${document.source}-${document.id}`" class="border-b border-slate-100 hover:bg-slate-50/50 transition">
                                <td class="px-5 py-4 text-sm text-slate-500">{{ document.created_at_display }}</td>
                                <td class="px-5 py-4 text-sm text-slate-600">{{ document.carrier_name }}</td>
                                <td class="px-5 py-4 text-sm text-slate-700">
                                    <Link v-if="document.driver_id" :href="route(props.routeNames?.driverShow ?? 'admin.drivers.show', document.driver_id)" class="hover:text-primary">
                                        {{ document.driver_name }}
                                    </Link>
                                    <span v-else>{{ document.driver_name }}</span>
                                </td>
                                <td class="px-5 py-4 text-sm text-slate-600">{{ document.accident_date_display ?? 'N/A' }}</td>
                                <td class="px-5 py-4 text-sm text-slate-600">{{ document.nature_of_accident ?? 'N/A' }}</td>
                                <td class="px-5 py-4">
                                    <a :href="document.preview_url" target="_blank" class="font-medium text-primary hover:text-primary/80">
                                        {{ document.original_name }}
                                    </a>
                                    <div class="text-xs text-slate-500 mt-1">
                                        {{ (document.size / 1024).toFixed(1) }} KB · {{ document.file_type.toUpperCase() }}
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center justify-center gap-2">
                                        <a :href="document.preview_url" target="_blank" class="p-1.5 text-slate-400 hover:text-primary transition" title="Preview">
                                            <Lucide icon="Eye" class="w-4 h-4" />
                                        </a>
                                        <Link v-if="document.accident_id" :href="route(props.routeNames?.edit ?? 'admin.accidents.edit', document.accident_id)" class="p-1.5 text-slate-400 hover:text-amber-500 transition" title="Edit accident">
                                            <Lucide icon="PenLine" class="w-4 h-4" />
                                        </Link>
                                        <button type="button" @click="deleteDocument(document)" class="p-1.5 text-slate-400 hover:text-red-500 transition" title="Delete">
                                            <Lucide icon="Trash2" class="w-4 h-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="!documents.data.length">
                                <td colspan="7" class="px-5 py-12 text-center text-slate-400">
                                    <Lucide icon="FileText" class="w-12 h-12 mx-auto mb-3 text-slate-300" />
                                    <p>No documents found</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="documents.last_page > 1" class="p-4 border-t border-slate-200/60 flex items-center justify-between">
                    <span class="text-sm text-slate-500">{{ documents.total }} documents</span>
                    <div class="flex gap-1">
                        <template v-for="link in documents.links" :key="link.label">
                            <Link v-if="link.url" :href="link.url" class="px-3 py-1 text-sm rounded" :class="link.active ? 'bg-primary text-white' : 'text-slate-600 hover:bg-slate-100'" v-html="link.label" />
                            <span v-else class="px-3 py-1 text-sm text-slate-300" v-html="link.label" />
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
