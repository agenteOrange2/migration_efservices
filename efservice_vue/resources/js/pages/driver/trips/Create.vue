<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import Button from '@/components/Base/Button'
import FormInput from '@/components/Base/Form/FormInput.vue'
import FormTextarea from '@/components/Base/Form/FormTextarea.vue'
import Litepicker from '@/components/Base/Litepicker/Litepicker.vue'
import Lucide from '@/components/Base/Lucide'
import TomSelect from '@/components/Base/TomSelect/TomSelect.vue'
import RazeLayout from '@/layouts/RazeLayout.vue'

declare function route(name: string, params?: any): string

defineOptions({ layout: RazeLayout })

interface VehicleOption {
    id: number
    label: string
    license_plate: string | null
}

const props = defineProps<{
    driver: {
        id: number
        full_name: string
    }
    carrier: {
        id: number
        name: string
    }
    vehicles: VehicleOption[]
    noAssignedVehicles: boolean
    fmcsaStatus: any
    weeklyCycleStatus: {
        cycle_type_name: string
        hours_remaining: number
        hours_limit: number
        percentage_used: number
        status_color: string
        is_over_limit: boolean
        is_approaching_limit: boolean
    }
}>()

const activeTab = ref<'quick' | 'full'>('quick')
const showOptionalFields = ref(false)

const pickerOptions = {
    autoApply: true,
    singleMode: true,
    numberOfColumns: 1,
    numberOfMonths: 1,
    format: 'M/D/YYYY',
    dropdowns: {
        minYear: 2020,
        maxYear: 2035,
        months: true,
        years: true,
    },
}

const fullTripForm = useForm({
    vehicle_id: '',
    origin_address: '',
    destination_address: '',
    scheduled_start_date: '',
    scheduled_start_time: '08:00',
    estimated_duration_minutes: '60',
    description: '',
    notes: '',
})

const quickTripForm = useForm({
    vehicle_id: '',
    origin_address: '',
    destination_address: '',
    estimated_duration_minutes: '60',
    description: '',
    notes: '',
})

const statusTone = computed(() => {
    if (props.weeklyCycleStatus.status_color === 'red') return 'bg-slate-200 text-slate-700'
    if (props.weeklyCycleStatus.status_color === 'yellow') return 'bg-slate-100 text-slate-700'
    return 'bg-primary/10 text-primary'
})

const blockingMessages = computed(() => props.fmcsaStatus?.can_drive?.reasons ?? [])

function submitFullTrip() {
    fullTripForm.post(route('driver.trips.store'))
}

function submitQuickTrip() {
    quickTripForm.post(route('driver.trips.quick-store'))
}
</script>

