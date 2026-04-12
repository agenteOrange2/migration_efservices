<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { computed, reactive } from 'vue'
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

interface TrainingCard {
    id: number
    status: string
    status_label: string
    assigned_date: string | null
    due_date: string | null
    completed_date: string | null
    is_overdue: boolean
    can_start: boolean
    can_complete: boolean
    training: {
        id: number | null
        title: string
        description: string | null
        content_type: string | null
        status: string | null
        documents_count: number
    }
}

const props = defineProps<{
    driver: {
        id: number
        full_name: string
        carrier_name: string | null
    }
    stats: {
        total: number
        completed: number
        in_progress: number
        pending: number
        overdue: number
        completion_percentage: number
    }
    filters: {
        search: string
        status: string
    }
    trainings: {
        data: TrainingCard[]
        links: PaginationLink[]
        total: number
        last_page: number
    }
}>()

const filters = reactive({
    search: props.filters.search,
    status: props.filters.status,
})

const statCards = computed(() => [
    { label: 'Total Trainings', value: props.stats.total, icon: 'GraduationCap', className: 'bg-primary/10 text-primary' },
    { label: 'Completed', value: props.stats.completed, icon: 'BadgeCheck', className: 'bg-success/10 text-success' },
    { label: 'In Progress', value: props.stats.in_progress, icon: 'PlayCircle', className: 'bg-info/10 text-info' },
    { label: 'Overdue', value: props.stats.overdue, icon: 'AlertTriangle', className: 'bg-danger/10 text-danger' },
])

function applyFilters() {
    router.get(route('driver.trainings.index'), {
        search: filters.search || undefined,
        status: filters.status || undefined,
    }, {
        preserveState: true,
        replace: true,
    })
}

function resetFilters() {
    filters.search = ''
    filters.status = ''
    applyFilters()
}

function startTraining(id: number) {
    router.post(route('driver.trainings.start-progress', id), {}, {
        preserveScroll: true,
    })
}

function statusClass(status: string) {
    if (status === 'completed') return 'bg-success/10 text-success'
    if (status === 'in_progress') return 'bg-info/10 text-info'
    if (status === 'overdue') return 'bg-danger/10 text-danger'
    return 'bg-warning/10 text-warning'
}

function contentTypeClass(type: string | null) {
    if (type === 'file') return 'bg-primary/10 text-primary'
    if (type === 'video') return 'bg-slate-100 text-slate-700'
    if (type === 'url') return 'bg-slate-100 text-slate-700'
    return 'bg-slate-100 text-slate-600'
}

function contentTypeLabel(type: string | null) {
    if (type === 'file') return 'Document'
    if (type === 'video') return 'Video'
    if (type === 'url') return 'External Link'
    return 'Content'
}
</script>

