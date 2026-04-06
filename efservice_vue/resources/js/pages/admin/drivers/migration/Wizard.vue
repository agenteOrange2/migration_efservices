<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import Lucide from '@/components/Base/Lucide'
import RazeLayout from '@/layouts/RazeLayout.vue'

declare function route(name: string, params?: any): string

defineOptions({ layout: RazeLayout })

interface DriverInfo {
    id: number
    full_name: string
    email: string
    status: number
    carrier_name: string
}
interface AvailableCarrier {
    id: number
    name: string
    dot_number: string | null
    mc_number: string | null
    state: string | null
    address: string | null
    current_drivers: number
    max_drivers: number
}

const props = defineProps<{
    driver: DriverInfo
    availableCarriers: AvailableCarrier[]
    driverWarnings: string[]
}>()

// ─── Steps ───────────────────────────────────────────────────────────────────
const step = ref<1 | 2 | 3>(1)
const selectedCarrier = ref<AvailableCarrier | null>(null)
const search = ref('')

const filteredCarriers = computed(() =>
    search.value.trim()
        ? props.availableCarriers.filter(c =>
            c.name.toLowerCase().includes(search.value.toLowerCase()) ||
            (c.dot_number ?? '').includes(search.value))
        : props.availableCarriers
)

// ─── Step 2 validation (client-side) ─────────────────────────────────────────
const validationErrors = computed<string[]>(() => {
    if (!selectedCarrier.value) return []
    const errors: string[] = []
    if (selectedCarrier.value.current_drivers >= selectedCarrier.value.max_drivers) {
        errors.push('Target carrier has reached maximum driver capacity.')
    }
    return errors
})

const validationWarnings = computed<string[]>(() => [...props.driverWarnings])

const canProceed = computed(() => validationErrors.value.length === 0)

function selectCarrier(carrier: AvailableCarrier) {
    selectedCarrier.value = carrier
    step.value = 2
}

// ─── Step 3 form ─────────────────────────────────────────────────────────────
const form = useForm({
    carrier_id: 0,
    reason: '',
    notes: '',
})

function goToConfirm() {
    if (!selectedCarrier.value) return
    form.carrier_id = selectedCarrier.value.id
    step.value = 3
}

function submit() {
    form.post(route('admin.drivers.migration.execute', props.driver.id))
}
</script>

