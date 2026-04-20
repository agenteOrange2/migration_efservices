<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { reactive } from 'vue'
import Button from '@/components/Base/Button'
import Litepicker from '@/components/Base/Litepicker/Litepicker.vue'
import Lucide from '@/components/Base/Lucide'
import TomSelect from '@/components/Base/TomSelect/TomSelect.vue'
import RazeLayout from '@/layouts/RazeLayout.vue'

declare function route(name: string, params?: any): string

defineOptions({ layout: RazeLayout })

type PaginationLink = { url: string | null; label: string; active: boolean }

const props = defineProps<{
    filters: { carrier_id: string; driver_id: string; violation_type: string; severity: string; date_from: string; date_to: string; acknowledged: string }
    stats: { total: number; acknowledged: number; unacknowledged: number; forgiven: number }
    carriers: { id: number; name: string }[]
    drivers: { id: number; name: string }[]
    violationTypes: { value: string; label: string }[]
    severities: { value: string; label: string }[]
    canFilterCarriers: boolean
    violations: { data: any[]; links: PaginationLink[] }
    routeNames?: {
        index?: string
        show?: string
    }
}>()

const filters = reactive({ ...props.filters })

const pickerOptions = {
    autoApply: true,
    singleMode: true,
    numberOfColumns: 1,
    numberOfMonths: 1,
    format: 'M/D/YYYY',
}

function severityTone(severity: string) {
    const value = String(severity || '').toLowerCase()
    if (['high', 'critical', 'major'].some((item) => value.includes(item))) return 'bg-danger/10 text-danger'
    if (['medium', 'moderate'].some((item) => value.includes(item))) return 'bg-warning/10 text-warning'
    if (['low', 'minor'].some((item) => value.includes(item))) return 'bg-info/10 text-info'
    return 'bg-primary/10 text-primary'
}

function applyFilters() {
    router.get(route(props.routeNames?.index ?? 'admin.hos.violations'), {
        carrier_id: filters.carrier_id || undefined,
        driver_id: filters.driver_id || undefined,
        violation_type: filters.violation_type || undefined,
        severity: filters.severity || undefined,
        date_from: filters.date_from || undefined,
        date_to: filters.date_to || undefined,
        acknowledged: filters.acknowledged || undefined,
    }, { preserveState: true, preserveScroll: true, replace: true })
}

function resetFilters() {
    filters.carrier_id = ''
    filters.driver_id = ''
    filters.violation_type = ''
    filters.severity = ''
    filters.date_from = ''
    filters.date_to = ''
    filters.acknowledged = ''
    applyFilters()
}
</script>

