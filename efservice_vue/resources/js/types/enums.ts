export const CarrierStatus = {
    PENDING: 0,
    ACTIVE: 1,
    INACTIVE: 2,
    SUSPENDED: 3,
} as const

export type CarrierStatusValue = (typeof CarrierStatus)[keyof typeof CarrierStatus]

export const CarrierStatusLabels: Record<CarrierStatusValue, string> = {
    [CarrierStatus.PENDING]: 'Pending',
    [CarrierStatus.ACTIVE]: 'Active',
    [CarrierStatus.INACTIVE]: 'Inactive',
    [CarrierStatus.SUSPENDED]: 'Suspended',
}

export const DriverStatus = {
    PENDING: 0,
    ACTIVE: 1,
    INACTIVE: 2,
    SUSPENDED: 3,
    REJECTED: 4,
} as const

export type DriverStatusValue = (typeof DriverStatus)[keyof typeof DriverStatus]

export const DriverStatusLabels: Record<DriverStatusValue, string> = {
    [DriverStatus.PENDING]: 'Pending',
    [DriverStatus.ACTIVE]: 'Active',
    [DriverStatus.INACTIVE]: 'Inactive',
    [DriverStatus.SUSPENDED]: 'Suspended',
    [DriverStatus.REJECTED]: 'Rejected',
}

export const VehicleStatus = {
    ACTIVE: 'active',
    INACTIVE: 'inactive',
    OUT_OF_SERVICE: 'out_of_service',
    SUSPENDED: 'suspended',
} as const

export type VehicleStatusValue = (typeof VehicleStatus)[keyof typeof VehicleStatus]

export const DocumentStatus = {
    PENDING: 'pending',
    APPROVED: 'approved',
    REJECTED: 'rejected',
    EXPIRED: 'expired',
} as const

export type DocumentStatusValue = (typeof DocumentStatus)[keyof typeof DocumentStatus]

export const HosStatus = {
    OFF_DUTY: 'off_duty',
    SLEEPER_BERTH: 'sleeper_berth',
    DRIVING: 'driving',
    ON_DUTY: 'on_duty',
} as const

export type HosStatusValue = (typeof HosStatus)[keyof typeof HosStatus]

export const TripStatus = {
    PENDING: 'pending',
    IN_PROGRESS: 'in_progress',
    COMPLETED: 'completed',
    CANCELLED: 'cancelled',
} as const

export type TripStatusValue = (typeof TripStatus)[keyof typeof TripStatus]

export const MessagePriority = {
    LOW: 'low',
    NORMAL: 'normal',
    HIGH: 'high',
    URGENT: 'urgent',
} as const

export type MessagePriorityValue = (typeof MessagePriority)[keyof typeof MessagePriority]

export const TestType = {
    PRE_EMPLOYMENT: 'pre_employment',
    RANDOM: 'random',
    POST_ACCIDENT: 'post_accident',
    REASONABLE_SUSPICION: 'reasonable_suspicion',
    RETURN_TO_DUTY: 'return_to_duty',
    FOLLOW_UP: 'follow_up',
} as const

export type TestTypeValue = (typeof TestType)[keyof typeof TestType]

export const InspectionType = {
    PRE_TRIP: 'pre_trip',
    POST_TRIP: 'post_trip',
    DOT: 'dot',
    ANNUAL: 'annual',
} as const

export type InspectionTypeValue = (typeof InspectionType)[keyof typeof InspectionType]

export const DriverType = {
    COMPANY_DRIVER: 'company_driver',
    OWNER_OPERATOR: 'owner_operator',
    THIRD_PARTY: 'third_party',
} as const

export type DriverTypeValue = (typeof DriverType)[keyof typeof DriverType]
