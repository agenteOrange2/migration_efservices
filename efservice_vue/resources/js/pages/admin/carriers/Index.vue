<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import { ref, computed, watch } from 'vue'
import Lucide from '@/components/Base/Lucide'
import { FormInput, FormSelect } from '@/components/Base/Form'
import Button from '@/components/Base/Button'
import RazeLayout from '@/layouts/RazeLayout.vue'

interface CarrierItem {
    id: number
    name: string
    slug: string
    address: string
    state: string
    zipcode: string
    ein_number: string
    dot_number: string | null
    mc_number: string | null
    status: number
    document_status: string | null
    created_at: string
    updated_at: string
    membership?: { id: number; name: string; price: number } | null
    user_carriers?: any[]
    documents?: any[]
    banking_details?: any | null
}

interface Props {
    carriers: {
        data: CarrierItem[]
        current_page: number
        last_page: number
        per_page: number
        total: number
        links: { url: string | null; label: string; active: boolean }[]
    }
    filters: Record<string, any>
    carrierStats: Record<string, any>
}

const props = defineProps<Props>()

const page = usePage()
const flash = computed(() => (page.props as any).flash ?? {})

const search = ref(props.filters?.search ?? '')
const statusFilter = ref(props.filters?.status ?? '')

const statusMap: Record<number, { label: string; color: string; icon: string }> = {
    0: { label: 'Inactive', color: 'text-danger', icon: 'XCircle' },
    1: { label: 'Active', color: 'text-success', icon: 'CheckCircle' },
    2: { label: 'Pending', color: 'text-warning', icon: 'Clock' },
    3: { label: 'Pending Validation', color: 'text-info', icon: 'AlertTriangle' },
    4: { label: 'Rejected', color: 'text-danger', icon: 'Ban' },
}

function getStatus(status: number) {
    return statusMap[status] ?? { label: 'Unknown', color: 'text-slate-500', icon: 'HelpCircle' }
}

function getDocStatusColor(docStatus: string | null): string {
    const map: Record<string, string> = {
        pending: 'bg-warning/10 text-warning',
        in_progress: 'bg-info/10 text-info',
        completed: 'bg-success/10 text-success',
        skipped: 'bg-slate-100 text-slate-500',
    }
    return map[docStatus ?? ''] ?? 'bg-slate-100 text-slate-500'
}

let debounce: ReturnType<typeof setTimeout>

watch(search, (val) => {
    clearTimeout(debounce)
    debounce = setTimeout(() => applyFilters(), 350)
})

watch(statusFilter, () => applyFilters())

function applyFilters() {
    const params: Record<string, any> = {}
    if (search.value) params.search = search.value
    if (statusFilter.value !== '') params.status = statusFilter.value

    router.get(route('admin.carriers.index'), params, {
        preserveState: true,
        preserveScroll: true,
    })
}

function goToPage(url: string | null) {
    if (url) router.get(url, {}, { preserveState: true, preserveScroll: true })
}

function confirmDelete(carrier: CarrierItem) {
    if (confirm(`Are you sure you want to deactivate "${carrier.name}"?`)) {
        router.delete(route('admin.carriers.destroy', carrier.slug))
    }
}
</script>

