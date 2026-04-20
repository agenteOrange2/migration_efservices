<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { ref } from 'vue'
import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import RazeLayout from '@/layouts/RazeLayout.vue'

declare function route(name: string, params?: any): string
defineOptions({ layout: RazeLayout })

interface ArchivedDetailRouteNames {
    index: string
    download?: string
}

const props = withDefaults(defineProps<{ archive: any; sections: any; stats: any; routeNames?: ArchivedDetailRouteNames }>(), {
    routeNames: () => ({
        index: 'admin.drivers.archived.index',
    }),
})
const activeTab = ref('personal')
const routeNames = ref(props.routeNames)

function namedRoute(name: keyof ArchivedDetailRouteNames, params?: any) {
    const routeName = props.routeNames[name]

    return routeName ? route(routeName, params) : '#'
}

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
    if (['archived', 'active', 'valid', 'verified', 'completed', 'qualified', 'certified', 'pass', 'passed', 'negative'].includes(v)) return 'bg-success/10 text-success'
    if (['expired', 'fail', 'failed', 'termination', 'positive', 'refused', 'disqualified'].includes(v)) return 'bg-danger/10 text-danger'
    if (['pending', 'in_progress', 'restored'].includes(v)) return 'bg-warning/10 text-warning'
    return 'bg-slate-100 text-slate-600'
}
function labelize(value: string) { return value.replace(/_/g, ' ') }
function hasValue(value: unknown) {
    if (value == null) return false
    if (typeof value === 'string') return value.trim() !== ''
    if (Array.isArray(value)) return value.length > 0
    return true
}
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
            <div class="box box--stacked p-5 border border-warning/20 bg-warning/10">
                <div class="flex items-start gap-4">
                    <div class="p-3 bg-warning/10 rounded-xl"><Lucide icon="Archive" class="w-6 h-6 text-warning" /></div>
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
                    <div class="flex flex-wrap gap-2">
                        <a v-if="routeNames.download" :href="namedRoute('download', archive.id)"><Button variant="primary" class="flex items-center gap-2"><Lucide icon="Download" class="w-4 h-4" />Download Archive</Button></a>
                        <Link :href="namedRoute('index')"><Button variant="outline-secondary" class="flex items-center gap-2"><Lucide icon="ArrowLeft" class="w-4 h-4" />Back to Archived</Button></Link>
                    </div>
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
                <div class="overflow-x-auto border-b border-slate-200/60"><div class="flex min-w-max bg-white"><button v-for="tab in tabs" :key="tab.id" type="button" @click="activeTab = tab.id" class="inline-flex items-center gap-2 px-4 py-3 text-sm font-medium border-b-2 transition" :class="activeTab === tab.id ? 'border-primary text-primary bg-primary/5' : 'border-transparent text-slate-600 hover:text-slate-800 hover:bg-slate-50'"><Lucide :icon="tab.icon" class="w-4 h-4" />{{ tab.label }}</button></div></div>
                <div class="p-6">
                    <div v-show="activeTab === 'personal'" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                        <div class="box box--stacked p-4" v-for="(value, key) in sections.personal" :key="key"><p class="text-xs uppercase tracking-wide text-slate-400">{{ labelize(String(key)) }}</p><p class="mt-2 text-sm font-medium text-slate-700 break-words">{{ String(key).includes('date') || String(key).includes('expiration') ? formatDate(String(value)) : value }}</p></div>
                        <div v-if="!Object.keys(sections.personal || {}).length" class="col-span-full text-sm text-slate-500">This archived record does not contain personal information.</div>
                    </div>
                    <div v-show="activeTab === 'licenses'" class="grid grid-cols-1 xl:grid-cols-2 gap-4">
                        <div v-for="(license, index) in sections.licenses" :key="index" class="box box--stacked p-5"><div class="flex items-start justify-between gap-3"><div><h3 class="text-base font-semibold text-slate-800">{{ license.license_number || 'No number' }}</h3><p class="text-sm text-slate-500 mt-1">{{ license.license_type || 'License' }} - {{ license.state || 'N/A' }}</p></div><span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium capitalize" :class="badge(license.status)">{{ license.status || 'unknown' }}</span></div><div class="grid grid-cols-2 gap-4 mt-4 text-sm"><div><p class="text-slate-400">Class</p><p class="mt-1 text-slate-700">{{ license.class || 'N/A' }}</p></div><div><p class="text-slate-400">Issue Date</p><p class="mt-1 text-slate-700">{{ formatDate(license.issue_date) }}</p></div><div><p class="text-slate-400">Expiration</p><p class="mt-1 text-slate-700">{{ formatDate(license.expiration_date) }}</p></div><div><p class="text-slate-400">Restrictions</p><p class="mt-1 text-slate-700">{{ hasValue(license.restrictions) ? (Array.isArray(license.restrictions) ? license.restrictions.join(', ') : license.restrictions) : 'None' }}</p></div></div><div class="mt-4 text-sm"><p class="text-slate-400">Endorsements</p><p class="mt-1 text-slate-700">{{ license.endorsements?.length ? license.endorsements.join(', ') : 'None' }}</p></div></div>
                        <div v-if="!sections.licenses.length" class="col-span-full text-sm text-slate-500">This archived record does not contain license information.</div>
                    </div>
                    <div v-show="activeTab === 'medical'" class="space-y-4">
                        <div v-for="(record, index) in sections.medical" :key="index" class="grid grid-cols-1 xl:grid-cols-2 gap-4">
                            <div class="box box--stacked p-5">
                                <div class="flex items-center gap-2 border-b border-dashed border-slate-200 pb-4 mb-4">
                                    <Lucide icon="CreditCard" class="w-5 h-5 text-primary" />
                                    <h3 class="text-base font-semibold text-slate-800">Social Security Information</h3>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                    <div v-if="record.social_security_number">
                                        <p class="text-slate-400">Social Security Number</p>
                                        <p class="mt-1 text-slate-700">{{ record.social_security_number }}</p>
                                    </div>
                                    <div v-if="record.hire_date">
                                        <p class="text-slate-400">Hire Date</p>
                                        <p class="mt-1 text-slate-700">{{ formatDate(record.hire_date) }}</p>
                                    </div>
                                    <div v-if="record.location" class="md:col-span-2">
                                        <p class="text-slate-400">Location</p>
                                        <p class="mt-1 text-slate-700">{{ record.location }}</p>
                                    </div>
                                    <div v-if="record.suspension_date || record.is_suspended">
                                        <p class="text-slate-400">Suspension</p>
                                        <p class="mt-1 text-slate-700">{{ record.suspension_date ? formatDate(record.suspension_date) : 'Yes' }}</p>
                                    </div>
                                    <div v-if="record.termination_date || record.is_terminated">
                                        <p class="text-slate-400">Termination</p>
                                        <p class="mt-1 text-slate-700">{{ record.termination_date ? formatDate(record.termination_date) : 'Yes' }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="box box--stacked p-5">
                                <div class="flex items-start justify-between gap-4 border-b border-dashed border-slate-200 pb-4 mb-4">
                                    <div>
                                        <h3 class="text-base font-semibold text-slate-800">Medical Certification Information</h3>
                                        <p class="text-sm text-slate-500 mt-1">{{ record.exam_type || 'DOT Medical Examination' }}</p>
                                    </div>
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium capitalize" :class="badge(record.status)">{{ record.status || 'N/A' }}</span>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                    <div v-if="record.examiner_name" class="md:col-span-2">
                                        <p class="text-slate-400">Medical Examiner Name</p>
                                        <p class="mt-1 text-slate-700">{{ record.examiner_name }}</p>
                                    </div>
                                    <div v-if="record.examiner_registry">
                                        <p class="text-slate-400">Registry Number</p>
                                        <p class="mt-1 text-slate-700">{{ record.examiner_registry }}</p>
                                    </div>
                                    <div v-if="record.expiration_date">
                                        <p class="text-slate-400">Medical Card Expiration</p>
                                        <p class="mt-1 text-slate-700">{{ formatDate(record.expiration_date) }}</p>
                                    </div>
                                    <div v-if="record.exam_date">
                                        <p class="text-slate-400">Exam Date</p>
                                        <p class="mt-1 text-slate-700">{{ formatDate(record.exam_date) }}</p>
                                    </div>
                                    <div v-if="record.certificate_number">
                                        <p class="text-slate-400">Certificate Number</p>
                                        <p class="mt-1 text-slate-700">{{ record.certificate_number }}</p>
                                    </div>
                                    <div v-if="record.examiner_license">
                                        <p class="text-slate-400">Examiner License</p>
                                        <p class="mt-1 text-slate-700">{{ record.examiner_license }}</p>
                                    </div>
                                    <div v-if="record.certification_type">
                                        <p class="text-slate-400">Certification Type</p>
                                        <p class="mt-1 text-slate-700">{{ record.certification_type }}</p>
                                    </div>
                                    <div v-if="record.has_variance">
                                        <p class="text-slate-400">Medical Variance</p>
                                        <p class="mt-1 text-slate-700">Variance Granted</p>
                                    </div>
                                    <div v-if="hasValue(record.restrictions)" class="md:col-span-2">
                                        <p class="text-slate-400">Restrictions</p>
                                        <p class="mt-1 text-slate-700">{{ Array.isArray(record.restrictions) ? record.restrictions.join(', ') : record.restrictions }}</p>
                                    </div>
                                    <div v-if="record.notes" class="md:col-span-2">
                                        <p class="text-slate-400">Notes</p>
                                        <p class="mt-1 text-slate-700">{{ record.notes }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-if="sections.medical_documents?.length" class="box box--stacked p-5"><h3 class="text-base font-semibold text-slate-800 mb-4">Medical Documents</h3><div class="overflow-x-auto rounded-lg border border-slate-200"><table class="w-full text-sm"><thead class="bg-slate-50"><tr><th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Name</th><th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Type</th><th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Size</th><th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Created</th><th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Action</th></tr></thead><tbody><tr v-for="(document, index) in sections.medical_documents" :key="index" class="border-t border-slate-100"><td class="px-4 py-3">{{ document.name }}</td><td class="px-4 py-3">{{ document.mime_type || 'N/A' }}</td><td class="px-4 py-3">{{ formatBytes(document.size) }}</td><td class="px-4 py-3">{{ formatDate(document.created_at, true) }}</td><td class="px-4 py-3"><a v-if="document.url" :href="document.url" target="_blank" class="inline-flex items-center gap-1 text-primary hover:underline"><Lucide icon="ExternalLink" class="w-3.5 h-3.5" />Open</a><span v-else class="text-slate-400">Unavailable</span></td></tr></tbody></table></div></div>
                        <div v-if="!sections.medical.length && !sections.medical_documents?.length" class="text-sm text-slate-500">This archived record does not contain medical information.</div>
                    </div>
                    <div v-show="activeTab === 'employment'" class="space-y-4">
                        <div v-for="(job, index) in sections.employment" :key="index" class="box box--stacked p-5"><div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4"><div><h3 class="text-base font-semibold text-slate-800">{{ job.employer_name }}</h3><p class="text-sm text-slate-500 mt-1">{{ job.position || 'Position not provided' }}</p></div><span v-if="job.is_verified" class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium bg-success/10 text-success">Verified</span></div><div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 mt-4 text-sm"><div><p class="text-slate-400">Employment Period</p><p class="mt-1 text-slate-700">{{ formatDate(job.start_date) }} - {{ formatDate(job.end_date) }}</p></div><div v-if="job.contact_name || job.phone"><p class="text-slate-400">Contact</p><p class="mt-1 text-slate-700">{{ job.contact_name || job.phone }}</p></div><div v-if="job.email"><p class="text-slate-400">Email</p><p class="mt-1 text-slate-700">{{ job.email }}</p></div><div v-if="job.fax"><p class="text-slate-400">Fax</p><p class="mt-1 text-slate-700">{{ job.fax }}</p></div><div v-if="job.reason_for_leaving"><p class="text-slate-400">Reason for Leaving</p><p class="mt-1 text-slate-700">{{ job.reason_for_leaving }}</p></div><div v-if="job.verification_status || job.is_verified"><p class="text-slate-400">Verification</p><p class="mt-1 text-slate-700">{{ job.verification_status || 'verified' }}</p></div></div><div v-if="job.address || job.city || job.state || job.zip" class="mt-4 text-sm"><p class="text-slate-400">Address</p><p class="mt-1 text-slate-700">{{ job.address || '' }} {{ [job.city, job.state, job.zip].filter(Boolean).join(', ') }}</p></div><div v-if="job.was_subject_to_fmcsr || job.was_subject_to_drug_testing" class="mt-4 flex flex-wrap gap-3 text-xs"><span v-if="job.was_subject_to_fmcsr" class="inline-flex rounded-full px-2.5 py-1 bg-primary/10 text-primary">Subject to FMCSR</span><span v-if="job.was_subject_to_drug_testing" class="inline-flex rounded-full px-2.5 py-1 bg-primary/10 text-primary">Drug/Alcohol Testing Required</span></div><div v-if="job.verified_at || job.notes" class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm"><div v-if="job.verified_at"><p class="text-slate-400">Verified At</p><p class="mt-1 text-slate-700">{{ formatDate(job.verified_at, true) }}</p></div><div v-if="job.notes"><p class="text-slate-400">Notes</p><p class="mt-1 text-slate-700">{{ job.notes }}</p></div></div></div>
                        <div v-if="!sections.employment.length" class="text-sm text-slate-500">This archived record does not contain employment history information.</div>
                    </div>
                    <div v-show="activeTab === 'training'"><div v-if="sections.training.length" class="overflow-x-auto rounded-lg border border-slate-200"><table class="w-full text-sm"><thead class="bg-slate-50"><tr><th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Name</th><th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Type</th><th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Provider</th><th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Start</th><th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Completion</th><th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Due</th><th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Status</th></tr></thead><tbody><tr v-for="(item, index) in sections.training" :key="index" class="border-t border-slate-100"><td class="px-4 py-3">{{ item.name }}</td><td class="px-4 py-3 capitalize">{{ item.type }}</td><td class="px-4 py-3">{{ item.provider || 'N/A' }}</td><td class="px-4 py-3">{{ formatDate(item.start_date || item.assigned_date) }}</td><td class="px-4 py-3">{{ formatDate(item.completion_date) }}</td><td class="px-4 py-3">{{ formatDate(item.due_date) }}</td><td class="px-4 py-3"><span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium capitalize" :class="badge(item.status)">{{ item.status || 'N/A' }}</span></td></tr></tbody></table></div><div v-else class="text-sm text-slate-500">This archived record does not contain training information.</div></div>
                    <div v-show="activeTab === 'testing'" class="space-y-4"><div v-for="(test, index) in sections.testing" :key="index" class="box box--stacked p-5"><div class="flex items-start justify-between gap-4"><div><h3 class="text-base font-semibold text-slate-800">{{ test.test_type || 'Drug/Alcohol Test' }}</h3><p v-if="test.test_reason" class="text-sm text-slate-500 mt-1">Reason: {{ labelize(test.test_reason) }}</p></div><span v-if="test.result || test.status" class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium capitalize" :class="badge(test.result || test.status)">{{ test.result || test.status }}</span></div><div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 mt-4 text-sm"><div v-if="test.test_date"><p class="text-slate-400">Test Date</p><p class="mt-1 text-slate-700">{{ formatDate(test.test_date) }}</p></div><div v-if="test.collection_site"><p class="text-slate-400">Collection Site</p><p class="mt-1 text-slate-700">{{ test.collection_site }}</p></div><div v-if="test.specimen_id"><p class="text-slate-400">Specimen ID</p><p class="mt-1 text-slate-700">{{ test.specimen_id }}</p></div><div v-if="test.laboratory"><p class="text-slate-400">Laboratory</p><p class="mt-1 text-slate-700">{{ test.laboratory }}</p></div><div v-if="test.mro_name"><p class="text-slate-400">Medical Review Officer</p><p class="mt-1 text-slate-700">{{ test.mro_name }}</p></div><div v-if="test.result_date"><p class="text-slate-400">Result Date</p><p class="mt-1 text-slate-700">{{ formatDate(test.result_date) }}</p></div><div v-if="test.administered_by"><p class="text-slate-400">Administered By</p><p class="mt-1 text-slate-700">{{ test.administered_by }}</p></div><div v-if="test.next_test_due"><p class="text-slate-400">Next Test Due</p><p class="mt-1 text-slate-700">{{ formatDate(test.next_test_due) }}</p></div><div v-if="test.bill_to"><p class="text-slate-400">Bill To</p><p class="mt-1 text-slate-700">{{ test.bill_to }}</p></div><div v-if="hasValue(test.substances_tested)" class="xl:col-span-3"><p class="text-slate-400">Substances Tested</p><p class="mt-1 text-slate-700">{{ Array.isArray(test.substances_tested) ? test.substances_tested.join(', ') : test.substances_tested }}</p></div><div v-if="test.follow_up_required || test.follow_up_notes" class="xl:col-span-3"><p class="text-slate-400">Follow-up</p><p class="mt-1 text-slate-700">{{ test.follow_up_notes || 'Follow-up required' }}</p></div><div v-if="test.notes" class="xl:col-span-3"><p class="text-slate-400">Notes</p><p class="mt-1 text-slate-700">{{ test.notes }}</p></div></div></div><div v-if="!sections.testing.length" class="text-sm text-slate-500">This archived record does not contain testing information.</div></div>
                    <div v-show="activeTab === 'safety'" class="space-y-6"><div class="grid grid-cols-1 md:grid-cols-3 gap-4"><div class="box box--stacked p-4 text-center"><p class="text-2xl font-semibold text-slate-800">{{ sections.safety.accidents.length }}</p><p class="text-xs text-slate-500 mt-1">Accidents</p></div><div class="box box--stacked p-4 text-center"><p class="text-2xl font-semibold text-slate-800">{{ sections.safety.convictions.length }}</p><p class="text-xs text-slate-500 mt-1">Convictions</p></div><div class="box box--stacked p-4 text-center"><p class="text-2xl font-semibold text-slate-800">{{ sections.safety.inspections.length }}</p><p class="text-xs text-slate-500 mt-1">Inspections</p></div></div><div class="box box--stacked p-5"><h3 class="text-base font-semibold text-slate-800 mb-4">Accident Records</h3><div v-for="(accident, index) in sections.safety.accidents" :key="`a-${index}`" class="rounded-lg border border-slate-200 p-4 mb-3"><p class="font-medium text-slate-800">Accident - {{ formatDate(accident.accident_date) }}</p><p v-if="accident.location" class="text-sm text-slate-500 mt-1">Location: {{ accident.location }}</p><div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 mt-4 text-sm"><div v-if="accident.nature_of_accident"><p class="text-slate-400">Accident Type</p><p class="mt-1 text-slate-700">{{ accident.nature_of_accident }}</p></div><div><p class="text-slate-400">Injuries</p><p class="mt-1 text-slate-700">{{ accident.injuries ? `Yes (${accident.injury_count})` : 'No' }}</p></div><div v-if="accident.fatalities"><p class="text-slate-400">Fatalities</p><p class="mt-1 text-slate-700">Yes ({{ accident.fatality_count }})</p></div><div v-if="accident.description" class="xl:col-span-3"><p class="text-slate-400">Description</p><p class="mt-1 text-slate-700">{{ accident.description }}</p></div><div v-if="accident.comments" class="xl:col-span-3"><p class="text-slate-400">Notes</p><p class="mt-1 text-slate-700">{{ accident.comments }}</p></div></div></div><div v-if="!sections.safety.accidents.length" class="text-sm text-slate-500">No accident records available.</div></div><div class="box box--stacked p-5"><h3 class="text-base font-semibold text-slate-800 mb-4">Traffic Violations & Convictions</h3><div v-for="(conviction, index) in sections.safety.convictions" :key="`c-${index}`" class="rounded-lg border border-slate-200 p-4 mb-3"><p class="font-medium text-slate-800">{{ conviction.violation_type }}</p><div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 mt-4 text-sm"><div v-if="conviction.location"><p class="text-slate-400">Location</p><p class="mt-1 text-slate-700">{{ conviction.location }}</p></div><div v-if="conviction.state"><p class="text-slate-400">State</p><p class="mt-1 text-slate-700">{{ conviction.state }}</p></div><div v-if="conviction.penalty"><p class="text-slate-400">Penalty</p><p class="mt-1 text-slate-700">{{ conviction.penalty }}</p></div><div v-if="conviction.conviction_date"><p class="text-slate-400">Conviction Date</p><p class="mt-1 text-slate-700">{{ formatDate(conviction.conviction_date) }}</p></div><div v-if="conviction.description" class="xl:col-span-3"><p class="text-slate-400">Description</p><p class="mt-1 text-slate-700">{{ conviction.description }}</p></div></div></div><div v-if="!sections.safety.convictions.length" class="text-sm text-slate-500">No traffic violation records available.</div></div><div class="box box--stacked p-5"><h3 class="text-base font-semibold text-slate-800 mb-4">DOT Inspections</h3><div v-for="(inspection, index) in sections.safety.inspections" :key="`i-${index}`" class="rounded-lg border border-slate-200 p-4 mb-3"><p class="font-medium text-slate-800">{{ inspection.level || inspection.inspection_level || 'DOT Inspection' }}</p><p v-if="inspection.inspection_date" class="text-sm text-slate-500 mt-1">Date: {{ formatDate(inspection.inspection_date) }}</p><div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4 text-sm"><div v-if="inspection.location"><p class="text-slate-400">Location</p><p class="mt-1 text-slate-700">{{ inspection.location }}</p></div><div v-if="inspection.inspector_name"><p class="text-slate-400">Inspector</p><p class="mt-1 text-slate-700">{{ inspection.inspector_name }}</p></div><div v-if="inspection.violations"><p class="text-slate-400">Violations</p><p class="mt-1 text-slate-700">{{ inspection.violations }}</p></div></div></div><div v-if="!sections.safety.inspections.length" class="text-sm text-slate-500">No inspection records available.</div></div></div>
                    <div v-show="activeTab === 'hos'" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4"><div class="box box--stacked p-5"><p class="text-sm text-slate-500">Entries</p><p class="mt-1 text-2xl font-semibold text-slate-800">{{ sections.hos.entries_count }}</p></div><div class="box box--stacked p-5"><p class="text-sm text-slate-500">Violations</p><p class="mt-1 text-2xl font-semibold text-slate-800">{{ sections.hos.violations_count }}</p></div><div class="box box--stacked p-5"><p class="text-sm text-slate-500">Last Entry</p><p class="mt-1 text-sm font-medium text-slate-800">{{ formatDate(sections.hos.last_entry_date) }}</p></div><div class="box box--stacked p-5"><p class="text-sm text-slate-500">Drive Hours</p><p class="mt-1 text-sm font-medium text-slate-800">{{ sections.hos.total_drive_hours || 'N/A' }}</p></div></div>
                    <div v-show="activeTab === 'vehicles'" class="space-y-4"><div v-for="(vehicle, index) in sections.vehicles" :key="index" class="box box--stacked p-5"><div class="flex items-start justify-between gap-4"><div><h3 class="text-base font-semibold text-slate-800">{{ vehicle.unit_number ? `Unit #${vehicle.unit_number}` : 'Vehicle Assignment' }}</h3><p v-if="vehicle.vehicle_make || vehicle.vehicle_model" class="text-sm text-slate-500 mt-1">{{ vehicle.vehicle_make || '' }} {{ vehicle.vehicle_model || '' }}<span v-if="vehicle.vehicle_year"> ({{ vehicle.vehicle_year }})</span></p><p v-if="vehicle.driver_type" class="text-xs text-slate-500 mt-1">Driver Type: {{ labelize(vehicle.driver_type) }}</p></div><span v-if="vehicle.status" class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium capitalize" :class="badge(vehicle.status)">{{ vehicle.status }}</span></div><div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 mt-4 text-sm"><div><p class="text-slate-400">Assignment Period</p><p class="mt-1 text-slate-700">{{ formatDate(vehicle.start_date) }} - {{ formatDate(vehicle.end_date) }}</p></div><div v-if="vehicle.vin"><p class="text-slate-400">VIN</p><p class="mt-1 text-slate-700 font-mono">{{ vehicle.vin }}</p></div><div v-if="vehicle.license_plate"><p class="text-slate-400">License Plate</p><p class="mt-1 text-slate-700">{{ vehicle.license_plate }}<span v-if="vehicle.plate_state"> ({{ vehicle.plate_state }})</span></p></div><div v-if="vehicle.vehicle_type"><p class="text-slate-400">Vehicle Type</p><p class="mt-1 text-slate-700">{{ labelize(vehicle.vehicle_type) }}</p></div><div v-if="vehicle.odometer_start"><p class="text-slate-400">Starting Odometer</p><p class="mt-1 text-slate-700">{{ vehicle.odometer_start }}</p></div><div v-if="vehicle.odometer_end"><p class="text-slate-400">Ending Odometer</p><p class="mt-1 text-slate-700">{{ vehicle.odometer_end }}</p></div><div v-if="vehicle.is_primary"><p class="text-slate-400">Assignment Type</p><p class="mt-1 text-slate-700">Primary Driver</p></div><div v-if="vehicle.end_reason" class="xl:col-span-3"><p class="text-slate-400">Reason for Assignment End</p><p class="mt-1 text-slate-700">{{ vehicle.end_reason }}</p></div><div v-if="vehicle.notes" class="xl:col-span-3"><p class="text-slate-400">Notes</p><p class="mt-1 text-slate-700">{{ vehicle.notes }}</p></div></div></div><div v-if="!sections.vehicles.length" class="text-sm text-slate-500">No vehicle assignments in this archive.</div></div>
                    <div v-show="activeTab === 'documents'" class="space-y-5"><div v-if="sections.documents.length" class="box box--stacked p-4 border border-primary/20 bg-primary/5"><div class="flex items-start gap-3"><Lucide icon="Info" class="w-5 h-5 text-primary mt-0.5" /><p class="text-sm text-slate-600">These documents represent all files that existed at the time of driver inactivation, organized by category.</p></div></div><div v-for="category in sections.documents" :key="category.category" class="box box--stacked p-5"><div class="flex items-center justify-between mb-4"><h3 class="text-base font-semibold text-slate-800">{{ category.category }}</h3><span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">{{ category.count }} file<span v-if="category.count !== 1">s</span></span></div><div v-if="category.documents.length" class="overflow-x-auto rounded-lg border border-slate-200"><table class="w-full text-sm"><thead class="bg-slate-50"><tr><th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Name</th><th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Type</th><th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Size</th><th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Created</th><th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Action</th></tr></thead><tbody><tr v-for="(document, index) in category.documents" :key="index" class="border-t border-slate-100"><td class="px-4 py-3">{{ document.name }}</td><td class="px-4 py-3">{{ document.mime_type || 'N/A' }}</td><td class="px-4 py-3">{{ formatBytes(document.size) }}</td><td class="px-4 py-3">{{ formatDate(document.created_at, true) }}</td><td class="px-4 py-3"><a v-if="document.url" :href="document.url" target="_blank" class="inline-flex items-center gap-1 text-primary hover:underline"><Lucide icon="ExternalLink" class="w-3.5 h-3.5" />Open</a><span v-else class="text-slate-400">Unavailable</span></td></tr></tbody></table></div><div v-else class="text-sm text-slate-500">No documents in this category.</div></div><div v-if="!sections.documents.length" class="text-sm text-slate-500">No documents were found for this archived driver record.</div></div>
                    <div v-show="activeTab === 'migration'"><div v-if="sections.migration" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4"><div class="box box--stacked p-4" v-for="(value, key) in sections.migration" :key="key"><p class="text-xs uppercase tracking-wide text-slate-400">{{ labelize(String(key)) }}</p><p class="mt-2 text-sm font-medium text-slate-700">{{ formatMigrationValue(String(key), value) }}</p></div></div><div v-else class="text-sm text-slate-500">This archived record is not linked to a migration record.</div></div>
                </div>
            </div>
        </div>
    </div>
</template>
