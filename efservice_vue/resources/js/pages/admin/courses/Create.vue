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
    organizationOptions: Record<string, string>
    selectedDriverId: string
}>()

const selectedDriver = props.drivers.find((driver) => String(driver.id) === props.selectedDriverId)

const form = useForm({
    carrier_id: selectedDriver?.carrier_id ? String(selectedDriver.carrier_id) : '',
    user_driver_detail_id: props.selectedDriverId || '',
    organization_name: '',
    organization_name_other: '',
    city: '',
    state: '',
    certification_date: '',
    expiration_date: '',
    experience: '',
    status: 'active',
    course_documents: [] as File[],
})

function submit() {
    form.post(route('admin.courses.store'))
}
</script>

<template>
    <Head title="New Course" />

    <div class="grid grid-cols-12 gap-y-8 gap-x-6">
        <div class="col-span-12">
            <div class="box box--stacked p-6">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h1 class="text-xl font-bold text-slate-800">New Course</h1>
                        <p class="text-sm text-slate-500 mt-0.5">Create a new driver course or certification record.</p>
                    </div>
                    <Link :href="route('admin.courses.index')">
                        <Button variant="outline-secondary" class="flex items-center gap-2">
                            <Lucide icon="ArrowLeft" class="w-4 h-4" />
                            Back to Courses
                        </Button>
                    </Link>
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
                />

                <div class="flex justify-end gap-3">
                    <Link :href="route('admin.courses.index')">
                        <Button type="button" variant="outline-secondary">Cancel</Button>
                    </Link>
                    <Button type="submit" variant="primary" :disabled="form.processing">
                        {{ form.processing ? 'Saving...' : 'Create Course' }}
                    </Button>
                </div>
            </form>
        </div>
    </div>
</template>
