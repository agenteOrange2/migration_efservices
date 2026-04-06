<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import Lucide from '@/components/Base/Lucide'
import { Dialog } from '@/components/Base/Headless'
import { FormInput, FormLabel, FormSelect, FormTextarea } from '@/components/Base/Form'
import Litepicker from '@/components/Base/Litepicker/Litepicker.vue'
import RazeLayout from '@/layouts/RazeLayout.vue'

const lpOptions = { singleMode: true, format: 'MM/DD/YYYY', autoApply: true }

declare function route(name: string, params?: any): string

defineOptions({ layout: RazeLayout })

// ─── Types ──────────────────────────────────────────────────────────────────
interface ChecklistItem { checked: boolean; label: string }
interface SavedVerification { id: number; verified_at: string; verifier: string; notes: string | null }
interface Address {
    id: number; address_line1: string; address_line2?: string
    city: string; state: string; zip_code: string
    from_date: string; to_date?: string; primary: boolean; lived_three_years: boolean
}
interface Endorsement { code: string; name: string }
interface License {
    id: number; license_number: string; state_of_issue: string; license_class: string
    expiration_date: string; is_cdl: boolean; status: string; is_expired: boolean
    front_image: string | null; back_image: string | null; endorsements: Endorsement[]
}
interface Medical {
    id: number; medical_examiner_name: string | null; medical_examiner_registry_number: string | null
    medical_card_expiration_date: string | null; ssn_last4: string | null
    medical_card_url: string | null; is_expired: boolean
}
interface Experience { equipment_type: string; years_experience: number; miles_driven: number; requires_cdl: boolean }
interface Certificate { id: number; url: string; name: string; is_image: boolean }
interface TrainingSchool {
    id: number; school_name: string; city: string; state: string
    date_start: string; date_end: string; graduated: boolean
    subject_to_safety_regulations: boolean; performed_safety_functions: boolean
    training_skills: string[] | null
    certificates: Certificate[]
}
interface Course {
    id: number; organization_name: string; city: string; state: string
    certification_date: string; expiration_date: string; experience: string
    years_experience: number; status: string
    certificates: Certificate[]
}
interface TrafficConviction {
    id: number; conviction_date: string; location: string; charge: string
    penalty: string; conviction_type: string; description: string
}
interface Accident {
    id: number; accident_date: string; nature_of_accident: string
    had_fatalities: boolean; had_injuries: boolean
    number_of_fatalities: number; number_of_injuries: number; comments: string
}
interface Testing {
    id: number; test_date: string; test_type: string; test_result: string; status: string
    administered_by: string; is_pre_employment_test: boolean; is_random_test: boolean; is_post_accident_test: boolean
}
interface FmcsrData {
    is_disqualified: boolean; disqualified_details: string | null
    is_license_suspended: boolean; suspension_details: string | null
    is_license_denied: boolean; denial_details: string | null
    has_positive_drug_test: boolean; has_duty_offenses: boolean
    offense_details: string | null; consent_driving_record: boolean
}
interface CriminalHistory {
    has_criminal_charges: boolean; has_felony_conviction: boolean
    has_minister_permit: boolean; fcra_consent: boolean
}
interface EmploymentCompany {
    company_name: string; city: string; state: string; phone: string
    position_held: string; from_date: string; to_date: string
    from_date_raw: string | null; to_date_raw: string | null
    reason_for_leaving: string; subject_to_fmcsr: boolean; safety_sensitive: boolean
    verification_status: string | null
}
interface UnemploymentPeriod {
    id: number; start_date: string; end_date: string
    start_date_raw: string | null; end_date_raw: string | null
    comments: string | null
}
interface RelatedEmployment {
    id: number; start_date: string; end_date: string
    start_date_raw: string | null; end_date_raw: string | null
    position: string | null; comments: string | null
}
interface DriverDocument { id: number; name: string; url: string; is_image: boolean; size: string }
interface DriverTrainingAssignment {
    id: number; training_id: number; title: string; content_type: string
    assigned_date: string | null; due_date: string | null; completed_date: string | null
    status: string; is_overdue: boolean
}
interface Driver {
    id: number; name: string; last_name: string; middle_name: string
    email: string; phone: string; date_of_birth: string
    carrier_name: string; profile_photo: string | null
    application_date: string; status: string; completion_pct: number
    application: {
        id: number; status: string; rejection_reason: string | null; completed_at: string | null
        pdf_url: string | null
        details: {
            applying_position: string; applying_position_other: string | null
            applying_location: string; eligible_to_work: boolean; can_speak_english: boolean
            has_twic_card: boolean; twic_expiration_date: string | null
            how_did_hear: string; how_did_hear_other: string | null; referral_employee_name: string | null
        } | null
        addresses: Address[]
    } | null
    licenses: License[]
    medical: Medical | null
    experiences: Experience[]
    training_schools: TrainingSchool[]
    courses: Course[]
    traffic_convictions: TrafficConviction[]
    accidents: Accident[]
    testings: Testing[]
    fmcsr_data: FmcsrData | null
    employment_companies: EmploymentCompany[]
    unemployment_periods: UnemploymentPeriod[]
    related_employments: RelatedEmployment[]
    driving_records: DriverDocument[]
    criminal_records: DriverDocument[]
    medical_records: DriverDocument[]
    clearing_house: DriverDocument[]
    driver_trainings: DriverTrainingAssignment[]
    criminal_history: CriminalHistory | null
}

const props = defineProps<{
    driver: Driver
    checklistItems: Record<string, ChecklistItem>
    checklistPct: number
    checkedCount: number
    totalCount: number
    savedVerification: SavedVerification | null
    stepsStatus: Record<number, string>
}>()

// ─── Tabs ────────────────────────────────────────────────────────────────────
const tabs = [
    { key: 'general',   label: 'Profile' },
    { key: 'licenses',  label: 'Licenses' },
    { key: 'medical',   label: 'Medical' },
    { key: 'records',   label: 'Records' },
    { key: 'training',  label: 'Training' },
    { key: 'history',   label: 'History' },
    { key: 'documents', label: 'Documents' },
]
const currentTab = ref('general')

// ─── Checklist ───────────────────────────────────────────────────────────────
const checklist = ref<Record<string, ChecklistItem>>(JSON.parse(JSON.stringify(props.checklistItems)))
const verificationNotes = ref('')
const savingChecklist   = ref(false)

const checkedCount = computed(() => Object.values(checklist.value).filter(i => i.checked).length)
const totalCount   = computed(() => Object.keys(checklist.value).length)
const checklistPct = computed(() =>
    totalCount.value > 0 ? Math.round((checkedCount.value / totalCount.value) * 100) : 0
)
const isChecklistComplete = computed(() => checkedCount.value === totalCount.value)

const checklistGroups = [
    { title: 'General Information',       keys: ['general_info','contact_info','address_info'] },
    { title: 'Licenses & Documents',      keys: ['license_info','license_image','medical_info','medical_image'] },
    { title: 'Experience & Records',      keys: ['experience_info','training_verified','traffic_verified','accident_verified','driving_record','criminal_record','history_info'] },
    { title: 'Application Certification', keys: ['application_certification','documents_checked'] },
    { title: 'Additional Verifications',  keys: ['criminal_check','drug_test','mvr_check','policy_agreed','clearing_house','vehicle_info'] },
]

// ─── Approve / Reject ────────────────────────────────────────────────────────
const showApproveModal = ref(false)
const approvingApp     = ref(false)
const showRejectModal  = ref(false)
const rejectionReason  = ref('')
const rejectingApp     = ref(false)

// ─── License image upload ────────────────────────────────────────────────────
const licenseImageModal = ref(false)
const licenseImageForm  = useForm({ image: null as File | null, side: 'front' as 'front' | 'back' })
const selectedLicenseId = ref<number | null>(null)

function openLicenseImageModal(licenseId: number, side: 'front' | 'back') {
    selectedLicenseId.value = licenseId
    licenseImageForm.side   = side
    licenseImageForm.image  = null
    licenseImageModal.value = true
}
function submitLicenseImage() {
    if (!selectedLicenseId.value || !licenseImageForm.image) return
    licenseImageForm.post(
        route('admin.driver-recruitment.licenses.upload-image', {
            driver: props.driver.id,
            license: selectedLicenseId.value,
        }),
        { onSuccess: () => { licenseImageModal.value = false } }
    )
}

// ─── Medical card upload ─────────────────────────────────────────────────────
const medicalImageModal = ref(false)
const medicalImageForm  = useForm({ image: null as File | null })

function submitMedicalImage() {
    if (!medicalImageForm.image) return
    medicalImageForm.post(
        route('admin.driver-recruitment.upload-medical-image', props.driver.id),
        { onSuccess: () => { medicalImageModal.value = false } }
    )
}

// ─── Training School modal ───────────────────────────────────────────────────
const availableTrainingSkills = [
    { value: 'double_trailer',      label: 'Double trailer' },
    { value: 'passenger',           label: 'Passenger' },
    { value: 'tank_vehicle',        label: 'Tank vehicle' },
    { value: 'hazardous_material',  label: 'Hazardous material' },
    { value: 'combination_vehicle', label: 'Combination vehicle' },
    { value: 'air_brakes',          label: 'Air brakes' },
]
const showTrainingModal = ref(false)
const editingTrainingId = ref<number | null>(null)
const trainingForm = useForm({
    school_name:                    '',
    city:                           '',
    state:                          '',
    date_start:                     '',
    date_end:                       '',
    graduated:                      false,
    subject_to_safety_regulations:  false,
    performed_safety_functions:     false,
    training_skills:                [] as string[],
    certificates:                   [] as File[],
})
function openTrainingModal(school?: TrainingSchool) {
    if (school) {
        editingTrainingId.value = school.id
        trainingForm.school_name                   = school.school_name
        trainingForm.city                          = school.city
        trainingForm.state                         = school.state
        trainingForm.date_start                    = school.date_start ?? ''
        trainingForm.date_end                      = school.date_end ?? ''
        trainingForm.graduated                     = school.graduated
        trainingForm.subject_to_safety_regulations = school.subject_to_safety_regulations
        trainingForm.performed_safety_functions    = school.performed_safety_functions
        trainingForm.training_skills               = school.training_skills ? [...school.training_skills] : []
        trainingForm.certificates                  = []
    } else {
        editingTrainingId.value = null
        trainingForm.reset()
    }
    showTrainingModal.value = true
}
function submitTraining() {
    const url = editingTrainingId.value !== null
        ? route('admin.driver-recruitment.training-schools.update', { driver: props.driver.id, school: editingTrainingId.value })
        : route('admin.driver-recruitment.training-schools.store', props.driver.id)
    trainingForm.post(url, {
        onSuccess: () => { showTrainingModal.value = false; trainingForm.reset(); editingTrainingId.value = null },
    })
}