<template>
    <Head title="Carriers" />

    <RazeLayout>
        <div class="grid grid-cols-12 gap-y-10 gap-x-6">
            <!-- Header -->
            <div class="col-span-12">
                <div class="flex flex-col md:h-10 gap-y-3 md:items-center md:flex-row">
                    <div class="text-base font-medium">Carriers Management</div>
                    <div class="flex flex-col sm:flex-row gap-x-3 gap-y-2 md:ml-auto">
                        <Link :href="route('admin.carriers.create')">
                            <Button variant="primary" class="w-full sm:w-auto">
                                <Lucide icon="Plus" class="w-4 h-4 mr-2" />
                                Add New Carrier
                            </Button>
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Flash Messages -->
            <div v-if="flash.success" class="col-span-12">
                <div class="px-5 py-3 border rounded-lg bg-success/10 border-success/20 text-success text-sm flex items-center">
                    <Lucide icon="CheckCircle" class="w-4 h-4 mr-2" />
                    {{ flash.success }}
                </div>
            </div>
            <div v-if="flash.error" class="col-span-12">
                <div class="px-5 py-3 border rounded-lg bg-danger/10 border-danger/20 text-danger text-sm flex items-center">
                    <Lucide icon="AlertCircle" class="w-4 h-4 mr-2" />
                    {{ flash.error }}
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="col-span-12" v-if="carrierStats">
                <div class="grid grid-cols-2 lg:grid-cols-5 gap-5">
                    <div class="p-5 box box--stacked">
                        <div class="text-2xl font-medium">{{ carrierStats.total ?? 0 }}</div>
                        <div class="text-slate-500 mt-1 text-sm">Total Carriers</div>
                    </div>
                    <div class="p-5 box box--stacked">
                        <div class="text-2xl font-medium text-success">{{ carrierStats.active ?? 0 }}</div>
                        <div class="text-slate-500 mt-1 text-sm">Active</div>
                    </div>
                    <div class="p-5 box box--stacked">
                        <div class="text-2xl font-medium text-warning">{{ carrierStats.pending ?? 0 }}</div>
                        <div class="text-slate-500 mt-1 text-sm">Pending</div>
                    </div>
                    <div class="p-5 box box--stacked">
                        <div class="text-2xl font-medium text-info">{{ carrierStats.pending_validation ?? 0 }}</div>
                        <div class="text-slate-500 mt-1 text-sm">Pending Validation</div>
                    </div>
                    <div class="p-5 box box--stacked">
                        <div class="text-2xl font-medium text-danger">{{ carrierStats.inactive ?? 0 }}</div>
                        <div class="text-slate-500 mt-1 text-sm">Inactive</div>
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="col-span-12">
                <div class="box box--stacked flex flex-col">
                    <!-- Filters -->
                    <div class="flex flex-col gap-y-2 p-5 sm:flex-row sm:items-center border-b border-slate-200/60">
                        <div class="relative flex-1 max-w-md">
                            <Lucide icon="Search" class="absolute inset-y-0 left-0 w-4 h-4 my-auto ml-3 text-slate-400" />
                            <FormInput
                                v-model="search"
                                type="text"
                                placeholder="Search by name, EIN, DOT, MC..."
                                class="pl-9 shadow-none"
                            />
                        </div>
                        <div class="flex gap-3 sm:ml-auto">
                            <FormSelect v-model="statusFilter" class="w-44 shadow-none">
                                <option value="">All Status</option>
                                <option value="0">Inactive</option>
                                <option value="1">Active</option>
                                <option value="2">Pending</option>
                                <option value="3">Pending Validation</option>
                            </FormSelect>
                        </div>
                    </div>

                    <!-- Table Content -->
                    <div class="overflow-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="[&>th]:px-5 [&>th]:py-3.5 [&>th]:font-medium [&>th]:text-slate-500 [&>th]:text-sm [&>th]:border-b [&>th]:border-slate-200/60">
                                    <th>Carrier</th>
                                    <th>EIN</th>
                                    <th>DOT / MC</th>
                                    <th>Status</th>
                                    <th>Documents</th>
                                    <th>Plan</th>
                                    <th>Created</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="carrier in carriers.data"
                                    :key="carrier.id"
                                    class="[&>td]:px-5 [&>td]:py-4 [&>td]:border-b [&>td]:border-slate-200/60 hover:bg-slate-50/50 transition"
                                >
                                    <td>
                                        <div class="flex items-center">
                                            <div class="flex items-center justify-center w-10 h-10 rounded-full bg-primary/10 border border-primary/20 flex-shrink-0">
                                                <Lucide icon="Truck" class="w-4 h-4 text-primary" />
                                            </div>
                                            <div class="ml-3">
                                                <Link :href="route('admin.carriers.show', carrier.slug)" class="font-medium text-primary hover:underline">
                                                    {{ carrier.name }}
                                                </Link>
                                                <div class="text-xs text-slate-500 mt-0.5">{{ carrier.state }}, {{ carrier.zipcode }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="font-mono text-sm">{{ carrier.ein_number ?? '-' }}</span>
                                    </td>
                                    <td>
                                        <div class="text-sm">
                                            <div v-if="carrier.dot_number">DOT: {{ carrier.dot_number }}</div>
                                            <div v-if="carrier.mc_number" class="text-xs text-slate-500">MC: {{ carrier.mc_number }}</div>
                                            <span v-if="!carrier.dot_number && !carrier.mc_number" class="text-slate-400">-</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div :class="['flex items-center text-sm font-medium', getStatus(carrier.status).color]">
                                            <Lucide :icon="getStatus(carrier.status).icon" class="w-3.5 h-3.5 mr-1.5 stroke-[1.7]" />
                                            {{ getStatus(carrier.status).label }}
                                        </div>
                                    </td>
                                    <td>
                                        <span :class="['inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium', getDocStatusColor(carrier.document_status)]">
                                            {{ carrier.document_status ?? 'N/A' }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="text-sm">{{ carrier.membership?.name ?? 'No Plan' }}</span>
                                    </td>
                                    <td>
                                        <span class="text-sm text-slate-500">{{ new Date(carrier.created_at).toLocaleDateString() }}</span>
                                    </td>
                                    <td>
                                        <div class="flex items-center justify-center gap-2">
                                            <Link :href="route('admin.carriers.show', carrier.slug)" class="p-1.5 rounded-md hover:bg-slate-100 text-slate-500 hover:text-primary transition" title="View">
                                                <Lucide icon="Eye" class="w-4 h-4 stroke-[1.3]" />
                                            </Link>
                                            <Link :href="route('admin.carriers.edit', carrier.slug)" class="p-1.5 rounded-md hover:bg-slate-100 text-slate-500 hover:text-primary transition" title="Edit">
                                                <Lucide icon="PenSquare" class="w-4 h-4 stroke-[1.3]" />
                                            </Link>
                                            <button @click="confirmDelete(carrier)" class="p-1.5 rounded-md hover:bg-red-50 text-slate-500 hover:text-danger transition" title="Deactivate">
                                                <Lucide icon="Trash2" class="w-4 h-4 stroke-[1.3]" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="!carriers.data?.length">
                                    <td colspan="8" class="px-5 py-12 text-center text-slate-400">
                                        <Lucide icon="Inbox" class="w-10 h-10 mx-auto mb-3 text-slate-300" />
                                        <p>No carriers found</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="carriers.last_page > 1" class="flex flex-col sm:flex-row items-center justify-between px-5 py-4 border-t border-slate-200/60 gap-3">
                        <div class="text-sm text-slate-500">
                            Showing {{ ((carriers.current_page - 1) * carriers.per_page) + 1 }} to {{ Math.min(carriers.current_page * carriers.per_page, carriers.total) }} of {{ carriers.total }} results
                        </div>
                        <div class="flex items-center gap-1">
                            <template v-for="(link, i) in carriers.links" :key="i">
                                <button
                                    v-if="link.url"
                                    @click="goToPage(link.url)"
                                    :class="[
                                        'px-3 py-1.5 text-sm rounded-md transition',
                                        link.active
                                            ? 'bg-primary text-white'
                                            : 'text-slate-600 hover:bg-slate-100',
                                    ]"
                                    v-html="link.label"
                                />
                                <span v-else class="px-3 py-1.5 text-sm text-slate-300" v-html="link.label" />
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </RazeLayout>
</template>
