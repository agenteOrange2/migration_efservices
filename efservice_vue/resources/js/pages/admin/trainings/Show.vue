<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import { Dialog } from '@/components/Base/Headless'
import RazeLayout from '@/layouts/RazeLayout.vue'
import AssignForm from '@/pages/admin/training-assignments/AssignForm.vue'

declare function route(name: string, params?: any): string

defineOptions({ layout: RazeLayout })

const props = withDefaults(defineProps<{
    training: {
        id: number
        title: string
        description: string
        content_type: string
        status: string
        video_url: string | null
        url: string | null
        creator_name: string | null
        created_at: string | null
        updated_at: string | null
        documents: { id: number; file_name: string; mime_type?: string | null; size_label: string; preview_url: string; created_at_display: string | null }[]
    }
    assignmentStats: { total: number; completed: number; in_progress: number; pending: number; overdue: number }
    recentAssignments: { id: number; driver_name: string; driver_email?: string | null; carrier_name?: string | null; status: string; status_label: string; assigned_date: string | null; due_date: string | null }[]
    assignmentFormOptions: {
        carriers: { id: number; name: string }[]
        drivers: { id: number; carrier_id: number | null; carrier_name?: string | null; name: string; email?: string | null }[]
        selectedTraining: { id: number; title: string }
    }
    routeNames?: {
        index: string
        show: string
        edit: string
        mediaDestroy: string
        assignForm?: string
        assign?: string
        assignmentsIndex?: string
    }
    assignmentRouteNames?: {
        store: string
        index: string
    }
    carrier?: { id: number; name: string } | null
    isCarrierContext?: boolean
}>(), {
    routeNames: () => ({
        index: 'admin.trainings.index',
        show: 'admin.trainings.show',
        edit: 'admin.trainings.edit',
        mediaDestroy: 'admin.trainings.media.destroy',
        assignForm: 'admin.trainings.assign.form',
        assign: 'admin.trainings.assign',
        assignmentsIndex: 'admin.training-assignments.index',
    }),
    assignmentRouteNames: () => ({
        store: 'admin.trainings.assign',
        index: 'admin.training-assignments.index',
    }),
    carrier: null,
    isCarrierContext: false,
})

const assignModalOpen = ref(false)
const assignmentForm = useForm({
    training_id: String(props.assignmentFormOptions.selectedTraining.id),
    carrier_id: '',
    driver_ids: [] as string[],
    due_date: '',
    status: 'assigned',
    notes: '',
})

function deleteDocument(documentId: number) {
    if (!confirm('Delete this file?')) return
    router.delete(route(props.routeNames?.mediaDestroy ?? 'admin.trainings.media.destroy', documentId), { preserveScroll: true })
}

function submitAssignment() {
    assignmentForm.post(route(props.assignmentRouteNames?.store ?? props.routeNames?.assign ?? 'admin.trainings.assign', props.training.id), {
        preserveScroll: true,
        onSuccess: () => {
            assignModalOpen.value = false
            assignmentForm.reset('carrier_id', 'driver_ids', 'due_date', 'status', 'notes')
            assignmentForm.training_id = String(props.assignmentFormOptions.selectedTraining.id)
            assignmentForm.status = 'assigned'
        },
    })
}

function assignmentStatusClass(status: string) {
    if (status === 'completed') return 'bg-primary/10 text-primary'
    if (status === 'overdue') return 'bg-red-100 text-red-600'
    if (status === 'in_progress') return 'bg-slate-200 text-slate-700'
    return 'bg-slate-100 text-slate-600'
}
</script>