<template>
    <Head title="HOS Violations" />

    <div class="grid grid-cols-12 gap-6">
        <div class="col-span-12">
            <div class="box box--stacked p-6">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex items-center gap-4">
                        <div class="rounded-2xl border border-primary/20 bg-primary/10 p-3">
                            <Lucide icon="AlertTriangle" class="h-8 w-8 text-primary" />
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-slate-800">HOS Violations</h1>
                            <p class="mt-1 text-sm text-slate-500">Review, acknowledge, and forgive driver violations with full trip context.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="box box--stacked border border-primary/10 bg-primary/[0.04] p-5"><div class="text-sm text-slate-500">Total</div><div class="mt-2 text-3xl font-semibold text-primary">{{ stats.total }}</div></div>
            <div class="box box--stacked border border-success/10 bg-success/[0.04] p-5"><div class="text-sm text-slate-500">Acknowledged</div><div class="mt-2 text-3xl font-semibold text-success">{{ stats.acknowledged }}</div></div>
            <div class="box box--stacked border border-warning/10 bg-warning/[0.04] p-5"><div class="text-sm text-slate-500">Pending</div><div class="mt-2 text-3xl font-semibold text-warning">{{ stats.unacknowledged }}</div></div>
            <div class="box box--stacked border border-info/10 bg-info/[0.04] p-5"><div class="text-sm text-slate-500">Forgiven</div><div class="mt-2 text-3xl font-semibold text-info">{{ stats.forgiven }}</div></div>
        </div>

        <div class="col-span-12">
            <div class="box box--stacked p-5">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <TomSelect v-if="canFilterCarriers" v-model="filters.carrier_id">
                        <option value="">All Carriers</option>
                        <option v-for="carrier in carriers" :key="carrier.id" :value="String(carrier.id)">{{ carrier.name }}</option>
                    </TomSelect>
                    <TomSelect v-model="filters.driver_id">
                        <option value="">All Drivers</option>
                        <option v-for="driver in drivers" :key="driver.id" :value="String(driver.id)">{{ driver.name }}</option>
                    </TomSelect>
                    <TomSelect v-model="filters.violation_type">
                        <option value="">All Types</option>
                        <option v-for="option in violationTypes" :key="option.value" :value="option.value">{{ option.label }}</option>
                    </TomSelect>
                    <TomSelect v-model="filters.severity">
                        <option value="">All Severities</option>
                        <option v-for="option in severities" :key="option.value" :value="option.value">{{ option.label }}</option>
                    </TomSelect>
                    <TomSelect v-model="filters.acknowledged">
                        <option value="">All Acknowledgment</option>
                        <option value="yes">Acknowledged</option>
                        <option value="no">Pending</option>
                    </TomSelect>
                    <Litepicker v-model="filters.date_from" :options="pickerOptions" />
                    <Litepicker v-model="filters.date_to" :options="pickerOptions" />
                    <div class="flex gap-3 xl:col-span-2">
                        <Button variant="primary" class="w-full" @click="applyFilters">Apply</Button>
                        <Button variant="outline-secondary" class="w-full" @click="resetFilters">Reset</Button>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12">
            <div class="box box--stacked overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Driver</th>
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Carrier</th>
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Trip</th>
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Violation</th>
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Date</th>
                                <th class="px-5 py-3 text-right font-semibold text-slate-600">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            <tr v-for="violation in violations.data" :key="violation.id">
                                <td class="px-5 py-4">
                                    <div class="font-medium text-slate-800">{{ violation.driver_name }}</div>
                                    <div class="text-xs text-slate-500">
                                        <span class="rounded-full px-2.5 py-1 font-medium" :class="severityTone(violation.severity)">{{ violation.severity }}</span>
                                        · {{ violation.hours_exceeded }}h exceeded
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-slate-600">{{ violation.carrier_name || 'N/A' }}</td>
                                <td class="px-5 py-4 text-slate-600">{{ violation.trip_number || 'N/A' }}</td>
                                <td class="px-5 py-4">
                                    <div class="text-slate-700">{{ violation.violation_type }}</div>
                                    <div class="mt-1">
                                        <span class="rounded-full px-2.5 py-1 text-xs font-medium" :class="violation.is_forgiven ? 'bg-info/10 text-info' : (violation.acknowledged ? 'bg-success/10 text-success' : 'bg-warning/10 text-warning')">
                                            {{ violation.is_forgiven ? 'Forgiven' : (violation.acknowledged ? 'Acknowledged' : 'Pending acknowledgment') }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-slate-600">{{ violation.date || 'N/A' }}</td>
                                <td class="px-5 py-4 text-right">
                                    <Link :href="route(props.routeNames?.show ?? 'admin.hos.violations.show', violation.id)" class="inline-flex items-center gap-2 text-primary hover:underline">
                                        <Lucide icon="Eye" class="h-4 w-4" />
                                        Open
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="!violations.data.length">
                                <td colspan="6" class="px-5 py-10 text-center text-slate-500">No violations matched the current filters.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="violations.links?.length" class="border-t border-slate-100 px-5 py-4">
                    <div class="flex flex-wrap items-center gap-2">
                        <template v-for="link in violations.links" :key="link.label">
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                class="rounded-md border px-3 py-1.5 text-sm transition-colors"
                                :class="link.active ? 'border-primary bg-primary text-white' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'"
                                v-html="link.label"
                                preserve-scroll
                            />
                            <span v-else class="cursor-default rounded-md border border-slate-200 bg-white px-3 py-1.5 text-sm text-slate-300" v-html="link.label" />
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
