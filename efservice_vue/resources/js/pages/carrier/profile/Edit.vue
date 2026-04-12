<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import { FormInput } from '@/components/Base/Form'
import TomSelect from '@/components/Base/TomSelect/TomSelect.vue'
import RazeLayout from '@/layouts/RazeLayout.vue'

defineOptions({ layout: RazeLayout })

declare function route(name: string, params?: any): string

interface Props {
    carrier: {
        id: number
        name: string
        address: string | null
        state: string | null
        zipcode: string | null
        ein_number: string | null
        dot_number: string | null
        mc_number: string | null
        state_dot: string | null
        ifta_account: string | null
        phone: string | null
        status: number
        status_name: string
        referrer_token: string | null
        logo_url: string | null
        created_at: string | null
        updated_at: string | null
        membership: {
            name: string
            price: number | null
        } | null
    }
    usStates: Record<string, string>
}

const props = defineProps<Props>()

const form = useForm({
    name: props.carrier.name ?? '',
    address: props.carrier.address ?? '',
    state: props.carrier.state ?? '',
    zipcode: props.carrier.zipcode ?? '',
    ein_number: props.carrier.ein_number ?? '',
    dot_number: props.carrier.dot_number ?? '',
    mc_number: props.carrier.mc_number ?? '',
    state_dot: props.carrier.state_dot ?? '',
    ifta_account: props.carrier.ifta_account ?? '',
    phone: props.carrier.phone ?? '',
    logo_carrier: null as File | null,
})

const logoPreview = ref<string | null>(props.carrier.logo_url ?? null)

const statusClass = computed(() => {
    if (props.carrier.status === 1) return 'bg-success/10 text-success'
    if (props.carrier.status === 0 || props.carrier.status === 4) return 'bg-danger/10 text-danger'
    return 'bg-warning/10 text-warning'
})

function handleLogoChange(event: Event) {
    const target = event.target as HTMLInputElement
    const file = target.files?.[0] ?? null
    form.logo_carrier = file

    if (!file) {
        logoPreview.value = props.carrier.logo_url ?? null
        return
    }

    const reader = new FileReader()
    reader.onload = e => {
        logoPreview.value = String(e.target?.result ?? '')
    }
    reader.readAsDataURL(file)
}

function submit() {
    form.transform(data => ({ ...data, _method: 'PUT' })).post(route('carrier.profile.update'), {
        forceFormData: true,
        preserveScroll: true,
    })
}
</script>

