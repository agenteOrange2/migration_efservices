<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { Link } from '@inertiajs/vue3'
import Button from '@/components/Base/Button'
import FormInput from '@/components/Base/Form/FormInput.vue'
import FormTextarea from '@/components/Base/Form/FormTextarea.vue'
import Litepicker from '@/components/Base/Litepicker/Litepicker.vue'
import Lucide from '@/components/Base/Lucide'
import TomSelect from '@/components/Base/TomSelect/TomSelect.vue'

declare function route(name: string, params?: any): string

type DriverOption = {
    id: number
    name: string
    email?: string | null
    hours_remaining: number
    status_color?: string
    can_drive: boolean
}

type VehicleOption = {
    id: number
    label: string
}

const props = defineProps<{
    form: any
    carriers: { id: number; name: string }[]
    drivers: DriverOption[]
    vehicles: VehicleOption[]
    isSuperadmin: boolean
    pageTitle: string
    pageDescription: string
    submitLabel: string
    cancelHref: string
    statusLabel?: string | null
    routeNames?: Partial<{
        carrierData: string
    }>
}>()

const emit = defineEmits<{
    (e: 'submit'): void
}>()

const driverOptions = ref<DriverOption[]>([...props.drivers])
const vehicleOptions = ref<VehicleOption[]>([...props.vehicles])
const loadingCarrierData = ref(false)

const pickerOptions = {
    autoApply: true,
    singleMode: true,
    numberOfColumns: 1,
    numberOfMonths: 1,
    format: 'M/D/YYYY',
    dropdowns: {
        minYear: 1990,
        maxYear: 2035,
        months: true,
        years: true,
    },
}

watch(() => props.drivers, (value) => {
    driverOptions.value = [...value]
}, { deep: true })

watch(() => props.vehicles, (value) => {
    vehicleOptions.value = [...value]
}, { deep: true })

watch(() => props.form.carrier_id, async (carrierId, previousValue) => {
    if (!carrierId) {
        driverOptions.value = []
        vehicleOptions.value = []
        props.form.driver_id = ''
        props.form.vehicle_id = ''
        return
    }

    if (carrierId === previousValue) {
        return
    }

    loadingCarrierData.value = true

    try {
        const response = await fetch(
            route(props.routeNames?.carrierData ?? 'admin.trips.carrier.data') +
            `?carrier_id=${encodeURIComponent(carrierId)}&selected_driver_id=${encodeURIComponent(props.form.driver_id || '')}&selected_vehicle_id=${encodeURIComponent(props.form.vehicle_id || '')}`
        )

        const payload = await response.json()
        driverOptions.value = payload.drivers ?? []
        vehicleOptions.value = payload.vehicles ?? []

        if (!driverOptions.value.some((driver) => String(driver.id) === String(props.form.driver_id))) {
            props.form.driver_id = ''
        }

        if (!vehicleOptions.value.some((vehicle) => String(vehicle.id) === String(props.form.vehicle_id))) {
            props.form.vehicle_id = ''
        }
    } finally {
        loadingCarrierData.value = false
    }
})

const selectedDriver = computed(() => driverOptions.value.find((driver) => String(driver.id) === String(props.form.driver_id)) ?? null)
</script>

