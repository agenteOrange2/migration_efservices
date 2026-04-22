<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, computed, reactive } from 'vue'
import Lucide from '@/components/Base/Lucide'
import Button from '@/components/Base/Button'
import { FormInput } from '@/components/Base/Form'
import Litepicker from '@/components/Base/Litepicker/Litepicker.vue'
import RazeLayout from '@/layouts/RazeLayout.vue'

declare function route(name: string, params?: any): string

defineOptions({ layout: RazeLayout })

// ─── Types ────────────────────────────────────────────────────────────────────
interface Carrier { id: number; name: string; address: string | null; state: string | null; dot_number: string | null; mc_number: string | null }
interface Address { address_line1: string; address_line2?: string; city: string; state: string; zip_code: string; primary: boolean; from_date?: string; to_date?: string }
interface License { id: number; license_number: string; state_of_issue: string | null; license_class: string | null; expiration_date: string | null; is_cdl: boolean; status: string | null; license_front_url: string | null; license_back_url: string | null; endorsements: string[] }
interface Experience { equipment_type: string | null; years_experience: number | null; miles_driven: number | null; requires_cdl: boolean }
interface MedicalRecord { name: string; url: string; size: string }
interface Medical { medical_card_expiration_date: string | null; medical_examiner_name: string | null; medical_examiner_registry: string | null; medical_status: string; ssn_masked: string | null; medical_card_url: string | null; social_security_card_url: string | null; medical_certificate_url: string | null; medical_records: MedicalRecord[] }
interface Employment { company_name: string | null; company_address: string | null; company_phone: string | null; positions_held: string | null; from_date: string | null; to_date: string | null; subject_to_fmcsr: boolean; safety_sensitive_function: boolean; reason_for_leaving: string | null; email: string | null; email_sent: boolean; verification_status: string | null; verification_date: string | null; explanation: string | null }
interface RelatedEmployment { position: string | null; comments: string | null; from_date: string | null; to_date: string | null }
interface UnemploymentPeriod { from_date: string | null; to_date: string | null; comments: string | null }
interface TrainingSchool { name: string | null; city: string | null; state: string | null; from_date: string | null; to_date: string | null; graduated: boolean; subject_fmcsr: boolean; certificate_url: string | null }
interface Course { organization_name: string | null; location: string | null; status: string | null; certification_date: string | null; expiration_date: string | null; certificate_url: string | null }
interface Testing { id: number; test_date: string | null; test_type: string | null; test_result: string | null; status: string | null; administered_by: string | null; location: string | null; next_test_due: string | null; notes: string | null; is_random_test: boolean; is_post_accident_test: boolean; is_pre_employment_test: boolean; drug_test_pdf_url: string | null; test_results_url: string | null }
interface Inspection { inspection_date: string | null; inspection_type: string | null; inspection_level: string | null; inspector_name: string | null; location: string | null; status: string | null; defects_found: string | null; is_defects_corrected: boolean; corrective_actions: string | null; notes: string | null; vehicle: string | null }
interface VehicleAssignment { driver_type: string | null; start_date: string | null; end_date: string | null; status: string | null; notes: string | null; vehicle: { make: string | null; model: string | null; year: string | null; vin: string | null } | null }
interface Accident { accident_date: string | null; nature_of_accident: string | null; had_fatalities: boolean; had_injuries: boolean; number_of_fatalities: number; number_of_injuries: number; comments: string | null }
interface TrafficConviction { conviction_date: string | null; location: string | null; charge: string | null; penalty: string | null; conviction_type: string | null; description: string | null }
interface FmcsrData { is_disqualified: boolean; disqualified_details: string | null; is_license_suspended: boolean; suspension_details: string | null; is_license_denied: boolean; denial_details: string | null; has_positive_drug_test: boolean; has_duty_offenses: boolean; offense_details: string | null; consent_driving_record: boolean }
interface CriminalHistory { has_criminal_charges: boolean; has_felony_conviction: boolean; has_minister_permit: boolean; fcra_consent: boolean }
interface HosData { cycle_type: string; change_requested: boolean; change_requested_to: string | null; change_requested_at: string | null; change_approved_at: string | null }
interface DriverTrip { id: number; trip_number: string | null; status: string; origin_address: string | null; destination_address: string | null; scheduled_start_date: string | null; scheduled_end_date: string | null; estimated_duration_minutes: number | null; vehicle: string | null }
interface HosDocument { id: number; type_label: string; file_name: string; size_label: string; document_date: string | null; created_at: string | null; preview_url: string; download_url: string }
interface WizardStep { step: number; label: string; status: string; percentage: number }
interface MigrationHistory { id: number; migrated_at: string; migrated_at_raw: string; source_carrier: string; target_carrier: string; performed_by: string; reason: string | null; notes: string | null; status: string; can_rollback: boolean; rolled_back_at: string | null; rollback_reason: string | null }
interface AvailableCarrier { id: number; name: string; dot_number: string | null; mc_number: string | null; state: string | null; address: string | null; current_drivers: number; max_drivers: number }

interface Driver {
    id: number; full_name: string; name: string; middle_name: string | null; last_name: string | null
    email: string; phone: string | null; date_of_birth: string | null; effective_status: string; status: number
    profile_photo_url: string | null; created_at: string; hire_date: string | null; termination_date: string | null
    notes: string | null; completion_percentage: number; application_status: string | null
    emergency_contact_name: string | null; emergency_contact_phone: string | null; emergency_contact_relationship: string | null
    carrier: Carrier | null
    addresses: Address[]; licenses: License[]; experiences: Experience[]; medical: Medical | null
    employment: Employment[]; related_employments: RelatedEmployment[]; unemployment_periods: UnemploymentPeriod[]
    training_schools: TrainingSchool[]; courses: Course[]; testings: Testing[]; inspections: Inspection[]
    vehicle_assignments: VehicleAssignment[]; accidents: Accident[]; traffic_convictions: TrafficConviction[]
    fmcsr_data: FmcsrData | null
    criminal_history: CriminalHistory | null
    hos_data: HosData
    trips: DriverTrip[]
    wizard_steps: WizardStep[]
    wizard_total_pct: number
    migration_history: MigrationHistory[]
}

interface Stats { total_documents: number; licenses_count: number; medical_status: string; medical_expiration: string | null; records_uploaded: number; testing_count: number; testing_status: string; vehicles_count: number; vehicles_status: string }
interface DocItem { name: string; url: string; size: string; date: string; related_info: string; type?: string; company?: string }

interface DriverShowRouteNames {
    index: string
    edit?: string
    documentsDownload?: string
    activate?: string
    deactivate?: string
    migrationWizard?: string
    hosGenerateDailyLog?: string
    hosGenerateMonthlySummary?: string
    hosGenerateFmcsaMonthly?: string
    hosDestroy?: string
}

const props = withDefaults(defineProps<{
    driver: Driver
    documentsByCategory: Record<string, DocItem[]>
    stats: Stats
    hosDocuments: HosDocument[]
    routeNames?: DriverShowRouteNames
    isCarrierContext?: boolean
}>(), {
    routeNames: () => ({
        index: 'admin.drivers.index',
        documentsDownload: 'admin.drivers.documents.download',
        activate: 'admin.drivers.activate',
        deactivate: 'admin.drivers.deactivate',
        migrationWizard: 'admin.drivers.migration.wizard',
        hosGenerateDailyLog: 'admin.hos.documents.generate-daily-log',
        hosGenerateMonthlySummary: 'admin.hos.documents.generate-monthly-summary',
        hosGenerateFmcsaMonthly: 'admin.hos.documents.generate-fmcsa-monthly',
        hosDestroy: 'admin.hos.documents.destroy',
    }),
    isCarrierContext: false,
})

const routeNames = computed(() => props.routeNames)
const isCarrierContext = computed(() => props.isCarrierContext)

function namedRoute(name: keyof DriverShowRouteNames, params?: any) {
    const routeName = props.routeNames[name]

    return routeName ? route(routeName, params) : '#'
}

// ─── State ────────────────────────────────────────────────────────────────────
const activeTab = ref('general')
const inspectionSection = ref<'inspections'|'accidents'|'violations'|'vehicles'>('inspections')

const tabs = [
    { id: 'general',     label: 'General',     icon: 'User'          },
    { id: 'licenses',    label: 'Licenses',    icon: 'CreditCard'    },
    { id: 'medical',     label: 'Medical',     icon: 'Heart'         },
    { id: 'employment',  label: 'Employment',  icon: 'Briefcase'     },
    { id: 'training',    label: 'Training',    icon: 'GraduationCap' },
    { id: 'testing',     label: 'Testing',     icon: 'ClipboardCheck'},
    { id: 'inspections', label: 'Inspections', icon: 'Shield'        },
    { id: 'compliance',  label: 'Compliance',  icon: 'ClipboardList' },
    { id: 'hos',         label: 'HOS',         icon: 'Clock'         },
    { id: 'wizard',      label: 'Wizard Steps',icon: 'ListChecks'    },
    { id: 'documents',   label: 'Documents',   icon: 'FileText'      },
]

// ─── Helpers ─────────────────────────────────────────────────────────────────
const asIcon = (s: string): any => s

function formatDate(d: string | null | undefined) {
    if (!d) return 'N/A'
    return new Date(d).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })
}

function formatNumber(n: number | null | undefined) {
    if (n == null) return 'N/A'
    return n.toLocaleString()
}

const statusBadge = (status: string) => {
    const map: Record<string, string> = {
        active: 'bg-success/10 text-success', inactive: 'bg-danger/10 text-danger',
        draft: 'bg-slate-100 text-slate-600', pending_review: 'bg-warning/10 text-warning',
        approved: 'bg-primary/10 text-primary', rejected: 'bg-danger/10 text-danger',
    }
    return map[status] ?? 'bg-slate-100 text-slate-600'
}

const testResultBadge = (result: string | null) => {
    if (!result) return 'bg-slate-100 text-slate-600'
    const r = result.toLowerCase()
    if (r === 'negative') return 'bg-success/10 text-success'
    if (r === 'positive' || r === 'refusal') return 'bg-danger/10 text-danger'
    return 'bg-slate-100 text-slate-600'
}

const medicalBadge = (s: string) => ({ valid: 'bg-success/10 text-success', expiring_soon: 'bg-warning/10 text-warning', expired: 'bg-danger/10 text-danger' }[s] ?? 'bg-slate-100 text-slate-600')
const medicalLabel = (s: string) => ({ valid: 'Valid', expiring_soon: 'Expiring Soon', expired: 'Expired' }[s] ?? 'Unknown')

const inspLevelBadge = (level: string | null) => {
    const map: Record<string, string> = { 'I': 'bg-danger/10 text-danger', 'II': 'bg-warning/10 text-warning', 'III': 'bg-primary/10 text-primary' }
    return map[level ?? ''] ?? 'bg-slate-100 text-slate-600'
}

const yesNo = (v: boolean | null | undefined) => v ? 'Yes' : 'No'
const categoryLabel = (key: string): string => ({
    license:                          'Licenses',
    medical:                          'Medical',
    training_schools:                 'Training Schools',
    courses:                          'Courses & Certifications',
    accidents:                        'Accidents',
    traffic:                          'Traffic Convictions',
    inspections:                      'Inspections',
    testing:                          'Testing',
    records:                          'Records (MVR / Criminal / Clearing House)',
    vehicle_verifications:            'Vehicle Verifications',
    violation_reports:                'Violation Reports',
    application_forms:                'Application Forms',
    employment_verification:          'Employment Verification',
    employment_verification_attempts: 'Employment Verification Attempts',
    w9_documents:                     'W-9 Tax Form',
    dot_policy_documents:             'DOT Drug & Alcohol Policy',
    certification:                    'Certification',
    other:                            'Other Documents',
}[key] ?? key.replace(/_/g, ' '))
const hosLabel: Record<string, string> = { '60_7': '60 hrs / 7 days', '70_8': '70 hrs / 8 days' }

// ─── HOS Document generation forms ───────────────────────────────────────────
const activeHosPanel = ref<'daily' | 'monthly' | 'fmcsa' | null>(null)
const dailyLogForm   = reactive({ date: '' })
const monthlyForm    = reactive({ year: String(new Date().getFullYear()), month: String(new Date().getMonth() + 1) })
const fmcsaForm      = reactive({ year: String(new Date().getFullYear()), month: String(new Date().getMonth() + 1) })
const generatingHos  = ref<string | null>(null)

const pickerOptionsSingle = { autoApply: true, singleMode: true, numberOfColumns: 1, numberOfMonths: 1, format: 'M/D/YYYY' }

function generateDailyLog() {
    generatingHos.value = 'daily'
    router.post(route(props.routeNames?.hosGenerateDailyLog ?? 'admin.hos.documents.generate-daily-log'), { driver_id: props.driver.id, date: dailyLogForm.date }, {
        preserveScroll: true, onFinish: () => { generatingHos.value = null },
    })
}

function generateMonthlySummary() {
    generatingHos.value = 'monthly'
    router.post(route(props.routeNames?.hosGenerateMonthlySummary ?? 'admin.hos.documents.generate-monthly-summary'), { driver_id: props.driver.id, year: monthlyForm.year, month: monthlyForm.month }, {
        preserveScroll: true, onFinish: () => { generatingHos.value = null },
    })
}

