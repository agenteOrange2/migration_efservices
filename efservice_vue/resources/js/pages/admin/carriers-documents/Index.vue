<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import Lucide from '@/components/Base/Lucide'
import { FormInput, FormSelect } from '@/components/Base/Form'
import RazeLayout from '@/layouts/RazeLayout.vue'

declare function route(name: string, params?: any): string

defineOptions({ layout: RazeLayout })

interface CarrierDoc {
    id: number
    name: string
    slug: string
    mc_number: string | null
    dot_number: string | null
    status: number
    membership_name: string | null
    completion_percentage: number
    document_status: string
    approved: number
    total: number
}
interface PaginationLink { url: string | null; label: string; active: boolean }
interface Paginated {
    data: CarrierDoc[]
    current_page: number
    last_page: number
    per_page: number
    total: number
    from: number | null
    to: number | null
    links: PaginationLink[]
}

const props = defineProps<{
    carriers: Paginated
    stats: { total: number; complete: number; in_progress: number; none: number }
    filters: { search: string; status: string; per_page: number }
}>()

// ─── Filter state ─────────────────────────────────────────────────────────────
const search   = ref(props.filters.search)
const status   = ref(props.filters.status)
const perPage  = ref(props.filters.per_page)

let searchTimer: ReturnType<typeof setTimeout> | null = null

function applyFilters(resetPage = true) {
    router.get(route('admin.carriers-documents.index'), {
        search:   search.value || undefined,
        status:   status.value || undefined,
        per_page: perPage.value !== 15 ? perPage.value : undefined,
        page:     resetPage ? undefined : props.carriers.current_page,
    }, { preserveScroll: true, replace: true })
}

watch(search, () => {
    if (searchTimer) clearTimeout(searchTimer)
    searchTimer = setTimeout(() => applyFilters(), 400)
})

watch([status, perPage], () => applyFilters())

// ─── Helpers ──────────────────────────────────────────────────────────────────
function progressColor(pct: number) {
    if (pct >= 100) return 'bg-success'
    if (pct >= 50)  return 'bg-warning'
    return 'bg-danger'
}

function statusBadge(s: string) {
    if (s === 'active')  return 'bg-success/10 text-success'
    if (s === 'pending') return 'bg-warning/10 text-warning'
    return 'bg-danger/10 text-danger'
}

function statusLabel(s: string) {
    if (s === 'active')  return 'Complete'
    if (s === 'pending') return 'In Progress'
    return 'None'
}

// ─── Export PDF URL ───────────────────────────────────────────────────────────
function exportPdfUrl() {
    const params = new URLSearchParams()
    if (search.value)  params.set('search', search.value)
    if (status.value)  params.set('status', status.value)
    return route('admin.carriers-documents.export-pdf') + (params.toString() ? '?' + params.toString() : '')
}
</script>

