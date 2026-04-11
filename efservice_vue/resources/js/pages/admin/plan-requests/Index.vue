<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { reactive } from 'vue'
import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import { FormInput, FormSelect } from '@/components/Base/Form'
import RazeLayout from '@/layouts/RazeLayout.vue'

declare function route(name: string, params?: any): string

defineOptions({ layout: RazeLayout })

interface PaginationLink {
    url: string | null
    label: string
    active: boolean
}

interface PlanRequestRow {
    id: number
    full_name: string
    company: string | null
    email: string
    phone: string | null
    plan_name: string
    plan_price: string | null
    status: string
    assigned_user_name: string | null
    created_at: string | null
    responded_at: string | null
}

const props = defineProps<{
    planRequests: {
        data: PlanRequestRow[]
        links: PaginationLink[]
        total: number
        last_page: number
    }
    counts: Record<string, number>
    filters: {
        status: string
        search: string
    }
}>()

const filters = reactive({ ...props.filters })

function applyFilters() {
    router.get(route('admin.plan-requests.index'), {
        status: filters.status || undefined,
        search: filters.search || undefined,
    }, {
        preserveState: true,
        replace: true,
    })
}

function resetFilters() {
    filters.status = ''
    filters.search = ''
    applyFilters()
}

function openStatus(status: string) {
    filters.status = status
    applyFilters()
}

function statusCardClass(status: string) {
    return filters.status === status || (!filters.status && status === '')
        ? 'border-primary bg-primary/5'
        : 'border-slate-200 hover:border-primary/30'
}

function statusBadgeClass(status: string) {
    if (status === 'new') return 'bg-primary/10 text-primary'
    if (status === 'in_progress') return 'bg-slate-200 text-slate-700'
    if (status === 'contacted') return 'bg-slate-100 text-slate-600'
    return 'bg-slate-100 text-slate-500'
}

function titleCase(value: string) {
    return value.replace(/_/g, ' ').replace(/\b\w/g, (char) => char.toUpperCase())
}
</script>

