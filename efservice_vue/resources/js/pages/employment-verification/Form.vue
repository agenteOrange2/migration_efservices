<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'
import { ref, onMounted, onUnmounted } from 'vue'
import RegistrationLayout from '@/layouts/RegistrationLayout.vue'
import Lucide from '@/components/Base/Lucide'

declare function route(name: string, params?: any): string

defineOptions({ layout: RegistrationLayout })

const props = defineProps<{
    token: string
    companyName: string
    driverName: string
    ssnLast4: string | null
    employment: {
        employed_from: string | null
        employed_to: string | null
        positions_held: string | null
        reason_for_leaving: string | null
        subject_to_fmcsr: boolean
        safety_sensitive_function: boolean
    }
}>()

// ─── Form ─────────────────────────────────────────────────────────────────────
const form = useForm({
    verification_status:   'verified',
    verification_notes:    '',
    verification_by:       '',
    signature:             '',
    employment_confirmed:  '1',
    dates_confirmed:       '',
    correct_dates:         '',
    drove_commercial:      '',
    safe_driver:           '',
    unsafe_driver_details: '',
    had_accidents:         '',
    accidents_details:     '',
    reason_confirmed:      '',
    different_reason:      '',
    positive_drug_test:    '',
    drug_test_details:     '',
    positive_alcohol_test: '',
    alcohol_test_details:  '',
    refused_test:          '',
    refused_test_details:  '',
    completed_rehab:       '',
    other_violations:      '',
    violation_details:     '',
})

// ─── Signature Pad ────────────────────────────────────────────────────────────
const canvasRef = ref<HTMLCanvasElement | null>(null)
let signaturePad: any = null

onMounted(async () => {
    if (canvasRef.value) {
        const { default: SignaturePad } = await import('signature_pad')
        signaturePad = new SignaturePad(canvasRef.value, {
            backgroundColor: 'rgb(255,255,255)',
            penColor: '#040A60',
        })
        resizeCanvas()
        window.addEventListener('resize', resizeCanvas)
    }
})

onUnmounted(() => {
    window.removeEventListener('resize', resizeCanvas)
})

function resizeCanvas() {
    if (!canvasRef.value || !signaturePad) return
    const ratio = Math.max(window.devicePixelRatio || 1, 1)
    canvasRef.value.width  = canvasRef.value.offsetWidth  * ratio
    canvasRef.value.height = canvasRef.value.offsetHeight * ratio
    canvasRef.value.getContext('2d')?.scale(ratio, ratio)
    signaturePad.clear()
}

function clearSignature() {
    signaturePad?.clear()
    form.signature = ''
}

function submit() {
    if (signaturePad && !signaturePad.isEmpty()) {
        form.signature = signaturePad.toDataURL()
    }
    form.post(route('employment-verification.process', props.token))
}
</script>

