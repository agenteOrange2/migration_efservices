<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import Lucide from '@/components/Base/Lucide'
import { FormInput, FormSelect } from '@/components/Base/Form'
import { Dialog } from '@/components/Base/Headless'
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
    created_at?: string
    image_url?: string | null
}

const props = defineProps<{
    memberships: Membership[]
}>()

defineOptions({ layout: RazeLayout })

const search = ref('')
const statusFilter = ref('')
const deleteModalOpen = ref(false)
const selectedMembership = ref<Membership | null>(null)

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

function formatPrice(value: number | null): string {
    if (value === null || value === undefined) return '-'
    return `$${Number(value).toFixed(2)}`
}

const stats = computed(() => ({
    total: props.memberships.length,
    active: props.memberships.filter(m => m.status === 1).length,
    visible: props.memberships.filter(m => Boolean(m.show_in_register)).length,
    assignedCarriers: props.memberships.reduce((sum, item) => sum + item.carriers_count, 0),
}))

function openDeleteModal(membership: Membership) {
    selectedMembership.value = membership
    deleteModalOpen.value = true
}

function confirmDelete() {
    if (!selectedMembership.value) return
    router.delete(route('admin.memberships.destroy', selectedMembership.value.id), {
        onSuccess: () => {
            deleteModalOpen.value = false
            selectedMembership.value = null
        },
    })
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
                            <Lucide icon="BadgeDollarSign" class="w-8 h-8 text-primary" />
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-slate-800">Membership Management</h1>
                            <p class="text-slate-500">Manage plans, pricing rules, and registration visibility.</p>
                        </div>
                    </div>
                    <Link :href="route('admin.memberships.create')" class="inline-flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition">
                        <Lucide icon="Plus" class="w-4 h-4" /> Add Membership
                    </Link>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
                <div class="box box--stacked p-5">
                    <p class="text-sm text-slate-500">Total Plans</p>
                    <p class="text-2xl font-semibold text-slate-800 mt-1">{{ stats.total }}</p>
                </div>
                <div class="box box--stacked p-5">
                    <p class="text-sm text-slate-500">Active Plans</p>
                    <p class="text-2xl font-semibold text-success mt-1">{{ stats.active }}</p>
                </div>
                <div class="box box--stacked p-5">
                    <p class="text-sm text-slate-500">Visible In Signup</p>
                    <p class="text-2xl font-semibold text-primary mt-1">{{ stats.visible }}</p>
                </div>
                <div class="box box--stacked p-5">
                    <p class="text-sm text-slate-500">Assigned Carriers</p>
                    <p class="text-2xl font-semibold text-slate-800 mt-1">{{ stats.assignedCarriers }}</p>
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
                                    <div class="flex items-center gap-3">
                                        <div class="h-12 w-12 rounded-xl overflow-hidden bg-slate-100 border border-slate-200 flex items-center justify-center">
                                            <img v-if="m.image_url" :src="m.image_url" :alt="m.name" class="h-full w-full object-cover" />
                                            <Lucide v-else icon="Image" class="w-5 h-5 text-slate-300" />
                                        </div>
                                        <div>
                                            <div class="font-semibold text-slate-700">{{ m.name }}</div>
                                            <div class="text-xs text-slate-400 mt-0.5 line-clamp-1">{{ m.description }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" :class="m.pricing_type === 'plan' ? 'bg-primary/10 text-primary' : 'bg-warning/10 text-warning'">
                                        {{ m.pricing_type === 'plan' ? 'Plan' : 'Individual' }}
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
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" :class="m.status ? 'bg-success/10 text-success' : 'bg-danger/10 text-danger'">
                                        {{ m.status ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" :class="m.show_in_register ? 'bg-success/10 text-success' : 'bg-slate-100 text-slate-500'">
                                        {{ m.show_in_register ? 'Visible' : 'Hidden' }}
                                    </span>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center justify-center gap-2">
                                        <Link :href="route('admin.memberships.show', m.id)" class="p-1.5 text-slate-400 hover:text-primary transition" title="View">
                                            <Lucide icon="Eye" class="w-4 h-4" />
                                        </Link>
                                        <Link :href="route('admin.memberships.edit', m.id)" class="p-1.5 text-slate-400 hover:text-warning transition" title="Edit">
                                            <Lucide icon="PenLine" class="w-4 h-4" />
                                        </Link>
                                        <button @click="openDeleteModal(m)" class="p-1.5 text-slate-400 hover:text-red-500 transition" title="Delete">
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

    <Dialog :open="deleteModalOpen" @close="deleteModalOpen = false" size="lg" staticBackdrop>
        <Dialog.Panel class="w-full max-w-[600px] overflow-hidden">
            <div class="p-8 text-center">
                <div class="flex justify-end mb-2">
                    <button type="button" class="text-slate-400 hover:text-slate-600" @click="deleteModalOpen = false">
                        <Lucide icon="X" class="w-5 h-5" />
                    </button>
                </div>
                <div class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-full border-2 border-red-600 text-red-600">
                    <Lucide icon="X" class="w-8 h-8" />
                </div>
                <h3 class="text-[44px] leading-none font-light text-slate-600 mb-4">Are you sure?</h3>
                <p class="text-[15px] leading-8 text-slate-500 max-w-[420px] mx-auto">
                    Do you really want to delete this membership?<br>
                    This process cannot be undone.
                </p>
                <p class="mt-5 text-2xl font-medium text-slate-700">{{ selectedMembership?.name ?? 'Membership' }}</p>
                <div class="mt-8 flex items-center justify-center gap-4">
                    <button type="button" class="min-w-[110px] rounded-xl border border-slate-300 px-6 py-3 text-lg text-slate-600 hover:bg-slate-50" @click="deleteModalOpen = false">Cancel</button>
                    <button type="button" class="min-w-[110px] rounded-xl bg-red-600 px-6 py-3 text-lg font-semibold text-white hover:bg-red-700" @click="confirmDelete">Delete</button>
                </div>
            </div>
        </Dialog.Panel>
    </Dialog>
</template>
