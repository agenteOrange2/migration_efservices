<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import Lucide from '@/components/Base/Lucide'
import Button from '@/components/Base/Button'
import RazeLayout from '@/layouts/RazeLayout.vue'

declare function route(name: string, params?: any): string

defineOptions({ layout: RazeLayout })

// ─── Props ────────────────────────────────────────────────────────────────────
interface DriverInfo {
    id: number
    full_name: string
    email: string
    phone: string | null
    carrier: { id: number; name: string } | null
    license: { number: string | null; class: string | null; expires: string | null } | null
}

const props = defineProps<{
    driver: DriverInfo
    testTypes: Record<string, string>
    locations: Record<string, string>
    statuses: Record<string, string>
    testResults: Record<string, string>
    billOptions: Record<string, string>
    administrators: Record<string, string>
}>()

// ─── Form ─────────────────────────────────────────────────────────────────────
const form = useForm({
    test_type: '',
    administered_by: '',
    administered_by_other: '',
    test_date: new Date().toISOString().slice(0, 10),
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
    const effectiveAdmin = form.administered_by === 'other' ? form.administered_by_other : form.administered_by

    const data = new FormData()
    Object.entries(form.data()).forEach(([key, value]) => {
        if (key === 'attachments' || key === 'administered_by_other') return
        if (typeof value === 'boolean') {
            data.append(key, value ? '1' : '0')
        } else if (value !== null && value !== undefined && value !== '') {
            data.append(key, String(value))
        }
    })
    data.set('administered_by', effectiveAdmin)
    selectedFiles.value.forEach(file => data.append('attachments[]', file))

    router.post(route('admin.drivers.testings.store', props.driver.id), data, {
        forceFormData: true,
        onError: (errors) => { form.setError(errors as any) },
    })
}

const testReasonCheckboxes = [
    { key: 'is_random_test',               label: 'Random Test',               color: 'blue'   },
    { key: 'is_post_accident_test',         label: 'Post-Accident Test',        color: 'orange' },
    { key: 'is_reasonable_suspicion_test',  label: 'Reasonable Suspicion',      color: 'red'    },
    { key: 'is_pre_employment_test',        label: 'Pre-Employment',            color: 'green'  },
    { key: 'is_follow_up_test',             label: 'Follow-Up Test',            color: 'purple' },
    { key: 'is_return_to_duty_test',        label: 'Return-To-Duty',            color: 'indigo' },
    { key: 'is_other_reason_test',          label: 'Other Reason',              color: 'gray'   },
] as const
</script>

<template>
    <Head :title="`Add Drug Test – ${driver.full_name}`" />

    <div class="grid grid-cols-12 gap-y-8 gap-x-6">

        <!-- ═══ HEADER ═══ -->
        <div class="col-span-12">
            <div class="box box--stacked p-6">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h1 class="text-xl font-bold text-slate-800">Schedule Drug/Alcohol Test</h1>
                        <p class="text-sm text-slate-500 mt-0.5">
                            Driver: <span class="font-medium text-slate-700">{{ driver.full_name }}</span>
                            <span v-if="driver.carrier"> · {{ driver.carrier.name }}</span>
                        </p>
                    </div>
                    <Link :href="route('admin.drivers.show', driver.id)">
                        <Button variant="outline-secondary" size="sm" class="flex items-center gap-1.5">
                            <Lucide icon="ArrowLeft" class="w-4 h-4" /> Back to Driver
                        </Button>
                    </Link>
                </div>
            </div>
        </div>

        <!-- ═══ DRIVER INFO SIDEBAR ═══ -->
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
            </div>
        </div>

        <!-- ═══ MAIN FORM ═══ -->
        <div class="col-span-12 lg:col-span-8">
            <form @submit.prevent="submit" class="space-y-6">

                <!-- ── Test Details Card ── -->
                <div class="box box--stacked p-6">
                    <h2 class="text-sm font-semibold text-slate-700 mb-5 flex items-center gap-2">
                        <Lucide icon="ClipboardCheck" class="w-4 h-4 text-primary" /> Test Details
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                        <!-- Test Type -->
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Test Type <span class="text-red-500">*</span></label>
                            <select v-model="form.test_type" required
                                class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary/30"
                                :class="form.errors.test_type ? 'border-red-400' : ''">
                                <option value="">-- Select test type --</option>
                                <option v-for="(label, key) in testTypes" :key="key" :value="key">{{ label }}</option>
                            </select>
                            <p v-if="form.errors.test_type" class="text-red-500 text-xs mt-1">{{ form.errors.test_type }}</p>
                        </div>

                        <!-- Administered By -->
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Administered By <span class="text-red-500">*</span></label>
                            <select v-model="form.administered_by" required
                                class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary/30"
                                :class="form.errors.administered_by ? 'border-red-400' : ''">
                                <option value="">-- Select administrator --</option>
                                <option v-for="(label, key) in administrators" :key="key" :value="key">{{ label }}</option>
                            </select>
                            <input v-if="adminIsOther" v-model="form.administered_by_other" type="text" placeholder="Please specify"
                                class="mt-2 w-full text-sm border border-slate-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary/30" required />
                            <p v-if="form.errors.administered_by" class="text-red-500 text-xs mt-1">{{ form.errors.administered_by }}</p>
                        </div>

                        <!-- Test Date -->
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Test Date <span class="text-red-500">*</span></label>
                            <input v-model="form.test_date" type="date" required
                                class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary/30"
                                :class="form.errors.test_date ? 'border-red-400' : ''" />
                            <p v-if="form.errors.test_date" class="text-red-500 text-xs mt-1">{{ form.errors.test_date }}</p>
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
                            <select v-model="form.location" required
                                class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary/30"
                                :class="form.errors.location ? 'border-red-400' : ''">
                                <option value="">-- Select location --</option>
                                <option v-for="(label, key) in locations" :key="key" :value="label">{{ label }}</option>
                            </select>
                            <p v-if="form.errors.location" class="text-red-500 text-xs mt-1">{{ form.errors.location }}</p>
                        </div>

                        <!-- Requester Name -->
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Requester Name <span class="text-red-500">*</span></label>
                            <input v-model="form.requester_name" type="text" required placeholder="Full name of requester"
                                class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary/30"
                                :class="form.errors.requester_name ? 'border-red-400' : ''" />
                            <p v-if="form.errors.requester_name" class="text-red-500 text-xs mt-1">{{ form.errors.requester_name }}</p>
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
                            <select v-model="form.status"
                                class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary/30">
                                <option v-for="(label, key) in statuses" :key="key" :value="key">{{ label }}</option>
                            </select>
                        </div>

                        <!-- Test Result -->
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Test Result</label>
                            <select v-model="form.test_result"
                                class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary/30">
                                <option value="">-- Select result --</option>
                                <option v-for="(label, key) in testResults" :key="key" :value="key">{{ label }}</option>
                            </select>
                        </div>

                        <!-- Next Test Due -->
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Next Test Due Date</label>
                            <input v-model="form.next_test_due" type="date"
                                class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary/30" />
                        </div>

                        <!-- Bill To -->
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Bill To <span class="text-red-500">*</span></label>
                            <select v-model="form.bill_to" required
                                class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary/30"
                                :class="form.errors.bill_to ? 'border-red-400' : ''">
                                <option value="">-- Select billing --</option>
                                <option v-for="(label, key) in billOptions" :key="key" :value="key">{{ label }}</option>
                            </select>
                            <p v-if="form.errors.bill_to" class="text-red-500 text-xs mt-1">{{ form.errors.bill_to }}</p>
                        </div>

                    </div>
                </div>

                <!-- ── Test Reason Checkboxes Card ── -->
                <div class="box box--stacked p-6">
                    <h2 class="text-sm font-semibold text-slate-700 mb-4 flex items-center gap-2">
                        <Lucide icon="CheckSquare" class="w-4 h-4 text-primary" /> Test Reason <span class="text-red-500 text-xs">(select at least one)</span>
                    </h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                        <label v-for="cb in testReasonCheckboxes" :key="cb.key"
                            class="flex items-center gap-3 p-3 rounded-lg border cursor-pointer transition"
                            :class="(form as any)[cb.key] ? 'border-primary bg-primary/5' : 'border-slate-200 hover:border-slate-300'">
                            <input type="checkbox" v-model="(form as any)[cb.key]" class="w-4 h-4 rounded text-primary" />
                            <span class="text-sm font-medium text-slate-700">{{ cb.label }}</span>
                        </label>
                    </div>
                    <!-- Other reason description -->
                    <div v-if="form.is_other_reason_test" class="mt-4">
                        <label class="block text-xs font-medium text-slate-600 mb-1.5">Describe Other Reason</label>
                        <input v-model="form.other_reason_description" type="text" placeholder="Specify other reason"
                            class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary/30" />
                    </div>
                </div>

                <!-- ── Notes Card ── -->
                <div class="box box--stacked p-6">
                    <h2 class="text-sm font-semibold text-slate-700 mb-4 flex items-center gap-2">
                        <Lucide icon="FileText" class="w-4 h-4 text-primary" /> Notes & Attachments
                    </h2>
                    <!-- Notes -->
                    <div class="mb-4">
                        <label class="block text-xs font-medium text-slate-600 mb-1.5">Notes</label>
                        <textarea v-model="form.notes" rows="3" placeholder="Additional notes or instructions..."
                            class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary/30 resize-none"></textarea>
                    </div>
                    <!-- File Upload -->
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
                        <!-- File List -->
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

                <!-- ── Actions ── -->
                <div class="flex justify-end gap-3">
                    <Link :href="route('admin.drivers.show', driver.id)">
                        <Button variant="outline-secondary" type="button" class="flex items-center gap-1.5">
                            <Lucide icon="X" class="w-4 h-4" /> Cancel
                        </Button>
                    </Link>
                    <Button type="submit" variant="primary" :disabled="form.processing" class="flex items-center gap-1.5">
                        <Lucide icon="Save" class="w-4 h-4" />
                        {{ form.processing ? 'Saving...' : 'Create Test Record' }}
                    </Button>
                </div>

            </form>
        </div>

    </div>
</template>
