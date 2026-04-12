<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import Button from '@/components/Base/Button/Button.vue'
import Lucide from '@/components/Base/Lucide'
import RazeLayout from '@/layouts/RazeLayout.vue'

type EffectiveStatus = 'active' | 'pending_review' | 'draft' | 'rejected' | 'inactive'

interface MediaFile {
    id: number
    name: string
    url: string
    size: string
    mime_type: string | null
    created_at: string | null
}

interface LicenseRow {
    id: number
    number: string | null
    state: string | null
    class: string | null
    expiration_date: string | null
    is_cdl: boolean
    is_primary: boolean
    is_expired: boolean
    is_expiring_soon: boolean
    endorsements: string[]
    front_url: string | null
    back_url: string | null
}

interface VehicleRow {
    id: number
    year: number | null
    make: string | null
    model: string | null
    vin: string | null
    type: string | null
    status: string | null
    status_name: string | null
    unit_number: string | null
}

interface TestingRow {
    id: number
    test_type: string | null
    test_result: string | null
    status: string | null
    test_date: string | null
    location: string | null
    administered_by: string | null
    next_test_due: string | null
    categories: string[]
    pdf_url: string | null
}

interface InspectionRow {
    id: number
    inspection_type: string | null
    status: string | null
    inspection_date: string | null
    inspector_name: string | null
    location: string | null
    inspection_level: string | null
    defects_found: string | null
    corrective_actions: string | null
    is_safe_to_operate: boolean
    documents: MediaFile[]
}

interface DocumentCategory {
    label: string
    count: number
    documents: MediaFile[]
}

interface Props {
    driver: {
        id: number
        first_name: string | null
        middle_name: string | null
        last_name: string | null
        full_name: string
        email: string | null
        phone: string | null
        date_of_birth: string | null
        created_at: string | null
        hire_date: string | null
        status_name: string
        effective_status: EffectiveStatus
        photo_url: string
        carrier: {
            id: number
            name: string
            dot_number: string | null
            mc_number: string | null
            address: string | null
        } | null
        application: {
            status: string
            status_name: string
            submitted_date: string | null
        } | null
    }
    stats: {
        total_documents: number
        licenses_count: number
        vehicles_count: number
        trainings_count: number
        testing_count: number
        medical_status: string
    }
    licenses: LicenseRow[]
    medical: {
        expiration_date: string | null
        examiner_name: string | null
        registry_number: string | null
        status: 'expired' | 'expiring_soon' | 'valid' | 'not_set'
        documents: MediaFile[]
    } | null
    vehicles: VehicleRow[]
    trainings: {
        schools: Array<{
            id: number
            name: string | null
            city: string | null
            state: string | null
            graduated: boolean
            date_start: string | null
            date_end: string | null
        }>
        courses: Array<{
            id: number
            organization_name: string | null
            city: string | null
            state: string | null
            certification_date: string | null
            years_experience: number | null
        }>
        assigned: Array<{
            id: number
            name: string
            status: string | null
            assigned_date: string | null
            due_date: string | null
            completed_date: string | null
        }>
    }
    testings: TestingRow[]
    inspections: InspectionRow[]
    documents: DocumentCategory[]
}

const props = defineProps<Props>()

const tabs = [
    { key: 'general', label: 'General', icon: 'User' },
    { key: 'licenses', label: 'Licenses', icon: 'CreditCard' },
    { key: 'medical', label: 'Medical', icon: 'Heart' },
    { key: 'vehicles', label: 'Vehicles', icon: 'Truck' },
    { key: 'trainings', label: 'Trainings', icon: 'GraduationCap' },
    { key: 'testing', label: 'Testing', icon: 'TestTube' },
    { key: 'inspections', label: 'Inspections', icon: 'Search' },
    { key: 'documents', label: 'Documents', icon: 'FileText' },
] as const

type TabKey = typeof tabs[number]['key']

const activeTab = ref<TabKey>('general')

const statusMeta = computed(() => {
    const map: Record<EffectiveStatus, { badge: string; dot: string; label: string }> = {
        active: { badge: 'bg-success/10 text-success', dot: 'bg-success', label: 'Active Driver' },
        pending_review: { badge: 'bg-warning/10 text-warning', dot: 'bg-warning', label: 'Pending Review' },
        draft: { badge: 'bg-slate-200 text-slate-600', dot: 'bg-slate-400', label: 'Draft' },
        rejected: { badge: 'bg-danger/10 text-danger', dot: 'bg-danger', label: 'Rejected' },
        inactive: { badge: 'bg-slate-100 text-slate-600', dot: 'bg-slate-400', label: 'Inactive' },
    }

    return map[props.driver.effective_status]
})

function setTab(tab: TabKey) {
    activeTab.value = tab
    history.replaceState(null, '', `#${tab}`)
}

