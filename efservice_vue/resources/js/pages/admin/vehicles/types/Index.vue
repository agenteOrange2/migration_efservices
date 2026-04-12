<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { reactive, ref, watch } from 'vue'
import { useDebounceFn } from '@vueuse/core'
import Lucide from '@/components/Base/Lucide'
import { Dialog } from '@/components/Base/Headless'
import RazeLayout from '@/layouts/RazeLayout.vue'

declare function route(name: string, params?: any): string

defineOptions({ layout: RazeLayout })

const props = defineProps<{
    types: { data: any[]; links: { url: string | null; label: string; active: boolean }[]; total: number; last_page: number }
    filters: { search: string }
    stats: { total: number; in_use: number; unused: number }
    routeNames?: Partial<{
        index: string
        store: string
        update: string
        destroy: string
    }>
}>()

const defaultRouteNames = {
    index: 'admin.vehicle-types.index',
    store: 'admin.vehicle-types.store',
    update: 'admin.vehicle-types.update',
    destroy: 'admin.vehicle-types.destroy',
} as const

function namedRoute(name: keyof typeof defaultRouteNames, params?: any) {
    return route(props.routeNames?.[name] ?? defaultRouteNames[name], params)
}

const search = ref(props.filters.search ?? '')
const showModal = ref(false)
const modalMode = ref<'create' | 'edit'>('create')
const saving = ref(false)
const errors = ref<Record<string, string[]>>({})
const form = reactive({ id: 0, name: '' })

function applyFilters() {
    router.get(namedRoute('index'), {
        search: search.value || undefined,
    }, { preserveState: true, replace: true })
}

const debouncedSearch = useDebounceFn(applyFilters, 300)
watch(search, debouncedSearch)

function openCreate() {
    form.id = 0
    form.name = ''
    errors.value = {}
    modalMode.value = 'create'
    showModal.value = true
}

function openEdit(type: any) {
    form.id = type.id
    form.name = type.name
    errors.value = {}
    modalMode.value = 'edit'
    showModal.value = true
}

function saveForm() {
    saving.value = true
    errors.value = {}

    const method = modalMode.value === 'create' ? 'post' : 'put'
    const url = modalMode.value === 'create'
        ? namedRoute('store')
        : namedRoute('update', form.id)

    router[method](url, { name: form.name }, {
        preserveScroll: true,
        onSuccess: () => {
            showModal.value = false
            saving.value = false
        },
        onError: (e) => {
            errors.value = e as any
            saving.value = false
        },
    })
}

function deleteType(type: any) {
    if (!confirm(`Delete "${type.name}"?`)) return
    router.delete(namedRoute('destroy', type.id), { preserveScroll: true })
}
</script>

