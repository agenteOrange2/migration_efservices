<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import CarrierLayout from '@/layouts/CarrierLayout.vue'

declare function route(name: string, params?: any): string

defineOptions({ layout: CarrierLayout })

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
    routeNames: {
        index: string
        create: string
        show: string
        edit: string
        destroy: string
        assignSelect: string
        assignForm: string
        assign: string
    }
}>()

function namedRoute(name: keyof typeof props.routeNames, params?: any) {
    return route(props.routeNames[name], params)
}

function contentTypeClass(type: string) {
    if (type === 'file') return 'bg-primary/10 text-primary'
    if (type === 'video') return 'bg-slate-100 text-slate-700'
    if (type === 'url') return 'bg-slate-100 text-slate-700'
    return 'bg-slate-100 text-slate-600'
}
</script>

<template>
    <Head title="Assign Training" />

    <div class="grid grid-cols-12 gap-x-6 gap-y-10">
        <div class="col-span-12">
            <div class="box box--stacked p-6 mb-6">
                <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                            <Lucide icon="UserPlus" class="w-8 h-8 text-primary" />
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-slate-800">Assign Training to Drivers</h1>
                            <p class="text-slate-500">Select an active training and assign it to one or more of your drivers.</p>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <Link :href="namedRoute('create')">
                            <Button variant="outline-primary" class="flex items-center gap-2">
                                <Lucide icon="Plus" class="w-4 h-4" />
                                Create Training
                            </Button>
                        </Link>
                        <Link :href="namedRoute('index')">
                            <Button variant="outline-secondary" class="flex items-center gap-2">
                                <Lucide icon="ArrowLeft" class="w-4 h-4" />
                                Back to Trainings
                            </Button>
                        </Link>
                    </div>
                </div>
            </div>

            <div class="box box--stacked p-5">
                <div v-if="trainings.data.length" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
                    <div v-for="training in trainings.data" :key="training.id" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="flex items-center justify-between gap-3 mb-4">
                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium capitalize" :class="contentTypeClass(training.content_type)">
                                {{ training.content_type }}
                            </span>
                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium" :class="training.status === 'active' ? 'bg-primary/10 text-primary' : 'bg-slate-100 text-slate-600'">
                                {{ training.status }}
                            </span>
                        </div>

                        <h2 class="text-base font-semibold text-slate-800 leading-6">{{ training.title }}</h2>
                        <p class="mt-2 text-sm text-slate-500 line-clamp-3 min-h-[60px]">{{ training.description }}</p>

                        <div class="mt-4 flex items-center justify-between text-xs text-slate-500">
                            <span>{{ training.created_at ?? 'N/A' }}</span>
                            <span>{{ training.assignments_count }} assigned</span>
                        </div>

                        <div class="mt-5 flex items-center gap-3">
                            <Link :href="namedRoute('assignForm', training.id)" class="flex-1">
                                <Button variant="primary" class="w-full flex items-center justify-center gap-2">
                                    <Lucide icon="UserPlus" class="w-4 h-4" />
                                    Assign
                                </Button>
                            </Link>
                            <Link :href="namedRoute('show', training.id)">
                                <Button variant="outline-secondary" class="flex items-center gap-2">
                                    <Lucide icon="Eye" class="w-4 h-4" />
                                    View
                                </Button>
                            </Link>
                        </div>
                    </div>
                </div>

                <div v-else class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-6 py-16 text-center">
                    <Lucide icon="BookOpen" class="w-12 h-12 text-slate-300 mx-auto mb-4" />
                    <h2 class="text-lg font-semibold text-slate-700">No Active Trainings Available</h2>
                    <p class="mt-2 text-sm text-slate-500">Create a training or activate an existing one to start assigning it to drivers.</p>
                </div>

                <div v-if="trainings.last_page > 1" class="mt-6 flex items-center justify-between border-t border-slate-200 pt-4">
                    <span class="text-sm text-slate-500">{{ trainings.total }} trainings</span>
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
