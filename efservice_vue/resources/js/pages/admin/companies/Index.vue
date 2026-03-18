<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, reactive, watch } from 'vue'
import Lucide from '@/components/Base/Lucide'
import RazeLayout from '@/layouts/RazeLayout.vue'
import { Dialog } from '@/components/Base/Headless'
import { useDebounceFn } from '@vueuse/core'

declare function route(name: string, params?: any): string

defineOptions({ layout: RazeLayout })

interface Company {
    id: number
    company_name: string
    address: string | null
    city: string | null
    state: string | null
    zip: string | null
    contact: string | null
    phone: string | null
    email: string | null
    fax: string | null
    driver_employment_companies_count: number
}

const props = defineProps<{
    companies: {
        data: Company[]
        links: { url: string | null; label: string; active: boolean }[]
        current_page: number
        last_page: number
        total: number
    }
    allStates: string[]
    allCities: string[]
    filters: { search?: string; state?: string; city?: string }
}>()

// Filters
const search = ref(props.filters.search ?? '')
const stateFilter = ref(props.filters.state ?? '')
const cityFilter = ref(props.filters.city ?? '')

function applyFilters() {
    router.get(route('admin.companies.index'), {
        search: search.value || undefined,
        state: stateFilter.value || undefined,
        city: cityFilter.value || undefined,
    }, { preserveState: true, replace: true })
}

const debouncedSearch = useDebounceFn(applyFilters, 400)
watch(search, debouncedSearch)
watch([stateFilter, cityFilter], applyFilters)

function clearFilters() {
    router.get(route('admin.companies.index'))
}

// Modal state
const showModal = ref(false)
const modalMode = ref<'create' | 'edit'>('create')
const saving = ref(false)
const errors = ref<Record<string, string[]>>({})

function blankForm() {
    return {
        id: 0,
        company_name: '',
        address: '',
        city: '',
        state: '',
        zip: '',
        contact: '',
        phone: '',
        email: '',
        fax: '',
    }
}

const form = reactive(blankForm())

function openCreate() {
    Object.assign(form, blankForm())
    errors.value = {}
    modalMode.value = 'create'
    showModal.value = true
}

function openEdit(company: Company) {
    Object.assign(form, {
        id: company.id,
        company_name: company.company_name,
        address: company.address ?? '',
        city: company.city ?? '',
        state: company.state ?? '',
        zip: company.zip ?? '',
        contact: company.contact ?? '',
        phone: company.phone ?? '',
        email: company.email ?? '',
        fax: company.fax ?? '',
    })
    errors.value = {}
    modalMode.value = 'edit'
    showModal.value = true
}

