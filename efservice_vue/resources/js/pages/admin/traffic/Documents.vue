<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import Lucide from '@/components/Base/Lucide'
import Button from '@/components/Base/Button'
import RazeLayout from '@/layouts/RazeLayout.vue'

declare function route(name: string, params?: any): string

defineOptions({ layout: RazeLayout })

interface DocumentRow {
    id: number
    file_name: string
    mime_type: string | null
    file_type: string
    size_label: string
    created_at_display: string | null
    preview_url: string
}

const props = defineProps<{
    conviction: {
        id: number
        driver_id: number
        driver_name: string
        carrier_name: string | null
        conviction_date_display: string | null
        location: string | null
        charge: string | null
        penalty: string | null
    }
    documents: DocumentRow[]
    routeNames?: {
        index: string
        create: string
        store: string
        edit: string
        update: string
        destroy: string
        driverHistory: string
        documentsShow: string
        mediaDestroy: string
        driverShow: string
    }
}>()

function deleteDocument(document: DocumentRow) {
    if (!confirm(`Delete "${document.file_name}"?`)) return
    router.delete(route(props.routeNames?.mediaDestroy ?? 'admin.traffic.media.destroy', document.id), { preserveScroll: true })
}
</script>

<template>
    <Head title="Traffic Documents" />

    <div class="grid grid-cols-12 gap-x-6 gap-y-10">
        <div class="col-span-12">
            <div class="box box--stacked p-6 mb-6">
                <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                            <Lucide icon="Files" class="w-8 h-8 text-primary" />
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-slate-800">Traffic Conviction Documents</h1>
                            <p class="text-slate-500">Documents for {{ conviction.driver_name }} · {{ conviction.charge }}</p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <Link :href="route(props.routeNames?.edit ?? 'admin.traffic.edit', conviction.id)">
                            <Button variant="outline-secondary" class="flex items-center gap-2">
                                <Lucide icon="PenLine" class="w-4 h-4" />
                                Edit Conviction
                            </Button>
                        </Link>
                        <Link :href="route(props.routeNames?.index ?? 'admin.traffic.index')">
                            <Button variant="outline-secondary" class="flex items-center gap-2">
                                <Lucide icon="ArrowLeft" class="w-4 h-4" />
                                Back to Traffic
                            </Button>
                        </Link>
                    </div>
                </div>
            </div>

            <div class="box box--stacked p-5 mb-6">
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 text-sm">
                    <div class="rounded-xl border border-slate-200 p-4">
                        <p class="text-slate-500">Driver</p>
                        <p class="mt-1 font-medium text-slate-800">{{ conviction.driver_name }}</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 p-4">
                        <p class="text-slate-500">Date</p>
                        <p class="mt-1 font-medium text-slate-800">{{ conviction.conviction_date_display ?? 'N/A' }}</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 p-4">
                        <p class="text-slate-500">Location</p>
                        <p class="mt-1 font-medium text-slate-800">{{ conviction.location ?? 'N/A' }}</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 p-4">
                        <p class="text-slate-500">Penalty</p>
                        <p class="mt-1 font-medium text-slate-800">{{ conviction.penalty ?? 'N/A' }}</p>
                    </div>
                </div>
            </div>

            <div class="box box--stacked p-0 overflow-hidden">
                <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200/60">
                    <div>
                        <h2 class="text-base font-semibold text-slate-800">Documents</h2>
                        <p class="text-sm text-slate-500">{{ documents.length }} files</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-slate-50/80">
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Uploaded</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Document</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="document in documents" :key="document.id" class="border-b border-slate-100 hover:bg-slate-50/50 transition">
                                <td class="px-5 py-4 text-sm text-slate-500">{{ document.created_at_display }}</td>
                                <td class="px-5 py-4">
                                    <a :href="document.preview_url" target="_blank" class="font-medium text-primary hover:text-primary/80">{{ document.file_name }}</a>
                                    <div class="text-xs text-slate-500 mt-1">{{ document.size_label }} · {{ document.file_type.toUpperCase() }}</div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center justify-center gap-2">
                                        <a :href="document.preview_url" target="_blank" class="p-1.5 text-slate-400 hover:text-primary transition" title="Preview"><Lucide icon="Eye" class="w-4 h-4" /></a>
                                        <button type="button" @click="deleteDocument(document)" class="p-1.5 text-slate-400 hover:text-danger transition" title="Delete"><Lucide icon="Trash2" class="w-4 h-4" /></button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="!documents.length">
                                <td colspan="3" class="px-5 py-12 text-center text-slate-400">
                                    <Lucide icon="FileText" class="w-12 h-12 mx-auto mb-3 text-slate-300" />
                                    <p>No documents found</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>
