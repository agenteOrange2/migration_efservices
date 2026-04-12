<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { reactive, ref } from 'vue'
import Button from '@/components/Base/Button'
import { Dialog } from '@/components/Base/Headless'
import Lucide from '@/components/Base/Lucide'
import TomSelect from '@/components/Base/TomSelect/TomSelect.vue'
import { FormInput } from '@/components/Base/Form'
import RazeLayout from '@/layouts/RazeLayout.vue'

declare function route(name: string, params?: any): string

defineOptions({ layout: RazeLayout })

type PaginationLink = { url: string | null; label: string; active: boolean }

const props = defineProps<{
    filters: { search: string; carrier_id: string; pending_only: string }
    stats: { total: number; pending: number; cycle_60_7: number; cycle_70_8: number }
    drivers: { data: any[]; links: PaginationLink[] }
    carriers: { id: number; name: string }[]
    cycleOptions: { value: string; label: string }[]
    canFilterCarriers: boolean
    routeNames?: {
        index?: string
        update?: string
        approve?: string
        reject?: string
        driverLog?: string
    }
}>()

const filters = reactive({ ...props.filters })
const cycleModalOpen = ref(false)
const selectedDriver = ref<any | null>(null)
const cycleForm = reactive({ hos_cycle_type: '70_8' })

function applyFilters() {
    router.get(route(props.routeNames?.index ?? 'admin.drivers.hos.index'), {
        search: filters.search || undefined,
        carrier_id: filters.carrier_id || undefined,
        pending_only: filters.pending_only || undefined,
    }, { preserveState: true, preserveScroll: true, replace: true })
}

function resetFilters() {
    filters.search = ''
    filters.carrier_id = ''
    filters.pending_only = ''
    applyFilters()
}

function openCycleModal(driver: any) {
    selectedDriver.value = driver
    cycleForm.hos_cycle_type = driver.current_cycle
    cycleModalOpen.value = true
}

function updateCycle() {
    if (!selectedDriver.value) return

    router.put(route(props.routeNames?.update ?? 'admin.drivers.hos.update', selectedDriver.value.id), cycleForm, {
        preserveScroll: true,
        onSuccess: () => {
            cycleModalOpen.value = false
        },
    })
}

function approve(driver: any) {
    router.post(route(props.routeNames?.approve ?? 'admin.drivers.hos.approve', driver.id), {}, { preserveScroll: true })
}

function reject(driver: any) {
    router.post(route(props.routeNames?.reject ?? 'admin.drivers.hos.reject', driver.id), {}, { preserveScroll: true })
}
</script>

