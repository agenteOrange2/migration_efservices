<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3'
import { ref, computed, reactive, watch } from 'vue'
import RazeLayout from '@/layouts/RazeLayout.vue'
import Lucide from '@/components/Base/Lucide'
import Button from '@/components/Base/Button'
import { FormInput, FormCheck } from '@/components/Base/Form'
import { Dialog } from '@/components/Base/Headless'
import Litepicker from '@/components/Base/Litepicker/Litepicker.vue'
import TomSelect from '@/components/Base/TomSelect/TomSelect.vue'

declare function route(name: string, params?: any): string

// Litepicker config – US format MM/DD/YYYY
const lpOptions = { singleMode: true, format: 'MM/DD/YYYY', autoApply: true }

// Convert backend Y-m-d to US MM/DD/YYYY for display
function toUsDate(val: string | null | undefined): string {
    if (!val) return ''
    if (/^\d{1,2}\/\d{1,2}\/\d{4}$/.test(val)) return val
    const parts = val.split('-')
    return parts.length === 3 ? `${parts[1]}/${parts[2]}/${parts[0]}` : ''
}

function todayUs(): string {
    return toUsDate(new Date().toISOString().slice(0, 10))
}

defineOptions({ layout: RazeLayout })

// ------------------------------------------------------------------
// Props
// ------------------------------------------------------------------
interface DriverBase {
    id: number
    user_id: number
    carrier_id: number
    carrier_name: string
    name: string
    middle_name: string | null
    last_name: string
    email: string
    phone: string
    date_of_birth: string | null
    status: number
    current_step: number
    application_completed: boolean
    hos_cycle_type: string
    photo_url: string | null
}

const props = defineProps<{
    driver: DriverBase | null
    stepData: Record<string, any> | null
    carriers: { id: number; name: string }[]
    selectedCarrierId: number | null
    initialStep: number | null
    vehicles: { id: number; make: string; model: string; year: number; vin: string; type: string }[]
    vehicleTypes: string[]
    usStates: Record<string, string>
    driverPositions: Record<string, string>
    referralSources: Record<string, string>
    endorsements: { id: number; code: string; name: string }[]
    equipmentTypes: Record<string, string>
}>()

const page = usePage()
const errors = computed(() => (page.props as any).errors ?? {})

const isEditMode = computed(() => !!props.driver)
const currentStep = ref(props.initialStep ?? props.driver?.current_step ?? 1)
watch(() => props.initialStep, (newStep) => { if (newStep != null) currentStep.value = newStep })
const totalSteps = 15

const steps = [
    { number: 1,  label: 'General',       icon: 'User' },
    { number: 2,  label: 'Address',       icon: 'MapPin' },
    { number: 3,  label: 'Application',   icon: 'ClipboardList' },
    { number: 4,  label: 'License',       icon: 'CreditCard' },
    { number: 5,  label: 'Medical',       icon: 'Stethoscope' },
    { number: 6,  label: 'Training',      icon: 'GraduationCap' },
    { number: 7,  label: 'Traffic',       icon: 'AlertTriangle' },
    { number: 8,  label: 'Accidents',     icon: 'Car' },
    { number: 9,  label: 'FMCSR',         icon: 'Shield' },
    { number: 10, label: 'Employment',    icon: 'Briefcase' },
    { number: 11, label: 'Policy',        icon: 'FileText' },
    { number: 12, label: 'Criminal',      icon: 'FileWarning' },
    { number: 13, label: 'W-9',           icon: 'Receipt' },
    { number: 14, label: 'Certification', icon: 'Award' },
    { number: 15, label: 'Clearinghouse', icon: 'Database' },
]

function goToStep(n: number) {
    if (isEditMode.value) {
        currentStep.value = n
    }
}

const completedStep = computed(() => props.driver?.current_step ?? 0)

// ------------------------------------------------------------------
// Step 1 – General Info
// ------------------------------------------------------------------
const step1 = reactive({
    carrier_id:    String(props.driver?.carrier_id ?? props.selectedCarrierId ?? (props.carriers[0]?.id ?? '')),
    name:          props.driver?.name ?? '',
    middle_name:   props.driver?.middle_name ?? '',
    last_name:     props.driver?.last_name ?? '',
    email:         props.driver?.email ?? '',
    phone:         props.driver?.phone ?? '',
    date_of_birth: toUsDate(props.driver?.date_of_birth),
    password:      '',
    password_confirmation: '',
    hos_cycle_type: props.driver?.hos_cycle_type ?? '70_8',
    status:         String((props.driver as any)?.status ?? 1),
    terms_accepted: (props.driver as any)?.terms_accepted ?? false,
    use_custom_dates: (props.driver as any)?.use_custom_dates ?? false,
    custom_created_at: toUsDate((props.driver as any)?.custom_created_at),
    photo: null as File | null,
    photoPreview: props.driver?.photo_url ?? null as string | null,
})

function handlePhotoChange(e: Event) {
    const file = (e.target as HTMLInputElement).files?.[0]
    if (file) {
        step1.photo = file
        step1.photoPreview = URL.createObjectURL(file)
    }
}

function submitStep1() {
    const data = new FormData()
    data.append('carrier_id',    String(step1.carrier_id))
    data.append('name',          step1.name)
    data.append('middle_name',   step1.middle_name)
    data.append('last_name',     step1.last_name)
    data.append('email',         step1.email)
    data.append('phone',         step1.phone)
    data.append('date_of_birth', step1.date_of_birth)
    if (step1.password) {
        data.append('password',              step1.password)
        data.append('password_confirmation', step1.password_confirmation)
    }
    data.append('hos_cycle_type',    step1.hos_cycle_type)
    data.append('status',            step1.status)
    data.append('terms_accepted',    step1.terms_accepted ? '1' : '0')
    data.append('use_custom_dates',  step1.use_custom_dates ? '1' : '0')
    if (step1.use_custom_dates && step1.custom_created_at) {
        data.append('custom_created_at', step1.custom_created_at)
    }
    if (step1.photo) data.append('photo', step1.photo)

    if (isEditMode.value) {
        data.append('_method', 'PUT')
        router.post(route('admin.drivers.wizard.update-step', { driver: props.driver!.id, step: 1 }), data)
    } else {
        router.post(route('admin.drivers.wizard.store'), data)
    }
}

// ------------------------------------------------------------------
// Step 2 – Address
// ------------------------------------------------------------------
const step2 = reactive({
    address_line1:    props.stepData?.step2?.primary?.address_line1 ?? '',
    address_line2:    props.stepData?.step2?.primary?.address_line2 ?? '',
    city:             props.stepData?.step2?.primary?.city ?? '',
    state:            props.stepData?.step2?.primary?.state ?? '',
    zip_code:         props.stepData?.step2?.primary?.zip_code ?? '',
    from_date:        toUsDate(props.stepData?.step2?.primary?.from_date),
    to_date:          toUsDate(props.stepData?.step2?.primary?.to_date),
    lived_three_years: props.stepData?.step2?.primary?.lived_three_years ?? false,
    previous_addresses: ((props.stepData?.step2?.previous ?? []) as any[]).map((a: any) => ({
        ...a,
        from_date: toUsDate(a.from_date),
        to_date: toUsDate(a.to_date),
    })),
})

function addPreviousAddress() {
    step2.previous_addresses.push({ address_line1: '', address_line2: '', city: '', state: '', zip_code: '', from_date: '', to_date: '' })
}
function removePreviousAddress(i: number) {
    step2.previous_addresses.splice(i, 1)
}

function submitStep2() {
    router.put(route('admin.drivers.wizard.update-step', { driver: props.driver!.id, step: 2 }), {
        address_line1:    step2.address_line1,
        address_line2:    step2.address_line2,
        city:             step2.city,
        state:            step2.state,
        zip_code:         step2.zip_code,
        from_date:        step2.from_date,
        to_date:          step2.to_date,
        lived_three_years: step2.lived_three_years,
        previous_addresses: step2.previous_addresses,
    })
}

// ------------------------------------------------------------------
// Step 3 – Application
// ------------------------------------------------------------------
const step3 = reactive({
    applying_position:        props.stepData?.step3?.applying_position ?? 'driver',
    applying_position_other:  props.stepData?.step3?.applying_position_other ?? '',
    applying_location:        props.stepData?.step3?.applying_location ?? '',
    eligible_to_work:         props.stepData?.step3?.eligible_to_work ?? true,
    can_speak_english:        props.stepData?.step3?.can_speak_english ?? true,
    has_twic_card:            props.stepData?.step3?.has_twic_card ?? false,
    twic_expiration_date:     toUsDate(props.stepData?.step3?.twic_expiration_date),
    expected_pay:             props.stepData?.step3?.expected_pay ?? '',
    how_did_hear:             props.stepData?.step3?.how_did_hear ?? 'internet',
    how_did_hear_other:       props.stepData?.step3?.how_did_hear_other ?? '',
    referral_employee_name:   props.stepData?.step3?.referral_employee_name ?? '',
    // Vehicle assignment — null = no selection yet (fresh driver, admin must choose)
    vehicle_assignment_type:  props.stepData?.step3?.vehicle_assignment_type ?? null as string | null,
    vehicle_id:               props.stepData?.step3?.vehicle_id ?? null as number | null,
    owner_name:               props.stepData?.step3?.owner_name ?? '',
    owner_phone:              props.stepData?.step3?.owner_phone ?? '',
    owner_email:              props.stepData?.step3?.owner_email ?? '',
    third_party_name:         props.stepData?.step3?.third_party_name ?? '',
    third_party_phone:        props.stepData?.step3?.third_party_phone ?? '',
    third_party_email:        props.stepData?.step3?.third_party_email ?? '',
    third_party_dba:          props.stepData?.step3?.third_party_dba ?? '',
    third_party_address:      props.stepData?.step3?.third_party_address ?? '',
    third_party_contact:      props.stepData?.step3?.third_party_contact ?? '',
    third_party_fein:         props.stepData?.step3?.third_party_fein ?? '',
})

// New vehicle modal state
const showVehicleModal = ref(false)
const newVehicle = reactive({
    make: '',
    model: '',
    year: '',
    vin: '',
    type: '',
    company_unit_number: '',
    gvwr: '',
    tire_size: '',
    fuel_type: 'Diesel',
    irp_apportioned_plate: false,
    registration_state: '',
    registration_number: '',
    registration_expiration_date: '',
    permanent_tag: false,
    location: '',
    notes: '',
    terms_accepted: false,
})
const pendingNewVehicle = ref<typeof newVehicle | null>(null)

function confirmNewVehicle() {
    pendingNewVehicle.value = { ...newVehicle }
    step3.vehicle_id = null // signal controller to create new
    showVehicleModal.value = false
}

