<script setup lang="ts">
import Lucide from '@/components/Base/Lucide'
import TomSelect from '@/components/Base/TomSelect/TomSelect.vue'

const props = defineProps<{
    form: any
    existingDocuments?: { id: number; file_name: string; mime_type?: string | null; size_label: string; preview_url: string; created_at_display: string | null }[]
}>()

function onFilesChange(event: Event) {
    const input = event.target as HTMLInputElement
    props.form.training_files = Array.from(input.files ?? [])
}
</script>

<template>
    <div class="space-y-6">
        <div class="box box--stacked p-6">
            <h2 class="text-sm font-semibold text-slate-700 mb-5 flex items-center gap-2">
                <Lucide icon="BookOpen" class="w-4 h-4 text-primary" />
                Basic Information
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-slate-600 mb-1.5">Title <span class="text-red-500">*</span></label>
                    <input v-model="form.title" type="text" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="Training title" />
                    <p v-if="form.errors.title" class="text-red-500 text-xs mt-1">{{ form.errors.title }}</p>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-slate-600 mb-1.5">Description <span class="text-red-500">*</span></label>
                    <textarea v-model="form.description" rows="5" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm resize-none" placeholder="Training description"></textarea>
                    <p v-if="form.errors.description" class="text-red-500 text-xs mt-1">{{ form.errors.description }}</p>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1.5">Content Type <span class="text-red-500">*</span></label>
                    <TomSelect v-model="form.content_type">
                        <option value="">Select type</option>
                        <option value="file">File</option>
                        <option value="video">Video</option>
                        <option value="url">URL</option>
                    </TomSelect>
                    <p v-if="form.errors.content_type" class="text-red-500 text-xs mt-1">{{ form.errors.content_type }}</p>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1.5">Status <span class="text-red-500">*</span></label>
                    <TomSelect v-model="form.status">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </TomSelect>
                    <p v-if="form.errors.status" class="text-red-500 text-xs mt-1">{{ form.errors.status }}</p>
                </div>

                <div v-if="form.content_type === 'video'" class="md:col-span-2">
                    <label class="block text-xs font-medium text-slate-600 mb-1.5">Video URL <span class="text-red-500">*</span></label>
                    <input v-model="form.video_url" type="url" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="https://www.youtube.com/watch?v=..." />
                    <p class="text-xs text-slate-400 mt-1">Use a YouTube, Vimeo, or direct video link.</p>
                    <p v-if="form.errors.video_url" class="text-red-500 text-xs mt-1">{{ form.errors.video_url }}</p>
                </div>

                <div v-if="form.content_type === 'url'" class="md:col-span-2">
                    <label class="block text-xs font-medium text-slate-600 mb-1.5">External URL <span class="text-red-500">*</span></label>
                    <input v-model="form.url" type="url" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="https://..." />
                    <p class="text-xs text-slate-400 mt-1">Use this when the training content lives on an external site.</p>
                    <p v-if="form.errors.url" class="text-red-500 text-xs mt-1">{{ form.errors.url }}</p>
                </div>
            </div>
        </div>

        <div v-if="form.content_type === 'file'" class="box box--stacked p-6">
            <h2 class="text-sm font-semibold text-slate-700 mb-4 flex items-center gap-2">
                <Lucide icon="Paperclip" class="w-4 h-4 text-primary" />
                Files
            </h2>

            <div v-if="existingDocuments?.length" class="mb-5">
                <p class="text-xs font-medium text-slate-600 mb-2">Current Files</p>
                <div class="space-y-2">
                    <div v-for="document in existingDocuments" :key="document.id" class="flex items-center justify-between rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                        <div class="min-w-0">
                            <a :href="document.preview_url" target="_blank" class="block truncate text-sm font-medium text-primary hover:underline">{{ document.file_name }}</a>
                            <p class="text-xs text-slate-500">{{ document.size_label }} · {{ document.created_at_display }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <label class="block text-xs font-medium text-slate-600 mb-1.5">Upload Files</label>
            <input type="file" multiple accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.ppt,.pptx,.mp4,.mov" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" @change="onFilesChange" />
            <p class="text-xs text-slate-400 mt-2">Accepted: PDF, JPG, PNG, DOC, DOCX, PPT, PPTX, MP4, MOV.</p>
            <p v-if="form.errors.training_files" class="text-red-500 text-xs mt-1">{{ form.errors.training_files }}</p>
        </div>
    </div>
</template>
