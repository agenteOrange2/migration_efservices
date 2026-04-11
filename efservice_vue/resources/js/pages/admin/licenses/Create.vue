<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'
import Lucide from '@/components/Base/Lucide'
import Button from '@/components/Base/Button'
import Litepicker from '@/components/Base/Litepicker/Litepicker.vue'
import TomSelect from '@/components/Base/TomSelect/TomSelect.vue'
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

interface EndorsementOption {
    id: number
    code: string
    name: string
}

interface LicenseRouteNames {
    index: string
    store: string
}

const props = withDefaults(defineProps<{
    carriers: CarrierOption[]
    drivers: DriverOption[]
    states: Record<string, string>
    endorsements: EndorsementOption[]
    carrier?: CarrierOption | null
    routeNames?: LicenseRouteNames
    isCarrierContext?: boolean
}>(), {
    carrier: null,
    routeNames: () => ({
        index: 'admin.licenses.index',
        store: 'admin.licenses.store',
    }),
    isCarrierContext: false,
})

const selectedCarrierId = ref(props.isCarrierContext ? String(props.carrier?.id ?? '') : '')

const form = useForm({
    user_driver_detail_id: '',
    license_number: '',
    license_class: 'A',
    state_of_issue: '',
    expiration_date: '',
    is_cdl: false,
    is_primary: true,
    endorsement_ids: [] as number[],
    license_front_image: null as File | null,
    license_back_image: null as File | null,
    license_documents: [] as File[],
})

const filteredDrivers = computed(() => {
    if (!selectedCarrierId.value) {
        return []
    }

    return props.drivers.filter(driver => String(driver.carrier_id ?? '') === selectedCarrierId.value)
})

watch(selectedCarrierId, () => {
    form.user_driver_detail_id = ''
})

function onFileChange(field: 'license_front_image' | 'license_back_image', event: Event) {
    const input = event.target as HTMLInputElement
    form[field] = input.files?.[0] ?? null
}

function onDocumentFilesChange(event: Event) {
    const input = event.target as HTMLInputElement
    form.license_documents = Array.from(input.files ?? [])
}

function toggleEndorsement(id: number) {
    if (form.endorsement_ids.includes(id)) {
        form.endorsement_ids = form.endorsement_ids.filter(item => item !== id)
        return
    }

    form.endorsement_ids = [...form.endorsement_ids, id]
}

function submit() {
    form.post(route(props.routeNames.store), {
        forceFormData: true,
    })
}
</script>