function syncTabFromHash() {
    const hash = window.location.hash.replace('#', '') as TabKey
    if (tabs.some((tab) => tab.key === hash)) {
        activeTab.value = hash
    }
}

onMounted(() => {
    syncTabFromHash()
    window.addEventListener('hashchange', syncTabFromHash)
})

onBeforeUnmount(() => {
    window.removeEventListener('hashchange', syncTabFromHash)
})

function licenseCardClass(license: LicenseRow) {
    if (license.is_expired) return 'border-danger/30 bg-danger/5'
    if (license.is_expiring_soon) return 'border-warning/30 bg-warning/5'
    return 'border-slate-200 bg-slate-50/60'
}

function licenseBadgeClass(license: LicenseRow) {
    if (license.is_expired) return 'bg-danger/10 text-danger'
    if (license.is_expiring_soon) return 'bg-warning/10 text-warning'
    return 'bg-success/10 text-success'
}

function medicalBadgeClass(status: 'expired' | 'expiring_soon' | 'valid' | 'not_set') {
    if (status === 'expired') return 'bg-danger/10 text-danger'
    if (status === 'expiring_soon') return 'bg-warning/10 text-warning'
    if (status === 'valid') return 'bg-success/10 text-success'
    return 'bg-slate-100 text-slate-600'
}

function trainingStatusClass(status: string | null) {
    if (status === 'completed') return 'bg-success/10 text-success'
    if (status === 'in_progress') return 'bg-info/10 text-info'
    if (status === 'overdue') return 'bg-danger/10 text-danger'
    return 'bg-warning/10 text-warning'
}

function testingResultClass(result: string | null) {
    if (result === 'Negative') return 'bg-success/10 text-success'
    if (result === 'Positive') return 'bg-danger/10 text-danger'
    return 'bg-slate-100 text-slate-600'
}

function inspectionStatusClass(row: InspectionRow) {
    if (row.status === 'failed') return 'bg-danger/10 text-danger'
    if (row.status === 'passed' || row.is_safe_to_operate) return 'bg-success/10 text-success'
    return 'bg-slate-100 text-slate-600'
}

function documentIcon(mimeType: string | null) {
    if (!mimeType) return 'File'
    if (mimeType.includes('pdf')) return 'FileText'
    if (mimeType.includes('image')) return 'Image'
    return 'File'
}

function documentTone(mimeType: string | null) {
    if (!mimeType) return 'text-primary bg-primary/10'
    if (mimeType.includes('pdf')) return 'text-danger bg-danger/10'
    if (mimeType.includes('image')) return 'text-info bg-info/10'
    return 'text-primary bg-primary/10'
}

function prettyText(value: string | null | undefined, fallback = 'N/A') {
    return value && value.trim().length ? value : fallback
}
</script>

