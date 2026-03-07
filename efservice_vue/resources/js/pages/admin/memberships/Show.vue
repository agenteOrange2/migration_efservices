<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import Lucide from '@/components/Base/Lucide'
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
    }
    carriers: {
        data: Array<{
            id: number
            name: string
            slug: string
            mc_number: string | null
            status: number
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
</script>

<template>
    <Head :title="`Membership: ${membership.name}`" />

    <div class="grid grid-cols-12 gap-x-6 gap-y-8">
        <div class="col-span-12">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-3">
                    <Link :href="route('admin.memberships.index')" class="p-2 rounded-lg hover:bg-slate-100 transition">
                        <Lucide icon="ArrowLeft" class="w-5 h-5 text-slate-600" />
                    </Link>
                    <div>
                        <h1 class="text-2xl font-bold text-slate-800">{{ membership.name }}</h1>
                        <p class="text-sm text-slate-500">{{ membership.description }}</p>
                    </div>
                </div>
                <div class="flex gap-2">
                    <Link :href="route('admin.memberships.edit', membership.id)" class="inline-flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition">
                        <Lucide icon="PenLine" class="w-4 h-4" /> Edit
                    </Link>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="col-span-12 grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="box box--stacked p-5">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-blue-100 rounded-lg"><Lucide icon="Building2" class="w-5 h-5 text-blue-600" /></div>
                    <div>
                        <div class="text-2xl font-bold text-slate-800">{{ stats.total_carriers }}</div>
                        <div class="text-xs text-slate-500">Total Carriers</div>
                    </div>
                </div>
            </div>
            <div class="box box--stacked p-5">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-emerald-100 rounded-lg"><Lucide icon="CheckCircle" class="w-5 h-5 text-emerald-600" /></div>
                    <div>
                        <div class="text-2xl font-bold text-slate-800">{{ stats.active_carriers }}</div>
                        <div class="text-xs text-slate-500">Active Carriers</div>
                    </div>
                </div>
            </div>
            <div class="box box--stacked p-5">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-purple-100 rounded-lg"><Lucide icon="Users" class="w-5 h-5 text-purple-600" /></div>
                    <div>
                        <div class="text-2xl font-bold text-slate-800">{{ stats.total_drivers }}</div>
                        <div class="text-xs text-slate-500">Total Drivers</div>
                    </div>
                </div>
            </div>
            <div class="box box--stacked p-5">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-amber-100 rounded-lg"><Lucide icon="Truck" class="w-5 h-5 text-amber-600" /></div>
                    <div>
                        <div class="text-2xl font-bold text-slate-800">{{ stats.total_vehicles }}</div>
                        <div class="text-xs text-slate-500">Total Vehicles</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Plan Details -->
        <div class="col-span-12 lg:col-span-4">
            <div class="box box--stacked p-6 space-y-5">
                <h3 class="text-lg font-semibold text-slate-700">Plan Details</h3>

                <div class="space-y-3">
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-500">Type</span>
                        <span class="font-medium" :class="membership.pricing_type === 'plan' ? 'text-blue-600' : 'text-purple-600'">
                            {{ membership.pricing_type === 'plan' ? 'Bundle' : 'Individual' }}
                        </span>
                    </div>

                    <template v-if="membership.pricing_type === 'plan'">
                        <div class="flex justify-between text-sm">
                            <span class="text-slate-500">Price</span>
                            <span class="font-bold text-lg text-slate-800">{{ formatPrice(membership.price) }}</span>
                        </div>
                    </template>
                    <template v-else>
                        <div class="flex justify-between text-sm">
                            <span class="text-slate-500">Carrier</span>
                            <span class="font-semibold text-slate-700">{{ formatPrice(membership.carrier_price) }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-slate-500">Driver</span>
                            <span class="font-semibold text-slate-700">{{ formatPrice(membership.driver_price) }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-slate-500">Vehicle</span>
                            <span class="font-semibold text-slate-700">{{ formatPrice(membership.vehicle_price) }}</span>
                        </div>
                    </template>
                </div>

                <div class="border-t border-slate-200/60 pt-4 space-y-3">
                    <h4 class="text-sm font-semibold text-slate-600">Limits</h4>
                    <div v-for="item in [
                        { label: 'Users', max: membership.max_carrier, current: stats.total_carriers, color: 'bg-blue-500' },
                        { label: 'Drivers', max: membership.max_drivers, current: stats.total_drivers, color: 'bg-purple-500' },
                        { label: 'Vehicles', max: membership.max_vehicles, current: stats.total_vehicles, color: 'bg-amber-500' }
                    ]" :key="item.label" class="space-y-1">
                        <div class="flex justify-between text-xs text-slate-500">
                            <span>{{ item.label }}</span>
                            <span>{{ item.current }} / {{ item.max }}</span>
                        </div>
                        <div class="w-full bg-slate-100 rounded-full h-2">
                            <div :class="item.color" class="h-2 rounded-full transition-all" :style="{ width: Math.min((item.current / item.max) * 100, 100) + '%' }"></div>
                        </div>
                    </div>
                </div>

                <div class="border-t border-slate-200/60 pt-4 space-y-2">
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-500">Status</span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium" :class="membership.status ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700'">
                            {{ membership.status ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-500">Registration</span>
                        <span class="text-slate-700">{{ membership.show_in_register ? 'Visible' : 'Hidden' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Carriers Table -->
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
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">MC Number</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Status</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="carrier in carriers.data" :key="carrier.id" class="border-b border-slate-100 hover:bg-slate-50/50">
                                <td class="px-5 py-4 font-medium text-slate-700">{{ carrier.name }}</td>
                                <td class="px-5 py-4 text-sm text-slate-600">{{ carrier.mc_number ?? '-' }}</td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium" :class="carrier.status === 1 ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500'">
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
                                <td colspan="4" class="px-5 py-12 text-center text-slate-400">
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
