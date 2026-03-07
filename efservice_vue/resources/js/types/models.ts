export interface PaginatedResponse<T> {
    data: T[]
    current_page: number
    last_page: number
    per_page: number
    total: number
    from: number | null
    to: number | null
    links: PaginationLink[]
}

export interface PaginationLink {
    url: string | null
    label: string
    active: boolean
}

export interface FlashMessages {
    success?: string
    error?: string
    warning?: string
    info?: string
}

export interface SharedPageProps {
    name: string
    auth: {
        user: User | null
    }
    flash: FlashMessages
    notifications: AppNotification[]
    unreadNotificationsCount: number
    sidebarOpen: boolean
    ziggy: {
        url: string
        port: number | null
        defaults: Record<string, unknown>
        routes: Record<string, unknown>
        location: string
    }
}

export interface User {
    id: number
    name: string
    email: string
    avatar?: string
    email_verified_at: string | null
    two_factor_confirmed_at: string | null
    created_at: string
    updated_at: string
    roles: string[]
    permissions: string[]
    all_permissions: string[]
    carrier_details?: UserCarrierDetail
    driver_details?: UserDriverDetail
}

export interface Carrier {
    id: number
    name: string
    slug: string
    referrer_token: string | null
    address: string | null
    headquarters: string | null
    state: string | null
    zipcode: string | null
    country: string | null
    ein_number: string | null
    dot_number: string | null
    mc_number: string | null
    state_dot: string | null
    ifta_account: string | null
    ifta: string | null
    business_type: string | null
    years_in_business: string | null
    fleet_size: string | null
    user_id: number | null
    id_plan: number | null
    membership_id: number | null
    documents_ready: boolean | null
    terms_accepted_at: string | null
    status: number
    document_status: string | null
    documents_completed: boolean
    documents_completed_at: string | null
    custom_safety_url: string | null
    created_at: string
    updated_at: string
    // Relations
    users?: User[]
    documents?: CarrierDocument[]
    membership?: Membership
    banking_details?: CarrierBankingDetail
    vehicles?: Vehicle[]
    user_carriers?: UserCarrierDetail[]
    user_drivers?: UserDriverDetail[]
}

export interface CarrierDocument {
    id: number
    carrier_id: number
    document_type_id: number
    filename: string | null
    date: string | null
    notes: string | null
    status: string
    created_at: string
    updated_at: string
    carrier?: Carrier
    document_type?: DocumentType
    media?: Media[]
}

export interface CarrierBankingDetail {
    id: number
    carrier_id: number
    bank_name: string | null
    account_number: string | null
    account_holder_name: string | null
    banking_routing_number: string | null
    zip_code: string | null
    security_code: string | null
    country_code: string | null
    status: string | null
    rejection_reason: string | null
    created_at: string
    updated_at: string
}

export interface UserCarrierDetail {
    id: number
    user_id: number
    carrier_id: number
    phone: string | null
    job_position: string | null
    status: number
    confirmation_token: string | null
    created_at: string
    updated_at: string
    user?: User
    carrier?: Carrier
}

export interface UserDriverDetail {
    id: number
    user_id: number
    carrier_id: number
    middle_name: string | null
    last_name: string | null
    phone: string | null
    date_of_birth: string | null
    status: number
    terms_accepted: boolean
    application_completed: boolean
    current_step: number
    assigned_vehicle_id: number | null
    emergency_contact_name: string | null
    emergency_contact_phone: string | null
    emergency_contact_relationship: string | null
    notes: string | null
    hire_date: string | null
    termination_date: string | null
    completion_percentage: number
    hos_cycle_type: string | null
    created_at: string
    updated_at: string
    user?: User
    carrier?: Carrier
    application?: DriverApplication
    assigned_vehicle?: Vehicle
    licenses?: DriverLicense[]
    primary_license?: DriverLicense
    medical_qualification?: DriverMedicalQualification
    accidents?: DriverAccident[]
    testings?: DriverTesting[]
    inspections?: DriverInspection[]
    traffic_convictions?: DriverTrafficConviction[]
    training_schools?: DriverTrainingSchool[]
    trainings?: DriverTraining[]
    employment_companies?: DriverEmploymentCompany[]
    certification?: DriverCertification
    w9_form?: DriverW9Form
    vehicle_assignments?: VehicleDriverAssignment[]
}

export interface DocumentType {
    id: number
    name: string
    requirement: string | null
    created_at: string
    updated_at: string
}

export interface Membership {
    id: number
    name: string
    description: string | null
    price: number | null
    pricing_type: string | null
    carrier_price: number | null
    driver_price: number | null
    vehicle_price: number | null
    max_carrier: number | null
    max_drivers: number | null
    max_vehicles: number | null
    status: string | null
    show_in_register: boolean | null
    created_at: string
    updated_at: string
}

export interface DriverApplication {
    id: number
    user_id: number
    status: string
    pdf_path: string | null
    completed_at: string | null
    rejection_reason: string | null
    created_at: string
    updated_at: string
    addresses?: DriverAddress[]
    details?: DriverApplicationDetail
}

