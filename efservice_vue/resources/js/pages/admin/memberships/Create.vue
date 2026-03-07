<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import Lucide from '@/components/Base/Lucide'
import { FormInput, FormSelect, FormLabel, FormCheck } from '@/components/Base/Form'
import Button from '@/components/Base/Button'
import RazeLayout from '@/layouts/RazeLayout.vue'

defineOptions({ layout: RazeLayout })

const form = useForm({
    name: '',
    description: '',
    pricing_type: 'plan',
    price: '',
    carrier_price: '',
    driver_price: '',
    vehicle_price: '',
    max_carrier: 1,
    max_drivers: 1,
    max_vehicles: 1,
    status: true,
    show_in_register: false,
    image_membership: null as File | null,
})

const imagePreview = ref<string | null>(null)

function handleImage(e: Event) {
    const target = e.target as HTMLInputElement
    if (target.files?.[0]) {
        form.image_membership = target.files[0]
        imagePreview.value = URL.createObjectURL(target.files[0])
    }
}

function removeImage() {
    form.image_membership = null
    imagePreview.value = null
}

function submit() {
    form.post(route('admin.memberships.store'), {
        forceFormData: true,
    })
}
</script>

<template>
    <Head title="Create Membership" />

    <div class="grid grid-cols-12 gap-x-6 gap-y-8">
        <div class="col-span-12">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-3">
                    <Link :href="route('admin.memberships.index')" class="p-2 rounded-lg hover:bg-slate-100 transition">
                        <Lucide icon="ArrowLeft" class="w-5 h-5 text-slate-600" />
                    </Link>
                    <div>
                        <h1 class="text-2xl font-bold text-slate-800">Create Membership</h1>
                        <p class="text-sm text-slate-500">Add a new subscription plan</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12 lg:col-span-8">
            <form @submit.prevent="submit">
                <div class="box box--stacked p-6 space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div class="md:col-span-2">
                            <FormLabel>Plan Name *</FormLabel>
                            <FormInput v-model="form.name" type="text" placeholder="e.g. Basic Plan" />
                            <div v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</div>
                        </div>

                        <div class="md:col-span-2">
                            <FormLabel>Description *</FormLabel>
                            <textarea v-model="form.description" rows="3" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-primary focus:border-primary" placeholder="Brief description of the plan..."></textarea>
                            <div v-if="form.errors.description" class="text-red-500 text-xs mt-1">{{ form.errors.description }}</div>
                        </div>
                    </div>

                    <div class="border-t border-slate-200/60 pt-5">
                        <h3 class="text-lg font-semibold text-slate-700 mb-4">Pricing</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <FormLabel>Pricing Type *</FormLabel>
                                <FormSelect v-model="form.pricing_type">
                                    <option value="plan">Plan (Bundle pricing)</option>
                                    <option value="individual">Individual (Separate pricing)</option>
                                </FormSelect>
                            </div>

                            <div v-if="form.pricing_type === 'plan'">
                                <FormLabel>Plan Price *</FormLabel>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">$</span>
                                    <FormInput v-model="form.price" type="number" step="0.01" min="0" class="pl-7" placeholder="0.00" />
                                </div>
                                <div v-if="form.errors.price" class="text-red-500 text-xs mt-1">{{ form.errors.price }}</div>
                            </div>

                            <template v-if="form.pricing_type === 'individual'">
                                <div>
                                    <FormLabel>Carrier Price *</FormLabel>
                                    <div class="relative">
                                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">$</span>
                                        <FormInput v-model="form.carrier_price" type="number" step="0.01" min="0" class="pl-7" placeholder="0.00" />
                                    </div>
                                    <div v-if="form.errors.carrier_price" class="text-red-500 text-xs mt-1">{{ form.errors.carrier_price }}</div>
                                </div>
                                <div>
                                    <FormLabel>Driver Price *</FormLabel>
                                    <div class="relative">
                                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">$</span>
                                        <FormInput v-model="form.driver_price" type="number" step="0.01" min="0" class="pl-7" placeholder="0.00" />
                                    </div>
                                    <div v-if="form.errors.driver_price" class="text-red-500 text-xs mt-1">{{ form.errors.driver_price }}</div>
                                </div>
                                <div>
                                    <FormLabel>Vehicle Price *</FormLabel>
                                    <div class="relative">
                                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">$</span>
                                        <FormInput v-model="form.vehicle_price" type="number" step="0.01" min="0" class="pl-7" placeholder="0.00" />
                                    </div>
                                    <div v-if="form.errors.vehicle_price" class="text-red-500 text-xs mt-1">{{ form.errors.vehicle_price }}</div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="border-t border-slate-200/60 pt-5">
                        <h3 class="text-lg font-semibold text-slate-700 mb-4">Units Allowed</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                            <div>
                                <FormLabel>Max Users</FormLabel>
                                <FormInput v-model="form.max_carrier" type="number" min="1" />
                                <div v-if="form.errors.max_carrier" class="text-red-500 text-xs mt-1">{{ form.errors.max_carrier }}</div>
                            </div>
                            <div>
                                <FormLabel>Max Drivers</FormLabel>
                                <FormInput v-model="form.max_drivers" type="number" min="1" />
                                <div v-if="form.errors.max_drivers" class="text-red-500 text-xs mt-1">{{ form.errors.max_drivers }}</div>
                            </div>
                            <div>
                                <FormLabel>Max Vehicles</FormLabel>
                                <FormInput v-model="form.max_vehicles" type="number" min="1" />
                                <div v-if="form.errors.max_vehicles" class="text-red-500 text-xs mt-1">{{ form.errors.max_vehicles }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="border-t border-slate-200/60 pt-5">
                        <h3 class="text-lg font-semibold text-slate-700 mb-4">Settings</h3>
                        <div class="flex flex-wrap gap-6">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" v-model="form.status" :true-value="true" :false-value="false" class="rounded border-slate-300 text-primary focus:ring-primary" />
                                <span class="text-sm text-slate-700">Active</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" v-model="form.show_in_register" :true-value="true" :false-value="false" class="rounded border-slate-300 text-primary focus:ring-primary" />
                                <span class="text-sm text-slate-700">Show in Registration</span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-3 mt-5">
                    <Link :href="route('admin.memberships.index')" class="px-4 py-2 border border-slate-300 rounded-lg text-slate-600 hover:bg-slate-50 transition">
                        Cancel
                    </Link>
                    <Button type="submit" variant="primary" :disabled="form.processing" class="px-6">
                        <Lucide icon="Save" class="w-4 h-4 mr-2" /> Save Membership
                    </Button>
                </div>
            </form>
        </div>

        <div class="col-span-12 lg:col-span-4">
            <div class="box box--stacked p-6">
                <h3 class="text-base font-semibold text-slate-700 mb-4">Plan Image</h3>
                <div class="border-2 border-dashed border-slate-200 rounded-xl p-6 text-center">
                    <template v-if="imagePreview">
                        <img :src="imagePreview" class="w-40 h-40 object-cover rounded-lg mx-auto mb-3" />
                        <button @click="removeImage" type="button" class="text-sm text-red-500 hover:text-red-600">Remove Image</button>
                    </template>
                    <template v-else>
                        <Lucide icon="ImagePlus" class="w-12 h-12 mx-auto text-slate-300 mb-3" />
                        <p class="text-sm text-slate-500 mb-2">Upload plan image</p>
                        <label class="inline-flex items-center gap-2 px-3 py-1.5 bg-primary/10 text-primary rounded-lg cursor-pointer hover:bg-primary/20 text-sm transition">
                            <Lucide icon="Upload" class="w-3 h-3" /> Choose File
                            <input type="file" accept="image/*" @change="handleImage" class="hidden" />
                        </label>
                    </template>
                </div>
                <div v-if="form.errors.image_membership" class="text-red-500 text-xs mt-2">{{ form.errors.image_membership }}</div>
            </div>
        </div>
    </div>
</template>
