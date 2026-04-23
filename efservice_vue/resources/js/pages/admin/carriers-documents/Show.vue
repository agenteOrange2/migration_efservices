<script setup lang="ts">
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import Lucide from '@/components/Base/Lucide';
import { FormSelect, FormLabel, FormTextarea } from '@/components/Base/Form';
import Button from '@/components/Base/Button';
import RazeLayout from '@/layouts/RazeLayout.vue';

interface DocumentItem {
    id: number;
    type: { id: number; name: string; requirement: boolean };
    status_name: string;
    has_file: boolean;
    file_url: string | null;
    notes: string | null;
    updated_at: string;
}

interface DocumentTypeItem {
    id: number;
    name: string;
    requirement: boolean;
}

const props = defineProps<{
    carrier: {
        id: number;
        name: string;
        slug: string;
        mc_number: string | null;
        dot_number: string | null;
    };
    documents: DocumentItem[];
    progress: {
        total: number;
        approved: number;
        percentage: number;
        status: string;
    };
    documentTypes: DocumentTypeItem[];
}>();

defineOptions({ layout: RazeLayout });

const uploadingTypeId = ref<number | null>(null);

const uploadForm = useForm({
    document: null as File | null,
    notes: '',
});

function startUpload(typeId: number) {
    uploadingTypeId.value = typeId;
    uploadForm.reset();
}

function cancelUpload() {
    uploadingTypeId.value = null;
    uploadForm.reset();
}

function handleFile(e: Event) {
    const target = e.target as HTMLInputElement;
    if (target.files?.[0]) uploadForm.document = target.files[0];
}

function submitUpload() {
    if (!uploadingTypeId.value) return;
    uploadForm.post(
        route('admin.carriers-documents.upload', {
            carrier: props.carrier.slug,
            documentType: uploadingTypeId.value,
        }),
        {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => cancelUpload(),
        },
    );
}

function updateStatus(docId: number, newStatus: string) {
    router.put(
        route('admin.carriers-documents.update-doc', {
            carrier: props.carrier.slug,
            document: docId,
        }),
        {
            status: parseInt(newStatus),
        },
        { preserveScroll: true },
    );
}

function statusColor(name: string): string {
    if (name === 'Approved' || name === 'approved')
        return 'bg-success/10 text-success border-success/20';
    if (name === 'In Process' || name === 'in_process')
        return 'bg-info/10 text-info border-info/20';
    if (name === 'Pending' || name === 'pending')
        return 'bg-warning/10 text-warning border-warning/20';
    return 'bg-danger/10 text-danger border-danger/20';
}

function progressTone() {
    if (props.progress.percentage >= 100) {
        return {
            card: 'border-success/20 bg-success/5',
            text: 'text-success',
            bar: 'bg-success',
            iconWrap: 'bg-success/10 border-success/20',
            icon: 'text-success',
        };
    }

    if (props.progress.percentage >= 50) {
        return {
            card: 'border-warning/20 bg-warning/5',
            text: 'text-warning',
            bar: 'bg-warning',
            iconWrap: 'bg-warning/10 border-warning/20',
            icon: 'text-warning',
        };
    }

    return {
        card: 'border-danger/20 bg-danger/5',
        text: 'text-danger',
        bar: 'bg-danger',
        iconWrap: 'bg-danger/10 border-danger/20',
        icon: 'text-danger',
    };
}

function requirementTone(required: boolean) {
    return required
        ? 'bg-danger/10 text-danger border-danger/20'
        : 'bg-slate-100 text-slate-500 border-slate-200';
}

function uploadButtonLabel(typeId: number) {
    return uploadingTypeId === typeId ? 'Uploading...' : 'Upload';
}
</script>

