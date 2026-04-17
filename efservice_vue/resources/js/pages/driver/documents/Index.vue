<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import RazeLayout from '@/layouts/RazeLayout.vue'

declare function route(name: string, params?: any): string

defineOptions({ layout: RazeLayout })

interface RequirementRow {
    collection: string
    label: string
    uploaded_count: number
    is_complete: boolean
    upload_url: string
}

interface DocumentRow {
    id: number
    name: string
    url: string
    size: string | null
    mime_type: string | null
    created_at: string | null
    collection_name: string
    status: string | null
    expiry_date: string | null
    notes: string | null
    can_delete: boolean
    delete_url: string | null
}

const props = defineProps<{
    driver: {
        id: number
        full_name: string
        carrier_name: string | null
    }
    pendingMode: boolean
    stats: {
        total_documents: number
        categories_count: number
        direct_uploads: number
        deletable_documents: number
    }
    requirements: RequirementRow[]
    categories: {
        label: string
        count: number
        documents: DocumentRow[]
    }[]
}>()

function fileIcon(mimeType: string | null) {
    if (mimeType?.includes('pdf')) return 'FileText'
    if (mimeType?.includes('image')) return 'Image'
    return 'File'
}

function fileTone(mimeType: string | null) {
    if (mimeType?.includes('pdf')) return 'bg-slate-200 text-slate-700'
    if (mimeType?.includes('image')) return 'bg-primary/10 text-primary'
    return 'bg-slate-100 text-slate-600'
}

function statusClass(status: string | null) {
    if (status === 'approved') return 'bg-primary/10 text-primary'
    if (status === 'rejected') return 'bg-slate-200 text-slate-700'
    if (status === 'expired') return 'bg-slate-200 text-slate-700'
    if (status === 'pending') return 'bg-slate-100 text-slate-700'
    return 'bg-slate-100 text-slate-600'
}

function titleCase(value: string) {
    return value.replace(/_/g, ' ').replace(/\b\w/g, (char) => char.toUpperCase())
}

function deleteDocument(document: DocumentRow) {
    if (!document.can_delete || !document.delete_url) return
    if (!confirm(`Delete "${document.name}"? This action cannot be undone.`)) return
    router.delete(document.delete_url, { preserveScroll: true })
}
</script>

