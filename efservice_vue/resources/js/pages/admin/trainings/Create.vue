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
    title: '',
    description: '',
    content_type: '',
    status: 'active',
    video_url: '',
    url: '',
    training_files: [] as File[],
})

function submit() {
    form.post(route(props.routeNames.store), { forceFormData: true })
}
</script>

<template>
    <Head title="Create Training" />

    <div class="grid grid-cols-12 gap-y-8 gap-x-6">
        <div class="col-span-12">
            <div class="box box--stacked p-6">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h1 class="text-xl font-bold text-slate-800">Create Training</h1>
                        <p class="text-sm text-slate-500 mt-0.5">{{ props.isCarrierContext ? 'Add a training that your team can assign to drivers.' : 'Add a new training for drivers.' }}</p>
                    </div>
                    <Link :href="route(props.routeNames.index)">
                        <Button variant="outline-secondary" class="flex items-center gap-2">
                            <Lucide icon="ArrowLeft" class="w-4 h-4" />
                            Back to Trainings
                        </Button>
                    </Link>
                </div>
            </div>
        </div>

        <div class="col-span-12">
            <form @submit.prevent="submit" class="space-y-6">
                <Form :form="form" />

                <div class="flex justify-end gap-3">
                    <Link :href="route(props.routeNames.index)">
                        <Button type="button" variant="outline-secondary" class="flex items-center gap-2">
                            <Lucide icon="X" class="w-4 h-4" />
                            Cancel
                        </Button>
                    </Link>
                    <Button type="submit" variant="primary" :disabled="form.processing" class="flex items-center gap-2">
                        <Lucide icon="Save" class="w-4 h-4" />
                        {{ form.processing ? 'Saving...' : 'Save Training' }}
                    </Button>
                </div>
            </form>
        </div>
    </div>
</template>
