<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import { FormInput, FormSelect, FormLabel } from '@/components/Base/Form'

declare function route(name: string, params?: any): string

interface MembershipPayload {
    id: number
    name: string
    description: string
    pricing_type: string
    price: number | null
    carrier_price: number | null
    driver_price: number | null
    vehicle_price: number | null
    max_carrier: number
    max_drivers: number
    max_vehicles: number
    status: number
    show_in_register: number
    image_url?: string | null
}

const props = defineProps<{
    mode: 'create' | 'edit'
    membership?: MembershipPayload
}>()

const imagePreview = ref<string | null>(props.membership?.image_url ?? null)

const form = useForm({
    name: props.membership?.name ?? '',
    description: props.membership?.description ?? '',
    pricing_type: props.membership?.pricing_type ?? 'plan',
    price: props.membership?.price ?? '',
    carrier_price: props.membership?.carrier_price ?? '',
    driver_price: props.membership?.driver_price ?? '',
    vehicle_price: props.membership?.vehicle_price ?? '',
    max_carrier: props.membership?.max_carrier ?? 1,
    max_drivers: props.membership?.max_drivers ?? 1,
    max_vehicles: props.membership?.max_vehicles ?? 1,
    status: Boolean(props.membership?.status ?? true),
    show_in_register: Boolean(props.membership?.show_in_register ?? false),
    image_membership: null as File | null,
})

const pricingCards = computed(() => {
    if (form.pricing_type === 'plan') {
        return [
            {
                title: 'Bundle Price',
                description: 'Single amount for the whole plan.',
                value: String(form.price || ''),
            },
        ]
    }

    return [
        {
            title: 'Carrier Price',
            description: 'Price charged per carrier seat.',
            value: String(form.carrier_price || ''),
        },
        {
            title: 'Driver Price',
            description: 'Price charged per driver seat.',
            value: String(form.driver_price || ''),
        },
        {
            title: 'Vehicle Price',
            description: 'Price charged per vehicle seat.',
            value: String(form.vehicle_price || ''),
        },
    ]
})

function handleImage(event: Event) {
    const input = event.target as HTMLInputElement
    if (!input.files?.[0]) return

    form.image_membership = input.files[0]
    imagePreview.value = URL.createObjectURL(input.files[0])
}

function removeImage() {
    form.image_membership = null
    imagePreview.value = props.mode === 'edit' ? props.membership?.image_url ?? null : null
}

function submit() {
    if (props.mode === 'edit' && props.membership) {
        form.transform((data) => ({ ...data, _method: 'PUT' }))
            .post(route('admin.memberships.update', props.membership.id), {
                forceFormData: true,
                preserveScroll: true,
            })
        return
    }

    form.post(route('admin.memberships.store'), {
        forceFormData: true,
    })
}
</script>