<template>
    <Head :title="`Documents: ${carrier.name}`" />

    <div class="grid grid-cols-12 gap-x-6 gap-y-8">
        <div class="col-span-12">
            <div class="box box--stacked p-6">
                <div
                    class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between"
                >
                    <div class="flex items-start gap-4">
                        <div
                            class="rounded-2xl border border-primary/20 bg-primary/10 p-3"
                        >
                            <Lucide
                                icon="FolderCheck"
                                class="h-8 w-8 text-primary"
                            />
                        </div>
                        <div>
                            <div
                                class="text-xs font-semibold tracking-wide text-slate-500 uppercase"
                            >
                                Carrier Documents
                            </div>
                            <h1 class="mt-1 text-2xl font-bold text-slate-800">
                                {{ carrier.name }}
                            </h1>
                            <div
                                class="mt-2 flex flex-wrap items-center gap-2 text-sm text-slate-500"
                            >
                                <span
                                    v-if="carrier.mc_number"
                                    class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-3 py-1"
                                >
                                    <Lucide
                                        icon="BadgeInfo"
                                        class="h-3.5 w-3.5 text-slate-500"
                                    />
                                    MC: {{ carrier.mc_number }}
                                </span>
                                <span
                                    v-if="carrier.dot_number"
                                    class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-3 py-1"
                                >
                                    <Lucide
                                        icon="ShieldCheck"
                                        class="h-3.5 w-3.5 text-slate-500"
                                    />
                                    DOT: {{ carrier.dot_number }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <Link :href="route('admin.carriers-documents.index')">
                            <Button
                                variant="outline-secondary"
                                class="flex items-center gap-2"
                            >
                                <Lucide icon="ArrowLeft" class="h-4 w-4" />
                                Back
                            </Button>
                        </Link>
                        <Link
                            :href="route('admin.carriers.show', carrier.slug)"
                        >
                            <Button
                                variant="outline-primary"
                                class="flex items-center gap-2"
                            >
                                <Lucide icon="Building2" class="h-4 w-4" />
                                View Carrier
                            </Button>
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12">
            <div class="grid grid-cols-1 gap-4 xl:grid-cols-4">
                <div
                    class="box box--stacked rounded-2xl border p-5"
                    :class="progressTone().card"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="rounded-xl border p-2.5"
                            :class="progressTone().iconWrap"
                        >
                            <Lucide
                                icon="Gauge"
                                class="h-5 w-5"
                                :class="progressTone().icon"
                            />
                        </div>
                        <div>
                            <div class="text-sm text-slate-500">Completion</div>
                            <div
                                class="mt-1 text-2xl font-semibold"
                                :class="progressTone().text"
                            >
                                {{ Math.round(progress.percentage) }}%
                            </div>
                        </div>
                    </div>
                    <div class="mt-4 h-2.5 w-full rounded-full bg-slate-100">
                        <div
                            class="h-2.5 rounded-full transition-all"
                            :class="progressTone().bar"
                            :style="{ width: progress.percentage + '%' }"
                        />
                    </div>
                </div>

                <div
                    class="box box--stacked rounded-2xl border border-primary/20 bg-primary/5 p-5"
                >
                    <div class="text-sm text-slate-500">Required Types</div>
                    <div class="mt-1 text-2xl font-semibold text-primary">
                        {{ progress.total }}
                    </div>
                </div>

                <div
                    class="box box--stacked rounded-2xl border border-success/20 bg-success/5 p-5"
                >
                    <div class="text-sm text-slate-500">Approved</div>
                    <div class="mt-1 text-2xl font-semibold text-success">
                        {{ progress.approved }}
                    </div>
                </div>

                <div
                    class="box box--stacked rounded-2xl border border-info/20 bg-info/5 p-5"
                >
                    <div class="text-sm text-slate-500">Overall Status</div>
                    <div class="mt-2">
                        <span
                            class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold tracking-wide uppercase"
                            :class="statusColor(progress.status)"
                        >
                            {{ progress.status }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12">
            <div class="box box--stacked overflow-hidden">
                <div class="border-b border-slate-200/70 px-5 py-4">
                    <h2 class="text-base font-semibold text-slate-800">
                        Documents Checklist
                    </h2>
                    <p class="mt-1 text-sm text-slate-500">
                        Review uploads, update statuses and keep the carrier
                        document set aligned with the admin workflow.
                    </p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-slate-50/80">
                                <th
                                    class="px-5 py-3 text-xs font-medium text-slate-500 uppercase"
                                >
                                    Document
                                </th>
                                <th
                                    class="px-5 py-3 text-xs font-medium text-slate-500 uppercase"
                                >
                                    Required
                                </th>
                                <th
                                    class="px-5 py-3 text-xs font-medium text-slate-500 uppercase"
                                >
                                    File
                                </th>
                                <th
                                    class="px-5 py-3 text-xs font-medium text-slate-500 uppercase"
                                >
                                    Status
                                </th>
                                <th
                                    class="px-5 py-3 text-xs font-medium text-slate-500 uppercase"
                                >
                                    Notes
                                </th>
                                <th
                                    class="px-5 py-3 text-xs font-medium text-slate-500 uppercase"
                                >
                                    Updated
                                </th>
                                <th
                                    class="px-5 py-3 text-center text-xs font-medium text-slate-500 uppercase"
                                >
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="doc in documents"
                                :key="doc.id"
                                class="border-b border-slate-100 transition hover:bg-slate-50/50"
                            >
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="flex h-10 w-10 items-center justify-center rounded-xl border"
                                            :class="
                                                doc.has_file
                                                    ? 'border-success/20 bg-success/10'
                                                    : 'border-slate-200 bg-slate-100'
                                            "
                                        >
                                            <Lucide
                                                :icon="
                                                    doc.has_file
                                                        ? 'FileCheck2'
                                                        : 'FileText'
                                                "
                                                class="h-4.5 w-4.5"
                                                :class="
                                                    doc.has_file
                                                        ? 'text-success'
                                                        : 'text-slate-400'
                                                "
                                            />
                                        </div>
                                        <div>
                                            <div
                                                class="font-medium text-slate-700"
                                            >
                                                {{ doc.type.name }}
                                            </div>
                                            <div
                                                class="mt-1 text-xs text-slate-400"
                                            >
                                                Document type #{{ doc.type.id }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <span
                                        class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold"
                                        :class="
                                            requirementTone(
                                                doc.type.requirement,
                                            )
                                        "
                                    >
                                        {{
                                            doc.type.requirement ? 'Yes' : 'No'
                                        }}
                                    </span>
                                </td>
                                <td class="px-5 py-4">
                                    <a
                                        v-if="doc.has_file"
                                        :href="doc.file_url ?? '#'"
                                        target="_blank"
                                        class="inline-flex items-center gap-1.5 text-sm font-medium text-primary hover:underline"
                                    >
                                        <Lucide
                                            icon="ExternalLink"
                                            class="h-3.5 w-3.5"
                                        />
                                        Open file
                                    </a>
                                    <span v-else class="text-xs text-slate-400"
                                        >Not uploaded</span
                                    >
                                </td>
                                <td class="px-5 py-4">
                                    <span
                                        class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold"
                                        :class="statusColor(doc.status_name)"
                                    >
                                        {{ doc.status_name }}
                                    </span>
                                </td>
                                <td
                                    class="max-w-[240px] px-5 py-4 text-xs text-slate-500"
                                >
                                    <div class="truncate">
                                        {{ doc.notes ?? 'No notes' }}
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-sm text-slate-500">
                                    {{ doc.updated_at }}
                                </td>
                                <td class="px-5 py-4">
                                    <div
                                        class="flex items-center justify-center gap-2"
                                    >
                                        <Button
                                            variant="outline-primary"
                                            class="flex items-center gap-2 !px-3 !py-2 text-xs"
                                            @click="startUpload(doc.type.id)"
                                        >
                                            <Lucide
                                                icon="Upload"
                                                class="h-3.5 w-3.5"
                                            />
                                            {{ uploadButtonLabel(doc.type.id) }}
                                        </Button>
                                        <FormSelect
                                            @change="
                                                (e) =>
                                                    updateStatus(
                                                        doc.id,
                                                        (
                                                            e.target as HTMLSelectElement
                                                        ).value,
                                                    )
                                            "
                                            class="min-w-[150px] text-xs"
                                        >
                                            <option value="" disabled selected>
                                                Change status
                                            </option>
                                            <option value="0">Pending</option>
                                            <option value="1">Approved</option>
                                            <option value="2">Rejected</option>
                                            <option value="3">
                                                In Process
                                            </option>
                                        </FormSelect>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="documents.length === 0">
                                <td
                                    colspan="7"
                                    class="px-5 py-14 text-center text-slate-400"
                                >
                                    <Lucide
                                        icon="Inbox"
                                        class="mx-auto mb-3 h-12 w-12 text-slate-300"
                                    />
                                    <p class="font-medium">
                                        No document rows found for this carrier
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <Teleport to="body">
            <div
                v-if="uploadingTypeId !== null"
                class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-sm"
                @click.self="cancelUpload"
            >
                <div
                    class="box box--stacked w-full max-w-md rounded-2xl p-6 shadow-2xl"
                >
                    <div
                        class="flex items-start justify-between gap-4 border-b border-slate-200/70 pb-4"
                    >
                        <div class="flex items-start gap-3">
                            <div
                                class="rounded-xl border border-primary/20 bg-primary/10 p-2.5"
                            >
                                <Lucide
                                    icon="UploadCloud"
                                    class="h-5 w-5 text-primary"
                                />
                            </div>
                            <div>
                                <h3
                                    class="text-lg font-semibold text-slate-800"
                                >
                                    Upload Document
                                </h3>
                                <p class="mt-1 text-sm text-slate-500">
                                    Add a new file and optional admin notes for
                                    this carrier document.
                                </p>
                            </div>
                        </div>
                        <button
                            @click="cancelUpload"
                            class="rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600"
                        >
                            <Lucide icon="X" class="h-5 w-5" />
                        </button>
                    </div>

                    <form @submit.prevent="submitUpload" class="space-y-4">
                        <div>
                            <FormLabel>File *</FormLabel>
                            <input
                                type="file"
                                accept=".pdf,.jpg,.jpeg,.png"
                                @change="handleFile"
                                class="w-full rounded-xl border border-dashed border-primary/25 bg-primary/5 px-3 py-3 text-sm text-slate-600 file:mr-4 file:cursor-pointer file:rounded-lg file:border-0 file:bg-primary file:px-4 file:py-2 file:text-white hover:file:bg-primary/90"
                            />
                            <div
                                v-if="uploadForm.errors.document"
                                class="mt-1 text-xs text-red-500"
                            >
                                {{ uploadForm.errors.document }}
                            </div>
                        </div>
                        <div>
                            <FormLabel>Notes (optional)</FormLabel>
                            <FormTextarea
                                v-model="uploadForm.notes"
                                rows="3"
                                placeholder="Add context for approvals, expirations or review notes..."
                            />
                        </div>
                        <div class="flex justify-end gap-3">
                            <Button
                                type="button"
                                variant="outline-secondary"
                                @click="cancelUpload"
                            >
                                Cancel
                            </Button>
                            <Button
                                type="submit"
                                variant="primary"
                                :disabled="
                                    uploadForm.processing ||
                                    !uploadForm.document
                                "
                                class="flex items-center gap-2"
                            >
                                <Lucide icon="Upload" class="h-4 w-4" />
                                Upload
                            </Button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </div>
</template>
