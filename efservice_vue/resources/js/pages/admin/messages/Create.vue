<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import Button from '@/components/Base/Button'
import Lucide from '@/components/Base/Lucide'
import RazeLayout from '@/layouts/RazeLayout.vue'
import Form from './Form.vue'

declare function route(name: string, params?: any): string

defineOptions({ layout: RazeLayout })

interface DriverOption {
    id: number
    name: string
    email: string
    carrier_name: string | null
}

interface CarrierOption {
    id: number
    name: string
    email: string | null
    contact_name: string | null
}

const props = defineProps<{
    drivers: DriverOption[]
    carriers: CarrierOption[]
}>()

const form = useForm({
    recipient_type: 'all_drivers',
    driver_ids: [] as string[],
    carrier_ids: [] as string[],
    custom_emails: '',
    carrier_filter: '',
    subject: '',
    message: '',
    priority: 'normal',
    status: 'draft',
})

function submit() {
    form.post(route('admin.messages.store'), {
        preserveScroll: true,
    })
}
</script>

<template>
    <Head title="Compose Message" />

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
                            <p class="text-sm text-slate-500">Create a draft or send a message to drivers, carriers, or external recipients.</p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <Link :href="route('admin.messages.dashboard')">
                            <Button variant="outline-secondary" class="flex items-center gap-2">
                                <Lucide icon="BarChart3" class="h-4 w-4" />
                                Dashboard
                            </Button>
                        </Link>
                        <Link :href="route('admin.messages.index')">
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
            <Form :mode="'create'" :form="form" :drivers="drivers" :carriers="carriers" />
        </div>

        <div class="col-span-12 xl:col-span-3">
            <div class="box box--stacked p-6">
                <h2 class="text-base font-semibold text-slate-800">Publishing Notes</h2>
                <div class="mt-4 space-y-4 text-sm text-slate-600">
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <p class="font-medium text-slate-800">Draft</p>
                        <p class="mt-1">Keeps the message editable so you can review recipients and content before delivery.</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <p class="font-medium text-slate-800">Send Now</p>
                        <p class="mt-1">Creates the record and attempts email delivery immediately for every valid recipient.</p>
                    </div>
                    <div class="rounded-xl border border-dashed border-slate-300 bg-white p-4">
                        <p class="font-medium text-slate-800">Tip</p>
                        <p class="mt-1">For larger announcements, save a draft first so the recipient list can be verified before sending.</p>
                    </div>
                </div>

                <div class="mt-6 flex flex-col gap-3">
                    <Button variant="primary" class="flex items-center justify-center gap-2" :disabled="form.processing" @click="submit">
                        <Lucide icon="Send" class="h-4 w-4" />
                        {{ form.status === 'sent' ? 'Create and Send' : 'Save Draft' }}
                    </Button>
                    <Link :href="route('admin.messages.index')">
                        <Button variant="outline-secondary" class="w-full">Cancel</Button>
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>
