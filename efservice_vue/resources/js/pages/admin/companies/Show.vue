<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, reactive } from 'vue'
import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import RazeLayout from '@/layouts/RazeLayout.vue'
import { Dialog } from '@/components/Base/Headless'
import { FormInput } from '@/components/Base/Form'

declare function route(name: string, params?: any): string

defineOptions({ layout: RazeLayout })

interface Company {
    id: number
    company_name: string
    address: string | null
    city: string | null
    state: string | null
    zip: string | null
    contact: string | null
    phone: string | null
    email: string | null
    fax: string | null
    driver_employment_companies_count: number
}

interface HistoryItem {
    id: number
    driver_id: number
    driver_name: string
    driver_email: string
    positions_held: string | null
    employed_from: string | null
    employed_to: string | null
    email: string | null
    email_sent: boolean
    verification_status: string | null
}

const props = defineProps<{
    company: Company
    employmentHistory: {
        data: HistoryItem[]
        links: { url: string | null; label: string; active: boolean }[]
        current_page: number
        last_page: number
        total: number
    }
}>()

// Edit modal
const showEditModal = ref(false)
const saving = ref(false)
const errors = ref<Record<string, string[]>>({})
const form = reactive({ ...props.company })

function openEdit() {
    Object.assign(form, props.company)
    errors.value = {}
    showEditModal.value = true
}

function saveEdit() {
    saving.value = true
    errors.value = {}
    router.put(route('admin.companies.update', props.company.id), { ...form }, {
        preserveScroll: true,
        onSuccess: () => { showEditModal.value = false; saving.value = false },
        onError: (e) => { errors.value = e as any; saving.value = false },
    })
}

function deleteCompany() {
    if (!confirm(`Delete "${props.company.company_name}"? This cannot be undone.`)) return
    router.delete(route('admin.companies.destroy', props.company.id))
}

function sendVerification(historyId: number) {
    if (!confirm('Send verification email for this employment record?')) return
    router.post(route('admin.drivers.employment-verification.resend', historyId), {}, { preserveScroll: true })
}

function fullAddress(c: Company) {
    const parts = [c.address, c.city, c.state, c.zip].filter(Boolean)
    return parts.length ? parts.join(', ') : 'Not specified'
}

const emailStatusBadge = (sent: boolean, hasEmail: boolean) => {
    if (!hasEmail) return 'bg-slate-100 text-slate-500'
    return sent ? 'bg-primary/10 text-primary' : 'bg-slate-100 text-slate-700'
}

const verificationBadge = (status: string | null) => {
    if (status === 'verified') return 'bg-primary/10 text-primary'
    if (status === 'rejected') return 'bg-danger/10 text-danger'
    return 'bg-slate-100 text-slate-700'
}
</script>

