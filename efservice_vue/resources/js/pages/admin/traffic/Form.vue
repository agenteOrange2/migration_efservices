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

interface CarrierOption { id: number; name: string }
interface DriverOption { id: number; carrier_id: number | null; carrier_name: string | null; name: string; email: string | null }
interface DocumentPayload { id: number; name: string; url: string; size: string; mime_type: string | null }
interface ConvictionPayload {
    id: number
    carrier_id: number | null
    user_driver_detail_id: number
    driver_name: string
    conviction_date: string | null
    location: string | null
    charge: string | null
    penalty: string | null
    documents: DocumentPayload[]
}

const props = defineProps<{
    mode: 'create' | 'edit'
    carriers: CarrierOption[]
    drivers: DriverOption[]
    conviction?: ConvictionPayload
}>()

const selectedCarrierId = ref(props.conviction?.carrier_id ? String(props.conviction.carrier_id) : '')
const previewOpen = ref(false)
const previewFile = ref<DocumentPayload | null>(null)

const form = useForm({
    carrier_id: selectedCarrierId.value,
    user_driver_detail_id: props.conviction ? String(props.conviction.user_driver_detail_id) : '',
    conviction_date: props.conviction?.conviction_date ?? '',
    location: props.conviction?.location ?? '',
    charge: props.conviction?.charge ?? '',
    penalty: props.conviction?.penalty ?? '',
    attachments: [] as File[],
})

const filteredDrivers = computed(() => {
    if (!selectedCarrierId.value) return []
    return props.drivers.filter(driver => String(driver.carrier_id ?? '') === selectedCarrierId.value)
})

