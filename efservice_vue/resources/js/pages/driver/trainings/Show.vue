<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import { Dialog } from '@/components/Base/Headless'
import RazeLayout from '@/layouts/RazeLayout.vue'

declare function route(name: string, params?: any): string

defineOptions({ layout: RazeLayout })

interface TrainingDocument {
    id: number
    file_name: string
    mime_type: string | null
    size_label: string
    file_type: string
    created_at: string | null
    preview_url: string
    download_url: string
}

const props = defineProps<{
    driver: {
        id: number
        full_name: string
        carrier_name: string | null
    }
    assignment: {
        id: number
        status: string
        status_label: string
        assigned_date: string | null
        due_date: string | null
        completed_date: string | null
        completion_notes: string | null
        can_start: boolean
        can_complete: boolean
        training: {
            id: number | null
            title: string
            description: string | null
            content_type: string | null
            status: string | null
            video_url: string | null
            url: string | null
            creator_name: string | null
            documents: TrainingDocument[]
        }
    }
}>()

const completionModalOpen = ref(false)
const completionForm = useForm({
    confirmed: false,
    notes: '',
})

const videoEmbedUrl = computed(() => {
    const url = props.assignment.training.video_url

    if (!url) return null

    try {
        if (url.includes('youtube.com/watch')) {
            const parsed = new URL(url)
            const videoId = parsed.searchParams.get('v')
            return videoId ? `https://www.youtube.com/embed/${videoId}` : null
        }

        if (url.includes('youtu.be/')) {
            const videoId = url.split('youtu.be/')[1]?.split('?')[0]
            return videoId ? `https://www.youtube.com/embed/${videoId}` : null
        }

        if (url.includes('vimeo.com/')) {
            const videoId = url.split('vimeo.com/')[1]?.split('?')[0]
            return videoId ? `https://player.vimeo.com/video/${videoId}` : null
        }
    } catch {
        return null
    }

    return null
})

const isDirectVideo = computed(() => {
    const url = props.assignment.training.video_url?.toLowerCase() ?? ''
    return ['.mp4', '.mov', '.webm', '.ogg'].some((extension) => url.includes(extension))
})

function statusClass(status: string) {
    if (status === 'completed') return 'bg-success/10 text-success'
    if (status === 'in_progress') return 'bg-info/10 text-info'
    if (status === 'overdue') return 'bg-danger/10 text-danger'
    return 'bg-warning/10 text-warning'
}

function contentTypeLabel(type: string | null) {
    if (type === 'file') return 'Document Training'
    if (type === 'video') return 'Video Training'
    if (type === 'url') return 'External Training'
    return 'Training Content'
}

function fileIcon(type: string) {
    if (type === 'pdf') return 'FileText'
    if (type === 'image') return 'Image'
    if (type === 'video') return 'Video'
    if (type === 'document') return 'Files'
    return 'File'
}

function fileTone(type: string) {
    if (type === 'pdf') return 'bg-danger/10 text-danger'
    if (type === 'image') return 'bg-info/10 text-info'
    if (type === 'video') return 'bg-primary/10 text-primary'
    return 'bg-slate-100 text-slate-600'
}

function startTraining() {
    router.post(route('driver.trainings.start-progress', props.assignment.id), {}, {
        preserveScroll: true,
    })
}

function completeTraining() {
    completionForm.post(route('driver.trainings.complete', props.assignment.id), {
        preserveScroll: true,
        onSuccess: () => {
            completionModalOpen.value = false
            completionForm.reset()
        },
    })
}
</script>