<template>
    <Head :title="isCarrierContext ? 'Add Carrier License' : 'Add License'" />

    <div class="grid grid-cols-12 gap-y-8 gap-x-6">
        <div class="col-span-12">
            <div class="box box--stacked p-6">
                <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                            <Lucide icon="CreditCard" class="w-8 h-8 text-primary" />
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-slate-800">{{ isCarrierContext ? 'Add Driver License' : 'Add New License' }}</h1>
                            <p class="text-slate-500">
                                {{ isCarrierContext ? 'Create a license record for one of your drivers.' : 'Create a driver license record in the new Vue admin.' }}
                            </p>
                        </div>
                    </div>

                    <Link :href="route(routeNames.index)">
                        <Button variant="outline-secondary" class="flex items-center gap-2">
                            <Lucide icon="ArrowLeft" class="w-4 h-4" />
                            Back to Licenses
                        </Button>
                    </Link>
                </div>
            </div>
        </div>

        <div class="col-span-12 xl:col-span-8">
            <form @submit.prevent="submit" class="space-y-6">
                <div class="box box--stacked p-6">
                    <h2 class="text-sm font-semibold text-slate-700 mb-5 flex items-center gap-2">
                        <Lucide icon="Users" class="w-4 h-4 text-primary" />
                        Driver Selection
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div v-if="!isCarrierContext">
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Carrier <span class="text-red-500">*</span></label>
                            <TomSelect v-model="selectedCarrierId">
                                <option value="">Select carrier</option>
                                <option v-for="carrier in carriers" :key="carrier.id" :value="String(carrier.id)">
                                    {{ carrier.name }}
                                </option>
                            </TomSelect>
                        </div>

                        <div v-else class="rounded-lg border border-primary/20 bg-primary/5 px-4 py-3">
                            <p class="text-xs font-medium uppercase tracking-[0.2em] text-primary/70">Carrier</p>
                            <p class="mt-1 font-semibold text-slate-800">{{ carrier?.name ?? 'Current carrier' }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Driver <span class="text-red-500">*</span></label>
                            <TomSelect
                                v-model="form.user_driver_detail_id"
                            >
                                <option value="">Select driver</option>
                                <option v-for="driver in filteredDrivers" :key="driver.id" :value="String(driver.id)">
                                    {{ driver.name }}
                                </option>
                            </TomSelect>
                            <p v-if="form.errors.user_driver_detail_id" class="text-red-500 text-xs mt-1">{{ form.errors.user_driver_detail_id }}</p>
                        </div>
                    </div>
                </div>

                <div class="box box--stacked p-6">
                    <h2 class="text-sm font-semibold text-slate-700 mb-5 flex items-center gap-2">
                        <Lucide icon="CreditCard" class="w-4 h-4 text-primary" />
                        License Information
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">License Number <span class="text-red-500">*</span></label>
                            <input v-model="form.license_number" type="text" class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2" :class="form.errors.license_number ? 'border-red-400' : ''" />
                            <p v-if="form.errors.license_number" class="text-red-500 text-xs mt-1">{{ form.errors.license_number }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">License Class <span class="text-red-500">*</span></label>
                            <TomSelect v-model="form.license_class">
                                <option value="A">Class A</option>
                                <option value="B">Class B</option>
                                <option value="C">Class C</option>
                            </TomSelect>
                            <p v-if="form.errors.license_class" class="text-red-500 text-xs mt-1">{{ form.errors.license_class }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">State of Issue <span class="text-red-500">*</span></label>
                            <TomSelect v-model="form.state_of_issue">
                                <option value="">Select state</option>
                                <option v-for="(label, code) in states" :key="code" :value="code">{{ label }}</option>
                            </TomSelect>
                            <p v-if="form.errors.state_of_issue" class="text-red-500 text-xs mt-1">{{ form.errors.state_of_issue }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Expiration Date <span class="text-red-500">*</span></label>
                            <Litepicker v-model="form.expiration_date" :options="lpOptions" />
                            <p v-if="form.errors.expiration_date" class="text-red-500 text-xs mt-1">{{ form.errors.expiration_date }}</p>
                        </div>
                    </div>
                </div>

                <div class="box box--stacked p-6">
                    <h2 class="text-sm font-semibold text-slate-700 mb-5 flex items-center gap-2">
                        <Lucide icon="BadgeCheck" class="w-4 h-4 text-primary" />
                        CDL Setup
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <label class="flex items-center gap-3 rounded-lg border border-slate-200 px-4 py-3 cursor-pointer">
                            <input v-model="form.is_cdl" type="checkbox" class="w-4 h-4 rounded text-primary" />
                            <span class="text-sm font-medium text-slate-700">This is a CDL license</span>
                        </label>

                        <label class="flex items-center gap-3 rounded-lg border border-slate-200 px-4 py-3 cursor-pointer">
                            <input v-model="form.is_primary" type="checkbox" class="w-4 h-4 rounded text-primary" />
                            <span class="text-sm font-medium text-slate-700">Set as primary license</span>
                        </label>
                    </div>

                    <div v-if="form.is_cdl">
                        <p class="text-xs font-medium text-slate-600 mb-3">Endorsements</p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3">
                            <label
                                v-for="endorsement in endorsements"
                                :key="endorsement.id"
                                class="flex items-center gap-3 rounded-lg border px-4 py-3 cursor-pointer transition"
                                :class="form.endorsement_ids.includes(endorsement.id) ? 'border-primary bg-primary/5' : 'border-slate-200'"
                            >
                                <input
                                    :checked="form.endorsement_ids.includes(endorsement.id)"
                                    type="checkbox"
                                    class="w-4 h-4 rounded text-primary"
                                    @change="toggleEndorsement(endorsement.id)"
                                />
                                <span class="text-sm text-slate-700">{{ endorsement.code }} - {{ endorsement.name }}</span>
                            </label>
                        </div>
                        <p v-if="form.errors.endorsement_ids" class="text-red-500 text-xs mt-2">{{ form.errors.endorsement_ids }}</p>
                    </div>
                </div>

                <div class="box box--stacked p-6">
                    <h2 class="text-sm font-semibold text-slate-700 mb-5 flex items-center gap-2">
                        <Lucide icon="Upload" class="w-4 h-4 text-primary" />
                        License Images
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Front Image</label>
                            <input type="file" accept=".jpg,.jpeg,.png,.pdf" class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2" @change="onFileChange('license_front_image', $event)" />
                            <p v-if="form.errors.license_front_image" class="text-red-500 text-xs mt-1">{{ form.errors.license_front_image }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Back Image</label>
                            <input type="file" accept=".jpg,.jpeg,.png,.pdf" class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2" @change="onFileChange('license_back_image', $event)" />
                            <p v-if="form.errors.license_back_image" class="text-red-500 text-xs mt-1">{{ form.errors.license_back_image }}</p>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Additional Documents</label>
                            <input type="file" multiple accept=".jpg,.jpeg,.png,.pdf,.doc,.docx" class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2" @change="onDocumentFilesChange" />
                            <p class="text-xs text-slate-500 mt-1">Upload extra files related to this license if needed.</p>
                            <p v-if="form.errors.license_documents" class="text-red-500 text-xs mt-1">{{ form.errors.license_documents }}</p>
                            <p v-if="form.errors['license_documents.0']" class="text-red-500 text-xs mt-1">{{ form.errors['license_documents.0'] }}</p>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-3">
                    <Link :href="route(routeNames.index)">
                        <Button variant="outline-secondary" type="button">Cancel</Button>
                    </Link>
                    <Button type="submit" variant="primary" :disabled="form.processing">
                        {{ form.processing ? 'Saving...' : 'Create License' }}
                    </Button>
                </div>
            </form>
        </div>

        <div class="col-span-12 xl:col-span-4">
            <div class="box box--stacked p-5 sticky top-4">
                <h2 class="text-sm font-semibold text-slate-700 mb-4 flex items-center gap-2">
                    <Lucide icon="Info" class="w-4 h-4 text-primary" />
                    Quick Notes
                </h2>

                <div class="space-y-3 text-sm text-slate-600">
                    <div class="rounded-lg bg-slate-50 p-3">
                        {{ isCarrierContext ? 'Your driver list is already scoped to your carrier account.' : 'Pick a carrier first so the driver list stays scoped correctly.' }}
                    </div>
                    <div class="rounded-lg bg-slate-50 p-3">
                        If no primary license exists for the driver, this record will become primary automatically.
                    </div>
                    <div class="rounded-lg bg-slate-50 p-3">
                        Front and back files accept `JPG`, `PNG` or `PDF` up to 10MB.
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