// ─── Course modal ─────────────────────────────────────────────────────────────
const showCourseModal  = ref(false)
const editingCourseId  = ref<number | null>(null)
const courseForm = useForm({
    organization_name:  '',
    city:               '',
    state:              '',
    certification_date: '',
    expiration_date:    '',
    experience:         '',
    years_experience:   null as number | null,
    status:             'Active',
    certificates:       [] as File[],
})
function openCourseModal(course?: Course) {
    if (course) {
        editingCourseId.value = course.id
        courseForm.organization_name  = course.organization_name
        courseForm.city               = course.city
        courseForm.state              = course.state
        courseForm.certification_date = course.certification_date ?? ''
        courseForm.expiration_date    = course.expiration_date ?? ''
        courseForm.experience         = course.experience ?? ''
        courseForm.years_experience   = course.years_experience ?? null
        courseForm.status             = course.status ?? 'Active'
        courseForm.certificates       = []
    } else {
        editingCourseId.value = null
        courseForm.reset()
    }
    showCourseModal.value = true
}
function submitCourse() {
    const url = editingCourseId.value !== null
        ? route('admin.driver-recruitment.courses.update', { driver: props.driver.id, course: editingCourseId.value })
        : route('admin.driver-recruitment.courses.store', props.driver.id)
    courseForm.post(url, {
        onSuccess: () => { showCourseModal.value = false; courseForm.reset(); editingCourseId.value = null },
    })
}

// ─── Traffic Conviction modal ─────────────────────────────────────────────────
const showTrafficModal  = ref(false)
const editingTrafficId  = ref<number | null>(null)
const trafficForm = useForm({
    conviction_date: '', location: '', charge: '',
    penalty: '', conviction_type: '', description: '',
})
function openTrafficModal(tc?: TrafficConviction) {
    if (tc) {
        editingTrafficId.value      = tc.id
        trafficForm.conviction_date = tc.conviction_date ?? ''
        trafficForm.location        = tc.location ?? ''
        trafficForm.charge          = tc.charge
        trafficForm.penalty         = tc.penalty ?? ''
        trafficForm.conviction_type = tc.conviction_type ?? ''
        trafficForm.description     = tc.description ?? ''
    } else {
        editingTrafficId.value = null
        trafficForm.reset()
    }
    showTrafficModal.value = true
}
function submitTraffic() {
    const url = editingTrafficId.value !== null
        ? route('admin.driver-recruitment.traffic-convictions.update', { driver: props.driver.id, conviction: editingTrafficId.value })
        : route('admin.driver-recruitment.traffic-convictions.store', props.driver.id)
    trafficForm.post(url, {
        onSuccess: () => { showTrafficModal.value = false; trafficForm.reset(); editingTrafficId.value = null },
    })
}

// ─── Accident modal ───────────────────────────────────────────────────────────
const showAccidentModal  = ref(false)
const editingAccidentId  = ref<number | null>(null)
const accidentForm = useForm({
    accident_date: '', nature_of_accident: '',
    had_fatalities: false, had_injuries: false,
    number_of_fatalities: 0, number_of_injuries: 0, comments: '',
})
function openAccidentModal(acc?: Accident) {
    if (acc) {
        editingAccidentId.value              = acc.id
        accidentForm.accident_date           = acc.accident_date ?? ''
        accidentForm.nature_of_accident      = acc.nature_of_accident
        accidentForm.had_fatalities          = acc.had_fatalities
        accidentForm.had_injuries            = acc.had_injuries
        accidentForm.number_of_fatalities    = acc.number_of_fatalities
        accidentForm.number_of_injuries      = acc.number_of_injuries
        accidentForm.comments                = acc.comments ?? ''
    } else {
        editingAccidentId.value = null
        accidentForm.reset()
    }
    showAccidentModal.value = true
}
function submitAccident() {
    const url = editingAccidentId.value !== null
        ? route('admin.driver-recruitment.accidents.update', { driver: props.driver.id, accident: editingAccidentId.value })
        : route('admin.driver-recruitment.accidents.store', props.driver.id)
    accidentForm.post(url, {
        onSuccess: () => { showAccidentModal.value = false; accidentForm.reset(); editingAccidentId.value = null },
    })
}

// ─── Delete helpers ───────────────────────────────────────────────────────────
function deleteRecord(routeName: string, params: object, label = 'this record') {
    if (!window.confirm(`Are you sure you want to delete ${label}? This action cannot be undone.`)) return
    router.delete(route(routeName, params), { preserveScroll: true })
}

// ─── Document uploads (modal) ─────────────────────────────────────────────────
const showDocModal   = ref(false)
const docModalLabel  = ref('')
const docUploadForm  = useForm({ file: null as File | null, collection: '' })

function openDocModal(collection: string, label: string) {
    docUploadForm.reset()
    docUploadForm.collection = collection
    docModalLabel.value      = label
    showDocModal.value       = true
}

function submitDocUpload() {
    if (!docUploadForm.file) return
    docUploadForm.post(route('admin.driver-recruitment.upload-document', props.driver.id), {
        preserveScroll: true,
        onSuccess: () => { showDocModal.value = false; docUploadForm.reset() },
    })
}

function deleteDocument(mediaId: number) {
    if (!window.confirm('Delete this document? This cannot be undone.')) return
    router.delete(route('admin.driver-recruitment.documents.destroy', { driver: props.driver.id, media: mediaId }), {
        preserveScroll: true,
    })
}

// ─── History timeline helpers ─────────────────────────────────────────────────
const historyTimeline = computed(() => {
    const items: { type: string; start_raw: string | null; end_raw: string | null; start: string; end: string; data: any }[] = []
    props.driver.employment_companies.forEach(c => items.push({
        type: 'employment', start_raw: c.from_date_raw, end_raw: c.to_date_raw,
        start: c.from_date, end: c.to_date, data: c,
    }))
    props.driver.unemployment_periods.forEach(u => items.push({
        type: 'unemployment', start_raw: u.start_date_raw, end_raw: u.end_date_raw,
        start: u.start_date, end: u.end_date, data: u,
    }))
    props.driver.related_employments.forEach(r => items.push({
        type: 'related', start_raw: r.start_date_raw, end_raw: r.end_date_raw,
        start: r.start_date, end: r.end_date, data: r,
    }))
    return items.sort((a, b) => (b.end_raw ?? '').localeCompare(a.end_raw ?? ''))
})

// ─── Helpers ─────────────────────────────────────────────────────────────────
const statusConfig: Record<string, { label: string; classes: string; icon: string }> = {
    draft:    { label: 'Draft',    classes: 'bg-slate-100 text-slate-600',     icon: 'FileEdit' },
    pending:  { label: 'Pending',  classes: 'bg-amber-100 text-amber-600',     icon: 'Clock' },
    approved: { label: 'Approved', classes: 'bg-emerald-100 text-emerald-700', icon: 'CheckCircle' },
    rejected: { label: 'Rejected', classes: 'bg-red-100 text-red-600',         icon: 'XCircle' },
}
const statusBadge = (s: string) => statusConfig[s] ?? statusConfig.draft
const primaryAddress    = computed(() => props.driver.application?.addresses.find(a => a.primary) ?? null)
const secondaryAddresses = computed(() => props.driver.application?.addresses.filter(a => !a.primary) ?? [])

const stepNames: Record<number, string> = {
    1:'General Information', 2:'Address', 3:'Application',
    4:'Licenses', 5:'Medical', 6:'Training',
    7:'Traffic', 8:'Accidents', 9:'FMCSR', 10:'Work History', 11:'Certification',
}

function saveChecklist() {
    savingChecklist.value = true
    router.post(
        route('admin.driver-recruitment.checklist.update', props.driver.id),
        { checklist_items: checklist.value, notes: verificationNotes.value },
        { preserveScroll: true, onFinish: () => { savingChecklist.value = false } }
    )
}
function approveApplication() {
    approvingApp.value = true
    router.post(route('admin.driver-recruitment.approve', props.driver.id), {}, {
        preserveScroll: true,
        onFinish: () => { approvingApp.value = false; showApproveModal.value = false },
    })
}
function rejectApplication() {
    if (!rejectionReason.value.trim()) return
    rejectingApp.value = true
    router.post(route('admin.driver-recruitment.reject', props.driver.id), { rejection_reason: rejectionReason.value }, {
        preserveScroll: true,
        onFinish: () => { rejectingApp.value = false; showRejectModal.value = false },
    })
}
function formatPosition(pos: string, other?: string | null) {
    if (pos === 'other') return other ?? pos
    return pos.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
}
function formatSource(src: string, other?: string | null, referral?: string | null) {
    if (src === 'other') return other ?? src
    if (src === 'employee_referral') return `Referred by: ${referral ?? ''}`
    return src.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
}
function onFileChange(e: Event, form: any, field: string) {
    const files = (e.target as HTMLInputElement).files
    if (files?.length) form[field] = files[0]
}
function yesNo(v: boolean) { return v ? 'Yes' : 'No' }
</script>

