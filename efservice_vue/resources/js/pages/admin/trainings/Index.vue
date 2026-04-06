<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { reactive } from 'vue'
import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import RazeLayout from '@/layouts/RazeLayout.vue'

declare function route(name: string, params?: any): string

defineOptions({ layout: RazeLayout })

interface PaginationLink {
    url: string | null
    label: string
    active: boolean
}

interface TrainingRow {
    id: number
    title: string
    description: string
    content_type: string
    status: string
    created_at: string | null
    assignments_count: number
    documents_count: number
}

const props = defineProps<{
    trainings: { data: TrainingRow[]; links: PaginationLink[]; total: number; last_page: number }
    filters: { search: string; status: string; content_type: string; sort: string; direction: string }
    stats: { total: number; active: number; inactive: number; assignments: number }
}>()

const filters = reactive({ ...props.filters })

function applyFilters() {
    router.get(route('admin.trainings.index'), {
        search: filters.search || undefined,
        status: filters.status || undefined,
        content_type: filters.content_type || undefined,
        sort: filters.sort || undefined,
        direction: filters.direction || undefined,
    }, { preserveState: true, replace: true })
}

function resetFilters() {
    filters.search = ''
    filters.status = ''
    filters.content_type = ''
    filters.sort = 'created_at'
    filters.direction = 'desc'
    applyFilters()
}

function deleteTraining(training: TrainingRow) {
    if (!confirm(`Delete "${training.title}"?`)) return
    router.delete(route('admin.trainings.destroy', training.id), { preserveScroll: true })
}

function contentTypeClass(type: string) {
    if (type === 'file') return 'bg-primary/10 text-primary'
    if (type === 'video') return 'bg-slate-100 text-slate-700'
    if (type === 'url') return 'bg-slate-100 text-slate-700'
    return 'bg-slate-100 text-slate-600'
}
</script>