<template>
    <form @submit.prevent="emit('submit')" class="grid grid-cols-12 gap-6">
        <div class="col-span-12">
            <div class="box box--stacked p-6">
                <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="rounded-xl border border-primary/20 bg-primary/10 p-3">
                            <Lucide icon="Truck" class="h-7 w-7 text-primary" />
                        </div>
                        <div>
                            <div class="flex flex-wrap items-center gap-3">
                                <h1 class="text-2xl font-bold text-slate-800">{{ pageTitle }}</h1>
                                <span v-if="statusLabel" class="rounded-full bg-primary/10 px-3 py-1 text-xs font-medium text-primary">
                                    {{ statusLabel }}
                                </span>
                            </div>
                            <p class="mt-1 text-sm text-slate-500">{{ pageDescription }}</p>
                        </div>
                    </div>
                    <Link :href="cancelHref">
                        <Button variant="outline-secondary" class="flex items-center gap-2">
                            <Lucide icon="ArrowLeft" class="h-4 w-4" />
                            Back to Trips
                        </Button>
                    </Link>
                </div>
            </div>
        </div>

        <div class="col-span-12 xl:col-span-8 space-y-6">
            <div class="box box--stacked p-6">
                <div class="mb-5 flex items-center gap-3">
                    <div class="rounded-lg bg-slate-100 p-2">
                        <Lucide icon="Building2" class="h-5 w-5 text-slate-600" />
                    </div>
                    <h2 class="text-base font-semibold text-slate-800">Carrier & Assignment</h2>
                </div>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
                    <div>
                        <label class="mb-2 block text-xs font-medium uppercase tracking-wide text-slate-500">Carrier</label>
                        <TomSelect v-if="isSuperadmin" v-model="form.carrier_id">
                            <option value="">Select carrier</option>
                            <option v-for="carrier in carriers" :key="carrier.id" :value="String(carrier.id)">
                                {{ carrier.name }}
                            </option>
                        </TomSelect>
                        <FormInput v-else :model-value="carriers[0]?.name || 'Current Carrier'" disabled />
                        <p v-if="form.errors.carrier_id" class="mt-1 text-xs text-red-500">{{ form.errors.carrier_id }}</p>
                    </div>

                    <div>
                        <label class="mb-2 block text-xs font-medium uppercase tracking-wide text-slate-500">Driver</label>
                        <TomSelect v-model="form.driver_id" :disabled="!form.carrier_id || loadingCarrierData">
                            <option value="">{{ loadingCarrierData ? 'Loading drivers...' : 'Select driver' }}</option>
                            <option v-for="driver in driverOptions" :key="driver.id" :value="String(driver.id)" :disabled="!driver.can_drive">
                                {{ driver.name }} ({{ driver.hours_remaining }}h left{{ driver.can_drive ? '' : ' - over limit' }})
                            </option>
                        </TomSelect>
                        <p v-if="selectedDriver" class="mt-1 text-xs text-slate-500">
                            {{ selectedDriver.email || 'No email' }} - {{ selectedDriver.hours_remaining }} hours remaining this cycle
                        </p>
                        <p v-if="form.errors.driver_id" class="mt-1 text-xs text-red-500">{{ form.errors.driver_id }}</p>
                    </div>

                    <div>
                        <label class="mb-2 block text-xs font-medium uppercase tracking-wide text-slate-500">Vehicle</label>
                        <TomSelect v-model="form.vehicle_id" :disabled="!form.carrier_id || loadingCarrierData">
                            <option value="">{{ loadingCarrierData ? 'Loading vehicles...' : 'Select vehicle' }}</option>
                            <option v-for="vehicle in vehicleOptions" :key="vehicle.id" :value="String(vehicle.id)">
                                {{ vehicle.label }}
                            </option>
                        </TomSelect>
                        <p v-if="form.errors.vehicle_id" class="mt-1 text-xs text-red-500">{{ form.errors.vehicle_id }}</p>
                    </div>
                </div>
            </div>

            <div class="box box--stacked p-6">
                <div class="mb-5 flex items-center gap-3">
                    <div class="rounded-lg bg-slate-100 p-2">
                        <Lucide icon="Route" class="h-5 w-5 text-slate-600" />
                    </div>
                    <h2 class="text-base font-semibold text-slate-800">Route Information</h2>
                </div>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-xs font-medium uppercase tracking-wide text-slate-500">Origin Address</label>
                        <FormTextarea v-model="form.origin_address" rows="3" placeholder="Enter pickup address" />
                        <p v-if="form.errors.origin_address" class="mt-1 text-xs text-red-500">{{ form.errors.origin_address }}</p>
                    </div>

                    <div>
                        <label class="mb-2 block text-xs font-medium uppercase tracking-wide text-slate-500">Destination Address</label>
                        <FormTextarea v-model="form.destination_address" rows="3" placeholder="Enter delivery address" />
                        <p v-if="form.errors.destination_address" class="mt-1 text-xs text-red-500">{{ form.errors.destination_address }}</p>
                    </div>
                </div>
            </div>

            <div class="box box--stacked p-6">
                <div class="mb-5 flex items-center gap-3">
                    <div class="rounded-lg bg-slate-100 p-2">
                        <Lucide icon="CalendarRange" class="h-5 w-5 text-slate-600" />
                    </div>
                    <h2 class="text-base font-semibold text-slate-800">Schedule</h2>
                </div>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-3">
                    <div>
                        <label class="mb-2 block text-xs font-medium uppercase tracking-wide text-slate-500">Scheduled Start Date</label>
                        <Litepicker v-model="form.scheduled_start_date" :options="pickerOptions" />
                        <p v-if="form.errors.scheduled_start_date" class="mt-1 text-xs text-red-500">{{ form.errors.scheduled_start_date }}</p>
                    </div>

                    <div>
                        <label class="mb-2 block text-xs font-medium uppercase tracking-wide text-slate-500">Start Time</label>
                        <FormInput v-model="form.scheduled_start_time" type="time" />
                        <p v-if="form.errors.scheduled_start_time" class="mt-1 text-xs text-red-500">{{ form.errors.scheduled_start_time }}</p>
                    </div>

                    <div>
                        <label class="mb-2 block text-xs font-medium uppercase tracking-wide text-slate-500">Estimated Duration (minutes)</label>
                        <FormInput v-model="form.estimated_duration_minutes" type="number" min="1" placeholder="e.g. 120" />
                        <p v-if="form.errors.estimated_duration_minutes" class="mt-1 text-xs text-red-500">{{ form.errors.estimated_duration_minutes }}</p>
                    </div>

                    <div>
                        <label class="mb-2 block text-xs font-medium uppercase tracking-wide text-slate-500">Scheduled End Date</label>
                        <Litepicker v-model="form.scheduled_end_date" :options="pickerOptions" />
                        <p v-if="form.errors.scheduled_end_date" class="mt-1 text-xs text-red-500">{{ form.errors.scheduled_end_date }}</p>
                    </div>

                    <div>
                        <label class="mb-2 block text-xs font-medium uppercase tracking-wide text-slate-500">End Time</label>
                        <FormInput v-model="form.scheduled_end_time" type="time" />
                        <p v-if="form.errors.scheduled_end_time" class="mt-1 text-xs text-red-500">{{ form.errors.scheduled_end_time }}</p>
                    </div>
                </div>
            </div>

            <div class="box box--stacked p-6">
                <div class="mb-5 flex items-center gap-3">
                    <div class="rounded-lg bg-slate-100 p-2">
                        <Lucide icon="Package" class="h-5 w-5 text-slate-600" />
                    </div>
                    <h2 class="text-base font-semibold text-slate-800">Load & Notes</h2>
                </div>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-xs font-medium uppercase tracking-wide text-slate-500">Load Type</label>
                        <FormInput v-model="form.load_type" placeholder="General freight, refrigerated, etc." />
                        <p v-if="form.errors.load_type" class="mt-1 text-xs text-red-500">{{ form.errors.load_type }}</p>
                    </div>

                    <div>
                        <label class="mb-2 block text-xs font-medium uppercase tracking-wide text-slate-500">Load Weight</label>
                        <FormInput v-model="form.load_weight" type="number" min="0" step="0.01" placeholder="e.g. 5000" />
                        <p v-if="form.errors.load_weight" class="mt-1 text-xs text-red-500">{{ form.errors.load_weight }}</p>
                    </div>

                    <div>
                        <label class="mb-2 block text-xs font-medium uppercase tracking-wide text-slate-500">Description</label>
                        <FormTextarea v-model="form.description" rows="4" placeholder="Trip description..." />
                        <p v-if="form.errors.description" class="mt-1 text-xs text-red-500">{{ form.errors.description }}</p>
                    </div>

                    <div>
                        <label class="mb-2 block text-xs font-medium uppercase tracking-wide text-slate-500">Notes</label>
                        <FormTextarea v-model="form.notes" rows="4" placeholder="Internal notes..." />
                        <p v-if="form.errors.notes" class="mt-1 text-xs text-red-500">{{ form.errors.notes }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12 xl:col-span-4">
            <div class="box box--stacked sticky top-6 p-6">
                <div class="mb-5 flex items-center gap-3">
                    <div class="rounded-lg bg-primary/10 p-2">
                        <Lucide icon="CheckCircle2" class="h-5 w-5 text-primary" />
                    </div>
                    <h2 class="text-base font-semibold text-slate-800">Actions</h2>
                </div>

                <div class="space-y-3">
                    <Button type="submit" variant="primary" class="w-full flex items-center justify-center gap-2" :disabled="form.processing">
                        <Lucide icon="Save" class="h-4 w-4" />
                        {{ form.processing ? 'Saving...' : submitLabel }}
                    </Button>
                    <Link :href="cancelHref" class="block">
                        <Button type="button" variant="outline-secondary" class="w-full">
                            Cancel
                        </Button>
                    </Link>
                </div>

                <div class="mt-6 rounded-xl border border-dashed border-slate-200 bg-slate-50 p-4">
                    <h3 class="text-sm font-semibold text-slate-700">Quick Notes</h3>
                    <ul class="mt-3 space-y-2 text-sm text-slate-500">
                        <li>Select a carrier first to load its drivers and vehicles.</li>
                        <li>Dates use the `M/D/YYYY` format.</li>
                        <li>Only pending or accepted trips remain editable after creation.</li>
                    </ul>
                </div>
            </div>
        </div>
    </form>
</template>