<template>
    <Head :title="`Company: ${company.company_name}`" />

    <div class="p-5 sm:p-8 max-w-screen-2xl mx-auto">

        <!-- Header -->
        <div class="box box--stacked p-8 mb-8">
            <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                        <Lucide icon="Building2" class="w-8 h-8 text-primary" />
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-slate-800 mb-1">{{ company.company_name }}</h1>
                        <p class="text-slate-500 text-sm">Company Details & Employment History</p>
                    </div>
                </div>
                <div class="flex flex-wrap gap-3">
                    <Link :href="route('admin.companies.index')">
                        <Button variant="outline-secondary" class="inline-flex items-center gap-2">
                            <Lucide icon="ArrowLeft" class="w-4 h-4" /> Back to List
                        </Button>
                    </Link>
                    <Button
                        @click="openEdit"
                        variant="primary"
                        class="inline-flex items-center gap-2"
                    >
                        <Lucide icon="Edit" class="w-4 h-4" /> Edit Company
                    </Button>
                    <Button
                        @click="deleteCompany"
                        variant="danger"
                        :disabled="company.driver_employment_companies_count > 0"
                        class="inline-flex items-center gap-2 disabled:opacity-40 disabled:cursor-not-allowed"
                        :title="company.driver_employment_companies_count > 0 ? 'Cannot delete: has employment records' : 'Delete company'"
                    >
                        <Lucide icon="Trash2" class="w-4 h-4" /> Delete
                    </Button>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-12 gap-6">

            <!-- Company Information -->
            <div class="col-span-12 lg:col-span-6">
                <div class="box box--stacked p-6 h-fit">
                    <div class="flex items-center gap-3 mb-6">
                        <Lucide icon="Info" class="w-5 h-5 text-primary" />
                        <h2 class="text-lg font-semibold text-slate-800">Company Information</h2>
                    </div>
                    <div class="space-y-3">
                        <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                            <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1 block">Company Name</label>
                            <p class="text-sm font-semibold text-slate-800">{{ company.company_name }}</p>
                        </div>
                        <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                            <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1 block">Contact Person</label>
                            <p class="text-sm font-semibold text-slate-800">{{ company.contact || 'N/A' }}</p>
                        </div>
                        <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                            <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1 block">Address</label>
                            <p class="text-sm font-semibold text-slate-800">{{ fullAddress(company) }}</p>
                        </div>
                        <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                            <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1 block">Drivers Associated</label>
                            <span class="inline-flex items-center gap-1 bg-primary/10 text-primary text-xs font-semibold px-2.5 py-1 rounded-full">
                                <Lucide icon="Users" class="w-3 h-3" />
                                {{ company.driver_employment_companies_count }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contact Information -->
            <div class="col-span-12 lg:col-span-6">
                <div class="box box--stacked p-6 h-fit">
                    <div class="flex items-center gap-3 mb-6">
                        <Lucide icon="Phone" class="w-5 h-5 text-primary" />
                        <h2 class="text-lg font-semibold text-slate-800">Contact Information</h2>
                    </div>
                    <div class="space-y-3">
                        <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                            <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1 block">Phone</label>
                            <div class="flex items-center gap-2">
                                <Lucide icon="Phone" class="w-4 h-4 text-slate-400" />
                                <p class="text-sm font-semibold text-slate-800">{{ company.phone || 'Not specified' }}</p>
                            </div>
                        </div>
                        <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                            <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1 block">Fax</label>
                            <div class="flex items-center gap-2">
                                <Lucide icon="Printer" class="w-4 h-4 text-slate-400" />
                                <p class="text-sm font-semibold text-slate-800">{{ company.fax || 'Not specified' }}</p>
                            </div>
                        </div>
                        <div class="bg-slate-50/50 rounded-lg p-4 border border-slate-100">
                            <label class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1 block">Email</label>
                            <div class="flex items-center gap-2">
                                <Lucide icon="Mail" class="w-4 h-4 text-slate-400" />
                                <p class="text-sm font-semibold text-slate-800 break-all">{{ company.email || 'Not specified' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Employment History Records -->
            <div class="col-span-12">
                <div class="box box--stacked">
                    <div class="p-6 border-b border-slate-200/60 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <Lucide icon="Users" class="w-5 h-5 text-primary" />
                            <h2 class="text-lg font-semibold text-slate-800">Employment History Records</h2>
                        </div>
                        <span class="bg-primary/10 text-primary text-xs font-semibold px-3 py-1.5 rounded-full">
                            {{ employmentHistory.total }} Total
                        </span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-200/60">
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Driver</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Position</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Period</th>
                                    <th class="px-6 py-4 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Email Status</th>
                                    <th class="px-6 py-4 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Verification</th>
                                    <th class="px-6 py-4 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200/60">
                                <tr v-for="h in employmentHistory.data" :key="h.id" class="hover:bg-slate-50/50 transition-colors">
                                    <td class="px-6 py-4">
                                        <Link
                                            :href="route('admin.drivers.show', h.driver_id)"
                                            class="font-medium text-primary hover:text-primary/80 transition-colors text-sm block"
                                        >
                                            {{ h.driver_name }}
                                        </Link>
                                        <div class="flex items-center gap-1.5 text-xs text-slate-500 mt-0.5">
                                            <Lucide icon="Mail" class="w-3 h-3" />
                                            {{ h.driver_email }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-slate-700">{{ h.positions_held || 'N/A' }}</td>
                                    <td class="px-6 py-4 text-sm text-slate-700">
                                        {{ h.employed_from || 'N/A' }} — {{ h.employed_to || 'Present' }}
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span
                                            class="inline-flex items-center gap-1 text-xs font-semibold px-2.5 py-1 rounded"
                                            :class="emailStatusBadge(h.email_sent, !!h.email)"
                                        >
                                            <span class="w-1.5 h-1.5 rounded-full inline-block"
                                                :class="!h.email ? 'bg-slate-400' : h.email_sent ? 'bg-primary' : 'bg-slate-500'" />
                                            {{ !h.email ? 'No Email' : h.email_sent ? 'Sent' : 'Not Sent' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span
                                            v-if="h.verification_status"
                                            class="inline-flex items-center gap-1 text-xs font-semibold px-2.5 py-1 rounded capitalize"
                                            :class="verificationBadge(h.verification_status)"
                                        >
                                            {{ h.verification_status }}
                                        </span>
                                        <span v-else class="inline-flex items-center gap-1 text-xs font-semibold px-2.5 py-1 rounded bg-slate-100 text-slate-700">
                                            Pending
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center justify-center gap-2">
                                            <Link
                                                :href="route('admin.drivers.show', h.driver_id)"
                                                class="p-1.5 rounded-lg border border-primary/30 text-primary hover:bg-primary/10 transition-colors"
                                                title="View driver"
                                            >
                                                <Lucide icon="Eye" class="w-4 h-4" />
                                            </Link>
                                            <Link
                                                :href="route('admin.drivers.employment-verification.show', h.id)"
                                                class="p-1.5 rounded-lg border border-primary/30 text-primary hover:bg-primary/10 transition-colors"
                                                title="View verification details"
                                            >
                                                <Lucide icon="FileCheck" class="w-4 h-4" />
                                            </Link>
                                            <button
                                                v-if="h.email"
                                                @click="sendVerification(h.id)"
                                                class="p-1.5 rounded-lg border border-primary/30 text-primary hover:bg-primary/10 transition-colors"
                                                title="Send/Resend verification email"
                                            >
                                                <Lucide icon="Mail" class="w-4 h-4" />
                                            </button>
                                            <span
                                                v-else
                                                class="p-1.5 rounded-lg border border-slate-200 text-slate-300 cursor-not-allowed"
                                                title="No email available"
                                            >
                                                <Lucide icon="MailX" class="w-4 h-4" />
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="employmentHistory.data.length === 0">
                                    <td colspan="6" class="px-6 py-12 text-center">
                                        <div class="flex flex-col items-center gap-3">
                                            <Lucide icon="Users" class="w-12 h-12 text-slate-300" />
                                            <p class="text-slate-500 font-medium">No employment history records found</p>
                                            <p class="text-sm text-slate-400">This company has no associated employment records yet</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="employmentHistory.last_page > 1" class="p-6 border-t border-slate-200/60 flex flex-wrap items-center gap-1">
                        <template v-for="link in employmentHistory.links" :key="link.label">
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                class="px-3 py-1.5 text-sm rounded-md border transition-colors"
                                :class="link.active ? 'bg-primary text-white border-primary' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50'"
                                v-html="link.label"
                                preserve-scroll
                            />
                            <span
                                v-else
                                class="px-3 py-1.5 text-sm rounded-md border bg-white text-slate-300 border-slate-200 cursor-default"
                                v-html="link.label"
                            />
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <Dialog :open="showEditModal" @close="showEditModal = false" static-backdrop size="xl">
        <Dialog.Panel class="flex max-h-[90vh] w-[95vw] max-w-[900px] flex-col overflow-hidden sm:w-[900px]">
            <div class="border-b border-slate-200 bg-white px-8 py-6">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex items-start gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl border border-primary/20 bg-primary/10">
                            <Lucide icon="Edit" class="h-6 w-6 text-primary" />
                        </div>
                        <div>
                            <h3 class="text-xl font-semibold text-slate-800">Update Company</h3>
                            <p class="mt-1 text-sm text-slate-500">
                                Edit the company details used by linked employment verifications.
                            </p>
                        </div>
                    </div>
                    <button @click="showEditModal = false" class="rounded-xl p-2 text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-600">
                        <Lucide icon="X" class="h-5 w-5" />
                    </button>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto bg-slate-50/50 px-8 py-7">
                <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-6 py-4">
                        <h4 class="text-base font-semibold text-slate-800">Company Information</h4>
                        <p class="mt-1 text-sm text-slate-500">
                            Keep the master directory clean so verification requests stay accurate.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 gap-5 p-6 md:grid-cols-2">
                        <div>
                            <label class="mb-2 flex items-center gap-2 text-sm font-medium text-slate-700">
                                <Lucide icon="Building2" class="h-4 w-4 text-slate-400" />
                                Company Name <span class="text-danger">*</span>
                            </label>
                            <FormInput v-model="form.company_name" type="text" placeholder="Enter company name" />
                            <p v-if="errors.company_name" class="mt-1 text-xs text-danger">{{ errors.company_name[0] }}</p>
                        </div>

                        <div>
                            <label class="mb-2 flex items-center gap-2 text-sm font-medium text-slate-700">
                                <Lucide icon="User" class="h-4 w-4 text-slate-400" />
                                Contact Person
                            </label>
                            <FormInput v-model="form.contact" type="text" placeholder="Enter contact person name" />
                        </div>

                        <div>
                            <label class="mb-2 flex items-center gap-2 text-sm font-medium text-slate-700">
                                <Lucide icon="Mail" class="h-4 w-4 text-slate-400" />
                                Email
                            </label>
                            <FormInput v-model="form.email" type="email" placeholder="Enter email address" />
                            <p v-if="errors.email" class="mt-1 text-xs text-danger">{{ errors.email[0] }}</p>
                        </div>

                        <div>
                            <label class="mb-2 flex items-center gap-2 text-sm font-medium text-slate-700">
                                <Lucide icon="Phone" class="h-4 w-4 text-slate-400" />
                                Phone
                            </label>
                            <FormInput v-model="form.phone" type="text" placeholder="Enter phone number" />
                        </div>

                        <div class="md:col-span-2">
                            <label class="mb-2 flex items-center gap-2 text-sm font-medium text-slate-700">
                                <Lucide icon="MapPin" class="h-4 w-4 text-slate-400" />
                                Address
                            </label>
                            <FormInput v-model="form.address" type="text" placeholder="Enter company address" />
                        </div>

                        <div>
                            <label class="mb-2 flex items-center gap-2 text-sm font-medium text-slate-700">
                                <Lucide icon="Map" class="h-4 w-4 text-slate-400" />
                                City
                            </label>
                            <FormInput v-model="form.city" type="text" placeholder="Enter city" />
                        </div>

                        <div>
                            <label class="mb-2 flex items-center gap-2 text-sm font-medium text-slate-700">
                                <Lucide icon="Flag" class="h-4 w-4 text-slate-400" />
                                State
                            </label>
                            <FormInput v-model="form.state" type="text" maxlength="10" placeholder="Enter state" />
                        </div>

                        <div>
                            <label class="mb-2 flex items-center gap-2 text-sm font-medium text-slate-700">
                                <Lucide icon="Hash" class="h-4 w-4 text-slate-400" />
                                ZIP Code
                            </label>
                            <FormInput v-model="form.zip" type="text" maxlength="20" placeholder="Enter ZIP code" />
                        </div>

                        <div>
                            <label class="mb-2 flex items-center gap-2 text-sm font-medium text-slate-700">
                                <Lucide icon="Printer" class="h-4 w-4 text-slate-400" />
                                Fax
                            </label>
                            <FormInput v-model="form.fax" type="text" placeholder="Enter fax number" />
                        </div>
                    </div>

                    <div class="border-t border-slate-200 bg-warning/10 px-6 py-3 text-xs text-warning">
                        Changing the email will update all linked driver employment records.
                    </div>
                </div>
            </div>

            <div class="border-t border-slate-200 bg-white px-8 py-5">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <Button
                        @click="saveEdit"
                        variant="primary"
                        :disabled="saving"
                        class="min-w-40 gap-2"
                    >
                        <Lucide v-if="saving" icon="Loader" class="h-4 w-4 animate-spin" />
                        <Lucide v-else icon="Check" class="h-4 w-4" />
                        {{ saving ? 'Saving...' : 'Save Changes' }}
                    </Button>
                    <Button variant="outline-secondary" @click="showEditModal = false" class="min-w-32 gap-2">
                        <Lucide icon="X" class="h-4 w-4" />
                        Cancel
                    </Button>
                </div>
            </div>

            <div class="hidden sticky bottom-0 bg-white px-6 py-4 border-t border-slate-200 flex justify-end gap-3">
                <Button variant="outline-secondary" @click="showEditModal = false">Cancel</Button>
                <Button
                    @click="saveEdit"
                    variant="primary"
                    :disabled="saving"
                    class="inline-flex items-center gap-2"
                >
                    <Lucide v-if="saving" icon="Loader" class="w-4 h-4 animate-spin" />
                    {{ saving ? 'Saving…' : 'Save Changes' }}
                </Button>
            </div>
        </Dialog.Panel>
    </Dialog>
</template>
