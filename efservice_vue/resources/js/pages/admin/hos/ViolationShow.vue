<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { reactive } from 'vue'
import Button from '@/components/Base/Button'
import { FormTextarea } from '@/components/Base/Form'
import Lucide from '@/components/Base/Lucide'
import RazeLayout from '@/layouts/RazeLayout.vue'

declare function route(name: string, params?: any): string

defineOptions({ layout: RazeLayout })

const props = defineProps<{
    violation: Record<string, any>
    routeNames?: {
        index?: string
        acknowledge?: string
        forgive?: string
    }
}>()

const forgiveness = reactive({
    forgiveness_reason: '',
    adjusted_end_time: '',
})

function acknowledge() {
    router.post(route(props.routeNames?.acknowledge ?? 'admin.hos.violations.acknowledge', props.violation.id), {}, { preserveScroll: true })
}

function forgive() {
    router.post(route(props.routeNames?.forgive ?? 'admin.hos.violations.forgive', props.violation.id), forgiveness, { preserveScroll: true })
}
</script>

<template>
    <Head :title="`Violation #${violation.id}`" />

    <div class="grid grid-cols-12 gap-6">
        <div class="col-span-12">
            <div class="box box--stacked p-6">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex items-center gap-4">
                        <div class="rounded-2xl border border-primary/20 bg-primary/10 p-3">
                            <Lucide icon="ShieldAlert" class="h-8 w-8 text-primary" />
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-slate-800">Violation #{{ violation.id }}</h1>
                            <p class="mt-1 text-sm text-slate-500">{{ violation.type }} for {{ violation.driver_name }}</p>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <Button v-if="violation.can_acknowledge" variant="success" class="gap-2" @click="acknowledge">
                            <Lucide icon="BadgeCheck" class="h-4 w-4" />
                            Acknowledge
                        </Button>
                        <Link :href="route(props.routeNames?.index ?? 'admin.hos.violations')" class="inline-flex items-center gap-2 text-sm font-medium text-primary hover:underline">
                            <Lucide icon="ArrowLeft" class="h-4 w-4" />
                            Back to violations
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12 xl:col-span-8">
            <div class="box box--stacked p-6">
                <h2 class="text-lg font-semibold text-slate-800">Violation Details</h2>
                <div class="mt-5 grid grid-cols-1 gap-4 text-sm md:grid-cols-2 xl:grid-cols-3">
                    <div><div class="text-slate-500">Driver</div><div class="font-medium text-slate-800">{{ violation.driver_name }}</div></div>
                    <div><div class="text-slate-500">Email</div><div class="font-medium text-slate-800">{{ violation.driver_email || 'N/A' }}</div></div>
                    <div><div class="text-slate-500">Carrier</div><div class="font-medium text-slate-800">{{ violation.carrier_name || 'N/A' }}</div></div>
                    <div><div class="text-slate-500">Vehicle</div><div class="font-medium text-slate-800">{{ violation.vehicle_label || 'N/A' }}</div></div>
                    <div><div class="text-slate-500">Trip</div><div class="font-medium text-slate-800">{{ violation.trip_number || 'N/A' }}</div></div>
                    <div><div class="text-slate-500">Violation Date</div><div class="font-medium text-slate-800">{{ violation.date || 'N/A' }}</div></div>
                    <div><div class="text-slate-500">Severity</div><div class="font-medium"><span class="rounded-full px-2.5 py-1 text-xs font-medium" :class="String(violation.severity || '').toLowerCase().includes('high') || String(violation.severity || '').toLowerCase().includes('critical') ? 'bg-danger/10 text-danger' : 'bg-warning/10 text-warning'">{{ violation.severity }}</span></div></div>
                    <div><div class="text-slate-500">Exceeded Time</div><div class="font-medium text-slate-800">{{ violation.formatted_hours_exceeded }}</div></div>
                    <div><div class="text-slate-500">FMCSA Rule</div><div class="font-medium text-slate-800">{{ violation.fmcsa_rule_reference || 'N/A' }}</div></div>
                    <div><div class="text-slate-500">Trip Start</div><div class="font-medium text-slate-800">{{ violation.trip_actual_start || 'N/A' }}</div></div>
                    <div><div class="text-slate-500">Trip End</div><div class="font-medium text-slate-800">{{ violation.trip_actual_end || 'N/A' }}</div></div>
                    <div><div class="text-slate-500">Acknowledged</div><div class="font-medium"><span class="rounded-full px-2.5 py-1 text-xs font-medium" :class="violation.acknowledged ? 'bg-success/10 text-success' : 'bg-warning/10 text-warning'">{{ violation.acknowledged ? `Yes · ${violation.acknowledged_at || 'Recorded'}` : 'No' }}</span></div></div>
                    <div><div class="text-slate-500">Penalty Type</div><div class="font-medium text-slate-800">{{ violation.penalty_type || 'N/A' }}</div></div>
                    <div><div class="text-slate-500">Penalty Start</div><div class="font-medium text-slate-800">{{ violation.penalty_start || 'N/A' }}</div></div>
                    <div><div class="text-slate-500">Penalty End</div><div class="font-medium text-slate-800">{{ violation.penalty_end || 'N/A' }}</div></div>
                </div>

                <div v-if="violation.penalty_notes" class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700">
                    <div class="mb-1 font-medium text-slate-800">Penalty Notes</div>
                    {{ violation.penalty_notes }}
                </div>

                <div v-if="violation.is_forgiven" class="mt-6 rounded-2xl border border-info/20 bg-info/5 p-4 text-sm">
                    <div class="font-medium text-slate-800">Forgiveness Summary</div>
                    <div class="mt-2 text-slate-700">Reason: {{ violation.forgiveness_reason || 'N/A' }}</div>
                    <div class="mt-1 text-slate-700">Forgiven by: {{ violation.forgiven_by || 'N/A' }}{{ violation.forgiven_at ? ` on ${violation.forgiven_at}` : '' }}</div>
                    <div class="mt-1 text-slate-700">Original trip end: {{ violation.original_trip_end_time || 'N/A' }}</div>
                    <div class="mt-1 text-slate-700">Adjusted trip end: {{ violation.adjusted_trip_end_time || 'N/A' }}</div>
                </div>
            </div>
        </div>

        <div class="col-span-12 xl:col-span-4">
            <div class="box box--stacked p-6">
                <h2 class="text-lg font-semibold text-slate-800">Forgive Violation</h2>
                <p class="mt-1 text-sm text-slate-500">Use this only when operations can justify correcting the violation.</p>

                <div v-if="violation.can_forgive" class="mt-5 space-y-4">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">Justification</label>
                        <FormTextarea v-model="forgiveness.forgiveness_reason" rows="6" placeholder="Explain why this violation should be forgiven..." />
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">Adjusted Trip End Time</label>
                        <input v-model="forgiveness.adjusted_end_time" type="datetime-local" class="form-control w-full" />
                        <p class="mt-1 text-xs text-slate-500">Optional. Use when the trip should have ended earlier and that caused the violation.</p>
                    </div>

                    <Button variant="warning" class="w-full gap-2" @click="forgive">
                        <Lucide icon="CheckCircle2" class="h-4 w-4" />
                        Forgive Violation
                    </Button>
                </div>

                <div v-else class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
                    This violation has already been forgiven.
                </div>
            </div>
        </div>
    </div>
</template>
