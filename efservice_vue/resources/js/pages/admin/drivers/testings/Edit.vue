<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import Lucide from '@/components/Base/Lucide'
import Button from '@/components/Base/Button'
import Litepicker from '@/components/Base/Litepicker/Litepicker.vue'
import TomSelect from '@/components/Base/TomSelect/TomSelect.vue'
import RazeLayout from '@/layouts/RazeLayout.vue'

declare function route(name: string, params?: any): string

defineOptions({ layout: RazeLayout })

// ─── Types ────────────────────────────────────────────────────────────────────
interface DriverInfo {
    id: number
    full_name: string
    email: string
    phone: string | null
    carrier: { id: number; name: string } | null
    license: { number: string | null; class: string | null; expires: string | null } | null
}

interface ExistingAttachment {
    id: number
    name: string
    url: string
    size: string
}

interface TestingData {
    id: number
    test_type: string
    administered_by: string
    test_date: string | null
    location: string
    requester_name: string
    mro: string | null
    scheduled_time: string | null
    test_result: string | null
    status: string | null
    next_test_due: string | null
    bill_to: string | null
    notes: string | null
    is_random_test: boolean
    is_post_accident_test: boolean
    is_reasonable_suspicion_test: boolean
    is_pre_employment_test: boolean
    is_follow_up_test: boolean
    is_return_to_duty_test: boolean
    is_other_reason_test: boolean
    other_reason_description: string | null
    attachments: ExistingAttachment[]
}

// ─── Props ────────────────────────────────────────────────────────────────────
const props = defineProps<{
    driver: DriverInfo
    drivers?: DriverInfo[]
    testing: TestingData
    testTypes: Record<string, string>
    locations: Record<string, string>
    statuses: Record<string, string>
    testResults: Record<string, string>
    billOptions: Record<string, string>
    administrators: Record<string, string>
    isCarrierContext?: boolean
    routeNames?: {
        index: string
        show: string
        edit: string
        update: string
        driverShow: string
    }
}>()

const lpOptions = { singleMode: true, format: 'M/D/YYYY', autoApply: true }
const availableDrivers = computed(() => props.drivers ?? [props.driver])
const selectedDriverId = ref<string>(String(props.driver.id))
const activeDriver = computed(() => availableDrivers.value.find((driver) => String(driver.id) === selectedDriverId.value) ?? props.driver)
const driver = computed<DriverInfo>(() => activeDriver.value)

// ─── Form state ───────────────────────────────────────────────────────────────
const form = ref({
    user_driver_detail_id: selectedDriverId.value,
    test_type:                    props.testing.test_type,
    administered_by:              props.testing.administered_by,
    administered_by_other:        '',
    test_date:                    props.testing.test_date ?? '',
    location:                     props.testing.location,
    requester_name:               props.testing.requester_name,
    mro:                          props.testing.mro ?? '',
    scheduled_time:               props.testing.scheduled_time ?? '',
    test_result:                  props.testing.test_result ?? '',
    status:                       props.testing.status ?? 'Schedule',
    next_test_due:                props.testing.next_test_due ?? '',
    bill_to:                      props.testing.bill_to ?? '',
    notes:                        props.testing.notes ?? '',
    is_random_test:               props.testing.is_random_test,
    is_post_accident_test:        props.testing.is_post_accident_test,
    is_reasonable_suspicion_test: props.testing.is_reasonable_suspicion_test,
    is_pre_employment_test:       props.testing.is_pre_employment_test,
    is_follow_up_test:            props.testing.is_follow_up_test,
    is_return_to_duty_test:       props.testing.is_return_to_duty_test,
    is_other_reason_test:         props.testing.is_other_reason_test,
    other_reason_description:     props.testing.other_reason_description ?? '',
})

const errors = ref<Record<string, string>>({})
const processing = ref(false)

// Check if administered_by is a custom value (not in the list)
const isOtherAdmin = props.testing.administered_by &&
    !Object.keys(props.administrators).includes(props.testing.administered_by)

