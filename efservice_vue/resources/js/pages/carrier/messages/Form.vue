<script setup lang="ts">
import { computed } from 'vue'
import Lucide from '@/components/Base/Lucide'
import TomSelect from '@/components/Base/TomSelect/TomSelect.vue'
import { FormHelp, FormInput, FormLabel, FormSelect, FormTextarea } from '@/components/Base/Form'

interface DriverOption {
    id: number
    name: string
    email: string
}

interface AdminContact {
    id: number
    name: string
    email: string
}

const props = defineProps<{
    mode: 'create' | 'edit'
    form: any
    drivers: DriverOption[]
    adminContact: AdminContact | null
}>()

const selectedType = computed(() => props.mode === 'create' ? props.form.recipient_type : props.form.add_recipient_type)
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
const selectedDriverCount = computed(() => Array.isArray(driverIdsModel.value) ? driverIdsModel.value.length : 0)
const totalDriverCount = computed(() => props.drivers.length)
const selectedAdminCount = computed(() => selectedType.value === 'admin' && props.adminContact ? 1 : 0)
const messageLength = computed(() => String(props.form.message ?? '').length)

function selectRecipientType(value: 'all_my_drivers' | 'specific_drivers' | 'admin') {
    if (props.mode === 'create') {
        props.form.recipient_type = value
        return
    }

    props.form.add_recipient_type = value
}
</script>

