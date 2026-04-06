<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import Lucide from '@/components/Base/Lucide'
import Button from '@/components/Base/Button'
import RazeLayout from '@/layouts/RazeLayout.vue'

defineOptions({ layout: RazeLayout })

const props = defineProps<{
    membership: {
        id: number
        name: string
        description: string
        pricing_type: string
        price: number | null
        carrier_price: number | null
        driver_price: number | null
        vehicle_price: number | null
        max_carrier: number
        max_drivers: number
        max_vehicles: number
        status: number
        show_in_register: number
        created_at: string
        image_url?: string | null
    }
    carriers: {
        data: Array<{
            id: number
            name: string
            slug: string
            mc_number: string | null
            status: number
            drivers_count: number
            vehicles_count: number
            contact_email: string | null
            logo_url: string | null
        }>
        links: any[]
        current_page: number
        last_page: number
    }
    stats: {
        total_carriers: number
        active_carriers: number
        total_drivers: number
        total_vehicles: number
    }
}>()

function formatPrice(value: number | null): string {
    if (value === null || value === undefined) return '-'
    return `$${Number(value).toFixed(2)}`
}

const usageRows = [
    { label: 'Carriers', current: props.stats.total_carriers, max: props.membership.max_carrier, color: 'bg-primary' },
    { label: 'Drivers', current: props.stats.total_drivers, max: props.membership.max_drivers, color: 'bg-info' },
    { label: 'Vehicles', current: props.stats.total_vehicles, max: props.membership.max_vehicles, color: 'bg-warning' },
]
</script>

