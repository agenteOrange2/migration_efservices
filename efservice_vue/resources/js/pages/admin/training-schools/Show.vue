<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import RazeLayout from '@/layouts/RazeLayout.vue'

declare function route(name: string, params?: any): string

defineOptions({ layout: RazeLayout })

const props = defineProps<{
    school: {
        id: number
        school_name: string
        city: string | null
        state: string | null
        date_start: string | null
        date_end: string | null
        graduated: boolean
        subject_to_safety_regulations: boolean
        performed_safety_functions: boolean
        training_skills: string[]
        created_at: string | null
        updated_at: string | null
        driver: { id: number; name: string; email?: string | null; phone?: string | null } | null
        carrier: { id: number; name: string } | null
        documents: { id: number; file_name: string; file_type: string; size_label: string; preview_url: string; created_at_display: string | null }[]
    }
}>()
</script>

<template>
    <Head :title="school.school_name" />

    <div class="grid grid-cols-12 gap-y-8 gap-x-6">
        <div class="col-span-12">
            <div class="box box--stacked p-6">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-xl font-bold text-slate-800">{{ school.school_name }}</h1>
                        <p class="text-sm text-slate-500 mt-0.5">{{ [school.city, school.state].filter(Boolean).join(', ') || 'Training school details' }}</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <Link :href="route('admin.training-schools.documents.show', school.id)">
                            <Button variant="outline-primary" class="flex items-center gap-2">
                                <Lucide icon="Files" class="w-4 h-4" />
                                Documents
                            </Button>
                        </Link>
                        <Link :href="route('admin.training-schools.edit', school.id)">
                            <Button variant="primary" class="flex items-center gap-2">
                                <Lucide icon="PenLine" class="w-4 h-4" />
                                Edit
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

        <div class="col-span-12 lg:col-span-8 space-y-6">
            <div class="box box--stacked p-6">
                <h2 class="text-sm font-semibold text-slate-700 mb-5 flex items-center gap-2">
                    <Lucide icon="GraduationCap" class="w-4 h-4 text-primary" />
                    Training School Details
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs text-slate-500">Start Date</p>
                        <p class="mt-1 text-sm font-medium text-slate-800">{{ school.date_start ?? 'N/A' }}</p>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs text-slate-500">End Date</p>
                        <p class="mt-1 text-sm font-medium text-slate-800">{{ school.date_end ?? 'N/A' }}</p>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs text-slate-500">Graduation Status</p>
                        <span class="mt-2 inline-flex rounded-full px-2.5 py-1 text-xs font-medium" :class="school.graduated ? 'bg-primary/10 text-primary' : 'bg-slate-100 text-slate-600'">
                            {{ school.graduated ? 'Graduated' : 'In Progress' }}
                        </span>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs text-slate-500">Safety Regulations</p>
                        <p class="mt-1 text-sm font-medium text-slate-800">{{ school.subject_to_safety_regulations ? 'Subject to regulations' : 'Not subject' }}</p>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4 md:col-span-2">
                        <p class="text-xs text-slate-500">Safety Functions</p>
                        <p class="mt-1 text-sm font-medium text-slate-800">{{ school.performed_safety_functions ? 'Performed safety functions' : 'Did not perform safety functions' }}</p>
                    </div>
                </div>
            </div>

            <div class="box box--stacked p-6">
                <h2 class="text-sm font-semibold text-slate-700 mb-4 flex items-center gap-2">
                    <Lucide icon="Award" class="w-4 h-4 text-primary" />
                    Training Skills
                </h2>
                <div v-if="school.training_skills.length" class="flex flex-wrap gap-2">
                    <span v-for="skill in school.training_skills" :key="skill" class="inline-flex rounded-full bg-primary/10 px-3 py-1 text-xs font-medium text-primary">
                        {{ skill.replaceAll('_', ' ') }}
                    </span>
                </div>
                <p v-else class="text-sm text-slate-400">No skills were recorded for this training school.</p>
            </div>

            <div class="box box--stacked p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-sm font-semibold text-slate-700 flex items-center gap-2">
                        <Lucide icon="Paperclip" class="w-4 h-4 text-primary" />
                        Documents
                    </h2>
                    <Link :href="route('admin.training-schools.documents.show', school.id)" class="text-sm text-primary hover:underline">Open documents view</Link>
                </div>
                <div v-if="school.documents.length" class="space-y-2">
                    <div v-for="document in school.documents" :key="document.id" class="flex items-center justify-between rounded-lg border border-slate-200 bg-slate-50 px-3 py-3">
                        <div class="min-w-0">
                            <a :href="document.preview_url" target="_blank" class="block truncate text-sm font-medium text-primary hover:underline">{{ document.file_name }}</a>
                            <p class="text-xs text-slate-500">{{ document.size_label }} · {{ document.created_at_display }}</p>
                        </div>
                        <a :href="document.preview_url" target="_blank" class="p-1.5 text-slate-400 hover:text-primary transition">
                            <Lucide icon="Eye" class="w-4 h-4" />
                        </a>
                    </div>
                </div>
                <p v-else class="text-sm text-slate-400">No documents uploaded yet.</p>
            </div>
        </div>

        <div class="col-span-12 lg:col-span-4 space-y-6">
            <div class="box box--stacked p-6">
                <h2 class="text-sm font-semibold text-slate-700 mb-4 flex items-center gap-2">
                    <Lucide icon="User" class="w-4 h-4 text-primary" />
                    Driver Information
                </h2>
                <div class="space-y-3">
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs text-slate-500">Driver</p>
                        <p class="mt-1 text-sm font-medium text-slate-800">{{ school.driver?.name ?? 'N/A' }}</p>
                    </div>
                    <div v-if="school.driver?.email" class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs text-slate-500">Email</p>
                        <p class="mt-1 text-sm font-medium text-slate-800">{{ school.driver.email }}</p>
                    </div>
                    <div v-if="school.driver?.phone" class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs text-slate-500">Phone</p>
                        <p class="mt-1 text-sm font-medium text-slate-800">{{ school.driver.phone }}</p>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs text-slate-500">Carrier</p>
                        <p class="mt-1 text-sm font-medium text-slate-800">{{ school.carrier?.name ?? 'N/A' }}</p>
                    </div>
                </div>
            </div>

            <div class="box box--stacked p-6">
                <h2 class="text-sm font-semibold text-slate-700 mb-4 flex items-center gap-2">
                    <Lucide icon="Clock3" class="w-4 h-4 text-primary" />
                    Record Info
                </h2>
                <div class="space-y-3 text-sm text-slate-600">
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs text-slate-500">Created</p>
                        <p class="mt-1 font-medium text-slate-800">{{ school.created_at ?? 'N/A' }}</p>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs text-slate-500">Updated</p>
                        <p class="mt-1 font-medium text-slate-800">{{ school.updated_at ?? 'N/A' }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
