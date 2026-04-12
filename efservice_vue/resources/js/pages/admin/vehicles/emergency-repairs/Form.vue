<script setup lang="ts">
import { Link, router, useForm } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'
import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import Litepicker from '@/components/Base/Litepicker/Litepicker.vue'
import TomSelect from '@/components/Base/TomSelect/TomSelect.vue'
import { FormInput, FormTextarea } from '@/components/Base/Form'

declare function route(name: string, params?: any): string

const pickerOptions = { singleMode: true, format: 'M/D/YYYY', autoApply: true }

interface CarrierOption {
    id: number
    name: string
}

interface VehicleOption {
    id: number
    carrier_id: number | null
    carrier_name: string | null
    label: string
}

interface ExistingAttachment {
    id: number
    name: string
    url: string
    size: string
    mime_type: string | null
}

interface ContextVehicle {
    id: number
    carrier_id: number | null
    carrier_name: string | null
    label: string
}

interface RepairPayload {
    id: number
    vehicle_id: string
    carrier_id: string
    repair_name: string
    repair_date: string
    cost: string
    odometer: string
    status: string
    description: string | null
    notes: string | null
    attachments: ExistingAttachment[]
}

const props = defineProps<{
    mode: 'create' | 'edit'
    repair?: RepairPayload | null
    carriers: CarrierOption[]
    vehicles: VehicleOption[]
    drivers: { id: number; carrier_id: number | null; name: string; email: string | null }[]
    statusOptions: Record<string, string>
    selectedCarrierId?: number | null
    contextVehicle?: ContextVehicle | null
    isSuperadmin: boolean
    routeNames?: Partial<{
        index: string
        store: string
        update: string
        attachmentsDestroy: string
        vehicleIndex: string
    }>
}>()

const defaultRouteNames = {
    index: 'admin.vehicles.emergency-repairs.index',
    store: 'admin.vehicles.emergency-repairs.store',
    update: 'admin.vehicles.emergency-repairs.update',
    attachmentsDestroy: 'admin.vehicles.emergency-repairs.attachments.destroy',
    vehicleIndex: 'admin.vehicles.repairs.index',
} as const

function namedRoute(name: keyof typeof defaultRouteNames, params?: any) {
    return route(props.routeNames?.[name] ?? defaultRouteNames[name], params)
}

const lockedVehicle = computed(() => props.contextVehicle ?? null)
const selectedCarrierId = ref(
    lockedVehicle.value?.carrier_id
        ? String(lockedVehicle.value.carrier_id)
        : props.repair?.carrier_id || (props.selectedCarrierId ? String(props.selectedCarrierId) : ''),
)
const existingAttachments = ref<ExistingAttachment[]>([...(props.repair?.attachments ?? [])])

const form = useForm({
    carrier_id: selectedCarrierId.value,
    vehicle_id: lockedVehicle.value ? String(lockedVehicle.value.id) : (props.repair?.vehicle_id ?? ''),
    repair_name: props.repair?.repair_name ?? '',
    repair_date: props.repair?.repair_date ?? '',
    cost: props.repair?.cost ?? '',
    odometer: props.repair?.odometer ?? '',
    status: props.repair?.status ?? 'pending',
    description: props.repair?.description ?? '',
    notes: props.repair?.notes ?? '',
    attachments: [] as File[],
})

const filteredVehicles = computed(() => {
    if (lockedVehicle.value) {
        return props.vehicles.filter(vehicle => vehicle.id === lockedVehicle.value?.id)
    }

    if (!selectedCarrierId.value) {
        return props.vehicles
    }

    return props.vehicles.filter(vehicle => String(vehicle.carrier_id ?? '') === selectedCarrierId.value)
})

const backHref = computed(() => {
    if (lockedVehicle.value) {
        return namedRoute('vehicleIndex', lockedVehicle.value.id)
    }

    return namedRoute('index')
})

watch(selectedCarrierId, value => {
    form.carrier_id = value

    if (lockedVehicle.value) {
        form.vehicle_id = String(lockedVehicle.value.id)
        return
    }

    if (!value) {
        return
    }

    const stillAvailable = filteredVehicles.value.some(vehicle => String(vehicle.id) === form.vehicle_id)
    if (!stillAvailable) {
        form.vehicle_id = ''
    }
})

