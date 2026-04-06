<script setup lang="ts">
import { computed } from 'vue'
import Lucide from '@/components/Base/Lucide'
import Litepicker from '@/components/Base/Litepicker/Litepicker.vue'
import TomSelect from '@/components/Base/TomSelect/TomSelect.vue'

const props = defineProps<{
    form: any
    carriers: { id: number; name: string }[]
    drivers: { id: number; carrier_id: number | null; carrier_name?: string | null; name: string; email?: string | null }[]
    states: Record<string, string>
    organizationOptions: Record<string, string>
    existingDocuments?: { id: number; file_name: string; file_type: string; size_label: string; preview_url: string; created_at_display: string | null }[]
}>()

const pickerOptions = { singleMode: true, format: 'M/D/YYYY', autoApply: true }

const filteredDrivers = computed(() => {
    if (!props.form.carrier_id) return props.drivers
    return props.drivers.filter((driver) => String(driver.carrier_id ?? '') === String(props.form.carrier_id))
})

function onDocumentsChange(event: Event) {
    const input = event.target as HTMLInputElement
    props.form.course_documents = Array.from(input.files ?? [])
}
</script>

<template>
    <div class="space-y-6">
        <div class="box box--stacked p-6">
            <h2 class="text-sm font-semibold text-slate-700 mb-5 flex items-center gap-2">
                <Lucide icon="Users" class="w-4 h-4 text-primary" />
                Carrier & Driver
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1.5">Carrier</label>
                    <TomSelect v-model="form.carrier_id">
                        <option value="">Select carrier</option>
                        <option v-for="carrier in carriers" :key="carrier.id" :value="String(carrier.id)">{{ carrier.name }}</option>
                    </TomSelect>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1.5">Driver <span class="text-red-500">*</span></label>
                    <TomSelect v-model="form.user_driver_detail_id">
                        <option value="">Select driver</option>
                        <option v-for="driver in filteredDrivers" :key="driver.id" :value="String(driver.id)">
                            {{ driver.name }}{{ driver.carrier_name ? ` - ${driver.carrier_name}` : '' }}
                        </option>
                    </TomSelect>
                    <p v-if="form.errors.user_driver_detail_id" class="text-red-500 text-xs mt-1">{{ form.errors.user_driver_detail_id }}</p>
                </div>
            </div>
        </div>

        <div class="box box--stacked p-6">
            <h2 class="text-sm font-semibold text-slate-700 mb-5 flex items-center gap-2">
                <Lucide icon="ShieldCheck" class="w-4 h-4 text-primary" />
                Course Details
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1.5">Organization <span class="text-red-500">*</span></label>
                    <TomSelect v-model="form.organization_name">
                        <option value="">Select organization</option>
                        <option v-for="(label, key) in organizationOptions" :key="key" :value="key">{{ label }}</option>
                    </TomSelect>
                    <p v-if="form.errors.organization_name" class="text-red-500 text-xs mt-1">{{ form.errors.organization_name }}</p>
                </div>

                <div v-if="form.organization_name === 'Other'">
                    <label class="block text-xs font-medium text-slate-600 mb-1.5">Custom Organization <span class="text-red-500">*</span></label>
                    <input v-model="form.organization_name_other" type="text" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="Enter organization name" />
                    <p v-if="form.errors.organization_name_other" class="text-red-500 text-xs mt-1">{{ form.errors.organization_name_other }}</p>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1.5">City</label>
                    <input v-model="form.city" type="text" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="Enter city" />
                    <p v-if="form.errors.city" class="text-red-500 text-xs mt-1">{{ form.errors.city }}</p>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1.5">State</label>
                    <TomSelect v-model="form.state">
                        <option value="">Select state</option>
                        <option v-for="(label, code) in states" :key="code" :value="code">{{ label }}</option>
                    </TomSelect>
                    <p v-if="form.errors.state" class="text-red-500 text-xs mt-1">{{ form.errors.state }}</p>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1.5">Certification Date</label>
                    <Litepicker v-model="form.certification_date" :options="pickerOptions" />
                    <p v-if="form.errors.certification_date" class="text-red-500 text-xs mt-1">{{ form.errors.certification_date }}</p>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1.5">Expiration Date</label>
                    <Litepicker v-model="form.expiration_date" :options="pickerOptions" />
                    <p v-if="form.errors.expiration_date" class="text-red-500 text-xs mt-1">{{ form.errors.expiration_date }}</p>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-slate-600 mb-1.5">Experience</label>
                    <input v-model="form.experience" type="text" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="Describe the course or certification focus" />
                    <p v-if="form.errors.experience" class="text-red-500 text-xs mt-1">{{ form.errors.experience }}</p>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1.5">Status <span class="text-red-500">*</span></label>
                    <TomSelect v-model="form.status">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </TomSelect>
                    <p v-if="form.errors.status" class="text-red-500 text-xs mt-1">{{ form.errors.status }}</p>
                </div>
            </div>
        </div>

        <div class="box box--stacked p-6">
            <h2 class="text-sm font-semibold text-slate-700 mb-4 flex items-center gap-2">
                <Lucide icon="Paperclip" class="w-4 h-4 text-primary" />
                Documents
            </h2>

            <div v-if="existingDocuments?.length" class="mb-5">
                <p class="text-xs font-medium text-slate-600 mb-2">Current Documents</p>
                <div class="space-y-2">
                    <div v-for="document in existingDocuments" :key="document.id" class="flex items-center justify-between rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                        <div class="min-w-0">
                            <a :href="document.preview_url" target="_blank" class="block truncate text-sm font-medium text-primary hover:underline">{{ document.file_name }}</a>
                            <p class="text-xs text-slate-500">{{ document.size_label }} - {{ document.created_at_display }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <label class="block text-xs font-medium text-slate-600 mb-1.5">Upload Documents</label>
            <input type="file" multiple accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" @change="onDocumentsChange" />
            <p class="text-xs text-slate-400 mt-2">Accepted: PDF, JPG, PNG, DOC, DOCX. Dates stay in M/D/YYYY format.</p>
            <p v-if="form.errors.course_documents" class="text-red-500 text-xs mt-1">{{ form.errors.course_documents }}</p>
        </div>
    </div>
</template>