<template>
    <Head title="Create Trip" />

    <div class="grid grid-cols-12 gap-4 sm:gap-6">
        <div class="col-span-12">
            <div class="box box--stacked p-4 sm:p-6">
                <div class="flex flex-col gap-4 sm:gap-5 xl:flex-row xl:items-center xl:justify-between">
                    <div class="flex items-start gap-3 sm:gap-4 min-w-0">
                        <div class="rounded-2xl border border-primary/20 bg-primary/10 p-3 shrink-0">
                            <Lucide icon="Plus" class="h-7 w-7 sm:h-8 sm:w-8 text-primary" />
                        </div>
                        <div class="min-w-0">
                            <h1 class="text-xl sm:text-2xl font-bold text-slate-800">Create New Trip</h1>
                            <p class="mt-1 text-sm sm:text-base text-slate-500">Build a full trip with all route details or start a quick trip with only the essentials.</p>
                            <p class="mt-2 text-sm text-slate-500 break-words">
                                Carrier: <span class="font-medium text-slate-700">{{ carrier.name }}</span>
                            </p>
                        </div>
                    </div>

                    <Link :href="route('driver.trips.index')" class="w-full xl:w-auto">
                        <Button variant="outline-secondary" class="w-full justify-center gap-2 xl:w-auto">
                            <Lucide icon="ArrowLeft" class="h-4 w-4" />
                            Back to Trips
                        </Button>
                    </Link>
                </div>
            </div>
        </div>

        <div class="col-span-12 lg:col-span-4">
                <div class="space-y-4 sm:space-y-6">
                    <div v-if="noAssignedVehicles" class="box box--stacked p-4 sm:p-6">
                        <h2 class="text-base font-semibold text-slate-800">No Assigned Vehicles</h2>
                        <p class="mt-2 text-sm text-slate-500">
                            You do not have any active vehicle assignment right now. Contact your carrier before creating a trip.
                        </p>
                    </div>

                    <div class="box box--stacked p-4 sm:p-6">
                        <h2 class="text-base font-semibold text-slate-800">HOS Snapshot</h2>
                    <div class="mt-5 grid grid-cols-1 gap-4">
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Available Today</p>
                            <p class="mt-2 text-3xl font-semibold text-slate-800">{{ fmcsaStatus?.driving_limit?.remaining_hours ?? 0 }}h</p>
                            <p class="mt-1 text-sm text-slate-500">Driving hours remaining</p>
                        </div>

                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <div class="flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Weekly Cycle</p>
                                    <p class="mt-2 text-2xl font-semibold text-slate-800">{{ weeklyCycleStatus.hours_remaining }}h</p>
                                    <p class="mt-1 text-sm text-slate-500 break-words">{{ weeklyCycleStatus.cycle_type_name }}</p>
                                </div>
                                <span class="rounded-full px-3 py-1 text-xs font-medium shrink-0" :class="statusTone">
                                    {{ weeklyCycleStatus.percentage_used }}%
                                </span>
                            </div>
                            <div class="mt-4 h-2 w-full overflow-hidden rounded-full bg-slate-200">
                                <div class="h-full rounded-full bg-primary" :style="{ width: `${Math.min(weeklyCycleStatus.percentage_used, 100)}%` }" />
                            </div>
                        </div>

                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Carrier</p>
                            <p class="mt-2 text-sm font-semibold text-slate-800 break-words">{{ carrier.name }}</p>
                            <p class="mt-1 text-sm text-slate-500">Trips created here are assigned directly to you.</p>
                        </div>
                    </div>
                </div>

                <div v-if="blockingMessages.length" class="box box--stacked p-4 sm:p-6">
                    <h2 class="text-base font-semibold text-slate-800">Before You Create a Trip</h2>
                    <div class="mt-4 space-y-3">
                        <div
                            v-for="message in blockingMessages"
                            :key="message"
                            class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600"
                        >
                            {{ message }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12 lg:col-span-8">
            <div class="box box--stacked overflow-hidden">
                <div class="border-b border-slate-200 bg-slate-50/80 px-4 py-3 sm:px-6 sm:py-4">
                    <div class="-mx-1 flex gap-2 overflow-x-auto px-1 pb-1 sm:flex-wrap sm:gap-3 sm:overflow-visible sm:pb-0">
                        <button
                            type="button"
                            class="inline-flex shrink-0 items-center gap-2 rounded-full border px-3 py-2 text-sm font-medium transition sm:px-4"
                            :class="activeTab === 'quick' ? 'border-primary bg-primary text-white' : 'border-slate-200 bg-white text-slate-600'"
                            @click="activeTab = 'quick'"
                        >
                            <Lucide icon="Zap" class="h-4 w-4" />
                            Quick Trip
                        </button>
                        <button
                            type="button"
                            class="inline-flex shrink-0 items-center gap-2 rounded-full border px-3 py-2 text-sm font-medium transition sm:px-4"
                            :class="activeTab === 'full' ? 'border-primary bg-primary text-white' : 'border-slate-200 bg-white text-slate-600'"
                            @click="activeTab = 'full'"
                        >
                            <Lucide icon="Route" class="h-4 w-4" />
                            Full Trip
                        </button>
                    </div>
                </div>

                <form v-if="activeTab === 'quick'" class="space-y-5 p-4 sm:space-y-6 sm:p-6" @submit.prevent="submitQuickTrip">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                        <div class="flex items-start gap-3">
                            <div class="rounded-xl bg-primary/10 p-2">
                                <Lucide icon="Zap" class="h-5 w-5 text-primary" />
                            </div>
                            <div>
                                <h2 class="text-base font-semibold text-slate-800">Quick Trip Mode</h2>
                                <p class="mt-1 text-sm text-slate-500">Best when you need to get moving now and route details can be completed later.</p>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="mb-2 block text-xs font-medium uppercase tracking-wide text-slate-500">Vehicle</label>
                        <TomSelect v-model="quickTripForm.vehicle_id">
                            <option value="">Select your vehicle</option>
                            <option v-for="vehicle in vehicles" :key="vehicle.id" :value="String(vehicle.id)">
                                {{ vehicle.label }}
                            </option>
                        </TomSelect>
                        <p v-if="quickTripForm.errors.vehicle_id" class="mt-1 text-xs text-danger">{{ quickTripForm.errors.vehicle_id }}</p>
                    </div>

                    <button
                        type="button"
                        class="inline-flex items-center gap-2 text-sm font-medium text-primary"
                        @click="showOptionalFields = !showOptionalFields"
                    >
                        <Lucide :icon="showOptionalFields ? 'ChevronUp' : 'ChevronDown'" class="h-4 w-4" />
                        {{ showOptionalFields ? 'Hide optional fields' : 'Show optional fields' }}
                    </button>

                    <div v-if="showOptionalFields" class="grid grid-cols-1 gap-5 md:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-xs font-medium uppercase tracking-wide text-slate-500">Origin Address</label>
                            <FormTextarea v-model="quickTripForm.origin_address" rows="3" placeholder="Optional pickup or starting location" />
                        </div>
                        <div>
                            <label class="mb-2 block text-xs font-medium uppercase tracking-wide text-slate-500">Destination Address</label>
                            <FormTextarea v-model="quickTripForm.destination_address" rows="3" placeholder="Optional destination if you already know it" />
                        </div>
                        <div>
                            <label class="mb-2 block text-xs font-medium uppercase tracking-wide text-slate-500">Estimated Duration (minutes)</label>
                            <FormInput v-model="quickTripForm.estimated_duration_minutes" type="number" min="15" max="720" />
                        </div>
                        <div>
                            <label class="mb-2 block text-xs font-medium uppercase tracking-wide text-slate-500">Description</label>
                            <FormInput v-model="quickTripForm.description" placeholder="Optional trip label" />
                        </div>
                        <div class="md:col-span-2">
                            <label class="mb-2 block text-xs font-medium uppercase tracking-wide text-slate-500">Notes</label>
                            <FormTextarea v-model="quickTripForm.notes" rows="4" placeholder="Anything your carrier should know about this quick trip..." />
                        </div>
                    </div>

                    <div class="flex flex-col-reverse gap-3 border-t border-slate-200 pt-5 sm:flex-row sm:justify-end">
                        <Link :href="route('driver.trips.index')" class="w-full sm:w-auto">
                            <Button type="button" variant="outline-secondary" class="w-full justify-center sm:w-auto">Cancel</Button>
                        </Link>
                        <Button type="submit" variant="primary" class="w-full justify-center gap-2 sm:w-auto" :disabled="quickTripForm.processing || weeklyCycleStatus.is_over_limit || noAssignedVehicles">
                            <Lucide icon="Zap" class="h-4 w-4" />
                            {{ quickTripForm.processing ? 'Creating...' : 'Create Quick Trip' }}
                        </Button>
                    </div>
                </form>

                <form v-else class="space-y-5 p-4 sm:space-y-6 sm:p-6" @submit.prevent="submitFullTrip">
                    <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                        <div class="md:col-span-2">
                            <label class="mb-2 block text-xs font-medium uppercase tracking-wide text-slate-500">Vehicle</label>
                            <TomSelect v-model="fullTripForm.vehicle_id">
                                <option value="">Select your vehicle</option>
                                <option v-for="vehicle in vehicles" :key="vehicle.id" :value="String(vehicle.id)">
                                    {{ vehicle.label }}
                                </option>
                            </TomSelect>
                            <p v-if="fullTripForm.errors.vehicle_id" class="mt-1 text-xs text-danger">{{ fullTripForm.errors.vehicle_id }}</p>
                        </div>

                        <div>
                            <label class="mb-2 block text-xs font-medium uppercase tracking-wide text-slate-500">Origin Address</label>
                            <FormTextarea v-model="fullTripForm.origin_address" rows="3" placeholder="Where are you starting from?" />
                            <p v-if="fullTripForm.errors.origin_address" class="mt-1 text-xs text-danger">{{ fullTripForm.errors.origin_address }}</p>
                        </div>
                        <div>
                            <label class="mb-2 block text-xs font-medium uppercase tracking-wide text-slate-500">Destination Address</label>
                            <FormTextarea v-model="fullTripForm.destination_address" rows="3" placeholder="Where are you headed?" />
                            <p v-if="fullTripForm.errors.destination_address" class="mt-1 text-xs text-danger">{{ fullTripForm.errors.destination_address }}</p>
                        </div>

                        <div>
                            <label class="mb-2 block text-xs font-medium uppercase tracking-wide text-slate-500">Planned Start Date</label>
                            <Litepicker v-model="fullTripForm.scheduled_start_date" :options="pickerOptions" />
                            <p v-if="fullTripForm.errors.scheduled_start_date" class="mt-1 text-xs text-danger">{{ fullTripForm.errors.scheduled_start_date }}</p>
                        </div>
                        <div>
                            <label class="mb-2 block text-xs font-medium uppercase tracking-wide text-slate-500">Planned Start Time</label>
                            <FormInput v-model="fullTripForm.scheduled_start_time" type="time" />
                            <p v-if="fullTripForm.errors.scheduled_start_time" class="mt-1 text-xs text-danger">{{ fullTripForm.errors.scheduled_start_time }}</p>
                        </div>

                        <div>
                            <label class="mb-2 block text-xs font-medium uppercase tracking-wide text-slate-500">Estimated Duration (minutes)</label>
                            <FormInput v-model="fullTripForm.estimated_duration_minutes" type="number" min="15" max="720" />
                            <p v-if="fullTripForm.errors.estimated_duration_minutes" class="mt-1 text-xs text-danger">{{ fullTripForm.errors.estimated_duration_minutes }}</p>
                        </div>
                        <div>
                            <label class="mb-2 block text-xs font-medium uppercase tracking-wide text-slate-500">Description</label>
                            <FormInput v-model="fullTripForm.description" placeholder="What is this trip for?" />
                        </div>

                        <div class="md:col-span-2">
                            <label class="mb-2 block text-xs font-medium uppercase tracking-wide text-slate-500">Notes</label>
                            <FormTextarea v-model="fullTripForm.notes" rows="4" placeholder="Add any trip notes or delivery context..." />
                        </div>
                    </div>

                    <div class="flex flex-col-reverse gap-3 border-t border-slate-200 pt-5 sm:flex-row sm:justify-end">
                        <Link :href="route('driver.trips.index')" class="w-full sm:w-auto">
                            <Button type="button" variant="outline-secondary" class="w-full justify-center sm:w-auto">Cancel</Button>
                        </Link>
                        <Button type="submit" variant="primary" class="w-full justify-center gap-2 sm:w-auto" :disabled="fullTripForm.processing || weeklyCycleStatus.is_over_limit || noAssignedVehicles">
                            <Lucide icon="Save" class="h-4 w-4" />
                            {{ fullTripForm.processing ? 'Creating...' : 'Create Trip' }}
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
