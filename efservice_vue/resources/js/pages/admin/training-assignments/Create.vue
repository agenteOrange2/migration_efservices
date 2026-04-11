<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import RazeLayout from '@/layouts/RazeLayout.vue'
import AssignForm from './AssignForm.vue'

declare function route(name: string, params?: any): string

defineOptions({ layout: RazeLayout })

interface AssignmentRouteNames {
    store: string
    index: string
}

const props = withDefaults(defineProps<{
    trainings: { id: number; title: string }[]
    carriers: { id: number; name: string }[]
    drivers: { id: number; carrier_id: number | null; carrier_name?: string | null; name: string; email?: string | null }[]
    selectedTraining: { id: number; title: string } | null
    carrier?: { id: number; name: string } | null
    isCarrierContext?: boolean
    routeNames?: AssignmentRouteNames
}>(), {
    carrier: null,
    isCarrierContext: false,
    routeNames: () => ({
        store: 'admin.training-assignments.store',
        index: 'admin.training-assignments.index',
    }),
})

const form = useForm({
    training_id: props.selectedTraining ? String(props.selectedTraining.id) : '',
    carrier_id: '',
    driver_ids: [] as string[],
    due_date: '',
    status: 'assigned',
    notes: '',
    redirect_to: '',
})

function submit() {
    if (props.selectedTraining) {
        form.post(route(props.routeNames.store, props.selectedTraining.id))
        return
    }

    form.post(route(props.routeNames.store))
}
</script>

<template>
    <Head title="New Training Assignment" />

    <div class="grid grid-cols-12 gap-y-8 gap-x-6">
        <div class="col-span-12">
            <div class="box box--stacked p-6">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h1 class="text-xl font-bold text-slate-800">New Training Assignment</h1>
                        <p class="text-sm text-slate-500 mt-0.5">
                            {{ selectedTraining ? `Assign "${selectedTraining.title}" to one or more drivers.` : 'Assign trainings to drivers.' }}
                        </p>
                    </div>
                    <Link :href="route(props.routeNames.index)">
                        <Button variant="outline-secondary" class="flex items-center gap-2">
                            <Lucide icon="ArrowLeft" class="w-4 h-4" />
                            Back to Assignments
                        </Button>
                    </Link>
                </div>
            </div>
        </div>

        <div class="col-span-12">
            <form @submit.prevent="submit" class="space-y-6">
                <AssignForm
                    :form="form"
                    :trainings="trainings"
                    :carriers="carriers"
                    :drivers="drivers"
                    :training-locked="!!selectedTraining"
                    :carrier="props.carrier"
                    :is-carrier-context="props.isCarrierContext"
                    :carrier-locked="props.isCarrierContext"
                />

                <div class="flex justify-end gap-3">
                    <Link :href="route(props.routeNames.index)">
                        <Button type="button" variant="outline-secondary" class="flex items-center gap-2">
                            <Lucide icon="X" class="w-4 h-4" />
                            Cancel
                        </Button>
                    </Link>
                    <Button type="submit" variant="primary" :disabled="form.processing" class="flex items-center gap-2">
                        <Lucide icon="UserPlus" class="w-4 h-4" />
                        {{ form.processing ? 'Assigning...' : 'Assign Training' }}
                    </Button>
                </div>
            </form>
        </div>
    </div>
</template>
