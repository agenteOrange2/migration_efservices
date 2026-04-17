<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import RazeLayout from '@/layouts/RazeLayout.vue'

declare function route(name: string, params?: any): string

defineOptions({ layout: RazeLayout })

interface MediaItem {
    id: number
    label: string
    name: string
    url: string
    mime_type: string | null
    size_label: string
    created_at: string | null
    extension: string
}

const props = defineProps<{
    driver: {
        id: number
        full_name: string
        carrier_name: string | null
        email: string | null
        phone: string | null
        license: {
            number: string
            class: string | null
            state: string | null
            expires: string | null
        } | null
    }
    carrier: {
        id: number
        name: string
        dot_number: string | null
        mc_number: string | null
    } | null
    testing: {
        id: number
        test_type: string
        test_type_label: string
        test_date: string | null
        scheduled_time: string | null
        test_result: string | null
        status: string | null
        administered_by: string | null
        mro: string | null
        requester_name: string | null
        location: string | null
        next_test_due: string | null
        bill_to: string | null
        notes: string | null
        reasons: { active: boolean; label: string }[]
        other_reason_description: string | null
        created_at: string | null
        updated_at: string | null
        created_by: string | null
        updated_by: string | null
        pdf: MediaItem | null
        result_documents: MediaItem[]
        certificate_documents: MediaItem[]
        attachments: MediaItem[]
    }
    history: {
        id: number
        test_date: string | null
        test_type: string
        status: string | null
        test_result: string | null
    }[]
}>()

const uploadForm = useForm({
    results: [] as File[],
})

function onFileChange(event: Event) {
    const input = event.target as HTMLInputElement
    uploadForm.results = input.files ? Array.from(input.files) : []
}

function submitResults() {
    uploadForm.post(route('driver.testing.upload-results', props.testing.id), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            uploadForm.reset()
        },
    })
}

function statusClass(status: string | null) {
    if (status === 'Completed') return 'bg-primary/10 text-primary'
    if (status === 'Pending Review') return 'bg-slate-100 text-slate-700'
    if (status === 'Cancelled') return 'bg-slate-200 text-slate-700'
    return 'bg-slate-100 text-slate-600'
}

function resultClass(result: string | null) {
    if (result === 'Negative') return 'bg-primary/10 text-primary'
    if (result === 'Positive') return 'bg-slate-200 text-slate-700'
    if (result === 'Refusal') return 'bg-slate-100 text-slate-700'
    return 'bg-slate-100 text-slate-500'
}

function fileIcon(extension: string) {
    if (extension === 'pdf') return 'FileText'
    if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(extension)) return 'Image'
    return 'Files'
}
</script>