if (isOtherAdmin) {
    form.value.administered_by_other = props.testing.administered_by
    form.value.administered_by = 'other'
}

const adminIsOther = computed(() => form.value.administered_by === 'other')

// ─── Attachments ──────────────────────────────────────────────────────────────
const existingAttachments = ref<ExistingAttachment[]>([...props.testing.attachments])
const deleteAttachmentIds = ref<number[]>([])
const newFiles = ref<File[]>([])
const fileInput = ref<HTMLInputElement | null>(null)

function markForDeletion(id: number) {
    deleteAttachmentIds.value.push(id)
    existingAttachments.value = existingAttachments.value.filter(a => a.id !== id)
}

function onFilesChange(e: Event) {
    const input = e.target as HTMLInputElement
    if (input.files) {
        newFiles.value = [...newFiles.value, ...Array.from(input.files)]
    }
}

function removeNewFile(index: number) {
    newFiles.value.splice(index, 1)
}

function formatBytes(bytes: number) {
    if (bytes < 1024) return bytes + ' B'
    if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB'
    return (bytes / 1048576).toFixed(1) + ' MB'
}

// ─── Submit ───────────────────────────────────────────────────────────────────
function submit() {
    processing.value = true
    errors.value = {}
    form.value.user_driver_detail_id = selectedDriverId.value

    const effectiveAdmin = form.value.administered_by === 'other'
        ? form.value.administered_by_other
        : form.value.administered_by

    const data = new FormData()
    data.append('_method', 'PUT')

    const fields = { ...form.value }
    delete (fields as any).administered_by_other

    Object.entries(fields).forEach(([key, value]) => {
        if (key === 'attachments') return
        if (typeof value === 'boolean') {
            data.append(key, value ? '1' : '0')
        } else if (value !== null && value !== undefined && value !== '') {
            data.append(key, String(value))
        }
    })

    data.set('administered_by', effectiveAdmin)

    deleteAttachmentIds.value.forEach(id => {
        data.append('delete_attachments[]', String(id))
    })

    newFiles.value.forEach(file => {
        data.append('attachments[]', file)
    })

    router.post(
        props.isCarrierContext
            ? route(props.routeNames?.update ?? 'carrier.drivers.testings.update', { testing: props.testing.id })
            : route(props.routeNames?.update ?? 'admin.drivers.testings.update', { driver: activeDriver.value.id, testing: props.testing.id }),
        data,
        {
            forceFormData: true,
            onError: (e) => { errors.value = e as Record<string, string> },
            onFinish: () => { processing.value = false },
        }
    )
}

// ─── Reason checkboxes ────────────────────────────────────────────────────────
const testReasonCheckboxes = [
    { key: 'is_random_test',               label: 'Random Test',        color: 'blue'   },
    { key: 'is_post_accident_test',        label: 'Post-Accident Test', color: 'orange' },
    { key: 'is_reasonable_suspicion_test', label: 'Reasonable Suspicion', color: 'red'  },
    { key: 'is_pre_employment_test',       label: 'Pre-Employment',     color: 'green'  },
    { key: 'is_follow_up_test',            label: 'Follow-Up Test',     color: 'purple' },
    { key: 'is_return_to_duty_test',       label: 'Return-To-Duty',     color: 'indigo' },
    { key: 'is_other_reason_test',         label: 'Other Reason',       color: 'gray'   },
] as const
</script>

