<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, computed, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import Lucide from '@/components/Base/Lucide'
import Button from '@/components/Base/Button'
import Litepicker from '@/components/Base/Litepicker/Litepicker.vue'
import TomSelect from '@/components/Base/TomSelect/TomSelect.vue'
import RazeLayout from '@/layouts/RazeLayout.vue'

declare function route(name: string, params?: any): string

defineOptions({ layout: RazeLayout })

interface DriverInfo {
    id: number
    full_name: string
    email: string
    phone: string | null
    carrier: { id: number; name: string } | null
    license: { number: string | null; class: string | null; expires: string | null } | null
}

const props = defineProps<{
    drivers: DriverInfo[]
    carriers: { id: number; name: string }[]
    testTypes: Record<string, string>
    locations: Record<string, string>
    statuses: Record<string, string>
    testResults: Record<string, string>
    billOptions: Record<string, string>
    administrators: Record<string, string>
}>()

const lpOptions = { singleMode: true, format: 'M/D/YYYY', autoApply: true }

// ─── Carrier / Driver cascade ──────────────────────────────────────────────────
const selectedCarrierId = ref<string>('')
const selectedDriverId  = ref<string>('')

const filteredDrivers = computed(() =>
    selectedCarrierId.value
        ? props.drivers.filter(d => String(d.carrier?.id) === selectedCarrierId.value)
        : props.drivers
)

watch(selectedCarrierId, () => {
    selectedDriverId.value = ''
})

const activeDriver = computed<DriverInfo | null>(() =>
    props.drivers.find(d => String(d.id) === selectedDriverId.value) ?? null
)

// ─── Form ──────────────────────────────────────────────────────────────────────
const form = useForm({
    user_driver_detail_id: '',
    test_type: '',
    administered_by: '',
    administered_by_other: '',
    test_date: `${new Date().getMonth() + 1}/${new Date().getDate()}/${new Date().getFullYear()}`,
    location: '',
    requester_name: '',
    mro: '',
    scheduled_time: '',
    test_result: '',
    status: 'Schedule',
    next_test_due: '',
    bill_to: '',
    notes: '',
    is_random_test: false,
    is_post_accident_test: false,
    is_reasonable_suspicion_test: false,
    is_pre_employment_test: false,
    is_follow_up_test: false,
    is_return_to_duty_test: false,
    is_other_reason_test: false,
    other_reason_description: '',
    attachments: [] as File[],
})

const adminIsOther = computed(() => form.administered_by === 'other')

const fileInput = ref<HTMLInputElement | null>(null)
const selectedFiles = ref<File[]>([])

function onFilesChange(e: Event) {
    const input = e.target as HTMLInputElement
    if (input.files) {
        selectedFiles.value = Array.from(input.files)
        form.attachments = selectedFiles.value
    }
}

function removeFile(index: number) {
    selectedFiles.value.splice(index, 1)
    form.attachments = [...selectedFiles.value]
}

function formatBytes(bytes: number) {
    if (bytes < 1024) return bytes + ' B'
    if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB'
    return (bytes / 1048576).toFixed(1) + ' MB'
}

function submit() {
    if (!selectedDriverId.value) return

    const effectiveAdmin = form.administered_by === 'other' ? form.administered_by_other : form.administered_by

    const data = new FormData()
    Object.entries(form.data()).forEach(([key, value]) => {
        if (key === 'attachments' || key === 'administered_by_other') return
        if (key === 'user_driver_detail_id') {
            data.append(key, selectedDriverId.value)
            return
        }
        if (typeof value === 'boolean') {
            data.append(key, value ? '1' : '0')
        } else if (value !== null && value !== undefined && value !== '') {
            data.append(key, String(value))
        }
    })
    data.set('administered_by', effectiveAdmin)
    selectedFiles.value.forEach(file => data.append('attachments[]', file))

    router.post(route('admin.driver-testings.store'), data, {
        forceFormData: true,
        onError: (errors) => { form.setError(errors as any) },
    })
}

