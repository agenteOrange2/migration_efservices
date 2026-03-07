<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import Lucide from '@/components/Base/Lucide'
import { FormInput, FormSelect } from '@/components/Base/Form'
import Button from '@/components/Base/Button'
import RazeLayout from '@/layouts/RazeLayout.vue'

interface Membership {
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
    carriers_count: number
    created_at: string
}

const props = defineProps<{
    memberships: Membership[]
}>()

defineOptions({ layout: RazeLayout })

const search = ref('')
const statusFilter = ref('')

const filteredMemberships = computed(() => {
    let result = props.memberships
    if (search.value) {
        const q = search.value.toLowerCase()
        result = result.filter(m => m.name.toLowerCase().includes(q) || m.description?.toLowerCase().includes(q))
    }
    if (statusFilter.value !== '') {
        result = result.filter(m => m.status === parseInt(statusFilter.value))
    }
    return result
})

function deleteMembership(membership: Membership) {
    if (confirm(`Are you sure you want to delete "${membership.name}"?`)) {
        router.delete(route('admin.memberships.destroy', membership.id))
    }
}

function formatPrice(value: number | null): string {
    if (value === null || value === undefined) return '-'
    return `$${Number(value).toFixed(2)}`
}
</script>

<template>
    <Head title="Memberships" />

    <div class="grid grid-cols-12 gap-x-6 gap-y-10">
        <div class="col-span-12">
            <div class="box box--stacked p-6 mb-6">
                <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                            <Lucide icon="CreditCard" class="w-8 h-8 text-primary" />
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-slate-800">Membership Management</h1>
                            <p class="text-slate-500">Manage subscription plans and pricing</p>
                        </div>
                    </div>
                    <Link :href="route('admin.memberships.create')" class="inline-flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition">
                        <Lucide icon="Plus" class="w-4 h-4" /> Add Membership
                    </Link>
                </div>
            </div>

            <div class="box box--stacked p-0">
                <div class="flex flex-col sm:flex-row gap-3 p-5 border-b border-slate-200/60">
                    <div class="relative flex-1">
                        <Lucide icon="Search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                        <FormInput v-model="search" type="text" placeholder="Search memberships..." class="pl-10" />
                    </div>
                    <FormSelect v-model="statusFilter" class="w-full sm:w-40">
                        <option value="">All Status</option>
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </FormSelect>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-slate-50/80">
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Name</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Type</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Price</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Limits</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Carriers</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Status</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Register</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="m in filteredMemberships" :key="m.id" class="border-b border-slate-100 hover:bg-slate-50/50 transition">
                                <td class="px-5 py-4">
                                    <div class="font-semibold text-slate-700">{{ m.name }}</div>
                                    <div class="text-xs text-slate-400 mt-0.5 line-clamp-1">{{ m.description }}</div>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" :class="m.pricing_type === 'plan' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700'">
                                        {{ m.pricing_type === 'plan' ? 'Bundle' : 'Individual' }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-sm">
                                    <template v-if="m.pricing_type === 'plan'">
                                        <span class="font-semibold text-slate-700">{{ formatPrice(m.price) }}</span>
                                    </template>
                                    <template v-else>
                                        <div class="text-xs space-y-0.5">
                                            <div>Carrier: {{ formatPrice(m.carrier_price) }}</div>
                                            <div>Driver: {{ formatPrice(m.driver_price) }}</div>
                                            <div>Vehicle: {{ formatPrice(m.vehicle_price) }}</div>
                                        </div>
                                    </template>
                                </td>
                                <td class="px-5 py-4 text-xs text-slate-600">
                                    <div>{{ m.max_carrier }} users</div>
                                    <div>{{ m.max_drivers }} drivers</div>
                                    <div>{{ m.max_vehicles }} vehicles</div>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-primary/10 text-primary text-sm font-bold">{{ m.carriers_count }}</span>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" :class="m.status ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700'">
                                        {{ m.status ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" :class="m.show_in_register ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500'">
                                        {{ m.show_in_register ? 'Visible' : 'Hidden' }}
                                    </span>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center justify-center gap-2">
                                        <Link :href="route('admin.memberships.show', m.id)" class="p-1.5 text-slate-400 hover:text-primary transition" title="View">
                                            <Lucide icon="Eye" class="w-4 h-4" />
                                        </Link>
                                        <Link :href="route('admin.memberships.edit', m.id)" class="p-1.5 text-slate-400 hover:text-amber-500 transition" title="Edit">
                                            <Lucide icon="PenLine" class="w-4 h-4" />
                                        </Link>
                                        <button @click="deleteMembership(m)" class="p-1.5 text-slate-400 hover:text-red-500 transition" title="Delete">
                                            <Lucide icon="Trash2" class="w-4 h-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="filteredMemberships.length === 0">
                                <td colspan="8" class="px-5 py-12 text-center text-slate-400">
                                    <Lucide icon="Inbox" class="w-12 h-12 mx-auto mb-3 text-slate-300" />
                                    <p>No memberships found</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>
