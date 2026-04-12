<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import CarrierLayout from '@/layouts/CarrierLayout.vue'
import Form from './Form.vue'

declare function route(name: string, params?: any): string

defineOptions({ layout: CarrierLayout })

interface DriverOption {
    id: number
    name: string
    email: string
}

interface AdminContact {
    id: number
    name: string
    email: string
}

const props = defineProps<{
    drivers: DriverOption[]
    adminContact: AdminContact | null
}>()

const form = useForm({
    recipient_type: 'all_my_drivers',
    driver_ids: [] as string[],
    subject: '',
    message: '',
    priority: 'normal',
    status: 'draft',
})

function submit() {
    form.post(route('carrier.messages.store'), {
        preserveScroll: true,
    })
}
</script>

<template>
    <Head title="Compose Carrier Message" />

    <div class="grid grid-cols-12 gap-6">
        <div class="col-span-12">
            <div class="box box--stacked p-6">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex items-center gap-4">
                        <div class="rounded-2xl border border-primary/20 bg-primary/10 p-3">
                            <Lucide icon="MailPlus" class="h-8 w-8 text-primary" />
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-slate-800">Compose Message</h1>
                            <p class="text-sm text-slate-500">Send an announcement to your drivers or contact the platform admin from one place.</p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <Link :href="route('carrier.messages.dashboard')">
                            <Button variant="outline-secondary" class="flex items-center gap-2">
                                <Lucide icon="BarChart3" class="h-4 w-4" />
                                Dashboard
                            </Button>
                        </Link>
                        <Link :href="route('carrier.messages.index')">
                            <Button variant="outline-secondary" class="flex items-center gap-2">
                                <Lucide icon="ArrowLeft" class="h-4 w-4" />
                                Back to Messages
                            </Button>
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12 xl:col-span-9">
            <Form :mode="'create'" :form="form" :drivers="drivers" :admin-contact="adminContact" />
        </div>

        <div class="col-span-12 xl:col-span-3">
            <div class="box box--stacked p-6">
                <h2 class="text-base font-semibold text-slate-800">Publishing Notes</h2>
                <div class="mt-4 space-y-4 text-sm text-slate-600">
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <p class="font-medium text-slate-800">Draft</p>
                        <p class="mt-1">Keep the message editable so your team can verify content and recipients before delivery.</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <p class="font-medium text-slate-800">Send Now</p>
                        <p class="mt-1">Creates the record and immediately attempts email delivery for every valid recipient.</p>
                    </div>
                    <div class="rounded-xl border border-dashed border-slate-300 bg-white p-4">
                        <p class="font-medium text-slate-800">Tip</p>
                        <p class="mt-1">For sensitive updates, save the draft first so you can double-check the audience and wording.</p>
                    </div>
                </div>

                <div class="mt-6 flex flex-col gap-3">
                    <Button variant="primary" class="flex items-center justify-center gap-2" :disabled="form.processing" @click="submit">
                        <Lucide icon="Send" class="h-4 w-4" />
                        {{ form.status === 'sent' ? 'Create and Send' : 'Save Draft' }}
                    </Button>
                    <Link :href="route('carrier.messages.index')">
                        <Button variant="outline-secondary" class="w-full">Cancel</Button>
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>
