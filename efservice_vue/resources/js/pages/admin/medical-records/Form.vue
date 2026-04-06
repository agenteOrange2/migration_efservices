<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'
import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import Litepicker from '@/components/Base/Litepicker/Litepicker.vue'
import TomSelect from '@/components/Base/TomSelect/TomSelect.vue'
import { FormInput } from '@/components/Base/Form'
import { Dialog } from '@/components/Base/Headless'

declare function route(name: string, params?: any): string

const lpOptions = { singleMode: true, format: 'M/D/YYYY', autoApply: true }

interface CarrierOption {
    id: number
    name: string
}

interface DriverOption {
    id: number
    carrier_id: number | null
    carrier_name: string | null
    name: string
    email: string | null
}

interface FilePayload {
    name: string
    url: string
    mime_type: string | null
    size: number | null
}

interface RecordPayload {
    id: number
    user_driver_detail_id: number
    carrier_id: number | null
    social_security_number: string | null
    hire_date: string | null
    location: string | null
    is_suspended: boolean
    suspension_date: string | null
    is_terminated: boolean
    termination_date: string | null
    medical_examiner_name: string | null
    medical_examiner_registry_number: string | null
    medical_card_expiration_date: string | null
    driver_name: string
    medical_card_file: FilePayload | null
    social_security_card_file: FilePayload | null
}

const props = defineProps<{
    mode: 'create' | 'edit'
    carriers: CarrierOption[]
    drivers: DriverOption[]
    record?: RecordPayload
}>()

function toUsDate(value: string | null | undefined) {
    if (!value) return ''
    if (/^\d{2}\/\d{2}\/\d{4}$/.test(value)) return value
    const parts = value.split('-')
    return parts.length === 3 ? `${parts[1]}/${parts[2]}/${parts[0]}` : ''
}

const selectedCarrierId = ref(props.record?.carrier_id ? String(props.record.carrier_id) : '')
const previewOpen = ref(false)
const previewFile = ref<FilePayload | null>(null)

const form = useForm({
    user_driver_detail_id: props.record ? String(props.record.user_driver_detail_id) : '',
    social_security_number: props.record?.social_security_number ?? '',
    hire_date: toUsDate(props.record?.hire_date),
    location: props.record?.location ?? '',
    is_suspended: props.record?.is_suspended ?? false,
    suspension_date: toUsDate(props.record?.suspension_date),
    is_terminated: props.record?.is_terminated ?? false,
    termination_date: toUsDate(props.record?.termination_date),
    medical_examiner_name: props.record?.medical_examiner_name ?? '',
    medical_examiner_registry_number: props.record?.medical_examiner_registry_number ?? '',
    medical_card_expiration_date: toUsDate(props.record?.medical_card_expiration_date),
    medical_card: null as File | null,
    social_security_card: null as File | null,
    medical_documents: [] as File[],
})

const filteredDrivers = computed(() => {
    if (!selectedCarrierId.value) {
        return []
    }

    return props.drivers.filter(driver => String(driver.carrier_id ?? '') === selectedCarrierId.value)
})

watch(selectedCarrierId, (value, previousValue) => {
    if (value !== previousValue) {
        form.user_driver_detail_id = props.record && String(props.record.carrier_id ?? '') === value
            ? String(props.record.user_driver_detail_id)
            : ''
    }
})

watch(() => form.is_suspended, (value) => {
    if (!value) {
        form.suspension_date = ''
    }
})

watch(() => form.is_terminated, (value) => {
    if (!value) {
        form.termination_date = ''
    }
})

function onFileChange(field: 'medical_card' | 'social_security_card', event: Event) {
    const input = event.target as HTMLInputElement
    form[field] = input.files?.[0] ?? null
}

function onDocumentFilesChange(event: Event) {
    const input = event.target as HTMLInputElement
    form.medical_documents = Array.from(input.files ?? [])
}

function openPreview(file: FilePayload | null) {
    if (!file) return
    previewFile.value = file
    previewOpen.value = true
}

function humanSize(size: number | null) {
    if (!size) return null
    return `${(size / 1024 / 1024).toFixed(2)} MB`
}

function isPdf(file: FilePayload | null) {
    return !!file?.mime_type?.includes('pdf')
}

function submit() {
    if (props.mode === 'edit' && props.record) {
        form.transform(data => ({ ...data, _method: 'put' }))
            .post(route('admin.medical-records.update', props.record.id), { forceFormData: true })
        return
    }

    form.post(route('admin.medical-records.store'), { forceFormData: true })
}
</script>

