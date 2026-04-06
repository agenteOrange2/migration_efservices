<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import RazeLayout from '@/layouts/RazeLayout.vue'
import Form from './Form.vue'

declare function route(name: string, params?: any): string

defineOptions({ layout: RazeLayout })

const props = defineProps<{
    carriers: { id: number; name: string }[]
    drivers: { id: number; carrier_id: number | null; carrier_name?: string | null; name: string; email?: string | null }[]
    states: Record<string, string>
    skillOptions: Record<string, string>
}>()

const form = useForm({
    carrier_id: '',
    user_driver_detail_id: '',
    school_name: '',
    city: '',
    state: '',
    date_start: '',
    date_end: '',
    graduated: false,
    subject_to_safety_regulations: false,
    performed_safety_functions: false,
    training_skills: [] as string[],
    training_documents: [] as File[],
})

function submit() {
    form.transform((data) => ({
        ...data,
        graduated: data.graduated ? 1 : 0,
        subject_to_safety_regulations: data.subject_to_safety_regulations ? 1 : 0,
        performed_safety_functions: data.performed_safety_functions ? 1 : 0,
    })).post(route('admin.training-schools.store'), {
        forceFormData: true,
    })
}
</script>

<template>
    <Head title="Add Training School" />

    <div class="grid grid-cols-12 gap-y-8 gap-x-6">
        <div class="col-span-12">
            <div class="box box--stacked p-6">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h1 class="text-xl font-bold text-slate-800">Add Training School</h1>
                        <p class="text-sm text-slate-500 mt-0.5">Create a new training school record for a driver.</p>
                    </div>
                    <Link :href="route('admin.training-schools.index')">
                        <Button variant="outline-secondary" class="flex items-center gap-2">
                            <Lucide icon="ArrowLeft" class="w-4 h-4" />
                            Back to Training Schools
                        </Button>
                    </Link>
                </div>
            </div>
        </div>

        <div class="col-span-12">
            <form @submit.prevent="submit" class="space-y-6">
                <Form :form="form" :carriers="carriers" :drivers="drivers" :states="states" :skill-options="skillOptions" />

                <div class="flex justify-end gap-3">
                    <Link :href="route('admin.training-schools.index')">
                        <Button type="button" variant="outline-secondary" class="flex items-center gap-2">
                            <Lucide icon="X" class="w-4 h-4" />
                            Cancel
                        </Button>
                    </Link>
                    <Button type="submit" variant="primary" :disabled="form.processing" class="flex items-center gap-2">
                        <Lucide icon="Save" class="w-4 h-4" />
                        {{ form.processing ? 'Saving...' : 'Save Training School' }}
                    </Button>
                </div>
            </form>
        </div>
    </div>
</template>