function saveForm() {
    saving.value = true
    errors.value = {}

    const url = modalMode.value === 'create'
        ? route('admin.companies.store')
        : route('admin.companies.update', form.id)

    const method = modalMode.value === 'create' ? 'post' : 'put'

    router[method](url, { ...form }, {
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

function deleteCompany(company: Company) {
    if (!confirm(`Delete "${company.company_name}"? This cannot be undone.`)) return

    router.delete(route('admin.companies.destroy', company.id), {
        preserveScroll: true,
    })
}
</script>

<template>
    <Head title="Master Companies" />

    <div class="p-5 sm:p-8 max-w-screen-2xl mx-auto">
        <!-- Header -->
        <div class="box box--stacked p-8 mb-8">
            <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                        <Lucide icon="Building2" class="w-8 h-8 text-primary" />
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-slate-800 mb-1">Master Companies</h1>
                        <p class="text-slate-500 text-sm">Manage employment verification companies database</p>
                    </div>
                </div>
                <button
                    @click="openCreate"
                    class="inline-flex items-center gap-2 bg-primary text-white px-4 py-2 rounded-lg hover:bg-primary/90 transition-colors font-medium"
                >
                    <Lucide icon="Plus" class="w-4 h-4" />
                    Add New Company
                </button>
            </div>
        </div>

        <!-- Filters -->
        <div class="box box--stacked p-6 mb-6">
            <div class="flex items-center gap-3 mb-4">
                <Lucide icon="Filter" class="w-5 h-5 text-primary" />
                <h2 class="text-lg font-semibold text-slate-800">Filters</h2>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Search</label>
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Company name, city, contact, email..."
                        class="w-full text-sm border-slate-200 shadow-sm rounded-lg py-2.5 px-3 border focus:ring-primary focus:border-primary"
                    />
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">State</label>
                    <select v-model="stateFilter" class="w-full text-sm border-slate-200 shadow-sm rounded-lg py-2.5 px-3 border focus:ring-primary focus:border-primary">
                        <option value="">All States</option>
                        <option v-for="s in allStates" :key="s" :value="s">{{ s }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">City</label>
                    <select v-model="cityFilter" class="w-full text-sm border-slate-200 shadow-sm rounded-lg py-2.5 px-3 border focus:ring-primary focus:border-primary">
                        <option value="">All Cities</option>
                        <option v-for="c in allCities" :key="c" :value="c">{{ c }}</option>
                    </select>
                </div>
                <div class="md:col-span-4 flex gap-2">
                    <button @click="applyFilters" class="inline-flex items-center gap-2 bg-primary text-white px-4 py-2 rounded-lg hover:bg-primary/90 transition-colors text-sm font-medium">
                        <Lucide icon="Search" class="w-4 h-4" /> Apply Filters
                    </button>
                    <button @click="clearFilters" class="inline-flex items-center gap-2 bg-white border border-slate-300 text-slate-700 px-4 py-2 rounded-lg hover:bg-slate-50 transition-colors text-sm font-medium">
                        <Lucide icon="X" class="w-4 h-4" /> Clear
                    </button>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="box box--stacked">
            <div class="p-6 border-b border-slate-200/60 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <Lucide icon="List" class="w-5 h-5 text-primary" />
                    <h2 class="text-lg font-semibold text-slate-800">Companies List</h2>
                </div>
                <span class="bg-primary/10 text-primary text-xs font-semibold px-3 py-1.5 rounded-full">
                    {{ companies.total }} Total
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200/60">
                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Company</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Contact</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Location</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Phone / Fax</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Email</th>
                            <th class="px-6 py-4 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Drivers</th>
                            <th class="px-6 py-4 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200/60">
                        <tr v-for="company in companies.data" :key="company.id" class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-primary/10 rounded-lg">
                                        <Lucide icon="Building2" class="w-4 h-4 text-primary" />
                                    </div>
                                    <Link
                                        :href="route('admin.companies.show', company.id)"
                                        class="font-medium text-primary hover:text-primary/80 transition-colors"
                                    >{{ company.company_name }}</Link>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-700">{{ company.contact || '—' }}</td>
                            <td class="px-6 py-4">
                                <div v-if="company.city || company.state" class="flex items-center gap-1.5 text-sm text-slate-700">
                                    <Lucide icon="MapPin" class="w-3 h-3 text-slate-400" />
                                    {{ [company.city, company.state].filter(Boolean).join(', ') }}
                                </div>
                                <span v-else class="text-sm text-slate-400">—</span>
                            </td>
                            <td class="px-6 py-4">
                                <div v-if="company.phone" class="flex items-center gap-1.5 text-sm text-slate-700">
                                    <Lucide icon="Phone" class="w-3 h-3 text-slate-400" />
                                    {{ company.phone }}
                                </div>
                                <div v-if="company.fax" class="flex items-center gap-1.5 text-sm text-slate-500 mt-0.5">
                                    <Lucide icon="Printer" class="w-3 h-3 text-slate-400" />
                                    {{ company.fax }}
                                </div>
                                <span v-if="!company.phone && !company.fax" class="text-sm text-slate-400">—</span>
                            </td>
                            <td class="px-6 py-4">
                                <div v-if="company.email" class="flex items-center gap-1.5 text-sm text-slate-700">
                                    <Lucide icon="Mail" class="w-3 h-3 text-slate-400" />
                                    {{ company.email }}
                                </div>
                                <span v-else class="text-sm text-slate-400">—</span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="inline-flex items-center gap-1 bg-primary/10 text-primary text-xs font-semibold px-2.5 py-1 rounded-full">
                                    <Lucide icon="Users" class="w-3 h-3" />
                                    {{ company.driver_employment_companies_count }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-center gap-2">
                                    <Link
                                        :href="route('admin.companies.show', company.id)"
                                        title="View details"
                                        class="p-1.5 rounded-lg border border-primary/30 text-primary hover:bg-primary/10 transition-colors"
                                    >
                                        <Lucide icon="Eye" class="w-4 h-4" />
                                    </Link>
                                    <button
                                        @click="openEdit(company)"
                                        title="Edit company"
                                        class="p-1.5 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-100 transition-colors"
                                    >
                                        <Lucide icon="Edit" class="w-4 h-4" />
                                    </button>
                                    <button
                                        @click="deleteCompany(company)"
                                        title="Delete company"
                                        :disabled="company.driver_employment_companies_count > 0"
                                        class="p-1.5 rounded-lg border border-red-200 text-red-600 hover:bg-red-50 transition-colors disabled:opacity-40 disabled:cursor-not-allowed"
                                    >
                                        <Lucide icon="Trash2" class="w-4 h-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="companies.data.length === 0">
                            <td colspan="7" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <Lucide icon="Building2" class="w-12 h-12 text-slate-300" />
                                    <p class="text-slate-500 font-medium">No companies found</p>
                                    <p class="text-sm text-slate-400">Try adjusting your filters or add a new company</p>
                                    <button @click="openCreate" class="mt-2 inline-flex items-center gap-2 bg-primary text-white px-4 py-2 rounded-lg hover:bg-primary/90 text-sm font-medium">
                                        <Lucide icon="Plus" class="w-4 h-4" /> Add Company
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="companies.last_page > 1" class="p-6 border-t border-slate-200/60 flex flex-wrap items-center gap-1">
                <template v-for="link in companies.links" :key="link.label">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        class="px-3 py-1.5 text-sm rounded-md border transition-colors"
                        :class="link.active
                            ? 'bg-primary text-white border-primary'
                            : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50'"
                        v-html="link.label"
                        preserve-scroll
                    />
                    <span
                        v-else
                        class="px-3 py-1.5 text-sm rounded-md border bg-white text-slate-300 border-slate-200 cursor-default"
                        v-html="link.label"
                    />
                </template>
            </div>
        </div>
    </div>

    <!-- Create / Edit Modal -->
    <Dialog :open="showModal" @close="showModal = false" static-backdrop>
        <Dialog.Panel class="w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <div class="sticky top-0 bg-white px-6 pt-6 pb-4 border-b border-slate-200 z-10">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-primary/10 rounded-lg">
                            <Lucide :icon="modalMode === 'create' ? 'Plus' : 'Edit'" class="w-5 h-5 text-primary" />
                        </div>
                        <h3 class="text-lg font-bold text-slate-800">
                            {{ modalMode === 'create' ? 'Add New Company' : 'Edit Company' }}
                        </h3>
                    </div>
                    <button @click="showModal = false" class="p-1.5 rounded-lg hover:bg-slate-100 text-slate-400">
                        <Lucide icon="X" class="w-5 h-5" />
                    </button>
                </div>
            </div>

            <div class="px-6 py-5 space-y-4">
                <!-- Company Name -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Company Name <span class="text-red-500">*</span></label>
                    <input v-model="form.company_name" type="text" class="w-full border-slate-300 rounded-lg text-sm px-3 py-2.5 border focus:ring-primary focus:border-primary" placeholder="Company name" />
                    <p v-if="errors.company_name" class="text-xs text-red-500 mt-1">{{ errors.company_name[0] }}</p>
                </div>

                <!-- Address -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Address</label>
                    <input v-model="form.address" type="text" class="w-full border-slate-300 rounded-lg text-sm px-3 py-2.5 border focus:ring-primary focus:border-primary" placeholder="Street address" />
                </div>

                <!-- City / State / Zip -->
                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">City</label>
                        <input v-model="form.city" type="text" class="w-full border-slate-300 rounded-lg text-sm px-3 py-2.5 border focus:ring-primary focus:border-primary" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">State</label>
                        <input v-model="form.state" type="text" class="w-full border-slate-300 rounded-lg text-sm px-3 py-2.5 border focus:ring-primary focus:border-primary" maxlength="10" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">ZIP</label>
                        <input v-model="form.zip" type="text" class="w-full border-slate-300 rounded-lg text-sm px-3 py-2.5 border focus:ring-primary focus:border-primary" maxlength="20" />
                    </div>
                </div>

                <!-- Contact / Phone / Fax -->
                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Contact Person</label>
                        <input v-model="form.contact" type="text" class="w-full border-slate-300 rounded-lg text-sm px-3 py-2.5 border focus:ring-primary focus:border-primary" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Phone</label>
                        <input v-model="form.phone" type="text" class="w-full border-slate-300 rounded-lg text-sm px-3 py-2.5 border focus:ring-primary focus:border-primary" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Fax</label>
                        <input v-model="form.fax" type="text" class="w-full border-slate-300 rounded-lg text-sm px-3 py-2.5 border focus:ring-primary focus:border-primary" />
                    </div>
                </div>

                <!-- Email -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                    <input v-model="form.email" type="email" class="w-full border-slate-300 rounded-lg text-sm px-3 py-2.5 border focus:ring-primary focus:border-primary" placeholder="contact@company.com" />
                    <p v-if="errors.email" class="text-xs text-red-500 mt-1">{{ errors.email[0] }}</p>
                    <p v-if="modalMode === 'edit'" class="text-xs text-amber-600 mt-1">
                        Changing the email will update all linked driver employment records.
                    </p>
                </div>
            </div>

            <div class="sticky bottom-0 bg-white px-6 py-4 border-t border-slate-200 flex justify-end gap-3">
                <button @click="showModal = false" class="px-4 py-2 text-sm rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 transition-colors font-medium">
                    Cancel
                </button>
                <button
                    @click="saveForm"
                    :disabled="saving"
                    class="px-4 py-2 text-sm rounded-lg bg-primary text-white hover:bg-primary/90 transition-colors font-medium disabled:opacity-60 flex items-center gap-2"
                >
                    <Lucide v-if="saving" icon="Loader" class="w-4 h-4 animate-spin" />
                    {{ saving ? 'Saving…' : (modalMode === 'create' ? 'Create Company' : 'Save Changes') }}
                </button>
            </div>
        </Dialog.Panel>
    </Dialog>
</template>