watch(selectedCarrierId, value => {
    form.carrier_id = value
    if (props.conviction && String(props.conviction.carrier_id ?? '') === value) {
        form.user_driver_detail_id = String(props.conviction.user_driver_detail_id)
        return
    }
    form.user_driver_detail_id = ''
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
    if (props.mode === 'edit' && props.conviction) {
        form.transform(data => ({ ...data, _method: 'put' }))
            .post(route('admin.traffic.update', props.conviction.id), { forceFormData: true })
        return
    }
    form.post(route('admin.traffic.store'), { forceFormData: true })
}
</script>

<template>
    <div class="grid grid-cols-12 gap-y-8 gap-x-6">
        <div class="col-span-12">
            <div class="box box--stacked p-6">
                <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                            <Lucide :icon="mode === 'edit' ? 'PenLine' : 'TrafficCone'" class="w-8 h-8 text-primary" />
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-slate-800">{{ mode === 'edit' ? 'Edit Traffic Conviction' : 'Add Traffic Conviction' }}</h1>
                            <p class="text-slate-500">{{ mode === 'edit' ? `Update ${conviction?.charge ?? 'traffic conviction'} for ${conviction?.driver_name ?? 'driver'}.` : 'Create a traffic conviction record in the Vue admin.' }}</p>
                        </div>
                    </div>

                    <Link :href="route('admin.traffic.index')">
                        <Button variant="outline-secondary" class="flex items-center gap-2">
                            <Lucide icon="ArrowLeft" class="w-4 h-4" />
                            Back to Traffic
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
                                <option v-for="carrier in carriers" :key="carrier.id" :value="String(carrier.id)">{{ carrier.name }}</option>
                            </TomSelect>
                            <p v-if="form.errors.carrier_id" class="text-red-500 text-xs mt-1">{{ form.errors.carrier_id }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Driver <span class="text-red-500">*</span></label>
                            <TomSelect v-model="form.user_driver_detail_id">
                                <option value="">Select driver</option>
                                <option v-for="driver in filteredDrivers" :key="driver.id" :value="String(driver.id)">{{ driver.name }}</option>
                            </TomSelect>
                            <p v-if="form.errors.user_driver_detail_id" class="text-red-500 text-xs mt-1">{{ form.errors.user_driver_detail_id }}</p>
                        </div>
                    </div>
                </div>

                <div class="box box--stacked p-6">
                    <h2 class="text-sm font-semibold text-slate-700 mb-5 flex items-center gap-2">
                        <Lucide icon="ShieldAlert" class="w-4 h-4 text-primary" />
                        Conviction Details
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Conviction Date <span class="text-red-500">*</span></label>
                            <Litepicker v-model="form.conviction_date" :options="lpOptions" />
                            <p v-if="form.errors.conviction_date" class="text-red-500 text-xs mt-1">{{ form.errors.conviction_date }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Location <span class="text-red-500">*</span></label>
                            <FormInput v-model="form.location" type="text" placeholder="City, State" />
                            <p v-if="form.errors.location" class="text-red-500 text-xs mt-1">{{ form.errors.location }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Charge <span class="text-red-500">*</span></label>
                            <FormInput v-model="form.charge" type="text" placeholder="Speeding, lane violation, etc." />
                            <p v-if="form.errors.charge" class="text-red-500 text-xs mt-1">{{ form.errors.charge }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Penalty <span class="text-red-500">*</span></label>
                            <FormInput v-model="form.penalty" type="text" placeholder="Fine, points, suspension..." />
                            <p v-if="form.errors.penalty" class="text-red-500 text-xs mt-1">{{ form.errors.penalty }}</p>
                        </div>
                    </div>
                </div>

                <div class="box box--stacked p-6">
                    <h2 class="text-sm font-semibold text-slate-700 mb-5 flex items-center gap-2">
                        <Lucide icon="Upload" class="w-4 h-4 text-primary" />
                        Supporting Documents
                    </h2>

                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1.5">Attachments</label>
                        <input type="file" multiple accept=".jpg,.jpeg,.png,.pdf,.doc,.docx" class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2" @change="onFileChange" />
                        <p class="text-xs text-slate-500 mt-1">You can upload citation scans, PDFs or other support files.</p>
                        <p v-if="form.errors.attachments" class="text-red-500 text-xs mt-1">{{ form.errors.attachments }}</p>
                        <p v-if="form.errors['attachments.0']" class="text-red-500 text-xs mt-1">{{ form.errors['attachments.0'] }}</p>
                    </div>
                </div>

                <div class="flex justify-end gap-3">
                    <Link :href="route('admin.traffic.index')"><Button variant="outline-secondary" type="button">Cancel</Button></Link>
                    <Button type="submit" variant="primary" :disabled="form.processing">{{ form.processing ? 'Saving...' : mode === 'edit' ? 'Update Conviction' : 'Create Conviction' }}</Button>
                </div>
            </form>
        </div>

        <div class="col-span-12 xl:col-span-4">
            <div class="box box--stacked p-5 sticky top-4">
                <h2 class="text-sm font-semibold text-slate-700 mb-4 flex items-center gap-2">
                    <Lucide icon="Files" class="w-4 h-4 text-primary" />
                    Current Documents
                </h2>

                <div class="space-y-3">
                    <Link v-if="conviction" :href="route('admin.traffic.documents.show', conviction.id)" class="w-full flex items-center justify-between rounded-lg border border-primary/20 bg-primary/5 px-4 py-3 text-sm text-primary hover:bg-primary/10">
                        <span>Manage all documents</span>
                        <Lucide icon="ArrowUpRight" class="w-4 h-4" />
                    </Link>

                    <button v-for="document in conviction?.documents ?? []" :key="document.id" type="button" @click="openPreview(document)" class="w-full flex items-center justify-between rounded-lg border border-slate-200 px-4 py-3 text-sm text-slate-700 hover:bg-slate-50">
                        <span class="truncate pr-3">{{ document.name }}</span>
                        <Lucide icon="Eye" class="w-4 h-4 text-slate-400 flex-shrink-0" />
                    </button>

                    <div v-if="!(conviction?.documents?.length)" class="rounded-lg bg-slate-50 px-4 py-3 text-sm text-slate-500">No documents uploaded yet.</div>
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
                    <iframe v-if="isPdf(previewFile)" :src="previewFile?.url" class="w-full h-[70vh] bg-white" title="File preview" />
                    <img v-else-if="previewFile?.url" :src="previewFile.url" :alt="previewFile.name" class="max-h-[70vh] w-auto object-contain">
                </div>
            </div>
        </Dialog.Panel>
    </Dialog>
</template>
