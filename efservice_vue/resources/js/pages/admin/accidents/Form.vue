<script setup lang="ts">
import { Link, router, useForm } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'
import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import Litepicker from '@/components/Base/Litepicker/Litepicker.vue'
import TomSelect from '@/components/Base/TomSelect/TomSelect.vue'
import { Dialog } from '@/components/Base/Headless'
import { FormCheck, FormInput } from '@/components/Base/Form'

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

interface ExistingFile {
    id: number
    source: 'document' | 'media'
    name: string
    url: string
    size: string
}

interface AccidentPayload {
    id: number
    user_driver_detail_id: number
    carrier_id: number | null
    driver_name: string
    accident_date: string
    registration_date: string | null
    nature_of_accident: string
    had_injuries: boolean
    number_of_injuries: number
    had_fatalities: boolean
    number_of_fatalities: number
    comments: string | null
    documents: ExistingFile[]
    media_files: ExistingFile[]
}

interface AccidentRouteNames {
    index: string
    create: string
    store: string
    edit: string
    update: string
    destroy: string
    driverHistory: string
    documentsIndex: string
    documentsShow: string
    documentsDestroy: string
    mediaDestroy: string
    driverShow: string
}

const props = defineProps<{
    mode: 'create' | 'edit'
    carriers: CarrierOption[]
    drivers: DriverOption[]
    accident?: AccidentPayload
    carrier?: CarrierOption | null
    isCarrierContext?: boolean
    routeNames?: AccidentRouteNames
}>()

const selectedCarrierId = ref(
    props.carrier?.id
        ? String(props.carrier.id)
        : props.accident?.carrier_id
            ? String(props.accident.carrier_id)
            : ''
)
const previewOpen = ref(false)
const previewFile = ref<ExistingFile | null>(null)

const form = useForm({
    carrier_id: selectedCarrierId.value,
    user_driver_detail_id: props.accident ? String(props.accident.user_driver_detail_id) : '',
    accident_date: props.accident?.accident_date ?? '',
    nature_of_accident: props.accident?.nature_of_accident ?? '',
    had_injuries: props.accident?.had_injuries ?? false,
    number_of_injuries: props.accident?.number_of_injuries ?? 0,
    had_fatalities: props.accident?.had_fatalities ?? false,
    number_of_fatalities: props.accident?.number_of_fatalities ?? 0,
    comments: props.accident?.comments ?? '',
    attachments: [] as File[],
})

const filteredDrivers = computed(() => {
    if (!selectedCarrierId.value) return []
    return props.drivers.filter(driver => String(driver.carrier_id ?? '') === selectedCarrierId.value)
})

const existingFiles = computed(() => [
    ...(props.accident?.documents ?? []),
    ...(props.accident?.media_files ?? []),
])

watch(selectedCarrierId, (value, oldValue) => {
    form.carrier_id = value

    if (value !== oldValue) {
        form.user_driver_detail_id = props.accident && String(props.accident.carrier_id ?? '') === value
            ? String(props.accident.user_driver_detail_id)
            : ''
    }
})

watch(() => props.carrier?.id, (value) => {
    if (props.isCarrierContext && value) {
        selectedCarrierId.value = String(value)
        form.carrier_id = String(value)
    }
}, { immediate: true })

watch(() => form.had_injuries, (value) => {
    if (!value) {
        form.number_of_injuries = 0
    }
})

watch(() => form.had_fatalities, (value) => {
    if (!value) {
        form.number_of_fatalities = 0
    }
})

function onFilesChange(event: Event) {
    const input = event.target as HTMLInputElement
    form.attachments = input.files ? Array.from(input.files) : []
}

function openPreview(file: ExistingFile) {
    previewFile.value = file
    previewOpen.value = true
}

function isPdf(url: string | undefined) {
    return !!url && /\.pdf($|\?)/i.test(url)
}

function isImage(url: string | undefined) {
    return !!url && /\.(png|jpe?g|gif|webp|svg|bmp)($|\?)/i.test(url)
}

function deleteFile(file: ExistingFile) {
    const confirmed = confirm(`Delete "${file.name}"?`)
    if (!confirmed || !props.accident) return

    const target = file.source === 'document'
        ? route(props.routeNames?.documentsDestroy ?? 'admin.accidents.documents.destroy', file.id)
        : route(props.routeNames?.mediaDestroy ?? 'admin.accidents.media.destroy', file.id)

    router.delete(target, {
        preserveScroll: true,
    })
}

function submit() {
    if (props.mode === 'edit' && props.accident) {
        form.transform(data => ({ ...data, _method: 'put' }))
            .post(route(props.routeNames?.update ?? 'admin.accidents.update', props.accident.id), { forceFormData: true })
        return
    }

    form.post(route(props.routeNames?.store ?? 'admin.accidents.store'), { forceFormData: true })
}
</script>

