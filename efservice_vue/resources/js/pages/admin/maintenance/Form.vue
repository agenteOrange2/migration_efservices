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

interface CarrierOption { id: number; name: string }
interface VehicleOption {
    id: number
    carrier_id: number | null
    carrier_name: string | null
    label: string
}
interface ContextVehicle {
    id: number
    carrier_id: number | null
    carrier_name: string | null
    label: string
    company_unit_number: string | null
}
interface ExistingAttachment {
    id: number
    name: string
    url: string
    size: string
    mime_type: string | null
}
interface MaintenancePayload {
    id: number
    vehicle_id: string
    carrier_id: string
    unit: string
    service_tasks: string
    service_date: string
    next_service_date: string
    vendor_mechanic: string
    cost: string
    odometer: string
    description: string | null
    notes: string | null
    status: boolean
    is_historical: boolean
    attachments: ExistingAttachment[]
}

const props = defineProps<{
    mode: 'create' | 'edit'
    maintenance?: MaintenancePayload | null
    carriers: CarrierOption[]
    vehicles: VehicleOption[]
    maintenanceTypes: string[]
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
    index: 'admin.maintenance.index',
    store: 'admin.maintenance.store',
    update: 'admin.maintenance.update',
    attachmentsDestroy: 'admin.maintenance.attachments.destroy',
    vehicleIndex: 'admin.vehicles.maintenance.index',
} as const

function namedRoute(name: keyof typeof defaultRouteNames, params?: any) {
    return route(props.routeNames?.[name] ?? defaultRouteNames[name], params)
}

const lockedVehicle = computed(() => props.contextVehicle ?? null)
const selectedCarrierId = ref(
    lockedVehicle.value?.carrier_id
        ? String(lockedVehicle.value.carrier_id)
        : props.maintenance?.carrier_id
            || (props.selectedCarrierId ? String(props.selectedCarrierId) : ''),
)

const existingAttachments = ref<ExistingAttachment[]>([...(props.maintenance?.attachments ?? [])])

