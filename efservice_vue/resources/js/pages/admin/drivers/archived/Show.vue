<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { ref } from 'vue'
import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import RazeLayout from '@/layouts/RazeLayout.vue'

declare function route(name: string, params?: any): string

defineOptions({ layout: RazeLayout })

const props = defineProps<{ archive: any; sections: any; stats: any }>()
const activeTab = ref('personal')

const tabs = [
    { id: 'personal', label: 'Personal', icon: 'User' },
    { id: 'licenses', label: 'Licenses', icon: 'CreditCard' },
    { id: 'medical', label: 'Medical', icon: 'Heart' },
    { id: 'employment', label: 'Employment', icon: 'Briefcase' },
    { id: 'training', label: 'Training', icon: 'GraduationCap' },
    { id: 'testing', label: 'Testing', icon: 'TestTube' },
    { id: 'safety', label: 'Safety', icon: 'Shield' },
    { id: 'hos', label: 'HOS', icon: 'Clock' },
    { id: 'vehicles', label: 'Vehicles', icon: 'Truck' },
    { id: 'documents', label: 'Documents', icon: 'Files' },
    { id: 'migration', label: 'Migration', icon: 'ArrowRightLeft' },
]

function formatDate(value: string | null | undefined, withTime = false) {
    if (!value) return 'N/A'
    const date = new Date(value)
    if (Number.isNaN(date.getTime())) return value
    return withTime ? date.toLocaleString('en-US') : date.toLocaleDateString('en-US')
}

function formatBytes(bytes: number | null | undefined) {
    if (!bytes) return '0 B'
    const units = ['B', 'KB', 'MB', 'GB']
    let value = bytes
    let idx = 0
    while (value >= 1024 && idx < units.length - 1) { value /= 1024; idx++ }
    return `${value.toFixed(value >= 10 || idx === 0 ? 0 : 1)} ${units[idx]}`
}

function badge(value: string | null | undefined) {
    const v = String(value || '').toLowerCase()
    if (['archived', 'active', 'valid', 'verified', 'completed'].includes(v)) return 'bg-emerald-100 text-emerald-700'
    if (['expired', 'failed', 'termination'].includes(v)) return 'bg-red-100 text-red-600'
    if (['pending', 'in_progress', 'restored'].includes(v)) return 'bg-amber-100 text-amber-700'
    return 'bg-slate-100 text-slate-600'
}
function labelize(value: string) { return value.replace(/_/g, ' ') }
function formatMigrationValue(key: string, value: unknown) {
    if (typeof value === 'boolean') return value ? 'Yes' : 'No'
    if (value == null || value === '') return 'N/A'
    return key.includes('at') ? formatDate(String(value), true) : String(value)
}
</script>