function submitStep3() {
    if (!step3.vehicle_assignment_type) {
        alert('Please select a Vehicle Assignment Type before continuing.')
        return
    }
    const data: Record<string, any> = { ...step3 }
    if (pendingNewVehicle.value?.make) {
        const v = pendingNewVehicle.value
        data.new_vehicle_make                    = v.make
        data.new_vehicle_model                   = v.model
        data.new_vehicle_year                    = v.year
        data.new_vehicle_vin                     = v.vin
        data.new_vehicle_type                    = v.type
        data.new_vehicle_company_unit_number     = v.company_unit_number
        data.new_vehicle_gvwr                    = v.gvwr
        data.new_vehicle_tire_size               = v.tire_size
        data.new_vehicle_fuel_type               = v.fuel_type
        data.new_vehicle_irp_apportioned_plate   = v.irp_apportioned_plate ? 1 : 0
        data.new_vehicle_registration_state      = v.registration_state
        data.new_vehicle_registration_number     = v.registration_number
        data.new_vehicle_registration_expiration_date = v.registration_expiration_date
        data.new_vehicle_permanent_tag           = v.permanent_tag ? 1 : 0
        data.new_vehicle_location                = v.location
        data.new_vehicle_notes                   = v.notes
    }
    router.put(route('admin.drivers.wizard.update-step', { driver: props.driver!.id, step: 3 }), data)
}

// ------------------------------------------------------------------
// Step 4 – License
// ------------------------------------------------------------------
const step4 = reactive({
    licenses: ((props.stepData?.step4?.licenses ?? [{ license_number: '', state_of_issue: '', license_class: '', expiration_date: '', is_cdl: true, is_primary: true, endorsements: [] }]) as any[]).map((l: any) => ({
        ...l,
        expiration_date: toUsDate(l.expiration_date),
    })),
    experiences: ((props.stepData?.step4?.experiences ?? []) as any[]).length > 0
        ? (props.stepData!.step4!.experiences as any[])
        : [{ equipment_type: '', years_experience: '', miles_driven: '', requires_cdl: false }],
    license_front: null as File | null,
    license_back:  null as File | null,
})

function addLicense() {
    step4.licenses.push({ license_number: '', state_of_issue: '', license_class: '', expiration_date: '', is_cdl: false, is_primary: false, endorsements: [] })
}
function removeLicense(i: number) {
    if (step4.licenses.length > 1) step4.licenses.splice(i, 1)
}
function addExperience() {
    step4.experiences.push({ equipment_type: '', years_experience: '', miles_driven: '', requires_cdl: false })
}
function removeExperience(i: number) {
    if (step4.experiences.length > 1) step4.experiences.splice(i, 1)
}

const licFrontPreview = ref<string | null>(null)
const licBackPreview  = ref<string | null>(null)

function onLicFront(e: Event) {
    const file = (e.target as HTMLInputElement).files?.[0] ?? null
    licFrontPreview.value = file ? URL.createObjectURL(file) : null
    step4.license_front = file
}
function onLicBack(e: Event) {
    const file = (e.target as HTMLInputElement).files?.[0] ?? null
    licBackPreview.value = file ? URL.createObjectURL(file) : null
    step4.license_back = file
}

function submitStep4() {
    const data = new FormData()
    const licenseFields = ['id', 'license_number', 'state_of_issue', 'license_class', 'expiration_date', 'is_cdl', 'is_primary']
    step4.licenses.forEach((l, i) => {
        licenseFields.forEach(k => {
            if (k in l) {
                const v = (l as any)[k]
                data.append(`licenses[${i}][${k}]`, typeof v === 'boolean' ? (v ? '1' : '0') : String(v ?? ''))
            }
        })
        if (Array.isArray(l.endorsements)) {
            l.endorsements.forEach((eid: number) => data.append(`licenses[${i}][endorsements][]`, String(eid)))
        }
    })
    if (step4.license_front) data.append('license_front', step4.license_front)
    if (step4.license_back)  data.append('license_back',  step4.license_back)
    step4.experiences.forEach((e, i) => {
        if (!e.equipment_type) return
        data.append(`experiences[${i}][equipment_type]`, String(e.equipment_type))
        data.append(`experiences[${i}][years_experience]`, String(parseInt(e.years_experience) || 0))
        data.append(`experiences[${i}][miles_driven]`, String(parseInt(e.miles_driven) || 0))
        data.append(`experiences[${i}][requires_cdl]`, e.requires_cdl ? '1' : '0')
    })
    data.append('_method', 'PUT')
    router.post(route('admin.drivers.wizard.update-step', { driver: props.driver!.id, step: 4 }), data)
}

// ------------------------------------------------------------------
// Step 5 – Medical
// ------------------------------------------------------------------
const step5 = reactive({
    // Social Security / Employment
    hire_date:              toUsDate(props.stepData?.step5?.hire_date),
    location:               props.stepData?.step5?.location ?? '',
    // Social Security
    social_security_number: props.stepData?.step5?.social_security_number ?? '',
    social_security_card:   null as File | null,
    ss_card_url:            props.stepData?.step5?.ss_card_url ?? null as string | null,
    // Medical Examiner
    medical_examiner_name:            props.stepData?.step5?.medical_examiner_name ?? '',
    medical_examiner_registry_number: props.stepData?.step5?.medical_examiner_registry_number ?? '',
    medical_card_expiration_date:     toUsDate(props.stepData?.step5?.medical_card_expiration_date),
    medical_card:     null as File | null,
    medical_card_url: props.stepData?.step5?.medical_card_url ?? null as string | null,
})

function submitStep5() {
    const data = new FormData()
    data.append('hire_date',                        step5.hire_date)
    data.append('location',                         step5.location)
    data.append('social_security_number',           step5.social_security_number)
    data.append('medical_examiner_name',            step5.medical_examiner_name)
    data.append('medical_examiner_registry_number', step5.medical_examiner_registry_number)
    data.append('medical_card_expiration_date',     step5.medical_card_expiration_date)
    if (step5.medical_card)         data.append('medical_card',         step5.medical_card)
    if (step5.social_security_card) data.append('social_security_card', step5.social_security_card)
    data.append('_method', 'PUT')
    router.post(route('admin.drivers.wizard.update-step', { driver: props.driver!.id, step: 5 }), data)
}

// ------------------------------------------------------------------
// Step 6 – Training
// ------------------------------------------------------------------
const step6 = reactive({
    schools: ((props.stepData?.step6?.schools ?? []) as any[]).map((s: any) => ({
        ...s,
        date_start: toUsDate(s.date_start),
        date_end: toUsDate(s.date_end),
    })),
    courses: ((props.stepData?.step6?.courses ?? []) as any[]).map((c: any) => ({
        ...c,
        certification_date: toUsDate(c.certification_date),
        expiration_date: toUsDate(c.expiration_date),
    })),
})

function addSchool() {
    step6.schools.push({ school_name: '', city: '', state: '', graduated: false, date_start: '', date_end: '', subject_to_safety_regulations: false, performed_safety_functions: false })
}
function removeSchool(i: number) {
    step6.schools.splice(i, 1)
}
function addCourse() {
    step6.courses.push({ organization_name: '', city: '', state: '', certification_date: '', expiration_date: '', experience: '', years_experience: '' })
}
function removeCourse(i: number) {
    step6.courses.splice(i, 1)
}

function submitStep6() {
    router.put(route('admin.drivers.wizard.update-step', { driver: props.driver!.id, step: 6 }), { schools: step6.schools, courses: step6.courses })
}

// ------------------------------------------------------------------
// Step 7 – Traffic
// ------------------------------------------------------------------
const step7 = reactive({
    no_traffic_convictions: props.stepData?.step7?.no_traffic_convictions ?? false,
    convictions: ((props.stepData?.step7?.convictions ?? []) as any[]).map((c: any) => ({
        ...c, conviction_date: toUsDate(c.conviction_date),
    })),
})

function addConviction() {
    step7.convictions.push({ conviction_date: '', location: '', charge: '', penalty: '' })
}
function removeConviction(i: number) {
    step7.convictions.splice(i, 1)
}

function submitStep7() {
    router.put(route('admin.drivers.wizard.update-step', { driver: props.driver!.id, step: 7 }), {
        no_traffic_convictions: step7.no_traffic_convictions,
        convictions: step7.convictions,
    })
}

// ------------------------------------------------------------------
// Step 8 – Accidents
// ------------------------------------------------------------------
const step8 = reactive({
    no_accidents: props.stepData?.step8?.no_accidents ?? false,
    accidents: ((props.stepData?.step8?.accidents ?? []) as any[]).map((a: any) => ({
        ...a, accident_date: toUsDate(a.accident_date),
    })),
})

function addAccident() {
    step8.accidents.push({ accident_date: '', nature_of_accident: '', number_of_fatalities: 0, number_of_injuries: 0, comments: '' })
}
function removeAccident(i: number) {
    step8.accidents.splice(i, 1)
}

function submitStep8() {
    router.put(route('admin.drivers.wizard.update-step', { driver: props.driver!.id, step: 8 }), {
        no_accidents: step8.no_accidents,
        accidents: step8.accidents,
    })
}

// ------------------------------------------------------------------
// Step 9 – FMCSR
// ------------------------------------------------------------------
const step9 = reactive({
    is_disqualified:        props.stepData?.step9?.is_disqualified        ?? false,
    is_license_suspended:   props.stepData?.step9?.is_license_suspended   ?? false,
    is_license_denied:      props.stepData?.step9?.is_license_denied      ?? false,
    has_positive_drug_test: props.stepData?.step9?.has_positive_drug_test ?? false,
    consent_to_release:     props.stepData?.step9?.consent_to_release     ?? false,
    has_duty_offenses:      props.stepData?.step9?.has_duty_offenses      ?? false,
    consent_driving_record: props.stepData?.step9?.consent_driving_record ?? false,
})

function submitStep9() {
    router.put(route('admin.drivers.wizard.update-step', { driver: props.driver!.id, step: 9 }), { ...step9 })
}

// ------------------------------------------------------------------
// Step 10 – Employment
// ------------------------------------------------------------------
const step10 = reactive({
    companies: ((props.stepData?.step10?.companies ?? []) as any[]).map((c: any) => ({
        ...c,
        employed_from: toUsDate(c.employed_from),
        employed_to: toUsDate(c.employed_to),
    })),
    unemployment_periods: ((props.stepData?.step10?.unemployment_periods ?? []) as any[]).map((u: any) => ({
        ...u,
        start_date: toUsDate(u.start_date),
        end_date: toUsDate(u.end_date),
    })),
})

function addCompany() {
    step10.companies.push({ company_name: '', address: '', city: '', state: '', zip: '', phone: '', email: '', employed_from: '', employed_to: '', positions_held: '', reason_for_leaving: '', subject_to_fmcsr: false, safety_sensitive_function: false })
}
function removeCompany(i: number) {
    step10.companies.splice(i, 1)
}
function addUnemployment() {
    step10.unemployment_periods.push({ start_date: '', end_date: '', comments: '' })
}
function removeUnemployment(i: number) {
    step10.unemployment_periods.splice(i, 1)
}

function submitStep10() {
    router.put(route('admin.drivers.wizard.update-step', { driver: props.driver!.id, step: 10 }), {
        companies:            step10.companies,
        unemployment_periods: step10.unemployment_periods,
    })
}

