<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import RazeLayout from '@/layouts/RazeLayout.vue'
import Form from './Form.vue'

declare function route(name: string, params?: any): string

defineOptions({ layout: RazeLayout })

const props = defineProps<{
    vehicle: {
        id: number
        carrier_id: string
        make: string
        model: string
        type: string
        company_unit_number: string | null
        year: number | string
        vin: string
        gvwr: string | null
        registration_state: string
        registration_number: string
        registration_expiration_date: string | null
        annual_inspection_expiration_date: string | null
        permanent_tag: boolean
        tire_size: string | null
        fuel_type: string | null
        irp_apportioned_plate: boolean
        location: string | null
        status: string
        status_effective_date: string | null
        notes: string | null
        driver_type: string | null
        user_driver_detail_id: string | null
        assignment_start_date: string | null
        assignment_end_date: string | null
        assignment_status: string
        assignment_notes: string | null
        owner_name: string | null
        owner_phone: string | null
        owner_email: string | null
        contract_agreed: boolean
        third_party_name: string | null
        third_party_phone: string | null
        third_party_email: string | null
        third_party_dba: string | null
        third_party_address: string | null
        third_party_contact: string | null
        third_party_fein: string | null
        third_party_email_sent: boolean
        documents_url: string
        history_url: string
    }
    carriers: { id: number; name: string }[]
    drivers: { id: number; carrier_id: number | null; carrier_name?: string | null; name: string; email?: string | null }[]
    vehicleMakes: string[]
    vehicleTypes: string[]
    driverTypes: Record<string, string>
    fuelTypes: string[]
    statusOptions: Record<string, string>
    states: Record<string, string>
    isSuperadmin?: boolean
}>()

const form = useForm({
    carrier_id: props.vehicle.carrier_id ?? '',
    make: props.vehicle.make ?? '',
    model: props.vehicle.model ?? '',
    type: props.vehicle.type ?? '',
    company_unit_number: props.vehicle.company_unit_number ?? '',
    year: String(props.vehicle.year ?? ''),
    vin: props.vehicle.vin ?? '',
    gvwr: props.vehicle.gvwr ?? '',
    registration_state: props.vehicle.registration_state ?? '',
    registration_number: props.vehicle.registration_number ?? '',
    registration_expiration_date: props.vehicle.registration_expiration_date ?? '',
    annual_inspection_expiration_date: props.vehicle.annual_inspection_expiration_date ?? '',
    permanent_tag: props.vehicle.permanent_tag ?? false,
    tire_size: props.vehicle.tire_size ?? '',
    fuel_type: props.vehicle.fuel_type ?? '',
    irp_apportioned_plate: props.vehicle.irp_apportioned_plate ?? false,
    location: props.vehicle.location ?? '',
    status: props.vehicle.status ?? 'pending',
    status_effective_date: props.vehicle.status_effective_date ?? '',
    notes: props.vehicle.notes ?? '',
    driver_type: props.vehicle.driver_type ?? '',
    user_driver_detail_id: props.vehicle.user_driver_detail_id ?? '',
    assignment_start_date: props.vehicle.assignment_start_date ?? '',
    assignment_end_date: props.vehicle.assignment_end_date ?? '',
    assignment_status: props.vehicle.assignment_status ?? 'active',
    assignment_notes: props.vehicle.assignment_notes ?? '',
    owner_name: props.vehicle.owner_name ?? '',
    owner_phone: props.vehicle.owner_phone ?? '',
    owner_email: props.vehicle.owner_email ?? '',
    contract_agreed: props.vehicle.contract_agreed ?? false,
    third_party_name: props.vehicle.third_party_name ?? '',
    third_party_phone: props.vehicle.third_party_phone ?? '',
    third_party_email: props.vehicle.third_party_email ?? '',
    third_party_dba: props.vehicle.third_party_dba ?? '',
    third_party_address: props.vehicle.third_party_address ?? '',
    third_party_contact: props.vehicle.third_party_contact ?? '',
    third_party_fein: props.vehicle.third_party_fein ?? '',
    third_party_email_sent: props.vehicle.third_party_email_sent ?? false,
    documents_url: props.vehicle.documents_url,
    history_url: props.vehicle.history_url,
})

function submit() {
    form.transform((data) => ({
        ...data,
        permanent_tag: data.permanent_tag ? 1 : 0,
        irp_apportioned_plate: data.irp_apportioned_plate ? 1 : 0,
        contract_agreed: data.contract_agreed ? 1 : 0,
        third_party_email_sent: data.third_party_email_sent ? 1 : 0,
    })).put(route('admin.vehicles.update', props.vehicle.id))
}
</script>

<template>
    <Head :title="`Edit ${vehicle.year} ${vehicle.make} ${vehicle.model}`" />

    <div class="grid grid-cols-12 gap-y-8 gap-x-6">
        <div class="col-span-12">
            <div class="box box--stacked p-6">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h1 class="text-xl font-bold text-slate-800">Edit Vehicle</h1>
                        <p class="text-sm text-slate-500 mt-0.5">{{ vehicle.year }} {{ vehicle.make }} {{ vehicle.model }}{{ vehicle.company_unit_number ? ` · Unit ${vehicle.company_unit_number}` : '' }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <Link :href="vehicle.documents_url">
                            <Button variant="outline-primary" class="flex items-center gap-2">
                                <Lucide icon="Files" class="w-4 h-4" />
                                Documents
                            </Button>
                        </Link>
                        <Link :href="route('admin.vehicles.show', vehicle.id)">
                            <Button variant="outline-secondary" class="flex items-center gap-2">
                                <Lucide icon="ArrowLeft" class="w-4 h-4" />
                                Back
                            </Button>
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12">
            <form @submit.prevent="submit" class="space-y-6">
                <Form
                    :form="form"
                    :carriers="carriers"
                    :drivers="drivers"
                    :vehicle-makes="vehicleMakes"
                    :vehicle-types="vehicleTypes"
                    :driver-types="driverTypes"
                    :fuel-types="fuelTypes"
                    :status-options="statusOptions"
                    :states="states"
                    :is-superadmin="isSuperadmin"
                />

                <div class="flex justify-end gap-3">
                    <Link :href="route('admin.vehicles.show', vehicle.id)">
                        <Button type="button" variant="outline-secondary" class="flex items-center gap-2">
                            <Lucide icon="X" class="w-4 h-4" />
                            Cancel
                        </Button>
                    </Link>
                    <Button type="submit" variant="primary" :disabled="form.processing" class="flex items-center gap-2">
                        <Lucide icon="Save" class="w-4 h-4" />
                        {{ form.processing ? 'Saving...' : 'Save Changes' }}
                    </Button>
                </div>
            </form>
        </div>
    </div>
</template>
