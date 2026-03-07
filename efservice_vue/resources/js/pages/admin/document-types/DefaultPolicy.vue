<script setup lang="ts">
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import Lucide from '@/components/Base/Lucide'
import { FormLabel } from '@/components/Base/Form'
import Button from '@/components/Base/Button'
import RazeLayout from '@/layouts/RazeLayout.vue'

defineOptions({ layout: RazeLayout })

const props = defineProps<{
    policyDocumentType: { id: number; name: string } | null
    policyMediaUrl: string | null
    policyMediaName: string | null
}>()

const form = useForm({
    policy_file: null as File | null,
})

function handleFile(e: Event) {
    const target = e.target as HTMLInputElement
    if (target.files?.[0]) {
        form.policy_file = target.files[0]
    }
}

function upload() {
    form.post(route('admin.document-types.upload-default-policy'), {
        forceFormData: true,
        preserveScroll: true,
    })
}

function deletePolicy() {
    if (confirm('Remove the current company policy?')) {
        router.delete(route('admin.document-types.delete-default-policy'))
    }
}
</script>

<template>
    <Head title="Company Policy" />

    <div class="max-w-2xl mx-auto">
        <div class="flex items-center gap-3 mb-6">
            <Link :href="route('admin.document-types.index')" class="p-2 rounded-lg hover:bg-slate-100 transition">
                <Lucide icon="ArrowLeft" class="w-5 h-5 text-slate-600" />
            </Link>
            <div>
                <h1 class="text-2xl font-bold text-slate-800">Default Company Policy</h1>
                <p class="text-sm text-slate-500">Manage the default company policy document for driver registration</p>
            </div>
        </div>

        <div class="box box--stacked p-6 space-y-6">
            <template v-if="policyMediaUrl">
                <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-lg">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-emerald-100 rounded-lg">
                                <Lucide icon="FileCheck" class="w-5 h-5 text-emerald-600" />
                            </div>
                            <div>
                                <p class="font-medium text-emerald-800">Policy Uploaded</p>
                                <p class="text-sm text-emerald-600">{{ policyMediaName }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <a :href="policyMediaUrl" target="_blank" class="p-2 text-emerald-600 hover:bg-emerald-100 rounded-lg transition">
                                <Lucide icon="ExternalLink" class="w-4 h-4" />
                            </a>
                            <button @click="deletePolicy" class="p-2 text-red-500 hover:bg-red-50 rounded-lg transition">
                                <Lucide icon="Trash2" class="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                </div>
            </template>

            <div>
                <FormLabel>{{ policyMediaUrl ? 'Replace Company Policy' : 'Upload Company Policy' }}</FormLabel>
                <p class="text-xs text-slate-500 mb-3">Upload a PDF document (max 10MB). This policy will be distributed to all carriers.</p>

                <form @submit.prevent="upload">
                    <input type="file" accept=".pdf" @change="handleFile" class="w-full text-sm text-slate-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-primary/10 file:text-primary hover:file:bg-primary/20 file:cursor-pointer" />
                    <div v-if="form.default_file" class="mt-2 text-sm text-emerald-600">
                        Selected: {{ (form.policy_file as File)?.name }}
                    </div>
                    <div v-if="form.errors.policy_file" class="text-red-500 text-xs mt-1">{{ form.errors.policy_file }}</div>

                    <Button type="submit" variant="primary" :disabled="form.processing || !form.policy_file" class="mt-4 px-6">
                        <Lucide icon="Upload" class="w-4 h-4 mr-2" /> Upload Policy
                    </Button>
                </form>
            </div>
        </div>
    </div>
</template>
