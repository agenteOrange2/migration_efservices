<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3'
import { ref, computed, reactive, watch, nextTick } from 'vue'
import axios from 'axios'

// Setup axios defaults for Laravel CSRF
axios.defaults.withCredentials = true
const csrfMeta = document.querySelector('meta[name="csrf-token"]')
if (csrfMeta) axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfMeta.getAttribute('content') ?? ''
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

interface WizardRouteNames {
    index: string
    create: string
    store: string
    edit: string
    updateStep: string
    employmentSearchCompanies: string
    employmentSendEmail: string
    employmentResendEmail: string
    employmentMarkEmailStatus: string
}

const props = withDefaults(defineProps<{
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
    carrierLocked?: boolean
    routeNames?: WizardRouteNames
}>(), {
    carrierLocked: false,
    routeNames: () => ({
        index: 'admin.drivers.index',
        create: 'admin.drivers.wizard.create',
        store: 'admin.drivers.wizard.store',
        edit: 'admin.drivers.wizard.edit',
        updateStep: 'admin.drivers.wizard.update-step',
        employmentSearchCompanies: 'admin.drivers.employment.search-companies',
        employmentSendEmail: 'admin.drivers.employment.send-email',
        employmentResendEmail: 'admin.drivers.employment.resend-email',
        employmentMarkEmailStatus: 'admin.drivers.employment.mark-email-status',
    }),
})

const page = usePage()
const errors = computed(() => (page.props as any).errors ?? {})

function namedRoute(name: keyof WizardRouteNames, params?: any) {
    return route(props.routeNames[name], params)
}

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

function canGoToStep(n: number): boolean {
    // Drivers filling their own application (carrierLocked) can only visit
    // steps they have already completed or the very next pending step.
    if (props.carrierLocked) {
        return n <= (completedStep.value + 1)
    }
    // Admin edit mode: free navigation
    return isEditMode.value
}

function goToStep(n: number) {
    if (canGoToStep(n)) {
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
        const reader = new FileReader()
        reader.onload = (ev) => { step1.photoPreview = ev.target?.result as string }
        reader.readAsDataURL(file)
    }
}

const step1Errors = ref<string[]>([])