<template>
    <Head :title="`Archived Driver: ${archive.full_name}`" />

    <div class="grid grid-cols-12 gap-x-6 gap-y-10">
        <div class="col-span-12">
            <div class="box box--stacked p-5 border border-amber-200 bg-amber-50/70">
                <div class="flex items-start gap-4">
                    <div class="p-3 bg-amber-100 rounded-xl"><Lucide icon="Archive" class="w-6 h-6 text-amber-700" /></div>
                    <div>
                        <h2 class="text-lg font-semibold text-slate-800">Archived Driver Record</h2>
                        <p class="text-sm text-slate-600 mt-1">Read-only snapshot archived on {{ formatDate(archive.archived_at, true) }}.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12">
            <div class="box box--stacked p-6">
                <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
                    <div class="flex items-start gap-5">
                        <div class="w-20 h-20 rounded-xl bg-slate-100 border border-slate-200 overflow-hidden flex items-center justify-center">
                            <img v-if="archive.profile_photo_url" :src="archive.profile_photo_url" :alt="archive.full_name" class="w-full h-full object-cover" />
                            <Lucide v-else icon="User" class="w-10 h-10 text-slate-400" />
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-slate-800">{{ archive.full_name }}</h1>
                            <div class="mt-2 flex flex-wrap items-center gap-3 text-sm text-slate-500">
                                <span v-if="archive.email" class="inline-flex items-center gap-1.5"><Lucide icon="Mail" class="w-4 h-4" />{{ archive.email }}</span>
                                <span v-if="archive.phone" class="inline-flex items-center gap-1.5"><Lucide icon="Phone" class="w-4 h-4" />{{ archive.phone }}</span>
                                <span v-if="archive.carrier_name" class="inline-flex items-center gap-1.5"><Lucide icon="Building2" class="w-4 h-4" />{{ archive.carrier_name }}</span>
                            </div>
                            <div class="mt-3 flex flex-wrap items-center gap-2">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium capitalize" :class="badge(archive.archive_reason)">{{ labelize(archive.archive_reason) }}</span>
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium capitalize" :class="badge(archive.status)">{{ archive.status }}</span>
                            </div>
                        </div>
                    </div>
                    <Link :href="route('admin.drivers.archived.index')">
                        <Button variant="outline-secondary" class="flex items-center gap-2"><Lucide icon="ArrowLeft" class="w-4 h-4" />Back to Archived</Button>
                    </Link>
                </div>
            </div>
        </div>

        <div class="col-span-12">
            <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-4">
                <div class="box box--stacked p-4 text-center"><p class="text-2xl font-semibold text-slate-800">{{ archive.document_count }}</p><p class="text-xs text-slate-500 mt-1">Documents</p></div>
                <div class="box box--stacked p-4 text-center"><p class="text-2xl font-semibold text-slate-800">{{ stats.licenses }}</p><p class="text-xs text-slate-500 mt-1">Licenses</p></div>
                <div class="box box--stacked p-4 text-center"><p class="text-2xl font-semibold text-slate-800">{{ stats.medical }}</p><p class="text-xs text-slate-500 mt-1">Medical</p></div>
                <div class="box box--stacked p-4 text-center"><p class="text-2xl font-semibold text-slate-800">{{ stats.employment }}</p><p class="text-xs text-slate-500 mt-1">Employment</p></div>
                <div class="box box--stacked p-4 text-center"><p class="text-2xl font-semibold text-slate-800">{{ stats.training }}</p><p class="text-xs text-slate-500 mt-1">Training</p></div>
                <div class="box box--stacked p-4 text-center"><p class="text-2xl font-semibold text-slate-800">{{ stats.safety }}</p><p class="text-xs text-slate-500 mt-1">Safety</p></div>
            </div>
        </div>

        <div class="col-span-12">
            <div class="box box--stacked overflow-hidden">
                <div class="overflow-x-auto border-b border-slate-200/60">
                    <div class="flex min-w-max bg-white">
                        <button v-for="tab in tabs" :key="tab.id" type="button" @click="activeTab = tab.id"
                            class="inline-flex items-center gap-2 px-4 py-3 text-sm font-medium border-b-2 transition"
                            :class="activeTab === tab.id ? 'border-primary text-primary bg-primary/5' : 'border-transparent text-slate-600 hover:text-slate-800 hover:bg-slate-50'">
                            <Lucide :icon="tab.icon" class="w-4 h-4" />{{ tab.label }}
                        </button>
                    </div>
                </div>

                <div class="p-6">
                    <div v-show="activeTab === 'personal'" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                        <div class="box box--stacked p-4" v-for="(value, key) in sections.personal" :key="key">
                            <p class="text-xs uppercase tracking-wide text-slate-400">{{ labelize(String(key)) }}</p>
                            <p class="mt-2 text-sm font-medium text-slate-700 break-words">{{ String(key) === 'date_of_birth' || String(key) === 'driver_license_expiration' ? formatDate(String(value)) : value }}</p>
                        </div>
                        <div v-if="!Object.keys(sections.personal || {}).length" class="col-span-full text-sm text-slate-500">This archived record does not contain personal information.</div>
                    </div>

                    <div v-show="activeTab === 'licenses'">
                        <div v-if="sections.licenses.length" class="grid grid-cols-1 xl:grid-cols-2 gap-4">
                            <div v-for="(license, index) in sections.licenses" :key="index" class="box box--stacked p-5">
                                <div class="flex items-start justify-between gap-3">
                                    <div><h3 class="text-base font-semibold text-slate-800">{{ license.license_number || 'No number' }}</h3><p class="text-sm text-slate-500 mt-1">{{ license.license_type || 'License' }} • {{ license.state || 'N/A' }}</p></div>
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium capitalize" :class="badge(license.status)">{{ license.status || 'unknown' }}</span>
                                </div>
                                <div class="grid grid-cols-2 gap-4 mt-4 text-sm">
                                    <div><p class="text-slate-400">Class</p><p class="mt-1 text-slate-700">{{ license.class || 'N/A' }}</p></div>
                                    <div><p class="text-slate-400">Issue Date</p><p class="mt-1 text-slate-700">{{ formatDate(license.issue_date) }}</p></div>
                                    <div><p class="text-slate-400">Expiration</p><p class="mt-1 text-slate-700">{{ formatDate(license.expiration_date) }}</p></div>
                                    <div><p class="text-slate-400">Endorsements</p><p class="mt-1 text-slate-700">{{ license.endorsements?.length ? license.endorsements.join(', ') : 'None' }}</p></div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-sm text-slate-500">This archived record does not contain license information.</div>
                    </div>

                    <div v-show="activeTab === 'medical'" class="space-y-4">
                        <div v-if="sections.medical.length" class="space-y-4">
                            <div v-for="(record, index) in sections.medical" :key="index" class="box box--stacked p-5">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <h3 class="text-base font-semibold text-slate-800">{{ record.exam_type || 'DOT Medical Examination' }}</h3>
                                        <p class="text-sm text-slate-500 mt-1">Examiner: {{ record.examiner_name || 'N/A' }}</p>
                                    </div>
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium capitalize" :class="badge(record.status)">{{ record.status || 'N/A' }}</span>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 mt-4 text-sm">
                                    <div><p class="text-slate-400">Exam Date</p><p class="mt-1 text-slate-700">{{ formatDate(record.exam_date) }}</p></div>
                                    <div><p class="text-slate-400">Expiration Date</p><p class="mt-1 text-slate-700">{{ formatDate(record.expiration_date) }}</p></div>
                                    <div><p class="text-slate-400">Registry</p><p class="mt-1 text-slate-700">{{ record.examiner_registry || 'N/A' }}</p></div>
                                    <div><p class="text-slate-400">Certificate Number</p><p class="mt-1 text-slate-700">{{ record.certificate_number || 'N/A' }}</p></div>
                                    <div><p class="text-slate-400">Examiner License</p><p class="mt-1 text-slate-700">{{ record.examiner_license || 'N/A' }}</p></div>
                                    <div><p class="text-slate-400">Certification Type</p><p class="mt-1 text-slate-700">{{ record.certification_type || 'N/A' }}</p></div>
                                </div>
                                <div v-if="record.restrictions || record.has_variance || record.notes" class="grid grid-cols-1 xl:grid-cols-3 gap-4 mt-4 text-sm">
                                    <div v-if="record.restrictions" class="xl:col-span-2"><p class="text-slate-400">Restrictions</p><p class="mt-1 text-slate-700">{{ Array.isArray(record.restrictions) ? record.restrictions.join(', ') : record.restrictions }}</p></div>
                                    <div v-if="record.has_variance"><p class="text-slate-400">Medical Variance</p><p class="mt-1 text-slate-700">Variance Granted</p></div>
                                    <div v-if="record.notes" class="xl:col-span-3"><p class="text-slate-400">Notes</p><p class="mt-1 text-slate-700">{{ record.notes }}</p></div>
                                </div>
                            </div>
                        </div>
                        <div v-if="sections.medical_documents?.length" class="box box--stacked p-5">
                            <h3 class="text-base font-semibold text-slate-800 mb-4">Medical Documents</h3>
                            <div class="overflow-x-auto rounded-lg border border-slate-200">
                                <table class="w-full text-sm">
                                    <thead class="bg-slate-50"><tr><th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Name</th><th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Type</th><th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Size</th><th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Created</th><th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Action</th></tr></thead>
                                    <tbody><tr v-for="(document, index) in sections.medical_documents" :key="index" class="border-t border-slate-100"><td class="px-4 py-3">{{ document.name }}</td><td class="px-4 py-3">{{ document.mime_type || 'N/A' }}</td><td class="px-4 py-3">{{ formatBytes(document.size) }}</td><td class="px-4 py-3">{{ formatDate(document.created_at, true) }}</td><td class="px-4 py-3"><a v-if="document.url" :href="document.url" target="_blank" class="inline-flex items-center gap-1 text-primary hover:underline"><Lucide icon="ExternalLink" class="w-3.5 h-3.5" />Open</a><span v-else class="text-slate-400">Unavailable</span></td></tr></tbody>
                                </table>
                            </div>
                        </div>
                        <div v-if="!sections.medical.length && !sections.medical_documents?.length" class="text-sm text-slate-500">This archived record does not contain medical information.</div>
                    </div>

                    <div v-show="activeTab === 'employment'" class="space-y-4">
                        <div v-if="sections.employment.length" v-for="(job, index) in sections.employment" :key="index" class="box box--stacked p-5">
                            <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
                                <div><h3 class="text-base font-semibold text-slate-800">{{ job.employer_name }}</h3><p class="text-sm text-slate-500 mt-1">{{ job.position || 'Position not provided' }}</p></div>
                                <div class="text-sm text-slate-500">{{ formatDate(job.start_date) }} to {{ formatDate(job.end_date) }}</div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 mt-4 text-sm">
                                <div><p class="text-slate-400">Contact</p><p class="mt-1 text-slate-700">{{ job.contact_name || job.phone || 'N/A' }}</p></div>
                                <div><p class="text-slate-400">Email</p><p class="mt-1 text-slate-700">{{ job.email || 'N/A' }}</p></div>
                                <div><p class="text-slate-400">Reason for Leaving</p><p class="mt-1 text-slate-700">{{ job.reason_for_leaving || 'N/A' }}</p></div>
                                <div><p class="text-slate-400">Verification</p><p class="mt-1 text-slate-700">{{ job.verification_status || 'N/A' }}</p></div>
                            </div>
                        </div>
                        <div v-if="!sections.employment.length" class="text-sm text-slate-500">This archived record does not contain employment history information.</div>
                    </div>

                    <div v-show="activeTab === 'training'">
                        <div v-if="sections.training.length" class="box box--stacked p-0 overflow-hidden">
                            <table class="w-full text-sm">
                                <thead class="bg-slate-50"><tr><th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Name</th><th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Type</th><th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Assigned / Start</th><th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Completed</th><th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Status</th></tr></thead>
                                <tbody><tr v-for="(item, index) in sections.training" :key="index" class="border-t border-slate-100"><td class="px-4 py-3">{{ item.name }}</td><td class="px-4 py-3 capitalize">{{ item.type }}</td><td class="px-4 py-3">{{ formatDate(item.start_date || item.assigned_date) }}</td><td class="px-4 py-3">{{ formatDate(item.completion_date) }}</td><td class="px-4 py-3"><span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium capitalize" :class="badge(item.status)">{{ item.status || 'N/A' }}</span></td></tr></tbody>
                            </table>
                        </div>
                        <div v-else class="text-sm text-slate-500">This archived record does not contain training information.</div>
                    </div>

                    <div v-show="activeTab === 'testing'">
                        <div v-if="sections.testing.length" class="box box--stacked p-0 overflow-hidden">
                            <table class="w-full text-sm">
                                <thead class="bg-slate-50"><tr><th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Date</th><th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Type</th><th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Result</th><th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Location</th></tr></thead>
                                <tbody><tr v-for="(test, index) in sections.testing" :key="index" class="border-t border-slate-100"><td class="px-4 py-3">{{ formatDate(test.test_date) }}</td><td class="px-4 py-3">{{ test.test_type || 'N/A' }}</td><td class="px-4 py-3">{{ test.test_result || test.status || 'N/A' }}</td><td class="px-4 py-3">{{ test.location || 'N/A' }}</td></tr></tbody>
                            </table>
                        </div>
                        <div v-else class="text-sm text-slate-500">This archived record does not contain testing information.</div>
                    </div>

                    <div v-show="activeTab === 'safety'" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="box box--stacked p-4 text-center"><p class="text-2xl font-semibold text-slate-800">{{ sections.safety.accidents.length }}</p><p class="text-xs text-slate-500 mt-1">Accidents</p></div>
                            <div class="box box--stacked p-4 text-center"><p class="text-2xl font-semibold text-slate-800">{{ sections.safety.convictions.length }}</p><p class="text-xs text-slate-500 mt-1">Convictions</p></div>
                            <div class="box box--stacked p-4 text-center"><p class="text-2xl font-semibold text-slate-800">{{ sections.safety.inspections.length }}</p><p class="text-xs text-slate-500 mt-1">Inspections</p></div>
                        </div>
                        <div class="box box--stacked p-5"><h3 class="text-base font-semibold text-slate-800 mb-3">Recent Safety Items</h3><div class="space-y-3">
                            <div v-for="(accident, index) in sections.safety.accidents" :key="`a-${index}`" class="rounded-lg border border-slate-200 p-4"><p class="font-medium text-slate-800">{{ accident.nature_of_accident || 'Accident Record' }}</p><p class="text-sm text-slate-500 mt-1">{{ formatDate(accident.accident_date) }} • Fatalities: {{ accident.number_of_fatalities }} • Injuries: {{ accident.number_of_injuries }}</p></div>
                            <div v-for="(conviction, index) in sections.safety.convictions" :key="`c-${index}`" class="rounded-lg border border-slate-200 p-4"><p class="font-medium text-slate-800">{{ conviction.violation_type }}</p><p class="text-sm text-slate-500 mt-1">{{ formatDate(conviction.conviction_date) }} • {{ [conviction.location, conviction.state].filter(Boolean).join(', ') || 'N/A' }}</p></div>
                            <div v-for="(inspection, index) in sections.safety.inspections" :key="`i-${index}`" class="rounded-lg border border-slate-200 p-4"><p class="font-medium text-slate-800">{{ inspection.inspection_type || 'Inspection' }}</p><p class="text-sm text-slate-500 mt-1">{{ formatDate(inspection.inspection_date) }} • {{ inspection.location || 'N/A' }}</p></div>
                        </div><p v-if="!stats.safety" class="text-sm text-slate-500">No safety records in this archive.</p></div>
                    </div>

                    <div v-show="activeTab === 'hos'" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                        <div class="box box--stacked p-5"><p class="text-sm text-slate-500">Entries</p><p class="mt-1 text-2xl font-semibold text-slate-800">{{ sections.hos.entries_count }}</p></div>
                        <div class="box box--stacked p-5"><p class="text-sm text-slate-500">Violations</p><p class="mt-1 text-2xl font-semibold text-slate-800">{{ sections.hos.violations_count }}</p></div>
                        <div class="box box--stacked p-5"><p class="text-sm text-slate-500">Last Entry</p><p class="mt-1 text-sm font-medium text-slate-800">{{ formatDate(sections.hos.last_entry_date) }}</p></div>
                        <div class="box box--stacked p-5"><p class="text-sm text-slate-500">Drive Hours</p><p class="mt-1 text-sm font-medium text-slate-800">{{ sections.hos.total_drive_hours || 'N/A' }}</p></div>
                    </div>

                    <div v-show="activeTab === 'vehicles'">
                        <div v-if="sections.vehicles.length" class="box box--stacked p-0 overflow-hidden">
                            <table class="w-full text-sm">
                                <thead class="bg-slate-50"><tr><th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Vehicle</th><th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Unit</th><th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Driver Type</th><th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Start</th><th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">End</th></tr></thead>
                                <tbody><tr v-for="(vehicle, index) in sections.vehicles" :key="index" class="border-t border-slate-100"><td class="px-4 py-3">{{ vehicle.vehicle || 'N/A' }}</td><td class="px-4 py-3">{{ vehicle.unit_number || 'N/A' }}</td><td class="px-4 py-3">{{ vehicle.driver_type || 'N/A' }}</td><td class="px-4 py-3">{{ formatDate(vehicle.start_date) }}</td><td class="px-4 py-3">{{ formatDate(vehicle.end_date) }}</td></tr></tbody>
                            </table>
                        </div>
                        <div v-else class="text-sm text-slate-500">No vehicle assignments in this archive.</div>
                    </div>

                    <div v-show="activeTab === 'documents'" class="space-y-5">
                        <div v-if="sections.documents.length" v-for="category in sections.documents" :key="category.category" class="box box--stacked p-5">
                            <div class="flex items-center justify-between mb-4"><h3 class="text-base font-semibold text-slate-800">{{ category.category }}</h3><span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">{{ category.count }} file<span v-if="category.count !== 1">s</span></span></div>
                            <div v-if="category.documents.length" class="box box--stacked p-0 overflow-hidden">
                                <table class="w-full text-sm">
                                    <thead class="bg-slate-50"><tr><th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Name</th><th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Type</th><th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Size</th><th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Created</th><th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Action</th></tr></thead>
                                    <tbody><tr v-for="(document, index) in category.documents" :key="index" class="border-t border-slate-100"><td class="px-4 py-3">{{ document.name }}</td><td class="px-4 py-3">{{ document.mime_type || 'N/A' }}</td><td class="px-4 py-3">{{ formatBytes(document.size) }}</td><td class="px-4 py-3">{{ formatDate(document.created_at, true) }}</td><td class="px-4 py-3"><a v-if="document.url" :href="document.url" target="_blank" class="inline-flex items-center gap-1 text-primary hover:underline"><Lucide icon="ExternalLink" class="w-3.5 h-3.5" />Open</a><span v-else class="text-slate-400">Unavailable</span></td></tr></tbody>
                                </table>
                            </div>
                            <div v-else class="text-sm text-slate-500">No documents in this category.</div>
                        </div>
                        <div v-if="!sections.documents.length" class="text-sm text-slate-500">No documents were found for this archived driver record.</div>
                    </div>

                    <div v-show="activeTab === 'migration'">
                        <div v-if="sections.migration" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                            <div class="box box--stacked p-4" v-for="(value, key) in sections.migration" :key="key">
                                <p class="text-xs uppercase tracking-wide text-slate-400">{{ labelize(String(key)) }}</p>
                                <p class="mt-2 text-sm font-medium text-slate-700">{{ formatMigrationValue(String(key), value) }}</p>
                            </div>
                        </div>
                        <div v-else class="text-sm text-slate-500">This archived record is not linked to a migration record.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
