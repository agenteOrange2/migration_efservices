<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import { FormSelect } from '@/components/Base/Form'
import RazeLayout from '@/layouts/RazeLayout.vue'

declare function route(name: string, params?: any): string

defineOptions({ layout: RazeLayout })

interface CategoryOption {
    value: string
    label: string
}

interface RequirementRow {
    collection: string
    label: string
    uploaded_count: number
    is_complete: boolean
    upload_url: string
}

const props = defineProps<{
    driver: {
        id: number
        full_name: string
        carrier_name: string | null
    }
    selectedCategory: string
    categories: CategoryOption[]
    requirements: RequirementRow[]
}>()

const form = useForm({
    category: props.selectedCategory,
    document: null as File | null,
})

function onFileChange(event: Event) {
    const target = event.target as HTMLInputElement
    form.document = target.files?.[0] ?? null
}

function submit() {
    form.post(route('driver.documents.store'), {
        forceFormData: true,
    })
}
</script>

<template>
    <Head title="Upload Document" />

    <div class="grid grid-cols-12 gap-6">
        <div class="col-span-12">
            <div class="box box--stacked p-6">
                <div class="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
                    <div class="space-y-4">
                        <Link
                            :href="route('driver.documents.index')"
                            class="inline-flex items-center gap-2 text-sm font-medium text-slate-500 transition hover:text-primary"
                        >
                            <Lucide icon="ArrowLeft" class="h-4 w-4" />
                            Back to Documents
                        </Link>

                        <div class="flex items-start gap-4">
                            <div class="rounded-2xl border border-primary/20 bg-primary/10 p-3">
                                <Lucide icon="Upload" class="h-8 w-8 text-primary" />
                            </div>
                            <div>
                                <h1 class="text-2xl font-bold text-slate-800">Upload Document</h1>
                                <p class="mt-1 text-slate-500">Add a new document to your driver library.</p>
                                <p class="mt-2 text-sm text-slate-500">
                                    Driver: <span class="font-medium text-slate-700">{{ driver.full_name }}</span>
                                    <span v-if="driver.carrier_name"> · Carrier: <span class="font-medium text-slate-700">{{ driver.carrier_name }}</span></span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12 xl:col-span-8">
            <div class="box box--stacked p-6">
                <div class="mb-5 flex items-center gap-3">
                    <Lucide icon="FileUp" class="h-5 w-5 text-primary" />
                    <h2 class="text-base font-semibold text-slate-800">Document Details</h2>
                </div>

                <form class="space-y-6" @submit.prevent="submit">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">Document Category</label>
                        <FormSelect v-model="form.category">
                            <option value="">Select a category</option>
                            <option v-for="category in categories" :key="category.value" :value="category.value">
                                {{ category.label }}
                            </option>
                        </FormSelect>
                        <p v-if="form.errors.category" class="mt-2 text-sm text-danger">{{ form.errors.category }}</p>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">Document File</label>
                        <div class="rounded-2xl border-2 border-dashed border-slate-300 bg-slate-50 p-8 text-center transition hover:border-primary/40">
                            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-primary/10">
                                <Lucide icon="UploadCloud" class="h-7 w-7 text-primary" />
                            </div>
                            <p class="mt-4 text-sm font-medium text-slate-700">Choose a PDF or image file</p>
                            <p class="mt-1 text-sm text-slate-500">Accepted formats: PDF, JPG, JPEG, PNG. Maximum size: 10MB.</p>
                            <input
                                type="file"
                                accept=".pdf,.jpg,.jpeg,.png"
                                class="mt-5 block w-full text-sm text-slate-500 file:mr-4 file:rounded-lg file:border-0 file:bg-primary file:px-4 file:py-2 file:font-medium file:text-white hover:file:bg-primary/90"
                                @change="onFileChange"
                            />
                            <p v-if="form.document" class="mt-3 text-sm text-slate-600">
                                Selected: <span class="font-medium text-slate-700">{{ form.document.name }}</span>
                            </p>
                        </div>
                        <p v-if="form.errors.document" class="mt-2 text-sm text-danger">{{ form.errors.document }}</p>
                    </div>

                    <div class="flex flex-wrap items-center gap-3 pt-2">
                        <Button type="submit" variant="primary" class="gap-2" :disabled="form.processing">
                            <Lucide icon="Upload" class="h-4 w-4" />
                            {{ form.processing ? 'Uploading...' : 'Upload Document' }}
                        </Button>
                        <Link :href="route('driver.documents.index')">
                            <Button type="button" variant="outline-secondary">
                                Cancel
                            </Button>
                        </Link>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-span-12 xl:col-span-4">
            <div class="box box--stacked p-6">
                <div class="mb-5 flex items-center gap-3">
                    <Lucide icon="ListChecks" class="h-5 w-5 text-primary" />
                    <h2 class="text-base font-semibold text-slate-800">Required Categories</h2>
                </div>

                <div class="space-y-3">
                    <div
                        v-for="requirement in requirements"
                        :key="requirement.collection"
                        class="rounded-2xl border p-4"
                        :class="requirement.is_complete ? 'border-primary/20 bg-primary/5' : 'border-slate-200 bg-white'"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold text-slate-800">{{ requirement.label }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ requirement.uploaded_count }} uploaded</p>
                            </div>
                            <span
                                class="rounded-full px-2.5 py-1 text-xs font-medium"
                                :class="requirement.is_complete ? 'bg-primary/10 text-primary' : 'bg-slate-100 text-slate-600'"
                            >
                                {{ requirement.is_complete ? 'Ready' : 'Needed' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