<template>
    <Head title="Carriers Documents Progress" />

    <div class="grid grid-cols-12 gap-x-6 gap-y-6">
        <div class="col-span-12">

            <!-- Header -->
            <div class="box box--stacked p-6 mb-6">
                <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                            <Lucide icon="FolderCheck" class="w-8 h-8 text-primary" />
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-slate-800">Carriers Documents</h1>
                            <p class="text-slate-500 text-sm">Review and manage document compliance for all carriers</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <a :href="exportPdfUrl()" target="_blank"
                            class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white transition hover:bg-primary/90">
                            <Lucide icon="FileDown" class="w-4 h-4" /> Export PDF
                        </a>
                        <Link :href="route('admin.carriers.index')"
                            class="inline-flex items-center gap-2 px-4 py-2 border border-slate-300 rounded-lg text-slate-600 hover:bg-slate-50 transition text-sm">
                            <Lucide icon="ArrowLeft" class="w-4 h-4" /> Back to Carriers
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <div class="box box--stacked p-4 flex items-center gap-3">
                    <div class="p-2.5 bg-slate-100 rounded-lg"><Lucide icon="Building2" class="w-5 h-5 text-slate-600" /></div>
                    <div>
                        <div class="text-2xl font-bold text-slate-800">{{ stats.total }}</div>
                        <div class="text-xs text-slate-500">Total Carriers</div>
                    </div>
                </div>
                <div class="box box--stacked p-4 flex items-center gap-3">
                    <div class="rounded-lg bg-success/10 p-2.5"><Lucide icon="CheckCircle" class="w-5 h-5 text-success" /></div>
                    <div>
                        <div class="text-2xl font-bold text-success">{{ stats.complete }}</div>
                        <div class="text-xs text-slate-500">Complete</div>
                    </div>
                </div>
                <div class="box box--stacked p-4 flex items-center gap-3">
                    <div class="rounded-lg bg-warning/10 p-2.5"><Lucide icon="Clock" class="w-5 h-5 text-warning" /></div>
                    <div>
                        <div class="text-2xl font-bold text-warning">{{ stats.in_progress }}</div>
                        <div class="text-xs text-slate-500">In Progress</div>
                    </div>
                </div>
                <div class="box box--stacked p-4 flex items-center gap-3">
                    <div class="rounded-lg bg-danger/10 p-2.5"><Lucide icon="FolderX" class="w-5 h-5 text-danger" /></div>
                    <div>
                        <div class="text-2xl font-bold text-danger">{{ stats.none }}</div>
                        <div class="text-xs text-slate-500">No Documents</div>
                    </div>
                </div>
            </div>

            <!-- Table card -->
            <div class="box box--stacked p-0">

                <!-- Filters bar -->
                <div class="flex flex-col sm:flex-row gap-3 p-5 border-b border-slate-200/60">
                    <div class="relative flex-1">
                        <Lucide icon="Search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                        <FormInput v-model="search" type="text" placeholder="Search by name, MC, DOT or EIN..." class="pl-10" />
                    </div>
                    <FormSelect v-model="status" class="w-full sm:w-44">
                        <option value="">All Status</option>
                        <option value="active">Complete</option>
                        <option value="pending">In Progress</option>
                        <option value="inactive">No Documents</option>
                    </FormSelect>
                    <FormSelect v-model.number="perPage" class="w-full sm:w-32">
                        <option :value="10">10 / page</option>
                        <option :value="15">15 / page</option>
                        <option :value="25">25 / page</option>
                        <option :value="50">50 / page</option>
                        <option :value="100">100 / page</option>
                    </FormSelect>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-slate-50/80">
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Carrier</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">MC / DOT</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Plan</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Progress</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Status</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="c in carriers.data" :key="c.id"
                                class="border-b border-slate-100 hover:bg-slate-50/50 transition">
                                <td class="px-5 py-4 font-medium text-slate-700">{{ c.name }}</td>
                                <td class="px-5 py-4 text-sm text-slate-500">
                                    <div v-if="c.mc_number" class="font-mono text-xs">MC: {{ c.mc_number }}</div>
                                    <div v-if="c.dot_number" class="font-mono text-xs">DOT: {{ c.dot_number }}</div>
                                </td>
                                <td class="px-5 py-4 text-sm text-slate-600">{{ c.membership_name ?? '—' }}</td>
                                <td class="px-5 py-4 w-48">
                                    <div class="flex items-center gap-3">
                                        <div class="flex-1">
                                            <div class="w-full bg-slate-100 rounded-full h-2">
                                                <div :class="progressColor(c.completion_percentage)"
                                                    class="h-2 rounded-full transition-all"
                                                    :style="{ width: c.completion_percentage + '%' }"></div>
                                            </div>
                                        </div>
                                        <span class="text-xs font-medium text-slate-600 whitespace-nowrap">
                                            {{ c.approved }}/{{ c.total }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                        :class="statusBadge(c.document_status)">
                                        {{ statusLabel(c.document_status) }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-center">
                                    <Link :href="route('admin.carriers-documents.carrier', c.slug)"
                                        class="inline-flex items-center gap-1 px-3 py-1.5 bg-primary/10 text-primary rounded-lg hover:bg-primary/20 text-sm transition">
                                        <Lucide icon="FileSearch" class="w-3.5 h-3.5" /> Review
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="carriers.data.length === 0">
                                <td colspan="6" class="px-5 py-14 text-center text-slate-400">
                                    <Lucide icon="Inbox" class="w-12 h-12 mx-auto mb-3 text-slate-300" />
                                    <p class="font-medium">No carriers found</p>
                                    <p v-if="search || status" class="text-xs mt-1">Try clearing the filters</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination footer -->
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4 px-5 py-4 border-t border-slate-100 bg-slate-50/50">
                    <!-- Showing info -->
                    <p class="text-sm text-slate-500">
                        <template v-if="carriers.total > 0">
                            Showing <span class="font-medium text-slate-700">{{ carriers.from }}</span>
                            to <span class="font-medium text-slate-700">{{ carriers.to }}</span>
                            of <span class="font-medium text-slate-700">{{ carriers.total }}</span> carriers
                        </template>
                        <template v-else>No results</template>
                    </p>

                    <!-- Page links -->
                    <div v-if="carriers.last_page > 1" class="flex items-center gap-1">
                        <template v-for="link in carriers.links" :key="link.label">
                            <span v-if="!link.url"
                                class="px-2.5 py-1.5 rounded text-xs text-slate-300"
                                v-html="link.label">
                            </span>
                            <Link v-else :href="link.url"
                                class="px-2.5 py-1.5 rounded text-xs transition"
                                :class="link.active
                                    ? 'bg-primary text-white font-semibold'
                                    : 'text-slate-600 hover:bg-slate-100'"
                                v-html="link.label">
                            </Link>
                        </template>
                    </div>
                </div>

            </div>
        </div>
    </div>
</template>