const testReasonCheckboxes = [
    { key: 'is_random_test',               label: 'Random Test',          color: 'blue'   },
    { key: 'is_post_accident_test',        label: 'Post-Accident Test',   color: 'orange' },
    { key: 'is_reasonable_suspicion_test', label: 'Reasonable Suspicion', color: 'red'    },
    { key: 'is_pre_employment_test',       label: 'Pre-Employment',       color: 'green'  },
    { key: 'is_follow_up_test',            label: 'Follow-Up Test',       color: 'purple' },
    { key: 'is_return_to_duty_test',       label: 'Return-To-Duty',       color: 'indigo' },
    { key: 'is_other_reason_test',         label: 'Other Reason',         color: 'gray'   },
] as const
</script>

<template>
    <Head title="Schedule Drug/Alcohol Test" />

    <div class="grid grid-cols-12 gap-y-8 gap-x-6">

        <!-- HEADER -->
        <div class="col-span-12">
            <div class="box box--stacked p-6">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h1 class="text-xl font-bold text-slate-800">Schedule Drug/Alcohol Test</h1>
                        <p class="text-sm text-slate-500 mt-0.5">Create a new test record for any driver</p>
                    </div>
                    <Link :href="route('admin.driver-testings.index')">
                        <Button variant="outline-secondary" size="sm" class="flex items-center gap-1.5">
                            <Lucide icon="ArrowLeft" class="w-4 h-4" /> Back to List
                        </Button>
                    </Link>
                </div>
            </div>
        </div>

        <!-- DRIVER SELECTOR + INFO -->
        <div class="col-span-12 lg:col-span-4">
            <div class="box box--stacked p-5 sticky top-4 space-y-4">

                <!-- Carrier selector -->
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1.5">
                        Filter by Carrier
                    </label>
                    <TomSelect v-model="selectedCarrierId" placeholder="All carriers">
                        <option value="">— All carriers —</option>
                        <option v-for="c in carriers" :key="c.id" :value="String(c.id)">
                            {{ c.name }}
                        </option>
                    </TomSelect>
                </div>

                <!-- Driver selector -->
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1.5">
                        Driver <span class="text-red-500">*</span>
                    </label>
                    <TomSelect v-model="selectedDriverId">
                        <option value="">— Select driver —</option>
                        <option v-for="d in filteredDrivers" :key="d.id" :value="String(d.id)">
                            {{ d.full_name }}
                            <template v-if="d.carrier"> · {{ d.carrier.name }}</template>
                        </option>
                    </TomSelect>
                    <p v-if="form.errors.user_driver_detail_id" class="text-red-500 text-xs mt-1">
                        {{ form.errors.user_driver_detail_id }}
                    </p>
                </div>

                <!-- Driver info card -->
                <template v-if="activeDriver">
                    <div class="border-t border-slate-100 pt-4 space-y-3">
                        <h2 class="text-sm font-semibold text-slate-700 flex items-center gap-2">
                            <Lucide icon="User" class="w-4 h-4 text-primary" /> Driver Information
                        </h2>
                        <div class="bg-slate-50 rounded-lg p-3">
                            <p class="text-xs text-slate-500">Full Name</p>
                            <p class="font-medium text-slate-800">{{ activeDriver.full_name }}</p>
                        </div>
                        <div class="bg-slate-50 rounded-lg p-3">
                            <p class="text-xs text-slate-500">Email</p>
                            <p class="font-medium text-slate-800 text-sm truncate">{{ activeDriver.email || 'N/A' }}</p>
                        </div>
                        <div class="bg-slate-50 rounded-lg p-3">
                            <p class="text-xs text-slate-500">Phone</p>
                            <p class="font-medium text-slate-800">{{ activeDriver.phone || 'N/A' }}</p>
                        </div>
                        <div v-if="activeDriver.carrier" class="bg-slate-50 rounded-lg p-3">
                            <p class="text-xs text-slate-500">Carrier</p>
                            <p class="font-medium text-slate-800">{{ activeDriver.carrier.name }}</p>
                        </div>
                        <div v-if="activeDriver.license" class="bg-primary/5 border border-primary/20 rounded-lg p-3">
                            <p class="text-xs text-primary font-medium mb-1">License on File</p>
                            <p class="text-sm font-mono text-slate-800">{{ activeDriver.license.number || 'N/A' }}</p>
                            <p class="text-xs text-slate-500">Class {{ activeDriver.license.class || 'N/A' }}</p>
                            <p v-if="activeDriver.license.expires" class="text-xs text-slate-500">Exp: {{ activeDriver.license.expires }}</p>
                        </div>
                    </div>
                </template>
                <div v-else class="border-t border-slate-100 pt-4 text-center text-sm text-slate-400 py-6">
                    <Lucide icon="User" class="w-8 h-8 mx-auto mb-2 text-slate-300" />
                    Select a driver to see their info
                </div>

            </div>
        </div>

        <!-- MAIN FORM -->
        <div class="col-span-12 lg:col-span-8">
            <form @submit.prevent="submit" class="space-y-6">

                <!-- Test Details -->
                <div class="box box--stacked p-6">
                    <h2 class="text-sm font-semibold text-slate-700 mb-5 flex items-center gap-2">
                        <Lucide icon="ClipboardCheck" class="w-4 h-4 text-primary" /> Test Details
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Test Type <span class="text-red-500">*</span></label>
                            <TomSelect v-model="form.test_type">
                                <option value="">— Select test type —</option>
                                <option v-for="(label, key) in testTypes" :key="key" :value="key">{{ label }}</option>
                            </TomSelect>
                            <p v-if="form.errors.test_type" class="text-red-500 text-xs mt-1">{{ form.errors.test_type }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Administered By <span class="text-red-500">*</span></label>
                            <TomSelect v-model="form.administered_by">
                                <option value="">— Select administrator —</option>
                                <option v-for="(label, key) in administrators" :key="key" :value="key">{{ label }}</option>
                            </TomSelect>
                            <input v-if="adminIsOther" v-model="form.administered_by_other" type="text"
                                placeholder="Please specify"
                                class="mt-2 w-full text-sm border border-slate-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary/30" required />
                            <p v-if="form.errors.administered_by" class="text-red-500 text-xs mt-1">{{ form.errors.administered_by }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Test Date <span class="text-red-500">*</span></label>
                            <Litepicker v-model="form.test_date" :options="lpOptions" />
                            <p v-if="form.errors.test_date" class="text-red-500 text-xs mt-1">{{ form.errors.test_date }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Scheduled Time</label>
                            <input v-model="form.scheduled_time" type="datetime-local"
                                class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary/30" />
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Location <span class="text-red-500">*</span></label>
                            <TomSelect v-model="form.location">
                                <option value="">— Select location —</option>
                                <option v-for="(label, key) in locations" :key="key" :value="label">{{ label }}</option>
                            </TomSelect>
                            <p v-if="form.errors.location" class="text-red-500 text-xs mt-1">{{ form.errors.location }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Requester Name <span class="text-red-500">*</span></label>
                            <input v-model="form.requester_name" type="text" required placeholder="Full name of requester"
                                class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary/30"
                                :class="form.errors.requester_name ? 'border-red-400' : ''" />
                            <p v-if="form.errors.requester_name" class="text-red-500 text-xs mt-1">{{ form.errors.requester_name }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">MRO (Medical Review Officer)</label>
                            <input v-model="form.mro" type="text" placeholder="MRO name or ID"
                                class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary/30" />
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Status</label>
                            <TomSelect v-model="form.status">
                                <option v-for="(label, key) in statuses" :key="key" :value="key">{{ label }}</option>
                            </TomSelect>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Test Result</label>
                            <TomSelect v-model="form.test_result">
                                <option value="">— Select result —</option>
                                <option v-for="(label, key) in testResults" :key="key" :value="key">{{ label }}</option>
                            </TomSelect>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Next Test Due Date</label>
                            <Litepicker v-model="form.next_test_due" :options="lpOptions" />
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Bill To</label>
                            <TomSelect v-model="form.bill_to">
                                <option value="">— Select billing —</option>
                                <option v-for="(label, key) in billOptions" :key="key" :value="key">{{ label }}</option>
                            </TomSelect>
                        </div>

                    </div>
                </div>

                <!-- Test Reason -->
                <div class="box box--stacked p-6">
                    <h2 class="text-sm font-semibold text-slate-700 mb-4 flex items-center gap-2">
                        <Lucide icon="CheckSquare" class="w-4 h-4 text-primary" />
                        Test Reason <span class="text-red-500 text-xs ml-1">(select at least one)</span>
                    </h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                        <label v-for="cb in testReasonCheckboxes" :key="cb.key"
                            class="flex items-center gap-3 p-3 rounded-lg border cursor-pointer transition"
                            :class="(form as any)[cb.key] ? 'border-primary bg-primary/5' : 'border-slate-200 hover:border-slate-300'">
                            <input type="checkbox" v-model="(form as any)[cb.key]" class="w-4 h-4 rounded text-primary" />
                            <span class="text-sm font-medium text-slate-700">{{ cb.label }}</span>
                        </label>
                    </div>
                    <div v-if="form.is_other_reason_test" class="mt-4">
                        <label class="block text-xs font-medium text-slate-600 mb-1.5">Describe Other Reason</label>
                        <input v-model="form.other_reason_description" type="text" placeholder="Specify other reason"
                            class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary/30" />
                    </div>
                </div>

                <!-- Notes & Attachments -->
                <div class="box box--stacked p-6">
                    <h2 class="text-sm font-semibold text-slate-700 mb-4 flex items-center gap-2">
                        <Lucide icon="FileText" class="w-4 h-4 text-primary" /> Notes & Attachments
                    </h2>
                    <div class="mb-4">
                        <label class="block text-xs font-medium text-slate-600 mb-1.5">Notes</label>
                        <textarea v-model="form.notes" rows="3" placeholder="Additional notes or instructions..."
                            class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary/30 resize-none"></textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1.5">Upload Attachments</label>
                        <div class="border-2 border-dashed border-slate-200 rounded-lg p-6 text-center hover:border-primary/40 transition cursor-pointer"
                            @click="fileInput?.click()">
                            <Lucide icon="Upload" class="w-8 h-8 text-slate-400 mx-auto mb-2" />
                            <p class="text-sm text-slate-500">Click to upload or drag & drop</p>
                            <p class="text-xs text-slate-400 mt-1">PDF, JPG, PNG, DOC, DOCX (max 10MB each)</p>
                        </div>
                        <input ref="fileInput" type="file" multiple accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                            class="hidden" @change="onFilesChange" />
                        <div v-if="selectedFiles.length" class="mt-3 space-y-2">
                            <div v-for="(file, i) in selectedFiles" :key="i"
                                class="flex items-center justify-between bg-slate-50 rounded-lg px-3 py-2 border border-slate-200">
                                <div class="flex items-center gap-2 min-w-0">
                                    <Lucide icon="FileText" class="w-4 h-4 text-slate-400 flex-shrink-0" />
                                    <span class="text-sm text-slate-700 truncate">{{ file.name }}</span>
                                    <span class="text-xs text-slate-400 flex-shrink-0">{{ formatBytes(file.size) }}</span>
                                </div>
                                <button type="button" @click="removeFile(i)" class="text-red-400 hover:text-red-600 flex-shrink-0 ml-2">
                                    <Lucide icon="X" class="w-4 h-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex justify-end gap-3">
                    <Link :href="route('admin.driver-testings.index')">
                        <Button variant="outline-secondary" type="button" class="flex items-center gap-1.5">
                            <Lucide icon="X" class="w-4 h-4" /> Cancel
                        </Button>
                    </Link>
                    <Button type="submit" variant="primary" :disabled="form.processing || !selectedDriverId"
                        class="flex items-center gap-1.5">
                        <Lucide icon="Save" class="w-4 h-4" />
                        {{ form.processing ? 'Saving...' : 'Create Test Record' }}
                    </Button>
                </div>

            </form>
        </div>

    </div>
</template>