<template>
    <Head :title="`Membership: ${membership.name}`" />

    <div class="grid grid-cols-12 gap-x-6 gap-y-8">
        <div class="col-span-12">
            <div class="box box--stacked p-6">
                <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="h-16 w-16 rounded-2xl overflow-hidden bg-primary/10 border border-primary/20 flex items-center justify-center">
                            <img v-if="membership.image_url" :src="membership.image_url" :alt="membership.name" class="h-full w-full object-cover" />
                            <Lucide v-else icon="BadgeDollarSign" class="w-8 h-8 text-primary" />
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-slate-800">{{ membership.name }}</h1>
                            <p class="text-sm text-slate-500">{{ membership.description }}</p>
                        </div>
                    </div>

                    <div class="flex gap-3">
                        <Link :href="route('admin.memberships.index')">
                            <Button variant="outline-secondary" class="flex items-center gap-2">
                                <Lucide icon="ArrowLeft" class="w-4 h-4" />
                                Back to Memberships
                            </Button>
                        </Link>
                        <Link :href="route('admin.memberships.edit', membership.id)">
                            <Button variant="primary" class="flex items-center gap-2">
                                <Lucide icon="PenLine" class="w-4 h-4" />
                                Edit Membership
                            </Button>
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12 grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="box box--stacked p-5">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-primary/10 rounded-lg"><Lucide icon="Building2" class="w-5 h-5 text-primary" /></div>
                    <div>
                        <div class="text-2xl font-bold text-slate-800">{{ stats.total_carriers }}</div>
                        <div class="text-xs text-slate-500">Total Carriers</div>
                    </div>
                </div>
            </div>
            <div class="box box--stacked p-5">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-success/10 rounded-lg"><Lucide icon="CheckCircle" class="w-5 h-5 text-success" /></div>
                    <div>
                        <div class="text-2xl font-bold text-slate-800">{{ stats.active_carriers }}</div>
                        <div class="text-xs text-slate-500">Active Carriers</div>
                    </div>
                </div>
            </div>
            <div class="box box--stacked p-5">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-info/10 rounded-lg"><Lucide icon="Users" class="w-5 h-5 text-info" /></div>
                    <div>
                        <div class="text-2xl font-bold text-slate-800">{{ stats.total_drivers }}</div>
                        <div class="text-xs text-slate-500">Total Drivers</div>
                    </div>
                </div>
            </div>
            <div class="box box--stacked p-5">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-warning/10 rounded-lg"><Lucide icon="Truck" class="w-5 h-5 text-warning" /></div>
                    <div>
                        <div class="text-2xl font-bold text-slate-800">{{ stats.total_vehicles }}</div>
                        <div class="text-xs text-slate-500">Total Vehicles</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12 lg:col-span-4 space-y-6">
            <div class="box box--stacked overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-200/60">
                    <h3 class="text-lg font-medium text-slate-700 flex items-center gap-2">
                        <Lucide icon="DollarSign" class="w-5 h-5 text-primary" />
                        Pricing
                    </h3>
                </div>
                <div class="p-5 space-y-4">
                    <div class="flex items-center justify-between rounded-xl bg-slate-50 px-4 py-4">
                        <span class="text-slate-600">Pricing Type</span>
                        <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium" :class="membership.pricing_type === 'plan' ? 'bg-primary/10 text-primary' : 'bg-info/10 text-info'">
                            {{ membership.pricing_type === 'plan' ? 'Plan' : 'Individual' }}
                        </span>
                    </div>

                    <template v-if="membership.pricing_type === 'plan'">
                        <div class="flex items-center justify-between rounded-xl border border-success/20 bg-success/5 px-4 py-4">
                            <span class="text-slate-600">Plan Price</span>
                            <span class="text-[22px] font-semibold text-success">{{ formatPrice(membership.price) }}</span>
                        </div>
                    </template>

                    <template v-else>
                        <div class="space-y-3">
                            <div class="flex items-center justify-between rounded-xl bg-slate-50 px-4 py-4">
                                <span class="text-slate-600">Carrier Price</span>
                                <span class="font-semibold text-slate-800">{{ formatPrice(membership.carrier_price) }}</span>
                            </div>
                            <div class="flex items-center justify-between rounded-xl bg-slate-50 px-4 py-4">
                                <span class="text-slate-600">Driver Price</span>
                                <span class="font-semibold text-slate-800">{{ formatPrice(membership.driver_price) }}</span>
                            </div>
                            <div class="flex items-center justify-between rounded-xl bg-slate-50 px-4 py-4">
                                <span class="text-slate-600">Vehicle Price</span>
                                <span class="font-semibold text-slate-800">{{ formatPrice(membership.vehicle_price) }}</span>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <div class="box box--stacked overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-200/60">
                    <h3 class="text-lg font-medium text-slate-700 flex items-center gap-2">
                        <Lucide icon="Gauge" class="w-5 h-5 text-primary" />
                        Plan Limits
                    </h3>
                </div>
                <div class="p-5 space-y-5">
                    <div v-for="item in usageRows" :key="item.label" class="space-y-2">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-slate-600">{{ item.label }}</span>
                            <span class="text-slate-700">{{ item.current }} / {{ item.max }}</span>
                        </div>
                        <div class="w-full bg-slate-100 rounded-full h-2.5 overflow-hidden">
                            <div :class="item.color" class="h-full rounded-full transition-all" :style="{ width: Math.min((item.current / item.max) * 100, 100) + '%' }"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="box box--stacked overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-200/60">
                    <h3 class="text-lg font-medium text-slate-700 flex items-center gap-2">
                        <Lucide icon="Settings2" class="w-5 h-5 text-primary" />
                        Settings
                    </h3>
                </div>
                <div class="p-5 space-y-3">
                    <div class="flex items-center justify-between rounded-xl bg-slate-50 px-4 py-4">
                        <span class="text-slate-600">Status</span>
                        <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium" :class="membership.status ? 'bg-success/10 text-success' : 'bg-danger/10 text-danger'">
                            {{ membership.status ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between rounded-xl bg-slate-50 px-4 py-4">
                        <span class="text-slate-600">Show in Register</span>
                        <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium" :class="membership.show_in_register ? 'bg-success/10 text-success' : 'bg-slate-200 text-slate-600'">
                            {{ membership.show_in_register ? 'Yes' : 'No' }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between rounded-xl bg-slate-50 px-4 py-4">
                        <span class="text-slate-600">Created</span>
                        <span class="text-slate-800">{{ new Date(membership.created_at).toLocaleDateString() }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12 lg:col-span-8">
            <div class="box box--stacked">
                <div class="p-5 border-b border-slate-200/60">
                    <h3 class="text-lg font-semibold text-slate-700">Carriers Using This Plan</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-slate-50/80">
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Carrier</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Contact</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">MC Number</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase text-center">Drivers</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase text-center">Vehicles</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Status</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="carrier in carriers.data" :key="carrier.id" class="border-b border-slate-100 hover:bg-slate-50/50">
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-lg overflow-hidden bg-primary/10 border border-primary/20 flex items-center justify-center">
                                            <img v-if="carrier.logo_url" :src="carrier.logo_url" :alt="carrier.name" class="w-full h-full object-cover" />
                                            <Lucide v-else icon="Building2" class="w-5 h-5 text-primary" />
                                        </div>
                                        <div class="font-medium text-slate-700">{{ carrier.name }}</div>
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-sm text-slate-600">{{ carrier.contact_email ?? '-' }}</td>
                                <td class="px-5 py-4 text-sm text-slate-600">{{ carrier.mc_number ?? '-' }}</td>
                                <td class="px-5 py-4 text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-primary/10 text-primary">
                                        {{ carrier.drivers_count }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-warning/10 text-warning">
                                        {{ carrier.vehicles_count }}
                                    </span>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium" :class="carrier.status === 1 ? 'bg-success/10 text-success' : 'bg-slate-100 text-slate-500'">
                                        {{ carrier.status === 1 ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-center">
                                    <Link :href="route('admin.carriers.show', carrier.slug)" class="p-1.5 text-slate-400 hover:text-primary transition">
                                        <Lucide icon="Eye" class="w-4 h-4" />
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="carriers.data.length === 0">
                                <td colspan="7" class="px-5 py-12 text-center text-slate-400">
                                    <Lucide icon="Inbox" class="w-10 h-10 mx-auto mb-2 text-slate-300" />
                                    <p>No carriers using this plan yet</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-if="carriers.last_page > 1" class="p-4 border-t border-slate-200/60 flex justify-center gap-1">
                    <template v-for="link in carriers.links" :key="link.label">
                        <Link v-if="link.url" :href="link.url" class="px-3 py-1 text-sm rounded" :class="link.active ? 'bg-primary text-white' : 'text-slate-600 hover:bg-slate-100'" v-html="link.label" />
                        <span v-else class="px-3 py-1 text-sm text-slate-300" v-html="link.label" />
                    </template>
                </div>
            </div>
        </div>
    </div>
</template>
