<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'
import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import Litepicker from '@/components/Base/Litepicker/Litepicker.vue'
import TomSelect from '@/components/Base/TomSelect/TomSelect.vue'
import { FormInput, FormTextarea } from '@/components/Base/Form'
import { Dialog } from '@/components/Base/Headless'

declare function route(name: string, params?: any): string

const lpOptions = { singleMode: true, format: 'M/D/YYYY', autoApply: true }

interface CarrierOption { id: number; name: string }
interface DriverOption { id: number; carrier_id: number | null; carrier_name: string | null; name: string; email: string | null }
interface VehicleOption { id: number; carrier_id: number | null; label: string }
interface DocumentPayload { id: number; name: string; url: string; size: string; mime_type: string | null }

interface InspectionPayload {
    id: number
    carrier_id: number | null
    user_driver_detail_id: number
    vehicle_id: number | null
    driver_name: string
    inspection_date: string | null
    inspection_type: string | null
    inspection_level: string | null
    inspector_name: string | null
    inspector_number: string | null
    location: string | null
    status: string | null
    defects_found: string | null
    corrective_actions: string | null
    is_defects_corrected: boolean
    defects_corrected_date: string | null
    corrected_by: string | null
    is_vehicle_safe_to_operate: boolean
    notes: string | null
    documents: DocumentPayload[]
}

const props = defineProps<{
    mode: 'create' | 'edit'
    carriers: CarrierOption[]
    drivers: DriverOption[]
    vehicles: VehicleOption[]
    inspectionTypes: string[]
    inspectionLevels: string[]
    statuses: string[]
    carrier?: CarrierOption | null
    isCarrierContext?: boolean
    routeNames?: Record<string, string>
    selectedDriverId?: string
    inspection?: InspectionPayload
}>()

const defaultRouteNames = {
    index: 'admin.inspections.index',
    store: 'admin.inspections.store',
    update: 'admin.inspections.update',
}

function routeName(key: keyof typeof defaultRouteNames) {
    return props.routeNames?.[key] ?? defaultRouteNames[key]
}

const selectedCarrierId = ref(
    props.isCarrierContext
        ? String(props.carrier?.id ?? props.inspection?.carrier_id ?? '')
        : (props.inspection?.carrier_id ? String(props.inspection.carrier_id) : '')
)
const previewOpen = ref(false)
const previewFile = ref<DocumentPayload | null>(null)

const form = useForm({
    carrier_id: selectedCarrierId.value,
    user_driver_detail_id: props.inspection ? String(props.inspection.user_driver_detail_id) : (props.selectedDriverId ?? ''),
    vehicle_id: props.inspection?.vehicle_id ? String(props.inspection.vehicle_id) : '',
    inspection_date: props.inspection?.inspection_date ?? '',
    inspection_type: props.inspection?.inspection_type ?? '',
    inspection_level: props.inspection?.inspection_level ?? '',
    inspector_name: props.inspection?.inspector_name ?? '',
    inspector_number: props.inspection?.inspector_number ?? '',
    location: props.inspection?.location ?? '',
    status: props.inspection?.status ?? '',
    defects_found: props.inspection?.defects_found ?? '',
    corrective_actions: props.inspection?.corrective_actions ?? '',
    is_defects_corrected: props.inspection?.is_defects_corrected ?? false,
    defects_corrected_date: props.inspection?.defects_corrected_date ?? '',
    corrected_by: props.inspection?.corrected_by ?? '',
    is_vehicle_safe_to_operate: props.inspection?.is_vehicle_safe_to_operate ?? true,
    notes: props.inspection?.notes ?? '',
    attachments: [] as File[],
})

const filteredDrivers = computed(() => {
    if (!selectedCarrierId.value) return []
    return props.drivers.filter(driver => String(driver.carrier_id ?? '') === selectedCarrierId.value)
})

const filteredVehicles = computed(() => {
    if (!selectedCarrierId.value) return []
    return props.vehicles.filter(vehicle => String(vehicle.carrier_id ?? '') === selectedCarrierId.value)
})

watch(selectedCarrierId, value => {
    form.carrier_id = value
    if (props.isCarrierContext) {
        return
    }
    if (props.inspection && String(props.inspection.carrier_id ?? '') === value) {
        form.user_driver_detail_id = String(props.inspection.user_driver_detail_id)
        form.vehicle_id = props.inspection.vehicle_id ? String(props.inspection.vehicle_id) : ''
        return
    }
    form.user_driver_detail_id = ''
    form.vehicle_id = ''
})

watch(() => form.is_defects_corrected, value => {
    if (!value) {
        form.defects_corrected_date = ''
        form.corrected_by = ''
    }
})

function onFileChange(event: Event) {
    const input = event.target as HTMLInputElement
    form.attachments = Array.from(input.files ?? [])
}

function openPreview(file: DocumentPayload) {
    previewFile.value = file
    previewOpen.value = true
}

function isPdf(file: DocumentPayload | null) {
    return !!file?.mime_type?.includes('pdf')
}