<template>
    <Head :title="`Review · ${driver.name} ${driver.last_name}`" />

    <div class="grid grid-cols-12 gap-y-6 gap-x-6">

        <!-- Breadcrumb -->
        <div class="col-span-12">
            <Link :href="route('admin.driver-recruitment.index')"
                class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-primary transition">
                <Lucide icon="ChevronLeft" class="w-4 h-4" /> Back to Driver Recruitment
            </Link>
        </div>

        <!-- ── Header Card ──────────────────────────────────────────────── -->
        <div class="col-span-12">
            <div class="box box--stacked">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between p-5 border-b border-slate-200/60 bg-slate-50 rounded-t-xl gap-4">
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 rounded-full overflow-hidden bg-slate-200 flex items-center justify-center flex-shrink-0">
                            <img v-if="driver.profile_photo" :src="driver.profile_photo" :alt="driver.name" class="w-full h-full object-cover" />
                            <Lucide v-else icon="User" class="w-7 h-7 text-slate-400" />
                        </div>
                        <div>
                            <div class="text-lg font-semibold text-slate-800">{{ driver.name }} {{ driver.last_name }}</div>
                            <div class="flex flex-wrap items-center gap-3 text-sm text-slate-500 mt-0.5">
                                <span class="flex items-center gap-1"><Lucide icon="Mail" class="w-3.5 h-3.5" />{{ driver.email }}</span>
                                <span class="flex items-center gap-1"><Lucide icon="Phone" class="w-3.5 h-3.5" />{{ driver.phone }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-medium" :class="statusBadge(driver.status).classes">
                            <Lucide :icon="statusBadge(driver.status).icon" class="w-4 h-4" />
                            {{ statusBadge(driver.status).label }}
                        </span>
                        <span class="text-sm text-slate-500">Apply: {{ driver.application_date }}</span>
                    </div>
                </div>
                <div class="p-5 grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="bg-slate-50 p-4 rounded-lg"><div class="text-xs text-slate-500 mb-1">Carrier</div><div class="font-medium">{{ driver.carrier_name }}</div></div>
                    <div class="bg-slate-50 p-4 rounded-lg"><div class="text-xs text-slate-500 mb-1">Date of Birth</div><div class="font-medium">{{ driver.date_of_birth }}</div></div>
                    <div class="bg-slate-50 p-4 rounded-lg">
                        <div class="text-xs text-slate-500 mb-1">License</div>
                        <div class="font-medium">
                            <template v-if="driver.licenses.length">{{ driver.licenses[0].license_number }} ({{ driver.licenses[0].state_of_issue }})</template>
                            <template v-else>Not registered</template>
                        </div>
                    </div>
                    <div class="bg-slate-50 p-4 rounded-lg">
                        <div class="text-xs text-slate-500 mb-2">Wizard Progress</div>
                        <div class="flex items-center gap-2">
                            <div class="flex-1 bg-slate-200 rounded-full h-2.5 overflow-hidden">
                                <div class="bg-primary h-2.5 rounded-full transition-all" :style="`width:${driver.completion_pct}%`"></div>
                            </div>
                            <span class="text-xs font-medium text-slate-700">{{ driver.completion_pct }}%</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Left: Tabs ──────────────────────────────────────────────── -->
        <div class="col-span-12 xl:col-span-8">

            <!-- Tab nav -->
            <div class="flex flex-wrap border rounded-xl bg-white overflow-hidden shadow-sm mb-5">
                <button v-for="tab in tabs" :key="tab.key" type="button"
                    class="px-4 py-3 text-sm font-medium transition"
                    :class="currentTab === tab.key ? 'bg-primary text-white' : 'bg-slate-50 text-slate-600 hover:bg-slate-100'"
                    @click="currentTab = tab.key">
                    {{ tab.label }}
                </button>
            </div>

            <div class="box box--stacked p-6">

                <!-- ── PROFILE ──────────────────────────────────────────── -->
                <template v-if="currentTab === 'general'">
                    <h3 class="text-base font-semibold text-slate-800 mb-4">Driver Applicant Information</h3>
                    <div class="grid grid-cols-2 gap-4 mb-6">
                        <div><div class="text-xs text-slate-500">Full Name</div><div class="font-medium">{{ driver.name }} {{ driver.middle_name }} {{ driver.last_name }}</div></div>
                        <div><div class="text-xs text-slate-500">Email</div><div class="font-medium">{{ driver.email }}</div></div>
                        <div><div class="text-xs text-slate-500">Phone</div><div class="font-medium">{{ driver.phone }}</div></div>
                        <div><div class="text-xs text-slate-500">Date of Birth</div><div class="font-medium">{{ driver.date_of_birth }}</div></div>
                    </div>

                    <!-- Address -->
                    <div v-if="primaryAddress" class="border-t pt-5 mb-5">
                        <h4 class="text-sm font-semibold text-slate-700 mb-3">Current Address</h4>
                        <div class="grid grid-cols-2 gap-4">
                            <div><div class="text-xs text-slate-500">Address</div><div class="font-medium">{{ primaryAddress.address_line1 }}<span v-if="primaryAddress.address_line2">, {{ primaryAddress.address_line2 }}</span></div></div>
                            <div><div class="text-xs text-slate-500">City, State, ZIP</div><div class="font-medium">{{ primaryAddress.city }}, {{ primaryAddress.state }} {{ primaryAddress.zip_code }}</div></div>
                            <div><div class="text-xs text-slate-500">Resident since</div><div class="font-medium">{{ primaryAddress.from_date }}</div></div>
                        </div>
                        <template v-if="!primaryAddress.lived_three_years && secondaryAddresses.length">
                            <h4 class="text-sm font-semibold text-slate-700 mt-4 mb-2">Previous Addresses</h4>
                            <div v-for="addr in secondaryAddresses" :key="addr.id" class="bg-slate-50 p-3 rounded-lg border border-slate-100 mb-2">
                                <div class="grid grid-cols-2 gap-3">
                                    <div><div class="text-xs text-slate-500">Address</div><div class="text-sm font-medium">{{ addr.address_line1 }}</div></div>
                                    <div><div class="text-xs text-slate-500">City, State, ZIP</div><div class="text-sm font-medium">{{ addr.city }}, {{ addr.state }} {{ addr.zip_code }}</div></div>
                                    <div><div class="text-xs text-slate-500">Period</div><div class="text-sm font-medium">{{ addr.from_date }} – {{ addr.to_date ?? 'Present' }}</div></div>
                                </div>
                            </div>
                        </template>
                    </div>
                    <div v-else class="border-t pt-5 mb-5 italic text-slate-400 text-sm">No address information recorded.</div>

                    <!-- Application details -->
                    <div v-if="driver.application?.details" class="border-t pt-5">
                        <h4 class="text-sm font-semibold text-slate-700 mb-3">Application Information</h4>
                        <div class="grid grid-cols-2 gap-4">
                            <div><div class="text-xs text-slate-500">Position Applied</div><div class="font-medium">{{ formatPosition(driver.application.details.applying_position, driver.application.details.applying_position_other) }}</div></div>
                            <div><div class="text-xs text-slate-500">Preferred Location</div><div class="font-medium">{{ driver.application.details.applying_location }}</div></div>
                            <div><div class="text-xs text-slate-500">Eligible to work in US</div><div class="font-medium">{{ yesNo(driver.application.details.eligible_to_work) }}</div></div>
                            <div><div class="text-xs text-slate-500">Speaks English</div><div class="font-medium">{{ yesNo(driver.application.details.can_speak_english) }}</div></div>
                            <div>
                                <div class="text-xs text-slate-500">TWIC Card</div>
                                <div class="font-medium">
                                    <template v-if="driver.application.details.has_twic_card">Yes, expires: {{ driver.application.details.twic_expiration_date }}</template>
                                    <template v-else>No</template>
                                </div>
                            </div>
                            <div><div class="text-xs text-slate-500">How did you hear?</div><div class="font-medium">{{ formatSource(driver.application.details.how_did_hear, driver.application.details.how_did_hear_other, driver.application.details.referral_employee_name) }}</div></div>
                        </div>
                    </div>
                </template>

                <!-- ── LICENSES ──────────────────────────────────────────── -->
                <template v-else-if="currentTab === 'licenses'">
                    <h3 class="text-base font-semibold text-slate-800 mb-4">Driver's License Information</h3>
                    <div v-if="!driver.licenses.length" class="italic text-slate-400 text-sm mb-6">No license information recorded.</div>

                    <div v-for="license in driver.licenses" :key="license.id" class="bg-slate-50 p-4 rounded-lg border border-slate-200 mb-4">
                        <!-- Info grid -->
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-4">
                            <div><div class="text-xs text-slate-500">License Number</div><div class="font-medium">{{ license.license_number }}</div></div>
                            <div><div class="text-xs text-slate-500">State</div><div class="font-medium">{{ license.state_of_issue }}</div></div>
                            <div><div class="text-xs text-slate-500">Class</div><div class="font-medium">{{ license.license_class }}</div></div>
                            <div>
                                <div class="text-xs text-slate-500">Expiration</div>
                                <div class="font-medium" :class="license.is_expired ? 'text-red-600' : ''">
                                    {{ license.expiration_date }}
                                    <span v-if="license.is_expired" class="ml-1 text-xs bg-red-100 text-red-600 px-1.5 py-0.5 rounded">Expired</span>
                                </div>
                            </div>
                            <div><div class="text-xs text-slate-500">Type</div><div class="font-medium">{{ license.is_cdl ? 'CDL' : 'Non-CDL' }}</div></div>
                            <div><div class="text-xs text-slate-500">Status</div><div class="font-medium capitalize">{{ license.status }}</div></div>
                        </div>

                        <!-- Endorsements -->
                        <div v-if="license.is_cdl && license.endorsements.length" class="mb-4 pt-3 border-t border-slate-200">
                            <div class="text-xs text-slate-500 mb-1.5">Endorsements</div>
                            <div class="flex flex-wrap gap-1.5">
                                <span v-for="e in license.endorsements" :key="e.code" class="px-2 py-0.5 bg-primary/10 text-primary rounded text-xs">{{ e.code }}: {{ e.name }}</span>
                            </div>
                        </div>

                        <!-- License images -->
                        <div class="pt-3 border-t border-slate-200">
                            <div class="text-xs text-slate-500 mb-2">License Images</div>
                            <div class="flex gap-6 flex-wrap">
                                <!-- Front -->
                                <div>
                                    <div class="text-xs text-slate-400 mb-1.5 font-medium">Front</div>
                                    <div class="relative group">
                                        <a v-if="license.front_image" :href="license.front_image" target="_blank">
                                            <img :src="license.front_image" alt="License front" class="h-28 w-44 border rounded object-contain bg-white" />
                                        </a>
                                        <div v-else class="h-28 w-44 border border-dashed border-slate-300 rounded flex items-center justify-center bg-white">
                                            <span class="text-xs text-slate-400">No image</span>
                                        </div>
                                        <button type="button"
                                            class="mt-2 w-44 flex items-center justify-center gap-1.5 px-2 py-1.5 border border-slate-300 rounded text-xs text-slate-600 hover:bg-slate-100 transition"
                                            @click="openLicenseImageModal(license.id, 'front')">
                                            <Lucide icon="Upload" class="w-3.5 h-3.5" />
                                            {{ license.front_image ? 'Replace' : 'Upload' }} Front
                                        </button>
                                    </div>
                                </div>
                                <!-- Back -->
                                <div>
                                    <div class="text-xs text-slate-400 mb-1.5 font-medium">Back</div>
                                    <div>
                                        <a v-if="license.back_image" :href="license.back_image" target="_blank">
                                            <img :src="license.back_image" alt="License back" class="h-28 w-44 border rounded object-contain bg-white" />
                                        </a>
                                        <div v-else class="h-28 w-44 border border-dashed border-slate-300 rounded flex items-center justify-center bg-white">
                                            <span class="text-xs text-slate-400">No image</span>
                                        </div>
                                        <button type="button"
                                            class="mt-2 w-44 flex items-center justify-center gap-1.5 px-2 py-1.5 border border-slate-300 rounded text-xs text-slate-600 hover:bg-slate-100 transition"
                                            @click="openLicenseImageModal(license.id, 'back')">
                                            <Lucide icon="Upload" class="w-3.5 h-3.5" />
                                            {{ license.back_image ? 'Replace' : 'Upload' }} Back
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Driving Experience -->
                    <div v-if="driver.experiences.length" class="mt-6">
                        <h4 class="text-sm font-semibold text-slate-700 mb-3">Driving Experience</h4>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm border-collapse">
                                <thead><tr class="bg-slate-50 text-slate-500 text-xs">
                                    <th class="p-2 text-left border border-slate-200">Equipment</th>
                                    <th class="p-2 text-left border border-slate-200">Years</th>
                                    <th class="p-2 text-left border border-slate-200">Miles</th>
                                    <th class="p-2 text-left border border-slate-200">CDL Req.</th>
                                </tr></thead>
                                <tbody>
                                    <tr v-for="(exp, i) in driver.experiences" :key="i">
                                        <td class="p-2 border border-slate-200">{{ exp.equipment_type }}</td>
                                        <td class="p-2 border border-slate-200">{{ exp.years_experience }}</td>
                                        <td class="p-2 border border-slate-200">{{ exp.miles_driven }}</td>
                                        <td class="p-2 border border-slate-200">{{ yesNo(exp.requires_cdl) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </template>

                <!-- ── MEDICAL ────────────────────────────────────────────── -->
                <template v-else-if="currentTab === 'medical'">
                    <h3 class="text-base font-semibold text-slate-800 mb-4">Medical Qualification</h3>
                    <div v-if="!driver.medical" class="italic text-slate-400 text-sm">No medical information recorded.</div>
                    <div v-else>
                        <div class="grid grid-cols-2 gap-4 mb-5">
                            <div>
                                <div class="text-xs text-slate-500 mb-0.5">Medical Examiner Name</div>
                                <div class="font-medium">{{ driver.medical.medical_examiner_name ?? '—' }}</div>
                            </div>
                            <div>
                                <div class="text-xs text-slate-500 mb-0.5">Medical Examiner Registry #</div>
                                <div class="font-medium">{{ driver.medical.medical_examiner_registry_number ?? '—' }}</div>
                            </div>
                            <div>
                                <div class="text-xs text-slate-500 mb-0.5">Medical Card Expiration Date</div>
                                <div class="font-medium" :class="driver.medical.is_expired ? 'text-red-600' : ''">
                                    {{ driver.medical.medical_card_expiration_date ?? '—' }}
                                    <span v-if="driver.medical.is_expired" class="ml-1 text-xs bg-red-100 text-red-600 px-1.5 py-0.5 rounded">Expired</span>
                                </div>
                            </div>
                            <div>
                                <div class="text-xs text-slate-500 mb-0.5">SSN (last 4 digits)</div>
                                <div class="font-medium">
                                    <template v-if="driver.medical.ssn_last4">••••–{{ driver.medical.ssn_last4 }}</template>
                                    <template v-else>—</template>
                                </div>
                            </div>
                        </div>

                        <!-- Medical card image -->
                        <div class="border-t pt-4">
                            <div class="text-xs text-slate-500 mb-2 font-medium">Medical Card Image</div>
                            <div class="flex flex-col gap-3">
                                <a v-if="driver.medical.medical_card_url" :href="driver.medical.medical_card_url" target="_blank" class="inline-block">
                                    <img :src="driver.medical.medical_card_url" alt="Medical card" class="h-36 border rounded object-contain bg-white hover:opacity-90 transition" />
                                </a>
                                <div v-else class="h-28 w-52 border border-dashed border-slate-300 rounded flex items-center justify-center bg-white">
                                    <span class="text-xs text-slate-400">No medical card image</span>
                                </div>
                                <button type="button"
                                    class="inline-flex items-center gap-1.5 px-3 py-2 border border-slate-300 rounded-lg text-sm text-slate-600 hover:bg-slate-50 transition w-fit"
                                    @click="medicalImageModal = true">
                                    <Lucide icon="Upload" class="w-4 h-4" />
                                    {{ driver.medical.medical_card_url ? 'Replace' : 'Upload' }} Medical Card
                                </button>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- ── RECORDS ───────────────────────────────────────────── -->
                <template v-else-if="currentTab === 'records'">

                    <!-- Commercial Driver Training Schools -->
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-base font-semibold text-slate-800">Commercial Driver Training Schools</h3>
                        <button type="button"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-primary text-white rounded-lg text-xs font-medium hover:bg-primary/90 transition"
                            @click="openTrainingModal()">
                            <Lucide icon="Plus" class="w-3.5 h-3.5" /> Add Training
                        </button>
                    </div>
                    <div v-if="!driver.training_schools.length" class="italic text-slate-400 text-sm mb-5">No training schools recorded.</div>
                    <div v-for="school in driver.training_schools" :key="school.id" class="bg-slate-50 p-4 rounded-lg border border-slate-200 mb-3 relative">
                        <div class="absolute top-3 right-3 flex items-center gap-1">
                            <button type="button" @click="openTrainingModal(school)"
                                class="p-1.5 text-slate-400 hover:text-primary transition rounded" title="Edit">
                                <Lucide icon="Pencil" class="w-4 h-4" />
                            </button>
                            <button type="button"
                                @click="deleteRecord('admin.driver-recruitment.training-schools.destroy', { driver: driver.id, school: school.id }, 'this training school')"
                                class="p-1.5 text-slate-400 hover:text-red-500 transition rounded" title="Delete">
                                <Lucide icon="Trash2" class="w-4 h-4" />
                            </button>
                        </div>
                        <div class="grid grid-cols-2 gap-3 text-sm pr-8">
                            <div><div class="text-xs text-slate-500">School Name</div><div class="font-medium">{{ school.school_name }}</div></div>
                            <div><div class="text-xs text-slate-500">Location</div><div class="font-medium">{{ school.city }}, {{ school.state }}</div></div>
                            <div><div class="text-xs text-slate-500">Period</div><div class="font-medium">{{ school.date_start ?? '—' }} – {{ school.date_end ?? '—' }}</div></div>
                            <div><div class="text-xs text-slate-500">Graduated</div><div class="font-medium">{{ yesNo(school.graduated) }}</div></div>
                            <div v-if="school.subject_to_safety_regulations || school.performed_safety_functions" class="col-span-2 flex gap-3">
                                <span v-if="school.subject_to_safety_regulations" class="text-xs bg-blue-50 text-blue-700 border border-blue-200 px-2 py-0.5 rounded-full">Subject to Safety Regs</span>
                                <span v-if="school.performed_safety_functions" class="text-xs bg-blue-50 text-blue-700 border border-blue-200 px-2 py-0.5 rounded-full">Performed Safety Functions</span>
                            </div>
                        </div>
                        <div v-if="school.training_skills && school.training_skills.length" class="mt-3 pt-3 border-t border-slate-200">
                            <div class="text-xs text-slate-500 mb-1">Skills learned</div>
                            <div class="flex flex-wrap gap-1.5">
                                <span v-for="skill in school.training_skills" :key="skill"
                                    class="px-2 py-0.5 bg-primary/10 text-primary rounded text-xs capitalize">
                                    {{ skill.replace(/_/g, ' ') }}
                                </span>
                            </div>
                        </div>
                        <div v-if="school.certificates && school.certificates.length" class="mt-3 pt-3 border-t border-slate-200">
                            <div class="text-xs text-slate-500 mb-2">Certificates</div>
                            <div class="flex flex-wrap gap-2">
                                <a v-for="cert in school.certificates" :key="cert.id" :href="cert.url" target="_blank" class="block">
                                    <img v-if="cert.is_image" :src="cert.url" :alt="cert.name" class="h-16 border rounded object-contain bg-white" />
                                    <div v-else class="h-16 w-16 border rounded flex flex-col items-center justify-center bg-white text-slate-500 gap-1">
                                        <Lucide icon="FileText" class="w-5 h-5" /><span class="text-xs">PDF</span>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Courses -->
                    <div class="flex items-center justify-between mb-3 mt-6 border-t pt-5">
                        <h3 class="text-base font-semibold text-slate-800">Courses</h3>
                        <button type="button"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-primary text-white rounded-lg text-xs font-medium hover:bg-primary/90 transition"
                            @click="openCourseModal()">
                            <Lucide icon="Plus" class="w-3.5 h-3.5" /> Add Course
                        </button>
                    </div>
                    <div v-if="!driver.courses.length" class="italic text-slate-400 text-sm mb-5">No courses have been recorded.</div>
                    <div v-for="course in driver.courses" :key="course.id" class="bg-slate-50 p-4 rounded-lg border border-slate-200 mb-3 relative">
                        <div class="absolute top-3 right-3 flex items-center gap-1">
                            <button type="button" @click="openCourseModal(course)"
                                class="p-1.5 text-slate-400 hover:text-primary transition rounded" title="Edit">
                                <Lucide icon="Pencil" class="w-4 h-4" />
                            </button>
                            <button type="button"
                                @click="deleteRecord('admin.driver-recruitment.courses.destroy', { driver: driver.id, course: course.id }, 'this course')"
                                class="p-1.5 text-slate-400 hover:text-red-500 transition rounded" title="Delete">
                                <Lucide icon="Trash2" class="w-4 h-4" />
                            </button>
                        </div>
                        <div class="grid grid-cols-2 gap-3 text-sm pr-8">
                            <div><div class="text-xs text-slate-500">Organization</div><div class="font-medium">{{ course.organization_name }}</div></div>
                            <div>
                                <div class="text-xs text-slate-500">Status</div>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                    :class="course.status === 'Active' ? 'bg-emerald-100 text-emerald-700' : course.status === 'Expired' ? 'bg-red-100 text-red-600' : 'bg-amber-100 text-amber-700'">
                                    {{ course.status }}
                                </span>
                            </div>
                            <div><div class="text-xs text-slate-500">Location</div><div class="font-medium">{{ [course.city, course.state].filter(Boolean).join(', ') || '—' }}</div></div>
                            <div><div class="text-xs text-slate-500">Certification Date</div><div class="font-medium">{{ course.certification_date ?? '—' }}</div></div>
                            <div><div class="text-xs text-slate-500">Expiration Date</div><div class="font-medium">{{ course.expiration_date ?? '—' }}</div></div>
                            <div v-if="course.years_experience"><div class="text-xs text-slate-500">Years of Experience</div><div class="font-medium">{{ course.years_experience }}</div></div>
                            <div v-if="course.experience" class="col-span-2"><div class="text-xs text-slate-500">Notes / Experience</div><div class="font-medium whitespace-pre-line">{{ course.experience }}</div></div>
                        </div>
                        <div v-if="course.certificates && course.certificates.length" class="mt-3 pt-3 border-t border-slate-200">
                            <div class="text-xs text-slate-500 mb-2">Certificates</div>
                            <div class="flex flex-wrap gap-2">
                                <a v-for="cert in course.certificates" :key="cert.id" :href="cert.url" target="_blank" class="block">
                                    <img v-if="cert.is_image" :src="cert.url" :alt="cert.name" class="h-16 border rounded object-contain bg-white" />
                                    <div v-else class="h-16 w-16 border rounded flex flex-col items-center justify-center bg-white text-slate-500 gap-1">
                                        <Lucide icon="FileText" class="w-5 h-5" /><span class="text-xs">DOC</span>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Testing -->
                    <div class="mt-6 border-t pt-5">
                        <h3 class="text-base font-semibold text-slate-800 mb-3">Testing</h3>
                        <div v-if="!driver.testings.length" class="italic text-slate-400 text-sm mb-5">No tests have been recorded.</div>
                        <div v-for="test in driver.testings" :key="test.id" class="bg-slate-50 p-4 rounded-lg border border-slate-200 mb-3">
                            <div class="grid grid-cols-2 md:grid-cols-3 gap-3 text-sm">
                                <div><div class="text-xs text-slate-500">Date</div><div class="font-medium">{{ test.test_date }}</div></div>
                                <div><div class="text-xs text-slate-500">Type</div><div class="font-medium capitalize">{{ test.test_type }}</div></div>
                                <div>
                                    <div class="text-xs text-slate-500">Result</div>
                                    <div class="font-medium" :class="test.test_result === 'positive' ? 'text-red-600' : 'text-emerald-600'">
                                        {{ test.test_result }}
                                    </div>
                                </div>
                                <div><div class="text-xs text-slate-500">Status</div><div class="font-medium capitalize">{{ test.status }}</div></div>
                                <div v-if="test.administered_by"><div class="text-xs text-slate-500">Administered By</div><div class="font-medium">{{ test.administered_by }}</div></div>
                                <div>
                                    <div class="text-xs text-slate-500">Category</div>
                                    <div class="font-medium text-xs">
                                        <span v-if="test.is_pre_employment_test">Pre-Employment</span>
                                        <span v-else-if="test.is_random_test">Random</span>
                                        <span v-else-if="test.is_post_accident_test">Post-Accident</span>
                                        <span v-else>Other</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Traffic Convictions -->
                    <div class="mt-6 border-t pt-5">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="text-base font-semibold text-slate-800">Traffic Convictions</h3>
                            <button type="button"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-primary text-white rounded-lg text-xs font-medium hover:bg-primary/90 transition"
                                @click="openTrafficModal()">
                                <Lucide icon="Plus" class="w-3.5 h-3.5" /> Add Traffic Conviction
                            </button>
                        </div>
                        <div v-if="!driver.traffic_convictions.length" class="italic text-slate-400 text-sm mb-5">No traffic convictions have been registered.</div>
                        <div v-for="tc in driver.traffic_convictions" :key="tc.id" class="bg-slate-50 p-4 rounded-lg border border-slate-200 mb-3 relative">
                            <div class="absolute top-3 right-3 flex items-center gap-1">
                                <button type="button" @click="openTrafficModal(tc)"
                                    class="p-1.5 text-slate-400 hover:text-primary transition rounded" title="Edit">
                                    <Lucide icon="Pencil" class="w-4 h-4" />
                                </button>
                                <button type="button"
                                    @click="deleteRecord('admin.driver-recruitment.traffic-convictions.destroy', { driver: driver.id, conviction: tc.id }, 'this traffic conviction')"
                                    class="p-1.5 text-slate-400 hover:text-red-500 transition rounded" title="Delete">
                                    <Lucide icon="Trash2" class="w-4 h-4" />
                                </button>
                            </div>
                            <div class="grid grid-cols-2 gap-3 text-sm pr-16">
                                <div><div class="text-xs text-slate-500">Date</div><div class="font-medium">{{ tc.conviction_date ?? '—' }}</div></div>
                                <div><div class="text-xs text-slate-500">Location</div><div class="font-medium">{{ tc.location ?? '—' }}</div></div>
                                <div><div class="text-xs text-slate-500">Charge</div><div class="font-medium">{{ tc.charge }}</div></div>
                                <div><div class="text-xs text-slate-500">Penalty</div><div class="font-medium">{{ tc.penalty ?? '—' }}</div></div>
                                <div v-if="tc.conviction_type"><div class="text-xs text-slate-500">Type</div><div class="font-medium">{{ tc.conviction_type }}</div></div>
                                <div v-if="tc.description" class="col-span-2"><div class="text-xs text-slate-500">Description</div><div class="font-medium">{{ tc.description }}</div></div>
                            </div>
                        </div>
                    </div>

                    <!-- Accident Record -->
                    <div class="mt-6 border-t pt-5">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="text-base font-semibold text-slate-800">Accident Record</h3>
                            <button type="button"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-primary text-white rounded-lg text-xs font-medium hover:bg-primary/90 transition"
                                @click="openAccidentModal()">
                                <Lucide icon="Plus" class="w-3.5 h-3.5" /> Add Accident
                            </button>
                        </div>
                        <div v-if="!driver.accidents.length" class="italic text-slate-400 text-sm mb-5">No accidents have been registered.</div>
                        <div v-for="acc in driver.accidents" :key="acc.id" class="bg-slate-50 p-4 rounded-lg border border-slate-200 mb-3 relative">
                            <div class="absolute top-3 right-3 flex items-center gap-1">
                                <button type="button" @click="openAccidentModal(acc)"
                                    class="p-1.5 text-slate-400 hover:text-primary transition rounded" title="Edit">
                                    <Lucide icon="Pencil" class="w-4 h-4" />
                                </button>
                                <button type="button"
                                    @click="deleteRecord('admin.driver-recruitment.accidents.destroy', { driver: driver.id, accident: acc.id }, 'this accident record')"
                                    class="p-1.5 text-slate-400 hover:text-red-500 transition rounded" title="Delete">
                                    <Lucide icon="Trash2" class="w-4 h-4" />
                                </button>
                            </div>
                            <div class="grid grid-cols-2 gap-3 text-sm pr-16">
                                <div><div class="text-xs text-slate-500">Date</div><div class="font-medium">{{ acc.accident_date ?? '—' }}</div></div>
                                <div class="col-span-2"><div class="text-xs text-slate-500">Nature of Accident</div><div class="font-medium">{{ acc.nature_of_accident }}</div></div>
                                <div><div class="text-xs text-slate-500">Fatalities</div><div class="font-medium">{{ yesNo(acc.had_fatalities) }}{{ acc.number_of_fatalities > 0 ? ` (${acc.number_of_fatalities})` : '' }}</div></div>
                                <div><div class="text-xs text-slate-500">Injuries</div><div class="font-medium">{{ yesNo(acc.had_injuries) }}{{ acc.number_of_injuries > 0 ? ` (${acc.number_of_injuries})` : '' }}</div></div>
                                <div v-if="acc.comments" class="col-span-2"><div class="text-xs text-slate-500">Comments</div><div class="font-medium">{{ acc.comments }}</div></div>
                            </div>
                        </div>
                    </div>

                    <!-- FMCSR Data -->
                    <div class="mt-6 border-t pt-5">
                        <h3 class="text-base font-semibold text-slate-800 mb-3">FMCSR Data</h3>
                        <div v-if="!driver.fmcsr_data" class="italic text-slate-400 text-sm">No FMCSR data recorded.</div>
                        <div v-else class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <div class="text-xs text-slate-500">Are you disqualified?</div>
                                <div class="font-medium" :class="driver.fmcsr_data.is_disqualified ? 'text-red-600' : 'text-emerald-600'">{{ yesNo(driver.fmcsr_data.is_disqualified) }}</div>
                                <div v-if="driver.fmcsr_data.disqualified_details" class="text-xs text-slate-600 mt-0.5">{{ driver.fmcsr_data.disqualified_details }}</div>
                            </div>
                            <div>
                                <div class="text-xs text-slate-500">License suspended?</div>
                                <div class="font-medium" :class="driver.fmcsr_data.is_license_suspended ? 'text-red-600' : 'text-emerald-600'">{{ yesNo(driver.fmcsr_data.is_license_suspended) }}</div>
                                <div v-if="driver.fmcsr_data.suspension_details" class="text-xs text-slate-600 mt-0.5">{{ driver.fmcsr_data.suspension_details }}</div>
                            </div>
                            <div>
                                <div class="text-xs text-slate-500">License denied?</div>
                                <div class="font-medium" :class="driver.fmcsr_data.is_license_denied ? 'text-red-600' : 'text-emerald-600'">{{ yesNo(driver.fmcsr_data.is_license_denied) }}</div>
                                <div v-if="driver.fmcsr_data.denial_details" class="text-xs text-slate-600 mt-0.5">{{ driver.fmcsr_data.denial_details }}</div>
                            </div>
                            <div>
                                <div class="text-xs text-slate-500">Positive drug test?</div>
                                <div class="font-medium" :class="driver.fmcsr_data.has_positive_drug_test ? 'text-red-600' : 'text-emerald-600'">{{ yesNo(driver.fmcsr_data.has_positive_drug_test) }}</div>
                            </div>
                            <div>
                                <div class="text-xs text-slate-500">Duty offenses?</div>
                                <div class="font-medium" :class="driver.fmcsr_data.has_duty_offenses ? 'text-red-600' : 'text-emerald-600'">{{ yesNo(driver.fmcsr_data.has_duty_offenses) }}</div>
                                <div v-if="driver.fmcsr_data.offense_details" class="text-xs text-slate-600 mt-0.5">{{ driver.fmcsr_data.offense_details }}</div>
                            </div>
                            <div>
                                <div class="text-xs text-slate-500">Consent to release driving record?</div>
                                <div class="font-medium">{{ yesNo(driver.fmcsr_data.consent_driving_record) }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Criminal History -->
                    <div class="mt-6 border-t pt-5">
                        <h3 class="text-base font-semibold text-slate-800 mb-3">Criminal History</h3>
                        <div v-if="!driver.criminal_history" class="italic text-slate-400 text-sm">No criminal history recorded.</div>
                        <div v-else class="grid grid-cols-2 gap-4 text-sm">
                            <div><div class="text-xs text-slate-500">Criminal Charges</div><div class="font-medium">{{ yesNo(driver.criminal_history.has_criminal_charges) }}</div></div>
                            <div><div class="text-xs text-slate-500">Felony Conviction</div><div class="font-medium">{{ yesNo(driver.criminal_history.has_felony_conviction) }}</div></div>
                            <div><div class="text-xs text-slate-500">Minister's Permit</div><div class="font-medium">{{ yesNo(driver.criminal_history.has_minister_permit) }}</div></div>
                            <div><div class="text-xs text-slate-500">FCRA Consent</div><div class="font-medium">{{ yesNo(driver.criminal_history.fcra_consent) }}</div></div>
                        </div>
                    </div>
                </template>

                <!-- ── HISTORY ────────────────────────────────────────────── -->
                <template v-else-if="currentTab === 'history'">
                    <h3 class="text-base font-semibold text-slate-800 mb-4">Employment History Timeline</h3>

                    <div v-if="!historyTimeline.length" class="italic text-slate-400 text-sm">No employment history recorded.</div>

                    <!-- Timeline -->
                    <div v-else class="relative border-l-2 border-slate-200 ml-4 space-y-0">
                        <div v-for="(item, i) in historyTimeline" :key="i" class="relative ml-6 pb-6">
                            <!-- Dot -->
                            <div class="absolute -left-[33px] top-1 w-5 h-5 rounded-full flex items-center justify-center ring-4 ring-white"
                                :class="{
                                    'bg-primary':    item.type === 'employment',
                                    'bg-emerald-500':item.type === 'related',
                                    'bg-amber-400':  item.type === 'unemployment',
                                }">
                                <Lucide :icon="item.type === 'unemployment' ? 'Clock' : 'Briefcase'" class="w-2.5 h-2.5 text-white" />
                            </div>

                            <!-- Card -->
                            <div class="bg-slate-50 border border-slate-200 rounded-lg p-4">
                                <div class="text-xs text-slate-500 mb-1.5">
                                    {{ item.start }} — {{ item.end }}
                                </div>

                                <!-- Employment -->
                                <template v-if="item.type === 'employment'">
                                    <div class="font-semibold text-slate-800 text-sm">{{ item.data.company_name }}</div>
                                    <div class="text-sm text-slate-600">{{ item.data.position_held }}</div>
                                    <div class="text-xs text-slate-500 mt-1">{{ [item.data.city, item.data.state].filter(Boolean).join(', ') }}</div>
                                    <div v-if="item.data.phone" class="text-xs text-slate-500">{{ item.data.phone }}</div>
                                    <div class="mt-2 flex flex-wrap gap-2 text-xs">
                                        <span v-if="item.data.subject_to_fmcsr" class="px-2 py-0.5 bg-blue-50 text-blue-700 border border-blue-200 rounded-full">FMCSR</span>
                                        <span v-if="item.data.safety_sensitive" class="px-2 py-0.5 bg-orange-50 text-orange-700 border border-orange-200 rounded-full">Safety-Sensitive</span>
                                        <span v-if="item.data.verification_status" class="px-2 py-0.5 rounded-full capitalize"
                                            :class="item.data.verification_status === 'verified' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-600 border border-slate-200'">
                                            {{ item.data.verification_status }}
                                        </span>
                                    </div>
                                    <div v-if="item.data.reason_for_leaving" class="mt-2 text-xs text-slate-600">
                                        <span class="text-slate-400">Reason for leaving: </span>{{ item.data.reason_for_leaving }}
                                    </div>
                                </template>

                                <!-- Related Employment -->
                                <template v-else-if="item.type === 'related'">
                                    <div class="font-semibold text-slate-800 text-sm">Driver Related Employment</div>
                                    <div v-if="item.data.position" class="text-sm text-slate-600">{{ item.data.position }}</div>
                                    <div v-if="item.data.comments" class="text-xs text-slate-500 mt-1">{{ item.data.comments }}</div>
                                </template>

                                <!-- Unemployment -->
                                <template v-else>
                                    <div class="font-semibold text-amber-700 text-sm">Period of Unemployment</div>
                                    <div v-if="item.data.comments" class="text-xs text-slate-600 mt-1">{{ item.data.comments }}</div>
                                </template>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- ── TRAINING ───────────────────────────────────────────── -->
                <template v-else-if="currentTab === 'training'">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-base font-semibold text-slate-800">Training Assignments</h3>
                        <a :href="route('admin.training-assignments.index')" target="_blank"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 border border-slate-200 text-slate-600 rounded-lg text-xs font-medium hover:bg-slate-50 transition">
                            <Lucide icon="ExternalLink" class="w-3.5 h-3.5" /> Manage Trainings
                        </a>
                    </div>

                    <div v-if="!driver.driver_trainings.length" class="bg-white border border-slate-200 rounded-lg p-8 text-center">
                        <Lucide icon="BookOpen" class="w-10 h-10 text-slate-300 mx-auto mb-3" />
                        <p class="text-slate-500 italic mb-2">No training assignments recorded for this driver.</p>
                        <a :href="route('admin.training-assignments.index')" target="_blank"
                            class="text-sm text-primary hover:underline">Go to Training Management</a>
                    </div>

                    <div v-else class="overflow-x-auto rounded-lg border border-slate-200">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Training</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Type</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Assigned</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Due Date</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Completed</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                                    <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-slate-100">
                                <tr v-for="dt in driver.driver_trainings" :key="dt.id" class="hover:bg-slate-50 transition">
                                    <td class="px-4 py-3 font-medium text-slate-800">{{ dt.title }}</td>
                                    <td class="px-4 py-3 capitalize text-slate-500">{{ dt.content_type || '—' }}</td>
                                    <td class="px-4 py-3 text-slate-500">{{ dt.assigned_date ?? '—' }}</td>
                                    <td class="px-4 py-3" :class="dt.is_overdue ? 'text-red-600 font-medium' : 'text-slate-500'">
                                        {{ dt.due_date ?? '—' }}
                                        <span v-if="dt.is_overdue" class="ml-1 text-xs">(Overdue)</span>
                                    </td>
                                    <td class="px-4 py-3 text-slate-500">{{ dt.completed_date ?? '—' }}</td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                            :class="{
                                                'bg-emerald-100 text-emerald-700': dt.status === 'completed',
                                                'bg-red-100 text-red-600':        dt.is_overdue,
                                                'bg-amber-100 text-amber-700':    !dt.is_overdue && dt.status !== 'completed',
                                            }">
                                            {{ dt.status === 'completed' ? 'Completed' : dt.is_overdue ? 'Overdue' : dt.status.replace('_',' ') }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <a :href="route('admin.trainings.show', dt.training_id)" target="_blank"
                                            class="text-xs text-primary hover:underline">View</a>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </template>

                <!-- ── DOCUMENTS ──────────────────────────────────────────── -->
                <template v-else-if="currentTab === 'documents'">

                    <!-- Application PDF -->
                    <div class="mb-8">
                        <h3 class="text-base font-semibold text-slate-800 flex items-center gap-2 mb-3">
                            <Lucide icon="FileText" class="w-4 h-4 text-primary" />
                            Application PDF
                        </h3>
                        <div v-if="driver.application?.pdf_url"
                            class="flex items-center justify-between border border-slate-200 bg-white rounded-lg p-3 hover:border-slate-300 transition">
                            <div class="flex items-center gap-2">
                                <div class="p-2 bg-slate-100 rounded-lg">
                                    <Lucide icon="FileText" class="w-4 h-4 text-slate-600" />
                                </div>
                                <span class="text-sm font-medium text-slate-700">Complete Application</span>
                            </div>
                            <a :href="driver.application.pdf_url" target="_blank"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-100 text-slate-700 rounded-lg text-xs font-medium hover:bg-slate-200 transition">
                                <Lucide icon="Download" class="w-3.5 h-3.5" /> Download
                            </a>
                        </div>
                        <div v-else class="italic text-slate-400 text-sm py-4 border border-dashed border-slate-200 rounded-lg text-center">
                            No application PDF generated yet.
                        </div>
                        <div class="border-t border-slate-100 mt-6"></div>
                    </div>

                    <template v-for="section in [
                        { key: 'driving_records',  label: 'Driving Record',  icon: 'Car' },
                        { key: 'criminal_records', label: 'Criminal Record', icon: 'Shield' },
                        { key: 'medical_records',  label: 'Medical Record',  icon: 'Heart' },
                        { key: 'clearing_house',   label: 'Clearing House',  icon: 'Database' },
                    ]" :key="section.key">
                        <div class="mb-8">
                            <div class="flex items-center justify-between mb-3">
                                <h3 class="text-base font-semibold text-slate-800 flex items-center gap-2">
                                    <Lucide :icon="section.icon" class="w-4 h-4 text-primary" />
                                    {{ section.label }}
                                </h3>
                                <button type="button"
                                    @click="openDocModal(section.key, section.label)"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-primary text-white rounded-lg text-xs font-medium hover:bg-primary/90 transition">
                                    <Lucide icon="Upload" class="w-3.5 h-3.5" /> Upload
                                </button>
                            </div>

                            <!-- Files list -->
                            <div v-if="(driver[section.key as keyof typeof driver] as DriverDocument[]).length"
                                class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div v-for="doc in (driver[section.key as keyof typeof driver] as DriverDocument[])" :key="doc.id"
                                    class="flex items-center justify-between border border-slate-200 bg-white rounded-lg p-3 hover:border-slate-300 transition">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <div class="p-2 bg-slate-100 rounded-lg flex-shrink-0">
                                            <Lucide :icon="doc.is_image ? 'Image' : 'FileText'" class="w-4 h-4 text-slate-600" />
                                        </div>
                                        <div class="min-w-0">
                                            <a :href="doc.url" target="_blank"
                                                class="text-sm font-medium text-primary hover:underline truncate block max-w-[200px]">
                                                {{ doc.name }}
                                            </a>
                                            <span class="text-xs text-slate-400">{{ doc.size }}</span>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-1 flex-shrink-0">
                                        <a :href="doc.url" target="_blank"
                                            class="p-1.5 text-slate-400 hover:text-primary transition rounded" title="View">
                                            <Lucide icon="ExternalLink" class="w-4 h-4" />
                                        </a>
                                        <button type="button" @click="deleteDocument(doc.id)"
                                            class="p-1.5 text-slate-400 hover:text-red-500 transition rounded" title="Delete">
                                            <Lucide icon="Trash2" class="w-4 h-4" />
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="italic text-slate-400 text-sm py-4 border border-dashed border-slate-200 rounded-lg text-center">
                                No {{ section.label.toLowerCase() }} documents uploaded yet.
                            </div>
                            <div class="border-t border-slate-100 mt-6" v-if="section.key !== 'clearing_house'"></div>
                        </div>
                    </template>

                </template>

            </div>
        </div>

        <!-- ── Right: Checklist + Actions ─────────────────────────────── -->
        <div class="col-span-12 xl:col-span-4 space-y-5">

            <!-- Checklist -->
            <div class="box box--stacked p-5">
                <h3 class="text-base font-semibold text-slate-800 mb-4">Verification Checklist</h3>
                <div class="mb-5">
                    <div class="flex justify-between items-center mb-1.5">
                        <span class="text-sm font-medium text-slate-700">Progress</span>
                        <span class="text-sm font-medium text-slate-700">{{ checkedCount }}/{{ totalCount }} ({{ checklistPct }}%)</span>
                    </div>
                    <div class="w-full bg-slate-200 rounded-full h-2.5 overflow-hidden">
                        <div class="bg-primary h-2.5 transition-all" :style="`width:${checklistPct}%`"></div>
                    </div>
                </div>
                <div class="space-y-3 mb-5">
                    <div v-for="group in checklistGroups" :key="group.title" class="border border-slate-200 rounded-lg overflow-hidden">
                        <div class="bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-600 border-b border-slate-200">{{ group.title }}</div>
                        <div class="p-3 space-y-1.5">
                            <template v-for="key in group.keys" :key="key">
                                <div v-if="checklist[key]" class="flex items-center gap-2 p-1 rounded hover:bg-slate-50">
                                    <input :id="`cl-${key}`" v-model="checklist[key].checked" type="checkbox"
                                        class="h-4 w-4 rounded border-slate-300 text-primary cursor-pointer" />
                                    <label :for="`cl-${key}`" class="text-xs text-slate-700 cursor-pointer leading-tight">{{ checklist[key].label }}</label>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="block text-xs font-medium text-slate-700 mb-1.5">Verification Notes</label>
                    <textarea v-model="verificationNotes" rows="3" placeholder="Add notes..."
                        class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary/30 resize-none"></textarea>
                </div>
                <button type="button" :disabled="savingChecklist"
                    class="w-full px-4 py-2.5 bg-primary text-white rounded-lg text-sm font-medium hover:bg-primary/90 transition disabled:opacity-60 flex items-center justify-center gap-2"
                    @click="saveChecklist">
                    <Lucide :icon="savingChecklist ? 'Loader' : 'Save'" class="w-4 h-4" :class="{ 'animate-spin': savingChecklist }" />
                    {{ savingChecklist ? 'Saving...' : 'Save Verification' }}
                </button>
                <div v-if="savedVerification" class="mt-4 p-3 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-600">
                    <div class="font-medium mb-1 text-slate-700">Last verification:</div>
                    <div>{{ savedVerification.verified_at }} · By: {{ savedVerification.verifier }}</div>
                    <div v-if="savedVerification.notes" class="mt-2 p-2 bg-white rounded border border-slate-100">{{ savedVerification.notes }}</div>
                </div>
            </div>

            <!-- Actions -->
            <div class="box box--stacked p-5">
                <template v-if="driver.application && (driver.application.status === 'pending' || driver.application.status === 'draft')">
                    <h3 class="text-base font-semibold text-slate-800 mb-3">Available Actions</h3>
                    <div v-if="!isChecklistComplete" class="mb-3 flex items-start gap-2 p-3 bg-blue-50 border border-blue-200 rounded-lg text-xs text-blue-700">
                        <Lucide icon="Info" class="w-4 h-4 flex-shrink-0 mt-0.5" />
                        Complete all {{ totalCount }} checklist items to enable approval.
                    </div>
                    <div class="flex flex-col gap-2">
                        <button type="button" :disabled="!isChecklistComplete"
                            class="w-full px-4 py-3 bg-emerald-600 text-white rounded-lg text-sm font-medium hover:bg-emerald-700 transition disabled:opacity-40 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                            @click="showApproveModal = true">
                            <Lucide icon="CheckCircle" class="w-4 h-4" /> Approve Application
                        </button>
                        <button type="button"
                            class="w-full px-4 py-3 bg-red-600 text-white rounded-lg text-sm font-medium hover:bg-red-700 transition flex items-center justify-center gap-2"
                            @click="showRejectModal = true">
                            <Lucide icon="XCircle" class="w-4 h-4" /> Reject Application
                        </button>
                    </div>
                </template>
                <template v-else-if="driver.application?.status === 'approved'">
                    <div class="border border-emerald-200 rounded-lg overflow-hidden">
                        <div class="bg-emerald-50 px-4 py-2.5 font-semibold text-emerald-700 text-sm border-b border-emerald-200 flex items-center gap-2"><Lucide icon="CheckCircle" class="w-4 h-4" /> Application Approved</div>
                        <div class="p-4 text-sm text-slate-600">Approval Date: {{ driver.application.completed_at ?? 'N/A' }}</div>
                    </div>
                </template>
                <template v-else-if="driver.application?.status === 'rejected'">
                    <div class="border border-red-200 rounded-lg overflow-hidden">
                        <div class="bg-red-50 px-4 py-2.5 font-semibold text-red-700 text-sm border-b border-red-200 flex items-center gap-2"><Lucide icon="XCircle" class="w-4 h-4" /> Application Rejected</div>
                        <div class="p-4">
                            <div class="text-sm text-slate-600">Rejection Date: {{ driver.application.completed_at ?? 'N/A' }}</div>
                            <div v-if="driver.application.rejection_reason" class="mt-2 p-2 bg-white border border-slate-200 rounded text-sm text-slate-700">{{ driver.application.rejection_reason }}</div>
                        </div>
                    </div>
                </template>
                <template v-else>
                    <p class="text-sm text-slate-400 italic">No application submitted yet.</p>
                </template>
            </div>

            <!-- Steps Status -->
            <div class="box box--stacked p-5">
                <h3 class="text-base font-semibold text-slate-800 mb-3">Steps Status</h3>
                <div class="space-y-1.5">
                    <div v-for="(status, step) in stepsStatus" :key="step"
                        class="flex items-center gap-2 p-2 rounded-lg border text-xs"
                        :class="{
                            'bg-emerald-50 text-emerald-700 border-emerald-200': status === 'completed',
                            'bg-amber-50 text-amber-600 border-amber-100': status === 'pending',
                            'bg-red-50 text-red-600 border-red-100': status === 'missing',
                        }">
                        <Lucide :icon="status === 'completed' ? 'CheckCircle' : status === 'pending' ? 'Clock' : 'XCircle'" class="w-3.5 h-3.5 flex-shrink-0" />
                        <span>{{ stepNames[Number(step)] ?? `Step ${step}` }}</span>
                        <span class="ml-auto capitalize font-medium">{{ status }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════════════
         MODALS — using Base/Headless Dialog
    ══════════════════════════════════════════════════════════════════════ -->

    <!-- License Image Upload -->
    <Dialog :open="licenseImageModal" @close="licenseImageModal = false" size="sm">
        <Dialog.Panel>
            <div class="p-5">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold text-base">Upload License {{ licenseImageForm.side === 'front' ? 'Front' : 'Back' }}</h3>
                    <button @click="licenseImageModal = false" class="text-slate-400 hover:text-slate-600"><Lucide icon="X" class="w-5 h-5" /></button>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Select Image <span class="text-slate-400 text-xs">(JPG, PNG, PDF · max 10MB)</span></label>
                    <input type="file" accept=".jpg,.jpeg,.png,.pdf"
                        class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 file:mr-3 file:py-1 file:px-3 file:rounded file:border-0 file:text-xs file:bg-primary/10 file:text-primary cursor-pointer"
                        @change="e => onFileChange(e, licenseImageForm, 'image')" />
                    <p v-if="licenseImageForm.errors.image" class="text-red-500 text-xs mt-1">{{ licenseImageForm.errors.image }}</p>
                </div>
                <div class="flex gap-2 justify-end">
                    <button type="button" class="px-4 py-2 border border-slate-200 text-slate-700 rounded-lg text-sm hover:bg-slate-50" @click="licenseImageModal = false">Cancel</button>
                    <button type="button" :disabled="!licenseImageForm.image || licenseImageForm.processing"
                        class="px-4 py-2 bg-primary text-white rounded-lg text-sm font-medium disabled:opacity-60 flex items-center gap-2"
                        @click="submitLicenseImage">
                        <Lucide v-if="licenseImageForm.processing" icon="Loader" class="w-4 h-4 animate-spin" />
                        {{ licenseImageForm.processing ? 'Uploading...' : 'Upload' }}
                    </button>
                </div>
            </div>
        </Dialog.Panel>
    </Dialog>

    <!-- Medical Card Upload -->
    <Dialog :open="medicalImageModal" @close="medicalImageModal = false" size="sm">
        <Dialog.Panel>
            <div class="p-5">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold text-base">Upload Medical Card</h3>
                    <button @click="medicalImageModal = false" class="text-slate-400 hover:text-slate-600"><Lucide icon="X" class="w-5 h-5" /></button>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Select Image <span class="text-slate-400 text-xs">(JPG, PNG, PDF · max 10MB)</span></label>
                    <input type="file" accept=".jpg,.jpeg,.png,.pdf"
                        class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 file:mr-3 file:py-1 file:px-3 file:rounded file:border-0 file:text-xs file:bg-primary/10 file:text-primary cursor-pointer"
                        @change="e => onFileChange(e, medicalImageForm, 'image')" />
                    <p v-if="medicalImageForm.errors.image" class="text-red-500 text-xs mt-1">{{ medicalImageForm.errors.image }}</p>
                </div>
                <div class="flex gap-2 justify-end">
                    <button type="button" class="px-4 py-2 border border-slate-200 text-slate-700 rounded-lg text-sm hover:bg-slate-50" @click="medicalImageModal = false">Cancel</button>
                    <button type="button" :disabled="!medicalImageForm.image || medicalImageForm.processing"
                        class="px-4 py-2 bg-primary text-white rounded-lg text-sm font-medium disabled:opacity-60 flex items-center gap-2"
                        @click="submitMedicalImage">
                        <Lucide v-if="medicalImageForm.processing" icon="Loader" class="w-4 h-4 animate-spin" />
                        {{ medicalImageForm.processing ? 'Uploading...' : 'Upload' }}
                    </button>
                </div>
            </div>
        </Dialog.Panel>
    </Dialog>

    <!-- Add / Edit Training School -->
    <Dialog :open="showTrainingModal" @close="showTrainingModal = false" size="lg" staticBackdrop>
        <Dialog.Panel class="max-h-[90vh] overflow-y-auto">
            <div class="p-6">
                <div class="flex items-center justify-between mb-5 pb-4 border-b sticky top-0 bg-white z-10">
                    <h3 class="text-lg font-semibold">{{ editingTrainingId ? 'Edit' : 'Add' }} Training School</h3>
                    <button @click="showTrainingModal = false" class="text-slate-400 hover:text-slate-600"><Lucide icon="X" class="w-5 h-5" /></button>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium mb-1">Driving School Name <span class="text-red-500">*</span></label>
                        <FormInput v-model="trainingForm.school_name" placeholder="School name" />
                        <p v-if="trainingForm.errors.school_name" class="text-red-500 text-xs mt-1">{{ trainingForm.errors.school_name }}</p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1">City</label>
                        <FormInput v-model="trainingForm.city" placeholder="City" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1">State</label>
                        <FormInput v-model="trainingForm.state" placeholder="TX" maxlength="5" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1">Start Date</label>
                        <Litepicker v-model="trainingForm.date_start" :options="lpOptions" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1">End Date</label>
                        <Litepicker v-model="trainingForm.date_end" :options="lpOptions" />
                    </div>
                    <div class="md:col-span-2 flex flex-wrap gap-4">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input v-model="trainingForm.graduated" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-primary" />
                            <span class="text-sm">Graduated?</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input v-model="trainingForm.subject_to_safety_regulations" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-primary" />
                            <span class="text-sm">Subject to Safety Regulations?</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input v-model="trainingForm.performed_safety_functions" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-primary" />
                            <span class="text-sm">Performed Safety Functions?</span>
                        </label>
                    </div>
                    <!-- Skills -->
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium mb-2">Skills Learned</label>
                        <div class="grid grid-cols-2 gap-2">
                            <label v-for="skill in availableTrainingSkills" :key="skill.value"
                                class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox"
                                    :value="skill.value"
                                    v-model="trainingForm.training_skills"
                                    class="h-4 w-4 rounded border-slate-300 text-primary" />
                                <span class="text-sm">{{ skill.label }}</span>
                            </label>
                        </div>
                    </div>
                    <!-- Certificates -->
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium mb-2">Certificates <span class="text-slate-400 font-normal">(JPG, PNG, PDF, DOC — max 10 MB each)</span></label>
                        <input type="file" multiple accept=".jpg,.jpeg,.png,.pdf,.doc,.docx"
                            class="block w-full text-sm text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-primary file:text-white hover:file:bg-primary/90 cursor-pointer"
                            @change="(e) => { const f = (e.target as HTMLInputElement).files; if (f) trainingForm.certificates = Array.from(f) }" />
                    </div>
                </div>
                <div class="flex gap-2 justify-end mt-6 pt-4 border-t">
                    <button type="button" class="px-4 py-2 border border-slate-200 text-slate-700 rounded-lg text-sm hover:bg-slate-50" @click="showTrainingModal = false">Cancel</button>
                    <button type="button" :disabled="trainingForm.processing"
                        class="px-4 py-2 bg-primary text-white rounded-lg text-sm font-medium disabled:opacity-60 flex items-center gap-2"
                        @click="submitTraining">
                        <Lucide v-if="trainingForm.processing" icon="Loader" class="w-4 h-4 animate-spin" />
                        Save
                    </button>
                </div>
            </div>
        </Dialog.Panel>
    </Dialog>

    <!-- Add / Edit Course -->
    <Dialog :open="showCourseModal" @close="showCourseModal = false" size="lg" staticBackdrop>
        <Dialog.Panel class="max-h-[90vh] overflow-y-auto">
            <div class="p-6">
                <div class="flex items-center justify-between mb-5 pb-4 border-b sticky top-0 bg-white z-10">
                    <h3 class="text-lg font-semibold">{{ editingCourseId ? 'Edit' : 'Add' }} Course</h3>
                    <button @click="showCourseModal = false" class="text-slate-400 hover:text-slate-600"><Lucide icon="X" class="w-5 h-5" /></button>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium mb-1">Organization Name <span class="text-red-500">*</span></label>
                        <FormInput v-model="courseForm.organization_name" placeholder="Organization name" />
                        <p v-if="courseForm.errors.organization_name" class="text-red-500 text-xs mt-1">{{ courseForm.errors.organization_name }}</p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1">City</label>
                        <FormInput v-model="courseForm.city" placeholder="City" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1">State</label>
                        <FormInput v-model="courseForm.state" placeholder="TX" maxlength="5" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1">Certification Date</label>
                        <Litepicker v-model="courseForm.certification_date" :options="lpOptions" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1">Expiration Date</label>
                        <Litepicker v-model="courseForm.expiration_date" :options="lpOptions" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1">Course Status</label>
                        <FormSelect v-model="courseForm.status">
                            <option value="Active">Active</option>
                            <option value="Expired">Expired</option>
                            <option value="Pending">Pending</option>
                        </FormSelect>
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1">Years of Experience</label>
                        <FormInput v-model="courseForm.years_experience" type="number" min="0" step="0.5" placeholder="0" />
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium mb-1">Experience / Notes</label>
                        <FormTextarea v-model="courseForm.experience" :rows="3" placeholder="Additional details, skills, or notes..." />
                    </div>
                    <!-- Certificates -->
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium mb-2">Certificates <span class="text-slate-400 font-normal">(JPG, PNG, PDF, DOC — max 10 MB each)</span></label>
                        <input type="file" multiple accept=".jpg,.jpeg,.png,.pdf,.doc,.docx"
                            class="block w-full text-sm text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-primary file:text-white hover:file:bg-primary/90 cursor-pointer"
                            @change="(e) => { const f = (e.target as HTMLInputElement).files; if (f) courseForm.certificates = Array.from(f) }" />
                    </div>
                </div>
                <div class="flex gap-2 justify-end mt-6 pt-4 border-t">
                    <button type="button" class="px-4 py-2 border border-slate-200 text-slate-700 rounded-lg text-sm hover:bg-slate-50" @click="showCourseModal = false">Cancel</button>
                    <button type="button" :disabled="courseForm.processing"
                        class="px-4 py-2 bg-primary text-white rounded-lg text-sm font-medium disabled:opacity-60 flex items-center gap-2"
                        @click="submitCourse">
                        <Lucide v-if="courseForm.processing" icon="Loader" class="w-4 h-4 animate-spin" />
                        Save
                    </button>
                </div>
            </div>
        </Dialog.Panel>
    </Dialog>

    <!-- Add / Edit Traffic Conviction -->
    <Dialog :open="showTrafficModal" @close="showTrafficModal = false" size="lg" staticBackdrop>
        <Dialog.Panel>
            <div class="p-6">
                <div class="flex items-center justify-between mb-5 pb-4 border-b">
                    <h3 class="text-lg font-semibold">{{ editingTrafficId ? 'Edit' : 'Add' }} Traffic Conviction</h3>
                    <button @click="showTrafficModal = false" class="text-slate-400 hover:text-slate-600"><Lucide icon="X" class="w-5 h-5" /></button>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div><label class="block text-xs font-medium mb-1">Conviction Date</label><Litepicker v-model="trafficForm.conviction_date" :options="lpOptions" /></div>
                    <div><label class="block text-xs font-medium mb-1">Location</label><FormInput v-model="trafficForm.location" placeholder="City, State" /></div>
                    <div>
                        <label class="block text-xs font-medium mb-1">Charge <span class="text-red-500">*</span></label>
                        <FormInput v-model="trafficForm.charge" placeholder="e.g. Speeding" />
                        <p v-if="trafficForm.errors.charge" class="text-red-500 text-xs mt-1">{{ trafficForm.errors.charge }}</p>
                    </div>
                    <div><label class="block text-xs font-medium mb-1">Penalty</label><FormInput v-model="trafficForm.penalty" placeholder="e.g. Fine $150" /></div>
                    <div><label class="block text-xs font-medium mb-1">Conviction Type</label><FormInput v-model="trafficForm.conviction_type" placeholder="e.g. Moving violation" /></div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium mb-1">Description</label>
                        <textarea v-model="trafficForm.description" rows="3" placeholder="Additional details..."
                            class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary/30 resize-none"></textarea>
                    </div>
                </div>
                <div class="flex gap-2 justify-end mt-6 pt-4 border-t">
                    <button type="button" class="px-4 py-2 border border-slate-200 text-slate-700 rounded-lg text-sm hover:bg-slate-50" @click="showTrafficModal = false">Cancel</button>
                    <button type="button" :disabled="trafficForm.processing"
                        class="px-4 py-2 bg-primary text-white rounded-lg text-sm font-medium disabled:opacity-60 flex items-center gap-2"
                        @click="submitTraffic">
                        <Lucide v-if="trafficForm.processing" icon="Loader" class="w-4 h-4 animate-spin" />
                        Save
                    </button>
                </div>
            </div>
        </Dialog.Panel>
    </Dialog>

    <!-- Add Accident -->
    <Dialog :open="showAccidentModal" @close="showAccidentModal = false" size="lg" staticBackdrop>
        <Dialog.Panel>
            <div class="p-6">
                <div class="flex items-center justify-between mb-5 pb-4 border-b">
                    <h3 class="text-lg font-semibold">{{ editingAccidentId ? 'Edit' : 'Add' }} Accident Record</h3>
                    <button @click="showAccidentModal = false" class="text-slate-400 hover:text-slate-600"><Lucide icon="X" class="w-5 h-5" /></button>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div><label class="block text-xs font-medium mb-1">Accident Date</label><Litepicker v-model="accidentForm.accident_date" :options="lpOptions" /></div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium mb-1">Nature of Accident <span class="text-red-500">*</span></label>
                        <FormInput v-model="accidentForm.nature_of_accident" placeholder="Describe the accident" />
                        <p v-if="accidentForm.errors.nature_of_accident" class="text-red-500 text-xs mt-1">{{ accidentForm.errors.nature_of_accident }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <input id="had_fatalities" v-model="accidentForm.had_fatalities" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-primary" />
                        <label for="had_fatalities" class="text-sm">Had Fatalities?</label>
                    </div>
                    <div v-if="accidentForm.had_fatalities">
                        <label class="block text-xs font-medium mb-1">Number of Fatalities</label>
                        <FormInput v-model="accidentForm.number_of_fatalities" type="number" min="0" />
                    </div>
                    <div class="flex items-center gap-2">
                        <input id="had_injuries" v-model="accidentForm.had_injuries" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-primary" />
                        <label for="had_injuries" class="text-sm">Had Injuries?</label>
                    </div>
                    <div v-if="accidentForm.had_injuries">
                        <label class="block text-xs font-medium mb-1">Number of Injuries</label>
                        <FormInput v-model="accidentForm.number_of_injuries" type="number" min="0" />
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium mb-1">Comments</label>
                        <textarea v-model="accidentForm.comments" rows="3" placeholder="Additional comments..."
                            class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary/30 resize-none"></textarea>
                    </div>
                </div>
                <div class="flex gap-2 justify-end mt-6 pt-4 border-t">
                    <button type="button" class="px-4 py-2 border border-slate-200 text-slate-700 rounded-lg text-sm hover:bg-slate-50" @click="showAccidentModal = false">Cancel</button>
                    <button type="button" :disabled="accidentForm.processing"
                        class="px-4 py-2 bg-primary text-white rounded-lg text-sm font-medium disabled:opacity-60 flex items-center gap-2"
                        @click="submitAccident">
                        <Lucide v-if="accidentForm.processing" icon="Loader" class="w-4 h-4 animate-spin" />
                        Save
                    </button>
                </div>
            </div>
        </Dialog.Panel>
    </Dialog>

    <!-- Approve Confirmation -->
    <Dialog :open="showApproveModal" @close="showApproveModal = false" size="sm">
        <Dialog.Panel>
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold text-base">Approve Application</h3>
                    <button @click="showApproveModal = false" class="text-slate-400 hover:text-slate-600"><Lucide icon="X" class="w-5 h-5" /></button>
                </div>
                <div class="flex items-start gap-3 p-4 bg-emerald-50 border border-emerald-200 rounded-lg mb-5">
                    <Lucide icon="CheckCircle" class="w-5 h-5 text-emerald-600 flex-shrink-0 mt-0.5" />
                    <div class="text-sm text-emerald-800">
                        <p class="font-medium mb-1">Confirm approval?</p>
                        <p>This will activate the driver account. The driver will be able to log in and access the system.</p>
                    </div>
                </div>
                <div class="flex gap-2 justify-end">
                    <button type="button" class="px-4 py-2 border border-slate-200 text-slate-700 rounded-lg text-sm hover:bg-slate-50" @click="showApproveModal = false">Cancel</button>
                    <button type="button" :disabled="approvingApp"
                        class="px-4 py-2 bg-emerald-600 text-white rounded-lg text-sm font-medium disabled:opacity-60 flex items-center gap-2"
                        @click="approveApplication">
                        <Lucide v-if="approvingApp" icon="Loader" class="w-4 h-4 animate-spin" />
                        {{ approvingApp ? 'Approving...' : 'Yes, Approve' }}
                    </button>
                </div>
            </div>
        </Dialog.Panel>
    </Dialog>

    <!-- Reject Modal -->
    <Dialog :open="showRejectModal" @close="showRejectModal = false" size="sm">
        <Dialog.Panel>
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold text-base">Reject Application</h3>
                    <button @click="showRejectModal = false" class="text-slate-400 hover:text-slate-600"><Lucide icon="X" class="w-5 h-5" /></button>
                </div>
                <div class="flex items-start gap-2 p-3 bg-amber-50 border border-amber-200 rounded-lg text-xs text-amber-800 mb-4">
                    <Lucide icon="AlertTriangle" class="w-4 h-4 flex-shrink-0 mt-0.5" />
                    This action will notify the driver about the rejection.
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Rejection Reason <span class="text-red-500">*</span></label>
                    <textarea v-model="rejectionReason" rows="4" placeholder="Explain the reason for rejection..."
                        class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-300 resize-none"></textarea>
                </div>
                <div class="flex gap-2 justify-end">
                    <button type="button" class="px-4 py-2 border border-slate-200 text-slate-700 rounded-lg text-sm hover:bg-slate-50" @click="showRejectModal = false">Cancel</button>
                    <button type="button" :disabled="rejectingApp || !rejectionReason.trim()"
                        class="px-4 py-2 bg-red-600 text-white rounded-lg text-sm font-medium disabled:opacity-60 flex items-center gap-2"
                        @click="rejectApplication">
                        <Lucide v-if="rejectingApp" icon="Loader" class="w-4 h-4 animate-spin" />
                        {{ rejectingApp ? 'Rejecting...' : 'Reject Application' }}
                    </button>
                </div>
            </div>
        </Dialog.Panel>
    </Dialog>

    <!-- Upload Document Modal -->
    <Dialog :open="showDocModal" @close="showDocModal = false" size="sm" staticBackdrop>
        <Dialog.Panel>
            <div class="p-6">
                <div class="flex items-center justify-between mb-5 pb-4 border-b">
                    <h3 class="text-base font-semibold">Upload {{ docModalLabel }}</h3>
                    <button @click="showDocModal = false" class="text-slate-400 hover:text-slate-600">
                        <Lucide icon="X" class="w-5 h-5" />
                    </button>
                </div>
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-medium mb-2">
                            File <span class="text-slate-400 font-normal">(PDF, JPG, PNG — max 10 MB)</span>
                        </label>
                        <input type="file" accept=".pdf,.jpg,.jpeg,.png"
                            class="block w-full text-sm text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-primary file:text-white hover:file:bg-primary/90 cursor-pointer border border-slate-200 rounded-lg p-1"
                            @change="(e) => { const f = (e.target as HTMLInputElement).files; if (f) docUploadForm.file = f[0] }" />
                        <p v-if="docUploadForm.errors.file" class="text-red-500 text-xs mt-1">{{ docUploadForm.errors.file }}</p>
                    </div>
                </div>
                <div class="flex gap-2 justify-end mt-6 pt-4 border-t">
                    <button type="button" class="px-4 py-2 border border-slate-200 text-slate-700 rounded-lg text-sm hover:bg-slate-50"
                        @click="showDocModal = false">Cancel</button>
                    <button type="button" :disabled="!docUploadForm.file || docUploadForm.processing"
                        class="px-4 py-2 bg-primary text-white rounded-lg text-sm font-medium disabled:opacity-60 flex items-center gap-2"
                        @click="submitDocUpload">
                        <Lucide v-if="docUploadForm.processing" icon="Loader" class="w-4 h-4 animate-spin" />
                        Upload
                    </button>
                </div>
            </div>
        </Dialog.Panel>
    </Dialog>
</template>