<template>
    <div class="grid grid-cols-12 gap-y-8 gap-x-6">
        <div class="col-span-12">
            <div class="box box--stacked p-6">
                <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                            <Lucide :icon="mode === 'edit' ? 'PenLine' : 'AlertTriangle'" class="w-8 h-8 text-primary" />
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-slate-800">
                                {{ mode === 'edit' ? 'Edit Accident Record' : 'Add New Accident Record' }}
                            </h1>
                            <p class="text-slate-500">
                                {{ mode === 'edit' ? `Update accident for ${accident?.driver_name ?? 'driver'}.` : 'Create a new accident record in the Vue admin.' }}
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <Link v-if="mode === 'edit' && accident" :href="route(props.routeNames?.documentsShow ?? 'admin.accidents.documents.show', accident.id)">
                            <Button variant="outline-primary" class="flex items-center gap-2">
                                <Lucide icon="FileText" class="w-4 h-4" />
                                View Documents
                            </Button>
                        </Link>
                        <Link :href="route(props.routeNames?.index ?? 'admin.accidents.index')">
                            <Button variant="outline-secondary" class="flex items-center gap-2">
                                <Lucide icon="ArrowLeft" class="w-4 h-4" />
                                Back to Accidents
                            </Button>
                        </Link>
                    </div>
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
                        <div v-if="!props.isCarrierContext">
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Carrier <span class="text-red-500">*</span></label>
                            <TomSelect v-model="selectedCarrierId">
                                <option value="">Select carrier</option>
                                <option v-for="carrier in carriers" :key="carrier.id" :value="String(carrier.id)">
                                    {{ carrier.name }}
                                </option>
                            </TomSelect>
                            <p v-if="form.errors.carrier_id" class="text-red-500 text-xs mt-1">{{ form.errors.carrier_id }}</p>
                        </div>

                        <div v-else class="rounded-xl border border-primary/15 bg-primary/5 px-4 py-3">
                            <div class="text-xs font-medium uppercase tracking-wide text-primary/80">Carrier</div>
                            <div class="mt-1 text-sm font-semibold text-slate-800">{{ props.carrier?.name ?? 'Assigned carrier' }}</div>
                            <div class="mt-1 text-xs text-slate-500">Only drivers from your carrier are available for this accident record.</div>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Driver <span class="text-red-500">*</span></label>
                            <TomSelect v-model="form.user_driver_detail_id">
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
                        <Lucide icon="CalendarDays" class="w-4 h-4 text-primary" />
                        Accident Details
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div v-if="mode === 'edit' && accident?.registration_date">
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Registration Date</label>
                            <FormInput :model-value="accident.registration_date" type="text" disabled />
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Accident Date <span class="text-red-500">*</span></label>
                            <Litepicker v-model="form.accident_date" :options="lpOptions" />
                            <p v-if="form.errors.accident_date" class="text-red-500 text-xs mt-1">{{ form.errors.accident_date }}</p>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Nature of Accident <span class="text-red-500">*</span></label>
                            <FormInput v-model="form.nature_of_accident" type="text" placeholder="Describe the accident" />
                            <p v-if="form.errors.nature_of_accident" class="text-red-500 text-xs mt-1">{{ form.errors.nature_of_accident }}</p>
                        </div>
                    </div>
                </div>

                <div class="box box--stacked p-6">
                    <h2 class="text-sm font-semibold text-slate-700 mb-5 flex items-center gap-2">
                        <Lucide icon="ShieldAlert" class="w-4 h-4 text-primary" />
                        Impact Summary
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div class="rounded-xl border border-slate-200 p-4">
                            <label class="flex items-center gap-3 text-sm font-medium text-slate-700">
                                <FormCheck.Input v-model="form.had_injuries" type="checkbox" class="mt-0.5" />
                                Had injuries
                            </label>

                            <div v-if="form.had_injuries" class="mt-4">
                                <label class="block text-xs font-medium text-slate-600 mb-1.5">Number of Injuries</label>
                                <FormInput v-model="form.number_of_injuries" type="number" min="0" />
                                <p v-if="form.errors.number_of_injuries" class="text-red-500 text-xs mt-1">{{ form.errors.number_of_injuries }}</p>
                            </div>
                        </div>

                        <div class="rounded-xl border border-slate-200 p-4">
                            <label class="flex items-center gap-3 text-sm font-medium text-slate-700">
                                <FormCheck.Input v-model="form.had_fatalities" type="checkbox" class="mt-0.5" />
                                Had fatalities
                            </label>

                            <div v-if="form.had_fatalities" class="mt-4">
                                <label class="block text-xs font-medium text-slate-600 mb-1.5">Number of Fatalities</label>
                                <FormInput v-model="form.number_of_fatalities" type="number" min="0" />
                                <p v-if="form.errors.number_of_fatalities" class="text-red-500 text-xs mt-1">{{ form.errors.number_of_fatalities }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-5">
                        <label class="block text-xs font-medium text-slate-600 mb-1.5">Comments</label>
                        <textarea
                            v-model="form.comments"
                            rows="4"
                            class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 focus:border-primary focus:ring-primary"
                            placeholder="Add any relevant details about the accident..."
                        />
                        <p v-if="form.errors.comments" class="text-red-500 text-xs mt-1">{{ form.errors.comments }}</p>
                    </div>
                </div>

                <div class="box box--stacked p-6">
                    <h2 class="text-sm font-semibold text-slate-700 mb-5 flex items-center gap-2">
                        <Lucide icon="Upload" class="w-4 h-4 text-primary" />
                        Documents
                    </h2>

                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1.5">Upload Files</label>
                        <input
                            type="file"
                            multiple
                            accept=".jpg,.jpeg,.png,.pdf,.doc,.docx"
                            class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2"
                            @change="onFilesChange"
                        />
                        <p class="text-xs text-slate-500 mt-2">Accepted: JPG, PNG, PDF, DOC, DOCX up to 10MB each.</p>
                        <p v-if="form.errors.attachments" class="text-red-500 text-xs mt-1">{{ form.errors.attachments }}</p>
                        <p v-if="form.errors['attachments.0']" class="text-red-500 text-xs mt-1">{{ form.errors['attachments.0'] }}</p>
                    </div>

                    <div v-if="form.attachments.length" class="mt-4 space-y-2">
                        <div
                            v-for="file in form.attachments"
                            :key="`${file.name}-${file.size}`"
                            class="flex items-center justify-between rounded-lg border border-slate-200 px-4 py-3 text-sm text-slate-700"
                        >
                            <span class="truncate">{{ file.name }}</span>
                            <span class="text-slate-400">{{ (file.size / 1024).toFixed(1) }} KB</span>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-3">
                    <Link :href="route(props.routeNames?.index ?? 'admin.accidents.index')">
                        <Button variant="outline-secondary" type="button">Cancel</Button>
                    </Link>
                    <Button type="submit" variant="primary" :disabled="form.processing">
                        {{ form.processing ? 'Saving...' : mode === 'edit' ? 'Update Accident' : 'Create Accident' }}
                    </Button>
                </div>
            </form>
        </div>

        <div class="col-span-12 xl:col-span-4">
            <div class="box box--stacked p-5 sticky top-4">
                <h2 class="text-sm font-semibold text-slate-700 mb-4 flex items-center gap-2">
                    <Lucide icon="Files" class="w-4 h-4 text-primary" />
                    Existing Files
                </h2>

                <div v-if="existingFiles.length" class="space-y-3">
                    <div
                        v-for="file in existingFiles"
                        :key="`${file.source}-${file.id}`"
                        class="rounded-lg border border-slate-200 px-4 py-3"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-slate-700 truncate">{{ file.name }}</p>
                                <p class="text-xs text-slate-500 mt-1">{{ file.size }}</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="button" class="text-slate-400 hover:text-primary" @click="openPreview(file)">
                                    <Lucide icon="Eye" class="w-4 h-4" />
                                </button>
                                <button type="button" class="text-slate-400 hover:text-danger" @click="deleteFile(file)">
                                    <Lucide icon="Trash2" class="w-4 h-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-else class="rounded-lg bg-slate-50 px-4 py-3 text-sm text-slate-500">
                    No documents uploaded yet.
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
                    <button type="button" class="text-slate-400 hover:text-slate-600" @click="previewOpen = false">
                        <Lucide icon="X" class="w-5 h-5" />
                    </button>
                </div>

                <div class="rounded-xl bg-slate-50 border border-slate-200 overflow-hidden min-h-[420px] flex items-center justify-center">
                    <iframe
                        v-if="isPdf(previewFile?.url)"
                        :src="previewFile?.url"
                        class="w-full h-[70vh] bg-white"
                        title="File preview"
                    />
                    <img
                        v-else-if="isImage(previewFile?.url)"
                        :src="previewFile.url"
                        :alt="previewFile.name"
                        class="max-h-[70vh] w-auto object-contain"
                    />
                    <div v-else-if="previewFile" class="text-center">
                        <p class="text-slate-600 mb-3">Preview is not available for this file type.</p>
                        <a :href="previewFile.url" target="_blank" class="text-primary font-medium">Open file</a>
                    </div>
                </div>
            </div>
        </Dialog.Panel>
    </Dialog>
</template>