<template>
    <Head title="My Profile" />

    <RazeLayout>
        <div class="space-y-6">
            <div class="box box--stacked p-6 sm:p-8">
                <div class="flex flex-col gap-6 xl:flex-row xl:items-center xl:justify-between">
                    <div class="flex flex-col gap-5 md:flex-row md:items-center">
                        <img
                            :src="driver.photo_url"
                            :alt="driver.full_name"
                            class="h-24 w-24 rounded-full border-4 border-white object-cover shadow-lg"
                        >

                        <div class="space-y-3">
                            <div>
                                <h1 class="text-2xl font-semibold text-slate-900 sm:text-3xl">{{ driver.full_name }}</h1>
                                <div class="mt-2 flex flex-wrap items-center gap-3 text-sm text-slate-500">
                                    <div class="flex items-center gap-2">
                                        <Lucide icon="Mail" class="h-4 w-4" />
                                        <span>{{ prettyText(driver.email) }}</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <Lucide icon="Building2" class="h-4 w-4" />
                                        <span>{{ prettyText(driver.carrier?.name, 'No carrier') }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-3">
                                <span class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-medium" :class="statusMeta.badge">
                                    <span class="h-2 w-2 rounded-full" :class="statusMeta.dot" />
                                    {{ statusMeta.label }}
                                </span>
                                <span class="text-xs text-slate-400">Joined {{ prettyText(driver.created_at) }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col gap-3 sm:flex-row">
                        <Link :href="route('driver.profile.edit')">
                            <Button variant="primary" class="w-full gap-2 sm:w-auto">
                                <Lucide icon="Edit" class="h-4 w-4" />
                                Edit Profile
                            </Button>
                        </Link>
                        <a v-if="stats.total_documents > 0" :href="route('driver.profile.download-documents')">
                            <Button variant="outline-secondary" class="w-full gap-2 sm:w-auto">
                                <Lucide icon="Download" class="h-4 w-4" />
                                Download Documents
                            </Button>
                        </a>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-5">
                <div class="box box--stacked p-5">
                    <div class="text-xs uppercase tracking-wide text-slate-400">Documents</div>
                    <div class="mt-2 text-2xl font-semibold text-slate-900">{{ stats.total_documents }}</div>
                    <div class="mt-1 text-sm text-slate-500">Files on record</div>
                </div>
                <div class="box box--stacked p-5">
                    <div class="text-xs uppercase tracking-wide text-slate-400">Licenses</div>
                    <div class="mt-2 text-2xl font-semibold text-slate-900">{{ stats.licenses_count }}</div>
                    <div class="mt-1 text-sm text-slate-500">License records</div>
                </div>
                <div class="box box--stacked p-5">
                    <div class="text-xs uppercase tracking-wide text-slate-400">Vehicles</div>
                    <div class="mt-2 text-2xl font-semibold text-slate-900">{{ stats.vehicles_count }}</div>
                    <div class="mt-1 text-sm text-slate-500">Assigned vehicles</div>
                </div>
                <div class="box box--stacked p-5">
                    <div class="text-xs uppercase tracking-wide text-slate-400">Trainings</div>
                    <div class="mt-2 text-2xl font-semibold text-slate-900">{{ stats.trainings_count }}</div>
                    <div class="mt-1 text-sm text-slate-500">School, courses and assigned</div>
                </div>
                <div class="box box--stacked p-5">
                    <div class="text-xs uppercase tracking-wide text-slate-400">Medical</div>
                    <div class="mt-2 text-lg font-semibold text-slate-900">{{ stats.medical_status }}</div>
                    <div class="mt-1 text-sm text-slate-500">Current medical status</div>
                </div>
            </div>

            <div class="box box--stacked overflow-hidden">
                <div class="border-b border-slate-200 px-4 sm:px-6">
                    <div class="flex gap-1 overflow-x-auto py-2">
                        <button
                            v-for="tab in tabs"
                            :key="tab.key"
                            type="button"
                            class="inline-flex items-center gap-2 whitespace-nowrap rounded-lg px-4 py-2 text-sm font-medium transition"
                            :class="activeTab === tab.key ? 'bg-primary text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100'"
                            @click="setTab(tab.key)"
                        >
                            <Lucide :icon="tab.icon" class="h-4 w-4" />
                            <span>{{ tab.label }}</span>
                        </button>
                    </div>
                </div>

                <div class="p-4 sm:p-6">
                    <div v-if="activeTab === 'general'" class="space-y-8">
                        <section class="space-y-4">
                            <h2 class="text-lg font-semibold text-slate-900">Personal Information</h2>
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                                <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-4">
                                    <div class="text-xs uppercase tracking-wide text-slate-400">Full Name</div>
                                    <div class="mt-2 text-sm font-semibold text-slate-900">{{ driver.full_name }}</div>
                                </div>
                                <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-4">
                                    <div class="text-xs uppercase tracking-wide text-slate-400">Email</div>
                                    <div class="mt-2 text-sm font-semibold text-slate-900">{{ prettyText(driver.email) }}</div>
                                </div>
                                <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-4">
                                    <div class="text-xs uppercase tracking-wide text-slate-400">Phone</div>
                                    <div class="mt-2 text-sm font-semibold text-slate-900">{{ prettyText(driver.phone) }}</div>
                                </div>
                                <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-4">
                                    <div class="text-xs uppercase tracking-wide text-slate-400">Date of Birth</div>
                                    <div class="mt-2 text-sm font-semibold text-slate-900">{{ prettyText(driver.date_of_birth) }}</div>
                                </div>
                                <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-4">
                                    <div class="text-xs uppercase tracking-wide text-slate-400">Status</div>
                                    <div class="mt-2 text-sm font-semibold text-slate-900">{{ driver.status_name }}</div>
                                </div>
                                <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-4">
                                    <div class="text-xs uppercase tracking-wide text-slate-400">Hire Date</div>
                                    <div class="mt-2 text-sm font-semibold text-slate-900">{{ prettyText(driver.hire_date) }}</div>
                                </div>
                            </div>
                        </section>

                        <section class="space-y-4">
                            <h2 class="text-lg font-semibold text-slate-900">Carrier Information</h2>
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                                <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-4">
                                    <div class="text-xs uppercase tracking-wide text-slate-400">Carrier Name</div>
                                    <div class="mt-2 text-sm font-semibold text-slate-900">{{ prettyText(driver.carrier?.name, 'No carrier') }}</div>
                                </div>
                                <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-4">
                                    <div class="text-xs uppercase tracking-wide text-slate-400">DOT Number</div>
                                    <div class="mt-2 text-sm font-semibold text-slate-900">{{ prettyText(driver.carrier?.dot_number) }}</div>
                                </div>
                                <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-4">
                                    <div class="text-xs uppercase tracking-wide text-slate-400">MC Number</div>
                                    <div class="mt-2 text-sm font-semibold text-slate-900">{{ prettyText(driver.carrier?.mc_number) }}</div>
                                </div>
                                <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-4 md:col-span-2 xl:col-span-3">
                                    <div class="text-xs uppercase tracking-wide text-slate-400">Address</div>
                                    <div class="mt-2 text-sm font-semibold text-slate-900">{{ prettyText(driver.carrier?.address) }}</div>
                                </div>
                            </div>
                        </section>

                        <section v-if="driver.application" class="space-y-4">
                            <h2 class="text-lg font-semibold text-slate-900">Application Status</h2>
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                                <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-4">
                                    <div class="text-xs uppercase tracking-wide text-slate-400">Status</div>
                                    <div class="mt-2">
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium"
                                              :class="driver.application.status === 'approved' ? 'bg-success/10 text-success' : driver.application.status === 'pending' ? 'bg-warning/10 text-warning' : 'bg-danger/10 text-danger'">
                                            {{ driver.application.status_name }}
                                        </span>
                                    </div>
                                </div>
                                <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-4">
                                    <div class="text-xs uppercase tracking-wide text-slate-400">Submitted Date</div>
                                    <div class="mt-2 text-sm font-semibold text-slate-900">{{ prettyText(driver.application.submitted_date) }}</div>
                                </div>
                            </div>
                        </section>
                    </div>

                    <div v-else-if="activeTab === 'licenses'" class="space-y-4">
                        <template v-if="licenses.length">
                            <div
                                v-for="license in licenses"
                                :key="license.id"
                                class="rounded-xl border p-5"
                                :class="licenseCardClass(license)"
                            >
                                <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                                    <div class="space-y-4">
                                        <div class="flex flex-wrap items-center gap-3">
                                            <div class="inline-flex items-center gap-2 text-base font-semibold text-slate-900">
                                                <Lucide icon="CreditCard" class="h-5 w-5 text-primary" />
                                                {{ prettyText(license.number) }}
                                            </div>
                                            <span v-if="license.is_primary" class="inline-flex rounded-full bg-primary/10 px-2.5 py-1 text-xs font-medium text-primary">Primary</span>
                                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium" :class="licenseBadgeClass(license)">
                                                {{ license.is_expired ? 'Expired' : license.is_expiring_soon ? 'Expiring Soon' : 'Valid' }}
                                            </span>
                                        </div>

                                        <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                                            <div>
                                                <div class="text-xs uppercase tracking-wide text-slate-400">State</div>
                                                <div class="mt-1 text-sm font-semibold text-slate-900">{{ prettyText(license.state) }}</div>
                                            </div>
                                            <div>
                                                <div class="text-xs uppercase tracking-wide text-slate-400">Class</div>
                                                <div class="mt-1 text-sm font-semibold text-slate-900">{{ prettyText(license.class) }}</div>
                                            </div>
                                            <div>
                                                <div class="text-xs uppercase tracking-wide text-slate-400">Expiration</div>
                                                <div class="mt-1 text-sm font-semibold" :class="license.is_expired ? 'text-danger' : license.is_expiring_soon ? 'text-warning' : 'text-slate-900'">
                                                    {{ prettyText(license.expiration_date) }}
                                                </div>
                                            </div>
                                            <div>
                                                <div class="text-xs uppercase tracking-wide text-slate-400">CDL</div>
                                                <div class="mt-1 text-sm font-semibold text-slate-900">{{ license.is_cdl ? 'Yes' : 'No' }}</div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex gap-3 text-sm">
                                        <a v-if="license.front_url" :href="license.front_url" target="_blank" class="inline-flex items-center gap-1 text-primary hover:underline">
                                            <Lucide icon="Eye" class="h-4 w-4" />
                                            Front
                                        </a>
                                        <a v-if="license.back_url" :href="license.back_url" target="_blank" class="inline-flex items-center gap-1 text-primary hover:underline">
                                            <Lucide icon="Eye" class="h-4 w-4" />
                                            Back
                                        </a>
                                    </div>
                                </div>
                                <div v-if="license.endorsements.length" class="mt-4">
                                    <div class="text-xs uppercase tracking-wide text-slate-400">Endorsements</div>
                                    <div class="mt-2 flex flex-wrap gap-2">
                                        <span
                                            v-for="endorsement in license.endorsements"
                                            :key="endorsement"
                                            class="inline-flex rounded-full bg-slate-200 px-2.5 py-1 text-xs font-medium text-slate-700"
                                        >
                                            {{ endorsement }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </template>
                        <div v-else class="rounded-xl border border-dashed border-slate-300 px-6 py-14 text-center">
                            <Lucide icon="CreditCard" class="mx-auto h-12 w-12 text-slate-300" />
                            <div class="mt-4 text-lg font-semibold text-slate-700">No Licenses Found</div>
                            <div class="mt-1 text-sm text-slate-500">You do not have license records on file yet.</div>
                        </div>
                    </div>

                    <div v-else-if="activeTab === 'medical'" class="space-y-5">
                        <template v-if="medical">
                            <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-5">
                                <div class="flex flex-wrap items-center gap-3">
                                    <div class="inline-flex items-center gap-2 text-base font-semibold text-slate-900">
                                        <Lucide icon="Heart" class="h-5 w-5 text-primary" />
                                        Medical Certificate
                                    </div>
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium" :class="medicalBadgeClass(medical.status)">
                                        {{ medical.status === 'expired' ? 'Expired' : medical.status === 'expiring_soon' ? 'Expiring Soon' : medical.status === 'valid' ? 'Valid' : 'Not Set' }}
                                    </span>
                                </div>

                                <div class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                                    <div>
                                        <div class="text-xs uppercase tracking-wide text-slate-400">Expiration Date</div>
                                        <div class="mt-1 text-sm font-semibold text-slate-900">{{ prettyText(medical.expiration_date) }}</div>
                                    </div>
                                    <div>
                                        <div class="text-xs uppercase tracking-wide text-slate-400">Examiner Name</div>
                                        <div class="mt-1 text-sm font-semibold text-slate-900">{{ prettyText(medical.examiner_name) }}</div>
                                    </div>
                                    <div>
                                        <div class="text-xs uppercase tracking-wide text-slate-400">Registry Number</div>
                                        <div class="mt-1 text-sm font-semibold text-slate-900">{{ prettyText(medical.registry_number) }}</div>
                                    </div>
                                </div>
                            </div>

                            <div v-if="medical.documents.length" class="space-y-3">
                                <h3 class="text-base font-semibold text-slate-900">Medical Documents</h3>
                                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                                    <div
                                        v-for="document in medical.documents"
                                        :key="document.id"
                                        class="rounded-xl border border-slate-200 bg-white p-4"
                                    >
                                        <div class="flex items-start gap-3">
                                            <div class="rounded-lg p-2" :class="documentTone(document.mime_type)">
                                                <Lucide :icon="documentIcon(document.mime_type)" class="h-5 w-5" />
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <div class="truncate text-sm font-semibold text-slate-900">{{ document.name }}</div>
                                                <div class="mt-1 text-xs text-slate-500">{{ document.size }}<span v-if="document.created_at"> | {{ document.created_at }}</span></div>
                                            </div>
                                            <a :href="document.url" target="_blank" class="text-primary hover:text-primary/80">
                                                <Lucide icon="Download" class="h-5 w-5" />
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <div v-else class="rounded-xl border border-dashed border-slate-300 px-6 py-14 text-center">
                            <Lucide icon="Heart" class="mx-auto h-12 w-12 text-slate-300" />
                            <div class="mt-4 text-lg font-semibold text-slate-700">No Medical Records</div>
                            <div class="mt-1 text-sm text-slate-500">You do not have medical qualification records on file.</div>
                        </div>
                    </div>

                    <div v-else-if="activeTab === 'vehicles'" class="space-y-4">
                        <template v-if="vehicles.length">
                            <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
                                <div
                                    v-for="vehicle in vehicles"
                                    :key="vehicle.id"
                                    class="rounded-xl border border-slate-200 bg-slate-50/60 p-5"
                                >
                                    <div class="flex items-start gap-4">
                                        <div class="rounded-xl bg-info/10 p-3">
                                            <Lucide icon="Truck" class="h-7 w-7 text-info" />
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <div class="text-base font-semibold text-slate-900">
                                                {{ [vehicle.year, vehicle.make, vehicle.model].filter(Boolean).join(' ') || 'Assigned Vehicle' }}
                                            </div>
                                            <div class="mt-1 text-sm text-slate-500">{{ prettyText(vehicle.unit_number, 'No unit number') }}</div>
                                            <div class="mt-4 grid grid-cols-2 gap-4">
                                                <div>
                                                    <div class="text-xs uppercase tracking-wide text-slate-400">VIN</div>
                                                    <div class="mt-1 text-sm font-semibold text-slate-900">{{ prettyText(vehicle.vin) }}</div>
                                                </div>
                                                <div>
                                                    <div class="text-xs uppercase tracking-wide text-slate-400">Type</div>
                                                    <div class="mt-1 text-sm font-semibold text-slate-900">{{ prettyText(vehicle.type) }}</div>
                                                </div>
                                                <div>
                                                    <div class="text-xs uppercase tracking-wide text-slate-400">Status</div>
                                                    <div class="mt-1 text-sm font-semibold text-slate-900">{{ prettyText(vehicle.status_name) }}</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                        <div v-else class="rounded-xl border border-dashed border-slate-300 px-6 py-14 text-center">
                            <Lucide icon="Truck" class="mx-auto h-12 w-12 text-slate-300" />
                            <div class="mt-4 text-lg font-semibold text-slate-700">No Vehicles Assigned</div>
                            <div class="mt-1 text-sm text-slate-500">You do not have vehicles assigned at this time.</div>
                        </div>
                    </div>

                    <div v-else-if="activeTab === 'trainings'" class="space-y-6">
                        <template v-if="trainings.schools.length || trainings.courses.length || trainings.assigned.length">
                            <section v-if="trainings.schools.length" class="space-y-3">
                                <h3 class="text-base font-semibold text-slate-900">Training Schools</h3>
                                <div class="space-y-3">
                                    <div v-for="school in trainings.schools" :key="school.id" class="rounded-xl border border-slate-200 bg-slate-50/60 p-4">
                                        <div class="flex flex-wrap items-center gap-3">
                                            <div class="inline-flex items-center gap-2 text-sm font-semibold text-slate-900">
                                                <Lucide icon="GraduationCap" class="h-5 w-5 text-success" />
                                                {{ prettyText(school.name, 'Training School') }}
                                            </div>
                                            <span v-if="school.graduated" class="inline-flex rounded-full bg-success/10 px-2.5 py-1 text-xs font-medium text-success">Graduated</span>
                                        </div>
                                        <div class="mt-4 grid grid-cols-2 gap-4 md:grid-cols-4">
                                            <div>
                                                <div class="text-xs uppercase tracking-wide text-slate-400">Location</div>
                                                <div class="mt-1 text-sm font-semibold text-slate-900">{{ [school.city, school.state].filter(Boolean).join(', ') || 'N/A' }}</div>
                                            </div>
                                            <div>
                                                <div class="text-xs uppercase tracking-wide text-slate-400">Start Date</div>
                                                <div class="mt-1 text-sm font-semibold text-slate-900">{{ prettyText(school.date_start) }}</div>
                                            </div>
                                            <div>
                                                <div class="text-xs uppercase tracking-wide text-slate-400">End Date</div>
                                                <div class="mt-1 text-sm font-semibold text-slate-900">{{ prettyText(school.date_end) }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </section>

                            <section v-if="trainings.courses.length" class="space-y-3">
                                <h3 class="text-base font-semibold text-slate-900">Courses & Certifications</h3>
                                <div class="space-y-3">
                                    <div v-for="course in trainings.courses" :key="course.id" class="rounded-xl border border-slate-200 bg-slate-50/60 p-4">
                                        <div class="inline-flex items-center gap-2 text-sm font-semibold text-slate-900">
                                            <Lucide icon="Award" class="h-5 w-5 text-info" />
                                            {{ prettyText(course.organization_name, 'Course') }}
                                        </div>
                                        <div class="mt-4 grid grid-cols-2 gap-4 md:grid-cols-4">
                                            <div>
                                                <div class="text-xs uppercase tracking-wide text-slate-400">Location</div>
                                                <div class="mt-1 text-sm font-semibold text-slate-900">{{ [course.city, course.state].filter(Boolean).join(', ') || 'N/A' }}</div>
                                            </div>
                                            <div>
                                                <div class="text-xs uppercase tracking-wide text-slate-400">Certification Date</div>
                                                <div class="mt-1 text-sm font-semibold text-slate-900">{{ prettyText(course.certification_date) }}</div>
                                            </div>
                                            <div>
                                                <div class="text-xs uppercase tracking-wide text-slate-400">Experience</div>
                                                <div class="mt-1 text-sm font-semibold text-slate-900">{{ course.years_experience ?? 0 }} years</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </section>

                            <section v-if="trainings.assigned.length" class="space-y-3">
                                <h3 class="text-base font-semibold text-slate-900">Assigned Trainings</h3>
                                <div class="space-y-3">
                                    <div v-for="training in trainings.assigned" :key="training.id" class="rounded-xl border border-slate-200 bg-slate-50/60 p-4">
                                        <div class="flex flex-wrap items-center gap-3">
                                            <div class="inline-flex items-center gap-2 text-sm font-semibold text-slate-900">
                                                <Lucide icon="BookOpen" class="h-5 w-5 text-primary" />
                                                {{ training.name }}
                                            </div>
                                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium" :class="trainingStatusClass(training.status)">
                                                {{ training.status ? training.status.replaceAll('_', ' ') : 'Assigned' }}
                                            </span>
                                        </div>
                                        <div class="mt-4 grid grid-cols-2 gap-4 md:grid-cols-4">
                                            <div>
                                                <div class="text-xs uppercase tracking-wide text-slate-400">Assigned Date</div>
                                                <div class="mt-1 text-sm font-semibold text-slate-900">{{ prettyText(training.assigned_date) }}</div>
                                            </div>
                                            <div>
                                                <div class="text-xs uppercase tracking-wide text-slate-400">Due Date</div>
                                                <div class="mt-1 text-sm font-semibold text-slate-900">{{ prettyText(training.due_date) }}</div>
                                            </div>
                                            <div v-if="training.completed_date">
                                                <div class="text-xs uppercase tracking-wide text-slate-400">Completed Date</div>
                                                <div class="mt-1 text-sm font-semibold text-slate-900">{{ training.completed_date }}</div>
                                            </div>
                                        </div>
                                        <div class="mt-4 flex justify-end">
                                            <Link
                                                :href="route('driver.trainings.show', training.id)"
                                                class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100"
                                            >
                                                <Lucide icon="ArrowRight" class="h-4 w-4" />
                                                Open Training
                                            </Link>
                                        </div>
                                    </div>
                                </div>
                            </section>
                        </template>
                        <div v-else class="rounded-xl border border-dashed border-slate-300 px-6 py-14 text-center">
                            <Lucide icon="GraduationCap" class="mx-auto h-12 w-12 text-slate-300" />
                            <div class="mt-4 text-lg font-semibold text-slate-700">No Training Records</div>
                            <div class="mt-1 text-sm text-slate-500">You do not have training records on file.</div>
                        </div>
                    </div>

                    <div v-else-if="activeTab === 'testing'" class="space-y-4">
                        <template v-if="testings.length">
                            <div v-for="testing in testings" :key="testing.id" class="rounded-xl border border-slate-200 bg-slate-50/60 p-5">
                                <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                                    <div class="space-y-4">
                                        <div class="flex flex-wrap items-center gap-3">
                                            <div class="inline-flex items-center gap-2 text-base font-semibold text-slate-900">
                                                <Lucide icon="TestTube" class="h-5 w-5 text-warning" />
                                                {{ prettyText(testing.test_type, 'Test') }}
                                            </div>
                                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium" :class="testingResultClass(testing.test_result)">
                                                {{ prettyText(testing.test_result, 'Pending') }}
                                            </span>
                                            <span v-if="testing.status" class="inline-flex rounded-full bg-info/10 px-2.5 py-1 text-xs font-medium text-info">{{ testing.status }}</span>
                                        </div>

                                        <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                                            <div>
                                                <div class="text-xs uppercase tracking-wide text-slate-400">Test Date</div>
                                                <div class="mt-1 text-sm font-semibold text-slate-900">{{ prettyText(testing.test_date) }}</div>
                                            </div>
                                            <div>
                                                <div class="text-xs uppercase tracking-wide text-slate-400">Location</div>
                                                <div class="mt-1 text-sm font-semibold text-slate-900">{{ prettyText(testing.location) }}</div>
                                            </div>
                                            <div>
                                                <div class="text-xs uppercase tracking-wide text-slate-400">Administered By</div>
                                                <div class="mt-1 text-sm font-semibold text-slate-900">{{ prettyText(testing.administered_by) }}</div>
                                            </div>
                                            <div>
                                                <div class="text-xs uppercase tracking-wide text-slate-400">Next Test Due</div>
                                                <div class="mt-1 text-sm font-semibold text-slate-900">{{ prettyText(testing.next_test_due) }}</div>
                                            </div>
                                        </div>

                                        <div v-if="testing.categories.length">
                                            <div class="text-xs uppercase tracking-wide text-slate-400">Test Categories</div>
                                            <div class="mt-2 flex flex-wrap gap-2">
                                                <span v-for="category in testing.categories" :key="category" class="inline-flex rounded-full bg-slate-200 px-2.5 py-1 text-xs font-medium text-slate-700">
                                                    {{ category }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <a v-if="testing.pdf_url" :href="testing.pdf_url" target="_blank" class="inline-flex items-center gap-2 text-sm font-medium text-primary hover:underline">
                                        <Lucide icon="Download" class="h-4 w-4" />
                                        PDF
                                    </a>
                                </div>
                            </div>
                        </template>
                        <div v-else class="rounded-xl border border-dashed border-slate-300 px-6 py-14 text-center">
                            <Lucide icon="TestTube" class="mx-auto h-12 w-12 text-slate-300" />
                            <div class="mt-4 text-lg font-semibold text-slate-700">No Test Records</div>
                            <div class="mt-1 text-sm text-slate-500">You do not have drug or alcohol testing records on file.</div>
                        </div>
                    </div>

                    <div v-else-if="activeTab === 'inspections'" class="space-y-4">
                        <template v-if="inspections.length">
                            <div v-for="inspection in inspections" :key="inspection.id" class="rounded-xl border border-slate-200 bg-slate-50/60 p-5">
                                <div class="space-y-4">
                                    <div class="flex flex-wrap items-center gap-3">
                                        <div class="inline-flex items-center gap-2 text-base font-semibold text-slate-900">
                                            <Lucide icon="Search" class="h-5 w-5 text-info" />
                                            {{ prettyText(inspection.inspection_type, 'Inspection') }}
                                        </div>
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium" :class="inspectionStatusClass(inspection)">
                                            {{ inspection.status === 'failed' ? 'Failed' : inspection.status === 'passed' || inspection.is_safe_to_operate ? 'Passed' : prettyText(inspection.status, 'Pending') }}
                                        </span>
                                    </div>

                                    <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                                        <div>
                                            <div class="text-xs uppercase tracking-wide text-slate-400">Inspection Date</div>
                                            <div class="mt-1 text-sm font-semibold text-slate-900">{{ prettyText(inspection.inspection_date) }}</div>
                                        </div>
                                        <div>
                                            <div class="text-xs uppercase tracking-wide text-slate-400">Inspector</div>
                                            <div class="mt-1 text-sm font-semibold text-slate-900">{{ prettyText(inspection.inspector_name) }}</div>
                                        </div>
                                        <div>
                                            <div class="text-xs uppercase tracking-wide text-slate-400">Location</div>
                                            <div class="mt-1 text-sm font-semibold text-slate-900">{{ prettyText(inspection.location) }}</div>
                                        </div>
                                        <div>
                                            <div class="text-xs uppercase tracking-wide text-slate-400">Level</div>
                                            <div class="mt-1 text-sm font-semibold text-slate-900">{{ prettyText(inspection.inspection_level) }}</div>
                                        </div>
                                    </div>

                                    <div v-if="inspection.defects_found">
                                        <div class="text-xs uppercase tracking-wide text-slate-400">Defects Found</div>
                                        <div class="mt-1 text-sm text-slate-700">{{ inspection.defects_found }}</div>
                                    </div>

                                    <div v-if="inspection.corrective_actions">
                                        <div class="text-xs uppercase tracking-wide text-slate-400">Corrective Actions</div>
                                        <div class="mt-1 text-sm text-slate-700">{{ inspection.corrective_actions }}</div>
                                    </div>

                                    <div v-if="inspection.documents.length" class="flex flex-wrap gap-3">
                                        <a
                                            v-for="document in inspection.documents"
                                            :key="document.id"
                                            :href="document.url"
                                            target="_blank"
                                            class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-primary hover:bg-slate-50"
                                        >
                                            <Lucide icon="Download" class="h-4 w-4" />
                                            {{ document.name }}
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </template>
                        <div v-else class="rounded-xl border border-dashed border-slate-300 px-6 py-14 text-center">
                            <Lucide icon="Search" class="mx-auto h-12 w-12 text-slate-300" />
                            <div class="mt-4 text-lg font-semibold text-slate-700">No Inspection Records</div>
                            <div class="mt-1 text-sm text-slate-500">You do not have vehicle inspection records on file.</div>
                        </div>
                    </div>

                    <div v-else class="space-y-6">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h2 class="text-lg font-semibold text-slate-900">All Documents</h2>
                                <p class="mt-1 text-sm text-slate-500">Grouped across licenses, medical, training, testing, inspections and other uploaded records.</p>
                            </div>
                            <a v-if="stats.total_documents > 0" :href="route('driver.profile.download-documents')">
                                <Button variant="primary" class="gap-2">
                                    <Lucide icon="Download" class="h-4 w-4" />
                                    Download All
                                </Button>
                            </a>
                        </div>

                        <template v-if="documents.length">
                            <section v-for="category in documents" :key="category.label" class="space-y-3">
                                <div class="flex items-center gap-2 text-base font-semibold text-slate-900">
                                    <Lucide icon="Folder" class="h-4 w-4 text-primary" />
                                    {{ category.label }}
                                    <span class="text-sm font-medium text-slate-400">({{ category.count }})</span>
                                </div>

                                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                                    <div
                                        v-for="document in category.documents"
                                        :key="document.id"
                                        class="rounded-xl border border-slate-200 bg-white p-4 transition hover:shadow-sm"
                                    >
                                        <div class="flex items-start gap-3">
                                            <div class="rounded-lg p-2" :class="documentTone(document.mime_type)">
                                                <Lucide :icon="documentIcon(document.mime_type)" class="h-5 w-5" />
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <div class="truncate text-sm font-semibold text-slate-900">{{ document.name }}</div>
                                                <div class="mt-1 text-xs text-slate-500">
                                                    {{ document.size }}<span v-if="document.created_at"> | {{ document.created_at }}</span>
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <a :href="document.url" target="_blank" class="text-slate-400 hover:text-primary" title="View">
                                                    <Lucide icon="Eye" class="h-5 w-5" />
                                                </a>
                                                <a :href="document.url" download class="text-slate-400 hover:text-primary" title="Download">
                                                    <Lucide icon="Download" class="h-5 w-5" />
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </section>
                        </template>
                        <div v-else class="rounded-xl border border-dashed border-slate-300 px-6 py-14 text-center">
                            <Lucide icon="FileText" class="mx-auto h-12 w-12 text-slate-300" />
                            <div class="mt-4 text-lg font-semibold text-slate-700">No Documents</div>
                            <div class="mt-1 text-sm text-slate-500">You do not have documents uploaded yet.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </RazeLayout>
</template>
