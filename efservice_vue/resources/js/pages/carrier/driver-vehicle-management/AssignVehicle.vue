<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import CarrierLayout from '@/layouts/CarrierLayout.vue'
import Lucide from '@/components/Base/Lucide'
import Button from '@/components/Base/Button'
import TomSelect from '@/components/Base/TomSelect/TomSelect.vue'
import Litepicker from '@/components/Base/Litepicker/Litepicker.vue'
import { FormTextarea } from '@/components/Base/Form'
import DriverSummaryCard from './components/DriverSummaryCard.vue'

declare function route(name: string, params?: any): string

defineOptions({ layout: CarrierLayout })

const props = defineProps<{
    driver: {
        id: number
        name: string
        email?: string | null
        phone?: string | null
        date_of_birth?: string | null
        status?: string | null
        profile_photo_url?: string | null
        carrier?: { id: number; name: string } | null
    }
    availableVehicles: {
        id: number
        label: string
        unit: string
        title: string
        vin: string | null
        status: string | null
        is_current?: boolean
    }[]
}>()

const pickerOptions = { singleMode: true, format: 'M/D/YYYY', autoApply: true }
const form = useForm({
    vehicle_id: '',
    assignment_date: new Date().toLocaleDateString('en-US'),
    notes: '',
})

function submit() {
    form.post(route('carrier.driver-vehicle-management.store-vehicle-assignment', props.driver.id))
}
</script>

<template>
    <Head :title="`Assign Vehicle - ${driver.name}`" />

    <div class="grid grid-cols-12 gap-x-6 gap-y-8">
        <div class="col-span-12">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                <div>
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Driver Assignment</div>
                    <h1 class="mt-1 text-2xl font-semibold text-slate-800">Assign Vehicle</h1>
                    <p class="mt-1 text-sm text-slate-500">Create the initial active assignment for this driver.</p>
                </div>
                <Link :href="route('carrier.driver-vehicle-management.show', driver.id)">
                    <Button variant="outline-secondary" class="flex items-center gap-2">
                        <Lucide icon="ArrowLeft" class="h-4 w-4" />
                        Back to Driver
                    </Button>
                </Link>
            </div>
        </div>

        <div class="col-span-12">
            <DriverSummaryCard :driver="driver" />
        </div>

        <div class="col-span-12 xl:col-span-7">
            <div class="box box--stacked p-6">
                <h2 class="text-lg font-semibold text-slate-800">Assignment Details</h2>
                <p class="mt-1 text-sm text-slate-500">Only currently available vehicles are shown here.</p>

                <div class="mt-5 grid grid-cols-1 gap-5">
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Vehicle <span class="text-red-500">*</span></label>
                        <TomSelect v-model="form.vehicle_id">
                            <option value="">Select a vehicle...</option>
                            <option v-for="vehicle in availableVehicles" :key="vehicle.id" :value="String(vehicle.id)">
                                {{ vehicle.label }}
                            </option>
                        </TomSelect>
                        <p v-if="form.errors.vehicle_id" class="mt-1 text-xs text-red-500">{{ form.errors.vehicle_id }}</p>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Assignment Date <span class="text-red-500">*</span></label>
                        <Litepicker v-model="form.assignment_date" :options="pickerOptions" />
                        <p v-if="form.errors.assignment_date" class="mt-1 text-xs text-red-500">{{ form.errors.assignment_date }}</p>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Notes</label>
                        <FormTextarea v-model="form.notes" rows="5" placeholder="Add any dispatch, onboarding, or unit notes..." />
                        <p v-if="form.errors.notes" class="mt-1 text-xs text-red-500">{{ form.errors.notes }}</p>
                    </div>
                </div>

                <div class="mt-6 flex flex-wrap items-center justify-end gap-3">
                    <Link :href="route('carrier.driver-vehicle-management.show', driver.id)">
                        <Button variant="outline-secondary">Cancel</Button>
                    </Link>
                    <Button variant="primary" :disabled="form.processing" @click="submit">
                        Save Assignment
                    </Button>
                </div>
            </div>
        </div>

        <div class="col-span-12 xl:col-span-5">
            <div class="box box--stacked p-6">
                <div class="flex items-center gap-3">
                    <Lucide icon="Truck" class="h-5 w-5 text-primary" />
                    <div>
                        <h2 class="text-lg font-semibold text-slate-800">Available Vehicles</h2>
                        <p class="text-sm text-slate-500">{{ availableVehicles.length }} unit(s) ready to assign.</p>
                    </div>
                </div>

                <div class="mt-5 space-y-3">
                    <div v-for="vehicle in availableVehicles" :key="vehicle.id" class="rounded-2xl border border-slate-200 p-4">
                        <div class="font-semibold text-slate-800">{{ vehicle.unit }}</div>
                        <div class="mt-1 text-sm text-slate-500">{{ vehicle.title }}</div>
                        <div v-if="vehicle.vin" class="mt-2 text-xs text-slate-400">VIN {{ vehicle.vin }}</div>
                    </div>

                    <div v-if="availableVehicles.length === 0" class="rounded-2xl border border-dashed border-slate-300/80 bg-slate-50/70 p-5 text-sm text-slate-500">
                        There are no available vehicles right now. Free one from another driver or create a new vehicle first.
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
