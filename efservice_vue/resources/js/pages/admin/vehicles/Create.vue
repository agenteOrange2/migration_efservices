<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import RazeLayout from '@/layouts/RazeLayout.vue'
import Form from './Form.vue'

declare function route(name: string, params?: any): string

defineOptions({ layout: RazeLayout })

const props = defineProps<{
    carriers: { id: number; name: string }[]
    drivers: { id: number; carrier_id: number | null; carrier_name?: string | null; name: string; email?: string | null }[]
    vehicleMakes: string[]
    vehicleTypes: string[]
    driverTypes: Record<string, string>
    fuelTypes: string[]
    statusOptions: Record<string, string>
    states: Record<string, string>
    isSuperadmin?: boolean
    selectedCarrierId?: number | null
}>()

const form = useForm({
    carrier_id: props.selectedCarrierId ? String(props.selectedCarrierId) : '',
    make: '',
    model: '',
    type: '',
    company_unit_number: '',
    year: '',
    vin: '',
    gvwr: '',
    registration_state: '',
    registration_number: '',
    registration_expiration_date: '',
    annual_inspection_expiration_date: '',
    permanent_tag: false,
    tire_size: '',
    fuel_type: '',
    irp_apportioned_plate: false,
    location: '',
    status: 'pending',
    status_effective_date: '',
    notes: '',
    driver_type: '',
    user_driver_detail_id: '',
    assignment_start_date: '',
    assignment_end_date: '',
    assignment_status: 'active',
    assignment_notes: '',
    owner_name: '',
    owner_phone: '',
    owner_email: '',
    contract_agreed: false,
    third_party_name: '',
    third_party_phone: '',
    third_party_email: '',
    third_party_dba: '',
    third_party_address: '',
    third_party_contact: '',
    third_party_fein: '',
    third_party_email_sent: false,
    documents_url: '',
    history_url: '',
})

function submit() {
    form.transform((data) => ({
        ...data,
        permanent_tag: data.permanent_tag ? 1 : 0,
        irp_apportioned_plate: data.irp_apportioned_plate ? 1 : 0,
        contract_agreed: data.contract_agreed ? 1 : 0,
        third_party_email_sent: data.third_party_email_sent ? 1 : 0,
    })).post(route('admin.vehicles.store'))
}
</script>

<template>
    <Head title="Add Vehicle" />

    <div class="grid grid-cols-12 gap-y-8 gap-x-6">
        <div class="col-span-12">
            <div class="box box--stacked p-6">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h1 class="text-xl font-bold text-slate-800">Add Vehicle</h1>
                        <p class="text-sm text-slate-500 mt-0.5">Create a vehicle record with assignment details and document-ready metadata.</p>
                    </div>
                    <Link :href="route('admin.vehicles.index')">
                        <Button variant="outline-secondary" class="flex items-center gap-2">
                            <Lucide icon="ArrowLeft" class="w-4 h-4" />
                            Back to Vehicles
                        </Button>
                    </Link>
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
                    <Link :href="route('admin.vehicles.index')">
                        <Button type="button" variant="outline-secondary" class="flex items-center gap-2">
                            <Lucide icon="X" class="w-4 h-4" />
                            Cancel
                        </Button>
                    </Link>
                    <Button type="submit" variant="primary" :disabled="form.processing" class="flex items-center gap-2">
                        <Lucide icon="Save" class="w-4 h-4" />
                        {{ form.processing ? 'Saving...' : 'Save Vehicle' }}
                    </Button>
                </div>
            </form>
        </div>
    </div>
</template>
