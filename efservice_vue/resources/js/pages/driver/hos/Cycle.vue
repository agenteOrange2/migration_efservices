<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import { computed } from 'vue'
import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import RazeLayout from '@/layouts/RazeLayout.vue'

declare function route(name: string, params?: any): string

defineOptions({ layout: RazeLayout })

const props = defineProps<{
    driver: {
        id: number
        full_name: string
        carrier_name: string | null
    }
    currentCycleType: string
    cycleStatus: {
        cycle_type: string
        cycle_type_name: string
        hours_used: number
        hours_remaining: number
        hours_limit: number
        percentage_used: number
        status_color: string
        is_over_limit: boolean
        is_approaching_limit: boolean
        is_at_warning?: boolean
    }
    dailyBreakdown: {
        date: string
        day_name: string
        driving_hours: number
        on_duty_hours: number
        total_duty_minutes: number
        has_violations: boolean
    }[]
    pendingRequest: {
        requested_to: string
        requested_to_label: string
        requested_at: string | null
    } | null
}>()

const requestForm = useForm({
    new_cycle_type: '',
})

const cycleCards = computed(() => [
    {
        value: '60_7',
        label: '60 Hours / 7 Days',
        description: 'Standard weekly cycle for most operations.',
        active: props.currentCycleType === '60_7',
    },
    {
        value: '70_8',
        label: '70 Hours / 8 Days',
        description: 'Extended weekly cycle, often used for Texas intrastate operations.',
        active: props.currentCycleType === '70_8',
    },
])

function submitRequest() {
    if (!requestForm.new_cycle_type) return
    requestForm.post(route('driver.hos.cycle.request'), { preserveScroll: true })
}

function cancelRequest() {
    if (!confirm('Cancel the pending cycle change request?')) return
    requestForm.post(route('driver.hos.cycle.cancel'), { preserveScroll: true })
}

function cycleTone() {
    if (props.cycleStatus.is_over_limit) return 'Over Limit'
    if (props.cycleStatus.is_approaching_limit) return 'Approaching Limit'
    return 'Healthy'
}
</script>

