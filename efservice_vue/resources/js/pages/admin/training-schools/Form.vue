<script setup lang="ts">
import { computed, watch } from 'vue'
import { FormInput } from '@/components/Base/Form'
import Lucide from '@/components/Base/Lucide'
import Litepicker from '@/components/Base/Litepicker/Litepicker.vue'
import TomSelect from '@/components/Base/TomSelect/TomSelect.vue'

const props = withDefaults(defineProps<{
    form: any
    carriers: { id: number; name: string }[]
    drivers: { id: number; carrier_id: number | null; carrier_name?: string | null; name: string; email?: string | null }[]
    states: Record<string, string>
    skillOptions: Record<string, string>
    existingDocuments?: { id: number; file_name: string; file_type: string; size_label: string; preview_url: string; created_at_display: string | null }[]
    carrier?: { id: number; name: string } | null
    isCarrierContext?: boolean
}>(), {
    existingDocuments: () => [],
    carrier: null,
    isCarrierContext: false,
})

const pickerOptions = { singleMode: true, format: 'M/D/YYYY', autoApply: true }

const filteredDrivers = computed(() => {
    if (!props.form.carrier_id) return props.drivers
    return props.drivers.filter((driver) => String(driver.carrier_id ?? '') === String(props.form.carrier_id))
})

watch(() => props.carrier, (carrier) => {
    if (props.isCarrierContext && carrier) {
        props.form.carrier_id = String(carrier.id)
    }
}, { immediate: true })

function onDocumentsChange(event: Event) {
    const input = event.target as HTMLInputElement
    props.form.training_documents = Array.from(input.files ?? [])
}
</script>