<template>
    <Head :title="`Migrate Driver · ${driver.full_name}`" />

    <div class="grid grid-cols-12 gap-6">

        <!-- Breadcrumb -->
        <div class="col-span-12">
            <div class="flex items-center gap-2 text-sm text-slate-500">
                <Link :href="route('admin.drivers.index')" class="hover:text-primary transition">Drivers</Link>
                <Lucide icon="ChevronRight" class="w-4 h-4" />
                <Link :href="route('admin.drivers.show', driver.id)" class="hover:text-primary transition">{{ driver.full_name }}</Link>
                <Lucide icon="ChevronRight" class="w-4 h-4" />
                <span class="text-slate-800 font-medium">Migrate Carrier</span>
            </div>
        </div>

        <!-- Main Card -->
        <div class="col-span-12 lg:col-span-8 lg:col-start-3">
            <div class="box box--stacked overflow-hidden">

                <!-- Header -->
                <div class="flex items-center gap-4 px-6 py-5 border-b border-slate-100 bg-slate-50">
                    <div class="p-2.5 bg-amber-100 rounded-xl">
                        <Lucide icon="ArrowRightLeft" class="w-6 h-6 text-amber-600" />
                    </div>
                    <div>
                        <h1 class="text-lg font-semibold text-slate-800">Migrate Driver to Another Carrier</h1>
                        <p class="text-sm text-slate-500 mt-0.5">{{ driver.full_name }} &middot; Currently at <strong>{{ driver.carrier_name }}</strong></p>
                    </div>
                </div>

                <!-- Step bar -->
                <div class="flex border-b border-slate-100">
                    <div v-for="(label, i) in ['1. Select Carrier', '2. Validation', '3. Confirm']" :key="i"
                        class="flex-1 py-3 text-center text-xs font-medium transition border-b-2"
                        :class="step === i + 1
                            ? 'text-primary border-primary bg-primary/5'
                            : step > i + 1
                                ? 'text-emerald-600 border-emerald-400 bg-emerald-50/50'
                                : 'text-slate-400 border-transparent'">
                        <span v-if="step > i + 1" class="inline-flex items-center gap-1">
                            <Lucide icon="CheckCircle" class="w-3.5 h-3.5" /> {{ label.replace(/^\d+\.\s/, '') }}
                        </span>
                        <span v-else>{{ label }}</span>
                    </div>
                </div>

                <!-- Body -->
                <div class="p-6 min-h-[300px]">

                    <!-- ── STEP 1: Select Carrier ─────────────────────────────── -->
                    <div v-if="step === 1">
                        <p class="text-sm text-slate-500 mb-4">Select the carrier you want to migrate this driver to. Only carriers with available capacity are shown.</p>

                        <input v-model="search" type="text" placeholder="Search by name or DOT number..."
                            class="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm mb-5 focus:outline-none focus:ring-2 focus:ring-primary/30 bg-slate-50" />

                        <div v-if="filteredCarriers.length" class="space-y-2">
                            <button v-for="c in filteredCarriers" :key="c.id" type="button"
                                class="w-full flex items-center justify-between border border-slate-200 rounded-xl p-4 hover:border-primary hover:bg-primary/5 cursor-pointer transition text-left"
                                @click="selectCarrier(c)">
                                <div>
                                    <div class="font-semibold text-slate-800">{{ c.name }}</div>
                                    <div class="text-xs text-slate-500 mt-0.5 flex items-center gap-3">
                                        <span v-if="c.dot_number">DOT: {{ c.dot_number }}</span>
                                        <span v-if="c.mc_number">MC: {{ c.mc_number }}</span>
                                        <span v-if="c.state">{{ c.state }}</span>
                                    </div>
                                </div>
                                <div class="text-right flex-shrink-0 ml-4">
                                    <div class="text-xs font-medium text-slate-600">{{ c.current_drivers }} / {{ c.max_drivers }} drivers</div>
                                    <div class="w-20 bg-slate-100 rounded-full h-1.5 mt-1.5">
                                        <div class="bg-primary h-1.5 rounded-full transition-all"
                                            :style="`width:${c.max_drivers > 0 ? Math.min((c.current_drivers / c.max_drivers) * 100, 100) : 0}%`"></div>
                                    </div>
                                </div>
                            </button>
                        </div>

                        <div v-else class="flex flex-col items-center py-14 text-slate-400">
                            <Lucide icon="Building2" class="w-12 h-12 mb-3" />
                            <p class="text-sm font-medium">No available carriers</p>
                            <p class="text-xs mt-1">All other carriers are at full capacity or unavailable.</p>
                        </div>
                    </div>

                    <!-- ── STEP 2: Validation ────────────────────────────────── -->
                    <div v-else-if="step === 2" class="space-y-4">

                        <!-- Target carrier summary -->
                        <div class="flex items-center justify-between p-4 bg-slate-50 border border-slate-200 rounded-xl">
                            <div>
                                <p class="text-xs text-slate-400 mb-0.5">Migrating to</p>
                                <p class="font-semibold text-slate-800">{{ selectedCarrier?.name }}</p>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    <span v-if="selectedCarrier?.dot_number">DOT: {{ selectedCarrier.dot_number }}</span>
                                    <span v-if="selectedCarrier?.state"> · {{ selectedCarrier.state }}</span>
                                </p>
                            </div>
                            <div class="text-right">
                                <div class="text-xs text-slate-500">{{ selectedCarrier?.current_drivers }} / {{ selectedCarrier?.max_drivers }} drivers</div>
                            </div>
                        </div>

                        <!-- Blocking errors -->
                        <div v-if="validationErrors.length" class="space-y-2">
                            <p class="text-sm font-semibold text-red-600 flex items-center gap-1.5">
                                <Lucide icon="XCircle" class="w-4 h-4" /> Blocking Issues
                            </p>
                            <div v-for="err in validationErrors" :key="err"
                                class="flex items-start gap-2 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">
                                <Lucide icon="AlertCircle" class="w-4 h-4 flex-shrink-0 mt-0.5" /> {{ err }}
                            </div>
                        </div>

                        <!-- Warnings -->
                        <div v-if="validationWarnings.length" class="space-y-2">
                            <p class="text-sm font-semibold text-amber-600 flex items-center gap-1.5">
                                <Lucide icon="AlertTriangle" class="w-4 h-4" /> Warnings
                            </p>
                            <div v-for="w in validationWarnings" :key="w"
                                class="flex items-start gap-2 p-3 bg-amber-50 border border-amber-200 rounded-lg text-sm text-amber-700">
                                <Lucide icon="AlertTriangle" class="w-4 h-4 flex-shrink-0 mt-0.5" /> {{ w }}
                            </div>
                        </div>

                        <!-- All clear -->
                        <div v-if="canProceed && !validationWarnings.length"
                            class="flex items-center gap-3 p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-700 text-sm">
                            <Lucide icon="CheckCircle" class="w-5 h-5 flex-shrink-0" />
                            <span>Driver is eligible for migration. No issues found.</span>
                        </div>
                        <div v-else-if="canProceed && validationWarnings.length"
                            class="flex items-start gap-3 p-4 bg-blue-50 border border-blue-200 rounded-xl text-blue-700 text-sm">
                            <Lucide icon="Info" class="w-5 h-5 flex-shrink-0 mt-0.5" />
                            <span>Migration can proceed, but please review the warnings above before continuing.</span>
                        </div>
                    </div>

                    <!-- ── STEP 3: Confirm ───────────────────────────────────── -->
                    <div v-else-if="step === 3">
                        <form @submit.prevent="submit" class="space-y-5">

                            <!-- From → To summary -->
                            <div class="grid grid-cols-2 gap-4">
                                <div class="bg-slate-50 border border-slate-200 rounded-xl p-4">
                                    <p class="text-xs text-slate-400 mb-1">From</p>
                                    <p class="font-semibold text-slate-700">{{ driver.carrier_name }}</p>
                                </div>
                                <div class="bg-primary/5 border border-primary/20 rounded-xl p-4">
                                    <p class="text-xs text-slate-400 mb-1">To</p>
                                    <p class="font-semibold text-primary">{{ selectedCarrier?.name }}</p>
                                </div>
                            </div>

                            <!-- Notice -->
                            <div class="flex items-start gap-3 p-4 bg-amber-50 border border-amber-200 rounded-xl text-sm text-amber-700">
                                <Lucide icon="Info" class="w-5 h-5 flex-shrink-0 mt-0.5" />
                                <div>
                                    Driver status will be set to <strong>Pending</strong> after migration.
                                    All active vehicle assignments will be ended.
                                    You have <strong>24 hours</strong> to rollback this action from the driver's profile page.
                                </div>
                            </div>

                            <!-- Reason -->
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1.5">
                                    Reason for Migration <span class="text-slate-400 font-normal text-xs">(optional)</span>
                                </label>
                                <textarea v-model="form.reason" rows="2" placeholder="Enter reason for this migration..."
                                    class="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30 resize-none"></textarea>
                            </div>

                            <!-- Notes -->
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1.5">
                                    Additional Notes <span class="text-slate-400 font-normal text-xs">(optional)</span>
                                </label>
                                <textarea v-model="form.notes" rows="2" placeholder="Any additional notes..."
                                    class="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30 resize-none"></textarea>
                            </div>

                            <!-- Error from server -->
                            <div v-if="form.errors.migration"
                                class="flex items-start gap-2 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">
                                <Lucide icon="AlertCircle" class="w-4 h-4 flex-shrink-0 mt-0.5" />
                                {{ form.errors.migration }}
                            </div>

                        </form>
                    </div>

                </div>

                <!-- Footer -->
                <div class="flex items-center justify-between px-6 py-4 border-t border-slate-100 bg-slate-50">
                    <!-- Back -->
                    <div>
                        <button v-if="step > 1" type="button" @click="step = (step - 1) as any"
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg border border-slate-200 text-sm text-slate-600 bg-white hover:bg-slate-50 transition">
                            <Lucide icon="ChevronLeft" class="w-4 h-4" /> Back
                        </button>
                        <Link v-else :href="route('admin.drivers.show', driver.id)"
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg border border-slate-200 text-sm text-slate-600 bg-white hover:bg-slate-50 transition">
                            <Lucide icon="ChevronLeft" class="w-4 h-4" /> Cancel
                        </Link>
                    </div>

                    <!-- Next / Submit -->
                    <div>
                        <button v-if="step === 2 && canProceed" type="button" @click="goToConfirm"
                            class="inline-flex items-center gap-1.5 px-5 py-2 rounded-lg bg-primary text-white text-sm font-medium hover:bg-primary/90 transition">
                            Continue <Lucide icon="ChevronRight" class="w-4 h-4" />
                        </button>
                        <button v-if="step === 3" type="button" @click="submit" :disabled="form.processing"
                            class="inline-flex items-center gap-2 px-6 py-2 rounded-lg bg-amber-500 text-white text-sm font-medium hover:bg-amber-600 transition disabled:opacity-60">
                            <Lucide v-if="form.processing" icon="Loader" class="w-4 h-4 animate-spin" />
                            <Lucide v-else icon="ArrowRightLeft" class="w-4 h-4" />
                            Confirm Migration
                        </button>
                    </div>
                </div>

            </div>
        </div>

    </div>
</template>
