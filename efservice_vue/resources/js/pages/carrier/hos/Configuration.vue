<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import Button from '@/components/Base/Button'
import { FormInput } from '@/components/Base/Form'
import Lucide from '@/components/Base/Lucide'
import CarrierLayout from '@/layouts/CarrierLayout.vue'

declare function route(name: string, params?: any): string

defineOptions({ layout: CarrierLayout })

const props = defineProps<{
    carrier: { id: number; name: string }
    config: {
        max_driving_hours: number
        max_duty_hours: number
        warning_threshold_minutes: number
        violation_threshold_minutes: number
        fmcsa_texas_mode: boolean
        allow_24_hour_reset: boolean
        require_30_min_break: boolean
        break_after_hours: number
        weekly_limit_60_hours: number
        weekly_limit_70_hours: number
        enable_ghost_log_detection: boolean
        ghost_log_threshold_minutes: number
        is_active: boolean
    }
    defaults: {
        max_driving_hours: number
        max_duty_hours: number
        warning_threshold_minutes: number
        violation_threshold_minutes: number
        weekly_limit_60_hours: number
        weekly_limit_70_hours: number
        break_after_hours: number
        ghost_log_threshold_minutes: number
    }
}>()

const form = useForm({
    max_driving_hours: props.config.max_driving_hours,
    max_duty_hours: props.config.max_duty_hours,
    warning_threshold_minutes: props.config.warning_threshold_minutes,
    violation_threshold_minutes: props.config.violation_threshold_minutes,
    fmcsa_texas_mode: props.config.fmcsa_texas_mode,
    allow_24_hour_reset: props.config.allow_24_hour_reset,
    require_30_min_break: props.config.require_30_min_break,
    break_after_hours: props.config.break_after_hours,
    weekly_limit_60_hours: props.config.weekly_limit_60_hours,
    weekly_limit_70_hours: props.config.weekly_limit_70_hours,
    enable_ghost_log_detection: props.config.enable_ghost_log_detection,
    ghost_log_threshold_minutes: props.config.ghost_log_threshold_minutes,
    is_active: props.config.is_active,
})

function resetToDefaults() {
    form.max_driving_hours = props.defaults.max_driving_hours
    form.max_duty_hours = props.defaults.max_duty_hours
    form.warning_threshold_minutes = props.defaults.warning_threshold_minutes
    form.violation_threshold_minutes = props.defaults.violation_threshold_minutes
    form.break_after_hours = props.defaults.break_after_hours
    form.weekly_limit_60_hours = props.defaults.weekly_limit_60_hours
    form.weekly_limit_70_hours = props.defaults.weekly_limit_70_hours
    form.ghost_log_threshold_minutes = props.defaults.ghost_log_threshold_minutes
    form.fmcsa_texas_mode = true
    form.allow_24_hour_reset = true
    form.require_30_min_break = true
    form.enable_ghost_log_detection = true
    form.is_active = true
}

function submit() {
    form.put(route('carrier.hos.fmcsa.configuration.update'))
}
</script>

