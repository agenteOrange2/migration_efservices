<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { reactive, ref } from 'vue'
import Lucide from '@/components/Base/Lucide'
import Button from '@/components/Base/Button'
import { Dialog } from '@/components/Base/Headless'
import TomSelect from '@/components/Base/TomSelect/TomSelect.vue'
import RazeLayout from '@/layouts/RazeLayout.vue'

declare function route(name: string, params?: any): string

defineOptions({ layout: RazeLayout })

interface PaginationLink {
    url: string | null
    label: string
    active: boolean
}

interface AssignmentRow {
    id: number
    status: string
    status_label: string
    assigned_date: string | null
    due_date: string | null
    completed_date: string | null
    completion_notes: string | null
    driver: { id: number; name: string; email?: string | null; carrier_name?: string | null } | null
    training: { id: number; title: string; content_type: string; status: string; description: string; video_url?: string | null; url?: string | null; creator_name?: string | null; documents: any[] } | null
}

const props = defineProps<{
    assignments: { data: AssignmentRow[]; links: PaginationLink[]; total: number; last_page: number }
    filters: { search: string; status: string; carrier_id: string; training_id: string }
    carriers: { id: number; name: string }[]
    trainings: { id: number; title: string }[]
    stats: { total: number; completed: number; in_progress: number; pending: number; overdue: number }
}>()

const filters = reactive({ ...props.filters })
const detailsOpen = ref(false)
const completeOpen = ref(false)
const deleteOpen = ref(false)
const selectedAssignment = ref<AssignmentRow | null>(null)

const completeForm = useForm({
    completion_notes: '',
    revert: false,
})

function applyFilters() {
    router.get(route('admin.training-assignments.index'), {
        search: filters.search || undefined,
        status: filters.status || undefined,
        carrier_id: filters.carrier_id || undefined,
        training_id: filters.training_id || undefined,
    }, { preserveState: true, replace: true })
}

function resetFilters() {
    filters.search = ''
    filters.status = ''
    filters.carrier_id = ''
    filters.training_id = ''
    applyFilters()
}

function openDetails(assignment: AssignmentRow) {
    selectedAssignment.value = assignment
    detailsOpen.value = true
}

function openComplete(assignment: AssignmentRow, revert = false) {
    selectedAssignment.value = assignment
    completeForm.completion_notes = assignment.completion_notes ?? ''
    completeForm.revert = revert
    completeOpen.value = true
}

function submitComplete() {
    if (!selectedAssignment.value) return
    completeForm.post(route('admin.training-assignments.mark-complete', selectedAssignment.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            completeOpen.value = false
        },
    })
}

function openDelete(assignment: AssignmentRow) {
    selectedAssignment.value = assignment
    deleteOpen.value = true
}

function confirmDelete() {
    if (!selectedAssignment.value) return
    router.delete(route('admin.training-assignments.destroy', selectedAssignment.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            deleteOpen.value = false
        },
    })
}
</script>