function onFileChange(event: Event) {
    const input = event.target as HTMLInputElement
    form.attachments = Array.from(input.files ?? [])
}

function submit() {
    if (props.mode === 'edit' && props.repair) {
        form.transform(data => ({ ...data, _method: 'put' }))
            .post(namedRoute('update', props.repair.id), { forceFormData: true })
        return
    }

    form.post(namedRoute('store'), { forceFormData: true })
}

function deleteAttachment(id: number) {
    if (!props.repair) return

    router.delete(namedRoute('attachmentsDestroy', {
        emergencyRepair: props.repair.id,
        media: id,
    }), {
        preserveScroll: true,
        onSuccess: () => {
            existingAttachments.value = existingAttachments.value.filter(file => file.id !== id)
        },
    })
}
</script>

<template>
    <div class="grid grid-cols-12 gap-y-8 gap-x-6">
        <div class="col-span-12">
            <div class="box box--stacked p-6">
                <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                            <Lucide :icon="mode === 'edit' ? 'PenLine' : 'Siren'" class="w-8 h-8 text-primary" />
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-slate-800">{{ mode === 'edit' ? 'Edit Emergency Repair' : 'New Emergency Repair' }}</h1>
                            <p class="text-slate-500">
                                {{ mode === 'edit' ? 'Update repair details, status and supporting files.' : 'Log a repair event for a vehicle and keep the backup files together.' }}
                            </p>
                            <p v-if="lockedVehicle" class="text-xs text-primary mt-2">{{ lockedVehicle.label }}</p>
                        </div>
                    </div>

                    <Link :href="backHref">
                        <Button variant="outline-secondary" class="flex items-center gap-2">
                            <Lucide icon="ArrowLeft" class="w-4 h-4" />
                            Back
                        </Button>
                    </Link>
                </div>
            </div>
        </div>

        <div class="col-span-12 xl:col-span-8">
            <form @submit.prevent="submit" class="space-y-6">
                <div class="box box--stacked p-6">
                    <h2 class="text-sm font-semibold text-slate-700 mb-5 flex items-center gap-2">
                        <Lucide icon="Truck" class="w-4 h-4 text-primary" />
                        Vehicle Assignment
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Carrier</label>
                            <TomSelect v-model="selectedCarrierId" :disabled="!!lockedVehicle || !isSuperadmin">
                                <option value="">Select carrier</option>
                                <option v-for="carrier in carriers" :key="carrier.id" :value="String(carrier.id)">{{ carrier.name }}</option>
                            </TomSelect>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Vehicle <span class="text-red-500">*</span></label>
                            <TomSelect v-model="form.vehicle_id" :disabled="!!lockedVehicle">
                                <option value="">Select vehicle</option>
                                <option v-for="vehicle in filteredVehicles" :key="vehicle.id" :value="String(vehicle.id)">{{ vehicle.label }}</option>
                            </TomSelect>
                            <p v-if="form.errors.vehicle_id" class="text-red-500 text-xs mt-1">{{ form.errors.vehicle_id }}</p>
                        </div>
                    </div>
                </div>

                <div class="box box--stacked p-6">
                    <h2 class="text-sm font-semibold text-slate-700 mb-5 flex items-center gap-2">
                        <Lucide icon="Wrench" class="w-4 h-4 text-primary" />
                        Repair Details
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Repair Name <span class="text-red-500">*</span></label>
                            <FormInput v-model="form.repair_name" type="text" placeholder="Flat tire, brake repair, tow service..." />
                            <p v-if="form.errors.repair_name" class="text-red-500 text-xs mt-1">{{ form.errors.repair_name }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Repair Date <span class="text-red-500">*</span></label>
                            <Litepicker v-model="form.repair_date" :options="pickerOptions" />
                            <p v-if="form.errors.repair_date" class="text-red-500 text-xs mt-1">{{ form.errors.repair_date }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Cost <span class="text-red-500">*</span></label>
                            <FormInput v-model="form.cost" type="number" step="0.01" min="0" placeholder="0.00" />
                            <p v-if="form.errors.cost" class="text-red-500 text-xs mt-1">{{ form.errors.cost }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Odometer</label>
                            <FormInput v-model="form.odometer" type="number" min="0" placeholder="Mileage at the time of repair" />
                            <p v-if="form.errors.odometer" class="text-red-500 text-xs mt-1">{{ form.errors.odometer }}</p>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Status <span class="text-red-500">*</span></label>
                            <TomSelect v-model="form.status">
                                <option v-for="(label, key) in statusOptions" :key="key" :value="key">{{ label }}</option>
                            </TomSelect>
                            <p v-if="form.errors.status" class="text-red-500 text-xs mt-1">{{ form.errors.status }}</p>
                        </div>
                    </div>
                </div>

                <div class="box box--stacked p-6">
                    <h2 class="text-sm font-semibold text-slate-700 mb-5 flex items-center gap-2">
                        <Lucide icon="StickyNote" class="w-4 h-4 text-primary" />
                        Description & Notes
                    </h2>

                    <div class="grid grid-cols-1 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Description</label>
                            <FormTextarea v-model="form.description" rows="4" placeholder="What happened, what was repaired, and what parts or services were involved..." />
                            <p v-if="form.errors.description" class="text-red-500 text-xs mt-1">{{ form.errors.description }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Internal Notes</label>
                            <FormTextarea v-model="form.notes" rows="4" placeholder="Optional admin notes, follow-up items or claim information..." />
                            <p v-if="form.errors.notes" class="text-red-500 text-xs mt-1">{{ form.errors.notes }}</p>
                        </div>
                    </div>
                </div>

                <div class="box box--stacked p-6">
                    <h2 class="text-sm font-semibold text-slate-700 mb-5 flex items-center gap-2">
                        <Lucide icon="Paperclip" class="w-4 h-4 text-primary" />
                        Attachments
                    </h2>

                    <input type="file" multiple accept=".jpg,.jpeg,.png,.pdf,.doc,.docx" class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2" @change="onFileChange" />
                    <p class="text-xs text-slate-500 mt-1">Accepted: PDF, images, DOC and DOCX up to 10 MB each.</p>
                    <p v-if="form.errors.attachments" class="text-red-500 text-xs mt-1">{{ form.errors.attachments }}</p>
                    <p v-if="form.errors['attachments.0']" class="text-red-500 text-xs mt-1">{{ form.errors['attachments.0'] }}</p>
                </div>

                <div class="flex justify-end gap-3">
                    <Link :href="backHref">
                        <Button variant="outline-secondary" type="button">Cancel</Button>
                    </Link>
                    <Button type="submit" variant="primary" :disabled="form.processing">
                        {{ form.processing ? 'Saving...' : mode === 'edit' ? 'Update Repair' : 'Create Repair' }}
                    </Button>
                </div>
            </form>
        </div>

        <div class="col-span-12 xl:col-span-4">
            <div class="box box--stacked p-5 sticky top-4">
                <h2 class="text-sm font-semibold text-slate-700 mb-4 flex items-center gap-2">
                    <Lucide icon="Files" class="w-4 h-4 text-primary" />
                    Current Attachments
                </h2>

                <div class="space-y-3">
                    <div v-for="file in existingAttachments" :key="file.id" class="rounded-lg border border-slate-200 px-4 py-3">
                        <div class="flex items-start justify-between gap-3">
                            <a :href="file.url" target="_blank" class="min-w-0 flex-1">
                                <p class="text-sm font-medium text-primary truncate">{{ file.name }}</p>
                                <p class="text-xs text-slate-500 mt-1">{{ file.size }}</p>
                            </a>
                            <button type="button" class="text-slate-400 hover:text-danger transition" @click="deleteAttachment(file.id)">
                                <Lucide icon="Trash2" class="w-4 h-4" />
                            </button>
                        </div>
                    </div>

                    <div v-if="!existingAttachments.length" class="rounded-lg bg-slate-50 px-4 py-3 text-sm text-slate-500">
                        No attachments uploaded yet.
                    </div>

                    <div class="rounded-lg border border-dashed border-primary/20 bg-primary/5 px-4 py-3 text-xs text-slate-600">
                        After saving, the detail screen lets you generate the repair PDF and manage files from one place.
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