// ------------------------------------------------------------------
// Step 11 – Policy
// ------------------------------------------------------------------
const step11 = reactive({
    consent_all_policies_attached: props.stepData?.step11?.consent_all_policies_attached ?? false,
    substance_testing_consent:     props.stepData?.step11?.substance_testing_consent     ?? false,
    authorization_consent:         props.stepData?.step11?.authorization_consent         ?? false,
    fmcsa_clearinghouse_consent:   props.stepData?.step11?.fmcsa_clearinghouse_consent   ?? false,
})

function submitStep11() {
    router.put(route('admin.drivers.wizard.update-step', { driver: props.driver!.id, step: 11 }), { ...step11 })
}

// ------------------------------------------------------------------
// Step 12 – Criminal
// ------------------------------------------------------------------
const step12 = reactive({
    has_criminal_charges:    props.stepData?.step12?.has_criminal_charges    ?? false,
    has_felony_conviction:   props.stepData?.step12?.has_felony_conviction   ?? false,
    has_minister_permit:     props.stepData?.step12?.has_minister_permit     ?? false,
    fcra_consent:            props.stepData?.step12?.fcra_consent            ?? false,
    background_info_consent: props.stepData?.step12?.background_info_consent ?? false,
})

function submitStep12() {
    router.put(route('admin.drivers.wizard.update-step', { driver: props.driver!.id, step: 12 }), { ...step12 })
}

// ------------------------------------------------------------------
// Step 13 – W-9
// ------------------------------------------------------------------
const step13 = reactive({
    name:               props.stepData?.step13?.name               ?? (props.driver?.name ?? ''),
    business_name:      props.stepData?.step13?.business_name      ?? '',
    tax_classification: props.stepData?.step13?.tax_classification ?? 'individual',
    tin_type:           props.stepData?.step13?.tin_type           ?? 'ssn',
    tin:                props.stepData?.step13?.tin                ?? '',
    signature:          props.stepData?.step13?.signature          ?? '',
    signed_date:        toUsDate(props.stepData?.step13?.signed_date) || todayUs(),
    address:            props.stepData?.step13?.address            ?? '',
    city:               props.stepData?.step13?.city               ?? '',
    state:              props.stepData?.step13?.state              ?? '',
    zip_code:           props.stepData?.step13?.zip_code           ?? '',
})

function submitStep13() {
    router.put(route('admin.drivers.wizard.update-step', { driver: props.driver!.id, step: 13 }), { ...step13 })
}

// ------------------------------------------------------------------
// Step 14 – Certification
// ------------------------------------------------------------------
const step14 = reactive({
    is_accepted: props.stepData?.step14?.is_accepted ?? false,
    signature:   props.stepData?.step14?.signature   ?? '',
    signed_at:   toUsDate(props.stepData?.step14?.signed_at) || todayUs(),
})

function submitStep14() {
    router.put(route('admin.drivers.wizard.update-step', { driver: props.driver!.id, step: 14 }), { ...step14 })
}

// ------------------------------------------------------------------
// Step 15 – Clearinghouse / Finalize
// ------------------------------------------------------------------
const step15 = reactive({
    clearinghouse_consent:    false,
    clearinghouse_query_date: '',
    clearinghouse_result:     '',
})

function submitStep15() {
    router.put(route('admin.drivers.wizard.update-step', { driver: props.driver!.id, step: 15 }), { ...step15 })
}

</script>

