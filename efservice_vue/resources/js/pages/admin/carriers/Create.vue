<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import { FormInput, FormLabel, FormSelect } from '@/components/Base/Form'
import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import RazeLayout from '@/layouts/RazeLayout.vue'

interface Props {
    memberships: { id: number; name: string; price: number }[]
    usStates: Record<string, string>
    statusOptions: { value: number; label: string }[]
}

const props = defineProps<Props>()

const form = useForm({
    name: '',
    address: '',
    state: '',
    zipcode: '',
    ein_number: '',
    dot_number: '',
    mc_number: '',
    state_dot: '',
    ifta_account: '',
    id_plan: '',
    status: 2,
    logo_carrier: null as File | null,
})

function handleFileChange(e: Event) {
    const target = e.target as HTMLInputElement
    if (target.files?.[0]) {
        form.logo_carrier = target.files[0]
    }
}

function submit() {
    form.post(route('admin.carriers.store'), {
        forceFormData: true,
        preserveScroll: true,
    })
}
</script>

<template>
    <Head title="Create Carrier" />

    <RazeLayout>
        <div class="grid grid-cols-12 gap-y-10 gap-x-6">
            <!-- Header -->
            <div class="col-span-12">
                <div class="flex flex-col md:h-10 gap-y-3 md:items-center md:flex-row">
                    <div class="text-base font-medium">
                        <Link :href="route('admin.carriers.index')" class="text-primary hover:underline">Carriers</Link>
                        <span class="mx-2 text-slate-400">/</span>
                        Create Carrier
                    </div>
                </div>
            </div>

            <!-- Form -->
            <div class="col-span-12">
                <div class="box box--stacked">
                    <form @submit.prevent="submit">
                        <div class="p-7">
                            <!-- Logo -->
                            <div class="mt-5 block flex-col pt-5 first:mt-0 first:pt-0 sm:flex xl:flex-row xl:items-center">
                                <div class="mb-2 inline-block sm:mb-0 sm:mr-5 sm:text-right xl:mr-14 xl:w-60">
                                    <div class="text-left">
                                        <div class="font-medium">Profile Photo</div>
                                        <div class="mt-1.5 text-xs leading-relaxed text-slate-500/80 xl:mt-3">Upload a carrier logo</div>
                                    </div>
                                </div>
                                <div class="mt-3 w-full flex-1 xl:mt-0">
                                    <input type="file" @change="handleFileChange" accept="image/*" class="text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-primary/10 file:text-primary hover:file:bg-primary/20" />
                                    <p v-if="form.errors.logo_carrier" class="mt-1 text-xs text-red-500">{{ form.errors.logo_carrier }}</p>
                                </div>
                            </div>

                            <!-- Name -->
                            <div class="mt-5 block flex-col pt-5 first:mt-0 first:pt-0 sm:flex xl:flex-row xl:items-center">
                                <div class="mb-2 inline-block sm:mb-0 sm:mr-5 sm:text-right xl:mr-14 xl:w-60">
                                    <div class="text-left">
                                        <div class="flex items-center">
                                            <div class="font-medium">Carrier Name</div>
                                            <div class="ml-2.5 rounded-md border border-slate-200 bg-slate-100 px-2 py-0.5 text-xs text-slate-500">Required</div>
                                        </div>
                                        <div class="mt-1.5 text-xs leading-relaxed text-slate-500/80 xl:mt-3">Enter the carrier's legal name</div>
                                    </div>
                                </div>
                                <div class="mt-3 w-full flex-1 xl:mt-0">
                                    <FormInput v-model="form.name" type="text" placeholder="Enter carrier name" />
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
                                    <FormInput v-model="form.address" type="text" placeholder="Enter full address" />
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
                                        <div class="mt-1.5 text-xs leading-relaxed text-slate-500/80 xl:mt-3">Format: XX-XXXXXXX</div>
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
                                        <div class="flex items-center">
                                            <div class="font-medium">DOT Number</div>
                                            <div class="ml-2.5 rounded-md border border-slate-200 bg-slate-100 px-2 py-0.5 text-xs text-slate-500">Optional</div>
                                        </div>
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
                                        <div class="flex items-center">
                                            <div class="font-medium">MC Number</div>
                                            <div class="ml-2.5 rounded-md border border-slate-200 bg-slate-100 px-2 py-0.5 text-xs text-slate-500">Optional</div>
                                        </div>
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
                                        <div class="flex items-center">
                                            <div class="font-medium">State DOT</div>
                                            <div class="ml-2.5 rounded-md border border-slate-200 bg-slate-100 px-2 py-0.5 text-xs text-slate-500">Optional</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-3 w-full flex-1 xl:mt-0">
                                    <FormInput v-model="form.state_dot" type="text" placeholder="State DOT" />
                                    <p v-if="form.errors.state_dot" class="mt-1 text-xs text-red-500">{{ form.errors.state_dot }}</p>
                                </div>
                            </div>

                            <!-- IFTA -->
                            <div class="mt-5 block flex-col pt-5 sm:flex xl:flex-row xl:items-center">
                                <div class="mb-2 inline-block sm:mb-0 sm:mr-5 sm:text-right xl:mr-14 xl:w-60">
                                    <div class="text-left">
                                        <div class="flex items-center">
                                            <div class="font-medium">IFTA Account</div>
                                            <div class="ml-2.5 rounded-md border border-slate-200 bg-slate-100 px-2 py-0.5 text-xs text-slate-500">Optional</div>
                                        </div>
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
                                        <option value="">Select a Membership Plan</option>
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
                                    <p v-if="form.errors.status" class="mt-1 text-xs text-red-500">{{ form.errors.status }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex flex-col sm:flex-row gap-3 border-t border-slate-200/80 px-7 py-5 md:justify-end">
                            <Link :href="route('admin.carriers.index')">
                                <Button variant="outline-secondary" class="w-full sm:w-auto px-10">
                                    <Lucide icon="X" class="w-4 h-4 mr-2" /> Cancel
                                </Button>
                            </Link>
                            <Button type="submit" variant="primary" class="w-full sm:w-auto px-10" :disabled="form.processing">
                                <Lucide icon="Save" class="w-4 h-4 mr-2" />
                                {{ form.processing ? 'Saving...' : 'Save Carrier' }}
                            </Button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </RazeLayout>
</template>