<template>
    <Head title="My Trainings" />

    <div class="grid grid-cols-12 gap-6">
        <div class="col-span-12">
            <div class="box box--stacked p-6">
                <div class="flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between">
                    <div class="flex items-start gap-4">
                        <div class="rounded-2xl border border-primary/20 bg-primary/10 p-3">
                            <Lucide icon="GraduationCap" class="h-8 w-8 text-primary" />
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-slate-800">My Trainings</h1>
                            <p class="mt-1 text-slate-500">
                                Review assigned training materials, track completion progress, and keep up with due dates.
                            </p>
                            <p class="mt-2 text-sm text-slate-500">
                                Driver: <span class="font-medium text-slate-700">{{ driver.full_name }}</span>
                                <span v-if="driver.carrier_name"> · Carrier: <span class="font-medium text-slate-700">{{ driver.carrier_name }}</span></span>
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                        <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div class="rounded-full bg-primary/10 p-2 text-primary">
                                    <Lucide icon="Target" class="h-4 w-4" />
                                </div>
                                <div>
                                    <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Completion</p>
                                    <p class="text-sm font-semibold text-slate-800">{{ stats.completion_percentage }}%</p>
                                </div>
                            </div>
                        </div>
                        <Link :href="`${route('driver.profile')}#trainings`">
                            <Button variant="outline-secondary" class="gap-2">
                                <Lucide icon="User" class="h-4 w-4" />
                                View Profile Tab
                            </Button>
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <div
            v-for="card in statCards"
            :key="card.label"
            class="col-span-12 sm:col-span-6 xl:col-span-3"
        >
            <div class="box box--stacked p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-slate-500">{{ card.label }}</p>
                        <p class="mt-2 text-3xl font-semibold text-slate-800">{{ card.value }}</p>
                    </div>
                    <div class="rounded-xl p-3" :class="card.className">
                        <Lucide :icon="card.icon" class="h-5 w-5" />
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12">
            <div class="box box--stacked p-5">
                <div class="grid grid-cols-1 gap-4 lg:grid-cols-[minmax(0,1fr),220px,auto]">
                    <div class="relative">
                        <Lucide icon="Search" class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                        <input
                            v-model="filters.search"
                            type="text"
                            class="w-full rounded-lg border border-slate-200 px-3 py-2 pl-10 text-sm"
                            placeholder="Search training title or description..."
                            @keyup.enter="applyFilters"
                        >
                    </div>

                    <select v-model="filters.status" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                        <option value="">All statuses</option>
                        <option value="pending">Pending</option>
                        <option value="in_progress">In progress</option>
                        <option value="completed">Completed</option>
                        <option value="overdue">Overdue</option>
                    </select>

                    <div class="flex items-center gap-3">
                        <Button variant="primary" class="gap-2" @click="applyFilters">
                            <Lucide icon="Filter" class="h-4 w-4" />
                            Apply
                        </Button>
                        <Button variant="outline-secondary" class="gap-2" @click="resetFilters">
                            <Lucide icon="RotateCcw" class="h-4 w-4" />
                            Clear
                        </Button>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12">
            <div v-if="trainings.data.length" class="grid grid-cols-12 gap-6">
                <div
                    v-for="assignment in trainings.data"
                    :key="assignment.id"
                    class="col-span-12 xl:col-span-6"
                >
                    <div class="box box--stacked h-full border p-6 transition hover:shadow-md" :class="assignment.is_overdue ? 'border-danger/25 bg-danger/5' : 'border-slate-200 bg-white'">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                            <div class="space-y-3">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h2 class="text-lg font-semibold text-slate-800">{{ assignment.training.title }}</h2>
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium" :class="statusClass(assignment.status)">
                                        {{ assignment.status_label }}
                                    </span>
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium" :class="contentTypeClass(assignment.training.content_type)">
                                        {{ contentTypeLabel(assignment.training.content_type) }}
                                    </span>
                                </div>
                                <p class="text-sm leading-6 text-slate-500">
                                    {{ assignment.training.description || 'No description provided for this training yet.' }}
                                </p>
                            </div>

                            <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-center sm:min-w-[140px]">
                                <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Files</p>
                                <p class="mt-2 text-2xl font-semibold text-slate-800">{{ assignment.training.documents_count }}</p>
                            </div>
                        </div>

                        <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-4">
                                <p class="text-xs font-medium uppercase tracking-[0.2em] text-slate-400">Assigned Date</p>
                                <p class="mt-2 text-sm font-semibold text-slate-800">{{ assignment.assigned_date || 'N/A' }}</p>
                            </div>
                            <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-4">
                                <p class="text-xs font-medium uppercase tracking-[0.2em] text-slate-400">Due Date</p>
                                <p class="mt-2 text-sm font-semibold text-slate-800">{{ assignment.due_date || 'No due date' }}</p>
                            </div>
                            <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-4">
                                <p class="text-xs font-medium uppercase tracking-[0.2em] text-slate-400">Completed</p>
                                <p class="mt-2 text-sm font-semibold text-slate-800">{{ assignment.completed_date || 'Not completed' }}</p>
                            </div>
                        </div>

                        <div class="mt-5 flex flex-wrap items-center gap-3 border-t border-slate-200 pt-5">
                            <Button
                                v-if="assignment.can_start"
                                variant="primary"
                                class="gap-2"
                                @click="startTraining(assignment.id)"
                            >
                                <Lucide icon="Play" class="h-4 w-4" />
                                Start Training
                            </Button>

                            <Link v-else :href="route('driver.trainings.show', assignment.id)">
                                <Button :variant="assignment.can_complete ? 'primary' : 'outline-secondary'" class="gap-2">
                                    <Lucide :icon="assignment.can_complete ? 'CheckCircle2' : 'Eye'" class="h-4 w-4" />
                                    {{ assignment.can_complete ? 'Finish Training' : 'View Details' }}
                                </Button>
                            </Link>

                            <Link :href="route('driver.trainings.show', assignment.id)" class="ml-auto">
                                <Button variant="outline-secondary" class="gap-2">
                                    <Lucide icon="ArrowRight" class="h-4 w-4" />
                                    Open
                                </Button>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <div v-else class="box box--stacked p-12 text-center">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-slate-100">
                    <Lucide icon="GraduationCap" class="h-8 w-8 text-slate-400" />
                </div>
                <h2 class="mt-5 text-lg font-semibold text-slate-800">No Trainings Found</h2>
                <p class="mx-auto mt-2 max-w-xl text-sm text-slate-500">
                    You do not have assigned trainings matching the current filters. Once your carrier assigns one, it will appear here.
                </p>
            </div>
        </div>

        <div v-if="trainings.last_page > 1" class="col-span-12">
            <div class="box box--stacked flex flex-col gap-4 p-4 sm:flex-row sm:items-center sm:justify-between">
                <span class="text-sm text-slate-500">{{ trainings.total }} total assignments</span>
                <div class="flex flex-wrap gap-1">
                    <template v-for="link in trainings.links" :key="link.label">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            class="rounded-lg px-3 py-1.5 text-sm transition"
                            :class="link.active ? 'bg-primary text-white' : 'text-slate-600 hover:bg-slate-100'"
                            v-html="link.label"
                        />
                        <span v-else class="px-3 py-1.5 text-sm text-slate-300" v-html="link.label" />
                    </template>
                </div>
            </div>
        </div>
    </div>
</template>
