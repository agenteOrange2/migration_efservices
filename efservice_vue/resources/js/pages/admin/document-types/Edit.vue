<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import Lucide from '@/components/Base/Lucide'
import { FormInput, FormSelect, FormLabel } from '@/components/Base/Form'
import Button from '@/components/Base/Button'
import RazeLayout from '@/layouts/RazeLayout.vue'

defineOptions({ layout: RazeLayout })

const props = defineProps<{
    documentType: {
        id: number
        name: string
        requirement: boolean | number
        has_default_file: boolean
        default_file_url: string | null
    }
}>()

const form = useForm({
    name: props.documentType.name,
    requirement: String(Number(props.documentType.requirement)),
    allow_default_file: props.documentType.has_default_file ? '1' : '0',
    default_file: null as File | null,
})

function handleFile(e: Event) {
    const target = e.target as HTMLInputElement
    if (target.files?.[0]) {
        form.default_file = target.files[0]
    }
}

function submit() {
    form.transform((data) => ({
        ...data,
        _method: 'PUT',
    })).post(route('admin.document-types.update', props.documentType.id), {
        forceFormData: true,
        preserveScroll: true,
    })
}
</script>

<template>
    <Head :title="`Edit: ${documentType.name}`" />

    <div class="max-w-2xl mx-auto">
        <div class="flex items-center gap-3 mb-6">
            <Link :href="route('admin.document-types.index')" class="p-2 rounded-lg hover:bg-slate-100 transition">
                <Lucide icon="ArrowLeft" class="w-5 h-5 text-slate-600" />
            </Link>
            <div>
                <h1 class="text-2xl font-bold text-slate-800">Edit Document Type</h1>
                <p class="text-sm text-slate-500">{{ documentType.name }}</p>
            </div>
        </div>

        <form @submit.prevent="submit">
            <div class="box box--stacked p-6 space-y-5">
                <div>
                    <FormLabel>Document Type Name *</FormLabel>
                    <FormInput v-model="form.name" type="text" />
                    <div v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</div>
                </div>

                <div>
                    <FormLabel>Required *</FormLabel>
                    <FormSelect v-model="form.requirement">
                        <option value="1">Yes - Required for all carriers</option>
                        <option value="0">No - Optional document</option>
                    </FormSelect>
                </div>

                <div>
                    <FormLabel>Default Document</FormLabel>
                    <FormSelect v-model="form.allow_default_file">
                        <option value="1">Yes - Provide a default file</option>
                        <option value="0">No - Carrier must upload</option>
                    </FormSelect>
                </div>

                <div v-if="form.allow_default_file === '1'" class="p-4 bg-slate-50 rounded-lg border border-slate-200">
                    <template v-if="documentType.has_default_file && !form.default_file">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-sm text-slate-600 flex items-center gap-2">
                                <Lucide icon="FileCheck" class="w-4 h-4 text-emerald-500" /> Current file uploaded
                            </span>
                            <a :href="documentType.default_file_url ?? '#'" target="_blank" class="text-sm text-primary hover:underline flex items-center gap-1">
                                <Lucide icon="ExternalLink" class="w-3 h-3" /> View
                            </a>
                        </div>
                    </template>

                    <FormLabel>{{ documentType.has_default_file ? 'Replace File' : 'Upload Default File' }}</FormLabel>
                    <p class="text-xs text-slate-500 mb-2">Accepted: PDF, JPG, PNG (max 10MB)</p>
                    <input type="file" accept=".pdf,.jpg,.jpeg,.png" @change="handleFile" class="w-full text-sm text-slate-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-primary/10 file:text-primary hover:file:bg-primary/20 file:cursor-pointer" />
                    <div v-if="form.default_file" class="mt-2 text-sm text-emerald-600 flex items-center gap-1">
                        <Lucide icon="CheckCircle" class="w-3 h-3" /> {{ (form.default_file as File).name }}
                    </div>
                    <div v-if="form.errors.default_file" class="text-red-500 text-xs mt-1">{{ form.errors.default_file }}</div>
                </div>
            </div>

            <div class="flex justify-end gap-3 mt-5">
                <Link :href="route('admin.document-types.index')" class="px-4 py-2 border border-slate-300 rounded-lg text-slate-600 hover:bg-slate-50 transition">
                    Cancel
                </Link>
                <Button type="submit" variant="primary" :disabled="form.processing" class="px-6">
                    <Lucide icon="Save" class="w-4 h-4 mr-2" /> Update Document Type
                </Button>
            </div>
        </form>
    </div>
</template>