export interface DriverApplicationDetail {
    id: number
    driver_application_id: number
    [key: string]: unknown
}

export interface DriverAddress {
    id: number
    driver_application_id: number
    primary: boolean
    address_line1: string
    address_line2: string | null
    city: string
    state: string
    zip_code: string
    lived_three_years: boolean
    from_date: string | null
    to_date: string | null
}

export interface DriverLicense {
    id: number
    user_driver_detail_id: number
    license_number: string
    state_of_issue: string
    license_class: string | null
    expiration_date: string
    is_cdl: boolean
    restrictions: string | null
    status: string | null
    is_primary: boolean
    created_at: string
    updated_at: string
    endorsements?: LicenseEndorsement[]
    media?: Media[]
}

export interface LicenseEndorsement {
    id: number
    name: string
    code: string
}

export interface DriverAccident {
    id: number
    user_driver_detail_id: number
    accident_date: string
    nature_of_accident: string | null
    had_fatalities: boolean
    had_injuries: boolean
    number_of_fatalities: number
    number_of_injuries: number
    comments: string | null
    created_at: string
    updated_at: string
}

export interface DriverTraining {
    id: number
    user_driver_detail_id: number
    training_id: number | null
    assigned_date: string | null
    due_date: string | null
    completed_date: string | null
    status: string
    assigned_by: number | null
    completion_notes: string | null
    created_at: string
    updated_at: string
    training?: Training
}

export interface Training {
    id: number
    name: string
    [key: string]: unknown
}

export interface DriverTrainingSchool {
    id: number
    user_driver_detail_id: number
    [key: string]: unknown
}

export interface DriverTesting {
    id: number
    user_driver_detail_id: number
    carrier_id: number | null
    test_date: string
    test_type: string
    test_result: string | null
    status: string
    administered_by: string | null
    location: string | null
    notes: string | null
    next_test_due: string | null
    is_random_test: boolean
    is_post_accident_test: boolean
    is_pre_employment_test: boolean
    created_at: string
    updated_at: string
}

export interface DriverTrafficConviction {
    id: number
    user_driver_detail_id: number
    carrier_id: number | null
    conviction_date: string
    location: string | null
    charge: string | null
    penalty: string | null
    conviction_type: string | null
    description: string | null
    created_at: string
    updated_at: string
}

export interface DriverMedicalQualification {
    id: number
    user_driver_detail_id: number
    social_security_number: string | null
    hire_date: string | null
    location: string | null
    is_suspended: boolean
    suspension_date: string | null
    is_terminated: boolean
    termination_date: string | null
    medical_examiner_name: string | null
    medical_examiner_registry_number: string | null
    medical_card_expiration_date: string | null
    created_at: string
    updated_at: string
    media?: Media[]
}

export interface DriverInspection {
    id: number
    user_driver_detail_id: number
    vehicle_id: number | null
    inspection_date: string
    inspection_type: string | null
    inspection_level: string | null
    inspector_name: string | null
    inspector_number: string | null
    location: string | null
    status: string
    defects_found: string | null
    corrective_actions: string | null
    is_defects_corrected: boolean
    defects_corrected_date: string | null
    is_vehicle_safe_to_operate: boolean
    notes: string | null
    created_at: string
    updated_at: string
}

export interface DriverCertification {
    id: number
    user_driver_detail_id: number
    signature: string | null
    is_accepted: boolean
    signed_at: string | null
}

export interface DriverEmploymentCompany {
    id: number
    user_driver_detail_id: number
    master_company_id: number | null
    employed_from: string | null
    employed_to: string | null
    positions_held: string | null
    subject_to_fmcsr: boolean
    safety_sensitive_function: boolean
    reason_for_leaving: string | null
    email: string | null
    verification_status: string | null
    created_at: string
    updated_at: string
    company?: MasterCompany
}

export interface MasterCompany {
    id: number
    name: string
    [key: string]: unknown
}

export interface DriverW9Form {
    id: number
    user_driver_detail_id: number
    name: string | null
    business_name: string | null
    tax_classification: string | null
    address: string | null
    city: string | null
    state: string | null
    zip_code: string | null
    tin_type: string | null
    signature: string | null
    signed_date: string | null
    pdf_path: string | null
    created_at: string
    updated_at: string
}

export interface Vehicle {
    id: number
    carrier_id: number
    make: number | null
    model: string | null
    type: number | null
    company_unit_number: string | null
    year: number | null
    vin: string | null
    gvwr: string | null
    registration_state: string | null
    registration_number: string | null
    registration_expiration_date: string | null
    permanent_tag: boolean
    tire_size: string | null
    fuel_type: string | null
    irp_apportioned_plate: boolean
    ownership_type: string | null
    driver_type: string | null
    location: string | null
    annual_inspection_expiration_date: string | null
    out_of_service: boolean
    suspended: boolean
    status: string | null
    notes: string | null
    created_at: string
    updated_at: string
    carrier?: Carrier
    driver?: UserDriverDetail
    vehicle_make?: VehicleMake
    vehicle_type?: VehicleType
    documents?: VehicleDocument[]
    maintenances?: VehicleMaintenance[]
    emergency_repairs?: EmergencyRepair[]
    active_driver_assignment?: VehicleDriverAssignment
}

