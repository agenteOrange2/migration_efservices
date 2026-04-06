<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'
import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import Litepicker from '@/components/Base/Litepicker/Litepicker.vue'
import TomSelect from '@/components/Base/TomSelect/TomSelect.vue'
import { FormCheck, FormInput } from '@/components/Base/Form'
import RazeLayout from '@/layouts/RazeLayout.vue'

declare function route(name: string, params?: any): string

defineOptions({ layout: RazeLayout })

const lpOptions = { singleMode: true, format: 'M/D/YYYY', autoApply: true }

interface CarrierOption {
    id: number
    name: string
}

interface DriverOption {
    id: number
    carrier_id: number | null
    carrier_name: string | null
    name: string
    email: string | null
}

interface CompanyOption {
    id: number
    company_name: string
    address: string | null
    city: string | null
    state: string | null
    zip: string | null
    phone: string | null
    fax: string | null
    contact: string | null
    email: string | null
}

const props = defineProps<{
    carriers: CarrierOption[]
    drivers: DriverOption[]
}>()

const companySearch = ref('')
const companyResults = ref<CompanyOption[]>([])
const searchLoading = ref(false)
const selectedCompany = ref<CompanyOption | null>(null)

const form = useForm({
    carrier_id: '',
    driver_id: '',
    company_mode: 'existing',
    selected_company_id: '',
    company_name: '',
    company_email: '',
    company_address: '',
    company_city: '',
    company_state: '',
    company_zip: '',
    company_phone: '',
    company_contact: '',
    company_fax: '',
    employed_from: '',
    employed_to: '',
    positions_held: '',
    subject_to_fmcsr: false,
    safety_sensitive_function: false,
    reason_for_leaving: '',
    other_reason_description: '',
    explanation: '',
    add_to_directory: true,
    send_email: true,
})

const filteredDrivers = computed(() => {
    if (!form.carrier_id) return []
    return props.drivers.filter(driver => String(driver.carrier_id ?? '') === form.carrier_id)
})

watch(() => form.carrier_id, () => {
    form.driver_id = ''
})

watch(() => form.company_mode, (mode) => {
    selectedCompany.value = null
    companyResults.value = []
    companySearch.value = ''
    form.selected_company_id = ''

    if (mode === 'new') {
        form.company_name = ''
        form.company_email = ''
        form.company_address = ''
        form.company_city = ''
        form.company_state = ''
        form.company_zip = ''
        form.company_phone = ''
        form.company_contact = ''
        form.company_fax = ''
    }
})

function applyCompany(company: CompanyOption) {
    selectedCompany.value = company
    form.selected_company_id = String(company.id)
    form.company_name = company.company_name ?? ''
    form.company_email = company.email ?? ''
    form.company_address = company.address ?? ''
    form.company_city = company.city ?? ''
    form.company_state = company.state ?? ''
    form.company_zip = company.zip ?? ''
    form.company_phone = company.phone ?? ''
    form.company_contact = company.contact ?? ''
    form.company_fax = company.fax ?? ''
}

async function searchCompanies() {
    if (!companySearch.value.trim()) {
        companyResults.value = []
        return
    }

    searchLoading.value = true

    try {
        const response = await fetch(`${route('admin.drivers.employment.search-companies')}?q=${encodeURIComponent(companySearch.value)}`, {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        })

        if (!response.ok) throw new Error('Search failed')

        companyResults.value = await response.json()
    } catch {
        companyResults.value = []
    } finally {
        searchLoading.value = false
    }
}

function submit() {
    form.post(route('admin.drivers.employment-verification.store'))
}
</script>