const form = useForm({
    carrier_id: selectedCarrierId.value,
    vehicle_id: lockedVehicle.value ? String(lockedVehicle.value.id) : (props.maintenance?.vehicle_id ?? ''),
    unit: props.maintenance?.unit ?? (lockedVehicle.value?.company_unit_number ?? ''),
    service_tasks: props.maintenance?.service_tasks ?? '',
    service_date: props.maintenance?.service_date ?? '',
    next_service_date: props.maintenance?.next_service_date ?? '',
    vendor_mechanic: props.maintenance?.vendor_mechanic ?? '',
    cost: props.maintenance?.cost ?? '',
    odometer: props.maintenance?.odometer ?? '',
    description: props.maintenance?.description ?? '',
    notes: props.maintenance?.notes ?? '',
    status: props.maintenance?.status ?? false,
    is_historical: props.maintenance?.is_historical ?? false,
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

watch(() => form.vehicle_id, value => {
    if (form.unit) return

    const selectedVehicle = filteredVehicles.value.find(vehicle => String(vehicle.id) === value)
    if (!selectedVehicle) return

    const unitMatch = selectedVehicle.label.match(/Unit\s+([^-]+)/i)
    if (unitMatch?.[1]) {
        form.unit = unitMatch[1].trim()
    }
})

function onFileChange(event: Event) {
    const input = event.target as HTMLInputElement
    form.attachments = Array.from(input.files ?? [])
}

function submit() {
    if (props.mode === 'edit' && props.maintenance) {
        form.transform(data => ({ ...data, _method: 'put' }))
            .post(namedRoute('update', props.maintenance.id), { forceFormData: true })
        return
    }

    form.post(namedRoute('store'), { forceFormData: true })
}

function deleteAttachment(id: number) {
    if (!props.maintenance) return

    router.delete(namedRoute('attachmentsDestroy', { maintenance: props.maintenance.id, media: id }), {
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
                            <Lucide :icon="mode === 'edit' ? 'PenLine' : 'Wrench'" class="w-8 h-8 text-primary" />
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-slate-800">{{ mode === 'edit' ? 'Edit Maintenance Record' : 'New Maintenance Record' }}</h1>
                            <p class="text-slate-500">
                                {{ mode === 'edit' ? 'Update the maintenance schedule, attachments and status.' : 'Create a maintenance record using the Vue admin flow.' }}
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
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Vehicle <span class="text-danger">*</span></label>
                            <TomSelect v-model="form.vehicle_id" :disabled="!!lockedVehicle">
                                <option value="">Select vehicle</option>
                                <option v-for="vehicle in filteredVehicles" :key="vehicle.id" :value="String(vehicle.id)">{{ vehicle.label }}</option>
                            </TomSelect>
                            <p v-if="form.errors.vehicle_id" class="text-danger text-xs mt-1">{{ form.errors.vehicle_id }}</p>
                        </div>
                    </div>
                </div>

                <div class="box box--stacked p-6">
                    <h2 class="text-sm font-semibold text-slate-700 mb-5 flex items-center gap-2">
                        <Lucide icon="ClipboardList" class="w-4 h-4 text-primary" />
                        Maintenance Details
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Unit <span class="text-danger">*</span></label>
                            <FormInput v-model="form.unit" type="text" placeholder="Unit number or fleet ID" />
                            <p v-if="form.errors.unit" class="text-danger text-xs mt-1">{{ form.errors.unit }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Maintenance Type <span class="text-danger">*</span></label>
                            <TomSelect v-model="form.service_tasks">
                                <option value="">Select maintenance type</option>
                                <option v-for="type in maintenanceTypes" :key="type" :value="type">{{ type }}</option>
                            </TomSelect>
                            <p v-if="form.errors.service_tasks" class="text-danger text-xs mt-1">{{ form.errors.service_tasks }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Service Date <span class="text-danger">*</span></label>
                            <Litepicker v-model="form.service_date" :options="pickerOptions" />
                            <p v-if="form.errors.service_date" class="text-danger text-xs mt-1">{{ form.errors.service_date }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Next Service Date</label>
                            <Litepicker v-model="form.next_service_date" :options="pickerOptions" />
                            <p class="text-xs text-slate-500 mt-1">If left blank, we default it to 3 months after the service date.</p>
                            <p v-if="form.errors.next_service_date" class="text-danger text-xs mt-1">{{ form.errors.next_service_date }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Vendor / Mechanic <span class="text-danger">*</span></label>
                            <FormInput v-model="form.vendor_mechanic" type="text" placeholder="Shop, vendor or mechanic name" />
                            <p v-if="form.errors.vendor_mechanic" class="text-danger text-xs mt-1">{{ form.errors.vendor_mechanic }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Cost <span class="text-danger">*</span></label>
                            <FormInput v-model="form.cost" type="number" step="0.01" min="0" placeholder="0.00" />
                            <p v-if="form.errors.cost" class="text-danger text-xs mt-1">{{ form.errors.cost }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Odometer</label>
                            <FormInput v-model="form.odometer" type="number" min="0" placeholder="Mileage at service time" />
                            <p v-if="form.errors.odometer" class="text-danger text-xs mt-1">{{ form.errors.odometer }}</p>
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
                            <FormTextarea v-model="form.description" rows="4" placeholder="Work completed, parts replaced or service summary..." />
                            <p v-if="form.errors.description" class="text-danger text-xs mt-1">{{ form.errors.description }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Internal Notes</label>
                            <FormTextarea v-model="form.notes" rows="4" placeholder="Optional notes for follow-up, scheduling or admin remarks..." />
                            <p v-if="form.errors.notes" class="text-danger text-xs mt-1">{{ form.errors.notes }}</p>
                        </div>
                    </div>
                </div>

                <div class="box box--stacked p-6">
                    <h2 class="text-sm font-semibold text-slate-700 mb-5 flex items-center gap-2">
                        <Lucide icon="Paperclip" class="w-4 h-4 text-primary" />
                        Attachments & Status
                    </h2>

                    <div class="grid grid-cols-1 gap-4">
                        <div>
                            <input type="file" multiple accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.xls,.xlsx" class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2" @change="onFileChange" />
                            <p class="text-xs text-slate-500 mt-1">Accepted: PDF, images, DOC, DOCX, XLS, XLSX.</p>
                            <p v-if="form.errors.attachments" class="text-danger text-xs mt-1">{{ form.errors.attachments }}</p>
                            <p v-if="form.errors['attachments.0']" class="text-danger text-xs mt-1">{{ form.errors['attachments.0'] }}</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <label class="flex items-center gap-3 rounded-xl border border-slate-200 px-4 py-3 text-sm text-slate-700">
                                <input v-model="form.status" type="checkbox" class="w-4 h-4 rounded text-primary" />
                                Mark as completed
                            </label>

                            <label class="flex items-center gap-3 rounded-xl border border-slate-200 px-4 py-3 text-sm text-slate-700">
                                <input v-model="form.is_historical" type="checkbox" class="w-4 h-4 rounded text-primary" />
                                Historical record
                            </label>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-3">
                    <Link :href="backHref">
                        <Button variant="outline-secondary" type="button">Cancel</Button>
                    </Link>
                    <Button type="submit" variant="primary" :disabled="form.processing">
                        {{ form.processing ? 'Saving...' : mode === 'edit' ? 'Update Maintenance' : 'Create Maintenance' }}
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
                        For scheduling changes, quick status toggles and report generation, use the detail screen after saving the record.
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
