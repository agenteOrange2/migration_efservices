<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import Lucide from '@/components/Base/Lucide'
import Button from '@/components/Base/Button'
import RazeLayout from '@/layouts/RazeLayout.vue'

declare function route(name: string, params?: any): string

defineOptions({ layout: RazeLayout })

// ─── Types ────────────────────────────────────────────────────────────────────
interface Carrier { id: number; name: string; address: string | null; state: string | null; dot_number: string | null; mc_number: string | null }
interface Address { address_line1: string; address_line2?: string; city: string; state: string; zip_code: string; primary: boolean; from_date?: string; to_date?: string }
interface License { id: number; license_number: string; state_of_issue: string | null; license_class: string | null; expiration_date: string | null; is_cdl: boolean; status: string | null; license_front_url: string | null; license_back_url: string | null; endorsements: string[] }
interface Experience { equipment_type: string | null; years_experience: number | null; miles_driven: number | null; requires_cdl: boolean }
interface Medical { medical_card_expiration_date: string | null; medical_examiner_name: string | null; medical_examiner_registry: string | null; medical_status: string; medical_card_url: string | null; social_security_card_url: string | null; medical_certificate_url: string | null }
interface Employment { company_name: string | null; company_address: string | null; company_phone: string | null; positions_held: string | null; from_date: string | null; to_date: string | null; subject_to_fmcsr: boolean; safety_sensitive_function: boolean; reason_for_leaving: string | null; email: string | null; email_sent: boolean; verification_status: string | null; verification_date: string | null; explanation: string | null }
interface RelatedEmployment { period: string | null; position: string | null; work_position: string | null; comments: string | null; from_date: string | null; to_date: string | null }
interface UnemploymentPeriod { from_date: string | null; to_date: string | null; comments: string | null; type: string | null }
interface TrainingSchool { name: string | null; location: string | null; from_date: string | null; to_date: string | null; graduated: boolean | null; subject_fmcsr: boolean | null; certificate_url: string | null }
interface Course { organization_name: string | null; location: string | null; status: string | null; certification_date: string | null; expiration_date: string | null; certificate_url: string | null }
interface Testing { id: number; test_date: string | null; test_type: string | null; test_result: string | null; status: string | null; administered_by: string | null; location: string | null; next_test_due: string | null; notes: string | null; is_random_test: boolean; is_post_accident_test: boolean; is_pre_employment_test: boolean; drug_test_pdf_url: string | null; test_results_url: string | null }
interface Inspection { inspection_date: string | null; inspection_type: string | null; inspection_level: string | null; inspector_name: string | null; location: string | null; status: string | null; defects_found: string | null; is_defects_corrected: boolean; corrective_actions: string | null; notes: string | null; vehicle: string | null }
interface VehicleAssignment { driver_type: string | null; start_date: string | null; end_date: string | null; status: string | null; notes: string | null; vehicle: { make: string | null; model: string | null; year: string | null; vin: string | null } | null }
interface Accident { accident_date: string | null; nature_of_accident: string | null; had_fatalities: boolean; had_injuries: boolean; number_of_fatalities: number; number_of_injuries: number; comments: string | null }
interface TrafficConviction { conviction_date: string | null; location: string | null; charge: string | null; penalty: string | null; conviction_type: string | null; description: string | null }

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
}

interface Stats { total_documents: number; licenses_count: number; medical_status: string; medical_expiration: string | null; records_uploaded: number; testing_count: number; testing_status: string; vehicles_count: number; vehicles_status: string }
interface DocItem { name: string; url: string; size: string; date: string; related_info: string }

const props = defineProps<{ driver: Driver; documentsByCategory: Record<string, DocItem[]>; stats: Stats }>()

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
        active: 'bg-emerald-100 text-emerald-700', inactive: 'bg-red-100 text-red-700',
        draft: 'bg-slate-100 text-slate-600', pending_review: 'bg-amber-100 text-amber-700',
        approved: 'bg-blue-100 text-blue-700', rejected: 'bg-red-100 text-red-700',
    }
    return map[status] ?? 'bg-slate-100 text-slate-600'
}

const testResultBadge = (result: string | null) => {
    if (!result) return 'bg-slate-100 text-slate-600'
    const r = result.toLowerCase()
    if (r === 'negative') return 'bg-emerald-100 text-emerald-700'
    if (r === 'positive' || r === 'refusal') return 'bg-red-100 text-red-700'
    return 'bg-slate-100 text-slate-600'
}