<template>
    <Head title="Training Assignments" />

    <div class="grid grid-cols-12 gap-x-6 gap-y-10">
        <div class="col-span-12">
            <div class="box box--stacked p-6 mb-6">
                <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                            <Lucide icon="ClipboardList" class="w-8 h-8 text-primary" />
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-slate-800">Training Assignments</h1>
                            <p class="text-slate-500">Manage assignments of trainings to drivers.</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <Link :href="route('admin.trainings.index')">
                            <Button variant="outline-secondary" class="flex items-center gap-2">
                                <Lucide icon="ArrowLeft" class="w-4 h-4" />
                                Trainings
                            </Button>
                        </Link>
                        <Link :href="route('admin.training-assignments.create')">
                            <Button variant="primary" class="flex items-center gap-2">
                                <Lucide icon="UserPlus" class="w-4 h-4" />
                                New Assignment
                            </Button>
                        </Link>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
                <div class="box box--stacked rounded-xl border border-dashed border-slate-300/80 p-5"><p class="text-sm text-slate-500">Total</p><p class="mt-1 text-2xl font-semibold text-slate-800">{{ stats.total }}</p></div>
                <div class="box box--stacked rounded-xl border border-dashed border-slate-300/80 p-5"><p class="text-sm text-slate-500">Completed</p><p class="mt-1 text-2xl font-semibold text-slate-800">{{ stats.completed }}</p></div>
                <div class="box box--stacked rounded-xl border border-dashed border-slate-300/80 p-5"><p class="text-sm text-slate-500">In Progress</p><p class="mt-1 text-2xl font-semibold text-slate-800">{{ stats.in_progress }}</p></div>
                <div class="box box--stacked rounded-xl border border-dashed border-slate-300/80 p-5"><p class="text-sm text-slate-500">Pending</p><p class="mt-1 text-2xl font-semibold text-slate-800">{{ stats.pending }}</p></div>
                <div class="box box--stacked rounded-xl border border-dashed border-slate-300/80 p-5"><p class="text-sm text-slate-500">Overdue</p><p class="mt-1 text-2xl font-semibold text-slate-800">{{ stats.overdue }}</p></div>
            </div>

            <div class="box box--stacked p-5 mb-6">
                <div class="grid grid-cols-1 lg:grid-cols-5 gap-4">
                    <div class="lg:col-span-2 relative">
                        <Lucide icon="Search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                        <input v-model="filters.search" type="text" class="w-full rounded-lg border border-slate-200 px-3 py-2 pl-10 text-sm" placeholder="Search driver or training..." />
                    </div>
                    <TomSelect v-model="filters.training_id">
                        <option value="">All Trainings</option>
                        <option v-for="training in trainings" :key="training.id" :value="String(training.id)">{{ training.title }}</option>
                    </TomSelect>
                    <TomSelect v-model="filters.carrier_id">
                        <option value="">All Carriers</option>
                        <option v-for="carrier in carriers" :key="carrier.id" :value="String(carrier.id)">{{ carrier.name }}</option>
                    </TomSelect>
                    <TomSelect v-model="filters.status">
                        <option value="">All Statuses</option>
                        <option value="assigned">Assigned</option>
                        <option value="in_progress">In Progress</option>
                        <option value="completed">Completed</option>
                        <option value="overdue">Overdue</option>
                    </TomSelect>
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
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-slate-50/80">
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Driver</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Carrier</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Training</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Due Date</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Status</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="assignment in assignments.data" :key="assignment.id" class="border-b border-slate-100 hover:bg-slate-50/50 transition">
                                <td class="px-5 py-4">
                                    <div class="font-medium text-slate-800">{{ assignment.driver?.name ?? 'N/A' }}</div>
                                    <div class="text-xs text-slate-400">{{ assignment.driver?.email }}</div>
                                </td>
                                <td class="px-5 py-4 text-sm text-slate-600">{{ assignment.driver?.carrier_name ?? 'N/A' }}</td>
                                <td class="px-5 py-4">
                                    <Link v-if="assignment.training" :href="route('admin.trainings.show', assignment.training.id)" class="font-medium text-primary hover:underline">
                                        {{ assignment.training.title }}
                                    </Link>
                                    <span v-else class="text-slate-400">N/A</span>
                                </td>
                                <td class="px-5 py-4 text-sm text-slate-600">{{ assignment.due_date ?? 'No due date' }}</td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium" :class="assignment.status === 'completed' ? 'bg-primary/10 text-primary' : assignment.status === 'overdue' ? 'bg-red-100 text-red-600' : 'bg-slate-100 text-slate-600'">
                                        {{ assignment.status_label }}
                                    </span>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center justify-center gap-2">
                                        <button type="button" @click="openDetails(assignment)" class="p-1.5 text-slate-400 hover:text-primary transition" title="Details">
                                            <Lucide icon="Eye" class="w-4 h-4" />
                                        </button>
                                        <Link :href="route('admin.training-assignments.show', assignment.id)" class="p-1.5 text-slate-400 hover:text-primary transition" title="Open page">
                                            <Lucide icon="ExternalLink" class="w-4 h-4" />
                                        </Link>
                                        <button v-if="assignment.status !== 'completed'" type="button" @click="openComplete(assignment, false)" class="p-1.5 text-slate-400 hover:text-primary transition" title="Mark completed">
                                            <Lucide icon="CheckCircle" class="w-4 h-4" />
                                        </button>
                                        <button v-else type="button" @click="openComplete(assignment, true)" class="p-1.5 text-slate-400 hover:text-primary transition" title="Revert">
                                            <Lucide icon="RotateCcw" class="w-4 h-4" />
                                        </button>
                                        <button type="button" @click="openDelete(assignment)" class="p-1.5 text-slate-400 hover:text-red-500 transition" title="Delete">
                                            <Lucide icon="Trash2" class="w-4 h-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!assignments.data.length">
                                <td colspan="6" class="px-5 py-12 text-center text-slate-400">
                                    <Lucide icon="ClipboardList" class="w-12 h-12 mx-auto mb-3 text-slate-300" />
                                    <p>No assignments found</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="assignments.last_page > 1" class="p-4 border-t border-slate-200/60 flex items-center justify-between">
                    <span class="text-sm text-slate-500">{{ assignments.total }} assignments</span>
                    <div class="flex gap-1">
                        <template v-for="link in assignments.links" :key="link.label">
                            <Link v-if="link.url" :href="link.url" class="px-3 py-1 text-sm rounded" :class="link.active ? 'bg-primary text-white' : 'text-slate-600 hover:bg-slate-100'" v-html="link.label" />
                            <span v-else class="px-3 py-1 text-sm text-slate-300" v-html="link.label" />
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <Dialog :open="detailsOpen" @close="detailsOpen = false" size="xl">
        <Dialog.Panel class="w-full max-w-[900px] overflow-hidden">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-slate-800">Assignment Details</h3>
                    <button type="button" @click="detailsOpen = false" class="text-slate-400 hover:text-slate-600">
                        <Lucide icon="X" class="w-5 h-5" />
                    </button>
                </div>
                <div v-if="selectedAssignment" class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs text-slate-500">Driver</p>
                        <p class="mt-1 font-medium text-slate-800">{{ selectedAssignment.driver?.name ?? 'N/A' }}</p>
                        <p class="text-slate-500 mt-1">{{ selectedAssignment.driver?.email }}</p>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs text-slate-500">Training</p>
                        <p class="mt-1 font-medium text-slate-800">{{ selectedAssignment.training?.title ?? 'N/A' }}</p>
                        <p class="text-slate-500 mt-1">{{ selectedAssignment.training?.content_type }}</p>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs text-slate-500">Assigned Date</p>
                        <p class="mt-1 font-medium text-slate-800">{{ selectedAssignment.assigned_date ?? 'N/A' }}</p>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs text-slate-500">Due Date</p>
                        <p class="mt-1 font-medium text-slate-800">{{ selectedAssignment.due_date ?? 'No due date' }}</p>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4 md:col-span-2">
                        <p class="text-xs text-slate-500">Notes</p>
                        <p class="mt-1 font-medium text-slate-800">{{ selectedAssignment.completion_notes || 'No notes available.' }}</p>
                    </div>
                </div>
            </div>
        </Dialog.Panel>
    </Dialog>

    <Dialog :open="completeOpen" @close="completeOpen = false" size="lg">
        <Dialog.Panel class="w-full max-w-[560px] overflow-hidden">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-slate-800">{{ completeForm.revert ? 'Revert Assignment' : 'Mark Assignment Complete' }}</h3>
                    <button type="button" @click="completeOpen = false" class="text-slate-400 hover:text-slate-600">
                        <Lucide icon="X" class="w-5 h-5" />
                    </button>
                </div>
                <div class="space-y-4">
                    <p class="text-sm text-slate-600">
                        {{ completeForm.revert ? 'This will move the assignment back to assigned status.' : 'This will mark the assignment as completed.' }}
                    </p>
                    <div v-if="!completeForm.revert">
                        <label class="block text-xs font-medium text-slate-600 mb-1.5">Completion Notes</label>
                        <textarea v-model="completeForm.completion_notes" rows="4" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm resize-none" placeholder="Optional completion notes"></textarea>
                    </div>
                    <div class="flex justify-end gap-3">
                        <Button type="button" variant="outline-secondary" @click="completeOpen = false">Cancel</Button>
                        <Button type="button" variant="primary" :disabled="completeForm.processing" @click="submitComplete">
                            {{ completeForm.processing ? 'Saving...' : (completeForm.revert ? 'Revert Status' : 'Mark Complete') }}
                        </Button>
                    </div>
                </div>
            </div>
        </Dialog.Panel>
    </Dialog>

    <Dialog :open="deleteOpen" @close="deleteOpen = false" size="lg">
        <Dialog.Panel class="w-full max-w-[520px] overflow-hidden">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-slate-800">Delete Assignment</h3>
                    <button type="button" @click="deleteOpen = false" class="text-slate-400 hover:text-slate-600">
                        <Lucide icon="X" class="w-5 h-5" />
                    </button>
                </div>
                <p class="text-sm text-slate-600">This will permanently delete the selected training assignment.</p>
                <div class="flex justify-end gap-3 mt-6">
                    <Button type="button" variant="outline-secondary" @click="deleteOpen = false">Cancel</Button>
                    <Button type="button" variant="danger" @click="confirmDelete">Delete</Button>
                </div>
            </div>
        </Dialog.Panel>
    </Dialog>
</template>