<template>
    <Head title="My Documents" />

    <div class="grid grid-cols-12 gap-6">
        <div v-if="pendingMode" class="col-span-12">
            <div class="box box--stacked border border-primary/20 bg-primary/5 p-6">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex items-start gap-4">
                        <div class="rounded-2xl bg-primary/10 p-3">
                            <Lucide icon="TriangleAlert" class="h-7 w-7 text-primary" />
                        </div>
                        <div>
                            <h2 class="text-xl font-semibold text-slate-800">Documents Required</h2>
                            <p class="mt-1 text-sm text-slate-600">
                                Please upload the missing required documents to complete your registration and unlock the rest of the driver area.
                            </p>
                        </div>
                    </div>

                    <Link :href="route('driver.documents.create')">
                        <Button variant="primary" class="gap-2">
                            <Lucide icon="Upload" class="h-4 w-4" />
                            Upload Documents
                        </Button>
                    </Link>
                </div>
            </div>
        </div>

        <div class="col-span-12">
            <div class="box box--stacked p-6">
                <div class="flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between">
                    <div class="flex items-start gap-4">
                        <div class="rounded-2xl border border-primary/20 bg-primary/10 p-3">
                            <Lucide icon="FileText" class="h-8 w-8 text-primary" />
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-slate-800">My Documents</h1>
                            <p class="mt-1 text-slate-500">Keep your direct uploads and compliance files organized in one place.</p>
                            <p class="mt-2 text-sm text-slate-500">
                                Driver: <span class="font-medium text-slate-700">{{ driver.full_name }}</span>
                                <span v-if="driver.carrier_name"> · Carrier: <span class="font-medium text-slate-700">{{ driver.carrier_name }}</span></span>
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <Link :href="route('driver.documents.create')">
                            <Button variant="outline-secondary" class="gap-2">
                                <Lucide icon="Upload" class="h-4 w-4" />
                                Upload
                            </Button>
                        </Link>
                        <Link v-if="stats.total_documents > 0" :href="route('driver.documents.download-all')">
                            <Button variant="primary" class="gap-2">
                                <Lucide icon="Download" class="h-4 w-4" />
                                Download All
                            </Button>
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="box box--stacked p-5">
                <p class="text-sm text-slate-500">Total Documents</p>
                <p class="mt-2 text-3xl font-semibold text-slate-800">{{ stats.total_documents }}</p>
            </div>
            <div class="box box--stacked p-5">
                <p class="text-sm text-slate-500">Categories</p>
                <p class="mt-2 text-3xl font-semibold text-slate-800">{{ stats.categories_count }}</p>
            </div>
            <div class="box box--stacked p-5">
                <p class="text-sm text-slate-500">Direct Uploads</p>
                <p class="mt-2 text-3xl font-semibold text-slate-800">{{ stats.direct_uploads }}</p>
            </div>
            <div class="box box--stacked p-5">
                <p class="text-sm text-slate-500">Can Be Deleted</p>
                <p class="mt-2 text-3xl font-semibold text-slate-800">{{ stats.deletable_documents }}</p>
            </div>
        </div>

        <div class="col-span-12">
            <div class="box box--stacked p-6">
                <div class="mb-5 flex items-center gap-3">
                    <Lucide icon="ShieldCheck" class="h-5 w-5 text-primary" />
                    <h2 class="text-base font-semibold text-slate-800">Quick Upload Categories</h2>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-5">
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

                        <Link :href="requirement.upload_url" class="mt-4 inline-flex items-center gap-2 text-sm font-medium text-primary hover:underline">
                            <Lucide icon="Upload" class="h-4 w-4" />
                            Upload to {{ requirement.label }}
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="categories.length" class="col-span-12 space-y-6">
            <div
                v-for="category in categories"
                :key="category.label"
                class="box box--stacked p-6"
            >
                <div class="mb-5 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="rounded-2xl bg-primary/10 p-2.5">
                            <Lucide icon="FolderOpen" class="h-5 w-5 text-primary" />
                        </div>
                        <div>
                            <h2 class="text-base font-semibold text-slate-800">{{ category.label }}</h2>
                            <p class="text-sm text-slate-500">{{ category.count }} document<span v-if="category.count !== 1">s</span></p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <div
                        v-for="document in category.documents"
                        :key="document.id"
                        class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4"
                    >
                        <div class="flex items-start gap-3">
                            <div class="rounded-xl p-3" :class="fileTone(document.mime_type)">
                                <Lucide :icon="fileIcon(document.mime_type) as any" class="h-5 w-5" />
                            </div>

                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold text-slate-800" :title="document.name">{{ document.name }}</p>
                                <p class="mt-1 text-xs text-slate-500">
                                    {{ document.size || 'Unknown size' }}
                                    <span v-if="document.created_at"> · {{ document.created_at }}</span>
                                </p>

                                <div class="mt-2 flex flex-wrap gap-2">
                                    <span v-if="document.status" class="rounded-full px-2.5 py-1 text-xs font-medium" :class="statusClass(document.status)">
                                        {{ titleCase(document.status) }}
                                    </span>
                                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">
                                        {{ titleCase(document.collection_name) }}
                                    </span>
                                </div>

                                <p v-if="document.expiry_date" class="mt-2 text-xs text-slate-500">
                                    Expires {{ document.expiry_date }}
                                </p>
                                <p v-if="document.notes" class="mt-2 text-xs text-slate-500">
                                    {{ document.notes }}
                                </p>
                            </div>
                        </div>

                        <div class="mt-4 flex flex-wrap items-center gap-2 border-t border-slate-200 pt-4">
                            <a
                                :href="document.url"
                                target="_blank"
                                class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-600 transition hover:bg-slate-100"
                            >
                                <Lucide icon="Eye" class="h-4 w-4" />
                                View
                            </a>
                            <a
                                :href="document.url"
                                download
                                class="inline-flex items-center gap-2 rounded-lg bg-primary px-3 py-2 text-sm font-medium text-white transition hover:bg-primary/90"
                            >
                                <Lucide icon="Download" class="h-4 w-4" />
                                Download
                            </a>
                            <Button
                                v-if="document.can_delete"
                                variant="outline-secondary"
                                class="gap-2"
                                @click="deleteDocument(document)"
                            >
                                <Lucide icon="Trash2" class="h-4 w-4" />
                                Delete
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div v-else class="col-span-12">
            <div class="box box--stacked p-10 text-center">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-slate-100">
                    <Lucide icon="FileText" class="h-8 w-8 text-slate-400" />
                </div>
                <h2 class="mt-4 text-lg font-semibold text-slate-800">No documents yet</h2>
                <p class="mt-2 text-sm text-slate-500">Upload your first compliance document to start building your library.</p>
                <Link :href="route('driver.documents.create')" class="mt-5 inline-flex">
                    <Button variant="primary" class="gap-2">
                        <Lucide icon="Upload" class="h-4 w-4" />
                        Upload Your First Document
                    </Button>
                </Link>
            </div>
        </div>
    </div>
</template>
