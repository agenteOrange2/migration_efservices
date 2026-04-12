<script setup lang="ts">
import { computed } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import CarrierLayout from '@/layouts/CarrierLayout.vue'
import Lucide from '@/components/Base/Lucide'
import Button from '@/components/Base/Button'
import TomSelect from '@/components/Base/TomSelect/TomSelect.vue'
import Litepicker from '@/components/Base/Litepicker/Litepicker.vue'
import { FormInput, FormTextarea } from '@/components/Base/Form'
import DriverSummaryCard from './components/DriverSummaryCard.vue'

declare function route(name: string, params?: any): string

defineOptions({ layout: CarrierLayout })

interface AssignmentDetail {
    id: number
    driver_type: string
    driver_type_label: string
    start_date: string | null
    end_date: string | null
    status: string
    status_label: string
    notes: string | null
    vehicle: { id: number; unit: string; title: string; vin: string | null } | null
    third_party?: { name?: string | null; dba?: string | null; address?: string | null; phone?: string | null; email?: string | null; fein?: string | null; contact?: string | null } | null
}

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
    currentAssignment: AssignmentDetail
    availableVehicles: {
        id: number
        label: string
        unit: string
        title: string
        vin: string | null
        status: string | null
        is_current?: boolean
    }[]
    driverTypeOptions: Record<string, string>
}>()

const pickerOptions = { singleMode: true, format: 'M/D/YYYY', autoApply: true }
const form = useForm({
    vehicle_id: String(props.currentAssignment.vehicle?.id ?? ''),
    driver_type: props.currentAssignment.driver_type,
    start_date: props.currentAssignment.start_date ?? new Date().toLocaleDateString('en-US'),
    notes: props.currentAssignment.notes ?? '',
    third_party_name: props.currentAssignment.third_party?.name ?? '',
    third_party_dba: props.currentAssignment.third_party?.dba ?? '',
    third_party_address: props.currentAssignment.third_party?.address ?? '',
    third_party_phone: props.currentAssignment.third_party?.phone ?? '',
    third_party_email: props.currentAssignment.third_party?.email ?? '',
    third_party_fein: props.currentAssignment.third_party?.fein ?? '',
    third_party_contact: props.currentAssignment.third_party?.contact ?? '',
})

const isThirdParty = computed(() => form.driver_type === 'third_party')

function submit() {
    form.put(route('carrier.driver-vehicle-management.update-assignment', props.driver.id))
}
</script>