<template>
    <Head title="New Employment Verification" />

    <div class="p-5 sm:p-8 max-w-screen-2xl mx-auto">
        <div class="box box--stacked p-8 mb-8">
            <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                        <Lucide icon="FilePlus" class="w-8 h-8 text-primary" />
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-slate-800 mb-1">New Employment Verification</h1>
                        <p class="text-slate-500 text-sm">Create a new employment verification request and optionally send the first email.</p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <Link
                        :href="route('admin.drivers.employment-verification.index')"
                        class="inline-flex items-center gap-2 bg-white border border-slate-300 text-slate-700 px-4 py-2 rounded-lg hover:bg-slate-50 transition-colors font-medium text-sm"
                    >
                        <Lucide icon="ArrowLeft" class="w-4 h-4" />
                        Back
                    </Link>
                </div>
            </div>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <div class="box box--stacked p-6">
                <div class="flex items-center gap-3 mb-5">
                    <Lucide icon="Users" class="w-5 h-5 text-primary" />
                    <h2 class="text-lg font-semibold text-slate-800">Driver Selection</h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Carrier <span class="text-danger">*</span></label>
                        <TomSelect v-model="form.carrier_id">
                            <option value="">Select carrier</option>
                            <option v-for="carrier in carriers" :key="carrier.id" :value="String(carrier.id)">
                                {{ carrier.name }}
                            </option>
                        </TomSelect>
                        <p v-if="form.errors.carrier_id" class="text-danger text-xs mt-1">{{ form.errors.carrier_id }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Driver <span class="text-danger">*</span></label>
                        <TomSelect v-model="form.driver_id">
                            <option value="">Select driver</option>
                            <option v-for="driver in filteredDrivers" :key="driver.id" :value="String(driver.id)">
                                {{ driver.name }}{{ driver.email ? ` - ${driver.email}` : '' }}
                            </option>
                        </TomSelect>
                        <p v-if="form.errors.driver_id" class="text-danger text-xs mt-1">{{ form.errors.driver_id }}</p>
                    </div>
                </div>
            </div>

            <div class="box box--stacked p-6">
                <div class="flex items-center gap-3 mb-5">
                    <Lucide icon="Building2" class="w-5 h-5 text-primary" />
                    <h2 class="text-lg font-semibold text-slate-800">Company</h2>
                </div>

                <div class="flex flex-wrap gap-3 mb-5">
                    <button
                        type="button"
                        @click="form.company_mode = 'existing'"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border text-sm font-medium transition-colors"
                        :class="form.company_mode === 'existing' ? 'border-primary bg-primary/10 text-primary' : 'border-slate-200 text-slate-600 hover:bg-slate-50'"
                    >
                        <Lucide icon="Building" class="w-4 h-4" />
                        Use Existing Company
                    </button>
                    <button
                        type="button"
                        @click="form.company_mode = 'new'"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border text-sm font-medium transition-colors"
                        :class="form.company_mode === 'new' ? 'border-primary bg-primary/10 text-primary' : 'border-slate-200 text-slate-600 hover:bg-slate-50'"
                    >
                        <Lucide icon="SquarePen" class="w-4 h-4" />
                        Create New Company
                    </button>
                </div>

                <div v-if="form.company_mode === 'existing'" class="rounded-xl border border-slate-200 p-4 mb-5">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Search company directory</label>
                    <div class="flex gap-3">
                        <FormInput v-model="companySearch" type="text" placeholder="Company name, email, phone..." @keydown.enter.prevent="searchCompanies" />
                        <Button type="button" variant="outline-secondary" @click="searchCompanies" :disabled="searchLoading">
                            {{ searchLoading ? 'Searching...' : 'Search' }}
                        </Button>
                    </div>

                    <div v-if="companyResults.length" class="mt-4 border border-slate-200 rounded-lg divide-y divide-slate-200 overflow-hidden">
                        <button
                            v-for="company in companyResults"
                            :key="company.id"
                            type="button"
                            @click="applyCompany(company)"
                            class="w-full text-left px-4 py-3 hover:bg-slate-50 transition-colors"
                            :class="String(company.id) === form.selected_company_id ? 'bg-primary/5' : ''"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="font-medium text-slate-800">{{ company.company_name }}</p>
                                    <p class="text-xs text-slate-500">
                                        {{ company.email || 'No email' }}<span v-if="company.phone"> | {{ company.phone }}</span>
                                    </p>
                                </div>
                                <span class="text-xs text-primary font-medium">Select</span>
                            </div>
                        </button>
                    </div>

                    <p v-if="form.errors.selected_company_id" class="text-danger text-xs mt-2">{{ form.errors.selected_company_id }}</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-slate-700 mb-1">Company Name <span class="text-danger">*</span></label>
                        <FormInput v-model="form.company_name" type="text" placeholder="Company name" />
                        <p v-if="form.errors.company_name" class="text-danger text-xs mt-1">{{ form.errors.company_name }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                        <FormInput v-model="form.company_email" type="email" placeholder="company@example.com" />
                        <p v-if="form.errors.company_email" class="text-danger text-xs mt-1">{{ form.errors.company_email }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Phone</label>
                        <FormInput v-model="form.company_phone" type="text" placeholder="(555) 123-4567" />
                        <p v-if="form.errors.company_phone" class="text-danger text-xs mt-1">{{ form.errors.company_phone }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Contact</label>
                        <FormInput v-model="form.company_contact" type="text" placeholder="Contact name" />
                        <p v-if="form.errors.company_contact" class="text-danger text-xs mt-1">{{ form.errors.company_contact }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Fax</label>
                        <FormInput v-model="form.company_fax" type="text" placeholder="Fax number" />
                        <p v-if="form.errors.company_fax" class="text-danger text-xs mt-1">{{ form.errors.company_fax }}</p>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-slate-700 mb-1">Address</label>
                        <FormInput v-model="form.company_address" type="text" placeholder="Street address" />
                        <p v-if="form.errors.company_address" class="text-danger text-xs mt-1">{{ form.errors.company_address }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">City</label>
                        <FormInput v-model="form.company_city" type="text" placeholder="City" />
                        <p v-if="form.errors.company_city" class="text-danger text-xs mt-1">{{ form.errors.company_city }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">State</label>
                        <FormInput v-model="form.company_state" type="text" placeholder="State" />
                        <p v-if="form.errors.company_state" class="text-danger text-xs mt-1">{{ form.errors.company_state }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">ZIP</label>
                        <FormInput v-model="form.company_zip" type="text" placeholder="ZIP code" />
                        <p v-if="form.errors.company_zip" class="text-danger text-xs mt-1">{{ form.errors.company_zip }}</p>
                    </div>
                </div>
            </div>

            <div class="box box--stacked p-6">
                <div class="flex items-center gap-3 mb-5">
                    <Lucide icon="Briefcase" class="w-5 h-5 text-primary" />
                    <h2 class="text-lg font-semibold text-slate-800">Employment Details</h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Employed From <span class="text-danger">*</span></label>
                        <Litepicker v-model="form.employed_from" :options="lpOptions" />
                        <p v-if="form.errors.employed_from" class="text-danger text-xs mt-1">{{ form.errors.employed_from }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Employed To</label>
                        <Litepicker v-model="form.employed_to" :options="lpOptions" />
                        <p v-if="form.errors.employed_to" class="text-danger text-xs mt-1">{{ form.errors.employed_to }}</p>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-slate-700 mb-1">Position(s) Held <span class="text-danger">*</span></label>
                        <FormInput v-model="form.positions_held" type="text" placeholder="Truck Driver, Dispatcher, etc." />
                        <p v-if="form.errors.positions_held" class="text-danger text-xs mt-1">{{ form.errors.positions_held }}</p>
                    </div>

                    <div class="md:col-span-2 rounded-xl border border-slate-200 p-4 space-y-3">
                        <label class="flex items-start gap-3 text-sm text-slate-700">
                            <FormCheck.Input v-model="form.subject_to_fmcsr" type="checkbox" class="mt-0.5" />
                            Was the driver subject to FMCSR while employed by this company?
                        </label>

                        <label class="flex items-start gap-3 text-sm text-slate-700">
                            <FormCheck.Input v-model="form.safety_sensitive_function" type="checkbox" class="mt-0.5" />
                            Was the job a safety-sensitive function subject to DOT drug/alcohol testing requirements?
                        </label>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Reason for Leaving <span class="text-danger">*</span></label>
                        <TomSelect v-model="form.reason_for_leaving">
                            <option value="">Select reason</option>
                            <option value="resignation">Resignation</option>
                            <option value="termination">Termination</option>
                            <option value="layoff">Layoff</option>
                            <option value="retirement">Retirement</option>
                            <option value="other">Other</option>
                        </TomSelect>
                        <p v-if="form.errors.reason_for_leaving" class="text-danger text-xs mt-1">{{ form.errors.reason_for_leaving }}</p>
                    </div>

                    <div v-if="form.reason_for_leaving === 'other'">
                        <label class="block text-sm font-medium text-slate-700 mb-1">Other Reason</label>
                        <FormInput v-model="form.other_reason_description" type="text" placeholder="Describe reason" />
                        <p v-if="form.errors.other_reason_description" class="text-danger text-xs mt-1">{{ form.errors.other_reason_description }}</p>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-slate-700 mb-1">Additional Notes</label>
                        <textarea
                            v-model="form.explanation"
                            rows="4"
                            class="w-full text-sm border-slate-200 shadow-sm rounded-lg py-2.5 px-3 border focus:ring-primary focus:border-primary"
                            placeholder="Any additional details about this employment..."
                        />
                        <p v-if="form.errors.explanation" class="text-danger text-xs mt-1">{{ form.errors.explanation }}</p>
                    </div>
                </div>
            </div>

            <div class="box box--stacked p-6">
                <div class="space-y-3">
                    <label class="flex items-center gap-3 text-sm text-slate-700">
                        <FormCheck.Input v-model="form.send_email" type="checkbox" />
                        Send initial verification email after creating the record
                    </label>

                    <p v-if="form.send_email" class="text-xs text-slate-500">
                        The email will only be sent if the selected company has a valid email address.
                    </p>
                </div>

                <p v-if="form.errors.general" class="text-danger text-sm mt-4">{{ form.errors.general }}</p>
            </div>

            <div class="flex justify-end gap-3">
                <Link :href="route('admin.drivers.employment-verification.index')">
                    <Button type="button" variant="outline-secondary">Cancel</Button>
                </Link>
                <Button type="submit" variant="primary" :disabled="form.processing">
                    {{ form.processing ? 'Saving...' : 'Create Verification' }}
                </Button>
            </div>
        </form>
    </div>
</template>