<template>
    <Head :title="training.title" />

    <div class="grid grid-cols-12 gap-y-8 gap-x-6">
        <div class="col-span-12">
            <div class="box box--stacked p-6">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-xl font-bold text-slate-800">{{ training.title }}</h1>
                        <div class="flex items-center gap-2 mt-2">
                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium capitalize" :class="training.status === 'active' ? 'bg-primary/10 text-primary' : 'bg-slate-100 text-slate-600'">
                                {{ training.status }}
                            </span>
                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium capitalize" :class="training.content_type === 'file' ? 'bg-primary/10 text-primary' : 'bg-slate-100 text-slate-600'">
                                {{ training.content_type }}
                            </span>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <Button variant="primary" class="flex items-center gap-2" @click="assignModalOpen = true">
                            <Lucide icon="UserPlus" class="w-4 h-4" />
                            Assign Drivers
                        </Button>
                        <Link v-if="props.routeNames?.assignForm" :href="route(props.routeNames.assignForm, training.id)">
                            <Button variant="outline-secondary" class="flex items-center gap-2">
                                <Lucide icon="ExternalLink" class="w-4 h-4" />
                                Open Assign Page
                            </Button>
                        </Link>
                        <Link v-if="props.routeNames?.assignmentsIndex" :href="route(props.routeNames.assignmentsIndex, { training_id: training.id })">
                            <Button variant="outline-secondary" class="flex items-center gap-2">
                                <Lucide icon="ClipboardList" class="w-4 h-4" />
                                View Assignments
                            </Button>
                        </Link>
                        <Link :href="route(props.routeNames?.edit ?? 'admin.trainings.edit', training.id)">
                            <Button variant="primary" class="flex items-center gap-2">
                                <Lucide icon="PenLine" class="w-4 h-4" />
                                Edit
                            </Button>
                        </Link>
                        <Link :href="route(props.routeNames?.index ?? 'admin.trainings.index')">
                            <Button variant="outline-secondary" class="flex items-center gap-2">
                                <Lucide icon="ArrowLeft" class="w-4 h-4" />
                                Back
                            </Button>
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12 xl:col-span-8 space-y-6">
            <div class="box box--stacked p-6">
                <h2 class="text-sm font-semibold text-slate-700 mb-4 flex items-center gap-2">
                    <Lucide icon="Info" class="w-4 h-4 text-primary" />
                    Training Information
                </h2>
                <div class="space-y-4 text-sm text-slate-600">
                    <p class="leading-relaxed">{{ training.description }}</p>
                    <div v-if="training.content_type === 'video' && training.video_url" class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs text-slate-500 mb-1">Video URL</p>
                        <a :href="training.video_url" target="_blank" class="text-primary hover:underline break-all">{{ training.video_url }}</a>
                    </div>
                    <div v-if="training.content_type === 'url' && training.url" class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs text-slate-500 mb-1">External URL</p>
                        <a :href="training.url" target="_blank" class="text-primary hover:underline break-all">{{ training.url }}</a>
                    </div>
                </div>
            </div>

            <div v-if="training.content_type === 'file'" class="box box--stacked p-6">
                <h2 class="text-sm font-semibold text-slate-700 mb-4 flex items-center gap-2">
                    <Lucide icon="Paperclip" class="w-4 h-4 text-primary" />
                    Attached Files
                </h2>

                <div v-if="training.documents.length" class="space-y-2">
                    <div v-for="document in training.documents" :key="document.id" class="flex items-center justify-between rounded-lg border border-slate-200 bg-slate-50 px-3 py-3">
                        <div class="min-w-0">
                            <a :href="document.preview_url" target="_blank" class="block truncate text-sm font-medium text-primary hover:underline">{{ document.file_name }}</a>
                            <p class="text-xs text-slate-500">{{ document.size_label }} · {{ document.created_at_display }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <a :href="document.preview_url" target="_blank" class="p-1.5 text-slate-400 hover:text-primary transition">
                                <Lucide icon="Eye" class="w-4 h-4" />
                            </a>
                            <button type="button" @click="deleteDocument(document.id)" class="p-1.5 text-slate-400 hover:text-red-500 transition">
                                <Lucide icon="Trash2" class="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                </div>
                <p v-else class="text-sm text-slate-400">No files uploaded for this training.</p>
            </div>

            <div class="box box--stacked p-6">
                <div class="flex items-center justify-between gap-3 mb-4">
                    <h2 class="text-sm font-semibold text-slate-700 flex items-center gap-2">
                        <Lucide icon="Users" class="w-4 h-4 text-primary" />
                        Recent Assignments
                    </h2>
                    <Link v-if="props.routeNames?.assignmentsIndex" :href="route(props.routeNames.assignmentsIndex, { training_id: training.id })" class="text-sm text-primary hover:underline">
                        View all
                    </Link>
                </div>

                <div v-if="recentAssignments.length" class="space-y-3">
                    <div v-for="assignment in recentAssignments" :key="assignment.id" class="rounded-lg border border-slate-200 bg-slate-50 p-4 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-slate-800 truncate">{{ assignment.driver_name }}</p>
                            <p class="text-xs text-slate-500 truncate">
                                {{ assignment.driver_email || 'No email' }}<span v-if="assignment.carrier_name"> - {{ assignment.carrier_name }}</span>
                            </p>
                            <p class="text-xs text-slate-500 mt-1">
                                Assigned {{ assignment.assigned_date || 'N/A' }}<span v-if="assignment.due_date"> - Due {{ assignment.due_date }}</span>
                            </p>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium" :class="assignmentStatusClass(assignment.status)">
                                {{ assignment.status_label }}
                            </span>
                            <Link v-if="props.routeNames?.assignmentsIndex" :href="route('admin.training-assignments.show', assignment.id)" class="p-1.5 text-slate-400 hover:text-primary transition">
                                <Lucide icon="Eye" class="w-4 h-4" />
                            </Link>
                        </div>
                    </div>
                </div>
                <div v-else class="rounded-lg border border-dashed border-slate-300 bg-slate-50 p-6 text-center">
                    <p class="text-sm text-slate-500">This training has not been assigned yet.</p>
                    <Button variant="outline-secondary" class="mt-4" @click="assignModalOpen = true">
                        Assign now
                    </Button>
                </div>
            </div>
        </div>

        <div class="col-span-12 xl:col-span-4 space-y-6">
            <div class="box box--stacked p-6">
                <h2 class="text-sm font-semibold text-slate-700 mb-4 flex items-center gap-2">
                    <Lucide icon="BarChart3" class="w-4 h-4 text-primary" />
                    Assignment Stats
                </h2>
                <div class="space-y-3">
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4 flex items-center justify-between">
                        <span class="text-sm text-slate-600">Total</span>
                        <span class="font-semibold text-slate-800">{{ assignmentStats.total }}</span>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4 flex items-center justify-between">
                        <span class="text-sm text-slate-600">Completed</span>
                        <span class="font-semibold text-slate-800">{{ assignmentStats.completed }}</span>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4 flex items-center justify-between">
                        <span class="text-sm text-slate-600">In Progress</span>
                        <span class="font-semibold text-slate-800">{{ assignmentStats.in_progress }}</span>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4 flex items-center justify-between">
                        <span class="text-sm text-slate-600">Pending</span>
                        <span class="font-semibold text-slate-800">{{ assignmentStats.pending }}</span>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4 flex items-center justify-between">
                        <span class="text-sm text-slate-600">Overdue</span>
                        <span class="font-semibold text-slate-800">{{ assignmentStats.overdue }}</span>
                    </div>
                </div>
            </div>

            <div class="box box--stacked p-6">
                <h2 class="text-sm font-semibold text-slate-700 mb-4 flex items-center gap-2">
                    <Lucide icon="Clock3" class="w-4 h-4 text-primary" />
                    Record Info
                </h2>
                <div class="space-y-3">
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs text-slate-500">Created By</p>
                        <p class="mt-1 text-sm font-medium text-slate-800">{{ training.creator_name ?? 'N/A' }}</p>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs text-slate-500">Created</p>
                        <p class="mt-1 text-sm font-medium text-slate-800">{{ training.created_at ?? 'N/A' }}</p>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs text-slate-500">Updated</p>
                        <p class="mt-1 text-sm font-medium text-slate-800">{{ training.updated_at ?? 'N/A' }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <Dialog :open="assignModalOpen" @close="assignModalOpen = false" size="xl">
        <Dialog.Panel class="w-full max-w-[920px] overflow-hidden">
            <div class="p-6">
                <div class="flex items-center justify-between gap-3 mb-5">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-800">Assign Training</h3>
                        <p class="text-sm text-slate-500 mt-1">Assign {{ training.title }} to one or more drivers without leaving this page.</p>
                    </div>
                    <button type="button" class="text-slate-400 hover:text-slate-600" @click="assignModalOpen = false">
                        <Lucide icon="X" class="w-5 h-5" />
                    </button>
                </div>

                <form @submit.prevent="submitAssignment" class="space-y-5">
                    <AssignForm
                        :form="assignmentForm"
                        :trainings="[assignmentFormOptions.selectedTraining]"
                        :carriers="assignmentFormOptions.carriers"
                        :drivers="assignmentFormOptions.drivers"
                        :training-locked="true"
                        :carrier="props.carrier"
                        :is-carrier-context="props.isCarrierContext"
                        :carrier-locked="props.isCarrierContext"
                    />

                    <div class="flex justify-end gap-3">
                        <Button type="button" variant="outline-secondary" @click="assignModalOpen = false">
                            Cancel
                        </Button>
                        <Button type="submit" variant="primary" :disabled="assignmentForm.processing" class="flex items-center gap-2">
                            <Lucide icon="UserPlus" class="w-4 h-4" />
                            {{ assignmentForm.processing ? 'Assigning...' : 'Assign Training' }}
                        </Button>
                    </div>
                </form>
            </div>
        </Dialog.Panel>
    </Dialog>
</template>