<template>
    <div class="grid grid-cols-12 gap-y-8 gap-x-6">
        <div class="col-span-12">
            <div class="box box--stacked p-6">
                <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                            <Lucide :icon="mode === 'edit' ? 'FilePenLine' : 'HeartPulse'" class="w-8 h-8 text-primary" />
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-slate-800">
                                {{ mode === 'edit' ? 'Edit Medical Record' : 'Add New Medical Record' }}
                            </h1>
                            <p class="text-slate-500">
                                {{ mode === 'edit' ? `Update ${record?.driver_name ?? 'driver'} medical details.` : 'Create a medical record in the Vue admin.' }}
                            </p>
                        </div>
                    </div>

                    <Link :href="route('admin.medical-records.index')">
                        <Button variant="outline-secondary" class="flex items-center gap-2">
                            <Lucide icon="ArrowLeft" class="w-4 h-4" />
                            Back to Medical Records
                        </Button>
                    </Link>
                </div>
            </div>
        </div>

        <div class="col-span-12 xl:col-span-8">
            <form @submit.prevent="submit" class="space-y-6">
                <div class="box box--stacked p-6">
                    <h2 class="text-sm font-semibold text-slate-700 mb-5 flex items-center gap-2">
                        <Lucide icon="Users" class="w-4 h-4 text-primary" />
                        Driver Selection
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Carrier <span class="text-red-500">*</span></label>
                            <TomSelect v-model="selectedCarrierId">
                                <option value="">Select carrier</option>
                                <option v-for="carrier in carriers" :key="carrier.id" :value="String(carrier.id)">
                                    {{ carrier.name }}
                                </option>
                            </TomSelect>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Driver <span class="text-red-500">*</span></label>
                            <TomSelect v-model="form.user_driver_detail_id" :class="form.errors.user_driver_detail_id ? 'border-red-400' : ''">
                                <option value="">Select driver</option>
                                <option v-for="driver in filteredDrivers" :key="driver.id" :value="String(driver.id)">
                                    {{ driver.name }}
                                </option>
                            </TomSelect>
                            <p v-if="form.errors.user_driver_detail_id" class="text-red-500 text-xs mt-1">{{ form.errors.user_driver_detail_id }}</p>
                        </div>
                    </div>
                </div>

                <div class="box box--stacked p-6">
                    <h2 class="text-sm font-semibold text-slate-700 mb-5 flex items-center gap-2">
                        <Lucide icon="IdCard" class="w-4 h-4 text-primary" />
                        Driver Information
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Social Security Number <span class="text-red-500">*</span></label>
                            <FormInput v-model="form.social_security_number" type="text" placeholder="XXX-XX-XXXX" :class="form.errors.social_security_number ? 'border-red-400' : ''" />
                            <p v-if="form.errors.social_security_number" class="text-red-500 text-xs mt-1">{{ form.errors.social_security_number }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Hire Date</label>
                            <Litepicker v-model="form.hire_date" :options="lpOptions" />
                            <p v-if="form.errors.hire_date" class="text-red-500 text-xs mt-1">{{ form.errors.hire_date }}</p>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Location</label>
                            <FormInput v-model="form.location" type="text" placeholder="Work location" :class="form.errors.location ? 'border-red-400' : ''" />
                            <p v-if="form.errors.location" class="text-red-500 text-xs mt-1">{{ form.errors.location }}</p>
                        </div>
                    </div>
                </div>

                <div class="box box--stacked p-6">
                    <h2 class="text-sm font-semibold text-slate-700 mb-5 flex items-center gap-2">
                        <Lucide icon="ShieldAlert" class="w-4 h-4 text-primary" />
                        Status Information
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div class="rounded-xl border border-slate-200 p-4">
                            <label class="flex items-center gap-3 text-sm font-medium text-slate-700">
                                <input v-model="form.is_suspended" type="checkbox" class="w-4 h-4 rounded text-primary" />
                                Driver is suspended
                            </label>

                            <div v-if="form.is_suspended" class="mt-4">
                                <label class="block text-xs font-medium text-slate-600 mb-1.5">Suspension Date</label>
                                <Litepicker v-model="form.suspension_date" :options="lpOptions" />
                                <p v-if="form.errors.suspension_date" class="text-red-500 text-xs mt-1">{{ form.errors.suspension_date }}</p>
                            </div>
                        </div>

                        <div class="rounded-xl border border-slate-200 p-4">
                            <label class="flex items-center gap-3 text-sm font-medium text-slate-700">
                                <input v-model="form.is_terminated" type="checkbox" class="w-4 h-4 rounded text-primary" />
                                Driver is terminated
                            </label>

                            <div v-if="form.is_terminated" class="mt-4">
                                <label class="block text-xs font-medium text-slate-600 mb-1.5">Termination Date</label>
                                <Litepicker v-model="form.termination_date" :options="lpOptions" />
                                <p v-if="form.errors.termination_date" class="text-red-500 text-xs mt-1">{{ form.errors.termination_date }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="box box--stacked p-6">
                    <h2 class="text-sm font-semibold text-slate-700 mb-5 flex items-center gap-2">
                        <Lucide icon="Stethoscope" class="w-4 h-4 text-primary" />
                        Medical Certification
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Medical Examiner Name <span class="text-red-500">*</span></label>
                            <FormInput v-model="form.medical_examiner_name" type="text" :class="form.errors.medical_examiner_name ? 'border-red-400' : ''" />
                            <p v-if="form.errors.medical_examiner_name" class="text-red-500 text-xs mt-1">{{ form.errors.medical_examiner_name }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Registry Number</label>
                            <FormInput v-model="form.medical_examiner_registry_number" type="text" :class="form.errors.medical_examiner_registry_number ? 'border-red-400' : ''" />
                            <p v-if="form.errors.medical_examiner_registry_number" class="text-red-500 text-xs mt-1">{{ form.errors.medical_examiner_registry_number }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Medical Card Expiration Date</label>
                            <Litepicker v-model="form.medical_card_expiration_date" :options="lpOptions" />
                            <p v-if="form.errors.medical_card_expiration_date" class="text-red-500 text-xs mt-1">{{ form.errors.medical_card_expiration_date }}</p>
                        </div>
                    </div>
                </div>

                <div class="box box--stacked p-6">
                    <h2 class="text-sm font-semibold text-slate-700 mb-5 flex items-center gap-2">
                        <Lucide icon="Upload" class="w-4 h-4 text-primary" />
                        Upload Files
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Medical Card</label>
                            <input type="file" accept=".jpg,.jpeg,.png,.pdf" class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2" @change="onFileChange('medical_card', $event)" />
                            <p v-if="form.errors.medical_card" class="text-red-500 text-xs mt-1">{{ form.errors.medical_card }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Social Security Card</label>
                            <input type="file" accept=".jpg,.jpeg,.png,.pdf" class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2" @change="onFileChange('social_security_card', $event)" />
                            <p v-if="form.errors.social_security_card" class="text-red-500 text-xs mt-1">{{ form.errors.social_security_card }}</p>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Additional Medical Documents</label>
                            <input type="file" multiple accept=".jpg,.jpeg,.png,.pdf,.doc,.docx" class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2" @change="onDocumentFilesChange" />
                            <p class="text-xs text-slate-500 mt-1">You can upload multiple supporting files here.</p>
                            <p v-if="form.errors.medical_documents" class="text-red-500 text-xs mt-1">{{ form.errors.medical_documents }}</p>
                            <p v-if="form.errors['medical_documents.0']" class="text-red-500 text-xs mt-1">{{ form.errors['medical_documents.0'] }}</p>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-3">
                    <Link :href="route('admin.medical-records.index')">
                        <Button variant="outline-secondary" type="button">Cancel</Button>
                    </Link>
                    <Button type="submit" variant="primary" :disabled="form.processing">
                        {{ form.processing ? 'Saving...' : mode === 'edit' ? 'Update Medical Record' : 'Create Medical Record' }}
                    </Button>
                </div>
            </form>
        </div>

        <div class="col-span-12 xl:col-span-4">
            <div class="box box--stacked p-5 sticky top-4">
                <h2 class="text-sm font-semibold text-slate-700 mb-4 flex items-center gap-2">
                    <Lucide icon="Files" class="w-4 h-4 text-primary" />
                    Current Files
                </h2>

                <div class="space-y-3">
                    <Link
                        v-if="record"
                        :href="route('admin.medical-records.documents.show', record.id)"
                        class="w-full flex items-center justify-between rounded-lg border border-primary/20 bg-primary/5 px-4 py-3 text-sm text-primary hover:bg-primary/10"
                    >
                        <span>Manage all documents</span>
                        <Lucide icon="ArrowUpRight" class="w-4 h-4" />
                    </Link>

                    <button
                        v-if="record?.medical_card_file"
                        type="button"
                        @click="openPreview(record.medical_card_file)"
                        class="w-full flex items-center justify-between rounded-lg border border-slate-200 px-4 py-3 text-sm text-slate-700 hover:bg-slate-50"
                    >
                        <span>Medical card</span>
                        <Lucide icon="Eye" class="w-4 h-4 text-slate-400" />
                    </button>
                    <div v-else class="rounded-lg bg-slate-50 px-4 py-3 text-sm text-slate-500">No medical card uploaded.</div>

                    <button
                        v-if="record?.social_security_card_file"
                        type="button"
                        @click="openPreview(record.social_security_card_file)"
                        class="w-full flex items-center justify-between rounded-lg border border-slate-200 px-4 py-3 text-sm text-slate-700 hover:bg-slate-50"
                    >
                        <span>Social security card</span>
                        <Lucide icon="Eye" class="w-4 h-4 text-slate-400" />
                    </button>
                    <div v-else class="rounded-lg bg-slate-50 px-4 py-3 text-sm text-slate-500">No social security card uploaded.</div>
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
                        <p v-if="previewFile?.size" class="text-sm text-slate-500">{{ humanSize(previewFile.size) }}</p>
                    </div>
                    <button type="button" class="text-slate-400 hover:text-slate-600" @click="previewOpen = false">
                        <Lucide icon="X" class="w-5 h-5" />
                    </button>
                </div>

                <div class="rounded-xl bg-slate-50 border border-slate-200 overflow-hidden min-h-[420px] flex items-center justify-center">
                    <iframe
                        v-if="isPdf(previewFile)"
                        :src="previewFile?.url"
                        class="w-full h-[70vh] bg-white"
                        title="File preview"
                    />
                    <img
                        v-else-if="previewFile?.url"
                        :src="previewFile.url"
                        :alt="previewFile.name"
                        class="max-h-[70vh] w-auto object-contain"
                    />
                </div>
            </div>
        </Dialog.Panel>
    </Dialog>
</template>
