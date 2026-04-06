<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { computed, reactive, ref } from 'vue'
import Lucide from '@/components/Base/Lucide'
import Litepicker from '@/components/Base/Litepicker/Litepicker.vue'
import { FormInput, FormSelect } from '@/components/Base/Form'
import { Dialog } from '@/components/Base/Headless'
import RazeLayout from '@/layouts/RazeLayout.vue'

declare function route(name: string, params?: any): string

defineOptions({ layout: RazeLayout })

const lpOptions = { singleMode: true, format: 'M/D/YYYY', autoApply: true }

interface PaginationLink {
    url: string | null
    label: string
    active: boolean
}

interface MedicalRow {
    id: number
    created_at: string | null
    social_security_number: string | null
    medical_examiner_name: string | null
    medical_examiner_registry_number: string | null
    medical_card_expiration_date: string | null
    status: 'active' | 'expiring' | 'expired'
    document_count: number
    driver: { id: number; name: string; email: string | null } | null
    carrier: { id: number; name: string } | null
}

const props = defineProps<{
    medicalRecords: {
        data: MedicalRow[]
        links: PaginationLink[]
        total: number
        last_page: number
    }
    drivers: { id: number; carrier_id: number | null; name: string }[]
    carriers: { id: number; name: string }[]
    filters: {
        search_term: string
        carrier_filter: string
        driver_filter: string
        date_from: string
        date_to: string
        tab: string
        sort_field: string
        sort_direction: string
    }
    stats: {
        total: number
        active: number
        expiring: number
        expired: number
    }
}>()

const filters = reactive({
    search_term: props.filters.search_term ?? '',
    carrier_filter: props.filters.carrier_filter ?? '',
    driver_filter: props.filters.driver_filter ?? '',
    date_from: props.filters.date_from ?? '',
    date_to: props.filters.date_to ?? '',
    tab: props.filters.tab ?? 'all',
})

const deleteModalOpen = ref(false)
const selectedRecord = ref<MedicalRow | null>(null)

const tabs = computed(() => ([
    { key: 'all', label: 'Total Records', icon: 'Files', count: props.stats.total, tone: 'text-slate-600 bg-slate-100 border-slate-200' },
    { key: 'active', label: 'Active', icon: 'BadgeCheck', count: props.stats.active, tone: 'text-emerald-700 bg-emerald-100 border-emerald-200' },
    { key: 'expiring', label: 'Expiring Soon', icon: 'Clock3', count: props.stats.expiring, tone: 'text-amber-700 bg-amber-100 border-amber-200' },
    { key: 'expired', label: 'Expired', icon: 'AlertTriangle', count: props.stats.expired, tone: 'text-red-700 bg-red-100 border-red-200' },
]))

function formatDate(value: string | null) {
    if (!value) return 'N/A'
    return new Date(`${value}T00:00:00`).toLocaleDateString()
}

function applyFilters() {
    router.get(route('admin.medical-records.index'), {
        ...filters,
        search_term: filters.search_term || undefined,
        carrier_filter: filters.carrier_filter || undefined,
        driver_filter: filters.driver_filter || undefined,
        date_from: filters.date_from || undefined,
        date_to: filters.date_to || undefined,
        tab: filters.tab !== 'all' ? filters.tab : undefined,
        sort_field: props.filters.sort_field || undefined,
        sort_direction: props.filters.sort_direction || undefined,
    }, {
        preserveState: true,
        replace: true,
    })
}

function resetFilters() {
    filters.search_term = ''
    filters.carrier_filter = ''
    filters.driver_filter = ''
    filters.date_from = ''
    filters.date_to = ''
    filters.tab = 'all'
    applyFilters()
}

function changeTab(tab: string) {
    filters.tab = tab
    applyFilters()
}

function sortUrl(field: string) {
    const direction = props.filters.sort_field === field && props.filters.sort_direction === 'asc' ? 'desc' : 'asc'

    return route('admin.medical-records.index', {
        ...filters,
        search_term: filters.search_term || undefined,
        carrier_filter: filters.carrier_filter || undefined,
        driver_filter: filters.driver_filter || undefined,
        date_from: filters.date_from || undefined,
        date_to: filters.date_to || undefined,
        tab: filters.tab !== 'all' ? filters.tab : undefined,
        sort_field: field,
        sort_direction: direction,
    })
}

function openDeleteModal(record: MedicalRow) {
    selectedRecord.value = record
    deleteModalOpen.value = true
}

function confirmDelete() {
    if (!selectedRecord.value) return

    router.delete(route('admin.medical-records.destroy', selectedRecord.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            deleteModalOpen.value = false
            selectedRecord.value = null
        },
    })
}

function statusClass(status: string) {
    return {
        active: 'bg-emerald-100 text-emerald-700',
        expiring: 'bg-amber-100 text-amber-700',
        expired: 'bg-red-100 text-red-700',
    }[status] ?? 'bg-slate-100 text-slate-600'
}

