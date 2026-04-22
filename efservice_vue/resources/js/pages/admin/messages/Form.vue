<script setup lang="ts">
import { computed } from 'vue'
import Lucide from '@/components/Base/Lucide'
import TomSelect from '@/components/Base/TomSelect/TomSelect.vue'
import { FormHelp, FormInput, FormLabel, FormSelect, FormTextarea } from '@/components/Base/Form'

interface DriverOption {
    id: number
    name: string
    email: string
    carrier_name: string | null
}

interface CarrierOption {
    id: number
    name: string
    email: string | null
    contact_name: string | null
}

const props = defineProps<{
    mode: 'create' | 'edit'
    form: any
    drivers: DriverOption[]
    carriers: CarrierOption[]
}>()

const messageLength = computed(() => String(props.form.message ?? '').length)
const selectedType = computed(() => props.mode === 'create' ? props.form.recipient_type : props.form.add_recipient_type)
const recipientTypeModel = computed({
    get: () => props.mode === 'create' ? props.form.recipient_type : props.form.add_recipient_type,
    set: (value: string) => {
        if (props.mode === 'create') {
            props.form.recipient_type = value
            return
        }

        props.form.add_recipient_type = value
    },
})
const driverIdsModel = computed({
    get: () => props.mode === 'create' ? props.form.driver_ids : props.form.add_driver_ids,
    set: (value: string[]) => {
        if (props.mode === 'create') {
            props.form.driver_ids = value
            return
        }

        props.form.add_driver_ids = value
    },
})
const carrierIdsModel = computed({
    get: () => props.mode === 'create' ? props.form.carrier_ids : props.form.add_carrier_ids,
    set: (value: string[]) => {
        if (props.mode === 'create') {
            props.form.carrier_ids = value
            return
        }

        props.form.add_carrier_ids = value
    },
})
const customEmailsModel = computed({
    get: () => props.mode === 'create' ? props.form.custom_emails : props.form.add_custom_emails,
    set: (value: string) => {
        if (props.mode === 'create') {
            props.form.custom_emails = value
            return
        }

        props.form.add_custom_emails = value
    },
})
const selectedDriverCount = computed(() => {
    const rows = driverIdsModel.value
    return Array.isArray(rows) ? rows.length : 0
})
const selectedCarrierCount = computed(() => {
    const rows = carrierIdsModel.value
    return Array.isArray(rows) ? rows.length : 0
})
const customEmailsPreview = computed(() => {
    const raw = String(customEmailsModel.value ?? '')
    return raw
        .split(/[\s,;]+/)
        .map((value) => value.trim())
        .filter(Boolean)
        .length
})
</script>