<template>
    <div class="space-y-6">
        <div class="box box--stacked p-6">
            <div class="mb-5 flex items-center gap-3 border-b border-slate-200/70 pb-4">
                <Lucide icon="Mail" class="h-5 w-5 text-primary" />
                <div>
                    <h2 class="text-lg font-semibold text-slate-800">Message Details</h2>
                    <p class="text-sm text-slate-500">Write a clear subject and message, then choose whether to save it as draft or send it now.</p>
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
                    <FormTextarea v-model="form.message" rows="8" placeholder="Write the message you want to send..." />
                    <div class="mt-1 flex items-center justify-between text-xs text-slate-500">
                        <span>Keep the content short, direct, and action-oriented.</span>
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
                            {{ mode === 'create' ? 'Drafts let you review recipients before delivery.' : 'Switch to Send Now when this draft is ready to go out.' }}
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
                        {{ mode === 'create' ? 'Choose who should receive this message from your carrier account.' : 'Append more recipients to this draft if needed.' }}
                    </p>
                </div>
            </div>

            <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3">
                <div class="rounded-2xl border border-dashed border-slate-300/80 bg-slate-50/80 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Active Drivers</p>
                    <p class="mt-2 text-2xl font-semibold text-slate-800">{{ totalDriverCount }}</p>
                    <p class="mt-1 text-xs text-slate-500">Available to message from this carrier.</p>
                </div>
                <div class="rounded-2xl border border-dashed border-slate-300/80 bg-slate-50/80 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Selected Drivers</p>
                    <p class="mt-2 text-2xl font-semibold text-slate-800">{{ selectedDriverCount }}</p>
                    <p class="mt-1 text-xs text-slate-500">Only used when sending to specific drivers.</p>
                </div>
                <div class="rounded-2xl border border-dashed border-slate-300/80 bg-slate-50/80 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Admin Recipient</p>
                    <p class="mt-2 text-2xl font-semibold text-slate-800">{{ selectedAdminCount }}</p>
                    <p class="mt-1 text-xs text-slate-500">Platform admin contact available for escalations.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <button
                    type="button"
                    class="rounded-2xl border p-4 text-left transition"
                    :class="selectedType === 'all_my_drivers' ? 'border-primary bg-primary/5' : 'border-slate-200 hover:border-primary/30'"
                    @click="selectRecipientType('all_my_drivers')"
                >
                    <div class="flex items-start gap-3">
                        <div class="rounded-xl bg-primary/10 p-2">
                            <Lucide icon="Users" class="h-5 w-5 text-primary" />
                        </div>
                        <div>
                            <p class="font-semibold text-slate-800">All My Drivers</p>
                            <p class="mt-1 text-sm text-slate-500">Send to every active driver assigned to this carrier.</p>
                        </div>
                    </div>
                </button>

                <button
                    type="button"
                    class="rounded-2xl border p-4 text-left transition"
                    :class="selectedType === 'specific_drivers' ? 'border-primary bg-primary/5' : 'border-slate-200 hover:border-primary/30'"
                    @click="selectRecipientType('specific_drivers')"
                >
                    <div class="flex items-start gap-3">
                        <div class="rounded-xl bg-primary/10 p-2">
                            <Lucide icon="UserCheck" class="h-5 w-5 text-primary" />
                        </div>
                        <div>
                            <p class="font-semibold text-slate-800">Specific Drivers</p>
                            <p class="mt-1 text-sm text-slate-500">Pick one or more individual drivers from your roster.</p>
                        </div>
                    </div>
                </button>

                <button
                    type="button"
                    class="rounded-2xl border p-4 text-left transition"
                    :class="selectedType === 'admin' ? 'border-primary bg-primary/5' : 'border-slate-200 hover:border-primary/30'"
                    @click="selectRecipientType('admin')"
                >
                    <div class="flex items-start gap-3">
                        <div class="rounded-xl bg-primary/10 p-2">
                            <Lucide icon="ShieldCheck" class="h-5 w-5 text-primary" />
                        </div>
                        <div>
                            <p class="font-semibold text-slate-800">Platform Admin</p>
                            <p class="mt-1 text-sm text-slate-500">Send directly to the admin contact for support or escalation.</p>
                        </div>
                    </div>
                </button>
            </div>

            <p
                v-if="mode === 'create' && form.errors.recipient_type"
                class="mt-3 text-xs text-danger"
            >
                {{ form.errors.recipient_type }}
            </p>
            <p
                v-if="mode === 'edit' && form.errors.add_recipient_type"
                class="mt-3 text-xs text-danger"
            >
                {{ form.errors.add_recipient_type }}
            </p>

            <div v-if="selectedType === 'all_my_drivers'" class="mt-6 rounded-2xl border border-primary/15 bg-primary/5 p-5">
                <div class="flex items-start gap-3">
                    <div class="rounded-xl bg-white p-2 shadow-sm">
                        <Lucide icon="Users" class="h-5 w-5 text-primary" />
                    </div>
                    <div>
                        <p class="font-semibold text-slate-800">Broadcast to all active drivers</p>
                        <p class="mt-1 text-sm text-slate-600">
                            This will target {{ totalDriverCount }} active driver<span v-if="totalDriverCount !== 1">s</span> currently assigned to your company.
                        </p>
                    </div>
                </div>
            </div>

            <div v-if="selectedType === 'specific_drivers'" class="mt-6">
                <FormLabel>{{ mode === 'create' ? 'Driver Selection' : 'Drivers to Add' }}</FormLabel>
                <TomSelect
                    v-model="driverIdsModel"
                    multiple
                    :options="{ placeholder: 'Select drivers...', plugins: { remove_button: { title: 'Remove' } } }"
                >
                    <option v-for="driver in drivers" :key="driver.id" :value="String(driver.id)">
                        {{ driver.name }}{{ driver.email ? ` (${driver.email})` : '' }}
                    </option>
                </TomSelect>
                <p v-if="mode === 'create' && form.errors.driver_ids" class="mt-1 text-xs text-danger">{{ form.errors.driver_ids }}</p>
                <p v-if="mode === 'edit' && form.errors.add_driver_ids" class="mt-1 text-xs text-danger">{{ form.errors.add_driver_ids }}</p>
            </div>

            <div v-if="selectedType === 'admin'" class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 p-5">
                <div class="flex items-start gap-3">
                    <div class="rounded-xl bg-white p-2 shadow-sm">
                        <Lucide icon="MailCheck" class="h-5 w-5 text-primary" />
                    </div>
                    <div class="min-w-0">
                        <p class="font-semibold text-slate-800">Admin recipient</p>
                        <p v-if="adminContact" class="mt-1 text-sm text-slate-600">
                            {{ adminContact.name }}<span v-if="adminContact.email"> · {{ adminContact.email }}</span>
                        </p>
                        <p v-else class="mt-1 text-sm text-danger">
                            No admin contact is configured right now.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