<template>
    <Head title="Vehicle Types" />

    <div class="p-5 sm:p-8 max-w-screen-2xl mx-auto">
        <div class="box box--stacked p-8 mb-8">
            <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                        <Lucide icon="Layers" class="w-8 h-8 text-primary" />
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-slate-800 mb-1">Vehicle Types</h1>
                        <p class="text-slate-500 text-sm">Manage available vehicle types across the fleet module.</p>
                    </div>
                </div>
                <button @click="openCreate" class="inline-flex items-center gap-2 bg-primary text-white px-4 py-2 rounded-lg hover:bg-primary/90 transition-colors font-medium">
                    <Lucide icon="Plus" class="w-4 h-4" />
                    Add Vehicle Type
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="box box--stacked rounded-xl border border-dashed border-slate-300/80 p-5"><p class="text-sm text-slate-500">Total</p><p class="mt-1 text-2xl font-semibold text-slate-800">{{ stats.total }}</p></div>
            <div class="box box--stacked rounded-xl border border-dashed border-slate-300/80 p-5"><p class="text-sm text-slate-500">In Use</p><p class="mt-1 text-2xl font-semibold text-slate-800">{{ stats.in_use }}</p></div>
            <div class="box box--stacked rounded-xl border border-dashed border-slate-300/80 p-5"><p class="text-sm text-slate-500">Unused</p><p class="mt-1 text-2xl font-semibold text-slate-800">{{ stats.unused }}</p></div>
        </div>

        <div class="box box--stacked p-6 mb-6">
            <div class="relative max-w-xl">
                <Lucide icon="Search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                <input v-model="search" type="text" placeholder="Search vehicle type..." class="w-full text-sm border-slate-200 shadow-sm rounded-lg py-2.5 px-3 pl-10 border focus:ring-primary focus:border-primary" />
            </div>
        </div>

        <div class="box box--stacked">
            <div class="p-6 border-b border-slate-200/60 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <Lucide icon="List" class="w-5 h-5 text-primary" />
                    <h2 class="text-lg font-semibold text-slate-800">Vehicle Type List</h2>
                </div>
                <span class="bg-primary/10 text-primary text-xs font-semibold px-3 py-1.5 rounded-full">{{ types.total }} Total</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200/60">
                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Type</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Created</th>
                            <th class="px-6 py-4 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Vehicles</th>
                            <th class="px-6 py-4 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200/60">
                        <tr v-for="type in types.data" :key="type.id" class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-primary/10 rounded-lg"><Lucide icon="Layers" class="w-4 h-4 text-primary" /></div>
                                    <div class="font-medium text-slate-800">{{ type.name }}</div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-600">{{ type.created_at }}</td>
                            <td class="px-6 py-4 text-center">
                                <span class="inline-flex items-center gap-1 bg-primary/10 text-primary text-xs font-semibold px-2.5 py-1 rounded-full">
                                    <Lucide icon="Truck" class="w-3 h-3" />
                                    {{ type.vehicles_count }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-center gap-2">
                                    <button @click="openEdit(type)" class="p-1.5 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-100 transition-colors"><Lucide icon="Edit" class="w-4 h-4" /></button>
                                    <button @click="deleteType(type)" :disabled="type.vehicles_count > 0" class="p-1.5 rounded-lg border border-danger/20 text-danger hover:bg-danger/10 transition-colors disabled:opacity-40 disabled:cursor-not-allowed"><Lucide icon="Trash2" class="w-4 h-4" /></button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!types.data.length">
                            <td colspan="4" class="px-6 py-12 text-center">
                                <Lucide icon="Layers" class="w-12 h-12 text-slate-300 mx-auto mb-3" />
                                <p class="text-slate-500 font-medium">No vehicle types found</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="types.last_page > 1" class="p-6 border-t border-slate-200/60 flex flex-wrap items-center gap-1">
                <template v-for="link in types.links" :key="link.label">
                    <Link v-if="link.url" :href="link.url" class="px-3 py-1.5 text-sm rounded-md border transition-colors" :class="link.active ? 'bg-primary text-white border-primary' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50'" v-html="link.label" />
                    <span v-else class="px-3 py-1.5 text-sm rounded-md border bg-white text-slate-300 border-slate-200 cursor-default" v-html="link.label" />
                </template>
            </div>
        </div>
    </div>

    <Dialog :open="showModal" @close="showModal = false" static-backdrop>
        <Dialog.Panel class="w-full max-w-xl overflow-hidden">
            <div class="sticky top-0 bg-white px-6 pt-6 pb-4 border-b border-slate-200 z-10">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-primary/10 rounded-lg"><Lucide :icon="modalMode === 'create' ? 'Plus' : 'Edit'" class="w-5 h-5 text-primary" /></div>
                        <h3 class="text-lg font-bold text-slate-800">{{ modalMode === 'create' ? 'Add Vehicle Type' : 'Edit Vehicle Type' }}</h3>
                    </div>
                    <button @click="showModal = false" class="p-1.5 rounded-lg hover:bg-slate-100 text-slate-400"><Lucide icon="X" class="w-5 h-5" /></button>
                </div>
            </div>

            <div class="px-6 py-5">
                <label class="block text-sm font-medium text-slate-700 mb-1">Type Name <span class="text-red-500">*</span></label>
                <input v-model="form.name" type="text" class="w-full border-slate-300 rounded-lg text-sm px-3 py-2.5 border focus:ring-primary focus:border-primary" placeholder="Enter type name" />
                <p v-if="errors.name" class="text-xs text-red-500 mt-1">{{ errors.name[0] }}</p>
            </div>

            <div class="sticky bottom-0 bg-white px-6 py-4 border-t border-slate-200 flex justify-end gap-3">
                <button @click="showModal = false" class="px-4 py-2 text-sm rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 transition-colors font-medium">Cancel</button>
                <button @click="saveForm" :disabled="saving" class="px-4 py-2 text-sm rounded-lg bg-primary text-white hover:bg-primary/90 transition-colors font-medium disabled:opacity-60 flex items-center gap-2">
                    <Lucide v-if="saving" icon="Loader" class="w-4 h-4 animate-spin" />
                    {{ saving ? 'Saving…' : (modalMode === 'create' ? 'Create Type' : 'Save Changes') }}
                </button>
            </div>
        </Dialog.Panel>
    </Dialog>
</template>