<template>
    <Head :title="`Edit Assignment - ${driver.name}`" />

    <div class="grid grid-cols-12 gap-x-6 gap-y-8">
        <div class="col-span-12">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                <div>
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Driver Assignment</div>
                    <h1 class="mt-1 text-2xl font-semibold text-slate-800">Edit Vehicle Assignment</h1>
                    <p class="mt-1 text-sm text-slate-500">Update the vehicle, driver type, and third-party context when needed.</p>
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

        <div class="col-span-12">
            <div class="box box--stacked p-6">
                <h2 class="text-lg font-semibold text-slate-800">Current Assignment</h2>
                <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <div class="rounded-2xl border border-dashed border-slate-300/80 bg-slate-50/70 p-4">
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Vehicle</div>
                        <div class="mt-1 text-base font-semibold text-slate-800">{{ currentAssignment.vehicle?.unit || 'N/A' }}</div>
                        <div class="mt-1 text-xs text-slate-500">{{ currentAssignment.vehicle?.title || 'N/A' }}</div>
                    </div>
                    <div class="rounded-2xl border border-dashed border-slate-300/80 bg-slate-50/70 p-4">
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Driver Type</div>
                        <div class="mt-1 text-base font-semibold text-slate-800">{{ currentAssignment.driver_type_label }}</div>
                    </div>
                    <div class="rounded-2xl border border-dashed border-slate-300/80 bg-slate-50/70 p-4">
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Start Date</div>
                        <div class="mt-1 text-base font-semibold text-slate-800">{{ currentAssignment.start_date || 'N/A' }}</div>
                    </div>
                    <div class="rounded-2xl border border-dashed border-slate-300/80 bg-slate-50/70 p-4">
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Status</div>
                        <div class="mt-1 text-base font-semibold text-slate-800">{{ currentAssignment.status_label }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12">
            <div class="box box--stacked p-6">
                <h2 class="text-lg font-semibold text-slate-800">New Assignment Details</h2>
                <p class="mt-1 text-sm text-slate-500">Saving will close the current assignment and register a fresh active one.</p>

                <div class="mt-5 grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div class="md:col-span-2">
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
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Driver Type <span class="text-red-500">*</span></label>
                        <TomSelect v-model="form.driver_type">
                            <option value="">Select a driver type...</option>
                            <option v-for="(label, key) in driverTypeOptions" :key="key" :value="key">{{ label }}</option>
                        </TomSelect>
                        <p v-if="form.errors.driver_type" class="mt-1 text-xs text-red-500">{{ form.errors.driver_type }}</p>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Start Date <span class="text-red-500">*</span></label>
                        <Litepicker v-model="form.start_date" :options="pickerOptions" />
                        <p v-if="form.errors.start_date" class="mt-1 text-xs text-red-500">{{ form.errors.start_date }}</p>
                    </div>

                    <div v-if="isThirdParty" class="md:col-span-2 rounded-2xl border border-primary/15 bg-primary/5 p-5">
                        <h3 class="text-base font-semibold text-slate-800">Third-Party Information</h3>
                        <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Company Name <span class="text-red-500">*</span></label>
                                <FormInput v-model="form.third_party_name" type="text" placeholder="Enter company name" />
                                <p v-if="form.errors.third_party_name" class="mt-1 text-xs text-red-500">{{ form.errors.third_party_name }}</p>
                            </div>
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">DBA</label>
                                <FormInput v-model="form.third_party_dba" type="text" placeholder="Doing business as" />
                            </div>
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Contact Person</label>
                                <FormInput v-model="form.third_party_contact" type="text" placeholder="Primary contact" />
                            </div>
                            <div class="md:col-span-2 xl:col-span-3">
                                <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Address <span class="text-red-500">*</span></label>
                                <FormInput v-model="form.third_party_address" type="text" placeholder="Complete address" />
                                <p v-if="form.errors.third_party_address" class="mt-1 text-xs text-red-500">{{ form.errors.third_party_address }}</p>
                            </div>
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Phone <span class="text-red-500">*</span></label>
                                <FormInput v-model="form.third_party_phone" type="text" placeholder="Phone number" />
                                <p v-if="form.errors.third_party_phone" class="mt-1 text-xs text-red-500">{{ form.errors.third_party_phone }}</p>
                            </div>
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Email <span class="text-red-500">*</span></label>
                                <FormInput v-model="form.third_party_email" type="email" placeholder="Email address" />
                                <p v-if="form.errors.third_party_email" class="mt-1 text-xs text-red-500">{{ form.errors.third_party_email }}</p>
                            </div>
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">FEIN</label>
                                <FormInput v-model="form.third_party_fein" type="text" placeholder="Tax identifier" />
                            </div>
                        </div>
                    </div>

                    <div class="md:col-span-2">
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Notes</label>
                        <FormTextarea v-model="form.notes" rows="5" placeholder="Add any details about this reassignment..." />
                        <p v-if="form.errors.notes" class="mt-1 text-xs text-red-500">{{ form.errors.notes }}</p>
                    </div>
                </div>

                <div class="mt-6 flex flex-wrap items-center justify-end gap-3">
                    <Link :href="route('carrier.driver-vehicle-management.show', driver.id)">
                        <Button variant="outline-secondary">Cancel</Button>
                    </Link>
                    <Button variant="primary" :disabled="form.processing" @click="submit">
                        Update Assignment
                    </Button>
                </div>
            </div>
        </div>
    </div>
</template>