<template>
    <Head title="Plan Requests" />

    <div class="grid grid-cols-12 gap-6">
        <div class="col-span-12">
            <div class="box box--stacked p-6">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex items-center gap-4">
                        <div class="rounded-2xl border border-primary/20 bg-primary/10 p-3">
                            <Lucide icon="CreditCard" class="h-8 w-8 text-primary" />
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-slate-800">Plan Requests</h1>
                            <p class="text-sm text-slate-500">Review subscription interest, requested plans, and follow-up ownership from the website funnel.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12">
            <div class="grid grid-cols-2 gap-4 xl:grid-cols-5">
                <button type="button" class="box box--stacked rounded-2xl border p-5 text-left transition" :class="statusCardClass('')" @click="openStatus('')">
                    <p class="text-sm text-slate-500">All</p>
                    <p class="mt-2 text-3xl font-semibold text-slate-800">{{ counts.all ?? 0 }}</p>
                </button>
                <button type="button" class="box box--stacked rounded-2xl border p-5 text-left transition" :class="statusCardClass('new')" @click="openStatus('new')">
                    <p class="text-sm text-slate-500">New</p>
                    <p class="mt-2 text-3xl font-semibold text-slate-800">{{ counts.new ?? 0 }}</p>
                </button>
                <button type="button" class="box box--stacked rounded-2xl border p-5 text-left transition" :class="statusCardClass('in_progress')" @click="openStatus('in_progress')">
                    <p class="text-sm text-slate-500">In Progress</p>
                    <p class="mt-2 text-3xl font-semibold text-slate-800">{{ counts.in_progress ?? 0 }}</p>
                </button>
                <button type="button" class="box box--stacked rounded-2xl border p-5 text-left transition" :class="statusCardClass('contacted')" @click="openStatus('contacted')">
                    <p class="text-sm text-slate-500">Contacted</p>
                    <p class="mt-2 text-3xl font-semibold text-slate-800">{{ counts.contacted ?? 0 }}</p>
                </button>
                <button type="button" class="box box--stacked rounded-2xl border p-5 text-left transition" :class="statusCardClass('closed')" @click="openStatus('closed')">
                    <p class="text-sm text-slate-500">Closed</p>
                    <p class="mt-2 text-3xl font-semibold text-slate-800">{{ counts.closed ?? 0 }}</p>
                </button>
            </div>
        </div>

        <div class="col-span-12">
            <div class="box box--stacked p-6">
                <div class="grid grid-cols-1 gap-4 lg:grid-cols-4">
                    <div class="lg:col-span-2">
                        <FormInput v-model="filters.search" type="text" placeholder="Search name, email, company, phone, or requested plan..." />
                    </div>
                    <div>
                        <FormSelect v-model="filters.status">
                            <option value="">All statuses</option>
                            <option value="new">New</option>
                            <option value="in_progress">In Progress</option>
                            <option value="contacted">Contacted</option>
                            <option value="closed">Closed</option>
                        </FormSelect>
                    </div>
                    <div class="flex items-center gap-3">
                        <Button variant="primary" class="flex items-center gap-2" @click="applyFilters">
                            <Lucide icon="Search" class="h-4 w-4" />
                            Apply
                        </Button>
                        <Button variant="outline-secondary" class="flex items-center gap-2" @click="resetFilters">
                            <Lucide icon="RotateCcw" class="h-4 w-4" />
                            Clear
                        </Button>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12">
            <div class="box box--stacked overflow-hidden">
                <div class="border-b border-slate-200/60 px-6 py-4">
                    <h2 class="text-base font-semibold text-slate-800">Plan Request Queue</h2>
                    <p class="text-sm text-slate-500">{{ planRequests.total }} total request<span v-if="planRequests.total !== 1">s</span></p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-slate-50/80">
                                <th class="px-6 py-3 text-xs font-medium uppercase text-slate-500">Lead</th>
                                <th class="px-6 py-3 text-xs font-medium uppercase text-slate-500">Requested Plan</th>
                                <th class="px-6 py-3 text-xs font-medium uppercase text-slate-500">Price</th>
                                <th class="px-6 py-3 text-xs font-medium uppercase text-slate-500">Status</th>
                                <th class="px-6 py-3 text-xs font-medium uppercase text-slate-500">Assigned</th>
                                <th class="px-6 py-3 text-xs font-medium uppercase text-slate-500">Submitted</th>
                                <th class="px-6 py-3 text-xs font-medium uppercase text-slate-500 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="planRequest in planRequests.data" :key="planRequest.id" class="border-t border-slate-100 hover:bg-slate-50/40">
                                <td class="px-6 py-4">
                                    <p class="font-medium text-slate-800">{{ planRequest.full_name }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ planRequest.email }}</p>
                                    <p class="mt-1 text-xs text-slate-400">{{ planRequest.company || planRequest.phone || 'No company provided' }}</p>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700">
                                        {{ planRequest.plan_name }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm font-medium text-slate-700">
                                    {{ planRequest.plan_price ? `$${planRequest.plan_price}/mo` : 'Custom' }}
                                </td>
                                <td class="px-6 py-4">
                                    <span class="rounded-full px-2.5 py-1 text-xs font-medium" :class="statusBadgeClass(planRequest.status)">
                                        {{ titleCase(planRequest.status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-600">
                                    {{ planRequest.assigned_user_name || 'Unassigned' }}
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-500">
                                    {{ planRequest.created_at || '—' }}
                                    <p v-if="planRequest.responded_at" class="mt-1 text-xs text-slate-400">Responded: {{ planRequest.responded_at }}</p>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-end gap-2">
                                        <Link :href="route('admin.plan-requests.show', planRequest.id)" class="rounded-lg border border-slate-200 p-2 text-slate-500 transition hover:border-slate-300 hover:text-primary">
                                            <Lucide icon="Eye" class="h-4 w-4" />
                                        </Link>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="!planRequests.data.length">
                                <td colspan="7" class="px-6 py-14 text-center">
                                    <Lucide icon="Inbox" class="mx-auto mb-3 h-12 w-12 text-slate-300" />
                                    <p class="text-sm text-slate-500">No plan requests matched the current filters.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="planRequests.last_page > 1" class="flex items-center justify-between border-t border-slate-200/60 px-6 py-4">
                    <span class="text-sm text-slate-500">{{ planRequests.total }} total records</span>
                    <div class="flex flex-wrap items-center gap-1">
                        <template v-for="link in planRequests.links" :key="link.label">
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                class="rounded-lg px-3 py-1.5 text-sm transition"
                                :class="link.active ? 'bg-primary text-white' : 'text-slate-600 hover:bg-slate-100'"
                                v-html="link.label"
                            />
                            <span v-else class="px-3 py-1.5 text-sm text-slate-300" v-html="link.label" />
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