<template>
    <Head :title="`Edit Test – ${driver.full_name}`" />

    <div class="grid grid-cols-12 gap-y-8 gap-x-6">

        <!-- ══ HEADER ══ -->
        <div class="col-span-12">
            <div class="box box--stacked p-6">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h1 class="text-xl font-bold text-slate-800">Edit Drug/Alcohol Test</h1>
                        <p class="text-sm text-slate-500 mt-0.5">
                            Driver: <span class="font-medium text-slate-700">{{ driver.full_name }}</span>
                            <span v-if="driver.carrier"> · {{ driver.carrier.name }}</span>
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <Link :href="route(props.routeNames?.show ?? 'admin.driver-testings.show', testing.id)">
                            <Button variant="outline-primary" size="sm" class="flex items-center gap-1.5">
                                <Lucide icon="Eye" class="w-4 h-4" /> View Test
                            </Button>
                        </Link>
                        <Link :href="route(props.routeNames?.index ?? 'admin.driver-testings.index')">
                            <Button variant="outline-secondary" size="sm" class="flex items-center gap-1.5">
                                <Lucide icon="List" class="w-4 h-4" /> All Testings
                            </Button>
                        </Link>
                        <Link v-if="driver.id" :href="route(props.routeNames?.driverShow ?? 'admin.drivers.show', driver.id)">
                            <Button variant="outline-secondary" size="sm" class="flex items-center gap-1.5">
                                <Lucide icon="ArrowLeft" class="w-4 h-4" /> Back to Driver
                            </Button>
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <!-- ══ DRIVER INFO SIDEBAR ══ -->
        <div class="col-span-12 lg:col-span-4">
            <div class="box box--stacked p-5 sticky top-4">
                <h2 class="text-sm font-semibold text-slate-700 mb-4 flex items-center gap-2">
                    <Lucide icon="User" class="w-4 h-4 text-primary" /> Driver Information
                </h2>
                <div class="space-y-3">
                    <div class="bg-slate-50 rounded-lg p-3">
                        <p class="text-xs text-slate-500">Full Name</p>
                        <p class="font-medium text-slate-800">{{ driver.full_name }}</p>
                    </div>
                    <div class="bg-slate-50 rounded-lg p-3">
                        <p class="text-xs text-slate-500">Email</p>
                        <p class="font-medium text-slate-800 text-sm truncate">{{ driver.email }}</p>
                    </div>
                    <div class="bg-slate-50 rounded-lg p-3">
                        <p class="text-xs text-slate-500">Phone</p>
                        <p class="font-medium text-slate-800">{{ driver.phone || 'N/A' }}</p>
                    </div>
                    <div v-if="driver.carrier" class="bg-slate-50 rounded-lg p-3">
                        <p class="text-xs text-slate-500">Carrier</p>
                        <p class="font-medium text-slate-800">{{ driver.carrier.name }}</p>
                    </div>
                    <div v-if="driver.license" class="bg-primary/5 border border-primary/20 rounded-lg p-3">
                        <p class="text-xs text-primary font-medium mb-1">License on File</p>
                        <p class="text-sm font-mono text-slate-800">{{ driver.license.number || 'N/A' }}</p>
                        <p class="text-xs text-slate-500">Class {{ driver.license.class || 'N/A' }}</p>
                        <p v-if="driver.license.expires" class="text-xs text-slate-500">Exp: {{ driver.license.expires }}</p>
                    </div>
                </div>

                <!-- Record ID badge -->
                <div class="mt-4 rounded-lg border border-slate-200 p-3 text-center">
                    <p class="text-xs text-slate-500">Record ID</p>
                    <p class="font-mono font-bold text-slate-700">#{{ testing.id }}</p>
                </div>
            </div>
        </div>

        <!-- ══ MAIN FORM ══ -->
        <div class="col-span-12 lg:col-span-8">
            <form @submit.prevent="submit" class="space-y-6">

                <!-- ── Test Details ── -->
                <div class="box box--stacked p-6">
                    <h2 class="text-sm font-semibold text-slate-700 mb-5 flex items-center gap-2">
                        <Lucide icon="ClipboardCheck" class="w-4 h-4 text-primary" /> Test Details
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                        <div v-if="props.isCarrierContext" class="md:col-span-2">
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Driver <span class="text-red-500">*</span></label>
                            <TomSelect v-model="selectedDriverId">
                                <option value="">-- Select driver --</option>
                                <option v-for="option in availableDrivers" :key="option.id" :value="String(option.id)">
                                    {{ option.full_name }}
                                </option>
                            </TomSelect>
                            <p v-if="errors.user_driver_detail_id" class="text-red-500 text-xs mt-1">{{ errors.user_driver_detail_id }}</p>
                        </div>

                        <!-- Test Type -->
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Test Type <span class="text-red-500">*</span></label>
                            <TomSelect v-model="form.test_type">
                                <option value="">-- Select test type --</option>
                                <option v-for="(label, key) in testTypes" :key="key" :value="key">{{ label }}</option>
                            </TomSelect>
                            <p v-if="errors.test_type" class="text-red-500 text-xs mt-1">{{ errors.test_type }}</p>
                        </div>

                        <!-- Administered By -->
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Administered By <span class="text-red-500">*</span></label>
                            <TomSelect v-model="form.administered_by">
                                <option value="">-- Select administrator --</option>
                                <option v-for="(label, key) in administrators" :key="key" :value="key">{{ label }}</option>
                            </TomSelect>
                            <input v-if="adminIsOther" v-model="form.administered_by_other" type="text"
                                placeholder="Please specify"
                                class="mt-2 w-full text-sm border border-slate-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary/30"
                                required />
                            <p v-if="errors.administered_by" class="text-red-500 text-xs mt-1">{{ errors.administered_by }}</p>
                        </div>

                        <!-- Test Date -->
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Test Date <span class="text-red-500">*</span></label>
                            <Litepicker v-model="form.test_date" :options="lpOptions" />
                            <p v-if="errors.test_date" class="text-red-500 text-xs mt-1">{{ errors.test_date }}</p>
                        </div>

                        <!-- Scheduled Time -->
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Scheduled Time</label>
                            <input v-model="form.scheduled_time" type="datetime-local"
                                class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary/30" />
                        </div>

                        <!-- Location -->
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Location <span class="text-red-500">*</span></label>
                            <TomSelect v-model="form.location">
                                <option value="">-- Select location --</option>
                                <option v-for="(label, key) in locations" :key="key" :value="label">{{ label }}</option>
                            </TomSelect>
                            <p v-if="errors.location" class="text-red-500 text-xs mt-1">{{ errors.location }}</p>
                        </div>

                        <!-- Requester Name -->
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Requester Name <span class="text-red-500">*</span></label>
                            <input v-model="form.requester_name" type="text" required placeholder="Full name of requester"
                                class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary/30"
                                :class="errors.requester_name ? 'border-red-400' : ''" />
                            <p v-if="errors.requester_name" class="text-red-500 text-xs mt-1">{{ errors.requester_name }}</p>
                        </div>

                        <!-- MRO -->
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">MRO (Medical Review Officer)</label>
                            <input v-model="form.mro" type="text" placeholder="MRO name or ID"
                                class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary/30" />
                        </div>

                        <!-- Status -->
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Status</label>
                            <TomSelect v-model="form.status">
                                <option v-for="(label, key) in statuses" :key="key" :value="key">{{ label }}</option>
                            </TomSelect>
                        </div>

                        <!-- Test Result -->
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Test Result</label>
                            <TomSelect v-model="form.test_result">
                                <option value="">-- Pending / Not yet --</option>
                                <option v-for="(label, key) in testResults" :key="key" :value="key">{{ label }}</option>
                            </TomSelect>
                        </div>

                        <!-- Next Test Due -->
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Next Test Due Date</label>
                            <Litepicker v-model="form.next_test_due" :options="lpOptions" />
                        </div>

                        <!-- Bill To -->
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Bill To</label>
                            <TomSelect v-model="form.bill_to">
                                <option value="">-- Select billing --</option>
                                <option v-for="(label, key) in billOptions" :key="key" :value="key">{{ label }}</option>
                            </TomSelect>
                        </div>

                    </div>
                </div>

                <!-- ── Test Reason Checkboxes ── -->
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

                <!-- ── Notes & Attachments ── -->
                <div class="box box--stacked p-6">
                    <h2 class="text-sm font-semibold text-slate-700 mb-4 flex items-center gap-2">
                        <Lucide icon="FileText" class="w-4 h-4 text-primary" /> Notes & Attachments
                    </h2>

                    <!-- Notes -->
                    <div class="mb-5">
                        <label class="block text-xs font-medium text-slate-600 mb-1.5">Notes</label>
                        <textarea v-model="form.notes" rows="3" placeholder="Additional notes or instructions..."
                            class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary/30 resize-none"></textarea>
                    </div>

                    <!-- Existing attachments -->
                    <div v-if="existingAttachments.length" class="mb-5">
                        <p class="text-xs font-medium text-slate-600 mb-2">Current Attachments</p>
                        <div class="space-y-2">
                            <div v-for="att in existingAttachments" :key="att.id"
                                class="flex items-center justify-between bg-slate-50 rounded-lg px-3 py-2 border border-slate-200">
                                <div class="flex items-center gap-2 min-w-0">
                                    <Lucide icon="FileText" class="w-4 h-4 text-slate-400 flex-shrink-0" />
                                    <a :href="att.url" target="_blank"
                                        class="text-sm text-primary hover:underline truncate">{{ att.name }}</a>
                                    <span class="text-xs text-slate-400 flex-shrink-0">{{ att.size }}</span>
                                </div>
                                <button type="button" @click="markForDeletion(att.id)"
                                    class="text-red-400 hover:text-red-600 flex-shrink-0 ml-2" title="Remove attachment">
                                    <Lucide icon="Trash2" class="w-4 h-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                    <div v-else-if="testing.attachments.length > 0" class="mb-5">
                        <p class="text-xs text-slate-400 italic">All existing attachments marked for deletion.</p>
                    </div>

                    <!-- New file upload -->
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1.5">Add New Attachments</label>
                        <div class="border-2 border-dashed border-slate-200 rounded-lg p-5 text-center hover:border-primary/40 transition cursor-pointer"
                            @click="fileInput?.click()">
                            <Lucide icon="Upload" class="w-7 h-7 text-slate-400 mx-auto mb-2" />
                            <p class="text-sm text-slate-500">Click to upload or drag & drop</p>
                            <p class="text-xs text-slate-400 mt-1">PDF, JPG, PNG, DOC, DOCX (max 10MB each)</p>
                        </div>
                        <input ref="fileInput" type="file" multiple accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                            class="hidden" @change="onFilesChange" />

                        <div v-if="newFiles.length" class="mt-3 space-y-2">
                            <div v-for="(file, i) in newFiles" :key="i"
                                class="flex items-center justify-between bg-primary/10 rounded-lg px-3 py-2 border border-primary/20">
                                <div class="flex items-center gap-2 min-w-0">
                                    <Lucide icon="FileCheck" class="w-4 h-4 text-primary flex-shrink-0" />
                                    <span class="text-sm text-slate-700 truncate">{{ file.name }}</span>
                                    <span class="text-xs text-slate-400 flex-shrink-0">{{ formatBytes(file.size) }}</span>
                                </div>
                                <button type="button" @click="removeNewFile(i)"
                                    class="text-red-400 hover:text-red-600 flex-shrink-0 ml-2">
                                    <Lucide icon="X" class="w-4 h-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ── Actions ── -->
                <div class="flex justify-end gap-3">
                    <Link v-if="driver.id" :href="route(props.routeNames?.driverShow ?? 'admin.drivers.show', driver.id)">
                        <Button variant="outline-secondary" type="button" class="flex items-center gap-1.5">
                            <Lucide icon="X" class="w-4 h-4" /> Cancel
                        </Button>
                    </Link>
                    <Button type="submit" variant="primary" :disabled="processing" class="flex items-center gap-1.5">
                        <Lucide icon="Save" class="w-4 h-4" />
                        {{ processing ? 'Saving...' : 'Save Changes' }}
                    </Button>
                </div>

            </form>
        </div>
    </div>
</template>
