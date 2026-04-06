<script setup lang="ts">
import { computed } from 'vue'
import Lucide from '@/components/Base/Lucide'
import Litepicker from '@/components/Base/Litepicker/Litepicker.vue'
import TomSelect from '@/components/Base/TomSelect/TomSelect.vue'

const props = defineProps<{
    form: any
    trainings: { id: number; title: string }[]
    carriers: { id: number; name: string }[]
    drivers: { id: number; carrier_id: number | null; carrier_name?: string | null; name: string; email?: string | null }[]
    trainingLocked?: boolean
}>()

const pickerOptions = { singleMode: true, format: 'M/D/YYYY', autoApply: true }

const filteredDrivers = computed(() => {
    if (!props.form.carrier_id) return props.drivers
    return props.drivers.filter((driver) => String(driver.carrier_id ?? '') === String(props.form.carrier_id))
})
</script>

<template>
    <div class="space-y-6">
        <div class="box box--stacked p-6">
            <h2 class="text-sm font-semibold text-slate-700 mb-5 flex items-center gap-2">
                <Lucide icon="ClipboardList" class="w-4 h-4 text-primary" />
                Assignment Details
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1.5">Training <span class="text-red-500">*</span></label>
                    <TomSelect v-model="form.training_id" :disabled="trainingLocked">
                        <option value="">Select training</option>
                        <option v-for="training in trainings" :key="training.id" :value="String(training.id)">{{ training.title }}</option>
                    </TomSelect>
                    <p v-if="form.errors.training_id" class="text-red-500 text-xs mt-1">{{ form.errors.training_id }}</p>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1.5">Carrier</label>
                    <TomSelect v-model="form.carrier_id">
                        <option value="">All carriers</option>
                        <option v-for="carrier in carriers" :key="carrier.id" :value="String(carrier.id)">{{ carrier.name }}</option>
                    </TomSelect>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-slate-600 mb-1.5">Drivers <span class="text-red-500">*</span></label>
                    <TomSelect v-model="form.driver_ids" multiple>
                        <option v-for="driver in filteredDrivers" :key="driver.id" :value="String(driver.id)">
                            {{ driver.name }}{{ driver.carrier_name ? ` · ${driver.carrier_name}` : '' }}
                        </option>
                    </TomSelect>
                    <p class="text-xs text-slate-400 mt-2">You can select multiple drivers.</p>
                    <p v-if="form.errors.driver_ids" class="text-red-500 text-xs mt-1">{{ form.errors.driver_ids }}</p>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1.5">Due Date</label>
                    <Litepicker v-model="form.due_date" :options="pickerOptions" />
                    <p v-if="form.errors.due_date" class="text-red-500 text-xs mt-1">{{ form.errors.due_date }}</p>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1.5">Initial Status <span class="text-red-500">*</span></label>
                    <TomSelect v-model="form.status">
                        <option value="assigned">Assigned</option>
                        <option value="in_progress">In Progress</option>
                        <option value="completed">Completed</option>
                    </TomSelect>
                    <p v-if="form.errors.status" class="text-red-500 text-xs mt-1">{{ form.errors.status }}</p>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-slate-600 mb-1.5">Notes</label>
                    <textarea v-model="form.notes" rows="4" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm resize-none" placeholder="Optional assignment notes"></textarea>
                    <p v-if="form.errors.notes" class="text-red-500 text-xs mt-1">{{ form.errors.notes }}</p>
                </div>
            </div>
        </div>
    </div>
</template>