function submitStep1() {
    const errs: string[] = []
    if (!step1.carrier_id)             errs.push('Carrier is required.')
    if (!step1.name.trim())            errs.push('First name is required.')
    if (!step1.last_name.trim())       errs.push('Last name is required.')
    if (!step1.email.trim())           errs.push('Email is required.')
    if (!step1.phone.trim())           errs.push('Phone is required.')
    if (!step1.date_of_birth)          errs.push('Date of birth is required.')
    if (!isEditMode.value && !step1.password) errs.push('Password is required.')
    if (!step1.terms_accepted)         errs.push('You must accept the terms and conditions.')
    step1Errors.value = errs
    if (errs.length) return

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
        router.post(namedRoute('updateStep', { driver: props.driver!.id, step: 1 }), data)
    } else {
        router.post(namedRoute('store'), data)
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

const step2Errors = ref<string[]>([])

function submitStep2() {
    const errs: string[] = []
    if (!step2.address_line1.trim()) errs.push('Address is required.')
    if (!step2.city.trim())          errs.push('City is required.')
    if (!step2.state)                errs.push('State is required.')
    if (!step2.zip_code.trim())      errs.push('ZIP Code is required.')
    if (!step2.from_date)            errs.push('Move-in date is required.')
    for (const [i, a] of step2.previous_addresses.entries()) {
        if (!a.address_line1?.trim()) errs.push(`Previous address #${i + 1}: Address is required.`)
        if (!a.city?.trim())          errs.push(`Previous address #${i + 1}: City is required.`)
        if (!a.state)                 errs.push(`Previous address #${i + 1}: State is required.`)
        if (!a.zip_code?.trim())      errs.push(`Previous address #${i + 1}: ZIP is required.`)
        if (!a.from_date)             errs.push(`Previous address #${i + 1}: From date is required.`)
    }
    step2Errors.value = errs
    if (errs.length) return

    router.put(namedRoute('updateStep', { driver: props.driver!.id, step: 2 }), {
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
    router.put(namedRoute('updateStep', { driver: props.driver!.id, step: 3 }), data)
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

const licFrontPreviews = ref<(string | null)[]>([])
const licBackPreviews  = ref<(string | null)[]>([])
const licFrontFiles    = ref<(File | null)[]>([])
const licBackFiles     = ref<(File | null)[]>([])

function onLicFront(e: Event, i: number) {
    const file = (e.target as HTMLInputElement).files?.[0] ?? null
    licFrontFiles.value[i] = file
    licFrontPreviews.value[i] = file ? URL.createObjectURL(file) : null
}
function onLicBack(e: Event, i: number) {
    const file = (e.target as HTMLInputElement).files?.[0] ?? null
    licBackFiles.value[i] = file
    licBackPreviews.value[i] = file ? URL.createObjectURL(file) : null
}

const step4Errors = ref<string[]>([])

function submitStep4() {
    const errs: string[] = []
    if (step4.licenses.length === 0) {
        errs.push('At least one license is required.')
    } else {
        step4.licenses.forEach((l, i) => {
            const n = step4.licenses.length > 1 ? ` #${i + 1}` : ''
            if (!l.license_number?.trim())  errs.push(`License${n}: License number is required.`)
            if (!l.state_of_issue)           errs.push(`License${n}: State of issue is required.`)
            if (!l.license_class)            errs.push(`License${n}: License class is required.`)
            if (!l.expiration_date)          errs.push(`License${n}: Expiration date is required.`)
        })
    }
    step4Errors.value = errs
    if (errs.length) return

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
    licFrontFiles.value.forEach((file, i) => { if (file) data.append(`license_front_${i}`, file) })
    licBackFiles.value.forEach((file, i)  => { if (file) data.append(`license_back_${i}`, file) })
    step4.experiences.forEach((e, i) => {
        if (!e.equipment_type) return
        data.append(`experiences[${i}][equipment_type]`, String(e.equipment_type))
        data.append(`experiences[${i}][years_experience]`, String(parseInt(e.years_experience) || 0))
        data.append(`experiences[${i}][miles_driven]`, String(parseInt(e.miles_driven) || 0))
        data.append(`experiences[${i}][requires_cdl]`, e.requires_cdl ? '1' : '0')
    })
    data.append('_method', 'PUT')
    router.post(namedRoute('updateStep', { driver: props.driver!.id, step: 4 }), data)
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
    router.post(namedRoute('updateStep', { driver: props.driver!.id, step: 5 }), data)
}

// ------------------------------------------------------------------
// Step 6 – Training
// ------------------------------------------------------------------
const trainingSkillOptions = [
    { value: 'double_trailer',     label: 'Double Trailer' },
    { value: 'passenger',          label: 'Passenger' },
    { value: 'tank_vehicle',       label: 'Tank Vehicle' },
    { value: 'hazardous_material', label: 'Hazardous Material' },
    { value: 'combination_vehicle',label: 'Combination Vehicle' },
    { value: 'air_brakes',         label: 'Air Brakes' },
]

const step6 = reactive({
    schools: ((props.stepData?.step6?.schools ?? []) as any[]).map((s: any) => ({
        ...s,
        date_start: toUsDate(s.date_start),
        date_end: toUsDate(s.date_end),
        training_skills: Array.isArray(s.training_skills) ? [...s.training_skills] : [] as string[],
        certificate_file: null as File | null,
        certificate_url: s.certificate_url ?? null as string | null,
    })),
    courses: ((props.stepData?.step6?.courses ?? []) as any[]).map((c: any) => ({
        ...c,
        certification_date: toUsDate(c.certification_date),
        expiration_date: toUsDate(c.expiration_date),
        certificate_file: null as File | null,
        certificate_url: c.certificate_url ?? null as string | null,
    })),
})

function addSchool() {
    step6.schools.push({ school_name: '', city: '', state: '', graduated: false, date_start: '', date_end: '', subject_to_safety_regulations: false, performed_safety_functions: false, training_skills: [], certificate_file: null, certificate_url: null })
}
function removeSchool(i: number) {
    step6.schools.splice(i, 1)
}
function addCourse() {
    step6.courses.push({ organization_name: '', city: '', state: '', certification_date: '', expiration_date: '', experience: '', years_experience: '', certificate_file: null, certificate_url: null })
}
function removeCourse(i: number) {
    step6.courses.splice(i, 1)
}

function onSchoolCertChange(e: Event, i: number) {
    const file = (e.target as HTMLInputElement).files?.[0] ?? null
    step6.schools[i].certificate_file = file
    if (file) {
        const reader = new FileReader()
        reader.onload = (ev) => { step6.schools[i].certificate_url = ev.target?.result as string }
        reader.readAsDataURL(file)
    }
}

function onCourseCertChange(e: Event, i: number) {
    const file = (e.target as HTMLInputElement).files?.[0] ?? null
    step6.courses[i].certificate_file = file
    if (file) {
        const reader = new FileReader()
        reader.onload = (ev) => { step6.courses[i].certificate_url = ev.target?.result as string }
        reader.readAsDataURL(file)
    }
}

function submitStep6() {
    const data = new FormData()
    const schoolFields = ['id', 'school_name', 'city', 'state', 'graduated', 'date_start', 'date_end', 'subject_to_safety_regulations', 'performed_safety_functions']
    step6.schools.forEach((s, i) => {
        schoolFields.forEach(k => {
            if (k in s) {
                const v = (s as any)[k]
                data.append(`schools[${i}][${k}]`, typeof v === 'boolean' ? (v ? '1' : '0') : String(v ?? ''))
            }
        })
        const skills: string[] = Array.isArray((s as any).training_skills) ? (s as any).training_skills : []
        skills.forEach(skill => data.append(`schools[${i}][training_skills][]`, skill))
        if ((s as any).certificate_file) {
            data.append(`school_certificates[${i}]`, (s as any).certificate_file)
        }
    })
    const courseFields = ['id', 'organization_name', 'city', 'state', 'certification_date', 'expiration_date', 'experience', 'years_experience']
    step6.courses.forEach((c, i) => {
        courseFields.forEach(k => {
            if (k in c) {
                const v = (c as any)[k]
                data.append(`courses[${i}][${k}]`, typeof v === 'boolean' ? (v ? '1' : '0') : String(v ?? ''))
            }
        })
        if ((c as any).certificate_file) {
            data.append(`course_certificates[${i}]`, (c as any).certificate_file)
        }
    })
    data.append('_method', 'PUT')
    router.post(namedRoute('updateStep', { driver: props.driver!.id, step: 6 }), data)
}

// ------------------------------------------------------------------
// Step 7 – Traffic
// ------------------------------------------------------------------
const step7 = reactive({
    no_traffic_convictions: props.stepData?.step7?.no_traffic_convictions ?? false,
    convictions: ((props.stepData?.step7?.convictions ?? []) as any[]).map((c: any) => ({
        ...c,
        conviction_date: toUsDate(c.conviction_date),
        image_file: null as File | null,
        image_url: c.image_url ?? null as string | null,
    })),
})

function addConviction() {
    step7.convictions.push({ conviction_date: '', location: '', charge: '', penalty: '', image_file: null, image_url: null })
}
function removeConviction(i: number) {
    step7.convictions.splice(i, 1)
}

function onConvictionImageChange(e: Event, i: number) {
    const file = (e.target as HTMLInputElement).files?.[0] ?? null
    step7.convictions[i].image_file = file
    if (file) {
        const reader = new FileReader()
        reader.onload = (ev) => { step7.convictions[i].image_url = ev.target?.result as string }
        reader.readAsDataURL(file)
    }
}

function submitStep7() {
    const data = new FormData()
    data.append('no_traffic_convictions', step7.no_traffic_convictions ? '1' : '0')
    const convFields = ['id', 'conviction_date', 'location', 'charge', 'penalty']
    step7.convictions.forEach((c, i) => {
        convFields.forEach(k => {
            if (k in c) data.append(`convictions[${i}][${k}]`, String((c as any)[k] ?? ''))
        })
        if ((c as any).image_file) data.append(`conviction_images[${i}]`, (c as any).image_file)
    })
    data.append('_method', 'PUT')
    router.post(namedRoute('updateStep', { driver: props.driver!.id, step: 7 }), data)
}

// ------------------------------------------------------------------
// Step 8 – Accidents
// ------------------------------------------------------------------
const step8 = reactive({
    no_accidents: props.stepData?.step8?.no_accidents ?? false,
    accidents: ((props.stepData?.step8?.accidents ?? []) as any[]).map((a: any) => ({
        ...a,
        accident_date: toUsDate(a.accident_date),
        had_fatalities: a.had_fatalities ?? false,
        had_injuries: a.had_injuries ?? false,
        image_file: null as File | null,
        image_url: a.image_url ?? null as string | null,
    })),
})

function addAccident() {
    step8.accidents.push({ accident_date: '', nature_of_accident: '', had_fatalities: false, had_injuries: false, number_of_fatalities: 0, number_of_injuries: 0, comments: '', image_file: null, image_url: null })
}
function removeAccident(i: number) {
    step8.accidents.splice(i, 1)
}

function onAccidentImageChange(e: Event, i: number) {
    const file = (e.target as HTMLInputElement).files?.[0] ?? null
    step8.accidents[i].image_file = file
    if (file) {
        const reader = new FileReader()
        reader.onload = (ev) => { step8.accidents[i].image_url = ev.target?.result as string }
        reader.readAsDataURL(file)
    }
}

function submitStep8() {
    const data = new FormData()
    data.append('no_accidents', step8.no_accidents ? '1' : '0')
    const accFields = ['id', 'accident_date', 'nature_of_accident', 'number_of_fatalities', 'number_of_injuries', 'comments']
    step8.accidents.forEach((a, i) => {
        accFields.forEach(k => {
            if (k in a) data.append(`accidents[${i}][${k}]`, String((a as any)[k] ?? ''))
        })
        if ((a as any).image_file) data.append(`accident_images[${i}]`, (a as any).image_file)
    })
    data.append('_method', 'PUT')
    router.post(namedRoute('updateStep', { driver: props.driver!.id, step: 8 }), data)
}

// ------------------------------------------------------------------
// Step 9 – FMCSR
// ------------------------------------------------------------------
const step9 = reactive({
    is_disqualified:              props.stepData?.step9?.is_disqualified              ?? false,
    disqualified_details:         props.stepData?.step9?.disqualified_details         ?? '',
    is_license_suspended:         props.stepData?.step9?.is_license_suspended         ?? false,
    suspension_details:           props.stepData?.step9?.suspension_details           ?? '',
    is_license_denied:            props.stepData?.step9?.is_license_denied            ?? false,
    denial_details:               props.stepData?.step9?.denial_details               ?? '',
    has_positive_drug_test:       props.stepData?.step9?.has_positive_drug_test       ?? false,
    substance_abuse_professional: props.stepData?.step9?.substance_abuse_professional ?? '',
    sap_phone:                    props.stepData?.step9?.sap_phone                    ?? '',
    return_duty_agency:           props.stepData?.step9?.return_duty_agency           ?? '',
    consent_to_release:           props.stepData?.step9?.consent_to_release           ?? false,
    has_duty_offenses:            props.stepData?.step9?.has_duty_offenses            ?? false,
    recent_conviction_date:       toUsDate(props.stepData?.step9?.recent_conviction_date),
    offense_details:              props.stepData?.step9?.offense_details              ?? '',
    consent_driving_record:       props.stepData?.step9?.consent_driving_record       ?? false,
})

const step9Errors = ref<string[]>([])

function submitStep9() {
    const errs: string[] = []
    if (step9.has_positive_drug_test && !step9.consent_to_release) errs.push('You must consent to the release of drug/alcohol test information.')
    if (!step9.consent_driving_record) errs.push('You must consent to the check of your driving record.')
    step9Errors.value = errs
    if (errs.length) return
    router.put(namedRoute('updateStep', { driver: props.driver!.id, step: 9 }), { ...step9 })
}

// ------------------------------------------------------------------
// Step 10 – Employment
// ------------------------------------------------------------------
function blankCompany() {
    return { company_name: '', address: '', city: '', state: '', zip: '', phone: '', fax: '', contact: '', email: '', employed_from: '', employed_to: '', positions_held: '', reason_for_leaving: '', other_reason_description: '', explanation: '', subject_to_fmcsr: false, safety_sensitive_function: false }
}
function blankUnemployment() {
    return { start_date: '', end_date: '', comments: '' }
}
function blankRelated() {
    return { start_date: '', end_date: '', position: '', comments: '' }
}

const step10 = reactive({
    companies: ((props.stepData?.step10?.companies ?? []) as any[]).map((c: any) => ({
        ...blankCompany(), ...c,
        employed_from: toUsDate(c.employed_from),
        employed_to: toUsDate(c.employed_to),
    })),
    unemployment_periods: ((props.stepData?.step10?.unemployment_periods ?? []) as any[]).map((u: any) => ({
        ...blankUnemployment(), ...u,
        start_date: toUsDate(u.start_date),
        end_date: toUsDate(u.end_date),
    })),
    related_employments: ((props.stepData?.step10?.related_employments ?? []) as any[]).map((r: any) => ({
        ...blankRelated(), ...r,
        start_date: toUsDate(r.start_date),
        end_date: toUsDate(r.end_date),
    })),
    has_unemployment_periods: ((props.stepData?.step10?.unemployment_periods ?? []) as any[]).length > 0,
    has_correct_information: props.stepData?.step10?.has_correct_information ?? false,
})

// --- Company modal ---
const showCompanyModal   = ref(false)
const editingCompanyIdx  = ref<number | null>(null)
const companyForm        = reactive(blankCompany())

function openAddCompany() {
    Object.assign(companyForm, blankCompany())
    editingCompanyIdx.value = null
    showCompanyModal.value = true
}
function openEditCompany(i: number) {
    Object.assign(companyForm, { ...step10.companies[i] })
    editingCompanyIdx.value = i
    showCompanyModal.value = true
}
function saveCompanyModal() {
    if (!companyForm.company_name.trim()) return
    if (editingCompanyIdx.value !== null) {
        Object.assign(step10.companies[editingCompanyIdx.value], { ...companyForm })
    } else {
        step10.companies.push({ ...companyForm })
    }
    showCompanyModal.value = false
}
function removeCompany(i: number) { step10.companies.splice(i, 1) }

// --- Unemployment modal ---
const showUnemploymentModal  = ref(false)
const editingUnemploymentIdx = ref<number | null>(null)
const unemploymentForm       = reactive(blankUnemployment())

function openAddUnemployment() {
    Object.assign(unemploymentForm, blankUnemployment())
    editingUnemploymentIdx.value = null
    showUnemploymentModal.value = true
}
function openEditUnemployment(i: number) {
    Object.assign(unemploymentForm, { ...step10.unemployment_periods[i] })
    editingUnemploymentIdx.value = i
    showUnemploymentModal.value = true
}
function saveUnemploymentModal() {
    if (!unemploymentForm.start_date) return
    if (editingUnemploymentIdx.value !== null) {
        Object.assign(step10.unemployment_periods[editingUnemploymentIdx.value], { ...unemploymentForm })
    } else {
        step10.unemployment_periods.push({ ...unemploymentForm })
    }
    showUnemploymentModal.value = false
}
function removeUnemployment(i: number) { step10.unemployment_periods.splice(i, 1) }

// --- Related employment modal ---
const showRelatedModal   = ref(false)
const editingRelatedIdx  = ref<number | null>(null)
const relatedForm        = reactive(blankRelated())

function openAddRelated() {
    Object.assign(relatedForm, blankRelated())
    editingRelatedIdx.value = null
    showRelatedModal.value = true
}
function openEditRelated(i: number) {
    Object.assign(relatedForm, { ...step10.related_employments[i] })
    editingRelatedIdx.value = i
    showRelatedModal.value = true
}
function saveRelatedModal() {
    if (!relatedForm.start_date) return
    if (editingRelatedIdx.value !== null) {
        Object.assign(step10.related_employments[editingRelatedIdx.value], { ...relatedForm })
    } else {
        step10.related_employments.push({ ...relatedForm })
    }
    showRelatedModal.value = false
}
function removeRelated(i: number) { step10.related_employments.splice(i, 1) }

function submitStep10() {
    if (!coverageStats.value.is_complete) {
        alert('You must cover at least 10 years of employment history before continuing.')
        return
    }
    if (!step10.has_correct_information) {
        alert('You must confirm that the information above is correct and contains no missing information.')
        return
    }
    router.put(namedRoute('updateStep', { driver: props.driver!.id, step: 10 }), {
        companies:               step10.companies,
        unemployment_periods:    step10.has_unemployment_periods ? step10.unemployment_periods : [],
        related_employments:     step10.related_employments,
        has_correct_information: step10.has_correct_information,
    })
}

// --- Search Company modal ---
const showSearchModal   = ref(false)
const companySearchTerm = ref('')
const searchResults     = ref<any[]>([])
const searchLoading     = ref(false)
const searchError       = ref('')
let searchTimer: any    = null

function onSearchInput() {
    clearTimeout(searchTimer)
    searchError.value = ''
    if (!companySearchTerm.value.trim()) { searchResults.value = []; return }
    searchLoading.value = true
    searchTimer = setTimeout(async () => {
        try {
            const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? ''
            const url = `${namedRoute('employmentSearchCompanies')}?q=${encodeURIComponent(companySearchTerm.value)}`
            const res = await fetch(url, {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            })
            if (!res.ok) throw new Error(`HTTP ${res.status}`)
            const data = await res.json()
            searchResults.value = Array.isArray(data) ? data : []
        } catch (e: any) {
            console.error('Company search failed:', e)
            searchError.value = 'Search failed. Please try again.'
            searchResults.value = []
        } finally {
            searchLoading.value = false
        }
    }, 300)
}
function selectSearchedCompany(c: any) {
    Object.assign(companyForm, {
        company_name: c.company_name ?? '',
        address:      c.address ?? '',
        city:         c.city ?? '',
        state:        c.state ?? '',
        zip:          c.zip ?? '',
        phone:        c.phone ?? '',
        fax:          c.fax ?? '',
        contact:      c.contact ?? '',
        email:        c.email ?? '',
    })
    showSearchModal.value  = false
    editingCompanyIdx.value = null
    showCompanyModal.value = true
    companySearchTerm.value = ''
    searchResults.value     = []
}

// --- Coverage computed ---
const coverageStats = computed(() => {
    function calcYears(from: string, to: string) {
        if (!from) return 0
        const f = new Date(from.replace(/(\d{2})\/(\d{2})\/(\d{4})/, '$3-$1-$2'))
        const t = to ? new Date(to.replace(/(\d{2})\/(\d{2})\/(\d{4})/, '$3-$1-$2')) : new Date()
        return Math.max(0, (t.getTime() - f.getTime()) / (365.25 * 24 * 3600 * 1000))
    }
    const empYears  = step10.companies.reduce((s: number, c: any) => s + calcYears(c.employed_from, c.employed_to), 0)
    const unempYears = step10.unemployment_periods.reduce((s: number, u: any) => s + calcYears(u.start_date, u.end_date), 0)
    const relYears  = step10.related_employments.reduce((s: number, r: any) => s + calcYears(r.start_date, r.end_date), 0)
    const total     = empYears + unempYears + relYears
    const required  = 10
    const pct       = Math.min(100, Math.round((total / required) * 100))
    return {
        employment_years:         Math.round(empYears * 10) / 10,
        unemployment_years:       Math.round(unempYears * 10) / 10,
        related_employment_years: Math.round(relYears * 10) / 10,
        total_years:              Math.round(total * 10) / 10,
        required_years:           required,
        coverage_percentage:      pct,
        is_complete:              total >= required,
    }
})

// --- Combined history (sorted newest first) ---
const combinedHistory = computed(() => {
    const items: any[] = []
    step10.companies.forEach((c: any, i: number) => {
        items.push({ type: 'employed', note: c.company_name, from: c.employed_from, to: c.employed_to, idx: i, email: c.email, email_sent: c.email_sent, id: c.id })
    })
    step10.unemployment_periods.forEach((u: any, i: number) => {
        items.push({ type: 'unemployed', note: u.comments || '—', from: u.start_date, to: u.end_date, idx: i })
    })
    step10.related_employments.forEach((r: any, i: number) => {
        items.push({ type: 'related', note: r.position || '—', from: r.start_date, to: r.end_date, idx: i })
    })
    return items.sort((a, b) => {
        const da = a.from ? new Date(a.from.replace(/(\d{2})\/(\d{2})\/(\d{4})/, '$3-$1-$2')).getTime() : 0
        const db = b.from ? new Date(b.from.replace(/(\d{2})\/(\d{2})\/(\d{4})/, '$3-$1-$2')).getTime() : 0
        return db - da
    })
})

// --- Email actions ---
const emailLoading = ref<Record<number, boolean>>({})

async function sendEmail(company: any) {
    if (!company.id) { alert('Save the record first before sending email.'); return }
    emailLoading.value[company.id] = true
    try {
        const res = await axios.post(namedRoute('employmentSendEmail', { driver: props.driver!.id, company: company.id }))
        company.email_sent = true
    } catch (e: any) {
        alert(e.response?.data?.message ?? 'Failed to send email.')
    } finally {
        delete emailLoading.value[company.id]
    }
}
async function resendEmail(company: any) {
    if (!company.id) { alert('Save the record first.'); return }
    emailLoading.value[company.id] = true
    try {
        await axios.post(namedRoute('employmentResendEmail', { driver: props.driver!.id, company: company.id }))
        company.email_sent = true
    } catch (e: any) {
        alert(e.response?.data?.message ?? 'Failed to resend email.')
    } finally {
        delete emailLoading.value[company.id]
    }
}
async function toggleEmailSent(company: any, sent: boolean) {
    if (!company.id) return
    try {
        await axios.post(namedRoute('employmentMarkEmailStatus', { driver: props.driver!.id, company: company.id }), { sent })
        company.email_sent = sent
    } catch {}
}

// Count unsent emails with email address
const unsentEmailCount = computed(() =>
    step10.companies.filter((c: any) => c.email && !c.email_sent && c.id).length
)

// ------------------------------------------------------------------
// Step 11 – Policy
// ------------------------------------------------------------------
const step11 = reactive({
    consent_all_policies_attached: props.stepData?.step11?.consent_all_policies_attached ?? false,
    substance_testing_consent:     props.stepData?.step11?.substance_testing_consent     ?? false,
    authorization_consent:         props.stepData?.step11?.authorization_consent         ?? false,
    fmcsa_clearinghouse_consent:   props.stepData?.step11?.fmcsa_clearinghouse_consent   ?? false,
    company_name:                  props.stepData?.step11?.company_name                  ?? '',
    license_number:                props.stepData?.step11?.license_number                ?? null,
    license_state:                 props.stepData?.step11?.license_state                 ?? null,
    policy_document_url:           props.stepData?.step11?.policy_document_url           ?? null,
})

const step11Errors = ref<string[]>([])

function submitStep11() {
    const errs: string[] = []
    if (!step11.consent_all_policies_attached) errs.push('You must agree to all policies attached.')
    if (!step11.substance_testing_consent) errs.push('You must consent to substance testing.')
    if (!step11.authorization_consent) errs.push('You must consent to authorization.')
    if (!step11.fmcsa_clearinghouse_consent) errs.push('You must consent to FMCSA Clearinghouse queries.')
    step11Errors.value = errs
    if (errs.length) return
    router.put(namedRoute('updateStep', { driver: props.driver!.id, step: 11 }), {
        consent_all_policies_attached: step11.consent_all_policies_attached,
        substance_testing_consent:     step11.substance_testing_consent,
        authorization_consent:         step11.authorization_consent,
        fmcsa_clearinghouse_consent:   step11.fmcsa_clearinghouse_consent,
        company_name:                  step11.company_name,
    })
}

// ------------------------------------------------------------------
// Step 12 – Criminal
// ------------------------------------------------------------------
const step12 = reactive({
    has_criminal_charges:    props.stepData?.step12?.has_criminal_charges    ?? null as boolean | null,
    has_felony_conviction:   props.stepData?.step12?.has_felony_conviction   ?? null as boolean | null,
    has_minister_permit:     props.stepData?.step12?.has_minister_permit     ?? null as boolean | null,
    fcra_consent:            props.stepData?.step12?.fcra_consent            ?? false,
    background_info_consent: props.stepData?.step12?.background_info_consent ?? false,
    // read-only display fields
    full_name:     (props.stepData?.step12 as any)?.full_name     ?? props.driver?.name ?? null,
    middle_name:   (props.stepData?.step12 as any)?.middle_name   ?? props.driver?.middle_name ?? null,
    last_name:     (props.stepData?.step12 as any)?.last_name     ?? props.driver?.last_name ?? null,
    date_of_birth: (props.stepData?.step12 as any)?.date_of_birth ?? props.driver?.date_of_birth ?? null,
    ssn_last_four: (props.stepData?.step12 as any)?.ssn_last_four ?? null,
    license_number:(props.stepData?.step12 as any)?.license_number ?? null,
    license_state: (props.stepData?.step12 as any)?.license_state  ?? null,
})

const step12Errors = ref<string[]>([])

function submitStep12() {
    const errs: string[] = []
    if (step12.has_criminal_charges === null) errs.push('Please answer the criminal charges question.')
    if (step12.has_felony_conviction === null) errs.push('Please answer the felony conviction question.')
    if (!step12.fcra_consent) errs.push('You must accept the FCRA consent.')
    if (!step12.background_info_consent) errs.push('You must certify that the information is correct.')
    step12Errors.value = errs
    if (errs.length) return
    router.put(namedRoute('updateStep', { driver: props.driver!.id, step: 12 }), {
        has_criminal_charges:    step12.has_criminal_charges,
        has_felony_conviction:   step12.has_felony_conviction,
        has_minister_permit:     step12.has_minister_permit,
        fcra_consent:            step12.fcra_consent,
        background_info_consent: step12.background_info_consent,
    })
}

// ------------------------------------------------------------------
// Step 13 – W-9
// ------------------------------------------------------------------
const step13 = reactive({
    name:                 props.stepData?.step13?.name               ?? '',
    business_name:        props.stepData?.step13?.business_name      ?? '',
    tax_classification:   props.stepData?.step13?.tax_classification ?? '',
    llc_classification:   props.stepData?.step13?.llc_classification ?? '',
    other_classification: props.stepData?.step13?.other_classification ?? '',
    has_foreign_partners: props.stepData?.step13?.has_foreign_partners ?? false,
    exempt_payee_code:    props.stepData?.step13?.exempt_payee_code  ?? '',
    fatca_exemption_code: props.stepData?.step13?.fatca_exemption_code ?? '',
    address:              props.stepData?.step13?.address            ?? '',
    city:                 props.stepData?.step13?.city               ?? '',
    state:                props.stepData?.step13?.state              ?? '',
    zip_code:             props.stepData?.step13?.zip_code           ?? '',
    account_numbers:      props.stepData?.step13?.account_numbers    ?? '',
    tin_type:             props.stepData?.step13?.tin_type           ?? 'ssn',
    tin:                  props.stepData?.step13?.tin                ?? '',
    signature:            props.stepData?.step13?.signature          ?? '',
    signed_date:          toUsDate(props.stepData?.step13?.signed_date) || todayUs(),
    pdf_url:              (props.stepData?.step13 as any)?.pdf_url   ?? null,
})

const step13Errors = ref<string[]>([])

function submitStep13() {
    const errs: string[] = []
    if (!step13.name.trim()) errs.push('Name is required.')
    if (!step13.tax_classification) errs.push('Please select a tax classification.')
    if (step13.tax_classification === 'llc' && !step13.llc_classification) errs.push('LLC tax classification letter is required.')
    if (step13.tax_classification === 'other' && !step13.other_classification.trim()) errs.push('Please specify the other classification.')
    if (!step13.address.trim()) errs.push('Address is required.')
    if (!step13.city.trim()) errs.push('City is required.')
    if (!step13.state.trim()) errs.push('State is required.')
    if (!step13.zip_code.trim()) errs.push('ZIP Code is required.')
    if (!step13.tin.trim()) errs.push('TIN is required.')
    step13Errors.value = errs
    if (errs.length) return
    router.put(namedRoute('updateStep', { driver: props.driver!.id, step: 13 }), { ...step13 })
}

function onTinInput(e: Event) {
    const digits = (e.target as HTMLInputElement).value.replace(/\D/g, '').slice(0, 9)
    if (step13.tin_type === 'ssn') {
        if (digits.length > 5)      step13.tin = `${digits.slice(0,3)}-${digits.slice(3,5)}-${digits.slice(5)}`
        else if (digits.length > 3) step13.tin = `${digits.slice(0,3)}-${digits.slice(3)}`
        else                        step13.tin = digits
    } else {
        step13.tin = digits.length > 2 ? `${digits.slice(0,2)}-${digits.slice(2)}` : digits
    }
}
function onZipInput(e: Event) {
    step13.zip_code = (e.target as HTMLInputElement).value.replace(/\D/g, '').slice(0, 5)
}
function onExemptCodeInput(e: Event) {
    step13.exempt_payee_code = (e.target as HTMLInputElement).value.replace(/\D/g, '').slice(0, 2)
}

// ------------------------------------------------------------------
// Step 14 – Certification
// ------------------------------------------------------------------
const step14 = reactive({
    is_accepted:         (props.stepData?.step14 as any)?.is_accepted    ?? false,
    signature:           (props.stepData?.step14 as any)?.signature      ?? '',
    signature_url:       (props.stepData?.step14 as any)?.signature_url  ?? null,
    employment_history:  (props.stepData?.step14 as any)?.employment_history ?? [] as any[],
})

// Signature pad state
const showSignatureModal = ref(false)
const signatureCanvas = ref<HTMLCanvasElement | null>(null)
let signaturePad: any = null

async function openSignatureModal() {
    showSignatureModal.value = true
    await nextTick()
    initSignaturePad()
}

function closeSignatureModal() {
    showSignatureModal.value = false
}

function initSignaturePad() {
    const canvas = signatureCanvas.value
    if (!canvas) return
    import('signature_pad').then(({ default: SignaturePad }) => {
        const ratio = Math.max(window.devicePixelRatio || 1, 1)
        canvas.width  = canvas.offsetWidth  * ratio
        canvas.height = canvas.offsetHeight * ratio
        canvas.getContext('2d')!.scale(ratio, ratio)
        signaturePad = new SignaturePad(canvas, { backgroundColor: 'rgb(255,255,255)', penColor: 'rgb(0,0,0)' })
        // Reload existing signature if any
        if (step14.signature && step14.signature.startsWith('data:image')) {
            signaturePad.fromDataURL(step14.signature)
        }
    })
}

function clearSignature() {
    signaturePad?.clear()
}

function saveSignature() {
    if (!signaturePad || signaturePad.isEmpty()) {
        alert('Please provide a signature first.')
        return
    }
    step14.signature = signaturePad.toDataURL('image/png')
    step14.signature_url = null // cleared so we show the new preview
    closeSignatureModal()
}

const step14Errors = ref<string[]>([])

function submitStep14() {
    const errs: string[] = []
    if (!step14.signature) errs.push('A signature is required.')
    if (!step14.is_accepted) errs.push('You must certify that all information is true and complete.')
    step14Errors.value = errs
    if (errs.length) return
    router.put(namedRoute('updateStep', { driver: props.driver!.id, step: 14 }), {
        signature:   step14.signature,
        is_accepted: step14.is_accepted,
    })
}

// ------------------------------------------------------------------
// Step 15 – Clearinghouse / Finalize
// ------------------------------------------------------------------
const step15 = reactive({
    application_completed:   props.stepData?.step15?.application_completed   ?? false,
    total_percentage:        props.stepData?.step15?.total_percentage        ?? 0,
    steps_needing_attention: props.stepData?.step15?.steps_needing_attention ?? [],
    is_complete:             props.stepData?.step15?.is_complete             ?? false,
})

function submitStep15() {
    if (!step15.is_complete) return
    router.put(namedRoute('updateStep', { driver: props.driver!.id, step: 15 }), {})
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
            <a :href="namedRoute('index')" class="flex items-center gap-1 text-slate-500 hover:text-slate-700 text-sm">
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
                    :disabled="!canGoToStep(step.number)"
                    class="flex items-center gap-1.5 px-3 py-2.5 text-xs font-medium rounded-t-lg transition-colors"
                    :class="{
                        'bg-white dark:bg-darkmode-600 border border-b-white dark:border-b-darkmode-600 border-slate-200 dark:border-slate-600 text-primary -mb-px': currentStep === step.number,
                        'text-slate-500 hover:text-slate-700 hover:bg-slate-50': currentStep !== step.number && canGoToStep(step.number),
                        'text-slate-300 cursor-not-allowed': !canGoToStep(step.number),
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
                        <TomSelect v-model="step1.carrier_id" :disabled="props.carrierLocked || (!!props.selectedCarrierId && !isEditMode)">
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
                    <div v-if="!carrierLocked">
                        <label class="block text-sm font-medium mb-1">HOS Cycle</label>
                        <TomSelect v-model="step1.hos_cycle_type">
                            <option value="70_8">70 hours / 8 days</option>
                            <option value="60_7">60 hours / 7 days</option>
                        </TomSelect>
                    </div>
                    <div v-if="!carrierLocked">
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

                    <div v-if="!carrierLocked" class="border border-slate-200 rounded-lg p-4">
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

                <!-- Frontend validation errors -->
                <div v-if="step1Errors.length" class="mt-4 p-4 bg-red-50 border border-red-200 rounded-xl">
                    <ul class="list-disc list-inside space-y-1">
                        <li v-for="e in step1Errors" :key="e" class="text-sm text-red-700">{{ e }}</li>
                    </ul>
                </div>

                <!-- Server validation errors summary -->
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

                <!-- Validation errors -->
                <div v-if="step2Errors.length" class="mb-4 p-4 bg-red-50 border border-red-200 rounded-xl">
                    <ul class="list-disc list-inside space-y-1">
                        <li v-for="e in step2Errors" :key="e" class="text-sm text-red-700">{{ e }}</li>
                    </ul>
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

                    <!-- License Images -->
                    <div class="mt-4 pt-4 border-t border-slate-100">
                        <h4 class="text-sm font-medium mb-3">License Images</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium mb-1">License Front</label>
                                <input type="file" accept="image/*,application/pdf" @change="(e) => onLicFront(e, i)" class="w-full text-sm" />
                                <img v-if="licFrontPreviews[i]" :src="licFrontPreviews[i]!" alt="License front preview" class="mt-2 rounded border max-h-32 object-contain" />
                                <template v-else-if="lic.front_url">
                                    <img :src="lic.front_url" alt="License front" class="mt-2 rounded border max-h-32 object-contain" />
                                    <a :href="lic.front_url" target="_blank" class="text-xs text-primary mt-1 block">View full size</a>
                                </template>
                            </div>
                            <div>
                                <label class="block text-xs font-medium mb-1">License Back</label>
                                <input type="file" accept="image/*,application/pdf" @change="(e) => onLicBack(e, i)" class="w-full text-sm" />
                                <img v-if="licBackPreviews[i]" :src="licBackPreviews[i]!" alt="License back preview" class="mt-2 rounded border max-h-32 object-contain" />
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

                <!-- Validation errors -->
                <div v-if="step4Errors.length" class="mb-4 p-4 bg-red-50 border border-red-200 rounded-xl">
                    <ul class="list-disc list-inside space-y-1">
                        <li v-for="e in step4Errors" :key="e" class="text-sm text-red-700">{{ e }}</li>
                    </ul>
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
                        <div class="md:col-span-2">
                            <label class="block text-xs font-medium mb-2">Which skills were trained in this program? <span class="text-slate-400 font-normal">(select all that apply)</span></label>
                            <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                                <FormCheck v-for="skill in trainingSkillOptions" :key="skill.value">
                                    <FormCheck.Input
                                        type="checkbox"
                                        :value="skill.value"
                                        :checked="school.training_skills?.includes(skill.value)"
                                        @change="(e: Event) => {
                                            const el = e.target as HTMLInputElement
                                            if (!school.training_skills) school.training_skills = []
                                            if (el.checked) {
                                                if (!school.training_skills.includes(skill.value)) school.training_skills.push(skill.value)
                                            } else {
                                                school.training_skills = school.training_skills.filter((v: string) => v !== skill.value)
                                            }
                                        }"
                                    />
                                    <FormCheck.Label>{{ skill.label }}</FormCheck.Label>
                                </FormCheck>
                            </div>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-xs font-medium mb-1">Certificate (PDF or image)</label>
                            <input type="file" accept=".pdf,image/*" @change="e => onSchoolCertChange(e, si)" class="w-full text-sm" />
                            <a v-if="school.certificate_url && !school.certificate_file" :href="school.certificate_url" target="_blank" class="text-xs text-primary hover:underline mt-1 inline-flex items-center gap-1">
                                <Lucide icon="FileText" class="w-3 h-3" /> View current certificate
                            </a>
                            <span v-if="school.certificate_file" class="text-xs text-success mt-1 inline-flex items-center gap-1">
                                <Lucide icon="CheckCircle" class="w-3 h-3" /> New file selected: {{ (school.certificate_file as File).name }}
                            </span>
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
                            <div class="md:col-span-3">
                                <label class="block text-xs font-medium mb-1">Certificate (PDF or image)</label>
                                <input type="file" accept=".pdf,image/*" @change="e => onCourseCertChange(e, ci)" class="w-full text-sm" />
                                <a v-if="course.certificate_url && !course.certificate_file" :href="course.certificate_url" target="_blank" class="text-xs text-primary hover:underline mt-1 inline-flex items-center gap-1">
                                    <Lucide icon="FileText" class="w-3 h-3" /> View current certificate
                                </a>
                                <span v-if="course.certificate_file" class="text-xs text-success mt-1 inline-flex items-center gap-1">
                                    <Lucide icon="CheckCircle" class="w-3 h-3" /> New file selected: {{ (course.certificate_file as File).name }}
                                </span>
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
                            <div class="md:col-span-2">
                                <label class="block text-xs font-medium mb-1">Supporting Document (PDF or image)</label>
                                <input type="file" accept=".pdf,image/*" @change="e => onConvictionImageChange(e, i)" class="w-full text-sm" />
                                <a v-if="c.image_url && !c.image_file" :href="c.image_url" target="_blank" class="text-xs text-primary hover:underline mt-1 inline-flex items-center gap-1">
                                    <Lucide icon="FileText" class="w-3 h-3" /> View current document
                                </a>
                                <span v-if="c.image_file" class="text-xs text-success mt-1 inline-flex items-center gap-1">
                                    <Lucide icon="CheckCircle" class="w-3 h-3" /> New file selected: {{ (c.image_file as File).name }}
                                </span>
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
                            <div class="flex items-center gap-3 md:col-span-2">
                                <FormCheck>
                                    <FormCheck.Input v-model="a.had_fatalities" type="checkbox" />
                                    <FormCheck.Label>Involved Fatalities</FormCheck.Label>
                                </FormCheck>
                                <FormCheck>
                                    <FormCheck.Input v-model="a.had_injuries" type="checkbox" />
                                    <FormCheck.Label>Involved Injuries</FormCheck.Label>
                                </FormCheck>
                            </div>
                            <div v-if="a.had_fatalities">
                                <label class="block text-xs font-medium mb-1">Number of Fatalities</label>
                                <FormInput v-model.number="a.number_of_fatalities" type="number" min="0" />
                            </div>
                            <div v-if="a.had_injuries">
                                <label class="block text-xs font-medium mb-1">Number of Injuries</label>
                                <FormInput v-model.number="a.number_of_injuries" type="number" min="0" />
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-xs font-medium mb-1">Comments</label>
                                <FormInput v-model="a.comments" placeholder="Additional notes" />
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-xs font-medium mb-1">Supporting Document (PDF or image)</label>
                                <input type="file" accept=".pdf,image/*" @change="e => onAccidentImageChange(e, i)" class="w-full text-sm" />
                                <a v-if="a.image_url && !a.image_file" :href="a.image_url" target="_blank" class="text-xs text-primary hover:underline mt-1 inline-flex items-center gap-1">
                                    <Lucide icon="FileText" class="w-3 h-3" /> View current document
                                </a>
                                <span v-if="a.image_file" class="text-xs text-success mt-1 inline-flex items-center gap-1">
                                    <Lucide icon="CheckCircle" class="w-3 h-3" /> New file selected: {{ (a.image_file as File).name }}
                                </span>
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
                <h2 class="text-lg font-semibold mb-1">Federal Motor Carrier Safety Regulations (FMCSR)</h2>
                <p class="text-sm text-slate-500 mb-6">Answer each question truthfully. A "Yes" answer does not automatically disqualify an applicant. If "Yes", provide additional details in the field that appears.</p>

                <div class="space-y-4">

                    <!-- 1. Disqualified -->
                    <div class="border border-slate-200 rounded-lg overflow-hidden">
                        <div class="flex items-start gap-3 p-4">
                            <FormCheck.Input v-model="step9.is_disqualified" type="checkbox" class="mt-0.5 shrink-0" />
                            <div>
                                <p class="text-sm font-medium">Under FMCSR 391.15, are you currently disqualified from driving a commercial motor vehicle? <span class="text-slate-400 font-normal">[49 CFR 391.15]</span></p>
                            </div>
                        </div>
                        <div v-if="step9.is_disqualified" class="px-4 pb-4 bg-amber-50 border-t border-amber-100">
                            <label class="block text-xs font-medium mb-1 mt-3">Please provide details <span class="text-danger">*</span></label>
                            <textarea v-model="step9.disqualified_details" rows="3" class="w-full text-sm border border-slate-300 rounded-md px-3 py-2 focus:outline-none focus:ring-1 focus:ring-primary" placeholder="State, date, reason, and resolution..." />
                        </div>
                    </div>

                    <!-- 2. License suspended -->
                    <div class="border border-slate-200 rounded-lg overflow-hidden">
                        <div class="flex items-start gap-3 p-4">
                            <FormCheck.Input v-model="step9.is_license_suspended" type="checkbox" class="mt-0.5 shrink-0" />
                            <div>
                                <p class="text-sm font-medium">Has your license, permit, or privilege to drive ever been suspended or revoked for any reason? <span class="text-slate-400 font-normal">[49 CFR 391.21(b)(9)]</span></p>
                            </div>
                        </div>
                        <div v-if="step9.is_license_suspended" class="px-4 pb-4 bg-amber-50 border-t border-amber-100">
                            <label class="block text-xs font-medium mb-1 mt-3">Please provide details <span class="text-danger">*</span></label>
                            <textarea v-model="step9.suspension_details" rows="3" class="w-full text-sm border border-slate-300 rounded-md px-3 py-2 focus:outline-none focus:ring-1 focus:ring-primary" placeholder="State, date, reason, and resolution..." />
                        </div>
                    </div>

                    <!-- 3. License denied -->
                    <div class="border border-slate-200 rounded-lg overflow-hidden">
                        <div class="flex items-start gap-3 p-4">
                            <FormCheck.Input v-model="step9.is_license_denied" type="checkbox" class="mt-0.5 shrink-0" />
                            <div>
                                <p class="text-sm font-medium">Have you ever been denied a license, permit, or privilege to operate a motor vehicle? <span class="text-slate-400 font-normal">[49 CFR 391.21(b)(9)]</span></p>
                            </div>
                        </div>
                        <div v-if="step9.is_license_denied" class="px-4 pb-4 bg-amber-50 border-t border-amber-100">
                            <label class="block text-xs font-medium mb-1 mt-3">Please provide details <span class="text-danger">*</span></label>
                            <textarea v-model="step9.denial_details" rows="3" class="w-full text-sm border border-slate-300 rounded-md px-3 py-2 focus:outline-none focus:ring-1 focus:ring-primary" placeholder="State, date, and reason..." />
                        </div>
                    </div>

                    <!-- 4. Positive drug/alcohol test -->
                    <div class="border border-slate-200 rounded-lg overflow-hidden">
                        <div class="flex items-start gap-3 p-4">
                            <FormCheck.Input v-model="step9.has_positive_drug_test" type="checkbox" class="mt-0.5 shrink-0" />
                            <div>
                                <p class="text-sm font-medium">Within the past two years, have you tested positive, or refused to test, on a pre-employment drug or alcohol test by an employer to whom you applied, but did not obtain, safety-sensitive transportation work covered by DOT agency drug and alcohol testing rules? <span class="text-slate-400 font-normal">[49 CFR 40.25(j)]</span></p>
                            </div>
                        </div>
                        <div v-if="step9.has_positive_drug_test" class="px-4 pb-4 bg-amber-50 border-t border-amber-100">
                            <p class="text-xs text-slate-500 mt-3 mb-2">If yes, please provide the name of the Substance Abuse Professional (SAP) that evaluated you below, along with the name of the agency that performed your return to duty test.</p>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-medium mb-1">Substance Abuse Professional</label>
                                    <FormInput v-model="step9.substance_abuse_professional" placeholder="Enter name" />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium mb-1">Phone</label>
                                    <FormInput v-model="step9.sap_phone" type="tel" placeholder="Enter phone number" />
                                </div>
                            </div>
                            <div class="mt-3">
                                <label class="block text-xs font-medium mb-1">Return to Duty Test Agency</label>
                                <FormInput v-model="step9.return_duty_agency" placeholder="Enter agency name" />
                            </div>
                            <div class="mt-4 border-t border-amber-200 pt-3">
                                <p class="text-xs text-slate-500 italic mb-2">*If you answered yes to the above question please agree to Consent for Release of Information regarding Previous Pre-Employment Controlled Substances or Alcohol Testing form.*</p>
                                <div class="flex items-start gap-2">
                                    <FormCheck.Input v-model="step9.consent_to_release" type="checkbox" class="mt-0.5 shrink-0" />
                                    <p class="text-sm">Do you agree and consent to the above?</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 5. Duty offenses -->
                    <div class="border border-slate-200 rounded-lg overflow-hidden">
                        <div class="flex items-start gap-3 p-4">
                            <FormCheck.Input v-model="step9.has_duty_offenses" type="checkbox" class="mt-0.5 shrink-0" />
                            <div>
                                <p class="text-sm font-medium">In the past three (3) years, have you ever been convicted of any of the following offenses committed during on-duty time? <span class="text-slate-400 font-normal">[49 CFR 391.15 and 49 CFR 395.2]</span></p>
                            </div>
                        </div>
                        <div v-if="step9.has_duty_offenses" class="px-4 pb-4 bg-amber-50 border-t border-amber-100">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-3">
                                <div>
                                    <label class="block text-xs font-medium mb-1">Date of Most Recent Conviction</label>
                                    <Litepicker v-model="step9.recent_conviction_date" :options="lpOptions" />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium mb-1">Offense Details <span class="text-danger">*</span></label>
                                    <textarea v-model="step9.offense_details" rows="3" class="w-full text-sm border border-slate-300 rounded-md px-3 py-2 focus:outline-none focus:ring-1 focus:ring-primary" placeholder="Describe the offense(s)..." />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Request for Check of Driving Record -->
                    <div class="border border-primary/30 rounded-lg bg-primary/5 p-4 space-y-3">
                        <p class="text-sm font-semibold text-primary">Request for Check of Driving Record</p>
                        <p class="text-sm text-slate-600">I understand that according to the Federal Motor Carrier Safety Regulations, my previous driving record will be investigated and that my employment is subject to satisfactory reports from previous employers and other sources.</p>
                        <div class="flex items-start gap-3 pt-1">
                            <FormCheck.Input v-model="step9.consent_driving_record" type="checkbox" class="mt-0.5 shrink-0" />
                            <p class="text-sm font-medium">Do you agree and consent to the above?</p>
                        </div>
                    </div>

                </div>

                <!-- Validation errors -->
                <div v-if="step9Errors.length" class="mb-4 p-4 bg-red-50 border border-red-200 rounded-xl">
                    <ul class="list-disc list-inside space-y-1">
                        <li v-for="e in step9Errors" :key="e" class="text-sm text-red-700">{{ e }}</li>
                    </ul>
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
                <h2 class="text-lg font-semibold mb-1">Employment History</h2>

                <!-- Regulatory notice -->
                <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 mb-5 text-sm text-slate-700">
                    <p><strong>All driver applicants must provide information on all work references during the preceding <span class="font-bold">three (3) years</span></strong> from the date application is submitted. Those applying to operate a <strong>commercial motor vehicle</strong> as defined in §383.5 (requiring a CDL) shall provide <strong>ten (10) years</strong> of employment history.</p>
                    <p class="mt-2"><strong>NOTE: Please list companies in reverse order starting with the most recent and leave no gaps in employment history.</strong></p>
                </div>

                <!-- Unsent emails banner -->
                <div v-if="unsentEmailCount > 0" class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-md flex items-center justify-between">
                    <div class="flex items-center gap-2 text-sm text-blue-800">
                        <Lucide icon="Mail" class="w-4 h-4 text-blue-600" />
                        <span><strong>{{ unsentEmailCount }}</strong> verification {{ unsentEmailCount === 1 ? 'email' : 'emails' }} ready to send</span>
                    </div>
                    <Button size="sm" variant="primary" @click="step10.companies.filter((c:any)=>c.email&&!c.email_sent&&c.id).forEach((c:any)=>sendEmail(c))">
                        <Lucide icon="Send" class="w-3.5 h-3.5 mr-1" /> Send All Verification Emails
                    </Button>
                </div>

                <!-- ── Employment History Summary ────────────────── -->
                <div class="mb-5">
                    <h3 class="text-sm font-semibold mb-3">Employment History Summary</h3>

                    <div v-if="combinedHistory.length === 0" class="text-center py-5 text-slate-400 text-sm border border-dashed rounded-lg">
                        No employment records yet. Use the buttons below to add companies, unemployment periods, or other positions.
                    </div>
                    <div v-else class="overflow-x-auto rounded-lg border border-slate-200">
                        <table class="min-w-full text-sm">
                            <thead class="bg-slate-50 text-xs text-slate-500 uppercase">
                                <tr>
                                    <th class="px-4 py-2 text-left">Status</th>
                                    <th class="px-4 py-2 text-left">Note</th>
                                    <th class="px-4 py-2 text-left">Start Date</th>
                                    <th class="px-4 py-2 text-left">End Date</th>
                                    <th class="px-4 py-2 text-left">Email Status</th>
                                    <th class="px-4 py-2 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="(item, i) in combinedHistory" :key="i" class="hover:bg-slate-50">
                                    <td class="px-4 py-2">
                                        <span v-if="item.type === 'employed'" class="inline-flex items-center rounded bg-green-100 px-2 py-0.5 text-xs font-semibold text-green-800">EMPLOYED</span>
                                        <span v-else-if="item.type === 'related'" class="inline-flex items-center rounded bg-blue-100 px-2 py-0.5 text-xs font-semibold text-blue-800">RELATED EMPLOYMENT</span>
                                        <span v-else class="inline-flex items-center rounded bg-yellow-100 px-2 py-0.5 text-xs font-semibold text-yellow-800">UNEMPLOYED</span>
                                    </td>
                                    <td class="px-4 py-2 font-medium">{{ item.note }}</td>
                                    <td class="px-4 py-2 text-slate-500">{{ item.from || '—' }}</td>
                                    <td class="px-4 py-2 text-slate-500">{{ item.to || '—' }}</td>
                                    <td class="px-4 py-2">
                                        <template v-if="item.type === 'employed'">
                                            <span v-if="!item.email" class="inline-flex items-center rounded bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-500">No Email</span>
                                            <span v-else-if="item.email_sent" class="inline-flex items-center rounded bg-green-100 px-2 py-0.5 text-xs font-semibold text-green-700">Sent</span>
                                            <span v-else class="inline-flex items-center rounded bg-yellow-100 px-2 py-0.5 text-xs font-semibold text-yellow-700">Not Sent</span>
                                        </template>
                                        <span v-else class="inline-flex items-center rounded bg-slate-100 px-2 py-0.5 text-xs text-slate-400">N/A</span>
                                    </td>
                                    <td class="px-4 py-2 text-right">
                                        <div class="flex justify-end flex-wrap gap-1">
                                            <button
                                                @click="item.type==='employed' ? openEditCompany(item.idx) : item.type==='unemployed' ? openEditUnemployment(item.idx) : openEditRelated(item.idx)"
                                                class="text-primary border border-primary rounded px-2 py-1 text-xs flex items-center gap-1 hover:opacity-70">
                                                <Lucide icon="Pencil" class="w-3 h-3" /> Edit
                                            </button>
                                            <button
                                                @click="item.type==='employed' ? removeCompany(item.idx) : item.type==='unemployed' ? removeUnemployment(item.idx) : removeRelated(item.idx)"
                                                class="text-danger border border-danger rounded px-2 py-1 text-xs flex items-center gap-1 hover:opacity-70">
                                                <Lucide icon="Trash2" class="w-3 h-3" /> Delete
                                            </button>
                                            <!-- Email actions for employed type -->
                                            <template v-if="item.type === 'employed' && item.email && item.id">
                                                <button v-if="!item.email_sent"
                                                    @click="sendEmail(step10.companies[item.idx])"
                                                    :disabled="emailLoading[item.id]"
                                                    class="text-purple-600 border border-purple-400 rounded px-2 py-1 text-xs flex items-center gap-1 hover:opacity-70 disabled:opacity-50">
                                                    <Lucide icon="Send" class="w-3 h-3" />
                                                    <span v-if="emailLoading[item.id]">...</span>
                                                    <span v-else>Send Email</span>
                                                </button>
                                                <button v-else
                                                    @click="resendEmail(step10.companies[item.idx])"
                                                    :disabled="emailLoading[item.id]"
                                                    class="text-green-600 border border-green-500 rounded px-2 py-1 text-xs flex items-center gap-1 hover:opacity-70 disabled:opacity-50">
                                                    <Lucide icon="MailCheck" class="w-3 h-3" />
                                                    <span v-if="emailLoading[item.id]">...</span>
                                                    <span v-else>Resend</span>
                                                </button>
                                                <button v-if="item.email_sent"
                                                    @click="toggleEmailSent(step10.companies[item.idx], false)"
                                                    class="text-orange-500 border border-orange-400 rounded px-2 py-1 text-xs flex items-center gap-1 hover:opacity-70"
                                                    title="Mark as not sent">
                                                    <Lucide icon="X" class="w-3 h-3" /> Unsent
                                                </button>
                                                <button v-else
                                                    @click="toggleEmailSent(step10.companies[item.idx], true)"
                                                    class="text-slate-500 border border-slate-300 rounded px-2 py-1 text-xs flex items-center gap-1 hover:opacity-70"
                                                    title="Mark as sent">
                                                    <Lucide icon="Check" class="w-3 h-3" /> Mark Sent
                                                </button>
                                            </template>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Coverage indicator -->
                    <div class="mt-4 p-4 bg-slate-50 border border-slate-200 rounded-lg">
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-xs font-semibold text-slate-700">Employment History Coverage</span>
                            <span :class="coverageStats.is_complete ? 'text-success' : 'text-danger'" class="text-xs font-semibold">
                                {{ coverageStats.total_years }} / {{ coverageStats.required_years }} years ({{ coverageStats.coverage_percentage }}%)
                            </span>
                        </div>
                        <div class="w-full bg-slate-200 rounded-full h-2.5 mb-2">
                            <div :class="coverageStats.is_complete ? 'bg-success' : 'bg-danger'" class="h-2.5 rounded-full transition-all duration-300" :style="{ width: coverageStats.coverage_percentage + '%' }" />
                        </div>
                        <div class="grid grid-cols-3 gap-2 text-xs text-slate-500">
                            <span>Employment: <strong>{{ coverageStats.employment_years }} yrs</strong></span>
                            <span>Unemployment: <strong>{{ coverageStats.unemployment_years }} yrs</strong></span>
                            <span>Related: <strong>{{ coverageStats.related_employment_years }} yrs</strong></span>
                        </div>
                        <div v-if="!coverageStats.is_complete" class="mt-2 p-2 bg-amber-50 border border-amber-200 rounded text-xs text-amber-700">
                            You need {{ coverageStats.required_years - coverageStats.total_years }} more years to meet the minimum requirement. Add more employment, unemployment periods, or related employment.
                        </div>
                    </div>

                    <!-- Action buttons below summary -->
                    <div class="flex gap-2 mt-3">
                        <Button size="sm" variant="outline-success" @click="showSearchModal = true; companySearchTerm = ''; searchResults = []; searchError = ''">
                            <Lucide icon="Search" class="w-3.5 h-3.5 mr-1" /> Search Company
                        </Button>
                        <Button size="sm" variant="primary" @click="openAddCompany">
                            <Lucide icon="Plus" class="w-3.5 h-3.5 mr-1" /> Add New Employment
                        </Button>
                    </div>
                </div>

                <!-- ── Unemployment Periods ───────────────────────── -->
                <div class="mb-4 border border-slate-200 rounded-lg p-4">
                    <div class="flex items-center gap-3">
                        <FormCheck.Input v-model="step10.has_unemployment_periods" type="checkbox" id="has_unemployment" />
                        <label for="has_unemployment" class="text-sm font-medium cursor-pointer flex-1">Have you been unemployed at any time within the last 10 years?</label>
                        <Button v-if="step10.has_unemployment_periods" size="sm" variant="outline-secondary" @click="openAddUnemployment">
                            <Lucide icon="Plus" class="w-3.5 h-3.5 mr-1" /> Add Unemployment Period
                        </Button>
                    </div>
                    <div v-if="step10.has_unemployment_periods && step10.unemployment_periods.length === 0" class="mt-3 text-center py-3 text-slate-400 text-xs border border-dashed rounded-md">
                        No unemployment periods added yet. Click "Add Unemployment Period" to add one.
                    </div>
                    <div v-else-if="step10.has_unemployment_periods" class="mt-2 text-xs text-slate-500">
                        {{ step10.unemployment_periods.length }} period(s) added — visible in the summary table above.
                    </div>
                </div>

                <!-- ── Other Employment ───────────────────────────── -->
                <div class="border border-slate-200 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-semibold">Other Employment</h3>
                            <p class="text-xs text-slate-500 mt-0.5">Other positions (Cook, Warehouseman, Carpenter, Clerk…) not part of your regular employment history. Count toward the 10-year requirement.</p>
                        </div>
                        <Button size="sm" variant="outline-secondary" class="ml-4 shrink-0" @click="openAddRelated">
                            <Lucide icon="Plus" class="w-3.5 h-3.5 mr-1" /> Add Other Position
                        </Button>
                    </div>
                    <div v-if="step10.related_employments.length === 0" class="mt-3 text-center py-3 text-slate-400 text-xs border border-dashed rounded-md">
                        No other positions added yet.
                    </div>
                    <div v-else class="mt-2 text-xs text-slate-500">
                        {{ step10.related_employments.length }} position(s) added — visible in the summary table above.
                    </div>
                </div>

                <!-- Confirmation checkbox -->
                <div class="mt-5 border border-slate-200 rounded-lg p-4">
                    <div class="flex items-start gap-3">
                        <FormCheck.Input
                            v-model="step10.has_correct_information"
                            type="checkbox"
                            id="has_correct_info"
                            :disabled="!coverageStats.is_complete"
                            class="mt-0.5 shrink-0"
                        />
                        <label for="has_correct_info" :class="coverageStats.is_complete ? 'cursor-pointer' : 'opacity-50 cursor-not-allowed'" class="text-sm">
                            <span class="text-danger font-semibold">*</span> Is the information above correct and contains no missing information?
                        </label>
                    </div>
                    <p v-if="!coverageStats.is_complete" class="mt-2 text-xs text-amber-600 flex items-center gap-1">
                        <Lucide icon="AlertTriangle" class="w-3.5 h-3.5 shrink-0" />
                        This confirmation requires at least 10 years of employment history coverage (currently {{ coverageStats.total_years }} yrs).
                    </p>
                </div>

                <div class="flex justify-between mt-4">
                    <Button variant="outline-secondary" @click="currentStep = 9">
                        <Lucide icon="ChevronLeft" class="w-4 h-4 mr-1" /> Previous
                    </Button>
                    <Button variant="primary" @click="submitStep10" :disabled="!coverageStats.is_complete || !step10.has_correct_information">
                        Save & Continue <Lucide icon="ChevronRight" class="w-4 h-4 ml-1" />
                    </Button>
                </div>

                <!-- ══════════════════════════════════════════════════
                     MODAL – Search Company
                     ══════════════════════════════════════════════════ -->
                <Dialog :open="showSearchModal" @close="showSearchModal = false" size="lg">
                    <Dialog.Panel>
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-5 pb-4 border-b">
                                <div>
                                    <h3 class="text-lg font-semibold">Search Previous Employer</h3>
                                    <p class="text-xs text-slate-500 mt-0.5">Find and select a company from the database</p>
                                </div>
                                <button @click="showSearchModal = false" class="text-slate-400 hover:text-slate-600"><Lucide icon="X" class="w-5 h-5" /></button>
                            </div>
                            <div class="mb-4">
                                <label class="block text-xs font-medium mb-1">Company Name</label>
                                <div class="relative">
                                    <Lucide icon="Search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" />
                                    <input v-model="companySearchTerm" @input="onSearchInput" type="text"
                                        class="w-full pl-9 pr-4 py-2.5 text-sm border border-slate-300 rounded-md focus:outline-none focus:ring-1 focus:ring-primary"
                                        placeholder="Type to search by company name..." autofocus />
                                    <Lucide v-if="searchLoading" icon="Loader" class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 animate-spin" />
                                </div>
                            </div>
                            <div class="max-h-80 overflow-y-auto border border-slate-200 rounded-lg">
                                <div v-if="searchError" class="p-6 text-center text-red-500 text-sm">
                                    {{ searchError }}
                                </div>
                                <div v-else-if="searchResults.length === 0 && companySearchTerm && !searchLoading" class="p-6 text-center text-slate-400 text-sm">
                                    No companies found for "{{ companySearchTerm }}"
                                </div>
                                <div v-else-if="searchResults.length === 0 && !companySearchTerm" class="p-6 text-center text-slate-400 text-sm">
                                    Start typing to search companies...
                                </div>
                                <div v-else class="divide-y divide-slate-100">
                                    <div v-for="c in searchResults" :key="c.id"
                                        @click="selectSearchedCompany(c)"
                                        class="p-4 hover:bg-slate-50 cursor-pointer transition">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <p class="text-sm font-medium text-slate-800">{{ c.company_name }}</p>
                                                <p class="text-xs text-slate-500 mt-0.5">
                                                    {{ [c.address, c.city, c.state, c.zip].filter(Boolean).join(', ') || 'No address' }}
                                                </p>
                                            </div>
                                            <div class="text-right text-xs text-slate-400">
                                                <p v-if="c.phone">📞 {{ c.phone }}</p>
                                                <p v-if="c.email">✉️ {{ c.email }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-4 pt-4 border-t flex justify-between items-center">
                                <p class="text-xs text-slate-400">Can't find the company? Use "Add New Employment" to enter it manually.</p>
                                <Button variant="outline-secondary" @click="showSearchModal = false">Close</Button>
                            </div>
                        </div>
                    </Dialog.Panel>
                </Dialog>

                <!-- ══════════════════════════════════════════════════
                     MODAL – Add / Edit Employment Company
                     ══════════════════════════════════════════════════ -->
                <Dialog :open="showCompanyModal" @close="showCompanyModal = false" size="lg" staticBackdrop>
                    <Dialog.Panel class="max-h-[90vh] overflow-y-auto">
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-5 pb-4 border-b sticky top-0 bg-white z-10">
                                <div>
                                    <h3 class="text-lg font-semibold">{{ editingCompanyIdx !== null ? 'Edit' : 'Add' }} Employment Information</h3>
                                    <p class="text-xs text-slate-500 mt-0.5">Fill in the employment details below</p>
                                </div>
                                <button @click="showCompanyModal = false" class="text-slate-400 hover:text-slate-600">
                                    <Lucide icon="X" class="w-5 h-5" />
                                </button>
                            </div>

                            <!-- Company Information -->
                            <div class="border border-slate-200 rounded-lg bg-slate-50 p-4 mb-4">
                                <p class="text-xs font-semibold text-slate-600 uppercase tracking-wide mb-3 flex items-center gap-1"><Lucide icon="Building2" class="w-3.5 h-3.5" /> Company Information</p>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">
                                    <div>
                                        <label class="block text-xs font-medium mb-1">Company Name <span class="text-danger">*</span></label>
                                        <FormInput v-model="companyForm.company_name" placeholder="Enter company name" />
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium mb-1">Phone</label>
                                        <FormInput v-model="companyForm.phone" placeholder="(555) 123-4567" />
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="block text-xs font-medium mb-1">Email <span class="text-slate-400">(for verification)</span></label>
                                    <FormInput v-model="companyForm.email" type="email" placeholder="company@example.com" />
                                </div>
                                <div class="mb-3">
                                    <label class="block text-xs font-medium mb-1">Address</label>
                                    <FormInput v-model="companyForm.address" placeholder="123 Main Street" />
                                </div>
                                <div class="grid grid-cols-3 gap-3 mb-3">
                                    <div>
                                        <label class="block text-xs font-medium mb-1">City</label>
                                        <FormInput v-model="companyForm.city" placeholder="City" />
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium mb-1">State</label>
                                        <TomSelect v-model="companyForm.state">
                                            <option value="">Select</option>
                                            <option v-for="(name, code) in props.usStates" :key="code" :value="code">{{ name }}</option>
                                        </TomSelect>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium mb-1">ZIP</label>
                                        <FormInput v-model="companyForm.zip" placeholder="12345" />
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-medium mb-1">Contact Person</label>
                                        <FormInput v-model="companyForm.contact" placeholder="Contact name" />
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium mb-1">Fax</label>
                                        <FormInput v-model="companyForm.fax" placeholder="Fax number" />
                                    </div>
                                </div>
                            </div>

                            <!-- Employment Details -->
                            <div class="border border-slate-200 rounded-lg bg-white p-4 mb-4">
                                <p class="text-xs font-semibold text-slate-600 uppercase tracking-wide mb-3 flex items-center gap-1"><Lucide icon="Briefcase" class="w-3.5 h-3.5" /> Employment Details</p>
                                <div class="grid grid-cols-2 gap-3 mb-3">
                                    <div>
                                        <label class="block text-xs font-medium mb-1">Employed From <span class="text-danger">*</span></label>
                                        <Litepicker v-model="companyForm.employed_from" :options="lpOptions" />
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium mb-1">Employed To <span class="text-danger">*</span></label>
                                        <Litepicker v-model="companyForm.employed_to" :options="lpOptions" />
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="block text-xs font-medium mb-1">Position(s) Held <span class="text-danger">*</span></label>
                                    <FormInput v-model="companyForm.positions_held" placeholder="e.g., Truck Driver, Dispatcher" />
                                </div>
                                <div class="bg-blue-50 border border-blue-100 rounded-md p-3 mb-3 space-y-2">
                                    <div class="flex items-start gap-2">
                                        <FormCheck.Input v-model="companyForm.subject_to_fmcsr" type="checkbox" class="mt-0.5 shrink-0" />
                                        <label class="text-xs text-slate-700 cursor-pointer">Were you subject to the Federal Motor Carrier Safety Regulations while employed by this employer?</label>
                                    </div>
                                    <div class="flex items-start gap-2">
                                        <FormCheck.Input v-model="companyForm.safety_sensitive_function" type="checkbox" class="mt-0.5 shrink-0" />
                                        <label class="text-xs text-slate-700 cursor-pointer">Was this job designated as a safety sensitive function in any D.O.T. regulated mode subject to alcohol and controlled substance testing requirements as required by 49 CFR Part 40?</label>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="block text-xs font-medium mb-1">Reason for Leaving <span class="text-danger">*</span></label>
                                    <select v-model="companyForm.reason_for_leaving" class="w-full text-sm border border-slate-300 rounded-md px-3 py-2 focus:outline-none focus:ring-1 focus:ring-primary">
                                        <option value="">Select Reason</option>
                                        <option value="resignation">Resignation</option>
                                        <option value="termination">Termination</option>
                                        <option value="layoff">Layoff</option>
                                        <option value="retirement">Retirement</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                                <div v-if="companyForm.reason_for_leaving === 'other'" class="mb-3">
                                    <label class="block text-xs font-medium mb-1">If other, please describe <span class="text-danger">*</span></label>
                                    <FormInput v-model="companyForm.other_reason_description" placeholder="Describe reason for leaving" />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium mb-1">Additional Explanation</label>
                                    <textarea v-model="companyForm.explanation" rows="2" class="w-full text-sm border border-slate-300 rounded-md px-3 py-2 focus:outline-none focus:ring-1 focus:ring-primary" placeholder="Any additional information about this employment..." />
                                </div>
                            </div>

                            <div class="flex justify-end gap-3 pt-2 sticky bottom-0 bg-white pb-1">
                                <Button variant="outline-secondary" @click="showCompanyModal = false">Cancel</Button>
                                <Button variant="primary" @click="saveCompanyModal">
                                    <Lucide icon="Save" class="w-4 h-4 mr-1" /> Save Employment
                                </Button>
                            </div>
                        </div>
                    </Dialog.Panel>
                </Dialog>

                <!-- ══════════════════════════════════════════════════
                     MODAL – Add / Edit Unemployment Period
                     ══════════════════════════════════════════════════ -->
                <Dialog :open="showUnemploymentModal" @close="showUnemploymentModal = false">
                    <Dialog.Panel>
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-semibold">{{ editingUnemploymentIdx !== null ? 'Edit' : 'Add' }} Unemployment Period</h3>
                                <button @click="showUnemploymentModal = false" class="text-slate-400 hover:text-slate-600"><Lucide icon="X" class="w-5 h-5" /></button>
                            </div>
                            <div class="grid grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label class="block text-xs font-medium mb-1">Start Date <span class="text-danger">*</span></label>
                                    <Litepicker v-model="unemploymentForm.start_date" :options="lpOptions" />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium mb-1">End Date</label>
                                    <Litepicker v-model="unemploymentForm.end_date" :options="lpOptions" />
                                </div>
                            </div>
                            <div class="mb-4">
                                <label class="block text-xs font-medium mb-1">Comments</label>
                                <textarea v-model="unemploymentForm.comments" rows="3" class="w-full text-sm border border-slate-300 rounded-md px-3 py-2 focus:outline-none focus:ring-1 focus:ring-primary" placeholder="Add any relevant details about this unemployment period" />
                            </div>
                            <div class="flex justify-end gap-3">
                                <Button variant="outline-secondary" @click="showUnemploymentModal = false">Cancel</Button>
                                <Button variant="primary" @click="saveUnemploymentModal">Save</Button>
                            </div>
                        </div>
                    </Dialog.Panel>
                </Dialog>

                <!-- ══════════════════════════════════════════════════
                     MODAL – Add / Edit Related Employment
                     ══════════════════════════════════════════════════ -->
                <Dialog :open="showRelatedModal" @close="showRelatedModal = false">
                    <Dialog.Panel>
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-semibold">{{ editingRelatedIdx !== null ? 'Edit' : 'Add' }} Other Employment</h3>
                                <button @click="showRelatedModal = false" class="text-slate-400 hover:text-slate-600"><Lucide icon="X" class="w-5 h-5" /></button>
                            </div>
                            <div class="grid grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label class="block text-xs font-medium mb-1">Start Date <span class="text-danger">*</span></label>
                                    <Litepicker v-model="relatedForm.start_date" :options="lpOptions" />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium mb-1">End Date</label>
                                    <Litepicker v-model="relatedForm.end_date" :options="lpOptions" />
                                </div>
                            </div>
                            <div class="mb-4">
                                <label class="block text-xs font-medium mb-1">Position</label>
                                <FormInput v-model="relatedForm.position" placeholder="e.g., Cook, Warehouseman, Carpenter" />
                            </div>
                            <div class="mb-4">
                                <label class="block text-xs font-medium mb-1">Comments</label>
                                <textarea v-model="relatedForm.comments" rows="2" class="w-full text-sm border border-slate-300 rounded-md px-3 py-2 focus:outline-none focus:ring-1 focus:ring-primary" placeholder="Additional details..." />
                            </div>
                            <div class="flex justify-end gap-3">
                                <Button variant="outline-secondary" @click="showRelatedModal = false">Cancel</Button>
                                <Button variant="primary" @click="saveRelatedModal">Save</Button>
                            </div>
                        </div>
                    </Dialog.Panel>
                </Dialog>
            </div>

            <!-- ====================================================
                 STEP 11 – Company Policy
                 ==================================================== -->
            <!-- ====================================================
                 STEP 11 – Company Policy
                 ==================================================== -->
            <div v-else-if="currentStep === 11">
                <h2 class="text-lg font-semibold mb-1">Company Policies</h2>
                <p class="text-sm text-slate-500 mb-6">Review the company policy document and provide your consent for each section.</p>

                <!-- Policy Document -->
                <div class="mb-6 p-5 bg-slate-50 rounded-xl border border-slate-200">
                    <h3 class="text-base font-semibold text-slate-800 mb-3 flex items-center gap-2">
                        <Lucide icon="FileText" class="w-4 h-4 text-primary" />
                        Company Policies Document
                    </h3>
                    <div class="mb-4">
                        <a
                            v-if="step11.policy_document_url"
                            :href="step11.policy_document_url"
                            target="_blank"
                            class="inline-flex items-center gap-2 text-primary hover:text-primary/80 font-medium text-sm transition-colors"
                        >
                            <Lucide icon="Download" class="w-4 h-4" />
                            View / Download Policy Document
                        </a>
                        <span v-else class="text-sm text-slate-400 italic">No policy document available</span>
                    </div>
                    <p class="text-sm text-slate-500 mb-4">Please review the company policy document before proceeding.</p>
                    <label class="flex items-start gap-3 cursor-pointer">
                        <FormCheck.Input v-model="step11.consent_all_policies_attached" type="checkbox" class="mt-0.5" />
                        <span class="text-sm">
                            <span class="text-danger font-bold">*</span>
                            I agree and consent to all policies attached above.
                        </span>
                    </label>
                </div>

                <!-- Section 1: Controlled Substances & Alcohol Testing -->
                <div class="mb-6 p-5 bg-white rounded-xl border border-slate-200 shadow-sm">
                    <h3 class="text-base font-semibold text-slate-800 mb-3">Controlled Substances &amp; Alcohol Testing Consent</h3>
                    <div class="text-sm text-slate-700 space-y-2 mb-4 leading-relaxed">
                        <p>I understand that as required by the Federal Motor Carrier Safety Regulations or company policy, all drivers must submit to alcohol and controlled substances testing.</p>
                        <p>I consent to all such testing as a condition of my employment. I understand that if I test positive for illegal drugs or alcohol misuse, I will not be eligible for employment with this company.</p>
                    </div>
                    <label class="flex items-start gap-3 cursor-pointer">
                        <FormCheck.Input v-model="step11.substance_testing_consent" type="checkbox" class="mt-0.5" />
                        <span class="text-sm">
                            <span class="text-danger font-bold">*</span>
                            Do you agree and consent to the above?
                        </span>
                    </label>
                </div>

                <!-- Section 2: Authorization -->
                <div class="mb-6 p-5 bg-white rounded-xl border border-slate-200 shadow-sm">
                    <h3 class="text-base font-semibold text-slate-800 mb-3">Authorization</h3>
                    <div class="text-sm text-slate-700 space-y-2 mb-4 leading-relaxed">
                        <p>I authorize you to make such investigations and inquiries of my personal, employment, financial or medical history and other related matters as may be necessary in arriving at an employment decision.</p>
                        <p>I hereby release employers, schools, health care providers and other persons from all liability in responding to inquiries and releasing information in connection with my application.</p>
                    </div>
                    <label class="flex items-start gap-3 cursor-pointer">
                        <FormCheck.Input v-model="step11.authorization_consent" type="checkbox" class="mt-0.5" />
                        <span class="text-sm">
                            <span class="text-danger font-bold">*</span>
                            Do you agree and consent to the above?
                        </span>
                    </label>
                </div>

                <!-- Section 3: FMCSA Drug & Alcohol Clearinghouse -->
                <div class="mb-6 p-5 bg-white rounded-xl border border-slate-200 shadow-sm">
                    <h3 class="text-base font-semibold text-slate-800 mb-3">General Consent for Limited Queries of the FMCSA Drug &amp; Alcohol Clearinghouse</h3>
                    <div class="text-sm text-slate-700 space-y-2 mb-4 leading-relaxed">
                        <p>
                            I hereby consent to <strong>{{ step11.company_name || 'the company' }}</strong> conducting limited queries of the Federal Motor Carrier Safety Administration (FMCSA) Commercial Driver's License Drug and Alcohol Clearinghouse to determine whether drug or alcohol violation information about me exists in the Clearinghouse.
                        </p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Employee / Company Name</label>
                            <div class="px-3 py-2 bg-slate-100 rounded-lg text-sm text-slate-700">
                                {{ step11.company_name || '—' }} / EFCTS
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Commercial Driver's License Number</label>
                            <div class="px-3 py-2 bg-slate-100 rounded-lg text-sm text-slate-700">
                                {{ step11.license_number || 'Not available' }}
                            </div>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="block text-xs font-medium text-slate-600 mb-1">State of Issuance</label>
                        <div class="px-3 py-2 bg-slate-100 rounded-lg text-sm text-slate-700">
                            {{ step11.license_state || 'Not available' }}
                        </div>
                    </div>
                    <label class="flex items-start gap-3 cursor-pointer">
                        <FormCheck.Input v-model="step11.fmcsa_clearinghouse_consent" type="checkbox" class="mt-0.5" />
                        <span class="text-sm">
                            <span class="text-danger font-bold">*</span>
                            Do you agree and consent to the above?
                        </span>
                    </label>
                </div>

                <!-- Validation errors -->
                <div v-if="step11Errors.length" class="mb-4 p-4 bg-red-50 border border-red-200 rounded-xl">
                    <ul class="list-disc list-inside space-y-1">
                        <li v-for="e in step11Errors" :key="e" class="text-sm text-red-700">{{ e }}</li>
                    </ul>
                </div>

                <!-- Navigation -->
                <div class="flex justify-between mt-6">
                    <Button variant="outline-secondary" @click="currentStep = 10">
                        <Lucide icon="ChevronLeft" class="w-4 h-4 mr-1" /> Previous
                    </Button>
                    <Button variant="primary" @click="submitStep11">
                        Save &amp; Continue <Lucide icon="ChevronRight" class="w-4 h-4 ml-1" />
                    </Button>
                </div>
            </div>

            <!-- ====================================================
                 STEP 12 – Criminal History
                 ==================================================== -->
            <div v-else-if="currentStep === 12">
                <h2 class="text-lg font-semibold mb-1">Criminal History Investigation</h2>
                <p class="text-sm text-slate-500 mb-6">Please answer all questions truthfully. A "Yes" answer does not automatically disqualify an applicant.</p>

                <!-- Criminal Record -->
                <div class="mb-6 p-5 bg-white rounded-xl border border-slate-200 shadow-sm">
                    <h3 class="text-base font-semibold text-slate-800 mb-3">Criminal Record <span class="text-danger">*</span></h3>
                    <p class="text-sm text-slate-700 mb-3">Do you have criminal charges pending?</p>
                    <div class="flex items-center gap-6">
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="radio" :value="false" v-model="step12.has_criminal_charges" class="form-radio h-4 w-4 text-primary" />
                            <span class="text-sm text-slate-700">No</span>
                        </label>
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="radio" :value="true" v-model="step12.has_criminal_charges" class="form-radio h-4 w-4 text-primary" />
                            <span class="text-sm text-slate-700">Yes</span>
                        </label>
                    </div>
                </div>

                <!-- Felonies -->
                <div class="mb-6 p-5 bg-white rounded-xl border border-slate-200 shadow-sm">
                    <h3 class="text-base font-semibold text-slate-800 mb-3">Felonies <span class="text-danger">*</span></h3>
                    <p class="text-sm text-slate-700 mb-3">Have you ever pled 'guilty' to, been convicted of, or pled 'no contest' to a felony?</p>
                    <div class="flex items-center gap-6 mb-5">
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="radio" :value="false" v-model="step12.has_felony_conviction" class="form-radio h-4 w-4 text-primary" />
                            <span class="text-sm text-slate-700">No</span>
                        </label>
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="radio" :value="true" v-model="step12.has_felony_conviction" class="form-radio h-4 w-4 text-primary" />
                            <span class="text-sm text-slate-700">Yes</span>
                        </label>
                    </div>
                    <p class="text-sm text-slate-700 mb-3">If you have any felony convictions, do you currently hold a minister's permit to enter or exit Canada?</p>
                    <div class="flex items-center gap-6">
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="radio" :value="false" v-model="step12.has_minister_permit" class="form-radio h-4 w-4 text-primary" />
                            <span class="text-sm text-slate-700">No</span>
                        </label>
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="radio" :value="true" v-model="step12.has_minister_permit" class="form-radio h-4 w-4 text-primary" />
                            <span class="text-sm text-slate-700">Yes</span>
                        </label>
                    </div>
                </div>

                <!-- FCRA -->
                <div class="mb-6 p-5 bg-white rounded-xl border border-slate-200 shadow-sm">
                    <h3 class="text-base font-semibold text-slate-800 mb-3">Criminal Background Check Release</h3>
                    <div class="text-sm text-slate-700 space-y-2 mb-4 leading-relaxed">
                        <h4 class="font-medium">Fair Credit Reporting Act Disclosure and Authorization Form For Employment Purposes</h4>
                        <p>I understand that, pursuant to the federal Fair Credit Reporting Act (FCRA), if any adverse action is to be taken based upon the consumer report, a copy of the report and a summary of the consumer's rights will be provided to me.</p>
                    </div>
                    <label class="flex items-start gap-3 cursor-pointer">
                        <FormCheck.Input v-model="step12.fcra_consent" type="checkbox" class="mt-0.5" />
                        <span class="text-sm"><span class="text-danger font-bold">*</span> I agree and consent to the above</span>
                    </label>
                </div>

                <!-- Background Info Form -->
                <div class="mb-6 p-5 bg-white rounded-xl border border-slate-200 shadow-sm">
                    <h3 class="text-base font-semibold text-slate-800 mb-4">Background Information Form</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">First Name</label>
                            <div class="px-3 py-2 bg-slate-100 rounded-lg text-sm text-slate-700">{{ step12.full_name || 'Not available' }}</div>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Middle Name</label>
                            <div class="px-3 py-2 bg-slate-100 rounded-lg text-sm text-slate-700">{{ step12.middle_name || 'Not available' }}</div>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Last Name</label>
                            <div class="px-3 py-2 bg-slate-100 rounded-lg text-sm text-slate-700">{{ step12.last_name || 'Not available' }}</div>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Last 4 Digits of SSN</label>
                            <div class="px-3 py-2 bg-slate-100 rounded-lg text-sm text-slate-700">{{ step12.ssn_last_four || 'Not available' }}</div>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Date of Birth</label>
                            <div class="px-3 py-2 bg-slate-100 rounded-lg text-sm text-slate-700">{{ step12.date_of_birth ? toUsDate(step12.date_of_birth) : 'Not available' }}</div>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Driver's License Number</label>
                            <div class="px-3 py-2 bg-slate-100 rounded-lg text-sm text-slate-700">{{ step12.license_number || 'Not available' }}</div>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="block text-xs font-medium text-slate-600 mb-1">License State of Issuance</label>
                        <div class="px-3 py-2 bg-slate-100 rounded-lg text-sm text-slate-700">{{ step12.license_state || 'Not available' }}</div>
                    </div>

                    <!-- Address History from step2 -->
                    <div class="mb-4">
                        <h4 class="text-sm font-medium text-slate-700 mb-2">Address History</h4>
                        <div class="overflow-x-auto rounded-lg border border-slate-200">
                            <table class="w-full text-sm">
                                <thead class="bg-slate-50 text-xs text-slate-500 uppercase">
                                    <tr>
                                        <th class="px-4 py-3 text-left">Address</th>
                                        <th class="px-4 py-3 text-left">City</th>
                                        <th class="px-4 py-3 text-left">State</th>
                                        <th class="px-4 py-3 text-left">ZIP</th>
                                        <th class="px-4 py-3 text-left">From</th>
                                        <th class="px-4 py-3 text-left">To</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <template v-if="props.stepData?.step2?.primary">
                                        <tr class="bg-white">
                                            <td class="px-4 py-2 text-slate-600">{{ props.stepData.step2.primary.address_line1 }}<span v-if="props.stepData.step2.primary.address_line2"> / {{ props.stepData.step2.primary.address_line2 }}</span></td>
                                            <td class="px-4 py-2 text-slate-600">{{ props.stepData.step2.primary.city }}</td>
                                            <td class="px-4 py-2 text-slate-600">{{ props.stepData.step2.primary.state }}</td>
                                            <td class="px-4 py-2 text-slate-600">{{ props.stepData.step2.primary.zip_code }}</td>
                                            <td class="px-4 py-2 text-slate-600">{{ props.stepData.step2.primary.from_date ? toUsDate(props.stepData.step2.primary.from_date) : '—' }}</td>
                                            <td class="px-4 py-2 text-slate-600">Present</td>
                                        </tr>
                                    </template>
                                    <template v-for="addr in (props.stepData?.step2?.previous ?? [])" :key="addr.id">
                                        <tr class="bg-slate-50/50">
                                            <td class="px-4 py-2 text-slate-600">{{ addr.address_line1 }}<span v-if="addr.address_line2"> / {{ addr.address_line2 }}</span></td>
                                            <td class="px-4 py-2 text-slate-600">{{ addr.city }}</td>
                                            <td class="px-4 py-2 text-slate-600">{{ addr.state }}</td>
                                            <td class="px-4 py-2 text-slate-600">{{ addr.zip_code }}</td>
                                            <td class="px-4 py-2 text-slate-600">{{ addr.from_date ? toUsDate(addr.from_date) : '—' }}</td>
                                            <td class="px-4 py-2 text-slate-600">{{ addr.to_date ? toUsDate(addr.to_date) : 'Present' }}</td>
                                        </tr>
                                    </template>
                                    <tr v-if="!props.stepData?.step2?.primary && !(props.stepData?.step2?.previous?.length)">
                                        <td colspan="6" class="px-4 py-4 text-center text-slate-400">No address history available</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <label class="flex items-start gap-3 cursor-pointer">
                        <FormCheck.Input v-model="step12.background_info_consent" type="checkbox" class="mt-0.5" />
                        <span class="text-sm"><span class="text-danger font-bold">*</span> By signing below, you are certifying that the above information is true and correct.</span>
                    </label>
                </div>

                <!-- Validation errors -->
                <div v-if="step12Errors.length" class="mb-4 p-4 bg-red-50 border border-red-200 rounded-xl">
                    <ul class="list-disc list-inside space-y-1">
                        <li v-for="e in step12Errors" :key="e" class="text-sm text-red-700">{{ e }}</li>
                    </ul>
                </div>

                <div class="flex justify-between mt-6">
                    <Button variant="outline-secondary" @click="currentStep = 11">
                        <Lucide icon="ChevronLeft" class="w-4 h-4 mr-1" /> Previous
                    </Button>
                    <Button variant="primary" @click="submitStep12">
                        Save &amp; Continue <Lucide icon="ChevronRight" class="w-4 h-4 ml-1" />
                    </Button>
                </div>
            </div>

            <!-- ====================================================
                 STEP 13 – W-9
                 ==================================================== -->
            <div v-else-if="currentStep === 13">
                <h2 class="text-lg font-semibold mb-1">W-9 Tax Form</h2>
                <p class="text-sm text-slate-500 mb-6">Complete the W-9 Request for Taxpayer Identification Number and Certification.</p>

                <!-- PDF generated banner -->
                <div v-if="step13.pdf_url" class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-xl flex items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <Lucide icon="FileText" class="w-6 h-6 text-blue-600 flex-shrink-0" />
                        <span class="text-sm font-medium text-blue-800">W-9 PDF generated successfully</span>
                    </div>
                    <a :href="step13.pdf_url" target="_blank"
                       class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors flex-shrink-0">
                        <Lucide icon="Download" class="w-4 h-4" />
                        Download W-9 PDF
                    </a>
                </div>

                <!-- Line 1: Name -->
                <div class="mb-6 p-5 bg-white rounded-xl border border-slate-200 shadow-sm">
                    <h3 class="text-base font-semibold text-slate-800 mb-1">Line 1 — Name <span class="text-danger">*</span></h3>
                    <p class="text-xs text-slate-500 mb-3">Name of entity/individual. An entry is required.</p>
                    <FormInput v-model="step13.name" placeholder="Enter name as shown on your income tax return" maxlength="100" />
                    <p v-if="step13Errors.find(e => e.includes('Name'))" class="text-red-500 text-xs mt-1">{{ step13Errors.find(e => e.includes('Name')) }}</p>
                </div>

                <!-- Line 2: Business Name -->
                <div class="mb-6 p-5 bg-white rounded-xl border border-slate-200 shadow-sm">
                    <h3 class="text-base font-semibold text-slate-800 mb-1">Line 2 — Business Name</h3>
                    <p class="text-xs text-slate-500 mb-3">Business name/disregarded entity name, if different from above.</p>
                    <FormInput v-model="step13.business_name" placeholder="Business name (optional)" maxlength="100" />
                </div>

                <!-- Line 3a: Tax Classification -->
                <div class="mb-6 p-5 bg-white rounded-xl border border-slate-200 shadow-sm">
                    <h3 class="text-base font-semibold text-slate-800 mb-1">Line 3a — Federal Tax Classification <span class="text-danger">*</span></h3>
                    <p class="text-xs text-slate-500 mb-3">Check the appropriate box for federal tax classification. Check only one.</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 mb-4">
                        <label v-for="[val, lbl] in [
                            ['individual', 'Individual/sole proprietor or single-member LLC'],
                            ['c_corporation', 'C Corporation'],
                            ['s_corporation', 'S Corporation'],
                            ['partnership', 'Partnership'],
                            ['trust_estate', 'Trust/estate'],
                            ['llc', 'Limited liability company (LLC)'],
                            ['other', 'Other (see instructions)'],
                        ]" :key="val"
                            class="relative flex items-start p-3 border rounded-lg cursor-pointer transition-all"
                            :class="step13.tax_classification === val ? 'border-primary bg-primary/5 ring-2 ring-primary' : 'border-slate-200 hover:border-slate-300 hover:bg-slate-50'"
                        >
                            <input type="radio" :value="val" v-model="step13.tax_classification" class="form-radio h-4 w-4 text-primary border-slate-300 mt-0.5" />
                            <span class="ml-2 text-sm text-slate-700">{{ lbl }}</span>
                        </label>
                    </div>
                    <p v-if="step13Errors.find(e => e.includes('tax classification'))" class="text-red-500 text-xs mt-1">{{ step13Errors.find(e => e.includes('tax classification')) }}</p>

                    <!-- LLC sub-option -->
                    <div v-if="step13.tax_classification === 'llc'" class="mt-4 p-4 bg-amber-50 border border-amber-200 rounded-lg">
                        <label class="block text-sm font-medium text-slate-700 mb-1">LLC Tax Classification <span class="text-danger">*</span></label>
                        <p class="text-xs text-slate-500 mb-2">Enter the tax classification (C = C corporation, S = S corporation, P = Partnership)</p>
                        <div class="flex items-center gap-6">
                            <label v-for="[v, l] in [['C','C Corporation'],['S','S Corporation'],['P','Partnership']]" :key="v" class="inline-flex items-center gap-2 cursor-pointer">
                                <input type="radio" :value="v" v-model="step13.llc_classification" class="form-radio h-4 w-4 text-primary" />
                                <span class="text-sm text-slate-700">{{ v }} — {{ l }}</span>
                            </label>
                        </div>
                        <p v-if="step13Errors.find(e => e.includes('LLC'))" class="text-red-500 text-xs mt-1">{{ step13Errors.find(e => e.includes('LLC')) }}</p>
                    </div>

                    <!-- Other sub-option -->
                    <div v-if="step13.tax_classification === 'other'" class="mt-4 p-4 bg-amber-50 border border-amber-200 rounded-lg">
                        <label class="block text-sm font-medium text-slate-700 mb-1">Specify Other Classification <span class="text-danger">*</span></label>
                        <FormInput v-model="step13.other_classification" placeholder="Enter classification" maxlength="50" />
                        <p v-if="step13Errors.find(e => e.includes('other classification'))" class="text-red-500 text-xs mt-1">{{ step13Errors.find(e => e.includes('other classification')) }}</p>
                    </div>
                </div>

                <!-- Line 3b: Foreign Partners (only for partnerships/trusts) -->
                <div v-if="['partnership','trust_estate'].includes(step13.tax_classification) || (step13.tax_classification === 'llc' && step13.llc_classification === 'P')"
                     class="mb-6 p-5 bg-white rounded-xl border border-slate-200 shadow-sm">
                    <h3 class="text-base font-semibold text-slate-800 mb-3">Line 3b — Foreign Partners</h3>
                    <label class="flex items-start gap-3 cursor-pointer">
                        <FormCheck.Input v-model="step13.has_foreign_partners" type="checkbox" class="mt-0.5" />
                        <span class="text-sm text-slate-700">Check this box if you have any foreign partners, owners, or beneficiaries.</span>
                    </label>
                </div>

                <!-- Line 4: Exemptions -->
                <div class="mb-6 p-5 bg-white rounded-xl border border-slate-200 shadow-sm">
                    <h3 class="text-base font-semibold text-slate-800 mb-1">Line 4 — Exemptions</h3>
                    <p class="text-xs text-slate-500 mb-3">Codes apply only to certain entities, not individuals; see instructions on page 3.</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Exempt payee code (if any)</label>
                            <FormInput v-model="step13.exempt_payee_code" placeholder="1–13" maxlength="2" inputmode="numeric" @input="onExemptCodeInput" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">FATCA reporting code (if any)</label>
                            <FormInput v-model="step13.fatca_exemption_code" placeholder="A–M" maxlength="1" />
                        </div>
                    </div>
                </div>

                <!-- Lines 5 & 6: Address -->
                <div class="mb-6 p-5 bg-white rounded-xl border border-slate-200 shadow-sm">
                    <h3 class="text-base font-semibold text-slate-800 mb-3">Lines 5 &amp; 6 — Address <span class="text-danger">*</span></h3>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-slate-700 mb-1">Address (number, street, and apt. or suite no.)</label>
                        <FormInput v-model="step13.address" placeholder="Street address" maxlength="200" />
                        <p v-if="step13Errors.find(e => e.includes('Address'))" class="text-red-500 text-xs mt-1">{{ step13Errors.find(e => e.includes('Address')) }}</p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">City <span class="text-danger">*</span></label>
                            <FormInput v-model="step13.city" placeholder="City" maxlength="50" />
                            <p v-if="step13Errors.find(e => e.includes('City'))" class="text-red-500 text-xs mt-1">{{ step13Errors.find(e => e.includes('City')) }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">State <span class="text-danger">*</span></label>
                            <TomSelect v-model="step13.state">
                                <option value="">Select state</option>
                                <option v-for="(name, code) in props.usStates" :key="code" :value="code">{{ name }}</option>
                            </TomSelect>
                            <p v-if="step13Errors.find(e => e.includes('State'))" class="text-red-500 text-xs mt-1">{{ step13Errors.find(e => e.includes('State')) }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">ZIP Code <span class="text-danger">*</span></label>
                            <FormInput v-model="step13.zip_code" placeholder="XXXXX" maxlength="5" inputmode="numeric" @input="onZipInput" />
                            <p v-if="step13Errors.find(e => e.includes('ZIP'))" class="text-red-500 text-xs mt-1">{{ step13Errors.find(e => e.includes('ZIP')) }}</p>
                        </div>
                    </div>
                </div>

                <!-- Line 7: Account Numbers -->
                <div class="mb-6 p-5 bg-white rounded-xl border border-slate-200 shadow-sm">
                    <h3 class="text-base font-semibold text-slate-800 mb-1">Line 7 — Account Numbers</h3>
                    <p class="text-xs text-slate-500 mb-3">List account number(s) here (optional).</p>
                    <FormInput v-model="step13.account_numbers" placeholder="Account numbers (optional)" maxlength="200" />
                </div>

                <!-- Part I: TIN -->
                <div class="mb-6 p-5 bg-white rounded-xl border border-slate-200 shadow-sm">
                    <h3 class="text-base font-semibold text-slate-800 mb-1">Part I — Taxpayer Identification Number (TIN) <span class="text-danger">*</span></h3>
                    <p class="text-xs text-slate-500 mb-4">Enter your TIN in the appropriate box. The TIN provided must match the name given on line 1.</p>
                    <div class="flex items-center gap-6 mb-4">
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="radio" value="ssn" v-model="step13.tin_type" class="form-radio h-4 w-4 text-primary" />
                            <span class="text-sm font-medium text-slate-700">Social Security Number (SSN)</span>
                        </label>
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="radio" value="ein" v-model="step13.tin_type" class="form-radio h-4 w-4 text-primary" />
                            <span class="text-sm font-medium text-slate-700">Employer Identification Number (EIN)</span>
                        </label>
                    </div>
                    <div class="max-w-sm">
                        <label class="block text-sm font-medium text-slate-700 mb-1">
                            {{ step13.tin_type === 'ssn' ? 'Social Security Number' : 'Employer Identification Number' }}
                        </label>
                        <FormInput v-model="step13.tin" type="password" :placeholder="step13.tin_type === 'ssn' ? 'XXX-XX-XXXX' : 'XX-XXXXXXX'" :maxlength="step13.tin_type === 'ssn' ? 11 : 10" inputmode="numeric" @input="onTinInput" />
                        <p v-if="step13Errors.find(e => e.includes('TIN'))" class="text-red-500 text-xs mt-1">{{ step13Errors.find(e => e.includes('TIN')) }}</p>
                    </div>
                </div>

                <!-- Certification note -->
                <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-xl flex items-start gap-3">
                    <Lucide icon="Info" class="w-5 h-5 text-blue-600 mt-0.5 flex-shrink-0" />
                    <p class="text-sm text-blue-800">The signature for Part II (Certification) will be applied automatically from the Certification step when the application is completed.</p>
                </div>

                <!-- Validation errors -->
                <div v-if="step13Errors.length" class="mb-4 p-4 bg-red-50 border border-red-200 rounded-xl">
                    <ul class="list-disc list-inside space-y-1">
                        <li v-for="e in step13Errors" :key="e" class="text-sm text-red-700">{{ e }}</li>
                    </ul>
                </div>

                <div class="flex justify-between mt-6">
                    <Button variant="outline-secondary" @click="currentStep = 12">
                        <Lucide icon="ChevronLeft" class="w-4 h-4 mr-1" /> Previous
                    </Button>
                    <Button variant="primary" @click="submitStep13">
                        Save &amp; Continue <Lucide icon="ChevronRight" class="w-4 h-4 ml-1" />
                    </Button>
                </div>
            </div>

            <!-- ====================================================
                 STEP 14 – Certification
                 ==================================================== -->
            <div v-else-if="currentStep === 14">
                <h2 class="text-lg font-semibold mb-1">Application Certification</h2>
                <p class="text-sm text-slate-500 mb-6">This certifies that this application was completed by me, and that all entries on it and information in it are true and complete to the best of my knowledge.</p>

                <!-- Safety Performance History -->
                <div class="mb-6 p-5 bg-white rounded-xl border border-slate-200 shadow-sm">
                    <h3 class="text-base font-semibold text-slate-800 mb-2">Safety Performance History Investigation — Previous USDOT Regulated Employers</h3>
                    <p class="text-sm text-slate-600 mb-4">I hereby specifically authorize you to release the following information to the specified company and their agents for the purposes of investigation as required by §391.23 and §40.321(b) of the Federal Motor Carrier Safety Regulations. You are hereby released from any and all liability which may result from furnishing such information.</p>
                    <div class="overflow-x-auto rounded-lg border border-slate-200">
                        <table class="w-full text-sm">
                            <thead class="bg-slate-50 text-xs text-slate-500 uppercase">
                                <tr>
                                    <th class="px-4 py-3 text-left">Company Name</th>
                                    <th class="px-4 py-3 text-left">Address</th>
                                    <th class="px-4 py-3 text-left">City</th>
                                    <th class="px-4 py-3 text-left">State</th>
                                    <th class="px-4 py-3 text-left">ZIP</th>
                                    <th class="px-4 py-3 text-left">Employed From</th>
                                    <th class="px-4 py-3 text-left">Employed To</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="(co, i) in step14.employment_history" :key="i" :class="i % 2 === 0 ? 'bg-white' : 'bg-slate-50/50'">
                                    <td class="px-4 py-2 text-slate-700">{{ co.company_name }}</td>
                                    <td class="px-4 py-2 text-slate-600">{{ co.address }}</td>
                                    <td class="px-4 py-2 text-slate-600">{{ co.city }}</td>
                                    <td class="px-4 py-2 text-slate-600">{{ co.state }}</td>
                                    <td class="px-4 py-2 text-slate-600">{{ co.zip }}</td>
                                    <td class="px-4 py-2 text-slate-600">{{ co.employed_from }}</td>
                                    <td class="px-4 py-2 text-slate-600">{{ co.employed_to }}</td>
                                </tr>
                                <tr v-if="!step14.employment_history.length">
                                    <td colspan="7" class="px-4 py-4 text-center text-slate-400">No employment history available</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Electronic Signature -->
                <div class="mb-6 p-5 bg-white rounded-xl border border-slate-200 shadow-sm">
                    <h3 class="text-base font-semibold text-slate-800 mb-2">Signature</h3>
                    <p class="text-sm text-slate-600 mb-4">By signing below, I agree to use an electronic signature and acknowledge that an electronic signature is as legally binding as an ink signature.</p>

                    <div class="flex items-center justify-between mb-3">
                        <span class="text-sm font-medium text-slate-700">Electronic Signature</span>
                        <Button variant="primary" @click="openSignatureModal">
                            <Lucide icon="PenLine" class="w-4 h-4 mr-1" />
                            {{ step14.signature ? 'Change Signature' : 'Sign Now' }}
                        </Button>
                    </div>

                    <!-- Signature Preview -->
                    <div v-if="step14.signature || step14.signature_url" class="border border-slate-200 rounded-lg p-4 bg-slate-50">
                        <p class="text-xs text-slate-500 mb-2">Your Signature:</p>
                        <div class="bg-white border border-slate-200 rounded p-3 flex justify-center">
                            <img :src="step14.signature || step14.signature_url" alt="Signature" class="max-h-32" />
                        </div>
                    </div>
                    <div v-else class="border border-dashed border-slate-300 rounded-lg p-6 bg-slate-50 text-center text-slate-400 text-sm">
                        No signature provided. Click "Sign Now" to add your signature.
                    </div>
                </div>

                <!-- Certification Acceptance -->
                <div class="mb-6 p-5 bg-white rounded-xl border border-slate-200 shadow-sm">
                    <label class="flex items-start gap-3 cursor-pointer">
                        <FormCheck.Input v-model="step14.is_accepted" type="checkbox" class="mt-0.5" />
                        <span class="text-sm">
                            <span class="text-danger font-bold">*</span>
                            I hereby certify that all information provided in this application is true and complete to the best of my knowledge.
                        </span>
                    </label>
                </div>

                <!-- Validation errors -->
                <div v-if="step14Errors.length" class="mb-4 p-4 bg-red-50 border border-red-200 rounded-xl">
                    <ul class="list-disc list-inside space-y-1">
                        <li v-for="e in step14Errors" :key="e" class="text-sm text-red-700">{{ e }}</li>
                    </ul>
                </div>

                <div class="flex justify-between mt-6">
                    <Button variant="outline-secondary" @click="currentStep = 13">
                        <Lucide icon="ChevronLeft" class="w-4 h-4 mr-1" /> Previous
                    </Button>
                    <Button variant="success" @click="submitStep14">
                        <Lucide icon="CheckCircle" class="w-4 h-4 mr-1" /> Complete Application
                    </Button>
                </div>

                <!-- Signature Modal -->
                <Teleport to="body">
                    <div v-if="showSignatureModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
                        <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl">
                            <div class="flex items-center justify-between p-5 border-b border-slate-200">
                                <h3 class="text-lg font-semibold text-slate-800">Please Sign Below</h3>
                                <button @click="closeSignatureModal" class="text-slate-400 hover:text-slate-600 transition-colors">
                                    <Lucide icon="X" class="w-6 h-6" />
                                </button>
                            </div>
                            <div class="p-5">
                                <div class="border-2 border-slate-300 rounded-lg mb-4 bg-white" style="height: 256px;">
                                    <canvas ref="signatureCanvas" class="w-full h-full cursor-crosshair rounded-lg"></canvas>
                                </div>
                                <div class="flex justify-end gap-3">
                                    <Button variant="outline-secondary" @click="clearSignature">
                                        <Lucide icon="Eraser" class="w-4 h-4 mr-1" /> Clear
                                    </Button>
                                    <Button variant="success" @click="saveSignature">
                                        <Lucide icon="Check" class="w-4 h-4 mr-1" /> Save Signature
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>
                </Teleport>
            </div>

            <!-- ====================================================
                 STEP 15 – Clearinghouse / Finalize
                 ==================================================== -->
            <div v-else-if="currentStep === 15">

                <!-- Overall Progress Bar -->
                <div class="mb-6">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-slate-700">Overall Completion</span>
                        <span class="text-sm font-bold" :class="step15.total_percentage >= 100 ? 'text-emerald-600' : 'text-primary'">
                            {{ Math.round(step15.total_percentage) }}%
                        </span>
                    </div>
                    <div class="w-full bg-slate-200 rounded-full h-3">
                        <div
                            class="h-3 rounded-full transition-all duration-500"
                            :class="step15.total_percentage >= 100 ? 'bg-emerald-500' : 'bg-primary'"
                            :style="{ width: Math.min(step15.total_percentage, 100) + '%' }"
                        />
                    </div>
                </div>

                <!-- Steps Needing Attention -->
                <div v-if="step15.steps_needing_attention.length > 0" class="bg-amber-50 border-l-4 border-amber-400 rounded-lg p-4 mb-6">
                    <div class="flex items-start gap-3">
                        <Lucide icon="AlertTriangle" class="w-5 h-5 text-amber-500 flex-shrink-0 mt-0.5" />
                        <div class="w-full">
                            <h3 class="text-sm font-semibold text-amber-800 mb-1">Steps Requiring Attention</h3>
                            <p class="text-sm text-amber-700 mb-3">The following sections have missing required fields:</p>
                            <ul class="space-y-2">
                                <li
                                    v-for="s in step15.steps_needing_attention"
                                    :key="s.step"
                                    class="flex items-center justify-between bg-white rounded-lg p-2.5 border border-amber-200"
                                >
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-amber-100 text-amber-800 text-xs font-bold">
                                            {{ s.step }}
                                        </span>
                                        <span class="text-sm font-medium text-slate-700">{{ s.name }}</span>
                                        <span class="text-xs text-slate-400">({{ s.percentage }}% complete)</span>
                                    </div>
                                    <button
                                        type="button"
                                        class="text-xs text-primary hover:text-primary/80 font-semibold"
                                        @click="currentStep = s.step"
                                    >
                                        Go to step &rarr;
                                    </button>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Status Header -->
                <div class="flex items-center gap-3 mb-6">
                    <div class="p-2 rounded-full" :class="step15.is_complete ? 'bg-emerald-100' : 'bg-primary/10'">
                        <Lucide
                            :icon="step15.is_complete ? 'CheckCircle' : 'Clock'"
                            class="w-8 h-8"
                            :class="step15.is_complete ? 'text-emerald-500' : 'text-primary'"
                        />
                    </div>
                    <h2 class="text-xl font-bold text-slate-800">
                        {{ step15.is_complete ? 'Registration Complete!' : 'Registration In Progress' }}
                    </h2>
                </div>

                <!-- Status Message -->
                <p class="text-slate-700 mb-6 text-sm">
                    <template v-if="step15.is_complete">
                        The driver registration has been completed successfully. All information has been saved.
                    </template>
                    <template v-else>
                        Please complete all required fields in the sections listed above before finalizing the registration.
                    </template>
                </p>

                <!-- FMCSA Important Notice -->
                <div class="bg-blue-50 border-l-4 border-blue-500 rounded-lg p-5 mb-8">
                    <div class="flex items-start gap-3">
                        <Lucide icon="Info" class="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" />
                        <div>
                            <h3 class="text-base font-semibold text-blue-800 mb-1">Important Notice</h3>
                            <p class="text-sm font-semibold text-blue-700 mb-2">
                                FMCSA's Drug and Alcohol Clearinghouse Electronic Consent Required
                            </p>
                            <p class="text-sm text-blue-700 mb-2">
                                Beginning on January 6, 2020, the driver must provide <strong>electronic consent</strong>
                                for a prospective employer to view their information in the FMCSA's Drug and Alcohol
                                Clearinghouse.
                            </p>
                            <p class="text-sm text-blue-700 mb-3">
                                To do this, the driver must register for the Drug and Alcohol Clearinghouse using the
                                link below and provide electronic consent when requested by the prospective employer.
                                If they do not do this, they will be prohibited from operating a commercial motor
                                vehicle for their prospective employer.
                            </p>
                            <a
                                href="https://clearinghouse.fmcsa.dot.gov/register"
                                target="_blank"
                                class="inline-flex items-center gap-1.5 text-sm font-semibold text-blue-700 hover:text-blue-900 underline"
                            >
                                <Lucide icon="ExternalLink" class="w-4 h-4" />
                                Register for the FMCSA Clearinghouse
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Navigation -->
                <div class="flex justify-between mt-6">
                    <Button variant="outline-secondary" @click="currentStep = 14">
                        <Lucide icon="ChevronLeft" class="w-4 h-4 mr-1" /> Previous
                    </Button>
                    <Button
                        :variant="step15.is_complete ? 'success' : 'secondary'"
                        :disabled="!step15.is_complete"
                        @click="submitStep15"
                        class="min-w-[180px]"
                    >
                        <Lucide icon="CheckCircle" class="w-4 h-4 mr-1" />
                        Complete Registration
                    </Button>
                </div>
            </div>

        </div>
        </div>
    </div>
</template>
