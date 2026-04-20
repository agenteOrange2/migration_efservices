<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import RazeLayout from '@/layouts/RazeLayout.vue'

declare function route(name: string, params?: any): string

defineOptions({ layout: RazeLayout })

interface Token {
    id: number
    email: string
    created_at: string
    expires_at: string
    verified_at: string | null
    is_verified: boolean
    is_expired: boolean
    document_url: string | null
    signature_url: string | null
}

interface Document {
    id: number
    file_name: string
    original_name: string | null
    uploaded_by: string | null
    uploaded_at: string | null
    url: string
    size_formatted: string
}

interface Verification {
    id: number
    driver_id: number
    driver_name: string
    company_name: string
    email: string | null
    email_sent: boolean
    verification_status: string | null
    verification_date: string | null
    verification_notes: string | null
    positions_held: string | null
    employed_from: string | null
    employed_to: string | null
    subject_to_fmcsr: boolean
    safety_sensitive_function: boolean
    reason_for_leaving: string | null
    attempt_count: number
    max_attempts: number
    can_send_more: boolean
    tokens: Token[]
    documents: Document[]
    latest_token: Token | null
}

const props = defineProps<{ verification: Verification }>()

const activeTab = ref<'overview' | 'documents' | 'signed' | 'history'>('overview')

const tabs = [
    { id: 'overview',   label: 'Overview',           icon: 'FileCheck'  },
    { id: 'documents',  label: 'Documents',          icon: 'Upload'     },
    { id: 'signed',     label: 'Signed Document',    icon: 'FilePen'    },
    { id: 'history',    label: 'Attempt History',    icon: 'History'    },
]

const signedToken = computed(() => props.verification.tokens.find(t => t.is_verified && t.document_url) ?? null)
const hasSignedDocument = computed(() => !!signedToken.value)

const uploadFile = ref<File | null>(null)
const uploadDate = ref(new Date().toISOString().slice(0, 10))
const uploadNotes = ref('')
const uploading = ref(false)

function handleFileChange(e: Event) {
    const input = e.target as HTMLInputElement
    uploadFile.value = input.files?.[0] ?? null
}

function submitUpload() {
    if (!uploadFile.value) return
    uploading.value = true
    const formData = new FormData()
    formData.append('verification_document', uploadFile.value)
    formData.append('verification_date', uploadDate.value)
    formData.append('verification_notes', uploadNotes.value)
    router.post(route('admin.drivers.employment-verification.upload-document', props.verification.id), formData, {
        preserveScroll: true,
        onFinish: () => { uploading.value = false },
    })
}

function deleteDocument(docId: number) {
    if (!confirm('Delete this document? This cannot be undone.')) return
    router.delete(route('admin.drivers.employment-verification.delete-document', [props.verification.id, docId]), { preserveScroll: true })
}

function deleteToken(tokenId: number) {
    if (!confirm('Delete this verification attempt? This cannot be undone.')) return
    router.delete(route('admin.drivers.employment-verification.delete-token', [props.verification.id, tokenId]), { preserveScroll: true })
}

function resend() {
    const remaining = props.verification.max_attempts - props.verification.attempt_count
    if (!confirm(`Resend verification email? (${remaining} attempt(s) remaining)`)) return
    router.post(route('admin.drivers.employment-verification.resend', props.verification.id), {}, { preserveScroll: true })
}

function toggleEmailFlag() {
    const msg = props.verification.email_sent ? 'Mark as NOT sent?' : 'Mark as sent?'
    if (!confirm(msg)) return
    router.post(route('admin.drivers.employment-verification.toggle-email-flag', props.verification.id), {}, { preserveScroll: true })
}

function markVerified() {
    if (!confirm('Mark this verification as Verified?')) return
    router.post(route('admin.drivers.employment-verification.mark-verified', props.verification.id), {}, { preserveScroll: true })
}

function markRejected() {
    if (!confirm('Mark this verification as Rejected?')) return
    router.post(route('admin.drivers.employment-verification.mark-rejected', props.verification.id), {}, { preserveScroll: true })
}

