<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { ref } from 'vue'
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
    if (status === 'verified') return 'bg-emerald-100 text-emerald-700'
    if (status === 'rejected') return 'bg-red-100 text-red-700'
    return 'bg-amber-100 text-amber-700'
}

const tokenStatusClass = (token: Token) => {
    if (token.is_verified) return 'bg-emerald-100 text-emerald-700'
    if (token.is_expired) return 'bg-red-100 text-red-700'
    return 'bg-blue-100 text-blue-700'
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
</script>

<template>
    <Head title="Employment Verification Details" />

    <div class="p-5 sm:p-8 max-w-screen-2xl mx-auto">

        <!-- Header -->
        <div class="box box--stacked p-8 mb-8">
            <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                        <Lucide icon="FileCheck" class="w-8 h-8 text-primary" />
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-slate-800 mb-1">Employment Verification</h1>
                        <div class="flex items-center gap-3 mt-1">
                            <span class="text-slate-600 text-sm">{{ verification.driver_name }}</span>
                            <span
                                class="inline-flex items-center gap-1 text-xs font-semibold px-2.5 py-1 rounded capitalize"
                                :class="statusBadgeClass(verification.verification_status)"
                            >
                                {{ verification.verification_status || 'Pending' }}
                            </span>
                        </div>
                    </div>
                </div>
                <div class="flex flex-wrap gap-3">
                    <Link
                        :href="route('admin.drivers.employment-verification.index')"
                        class="inline-flex items-center gap-2 bg-white border border-slate-300 text-slate-700 px-4 py-2 rounded-lg hover:bg-slate-50 transition-colors font-medium text-sm"
                    >
                        <Lucide icon="ArrowLeft" class="w-4 h-4" /> Back to List
                    </Link>
                    <Link
                        :href="route('admin.drivers.show', verification.driver_id)"
                        class="inline-flex items-center gap-2 bg-primary text-white px-4 py-2 rounded-lg hover:bg-primary/90 transition-colors font-medium text-sm"
                    >
                        <Lucide icon="User" class="w-4 h-4" /> View Driver
                    </Link>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-12 gap-6">

            <!-- Employment Information -->
            <div class="col-span-12 lg:col-span-6">
                <div class="box box--stacked p-6 h-fit">
                    <div class="flex items-center gap-3 mb-6">
                        <Lucide icon="Briefcase" class="w-5 h-5 text-primary" />
                        <h2 class="text-lg font-semibold text-slate-800">Employment Information</h2>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                            <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1 block">Driver</label>
                            <p class="text-sm font-semibold text-slate-800">{{ verification.driver_name }}</p>
                        </div>
                        <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                            <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1 block">Company</label>
                            <p class="text-sm font-semibold text-slate-800">{{ verification.company_name }}</p>
                        </div>
                        <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                            <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1 block">Position</label>
                            <p class="text-sm font-semibold text-slate-800">{{ verification.positions_held || 'N/A' }}</p>
                        </div>
                        <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                            <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1 block">Contact Email</label>
                            <p class="text-sm font-semibold text-slate-800 break-all">{{ verification.email || 'N/A' }}</p>
                        </div>
                        <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100 col-span-2">
                            <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1 block">Employment Period</label>
                            <p class="text-sm font-semibold text-slate-800">
                                {{ verification.employed_from || 'N/A' }} — {{ verification.employed_to || 'Present' }}
                            </p>
                        </div>
                        <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                            <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1 block">Subject to FMCSR</label>
                            <span :class="verification.subject_to_fmcsr ? 'text-emerald-600' : 'text-slate-500'" class="text-sm font-semibold">
                                {{ verification.subject_to_fmcsr ? 'Yes' : 'No' }}
                            </span>
                        </div>
                        <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                            <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1 block">Safety Sensitive</label>
                            <span :class="verification.safety_sensitive_function ? 'text-emerald-600' : 'text-slate-500'" class="text-sm font-semibold">
                                {{ verification.safety_sensitive_function ? 'Yes' : 'No' }}
                            </span>
                        </div>
                        <div v-if="verification.reason_for_leaving" class="bg-slate-50/50 rounded-lg p-4 border border-slate-100 col-span-2">
                            <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1 block">Reason for Leaving</label>
                            <p class="text-sm text-slate-700">{{ verification.reason_for_leaving }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Email & Verification Status -->
            <div class="col-span-12 lg:col-span-6">
                <div class="box box--stacked p-6 h-fit">
                    <div class="flex items-center gap-3 mb-6">
                        <Lucide icon="Mail" class="w-5 h-5 text-primary" />
                        <h2 class="text-lg font-semibold text-slate-800">Email & Verification Status</h2>
                    </div>
                    <div class="space-y-3">
                        <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                            <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-2 block">Email Status</label>
                            <span
                                class="inline-flex items-center gap-1.5 text-xs font-semibold px-2.5 py-1 rounded"
                                :class="verification.email_sent ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'"
                            >
                                <span class="w-1.5 h-1.5 rounded-full" :class="verification.email_sent ? 'bg-emerald-500' : 'bg-amber-500'" />
                                {{ verification.email_sent ? 'Email Sent' : 'Not Sent' }}
                            </span>
                        </div>
                        <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                            <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-2 block">Verification Status</label>
                            <span
                                class="inline-flex items-center gap-1.5 text-xs font-semibold px-2.5 py-1 rounded capitalize"
                                :class="statusBadgeClass(verification.verification_status)"
                            >
                                <Lucide
                                    :icon="verification.verification_status === 'verified' ? 'CheckCircle' : verification.verification_status === 'rejected' ? 'XCircle' : 'Clock'"
                                    class="w-3 h-3"
                                />
                                {{ verification.verification_status || 'Pending' }}
                            </span>
                        </div>
                        <div v-if="verification.verification_date" class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                            <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1 block">Verification Date</label>
                            <p class="text-sm font-semibold text-slate-800">{{ verification.verification_date }}</p>
                        </div>
                        <div v-if="verification.verification_notes" class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                            <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1 block">Notes</label>
                            <p class="text-sm text-slate-700 whitespace-pre-wrap">{{ verification.verification_notes }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Latest Token Status -->
            <div v-if="verification.latest_token" class="col-span-12">
                <div class="box box--stacked p-6">
                    <div class="flex items-center gap-3 mb-6">
                        <Lucide icon="Key" class="w-5 h-5 text-primary" />
                        <h2 class="text-lg font-semibold text-slate-800">Latest Token Status</h2>
                    </div>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                            <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-2 block">Status</label>
                            <span
                                class="inline-flex items-center gap-1 text-xs font-semibold px-2.5 py-1 rounded"
                                :class="tokenStatusClass(verification.latest_token)"
                            >
                                <Lucide :icon="tokenStatusIcon(verification.latest_token)" class="w-3 h-3" />
                                {{ tokenStatusLabel(verification.latest_token) }}
                            </span>
                        </div>
                        <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                            <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1 block">Created At</label>
                            <p class="text-sm font-semibold text-slate-800">{{ verification.latest_token.created_at }}</p>
                        </div>
                        <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                            <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1 block">Expires At</label>
                            <p class="text-sm font-semibold text-slate-800">{{ verification.latest_token.expires_at }}</p>
                        </div>
                        <div v-if="verification.latest_token.verified_at" class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                            <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1 block">Verified At</label>
                            <p class="text-sm font-semibold text-slate-800">{{ verification.latest_token.verified_at }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Admin Actions -->
            <div v-if="verification.email" class="col-span-12">
                <div class="box box--stacked p-6">
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex items-center gap-3">
                            <Lucide icon="Settings" class="w-5 h-5 text-primary" />
                            <h2 class="text-lg font-semibold text-slate-800">Admin Actions</h2>
                        </div>
                        <span
                            class="inline-flex items-center gap-1 text-xs font-semibold px-3 py-1 rounded"
                            :class="verification.can_send_more ? 'bg-primary/10 text-primary' : 'bg-red-100 text-red-700'"
                        >
                            Attempts: {{ verification.attempt_count }}/{{ verification.max_attempts }}
                        </span>
                    </div>

                    <div v-if="!verification.can_send_more" class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg flex items-center gap-2 text-red-700">
                        <Lucide icon="AlertTriangle" class="w-5 h-5 flex-shrink-0" />
                        <span class="font-medium text-sm">Maximum verification attempts (3) reached. No more emails can be sent.</span>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <!-- Resend -->
                        <button
                            v-if="verification.can_send_more"
                            @click="resend"
                            class="inline-flex items-center gap-2 bg-primary text-white px-4 py-2 rounded-lg hover:bg-primary/90 transition-colors font-medium text-sm"
                        >
                            <Lucide icon="Mail" class="w-4 h-4" />
                            {{ verification.email_sent ? 'Resend Email' : 'Send Email' }}
                            ({{ verification.max_attempts - verification.attempt_count }} left)
                        </button>
                        <button
                            v-else
                            disabled
                            class="inline-flex items-center gap-2 bg-slate-100 text-slate-400 px-4 py-2 rounded-lg cursor-not-allowed font-medium text-sm"
                        >
                            <Lucide icon="Mail" class="w-4 h-4" />
                            No Attempts Remaining
                        </button>

                        <!-- Toggle email flag -->
                        <button
                            @click="toggleEmailFlag"
                            class="inline-flex items-center gap-2 px-4 py-2 rounded-lg font-medium text-sm border transition-colors"
                            :class="verification.email_sent
                                ? 'border-slate-300 bg-white text-slate-700 hover:bg-slate-50'
                                : 'border-emerald-300 bg-emerald-50 text-emerald-700 hover:bg-emerald-100'"
                        >
                            <Lucide :icon="verification.email_sent ? 'X' : 'Check'" class="w-4 h-4" />
                            {{ verification.email_sent ? 'Mark Not Sent' : 'Mark Sent' }}
                        </button>

                        <!-- Mark verified -->
                        <button
                            v-if="verification.verification_status !== 'verified'"
                            @click="markVerified"
                            class="inline-flex items-center gap-2 bg-emerald-600 text-white px-4 py-2 rounded-lg hover:bg-emerald-700 transition-colors font-medium text-sm"
                        >
                            <Lucide icon="CheckCircle" class="w-4 h-4" />
                            Mark as Verified
                        </button>

                        <!-- Mark rejected -->
                        <button
                            v-if="verification.verification_status !== 'rejected'"
                            @click="markRejected"
                            class="inline-flex items-center gap-2 bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors font-medium text-sm"
                        >
                            <Lucide icon="XCircle" class="w-4 h-4" />
                            Mark as Rejected
                        </button>
                    </div>
                </div>
            </div>

            <!-- Upload Document -->
            <div class="col-span-12">
                <div class="box box--stacked p-6">
                    <div class="flex items-center gap-3 mb-6">
                        <Lucide icon="Upload" class="w-5 h-5 text-primary" />
                        <h2 class="text-lg font-semibold text-slate-800">Upload Verification Document</h2>
                    </div>

                    <form @submit.prevent="submitUpload" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">
                                    Verification Document <span class="text-red-500">*</span>
                                </label>
                                <input
                                    type="file"
                                    accept=".pdf,.jpg,.jpeg,.png"
                                    required
                                    @change="handleFileChange"
                                    class="w-full text-sm border border-slate-200 rounded-lg file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-primary file:text-white hover:file:bg-primary/90 py-1"
                                />
                                <p class="mt-1 text-xs text-slate-500">PDF, JPG, PNG — max 10MB</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">
                                    Verification Date <span class="text-red-500">*</span>
                                </label>
                                <input
                                    v-model="uploadDate"
                                    type="date"
                                    required
                                    class="w-full border border-slate-200 rounded-lg text-sm py-2.5 px-3 focus:ring-primary focus:border-primary"
                                />
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Notes (Optional)</label>
                            <textarea
                                v-model="uploadNotes"
                                rows="3"
                                maxlength="500"
                                placeholder="Add any additional notes about this verification..."
                                class="w-full border border-slate-200 rounded-lg text-sm py-2.5 px-3 focus:ring-primary focus:border-primary"
                            />
                            <p class="mt-1 text-xs text-slate-500">Max 500 characters</p>
                        </div>
                        <div class="flex justify-end">
                            <button
                                type="submit"
                                :disabled="uploading || !uploadFile"
                                class="inline-flex items-center gap-2 bg-primary text-white px-4 py-2 rounded-lg hover:bg-primary/90 font-medium text-sm disabled:opacity-60"
                            >
                                <Lucide v-if="uploading" icon="Loader" class="w-4 h-4 animate-spin" />
                                <Lucide v-else icon="Upload" class="w-4 h-4" />
                                {{ uploading ? 'Uploading…' : 'Upload Document' }}
                            </button>
                        </div>
                    </form>

                    <!-- Uploaded documents list -->
                    <div v-if="verification.documents.length > 0" class="mt-8 pt-6 border-t border-slate-200/60">
                        <h3 class="text-sm font-semibold text-slate-700 mb-4">
                            Uploaded Documents ({{ verification.documents.length }})
                        </h3>
                        <div class="space-y-2">
                            <div
                                v-for="doc in verification.documents"
                                :key="doc.id"
                                class="flex items-center justify-between p-3 bg-slate-50/50 rounded-lg border border-slate-100 hover:border-slate-200 transition-colors"
                            >
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-primary/10 rounded-lg">
                                        <Lucide icon="FileText" class="w-4 h-4 text-primary" />
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-slate-800">
                                            {{ doc.original_name || doc.file_name }}
                                        </p>
                                        <p class="text-xs text-slate-500">
                                            {{ doc.uploaded_at }}
                                            <template v-if="doc.uploaded_by"> · by {{ doc.uploaded_by }}</template>
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <a
                                        :href="doc.url"
                                        target="_blank"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg border border-primary/30 text-primary hover:bg-primary/10 transition-colors"
                                    >
                                        <Lucide icon="Eye" class="w-3 h-3" /> View
                                    </a>
                                    <a
                                        :href="doc.url"
                                        download
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 transition-colors"
                                    >
                                        <Lucide icon="Download" class="w-3 h-3" /> Download
                                    </a>
                                    <button
                                        @click="deleteDocument(doc.id)"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg border border-red-200 text-red-600 hover:bg-red-50 transition-colors"
                                    >
                                        <Lucide icon="Trash2" class="w-3 h-3" /> Delete
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Verification History -->
            <div v-if="verification.tokens.length > 0" class="col-span-12">
                <div class="box box--stacked">
                    <div class="p-6 border-b border-slate-200/60 flex items-center gap-3">
                        <Lucide icon="History" class="w-5 h-5 text-primary" />
                        <h2 class="text-lg font-semibold text-slate-800">Verification History</h2>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-200/60">
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">#</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Sent To</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Sent Date</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Expires</th>
                                    <th class="px-6 py-4 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Verified At</th>
                                    <th class="px-6 py-4 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200/60">
                                <tr
                                    v-for="(token, idx) in verification.tokens"
                                    :key="token.id"
                                    class="hover:bg-slate-50/50 transition-colors"
                                >
                                    <td class="px-6 py-4 text-sm font-semibold text-slate-700">{{ idx + 1 }}</td>
                                    <td class="px-6 py-4 text-sm text-slate-700">{{ token.email || '—' }}</td>
                                    <td class="px-6 py-4 text-sm text-slate-700">{{ token.created_at }}</td>
                                    <td class="px-6 py-4 text-sm text-slate-700">{{ token.expires_at }}</td>
                                    <td class="px-6 py-4 text-center">
                                        <span
                                            class="inline-flex items-center gap-1 text-xs font-semibold px-2.5 py-1 rounded"
                                            :class="tokenStatusClass(token)"
                                        >
                                            <Lucide :icon="tokenStatusIcon(token)" class="w-3 h-3" />
                                            {{ tokenStatusLabel(token) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-slate-700">{{ token.verified_at || '—' }}</td>
                                    <td class="px-6 py-4 text-center">
                                        <button
                                            v-if="!token.is_verified"
                                            @click="deleteToken(token.id)"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg border border-red-200 text-red-600 hover:bg-red-50 transition-colors"
                                        >
                                            <Lucide icon="Trash2" class="w-3 h-3" /> Delete
                                        </button>
                                        <span v-else class="text-xs text-slate-400">—</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</template>