function generateFmcsaMonthly() {
    generatingHos.value = 'fmcsa'
    router.post(route(props.routeNames?.hosGenerateFmcsaMonthly ?? 'admin.hos.documents.generate-fmcsa-monthly'), { driver_id: props.driver.id, year: fmcsaForm.year, month: fmcsaForm.month }, {
        preserveScroll: true, onFinish: () => { generatingHos.value = null },
    })
}

function deleteHosDocument(id: number) {
    if (!confirm('Delete this HOS document?')) return
    router.delete(route(props.routeNames?.hosDestroy ?? 'admin.hos.documents.destroy', id), { preserveScroll: true })
}

const tripStatusBadge = (status: string) => {
    const map: Record<string, string> = {
        pending:     'bg-warning/10 text-warning',
        accepted:    'bg-primary/10 text-primary',
        in_progress: 'bg-blue-100 text-blue-700',
        paused:      'bg-amber-100 text-amber-700',
        completed:   'bg-success/10 text-success',
        cancelled:   'bg-danger/10 text-danger',
    }
    return map[status] ?? 'bg-slate-100 text-slate-600'
}
const tripStatusLabel = (status: string) => ({
    pending: 'Pending', accepted: 'Accepted', in_progress: 'In Progress',
    paused: 'Paused', completed: 'Completed', cancelled: 'Cancelled',
}[status] ?? status)
const stepStatusClass: Record<string, string> = {
    completed: 'bg-success/10 text-success border-success/20',
    missing:   'bg-danger/10 text-danger border-danger/20',
    pending:   'bg-warning/10 text-warning border-warning/20',
}

const testTypeName: Record<string, string> = {
    dot_drug_test: 'DOT Drug Test (MRO)', non_dot_lab: 'NON-DOT Lab (MRO)',
    dot_alcohol_test: 'DOT Alcohol Test', non_dot_alcohol_test: 'NON-DOT Alcohol Test',
    panel_instant_test: '10 Panel Instant Test', dot_drug_alcohol_test: 'DOT Drug & Alcohol Test',
}

// ─── Computed ─────────────────────────────────────────────────────────────────
const drugTests = computed(() =>
    props.driver.testings.filter(t => t.test_type?.includes('drug')).sort((a, b) => (b.test_date ?? '').localeCompare(a.test_date ?? ''))
)
const alcoholTests = computed(() =>
    props.driver.testings.filter(t => t.test_type?.includes('alcohol')).sort((a, b) => (b.test_date ?? '').localeCompare(a.test_date ?? ''))
)
const isActive = computed(() => props.driver.effective_status === 'active')
const medicalStatTone = computed(() => {
    const value = String(props.stats.medical_status || '').toLowerCase()
    if (value === 'valid') return 'text-success'
    if (value.includes('expiring')) return 'text-warning'
    return 'text-danger'
})
const testingStatTone = computed(() => {
    const value = String(props.stats.testing_status || '').toLowerCase()
    if (value.includes('negative') || value.includes('compliant') || value.includes('passed')) return 'text-success'
    if (value.includes('pending') || value.includes('review')) return 'text-warning'
    if (value.includes('positive') || value.includes('refusal')) return 'text-danger'
    return 'text-primary'
})
const vehiclesStatTone = computed(() => {
    const value = String(props.stats.vehicles_status || '').toLowerCase()
    if (value.includes('active') || value.includes('assigned')) return 'text-success'
    if (value.includes('pending') || value.includes('review')) return 'text-warning'
    if (value.includes('inactive') || value.includes('unassigned')) return 'text-danger'
    return 'text-primary'
})

// ─── Actions ─────────────────────────────────────────────────────────────────
function activateDriver() {
    if (props.routeNames.activate && confirm(`Activate driver "${props.driver.full_name}"?`))
        router.put(route(props.routeNames.activate, props.driver.id), {}, { preserveScroll: true })
}
function deactivateDriver() {
    if (props.routeNames.deactivate && confirm(`Deactivate driver "${props.driver.full_name}"?`))
        router.put(route(props.routeNames.deactivate, props.driver.id), {}, { preserveScroll: true })
}

// ─── Migration Wizard ─────────────────────────────────────────────────────────
const showMigrationModal  = ref(false)
const migrationStep       = ref<1|2|3|4>(1)
const migrationCarriers   = ref<AvailableCarrier[]>([])
const migrationLoading    = ref(false)
const migrationSearch     = ref('')
const selectedCarrier     = ref<AvailableCarrier | null>(null)
const migrationValidation = ref<{ is_valid: boolean; errors: string[]; warnings: string[] } | null>(null)
const migrationReason     = ref('')
const migrationNotes      = ref('')
const migrationDone       = ref<{ record_id: number; target_carrier: string } | null>(null)
const migrationError      = ref<string | null>(null)
const rollbackModal       = ref(false)
const rollbackRecordId    = ref<number | null>(null)
const rollbackReason      = ref('')

const filteredCarriers = computed(() =>
    migrationSearch.value.trim()
        ? migrationCarriers.value.filter(c =>
            c.name.toLowerCase().includes(migrationSearch.value.toLowerCase()) ||
            (c.dot_number ?? '').includes(migrationSearch.value))
        : migrationCarriers.value
)

async function openMigrationModal() {
    migrationStep.value       = 1
    selectedCarrier.value     = null
    migrationValidation.value = null
    migrationReason.value     = ''
    migrationNotes.value      = ''
    migrationDone.value       = null
    migrationError.value      = null
    migrationSearch.value     = ''
    migrationCarriers.value   = []
    migrationLoading.value    = true
    showMigrationModal.value  = true
    try {
        const res = await fetch(route('admin.drivers.migration.carriers', { driver: props.driver.id }), {
            headers: { 'Accept': 'application/json' },
        })
        const data = await res.json()
        migrationCarriers.value = Array.isArray(data) ? data : []
    } catch { migrationCarriers.value = [] }
    migrationLoading.value = false
}

function getCsrfToken(): string {
    const meta = document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null
    return meta?.content ?? ''
}

async function postJson(url: string, body: object): Promise<{ ok: boolean; status: number; data: any }> {
    const res = await fetch(url, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': getCsrfToken(),
        },
        body: JSON.stringify(body),
    })
    let data: any = null
    try { data = await res.json() } catch { data = null }
    return { ok: res.ok, status: res.status, data }
}

async function selectCarrierAndValidate(carrier: AvailableCarrier) {
    selectedCarrier.value     = carrier
    migrationValidation.value = null
    migrationError.value      = null
    migrationLoading.value    = true
    migrationStep.value       = 2
    try {
        const { ok, status, data } = await postJson(
            route('admin.drivers.migration.validate', { driver: props.driver.id }),
            { carrier_id: carrier.id }
        )
        if (!ok || !data) {
            migrationError.value = data?.message ?? `Server error (${status}). Check that you are logged in and try again.`
        } else {
            migrationValidation.value = {
                is_valid: data.is_valid ?? false,
                errors:   Array.isArray(data.errors)   ? data.errors   : [],
                warnings: Array.isArray(data.warnings) ? data.warnings : [],
            }
        }
    } catch (e: any) {
        migrationError.value = e?.message ?? 'Network error. Please try again.'
    }
    migrationLoading.value = false
}

function goToConfirm() { migrationStep.value = 3 }

function submitMigration() {
    migrationLoading.value = true
    router.post(
        route('admin.drivers.migration.execute', props.driver.id),
        { carrier_id: selectedCarrier.value!.id, reason: migrationReason.value, notes: migrationNotes.value },
        {
            onSuccess: () => {
                migrationDone.value = { record_id: 0, target_carrier: selectedCarrier.value!.name }
                migrationStep.value = 4
                migrationLoading.value = false
            },
            onError: (errors) => {
                migrationError.value = errors.migration ?? 'Migration failed.'
                migrationLoading.value = false
            },
        }
    )
}

function openRollbackModal(recordId: number) {
    rollbackRecordId.value = recordId
    rollbackReason.value   = ''
    rollbackModal.value    = true
}

function submitRollback() {
    if (!rollbackReason.value.trim() || !rollbackRecordId.value) return
    router.post(
        route('admin.migration-records.rollback', rollbackRecordId.value),
        { rollback_reason: rollbackReason.value },
        { onFinish: () => { rollbackModal.value = false } }
    )
}
</script>