const statusBadgeClass = (status: string | null) => {
    if (status === 'verified') return 'bg-success/10 text-success'
    if (status === 'rejected') return 'bg-danger/10 text-danger'
    return 'bg-warning/10 text-warning'
}

const statusIcon = (status: string | null) => {
    if (status === 'verified') return 'CheckCircle'
    if (status === 'rejected') return 'XCircle'
    return 'Clock'
}

const tokenStatusClass = (token: Token) => {
    if (token.is_verified) return 'bg-success/10 text-success'
    if (token.is_expired) return 'bg-danger/10 text-danger'
    return 'bg-primary/10 text-primary'
}

const tokenStatusLabel = (token: Token) => {
    if (token.is_verified) return 'Verified'
    if (token.is_expired) return 'Expired'
    return 'Active'
}

const tokenStatusIcon = (token: Token) => {
    if (token.is_verified) return 'CheckCircle'
    if (token.is_expired) return 'XCircle'
    return 'Clock'
}

const isDocumentPdf = (url: string) => url.toLowerCase().includes('.pdf')
</script>

<template>
    <Head title="Employment Verification Details" />

    <div class="p-5 sm:p-8 max-w-screen-2xl mx-auto">

        <!-- Header -->
        <div class="box box--stacked p-6 mb-6 border border-primary/10 bg-gradient-to-r from-primary/[0.04] via-white to-white">
            <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-primary/10 rounded-xl border border-primary/20 shadow-sm">
                        <Lucide icon="FileCheck" class="w-7 h-7 text-primary" />
                    </div>
                    <div>
                        <div class="flex items-center gap-3 flex-wrap">
                            <h1 class="text-2xl font-bold text-slate-800">Employment Verification</h1>
                            <span
                                class="inline-flex items-center gap-1.5 text-xs font-semibold px-2.5 py-1 rounded-full capitalize"
                                :class="statusBadgeClass(verification.verification_status)"
                            >
                                <Lucide :icon="statusIcon(verification.verification_status)" class="w-3 h-3" />
                                {{ verification.verification_status || 'Pending' }}
                            </span>
                        </div>
                        <p class="text-slate-500 text-sm mt-1">
                            <span class="font-medium text-slate-700">{{ verification.driver_name }}</span>
                            <span class="mx-1.5 text-slate-300">·</span>
                            {{ verification.company_name }}
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-3 flex-wrap">
                    <Link :href="route('admin.drivers.employment-verification.index')">
                        <Button variant="outline-secondary" size="sm" class="inline-flex items-center gap-2">
                            <Lucide icon="ArrowLeft" class="w-4 h-4" /> Back to List
                        </Button>
                    </Link>
                    <Link :href="route('admin.drivers.show', verification.driver_id)">
                        <Button variant="primary" size="sm" class="inline-flex items-center gap-2 shadow-sm">
                            <Lucide icon="User" class="w-4 h-4" /> View Driver
                        </Button>
                    </Link>
                </div>
            </div>
        </div>

        <!-- Stats row -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <div
                class="box box--stacked p-4 flex items-center gap-3 border"
                :class="verification.email_sent ? 'border-success/10 bg-success/[0.04]' : 'border-warning/10 bg-warning/[0.04]'"
            >
                <div class="p-2 rounded-lg border" :class="verification.email_sent ? 'bg-success/10 border-success/10' : 'bg-warning/10 border-warning/10'">
                    <Lucide icon="Mail" class="w-5 h-5" :class="verification.email_sent ? 'text-success' : 'text-warning'" />
                </div>
                <div>
                    <p class="text-xs text-slate-500">Email</p>
                    <p class="text-sm font-semibold" :class="verification.email_sent ? 'text-success' : 'text-warning'">
                        {{ verification.email_sent ? 'Sent' : 'Not Sent' }}
                    </p>
                </div>
            </div>
            <div
                class="box box--stacked p-4 flex items-center gap-3 border"
                :class="verification.attempt_count >= verification.max_attempts ? 'border-danger/10 bg-danger/[0.04]' : 'border-primary/10 bg-primary/[0.04]'"
            >
                <div class="p-2 rounded-lg border" :class="verification.attempt_count >= verification.max_attempts ? 'bg-danger/10 border-danger/10' : 'bg-primary/10 border-primary/10'">
                    <Lucide icon="RefreshCw" class="w-5 h-5 text-primary" />
                </div>
                <div>
                    <p class="text-xs text-slate-500">Attempts</p>
                    <p class="text-sm font-semibold" :class="verification.attempt_count >= verification.max_attempts ? 'text-danger' : 'text-primary'">
                        {{ verification.attempt_count }}/{{ verification.max_attempts }}
                    </p>
                </div>
            </div>
            <div class="box box--stacked p-4 flex items-center gap-3 border border-info/10 bg-info/[0.04]">
                <div class="p-2 rounded-lg bg-info/10 border border-info/10">
                    <Lucide icon="FileText" class="w-5 h-5 text-info" />
                </div>
                <div>
                    <p class="text-xs text-slate-500">Documents</p>
                    <p class="text-sm font-semibold text-info">{{ verification.documents.length }}</p>
                </div>
            </div>
            <div
                class="box box--stacked p-4 flex items-center gap-3 border"
                :class="hasSignedDocument ? 'border-success/10 bg-success/[0.04]' : 'border-slate-200 bg-slate-50/70'"
            >
                <div class="p-2 rounded-lg border" :class="hasSignedDocument ? 'bg-success/10 border-success/10' : 'bg-slate-100 border-slate-200'">
                    <Lucide icon="FilePen" class="w-5 h-5" :class="hasSignedDocument ? 'text-success' : 'text-slate-400'" />
                </div>
                <div>
                    <p class="text-xs text-slate-500">Signed Doc</p>
                    <p class="text-sm font-semibold" :class="hasSignedDocument ? 'text-success' : 'text-slate-400'">
                        {{ hasSignedDocument ? 'Available' : 'None' }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="box box--stacked overflow-hidden">
            <div class="border-b border-slate-200 bg-slate-50/50 overflow-x-auto">
                <nav class="flex min-w-max px-4">
                    <button
                        v-for="tab in tabs"
                        :key="tab.id"
                        type="button"
                        @click="activeTab = tab.id as any"
                        class="relative flex items-center gap-2 px-4 py-3.5 text-sm font-medium border-b-2 transition whitespace-nowrap"
                        :class="activeTab === tab.id
                            ? 'border-primary text-primary bg-white'
                            : 'border-transparent text-slate-500 hover:text-slate-700'"
                    >
                        <Lucide :icon="tab.icon as any" class="w-4 h-4" />
                        {{ tab.label }}
                        <span
                            v-if="tab.id === 'documents' && verification.documents.length > 0"
                            class="ml-1 px-1.5 py-0.5 rounded-full bg-primary/10 text-primary text-xs"
                        >{{ verification.documents.length }}</span>
                        <span
                            v-if="tab.id === 'signed' && hasSignedDocument"
                            class="ml-1 w-2 h-2 rounded-full bg-success inline-block"
                        />
                    </button>
                </nav>
            </div>

            <div class="p-6">

                <!-- ── TAB: OVERVIEW ─────────────────────────────────── -->
                <div v-show="activeTab === 'overview'" class="space-y-6">

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Employment Information -->
                        <div>
                            <h3 class="text-sm font-semibold text-slate-700 mb-4 flex items-center gap-2">
                                <Lucide icon="Briefcase" class="w-4 h-4 text-primary" />
                                Employment Information
                            </h3>
                            <div class="grid grid-cols-2 gap-3">
                                <div class="bg-slate-50 rounded-lg p-3 border border-slate-100">
                                    <p class="text-xs text-slate-500 mb-1">Driver</p>
                                    <p class="text-sm font-semibold text-slate-800">{{ verification.driver_name }}</p>
                                </div>
                                <div class="bg-slate-50 rounded-lg p-3 border border-slate-100">
                                    <p class="text-xs text-slate-500 mb-1">Company</p>
                                    <p class="text-sm font-semibold text-slate-800">{{ verification.company_name }}</p>
                                </div>
                                <div class="bg-slate-50 rounded-lg p-3 border border-slate-100">
                                    <p class="text-xs text-slate-500 mb-1">Position</p>
                                    <p class="text-sm font-semibold text-slate-800">{{ verification.positions_held || 'N/A' }}</p>
                                </div>
                                <div class="bg-slate-50 rounded-lg p-3 border border-slate-100">
                                    <p class="text-xs text-slate-500 mb-1">Contact Email</p>
                                    <p class="text-sm font-semibold text-slate-800 break-all">{{ verification.email || 'N/A' }}</p>
                                </div>
                                <div class="bg-slate-50 rounded-lg p-3 border border-slate-100 col-span-2">
                                    <p class="text-xs text-slate-500 mb-1">Employment Period</p>
                                    <p class="text-sm font-semibold text-slate-800">
                                        {{ verification.employed_from || 'N/A' }} — {{ verification.employed_to || 'Present' }}
                                    </p>
                                </div>
                                <div class="bg-slate-50 rounded-lg p-3 border border-slate-100">
                                    <p class="text-xs text-slate-500 mb-1">Subject to FMCSR</p>
                                    <span class="text-sm font-semibold" :class="verification.subject_to_fmcsr ? 'text-success' : 'text-slate-500'">
                                        {{ verification.subject_to_fmcsr ? 'Yes' : 'No' }}
                                    </span>
                                </div>
                                <div class="bg-slate-50 rounded-lg p-3 border border-slate-100">
                                    <p class="text-xs text-slate-500 mb-1">Safety Sensitive</p>
                                    <span class="text-sm font-semibold" :class="verification.safety_sensitive_function ? 'text-success' : 'text-slate-500'">
                                        {{ verification.safety_sensitive_function ? 'Yes' : 'No' }}
                                    </span>
                                </div>
                                <div v-if="verification.reason_for_leaving" class="bg-slate-50 rounded-lg p-3 border border-slate-100 col-span-2">
                                    <p class="text-xs text-slate-500 mb-1">Reason for Leaving</p>
                                    <p class="text-sm text-slate-700">{{ verification.reason_for_leaving }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Status & Notes -->
                        <div class="space-y-4">
                            <h3 class="text-sm font-semibold text-slate-700 mb-4 flex items-center gap-2">
                                <Lucide icon="ClipboardCheck" class="w-4 h-4 text-primary" />
                                Verification Status
                            </h3>

                            <div class="bg-slate-50 rounded-lg p-4 border border-slate-100 flex items-center justify-between">
                                <div>
                                    <p class="text-xs text-slate-500 mb-1.5">Verification Status</p>
                                    <span
                                        class="inline-flex items-center gap-1.5 text-xs font-semibold px-2.5 py-1 rounded-full capitalize"
                                        :class="statusBadgeClass(verification.verification_status)"
                                    >
                                        <Lucide :icon="statusIcon(verification.verification_status)" class="w-3 h-3" />
                                        {{ verification.verification_status || 'Pending' }}
                                    </span>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-500 mb-1.5">Email Status</p>
                                    <span
                                        class="inline-flex items-center gap-1.5 text-xs font-semibold px-2.5 py-1 rounded-full"
                                        :class="verification.email_sent ? 'bg-success/10 text-success' : 'bg-warning/10 text-warning'"
                                    >
                                        <span class="w-1.5 h-1.5 rounded-full" :class="verification.email_sent ? 'bg-success' : 'bg-warning'" />
                                        {{ verification.email_sent ? 'Email Sent' : 'Not Sent' }}
                                    </span>
                                </div>
                            </div>

                            <div v-if="verification.verification_date" class="bg-slate-50 rounded-lg p-3 border border-slate-100">
                                <p class="text-xs text-slate-500 mb-1">Verification Date</p>
                                <p class="text-sm font-semibold text-slate-800">{{ verification.verification_date }}</p>
                            </div>

                            <div v-if="verification.verification_notes" class="bg-slate-50 rounded-lg p-3 border border-slate-100">
                                <p class="text-xs text-slate-500 mb-1">Notes</p>
                                <p class="text-sm text-slate-700 whitespace-pre-wrap">{{ verification.verification_notes }}</p>
                            </div>

                            <!-- Latest Token -->
                            <div v-if="verification.latest_token" class="bg-slate-50 rounded-lg p-4 border border-slate-100">
                                <p class="text-xs text-slate-500 mb-3 flex items-center gap-1.5">
                                    <Lucide icon="Key" class="w-3.5 h-3.5" />
                                    Latest Token
                                </p>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <p class="text-xs text-slate-400">Status</p>
                                        <span
                                            class="inline-flex items-center gap-1 text-xs font-semibold px-2 py-0.5 rounded-full mt-1"
                                            :class="tokenStatusClass(verification.latest_token)"
                                        >
                                            <Lucide :icon="tokenStatusIcon(verification.latest_token)" class="w-3 h-3" />
                                            {{ tokenStatusLabel(verification.latest_token) }}
                                        </span>
                                    </div>
                                    <div>
                                        <p class="text-xs text-slate-400">Expires</p>
                                        <p class="text-sm font-medium text-slate-700 mt-1">{{ verification.latest_token.expires_at }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Admin Actions -->
                    <div v-if="verification.email" class="border-t border-slate-200/60 pt-6">
                        <h3 class="text-sm font-semibold text-slate-700 mb-4 flex items-center gap-2">
                            <Lucide icon="Settings" class="w-4 h-4 text-primary" />
                            Admin Actions
                            <span
                                class="ml-auto inline-flex items-center gap-1 text-xs font-semibold px-2.5 py-1 rounded-full"
                                :class="verification.can_send_more ? 'bg-primary/10 text-primary' : 'bg-danger/10 text-danger'"
                            >
                                {{ verification.attempt_count }}/{{ verification.max_attempts }} attempts used
                            </span>
                        </h3>

                        <div v-if="!verification.can_send_more" class="mb-4 p-3 bg-danger/5 border border-danger/20 rounded-lg flex items-center gap-2 text-danger">
                            <Lucide icon="AlertTriangle" class="w-4 h-4 flex-shrink-0" />
                            <span class="text-sm">Maximum verification attempts (3) reached. No more emails can be sent.</span>
                        </div>

                        <div class="flex flex-wrap gap-3">
                            <Button
                                v-if="verification.can_send_more"
                                @click="resend"
                                variant="primary"
                                size="sm"
                                class="inline-flex items-center gap-2 shadow-sm"
                            >
                                <Lucide icon="Mail" class="w-4 h-4" />
                                {{ verification.email_sent ? 'Resend Email' : 'Send Email' }}
                                <span class="opacity-75">({{ verification.max_attempts - verification.attempt_count }} left)</span>
                            </Button>

                            <Button
                                @click="toggleEmailFlag"
                                :variant="verification.email_sent ? 'outline-secondary' : 'outline-success'"
                                size="sm"
                                class="inline-flex items-center gap-2"
                            >
                                <Lucide :icon="verification.email_sent ? 'X' : 'Check'" class="w-4 h-4" />
                                {{ verification.email_sent ? 'Mark Not Sent' : 'Mark Sent' }}
                            </Button>

                            <Button
                                v-if="verification.verification_status !== 'verified'"
                                @click="markVerified"
                                variant="success"
                                size="sm"
                                class="inline-flex items-center gap-2 shadow-sm"
                            >
                                <Lucide icon="CheckCircle" class="w-4 h-4" />
                                Mark as Verified
                            </Button>

                            <Button
                                v-if="verification.verification_status !== 'rejected'"
                                @click="markRejected"
                                variant="danger"
                                size="sm"
                                class="inline-flex items-center gap-2 shadow-sm"
                            >
                                <Lucide icon="XCircle" class="w-4 h-4" />
                                Mark as Rejected
                            </Button>
                        </div>
                    </div>
                </div>

                <!-- ── TAB: DOCUMENTS ─────────────────────────────────── -->
                <div v-show="activeTab === 'documents'" class="space-y-6">
                    <div>
                        <h3 class="text-sm font-semibold text-slate-700 mb-4 flex items-center gap-2">
                            <Lucide icon="Upload" class="w-4 h-4 text-primary" />
                            Upload Verification Document
                        </h3>
                        <form @submit.prevent="submitUpload" class="bg-slate-50 rounded-xl border border-slate-200 p-5 space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-slate-600 mb-1.5">
                                        Verification Document <span class="text-danger">*</span>
                                    </label>
                                    <input
                                        type="file"
                                        accept=".pdf,.jpg,.jpeg,.png"
                                        required
                                        @change="handleFileChange"
                                        class="w-full text-sm border border-slate-200 bg-white rounded-lg file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-primary file:text-white hover:file:bg-primary/90 py-1"
                                    />
                                    <p class="mt-1 text-xs text-slate-400">PDF, JPG, PNG — max 10MB</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-600 mb-1.5">
                                        Verification Date <span class="text-danger">*</span>
                                    </label>
                                    <input
                                        v-model="uploadDate"
                                        type="date"
                                        required
                                        class="form-control w-full text-sm"
                                    />
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-1.5">Notes <span class="text-slate-400">(optional)</span></label>
                                <textarea
                                    v-model="uploadNotes"
                                    rows="3"
                                    maxlength="500"
                                    placeholder="Add any additional notes about this verification..."
                                    class="form-control w-full text-sm"
                                />
                                <p class="mt-1 text-xs text-slate-400">Max 500 characters</p>
                            </div>
                            <div class="flex justify-end">
                                <button
                                    type="submit"
                                    :disabled="uploading || !uploadFile"
                                    class="btn btn-primary btn-sm inline-flex items-center gap-2 disabled:opacity-60"
                                >
                                    <Lucide v-if="uploading" icon="Loader" class="w-4 h-4 animate-spin" />
                                    <Lucide v-else icon="Upload" class="w-4 h-4" />
                                    {{ uploading ? 'Uploading…' : 'Upload Document' }}
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Uploaded documents list -->
                    <div v-if="verification.documents.length > 0">
                        <h3 class="text-sm font-semibold text-slate-700 mb-3 flex items-center gap-2">
                            <Lucide icon="FolderOpen" class="w-4 h-4 text-primary" />
                            Uploaded Documents
                            <span class="ml-1 px-2 py-0.5 rounded-full bg-primary/10 text-primary text-xs">{{ verification.documents.length }}</span>
                        </h3>
                        <div class="space-y-2">
                            <div
                                v-for="doc in verification.documents"
                                :key="doc.id"
                                class="flex items-center justify-between p-3 bg-slate-50 rounded-lg border border-slate-200 hover:border-slate-300 transition-colors"
                            >
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="p-2 bg-primary/10 rounded-lg flex-shrink-0">
                                        <Lucide icon="FileText" class="w-4 h-4 text-primary" />
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-slate-800 truncate">
                                            {{ doc.original_name || doc.file_name }}
                                        </p>
                                        <p class="text-xs text-slate-400">
                                            {{ doc.size_formatted }}
                                            <template v-if="doc.uploaded_at"> · {{ doc.uploaded_at }}</template>
                                            <template v-if="doc.uploaded_by"> · by {{ doc.uploaded_by }}</template>
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 ml-3 flex-shrink-0">
                                    <a :href="doc.url" target="_blank" class="btn btn-outline-primary btn-xs inline-flex items-center gap-1">
                                        <Lucide icon="Eye" class="w-3 h-3" /> View
                                    </a>
                                    <a :href="doc.url" download class="btn btn-outline-secondary btn-xs inline-flex items-center gap-1">
                                        <Lucide icon="Download" class="w-3 h-3" /> Download
                                    </a>
                                    <button @click="deleteDocument(doc.id)" class="btn btn-outline-danger btn-xs inline-flex items-center gap-1">
                                        <Lucide icon="Trash2" class="w-3 h-3" /> Delete
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-else class="flex flex-col items-center py-12 gap-3 text-slate-400">
                        <div class="p-4 bg-slate-100 rounded-full">
                            <Lucide icon="FileX" class="w-8 h-8" />
                        </div>
                        <p class="text-sm font-medium">No documents uploaded yet</p>
                    </div>
                </div>

                <!-- ── TAB: SIGNED DOCUMENT ──────────────────────────── -->
                <div v-show="activeTab === 'signed'" class="space-y-4">
                    <h3 class="text-sm font-semibold text-slate-700 flex items-center gap-2">
                        <Lucide icon="FilePen" class="w-4 h-4 text-primary" />
                        Signed Verification Document
                        <span class="text-xs text-slate-400 font-normal">— generated when the employer completed the online form</span>
                    </h3>

                    <!-- Has signed document -->
                    <template v-if="signedToken">
                        <div class="flex items-center gap-3 p-3 bg-success/5 border border-success/20 rounded-lg text-success">
                            <Lucide icon="CheckCircle" class="w-5 h-5 flex-shrink-0" />
                            <div class="text-sm">
                                <span class="font-semibold">Form signed and verified</span>
                                <span class="text-success/70 ml-2">on {{ signedToken.verified_at }}</span>
                            </div>
                        </div>

                        <!-- PDF Embed -->
                        <div v-if="isDocumentPdf(signedToken.document_url!)" class="border border-slate-200 rounded-xl overflow-hidden bg-slate-50">
                            <div class="flex items-center justify-between px-4 py-3 border-b border-slate-200 bg-white">
                                <div class="flex items-center gap-2">
                                    <Lucide icon="FileText" class="w-4 h-4 text-danger" />
                                    <span class="text-sm font-medium text-slate-700">Employment Verification — Signed PDF</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <a
                                        :href="signedToken.document_url!"
                                        target="_blank"
                                        class="btn btn-outline-primary btn-xs inline-flex items-center gap-1"
                                    >
                                        <Lucide icon="ExternalLink" class="w-3 h-3" /> Open
                                    </a>
                                    <a
                                        :href="signedToken.document_url!"
                                        download
                                        class="btn btn-outline-secondary btn-xs inline-flex items-center gap-1"
                                    >
                                        <Lucide icon="Download" class="w-3 h-3" /> Download
                                    </a>
                                </div>
                            </div>
                            <iframe
                                :src="signedToken.document_url + '#toolbar=1&navpanes=0'"
                                class="w-full"
                                style="height: 700px;"
                                title="Signed Verification Document"
                            />
                        </div>

                        <!-- Image embed -->
                        <div v-else class="border border-slate-200 rounded-xl overflow-hidden bg-slate-50">
                            <div class="flex items-center justify-between px-4 py-3 border-b border-slate-200 bg-white">
                                <div class="flex items-center gap-2">
                                    <Lucide icon="Image" class="w-4 h-4 text-primary" />
                                    <span class="text-sm font-medium text-slate-700">Employment Verification — Signed Document</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <a :href="signedToken.document_url!" target="_blank" class="btn btn-outline-primary btn-xs inline-flex items-center gap-1">
                                        <Lucide icon="ExternalLink" class="w-3 h-3" /> Open
                                    </a>
                                    <a :href="signedToken.document_url!" download class="btn btn-outline-secondary btn-xs inline-flex items-center gap-1">
                                        <Lucide icon="Download" class="w-3 h-3" /> Download
                                    </a>
                                </div>
                            </div>
                            <img :src="signedToken.document_url!" alt="Signed Verification Document" class="max-w-full mx-auto p-4" />
                        </div>

                        <!-- Signature image (if available) -->
                        <div v-if="signedToken.signature_url" class="border border-slate-200 rounded-xl overflow-hidden bg-white p-4">
                            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-3 flex items-center gap-1.5">
                                <Lucide icon="PenLine" class="w-3.5 h-3.5" /> Employer Signature
                            </p>
                            <img :src="signedToken.signature_url" alt="Employer Signature" class="max-h-24 border border-slate-200 rounded-lg p-2 bg-white" />
                        </div>
                    </template>

                    <!-- No signed document -->
                    <div v-else class="flex flex-col items-center py-16 gap-4 text-slate-400">
                        <div class="p-5 bg-slate-100 rounded-full">
                            <Lucide icon="FilePen" class="w-10 h-10" />
                        </div>
                        <div class="text-center">
                            <p class="font-medium text-slate-600">No signed document yet</p>
                            <p class="text-sm mt-1">The document will appear here once the employer completes and signs the online verification form.</p>
                        </div>
                        <div v-if="verification.verification_status !== 'verified'" class="mt-2 flex items-center gap-2 text-sm">
                            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-warning/10 text-warning text-xs font-medium">
                                <Lucide icon="Clock" class="w-3.5 h-3.5" />
                                {{ verification.email_sent ? 'Waiting for employer response' : 'Email not sent yet' }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- ── TAB: HISTORY ───────────────────────────────────── -->
                <div v-show="activeTab === 'history'">
                    <h3 class="text-sm font-semibold text-slate-700 mb-4 flex items-center gap-2">
                        <Lucide icon="History" class="w-4 h-4 text-primary" />
                        Verification Attempts
                        <span class="ml-1 px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 text-xs">{{ verification.tokens.length }}</span>
                    </h3>

                    <div v-if="verification.tokens.length > 0" class="overflow-x-auto rounded-xl border border-slate-200">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="bg-slate-50/80">
                                    <th class="px-5 py-3 text-xs font-medium uppercase tracking-wide text-slate-500">#</th>
                                    <th class="px-5 py-3 text-xs font-medium uppercase tracking-wide text-slate-500">Sent To</th>
                                    <th class="px-5 py-3 text-xs font-medium uppercase tracking-wide text-slate-500">Sent Date</th>
                                    <th class="px-5 py-3 text-xs font-medium uppercase tracking-wide text-slate-500">Expires</th>
                                    <th class="px-5 py-3 text-center text-xs font-medium uppercase tracking-wide text-slate-500">Status</th>
                                    <th class="px-5 py-3 text-xs font-medium uppercase tracking-wide text-slate-500">Verified At</th>
                                    <th class="px-5 py-3 text-center text-xs font-medium uppercase tracking-wide text-slate-500">Document</th>
                                    <th class="px-5 py-3 text-center text-xs font-medium uppercase tracking-wide text-slate-500">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="(token, idx) in verification.tokens"
                                    :key="token.id"
                                    class="border-b border-slate-100 transition hover:bg-slate-50/50"
                                >
                                    <td class="px-5 py-4 align-top font-semibold text-slate-700">{{ idx + 1 }}</td>
                                    <td class="px-5 py-4 align-top text-slate-700">{{ token.email || '—' }}</td>
                                    <td class="px-5 py-4 align-top whitespace-nowrap text-sm text-slate-600">{{ token.created_at }}</td>
                                    <td class="px-5 py-4 align-top whitespace-nowrap text-sm text-slate-600">{{ token.expires_at }}</td>
                                    <td class="px-5 py-4 text-center align-top">
                                        <span
                                            class="inline-flex items-center gap-1 text-xs font-medium px-2.5 py-1 rounded-full"
                                            :class="tokenStatusClass(token)"
                                        >
                                            <Lucide :icon="tokenStatusIcon(token)" class="w-3 h-3" />
                                            {{ tokenStatusLabel(token) }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 align-top whitespace-nowrap text-sm text-slate-600">{{ token.verified_at || '—' }}</td>
                                    <td class="px-5 py-4 text-center align-top">
                                        <a
                                            v-if="token.document_url"
                                            :href="token.document_url"
                                            target="_blank"
                                            class="btn btn-outline-success btn-xs inline-flex items-center gap-1"
                                        >
                                            <Lucide icon="FileText" class="w-3 h-3" /> View
                                        </a>
                                        <span v-else class="text-xs text-slate-400">—</span>
                                    </td>
                                    <td class="px-5 py-4 text-center align-top">
                                        <button
                                            v-if="!token.is_verified"
                                            @click="deleteToken(token.id)"
                                            class="btn btn-outline-danger btn-xs inline-flex items-center gap-1"
                                        >
                                            <Lucide icon="Trash2" class="w-3 h-3" /> Delete
                                        </button>
                                        <span v-else class="text-xs text-slate-400">—</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-else class="flex flex-col items-center py-12 gap-3 text-slate-400">
                        <div class="p-4 bg-slate-100 rounded-full">
                            <Lucide icon="History" class="w-8 h-8" />
                        </div>
                        <p class="text-sm font-medium">No verification attempts yet</p>
                    </div>
                </div>

            </div>
        </div>

    </div>
</template>
