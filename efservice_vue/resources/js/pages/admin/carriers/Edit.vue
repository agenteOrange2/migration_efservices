<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import { FormInput, FormLabel, FormSelect } from '@/components/Base/Form'
import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import RazeLayout from '@/layouts/RazeLayout.vue'

interface Props {
    carrier: Record<string, any>
    memberships: { id: number; name: string; price: number }[]
    usStates: Record<string, string>
    statusOptions: { value: number; label: string }[]
    bankingDetails: Record<string, any> | null
    referralUrl: string
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
    id_plan: props.carrier.id_plan ?? props.carrier.membership_id ?? '',
    status: props.carrier.status ?? 2,
    referrer_token: props.carrier.referrer_token ?? '',
    logo_carrier: null as File | null,
})

const bankingForm = useForm({
    account_holder_name: props.bankingDetails?.account_holder_name ?? '',
    account_number: props.bankingDetails?.account_number ?? '',
    banking_routing_number: props.bankingDetails?.banking_routing_number ?? '',
    zip_code: props.bankingDetails?.zip_code ?? '',
    security_code: props.bankingDetails?.security_code ?? '',
    country_code: props.bankingDetails?.country_code ?? 'US',
    status: props.bankingDetails?.status ?? 'pending',
    rejection_reason: props.bankingDetails?.rejection_reason ?? '',
})

const activeTab = ref('carrier')
const referralCopied = ref(false)

function handleFileChange(e: Event) {
    const target = e.target as HTMLInputElement
    if (target.files?.[0]) {
        form.logo_carrier = target.files[0]
    }
}

function submitCarrier() {
    form.transform((data) => ({
        ...data,
        _method: 'PUT',
    })).post(route('admin.carriers.update', props.carrier.slug), {
        forceFormData: true,
        preserveScroll: true,
    })
}

function submitBanking() {
    const routeName = props.bankingDetails
        ? 'admin.carriers.banking.update'
        : 'admin.carriers.banking.store'

    const method = props.bankingDetails ? 'put' : 'post'

    if (method === 'put') {
        bankingForm.put(route(routeName, props.carrier.slug), { preserveScroll: true })
    } else {
        bankingForm.post(route(routeName, props.carrier.slug), { preserveScroll: true })
    }
}

function copyReferralUrl() {
    navigator.clipboard.writeText(props.referralUrl)
    referralCopied.value = true
    setTimeout(() => (referralCopied.value = false), 2000)
}
</script>

