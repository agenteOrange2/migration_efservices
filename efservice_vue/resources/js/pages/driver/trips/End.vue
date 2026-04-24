<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import { computed } from 'vue'
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
        actual_start: string | null
        status_label: string
        vehicle_label: string
        has_trailer: boolean
    }
    inspection: {
        tractor_items: InspectionMap
        tractor_columns: Record<string, string[]>
        trailer_items: InspectionMap
        trailer_columns: Record<string, string[]>
    }
}>()

const form = useForm({
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
    notes: '',
})

const tractorTotal = computed(() => Object.keys(props.inspection.tractor_items).filter((key) => key !== 'other_tractor').length)
const trailerTotal = computed(() => Object.keys(props.inspection.trailer_items).filter((key) => key !== 'other_trailer').length)

const tractorProgress = computed(() => form.tractor.filter((item) => item !== 'other_tractor').length)
const trailerProgress = computed(() => form.trailer.filter((item) => item !== 'other_trailer').length)

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
    form.post(route('driver.trips.end', props.trip.id))
}
</script>

<template>
    <Head :title="`End ${trip.trip_number}`" />

    <div class="grid grid-cols-12 gap-4 sm:gap-6">
        <div class="col-span-12">
            <div class="box box--stacked p-4 sm:p-6">
                <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                    <div>
                        <Link :href="route('driver.trips.show', trip.id)" class="inline-flex items-center gap-2 text-sm font-medium text-slate-500 transition hover:text-primary">
                            <Lucide icon="ArrowLeft" class="h-4 w-4" />
                            Back to Trip
                        </Link>
                        <h1 class="mt-3 text-xl sm:text-2xl font-bold text-slate-800">End Trip</h1>
                        <p class="mt-1 text-sm text-slate-500">Complete the post-trip inspection and close out the trip cleanly.</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Trip</p>
                        <p class="mt-1 text-sm font-semibold text-slate-800">{{ trip.trip_number }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12 xl:col-span-8">
            <form class="space-y-4 sm:space-y-6" @submit.prevent="submit">
                <div class="box box--stacked p-4 sm:p-6">
                    <h2 class="text-base font-semibold text-slate-800">Trip Summary</h2>
                    <div class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4"><p class="text-xs uppercase tracking-[0.2em] text-slate-400">Started</p><p class="mt-2 text-sm text-slate-800 break-words">{{ trip.actual_start || 'N/A' }}</p></div>
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4"><p class="text-xs uppercase tracking-[0.2em] text-slate-400">Vehicle</p><p class="mt-2 text-sm text-slate-800 break-words">{{ trip.vehicle_label }}</p></div>
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4"><p class="text-xs uppercase tracking-[0.2em] text-slate-400">Origin</p><p class="mt-2 text-sm text-slate-800 break-words">{{ trip.origin_address || 'N/A' }}</p></div>
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4"><p class="text-xs uppercase tracking-[0.2em] text-slate-400">Destination</p><p class="mt-2 text-sm text-slate-800 break-words">{{ trip.destination_address || 'N/A' }}</p></div>
                    </div>
                </div>

                <div class="box box--stacked p-4 sm:p-6">
                    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 class="text-base font-semibold text-slate-800">Tractor / Truck Inspection</h2>
                            <p class="mt-1 text-sm text-slate-500">Review all required items before closing the trip.</p>
                        </div>
                        <Button type="button" variant="outline-secondary" class="w-full justify-center gap-2 sm:w-auto" @click="selectAll('tractor')">
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
                    <FormInput v-if="form.tractor.includes('other_tractor')" v-model="form.other_tractor" class="mt-4" placeholder="Describe the other tractor item" />
                </div>

                <div v-if="trip.has_trailer" class="box box--stacked p-4 sm:p-6">
                    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 class="text-base font-semibold text-slate-800">Trailer Inspection</h2>
                            <p class="mt-1 text-sm text-slate-500">Required because this trip started with a trailer.</p>
                        </div>
                        <Button type="button" variant="outline-secondary" class="w-full justify-center gap-2 sm:w-auto" @click="selectAll('trailer')">
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
                    <FormInput v-if="form.trailer.includes('other_trailer')" v-model="form.other_trailer" class="mt-4" placeholder="Describe the other trailer item" />
                </div>

                <div class="box box--stacked p-4 sm:p-6">
                    <h2 class="text-base font-semibold text-slate-800">Condition & Closeout Notes</h2>
                    <div class="mt-5 space-y-4">
                        <FormTextarea v-model="form.remarks" rows="4" placeholder="Describe any defects or post-trip remarks..." />
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
                        <FormTextarea v-model="form.notes" rows="4" placeholder="Any final notes, delays, or delivery comments..." />
                    </div>
                </div>

                <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <Link :href="route('driver.trips.show', trip.id)" class="w-full sm:w-auto">
                        <Button type="button" variant="outline-secondary" class="w-full justify-center sm:w-auto">Cancel</Button>
                    </Link>
                    <Button type="submit" variant="primary" class="w-full justify-center gap-2 sm:w-auto" :disabled="form.processing">
                        <Lucide icon="Square" class="h-4 w-4" />
                        {{ form.processing ? 'Ending...' : 'Complete Trip' }}
                    </Button>
                </div>
            </form>
        </div>

        <div class="col-span-12 xl:col-span-4">
            <div class="box box--stacked xl:sticky xl:top-6 p-4 sm:p-6">
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
                    <div v-if="trip.has_trailer">
                        <div class="flex items-center justify-between text-sm text-slate-600">
                            <span>Trailer</span>
                            <span>{{ trailerProgress }} / {{ trailerTotal }}</span>
                        </div>
                        <div class="mt-2 h-2 w-full overflow-hidden rounded-full bg-slate-200">
                            <div class="h-full rounded-full bg-primary" :style="{ width: `${(trailerProgress / Math.max(trailerTotal, 1)) * 100}%` }" />
                        </div>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
                        <p class="font-medium text-slate-800">Post-Trip Reminder</p>
                        <p class="mt-1">If you found anything that maintenance should inspect, be explicit in the remarks so the next handoff is clean.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