<template>
    <div class="space-y-6">
        <div class="box box--stacked p-6">
            <div class="mb-5 flex items-center gap-3 border-b border-slate-200/70 pb-4">
                <Lucide icon="Mail" class="h-5 w-5 text-primary" />
                <div>
                    <h2 class="text-lg font-semibold text-slate-800">Message Details</h2>
                    <p class="text-sm text-slate-500">Compose the subject, content, and delivery intent for this message.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-5">
                <div>
                    <FormLabel>Subject *</FormLabel>
                    <FormInput v-model="form.subject" type="text" placeholder="Enter message subject..." />
                    <p v-if="form.errors.subject" class="mt-1 text-xs text-danger">{{ form.errors.subject }}</p>
                </div>

                <div>
                    <FormLabel>Message *</FormLabel>
                    <FormTextarea v-model="form.message" rows="8" placeholder="Write the message you want recipients to receive..." />
                    <div class="mt-1 flex items-center justify-between text-xs text-slate-500">
                        <span>Keep the content clear and direct.</span>
                        <span>{{ messageLength }}/2000</span>
                    </div>
                    <p v-if="form.errors.message" class="mt-1 text-xs text-danger">{{ form.errors.message }}</p>
                </div>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div>
                        <FormLabel>Priority *</FormLabel>
                        <FormSelect v-model="form.priority">
                            <option value="low">Low</option>
                            <option value="normal">Normal</option>
                            <option value="high">High</option>
                        </FormSelect>
                        <p v-if="form.errors.priority" class="mt-1 text-xs text-danger">{{ form.errors.priority }}</p>
                    </div>

                    <div>
                        <FormLabel>{{ mode === 'create' ? 'Delivery' : 'Draft Status' }} *</FormLabel>
                        <FormSelect v-model="form.status">
                            <option value="draft">Save as Draft</option>
                            <option value="sent">Send Now</option>
                        </FormSelect>
                        <FormHelp>
                            {{ mode === 'create' ? 'Drafts can be reviewed before sending.' : 'Changing to "Send Now" will deliver the draft to all pending recipients.' }}
                        </FormHelp>
                        <p v-if="form.errors.status" class="mt-1 text-xs text-danger">{{ form.errors.status }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="box box--stacked p-6">
            <div class="mb-5 flex items-center gap-3 border-b border-slate-200/70 pb-4">
                <Lucide :icon="mode === 'create' ? 'Users' : 'UserPlus'" class="h-5 w-5 text-primary" />
                <div>
                    <h2 class="text-lg font-semibold text-slate-800">{{ mode === 'create' ? 'Recipients' : 'Add Recipients' }}</h2>
                    <p class="text-sm text-slate-500">
                        {{ mode === 'create' ? 'Choose who should receive this message.' : 'Append more recipients to this draft if needed.' }}
                    </p>
                </div>
            </div>

            <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3">
                <div class="rounded-2xl border border-primary/10 bg-primary/5 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Drivers</p>
                    <p class="mt-2 text-2xl font-semibold text-primary">{{ selectedDriverCount }}</p>
                    <p class="mt-1 text-xs text-slate-500">Currently selected in this form.</p>
                </div>
                <div class="rounded-2xl border border-success/10 bg-success/5 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Carriers</p>
                    <p class="mt-2 text-2xl font-semibold text-success">{{ selectedCarrierCount }}</p>
                    <p class="mt-1 text-xs text-slate-500">Carrier contacts that will receive the message.</p>
                </div>
                <div class="rounded-2xl border border-info/10 bg-info/5 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Email Entries</p>
                    <p class="mt-2 text-2xl font-semibold text-info">{{ customEmailsPreview }}</p>
                    <p class="mt-1 text-xs text-slate-500">Detected from the pasted email list.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                <label
                    v-if="mode === 'create'"
                    class="cursor-pointer rounded-2xl border p-4 transition"
                    :class="selectedType === 'all_drivers' ? 'border-primary bg-primary/5' : 'border-slate-200 hover:border-primary/30'"
                >
                    <input v-model="form.recipient_type" type="radio" value="all_drivers" class="sr-only">
                    <div class="flex items-start gap-3">
                        <div class="rounded-xl bg-primary/10 p-2">
                            <Lucide icon="Users" class="h-5 w-5 text-primary" />
                        </div>
                        <div>
                            <p class="font-semibold text-slate-800">All Drivers</p>
                            <p class="mt-1 text-sm text-slate-500">Send to every active driver, optionally filtered by carrier.</p>
                        </div>
                    </div>
                </label>

                <label
                    class="cursor-pointer rounded-2xl border p-4 transition"
                    :class="selectedType === (mode === 'create' ? 'specific_drivers' : 'specific_drivers') ? 'border-primary bg-primary/5' : 'border-slate-200 hover:border-primary/30'"
                >
                    <input v-model="recipientTypeModel" type="radio" value="specific_drivers" class="sr-only">
                    <div class="flex items-start gap-3">
                        <div class="rounded-xl bg-primary/10 p-2">
                            <Lucide icon="UserCheck" class="h-5 w-5 text-primary" />
                        </div>
                        <div>
                            <p class="font-semibold text-slate-800">Specific Drivers</p>
                            <p class="mt-1 text-sm text-slate-500">Select one or more individual drivers.</p>
                        </div>
                    </div>
                </label>

                <label
                    class="cursor-pointer rounded-2xl border p-4 transition"
                    :class="selectedType === 'specific_carriers' ? 'border-primary bg-primary/5' : 'border-slate-200 hover:border-primary/30'"
                >
                    <input v-model="recipientTypeModel" type="radio" value="specific_carriers" class="sr-only">
                    <div class="flex items-start gap-3">
                        <div class="rounded-xl bg-primary/10 p-2">
                            <Lucide icon="Building2" class="h-5 w-5 text-primary" />
                        </div>
                        <div>
                            <p class="font-semibold text-slate-800">Carriers</p>
                            <p class="mt-1 text-sm text-slate-500">Deliver to carrier contacts using their main email.</p>
                        </div>
                    </div>
                </label>

                <label
                    class="cursor-pointer rounded-2xl border p-4 transition"
                    :class="selectedType === 'custom_emails' ? 'border-primary bg-primary/5' : 'border-slate-200 hover:border-primary/30'"
                >
                    <input v-model="recipientTypeModel" type="radio" value="custom_emails" class="sr-only">
                    <div class="flex items-start gap-3">
                        <div class="rounded-xl bg-primary/10 p-2">
                            <Lucide icon="AtSign" class="h-5 w-5 text-primary" />
                        </div>
                        <div>
                            <p class="font-semibold text-slate-800">Custom Emails</p>
                            <p class="mt-1 text-sm text-slate-500">Paste external recipients separated by commas or spaces.</p>
                        </div>
                    </div>
                </label>
            </div>

            <div v-if="mode === 'create' && selectedType === 'all_drivers'" class="mt-6">
                <FormLabel>Carrier Filter</FormLabel>
                <TomSelect v-model="form.carrier_filter">
                    <option value="">All carriers</option>
                    <option v-for="carrier in carriers" :key="carrier.id" :value="String(carrier.id)">
                        {{ carrier.name }}
                    </option>
                </TomSelect>
                <FormHelp>Leave empty to include all active drivers across all carriers.</FormHelp>
            </div>

            <div v-if="selectedType === 'specific_drivers'" class="mt-6">
                <FormLabel>{{ mode === 'create' ? 'Driver Selection' : 'Drivers to Add' }}</FormLabel>
                <TomSelect
                    v-model="driverIdsModel"
                    multiple
                    :options="{ placeholder: 'Select drivers...', plugins: { remove_button: { title: 'Remove' } } }"
                >
                    <option v-for="driver in drivers" :key="driver.id" :value="String(driver.id)">
                        {{ driver.name }}{{ driver.email ? ` (${driver.email})` : '' }}{{ driver.carrier_name ? ` - ${driver.carrier_name}` : '' }}
                    </option>
                </TomSelect>
                <p v-if="mode === 'create' && form.errors.driver_ids" class="mt-1 text-xs text-danger">{{ form.errors.driver_ids }}</p>
                <p v-if="mode === 'edit' && form.errors.add_driver_ids" class="mt-1 text-xs text-danger">{{ form.errors.add_driver_ids }}</p>
            </div>

            <div v-if="selectedType === 'specific_carriers'" class="mt-6">
                <FormLabel>{{ mode === 'create' ? 'Carrier Selection' : 'Carriers to Add' }}</FormLabel>
                <TomSelect
                    v-model="carrierIdsModel"
                    multiple
                    :options="{ placeholder: 'Select carriers...', plugins: { remove_button: { title: 'Remove' } } }"
                >
                    <option v-for="carrier in carriers" :key="carrier.id" :value="String(carrier.id)">
                        {{ carrier.name }}{{ carrier.email ? ` (${carrier.email})` : '' }}
                    </option>
                </TomSelect>
                <p v-if="mode === 'create' && form.errors.carrier_ids" class="mt-1 text-xs text-danger">{{ form.errors.carrier_ids }}</p>
                <p v-if="mode === 'edit' && form.errors.add_carrier_ids" class="mt-1 text-xs text-danger">{{ form.errors.add_carrier_ids }}</p>
            </div>

            <div v-if="selectedType === 'custom_emails'" class="mt-6">
                <FormLabel>{{ mode === 'create' ? 'Custom Email Addresses' : 'Extra Email Addresses' }}</FormLabel>
                <FormTextarea
                    v-model="customEmailsModel"
                    rows="4"
                    placeholder="email1@example.com, email2@example.com"
                />
                <FormHelp>Separate multiple emails with commas, spaces, or new lines.</FormHelp>
                <p v-if="mode === 'create' && form.errors.custom_emails" class="mt-1 text-xs text-danger">{{ form.errors.custom_emails }}</p>
                <p v-if="mode === 'edit' && form.errors.add_custom_emails" class="mt-1 text-xs text-danger">{{ form.errors.add_custom_emails }}</p>
            </div>
        </div>
    </div>
</template>