<template>
    <Head title="Driver HOS Settings" />

    <div class="grid grid-cols-12 gap-6">
        <div class="col-span-12">
            <div class="box box--stacked p-6">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex items-center gap-4">
                        <div class="rounded-2xl border border-primary/20 bg-primary/10 p-3">
                            <Lucide icon="Settings" class="h-8 w-8 text-primary" />
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-slate-800">Driver HOS Settings</h1>
                            <p class="mt-1 text-sm text-slate-500">Manage weekly cycle assignments and pending driver requests.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="box box--stacked p-5"><div class="text-sm text-slate-500">Drivers</div><div class="mt-2 text-3xl font-semibold text-slate-800">{{ stats.total }}</div></div>
            <div class="box box--stacked p-5"><div class="text-sm text-slate-500">Pending Requests</div><div class="mt-2 text-3xl font-semibold text-primary">{{ stats.pending }}</div></div>
            <div class="box box--stacked p-5"><div class="text-sm text-slate-500">60 / 7</div><div class="mt-2 text-3xl font-semibold text-slate-800">{{ stats.cycle_60_7 }}</div></div>
            <div class="box box--stacked p-5"><div class="text-sm text-slate-500">70 / 8</div><div class="mt-2 text-3xl font-semibold text-slate-800">{{ stats.cycle_70_8 }}</div></div>
        </div>

        <div class="col-span-12">
            <div class="box box--stacked p-5">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <FormInput v-model="filters.search" type="text" placeholder="Search by driver name or email..." />
                    <TomSelect v-if="canFilterCarriers" v-model="filters.carrier_id">
                        <option value="">All Carriers</option>
                        <option v-for="carrier in carriers" :key="carrier.id" :value="String(carrier.id)">{{ carrier.name }}</option>
                    </TomSelect>
                    <TomSelect v-model="filters.pending_only">
                        <option value="">All Drivers</option>
                        <option value="yes">Pending Only</option>
                    </TomSelect>
                    <div class="flex gap-3">
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
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Current Cycle</th>
                                <th class="px-5 py-3 text-left font-semibold text-slate-600">Pending Request</th>
                                <th class="px-5 py-3 text-right font-semibold text-slate-600">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            <tr v-for="driver in drivers.data" :key="driver.id">
                                <td class="px-5 py-4">
                                    <div class="font-medium text-slate-800">{{ driver.name }}</div>
                                    <div class="text-xs text-slate-500">{{ driver.email || 'No email' }}</div>
                                </td>
                                <td class="px-5 py-4 text-slate-600">{{ driver.carrier_name || 'N/A' }}</td>
                                <td class="px-5 py-4 text-slate-600">{{ driver.current_cycle_label }}</td>
                                <td class="px-5 py-4">
                                    <div v-if="driver.pending_requested">
                                        <div class="font-medium text-primary">{{ driver.pending_cycle_label }}</div>
                                        <div class="text-xs text-slate-500">{{ driver.requested_at || 'Pending' }}</div>
                                    </div>
                                    <span v-else class="text-slate-400">No request</span>
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <div class="flex justify-end gap-2">
                                        <Button v-if="driver.pending_requested" variant="outline-secondary" class="gap-2" @click="approve(driver)">
                                            <Lucide icon="Check" class="h-4 w-4" />
                                            Approve
                                        </Button>
                                        <Button v-if="driver.pending_requested" variant="outline-secondary" class="gap-2" @click="reject(driver)">
                                            <Lucide icon="X" class="h-4 w-4" />
                                            Reject
                                        </Button>
                                        <Button variant="primary" class="gap-2" @click="openCycleModal(driver)">
                                            <Lucide icon="Settings" class="h-4 w-4" />
                                            Change
                                        </Button>
                                        <Link :href="route(props.routeNames?.driverLog ?? 'admin.hos.driver.log', driver.id)" class="inline-flex items-center gap-2 text-primary hover:underline">
                                            <Lucide icon="Eye" class="h-4 w-4" />
                                            View Log
                                        </Link>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!drivers.data.length">
                                <td colspan="5" class="px-5 py-10 text-center text-slate-500">No drivers matched the current filters.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="drivers.links?.length" class="border-t border-slate-100 px-5 py-4">
                    <div class="flex flex-wrap items-center gap-2">
                        <template v-for="link in drivers.links" :key="link.label">
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

    <Dialog :open="cycleModalOpen" @close="cycleModalOpen = false" size="lg">
        <Dialog.Panel class="w-full max-w-[560px] overflow-hidden">
            <div class="border-b border-slate-200 px-6 py-5">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-800">Update Driver Cycle</h3>
                        <p class="mt-1 text-sm text-slate-500">{{ selectedDriver?.name || 'Driver' }}</p>
                    </div>
                    <button class="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-600" @click="cycleModalOpen = false">
                        <Lucide icon="X" class="h-5 w-5" />
                    </button>
                </div>
            </div>

            <div class="space-y-4 bg-slate-50/50 px-6 py-6">
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">Cycle Type</label>
                    <TomSelect v-model="cycleForm.hos_cycle_type">
                        <option v-for="option in cycleOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                    </TomSelect>
                </div>
            </div>

            <div class="border-t border-slate-200 px-6 py-4">
                <div class="flex justify-end gap-3">
                    <Button variant="outline-secondary" @click="cycleModalOpen = false">Cancel</Button>
                    <Button variant="primary" @click="updateCycle">Save</Button>
                </div>
            </div>
        </Dialog.Panel>
    </Dialog>
</template>
