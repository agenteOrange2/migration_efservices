<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import RazeLayout from '@/layouts/RazeLayout.vue'

declare function route(name: string, params?: any): string

defineOptions({ layout: RazeLayout })

const props = defineProps<{ assignment: any }>()

const form = useForm({
    completion_notes: props.assignment.completion_notes ?? '',
    revert: props.assignment.status === 'completed',
})

function submitStatus() {
    form.post(route('admin.training-assignments.mark-complete', props.assignment.id))
}
</script>

<template>
    <Head :title="`Assignment #${assignment.id}`" />

    <div class="grid grid-cols-12 gap-y-8 gap-x-6">
        <div class="col-span-12">
            <div class="box box--stacked p-6">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-xl font-bold text-slate-800">Training Assignment #{{ assignment.id }}</h1>
                        <p class="text-sm text-slate-500 mt-0.5">{{ assignment.driver?.name }} · {{ assignment.training?.title }}</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <Link :href="route('admin.training-assignments.index')">
                            <Button variant="outline-secondary" class="flex items-center gap-2">
                                <Lucide icon="ArrowLeft" class="w-4 h-4" />
                                Back
                            </Button>
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12 lg:col-span-8 space-y-6">
            <div class="box box--stacked p-6">
                <h2 class="text-sm font-semibold text-slate-700 mb-4 flex items-center gap-2">
                    <Lucide icon="Info" class="w-4 h-4 text-primary" />
                    Assignment Details
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4"><p class="text-xs text-slate-500">Driver</p><p class="mt-1 text-sm font-medium text-slate-800">{{ assignment.driver?.name ?? 'N/A' }}</p></div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4"><p class="text-xs text-slate-500">Carrier</p><p class="mt-1 text-sm font-medium text-slate-800">{{ assignment.driver?.carrier_name ?? 'N/A' }}</p></div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4"><p class="text-xs text-slate-500">Training</p><p class="mt-1 text-sm font-medium text-slate-800">{{ assignment.training?.title ?? 'N/A' }}</p></div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4"><p class="text-xs text-slate-500">Status</p><p class="mt-1 text-sm font-medium text-slate-800">{{ assignment.status_label }}</p></div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4"><p class="text-xs text-slate-500">Assigned</p><p class="mt-1 text-sm font-medium text-slate-800">{{ assignment.assigned_date ?? 'N/A' }}</p></div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4"><p class="text-xs text-slate-500">Due Date</p><p class="mt-1 text-sm font-medium text-slate-800">{{ assignment.due_date ?? 'No due date' }}</p></div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4 md:col-span-2"><p class="text-xs text-slate-500">Notes</p><p class="mt-1 text-sm font-medium text-slate-800">{{ assignment.completion_notes || 'No notes available.' }}</p></div>
                </div>
            </div>

            <div v-if="assignment.training?.documents?.length" class="box box--stacked p-6">
                <h2 class="text-sm font-semibold text-slate-700 mb-4 flex items-center gap-2">
                    <Lucide icon="Paperclip" class="w-4 h-4 text-primary" />
                    Training Files
                </h2>
                <div class="space-y-2">
                    <div v-for="document in assignment.training.documents" :key="document.id" class="flex items-center justify-between rounded-lg border border-slate-200 bg-slate-50 px-3 py-3">
                        <div class="min-w-0">
                            <a :href="document.preview_url" target="_blank" class="block truncate text-sm font-medium text-primary hover:underline">{{ document.file_name }}</a>
                            <p class="text-xs text-slate-500">{{ document.size_label }}</p>
                        </div>
                        <a :href="document.preview_url" target="_blank" class="p-1.5 text-slate-400 hover:text-primary transition">
                            <Lucide icon="Eye" class="w-4 h-4" />
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12 lg:col-span-4">
            <div class="box box--stacked p-6">
                <h2 class="text-sm font-semibold text-slate-700 mb-4 flex items-center gap-2">
                    <Lucide icon="CheckCircle" class="w-4 h-4 text-primary" />
                    Status Action
                </h2>
                <form @submit.prevent="submitStatus" class="space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1.5">Completion Notes</label>
                        <textarea v-model="form.completion_notes" rows="4" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm resize-none"></textarea>
                    </div>
                    <div class="flex items-center gap-3">
                        <input id="revert" v-model="form.revert" type="checkbox" class="rounded border-slate-300 text-primary" />
                        <label for="revert" class="text-sm text-slate-700">Revert to assigned</label>
                    </div>
                    <Button type="submit" variant="primary" :disabled="form.processing" class="w-full">
                        {{ form.processing ? 'Saving...' : 'Save Status' }}
                    </Button>
                </form>
            </div>
        </div>
    </div>
</template>
