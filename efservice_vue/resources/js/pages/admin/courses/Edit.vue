<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import RazeLayout from '@/layouts/RazeLayout.vue'
import Form from './Form.vue'

declare function route(name: string, params?: any): string

defineOptions({ layout: RazeLayout })

const props = defineProps<{
    course: {
        id: number
        carrier_id: string
        user_driver_detail_id: string
        organization_name: string
        organization_name_other: string
        city: string
        state: string
        certification_date: string
        expiration_date: string
        experience: string
        status: string
        documents: { id: number; file_name: string; file_type: string; size_label: string; preview_url: string; created_at_display: string | null }[]
    }
    carriers: { id: number; name: string }[]
    drivers: { id: number; carrier_id: number | null; carrier_name?: string | null; name: string; email?: string | null }[]
    states: Record<string, string>
    organizationOptions: Record<string, string>
}>()

const form = useForm({
    carrier_id: props.course.carrier_id || '',
    user_driver_detail_id: props.course.user_driver_detail_id || '',
    organization_name: props.course.organization_name || '',
    organization_name_other: props.course.organization_name_other || '',
    city: props.course.city || '',
    state: props.course.state || '',
    certification_date: props.course.certification_date || '',
    expiration_date: props.course.expiration_date || '',
    experience: props.course.experience || '',
    status: props.course.status || 'active',
    course_documents: [] as File[],
})

function submit() {
    form.transform((data) => ({
        ...data,
        _method: 'put',
    })).post(route('admin.courses.update', props.course.id))
}
</script>

<template>
    <Head title="Edit Course" />

    <div class="grid grid-cols-12 gap-y-8 gap-x-6">
        <div class="col-span-12">
            <div class="box box--stacked p-6">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h1 class="text-xl font-bold text-slate-800">Edit Course</h1>
                        <p class="text-sm text-slate-500 mt-0.5">Update the selected course or certification record.</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <Link :href="route('admin.courses.documents', props.course.id)">
                            <Button variant="outline-primary" class="flex items-center gap-2">
                                <Lucide icon="Files" class="w-4 h-4" />
                                Documents
                            </Button>
                        </Link>
                        <Link :href="route('admin.courses.index')">
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
                    :organization-options="organizationOptions"
                    :existing-documents="course.documents"
                />

                <div class="flex justify-end gap-3">
                    <Link :href="route('admin.courses.index')">
                        <Button type="button" variant="outline-secondary">Cancel</Button>
                    </Link>
                    <Button type="submit" variant="primary" :disabled="form.processing">
                        {{ form.processing ? 'Saving...' : 'Update Course' }}
                    </Button>
                </div>
            </form>
        </div>
    </div>
</template>
