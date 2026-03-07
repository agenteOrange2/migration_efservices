<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import Lucide from '@/components/Base/Lucide'
import { FormInput, FormSelect } from '@/components/Base/Form'
import RazeLayout from '@/layouts/RazeLayout.vue'

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

const props = defineProps<{
    carriers: CarrierDoc[]
}>()

defineOptions({ layout: RazeLayout })

const search = ref('')
const statusFilter = ref('')

const filtered = computed(() => {
    let result = props.carriers
    if (search.value) {
        const q = search.value.toLowerCase()
        result = result.filter(c => c.name.toLowerCase().includes(q) || c.mc_number?.toLowerCase().includes(q) || c.dot_number?.toLowerCase().includes(q))
    }
    if (statusFilter.value) {
        result = result.filter(c => c.document_status === statusFilter.value)
    }
    return result
})

function progressColor(pct: number): string {
    if (pct >= 100) return 'bg-emerald-500'
    if (pct >= 50) return 'bg-amber-500'
    return 'bg-red-500'
}

function statusBadge(status: string): string {
    if (status === 'active') return 'bg-emerald-100 text-emerald-700'
    if (status === 'pending') return 'bg-amber-100 text-amber-700'
    return 'bg-red-100 text-red-700'
}
</script>

<template>
    <Head title="Carriers Documents Progress" />

    <div class="grid grid-cols-12 gap-x-6 gap-y-10">
        <div class="col-span-12">
            <div class="box box--stacked p-6 mb-6">
                <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                            <Lucide icon="FolderCheck" class="w-8 h-8 text-primary" />
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-slate-800">Carriers Documents</h1>
                            <p class="text-slate-500">Review and manage document compliance for all carriers</p>
                        </div>
                    </div>
                    <Link :href="route('admin.carriers.index')" class="inline-flex items-center gap-2 px-4 py-2 border border-slate-300 rounded-lg text-slate-600 hover:bg-slate-50 transition">
                        <Lucide icon="ArrowLeft" class="w-4 h-4" /> Back to Carriers
                    </Link>
                </div>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <div class="box box--stacked p-4">
                    <div class="text-2xl font-bold text-slate-800">{{ carriers.length }}</div>
                    <div class="text-xs text-slate-500">Total Carriers</div>
                </div>
                <div class="box box--stacked p-4">
                    <div class="text-2xl font-bold text-emerald-600">{{ carriers.filter(c => c.document_status === 'active').length }}</div>
                    <div class="text-xs text-slate-500">Complete</div>
                </div>
                <div class="box box--stacked p-4">
                    <div class="text-2xl font-bold text-amber-600">{{ carriers.filter(c => c.document_status === 'pending').length }}</div>
                    <div class="text-xs text-slate-500">In Progress</div>
                </div>
                <div class="box box--stacked p-4">
                    <div class="text-2xl font-bold text-red-600">{{ carriers.filter(c => c.document_status === 'inactive').length }}</div>
                    <div class="text-xs text-slate-500">No Documents</div>
                </div>
            </div>

            <div class="box box--stacked p-0">
                <div class="flex flex-col sm:flex-row gap-3 p-5 border-b border-slate-200/60">
                    <div class="relative flex-1">
                        <Lucide icon="Search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                        <FormInput v-model="search" type="text" placeholder="Search by name, MC or DOT..." class="pl-10" />
                    </div>
                    <FormSelect v-model="statusFilter" class="w-full sm:w-44">
                        <option value="">All Status</option>
                        <option value="active">Complete</option>
                        <option value="pending">In Progress</option>
                        <option value="inactive">No Documents</option>
                    </FormSelect>
                </div>

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
                            <tr v-for="c in filtered" :key="c.id" class="border-b border-slate-100 hover:bg-slate-50/50 transition">
                                <td class="px-5 py-4 font-medium text-slate-700">{{ c.name }}</td>
                                <td class="px-5 py-4 text-sm text-slate-600">
                                    <div v-if="c.mc_number">MC: {{ c.mc_number }}</div>
                                    <div v-if="c.dot_number">DOT: {{ c.dot_number }}</div>
                                </td>
                                <td class="px-5 py-4 text-sm text-slate-600">{{ c.membership_name ?? '-' }}</td>
                                <td class="px-5 py-4 w-48">
                                    <div class="flex items-center gap-3">
                                        <div class="flex-1">
                                            <div class="w-full bg-slate-100 rounded-full h-2">
                                                <div :class="progressColor(c.completion_percentage)" class="h-2 rounded-full transition-all" :style="{ width: c.completion_percentage + '%' }"></div>
                                            </div>
                                        </div>
                                        <span class="text-xs font-medium text-slate-600 whitespace-nowrap">{{ c.approved }}/{{ c.total }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" :class="statusBadge(c.document_status)">
                                        {{ c.document_status === 'active' ? 'Complete' : c.document_status === 'pending' ? 'In Progress' : 'None' }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-center">
                                    <Link :href="route('admin.carriers-documents.carrier', c.slug)" class="inline-flex items-center gap-1 px-3 py-1.5 bg-primary/10 text-primary rounded-lg hover:bg-primary/20 text-sm transition">
                                        <Lucide icon="FileSearch" class="w-3 h-3" /> Review
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="filtered.length === 0">
                                <td colspan="6" class="px-5 py-12 text-center text-slate-400">
                                    <Lucide icon="Inbox" class="w-12 h-12 mx-auto mb-3 text-slate-300" />
                                    <p>No carriers found</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>
