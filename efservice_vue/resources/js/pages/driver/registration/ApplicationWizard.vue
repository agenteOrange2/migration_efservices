<script setup lang="ts">
/**
 * Driver-facing application wizard.
 *
 * This page wraps the shared Wizard component and assigns RegistrationLayout
 * (no sidebar) so the driver fills their application in a clean, focused
 * environment without access to the driver admin area.
 *
 * The driver is logged in at this point (auto-logged after registration)
 * but their application is still DRAFT — CheckDriverStatus blocks the full
 * driver area and redirects here until the application is submitted.
 */
import RegistrationLayout from '@/layouts/RegistrationLayout.vue'
import WizardContent from '@/pages/admin/drivers/wizard/Wizard.vue'

defineOptions({ layout: RegistrationLayout })

// ── Mirror the exact prop interface of Wizard.vue ───────────────────────────

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

defineProps<{
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
}>()
</script>

<template>
    <!--
        Render the full wizard as a child component.
        Since ApplicationWizard.vue is the Inertia root page, RegistrationLayout
        is used. WizardContent's own defineOptions({ layout: RazeLayout }) is
        ignored by Inertia when the component is not the root page.
    -->
    <WizardContent v-bind="$props" />
</template>
