<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import RazeLayout from '@/layouts/RazeLayout.vue'
import Form from './Form.vue'

declare function route(name: string, params?: any): string

defineOptions({ layout: RazeLayout })

const props = defineProps<{
    school: {
        id: number
        carrier_id: number | null
        user_driver_detail_id: number | null
        school_name: string
        city: string
        state: string
        date_start: string
        date_end: string
        graduated: boolean
        subject_to_safety_regulations: boolean
        performed_safety_functions: boolean
        training_skills: string[]
        driver_name: string
        documents: { id: number; file_name: string; file_type: string; size_label: string; preview_url: string; created_at_display: string | null }[]
    }
    carriers: { id: number; name: string }[]
    drivers: { id: number; carrier_id: number | null; carrier_name?: string | null; name: string; email?: string | null }[]
    states: Record<string, string>
    skillOptions: Record<string, string>
}>()

const form = useForm({
    carrier_id: props.school.carrier_id ? String(props.school.carrier_id) : '',
    user_driver_detail_id: props.school.user_driver_detail_id ? String(props.school.user_driver_detail_id) : '',
    school_name: props.school.school_name ?? '',
    city: props.school.city ?? '',
    state: props.school.state ?? '',
    date_start: props.school.date_start ?? '',
    date_end: props.school.date_end ?? '',
    graduated: props.school.graduated ?? false,
    subject_to_safety_regulations: props.school.subject_to_safety_regulations ?? false,
    performed_safety_functions: props.school.performed_safety_functions ?? false,
    training_skills: [...(props.school.training_skills ?? [])],
    training_documents: [] as File[],
})

function submit() {
    form.transform((data) => ({
        ...data,
        graduated: data.graduated ? 1 : 0,
        subject_to_safety_regulations: data.subject_to_safety_regulations ? 1 : 0,
        performed_safety_functions: data.performed_safety_functions ? 1 : 0,
    })).put(route('admin.training-schools.update', props.school.id), {
        forceFormData: true,
    })
}
</script>

<template>
    <Head :title="`Edit ${school.school_name}`" />

    <div class="grid grid-cols-12 gap-y-8 gap-x-6">
        <div class="col-span-12">
            <div class="box box--stacked p-6">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h1 class="text-xl font-bold text-slate-800">Edit Training School</h1>
                        <p class="text-sm text-slate-500 mt-0.5">Driver: <span class="font-medium text-slate-700">{{ school.driver_name }}</span></p>
                    </div>
                    <div class="flex items-center gap-3">
                        <Link :href="route('admin.training-schools.documents.show', school.id)">
                            <Button variant="outline-primary" class="flex items-center gap-2">
                                <Lucide icon="Files" class="w-4 h-4" />
                                Documents
                            </Button>
                        </Link>
                        <Link :href="route('admin.training-schools.index')">
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
                <Form
                    :form="form"
                    :carriers="carriers"
                    :drivers="drivers"
                    :states="states"
                    :skill-options="skillOptions"
                    :existing-documents="school.documents"
                />

                <div class="flex justify-end gap-3">
                    <Link :href="route('admin.training-schools.index')">
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