export interface VehicleDocument {
    id: number
    vehicle_id: number
    document_type: string | null
    document_number: string | null
    issued_date: string | null
    expiration_date: string | null
    status: string | null
    notes: string | null
    created_at: string
    updated_at: string
    media?: Media[]
}

export interface VehicleMaintenance {
    id: number
    vehicle_id: number
    unit: string | null
    service_date: string | null
    next_service_date: string | null
    service_tasks: string | null
    vendor_mechanic: string | null
    description: string | null
    cost: number | null
    odometer: number | null
    status: boolean
    is_historical: boolean
    created_at: string
    updated_at: string
}

export interface VehicleDriverAssignment {
    id: number
    vehicle_id: number
    user_driver_detail_id: number
    driver_type: string | null
    start_date: string | null
    end_date: string | null
    status: string
    notes: string | null
    assigned_by: number | null
    created_at: string
    updated_at: string
    vehicle?: Vehicle
    driver?: UserDriverDetail
}

export interface VehicleMake {
    id: number
    name: string
}

export interface VehicleType {
    id: number
    name: string
}

export interface EmergencyRepair {
    id: number
    vehicle_id: number
    repair_name: string | null
    repair_date: string | null
    cost: number | null
    odometer: number | null
    status: string | null
    description: string | null
    notes: string | null
    attachments: string[] | null
    created_at: string
    updated_at: string
    vehicle?: Vehicle
}

export interface HosEntry {
    id: number
    user_driver_detail_id: number
    vehicle_id: number | null
    carrier_id: number
    trip_id: number | null
    status: string
    start_time: string
    end_time: string | null
    latitude: number | null
    longitude: number | null
    formatted_address: string | null
    location_available: boolean
    is_manual_entry: boolean
    is_ghost_log: boolean
    date: string
    created_at: string
    updated_at: string
}

export interface HosDailyLog {
    id: number
    user_driver_detail_id: number
    carrier_id: number
    vehicle_id: number | null
    date: string
    total_driving_minutes: number
    total_on_duty_minutes: number
    total_off_duty_minutes: number
    has_violations: boolean
    driver_signature: string | null
    signed_at: string | null
    created_at: string
    updated_at: string
}

export interface HosViolation {
    id: number
    user_driver_detail_id: number
    carrier_id: number
    vehicle_id: number | null
    violation_type: string
    violation_severity: string | null
    fmcsa_rule_reference: string | null
    violation_date: string
    hours_exceeded: number | null
    acknowledged: boolean
    is_forgiven: boolean
    created_at: string
    updated_at: string
}

export interface HosConfiguration {
    id: number
    carrier_id: number
    max_driving_hours: number
    max_duty_hours: number
    warning_threshold_minutes: number
    violation_threshold_minutes: number
    is_active: boolean
    require_30_min_break: boolean
    created_at: string
    updated_at: string
}

export interface Trip {
    id: number
    carrier_id: number
    user_driver_detail_id: number
    vehicle_id: number | null
    trip_number: string | null
    status: string
    created_at: string
    updated_at: string
    carrier?: Carrier
    driver?: UserDriverDetail
    vehicle?: Vehicle
}

export interface AdminMessage {
    id: number
    sender_type: string
    sender_id: number
    subject: string
    message: string
    priority: string | null
    status: string
    sent_at: string | null
    created_at: string
    updated_at: string
    recipients?: MessageRecipient[]
}

export interface MessageRecipient {
    id: number
    message_id: number
    recipient_type: string
    recipient_id: number
    email: string | null
    name: string | null
    delivery_status: string | null
    read_at: string | null
    created_at: string
}

export interface ContactSubmission {
    id: number
    full_name: string
    company: string | null
    email: string
    phone: string | null
    message: string
    status: string
    admin_notes: string | null
    assigned_to: number | null
    responded_at: string | null
    ip_address: string | null
    created_at: string
    updated_at: string
}

export interface PlanRequest {
    id: number
    full_name: string
    company: string | null
    email: string
    phone: string | null
    plan_name: string
    plan_price: number
    status: string
    admin_notes: string | null
    assigned_to: number | null
    responded_at: string | null
    ip_address: string | null
    created_at: string
    updated_at: string
}

export interface NotificationSetting {
    id: number
    event_type: string
    step: string | null
    recipients: string[]
    is_active: boolean
}

export interface AppNotification {
    id: string
    type: string
    data: Record<string, unknown>
    read_at: string | null
    created_at: string
}

export interface Media {
    id: number
    model_type: string
    model_id: number
    collection_name: string
    name: string
    file_name: string
    mime_type: string
    disk: string
    size: number
    original_url: string
    preview_url: string | null
    created_at: string
    updated_at: string
}