function submit() {
    if (props.mode === 'edit' && props.inspection) {
        form.transform(data => ({ ...data, _method: 'put' }))
            .post(route(routeName('update'), props.inspection.id), { forceFormData: true })
        return
    }
    form.post(route(routeName('store')), { forceFormData: true })
}
</script>

<template>
    <div class="grid grid-cols-12 gap-y-8 gap-x-6">
        <div class="col-span-12">
            <div class="box box--stacked p-6">
                <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                            <Lucide :icon="mode === 'edit' ? 'PenLine' : 'FileCheck'" class="w-8 h-8 text-primary" />
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-slate-800">{{ mode === 'edit' ? 'Edit Inspection' : 'Add Inspection' }}</h1>
                            <p class="text-slate-500">{{ mode === 'edit' ? `Update inspection for ${inspection?.driver_name ?? 'driver'}.` : 'Create an inspection record in the Vue admin.' }}</p>
                        </div>
                    </div>

                    <Link :href="route(routeName('index'))">
                        <Button variant="outline-secondary" class="flex items-center gap-2">
                            <Lucide icon="ArrowLeft" class="w-4 h-4" />
                            Back to Inspections
                        </Button>
                    </Link>
                </div>
            </div>
        </div>

        <div class="col-span-12 xl:col-span-8">
            <form @submit.prevent="submit" class="space-y-6">
                <div class="box box--stacked p-6">
                    <h2 class="text-sm font-semibold text-slate-700 mb-5 flex items-center gap-2"><Lucide icon="Users" class="w-4 h-4 text-primary" /> Assignment</h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div v-if="!props.isCarrierContext">
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Carrier <span class="text-danger">*</span></label>
                            <TomSelect v-model="selectedCarrierId">
                                <option value="">Select carrier</option>
                                <option v-for="carrier in carriers" :key="carrier.id" :value="String(carrier.id)">{{ carrier.name }}</option>
                            </TomSelect>
                            <p v-if="form.errors.carrier_id" class="text-danger text-xs mt-1">{{ form.errors.carrier_id }}</p>
                        </div>
                        <div v-else class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <label class="block text-xs font-medium text-slate-500 mb-1.5">Carrier</label>
                            <div class="text-sm font-medium text-slate-700">{{ props.carrier?.name ?? 'Current Carrier' }}</div>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Driver <span class="text-danger">*</span></label>
                            <TomSelect v-model="form.user_driver_detail_id">
                                <option value="">Select driver</option>
                                <option v-for="driver in filteredDrivers" :key="driver.id" :value="String(driver.id)">{{ driver.name }}</option>
                            </TomSelect>
                            <p v-if="form.errors.user_driver_detail_id" class="text-danger text-xs mt-1">{{ form.errors.user_driver_detail_id }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Vehicle</label>
                            <TomSelect v-model="form.vehicle_id">
                                <option value="">Select vehicle</option>
                                <option v-for="vehicle in filteredVehicles" :key="vehicle.id" :value="String(vehicle.id)">{{ vehicle.label }}</option>
                            </TomSelect>
                            <p v-if="form.errors.vehicle_id" class="text-danger text-xs mt-1">{{ form.errors.vehicle_id }}</p>
                        </div>
                    </div>
                </div>

                <div class="box box--stacked p-6">
                    <h2 class="text-sm font-semibold text-slate-700 mb-5 flex items-center gap-2"><Lucide icon="ClipboardCheck" class="w-4 h-4 text-primary" /> Inspection Details</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Inspection Date <span class="text-danger">*</span></label>
                            <Litepicker v-model="form.inspection_date" :options="lpOptions" />
                            <p v-if="form.errors.inspection_date" class="text-danger text-xs mt-1">{{ form.errors.inspection_date }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Inspection Type <span class="text-danger">*</span></label>
                            <TomSelect v-model="form.inspection_type">
                                <option value="">Select type</option>
                                <option v-for="type in inspectionTypes" :key="type" :value="type">{{ type }}</option>
                            </TomSelect>
                            <p v-if="form.errors.inspection_type" class="text-danger text-xs mt-1">{{ form.errors.inspection_type }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Inspection Level</label>
                            <TomSelect v-model="form.inspection_level">
                                <option value="">Select level</option>
                                <option v-for="level in inspectionLevels" :key="level" :value="level">{{ level }}</option>
                            </TomSelect>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Status</label>
                            <TomSelect v-model="form.status">
                                <option value="">Select status</option>
                                <option v-for="status in statuses" :key="status" :value="status">{{ status }}</option>
                            </TomSelect>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Inspector Name <span class="text-danger">*</span></label>
                            <FormInput v-model="form.inspector_name" type="text" />
                            <p v-if="form.errors.inspector_name" class="text-danger text-xs mt-1">{{ form.errors.inspector_name }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Inspector Number / Badge</label>
                            <FormInput v-model="form.inspector_number" type="text" />
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Location</label>
                            <FormInput v-model="form.location" type="text" placeholder="City, State or checkpoint" />
                        </div>
                    </div>
                </div>

                <div class="box box--stacked p-6">
                    <h2 class="text-sm font-semibold text-slate-700 mb-5 flex items-center gap-2"><Lucide icon="ShieldAlert" class="w-4 h-4 text-primary" /> Findings</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Defects Found</label>
                            <FormTextarea v-model="form.defects_found" rows="4" placeholder="Describe defects found during inspection..." />
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Corrective Actions</label>
                            <FormTextarea v-model="form.corrective_actions" rows="4" placeholder="Describe corrective actions taken..." />
                        </div>
                        <div class="rounded-xl border border-slate-200 p-4">
                            <label class="block text-xs font-medium text-slate-600 mb-2">Vehicle Safe to Operate</label>
                            <div class="flex gap-4">
                                <label class="flex items-center gap-2 text-sm text-slate-700"><input v-model="form.is_vehicle_safe_to_operate" :value="true" type="radio"> Yes</label>
                                <label class="flex items-center gap-2 text-sm text-slate-700"><input v-model="form.is_vehicle_safe_to_operate" :value="false" type="radio"> No</label>
                            </div>
                        </div>
                        <div class="rounded-xl border border-slate-200 p-4">
                            <label class="flex items-center gap-3 text-sm font-medium text-slate-700">
                                <input v-model="form.is_defects_corrected" type="checkbox" class="w-4 h-4 rounded text-primary" />
                                Defects have been corrected
                            </label>
                            <div v-if="form.is_defects_corrected" class="mt-4 grid grid-cols-1 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-slate-600 mb-1.5">Corrected Date</label>
                                    <Litepicker v-model="form.defects_corrected_date" :options="lpOptions" />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-600 mb-1.5">Corrected By</label>
                                    <FormInput v-model="form.corrected_by" type="text" />
                                </div>
                            </div>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Notes</label>
                            <FormTextarea v-model="form.notes" rows="4" placeholder="Additional inspection notes..." />
                        </div>
                    </div>
                </div>

                <div class="box box--stacked p-6">
                    <h2 class="text-sm font-semibold text-slate-700 mb-5 flex items-center gap-2"><Lucide icon="Upload" class="w-4 h-4 text-primary" /> Documents</h2>
                    <input type="file" multiple accept=".jpg,.jpeg,.png,.pdf,.doc,.docx" class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2" @change="onFileChange" />
                    <p class="text-xs text-slate-500 mt-1">Upload inspection reports, photos or supporting files.</p>
                    <p v-if="form.errors.attachments" class="text-danger text-xs mt-1">{{ form.errors.attachments }}</p>
                    <p v-if="form.errors['attachments.0']" class="text-danger text-xs mt-1">{{ form.errors['attachments.0'] }}</p>
                </div>

                <div class="flex justify-end gap-3">
                    <Link :href="route(routeName('index'))"><Button variant="outline-secondary" type="button">Cancel</Button></Link>
                    <Button type="submit" variant="primary" :disabled="form.processing">{{ form.processing ? 'Saving...' : mode === 'edit' ? 'Update Inspection' : 'Create Inspection' }}</Button>
                </div>
            </form>
        </div>

        <div class="col-span-12 xl:col-span-4">
            <div class="box box--stacked p-5 sticky top-4">
                <h2 class="text-sm font-semibold text-slate-700 mb-4 flex items-center gap-2"><Lucide icon="Files" class="w-4 h-4 text-primary" /> Current Documents</h2>
                <div class="space-y-3">
                    <button v-for="document in inspection?.documents ?? []" :key="document.id" type="button" @click="openPreview(document)" class="w-full flex items-center justify-between rounded-lg border border-slate-200 px-4 py-3 text-sm text-slate-700 hover:bg-slate-50">
                        <span class="truncate pr-3">{{ document.name }}</span>
                        <Lucide icon="Eye" class="w-4 h-4 text-slate-400 flex-shrink-0" />
                    </button>
                    <div v-if="!(inspection?.documents?.length)" class="rounded-lg bg-slate-50 px-4 py-3 text-sm text-slate-500">No documents uploaded yet.</div>
                </div>
            </div>
        </div>
    </div>

    <Dialog :open="previewOpen" @close="previewOpen = false" size="xl">
        <Dialog.Panel class="w-full max-w-[900px] overflow-hidden">
            <div class="p-6">
                <div class="flex items-center justify-between border-b border-slate-200 pb-4 mb-5">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-800">{{ previewFile?.name }}</h3>
                        <p class="text-sm text-slate-500">{{ previewFile?.size }}</p>
                    </div>
                    <button type="button" class="text-slate-400 hover:text-slate-600" @click="previewOpen = false"><Lucide icon="X" class="w-5 h-5" /></button>
                </div>
                <div class="rounded-xl bg-slate-50 border border-slate-200 overflow-hidden min-h-[420px] flex items-center justify-center">
                    <iframe v-if="isPdf(previewFile)" :src="previewFile?.url" class="w-full h-[70vh] bg-white" title="File preview" />
                    <img v-else-if="previewFile?.url" :src="previewFile.url" :alt="previewFile.name" class="max-h-[70vh] w-auto object-contain">
                </div>
            </div>
        </Dialog.Panel>
    </Dialog>
</template>
