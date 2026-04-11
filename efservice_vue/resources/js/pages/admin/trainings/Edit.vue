<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import RazeLayout from '@/layouts/RazeLayout.vue'
import Form from './Form.vue'

declare function route(name: string, params?: any): string

defineOptions({ layout: RazeLayout })

interface TrainingRouteNames {
    index: string
    store: string
    show: string
    edit: string
    update: string
    destroy: string
    mediaDestroy: string
    assignSelect?: string
    assignForm?: string
    assign?: string
    dashboard?: string
    assignmentsIndex?: string
}

const props = withDefaults(defineProps<{
    training: {
        id: number
        title: string
        description: string
        content_type: string
        status: string
        video_url: string | null
        url: string | null
        documents: { id: number; file_name: string; mime_type?: string | null; size_label: string; preview_url: string; created_at_display: string | null }[]
    }
    routeNames?: TrainingRouteNames
    isCarrierContext?: boolean
}>(), {
    routeNames: () => ({
        index: 'admin.trainings.index',
        store: 'admin.trainings.store',
        show: 'admin.trainings.show',
        edit: 'admin.trainings.edit',
        update: 'admin.trainings.update',
        destroy: 'admin.trainings.destroy',
        mediaDestroy: 'admin.trainings.media.destroy',
        assignForm: 'admin.trainings.assign.form',
        assign: 'admin.trainings.assign',
        dashboard: 'admin.training-dashboard.index',
        assignmentsIndex: 'admin.training-assignments.index',
    }),
    isCarrierContext: false,
})

const form = useForm({
    title: props.training.title ?? '',
    description: props.training.description ?? '',
    content_type: props.training.content_type ?? '',
    status: props.training.status ?? 'active',
    video_url: props.training.video_url ?? '',
    url: props.training.url ?? '',
    training_files: [] as File[],
})

function submit() {
    form.put(route(props.routeNames.update, props.training.id), { forceFormData: true })
}
</script>

<template>
    <Head :title="`Edit ${training.title}`" />

    <div class="grid grid-cols-12 gap-y-8 gap-x-6">
        <div class="col-span-12">
            <div class="box box--stacked p-6">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h1 class="text-xl font-bold text-slate-800">Edit Training</h1>
                        <p class="text-sm text-slate-500 mt-0.5">{{ training.title }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <Link :href="route(props.routeNames.show, training.id)">
                            <Button variant="outline-primary" class="flex items-center gap-2">
                                <Lucide icon="Eye" class="w-4 h-4" />
                                View Training
                            </Button>
                        </Link>
                        <Link :href="route(props.routeNames.index)">
                            <Button variant="outline-secondary" class="flex items-center gap-2">
                                <Lucide icon="ArrowLeft" class="w-4 h-4" />
                                Back
                            </Button>
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12">
            <form @submit.prevent="submit" class="space-y-6">
                <Form :form="form" :existing-documents="training.documents" />

                <div class="flex justify-end gap-3">
                    <Link :href="route(props.routeNames.index)">
                        <Button type="button" variant="outline-secondary" class="flex items-center gap-2">
                            <Lucide icon="X" class="w-4 h-4" />
                            Cancel
                        </Button>
                    </Link>
                    <Button type="submit" variant="primary" :disabled="form.processing" class="flex items-center gap-2">
                        <Lucide icon="Save" class="w-4 h-4" />
                        {{ form.processing ? 'Saving...' : 'Save Changes' }}
                    </Button>
                </div>
            </form>
        </div>
    </div>
</template>
