<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import { computed, reactive } from 'vue'
import Button from '@/components/Base/Button'
import FormInput from '@/components/Base/Form/FormInput.vue'
import FormTextarea from '@/components/Base/Form/FormTextarea.vue'
import Lucide from '@/components/Base/Lucide'
import RazeLayout from '@/layouts/RazeLayout.vue'

declare function route(name: string, params?: any): string

defineOptions({ layout: RazeLayout })

interface InspectionMap {
    [key: string]: string
}

const props = defineProps<{
    trip: {
        id: number
        trip_number: string
        origin_address: string | null
        destination_address: string | null
        scheduled_start: string | null
        status_label: string
        vehicle_label: string
        has_trailer: boolean
    }
    validation: {
        valid: boolean
        errors: Array<{ message: string; fmcsa_reference?: string | null }>
        warnings: Array<{ message: string }>
        weekly_status?: { hours_remaining?: number }
    }
    inspection: {
        tractor_items: InspectionMap
        tractor_columns: Record<string, string[]>
        trailer_items: InspectionMap
        trailer_columns: Record<string, string[]>
    }
}>()

const form = useForm({
    has_trailer: props.trip.has_trailer,
    tractor: [] as string[],
    trailer: [] as string[],
    other_tractor: '',
    other_trailer: '',
    remarks: '',
    condition_satisfactory: false,
    defects_corrected: false,
    defects_corrected_notes: '',
    defects_not_need_correction: false,
    defects_not_need_correction_notes: '',
    driver_signature: '',
})

const tractorTotal = computed(() => Object.keys(props.inspection.tractor_items).filter((key) => key !== 'other_tractor').length)
const trailerTotal = computed(() => Object.keys(props.inspection.trailer_items).filter((key) => key !== 'other_trailer').length)

const tractorProgress = computed(() => form.tractor.filter((item) => item !== 'other_tractor').length)
const trailerProgress = computed(() => form.trailer.filter((item) => item !== 'other_trailer').length)

const signatureHint = reactive({
    fullName: '',
})

function toggleSelection(target: string[], value: string) {
    const index = target.indexOf(value)
    if (index >= 0) {
        target.splice(index, 1)
        return
    }

    target.push(value)
}

function selectAll(section: 'tractor' | 'trailer') {
    if (section === 'tractor') {
        form.tractor = Object.keys(props.inspection.tractor_items).filter((key) => key !== 'other_tractor')
        return
    }

    form.trailer = Object.keys(props.inspection.trailer_items).filter((key) => key !== 'other_trailer')
}

function isChecked(section: 'tractor' | 'trailer', value: string) {
    return section === 'tractor'
        ? form.tractor.includes(value)
        : form.trailer.includes(value)
}

function submit() {
    form.post(route('driver.trips.start', props.trip.id))
}
</script>