<template>
    <Head :title="`Test #${testing.id}`" />

    <div class="grid grid-cols-12 gap-6">
        <div class="col-span-12">
            <div class="box box--stacked p-6">
                <div class="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
                    <div class="space-y-4">
                        <Link :href="route('driver.testing.index')" class="inline-flex items-center gap-2 text-sm font-medium text-slate-500 transition hover:text-primary">
                            <Lucide icon="ArrowLeft" class="h-4 w-4" />
                            Back to Tests
                        </Link>

                        <div class="flex flex-wrap items-center gap-3">
                            <h1 class="text-2xl font-bold text-slate-800">{{ testing.test_type_label }}</h1>
                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium" :class="statusClass(testing.status)">
                                {{ testing.status ?? 'Not Set' }}
                            </span>
                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium" :class="resultClass(testing.test_result)">
                                {{ testing.test_result ?? 'Pending' }}
                            </span>
                        </div>

                        <p class="text-sm text-slate-500">
                            Driver: <span class="font-medium text-slate-700">{{ driver.full_name }}</span>
                            <span v-if="driver.carrier_name"> · Carrier: <span class="font-medium text-slate-700">{{ driver.carrier_name }}</span></span>
                        </p>
                    </div>

                    <a v-if="testing.pdf" :href="testing.pdf.url" target="_blank">
                        <Button variant="outline-secondary" class="gap-2">
                            <Lucide icon="FileDown" class="h-4 w-4" />
                            View Authorization PDF
                        </Button>
                    </a>
                </div>
            </div>
        </div>

        <div class="col-span-12 xl:col-span-8 space-y-6">
            <div class="box box--stacked p-6">
                <h2 class="text-base font-semibold text-slate-800">Test Details</h2>

                <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-4"><p class="text-xs uppercase tracking-[0.2em] text-slate-400">Test Date</p><p class="mt-2 text-sm font-semibold text-slate-800">{{ testing.test_date || 'N/A' }}</p></div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-4"><p class="text-xs uppercase tracking-[0.2em] text-slate-400">Scheduled Time</p><p class="mt-2 text-sm font-semibold text-slate-800">{{ testing.scheduled_time || 'Not scheduled' }}</p></div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-4"><p class="text-xs uppercase tracking-[0.2em] text-slate-400">Next Test Due</p><p class="mt-2 text-sm font-semibold text-slate-800">{{ testing.next_test_due || 'Not set' }}</p></div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-4"><p class="text-xs uppercase tracking-[0.2em] text-slate-400">Administered By</p><p class="mt-2 text-sm font-semibold text-slate-800">{{ testing.administered_by || 'N/A' }}</p></div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-4"><p class="text-xs uppercase tracking-[0.2em] text-slate-400">Requested By</p><p class="mt-2 text-sm font-semibold text-slate-800">{{ testing.requester_name || 'N/A' }}</p></div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-4"><p class="text-xs uppercase tracking-[0.2em] text-slate-400">Location</p><p class="mt-2 text-sm font-semibold text-slate-800">{{ testing.location || 'N/A' }}</p></div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-4"><p class="text-xs uppercase tracking-[0.2em] text-slate-400">MRO</p><p class="mt-2 text-sm font-semibold text-slate-800">{{ testing.mro || 'Not provided' }}</p></div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-4"><p class="text-xs uppercase tracking-[0.2em] text-slate-400">Bill To</p><p class="mt-2 text-sm font-semibold text-slate-800">{{ testing.bill_to || 'Not set' }}</p></div>
                </div>

                <div class="mt-5">
                    <p class="text-xs font-medium uppercase tracking-[0.2em] text-slate-400">Reasons</p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <span v-for="reason in testing.reasons" :key="reason.label" class="inline-flex rounded-full bg-primary/10 px-2.5 py-1 text-xs font-medium text-primary">{{ reason.label }}</span>
                        <span v-if="!testing.reasons.length" class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-500">No reason selected</span>
                    </div>
                    <p v-if="testing.other_reason_description" class="mt-3 text-sm text-slate-600"><span class="font-medium text-slate-700">Other reason:</span> {{ testing.other_reason_description }}</p>
                </div>

                <div class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 p-5">
                    <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Notes</p>
                    <p class="mt-3 whitespace-pre-line text-sm leading-6 text-slate-600">{{ testing.notes || 'No notes available for this test.' }}</p>
                </div>
            </div>

            <div class="box box--stacked p-6">
                <div class="flex items-center justify-between gap-4">
                    <h2 class="text-base font-semibold text-slate-800">Upload Results</h2>
                    <span class="text-sm text-slate-500">Accepted: PDF, JPG, PNG, DOC, DOCX</span>
                </div>

                <form class="mt-5 space-y-4" @submit.prevent="submitResults">
                    <div class="rounded-2xl border border-dashed border-primary/30 bg-primary/5 p-6">
                        <label class="flex cursor-pointer flex-col items-center justify-center gap-3 text-center">
                            <div class="rounded-full bg-white p-3 shadow-sm"><Lucide icon="UploadCloud" class="h-6 w-6 text-primary" /></div>
                            <div><p class="text-sm font-medium text-slate-700">Select result files to upload</p><p class="mt-1 text-xs text-slate-500">Each file can be up to 10MB.</p></div>
                            <input type="file" multiple class="hidden" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" @change="onFileChange">
                            <span class="inline-flex rounded-lg border border-primary/20 bg-white px-3 py-2 text-xs font-medium text-primary">Choose Files</span>
                        </label>
                    </div>

                    <div v-if="uploadForm.results.length" class="space-y-2">
                        <div v-for="file in uploadForm.results" :key="file.name + file.size" class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <Lucide icon="FileUp" class="h-4 w-4 text-primary" />
                            <div class="min-w-0 flex-1"><p class="truncate text-sm font-medium text-slate-700">{{ file.name }}</p><p class="text-xs text-slate-400">{{ Math.round(file.size / 1024) }} KB</p></div>
                        </div>
                    </div>

                    <p v-if="uploadForm.errors.results" class="text-sm text-danger">{{ uploadForm.errors.results }}</p>
                    <p v-if="uploadForm.errors['results.0']" class="text-sm text-danger">{{ uploadForm.errors['results.0'] }}</p>

                    <div class="flex justify-end">
                        <Button type="submit" variant="primary" class="gap-2" :disabled="uploadForm.processing || !uploadForm.results.length">
                            <Lucide icon="Upload" class="h-4 w-4" />
                            {{ uploadForm.processing ? 'Uploading...' : 'Upload Results' }}
                        </Button>
                    </div>
                </form>
            </div>

            <div class="box box--stacked p-6">
                <h2 class="text-base font-semibold text-slate-800">Documents</h2>

                <div class="mt-5 space-y-5">
                    <div v-if="testing.pdf">
                        <p class="text-xs font-medium uppercase tracking-[0.2em] text-slate-400">Authorization PDF</p>
                        <div class="mt-3 flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <Lucide icon="FileText" class="h-4 w-4 text-primary" />
                            <div class="min-w-0 flex-1"><p class="truncate text-sm font-medium text-slate-700">{{ testing.pdf.name }}</p><p class="text-xs text-slate-400">{{ testing.pdf.size_label }}</p></div>
                            <a :href="testing.pdf.url" target="_blank" class="text-sm font-medium text-primary hover:underline">Open</a>
                        </div>
                    </div>

                    <div>
                        <p class="text-xs font-medium uppercase tracking-[0.2em] text-slate-400">Result Files</p>
                        <div v-if="testing.result_documents.length" class="mt-3 space-y-2">
                            <div v-for="item in testing.result_documents" :key="item.id" class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                                <Lucide :icon="fileIcon(item.extension)" class="h-4 w-4 text-primary" />
                                <div class="min-w-0 flex-1"><p class="truncate text-sm font-medium text-slate-700">{{ item.name }}</p><p class="text-xs text-slate-400">{{ item.size_label }}<span v-if="item.created_at"> · {{ item.created_at }}</span></p></div>
                                <a :href="item.url" target="_blank" class="text-sm font-medium text-primary hover:underline">Open</a>
                            </div>
                        </div>
                        <p v-else class="mt-3 text-sm text-slate-500">No result files uploaded yet.</p>
                    </div>

                    <div v-if="testing.certificate_documents.length">
                        <p class="text-xs font-medium uppercase tracking-[0.2em] text-slate-400">Certificates</p>
                        <div class="mt-3 space-y-2">
                            <div v-for="item in testing.certificate_documents" :key="item.id" class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                                <Lucide :icon="fileIcon(item.extension)" class="h-4 w-4 text-primary" />
                                <div class="min-w-0 flex-1"><p class="truncate text-sm font-medium text-slate-700">{{ item.name }}</p><p class="text-xs text-slate-400">{{ item.size_label }}</p></div>
                                <a :href="item.url" target="_blank" class="text-sm font-medium text-primary hover:underline">Open</a>
                            </div>
                        </div>
                    </div>

                    <div v-if="testing.attachments.length">
                        <p class="text-xs font-medium uppercase tracking-[0.2em] text-slate-400">Attachments</p>
                        <div class="mt-3 space-y-2">
                            <div v-for="item in testing.attachments" :key="item.id" class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                                <Lucide :icon="fileIcon(item.extension)" class="h-4 w-4 text-primary" />
                                <div class="min-w-0 flex-1"><p class="truncate text-sm font-medium text-slate-700">{{ item.name }}</p><p class="text-xs text-slate-400">{{ item.size_label }}</p></div>
                                <a :href="item.url" target="_blank" class="text-sm font-medium text-primary hover:underline">Open</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12 xl:col-span-4 space-y-6">
            <div class="box box--stacked p-6">
                <h2 class="text-base font-semibold text-slate-800">Driver Information</h2>
                <div class="mt-5 space-y-4">
                    <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-4"><p class="text-xs uppercase tracking-[0.2em] text-slate-400">Email</p><p class="mt-2 text-sm font-semibold text-slate-800">{{ driver.email || 'N/A' }}</p></div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-4"><p class="text-xs uppercase tracking-[0.2em] text-slate-400">Phone</p><p class="mt-2 text-sm font-semibold text-slate-800">{{ driver.phone || 'N/A' }}</p></div>
                    <div v-if="driver.license" class="rounded-xl border border-slate-200 bg-slate-50/70 p-4"><p class="text-xs uppercase tracking-[0.2em] text-slate-400">License</p><p class="mt-2 text-sm font-semibold text-slate-800">{{ driver.license.number }}</p><p class="mt-1 text-xs text-slate-500">Class {{ driver.license.class || 'N/A' }} · {{ driver.license.state || 'N/A' }}</p><p class="mt-1 text-xs text-slate-500">Expires {{ driver.license.expires || 'N/A' }}</p></div>
                </div>
            </div>

            <div v-if="carrier" class="box box--stacked p-6">
                <h2 class="text-base font-semibold text-slate-800">Carrier Information</h2>
                <div class="mt-5 space-y-4">
                    <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-4"><p class="text-xs uppercase tracking-[0.2em] text-slate-400">Carrier</p><p class="mt-2 text-sm font-semibold text-slate-800">{{ carrier.name }}</p></div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-4"><p class="text-xs uppercase tracking-[0.2em] text-slate-400">DOT / MC</p><p class="mt-2 text-sm font-semibold text-slate-800">{{ carrier.dot_number || 'N/A' }} / {{ carrier.mc_number || 'N/A' }}</p></div>
                </div>
            </div>

            <div class="box box--stacked p-6">
                <h2 class="text-base font-semibold text-slate-800">Recent Test History</h2>
                <div class="mt-5 space-y-3">
                    <div v-for="item in history" :key="item.id" class="rounded-xl border border-slate-200 bg-slate-50/70 p-4">
                        <p class="text-sm font-semibold text-slate-800">{{ item.test_type }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ item.test_date || 'No date' }}</p>
                        <div class="mt-2 flex flex-wrap gap-2">
                            <span class="inline-flex rounded-full bg-slate-100 px-2 py-1 text-xs font-medium text-slate-600">{{ item.status || 'Not set' }}</span>
                            <span class="inline-flex rounded-full bg-primary/10 px-2 py-1 text-xs font-medium text-primary">{{ item.test_result || 'Pending' }}</span>
                        </div>
                        <Link :href="route('driver.testing.show', item.id)" class="mt-3 inline-flex text-xs font-medium text-primary hover:underline">Open record</Link>
                    </div>

                    <p v-if="!history.length" class="text-sm text-slate-500">No previous tests on file yet.</p>
                </div>
            </div>
        </div>
    </div>
</template>