<template>
    <div class="grid grid-cols-12 gap-x-6 gap-y-8">
        <div class="col-span-12">
            <div class="box box--stacked p-6">
                <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-primary/10 rounded-xl border border-primary/20">
                            <Lucide :icon="mode === 'edit' ? 'BadgeDollarSign' : 'CreditCard'" class="w-8 h-8 text-primary" />
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-slate-800">
                                {{ mode === 'edit' ? 'Edit Membership' : 'Create Membership' }}
                            </h1>
                            <p class="text-slate-500">
                                {{ mode === 'edit' ? `Update ${membership?.name ?? 'membership'} pricing and limits.` : 'Add a new membership plan to the admin catalog.' }}
                            </p>
                        </div>
                    </div>

                    <div class="flex gap-3">
                        <Link :href="route('admin.memberships.index')">
                            <Button variant="outline-secondary" class="flex items-center gap-2">
                                <Lucide icon="ArrowLeft" class="w-4 h-4" />
                                Back to Memberships
                            </Button>
                        </Link>
                        <Link v-if="mode === 'edit' && membership" :href="route('admin.memberships.show', membership.id)">
                            <Button variant="outline-secondary" class="flex items-center gap-2">
                                <Lucide icon="Eye" class="w-4 h-4" />
                                View Details
                            </Button>
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12 xl:col-span-8">
            <form @submit.prevent="submit" class="space-y-6">
                <div class="box box--stacked p-6">
                    <h2 class="text-sm font-semibold text-slate-700 mb-5 flex items-center gap-2">
                        <Lucide icon="FileText" class="w-4 h-4 text-primary" />
                        Plan Details
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div class="md:col-span-2">
                            <FormLabel>Membership Name <span class="text-red-500">*</span></FormLabel>
                            <FormInput v-model="form.name" type="text" placeholder="e.g. Growth Plan" :class="form.errors.name ? 'border-red-400' : ''" />
                            <p v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</p>
                        </div>

                        <div class="md:col-span-2">
                            <FormLabel>Description <span class="text-red-500">*</span></FormLabel>
                            <textarea
                                v-model="form.description"
                                rows="4"
                                class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-primary focus:border-primary"
                                :class="form.errors.description ? 'border-red-400' : ''"
                                placeholder="Summarize what this plan includes..."
                            />
                            <p v-if="form.errors.description" class="text-red-500 text-xs mt-1">{{ form.errors.description }}</p>
                        </div>

                        <div>
                            <FormLabel>Pricing Type <span class="text-red-500">*</span></FormLabel>
                            <FormSelect v-model="form.pricing_type">
                                <option value="plan">Plan</option>
                                <option value="individual">Individual</option>
                            </FormSelect>
                        </div>
                    </div>
                </div>

                <div class="box box--stacked p-6">
                    <h2 class="text-sm font-semibold text-slate-700 mb-5 flex items-center gap-2">
                        <Lucide icon="DollarSign" class="w-4 h-4 text-primary" />
                        Pricing Setup
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-5">
                        <div
                            v-for="card in pricingCards"
                            :key="card.title"
                            class="rounded-xl border border-slate-200 bg-slate-50/80 p-4"
                        >
                            <p class="text-xs uppercase tracking-wide text-slate-500">{{ card.title }}</p>
                            <p class="text-xl font-semibold text-slate-800 mt-1">{{ card.value ? `$${card.value}` : '$0.00' }}</p>
                            <p class="text-xs text-slate-500 mt-1">{{ card.description }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div v-if="form.pricing_type === 'plan'">
                            <FormLabel>Plan Price <span class="text-red-500">*</span></FormLabel>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">$</span>
                                <FormInput v-model="form.price" type="number" min="0" step="0.01" class="pl-7" :class="form.errors.price ? 'border-red-400' : ''" placeholder="0.00" />
                            </div>
                            <p v-if="form.errors.price" class="text-red-500 text-xs mt-1">{{ form.errors.price }}</p>
                        </div>

                        <template v-if="form.pricing_type === 'individual'">
                            <div>
                                <FormLabel>Carrier Price <span class="text-red-500">*</span></FormLabel>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">$</span>
                                    <FormInput v-model="form.carrier_price" type="number" min="0" step="0.01" class="pl-7" :class="form.errors.carrier_price ? 'border-red-400' : ''" placeholder="0.00" />
                                </div>
                                <p v-if="form.errors.carrier_price" class="text-red-500 text-xs mt-1">{{ form.errors.carrier_price }}</p>
                            </div>

                            <div>
                                <FormLabel>Driver Price <span class="text-red-500">*</span></FormLabel>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">$</span>
                                    <FormInput v-model="form.driver_price" type="number" min="0" step="0.01" class="pl-7" :class="form.errors.driver_price ? 'border-red-400' : ''" placeholder="0.00" />
                                </div>
                                <p v-if="form.errors.driver_price" class="text-red-500 text-xs mt-1">{{ form.errors.driver_price }}</p>
                            </div>

                            <div>
                                <FormLabel>Vehicle Price <span class="text-red-500">*</span></FormLabel>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">$</span>
                                    <FormInput v-model="form.vehicle_price" type="number" min="0" step="0.01" class="pl-7" :class="form.errors.vehicle_price ? 'border-red-400' : ''" placeholder="0.00" />
                                </div>
                                <p v-if="form.errors.vehicle_price" class="text-red-500 text-xs mt-1">{{ form.errors.vehicle_price }}</p>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="box box--stacked p-6">
                    <h2 class="text-sm font-semibold text-slate-700 mb-5 flex items-center gap-2">
                        <Lucide icon="Gauge" class="w-4 h-4 text-primary" />
                        Capacity Limits
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                        <div>
                            <FormLabel>Max Users</FormLabel>
                            <FormInput v-model="form.max_carrier" type="number" min="1" :class="form.errors.max_carrier ? 'border-red-400' : ''" />
                            <p v-if="form.errors.max_carrier" class="text-red-500 text-xs mt-1">{{ form.errors.max_carrier }}</p>
                        </div>
                        <div>
                            <FormLabel>Max Drivers</FormLabel>
                            <FormInput v-model="form.max_drivers" type="number" min="1" :class="form.errors.max_drivers ? 'border-red-400' : ''" />
                            <p v-if="form.errors.max_drivers" class="text-red-500 text-xs mt-1">{{ form.errors.max_drivers }}</p>
                        </div>
                        <div>
                            <FormLabel>Max Vehicles</FormLabel>
                            <FormInput v-model="form.max_vehicles" type="number" min="1" :class="form.errors.max_vehicles ? 'border-red-400' : ''" />
                            <p v-if="form.errors.max_vehicles" class="text-red-500 text-xs mt-1">{{ form.errors.max_vehicles }}</p>
                        </div>
                    </div>
                </div>

                <div class="box box--stacked p-6">
                    <h2 class="text-sm font-semibold text-slate-700 mb-5 flex items-center gap-2">
                        <Lucide icon="Settings2" class="w-4 h-4 text-primary" />
                        Visibility & Status
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <label class="flex items-center gap-3 rounded-xl border border-slate-200 px-4 py-4 cursor-pointer">
                            <input v-model="form.status" type="checkbox" class="w-4 h-4 rounded text-primary" />
                            <div>
                                <p class="text-sm font-medium text-slate-700">Membership active</p>
                                <p class="text-xs text-slate-500">Enable this plan for internal use.</p>
                            </div>
                        </label>

                        <label class="flex items-center gap-3 rounded-xl border border-slate-200 px-4 py-4 cursor-pointer">
                            <input v-model="form.show_in_register" type="checkbox" class="w-4 h-4 rounded text-primary" />
                            <div>
                                <p class="text-sm font-medium text-slate-700">Visible in registration</p>
                                <p class="text-xs text-slate-500">Show this plan during signup flows.</p>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="flex justify-end gap-3">
                    <Link :href="route('admin.memberships.index')">
                        <Button variant="outline-secondary" type="button">Cancel</Button>
                    </Link>
                    <Button type="submit" variant="primary" :disabled="form.processing">
                        {{ form.processing ? 'Saving...' : mode === 'edit' ? 'Update Membership' : 'Create Membership' }}
                    </Button>
                </div>
            </form>
        </div>

        <div class="col-span-12 xl:col-span-4">
            <div class="box box--stacked p-6 sticky top-4">
                <h2 class="text-sm font-semibold text-slate-700 mb-5 flex items-center gap-2">
                    <Lucide icon="ImagePlus" class="w-4 h-4 text-primary" />
                    Plan Artwork
                </h2>

                <div class="rounded-2xl border-2 border-dashed border-slate-200 bg-slate-50/70 p-6 text-center">
                    <template v-if="imagePreview">
                        <img :src="imagePreview" alt="Membership preview" class="w-full max-w-[220px] aspect-square object-cover rounded-2xl mx-auto mb-4 shadow-sm" />
                        <button type="button" class="text-sm text-red-500 hover:text-red-600" @click="removeImage">Remove image</button>
                    </template>

                    <template v-else>
                        <div class="mx-auto mb-4 flex h-20 w-20 items-center justify-center rounded-2xl bg-primary/10 text-primary">
                            <Lucide icon="ImagePlus" class="w-9 h-9" />
                        </div>
                        <p class="text-sm font-medium text-slate-700">Upload membership image</p>
                        <p class="text-xs text-slate-500 mt-1 mb-4">Square image recommended for cleaner cards.</p>
                    </template>

                    <label class="inline-flex items-center gap-2 px-4 py-2 bg-primary/10 text-primary rounded-lg cursor-pointer hover:bg-primary/20 text-sm transition">
                        <Lucide icon="Upload" class="w-4 h-4" />
                        Choose File
                        <input type="file" accept="image/*" class="hidden" @change="handleImage" />
                    </label>
                </div>
                <p v-if="form.errors.image_membership" class="text-red-500 text-xs mt-2">{{ form.errors.image_membership }}</p>
            </div>
        </div>
    </div>
</template>
