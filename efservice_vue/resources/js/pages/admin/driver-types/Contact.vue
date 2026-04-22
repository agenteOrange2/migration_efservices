<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import RazeLayout from '@/layouts/RazeLayout.vue';
import Lucide from '@/components/Base/Lucide';
import Button from '@/components/Base/Button';
import TomSelect from '@/components/Base/TomSelect/TomSelect.vue';
import { FormInput, FormTextarea } from '@/components/Base/Form';
import DriverSummaryCard from './components/DriverSummaryCard.vue';

declare function route(name: string, params?: any): string;

defineOptions({ layout: RazeLayout });

const props = defineProps<{
    driver: {
        id: number;
        name: string;
        email?: string | null;
        phone?: string | null;
        date_of_birth?: string | null;
        status?: string | null;
        profile_photo_url?: string | null;
        carrier?: { id: number; name: string } | null;
    };
    priorityOptions: { value: string; label: string }[];
}>();

const form = useForm({
    subject: '',
    priority: 'normal',
    message: '',
});

function submit() {
    form.post(route('admin.driver-types.send-contact', props.driver.id));
}
</script>

<template>
    <Head :title="`Contact Driver - ${driver.name}`" />

    <div class="grid grid-cols-12 gap-x-6 gap-y-8">
        <div class="col-span-12">
            <div
                class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between"
            >
                <div>
                    <div
                        class="text-xs font-semibold tracking-wide text-slate-500 uppercase"
                    >
                        Driver Communication
                    </div>
                    <h1 class="mt-1 text-2xl font-semibold text-slate-800">
                        Contact Driver
                    </h1>
                    <p class="mt-1 text-sm text-slate-500">
                        Send a direct message and keep a record tied to the
                        assignment workflow.
                    </p>
                </div>
                <Link :href="route('admin.driver-types.show', driver.id)">
                    <Button variant="outline-secondary">Back to Driver</Button>
                </Link>
            </div>
        </div>

        <div class="col-span-12">
            <DriverSummaryCard :driver="driver" />
        </div>

        <div class="col-span-12 xl:col-span-8">
            <div class="box box--stacked p-6">
                <div
                    class="flex items-start gap-3 border-b border-slate-200/70 pb-4"
                >
                    <div class="rounded-xl bg-primary/10 p-2">
                        <Lucide icon="Send" class="h-5 w-5 text-primary" />
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-slate-800">
                            Compose Message
                        </h2>
                        <p class="mt-1 text-sm text-slate-500">
                            The driver will receive this via email, and we will
                            store the send result.
                        </p>
                    </div>
                </div>

                <div class="mt-5 grid grid-cols-1 gap-5">
                    <div>
                        <label
                            class="mb-1.5 block text-xs font-semibold tracking-wide text-slate-500 uppercase"
                            >Subject <span class="text-red-500">*</span></label
                        >
                        <FormInput
                            v-model="form.subject"
                            type="text"
                            placeholder="Enter message subject..."
                        />
                        <p
                            v-if="form.errors.subject"
                            class="mt-1 text-xs text-red-500"
                        >
                            {{ form.errors.subject }}
                        </p>
                    </div>

                    <div>
                        <label
                            class="mb-1.5 block text-xs font-semibold tracking-wide text-slate-500 uppercase"
                            >Priority <span class="text-red-500">*</span></label
                        >
                        <TomSelect v-model="form.priority">
                            <option
                                v-for="option in priorityOptions"
                                :key="option.value"
                                :value="option.value"
                            >
                                {{ option.label }}
                            </option>
                        </TomSelect>
                        <p
                            v-if="form.errors.priority"
                            class="mt-1 text-xs text-red-500"
                        >
                            {{ form.errors.priority }}
                        </p>
                    </div>

                    <div>
                        <label
                            class="mb-1.5 block text-xs font-semibold tracking-wide text-slate-500 uppercase"
                            >Message <span class="text-red-500">*</span></label
                        >
                        <FormTextarea
                            v-model="form.message"
                            rows="8"
                            placeholder="Write the message you want the driver to receive..."
                        />
                        <p
                            v-if="form.errors.message"
                            class="mt-1 text-xs text-red-500"
                        >
                            {{ form.errors.message }}
                        </p>
                    </div>
                </div>

                <div class="mt-6 flex flex-wrap items-center justify-end gap-3">
                    <Link :href="route('admin.driver-types.show', driver.id)">
                        <Button variant="outline-secondary">Cancel</Button>
                    </Link>
                    <Button
                        variant="primary"
                        :disabled="form.processing"
                        @click="submit"
                    >
                        Send Message
                    </Button>
                </div>
            </div>
        </div>

        <div class="col-span-12 xl:col-span-4">
            <div class="box box--stacked p-6">
                <h2 class="text-lg font-semibold text-slate-800">
                    Delivery Notes
                </h2>
                <div class="mt-4 space-y-3 text-sm text-slate-600">
                    <p>
                        The email will be sent to
                        <span class="font-medium text-slate-800">{{
                            driver.email || 'the driver email on file'
                        }}</span
                        >.
                    </p>
                    <p>
                        Priority is included in the message so urgent
                        operational notes are clear to the driver.
                    </p>
                    <p>
                        A delivery status row is stored together with the
                        message so we can audit follow-up if something fails.
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