<template>
    <Head title="FMCSA HOS Configuration" />

    <div class="grid grid-cols-12 gap-6">
        <div class="col-span-12">
            <div class="box box--stacked p-6">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex items-center gap-4">
                        <div class="rounded-2xl border border-primary/20 bg-primary/10 p-3">
                            <Lucide icon="Settings" class="h-8 w-8 text-primary" />
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-slate-800">FMCSA Configuration</h1>
                            <p class="mt-1 text-sm text-slate-500">Fine-tune HOS rules for {{ carrier.name }} without leaving the carrier portal.</p>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <Button variant="outline-secondary" class="gap-2" @click="resetToDefaults">
                            <Lucide icon="RotateCcw" class="h-4 w-4" />
                            Reset Defaults
                        </Button>
                        <Link :href="route('carrier.hos.dashboard')" class="inline-flex items-center gap-2 text-sm font-medium text-primary hover:underline">
                            <Lucide icon="ArrowLeft" class="h-4 w-4" />
                            Back to HOS
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12 xl:col-span-6">
            <div class="box box--stacked p-6">
                <h2 class="text-lg font-semibold text-slate-800">Core Limits</h2>
                <div class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">Max Driving Hours</label>
                        <FormInput v-model="form.max_driving_hours" type="number" min="1" max="24" step="0.25" />
                        <div v-if="form.errors.max_driving_hours" class="mt-1 text-xs text-primary">{{ form.errors.max_driving_hours }}</div>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">Max Duty Hours</label>
                        <FormInput v-model="form.max_duty_hours" type="number" min="1" max="24" step="0.25" />
                        <div v-if="form.errors.max_duty_hours" class="mt-1 text-xs text-primary">{{ form.errors.max_duty_hours }}</div>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">Warning Threshold Minutes</label>
                        <FormInput v-model="form.warning_threshold_minutes" type="number" min="0" max="180" />
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">Violation Threshold Minutes</label>
                        <FormInput v-model="form.violation_threshold_minutes" type="number" min="0" max="180" />
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">60 / 7 Weekly Limit Hours</label>
                        <FormInput v-model="form.weekly_limit_60_hours" type="number" min="1" max="168" />
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">70 / 8 Weekly Limit Hours</label>
                        <FormInput v-model="form.weekly_limit_70_hours" type="number" min="1" max="192" />
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12 xl:col-span-6">
            <div class="box box--stacked p-6">
                <h2 class="text-lg font-semibold text-slate-800">Operational Rules</h2>
                <div class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">Break After Hours</label>
                        <FormInput v-model="form.break_after_hours" type="number" min="1" max="24" />
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">Ghost Log Threshold Minutes</label>
                        <FormInput v-model="form.ghost_log_threshold_minutes" type="number" min="5" max="240" />
                    </div>
                </div>

                <div class="mt-5 space-y-3">
                    <label class="flex items-center justify-between rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <div>
                            <div class="text-sm font-medium text-slate-800">Configuration Active</div>
                            <div class="text-xs text-slate-500">Disable only if you need to suspend carrier-level HOS enforcement.</div>
                        </div>
                        <input v-model="form.is_active" type="checkbox" class="form-check-input" />
                    </label>
                    <label class="flex items-center justify-between rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <div>
                            <div class="text-sm font-medium text-slate-800">FMCSA Texas Intrastate Mode</div>
                            <div class="text-xs text-slate-500">Use Texas intrastate HOS rules when your operation requires them.</div>
                        </div>
                        <input v-model="form.fmcsa_texas_mode" type="checkbox" class="form-check-input" />
                    </label>
                    <label class="flex items-center justify-between rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <div>
                            <div class="text-sm font-medium text-slate-800">Allow 24-Hour Reset</div>
                            <div class="text-xs text-slate-500">Useful for specific construction or oilfield scenarios.</div>
                        </div>
                        <input v-model="form.allow_24_hour_reset" type="checkbox" class="form-check-input" />
                    </label>
                    <label class="flex items-center justify-between rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <div>
                            <div class="text-sm font-medium text-slate-800">Require 30-Minute Break</div>
                            <div class="text-xs text-slate-500">Warn drivers once they approach the break threshold.</div>
                        </div>
                        <input v-model="form.require_30_min_break" type="checkbox" class="form-check-input" />
                    </label>
                    <label class="flex items-center justify-between rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <div>
                            <div class="text-sm font-medium text-slate-800">Enable Ghost Log Detection</div>
                            <div class="text-xs text-slate-500">Flag possible forgotten trip closures automatically.</div>
                        </div>
                        <input v-model="form.enable_ghost_log_detection" type="checkbox" class="form-check-input" />
                    </label>
                </div>
            </div>
        </div>

        <div class="col-span-12">
            <div class="box box--stacked p-5">
                <div class="flex flex-wrap items-center justify-end gap-3">
                    <Button variant="outline-secondary" @click="resetToDefaults">Reset</Button>
                    <Button variant="primary" class="gap-2" :disabled="form.processing" @click="submit">
                        <Lucide icon="Save" class="h-4 w-4" />
                        Save Configuration
                    </Button>
                </div>
            </div>
        </div>
    </div>
</template>