<template>
    <Head :title="`Driver: ${driver.full_name}`" />

    <div class="grid grid-cols-12 gap-y-8 gap-x-6">

        <!-- ═══════════════════════════════════════════════════════════ HEADER -->
        <div class="col-span-12">
            <div class="box box--stacked p-6 md:p-8">
                <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
                    <!-- Photo + Info -->
                    <div class="flex items-start gap-5">
                        <div class="w-20 h-20 rounded-full bg-slate-100 border-2 border-white shadow flex items-center justify-center overflow-hidden flex-shrink-0">
                            <img v-if="driver.profile_photo_url" :src="driver.profile_photo_url" :alt="driver.full_name" class="w-full h-full object-cover" />
                            <Lucide v-else icon="User" class="w-10 h-10 text-slate-400" />
                        </div>
                        <div>
                            <h1 class="text-xl md:text-2xl font-bold text-slate-800">{{ driver.full_name }}</h1>
                            <p class="text-sm text-slate-500 flex items-center gap-1.5 mt-0.5">
                                <Lucide icon="Mail" class="w-3.5 h-3.5" /> {{ driver.email }}
                            </p>
                            <div class="flex flex-wrap items-center gap-3 mt-2">
                                <span v-if="driver.phone" class="text-sm text-slate-500 flex items-center gap-1">
                                    <Lucide icon="Phone" class="w-3.5 h-3.5" /> {{ driver.phone }}
                                </span>
                                <span v-if="driver.carrier" class="text-sm text-slate-500 flex items-center gap-1">
                                    <Lucide icon="Building2" class="w-3.5 h-3.5" /> {{ driver.carrier.name }}
                                </span>
                                <span :class="[statusBadge(driver.effective_status), 'inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium capitalize']">
                                    {{ driver.effective_status.replace(/_/g, ' ') }}
                                </span>
                                <span class="text-xs text-slate-400">Joined {{ formatDate(driver.created_at) }}</span>
                            </div>
                            <!-- Progress -->
                            <div class="mt-3 flex items-center gap-2">
                                <div class="h-1.5 w-32 rounded-full bg-slate-100 overflow-hidden">
                                    <div class="h-full rounded-full bg-primary/70 transition-all" :style="{ width: Math.min(driver.completion_percentage, 100) + '%' }" />
                                </div>
                                <span class="text-xs text-slate-500">{{ driver.completion_percentage }}% profile complete</span>
                            </div>
                        </div>
                    </div>
                    <!-- Actions -->
                    <div class="flex flex-wrap gap-2">
                        <Link :href="namedRoute('index')">
                            <Button variant="outline-secondary" size="sm" class="flex items-center gap-1.5">
                                <Lucide icon="ArrowLeft" class="w-4 h-4" /> Back
                            </Button>
                        </Link>
                        <Link v-if="routeNames.edit" :href="namedRoute('edit', driver.id)">
                            <Button variant="outline-secondary" size="sm" class="flex items-center gap-1.5">
                                <Lucide icon="Pencil" class="w-4 h-4" /> Edit
                            </Button>
                        </Link>
                        <button v-if="routeNames.activate && !isActive" @click="activateDriver"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-success text-white text-sm font-medium hover:bg-success/90 transition">
                            <Lucide icon="UserCheck" class="w-4 h-4" /> Activate
                        </button>
                        <button v-else-if="routeNames.deactivate && isActive" @click="deactivateDriver"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-danger text-white text-sm font-medium hover:bg-danger/90 transition">
                            <Lucide icon="UserMinus" class="w-4 h-4" /> Deactivate
                        </button>
                        <a v-if="routeNames.documentsDownload && stats.total_documents > 0" :href="namedRoute('documentsDownload', driver.id)"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-primary text-white text-sm font-medium hover:bg-primary/90 transition">
                            <Lucide icon="Download" class="w-4 h-4" /> Download Docs
                        </a>
                        <Link v-if="routeNames.migrationWizard && isActive && !isCarrierContext" :href="namedRoute('migrationWizard', driver.id)"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-warning text-white text-sm font-medium hover:bg-warning/90 transition">
                            <Lucide icon="ArrowRightLeft" class="w-4 h-4" /> Migrate Carrier
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <!-- ══════════════════════════════════════════════════ STATS CARDS -->
        <div class="col-span-12">
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
                <div class="box box--stacked p-4 text-center border border-info/10 bg-info/[0.04]">
                    <p class="text-2xl font-bold text-info">{{ stats.total_documents }}</p>
                    <p class="text-xs text-slate-500 mt-1">Documents</p>
                </div>
                <div class="box box--stacked p-4 text-center border border-primary/10 bg-primary/[0.04]">
                    <p class="text-2xl font-bold text-primary">{{ stats.licenses_count }}</p>
                    <p class="text-xs text-slate-500 mt-1">Licenses</p>
                </div>
                <div class="box box--stacked p-4 text-center border" :class="medicalStatTone === 'text-success' ? 'border-success/10 bg-success/[0.04]' : medicalStatTone === 'text-warning' ? 'border-warning/10 bg-warning/[0.04]' : 'border-danger/10 bg-danger/[0.04]'">
                    <p class="text-2xl font-bold" :class="medicalStatTone">{{ stats.medical_status }}</p>
                    <p class="text-xs text-slate-500 mt-1">Medical</p>
                    <p v-if="stats.medical_expiration" class="text-xs text-slate-400">{{ formatDate(stats.medical_expiration) }}</p>
                </div>
                <div class="box box--stacked p-4 text-center border border-primary/10 bg-primary/[0.04]">
                    <p class="text-2xl font-bold text-primary">{{ stats.records_uploaded }}</p>
                    <p class="text-xs text-slate-500 mt-1">Records</p>
                </div>
                <div class="box box--stacked p-4 text-center border" :class="testingStatTone === 'text-success' ? 'border-success/10 bg-success/[0.04]' : testingStatTone === 'text-warning' ? 'border-warning/10 bg-warning/[0.04]' : testingStatTone === 'text-danger' ? 'border-danger/10 bg-danger/[0.04]' : 'border-primary/10 bg-primary/[0.04]'">
                    <p class="text-2xl font-bold" :class="testingStatTone">{{ stats.testing_count }}</p>
                    <p class="text-xs text-slate-500 mt-1">Tests</p>
                </div>
                <div class="box box--stacked p-4 text-center border" :class="vehiclesStatTone === 'text-success' ? 'border-success/10 bg-success/[0.04]' : vehiclesStatTone === 'text-warning' ? 'border-warning/10 bg-warning/[0.04]' : vehiclesStatTone === 'text-danger' ? 'border-danger/10 bg-danger/[0.04]' : 'border-primary/10 bg-primary/[0.04]'">
                    <p class="text-2xl font-bold" :class="vehiclesStatTone">{{ stats.vehicles_count }}</p>
                    <p class="text-xs text-slate-500 mt-1">Vehicles</p>
                </div>
            </div>
        </div>

        <!-- ══════════════════════════════════════════════════ MAIN INFO + CARRIER -->
        <div class="col-span-12 lg:col-span-7">
            <div class="box box--stacked p-6 h-full">
                <h2 class="text-base font-semibold text-slate-800 mb-4 flex items-center gap-2">
                    <Lucide icon="User" class="w-4 h-4 text-primary" /> Personal Information
                </h2>
                <div class="grid grid-cols-2 gap-3">
                    <div class="bg-slate-50/60 rounded-lg p-3">
                        <p class="text-xs text-slate-500 mb-0.5">Full Name</p>
                        <p class="text-sm font-medium text-slate-800">{{ driver.full_name }}</p>
                    </div>
                    <div class="bg-slate-50/60 rounded-lg p-3">
                        <p class="text-xs text-slate-500 mb-0.5">Date of Birth</p>
                        <p class="text-sm font-medium text-slate-800">{{ formatDate(driver.date_of_birth) }}</p>
                    </div>
                    <div class="bg-slate-50/60 rounded-lg p-3">
                        <p class="text-xs text-slate-500 mb-0.5">Email</p>
                        <p class="text-sm font-medium text-slate-800 truncate">{{ driver.email }}</p>
                    </div>
                    <div class="bg-slate-50/60 rounded-lg p-3">
                        <p class="text-xs text-slate-500 mb-0.5">Phone</p>
                        <p class="text-sm font-medium text-slate-800">{{ driver.phone || 'N/A' }}</p>
                    </div>
                    <div class="bg-slate-50/60 rounded-lg p-3">
                        <p class="text-xs text-slate-500 mb-0.5">Hire Date</p>
                        <p class="text-sm font-medium text-slate-800">{{ formatDate(driver.hire_date) }}</p>
                    </div>
                    <div class="bg-slate-50/60 rounded-lg p-3">
                        <p class="text-xs text-slate-500 mb-0.5">Application</p>
                        <span :class="[statusBadge(driver.application_status || 'none'), 'inline-flex px-2 py-0.5 rounded-full text-xs font-medium capitalize']">
                            {{ driver.application_status || 'N/A' }}
                        </span>
                    </div>
                </div>
                <!-- Emergency Contact -->
                <div v-if="driver.emergency_contact_name" class="mt-4 bg-warning/10 border border-warning/20 rounded-lg p-3">
                    <p class="text-xs font-semibold text-warning mb-2 flex items-center gap-1"><Lucide icon="Phone" class="w-3.5 h-3.5" /> Emergency Contact</p>
                    <div class="grid grid-cols-3 gap-2 text-sm">
                        <div><p class="text-xs text-slate-500">Name</p><p class="font-medium">{{ driver.emergency_contact_name }}</p></div>
                        <div><p class="text-xs text-slate-500">Phone</p><p class="font-medium">{{ driver.emergency_contact_phone || 'N/A' }}</p></div>
                        <div><p class="text-xs text-slate-500">Relationship</p><p class="font-medium capitalize">{{ driver.emergency_contact_relationship || 'N/A' }}</p></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12 lg:col-span-5">
            <div class="box box--stacked p-6 h-full">
                <h2 class="text-base font-semibold text-slate-800 mb-4 flex items-center gap-2">
                    <Lucide icon="Building2" class="w-4 h-4 text-primary" /> Carrier Information
                </h2>
                <div v-if="driver.carrier" class="space-y-2">
                    <div class="bg-slate-50/60 rounded-lg p-3">
                        <p class="text-xs text-slate-500 mb-0.5">Carrier</p>
                        <p class="text-sm font-semibold text-slate-800">{{ driver.carrier.name }}</p>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div class="bg-slate-50/60 rounded-lg p-3">
                            <p class="text-xs text-slate-500 mb-0.5">DOT Number</p>
                            <p class="text-sm font-mono font-medium">{{ driver.carrier.dot_number || 'N/A' }}</p>
                        </div>
                        <div class="bg-slate-50/60 rounded-lg p-3">
                            <p class="text-xs text-slate-500 mb-0.5">MC Number</p>
                            <p class="text-sm font-mono font-medium">{{ driver.carrier.mc_number || 'N/A' }}</p>
                        </div>
                    </div>
                    <div class="bg-slate-50/60 rounded-lg p-3">
                        <p class="text-xs text-slate-500 mb-0.5">Address</p>
                        <p class="text-sm text-slate-700">{{ driver.carrier.address || 'N/A' }}</p>
                    </div>
                </div>
                <p v-else class="text-slate-500 text-sm">No carrier assigned</p>
                <!-- Notes -->
                <div v-if="driver.notes" class="mt-4 bg-slate-50 rounded-lg p-3">
                    <p class="text-xs text-slate-500 mb-1">Admin Notes</p>
                    <p class="text-sm text-slate-700">{{ driver.notes }}</p>
                </div>
            </div>
        </div>

        <!-- ══════════════════════════════════════════════════ TABS -->
        <div class="col-span-12">
            <div class="box box--stacked overflow-hidden">
                <!-- Tab Nav -->
                <div class="border-b border-slate-200 bg-slate-50/50 overflow-x-auto">
                    <nav class="flex min-w-max px-4">
                        <button v-for="tab in tabs" :key="tab.id" type="button" @click="activeTab = tab.id"
                            class="relative flex items-center gap-2 px-4 py-3.5 text-sm font-medium border-b-2 transition whitespace-nowrap"
                            :class="activeTab === tab.id ? 'border-primary text-primary bg-white' : 'border-transparent text-slate-500 hover:text-slate-700'">
                            <Lucide :icon="asIcon(tab.icon)" class="w-4 h-4" />
                            {{ tab.label }}
                            <span v-if="tab.id === 'documents' && stats.total_documents > 0" class="ml-1 px-1.5 py-0.5 rounded-full bg-success/10 text-success text-xs">{{ stats.total_documents }}</span>
                        </button>
                    </nav>
                </div>

                <div class="p-6">

                    <!-- ─────────────────────────── TAB: GENERAL -->
                    <div v-show="activeTab === 'general'" class="space-y-6">
                        <div v-if="driver.addresses?.length">
                            <h3 class="text-sm font-semibold text-slate-700 mb-3 flex items-center gap-2"><Lucide icon="MapPin" class="w-4 h-4 text-primary" /> Addresses</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div v-for="(addr, i) in driver.addresses" :key="i" class="bg-slate-50 rounded-lg p-4 border border-slate-200">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-xs font-medium px-2 py-0.5 rounded-full" :class="addr.primary ? 'bg-primary/10 text-primary' : 'bg-slate-100 text-slate-500'">
                                            {{ addr.primary ? 'Current Address' : 'Previous Address' }}
                                        </span>
                                        <span v-if="addr.from_date || addr.to_date" class="text-xs text-slate-400">
                                            {{ formatDate(addr.from_date ?? null) }} – {{ addr.to_date ? formatDate(addr.to_date) : 'Present' }}
                                        </span>
                                    </div>
                                    <p class="text-sm text-slate-800">{{ addr.address_line1 }}</p>
                                    <p v-if="addr.address_line2" class="text-sm text-slate-700">{{ addr.address_line2 }}</p>
                                    <p class="text-sm text-slate-600">{{ addr.city }}, {{ addr.state }} {{ addr.zip_code }}</p>
                                </div>
                            </div>
                        </div>
                        <p v-else class="text-slate-500 text-sm">No address information on file</p>
                    </div>

                    <!-- ─────────────────────────── TAB: LICENSES -->
                    <div v-show="activeTab === 'licenses'" class="space-y-6">
                        <!-- Licenses -->
                        <div>
                            <h3 class="text-sm font-semibold text-slate-700 mb-3 flex items-center gap-2"><Lucide icon="CreditCard" class="w-4 h-4 text-primary" /> Driver Licenses</h3>
                            <div v-if="driver.licenses?.length" class="space-y-4">
                                <div v-for="lic in driver.licenses" :key="lic.id" class="bg-slate-50 rounded-lg p-5 border border-slate-200">
                                    <div class="flex items-start justify-between mb-3">
                                        <div>
                                            <p class="font-mono font-semibold text-slate-800 text-lg">{{ lic.license_number || 'N/A' }}</p>
                                            <p class="text-xs text-slate-500">{{ lic.state_of_issue || 'N/A' }} • Class {{ lic.license_class || 'N/A' }}</p>
                                        </div>
                                        <div class="flex gap-2">
                                            <span v-if="lic.is_cdl" class="px-2 py-0.5 rounded-full bg-primary/10 text-primary text-xs font-medium">CDL</span>
                                            <span :class="[statusBadge(lic.status || 'none'), 'px-2 py-0.5 rounded-full text-xs font-medium capitalize']">{{ lic.status || 'N/A' }}</span>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-3">
                                        <div><p class="text-xs text-slate-500">Expires</p><p class="text-sm font-medium">{{ formatDate(lic.expiration_date) }}</p></div>
                                    </div>
                                    <div v-if="lic.endorsements?.length" class="flex flex-wrap gap-1.5 mb-3">
                                        <span v-for="e in lic.endorsements" :key="e" class="px-2 py-0.5 rounded bg-primary/10 text-primary text-xs">{{ e }}</span>
                                    </div>
                                    <div class="flex gap-3">
                                        <a v-if="lic.license_front_url" :href="lic.license_front_url" target="_blank" class="inline-flex items-center gap-1.5 text-sm text-primary hover:underline">
                                            <Lucide icon="Eye" class="w-3.5 h-3.5" /> Front
                                        </a>
                                        <a v-if="lic.license_back_url" :href="lic.license_back_url" target="_blank" class="inline-flex items-center gap-1.5 text-sm text-primary hover:underline">
                                            <Lucide icon="Eye" class="w-3.5 h-3.5" /> Back
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <p v-else class="text-slate-500 text-sm">No licenses on file</p>
                        </div>

                        <!-- Driving Experience -->
                        <div v-if="driver.experiences?.length">
                            <h3 class="text-sm font-semibold text-slate-700 mb-3 flex items-center gap-2"><Lucide icon="Truck" class="w-4 h-4 text-primary" /> Driving Experience</h3>
                            <div class="overflow-x-auto rounded-lg border border-slate-200">
                                <table class="w-full text-sm">
                                    <thead class="bg-slate-50">
                                        <tr>
                                            <th class="text-left px-4 py-2.5 text-xs font-medium text-slate-500">Equipment Type</th>
                                            <th class="text-left px-4 py-2.5 text-xs font-medium text-slate-500">Years</th>
                                            <th class="text-left px-4 py-2.5 text-xs font-medium text-slate-500">Miles Driven</th>
                                            <th class="text-left px-4 py-2.5 text-xs font-medium text-slate-500">CDL Required</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(exp, i) in driver.experiences" :key="i" class="border-t border-slate-100">
                                            <td class="px-4 py-3 font-medium">{{ exp.equipment_type || 'N/A' }}</td>
                                            <td class="px-4 py-3">{{ exp.years_experience ?? 'N/A' }}</td>
                                            <td class="px-4 py-3">{{ exp.miles_driven ? formatNumber(exp.miles_driven) : 'N/A' }}</td>
                                            <td class="px-4 py-3">
                                                <span :class="exp.requires_cdl ? 'bg-primary/10 text-primary' : 'bg-slate-100 text-slate-600'" class="px-2 py-0.5 rounded-full text-xs">{{ exp.requires_cdl ? 'Yes' : 'No' }}</span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- ─────────────────────────── TAB: MEDICAL -->
                    <div v-show="activeTab === 'medical'" class="space-y-6">
                        <div v-if="driver.medical">

                            <!-- Medical Qualification Card -->
                            <div class="border border-slate-200 rounded-xl overflow-hidden">
                                <!-- Card Header -->
                                <div class="flex items-center gap-2 px-5 py-4 border-b border-slate-100 bg-slate-50">
                                    <div class="p-1.5 bg-primary/10 rounded-lg">
                                        <Lucide icon="Heart" class="w-4 h-4 text-primary" />
                                    </div>
                                    <span class="font-semibold text-slate-800">Medical Qualification</span>
                                </div>

                                <!-- Fields Grid -->
                                <div class="grid grid-cols-1 md:grid-cols-2 divide-y md:divide-y-0 md:divide-x divide-slate-100">
                                    <!-- Medical Examiner -->
                                    <div class="px-5 py-4">
                                        <p class="text-xs text-slate-400 mb-1">Medical Examiner</p>
                                        <p class="text-sm font-medium text-slate-800">{{ driver.medical.medical_examiner_name || '—' }}</p>
                                    </div>
                                    <!-- Registry Number -->
                                    <div class="px-5 py-4">
                                        <p class="text-xs text-slate-400 mb-1">Registry Number</p>
                                        <p class="text-sm font-medium text-slate-800">{{ driver.medical.medical_examiner_registry || '—' }}</p>
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 divide-y md:divide-y-0 md:divide-x divide-slate-100 border-t border-slate-100">
                                    <!-- Medical Card Expiration -->
                                    <div class="px-5 py-4">
                                        <p class="text-xs text-slate-400 mb-1">Medical Card Expiration</p>
                                        <div class="flex items-center gap-2">
                                            <p class="text-sm font-medium text-slate-800">{{ formatDate(driver.medical.medical_card_expiration_date) || '—' }}</p>
                                            <span v-if="driver.medical.medical_status !== 'valid' && driver.medical.medical_card_expiration_date"
                                                :class="medicalBadge(driver.medical.medical_status)"
                                                class="px-2 py-0.5 rounded-full text-xs font-medium">
                                                {{ medicalLabel(driver.medical.medical_status) }}
                                            </span>
                                        </div>
                                    </div>
                                    <!-- SSN (masked) -->
                                    <div class="px-5 py-4">
                                        <p class="text-xs text-slate-400 mb-1">Expiration Date</p>
                                        <p class="text-sm font-medium text-slate-800 font-mono">{{ driver.medical.ssn_masked || '—' }}</p>
                                    </div>
                                </div>

                                <!-- View Medical Card -->
                                <div class="border-t border-slate-100 px-5 py-4">
                                    <p class="text-sm font-semibold text-slate-700 mb-3">View Medical Card</p>
                                    <div class="flex flex-wrap gap-2">
                                        <a v-if="driver.medical.medical_card_url" :href="driver.medical.medical_card_url" target="_blank"
                                            class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-slate-200 text-sm text-slate-700 bg-white hover:bg-slate-50 transition">
                                            <Lucide icon="FileText" class="w-4 h-4 text-slate-500" /> View Certificate
                                        </a>
                                        <a v-if="driver.medical.medical_certificate_url" :href="driver.medical.medical_certificate_url" target="_blank"
                                            class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-slate-200 text-sm text-slate-700 bg-white hover:bg-slate-50 transition">
                                            <Lucide icon="Award" class="w-4 h-4 text-slate-500" /> Medical Certificate
                                        </a>
                                        <a v-if="driver.medical.social_security_card_url" :href="driver.medical.social_security_card_url" target="_blank"
                                            class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-slate-200 text-sm text-slate-700 bg-white hover:bg-slate-50 transition">
                                            <Lucide icon="CreditCard" class="w-4 h-4 text-slate-500" /> SSN Card
                                        </a>
                                        <span v-if="!driver.medical.medical_card_url && !driver.medical.medical_certificate_url && !driver.medical.social_security_card_url"
                                            class="text-sm text-slate-400 italic">No documents uploaded</span>
                                    </div>
                                </div>

                                <!-- View Medical Records -->
                                <div class="border-t border-slate-100 px-5 py-4">
                                    <p class="text-sm font-semibold text-slate-700 mb-3">View Medical Records</p>
                                    <div v-if="driver.medical.medical_records?.length" class="flex flex-wrap gap-2">
                                        <a v-for="rec in driver.medical.medical_records" :key="rec.url"
                                            :href="rec.url" target="_blank"
                                            class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-slate-200 text-sm text-slate-700 bg-white hover:bg-slate-50 transition">
                                            <Lucide icon="FileText" class="w-4 h-4 text-slate-500" /> {{ rec.name }}
                                        </a>
                                    </div>
                                    <span v-else class="text-sm text-slate-400 italic">No medical records uploaded</span>
                                </div>
                            </div>

                        </div>
                        <div v-else class="flex flex-col items-center py-12 text-slate-400">
                            <Lucide icon="Heart" class="w-12 h-12 mb-3" />
                            <p>No medical qualification on file</p>
                        </div>
                    </div>

                    <!-- ─────────────────────────── TAB: EMPLOYMENT -->
                    <div v-show="activeTab === 'employment'" class="space-y-8">
                        <!-- Employment History -->
                        <div>
                            <h3 class="text-sm font-semibold text-slate-700 mb-3 flex items-center gap-2"><Lucide icon="Briefcase" class="w-4 h-4 text-primary" /> Employment History</h3>
                            <div v-if="driver.employment?.length" class="space-y-4">
                                <div v-for="(emp, i) in driver.employment" :key="i" class="bg-slate-50 rounded-lg p-5 border border-slate-200">
                                    <div class="flex items-start justify-between gap-4 mb-3">
                                        <div>
                                            <p class="font-semibold text-slate-800">{{ emp.company_name || 'Unknown Company' }}</p>
                                            <p class="text-sm text-slate-600">{{ emp.positions_held || 'N/A' }}</p>
                                            <p class="text-xs text-slate-500 mt-0.5">{{ formatDate(emp.from_date) }} – {{ emp.to_date ? formatDate(emp.to_date) : 'Present' }}</p>
                                        </div>
                                        <div class="flex flex-col items-end gap-2 flex-shrink-0">
                                            <span :class="emp.email_sent ? 'bg-success/10 text-success' : 'bg-slate-100 text-slate-500'" class="px-2 py-0.5 rounded-full text-xs font-medium">
                                                {{ emp.email_sent ? 'Email Sent' : 'Email Pending' }}
                                            </span>
                                            <span v-if="emp.verification_status" :class="emp.verification_status === 'verified' ? 'bg-primary/10 text-primary' : 'bg-warning/10 text-warning'" class="px-2 py-0.5 rounded-full text-xs font-medium capitalize">
                                                {{ emp.verification_status }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm mb-3">
                                        <div v-if="emp.company_address"><p class="text-xs text-slate-500">Address</p><p>{{ emp.company_address }}</p></div>
                                        <div v-if="emp.company_phone"><p class="text-xs text-slate-500">Phone</p><p>{{ emp.company_phone }}</p></div>
                                        <div v-if="emp.email"><p class="text-xs text-slate-500">Email</p><p class="truncate">{{ emp.email }}</p></div>
                                        <div v-if="emp.reason_for_leaving"><p class="text-xs text-slate-500">Reason Left</p><p class="capitalize">{{ emp.reason_for_leaving }}</p></div>
                                    </div>
                                    <div class="flex flex-wrap gap-2">
                                        <span :class="emp.subject_to_fmcsr ? 'bg-warning/10 text-warning' : 'bg-slate-100 text-slate-500'" class="flex items-center gap-1 px-2 py-0.5 rounded-full text-xs">
                                            <Lucide icon="AlertCircle" class="w-3 h-3" /> FMCSR: {{ emp.subject_to_fmcsr ? 'Yes' : 'No' }}
                                        </span>
                                        <span :class="emp.safety_sensitive_function ? 'bg-warning/10 text-warning' : 'bg-slate-100 text-slate-500'" class="flex items-center gap-1 px-2 py-0.5 rounded-full text-xs">
                                            <Lucide icon="Shield" class="w-3 h-3" /> Safety-Sensitive: {{ emp.safety_sensitive_function ? 'Yes' : 'No' }}
                                        </span>
                                    </div>
                                    <p v-if="emp.explanation" class="text-xs text-slate-600 mt-2 italic">{{ emp.explanation }}</p>
                                </div>
                            </div>
                            <p v-else class="text-slate-500 text-sm">No employment history on file</p>
                        </div>

                        <!-- Unemployment Periods -->
                        <div v-if="driver.unemployment_periods?.length">
                            <h3 class="text-sm font-semibold text-slate-700 mb-3 flex items-center gap-2"><Lucide icon="Clock" class="w-4 h-4 text-warning" /> Unemployment Periods</h3>
                            <div class="space-y-2">
                                <div v-for="(up, i) in driver.unemployment_periods" :key="i" class="flex items-center justify-between bg-warning/10 rounded-lg p-4 border border-warning/20">
                                    <div>
                                        <p class="text-sm font-medium">{{ formatDate(up.from_date) }} – {{ up.to_date ? formatDate(up.to_date) : 'Present' }}</p>
                                        <p v-if="up.comments" class="text-xs text-slate-600 mt-0.5">{{ up.comments }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Related Employment -->
                        <div v-if="driver.related_employments?.length">
                            <h3 class="text-sm font-semibold text-slate-700 mb-3 flex items-center gap-2"><Lucide icon="Link" class="w-4 h-4 text-primary" /> Related Employment</h3>
                            <div class="space-y-2">
                                <div v-for="(re, i) in driver.related_employments" :key="i" class="bg-slate-50 rounded-lg p-4 border border-slate-200 text-sm">
                                    <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                                        <div v-if="re.position"><p class="text-xs text-slate-500">Position</p><p>{{ re.position }}</p></div>
                                        <div v-if="re.from_date || re.to_date"><p class="text-xs text-slate-500">Period</p><p>{{ formatDate(re.from_date) }} – {{ re.to_date ? formatDate(re.to_date) : 'Present' }}</p></div>
                                    </div>
                                    <p v-if="re.comments" class="text-xs text-slate-600 mt-2 italic">{{ re.comments }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ─────────────────────────── TAB: TRAINING -->
                    <div v-show="activeTab === 'training'" class="space-y-8">
                        <!-- Training Schools -->
                        <div>
                            <h3 class="text-sm font-semibold text-slate-700 mb-3 flex items-center gap-2"><Lucide icon="GraduationCap" class="w-4 h-4 text-primary" /> Training Schools</h3>
                            <div v-if="driver.training_schools?.length" class="space-y-3">
                                <div v-for="(ts, i) in driver.training_schools" :key="i" class="bg-slate-50 rounded-lg p-4 border border-slate-200">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <p class="font-medium text-slate-800">{{ ts.name || 'N/A' }}</p>
                                            <p v-if="ts.city || ts.state" class="text-xs text-slate-500">{{ [ts.city, ts.state].filter(Boolean).join(', ') }}</p>
                                            <p v-if="ts.from_date || ts.to_date" class="text-xs text-slate-500 mt-0.5">
                                                {{ formatDate(ts.from_date) }} – {{ ts.to_date ? formatDate(ts.to_date) : 'Present' }}
                                            </p>
                                        </div>
                                        <div class="flex gap-2 flex-shrink-0">
                                            <span v-if="ts.graduated !== null" :class="ts.graduated ? 'bg-success/10 text-success' : 'bg-slate-100 text-slate-500'" class="px-2 py-0.5 rounded-full text-xs">{{ ts.graduated ? 'Graduated' : 'Not Graduated' }}</span>
                                            <a v-if="ts.certificate_url" :href="ts.certificate_url" target="_blank" class="text-xs text-primary hover:underline flex items-center gap-1">
                                                <Lucide icon="FileText" class="w-3.5 h-3.5" /> Certificate
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <p v-else class="text-slate-500 text-sm">No training schools on file</p>
                        </div>

                        <!-- Courses -->
                        <div>
                            <h3 class="text-sm font-semibold text-slate-700 mb-3 flex items-center gap-2"><Lucide icon="BookOpen" class="w-4 h-4 text-primary" /> Courses & Certifications</h3>
                            <div v-if="driver.courses?.length" class="space-y-3">
                                <div v-for="(c, i) in driver.courses" :key="i" class="bg-slate-50 rounded-lg p-4 border border-slate-200">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <p class="font-medium text-slate-800">{{ c.organization_name || 'N/A' }}</p>
                                            <p v-if="c.location" class="text-xs text-slate-500">{{ c.location }}</p>
                                            <div class="flex gap-3 mt-1 text-xs text-slate-500">
                                                <span v-if="c.certification_date">Certified: {{ formatDate(c.certification_date) }}</span>
                                                <span v-if="c.expiration_date">Expires: {{ formatDate(c.expiration_date) }}</span>
                                            </div>
                                        </div>
                                        <div class="flex gap-2 flex-shrink-0">
                                            <span v-if="c.status" :class="c.status === 'completed' ? 'bg-success/10 text-success' : 'bg-primary/10 text-primary'" class="px-2 py-0.5 rounded-full text-xs capitalize">{{ c.status }}</span>
                                            <a v-if="c.certificate_url" :href="c.certificate_url" target="_blank" class="text-xs text-primary hover:underline flex items-center gap-1">
                                                <Lucide icon="FileText" class="w-3.5 h-3.5" /> Certificate
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <p v-else class="text-slate-500 text-sm">No courses on file</p>
                        </div>
                    </div>

                    <!-- ─────────────────────────── TAB: TESTING -->
                    <div v-show="activeTab === 'testing'" class="space-y-6">
                        <!-- Summary -->
                        <div v-if="driver.testings?.length" class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div class="bg-slate-50 rounded-lg p-4 text-center border border-slate-200">
                                <p class="text-2xl font-bold text-slate-800">{{ driver.testings.length }}</p>
                                <p class="text-xs text-slate-500 mt-1">Total Tests</p>
                            </div>
                            <div class="bg-success/10 rounded-lg p-4 text-center border border-success/20">
                                <p class="text-2xl font-bold text-success">{{ driver.testings.filter(t => t.test_result?.toLowerCase() === 'negative').length }}</p>
                                <p class="text-xs text-slate-500 mt-1">Negative</p>
                            </div>
                            <div class="bg-danger/10 rounded-lg p-4 text-center border border-danger/20">
                                <p class="text-2xl font-bold text-danger">{{ driver.testings.filter(t => t.test_result?.toLowerCase() === 'positive').length }}</p>
                                <p class="text-xs text-slate-500 mt-1">Positive</p>
                            </div>
                            <div class="bg-slate-50 rounded-lg p-4 text-center border border-slate-200">
                                <p class="text-2xl font-bold text-slate-800">
                                    {{ driver.testings.filter(t => t.test_result?.toLowerCase() === 'negative').length > 0
                                        ? Math.round((driver.testings.filter(t => t.test_result?.toLowerCase() === 'negative').length / driver.testings.length) * 100)
                                        : 0 }}%
                                </p>
                                <p class="text-xs text-slate-500 mt-1">Pass Rate</p>
                            </div>
                        </div>

                        <!-- Drug / Alcohol Breakdown -->
                        <div v-if="drugTests.length || alcoholTests.length" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="bg-slate-50 rounded-lg p-4 border border-slate-200">
                                <h4 class="font-medium text-slate-700 mb-3 flex items-center gap-2"><Lucide icon="FlaskConical" class="w-4 h-4 text-primary" /> Drug Testing</h4>
                                <div class="space-y-2 text-sm">
                                    <div class="flex justify-between"><span class="text-slate-500">Total</span><span class="font-medium">{{ drugTests.length }}</span></div>
                                    <div class="flex justify-between"><span class="text-slate-500">Last Date</span><span>{{ formatDate(drugTests[0]?.test_date ?? null) }}</span></div>
                                    <div class="flex justify-between items-center"><span class="text-slate-500">Last Result</span>
                                        <span v-if="drugTests[0]" :class="[testResultBadge(drugTests[0].test_result), 'px-2 py-0.5 rounded-full text-xs font-medium capitalize']">{{ drugTests[0].test_result || 'Pending' }}</span>
                                        <span v-else class="text-slate-400">N/A</span>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-slate-50 rounded-lg p-4 border border-slate-200">
                                <h4 class="font-medium text-slate-700 mb-3 flex items-center gap-2"><Lucide icon="Wine" class="w-4 h-4 text-warning" /> Alcohol Testing</h4>
                                <div class="space-y-2 text-sm">
                                    <div class="flex justify-between"><span class="text-slate-500">Total</span><span class="font-medium">{{ alcoholTests.length }}</span></div>
                                    <div class="flex justify-between"><span class="text-slate-500">Last Date</span><span>{{ formatDate(alcoholTests[0]?.test_date ?? null) }}</span></div>
                                    <div class="flex justify-between items-center"><span class="text-slate-500">Last Result</span>
                                        <span v-if="alcoholTests[0]" :class="[testResultBadge(alcoholTests[0].test_result), 'px-2 py-0.5 rounded-full text-xs font-medium capitalize']">{{ alcoholTests[0].test_result || 'Pending' }}</span>
                                        <span v-else class="text-slate-400">N/A</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Testing History -->
                        <div v-if="driver.testings?.length">
                            <h3 class="text-sm font-semibold text-slate-700 mb-3">Testing History</h3>
                            <div class="space-y-3">
                                <div v-for="t in [...driver.testings].sort((a,b)=>(b.test_date??'').localeCompare(a.test_date??''))" :key="t.id"
                                    class="bg-slate-50 rounded-lg p-4 border border-slate-200 flex items-start justify-between gap-4">
                                    <div class="flex-1 min-w-0">
                                        <p class="font-medium text-slate-800 text-sm">{{ testTypeName[t.test_type ?? ''] ?? (t.test_type?.replace(/_/g, ' ') ?? 'Test') }}</p>
                                        <p class="text-xs text-slate-500">{{ formatDate(t.test_date) }}</p>
                                        <p v-if="t.administered_by" class="text-xs text-slate-500">By: {{ t.administered_by }}</p>
                                        <div class="flex flex-wrap gap-1.5 mt-1.5">
                                            <span v-if="t.is_random_test" class="px-1.5 py-0.5 rounded bg-primary/10 text-primary text-xs">Random</span>
                                            <span v-if="t.is_post_accident_test" class="px-1.5 py-0.5 rounded bg-warning/10 text-warning text-xs">Post-Accident</span>
                                            <span v-if="t.is_pre_employment_test" class="px-1.5 py-0.5 rounded bg-success/10 text-success text-xs">Pre-Employment</span>
                                        </div>
                                        <p v-if="t.next_test_due" class="text-xs text-slate-400 mt-1">Next due: {{ formatDate(t.next_test_due) }}</p>
                                    </div>
                                    <div class="flex flex-col items-end gap-2 flex-shrink-0">
                                        <span :class="[testResultBadge(t.test_result), 'px-2.5 py-0.5 rounded-full text-xs font-medium capitalize']">{{ t.test_result || 'Pending' }}</span>
                                        <div class="flex gap-2">
                                            <a v-if="t.drug_test_pdf_url" :href="t.drug_test_pdf_url" target="_blank" class="text-xs text-primary hover:underline flex items-center gap-1"><Lucide icon="FileText" class="w-3 h-3" /> Report</a>
                                            <a v-if="t.test_results_url" :href="t.test_results_url" target="_blank" class="text-xs text-primary hover:underline flex items-center gap-1"><Lucide icon="Download" class="w-3 h-3" /> Results</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <p v-if="!driver.testings?.length" class="text-slate-500 text-sm">No testing records on file</p>
                    </div>

                    <!-- ─────────────────────────── TAB: INSPECTIONS (4 sub-sections) -->
                    <div v-show="activeTab === 'inspections'" class="space-y-6">
                        <!-- Sub-nav -->
                        <div class="flex flex-wrap gap-2 border-b border-slate-200 pb-4">
                            <button v-for="sec in ([{id:'inspections',label:'Inspections',count:driver.inspections?.length},{id:'accidents',label:'Accidents',count:driver.accidents?.length},{id:'violations',label:'Violations',count:driver.traffic_convictions?.length},{id:'vehicles',label:'Vehicles',count:driver.vehicle_assignments?.length}] as const)"
                                :key="sec.id" @click="inspectionSection = sec.id"
                                class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-medium transition"
                                :class="inspectionSection === sec.id ? 'bg-primary text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'">
                                {{ sec.label }}
                                <span v-if="sec.count" class="px-1.5 py-0.5 rounded-full text-xs" :class="inspectionSection === sec.id ? 'bg-white/20' : 'bg-slate-300'">{{ sec.count }}</span>
                            </button>
                        </div>

                        <!-- Driver Inspections -->
                        <div v-show="inspectionSection === 'inspections'">
                            <div v-if="driver.inspections?.length" class="space-y-3">
                                <!-- Stats -->
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
                                    <div class="bg-slate-50 rounded-lg p-3 text-center border border-slate-200">
                                        <p class="text-xl font-bold text-slate-800">{{ driver.inspections.length }}</p>
                                        <p class="text-xs text-slate-500">Total</p>
                                    </div>
                                    <div class="bg-slate-50 rounded-lg p-3 text-center border border-slate-200">
                                        <p class="text-xl font-bold text-slate-800">{{ driver.inspections.filter(i=>i.inspection_date?.startsWith(new Date().getFullYear().toString())).length }}</p>
                                        <p class="text-xs text-slate-500">This Year</p>
                                    </div>
                                    <div class="bg-danger/10 rounded-lg p-3 text-center border border-danger/20">
                                        <p class="text-xl font-bold text-danger">{{ driver.inspections.filter(i=>i.defects_found).length }}</p>
                                        <p class="text-xs text-slate-500">Defects Found</p>
                                    </div>
                                    <div class="bg-slate-50 rounded-lg p-3 text-center border border-slate-200">
                                        <p class="text-sm font-medium text-slate-800">{{ formatDate(driver.inspections.slice().sort((a,b)=>(b.inspection_date??'').localeCompare(a.inspection_date??''))[0]?.inspection_date ?? null) }}</p>
                                        <p class="text-xs text-slate-500">Last Inspection</p>
                                    </div>
                                </div>
                                <!-- Table -->
                                <div class="overflow-x-auto rounded-lg border border-slate-200">
                                    <table class="w-full text-sm">
                                        <thead class="bg-slate-50">
                                            <tr>
                                                <th class="text-left px-4 py-2.5 text-xs font-medium text-slate-500">Date</th>
                                                <th class="text-left px-4 py-2.5 text-xs font-medium text-slate-500">Type</th>
                                                <th class="text-left px-4 py-2.5 text-xs font-medium text-slate-500">Level</th>
                                                <th class="text-left px-4 py-2.5 text-xs font-medium text-slate-500">Inspector</th>
                                                <th class="text-left px-4 py-2.5 text-xs font-medium text-slate-500">Location</th>
                                                <th class="text-left px-4 py-2.5 text-xs font-medium text-slate-500">Defects</th>
                                                <th class="text-left px-4 py-2.5 text-xs font-medium text-slate-500">Vehicle</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="(insp, i) in driver.inspections" :key="i" class="border-t border-slate-100 hover:bg-slate-50/50">
                                                <td class="px-4 py-3">{{ formatDate(insp.inspection_date) }}</td>
                                                <td class="px-4 py-3 capitalize">{{ insp.inspection_type?.replace(/_/g, ' ') || 'N/A' }}</td>
                                                <td class="px-4 py-3">
                                                    <span :class="[inspLevelBadge(insp.inspection_level), 'px-2 py-0.5 rounded-full text-xs font-semibold']">{{ insp.inspection_level || 'N/A' }}</span>
                                                </td>
                                                <td class="px-4 py-3">{{ insp.inspector_name || 'N/A' }}</td>
                                                <td class="px-4 py-3 max-w-[120px] truncate">{{ insp.location || 'N/A' }}</td>
                                                <td class="px-4 py-3">
                                                    <span v-if="insp.defects_found" class="flex items-center gap-1 text-danger text-xs"><Lucide icon="AlertCircle" class="w-3.5 h-3.5" /> Defects</span>
                                                    <span v-else class="flex items-center gap-1 text-success text-xs"><Lucide icon="CheckCircle" class="w-3.5 h-3.5" /> Clean</span>
                                                </td>
                                                <td class="px-4 py-3 text-xs">{{ insp.vehicle || 'N/A' }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div v-else class="flex flex-col items-center py-10 text-slate-400">
                                <Lucide icon="Shield" class="w-10 h-10 mb-2" />
                                <p>No inspection records on file</p>
                            </div>
                        </div>

                        <!-- Accidents -->
                        <div v-show="inspectionSection === 'accidents'">
                            <div v-if="driver.accidents?.length" class="space-y-3">
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
                                    <div class="bg-slate-50 rounded-lg p-3 text-center border"><p class="text-xl font-bold">{{ driver.accidents.length }}</p><p class="text-xs text-slate-500">Total</p></div>
                                    <div class="bg-danger/10 rounded-lg p-3 text-center border border-danger/20"><p class="text-xl font-bold text-danger">{{ driver.accidents.filter(a=>a.had_fatalities).length }}</p><p class="text-xs text-slate-500">W/ Fatalities</p></div>
                                    <div class="bg-warning/10 rounded-lg p-3 text-center border border-warning/20"><p class="text-xl font-bold text-warning">{{ driver.accidents.filter(a=>a.had_injuries).length }}</p><p class="text-xs text-slate-500">W/ Injuries</p></div>
                                    <div class="bg-slate-50 rounded-lg p-3 text-center border"><p class="text-sm font-medium">{{ formatDate(driver.accidents.slice().sort((a,b)=>(b.accident_date??'').localeCompare(a.accident_date??''))[0]?.accident_date ?? null) }}</p><p class="text-xs text-slate-500">Last Accident</p></div>
                                </div>
                                <div class="overflow-x-auto rounded-lg border border-slate-200">
                                    <table class="w-full text-sm">
                                        <thead class="bg-slate-50"><tr>
                                            <th class="text-left px-4 py-2.5 text-xs font-medium text-slate-500">Date</th>
                                            <th class="text-left px-4 py-2.5 text-xs font-medium text-slate-500">Nature</th>
                                            <th class="text-left px-4 py-2.5 text-xs font-medium text-slate-500">Fatalities</th>
                                            <th class="text-left px-4 py-2.5 text-xs font-medium text-slate-500">Injuries</th>
                                            <th class="text-left px-4 py-2.5 text-xs font-medium text-slate-500">Comments</th>
                                        </tr></thead>
                                        <tbody>
                                            <tr v-for="(acc, i) in driver.accidents" :key="i" class="border-t border-slate-100 hover:bg-slate-50/50">
                                                <td class="px-4 py-3">{{ formatDate(acc.accident_date) }}</td>
                                                <td class="px-4 py-3">{{ acc.nature_of_accident || 'N/A' }}</td>
                                                <td class="px-4 py-3"><span :class="acc.had_fatalities ? 'text-danger font-medium' : 'text-slate-500'">{{ acc.had_fatalities ? acc.number_of_fatalities : 'None' }}</span></td>
                                                <td class="px-4 py-3"><span :class="acc.had_injuries ? 'text-warning font-medium' : 'text-slate-500'">{{ acc.had_injuries ? acc.number_of_injuries : 'None' }}</span></td>
                                                <td class="px-4 py-3 max-w-[150px] truncate text-xs text-slate-600">{{ acc.comments || '—' }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div v-else class="flex flex-col items-center py-10 text-success">
                                <Lucide icon="CheckCircle" class="w-10 h-10 mb-2" />
                                <p class="font-medium">No accident records — clean driving record</p>
                            </div>
                        </div>

                        <!-- Traffic Violations -->
                        <div v-show="inspectionSection === 'violations'">
                            <div v-if="driver.traffic_convictions?.length" class="space-y-3">
                                <div class="grid grid-cols-2 md:grid-cols-3 gap-3 mb-4">
                                    <div class="bg-slate-50 rounded-lg p-3 text-center border"><p class="text-xl font-bold">{{ driver.traffic_convictions.length }}</p><p class="text-xs text-slate-500">Total</p></div>
                                    <div class="bg-slate-50 rounded-lg p-3 text-center border"><p class="text-xl font-bold">{{ driver.traffic_convictions.filter(t=>t.conviction_date && new Date(t.conviction_date) >= new Date(Date.now()-3*365*24*60*60*1000)).length }}</p><p class="text-xs text-slate-500">Last 3 Years</p></div>
                                    <div class="bg-slate-50 rounded-lg p-3 text-center border"><p class="text-sm font-medium">{{ formatDate(driver.traffic_convictions.slice().sort((a,b)=>(b.conviction_date??'').localeCompare(a.conviction_date??''))[0]?.conviction_date ?? null) }}</p><p class="text-xs text-slate-500">Last Conviction</p></div>
                                </div>
                                <div class="overflow-x-auto rounded-lg border border-slate-200">
                                    <table class="w-full text-sm">
                                        <thead class="bg-slate-50"><tr>
                                            <th class="text-left px-4 py-2.5 text-xs font-medium text-slate-500">Date</th>
                                            <th class="text-left px-4 py-2.5 text-xs font-medium text-slate-500">Charge</th>
                                            <th class="text-left px-4 py-2.5 text-xs font-medium text-slate-500">Type</th>
                                            <th class="text-left px-4 py-2.5 text-xs font-medium text-slate-500">Location</th>
                                            <th class="text-left px-4 py-2.5 text-xs font-medium text-slate-500">Penalty</th>
                                        </tr></thead>
                                        <tbody>
                                            <tr v-for="(tc, i) in driver.traffic_convictions" :key="i" class="border-t border-slate-100 hover:bg-slate-50/50">
                                                <td class="px-4 py-3">{{ formatDate(tc.conviction_date) }}</td>
                                                <td class="px-4 py-3 font-medium">{{ tc.charge || 'N/A' }}</td>
                                                <td class="px-4 py-3"><span v-if="tc.conviction_type" class="px-2 py-0.5 rounded-full bg-warning/10 text-warning text-xs capitalize">{{ tc.conviction_type }}</span><span v-else>N/A</span></td>
                                                <td class="px-4 py-3">{{ tc.location || 'N/A' }}</td>
                                                <td class="px-4 py-3 text-xs">{{ tc.penalty || 'N/A' }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div v-else class="flex flex-col items-center py-10 text-success">
                                <Lucide icon="CheckCircle" class="w-10 h-10 mb-2" />
                                <p class="font-medium">No traffic convictions — clean driving record</p>
                            </div>
                        </div>

                        <!-- Vehicle Assignments -->
                        <div v-show="inspectionSection === 'vehicles'">
                            <div v-if="driver.vehicle_assignments?.length" class="overflow-x-auto rounded-lg border border-slate-200">
                                <table class="w-full text-sm">
                                    <thead class="bg-slate-50"><tr>
                                        <th class="text-left px-4 py-2.5 text-xs font-medium text-slate-500">Vehicle</th>
                                        <th class="text-left px-4 py-2.5 text-xs font-medium text-slate-500">VIN</th>
                                        <th class="text-left px-4 py-2.5 text-xs font-medium text-slate-500">Driver Type</th>
                                        <th class="text-left px-4 py-2.5 text-xs font-medium text-slate-500">Start</th>
                                        <th class="text-left px-4 py-2.5 text-xs font-medium text-slate-500">End</th>
                                        <th class="text-left px-4 py-2.5 text-xs font-medium text-slate-500">Status</th>
                                    </tr></thead>
                                    <tbody>
                                        <tr v-for="(va, i) in driver.vehicle_assignments" :key="i" class="border-t border-slate-100 hover:bg-slate-50/50">
                                            <td class="px-4 py-3 font-medium">{{ va.vehicle ? `${va.vehicle.year ?? ''} ${va.vehicle.make ?? ''} ${va.vehicle.model ?? ''}`.trim() : 'N/A' }}</td>
                                            <td class="px-4 py-3 font-mono text-xs">{{ va.vehicle?.vin || 'N/A' }}</td>
                                            <td class="px-4 py-3 capitalize">{{ va.driver_type?.replace(/_/g, ' ') || 'N/A' }}</td>
                                            <td class="px-4 py-3">{{ formatDate(va.start_date) }}</td>
                                            <td class="px-4 py-3">{{ va.end_date ? formatDate(va.end_date) : 'Ongoing' }}</td>
                                            <td class="px-4 py-3">
                                                <span :class="va.status === 'active' ? 'bg-success/10 text-success' : 'bg-slate-100 text-slate-500'" class="px-2 py-0.5 rounded-full text-xs capitalize">{{ va.status || 'N/A' }}</span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div v-else class="flex flex-col items-center py-10 text-slate-400">
                                <Lucide icon="Truck" class="w-10 h-10 mb-2" />
                                <p>No vehicle assignments on file</p>
                            </div>
                        </div>
                    </div>

                    <!-- ─────────────────────────── TAB: COMPLIANCE (FMCSR + Criminal) -->
                    <div v-show="activeTab === 'compliance'" class="space-y-8">
                        <!-- FMCSR -->
                        <div>
                            <h3 class="text-sm font-semibold text-slate-700 mb-4 flex items-center gap-2">
                                <Lucide icon="ClipboardList" class="w-4 h-4 text-primary" /> FMCSR Data
                            </h3>
                            <div v-if="driver.fmcsr_data" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="bg-slate-50 rounded-lg p-4 border border-slate-200 space-y-3">
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-slate-600">License Disqualified</span>
                                        <span :class="driver.fmcsr_data.is_disqualified ? 'bg-danger/10 text-danger' : 'bg-success/10 text-success'" class="px-2 py-0.5 rounded-full text-xs font-medium">{{ yesNo(driver.fmcsr_data.is_disqualified) }}</span>
                                    </div>
                                    <p v-if="driver.fmcsr_data.disqualified_details" class="text-xs text-slate-500 italic">{{ driver.fmcsr_data.disqualified_details }}</p>
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-slate-600">License Suspended</span>
                                        <span :class="driver.fmcsr_data.is_license_suspended ? 'bg-danger/10 text-danger' : 'bg-success/10 text-success'" class="px-2 py-0.5 rounded-full text-xs font-medium">{{ yesNo(driver.fmcsr_data.is_license_suspended) }}</span>
                                    </div>
                                    <p v-if="driver.fmcsr_data.suspension_details" class="text-xs text-slate-500 italic">{{ driver.fmcsr_data.suspension_details }}</p>
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-slate-600">License Denied</span>
                                        <span :class="driver.fmcsr_data.is_license_denied ? 'bg-danger/10 text-danger' : 'bg-success/10 text-success'" class="px-2 py-0.5 rounded-full text-xs font-medium">{{ yesNo(driver.fmcsr_data.is_license_denied) }}</span>
                                    </div>
                                    <p v-if="driver.fmcsr_data.denial_details" class="text-xs text-slate-500 italic">{{ driver.fmcsr_data.denial_details }}</p>
                                </div>
                                <div class="bg-slate-50 rounded-lg p-4 border border-slate-200 space-y-3">
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-slate-600">Positive Drug Test</span>
                                        <span :class="driver.fmcsr_data.has_positive_drug_test ? 'bg-danger/10 text-danger' : 'bg-success/10 text-success'" class="px-2 py-0.5 rounded-full text-xs font-medium">{{ yesNo(driver.fmcsr_data.has_positive_drug_test) }}</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-slate-600">Duty Offenses</span>
                                        <span :class="driver.fmcsr_data.has_duty_offenses ? 'bg-danger/10 text-danger' : 'bg-success/10 text-success'" class="px-2 py-0.5 rounded-full text-xs font-medium">{{ yesNo(driver.fmcsr_data.has_duty_offenses) }}</span>
                                    </div>
                                    <p v-if="driver.fmcsr_data.offense_details" class="text-xs text-slate-500 italic">{{ driver.fmcsr_data.offense_details }}</p>
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-slate-600">Consent Driving Record</span>
                                        <span :class="driver.fmcsr_data.consent_driving_record ? 'bg-success/10 text-success' : 'bg-slate-100 text-slate-500'" class="px-2 py-0.5 rounded-full text-xs font-medium">{{ yesNo(driver.fmcsr_data.consent_driving_record) }}</span>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="text-slate-400 text-sm italic">No FMCSR data recorded.</div>
                        </div>

                        <!-- Criminal History -->
                        <div class="border-t pt-6">
                            <h3 class="text-sm font-semibold text-slate-700 mb-4 flex items-center gap-2">
                                <Lucide icon="AlertTriangle" class="w-4 h-4 text-warning" /> Criminal History
                            </h3>
                            <div v-if="driver.criminal_history" class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <div class="bg-slate-50 rounded-lg p-4 border border-slate-200 text-center">
                                    <p class="text-xs text-slate-500 mb-1">Criminal Charges</p>
                                    <span :class="driver.criminal_history.has_criminal_charges ? 'text-danger' : 'text-success'" class="text-lg font-bold">{{ yesNo(driver.criminal_history.has_criminal_charges) }}</span>
                                </div>
                                <div class="bg-slate-50 rounded-lg p-4 border border-slate-200 text-center">
                                    <p class="text-xs text-slate-500 mb-1">Felony Conviction</p>
                                    <span :class="driver.criminal_history.has_felony_conviction ? 'text-danger' : 'text-success'" class="text-lg font-bold">{{ yesNo(driver.criminal_history.has_felony_conviction) }}</span>
                                </div>
                                <div class="bg-slate-50 rounded-lg p-4 border border-slate-200 text-center">
                                    <p class="text-xs text-slate-500 mb-1">Minister's Permit</p>
                                    <span class="text-lg font-bold text-slate-700">{{ yesNo(driver.criminal_history.has_minister_permit) }}</span>
                                </div>
                                <div class="bg-slate-50 rounded-lg p-4 border border-slate-200 text-center">
                                    <p class="text-xs text-slate-500 mb-1">FCRA Consent</p>
                                    <span :class="driver.criminal_history.fcra_consent ? 'text-success' : 'text-slate-500'" class="text-lg font-bold">{{ yesNo(driver.criminal_history.fcra_consent) }}</span>
                                </div>
                            </div>
                            <div v-else class="text-slate-400 text-sm italic">No criminal history recorded.</div>
                        </div>
                    </div>

                    <!-- ─────────────────────────── TAB: HOS -->
                    <div v-show="activeTab === 'hos'" class="space-y-6">
                        <h3 class="text-sm font-semibold text-slate-700 mb-2 flex items-center gap-2">
                            <Lucide icon="Clock" class="w-4 h-4 text-primary" /> Hours of Service (HOS)
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Current cycle -->
                            <div class="bg-primary/5 border border-primary/20 rounded-xl p-5">
                                <p class="text-xs text-slate-500 mb-1">Current Cycle</p>
                                <p class="text-xl font-bold text-primary">{{ hosLabel[driver.hos_data.cycle_type] ?? driver.hos_data.cycle_type }}</p>
                                <p class="text-xs text-slate-500 mt-1">{{ driver.hos_data.cycle_type === '60_7' ? '60 hours available in 7 consecutive days' : '70 hours available in 8 consecutive days' }}</p>
                            </div>

                            <!-- Change request status -->
                            <div class="bg-slate-50 border border-slate-200 rounded-xl p-5">
                                <p class="text-xs text-slate-500 mb-3">Change Request</p>
                                <div v-if="driver.hos_data.change_requested" class="space-y-2">
                                    <div class="flex items-center gap-2">
                                        <span class="px-2 py-0.5 rounded-full bg-warning/10 text-warning text-xs font-medium">Pending Approval</span>
                                    </div>
                                    <p class="text-sm">Requesting: <strong>{{ hosLabel[driver.hos_data.change_requested_to ?? ''] ?? driver.hos_data.change_requested_to }}</strong></p>
                                    <p v-if="driver.hos_data.change_requested_at" class="text-xs text-slate-500">Requested: {{ driver.hos_data.change_requested_at }}</p>
                                </div>
                                <div v-else-if="driver.hos_data.change_approved_at" class="space-y-1">
                                    <span class="px-2 py-0.5 rounded-full bg-success/10 text-success text-xs font-medium">Approved</span>
                                    <p class="text-xs text-slate-500 mt-1">Approved: {{ driver.hos_data.change_approved_at }}</p>
                                </div>
                                <p v-else class="text-sm text-slate-400 italic">No change request pending</p>
                            </div>
                        </div>

                        <!-- HOS Documents -->
                        <div class="mt-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-sm font-semibold text-slate-700 flex items-center gap-2">
                                    <Lucide icon="FileText" class="w-4 h-4 text-primary" /> HOS Documents
                                </h3>
                                <div class="flex items-center gap-2">
                                    <Button variant="primary" class="flex items-center gap-1.5 text-xs px-3 py-1.5 h-auto" :disabled="generatingHos !== null" @click="activeHosPanel = activeHosPanel === 'daily' ? null : 'daily'">
                                        <Lucide icon="CalendarDays" class="w-3.5 h-3.5" /> Generate Daily Log
                                    </Button>
                                    <Button variant="outline-secondary" class="flex items-center gap-1.5 text-xs px-3 py-1.5 h-auto" :disabled="generatingHos !== null" @click="activeHosPanel = activeHosPanel === 'monthly' ? null : 'monthly'">
                                        <Lucide icon="BarChart2" class="w-3.5 h-3.5" /> Monthly Summary
                                    </Button>
                                    <Button variant="warning" class="flex items-center gap-1.5 text-xs px-3 py-1.5 h-auto" :disabled="generatingHos !== null" @click="activeHosPanel = activeHosPanel === 'fmcsa' ? null : 'fmcsa'">
                                        <Lucide icon="FileStack" class="w-3.5 h-3.5" /> FMCSA Monthly
                                    </Button>
                                </div>
                            </div>

                            <!-- Generate Daily Log panel -->
                            <div v-if="activeHosPanel === 'daily'" class="mb-4 rounded-xl border border-slate-200 bg-slate-50 p-4">
                                <p class="text-xs font-semibold text-slate-600 uppercase tracking-wide mb-3">Daily Log — Select Date</p>
                                <div class="flex items-end gap-3">
                                    <div class="flex-1">
                                        <Litepicker v-model="dailyLogForm.date" :options="pickerOptionsSingle" />
                                    </div>
                                    <Button variant="primary" class="text-xs px-4 h-9" :disabled="!dailyLogForm.date || generatingHos === 'daily'" @click="generateDailyLog">
                                        {{ generatingHos === 'daily' ? 'Generating...' : 'Generate' }}
                                    </Button>
                                </div>
                            </div>

                            <!-- Generate Monthly Summary panel -->
                            <div v-if="activeHosPanel === 'monthly'" class="mb-4 rounded-xl border border-slate-200 bg-slate-50 p-4">
                                <p class="text-xs font-semibold text-slate-600 uppercase tracking-wide mb-3">Monthly Summary — Select Month & Year</p>
                                <div class="flex items-end gap-3">
                                    <FormInput v-model="monthlyForm.month" type="number" min="1" max="12" placeholder="Month" class="w-24" />
                                    <FormInput v-model="monthlyForm.year" type="number" min="2020" placeholder="Year" class="w-28" />
                                    <Button variant="primary" class="text-xs px-4 h-9" :disabled="generatingHos === 'monthly'" @click="generateMonthlySummary">
                                        {{ generatingHos === 'monthly' ? 'Generating...' : 'Generate' }}
                                    </Button>
                                </div>
                            </div>

                            <!-- Generate FMCSA Monthly panel -->
                            <div v-if="activeHosPanel === 'fmcsa'" class="mb-4 rounded-xl border border-slate-200 bg-slate-50 p-4">
                                <p class="text-xs font-semibold text-slate-600 uppercase tracking-wide mb-3">FMCSA Monthly Report — Select Month & Year</p>
                                <div class="flex items-end gap-3">
                                    <FormInput v-model="fmcsaForm.month" type="number" min="1" max="12" placeholder="Month" class="w-24" />
                                    <FormInput v-model="fmcsaForm.year" type="number" min="2020" placeholder="Year" class="w-28" />
                                    <Button variant="warning" class="text-xs px-4 h-9" :disabled="generatingHos === 'fmcsa'" @click="generateFmcsaMonthly">
                                        {{ generatingHos === 'fmcsa' ? 'Generating...' : 'Generate' }}
                                    </Button>
                                </div>
                            </div>

                            <!-- Documents list -->
                            <div v-if="hosDocuments && hosDocuments.length" class="overflow-x-auto rounded-xl border border-slate-200">
                                <table class="min-w-full divide-y divide-slate-200 text-sm">
                                    <thead class="bg-slate-50">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Type</th>
                                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">File</th>
                                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Doc Date</th>
                                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Generated</th>
                                            <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase tracking-wide">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-slate-100">
                                        <tr v-for="doc in hosDocuments" :key="doc.id" class="hover:bg-slate-50 transition-colors">
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-primary/10 text-primary">{{ doc.type_label }}</span>
                                            </td>
                                            <td class="px-4 py-3">
                                                <div class="font-medium text-slate-800 text-xs">{{ doc.file_name }}</div>
                                                <div class="text-xs text-slate-400">{{ doc.size_label }}</div>
                                            </td>
                                            <td class="px-4 py-3 text-slate-600 text-xs whitespace-nowrap">{{ doc.document_date ?? '—' }}</td>
                                            <td class="px-4 py-3 text-slate-500 text-xs whitespace-nowrap">{{ doc.created_at ?? '—' }}</td>
                                            <td class="px-4 py-3 text-right">
                                                <div class="flex justify-end gap-3 text-xs">
                                                    <a :href="doc.preview_url" target="_blank" class="text-primary hover:underline">Preview</a>
                                                    <a :href="doc.download_url" class="text-primary hover:underline">Download</a>
                                                    <button class="text-danger hover:underline" @click="deleteHosDocument(doc.id)">Delete</button>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div v-else class="rounded-xl border border-dashed border-slate-200 bg-slate-50 p-6 text-center">
                                <Lucide icon="FileText" class="mx-auto h-8 w-8 text-slate-300 mb-2" />
                                <p class="text-sm font-medium text-slate-600">No HOS Documents</p>
                                <p class="text-xs text-slate-400 mt-1">Generate daily logs or monthly summaries for this driver.</p>
                            </div>
                        </div>

                        <!-- Trips Table -->
                        <div class="mt-6">
                            <h3 class="text-sm font-semibold text-slate-700 mb-3 flex items-center gap-2">
                                <Lucide icon="Truck" class="w-4 h-4 text-primary" /> Trip History
                            </h3>
                            <div v-if="driver.trips && driver.trips.length" class="overflow-x-auto rounded-xl border border-slate-200">
                                <table class="min-w-full divide-y divide-slate-200 text-sm">
                                    <thead class="bg-slate-50">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Trip #</th>
                                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Status</th>
                                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Start Date</th>
                                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">End Date</th>
                                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Origin</th>
                                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Destination</th>
                                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Vehicle</th>
                                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Duration</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-slate-100">
                                        <tr v-for="trip in driver.trips" :key="trip.id" class="hover:bg-slate-50 transition-colors">
                                            <td class="px-4 py-3 font-mono text-xs font-medium text-primary whitespace-nowrap">
                                                {{ trip.trip_number ?? '—' }}
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium" :class="tripStatusBadge(trip.status)">
                                                    {{ tripStatusLabel(trip.status) }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-slate-600 whitespace-nowrap">{{ trip.scheduled_start_date ? formatDate(trip.scheduled_start_date) : '—' }}</td>
                                            <td class="px-4 py-3 text-slate-600 whitespace-nowrap">{{ trip.scheduled_end_date ? formatDate(trip.scheduled_end_date) : '—' }}</td>
                                            <td class="px-4 py-3 text-slate-600 max-w-[180px] truncate" :title="trip.origin_address ?? ''">{{ trip.origin_address ?? '—' }}</td>
                                            <td class="px-4 py-3 text-slate-600 max-w-[180px] truncate" :title="trip.destination_address ?? ''">{{ trip.destination_address ?? '—' }}</td>
                                            <td class="px-4 py-3 text-slate-600 whitespace-nowrap">{{ trip.vehicle ?? '—' }}</td>
                                            <td class="px-4 py-3 text-slate-600 whitespace-nowrap">
                                                {{ trip.estimated_duration_minutes ? Math.floor(trip.estimated_duration_minutes / 60) + 'h ' + (trip.estimated_duration_minutes % 60) + 'm' : '—' }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div v-else class="rounded-xl border border-dashed border-slate-200 bg-slate-50 p-6 text-center text-sm text-slate-400 italic">
                                No trips found for this driver.
                            </div>
                        </div>
                    </div>

                    <!-- ─────────────────────────── TAB: WIZARD STEPS -->
                    <div v-show="activeTab === 'wizard'" class="space-y-6">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-sm font-semibold text-slate-700 flex items-center gap-2">
                                <Lucide icon="ListChecks" class="w-4 h-4 text-primary" /> Application Wizard Progress
                            </h3>
                            <div class="flex items-center gap-3">
                                <div class="w-32 h-2 rounded-full bg-slate-200 overflow-hidden">
                                    <div class="h-full rounded-full bg-primary transition-all" :style="`width:${driver.wizard_total_pct}%`" />
                                </div>
                                <span class="text-sm font-semibold text-slate-700">{{ driver.wizard_total_pct }}%</span>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
                            <div v-for="step in driver.wizard_steps" :key="step.step"
                                class="flex items-center gap-3 p-3 rounded-lg border text-sm"
                                :class="stepStatusClass[step.status] ?? 'bg-slate-50 text-slate-600 border-slate-200'">
                                <div class="flex-shrink-0 w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold"
                                    :class="step.status === 'completed' ? 'bg-success text-white' : step.status === 'missing' ? 'bg-danger text-white' : 'bg-warning text-white'">
                                    <Lucide v-if="step.status === 'completed'" icon="Check" class="w-3.5 h-3.5" />
                                    <Lucide v-else-if="step.status === 'missing'" icon="X" class="w-3.5 h-3.5" />
                                    <span v-else>{{ step.step }}</span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="font-medium truncate">{{ step.label }}</p>
                                    <div class="flex items-center gap-2 mt-0.5">
                                        <div class="flex-1 h-1 rounded-full bg-black/10 overflow-hidden">
                                            <div class="h-full rounded-full bg-current opacity-60 transition-all" :style="`width:${step.percentage}%`" />
                                        </div>
                                        <span class="text-xs opacity-75">{{ step.percentage }}%</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-if="!driver.wizard_steps.length" class="text-slate-400 text-sm italic">No wizard data available.</div>
                    </div>

                    <!-- ─────────────────────────── TAB: DOCUMENTS -->
                    <div v-show="activeTab === 'documents'" class="space-y-8">
                        <div v-for="(docs, category) in documentsByCategory" :key="category">
                            <!-- Section header -->
                            <div class="flex items-center gap-2 mb-3">
                                <span class="flex items-center justify-center w-6 h-6 rounded-full bg-primary text-white text-xs font-bold">{{ docs.length }}</span>
                                <h4 class="font-semibold text-slate-700 capitalize text-sm">
                                    {{ categoryLabel(String(category)) }}
                                </h4>
                            </div>
                            <!-- Cards grid -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4 mb-2">
                                <div v-for="(doc, di) in docs" :key="di"
                                    class="bg-white border border-slate-200 rounded-xl p-4 flex flex-col gap-2 shadow-sm hover:shadow-md transition">
                                    <div class="flex items-start gap-3">
                                        <div class="flex-shrink-0 w-9 h-9 rounded-lg bg-slate-100 flex items-center justify-center">
                                            <Lucide icon="FileText" class="w-5 h-5 text-slate-400" />
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-sm font-medium text-slate-800 truncate">{{ doc.name }}</p>
                                            <p class="text-xs text-slate-400">{{ doc.size }}</p>
                                        </div>
                                    </div>
                                    <div class="text-xs text-slate-500 space-y-0.5">
                                        <p>Date: {{ doc.date }}</p>
                                        <p v-if="doc.type">Type: {{ doc.type }}</p>
                                        <p>Info: {{ doc.related_info }}</p>
                                        <p v-if="doc.company">Company: {{ doc.company }}</p>
                                    </div>
                                    <div class="flex gap-2 mt-auto pt-2 border-t border-slate-100">
                                        <a :href="doc.url" target="_blank"
                                            class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-300 text-xs text-slate-700 hover:bg-slate-50 transition">
                                            <Lucide icon="Eye" class="w-3.5 h-3.5" /> View
                                        </a>
                                        <a :href="doc.url" download
                                            class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-lg bg-primary text-white text-xs hover:bg-primary/90 transition">
                                            <Lucide icon="Download" class="w-3.5 h-3.5" /> Download
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-if="stats.total_documents === 0" class="flex flex-col items-center py-12 text-slate-400">
                            <Lucide icon="FileX" class="w-12 h-12 mb-3" />
                            <p>No documents uploaded</p>
                        </div>
                    </div>

                </div><!-- /p-6 -->
            </div><!-- /box -->
        </div>

        <!-- ══════════════════════════════════════ MIGRATION HISTORY -->
        <div v-if="driver.migration_history?.length" class="col-span-12">
            <div class="box box--stacked p-6">
                <h2 class="text-base font-semibold text-slate-800 mb-4 flex items-center gap-2">
                    <Lucide icon="ArrowRightLeft" class="w-5 h-5 text-warning" />
                    Migration History
                </h2>
                <div class="overflow-x-auto rounded-lg border border-slate-200">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="text-left px-4 py-2.5 text-xs font-medium text-slate-500">#</th>
                                <th class="text-left px-4 py-2.5 text-xs font-medium text-slate-500">Date</th>
                                <th class="text-left px-4 py-2.5 text-xs font-medium text-slate-500">From</th>
                                <th class="text-left px-4 py-2.5 text-xs font-medium text-slate-500">To</th>
                                <th class="text-left px-4 py-2.5 text-xs font-medium text-slate-500">By</th>
                                <th class="text-left px-4 py-2.5 text-xs font-medium text-slate-500">Reason</th>
                                <th class="text-left px-4 py-2.5 text-xs font-medium text-slate-500">Status</th>
                                <th class="text-left px-4 py-2.5 text-xs font-medium text-slate-500">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="m in driver.migration_history" :key="m.id" class="hover:bg-slate-50">
                                <td class="px-4 py-3 text-xs text-slate-400">#{{ m.id }}</td>
                                <td class="px-4 py-3 text-xs text-slate-600 whitespace-nowrap">{{ m.migrated_at }}</td>
                                <td class="px-4 py-3 text-xs font-medium text-slate-700">{{ m.source_carrier }}</td>
                                <td class="px-4 py-3 text-xs font-medium text-slate-700">{{ m.target_carrier }}</td>
                                <td class="px-4 py-3 text-xs text-slate-600">{{ m.performed_by }}</td>
                                <td class="px-4 py-3 text-xs text-slate-500 max-w-[160px] truncate">{{ m.reason || '—' }}</td>
                                <td class="px-4 py-3">
                                    <span v-if="m.status === 'completed'" class="px-2 py-0.5 rounded-full text-xs font-medium bg-success/10 text-success">Completed</span>
                                    <span v-else class="px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-500">Rolled Back</span>
                                    <div v-if="m.status === 'rolled_back'" class="text-xs text-slate-400 mt-0.5">{{ m.rolled_back_at }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <button v-if="m.can_rollback" type="button" @click="openRollbackModal(m.id)"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-danger/10 text-danger text-xs font-medium hover:bg-danger/15 transition">
                                        <Lucide icon="RotateCcw" class="w-3.5 h-3.5" /> Rollback
                                    </button>
                                    <span v-else class="text-xs text-slate-300">—</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <!-- ══════════════════ MIGRATION WIZARD MODAL ══════════════════════════════ -->
    <div v-if="showMigrationModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] flex flex-col overflow-hidden">

            <!-- Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 flex-shrink-0">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-warning/10 rounded-lg"><Lucide icon="ArrowRightLeft" class="w-5 h-5 text-warning" /></div>
                    <div>
                        <h2 class="text-base font-semibold text-slate-800">Migrate Driver to Another Carrier</h2>
                        <p class="text-xs text-slate-500">{{ driver.full_name }}</p>
                    </div>
                </div>
                <button type="button" @click="showMigrationModal = false" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition">
                    <Lucide icon="X" class="w-5 h-5" />
                </button>
            </div>

            <!-- Step indicators -->
            <div class="flex border-b border-slate-100 flex-shrink-0">
                <div v-for="s in [1,2,3,4]" :key="s"
                    class="flex-1 py-2.5 text-center text-xs font-medium transition"
                    :class="migrationStep === s ? 'text-primary border-b-2 border-primary bg-primary/5' : migrationStep > s ? 'text-success' : 'text-slate-400'">
                    <span class="mr-1">{{ ['1. Select Carrier','2. Validation','3. Confirm','4. Done'][s-1] }}</span>
                </div>
            </div>

            <!-- Body -->
            <div class="flex-1 overflow-y-auto p-6">

                <!-- Step 1: Select Carrier -->
                <div v-if="migrationStep === 1">
                    <div v-if="migrationLoading" class="flex flex-col items-center py-10 text-slate-400">
                        <Lucide icon="Loader" class="w-8 h-8 animate-spin mb-2" /> Loading carriers...
                    </div>
                    <template v-else>
                        <input v-model="migrationSearch" type="text" placeholder="Search by name or DOT number..."
                            class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm mb-4 focus:outline-none focus:ring-2 focus:ring-primary/30" />
                        <div v-if="filteredCarriers.length" class="space-y-2">
                            <div v-for="c in filteredCarriers" :key="c.id"
                                class="flex items-center justify-between border border-slate-200 rounded-xl p-4 hover:border-primary hover:bg-primary/5 cursor-pointer transition"
                                @click="selectCarrierAndValidate(c)">
                                <div>
                                    <div class="font-semibold text-slate-800">{{ c.name }}</div>
                                    <div class="text-xs text-slate-500 mt-0.5 flex items-center gap-3">
                                        <span v-if="c.dot_number">DOT: {{ c.dot_number }}</span>
                                        <span v-if="c.state">{{ c.state }}</span>
                                    </div>
                                </div>
                                <div class="text-right flex-shrink-0 ml-4">
                                    <div class="text-xs text-slate-500">{{ c.current_drivers }} / {{ c.max_drivers }} drivers</div>
                                    <div class="w-16 bg-slate-100 rounded-full h-1.5 mt-1">
                                        <div class="bg-primary h-1.5 rounded-full" :style="`width:${Math.min((c.current_drivers/c.max_drivers)*100,100)}%`"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="flex flex-col items-center py-10 text-slate-400">
                            <Lucide icon="Building2" class="w-10 h-10 mb-2" />
                            <p class="text-sm">No available carriers found</p>
                        </div>
                    </template>
                </div>

                <!-- Step 2: Validation -->
                <div v-else-if="migrationStep === 2">
                    <div v-if="migrationLoading" class="flex flex-col items-center py-10 text-slate-400">
                        <Lucide icon="Loader" class="w-8 h-8 animate-spin mb-2" /> Validating migration...
                    </div>
                    <div v-else-if="migrationError" class="flex flex-col items-center py-10 text-center">
                        <Lucide icon="AlertCircle" class="w-10 h-10 text-danger mb-3" />
                        <p class="text-sm font-medium text-danger mb-1">Validation Failed</p>
                        <p class="text-xs text-slate-500 max-w-xs">{{ migrationError }}</p>
                        <button type="button" @click="migrationStep = 1; migrationError = null" class="mt-4 px-4 py-2 rounded-lg border border-slate-200 text-sm text-slate-600 hover:bg-slate-50 transition">
                            Go Back
                        </button>
                    </div>
                    <div v-else-if="migrationValidation">
                        <div class="mb-4 p-4 bg-slate-50 rounded-xl border border-slate-200">
                            <p class="text-xs text-slate-500 mb-0.5">Migrating to</p>
                            <p class="font-semibold text-slate-800">{{ selectedCarrier?.name }}</p>
                        </div>
                        <div v-if="migrationValidation.errors.length" class="mb-4">
                            <div class="flex items-center gap-2 mb-2 text-danger font-medium text-sm"><Lucide icon="XCircle" class="w-4 h-4" /> Blocking Issues</div>
                            <div v-for="e in migrationValidation.errors" :key="e"
                                class="flex items-start gap-2 p-3 bg-danger/10 border border-danger/20 rounded-lg text-sm text-danger mb-2">
                                <Lucide icon="AlertCircle" class="w-4 h-4 flex-shrink-0 mt-0.5" /> {{ e }}
                            </div>
                        </div>
                        <div v-if="migrationValidation.warnings.length" class="mb-4">
                            <div class="flex items-center gap-2 mb-2 text-warning font-medium text-sm"><Lucide icon="AlertTriangle" class="w-4 h-4" /> Warnings</div>
                            <div v-for="w in migrationValidation.warnings" :key="w"
                                class="flex items-start gap-2 p-3 bg-warning/10 border border-warning/20 rounded-lg text-sm text-warning mb-2">
                                <Lucide icon="AlertTriangle" class="w-4 h-4 flex-shrink-0 mt-0.5" /> {{ w }}
                            </div>
                        </div>
                        <div v-if="migrationValidation.is_valid && !migrationValidation.warnings.length"
                            class="flex items-center gap-2 p-4 bg-success/10 border border-success/20 rounded-xl text-success text-sm">
                            <Lucide icon="CheckCircle" class="w-5 h-5" /> Driver is eligible for migration.
                        </div>
                    </div>
                </div>

                <!-- Step 3: Confirm -->
                <div v-else-if="migrationStep === 3" class="space-y-5">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-slate-50 rounded-xl p-4 border border-slate-200">
                            <p class="text-xs text-slate-400 mb-1">From</p>
                            <p class="font-semibold text-slate-800">{{ driver.carrier?.name ?? '—' }}</p>
                        </div>
                        <div class="bg-primary/5 rounded-xl p-4 border border-primary/20">
                            <p class="text-xs text-slate-400 mb-1">To</p>
                            <p class="font-semibold text-primary">{{ selectedCarrier?.name }}</p>
                        </div>
                    </div>
                    <div class="p-4 bg-warning/10 border border-warning/20 rounded-xl text-xs text-warning flex items-start gap-2">
                        <Lucide icon="Info" class="w-4 h-4 flex-shrink-0 mt-0.5" />
                        Driver status will be set to <strong>Pending</strong> after migration. All active vehicle assignments will be ended. You have 24 hours to rollback this action.
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Reason for Migration <span class="text-slate-400 font-normal">(optional)</span></label>
                        <textarea v-model="migrationReason" rows="2" placeholder="Enter reason..."
                            class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30 resize-none"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Additional Notes <span class="text-slate-400 font-normal">(optional)</span></label>
                        <textarea v-model="migrationNotes" rows="2" placeholder="Any additional notes..."
                            class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30 resize-none"></textarea>
                    </div>
                    <div v-if="migrationError" class="p-3 bg-danger/10 border border-danger/20 rounded-lg text-sm text-danger">{{ migrationError }}</div>
                </div>

                <!-- Step 4: Done -->
                <div v-else-if="migrationStep === 4" class="flex flex-col items-center py-8 text-center">
                    <div class="w-16 h-16 bg-success/10 rounded-full flex items-center justify-center mb-4">
                        <Lucide icon="CheckCircle" class="w-8 h-8 text-success" />
                    </div>
                    <h3 class="text-lg font-semibold text-slate-800 mb-2">Migration Successful!</h3>
                    <p class="text-sm text-slate-500 mb-6">Driver has been successfully migrated to <strong>{{ migrationDone?.target_carrier }}</strong>.</p>
                    <button type="button" @click="showMigrationModal = false"
                        class="px-6 py-2.5 bg-primary text-white rounded-lg text-sm font-medium hover:bg-primary/90 transition">
                        Close
                    </button>
                </div>

            </div>

            <!-- Footer -->
            <div class="flex items-center justify-between px-6 py-4 border-t border-slate-100 flex-shrink-0 bg-slate-50">
                <button v-if="migrationStep > 1 && migrationStep < 4" type="button" @click="migrationStep = (migrationStep - 1) as any"
                    class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg border border-slate-200 text-sm text-slate-600 hover:bg-white transition">
                    <Lucide icon="ChevronLeft" class="w-4 h-4" /> Back
                </button>
                <div v-else></div>
                <div class="flex gap-2">
                    <button v-if="migrationStep < 4" type="button" @click="showMigrationModal = false"
                        class="px-4 py-2 rounded-lg border border-slate-200 text-sm text-slate-600 hover:bg-white transition">Cancel</button>
                    <button v-if="migrationStep === 2 && migrationValidation?.is_valid" type="button" @click="goToConfirm"
                        class="px-5 py-2 rounded-lg bg-primary text-white text-sm font-medium hover:bg-primary/90 transition">
                        Continue <Lucide icon="ChevronRight" class="w-4 h-4 inline" />
                    </button>
                    <button v-if="migrationStep === 3" type="button" @click="submitMigration" :disabled="migrationLoading"
                        class="px-5 py-2 rounded-lg bg-warning text-white text-sm font-medium hover:bg-warning/90 transition disabled:opacity-60 flex items-center gap-1.5">
                        <Lucide v-if="migrationLoading" icon="Loader" class="w-4 h-4 animate-spin" />
                        Confirm Migration
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ══════════════════ ROLLBACK MODAL ══════════════════════════════════════ -->
    <div v-if="rollbackModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
            <div class="flex items-center gap-3 mb-5">
                <div class="p-2 bg-danger/10 rounded-lg"><Lucide icon="RotateCcw" class="w-5 h-5 text-danger" /></div>
                <h2 class="text-base font-semibold text-slate-800">Rollback Migration</h2>
            </div>
            <p class="text-sm text-slate-600 mb-4">This will restore the driver to their original carrier. This action can only be performed within 24 hours of the migration.</p>
            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Reason for Rollback <span class="text-danger">*</span></label>
                <textarea v-model="rollbackReason" rows="3" placeholder="Required..."
                    class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-danger/30 resize-none"></textarea>
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" @click="rollbackModal = false"
                    class="px-4 py-2 rounded-lg border border-slate-200 text-sm text-slate-600 hover:bg-slate-50 transition">Cancel</button>
                <button type="button" @click="submitRollback" :disabled="!rollbackReason.trim()"
                    class="px-5 py-2 rounded-lg bg-danger text-white text-sm font-medium hover:bg-danger/90 transition disabled:opacity-50">
                    Confirm Rollback
                </button>
            </div>
        </div>
    </div>

</template>