<template>
    <div class="space-y-6">
        <div class="box box--stacked p-6">
            <h2 class="text-sm font-semibold text-slate-700 mb-5 flex items-center gap-2">
                <Lucide icon="Users" class="w-4 h-4 text-primary" />
                Carrier & Driver Information
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div v-if="!props.isCarrierContext">
                    <label class="block text-xs font-medium text-slate-600 mb-1.5">Carrier <span class="text-red-500">*</span></label>
                    <TomSelect v-model="form.carrier_id">
                        <option value="">Select carrier</option>
                        <option v-for="carrier in carriers" :key="carrier.id" :value="String(carrier.id)">{{ carrier.name }}</option>
                    </TomSelect>
                    <p v-if="form.errors.carrier_id" class="text-red-500 text-xs mt-1">{{ form.errors.carrier_id }}</p>
                </div>

                <div v-else class="rounded-lg border border-primary/20 bg-primary/5 px-4 py-3">
                    <p class="text-xs font-medium uppercase tracking-[0.2em] text-primary/70">Carrier</p>
                    <p class="mt-1 font-semibold text-slate-800">{{ props.carrier?.name ?? 'Current carrier' }}</p>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1.5">Driver <span class="text-red-500">*</span></label>
                    <TomSelect v-model="form.user_driver_detail_id">
                        <option value="">Select driver</option>
                        <option v-for="driver in filteredDrivers" :key="driver.id" :value="String(driver.id)">
                            {{ driver.name }}{{ !props.isCarrierContext && driver.carrier_name ? ` - ${driver.carrier_name}` : '' }}
                        </option>
                    </TomSelect>
                    <p v-if="form.errors.user_driver_detail_id" class="text-red-500 text-xs mt-1">{{ form.errors.user_driver_detail_id }}</p>
                </div>
            </div>
        </div>

        <div class="box box--stacked p-6">
            <h2 class="text-sm font-semibold text-slate-700 mb-5 flex items-center gap-2">
                <Lucide icon="GraduationCap" class="w-4 h-4 text-primary" />
                School Details
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1.5">School Name <span class="text-red-500">*</span></label>
                    <FormInput v-model="form.school_name" type="text" placeholder="Enter school name" />
                    <p v-if="form.errors.school_name" class="text-red-500 text-xs mt-1">{{ form.errors.school_name }}</p>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1.5">City <span class="text-red-500">*</span></label>
                    <FormInput v-model="form.city" type="text" placeholder="Enter city" />
                    <p v-if="form.errors.city" class="text-red-500 text-xs mt-1">{{ form.errors.city }}</p>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1.5">State <span class="text-red-500">*</span></label>
                    <TomSelect v-model="form.state">
                        <option value="">Select state</option>
                        <option v-for="(label, code) in states" :key="code" :value="code">{{ label }}</option>
                    </TomSelect>
                    <p v-if="form.errors.state" class="text-red-500 text-xs mt-1">{{ form.errors.state }}</p>
                </div>
            </div>
        </div>

        <div class="box box--stacked p-6">
            <h2 class="text-sm font-semibold text-slate-700 mb-5 flex items-center gap-2">
                <Lucide icon="CalendarRange" class="w-4 h-4 text-primary" />
                Training Period
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1.5">Start Date <span class="text-red-500">*</span></label>
                    <Litepicker v-model="form.date_start" :options="pickerOptions" />
                    <p v-if="form.errors.date_start" class="text-red-500 text-xs mt-1">{{ form.errors.date_start }}</p>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1.5">End Date <span class="text-red-500">*</span></label>
                    <Litepicker v-model="form.date_end" :options="pickerOptions" />
                    <p v-if="form.errors.date_end" class="text-red-500 text-xs mt-1">{{ form.errors.date_end }}</p>
                </div>
            </div>
        </div>

        <div class="box box--stacked p-6">
            <h2 class="text-sm font-semibold text-slate-700 mb-4 flex items-center gap-2">
                <Lucide icon="ShieldCheck" class="w-4 h-4 text-primary" />
                Status & Safety
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <label class="flex items-center gap-3 rounded-lg border border-slate-200 p-3 cursor-pointer transition hover:border-primary/30">
                    <input v-model="form.graduated" type="checkbox" class="rounded border-slate-300 text-primary focus:ring-primary/30" />
                    <span class="text-sm text-slate-700">Graduated</span>
                </label>
                <label class="flex items-center gap-3 rounded-lg border border-slate-200 p-3 cursor-pointer transition hover:border-primary/30">
                    <input v-model="form.subject_to_safety_regulations" type="checkbox" class="rounded border-slate-300 text-primary focus:ring-primary/30" />
                    <span class="text-sm text-slate-700">Subject to Safety Regulations</span>
                </label>
                <label class="flex items-center gap-3 rounded-lg border border-slate-200 p-3 cursor-pointer transition hover:border-primary/30">
                    <input v-model="form.performed_safety_functions" type="checkbox" class="rounded border-slate-300 text-primary focus:ring-primary/30" />
                    <span class="text-sm text-slate-700">Performed Safety Functions</span>
                </label>
            </div>
        </div>

        <div class="box box--stacked p-6">
            <h2 class="text-sm font-semibold text-slate-700 mb-4 flex items-center gap-2">
                <Lucide icon="Award" class="w-4 h-4 text-primary" />
                Training Skills
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                <label
                    v-for="(label, key) in skillOptions"
                    :key="key"
                    class="flex items-center gap-3 p-3 rounded-lg border cursor-pointer transition"
                    :class="form.training_skills.includes(key) ? 'border-primary bg-primary/5' : 'border-slate-200 hover:border-slate-300'"
                >
                    <input v-model="form.training_skills" :value="key" type="checkbox" class="rounded border-slate-300 text-primary focus:ring-primary/30" />
                    <span class="text-sm font-medium text-slate-700">{{ label }}</span>
                </label>
            </div>
            <p v-if="form.errors.training_skills" class="text-red-500 text-xs mt-2">{{ form.errors.training_skills }}</p>
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
            <p class="text-xs text-slate-400 mt-2">Accepted: PDF, JPG, PNG, DOC, DOCX. Dates should stay in M/D/YYYY format.</p>
            <p v-if="form.errors.training_documents" class="text-red-500 text-xs mt-1">{{ form.errors.training_documents }}</p>
        </div>
    </div>
</template>