<template>
    <Head :title="`Employment Verification — ${companyName}`" />

    <!-- Header -->
    <div class="box box--stacked mb-6 overflow-hidden">
        <div class="bg-slate-800 px-8 py-6 text-center">
            <div class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-full bg-white/10">
                <Lucide icon="FileText" class="h-7 w-7 text-white" />
            </div>
            <h1 class="text-2xl font-bold text-white">Employment Verification</h1>
            <p class="mt-1 text-sm text-slate-300">Official Request for Information · DOT Regulated</p>
        </div>
        <div class="border-b border-slate-100 px-8 py-5">
            <p class="text-slate-600">
                To: <span class="font-semibold text-slate-800">{{ companyName }}</span>
            </p>
            <p class="mt-2 text-slate-600">
                <span class="font-semibold text-slate-800">{{ driverName }}</span>
                has listed your company as a previous employer. As part of our verification process under
                <span class="font-medium">49 CFR 40.25 and 391.23</span>, we kindly request your confirmation of the following employment details.
                Under DOT rule 391.23(g), you must respond within 30 days of receipt.
            </p>
        </div>
    </div>

    <form @submit.prevent="submit" class="space-y-6">

        <!-- Candidate Info -->
        <div class="box box--stacked p-6">
            <div class="mb-5 flex items-center justify-between">
                <h2 class="text-sm font-bold uppercase tracking-wider text-slate-400">Candidate Information</h2>
                <span class="rounded-full bg-primary/10 px-3 py-1 text-xs font-semibold text-primary">DOT Regulated</span>
            </div>
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-slate-400">Applicant Name</p>
                    <p class="mt-1 text-lg font-semibold text-slate-800">{{ driverName }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-slate-400">SSN (Last 4)</p>
                    <p class="mt-1 font-mono text-lg tracking-widest text-slate-800">{{ ssnLast4 ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-slate-400">Employment Dates</p>
                    <p class="mt-1 font-medium text-slate-800">{{ employment.employed_from }} — {{ employment.employed_to }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-slate-400">Position</p>
                    <p class="mt-1 font-medium text-slate-800">{{ employment.positions_held ?? 'N/A' }}</p>
                </div>
            </div>
            <div v-if="employment.subject_to_fmcsr || employment.safety_sensitive_function"
                 class="mt-5 flex flex-wrap gap-3 border-t border-slate-100 pt-4">
                <span v-if="employment.subject_to_fmcsr" class="inline-flex items-center gap-1.5 text-sm text-success">
                    <Lucide icon="CheckCircle" class="h-4 w-4" /> Subject to FMCSR
                </span>
                <span v-if="employment.safety_sensitive_function" class="inline-flex items-center gap-1.5 text-sm text-success">
                    <Lucide icon="CheckCircle" class="h-4 w-4" /> Performed Safety-Sensitive Functions
                </span>
            </div>
        </div>

        <!-- Safety Performance Questions -->
        <div class="box box--stacked p-6">
            <h2 class="mb-6 flex items-center text-lg font-bold text-slate-800">
                <span class="mr-3 inline-block h-6 w-1.5 rounded-r bg-slate-800"></span>
                Safety Performance History Questions
            </h2>

            <div class="space-y-8">

                <!-- Q1 -->
                <div>
                    <p class="mb-3 font-medium text-slate-800">1. Are the dates of employment correct as stated above?</p>
                    <div class="flex gap-6">
                        <label class="flex cursor-pointer items-center gap-2">
                            <input v-model="form.dates_confirmed" type="radio" value="1" required class="form-check-input" />
                            Yes
                        </label>
                        <label class="flex cursor-pointer items-center gap-2">
                            <input v-model="form.dates_confirmed" type="radio" value="0" class="form-check-input" />
                            No
                        </label>
                    </div>
                    <div v-if="form.dates_confirmed === '0'" class="mt-3 border-l-2 border-primary/30 pl-4">
                        <input v-model="form.correct_dates" type="text" placeholder="Please provide correct dates"
                               class="form-control w-full" />
                    </div>
                    <p v-if="form.errors.dates_confirmed" class="mt-1 text-xs text-danger">{{ form.errors.dates_confirmed }}</p>
                </div>

                <!-- Q2 -->
                <div class="border-t border-slate-100 pt-6">
                    <p class="mb-3 font-medium text-slate-800">2. Did the applicant drive commercial vehicles for your company?</p>
                    <div class="flex gap-6">
                        <label class="flex cursor-pointer items-center gap-2"><input v-model="form.drove_commercial" type="radio" value="1" required class="form-check-input" /> Yes</label>
                        <label class="flex cursor-pointer items-center gap-2"><input v-model="form.drove_commercial" type="radio" value="0" class="form-check-input" /> No</label>
                    </div>
                    <p v-if="form.errors.drove_commercial" class="mt-1 text-xs text-danger">{{ form.errors.drove_commercial }}</p>
                </div>

                <!-- Q3 -->
                <div class="border-t border-slate-100 pt-6">
                    <p class="mb-3 font-medium text-slate-800">3. Was the applicant a safe and efficient driver?</p>
                    <div class="flex gap-6">
                        <label class="flex cursor-pointer items-center gap-2"><input v-model="form.safe_driver" type="radio" value="1" required class="form-check-input" /> Yes</label>
                        <label class="flex cursor-pointer items-center gap-2"><input v-model="form.safe_driver" type="radio" value="0" class="form-check-input" /> No</label>
                    </div>
                    <div v-if="form.safe_driver === '0'" class="mt-3 border-l-2 border-primary/30 pl-4">
                        <textarea v-model="form.unsafe_driver_details" rows="2" placeholder="Please explain..." class="form-control w-full" />
                    </div>
                    <p v-if="form.errors.safe_driver" class="mt-1 text-xs text-danger">{{ form.errors.safe_driver }}</p>
                </div>

                <!-- Q4 -->
                <div class="border-t border-slate-100 pt-6">
                    <p class="mb-3 font-medium text-slate-800">4. Was the applicant involved in any vehicle accidents while employed with your company?</p>
                    <div class="flex gap-6">
                        <label class="flex cursor-pointer items-center gap-2"><input v-model="form.had_accidents" type="radio" value="1" required class="form-check-input" /> Yes</label>
                        <label class="flex cursor-pointer items-center gap-2"><input v-model="form.had_accidents" type="radio" value="0" class="form-check-input" /> No</label>
                    </div>
                    <div v-if="form.had_accidents === '1'" class="mt-3 border-l-2 border-primary/30 pl-4">
                        <textarea v-model="form.accidents_details" rows="3" placeholder="Date, description, and outcome of each accident..." class="form-control w-full" />
                    </div>
                    <p v-if="form.errors.had_accidents" class="mt-1 text-xs text-danger">{{ form.errors.had_accidents }}</p>
                </div>

                <!-- Q5 -->
                <div class="border-t border-slate-100 pt-6">
                    <p class="mb-3 font-medium text-slate-800">5. Reason for leaving your employment:</p>
                    <div class="mb-3 rounded-lg border border-primary/20 bg-primary/5 p-3 text-sm text-primary">
                        Stated reason: "{{ employment.reason_for_leaving }}"
                    </div>
                    <div class="flex gap-6">
                        <label class="flex cursor-pointer items-center gap-2"><input v-model="form.reason_confirmed" type="radio" value="1" required class="form-check-input" /> Confirm</label>
                        <label class="flex cursor-pointer items-center gap-2"><input v-model="form.reason_confirmed" type="radio" value="0" class="form-check-input" /> Different Reason</label>
                    </div>
                    <div v-if="form.reason_confirmed === '0'" class="mt-3 border-l-2 border-primary/30 pl-4">
                        <input v-model="form.different_reason" type="text" placeholder="Specify correct reason" class="form-control w-full" />
                    </div>
                    <p v-if="form.errors.reason_confirmed" class="mt-1 text-xs text-danger">{{ form.errors.reason_confirmed }}</p>
                </div>

                <!-- Drug & Alcohol -->
                <div class="border-t border-slate-100 pt-6">
                    <h3 class="mb-6 text-sm font-bold uppercase tracking-wider text-slate-400">Drug & Alcohol History (Last 3 Years)</h3>
                    <div class="space-y-6">

                        <div>
                            <p class="mb-2 font-medium text-slate-800">6. Has the applicant tested positive for a controlled substance in the last three (3) years?</p>
                            <div class="flex gap-6">
                                <label class="flex cursor-pointer items-center gap-2"><input v-model="form.positive_drug_test" type="radio" value="1" required class="form-check-input" /> Yes</label>
                                <label class="flex cursor-pointer items-center gap-2"><input v-model="form.positive_drug_test" type="radio" value="0" class="form-check-input" /> No</label>
                            </div>
                            <div v-if="form.positive_drug_test === '1'" class="mt-2">
                                <input v-model="form.drug_test_details" type="text" placeholder="Date and substance" class="form-control w-full" />
                            </div>
                        </div>

                        <div>
                            <p class="mb-2 font-medium text-slate-800">7. Has the applicant had an alcohol test with a B.A.C. of 0.04 or greater in the last three (3) years?</p>
                            <div class="flex gap-6">
                                <label class="flex cursor-pointer items-center gap-2"><input v-model="form.positive_alcohol_test" type="radio" value="1" required class="form-check-input" /> Yes</label>
                                <label class="flex cursor-pointer items-center gap-2"><input v-model="form.positive_alcohol_test" type="radio" value="0" class="form-check-input" /> No</label>
                            </div>
                            <div v-if="form.positive_alcohol_test === '1'" class="mt-2">
                                <input v-model="form.alcohol_test_details" type="text" placeholder="Date and level" class="form-control w-full" />
                            </div>
                        </div>

                        <div>
                            <p class="mb-2 font-medium text-slate-800">8. Has the applicant refused a required test for drugs or alcohol in the last three (3) years?</p>
                            <div class="flex gap-6">
                                <label class="flex cursor-pointer items-center gap-2"><input v-model="form.refused_test" type="radio" value="1" required class="form-check-input" /> Yes</label>
                                <label class="flex cursor-pointer items-center gap-2"><input v-model="form.refused_test" type="radio" value="0" class="form-check-input" /> No</label>
                            </div>
                            <div v-if="form.refused_test === '1'" class="mt-2">
                                <input v-model="form.refused_test_details" type="text" placeholder="Details of refusal" class="form-control w-full" />
                            </div>
                        </div>

                        <div>
                            <p class="mb-2 font-medium text-slate-800">9. Did the applicant complete a substance abuse rehabilitation program, if required?</p>
                            <div class="flex gap-6">
                                <label class="flex cursor-pointer items-center gap-2"><input v-model="form.completed_rehab" type="radio" value="1" required class="form-check-input" /> Yes</label>
                                <label class="flex cursor-pointer items-center gap-2"><input v-model="form.completed_rehab" type="radio" value="0" class="form-check-input" /> No</label>
                                <label class="flex cursor-pointer items-center gap-2"><input v-model="form.completed_rehab" type="radio" value="2" class="form-check-input" /> N/A</label>
                            </div>
                        </div>

                        <div>
                            <p class="mb-2 font-medium text-slate-800">10. Other DOT violations?</p>
                            <div class="flex gap-6">
                                <label class="flex cursor-pointer items-center gap-2"><input v-model="form.other_violations" type="radio" value="1" required class="form-check-input" /> Yes</label>
                                <label class="flex cursor-pointer items-center gap-2"><input v-model="form.other_violations" type="radio" value="0" class="form-check-input" /> No</label>
                            </div>
                            <div v-if="form.other_violations === '1'" class="mt-2">
                                <input v-model="form.violation_details" type="text" placeholder="Violation details" class="form-control w-full" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Verification Decision + Signature -->
        <div class="box box--stacked p-6">
            <div class="grid grid-cols-1 gap-8 md:grid-cols-2">

                <!-- Left: Decision fields -->
                <div class="space-y-5">
                    <div class="flex items-start gap-3 rounded-lg border border-slate-200 bg-slate-50 p-4">
                        <input v-model="form.employment_confirmed" type="checkbox" value="1" required class="form-check-input mt-0.5" />
                        <label class="cursor-pointer font-medium text-slate-700">I confirm this person was employed at our company</label>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Verification Decision</label>
                        <select v-model="form.verification_status" required class="form-select w-full">
                            <option value="verified">Verified — Information Correct</option>
                            <option value="rejected">Rejected — Information Incorrect</option>
                        </select>
                        <p v-if="form.errors.verification_status" class="mt-1 text-xs text-danger">{{ form.errors.verification_status }}</p>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Verified By (Full Name)</label>
                        <input v-model="form.verification_by" type="text" required placeholder="John Doe" class="form-control w-full" />
                        <p v-if="form.errors.verification_by" class="mt-1 text-xs text-danger">{{ form.errors.verification_by }}</p>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Additional Comments (Optional)</label>
                        <textarea v-model="form.verification_notes" rows="3" class="form-control w-full" />
                    </div>
                </div>

                <!-- Right: Signature -->
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">Digital Signature</label>
                    <div class="relative h-48 w-full cursor-crosshair overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                        <canvas ref="canvasRef" class="absolute inset-0 h-full w-full" />
                        <span class="pointer-events-none absolute bottom-2 right-3 select-none text-xs text-slate-300">Sign inside the box</span>
                    </div>
                    <button type="button" class="mt-2 text-sm font-medium text-primary hover:underline" @click="clearSignature">
                        Clear Signature
                    </button>
                    <p v-if="form.errors.signature" class="mt-1 text-xs text-danger">{{ form.errors.signature }}</p>
                </div>
            </div>
        </div>

        <!-- Submit -->
        <button type="submit" :disabled="form.processing"
                class="w-full rounded-lg bg-slate-800 py-4 text-base font-bold text-white transition hover:bg-slate-700 disabled:opacity-60">
            {{ form.processing ? 'Submitting...' : 'Submit Official Verification' }}
        </button>

    </form>
</template>