<template>
    <Head :title="`Edit - ${carrier.name}`" />

    <RazeLayout>
        <div class="grid grid-cols-12 gap-y-10 gap-x-6">
            <!-- Header -->
            <div class="col-span-12">
                <div class="flex flex-col md:h-10 gap-y-3 md:items-center md:flex-row">
                    <div class="text-base font-medium">
                        <Link :href="route('admin.carriers.index')" class="text-primary hover:underline">Carriers</Link>
                        <span class="mx-2 text-slate-400">/</span>
                        <Link :href="route('admin.carriers.show', carrier.slug)" class="text-primary hover:underline">{{ carrier.name }}</Link>
                        <span class="mx-2 text-slate-400">/</span>
                        Edit
                    </div>
                </div>
            </div>

            <!-- Tabs -->
            <div class="col-span-12">
                <div class="box box--stacked">
                    <div class="border-b border-slate-200/60">
                        <nav class="flex">
                            <button
                                @click="activeTab = 'carrier'"
                                :class="[
                                    'flex items-center px-5 py-3.5 text-sm font-medium border-b-2 transition',
                                    activeTab === 'carrier' ? 'border-primary text-primary' : 'border-transparent text-slate-500 hover:text-slate-700',
                                ]"
                            >
                                <Lucide icon="Truck" class="w-4 h-4 mr-2" /> Carrier Info
                            </button>
                            <button
                                @click="activeTab = 'banking'"
                                :class="[
                                    'flex items-center px-5 py-3.5 text-sm font-medium border-b-2 transition',
                                    activeTab === 'banking' ? 'border-primary text-primary' : 'border-transparent text-slate-500 hover:text-slate-700',
                                ]"
                            >
                                <Lucide icon="CreditCard" class="w-4 h-4 mr-2" /> Banking
                            </button>
                        </nav>
                    </div>

                    <!-- Carrier Tab -->
                    <div v-show="activeTab === 'carrier'">
                        <form @submit.prevent="submitCarrier">
                            <div class="p-7">
                                <!-- Logo -->
                                <div class="mt-5 block flex-col pt-5 first:mt-0 first:pt-0 sm:flex xl:flex-row xl:items-center">
                                    <div class="mb-2 inline-block sm:mb-0 sm:mr-5 sm:text-right xl:mr-14 xl:w-60">
                                        <div class="text-left">
                                            <div class="font-medium">Profile Photo</div>
                                            <div class="mt-1.5 text-xs leading-relaxed text-slate-500/80 xl:mt-3">Upload or change carrier logo</div>
                                        </div>
                                    </div>
                                    <div class="mt-3 w-full flex-1 xl:mt-0">
                                        <input type="file" @change="handleFileChange" accept="image/*" class="text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-primary/10 file:text-primary hover:file:bg-primary/20" />
                                        <p v-if="form.errors.logo_carrier" class="mt-1 text-xs text-red-500">{{ form.errors.logo_carrier }}</p>
                                    </div>
                                </div>

                                <!-- Name -->
                                <div class="mt-5 block flex-col pt-5 sm:flex xl:flex-row xl:items-center">
                                    <div class="mb-2 inline-block sm:mb-0 sm:mr-5 sm:text-right xl:mr-14 xl:w-60">
                                        <div class="text-left">
                                            <div class="flex items-center">
                                                <div class="font-medium">Carrier Name</div>
                                                <div class="ml-2.5 rounded-md border border-slate-200 bg-slate-100 px-2 py-0.5 text-xs text-slate-500">Required</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-3 w-full flex-1 xl:mt-0">
                                        <FormInput v-model="form.name" type="text" placeholder="Carrier name" />
                                        <p v-if="form.errors.name" class="mt-1 text-xs text-red-500">{{ form.errors.name }}</p>
                                    </div>
                                </div>

                                <!-- Address -->
                                <div class="mt-5 block flex-col pt-5 sm:flex xl:flex-row xl:items-center">
                                    <div class="mb-2 inline-block sm:mb-0 sm:mr-5 sm:text-right xl:mr-14 xl:w-60">
                                        <div class="text-left">
                                            <div class="flex items-center">
                                                <div class="font-medium">Address</div>
                                                <div class="ml-2.5 rounded-md border border-slate-200 bg-slate-100 px-2 py-0.5 text-xs text-slate-500">Required</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-3 w-full flex-1 xl:mt-0">
                                        <FormInput v-model="form.address" type="text" placeholder="Address" />
                                        <p v-if="form.errors.address" class="mt-1 text-xs text-red-500">{{ form.errors.address }}</p>
                                    </div>
                                </div>

                                <!-- State + Zip -->
                                <div class="mt-5 block flex-col pt-5 sm:flex xl:flex-row xl:items-center">
                                    <div class="mb-2 inline-block sm:mb-0 sm:mr-5 sm:text-right xl:mr-14 xl:w-60">
                                        <div class="text-left">
                                            <div class="flex items-center">
                                                <div class="font-medium">State & ZIP</div>
                                                <div class="ml-2.5 rounded-md border border-slate-200 bg-slate-100 px-2 py-0.5 text-xs text-slate-500">Required</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-3 w-full flex-1 xl:mt-0 flex gap-4">
                                        <div class="flex-1">
                                            <FormSelect v-model="form.state">
                                                <option value="">Select a State</option>
                                                <option v-for="(name, abbr) in usStates" :key="abbr" :value="abbr">{{ name }}</option>
                                            </FormSelect>
                                            <p v-if="form.errors.state" class="mt-1 text-xs text-red-500">{{ form.errors.state }}</p>
                                        </div>
                                        <div class="w-40">
                                            <FormInput v-model="form.zipcode" type="text" placeholder="ZIP Code" />
                                            <p v-if="form.errors.zipcode" class="mt-1 text-xs text-red-500">{{ form.errors.zipcode }}</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- EIN Number -->
                                <div class="mt-5 block flex-col pt-5 sm:flex xl:flex-row xl:items-center">
                                    <div class="mb-2 inline-block sm:mb-0 sm:mr-5 sm:text-right xl:mr-14 xl:w-60">
                                        <div class="text-left">
                                            <div class="flex items-center">
                                                <div class="font-medium">EIN Number</div>
                                                <div class="ml-2.5 rounded-md border border-slate-200 bg-slate-100 px-2 py-0.5 text-xs text-slate-500">Required</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-3 w-full flex-1 xl:mt-0">
                                        <FormInput v-model="form.ein_number" type="text" placeholder="XX-XXXXXXX" />
                                        <p v-if="form.errors.ein_number" class="mt-1 text-xs text-red-500">{{ form.errors.ein_number }}</p>
                                    </div>
                                </div>

                                <!-- DOT Number -->
                                <div class="mt-5 block flex-col pt-5 sm:flex xl:flex-row xl:items-center">
                                    <div class="mb-2 inline-block sm:mb-0 sm:mr-5 sm:text-right xl:mr-14 xl:w-60">
                                        <div class="text-left">
                                            <div class="font-medium">DOT Number</div>
                                        </div>
                                    </div>
                                    <div class="mt-3 w-full flex-1 xl:mt-0">
                                        <FormInput v-model="form.dot_number" type="text" placeholder="DOT Number" />
                                        <p v-if="form.errors.dot_number" class="mt-1 text-xs text-red-500">{{ form.errors.dot_number }}</p>
                                    </div>
                                </div>

                                <!-- MC Number -->
                                <div class="mt-5 block flex-col pt-5 sm:flex xl:flex-row xl:items-center">
                                    <div class="mb-2 inline-block sm:mb-0 sm:mr-5 sm:text-right xl:mr-14 xl:w-60">
                                        <div class="text-left">
                                            <div class="font-medium">MC Number</div>
                                        </div>
                                    </div>
                                    <div class="mt-3 w-full flex-1 xl:mt-0">
                                        <FormInput v-model="form.mc_number" type="text" placeholder="MC Number" />
                                        <p v-if="form.errors.mc_number" class="mt-1 text-xs text-red-500">{{ form.errors.mc_number }}</p>
                                    </div>
                                </div>

                                <!-- State DOT -->
                                <div class="mt-5 block flex-col pt-5 sm:flex xl:flex-row xl:items-center">
                                    <div class="mb-2 inline-block sm:mb-0 sm:mr-5 sm:text-right xl:mr-14 xl:w-60">
                                        <div class="text-left">
                                            <div class="font-medium">State DOT</div>
                                        </div>
                                    </div>
                                    <div class="mt-3 w-full flex-1 xl:mt-0">
                                        <FormInput v-model="form.state_dot" type="text" placeholder="State DOT" />
                                    </div>
                                </div>

                                <!-- IFTA -->
                                <div class="mt-5 block flex-col pt-5 sm:flex xl:flex-row xl:items-center">
                                    <div class="mb-2 inline-block sm:mb-0 sm:mr-5 sm:text-right xl:mr-14 xl:w-60">
                                        <div class="text-left">
                                            <div class="font-medium">IFTA Account</div>
                                        </div>
                                    </div>
                                    <div class="mt-3 w-full flex-1 xl:mt-0">
                                        <FormInput v-model="form.ifta_account" type="text" placeholder="IFTA Account" />
                                    </div>
                                </div>

                                <!-- Membership Plan -->
                                <div class="mt-5 block flex-col pt-5 sm:flex xl:flex-row xl:items-center">
                                    <div class="mb-2 inline-block sm:mb-0 sm:mr-5 sm:text-right xl:mr-14 xl:w-60">
                                        <div class="text-left">
                                            <div class="flex items-center">
                                                <div class="font-medium">Membership Plan</div>
                                                <div class="ml-2.5 rounded-md border border-slate-200 bg-slate-100 px-2 py-0.5 text-xs text-slate-500">Required</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-3 w-full flex-1 xl:mt-0">
                                        <FormSelect v-model="form.id_plan">
                                            <option value="">Select Plan</option>
                                            <option v-for="m in memberships" :key="m.id" :value="m.id">{{ m.name }} (${{ m.price }})</option>
                                        </FormSelect>
                                        <p v-if="form.errors.id_plan" class="mt-1 text-xs text-red-500">{{ form.errors.id_plan }}</p>
                                    </div>
                                </div>

                                <!-- Status -->
                                <div class="mt-5 block flex-col pt-5 sm:flex xl:flex-row xl:items-center">
                                    <div class="mb-2 inline-block sm:mb-0 sm:mr-5 sm:text-right xl:mr-14 xl:w-60">
                                        <div class="text-left">
                                            <div class="font-medium">Status</div>
                                        </div>
                                    </div>
                                    <div class="mt-3 w-full flex-1 xl:mt-0">
                                        <FormSelect v-model="form.status">
                                            <option v-for="opt in statusOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                                        </FormSelect>
                                    </div>
                                </div>

                                <!-- Referral Token -->
                                <div class="mt-5 block flex-col pt-5 sm:flex xl:flex-row xl:items-center">
                                    <div class="mb-2 inline-block sm:mb-0 sm:mr-5 sm:text-right xl:mr-14 xl:w-60">
                                        <div class="text-left">
                                            <div class="font-medium">Referrer Token</div>
                                            <div class="mt-1.5 text-xs leading-relaxed text-slate-500/80 xl:mt-3">Driver registration referral</div>
                                        </div>
                                    </div>
                                    <div class="mt-3 w-full flex-1 xl:mt-0">
                                        <FormInput v-model="form.referrer_token" type="text" placeholder="Auto-generated token" />
                                        <div v-if="referralUrl" class="mt-2 flex items-center gap-2">
                                            <input type="text" :value="referralUrl" readonly class="flex-1 px-3 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-md text-slate-600 font-mono" />
                                            <button type="button" @click="copyReferralUrl" class="px-3 py-1.5 text-xs bg-primary/10 text-primary rounded-md hover:bg-primary/20 transition">
                                                {{ referralCopied ? 'Copied!' : 'Copy' }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-col sm:flex-row gap-3 border-t border-slate-200/80 px-7 py-5 md:justify-end">
                                <Link :href="route('admin.carriers.show', carrier.slug)">
                                    <Button variant="outline-secondary" class="w-full sm:w-auto px-10">
                                        <Lucide icon="X" class="w-4 h-4 mr-2" /> Cancel
                                    </Button>
                                </Link>
                                <Button type="submit" variant="primary" class="w-full sm:w-auto px-10" :disabled="form.processing">
                                    <Lucide icon="Save" class="w-4 h-4 mr-2" />
                                    {{ form.processing ? 'Saving...' : 'Update Carrier' }}
                                </Button>
                            </div>
                        </form>
                    </div>

                    <!-- Banking Tab -->
                    <div v-show="activeTab === 'banking'">
                        <form @submit.prevent="submitBanking">
                            <div class="p-7">
                                <h3 class="font-medium text-slate-800 mb-6">
                                    {{ bankingDetails ? 'Edit Banking Information' : 'Add Banking Information' }}
                                </h3>

                                <!-- Account Holder -->
                                <div class="mt-5 block flex-col pt-5 first:mt-0 first:pt-0 sm:flex xl:flex-row xl:items-center">
                                    <div class="mb-2 inline-block sm:mb-0 sm:mr-5 sm:text-right xl:mr-14 xl:w-60">
                                        <div class="text-left">
                                            <div class="font-medium">Account Holder Name</div>
                                        </div>
                                    </div>
                                    <div class="mt-3 w-full flex-1 xl:mt-0">
                                        <FormInput v-model="bankingForm.account_holder_name" type="text" placeholder="Account holder name" />
                                        <p v-if="bankingForm.errors.account_holder_name" class="mt-1 text-xs text-red-500">{{ bankingForm.errors.account_holder_name }}</p>
                                    </div>
                                </div>

                                <!-- Account Number -->
                                <div class="mt-5 block flex-col pt-5 sm:flex xl:flex-row xl:items-center">
                                    <div class="mb-2 inline-block sm:mb-0 sm:mr-5 sm:text-right xl:mr-14 xl:w-60">
                                        <div class="text-left">
                                            <div class="font-medium">Account Number</div>
                                        </div>
                                    </div>
                                    <div class="mt-3 w-full flex-1 xl:mt-0">
                                        <FormInput v-model="bankingForm.account_number" type="text" placeholder="Account number" />
                                        <p v-if="bankingForm.errors.account_number" class="mt-1 text-xs text-red-500">{{ bankingForm.errors.account_number }}</p>
                                    </div>
                                </div>

                                <!-- Routing Number -->
                                <div class="mt-5 block flex-col pt-5 sm:flex xl:flex-row xl:items-center">
                                    <div class="mb-2 inline-block sm:mb-0 sm:mr-5 sm:text-right xl:mr-14 xl:w-60">
                                        <div class="text-left">
                                            <div class="font-medium">Routing Number</div>
                                            <div class="mt-1.5 text-xs text-slate-500/80">9 digits</div>
                                        </div>
                                    </div>
                                    <div class="mt-3 w-full flex-1 xl:mt-0">
                                        <FormInput v-model="bankingForm.banking_routing_number" type="text" placeholder="123456789" maxlength="9" />
                                        <p v-if="bankingForm.errors.banking_routing_number" class="mt-1 text-xs text-red-500">{{ bankingForm.errors.banking_routing_number }}</p>
                                    </div>
                                </div>

                                <!-- ZIP + Security Code -->
                                <div class="mt-5 block flex-col pt-5 sm:flex xl:flex-row xl:items-center">
                                    <div class="mb-2 inline-block sm:mb-0 sm:mr-5 sm:text-right xl:mr-14 xl:w-60">
                                        <div class="text-left">
                                            <div class="font-medium">ZIP & Security Code</div>
                                        </div>
                                    </div>
                                    <div class="mt-3 w-full flex-1 xl:mt-0 flex gap-4">
                                        <div class="flex-1">
                                            <FormInput v-model="bankingForm.zip_code" type="text" placeholder="ZIP Code" />
                                            <p v-if="bankingForm.errors.zip_code" class="mt-1 text-xs text-red-500">{{ bankingForm.errors.zip_code }}</p>
                                        </div>
                                        <div class="w-32">
                                            <FormInput v-model="bankingForm.security_code" type="text" placeholder="CVV" maxlength="4" />
                                            <p v-if="bankingForm.errors.security_code" class="mt-1 text-xs text-red-500">{{ bankingForm.errors.security_code }}</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Country + Status -->
                                <div class="mt-5 block flex-col pt-5 sm:flex xl:flex-row xl:items-center">
                                    <div class="mb-2 inline-block sm:mb-0 sm:mr-5 sm:text-right xl:mr-14 xl:w-60">
                                        <div class="text-left">
                                            <div class="font-medium">Country & Status</div>
                                        </div>
                                    </div>
                                    <div class="mt-3 w-full flex-1 xl:mt-0 flex gap-4">
                                        <div class="w-32">
                                            <FormSelect v-model="bankingForm.country_code">
                                                <option value="US">US</option>
                                                <option value="CA">CA</option>
                                                <option value="MX">MX</option>
                                            </FormSelect>
                                        </div>
                                        <div class="flex-1">
                                            <FormSelect v-model="bankingForm.status">
                                                <option value="pending">Pending</option>
                                                <option value="approved">Approved</option>
                                                <option value="rejected">Rejected</option>
                                            </FormSelect>
                                        </div>
                                    </div>
                                </div>

                                <!-- Rejection Reason -->
                                <div v-if="bankingForm.status === 'rejected'" class="mt-5 block flex-col pt-5 sm:flex xl:flex-row xl:items-center">
                                    <div class="mb-2 inline-block sm:mb-0 sm:mr-5 sm:text-right xl:mr-14 xl:w-60">
                                        <div class="text-left">
                                            <div class="font-medium">Rejection Reason</div>
                                        </div>
                                    </div>
                                    <div class="mt-3 w-full flex-1 xl:mt-0">
                                        <textarea v-model="bankingForm.rejection_reason" rows="3" placeholder="Reason for rejection" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-md focus:ring-2 focus:ring-primary/20 focus:border-primary" />
                                        <p v-if="bankingForm.errors.rejection_reason" class="mt-1 text-xs text-red-500">{{ bankingForm.errors.rejection_reason }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-col sm:flex-row gap-3 border-t border-slate-200/80 px-7 py-5 md:justify-end">
                                <Link :href="route('admin.carriers.show', carrier.slug)">
                                    <Button variant="outline-secondary" class="w-full sm:w-auto px-10">
                                        <Lucide icon="X" class="w-4 h-4 mr-2" /> Cancel
                                    </Button>
                                </Link>
                                <Button type="submit" variant="primary" class="w-full sm:w-auto px-10" :disabled="bankingForm.processing">
                                    <Lucide icon="Save" class="w-4 h-4 mr-2" />
                                    {{ bankingForm.processing ? 'Saving...' : (bankingDetails ? 'Update Banking' : 'Save Banking') }}
                                </Button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </RazeLayout>
</template>