<template>
    <Head title="Edit Carrier Profile" />

    <div class="grid grid-cols-12 gap-y-8 gap-x-6">
        <div class="col-span-12">
            <div class="box box--stacked p-6">
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-slate-800">Edit Company Profile</h1>
                        <p class="mt-1 text-slate-500">Update your company information and logo without leaving the carrier portal.</p>
                    </div>

                    <Link :href="route('carrier.profile')">
                        <Button variant="outline-secondary" class="gap-2">
                            <Lucide icon="ArrowLeft" class="h-4 w-4" />
                            Back to Profile
                        </Button>
                    </Link>
                </div>
            </div>
        </div>

        <div class="col-span-12">
            <form class="grid grid-cols-12 gap-6" @submit.prevent="submit">
                <div class="col-span-12 space-y-6 xl:col-span-8">
                    <div class="box box--stacked p-6">
                        <div class="mb-6 flex items-center gap-3 border-b border-slate-200/60 pb-4">
                            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10">
                                <Lucide icon="Building2" class="h-5 w-5 text-primary" />
                            </div>
                            <div>
                                <h2 class="text-lg font-semibold text-slate-800">Company Information</h2>
                                <p class="text-sm text-slate-500">Basic details about your company.</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                            <div class="md:col-span-2">
                                <label class="mb-2 block text-sm font-medium text-slate-700">Company Name <span class="text-danger">*</span></label>
                                <FormInput v-model="form.name" type="text" placeholder="Enter company name" />
                                <p v-if="form.errors.name" class="mt-1 text-xs text-danger">{{ form.errors.name }}</p>
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-700">EIN Number <span class="text-danger">*</span></label>
                                <FormInput v-model="form.ein_number" type="text" placeholder="XX-XXXXXXX" />
                                <p v-if="form.errors.ein_number" class="mt-1 text-xs text-danger">{{ form.errors.ein_number }}</p>
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-700">Phone Number <span class="text-danger">*</span></label>
                                <FormInput v-model="form.phone" type="text" placeholder="(XXX) XXX-XXXX" />
                                <p v-if="form.errors.phone" class="mt-1 text-xs text-danger">{{ form.errors.phone }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="box box--stacked p-6">
                        <div class="mb-6 flex items-center gap-3 border-b border-slate-200/60 pb-4">
                            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-info/10">
                                <Lucide icon="MapPin" class="h-5 w-5 text-info" />
                            </div>
                            <div>
                                <h2 class="text-lg font-semibold text-slate-800">Address Information</h2>
                                <p class="text-sm text-slate-500">Keep your carrier address up to date.</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                            <div class="md:col-span-2">
                                <label class="mb-2 block text-sm font-medium text-slate-700">Street Address <span class="text-danger">*</span></label>
                                <FormInput v-model="form.address" type="text" placeholder="Enter street address" />
                                <p v-if="form.errors.address" class="mt-1 text-xs text-danger">{{ form.errors.address }}</p>
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-700">State <span class="text-danger">*</span></label>
                                <TomSelect v-model="form.state">
                                    <option value="">Select state</option>
                                    <option v-for="(name, code) in usStates" :key="code" :value="code">{{ name }}</option>
                                </TomSelect>
                                <p v-if="form.errors.state" class="mt-1 text-xs text-danger">{{ form.errors.state }}</p>
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-700">ZIP Code <span class="text-danger">*</span></label>
                                <FormInput v-model="form.zipcode" type="text" maxlength="10" placeholder="XXXXX" />
                                <p v-if="form.errors.zipcode" class="mt-1 text-xs text-danger">{{ form.errors.zipcode }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="box box--stacked p-6">
                        <div class="mb-6 flex items-center gap-3 border-b border-slate-200/60 pb-4">
                            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-success/10">
                                <Lucide icon="Shield" class="h-5 w-5 text-success" />
                            </div>
                            <div>
                                <h2 class="text-lg font-semibold text-slate-800">DOT & Authority Information</h2>
                                <p class="text-sm text-slate-500">Federal and state authority numbers.</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-700">USDOT Number <span class="text-danger">*</span></label>
                                <FormInput v-model="form.dot_number" type="text" placeholder="DOT Number" />
                                <p v-if="form.errors.dot_number" class="mt-1 text-xs text-danger">{{ form.errors.dot_number }}</p>
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-700">MC Number</label>
                                <FormInput v-model="form.mc_number" type="text" placeholder="MC Number" />
                                <p v-if="form.errors.mc_number" class="mt-1 text-xs text-danger">{{ form.errors.mc_number }}</p>
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-700">State DOT Number</label>
                                <FormInput v-model="form.state_dot" type="text" placeholder="State DOT Number" />
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-700">IFTA Account Number</label>
                                <FormInput v-model="form.ifta_account" type="text" placeholder="IFTA Account Number" />
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3">
                        <Link :href="route('carrier.profile')">
                            <Button variant="outline-secondary">Cancel</Button>
                        </Link>
                        <Button variant="primary" type="submit" :disabled="form.processing" class="gap-2">
                            <Lucide v-if="form.processing" icon="Loader" class="h-4 w-4 animate-spin" />
                            <Lucide v-else icon="Save" class="h-4 w-4" />
                            {{ form.processing ? 'Saving...' : 'Save Changes' }}
                        </Button>
                    </div>
                </div>

                <div class="col-span-12 space-y-6 xl:col-span-4">
                    <div class="box box--stacked p-6">
                        <div class="mb-6 flex items-center gap-3 border-b border-slate-200/60 pb-4">
                            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-warning/10">
                                <Lucide icon="Image" class="h-5 w-5 text-warning" />
                            </div>
                            <div>
                                <h2 class="text-lg font-semibold text-slate-800">Company Logo</h2>
                                <p class="text-sm text-slate-500">Upload a square logo for best results.</p>
                            </div>
                        </div>

                        <div class="flex flex-col items-center">
                            <div class="relative mb-5">
                                <div class="flex h-36 w-36 items-center justify-center overflow-hidden rounded-full border-4 border-slate-200 bg-slate-100 shadow-lg">
                                    <img v-if="logoPreview" :src="logoPreview" :alt="carrier.name" class="h-full w-full object-cover">
                                    <Lucide v-else icon="Building2" class="h-12 w-12 text-slate-400" />
                                </div>
                                <label for="logo_carrier" class="absolute bottom-1 right-1 flex h-10 w-10 cursor-pointer items-center justify-center rounded-full bg-primary text-white shadow-lg transition-colors hover:bg-primary/90">
                                    <Lucide icon="Camera" class="h-5 w-5" />
                                </label>
                            </div>

                            <input id="logo_carrier" type="file" accept="image/*" class="hidden" @change="handleLogoChange">
                            <p class="text-center text-xs text-slate-400">
                                Recommended: square image, at least 200x200px.<br>
                                Max file size: 2MB.
                            </p>
                            <p v-if="form.errors.logo_carrier" class="mt-2 text-xs text-danger">{{ form.errors.logo_carrier }}</p>
                        </div>
                    </div>

                    <div class="box box--stacked p-6">
                        <div class="mb-4 flex items-center gap-3 border-b border-slate-200/60 pb-4">
                            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-slate-100">
                                <Lucide icon="Info" class="h-5 w-5 text-slate-500" />
                            </div>
                            <h2 class="text-lg font-semibold text-slate-800">Current Info</h2>
                        </div>

                        <div class="space-y-3 text-sm">
                            <div class="flex items-center justify-between border-b border-slate-100 py-2">
                                <span class="text-slate-500">Status</span>
                                <span class="rounded-full px-2 py-1 text-xs font-medium" :class="statusClass">{{ carrier.status_name }}</span>
                            </div>
                            <div class="flex items-center justify-between border-b border-slate-100 py-2">
                                <span class="text-slate-500">Created</span>
                                <span class="font-medium text-slate-700">{{ carrier.created_at || 'N/A' }}</span>
                            </div>
                            <div class="flex items-center justify-between border-b border-slate-100 py-2">
                                <span class="text-slate-500">Last Updated</span>
                                <span class="font-medium text-slate-700">{{ carrier.updated_at || 'N/A' }}</span>
                            </div>
                            <div class="flex items-center justify-between border-b border-slate-100 py-2">
                                <span class="text-slate-500">Referral Token</span>
                                <code class="rounded bg-slate-100 px-2 py-1 text-xs">{{ carrier.referrer_token || 'N/A' }}</code>
                            </div>
                            <div class="flex items-center justify-between py-2">
                                <span class="text-slate-500">Plan</span>
                                <span class="font-medium text-slate-700">{{ carrier.membership?.name || 'N/A' }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="box box--stacked border border-primary/15 bg-gradient-to-br from-primary/5 to-transparent p-6">
                        <div class="flex items-start gap-3">
                            <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg bg-primary/10">
                                <Lucide icon="HelpCircle" class="h-5 w-5 text-primary" />
                            </div>
                            <div>
                                <h3 class="font-semibold text-slate-800">Need Help?</h3>
                                <p class="mt-1 text-sm text-slate-500">
                                    If you need support with DOT, MC, or billing changes, our team can help you safely update the account.
                                </p>
                                <a href="mailto:support@efct.com" class="mt-3 inline-flex items-center gap-1 text-sm font-medium text-primary hover:text-primary/80">
                                    <Lucide icon="Mail" class="h-4 w-4" />
                                    Contact Support
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</template>