<template>
    <Head :title="assignment.training.title" />

    <div class="grid grid-cols-12 gap-6">
        <div class="col-span-12">
            <div class="box box--stacked p-6">
                <div class="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
                    <div class="space-y-4">
                        <Link
                            :href="route('driver.trainings.index')"
                            class="inline-flex items-center gap-2 text-sm font-medium text-slate-500 transition hover:text-primary"
                        >
                            <Lucide icon="ArrowLeft" class="h-4 w-4" />
                            Back to Trainings
                        </Link>

                        <div class="flex flex-wrap items-center gap-3">
                            <h1 class="text-2xl font-bold text-slate-800">{{ assignment.training.title }}</h1>
                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium" :class="statusClass(assignment.status)">
                                {{ assignment.status_label }}
                            </span>
                            <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">
                                {{ contentTypeLabel(assignment.training.content_type) }}
                            </span>
                        </div>

                        <p class="max-w-3xl text-sm leading-6 text-slate-500">
                            {{ assignment.training.description || 'No description provided for this training.' }}
                        </p>

                        <p class="text-sm text-slate-500">
                            Driver: <span class="font-medium text-slate-700">{{ driver.full_name }}</span>
                            <span v-if="driver.carrier_name"> · Carrier: <span class="font-medium text-slate-700">{{ driver.carrier_name }}</span></span>
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <Button
                            v-if="assignment.can_start"
                            variant="primary"
                            class="gap-2"
                            @click="startTraining"
                        >
                            <Lucide icon="Play" class="h-4 w-4" />
                            Start Training
                        </Button>
                        <Button
                            v-else-if="assignment.can_complete"
                            variant="primary"
                            class="gap-2"
                            @click="completionModalOpen = true"
                        >
                            <Lucide icon="CheckCircle2" class="h-4 w-4" />
                            Mark Complete
                        </Button>
                        <Button
                            v-else
                            variant="outline-secondary"
                            class="gap-2"
                            disabled
                        >
                            <Lucide icon="BadgeCheck" class="h-4 w-4" />
                            {{ assignment.status === 'completed' ? 'Completed' : 'Read Only' }}
                        </Button>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12 xl:col-span-8 space-y-6">
            <div class="box box--stacked p-6">
                <h2 class="text-base font-semibold text-slate-800">Training Content</h2>

                <div v-if="assignment.training.content_type === 'video'" class="mt-5 space-y-4">
                    <div v-if="videoEmbedUrl" class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-950 shadow-sm">
                        <iframe
                            :src="videoEmbedUrl"
                            class="aspect-video w-full"
                            frameborder="0"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                            allowfullscreen
                        />
                    </div>

                    <video
                        v-else-if="isDirectVideo && assignment.training.video_url"
                        controls
                        class="aspect-video w-full rounded-2xl border border-slate-200 bg-slate-950"
                    >
                        <source :src="assignment.training.video_url" />
                    </video>

                    <div v-else class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-8 text-center">
                        <Lucide icon="Video" class="mx-auto h-10 w-10 text-slate-400" />
                        <p class="mt-4 text-sm text-slate-500">This training video opens in a separate tab.</p>
                    </div>

                    <a
                        v-if="assignment.training.video_url"
                        :href="assignment.training.video_url"
                        target="_blank"
                        class="inline-flex items-center gap-2 text-sm font-medium text-primary hover:underline"
                    >
                        <Lucide icon="ExternalLink" class="h-4 w-4" />
                        Open video in new tab
                    </a>
                </div>

                <div v-else-if="assignment.training.content_type === 'url'" class="mt-5 rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-8 text-center">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-primary/10">
                        <Lucide icon="ExternalLink" class="h-7 w-7 text-primary" />
                    </div>
                    <h3 class="mt-4 text-lg font-semibold text-slate-800">External Training Link</h3>
                    <p class="mx-auto mt-2 max-w-xl text-sm text-slate-500">
                        This training is hosted outside the platform. Open it in a new tab, review the material, and then come back here to complete it.
                    </p>
                    <a
                        v-if="assignment.training.url"
                        :href="assignment.training.url"
                        target="_blank"
                        class="mt-5 inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white transition hover:bg-primary/90"
                    >
                        <Lucide icon="ExternalLink" class="h-4 w-4" />
                        Open Training
                    </a>
                </div>

                <div v-else-if="assignment.training.content_type === 'file'" class="mt-5 space-y-4">
                    <div
                        v-if="assignment.training.documents.length"
                        v-for="document in assignment.training.documents"
                        :key="document.id"
                        class="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-slate-50/70 p-4 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div class="flex min-w-0 items-start gap-3">
                            <div class="rounded-xl p-3" :class="fileTone(document.file_type)">
                                <Lucide :icon="fileIcon(document.file_type)" class="h-5 w-5" />
                            </div>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-slate-800">{{ document.file_name }}</p>
                                <p class="mt-1 text-xs text-slate-500">
                                    {{ document.size_label }}<span v-if="document.created_at"> · {{ document.created_at }}</span>
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <a
                                :href="document.preview_url"
                                target="_blank"
                                class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-600 transition hover:bg-slate-100"
                            >
                                <Lucide icon="Eye" class="h-4 w-4" />
                                Preview
                            </a>
                            <a
                                :href="document.download_url"
                                class="inline-flex items-center gap-2 rounded-lg bg-primary px-3 py-2 text-sm font-medium text-white transition hover:bg-primary/90"
                            >
                                <Lucide icon="Download" class="h-4 w-4" />
                                Download
                            </a>
                        </div>
                    </div>

                    <div v-else class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-8 text-center">
                        <Lucide icon="Files" class="mx-auto h-10 w-10 text-slate-400" />
                        <p class="mt-4 text-sm text-slate-500">No training documents were attached to this assignment.</p>
                    </div>
                </div>

                <div v-else class="mt-5 rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-8 text-center">
                    <Lucide icon="FileQuestion" class="mx-auto h-10 w-10 text-slate-400" />
                    <p class="mt-4 text-sm text-slate-500">This training does not have content configured yet.</p>
                </div>
            </div>

            <div
                v-if="assignment.completion_notes"
                class="box box--stacked border border-success/20 bg-success/5 p-6"
            >
                <div class="flex items-center gap-2">
                    <Lucide icon="MessageSquare" class="h-5 w-5 text-success" />
                    <h2 class="text-base font-semibold text-slate-800">Completion Notes</h2>
                </div>
                <p class="mt-4 whitespace-pre-line text-sm leading-6 text-slate-600">{{ assignment.completion_notes }}</p>
            </div>
        </div>

        <div class="col-span-12 xl:col-span-4 space-y-6">
            <div class="box box--stacked p-6">
                <h2 class="text-base font-semibold text-slate-800">Assignment Summary</h2>
                <div class="mt-5 space-y-4">
                    <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-4">
                        <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Assigned Date</p>
                        <p class="mt-2 text-sm font-semibold text-slate-800">{{ assignment.assigned_date || 'N/A' }}</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-4">
                        <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Due Date</p>
                        <p class="mt-2 text-sm font-semibold text-slate-800">{{ assignment.due_date || 'No due date' }}</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-4">
                        <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Completed Date</p>
                        <p class="mt-2 text-sm font-semibold text-slate-800">{{ assignment.completed_date || 'Not completed' }}</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-4">
                        <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Content Type</p>
                        <p class="mt-2 text-sm font-semibold text-slate-800">{{ contentTypeLabel(assignment.training.content_type) }}</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-4">
                        <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Created By</p>
                        <p class="mt-2 text-sm font-semibold text-slate-800">{{ assignment.training.creator_name || 'N/A' }}</p>
                    </div>
                </div>
            </div>

            <div class="box box--stacked p-6">
                <h2 class="text-base font-semibold text-slate-800">Quick Actions</h2>
                <div class="mt-5 flex flex-col gap-3">
                    <Button
                        v-if="assignment.can_start"
                        variant="primary"
                        class="justify-center gap-2"
                        @click="startTraining"
                    >
                        <Lucide icon="Play" class="h-4 w-4" />
                        Start Training
                    </Button>
                    <Button
                        v-else-if="assignment.can_complete"
                        variant="primary"
                        class="justify-center gap-2"
                        @click="completionModalOpen = true"
                    >
                        <Lucide icon="CheckCircle2" class="h-4 w-4" />
                        Mark as Complete
                    </Button>
                    <div
                        v-else
                        class="flex items-center justify-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-600"
                    >
                        <Lucide icon="BadgeCheck" class="h-4 w-4" />
                        {{ assignment.status === 'completed' ? 'Training Completed' : 'No pending action' }}
                    </div>

                    <Link :href="route('driver.trainings.index')">
                        <Button variant="outline-secondary" class="w-full justify-center gap-2">
                            <Lucide icon="ArrowLeft" class="h-4 w-4" />
                            Back to Trainings
                        </Button>
                    </Link>
                </div>
            </div>
        </div>
    </div>

    <Dialog :open="completionModalOpen" @close="completionModalOpen = false" size="lg">
        <Dialog.Panel class="w-full max-w-[720px] overflow-hidden">
            <div class="p-6">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-800">Complete Training</h3>
                        <p class="mt-1 text-sm text-slate-500">
                            Confirm that you reviewed the material for <span class="font-medium text-slate-700">{{ assignment.training.title }}</span>.
                        </p>
                    </div>
                    <button type="button" class="text-slate-400 transition hover:text-slate-600" @click="completionModalOpen = false">
                        <Lucide icon="X" class="h-5 w-5" />
                    </button>
                </div>

                <form class="mt-6 space-y-5" @submit.prevent="completeTraining">
                    <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <input
                            v-model="completionForm.confirmed"
                            type="checkbox"
                            class="mt-1 rounded border-slate-300 text-primary focus:ring-primary"
                        >
                        <div>
                            <p class="text-sm font-semibold text-slate-800">I confirm that I completed this training.</p>
                            <p class="mt-1 text-sm text-slate-500">
                                This will mark the assignment as completed and store the date in your training history.
                            </p>
                        </div>
                    </label>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">Notes</label>
                        <textarea
                            v-model="completionForm.notes"
                            rows="4"
                            class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm text-slate-700 shadow-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                            placeholder="Add any notes about what you completed or reviewed..."
                        />
                        <p v-if="completionForm.errors.notes" class="mt-2 text-sm text-danger">{{ completionForm.errors.notes }}</p>
                    </div>

                    <p v-if="completionForm.errors.confirmed" class="text-sm text-danger">{{ completionForm.errors.confirmed }}</p>

                    <div class="flex flex-col-reverse gap-3 border-t border-slate-200 pt-5 sm:flex-row sm:justify-end">
                        <Button type="button" variant="outline-secondary" class="justify-center gap-2" @click="completionModalOpen = false">
                            <Lucide icon="X" class="h-4 w-4" />
                            Cancel
                        </Button>
                        <Button type="submit" variant="primary" class="justify-center gap-2" :disabled="completionForm.processing">
                            <Lucide icon="CheckCircle2" class="h-4 w-4" />
                            {{ completionForm.processing ? 'Saving...' : 'Complete Training' }}
                        </Button>
                    </div>
                </form>
            </div>
        </Dialog.Panel>
    </Dialog>
</template>