const medicalBadge = (s: string) => ({ valid: 'bg-emerald-100 text-emerald-700', expiring_soon: 'bg-amber-100 text-amber-700', expired: 'bg-red-100 text-red-700' }[s] ?? 'bg-slate-100 text-slate-600')
const medicalLabel = (s: string) => ({ valid: 'Valid', expiring_soon: 'Expiring Soon', expired: 'Expired' }[s] ?? 'Unknown')

const inspLevelBadge = (level: string | null) => {
    const map: Record<string, string> = { 'I': 'bg-red-100 text-red-700', 'II': 'bg-orange-100 text-orange-700', 'III': 'bg-blue-100 text-blue-700' }
    return map[level ?? ''] ?? 'bg-slate-100 text-slate-600'
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

// ─── Actions ─────────────────────────────────────────────────────────────────
function activateDriver() {
    if (confirm(`Activate driver "${props.driver.full_name}"?`))
        router.put(route('admin.drivers.activate', props.driver.id), {}, { preserveScroll: true })
}
function deactivateDriver() {
    if (confirm(`Deactivate driver "${props.driver.full_name}"?`))
        router.put(route('admin.drivers.deactivate', props.driver.id), {}, { preserveScroll: true })
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
                        <Link :href="route('admin.drivers.index')">
                            <Button variant="outline-secondary" size="sm" class="flex items-center gap-1.5">
                                <Lucide icon="ArrowLeft" class="w-4 h-4" /> Back
                            </Button>
                        </Link>
                        <button v-if="!isActive" @click="activateDriver"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700 transition">
                            <Lucide icon="UserCheck" class="w-4 h-4" /> Activate
                        </button>
                        <button v-else @click="deactivateDriver"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-red-600 text-white text-sm font-medium hover:bg-red-700 transition">
                            <Lucide icon="UserMinus" class="w-4 h-4" /> Deactivate
                        </button>
                        <a v-if="stats.total_documents > 0" :href="route('admin.drivers.documents.download', driver.id)"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-primary text-white text-sm font-medium hover:bg-primary/90 transition">
                            <Lucide icon="Download" class="w-4 h-4" /> Download Docs
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- ══════════════════════════════════════════════════ STATS CARDS -->
        <div class="col-span-12">
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
                <div class="box box--stacked p-4 text-center">
                    <p class="text-2xl font-bold text-slate-800">{{ stats.total_documents }}</p>
                    <p class="text-xs text-slate-500 mt-1">Documents</p>
                </div>
                <div class="box box--stacked p-4 text-center">
                    <p class="text-2xl font-bold text-slate-800">{{ stats.licenses_count }}</p>
                    <p class="text-xs text-slate-500 mt-1">Licenses</p>
                </div>
                <div class="box box--stacked p-4 text-center">
                    <p class="text-2xl font-bold" :class="stats.medical_status === 'Valid' ? 'text-emerald-600' : 'text-red-600'">{{ stats.medical_status }}</p>
                    <p class="text-xs text-slate-500 mt-1">Medical</p>
                    <p v-if="stats.medical_expiration" class="text-xs text-slate-400">{{ formatDate(stats.medical_expiration) }}</p>
                </div>
                <div class="box box--stacked p-4 text-center">
                    <p class="text-2xl font-bold text-slate-800">{{ stats.records_uploaded }}</p>
                    <p class="text-xs text-slate-500 mt-1">Records</p>
                </div>
                <div class="box box--stacked p-4 text-center">
                    <p class="text-2xl font-bold text-slate-800">{{ stats.testing_count }}</p>
                    <p class="text-xs text-slate-500 mt-1">Tests</p>
                </div>
                <div class="box box--stacked p-4 text-center">
                    <p class="text-2xl font-bold text-slate-800">{{ stats.vehicles_count }}</p>
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
                <div v-if="driver.emergency_contact_name" class="mt-4 bg-amber-50/60 border border-amber-100 rounded-lg p-3">
                    <p class="text-xs font-semibold text-amber-700 mb-2 flex items-center gap-1"><Lucide icon="Phone" class="w-3.5 h-3.5" /> Emergency Contact</p>
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
                            <span v-if="tab.id === 'documents' && stats.total_documents > 0" class="ml-1 px-1.5 py-0.5 rounded-full bg-emerald-100 text-emerald-700 text-xs">{{ stats.total_documents }}</span>
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
                                            <span v-if="lic.is_cdl" class="px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 text-xs font-medium">CDL</span>
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
                                                <span :class="exp.requires_cdl ? 'bg-blue-100 text-blue-700' : 'bg-slate-100 text-slate-600'" class="px-2 py-0.5 rounded-full text-xs">{{ exp.requires_cdl ? 'Yes' : 'No' }}</span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- ─────────────────────────── TAB: MEDICAL -->
                    <div v-show="activeTab === 'medical'" class="space-y-6">
                        <div v-if="driver.medical" class="space-y-4">
                            <!-- Status Banner -->
                            <div :class="[medicalBadge(driver.medical.medical_status), 'flex items-center gap-3 p-4 rounded-lg border']">
                                <Lucide icon="Heart" class="w-5 h-5 flex-shrink-0" />
                                <div>
                                    <p class="font-semibold">Medical Certification: {{ medicalLabel(driver.medical.medical_status) }}</p>
                                    <p v-if="driver.medical.medical_card_expiration_date" class="text-sm opacity-80">
                                        Expires {{ formatDate(driver.medical.medical_card_expiration_date) }}
                                    </p>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="bg-slate-50 rounded-lg p-4 border border-slate-200">
                                    <p class="text-xs text-slate-500 mb-1">Medical Examiner</p>
                                    <p class="font-medium text-slate-800">{{ driver.medical.medical_examiner_name || 'N/A' }}</p>
                                    <p v-if="driver.medical.medical_examiner_registry" class="text-xs text-slate-500 mt-0.5">Registry: {{ driver.medical.medical_examiner_registry }}</p>
                                </div>
                                <div class="bg-slate-50 rounded-lg p-4 border border-slate-200">
                                    <p class="text-xs text-slate-500 mb-1">Expiration Date</p>
                                    <p class="font-medium text-slate-800">{{ formatDate(driver.medical.medical_card_expiration_date) }}</p>
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-3">
                                <a v-if="driver.medical.medical_card_url" :href="driver.medical.medical_card_url" target="_blank"
                                    class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-slate-300 text-sm text-slate-700 hover:bg-slate-50">
                                    <Lucide icon="FileText" class="w-4 h-4" /> View Medical Card
                                </a>
                                <a v-if="driver.medical.medical_certificate_url" :href="driver.medical.medical_certificate_url" target="_blank"
                                    class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-slate-300 text-sm text-slate-700 hover:bg-slate-50">
                                    <Lucide icon="Award" class="w-4 h-4" /> Medical Certificate
                                </a>
                                <a v-if="driver.medical.social_security_card_url" :href="driver.medical.social_security_card_url" target="_blank"
                                    class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-slate-300 text-sm text-slate-700 hover:bg-slate-50">
                                    <Lucide icon="CreditCard" class="w-4 h-4" /> SSN Card
                                </a>
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
                                            <span :class="emp.email_sent ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500'" class="px-2 py-0.5 rounded-full text-xs font-medium">
                                                {{ emp.email_sent ? 'Email Sent' : 'Email Pending' }}
                                            </span>
                                            <span v-if="emp.verification_status" :class="emp.verification_status === 'verified' ? 'bg-blue-100 text-blue-700' : 'bg-amber-100 text-amber-700'" class="px-2 py-0.5 rounded-full text-xs font-medium capitalize">
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
                                        <span :class="emp.subject_to_fmcsr ? 'bg-orange-100 text-orange-700' : 'bg-slate-100 text-slate-500'" class="flex items-center gap-1 px-2 py-0.5 rounded-full text-xs">
                                            <Lucide icon="AlertCircle" class="w-3 h-3" /> FMCSR: {{ emp.subject_to_fmcsr ? 'Yes' : 'No' }}
                                        </span>
                                        <span :class="emp.safety_sensitive_function ? 'bg-orange-100 text-orange-700' : 'bg-slate-100 text-slate-500'" class="flex items-center gap-1 px-2 py-0.5 rounded-full text-xs">
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
                            <h3 class="text-sm font-semibold text-slate-700 mb-3 flex items-center gap-2"><Lucide icon="Clock" class="w-4 h-4 text-amber-500" /> Unemployment Periods</h3>
                            <div class="space-y-2">
                                <div v-for="(up, i) in driver.unemployment_periods" :key="i" class="flex items-center justify-between bg-amber-50 rounded-lg p-4 border border-amber-100">
                                    <div>
                                        <p class="text-sm font-medium">{{ formatDate(up.from_date) }} – {{ up.to_date ? formatDate(up.to_date) : 'Present' }}</p>
                                        <p v-if="up.comments" class="text-xs text-slate-600 mt-0.5">{{ up.comments }}</p>
                                    </div>
                                    <span v-if="up.type" class="px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 text-xs capitalize">{{ up.type }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Related Employment -->
                        <div v-if="driver.related_employments?.length">
                            <h3 class="text-sm font-semibold text-slate-700 mb-3 flex items-center gap-2"><Lucide icon="Link" class="w-4 h-4 text-primary" /> Related Employment</h3>
                            <div class="space-y-2">
                                <div v-for="(re, i) in driver.related_employments" :key="i" class="bg-slate-50 rounded-lg p-4 border border-slate-200 text-sm">
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                                        <div v-if="re.position"><p class="text-xs text-slate-500">Position</p><p>{{ re.position }}</p></div>
                                        <div v-if="re.work_position"><p class="text-xs text-slate-500">Work Position</p><p>{{ re.work_position }}</p></div>
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
                                            <p v-if="ts.location" class="text-xs text-slate-500">{{ ts.location }}</p>
                                            <p v-if="ts.from_date || ts.to_date" class="text-xs text-slate-500 mt-0.5">
                                                {{ formatDate(ts.from_date) }} – {{ ts.to_date ? formatDate(ts.to_date) : 'Present' }}
                                            </p>
                                        </div>
                                        <div class="flex gap-2 flex-shrink-0">
                                            <span v-if="ts.graduated !== null" :class="ts.graduated ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500'" class="px-2 py-0.5 rounded-full text-xs">{{ ts.graduated ? 'Graduated' : 'Not Graduated' }}</span>
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
                                            <span v-if="c.status" :class="c.status === 'completed' ? 'bg-emerald-100 text-emerald-700' : 'bg-blue-100 text-blue-700'" class="px-2 py-0.5 rounded-full text-xs capitalize">{{ c.status }}</span>
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
                            <div class="bg-emerald-50 rounded-lg p-4 text-center border border-emerald-100">
                                <p class="text-2xl font-bold text-emerald-600">{{ driver.testings.filter(t => t.test_result?.toLowerCase() === 'negative').length }}</p>
                                <p class="text-xs text-slate-500 mt-1">Negative</p>
                            </div>
                            <div class="bg-red-50 rounded-lg p-4 text-center border border-red-100">
                                <p class="text-2xl font-bold text-red-600">{{ driver.testings.filter(t => t.test_result?.toLowerCase() === 'positive').length }}</p>
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
                                <h4 class="font-medium text-slate-700 mb-3 flex items-center gap-2"><Lucide icon="FlaskConical" class="w-4 h-4 text-blue-500" /> Drug Testing</h4>
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
                                <h4 class="font-medium text-slate-700 mb-3 flex items-center gap-2"><Lucide icon="Wine" class="w-4 h-4 text-purple-500" /> Alcohol Testing</h4>
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
                                            <span v-if="t.is_random_test" class="px-1.5 py-0.5 rounded bg-blue-100 text-blue-700 text-xs">Random</span>
                                            <span v-if="t.is_post_accident_test" class="px-1.5 py-0.5 rounded bg-orange-100 text-orange-700 text-xs">Post-Accident</span>
                                            <span v-if="t.is_pre_employment_test" class="px-1.5 py-0.5 rounded bg-green-100 text-green-700 text-xs">Pre-Employment</span>
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
                                    <div class="bg-red-50 rounded-lg p-3 text-center border border-red-100">
                                        <p class="text-xl font-bold text-red-600">{{ driver.inspections.filter(i=>i.defects_found).length }}</p>
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
                                                    <span v-if="insp.defects_found" class="flex items-center gap-1 text-red-600 text-xs"><Lucide icon="AlertCircle" class="w-3.5 h-3.5" /> Defects</span>
                                                    <span v-else class="flex items-center gap-1 text-emerald-600 text-xs"><Lucide icon="CheckCircle" class="w-3.5 h-3.5" /> Clean</span>
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
                                    <div class="bg-red-50 rounded-lg p-3 text-center border border-red-100"><p class="text-xl font-bold text-red-600">{{ driver.accidents.filter(a=>a.had_fatalities).length }}</p><p class="text-xs text-slate-500">W/ Fatalities</p></div>
                                    <div class="bg-orange-50 rounded-lg p-3 text-center border border-orange-100"><p class="text-xl font-bold text-orange-600">{{ driver.accidents.filter(a=>a.had_injuries).length }}</p><p class="text-xs text-slate-500">W/ Injuries</p></div>
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
                                                <td class="px-4 py-3"><span :class="acc.had_fatalities ? 'text-red-600 font-medium' : 'text-slate-500'">{{ acc.had_fatalities ? acc.number_of_fatalities : 'None' }}</span></td>
                                                <td class="px-4 py-3"><span :class="acc.had_injuries ? 'text-orange-600 font-medium' : 'text-slate-500'">{{ acc.had_injuries ? acc.number_of_injuries : 'None' }}</span></td>
                                                <td class="px-4 py-3 max-w-[150px] truncate text-xs text-slate-600">{{ acc.comments || '—' }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div v-else class="flex flex-col items-center py-10 text-emerald-600">
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
                                                <td class="px-4 py-3"><span v-if="tc.conviction_type" class="px-2 py-0.5 rounded-full bg-orange-100 text-orange-700 text-xs capitalize">{{ tc.conviction_type }}</span><span v-else>N/A</span></td>
                                                <td class="px-4 py-3">{{ tc.location || 'N/A' }}</td>
                                                <td class="px-4 py-3 text-xs">{{ tc.penalty || 'N/A' }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div v-else class="flex flex-col items-center py-10 text-emerald-600">
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
                                                <span :class="va.status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500'" class="px-2 py-0.5 rounded-full text-xs capitalize">{{ va.status || 'N/A' }}</span>
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

                    <!-- ─────────────────────────── TAB: DOCUMENTS -->
                    <div v-show="activeTab === 'documents'" class="space-y-6">
                        <div v-for="(docs, category) in documentsByCategory" :key="category">
                            <template v-if="docs?.length">
                                <h4 class="font-semibold text-slate-700 mb-2 capitalize flex items-center gap-2">
                                    <Lucide icon="Folder" class="w-4 h-4 text-primary" />
                                    {{ String(category).replace(/_/g, ' ') }}
                                    <span class="text-xs text-slate-400 font-normal">({{ docs.length }})</span>
                                </h4>
                                <div class="overflow-x-auto rounded-lg border border-slate-200 mb-4">
                                    <table class="w-full text-sm">
                                        <thead class="bg-slate-50">
                                            <tr>
                                                <th class="text-left px-4 py-2.5 text-xs font-medium text-slate-500">File Name</th>
                                                <th class="text-left px-4 py-2.5 text-xs font-medium text-slate-500">Size</th>
                                                <th class="text-left px-4 py-2.5 text-xs font-medium text-slate-500">Date</th>
                                                <th class="text-left px-4 py-2.5 text-xs font-medium text-slate-500">Related To</th>
                                                <th class="text-left px-4 py-2.5 text-xs font-medium text-slate-500">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="(doc, di) in docs" :key="di" class="border-t border-slate-100 hover:bg-slate-50/50">
                                                <td class="px-4 py-3 flex items-center gap-2">
                                                    <Lucide icon="FileText" class="w-4 h-4 text-slate-400 flex-shrink-0" />
                                                    {{ doc.name }}
                                                </td>
                                                <td class="px-4 py-3 text-slate-500">{{ doc.size }}</td>
                                                <td class="px-4 py-3 text-slate-500">{{ doc.date }}</td>
                                                <td class="px-4 py-3 text-slate-500 text-xs">{{ doc.related_info }}</td>
                                                <td class="px-4 py-3">
                                                    <a :href="doc.url" target="_blank" class="inline-flex items-center gap-1 text-primary hover:underline text-xs"><Lucide icon="Eye" class="w-3.5 h-3.5" /> View</a>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </template>
                        </div>
                        <div v-if="stats.total_documents === 0" class="flex flex-col items-center py-12 text-slate-400">
                            <Lucide icon="FileX" class="w-12 h-12 mb-3" />
                            <p>No documents uploaded</p>
                        </div>
                    </div>

                </div><!-- /p-6 -->
            </div><!-- /box -->
        </div>
    </div>
</template>
