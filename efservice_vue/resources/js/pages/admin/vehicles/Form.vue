<script setup lang="ts">
import { computed, watch } from 'vue'
import Lucide from '@/components/Base/Lucide'
import Litepicker from '@/components/Base/Litepicker/Litepicker.vue'
import TomSelect from '@/components/Base/TomSelect/TomSelect.vue'
import { FormInput, FormTextarea } from '@/components/Base/Form'

declare function route(name: string, params?: any): string

const props = defineProps<{
    form: any
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

const lpOptions = { singleMode: true, format: 'M/D/YYYY', autoApply: true }

const filteredDrivers = computed(() => {
    if (!props.form.carrier_id) return props.drivers
    return props.drivers.filter((driver) => String(driver.carrier_id ?? '') === String(props.form.carrier_id))
})

const showStatusDate = computed(() => ['suspended', 'out_of_service'].includes(props.form.status))
const showAssignmentDetails = computed(() => !!props.form.driver_type)
const showOwnerOperator = computed(() => props.form.driver_type === 'owner_operator')
const showThirdParty = computed(() => props.form.driver_type === 'third_party')

watch(() => props.form.carrier_id, (carrierId) => {
    if (!carrierId) return

    const driverMatchesCarrier = filteredDrivers.value.some((driver) => String(driver.id) === String(props.form.user_driver_detail_id))
    if (!driverMatchesCarrier) {
        props.form.user_driver_detail_id = ''
    }
})

watch(() => props.form.status, (status) => {
    if (!['suspended', 'out_of_service'].includes(status)) {
        props.form.status_effective_date = ''
    }
})

watch(() => props.form.driver_type, (driverType) => {
    if (driverType !== 'owner_operator') {
        props.form.owner_name = ''
        props.form.owner_phone = ''
        props.form.owner_email = ''
        props.form.contract_agreed = false
    }

    if (driverType !== 'third_party') {
        props.form.third_party_name = ''
        props.form.third_party_phone = ''
        props.form.third_party_email = ''
        props.form.third_party_dba = ''
        props.form.third_party_address = ''
        props.form.third_party_contact = ''
        props.form.third_party_fein = ''
        props.form.third_party_email_sent = false
    }

    if (!driverType) {
        props.form.assignment_start_date = ''
        props.form.assignment_end_date = ''
        props.form.assignment_status = 'active'
        props.form.assignment_notes = ''
    }
})
</script>

<template>
    <div class="space-y-6">
        <div class="box box--stacked p-6">
            <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 mb-5">
                <h2 class="text-sm font-semibold text-slate-700 flex items-center gap-2">
                    <Lucide icon="Truck" class="w-4 h-4 text-primary" />
                    Vehicle Profile
                </h2>

                <div v-if="form.documents_url || form.history_url" class="flex flex-wrap gap-2">
                    <a
                        v-if="form.documents_url"
                        :href="form.documents_url"
                        class="inline-flex items-center gap-2 rounded-lg border border-primary/20 bg-primary/5 px-3 py-2 text-xs font-medium text-primary hover:bg-primary/10"
                    >
                        <Lucide icon="Files" class="w-4 h-4" />
                        Documents
                    </a>
                    <a
                        v-if="form.history_url"
                        :href="form.history_url"
                        class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-medium text-slate-600 hover:bg-slate-100"
                    >
                        <Lucide icon="History" class="w-4 h-4" />
                        Assignment History
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1.5">Carrier <span class="text-red-500">*</span></label>
                    <TomSelect v-model="form.carrier_id">
                        <option value="">Select carrier</option>
                        <option v-for="carrier in carriers" :key="carrier.id" :value="String(carrier.id)">{{ carrier.name }}</option>
                    </TomSelect>
                    <p v-if="form.errors.carrier_id" class="text-red-500 text-xs mt-1">{{ form.errors.carrier_id }}</p>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1.5">Unit Number</label>
                    <FormInput v-model="form.company_unit_number" type="text" placeholder="e.g. TRK-102" />
                    <p v-if="form.errors.company_unit_number" class="text-red-500 text-xs mt-1">{{ form.errors.company_unit_number }}</p>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1.5">Year <span class="text-red-500">*</span></label>
                    <FormInput v-model="form.year" type="number" min="1900" :max="new Date().getFullYear() + 1" />
                    <p v-if="form.errors.year" class="text-red-500 text-xs mt-1">{{ form.errors.year }}</p>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1.5">Status <span class="text-red-500">*</span></label>
                    <TomSelect v-model="form.status">
                        <option value="">Select status</option>
                        <option v-for="(label, key) in statusOptions" :key="key" :value="key">{{ label }}</option>
                    </TomSelect>
                    <p v-if="form.errors.status" class="text-red-500 text-xs mt-1">{{ form.errors.status }}</p>
                </div>
            </div>

            <div v-if="showStatusDate" class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1.5">Status Effective Date</label>
                    <Litepicker v-model="form.status_effective_date" :options="lpOptions" />
                    <p v-if="form.errors.status_effective_date" class="text-red-500 text-xs mt-1">{{ form.errors.status_effective_date }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-500">
                    Use this date for suspension or out-of-service events. Vehicle dates must stay in <span class="font-medium text-slate-700">M/D/YYYY</span>.
                </div>
            </div>
        </div>

        <div class="box box--stacked p-6">
            <div class="flex items-center justify-between gap-3 mb-5">
                <h2 class="text-sm font-semibold text-slate-700 flex items-center gap-2">
                    <Lucide icon="BadgeInfo" class="w-4 h-4 text-primary" />
                    Vehicle Specifications
                </h2>
                <div class="flex gap-2 text-xs">
                    <a :href="route('admin.vehicle-makes.index')" class="text-primary hover:underline">Manage makes</a>
                    <span class="text-slate-300">|</span>
                    <a :href="route('admin.vehicle-types.index')" class="text-primary hover:underline">Manage types</a>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1.5">Make <span class="text-red-500">*</span></label>
                    <FormInput v-model="form.make" type="text" list="vehicle-makes-list" placeholder="Select or type a make" />
                    <datalist id="vehicle-makes-list">
                        <option v-for="make in vehicleMakes" :key="make" :value="make" />
                    </datalist>
                    <p v-if="form.errors.make" class="text-red-500 text-xs mt-1">{{ form.errors.make }}</p>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1.5">Model <span class="text-red-500">*</span></label>
                    <FormInput v-model="form.model" type="text" placeholder="Model" />
                    <p v-if="form.errors.model" class="text-red-500 text-xs mt-1">{{ form.errors.model }}</p>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1.5">Type <span class="text-red-500">*</span></label>
                    <FormInput v-model="form.type" type="text" list="vehicle-types-list" placeholder="Select or type a type" />
                    <datalist id="vehicle-types-list">
                        <option v-for="type in vehicleTypes" :key="type" :value="type" />
                    </datalist>
                    <p v-if="form.errors.type" class="text-red-500 text-xs mt-1">{{ form.errors.type }}</p>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1.5">VIN <span class="text-red-500">*</span></label>
                    <FormInput v-model="form.vin" type="text" maxlength="17" placeholder="17-character VIN" />
                    <p v-if="form.errors.vin" class="text-red-500 text-xs mt-1">{{ form.errors.vin }}</p>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1.5">GVWR</label>
                    <FormInput v-model="form.gvwr" type="text" placeholder="Gross vehicle weight rating" />
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1.5">Tire Size</label>
                    <FormInput v-model="form.tire_size" type="text" placeholder="e.g. 11R22.5" />
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1.5">Fuel Type</label>
                    <TomSelect v-model="form.fuel_type">
                        <option value="">Select fuel type</option>
                        <option v-for="fuelType in fuelTypes" :key="fuelType" :value="fuelType">{{ fuelType }}</option>
                    </TomSelect>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1.5">Location</label>
                    <FormInput v-model="form.location" type="text" placeholder="Current location" />
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-4">
                <label class="flex items-center gap-3 rounded-lg border border-slate-200 p-3 text-sm text-slate-700">
                    <input v-model="form.irp_apportioned_plate" type="checkbox" class="rounded border-slate-300 text-primary focus:ring-primary/30" />
                    IRP apportioned plate
                </label>
                <label class="flex items-center gap-3 rounded-lg border border-slate-200 p-3 text-sm text-slate-700">
                    <input v-model="form.permanent_tag" type="checkbox" class="rounded border-slate-300 text-primary focus:ring-primary/30" />
                    Permanent tag
                </label>
            </div>
        </div>

        <div class="box box--stacked p-6">
            <h2 class="text-sm font-semibold text-slate-700 mb-5 flex items-center gap-2">
                <Lucide icon="FileBadge" class="w-4 h-4 text-primary" />
                Registration & Inspection
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1.5">Registration State <span class="text-red-500">*</span></label>
                    <TomSelect v-model="form.registration_state">
                        <option value="">Select state</option>
                        <option v-for="(label, code) in states" :key="code" :value="code">{{ label }}</option>
                    </TomSelect>
                    <p v-if="form.errors.registration_state" class="text-red-500 text-xs mt-1">{{ form.errors.registration_state }}</p>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1.5">Registration Number <span class="text-red-500">*</span></label>
                    <FormInput v-model="form.registration_number" type="text" placeholder="Registration / tag number" />
                    <p v-if="form.errors.registration_number" class="text-red-500 text-xs mt-1">{{ form.errors.registration_number }}</p>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1.5">Registration Expiration</label>
                    <Litepicker v-model="form.registration_expiration_date" :options="lpOptions" />
                    <p v-if="form.errors.registration_expiration_date" class="text-red-500 text-xs mt-1">{{ form.errors.registration_expiration_date }}</p>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1.5">Annual Inspection Expiration</label>
                    <Litepicker v-model="form.annual_inspection_expiration_date" :options="lpOptions" />
                    <p v-if="form.errors.annual_inspection_expiration_date" class="text-red-500 text-xs mt-1">{{ form.errors.annual_inspection_expiration_date }}</p>
                </div>
            </div>
        </div>

        <div class="box box--stacked p-6">
            <h2 class="text-sm font-semibold text-slate-700 mb-5 flex items-center gap-2">
                <Lucide icon="Users" class="w-4 h-4 text-primary" />
                Driver Assignment
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1.5">Driver Type</label>
                    <TomSelect v-model="form.driver_type">
                        <option value="">Leave unassigned</option>
                        <option v-for="(label, key) in driverTypes" :key="key" :value="key">{{ label }}</option>
                    </TomSelect>
                    <p v-if="form.errors.driver_type" class="text-red-500 text-xs mt-1">{{ form.errors.driver_type }}</p>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1.5">Assigned Driver</label>
                    <TomSelect v-model="form.user_driver_detail_id">
                        <option value="">No driver assigned</option>
                        <option v-for="driver in filteredDrivers" :key="driver.id" :value="String(driver.id)">
                            {{ driver.name }}{{ driver.carrier_name ? ` · ${driver.carrier_name}` : '' }}
                        </option>
                    </TomSelect>
                    <p v-if="form.errors.user_driver_detail_id" class="text-red-500 text-xs mt-1">{{ form.errors.user_driver_detail_id }}</p>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1.5">Assignment Start</label>
                    <Litepicker v-model="form.assignment_start_date" :options="lpOptions" />
                    <p v-if="form.errors.assignment_start_date" class="text-red-500 text-xs mt-1">{{ form.errors.assignment_start_date }}</p>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1.5">Assignment Status</label>
                    <TomSelect v-model="form.assignment_status">
                        <option value="active">Active</option>
                        <option value="pending">Pending</option>
                        <option value="inactive">Inactive</option>
                    </TomSelect>
                    <p v-if="form.errors.assignment_status" class="text-red-500 text-xs mt-1">{{ form.errors.assignment_status }}</p>
                </div>
            </div>

            <div v-if="showAssignmentDetails" class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1.5">Assignment End</label>
                    <Litepicker v-model="form.assignment_end_date" :options="lpOptions" />
                    <p v-if="form.errors.assignment_end_date" class="text-red-500 text-xs mt-1">{{ form.errors.assignment_end_date }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-500">
                    `Company Driver` can stay without a specific driver selected. `Owner Operator` and `Third Party` use the detail sections below.
                </div>
            </div>

            <div v-if="showOwnerOperator" class="mt-5 rounded-xl border border-slate-200 p-5">
                <h3 class="text-sm font-semibold text-slate-700 mb-4">Owner Operator Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1.5">Owner Name <span class="text-red-500">*</span></label>
                        <FormInput v-model="form.owner_name" type="text" placeholder="Owner full name" />
                        <p v-if="form.errors.owner_name" class="text-red-500 text-xs mt-1">{{ form.errors.owner_name }}</p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1.5">Owner Phone</label>
                        <FormInput v-model="form.owner_phone" type="text" placeholder="Phone number" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1.5">Owner Email</label>
                        <FormInput v-model="form.owner_email" type="email" placeholder="owner@example.com" />
                    </div>
                </div>
                <label class="mt-4 flex items-center gap-3 rounded-lg border border-slate-200 p-3 text-sm text-slate-700">
                    <input v-model="form.contract_agreed" type="checkbox" class="rounded border-slate-300 text-primary focus:ring-primary/30" />
                    Contract agreed / authority acknowledged
                </label>
            </div>

            <div v-if="showThirdParty" class="mt-5 rounded-xl border border-slate-200 p-5">
                <h3 class="text-sm font-semibold text-slate-700 mb-4">Third Party Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1.5">Company Name <span class="text-red-500">*</span></label>
                        <FormInput v-model="form.third_party_name" type="text" placeholder="Company name" />
                        <p v-if="form.errors.third_party_name" class="text-red-500 text-xs mt-1">{{ form.errors.third_party_name }}</p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1.5">Phone</label>
                        <FormInput v-model="form.third_party_phone" type="text" placeholder="Phone number" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1.5">Email</label>
                        <FormInput v-model="form.third_party_email" type="email" placeholder="dispatch@example.com" />
                        <p v-if="form.errors.third_party_email" class="text-red-500 text-xs mt-1">{{ form.errors.third_party_email }}</p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1.5">DBA</label>
                        <FormInput v-model="form.third_party_dba" type="text" placeholder="Doing business as" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1.5">Contact Name</label>
                        <FormInput v-model="form.third_party_contact" type="text" placeholder="Primary contact" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1.5">FEIN</label>
                        <FormInput v-model="form.third_party_fein" type="text" placeholder="Federal EIN" />
                    </div>
                    <div class="md:col-span-3">
                        <label class="block text-xs font-medium text-slate-600 mb-1.5">Address</label>
                        <FormInput v-model="form.third_party_address" type="text" placeholder="Street address" />
                    </div>
                </div>

                <label class="mt-4 flex items-center gap-3 rounded-lg border border-slate-200 p-3 text-sm text-slate-700">
                    <input v-model="form.third_party_email_sent" type="checkbox" class="rounded border-slate-300 text-primary focus:ring-primary/30" />
                    Verification email already sent
                </label>
            </div>

            <div v-if="showAssignmentDetails" class="mt-4">
                <label class="block text-xs font-medium text-slate-600 mb-1.5">Assignment Notes</label>
                <FormTextarea v-model="form.assignment_notes" rows="3" placeholder="Any assignment-specific notes..." />
                <p v-if="form.errors.assignment_notes" class="text-red-500 text-xs mt-1">{{ form.errors.assignment_notes }}</p>
            </div>
        </div>

        <div class="box box--stacked p-6">
            <h2 class="text-sm font-semibold text-slate-700 mb-4 flex items-center gap-2">
                <Lucide icon="StickyNote" class="w-4 h-4 text-primary" />
                Notes
            </h2>
            <FormTextarea v-model="form.notes" rows="5" placeholder="Operational notes, reminders, or internal comments..." />
            <p v-if="form.errors.notes" class="text-red-500 text-xs mt-1">{{ form.errors.notes }}</p>
        </div>
    </div>
</template>