<template>
    <Head title="HOS Cycle Settings" />

    <div class="grid grid-cols-12 gap-6">
        <div class="col-span-12">
            <div class="box box--stacked p-6">
                <div class="flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between">
                    <div class="flex items-start gap-4">
                        <div class="rounded-2xl border border-primary/20 bg-primary/10 p-3">
                            <Lucide icon="Settings" class="h-8 w-8 text-primary" />
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-slate-800">HOS Cycle Settings</h1>
                            <p class="mt-1 text-slate-500">Review your weekly cycle, remaining hours, and request a different cycle when needed.</p>
                            <p class="mt-2 text-sm text-slate-500">
                                Driver: <span class="font-medium text-slate-700">{{ driver.full_name }}</span>
                                <span v-if="driver.carrier_name"> · Carrier: <span class="font-medium text-slate-700">{{ driver.carrier_name }}</span></span>
                            </p>
                        </div>
                    </div>

                    <Link :href="route('driver.hos.dashboard')">
                        <Button variant="outline-secondary" class="gap-2">
                            <Lucide icon="ArrowLeft" class="h-4 w-4" />
                            Back to HOS
                        </Button>
                    </Link>
                </div>
            </div>
        </div>

        <div class="col-span-12 lg:col-span-8 space-y-6">
            <div class="box box--stacked p-6">
                <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-800">Current Cycle Status</h2>
                        <p class="mt-1 text-sm text-slate-500">{{ cycleStatus.cycle_type_name }}</p>
                    </div>
                    <span class="inline-flex rounded-full bg-primary/10 px-3 py-1 text-xs font-medium text-primary">{{ cycleTone() }}</span>
                </div>

                <div class="mt-5 rounded-2xl border border-primary/20 bg-primary/5 p-6 text-center">
                    <p class="text-sm text-slate-500">Your Current Cycle</p>
                    <p class="mt-2 text-4xl font-bold text-primary">{{ currentCycleType === '70_8' ? '70' : '60' }} Hours</p>
                    <p class="mt-1 text-sm text-slate-600">{{ currentCycleType === '70_8' ? '8 Days Rolling Period' : '7 Days Rolling Period' }}</p>
                </div>

                <div class="mt-6">
                    <div class="flex items-center justify-between text-sm text-slate-500">
                        <span>{{ cycleStatus.hours_used }} / {{ cycleStatus.hours_limit }} hours used</span>
                        <span>{{ cycleStatus.hours_remaining }} hours remaining</span>
                    </div>
                    <div class="mt-2 h-3 overflow-hidden rounded-full bg-slate-100">
                        <div class="h-full rounded-full bg-primary transition-all duration-300" :style="{ width: `${Math.min(cycleStatus.percentage_used, 100)}%` }" />
                    </div>
                    <div class="mt-2 flex items-center justify-between text-xs text-slate-500">
                        <span>{{ cycleStatus.percentage_used }}% used</span>
                        <span>{{ cycleStatus.hours_limit }} hour limit</span>
                    </div>
                </div>

                <div class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div class="rounded-xl border border-slate-200 p-4"><p class="text-xs font-medium uppercase tracking-[0.2em] text-slate-400">Cycle Type</p><p class="mt-2 font-semibold text-slate-800">{{ cycleStatus.cycle_type_name }}</p></div>
                    <div class="rounded-xl border border-slate-200 p-4"><p class="text-xs font-medium uppercase tracking-[0.2em] text-slate-400">Hours Remaining</p><p class="mt-2 font-semibold text-slate-800">{{ cycleStatus.hours_remaining }} hours</p></div>
                    <div class="rounded-xl border border-slate-200 p-4"><p class="text-xs font-medium uppercase tracking-[0.2em] text-slate-400">Status</p><p class="mt-2 font-semibold text-slate-800">{{ cycleTone() }}</p></div>
                </div>
            </div>

            <div class="box box--stacked p-6">
                <div class="flex items-center gap-2">
                    <Lucide icon="RefreshCw" class="h-5 w-5 text-primary" />
                    <h2 class="text-lg font-semibold text-slate-800">Request Cycle Change</h2>
                </div>

                <div v-if="pendingRequest" class="mt-5 rounded-2xl border border-primary/20 bg-primary/5 p-5">
                    <div class="flex items-start gap-4">
                        <div class="rounded-xl bg-primary/10 p-2 text-primary">
                            <Lucide icon="Clock3" class="h-5 w-5" />
                        </div>
                        <div class="flex-1">
                            <h3 class="font-semibold text-slate-800">Pending Request</h3>
                            <p class="mt-1 text-sm text-slate-600">You requested to change to <strong>{{ pendingRequest.requested_to_label }}</strong>.</p>
                            <p class="mt-1 text-xs text-slate-500">Submitted {{ pendingRequest.requested_at || 'recently' }}. Waiting for carrier approval.</p>
                            <div class="mt-4">
                                <Button variant="outline-secondary" class="gap-2" @click="cancelRequest"><Lucide icon="X" class="h-4 w-4" />Cancel Request</Button>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-else class="mt-5">
                    <p class="text-sm text-slate-500">You can request a different weekly cycle when your operation requires it. Your carrier must approve the change before it takes effect.</p>

                    <div class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-2">
                        <label
                            v-for="card in cycleCards"
                            :key="card.value"
                            class="cursor-pointer rounded-2xl border-2 p-5 transition"
                            :class="requestForm.new_cycle_type === card.value ? 'border-primary bg-primary/5' : 'border-slate-200 hover:border-slate-300'"
                        >
                            <input v-model="requestForm.new_cycle_type" type="radio" class="sr-only" :value="card.value" :disabled="card.active">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-lg font-semibold text-slate-800">{{ card.label }}</p>
                                    <p class="mt-1 text-sm text-slate-500">{{ card.description }}</p>
                                </div>
                                <span v-if="card.active" class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-600">Current</span>
                            </div>
                        </label>
                    </div>

                    <div class="mt-5">
                        <Button variant="primary" class="gap-2" :disabled="requestForm.processing || !requestForm.new_cycle_type" @click="submitRequest"><Lucide icon="Send" class="h-4 w-4" />Submit Change Request</Button>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12 lg:col-span-4 space-y-6">
            <div class="box box--stacked p-6">
                <h2 class="text-lg font-semibold text-slate-800">Rolling Breakdown</h2>
                <div class="mt-4 space-y-3">
                    <div v-for="day in dailyBreakdown" :key="day.date" class="rounded-xl border border-slate-200 px-4 py-3">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="text-sm font-medium text-slate-800">{{ day.day_name }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ day.date }}</p>
                            </div>
                            <span v-if="day.has_violations" class="rounded-full bg-primary/10 px-2 py-0.5 text-[11px] font-medium text-primary">Violation</span>
                        </div>
                        <div class="mt-3 grid grid-cols-2 gap-3 text-xs text-slate-500">
                            <div class="rounded-lg bg-slate-50 px-3 py-2"><p class="uppercase tracking-[0.2em] text-slate-400">Driving</p><p class="mt-1 text-sm font-semibold text-slate-800">{{ day.driving_hours.toFixed(1) }}h</p></div>
                            <div class="rounded-lg bg-slate-50 px-3 py-2"><p class="uppercase tracking-[0.2em] text-slate-400">On Duty</p><p class="mt-1 text-sm font-semibold text-slate-800">{{ day.on_duty_hours.toFixed(1) }}h</p></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="box box--stacked p-6">
                <h2 class="text-lg font-semibold text-slate-800">Reset Reminder</h2>
                <p class="mt-3 text-sm text-slate-500">To regain full weekly hours, you generally need a 34-hour off-duty reset. If you are close to the limit, plan breaks early instead of waiting until you are blocked.</p>
            </div>
        </div>
    </div>
</template>
