<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import Button from '@/components/Base/Button'
import { FormInput, FormLabel, FormTextarea } from '@/components/Base/Form'
import Litepicker from '@/components/Base/Litepicker/Litepicker.vue'
import Lucide from '@/components/Base/Lucide'
import TomSelect from '@/components/Base/TomSelect/TomSelect.vue'
import RazeLayout from '@/layouts/RazeLayout.vue'
import ReportHero from './components/ReportHero.vue'
import { pickerOptions } from './components/reportUtils'

declare function route(name: string, params?: any): string

defineOptions({ layout: RazeLayout })

const props = defineProps<{
    carriers: { id: number; name: string }[]
    drivers: { id: number; name: string }[]
    filters: { carrier_id: string }
    canFilterCarriers: boolean
}>()

const selectedFiles = ref<File[]>([])

const form = useForm({
    carrier_id: props.filters.carrier_id || '',
    user_driver_detail_id: '',
    accident_date: '',
    nature_of_accident: '',
    had_injuries: false,
    number_of_injuries: 0,
    had_fatalities: false,
    number_of_fatalities: 0,
    comments: '',
    attachments: [] as File[],
})

const availableDrivers = computed(() => props.drivers)

function onFilesChange(event: Event) {
    const target = event.target as HTMLInputElement
    selectedFiles.value = Array.from(target.files || [])
    form.attachments = selectedFiles.value
}

function submit() {
    form.post(route('admin.reports.store-accident'))
}
</script>

<template>
    <Head title="Register Accident" />

    <div class="grid grid-cols-12 gap-6">
        <div class="col-span-12">
            <ReportHero title="Register Accident" subtitle="Quick-entry form from the reports area for new driver accident records." icon="FileWarning" />
        </div>

        <div class="col-span-12">
            <div class="box box--stacked p-6">
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div>
                        <FormLabel>Carrier</FormLabel>
                        <TomSelect v-model="form.carrier_id">
                            <option value="">Select carrier</option>
                            <option v-for="carrier in carriers" :key="carrier.id" :value="String(carrier.id)">{{ carrier.name }}</option>
                        </TomSelect>
                        <div v-if="form.errors.carrier_id" class="mt-1 text-xs text-red-500">{{ form.errors.carrier_id }}</div>
                    </div>
                    <div>
                        <FormLabel>Driver</FormLabel>
                        <TomSelect v-model="form.user_driver_detail_id">
                            <option value="">Select driver</option>
                            <option v-for="driver in availableDrivers" :key="driver.id" :value="String(driver.id)">{{ driver.name }}</option>
                        </TomSelect>
                        <div v-if="form.errors.user_driver_detail_id" class="mt-1 text-xs text-red-500">{{ form.errors.user_driver_detail_id }}</div>
                    </div>
                    <div>
                        <FormLabel>Accident Date</FormLabel>
                        <Litepicker v-model="form.accident_date" :options="pickerOptions" />
                        <div v-if="form.errors.accident_date" class="mt-1 text-xs text-red-500">{{ form.errors.accident_date }}</div>
                    </div>
                    <div>
                        <FormLabel>Nature of Accident</FormLabel>
                        <FormInput v-model="form.nature_of_accident" type="text" placeholder="Rear-end collision, lane departure, etc." />
                        <div v-if="form.errors.nature_of_accident" class="mt-1 text-xs text-red-500">{{ form.errors.nature_of_accident }}</div>
                    </div>
                    <div class="rounded-xl border border-slate-200 p-4">
                        <label class="flex items-center gap-3 text-sm font-medium text-slate-700">
                            <input v-model="form.had_injuries" type="checkbox" class="rounded border-slate-300" />
                            Accident had injuries
                        </label>
                        <FormInput v-model="form.number_of_injuries" type="number" min="0" class="mt-3" placeholder="Number of injuries" />
                    </div>
                    <div class="rounded-xl border border-slate-200 p-4">
                        <label class="flex items-center gap-3 text-sm font-medium text-slate-700">
                            <input v-model="form.had_fatalities" type="checkbox" class="rounded border-slate-300" />
                            Accident had fatalities
                        </label>
                        <FormInput v-model="form.number_of_fatalities" type="number" min="0" class="mt-3" placeholder="Number of fatalities" />
                    </div>
                    <div class="md:col-span-2">
                        <FormLabel>Comments</FormLabel>
                        <FormTextarea v-model="form.comments" rows="5" placeholder="Additional notes about the accident..." />
                    </div>
                    <div class="md:col-span-2">
                        <FormLabel>Attachments</FormLabel>
                        <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-4">
                            <label class="flex cursor-pointer items-center gap-3 text-sm text-slate-600">
                                <Lucide icon="Paperclip" class="h-4 w-4" />
                                <span>Select files</span>
                                <input type="file" multiple class="hidden" @change="onFilesChange" />
                            </label>
                            <ul v-if="selectedFiles.length" class="mt-3 space-y-1 text-xs text-slate-500">
                                <li v-for="file in selectedFiles" :key="file.name">{{ file.name }}</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <Button as="a" :href="route('admin.reports.accidents')" variant="outline-secondary">Cancel</Button>
                    <Button variant="primary" class="gap-2" :disabled="form.processing" @click="submit">
                        <Lucide icon="Save" class="h-4 w-4" />
                        Save Accident
                    </Button>
                </div>
            </div>
        </div>
    </div>
</template>