<template>
    <Head title="Trainings" />

    <div class="grid grid-cols-12 gap-x-6 gap-y-10">
        <div class="col-span-12">
            <div class="box box--stacked p-6 mb-6">
                <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                            <Lucide icon="BookOpen" class="w-8 h-8 text-primary" />
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-slate-800">Trainings</h1>
                            <p class="text-slate-500">Manage the training materials assigned to drivers.</p>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <Link :href="route('admin.training-dashboard.index')">
                            <Button variant="outline-secondary" class="flex items-center gap-2">
                                <Lucide icon="BarChart3" class="w-4 h-4" />
                                Dashboard
                            </Button>
                        </Link>
                        <Link :href="route('admin.training-assignments.index')">
                            <Button variant="outline-secondary" class="flex items-center gap-2">
                                <Lucide icon="ClipboardList" class="w-4 h-4" />
                                Assignments
                            </Button>
                        </Link>
                        <Link :href="route('admin.trainings.create')">
                            <Button variant="primary" class="flex items-center gap-2">
                                <Lucide icon="Plus" class="w-4 h-4" />
                                Create Training
                            </Button>
                        </Link>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
                <div class="box box--stacked rounded-xl border border-dashed border-slate-300/80 p-5">
                    <p class="text-sm text-slate-500">Total Trainings</p>
                    <p class="mt-1 text-2xl font-semibold text-slate-800">{{ stats.total }}</p>
                </div>
                <div class="box box--stacked rounded-xl border border-dashed border-slate-300/80 p-5">
                    <p class="text-sm text-slate-500">Active</p>
                    <p class="mt-1 text-2xl font-semibold text-slate-800">{{ stats.active }}</p>
                </div>
                <div class="box box--stacked rounded-xl border border-dashed border-slate-300/80 p-5">
                    <p class="text-sm text-slate-500">Inactive</p>
                    <p class="mt-1 text-2xl font-semibold text-slate-800">{{ stats.inactive }}</p>
                </div>
                <div class="box box--stacked rounded-xl border border-dashed border-slate-300/80 p-5">
                    <p class="text-sm text-slate-500">Assignments</p>
                    <p class="mt-1 text-2xl font-semibold text-slate-800">{{ stats.assignments }}</p>
                </div>
            </div>

            <div class="box box--stacked p-5 mb-6">
                <div class="grid grid-cols-1 lg:grid-cols-5 gap-4">
                    <div class="lg:col-span-2 relative">
                        <Lucide icon="Search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                        <input v-model="filters.search" type="text" class="w-full rounded-lg border border-slate-200 px-3 py-2 pl-10 text-sm" placeholder="Search title or description..." />
                    </div>

                    <select v-model="filters.status" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                        <option value="">All Statuses</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>

                    <select v-model="filters.content_type" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                        <option value="">All Content Types</option>
                        <option value="file">File</option>
                        <option value="video">Video</option>
                        <option value="url">URL</option>
                    </select>

                    <div class="flex items-center gap-3">
                        <button type="button" @click="applyFilters" class="inline-flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition">
                            <Lucide icon="Filter" class="w-4 h-4" />
                            Apply
                        </button>
                        <button type="button" @click="resetFilters" class="inline-flex items-center gap-2 px-4 py-2 border border-slate-300 rounded-lg text-slate-600 hover:bg-slate-50 transition">
                            <Lucide icon="RotateCcw" class="w-4 h-4" />
                            Clear
                        </button>
                    </div>
                </div>
            </div>

            <div class="box box--stacked p-0 overflow-hidden">
                <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200/60">
                    <div>
                        <h2 class="text-base font-semibold text-slate-800">Training Records</h2>
                        <p class="text-sm text-slate-500">{{ trainings.total }} records</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-slate-50/80">
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Title</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Description</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Type</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Status</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Created</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Assignments</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="training in trainings.data" :key="training.id" class="border-b border-slate-100 hover:bg-slate-50/50 transition">
                                <td class="px-5 py-4">
                                    <div class="font-medium text-slate-800">{{ training.title }}</div>
                                    <div class="text-xs text-slate-400">{{ training.documents_count }} file<span v-if="training.documents_count !== 1">s</span></div>
                                </td>
                                <td class="px-5 py-4 text-sm text-slate-600 max-w-xs truncate">{{ training.description }}</td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium capitalize" :class="contentTypeClass(training.content_type)">
                                        {{ training.content_type }}
                                    </span>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium capitalize" :class="training.status === 'active' ? 'bg-primary/10 text-primary' : 'bg-slate-100 text-slate-600'">
                                        {{ training.status }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-sm text-slate-500">{{ training.created_at }}</td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">
                                        {{ training.assignments_count }} assigned
                                    </span>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center justify-center gap-2">
                                        <Link :href="route('admin.trainings.show', training.id)" class="p-1.5 text-slate-400 hover:text-primary transition" title="View">
                                            <Lucide icon="Eye" class="w-4 h-4" />
                                        </Link>
                                        <Link :href="route('admin.trainings.assign.form', training.id)" class="p-1.5 text-slate-400 hover:text-primary transition" title="Assign">
                                            <Lucide icon="UserPlus" class="w-4 h-4" />
                                        </Link>
                                        <Link :href="route('admin.trainings.edit', training.id)" class="p-1.5 text-slate-400 hover:text-primary transition" title="Edit">
                                            <Lucide icon="PenLine" class="w-4 h-4" />
                                        </Link>
                                        <button type="button" @click="deleteTraining(training)" class="p-1.5 text-slate-400 hover:text-red-500 transition" title="Delete">
                                            <Lucide icon="Trash2" class="w-4 h-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="!trainings.data.length">
                                <td colspan="7" class="px-5 py-12 text-center text-slate-400">
                                    <Lucide icon="BookOpen" class="w-12 h-12 mx-auto mb-3 text-slate-300" />
                                    <p>No trainings found</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="trainings.last_page > 1" class="p-4 border-t border-slate-200/60 flex items-center justify-between">
                    <span class="text-sm text-slate-500">{{ trainings.total }} total records</span>
                    <div class="flex gap-1">
                        <template v-for="link in trainings.links" :key="link.label">
                            <Link v-if="link.url" :href="link.url" class="px-3 py-1 text-sm rounded" :class="link.active ? 'bg-primary text-white' : 'text-slate-600 hover:bg-slate-100'" v-html="link.label" />
                            <span v-else class="px-3 py-1 text-sm text-slate-300" v-html="link.label" />
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