<template>
    <Head :title="`Start ${trip.trip_number}`" />

    <div class="grid grid-cols-12 gap-6">
        <div class="col-span-12">
            <div class="box box--stacked p-6">
                <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                    <div>
                        <Link :href="route('driver.trips.show', trip.id)" class="inline-flex items-center gap-2 text-sm font-medium text-slate-500 transition hover:text-primary">
                            <Lucide icon="ArrowLeft" class="h-4 w-4" />
                            Back to Trip
                        </Link>
                        <h1 class="mt-3 text-2xl font-bold text-slate-800">Start Trip</h1>
                        <p class="mt-1 text-sm text-slate-500">Complete the pre-trip inspection before you begin driving.</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Trip</p>
                        <p class="mt-1 text-sm font-semibold text-slate-800">{{ trip.trip_number }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="!validation.valid" class="col-span-12">
            <div class="box box--stacked p-6">
                <h2 class="text-base font-semibold text-slate-800">Cannot Start This Trip Yet</h2>
                <div class="mt-4 space-y-3">
                    <div v-for="error in validation.errors" :key="error.message" class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
                        <p class="font-medium text-slate-800">{{ error.message }}</p>
                        <p v-if="error.fmcsa_reference" class="mt-1 text-xs text-slate-400">Reference: {{ error.fmcsa_reference }}</p>
                    </div>
                </div>
            </div>
        </div>

        <template v-else>
            <div class="col-span-12 xl:col-span-8">
                <form class="space-y-6" @submit.prevent="submit">
                    <div v-if="validation.warnings.length" class="box box--stacked p-6">
                        <h2 class="text-base font-semibold text-slate-800">Warnings</h2>
                        <div class="mt-4 space-y-3">
                            <div v-for="warning in validation.warnings" :key="warning.message" class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
                                {{ warning.message }}
                            </div>
                        </div>
                    </div>

                    <div class="box box--stacked p-6">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <h2 class="text-base font-semibold text-slate-800">Trip Summary</h2>
                                <p class="mt-1 text-sm text-slate-500">{{ trip.vehicle_label }}</p>
                            </div>
                            <label class="inline-flex items-center gap-3 rounded-full border border-slate-200 bg-slate-50 px-4 py-2 text-sm text-slate-600">
                                <input v-model="form.has_trailer" type="checkbox" class="rounded border-slate-300 text-primary focus:ring-primary">
                                Trip includes trailer
                            </label>
                        </div>
                        <div class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4"><p class="text-xs uppercase tracking-[0.2em] text-slate-400">Origin</p><p class="mt-2 text-sm text-slate-800">{{ trip.origin_address || 'N/A' }}</p></div>
                            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4"><p class="text-xs uppercase tracking-[0.2em] text-slate-400">Destination</p><p class="mt-2 text-sm text-slate-800">{{ trip.destination_address || 'N/A' }}</p></div>
                        </div>
                    </div>

                    <div class="box box--stacked p-6">
                        <div class="mb-5 flex items-center justify-between gap-3">
                            <div>
                                <h2 class="text-base font-semibold text-slate-800">Tractor / Truck Inspection</h2>
                                <p class="mt-1 text-sm text-slate-500">All required items must be reviewed.</p>
                            </div>
                            <Button type="button" variant="outline-secondary" class="gap-2" @click="selectAll('tractor')">
                                <Lucide icon="CheckSquare" class="h-4 w-4" />
                                Select All
                            </Button>
                        </div>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                            <div v-for="(items, column) in inspection.tractor_columns" :key="column" class="space-y-2">
                                <label
                                    v-for="item in items"
                                    :key="item"
                                    class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700"
                                >
                                    <input
                                        :checked="isChecked('tractor', item)"
                                        type="checkbox"
                                        class="rounded border-slate-300 text-primary focus:ring-primary"
                                        @change="toggleSelection(form.tractor, item)"
                                    >
                                    {{ inspection.tractor_items[item] }}
                                </label>
                            </div>
                        </div>
                        <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                            <FormInput v-if="form.tractor.includes('other_tractor')" v-model="form.other_tractor" placeholder="Describe the other tractor item" />
                            <p v-if="form.errors.other_tractor" class="text-sm text-danger">{{ form.errors.other_tractor }}</p>
                        </div>
                    </div>

                    <div v-if="form.has_trailer" class="box box--stacked p-6">
                        <div class="mb-5 flex items-center justify-between gap-3">
                            <div>
                                <h2 class="text-base font-semibold text-slate-800">Trailer Inspection</h2>
                                <p class="mt-1 text-sm text-slate-500">Required because this trip includes a trailer.</p>
                            </div>
                            <Button type="button" variant="outline-secondary" class="gap-2" @click="selectAll('trailer')">
                                <Lucide icon="CheckSquare" class="h-4 w-4" />
                                Select All
                            </Button>
                        </div>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                            <div v-for="(items, column) in inspection.trailer_columns" :key="column" class="space-y-2">
                                <label
                                    v-for="item in items"
                                    :key="item"
                                    class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700"
                                >
                                    <input
                                        :checked="isChecked('trailer', item)"
                                        type="checkbox"
                                        class="rounded border-slate-300 text-primary focus:ring-primary"
                                        @change="toggleSelection(form.trailer, item)"
                                    >
                                    {{ inspection.trailer_items[item] }}
                                </label>
                            </div>
                        </div>
                        <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                            <FormInput v-if="form.trailer.includes('other_trailer')" v-model="form.other_trailer" placeholder="Describe the other trailer item" />
                            <p v-if="form.errors.other_trailer" class="text-sm text-danger">{{ form.errors.other_trailer }}</p>
                        </div>
                    </div>

                    <div class="box box--stacked p-6">
                        <h2 class="text-base font-semibold text-slate-800">Condition & Notes</h2>
                        <div class="mt-5 space-y-4">
                            <FormTextarea v-model="form.remarks" rows="4" placeholder="Describe any defects, remarks, or observations..." />
                            <label class="flex items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-4 text-sm text-slate-700">
                                <input v-model="form.condition_satisfactory" type="checkbox" class="mt-1 rounded border-slate-300 text-primary focus:ring-primary">
                                <span>Condition of the above vehicle is satisfactory.</span>
                            </label>
                            <label class="flex items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-4 text-sm text-slate-700">
                                <input v-model="form.defects_corrected" type="checkbox" class="mt-1 rounded border-slate-300 text-primary focus:ring-primary">
                                <span>Above defects corrected.</span>
                            </label>
                            <FormTextarea v-if="form.defects_corrected" v-model="form.defects_corrected_notes" rows="3" placeholder="Describe the corrections made..." />
                            <label class="flex items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-4 text-sm text-slate-700">
                                <input v-model="form.defects_not_need_correction" type="checkbox" class="mt-1 rounded border-slate-300 text-primary focus:ring-primary">
                                <span>Above defects do not need correction for safe operation.</span>
                            </label>
                            <FormTextarea v-if="form.defects_not_need_correction" v-model="form.defects_not_need_correction_notes" rows="3" placeholder="Explain why the defects do not affect safe operation..." />
                            <FormInput v-model="form.driver_signature" placeholder="Type your full name as your driver signature" />
                        </div>
                    </div>

                    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                        <Link :href="route('driver.trips.show', trip.id)">
                            <Button type="button" variant="outline-secondary">Cancel</Button>
                        </Link>
                        <Button type="submit" variant="primary" class="gap-2" :disabled="form.processing">
                            <Lucide icon="Play" class="h-4 w-4" />
                            {{ form.processing ? 'Starting...' : 'Start Trip' }}
                        </Button>
                    </div>
                </form>
            </div>

            <div class="col-span-12 xl:col-span-4">
                <div class="box box--stacked sticky top-6 p-6">
                    <h2 class="text-base font-semibold text-slate-800">Inspection Progress</h2>
                    <div class="mt-5 space-y-4">
                        <div>
                            <div class="flex items-center justify-between text-sm text-slate-600">
                                <span>Tractor</span>
                                <span>{{ tractorProgress }} / {{ tractorTotal }}</span>
                            </div>
                            <div class="mt-2 h-2 w-full overflow-hidden rounded-full bg-slate-200">
                                <div class="h-full rounded-full bg-primary" :style="{ width: `${(tractorProgress / Math.max(tractorTotal, 1)) * 100}%` }" />
                            </div>
                        </div>
                        <div v-if="form.has_trailer">
                            <div class="flex items-center justify-between text-sm text-slate-600">
                                <span>Trailer</span>
                                <span>{{ trailerProgress }} / {{ trailerTotal }}</span>
                            </div>
                            <div class="mt-2 h-2 w-full overflow-hidden rounded-full bg-slate-200">
                                <div class="h-full rounded-full bg-primary" :style="{ width: `${(trailerProgress / Math.max(trailerTotal, 1)) * 100}%` }" />
                            </div>
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
                            <p class="font-medium text-slate-800">Driver Signature</p>
                            <p class="mt-1">Typing your full name acts as your acknowledgement for this inspection report.</p>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>
</template>