<template>
    <div>
        <Head :title="isEditMode ? 'Edit Driver – Wizard' : 'Register Driver – Wizard'" />
        <div class="p-5">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-semibold text-slate-800 dark:text-slate-100">
                    {{ isEditMode ? 'Edit Driver Registration' : 'Register New Driver' }}
                </h1>
                <p v-if="isEditMode" class="text-sm text-slate-500 mt-1">
                    {{ driver!.name }} {{ driver!.last_name }} &bull; {{ driver!.carrier_name }}
                </p>
            </div>
            <a :href="route('admin.drivers.index')" class="flex items-center gap-1 text-slate-500 hover:text-slate-700 text-sm">
                <Lucide icon="ArrowLeft" class="w-4 h-4" />
                Back to Drivers
            </a>
        </div>

        <!-- Progress bar -->
        <div v-if="isEditMode" class="mb-6">
            <div class="flex justify-between text-xs text-slate-500 mb-1">
                <span>Step {{ currentStep }} of {{ totalSteps }}</span>
                <span>{{ Math.round((completedStep / totalSteps) * 100) }}% completed</span>
            </div>
            <div class="w-full bg-slate-200 dark:bg-slate-700 rounded-full h-2">
                <div class="bg-primary h-2 rounded-full transition-all" :style="{ width: `${(completedStep / totalSteps) * 100}%` }"></div>
            </div>
        </div>

        <!-- Step Tabs -->
        <div class="overflow-x-auto mb-6">
            <div class="flex gap-1 min-w-max border-b border-slate-200 dark:border-slate-700 pb-0">
                <button
                    v-for="step in steps"
                    :key="step.number"
                    @click="goToStep(step.number)"
                    :disabled="!isEditMode && step.number > 1"
                    class="flex items-center gap-1.5 px-3 py-2.5 text-xs font-medium rounded-t-lg transition-colors"
                    :class="{
                        'bg-white dark:bg-darkmode-600 border border-b-white dark:border-b-darkmode-600 border-slate-200 dark:border-slate-600 text-primary -mb-px': currentStep === step.number,
                        'text-slate-500 hover:text-slate-700 hover:bg-slate-50': currentStep !== step.number && (isEditMode || step.number === 1),
                        'text-slate-300 cursor-not-allowed': !isEditMode && step.number > 1,
                    }"
                >
                    <Lucide
                        :icon="(step.number <= completedStep ? 'CheckCircle' : step.icon) as any"
                        class="w-3.5 h-3.5"
                        :class="step.number <= completedStep && currentStep !== step.number ? 'text-success' : ''"
                    />
                    {{ step.label }}
                </button>
            </div>
        </div>

        <!-- Step Panels -->
        <div class="bg-white dark:bg-darkmode-600 rounded-xl border border-slate-200 dark:border-slate-600 p-6">

            <!-- ====================================================
                 STEP 1 – General Info
                 ==================================================== -->
            <div v-if="currentStep === 1">
                <h2 class="text-lg font-semibold mb-5">General Information</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium mb-1">Carrier <span class="text-danger">*</span></label>
                        <TomSelect v-model="step1.carrier_id" :disabled="!!props.selectedCarrierId && !isEditMode">
                            <option v-for="c in props.carriers" :key="c.id" :value="c.id">{{ c.name }}</option>
                        </TomSelect>
                        <p v-if="props.selectedCarrierId && !isEditMode" class="text-xs text-slate-400 mt-1">
                            Carrier pre-selected from carrier profile
                        </p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">First Name <span class="text-danger">*</span></label>
                        <FormInput v-model="step1.name" placeholder="First name" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Middle Name</label>
                        <FormInput v-model="step1.middle_name" placeholder="Middle name" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Last Name <span class="text-danger">*</span></label>
                        <FormInput v-model="step1.last_name" placeholder="Last name" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Email <span class="text-danger">*</span></label>
                        <FormInput v-model="step1.email" type="email" placeholder="email@example.com" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Phone <span class="text-danger">*</span></label>
                        <FormInput v-model="step1.phone" type="tel" placeholder="(555) 000-0000" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Date of Birth <span class="text-danger">*</span></label>
                        <Litepicker v-model="step1.date_of_birth" :options="lpOptions" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">{{ isEditMode ? 'New Password' : 'Password' }} {{ !isEditMode ? '*' : '' }}</label>
                        <FormInput v-model="step1.password" type="password" placeholder="Min. 8 characters" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Confirm Password</label>
                        <FormInput v-model="step1.password_confirmation" type="password" placeholder="Repeat password" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">HOS Cycle</label>
                        <TomSelect v-model="step1.hos_cycle_type">
                            <option value="70_8">70 hours / 8 days</option>
                            <option value="60_7">60 hours / 7 days</option>
                        </TomSelect>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Driver Status</label>
                        <TomSelect v-model="step1.status">
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                            <option value="2">Pending</option>
                        </TomSelect>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Profile Photo</label>
                        <input type="file" accept="image/*" @change="handlePhotoChange" class="w-full text-sm" />
                        <img v-if="step1.photoPreview" :src="step1.photoPreview" class="mt-2 h-20 w-20 rounded-full object-cover border" />
                    </div>
                </div>

                <!-- Terms & Custom Dates -->
                <div class="mt-5 space-y-4">
                    <div class="flex items-start gap-3 p-4 border border-slate-200 rounded-lg" :class="errors.terms_accepted ? 'border-danger' : ''">
                        <FormCheck.Input v-model="step1.terms_accepted" type="checkbox" class="mt-0.5" />
                        <div>
                            <p class="text-sm font-medium">I accept the terms and conditions <span class="text-danger">*</span></p>
                            <p class="text-xs text-slate-500 mt-0.5">Driver acknowledges and accepts all company policies</p>
                        </div>
                    </div>
                    <p v-if="errors.terms_accepted" class="text-xs text-danger -mt-2">{{ errors.terms_accepted }}</p>

                    <div class="border border-slate-200 rounded-lg p-4">
                        <div class="flex items-start gap-3">
                            <FormCheck.Input v-model="step1.use_custom_dates" type="checkbox" class="mt-0.5" />
                            <div>
                                <p class="text-sm font-medium">Custom Dates <span class="text-slate-400 font-normal">(Only for Historical Drivers)</span></p>
                                <p class="text-xs text-slate-500 mt-0.5">Enable this option if you are registering a historical driver with specific registration dates.</p>
                            </div>
                        </div>
                        <div v-if="step1.use_custom_dates" class="mt-3 grid grid-cols-1 gap-3">
                            <div>
                                <label class="block text-xs font-medium mb-1">Custom Registration Date</label>
                                <Litepicker v-model="step1.custom_created_at" :options="lpOptions" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Validation errors summary -->
                <div v-if="Object.keys(errors).length" class="mt-4 p-3 bg-danger/10 border border-danger/30 rounded-lg text-sm text-danger">
                    <p class="font-medium mb-1">Please fix the following errors:</p>
                    <ul class="list-disc list-inside space-y-0.5">
                        <li v-for="(msg, field) in errors" :key="field">{{ msg }}</li>
                    </ul>
                </div>

                <div class="flex justify-end mt-6">
                    <Button variant="primary" @click="submitStep1">
                        {{ isEditMode ? 'Save & Continue' : 'Create Driver' }}
                        <Lucide icon="ChevronRight" class="w-4 h-4 ml-1" />
                    </Button>
                </div>
            </div>

            <!-- ====================================================
                 STEP 2 – Address
                 ==================================================== -->
            <div v-else-if="currentStep === 2">
                <h2 class="text-lg font-semibold mb-5">Address Information</h2>
                <h3 class="text-sm font-semibold text-slate-600 mb-3">Current Address</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium mb-1">Address Line 1 <span class="text-danger">*</span></label>
                        <FormInput v-model="step2.address_line1" placeholder="Street address" />
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium mb-1">Address Line 2</label>
                        <FormInput v-model="step2.address_line2" placeholder="Apt, suite, etc." />
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">City <span class="text-danger">*</span></label>
                        <FormInput v-model="step2.city" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">State <span class="text-danger">*</span></label>
                        <TomSelect v-model="step2.state">
                            <option value="">Select state</option>
                            <option v-for="(name, code) in props.usStates" :key="code" :value="code">{{ name }}</option>
                        </TomSelect>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">ZIP Code <span class="text-danger">*</span></label>
                        <FormInput v-model="step2.zip_code" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">From Date <span class="text-danger">*</span></label>
                        <Litepicker v-model="step2.from_date" :options="lpOptions" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">To Date</label>
                        <Litepicker v-model="step2.to_date" :options="lpOptions" />
                    </div>
                    <div class="md:col-span-2">
                        <FormCheck>
                            <FormCheck.Input v-model="step2.lived_three_years" type="checkbox" />
                            <FormCheck.Label>I have lived at this address for 3+ years</FormCheck.Label>
                        </FormCheck>
                    </div>
                </div>

                <!-- Previous Addresses -->
                <div v-if="!step2.lived_three_years">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-semibold text-slate-600">Previous Addresses</h3>
                        <Button size="sm" variant="outline-secondary" @click="addPreviousAddress">
                            <Lucide icon="Plus" class="w-3.5 h-3.5 mr-1" /> Add
                        </Button>
                    </div>
                    <div v-for="(addr, i) in step2.previous_addresses" :key="i" class="border border-slate-200 rounded-lg p-4 mb-3">
                        <div class="flex justify-between mb-2">
                            <span class="text-xs font-medium text-slate-500">Address #{{ i + 1 }}</span>
                            <button @click="removePreviousAddress(i)" class="text-danger hover:opacity-70">
                                <Lucide icon="X" class="w-4 h-4" />
                            </button>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div class="md:col-span-2">
                                <FormInput v-model="addr.address_line1" placeholder="Street address" />
                            </div>
                            <FormInput v-model="addr.city" placeholder="City" />
                            <TomSelect v-model="addr.state">
                                <option value="">State</option>
                                <option v-for="(name, code) in props.usStates" :key="code" :value="code">{{ name }}</option>
                            </TomSelect>
                            <FormInput v-model="addr.zip_code" placeholder="ZIP" />
                            <Litepicker v-model="addr.from_date" :options="lpOptions" />
                            <Litepicker v-model="addr.to_date" :options="lpOptions" />
                        </div>
                    </div>
                </div>

                <div class="flex justify-between mt-6">
                    <Button variant="outline-secondary" @click="currentStep = 1">
                        <Lucide icon="ChevronLeft" class="w-4 h-4 mr-1" /> Previous
                    </Button>
                    <Button variant="primary" @click="submitStep2">
                        Save & Continue <Lucide icon="ChevronRight" class="w-4 h-4 ml-1" />
                    </Button>
                </div>
            </div>

            <!-- ====================================================
                 STEP 3 – Application
                 ==================================================== -->
            <div v-else-if="currentStep === 3">
                <h2 class="text-lg font-semibold mb-5">Application Details</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Position Applied For <span class="text-danger">*</span></label>
                        <TomSelect v-model="step3.applying_position">
                            <option v-for="(label, key) in props.driverPositions" :key="key" :value="key">{{ label }}</option>
                        </TomSelect>
                    </div>
                    <div v-if="step3.applying_position === 'other'">
                        <label class="block text-sm font-medium mb-1">Position Description</label>
                        <FormInput v-model="step3.applying_position_other" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Applying Location</label>
                        <FormInput v-model="step3.applying_location" placeholder="City, State" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Expected Pay ($/hr)</label>
                        <FormInput v-model="step3.expected_pay" type="number" step="0.01" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">How Did You Hear?</label>
                        <TomSelect v-model="step3.how_did_hear">
                            <option value="internet">Internet</option>
                            <option v-for="(label, key) in props.referralSources" :key="key" :value="key">{{ label }}</option>
                        </TomSelect>
                    </div>
                    <div v-if="step3.how_did_hear === 'employee_referral'">
                        <label class="block text-sm font-medium mb-1">Referral Employee Name</label>
                        <FormInput v-model="step3.referral_employee_name" />
                    </div>
                    <div v-if="step3.how_did_hear === 'other'">
                        <label class="block text-sm font-medium mb-1">Other Source</label>
                        <FormInput v-model="step3.how_did_hear_other" />
                    </div>
                </div>

                <!-- Vehicle Assignment Type -->
                <div class="mt-6">
                    <h3 class="text-base font-semibold mb-1">Vehicle Assignment Type <span class="text-danger">*</span></h3>
                    <p class="text-sm text-slate-500 mb-3">Select the vehicle arrangement for this driver:</p>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <label
                            v-for="opt in [
                                { value: 'owner_operator', label: 'Owner Operator',  desc: 'Driver owns their vehicle' },
                                { value: 'third_party',    label: 'Third Party',     desc: 'Vehicle from a third-party company' },
                                { value: 'company',        label: 'Company Driver',  desc: 'Uses a company-owned vehicle' },
                            ]"
                            :key="opt.value"
                            class="flex items-start gap-3 p-4 border rounded-lg cursor-pointer transition"
                            :class="step3.vehicle_assignment_type === opt.value
                                ? 'border-primary bg-primary/5'
                                : (errors.vehicle_assignment_type && !step3.vehicle_assignment_type)
                                    ? 'border-danger/50 hover:border-danger'
                                    : 'border-slate-200 hover:border-slate-300'"
                        >
                            <input
                                type="radio"
                                :value="opt.value"
                                v-model="step3.vehicle_assignment_type"
                                class="mt-0.5 accent-primary"
                            />
                            <div>
                                <div class="text-sm font-medium">{{ opt.label }}</div>
                                <div class="text-xs text-slate-500">{{ opt.desc }}</div>
                            </div>
                        </label>
                    </div>
                    <p v-if="errors.vehicle_assignment_type" class="text-xs text-danger mt-1">{{ errors.vehicle_assignment_type }}</p>
                    <p v-else-if="!step3.vehicle_assignment_type" class="text-xs text-slate-400 mt-1">A selection is required to continue.</p>
                </div>

                <!-- Owner Operator Info -->
                <div v-if="step3.vehicle_assignment_type === 'owner_operator'" class="mt-4 p-4 border border-slate-200 rounded-lg">
                    <h4 class="text-sm font-semibold text-primary mb-3">Owner Operator Information</h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Owner Name <span class="text-danger">*</span></label>
                            <FormInput v-model="step3.owner_name" placeholder="Full name" :class="errors.owner_name ? 'border-danger' : ''" />
                            <p v-if="errors.owner_name" class="text-xs text-danger mt-1">{{ errors.owner_name }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Phone Number</label>
                            <FormInput v-model="step3.owner_phone" placeholder="Phone" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Email</label>
                            <FormInput v-model="step3.owner_email" type="email" placeholder="Email" :class="errors.owner_email ? 'border-danger' : ''" />
                            <p v-if="errors.owner_email" class="text-xs text-danger mt-1">{{ errors.owner_email }}</p>
                        </div>
                    </div>
                </div>

                <!-- Third Party Info -->
                <div v-if="step3.vehicle_assignment_type === 'third_party'" class="mt-4 p-4 border border-slate-200 rounded-lg">
                    <h4 class="text-sm font-semibold text-primary mb-3">Third Party Company Information</h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Company Name <span class="text-danger">*</span></label>
                            <FormInput v-model="step3.third_party_name" placeholder="Company name" :class="errors.third_party_name ? 'border-danger' : ''" />
                            <p v-if="errors.third_party_name" class="text-xs text-danger mt-1">{{ errors.third_party_name }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Phone</label>
                            <FormInput v-model="step3.third_party_phone" placeholder="Phone" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Email</label>
                            <FormInput v-model="step3.third_party_email" type="email" placeholder="Email" :class="errors.third_party_email ? 'border-danger' : ''" />
                            <p v-if="errors.third_party_email" class="text-xs text-danger mt-1">{{ errors.third_party_email }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">DBA</label>
                            <FormInput v-model="step3.third_party_dba" placeholder="Doing business as" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Address</label>
                            <FormInput v-model="step3.third_party_address" placeholder="Address" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Contact Person</label>
                            <FormInput v-model="step3.third_party_contact" placeholder="Contact name" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">FEIN</label>
                            <FormInput v-model="step3.third_party_fein" placeholder="Federal EIN" />
                        </div>
                    </div>
                </div>

                <!-- Vehicle Information (owner_operator and third_party only) -->
                <div v-if="step3.vehicle_assignment_type !== 'company'" class="mt-6">
                    <h3 class="text-base font-semibold mb-3">Vehicle Information <span class="text-xs text-slate-400 font-normal">(vehicles registered by this driver)</span></h3>

                    <!-- Pending new vehicle preview -->
                    <div v-if="pendingNewVehicle" class="mb-3 flex items-center gap-2 p-3 bg-success/10 border border-success/30 rounded-lg text-sm">
                        <Lucide icon="CheckCircle" class="w-4 h-4 text-success shrink-0" />
                        <span>New vehicle to register: <strong>{{ pendingNewVehicle.year }} {{ pendingNewVehicle.make }} {{ pendingNewVehicle.model }}</strong> — VIN: {{ pendingNewVehicle.vin || 'N/A' }}{{ pendingNewVehicle.company_unit_number ? ` — Unit: ${pendingNewVehicle.company_unit_number}` : '' }}</span>
                        <button type="button" class="ml-auto text-slate-400 hover:text-danger" @click="pendingNewVehicle = null">
                            <Lucide icon="X" class="w-4 h-4" />
                        </button>
                    </div>

                    <!-- Existing vehicles table -->
                    <div v-if="props.vehicles.length" class="border border-slate-200 rounded-lg overflow-hidden">
                        <div class="px-4 py-2 bg-slate-50 text-xs font-semibold text-slate-500 uppercase tracking-wide">Existing Vehicles</div>
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-slate-200 text-left text-xs text-slate-500 uppercase">
                                    <th class="px-4 py-2">Make</th>
                                    <th class="px-4 py-2">Model</th>
                                    <th class="px-4 py-2">Year</th>
                                    <th class="px-4 py-2">VIN</th>
                                    <th class="px-4 py-2">Type</th>
                                    <th class="px-4 py-2">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="v in props.vehicles"
                                    :key="v.id"
                                    class="border-b border-slate-100 last:border-0 transition"
                                    :class="step3.vehicle_id === v.id ? 'bg-primary/5' : 'hover:bg-slate-50'"
                                >
                                    <td class="px-4 py-2 font-medium">{{ v.make }}</td>
                                    <td class="px-4 py-2">{{ v.model }}</td>
                                    <td class="px-4 py-2">{{ v.year }}</td>
                                    <td class="px-4 py-2 font-mono text-xs">{{ v.vin }}</td>
                                    <td class="px-4 py-2">{{ v.type }}</td>
                                    <td class="px-4 py-2">
                                        <Button
                                            v-if="step3.vehicle_id !== v.id"
                                            variant="primary"
                                            size="sm"
                                            @click="step3.vehicle_id = v.id; pendingNewVehicle = null"
                                        >Select</Button>
                                        <Button
                                            v-else
                                            variant="outline-secondary"
                                            size="sm"
                                            @click="step3.vehicle_id = null"
                                        >
                                            <Lucide icon="Check" class="w-3 h-3 mr-1 text-success" /> Selected
                                        </Button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-else class="text-sm text-slate-400 italic mb-3">No vehicles registered for this carrier yet.</div>

                    <Button variant="outline-secondary" class="mt-3" @click="showVehicleModal = true">
                        <Lucide icon="Plus" class="w-4 h-4 mr-1" /> Register New Vehicle
                    </Button>
                </div>

                <!-- Other fields -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6">
                    <div class="md:col-span-2 space-y-3">
                        <!-- eligible_to_work: REQUIRED -->
                        <div
                            class="flex items-start gap-3 p-3 border rounded-lg"
                            :class="errors.eligible_to_work ? 'border-danger bg-danger/5' : 'border-slate-200'"
                        >
                            <FormCheck.Input v-model="step3.eligible_to_work" type="checkbox" class="mt-0.5" />
                            <div>
                                <span class="text-sm font-medium">Eligible to work in the US <span class="text-danger">*</span></span>
                                <p v-if="errors.eligible_to_work" class="text-xs text-danger mt-0.5">{{ errors.eligible_to_work }}</p>
                                <p v-else class="text-xs text-slate-400">Required — applicant must be legally authorized to work in the United States.</p>
                            </div>
                        </div>
                        <!-- Optional checkboxes -->
                        <div class="flex flex-wrap gap-6">
                            <FormCheck>
                                <FormCheck.Input v-model="step3.can_speak_english" type="checkbox" />
                                <FormCheck.Label>Can speak English</FormCheck.Label>
                            </FormCheck>
                            <FormCheck>
                                <FormCheck.Input v-model="step3.has_twic_card" type="checkbox" />
                                <FormCheck.Label>Has TWIC Card</FormCheck.Label>
                            </FormCheck>
                        </div>
                    </div>
                    <div v-if="step3.has_twic_card">
                        <label class="block text-sm font-medium mb-1">TWIC Expiration Date</label>
                        <Litepicker v-model="step3.twic_expiration_date" :options="lpOptions" />
                    </div>
                </div>

                <div class="flex justify-between mt-6">
                    <Button variant="outline-secondary" @click="currentStep = 2">
                        <Lucide icon="ChevronLeft" class="w-4 h-4 mr-1" /> Previous
                    </Button>
                    <Button variant="primary" @click="submitStep3">
                        Save & Continue <Lucide icon="ChevronRight" class="w-4 h-4 ml-1" />
                    </Button>
                </div>

                <!-- Register New Vehicle Modal -->
                <Dialog :open="showVehicleModal" @close="showVehicleModal = false" size="xl">
                <Dialog.Panel>
                    <div class="p-5 max-h-[85vh] overflow-y-auto">
                        <div class="flex items-center gap-3 mb-5">
                            <div class="flex items-center justify-center w-10 h-10 rounded-full bg-primary/10">
                                <Lucide icon="Truck" class="w-5 h-5 text-primary" />
                            </div>
                            <div>
                                <h3 class="text-base font-semibold">Register New Vehicle</h3>
                                <p class="text-slate-500 text-sm">Fill in the vehicle details — type shown based on assignment type</p>
                            </div>
                        </div>

                        <!-- Section: Basic Info -->
                        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3">Vehicle Information</p>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-5">
                            <div>
                                <label class="block text-sm font-medium mb-1">Make <span class="text-danger">*</span></label>
                                <FormInput v-model="newVehicle.make" placeholder="e.g. Freightliner" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">Model <span class="text-danger">*</span></label>
                                <FormInput v-model="newVehicle.model" placeholder="e.g. Cascadia" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">Year</label>
                                <FormInput v-model="newVehicle.year" type="number" placeholder="e.g. 2022" min="1900" :max="new Date().getFullYear() + 1" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">VIN</label>
                                <FormInput v-model="newVehicle.vin" placeholder="Vehicle identification number" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">Company Unit #</label>
                                <FormInput v-model="newVehicle.company_unit_number" placeholder="e.g. U-101" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">Type</label>
                                <TomSelect v-model="newVehicle.type">
                                    <option value="">Select type...</option>
                                    <option v-for="t in props.vehicleTypes" :key="t" :value="t">{{ t }}</option>
                                </TomSelect>
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">GVWR</label>
                                <FormInput v-model="newVehicle.gvwr" placeholder="e.g. 80000 lbs" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">Tire Size</label>
                                <FormInput v-model="newVehicle.tire_size" placeholder="e.g. 11R22.5" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">Fuel Type</label>
                                <TomSelect v-model="newVehicle.fuel_type">
                                    <option value="Diesel">Diesel</option>
                                    <option value="Gasoline">Gasoline</option>
                                    <option value="Electric">Electric</option>
                                    <option value="Hybrid">Hybrid</option>
                                    <option value="Natural Gas">Natural Gas</option>
                                    <option value="Propane">Propane</option>
                                </TomSelect>
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">Location</label>
                                <FormInput v-model="newVehicle.location" placeholder="e.g. Houston TX" />
                            </div>
                        </div>

                        <!-- Section: Registration -->
                        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3">Registration</p>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-5">
                            <div>
                                <label class="block text-sm font-medium mb-1">Registration State</label>
                                <TomSelect v-model="newVehicle.registration_state">
                                    <option value="">Select state...</option>
                                    <option v-for="(name, code) in props.usStates" :key="code" :value="code">{{ name }}</option>
                                </TomSelect>
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">Registration Number</label>
                                <FormInput v-model="newVehicle.registration_number" placeholder="Plate / Tag number" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">Registration Expiration</label>
                                <Litepicker v-model="newVehicle.registration_expiration_date" :options="lpOptions" class="w-full" />
                            </div>
                            <div class="flex items-center gap-6 md:col-span-3">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <FormCheck.Input type="checkbox" v-model="newVehicle.permanent_tag" />
                                    <span class="text-sm">Permanent Tag</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <FormCheck.Input type="checkbox" v-model="newVehicle.irp_apportioned_plate" />
                                    <span class="text-sm">IRP Apportioned Plate</span>
                                </label>
                            </div>
                        </div>

                        <!-- Section: Notes (company type) -->
                        <div v-if="step3.vehicle_assignment_type === 'company'" class="mb-5">
                            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3">Company Driver Information</p>
                            <div>
                                <label class="block text-sm font-medium mb-1">Notes</label>
                                <textarea v-model="newVehicle.notes" rows="3" class="w-full border border-slate-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-primary" placeholder="Additional notes..."></textarea>
                            </div>
                        </div>

                        <!-- Section: Notes (other types) -->
                        <div v-else class="mb-5">
                            <label class="block text-sm font-medium mb-1">Notes</label>
                            <textarea v-model="newVehicle.notes" rows="2" class="w-full border border-slate-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-primary" placeholder="Additional notes..."></textarea>
                        </div>

                        <!-- Terms (owner_operator only) -->
                        <div v-if="step3.vehicle_assignment_type === 'owner_operator'" class="mb-5 p-3 bg-slate-50 border border-slate-200 rounded-lg">
                            <label class="flex items-start gap-2 cursor-pointer">
                                <FormCheck.Input type="checkbox" v-model="newVehicle.terms_accepted" class="mt-0.5" />
                                <span class="text-sm text-slate-600">
                                    I confirm that the vehicle information provided is accurate and the owner has agreed to operate under the carrier's authority.
                                </span>
                            </label>
                        </div>

                        <!-- Third Party Notice -->
                        <div v-if="step3.vehicle_assignment_type === 'third_party'" class="mb-5 p-3 bg-warning/10 border border-warning/30 rounded-lg flex items-start gap-2">
                            <Lucide icon="Mail" class="w-4 h-4 text-warning mt-0.5 shrink-0" />
                            <p class="text-sm text-warning-dark">
                                A document signing request will be sent automatically to <strong>{{ step3.third_party_email || 'the third party email' }}</strong> when this step is saved.
                            </p>
                        </div>

                        <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                            <Button variant="outline-secondary" @click="showVehicleModal = false">Cancel</Button>
                            <Button variant="primary" :disabled="!newVehicle.make || !newVehicle.model" @click="confirmNewVehicle">
                                <Lucide icon="Plus" class="w-4 h-4 mr-1" /> Add Vehicle
                            </Button>
                        </div>
                    </div>
                </Dialog.Panel>
                </Dialog>
            </div>

            <!-- ====================================================
                 STEP 4 – License
                 ==================================================== -->
            <div v-else-if="currentStep === 4">
                <div class="flex items-center justify-between mb-5">
                    <h2 class="text-lg font-semibold">Driver's License</h2>
                    <Button size="sm" variant="outline-primary" @click="addLicense">
                        <Lucide icon="Plus" class="w-3.5 h-3.5 mr-1" /> Add License
                    </Button>
                </div>

                <div v-for="(lic, i) in step4.licenses" :key="i" class="border border-slate-200 rounded-lg p-4 mb-4">
                    <div class="flex justify-between mb-3">
                        <span class="text-sm font-medium">License #{{ i + 1 }} {{ i === 0 ? '(Primary)' : '' }}</span>
                        <button v-if="i > 0" @click="removeLicense(i)" class="text-danger hover:opacity-70">
                            <Lucide icon="X" class="w-4 h-4" />
                        </button>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium mb-1">License Number <span class="text-danger">*</span></label>
                            <FormInput v-model="lic.license_number" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium mb-1">State of Issue <span class="text-danger">*</span></label>
                            <TomSelect v-model="lic.state_of_issue">
                                <option value="">Select state</option>
                                <option v-for="(name, code) in props.usStates" :key="code" :value="code">{{ name }}</option>
                            </TomSelect>
                        </div>
                        <div>
                            <label class="block text-xs font-medium mb-1">License Class <span class="text-danger">*</span></label>
                            <TomSelect v-model="lic.license_class">
                                <option value="">Select class</option>
                                <option value="A">Class A – CDL</option>
                                <option value="B">Class B – CDL</option>
                                <option value="C">Class C – CDL</option>
                                <option value="D">Class D – Non-CDL</option>
                            </TomSelect>
                        </div>
                        <div>
                            <label class="block text-xs font-medium mb-1">Expiration Date <span class="text-danger">*</span></label>
                            <Litepicker v-model="lic.expiration_date" :options="lpOptions" />
                        </div>
                        <div class="flex items-center pt-5">
                            <FormCheck>
                                <FormCheck.Input v-model="lic.is_cdl" type="checkbox" />
                                <FormCheck.Label>This is a Commercial Driver's License (CDL)</FormCheck.Label>
                            </FormCheck>
                        </div>
                    </div>

                    <!-- Endorsements -->
                    <div v-if="props.endorsements && props.endorsements.length" class="mt-3">
                        <label class="block text-xs font-medium mb-2">Endorsements</label>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                            <FormCheck v-for="end in props.endorsements" :key="end.id">
                                <FormCheck.Input
                                    type="checkbox"
                                    :value="end.id"
                                    :checked="lic.endorsements.includes(end.id)"
                                    @change="(e: Event) => {
                                        const checked = (e.target as HTMLInputElement).checked
                                        if (checked) { if (!lic.endorsements.includes(end.id)) lic.endorsements.push(end.id) }
                                        else { lic.endorsements = lic.endorsements.filter((id: number) => id !== end.id) }
                                    }"
                                />
                                <FormCheck.Label>{{ end.code }} ({{ end.name }})</FormCheck.Label>
                            </FormCheck>
                        </div>
                    </div>

                    <!-- License Images — only for first/primary license -->
                    <div v-if="i === 0" class="mt-4 pt-4 border-t border-slate-100">
                        <h4 class="text-sm font-medium mb-3">License Images</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium mb-1">License Front</label>
                                <input type="file" accept="image/*,application/pdf" @change="onLicFront" class="w-full text-sm" />
                                <img v-if="licFrontPreview" :src="licFrontPreview" alt="License front preview" class="mt-2 rounded border max-h-32 object-contain" />
                                <template v-else-if="lic.front_url">
                                    <img :src="lic.front_url" alt="License front" class="mt-2 rounded border max-h-32 object-contain" />
                                    <a :href="lic.front_url" target="_blank" class="text-xs text-primary mt-1 block">View full size</a>
                                </template>
                            </div>
                            <div>
                                <label class="block text-xs font-medium mb-1">License Back</label>
                                <input type="file" accept="image/*,application/pdf" @change="onLicBack" class="w-full text-sm" />
                                <img v-if="licBackPreview" :src="licBackPreview" alt="License back preview" class="mt-2 rounded border max-h-32 object-contain" />
                                <template v-else-if="lic.back_url">
                                    <img :src="lic.back_url" alt="License back" class="mt-2 rounded border max-h-32 object-contain" />
                                    <a :href="lic.back_url" target="_blank" class="text-xs text-primary mt-1 block">View full size</a>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Driving Experience -->
                <div class="mt-6">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-base font-semibold">Driving Experience</h3>
                        <Button size="sm" variant="outline-primary" @click="addExperience">
                            <Lucide icon="Plus" class="w-3.5 h-3.5 mr-1" /> Add Another Vehicle Experience
                        </Button>
                    </div>

                    <div v-for="(exp, i) in step4.experiences" :key="i" class="border border-slate-200 rounded-lg p-4 mb-3">
                        <div class="flex justify-between mb-3">
                            <span class="text-sm font-medium">Vehicle #{{ i + 1 }}</span>
                            <button v-if="step4.experiences.length > 1" @click="removeExperience(i)" class="text-danger hover:opacity-70">
                                <Lucide icon="X" class="w-4 h-4" />
                            </button>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium mb-1">Equipment Type <span class="text-danger">*</span></label>
                                <TomSelect v-model="exp.equipment_type">
                                    <option value="">Select type</option>
                                    <option v-for="(label, val) in props.equipmentTypes" :key="val" :value="val">{{ label }}</option>
                                </TomSelect>
                            </div>
                            <div>
                                <label class="block text-xs font-medium mb-1">Years of Experience <span class="text-danger">*</span></label>
                                <FormInput v-model="exp.years_experience" type="number" min="0" />
                            </div>
                            <div>
                                <label class="block text-xs font-medium mb-1">Total Miles Driven <span class="text-danger">*</span></label>
                                <FormInput v-model="exp.miles_driven" type="number" min="0" />
                            </div>
                            <div class="flex items-center pt-5">
                                <FormCheck>
                                    <FormCheck.Input v-model="exp.requires_cdl" type="checkbox" />
                                    <FormCheck.Label>This vehicle requires a CDL</FormCheck.Label>
                                </FormCheck>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-between mt-6">
                    <Button variant="outline-secondary" @click="currentStep = 3">
                        <Lucide icon="ChevronLeft" class="w-4 h-4 mr-1" /> Previous
                    </Button>
                    <Button variant="primary" @click="submitStep4">
                        Save & Continue <Lucide icon="ChevronRight" class="w-4 h-4 ml-1" />
                    </Button>
                </div>
            </div>

            <!-- ====================================================
                 STEP 5 – Medical
                 ==================================================== -->
            <div v-else-if="currentStep === 5">
                <h2 class="text-lg font-semibold mb-5">Medical Qualification</h2>

                <!-- Social Security -->
                <div class="border border-slate-200 rounded-lg p-4 mb-5">
                    <h3 class="text-sm font-semibold text-slate-700 mb-4 flex items-center gap-2">
                        <Lucide icon="ShieldCheck" class="w-4 h-4" /> Social Security
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium mb-1">Social Security Number <span class="text-danger">*</span></label>
                            <FormInput v-model="step5.social_security_number" placeholder="XXX-XX-XXXX" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium mb-1">Hire Date</label>
                            <Litepicker v-model="step5.hire_date" :options="lpOptions" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium mb-1">Location</label>
                            <FormInput v-model="step5.location" placeholder="City, State" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium mb-1">Social Security Card</label>
                            <input type="file" accept="image/*,application/pdf"
                                @change="e => step5.social_security_card = (e.target as HTMLInputElement).files?.[0] ?? null"
                                class="w-full text-sm" />
                            <a v-if="step5.ss_card_url" :href="step5.ss_card_url" target="_blank" class="text-xs text-primary mt-1 block">
                                <Lucide icon="Paperclip" class="w-3 h-3 inline mr-1" />View current file
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Medical Examiner -->
                <div class="border border-slate-200 rounded-lg p-4 mb-5">
                    <h3 class="text-sm font-semibold text-slate-700 mb-4 flex items-center gap-2">
                        <Lucide icon="Stethoscope" class="w-4 h-4" /> Medical Examiner
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium mb-1">Examiner Name <span class="text-danger">*</span></label>
                            <FormInput v-model="step5.medical_examiner_name" placeholder="Dr. John Smith" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium mb-1">Registry Number <span class="text-danger">*</span></label>
                            <FormInput v-model="step5.medical_examiner_registry_number" placeholder="0000000000" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium mb-1">Medical Card Expiration <span class="text-danger">*</span></label>
                            <Litepicker v-model="step5.medical_card_expiration_date" :options="lpOptions" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium mb-1">Medical Card Document</label>
                            <input type="file" accept="image/*,application/pdf"
                                @change="e => step5.medical_card = (e.target as HTMLInputElement).files?.[0] ?? null"
                                class="w-full text-sm" />
                            <a v-if="step5.medical_card_url" :href="step5.medical_card_url" target="_blank" class="text-xs text-primary mt-1 block">
                                <Lucide icon="Paperclip" class="w-3 h-3 inline mr-1" />View current file
                            </a>
                        </div>
                    </div>
                </div>

                <div class="flex justify-between mt-6">
                    <Button variant="outline-secondary" @click="currentStep = 4">
                        <Lucide icon="ChevronLeft" class="w-4 h-4 mr-1" /> Previous
                    </Button>
                    <Button variant="primary" @click="submitStep5">
                        Save & Continue <Lucide icon="ChevronRight" class="w-4 h-4 ml-1" />
                    </Button>
                </div>
            </div>

            <!-- ====================================================
                 STEP 6 – Training
                 ==================================================== -->
            <div v-else-if="currentStep === 6">
                <div class="flex items-center justify-between mb-5">
                    <h2 class="text-lg font-semibold">Training Schools</h2>
                    <Button size="sm" variant="outline-primary" @click="addSchool">
                        <Lucide icon="Plus" class="w-3.5 h-3.5 mr-1" /> Add School
                    </Button>
                </div>

                <div v-if="step6.schools.length === 0" class="text-center py-8 text-slate-400 text-sm border border-dashed rounded-lg">
                    No schools added yet. Click "Add School" to add one.
                </div>

                <div v-for="(school, si) in step6.schools" :key="si" class="border border-slate-200 rounded-lg p-4 mb-4">
                    <div class="flex justify-between mb-3">
                        <span class="text-sm font-semibold">School #{{ si + 1 }}</span>
                        <button @click="removeSchool(si)" class="text-danger hover:opacity-70">
                            <Lucide icon="X" class="w-4 h-4" />
                        </button>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div class="md:col-span-2">
                            <label class="block text-xs font-medium mb-1">School Name <span class="text-danger">*</span></label>
                            <FormInput v-model="school.school_name" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium mb-1">City</label>
                            <FormInput v-model="school.city" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium mb-1">State</label>
                            <TomSelect v-model="school.state">
                                <option value="">Select</option>
                                <option v-for="(name, code) in props.usStates" :key="code" :value="code">{{ name }}</option>
                            </TomSelect>
                        </div>
                        <div>
                            <label class="block text-xs font-medium mb-1">Date Start</label>
                            <Litepicker v-model="school.date_start" :options="lpOptions" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium mb-1">Date End</label>
                            <Litepicker v-model="school.date_end" :options="lpOptions" />
                        </div>
                        <div class="flex gap-4 md:col-span-2">
                            <FormCheck>
                                <FormCheck.Input v-model="school.graduated" type="checkbox" />
                                <FormCheck.Label>Graduated</FormCheck.Label>
                            </FormCheck>
                            <FormCheck>
                                <FormCheck.Input v-model="school.subject_to_safety_regulations" type="checkbox" />
                                <FormCheck.Label>Subject to Safety Regulations</FormCheck.Label>
                            </FormCheck>
                            <FormCheck>
                                <FormCheck.Input v-model="school.performed_safety_functions" type="checkbox" />
                                <FormCheck.Label>Performed Safety Functions</FormCheck.Label>
                            </FormCheck>
                        </div>
                    </div>
                </div>

                <!-- Courses / Certifications (separate from schools) -->
                <div class="mt-4">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-semibold">Courses / Certifications</h3>
                        <Button size="sm" variant="outline-secondary" @click="addCourse">
                            <Lucide icon="Plus" class="w-3.5 h-3.5 mr-1" /> Add Course
                        </Button>
                    </div>
                    <div v-for="(course, ci) in step6.courses" :key="ci" class="border border-slate-200 rounded-lg p-3 mb-2">
                        <div class="flex justify-between mb-2">
                            <span class="text-xs font-medium text-slate-500">Course #{{ ci + 1 }}</span>
                            <button @click="removeCourse(ci)" class="text-danger hover:opacity-70">
                                <Lucide icon="X" class="w-3.5 h-3.5" />
                            </button>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                            <div class="md:col-span-2">
                                <label class="block text-xs font-medium mb-1">Organization <span class="text-danger">*</span></label>
                                <FormInput v-model="course.organization_name" />
                            </div>
                            <div>
                                <label class="block text-xs font-medium mb-1">State</label>
                                <TomSelect v-model="course.state">
                                    <option value="">Select</option>
                                    <option v-for="(name, code) in props.usStates" :key="code" :value="code">{{ name }}</option>
                                </TomSelect>
                            </div>
                            <div>
                                <label class="block text-xs font-medium mb-1">Certification Date</label>
                                <Litepicker v-model="course.certification_date" :options="lpOptions" />
                            </div>
                            <div>
                                <label class="block text-xs font-medium mb-1">Expiration Date</label>
                                <Litepicker v-model="course.expiration_date" :options="lpOptions" />
                            </div>
                            <div>
                                <label class="block text-xs font-medium mb-1">Years Experience</label>
                                <FormInput v-model="course.years_experience" type="number" step="0.5" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-between mt-6">
                    <Button variant="outline-secondary" @click="currentStep = 5">
                        <Lucide icon="ChevronLeft" class="w-4 h-4 mr-1" /> Previous
                    </Button>
                    <Button variant="primary" @click="submitStep6">
                        Save & Continue <Lucide icon="ChevronRight" class="w-4 h-4 ml-1" />
                    </Button>
                </div>
            </div>

            <!-- ====================================================
                 STEP 7 – Traffic Convictions
                 ==================================================== -->
            <div v-else-if="currentStep === 7">
                <h2 class="text-lg font-semibold mb-5">Traffic Convictions</h2>
                <FormCheck class="mb-4">
                    <FormCheck.Input v-model="step7.no_traffic_convictions" type="checkbox" @change="step7.convictions = []" />
                    <FormCheck.Label>No traffic convictions in the past 3 years</FormCheck.Label>
                </FormCheck>

                <div v-if="!step7.no_traffic_convictions">
                    <div class="flex justify-end mb-3">
                        <Button size="sm" variant="outline-primary" @click="addConviction">
                            <Lucide icon="Plus" class="w-3.5 h-3.5 mr-1" /> Add Conviction
                        </Button>
                    </div>
                    <div v-for="(c, i) in step7.convictions" :key="i" class="border border-slate-200 rounded-lg p-4 mb-3">
                        <div class="flex justify-between mb-2">
                            <span class="text-xs font-medium text-slate-500">Conviction #{{ i + 1 }}</span>
                            <button @click="removeConviction(i)" class="text-danger hover:opacity-70"><Lucide icon="X" class="w-4 h-4" /></button>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium mb-1">Date <span class="text-danger">*</span></label>
                                <Litepicker v-model="c.conviction_date" :options="lpOptions" />
                            </div>
                            <div>
                                <label class="block text-xs font-medium mb-1">Location <span class="text-danger">*</span></label>
                                <FormInput v-model="c.location" placeholder="City, State" />
                            </div>
                            <div>
                                <label class="block text-xs font-medium mb-1">Charge <span class="text-danger">*</span></label>
                                <FormInput v-model="c.charge" />
                            </div>
                            <div>
                                <label class="block text-xs font-medium mb-1">Penalty</label>
                                <FormInput v-model="c.penalty" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-between mt-6">
                    <Button variant="outline-secondary" @click="currentStep = 6">
                        <Lucide icon="ChevronLeft" class="w-4 h-4 mr-1" /> Previous
                    </Button>
                    <Button variant="primary" @click="submitStep7">
                        Save & Continue <Lucide icon="ChevronRight" class="w-4 h-4 ml-1" />
                    </Button>
                </div>
            </div>

            <!-- ====================================================
                 STEP 8 – Accidents
                 ==================================================== -->
            <div v-else-if="currentStep === 8">
                <h2 class="text-lg font-semibold mb-5">Accident Record</h2>
                <FormCheck class="mb-4">
                    <FormCheck.Input v-model="step8.no_accidents" type="checkbox" @change="step8.accidents = []" />
                    <FormCheck.Label>No accidents in the past 3 years</FormCheck.Label>
                </FormCheck>

                <div v-if="!step8.no_accidents">
                    <div class="flex justify-end mb-3">
                        <Button size="sm" variant="outline-primary" @click="addAccident">
                            <Lucide icon="Plus" class="w-3.5 h-3.5 mr-1" /> Add Accident
                        </Button>
                    </div>
                    <div v-for="(a, i) in step8.accidents" :key="i" class="border border-slate-200 rounded-lg p-4 mb-3">
                        <div class="flex justify-between mb-2">
                            <span class="text-xs font-medium text-slate-500">Accident #{{ i + 1 }}</span>
                            <button @click="removeAccident(i)" class="text-danger hover:opacity-70"><Lucide icon="X" class="w-4 h-4" /></button>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium mb-1">Date <span class="text-danger">*</span></label>
                                <Litepicker v-model="a.accident_date" :options="lpOptions" />
                            </div>
                            <div>
                                <label class="block text-xs font-medium mb-1">Nature of Accident <span class="text-danger">*</span></label>
                                <FormInput v-model="a.nature_of_accident" />
                            </div>
                            <div>
                                <label class="block text-xs font-medium mb-1">Fatalities</label>
                                <FormInput v-model.number="a.number_of_fatalities" type="number" min="0" />
                            </div>
                            <div>
                                <label class="block text-xs font-medium mb-1">Injuries</label>
                                <FormInput v-model.number="a.number_of_injuries" type="number" min="0" />
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-xs font-medium mb-1">Comments</label>
                                <FormInput v-model="a.comments" placeholder="Additional notes" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-between mt-6">
                    <Button variant="outline-secondary" @click="currentStep = 7">
                        <Lucide icon="ChevronLeft" class="w-4 h-4 mr-1" /> Previous
                    </Button>
                    <Button variant="primary" @click="submitStep8">
                        Save & Continue <Lucide icon="ChevronRight" class="w-4 h-4 ml-1" />
                    </Button>
                </div>
            </div>

            <!-- ====================================================
                 STEP 9 – FMCSR
                 ==================================================== -->
            <div v-else-if="currentStep === 9">
                <h2 class="text-lg font-semibold mb-2">FMCSR Compliance</h2>
                <p class="text-sm text-slate-500 mb-5">Federal Motor Carrier Safety Regulations – answer truthfully.</p>

                <div class="space-y-4">
                    <div class="flex items-start gap-3 p-4 border border-slate-200 rounded-lg">
                        <FormCheck.Input v-model="step9.is_disqualified" type="checkbox" class="mt-0.5" />
                        <div>
                            <p class="text-sm font-medium">Have you been disqualified from operating a CMV?</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3 p-4 border border-slate-200 rounded-lg">
                        <FormCheck.Input v-model="step9.is_license_suspended" type="checkbox" class="mt-0.5" />
                        <div>
                            <p class="text-sm font-medium">Has your license ever been suspended or revoked?</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3 p-4 border border-slate-200 rounded-lg">
                        <FormCheck.Input v-model="step9.is_license_denied" type="checkbox" class="mt-0.5" />
                        <div>
                            <p class="text-sm font-medium">Has a license been denied?</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3 p-4 border border-slate-200 rounded-lg">
                        <FormCheck.Input v-model="step9.has_positive_drug_test" type="checkbox" class="mt-0.5" />
                        <div>
                            <p class="text-sm font-medium">Have you had a positive drug/alcohol test?</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3 p-4 border border-slate-200 rounded-lg">
                        <FormCheck.Input v-model="step9.has_duty_offenses" type="checkbox" class="mt-0.5" />
                        <div>
                            <p class="text-sm font-medium">Do you have any on-duty driving offenses?</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3 p-4 border border-primary/20 rounded-lg bg-primary/5">
                        <FormCheck.Input v-model="step9.consent_to_release" type="checkbox" class="mt-0.5" />
                        <div>
                            <p class="text-sm font-medium">Consent to release of driving records</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3 p-4 border border-primary/20 rounded-lg bg-primary/5">
                        <FormCheck.Input v-model="step9.consent_driving_record" type="checkbox" class="mt-0.5" />
                        <div>
                            <p class="text-sm font-medium">Consent to driving record review</p>
                        </div>
                    </div>
                </div>

                <div class="flex justify-between mt-6">
                    <Button variant="outline-secondary" @click="currentStep = 8">
                        <Lucide icon="ChevronLeft" class="w-4 h-4 mr-1" /> Previous
                    </Button>
                    <Button variant="primary" @click="submitStep9">
                        Save & Continue <Lucide icon="ChevronRight" class="w-4 h-4 ml-1" />
                    </Button>
                </div>
            </div>

            <!-- ====================================================
                 STEP 10 – Employment History
                 ==================================================== -->
            <div v-else-if="currentStep === 10">
                <div class="flex items-center justify-between mb-5">
                    <h2 class="text-lg font-semibold">Employment History</h2>
                    <Button size="sm" variant="outline-primary" @click="addCompany">
                        <Lucide icon="Plus" class="w-3.5 h-3.5 mr-1" /> Add Company
                    </Button>
                </div>

                <div v-if="step10.companies.length === 0" class="text-center py-6 text-slate-400 text-sm border border-dashed rounded-lg mb-4">
                    No employment history added.
                </div>

                <div v-for="(c, i) in step10.companies" :key="i" class="border border-slate-200 rounded-lg p-4 mb-3">
                    <div class="flex justify-between mb-2">
                        <span class="text-xs font-medium text-slate-500">Company #{{ i + 1 }}</span>
                        <button @click="removeCompany(i)" class="text-danger hover:opacity-70"><Lucide icon="X" class="w-4 h-4" /></button>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div class="md:col-span-2">
                            <label class="block text-xs font-medium mb-1">Company Name <span class="text-danger">*</span></label>
                            <FormInput v-model="c.company_name" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium mb-1">Address</label>
                            <FormInput v-model="c.address" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium mb-1">City</label>
                            <FormInput v-model="c.city" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium mb-1">State</label>
                            <TomSelect v-model="c.state">
                                <option value="">Select</option>
                                <option v-for="(name, code) in props.usStates" :key="code" :value="code">{{ name }}</option>
                            </TomSelect>
                        </div>
                        <div>
                            <label class="block text-xs font-medium mb-1">ZIP</label>
                            <FormInput v-model="c.zip" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium mb-1">Phone</label>
                            <FormInput v-model="c.phone" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium mb-1">Employed From</label>
                            <Litepicker v-model="c.employed_from" :options="lpOptions" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium mb-1">Employed To</label>
                            <Litepicker v-model="c.employed_to" :options="lpOptions" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium mb-1">Positions Held</label>
                            <FormInput v-model="c.positions_held" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium mb-1">Reason for Leaving</label>
                            <FormInput v-model="c.reason_for_leaving" />
                        </div>
                        <div class="flex gap-4">
                            <FormCheck>
                                <FormCheck.Input v-model="c.subject_to_fmcsr" type="checkbox" />
                                <FormCheck.Label class="text-xs">Subject to FMCSR</FormCheck.Label>
                            </FormCheck>
                            <FormCheck>
                                <FormCheck.Input v-model="c.safety_sensitive_function" type="checkbox" />
                                <FormCheck.Label class="text-xs">Safety Sensitive</FormCheck.Label>
                            </FormCheck>
                        </div>
                    </div>
                </div>

                <!-- Unemployment periods -->
                <div class="mt-6">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-semibold">Unemployment Periods</h3>
                        <Button size="sm" variant="outline-secondary" @click="addUnemployment">
                            <Lucide icon="Plus" class="w-3.5 h-3.5 mr-1" /> Add Period
                        </Button>
                    </div>
                    <div v-for="(u, i) in step10.unemployment_periods" :key="i" class="grid grid-cols-4 gap-3 mb-2 items-end">
                        <Litepicker v-model="u.start_date" :options="lpOptions" />
                        <Litepicker v-model="u.end_date" :options="lpOptions" />
                        <FormInput v-model="u.comments" placeholder="Reason / Comments" />
                        <button @click="removeUnemployment(i)" class="text-danger hover:opacity-70 pb-1">
                            <Lucide icon="X" class="w-4 h-4" />
                        </button>
                    </div>
                </div>

                <div class="flex justify-between mt-6">
                    <Button variant="outline-secondary" @click="currentStep = 9">
                        <Lucide icon="ChevronLeft" class="w-4 h-4 mr-1" /> Previous
                    </Button>
                    <Button variant="primary" @click="submitStep10">
                        Save & Continue <Lucide icon="ChevronRight" class="w-4 h-4 ml-1" />
                    </Button>
                </div>
            </div>

            <!-- ====================================================
                 STEP 11 – Company Policy
                 ==================================================== -->
            <div v-else-if="currentStep === 11">
                <h2 class="text-lg font-semibold mb-2">Company Policy</h2>
                <p class="text-sm text-slate-500 mb-5">The driver must acknowledge and accept the company policy.</p>
                <div class="space-y-3">
                    <div class="flex items-start gap-3 p-4 border border-slate-200 rounded-lg">
                        <FormCheck.Input v-model="step11.consent_all_policies_attached" type="checkbox" class="mt-0.5" />
                        <p class="text-sm">I have read and consent to all company policies attached</p>
                    </div>
                    <div class="flex items-start gap-3 p-4 border border-slate-200 rounded-lg">
                        <FormCheck.Input v-model="step11.substance_testing_consent" type="checkbox" class="mt-0.5" />
                        <p class="text-sm">I consent to substance testing as required by DOT regulations</p>
                    </div>
                    <div class="flex items-start gap-3 p-4 border border-slate-200 rounded-lg">
                        <FormCheck.Input v-model="step11.authorization_consent" type="checkbox" class="mt-0.5" />
                        <p class="text-sm">I authorize the company to conduct necessary verifications and background checks</p>
                    </div>
                    <div class="flex items-start gap-3 p-4 border border-primary/20 rounded-lg bg-primary/5">
                        <FormCheck.Input v-model="step11.fmcsa_clearinghouse_consent" type="checkbox" class="mt-0.5" />
                        <p class="text-sm">I consent to FMCSA Drug & Alcohol Clearinghouse queries as required</p>
                    </div>
                </div>
                <div class="flex justify-between mt-6">
                    <Button variant="outline-secondary" @click="currentStep = 10">
                        <Lucide icon="ChevronLeft" class="w-4 h-4 mr-1" /> Previous
                    </Button>
                    <Button variant="primary" @click="submitStep11">
                        Save & Continue <Lucide icon="ChevronRight" class="w-4 h-4 ml-1" />
                    </Button>
                </div>
            </div>

            <!-- ====================================================
                 STEP 12 – Criminal History
                 ==================================================== -->
            <div v-else-if="currentStep === 12">
                <h2 class="text-lg font-semibold mb-2">Criminal History</h2>
                <p class="text-sm text-slate-500 mb-5">Please answer all questions truthfully. A "Yes" answer does not automatically disqualify an applicant.</p>
                <div class="space-y-4">
                    <div class="flex items-start gap-3 p-4 border border-slate-200 rounded-lg">
                        <FormCheck.Input v-model="step12.has_criminal_charges" type="checkbox" class="mt-0.5" />
                        <p class="text-sm">Have you ever been arrested or had criminal charges filed against you?</p>
                    </div>
                    <div class="flex items-start gap-3 p-4 border border-slate-200 rounded-lg">
                        <FormCheck.Input v-model="step12.has_felony_conviction" type="checkbox" class="mt-0.5" />
                        <p class="text-sm">Have you ever been convicted of a felony?</p>
                    </div>
                    <div class="flex items-start gap-3 p-4 border border-slate-200 rounded-lg">
                        <FormCheck.Input v-model="step12.has_minister_permit" type="checkbox" class="mt-0.5" />
                        <p class="text-sm">Do you have a minister permit or expungement?</p>
                    </div>
                    <div class="flex items-start gap-3 p-4 border border-primary/20 rounded-lg bg-primary/5">
                        <FormCheck.Input v-model="step12.fcra_consent" type="checkbox" class="mt-0.5" />
                        <p class="text-sm">I consent to a background check as required by FCRA</p>
                    </div>
                    <div class="flex items-start gap-3 p-4 border border-primary/20 rounded-lg bg-primary/5">
                        <FormCheck.Input v-model="step12.background_info_consent" type="checkbox" class="mt-0.5" />
                        <p class="text-sm">I consent to the use of background information provided</p>
                    </div>
                </div>
                <div class="flex justify-between mt-6">
                    <Button variant="outline-secondary" @click="currentStep = 11">
                        <Lucide icon="ChevronLeft" class="w-4 h-4 mr-1" /> Previous
                    </Button>
                    <Button variant="primary" @click="submitStep12">
                        Save & Continue <Lucide icon="ChevronRight" class="w-4 h-4 ml-1" />
                    </Button>
                </div>
            </div>

            <!-- ====================================================
                 STEP 13 – W-9
                 ==================================================== -->
            <div v-else-if="currentStep === 13">
                <h2 class="text-lg font-semibold mb-5">W-9 Tax Form</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Name (as on tax return) <span class="text-danger">*</span></label>
                        <FormInput v-model="step13.name" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Business Name (DBA)</label>
                        <FormInput v-model="step13.business_name" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Tax Classification <span class="text-danger">*</span></label>
                        <TomSelect v-model="step13.tax_classification">
                            <option value="individual">Individual / Sole Proprietor</option>
                            <option value="c_corp">C Corporation</option>
                            <option value="s_corp">S Corporation</option>
                            <option value="partnership">Partnership</option>
                            <option value="trust">Trust / Estate</option>
                            <option value="llc">LLC</option>
                        </TomSelect>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">TIN Type <span class="text-danger">*</span></label>
                        <TomSelect v-model="step13.tin_type">
                            <option value="ssn">SSN</option>
                            <option value="ein">EIN</option>
                        </TomSelect>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">TIN (SSN / EIN) <span class="text-danger">*</span></label>
                        <FormInput v-model="step13.tin" placeholder="XXX-XX-XXXX" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Address</label>
                        <FormInput v-model="step13.address" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">City</label>
                        <FormInput v-model="step13.city" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">State</label>
                        <TomSelect v-model="step13.state">
                            <option value="">Select</option>
                            <option v-for="(name, code) in props.usStates" :key="code" :value="code">{{ name }}</option>
                        </TomSelect>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">ZIP Code</label>
                        <FormInput v-model="step13.zip_code" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Signature (type name)</label>
                        <FormInput v-model="step13.signature" placeholder="Type full name" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Date Signed</label>
                        <Litepicker v-model="step13.signed_date" :options="lpOptions" />
                    </div>
                </div>
                <div class="flex justify-between mt-6">
                    <Button variant="outline-secondary" @click="currentStep = 12">
                        <Lucide icon="ChevronLeft" class="w-4 h-4 mr-1" /> Previous
                    </Button>
                    <Button variant="primary" @click="submitStep13">
                        Save & Continue <Lucide icon="ChevronRight" class="w-4 h-4 ml-1" />
                    </Button>
                </div>
            </div>

            <!-- ====================================================
                 STEP 14 – Certification
                 ==================================================== -->
            <div v-else-if="currentStep === 14">
                <h2 class="text-lg font-semibold mb-2">DOT Certification</h2>
                <p class="text-sm text-slate-500 mb-5">The driver certifies that all information provided in this application is true and complete.</p>
                <div class="bg-slate-50 dark:bg-darkmode-700 border rounded-lg p-4 mb-5 text-sm text-slate-600 dark:text-slate-400">
                    I certify that this application was completed by me, and that all entries on it and information in it are true and complete to the best of my knowledge.
                </div>
                <div class="mb-4">
                    <FormCheck>
                        <FormCheck.Input v-model="step14.is_accepted" type="checkbox" />
                        <FormCheck.Label>I certify the above statement</FormCheck.Label>
                    </FormCheck>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Signature (type name)</label>
                        <FormInput v-model="step14.signature" placeholder="Full name" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Date</label>
                        <Litepicker v-model="step14.signed_at" :options="lpOptions" />
                    </div>
                </div>
                <div class="flex justify-between mt-6">
                    <Button variant="outline-secondary" @click="currentStep = 13">
                        <Lucide icon="ChevronLeft" class="w-4 h-4 mr-1" /> Previous
                    </Button>
                    <Button variant="primary" @click="submitStep14">
                        Save & Continue <Lucide icon="ChevronRight" class="w-4 h-4 ml-1" />
                    </Button>
                </div>
            </div>

            <!-- ====================================================
                 STEP 15 – Clearinghouse / Finalize
                 ==================================================== -->
            <div v-else-if="currentStep === 15">
                <h2 class="text-lg font-semibold mb-2">FMCSA Clearinghouse</h2>
                <p class="text-sm text-slate-500 mb-5">Final step: drug and alcohol clearinghouse verification.</p>

                <div class="border border-slate-200 rounded-lg p-4 mb-4">
                    <FormCheck class="mb-3">
                        <FormCheck.Input v-model="step15.clearinghouse_consent" type="checkbox" />
                        <FormCheck.Label>Driver consents to FMCSA Drug & Alcohol Clearinghouse query</FormCheck.Label>
                    </FormCheck>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Query Date</label>
                            <Litepicker v-model="step15.clearinghouse_query_date" :options="lpOptions" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Result</label>
                            <TomSelect v-model="step15.clearinghouse_result">
                                <option value="">Pending query</option>
                                <option value="clear">Clear</option>
                                <option value="fail">Disqualifying record found</option>
                            </TomSelect>
                        </div>
                    </div>
                </div>
                <div class="flex justify-between mt-6">
                    <Button variant="outline-secondary" @click="currentStep = 14">
                        <Lucide icon="ChevronLeft" class="w-4 h-4 mr-1" /> Previous
                    </Button>
                    <Button variant="primary" @click="submitStep15">
                        <Lucide icon="Check" class="w-4 h-4 mr-1" /> Complete Application
                    </Button>
                </div>
            </div>

        </div>
        </div>
    </div>
</template>