function statusLabel(status: string) {
    return {
        active: 'Active',
        expiring: 'Expiring Soon',
        expired: 'Expired',
    }[status] ?? 'Unknown'
}
</script>

<template>
    <Head title="Medical Records" />

    <div class="grid grid-cols-12 gap-x-6 gap-y-10">
        <div class="col-span-12">
            <div class="box box--stacked p-6 mb-6">
                <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                            <Lucide icon="HeartPulse" class="w-8 h-8 text-primary" />
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-slate-800">Medical Records Management</h1>
                            <p class="text-slate-500">Manage driver medical records in the Vue admin.</p>
                        </div>
                    </div>

                    <Link
                        :href="route('admin.medical-records.create')"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition"
                    >
                        <Lucide icon="Plus" class="w-4 h-4" />
                        Add Medical Record
                    </Link>
                </div>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-4 gap-4 mb-6">
                <button
                    v-for="tab in tabs"
                    :key="tab.key"
                    type="button"
                    class="box box--stacked rounded-xl border border-dashed p-5 text-left transition hover:border-primary/50 hover:bg-primary/5"
                    :class="filters.tab === tab.key ? 'border-primary bg-primary/5' : 'border-slate-300/80'"
                    @click="changeTab(tab.key)"
                >
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <p class="text-sm text-slate-500">{{ tab.label }}</p>
                            <p class="text-2xl font-semibold text-slate-800 mt-1">{{ tab.count }}</p>
                        </div>
                        <div class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-medium" :class="tab.tone">
                            <Lucide :icon="tab.icon" class="w-3.5 h-3.5 mr-1" />
                            {{ tab.key === 'all' ? 'All' : tab.label }}
                        </div>
                    </div>
                </button>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
                <div v-if="stats.expiring > 0" class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 flex items-start gap-3">
                    <Lucide icon="Clock3" class="w-5 h-5 flex-shrink-0 mt-0.5" />
                    <span><strong>{{ stats.expiring }}</strong> medical records are expiring within 30 days.</span>
                </div>
                <div v-if="stats.expired > 0" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 flex items-start gap-3">
                    <Lucide icon="AlertTriangle" class="w-5 h-5 flex-shrink-0 mt-0.5" />
                    <span><strong>{{ stats.expired }}</strong> medical records have expired.</span>
                </div>
            </div>

            <div class="box box--stacked p-5 mb-6">
                <div class="grid grid-cols-1 lg:grid-cols-5 gap-4">
                    <div class="lg:col-span-2 relative">
                        <Lucide icon="Search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                        <FormInput v-model="filters.search_term" type="text" class="pl-10" placeholder="Examiner, registry, SSN, driver..." />
                    </div>

                    <FormSelect v-model="filters.carrier_filter">
                        <option value="">All Carriers</option>
                        <option v-for="carrier in carriers" :key="carrier.id" :value="String(carrier.id)">
                            {{ carrier.name }}
                        </option>
                    </FormSelect>

                    <FormSelect v-model="filters.driver_filter">
                        <option value="">All Drivers</option>
                        <option v-for="driver in drivers" :key="driver.id" :value="String(driver.id)">
                            {{ driver.name }}
                        </option>
                    </FormSelect>

                    <div class="grid grid-cols-2 gap-3">
                        <Litepicker v-model="filters.date_from" :options="lpOptions" />
                        <Litepicker v-model="filters.date_to" :options="lpOptions" />
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3 mt-4">
                    <button
                        type="button"
                        @click="applyFilters"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition"
                    >
                        <Lucide icon="Filter" class="w-4 h-4" />
                        Apply Filters
                    </button>

                    <button
                        type="button"
                        @click="resetFilters"
                        class="inline-flex items-center gap-2 px-4 py-2 border border-slate-300 rounded-lg text-slate-600 hover:bg-slate-50 transition"
                    >
                        <Lucide icon="RotateCcw" class="w-4 h-4" />
                        Clear
                    </button>
                </div>
            </div>

            <div class="box box--stacked p-0 overflow-hidden">
                <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200/60">
                    <div>
                        <h2 class="text-base font-semibold text-slate-800">Medical Records</h2>
                        <p class="text-sm text-slate-500">{{ medicalRecords.total }} records</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-slate-50/80">
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">
                                    <Link :href="sortUrl('created_at')" class="inline-flex items-center gap-1 hover:text-slate-700">
                                        Created
                                        <Lucide v-if="props.filters.sort_field === 'created_at'" :icon="props.filters.sort_direction === 'asc' ? 'ChevronUp' : 'ChevronDown'" class="w-3 h-3" />
                                    </Link>
                                </th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Driver</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Carrier</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">
                                    <Link :href="sortUrl('medical_examiner_name')" class="inline-flex items-center gap-1 hover:text-slate-700">
                                        Examiner
                                        <Lucide v-if="props.filters.sort_field === 'medical_examiner_name'" :icon="props.filters.sort_direction === 'asc' ? 'ChevronUp' : 'ChevronDown'" class="w-3 h-3" />
                                    </Link>
                                </th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">
                                    <Link :href="sortUrl('medical_card_expiration_date')" class="inline-flex items-center gap-1 hover:text-slate-700">
                                        Expiration
                                        <Lucide v-if="props.filters.sort_field === 'medical_card_expiration_date'" :icon="props.filters.sort_direction === 'asc' ? 'ChevronUp' : 'ChevronDown'" class="w-3 h-3" />
                                    </Link>
                                </th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase">Status</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase text-center">Docs</th>
                                <th class="px-5 py-3 text-xs font-medium text-slate-500 uppercase text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="record in medicalRecords.data" :key="record.id" class="border-b border-slate-100 hover:bg-slate-50/50 transition">
                                <td class="px-5 py-4 text-sm text-slate-500">{{ formatDate(record.created_at) }}</td>
                                <td class="px-5 py-4">
                                    <div class="font-medium text-slate-700">{{ record.driver?.name ?? 'N/A' }}</div>
                                    <div class="text-xs text-slate-500">{{ record.driver?.email ?? 'No email' }}</div>
                                </td>
                                <td class="px-5 py-4 text-sm text-slate-600">{{ record.carrier?.name ?? 'N/A' }}</td>
                                <td class="px-5 py-4">
                                    <div class="font-medium text-slate-800">{{ record.medical_examiner_name || 'N/A' }}</div>
                                    <div class="text-xs text-slate-500">
                                        {{ record.medical_examiner_registry_number || record.social_security_number || 'No extra identifier' }}
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-sm text-slate-600">{{ formatDate(record.medical_card_expiration_date) }}</td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium" :class="statusClass(record.status)">
                                        {{ statusLabel(record.status) }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-center text-sm font-medium text-slate-700">
                                    <Link
                                        :href="route('admin.medical-records.documents.show', record.id)"
                                        class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600 hover:bg-primary/10 hover:text-primary transition"
                                    >
                                        {{ record.document_count }}
                                    </Link>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center justify-center gap-2">
                                        <Link :href="route('admin.medical-records.documents.show', record.id)" class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-slate-200 text-slate-500 hover:text-sky-500 hover:border-sky-200 hover:bg-sky-50">
                                            <Lucide icon="Files" class="w-4 h-4" />
                                        </Link>
                                        <Link :href="route('admin.medical-records.edit', record.id)" class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-slate-200 text-slate-500 hover:text-primary hover:border-primary/30 hover:bg-primary/5">
                                            <Lucide icon="PenLine" class="w-4 h-4" />
                                        </Link>
                                        <button type="button" class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-slate-200 text-slate-500 hover:text-red-600 hover:border-red-200 hover:bg-red-50" @click="openDeleteModal(record)">
                                            <Lucide icon="Trash2" class="w-4 h-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="medicalRecords.data.length === 0">
                                <td colspan="8" class="px-5 py-12 text-center">
                                    <div class="flex flex-col items-center gap-3 text-slate-500">
                                        <Lucide icon="HeartCrack" class="w-10 h-10 text-slate-300" />
                                        <div>
                                            <p class="font-medium text-slate-600">No medical records found</p>
                                            <p class="text-sm">Try adjusting your filters or create a new record.</p>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="medicalRecords.links.length > 3" class="px-5 py-4 border-t border-slate-200/60 flex flex-wrap gap-2">
                    <template v-for="link in medicalRecords.links" :key="link.label">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            class="px-3 py-2 rounded-lg text-sm border transition"
                            :class="link.active ? 'bg-primary text-white border-primary' : 'border-slate-200 text-slate-600 hover:bg-slate-50'"
                            v-html="link.label"
                        />
                        <span
                            v-else
                            class="px-3 py-2 rounded-lg text-sm border border-slate-100 text-slate-300"
                            v-html="link.label"
                        />
                    </template>
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
                    Do you really want to delete this medical record?<br>
                    This process cannot be undone.
                </p>
                <p class="mt-5 text-2xl font-medium text-slate-700">{{ selectedRecord?.social_security_number ?? 'Medical record' }}</p>

                <div class="mt-8 flex items-center justify-center gap-4">
                    <button
                        type="button"
                        class="min-w-[110px] rounded-xl border border-slate-300 px-6 py-3 text-lg text-slate-600 hover:bg-slate-50"
                        @click="deleteModalOpen = false"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        class="min-w-[110px] rounded-xl bg-red-600 px-6 py-3 text-lg font-semibold text-white hover:bg-red-700"
                        @click="confirmDelete"
                    >
                        Delete
                    </button>
                </div>
            </div>
        </Dialog.Panel>
    </Dialog>
</template>
