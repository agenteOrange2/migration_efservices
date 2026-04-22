<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import RazeLayout from '@/layouts/RazeLayout.vue';
import Button from '@/components/Base/Button';
import Lucide from '@/components/Base/Lucide';
import Litepicker from '@/components/Base/Litepicker/Litepicker.vue';
import { Dialog } from '@/components/Base/Headless';
import { ref } from 'vue';
import DriverSummaryCard from './components/DriverSummaryCard.vue';

declare function route(name: string, params?: any): string;

defineOptions({ layout: RazeLayout });

interface AssignmentDetail {
    id: number;
    driver_type: string;
    driver_type_label: string;
    start_date: string | null;
    end_date: string | null;
    status: string;
    status_label: string;
    notes: string | null;
    duration_label: string;
    assigned_by: string | null;
    created_at: string | null;
    vehicle: {
        id: number;
        unit: string;
        title: string;
        vin: string | null;
        status: string | null;
    } | null;
    company_driver?: { carrier_name?: string | null } | null;
    owner_operator?: {
        owner_name?: string | null;
        owner_phone?: string | null;
        owner_email?: string | null;
    } | null;
    third_party?: {
        name?: string | null;
        dba?: string | null;
        address?: string | null;
        phone?: string | null;
        email?: string | null;
        fein?: string | null;
        contact?: string | null;
        email_sent?: boolean;
    } | null;
}

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
    activeAssignment: AssignmentDetail | null;
    recentAssignments: AssignmentDetail[];
    availableVehiclesCount: number;
}>();

const cancelModalOpen = ref(false);
const pickerOptions = { singleMode: true, format: 'M/D/YYYY', autoApply: true };
const cancelForm = useForm({
    termination_date: new Date().toLocaleDateString('en-US'),
    termination_reason: '',
});

function submitCancellation() {
    cancelForm.post(
        route('admin.driver-types.cancel-assignment', props.driver.id),
        {
            preserveScroll: true,
            onSuccess: () => {
                cancelModalOpen.value = false;
                cancelForm.reset('termination_reason');
            },
        },
    );
}
</script>

<template>
    <Head :title="`Driver Types - ${driver.name}`" />

    <div class="grid grid-cols-12 gap-x-6 gap-y-8">
        <div class="col-span-12">
            <div
                class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between"
            >
                <div>
                    <div
                        class="text-xs font-semibold tracking-wide text-slate-500 uppercase"
                    >
                        Admin Driver Types
                    </div>
                    <h1 class="mt-1 text-2xl font-semibold text-slate-800">
                        Driver Assignment Profile
                    </h1>
                    <p class="mt-1 text-sm text-slate-500">
                        Review the active vehicle, recent changes, and quick
                        next actions for this driver.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <Link :href="route('admin.driver-types.index')">
                        <Button
                            variant="outline-secondary"
                            class="flex items-center gap-2"
                        >
                            <Lucide icon="ArrowLeft" class="h-4 w-4" />
                            Back
                        </Button>
                    </Link>
                    <Link
                        :href="
                            route(
                                'admin.driver-types.assignment-history',
                                driver.id,
                            )
                        "
                    >
                        <Button variant="outline-secondary"
                            >Full History</Button
                        >
                    </Link>
                    <Link
                        :href="route('admin.driver-types.contact', driver.id)"
                    >
                        <Button variant="outline-primary"
                            >Contact Driver</Button
                        >
                    </Link>
                    <Link
                        :href="
                            route(
                                activeAssignment
                                    ? 'admin.driver-types.edit-assignment'
                                    : 'admin.driver-types.assign-vehicle',
                                driver.id,
                            )
                        "
                    >
                        <Button variant="primary">
                            {{
                                activeAssignment
                                    ? 'Edit Assignment'
                                    : 'Assign Vehicle'
                            }}
                        </Button>
                    </Link>
                </div>
            </div>
        </div>

        <div class="col-span-12">
            <DriverSummaryCard :driver="driver" />
        </div>

        <div class="col-span-12 xl:col-span-8">
            <div class="box box--stacked p-6">
                <div
                    class="flex flex-col gap-3 border-b border-slate-200/70 pb-4 md:flex-row md:items-start md:justify-between"
                >
                    <div>
                        <h2 class="text-lg font-semibold text-slate-800">
                            Active Assignment
                        </h2>
                        <p class="mt-1 text-sm text-slate-500">
                            Current vehicle relationship and assignment
                            metadata.
                        </p>
                    </div>

                    <button
                        v-if="activeAssignment"
                        type="button"
                        class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-600 transition hover:bg-slate-50"
                        @click="cancelModalOpen = true"
                    >
                        <Lucide icon="Unlink" class="h-4 w-4" />
                        Cancel Assignment
                    </button>
                </div>

                <div v-if="activeAssignment" class="mt-5 space-y-5">
                    <div
                        class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4"
                    >
                        <div
                            class="rounded-2xl border border-dashed border-slate-300/80 bg-slate-50/70 p-4"
                        >
                            <div
                                class="text-xs font-semibold tracking-wide text-slate-500 uppercase"
                            >
                                Vehicle
                            </div>
                            <div
                                class="mt-1 text-base font-semibold text-slate-800"
                            >
                                {{ activeAssignment.vehicle?.unit || 'N/A' }}
                            </div>
                            <div class="mt-1 text-xs text-slate-500">
                                {{
                                    activeAssignment.vehicle?.title ||
                                    'No title available'
                                }}
                            </div>
                        </div>
                        <div
                            class="rounded-2xl border border-dashed border-slate-300/80 bg-slate-50/70 p-4"
                        >
                            <div
                                class="text-xs font-semibold tracking-wide text-slate-500 uppercase"
                            >
                                Driver Type
                            </div>
                            <div
                                class="mt-1 text-base font-semibold text-slate-800"
                            >
                                {{ activeAssignment.driver_type_label }}
                            </div>
                        </div>
                        <div
                            class="rounded-2xl border border-dashed border-slate-300/80 bg-slate-50/70 p-4"
                        >
                            <div
                                class="text-xs font-semibold tracking-wide text-slate-500 uppercase"
                            >
                                Start Date
                            </div>
                            <div
                                class="mt-1 text-base font-semibold text-slate-800"
                            >
                                {{ activeAssignment.start_date || 'N/A' }}
                            </div>
                        </div>
                        <div
                            class="rounded-2xl border border-dashed border-slate-300/80 bg-slate-50/70 p-4"
                        >
                            <div
                                class="text-xs font-semibold tracking-wide text-slate-500 uppercase"
                            >
                                Status
                            </div>
                            <div
                                class="mt-1 text-base font-semibold text-slate-800"
                            >
                                {{ activeAssignment.status_label }}
                            </div>
                            <div class="mt-1 text-xs text-slate-500">
                                {{ activeAssignment.duration_label }}
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div class="rounded-2xl border border-slate-200 p-5">
                            <h3
                                class="text-sm font-semibold tracking-wide text-slate-500 uppercase"
                            >
                                Vehicle Details
                            </h3>
                            <div class="mt-4 space-y-3 text-sm">
                                <div>
                                    <span class="font-medium text-slate-700"
                                        >Description:</span
                                    >
                                    <span class="text-slate-600">{{
                                        activeAssignment.vehicle?.title || 'N/A'
                                    }}</span>
                                </div>
                                <div>
                                    <span class="font-medium text-slate-700"
                                        >VIN:</span
                                    >
                                    <span class="text-slate-600">{{
                                        activeAssignment.vehicle?.vin || 'N/A'
                                    }}</span>
                                </div>
                                <div>
                                    <span class="font-medium text-slate-700"
                                        >Vehicle Status:</span
                                    >
                                    <span class="text-slate-600">{{
                                        activeAssignment.vehicle?.status ||
                                        'N/A'
                                    }}</span>
                                </div>
                                <div>
                                    <span class="font-medium text-slate-700"
                                        >Assigned By:</span
                                    >
                                    <span class="text-slate-600">{{
                                        activeAssignment.assigned_by || 'System'
                                    }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-2xl border border-slate-200 p-5">
                            <h3
                                class="text-sm font-semibold tracking-wide text-slate-500 uppercase"
                            >
                                Assignment Notes
                            </h3>
                            <div
                                class="mt-4 text-sm leading-6 whitespace-pre-wrap text-slate-600"
                            >
                                {{
                                    activeAssignment.notes ||
                                    'No notes were added to this assignment.'
                                }}
                            </div>
                        </div>
                    </div>

                    <div
                        v-if="
                            activeAssignment.third_party ||
                            activeAssignment.owner_operator ||
                            activeAssignment.company_driver
                        "
                        class="rounded-2xl border border-slate-200 p-5"
                    >
                        <h3
                            class="text-sm font-semibold tracking-wide text-slate-500 uppercase"
                        >
                            Assignment Context
                        </h3>

                        <div
                            v-if="activeAssignment.company_driver"
                            class="mt-4 text-sm text-slate-600"
                        >
                            <span class="font-medium text-slate-700"
                                >Carrier:</span
                            >
                            {{
                                activeAssignment.company_driver.carrier_name ||
                                driver.carrier?.name ||
                                'N/A'
                            }}
                        </div>

                        <div
                            v-if="activeAssignment.owner_operator"
                            class="mt-4 grid grid-cols-1 gap-3 text-sm md:grid-cols-3"
                        >
                            <div>
                                <span class="font-medium text-slate-700"
                                    >Owner:</span
                                >
                                {{
                                    activeAssignment.owner_operator
                                        .owner_name || 'N/A'
                                }}
                            </div>
                            <div>
                                <span class="font-medium text-slate-700"
                                    >Phone:</span
                                >
                                {{
                                    activeAssignment.owner_operator
                                        .owner_phone || 'N/A'
                                }}
                            </div>
                            <div>
                                <span class="font-medium text-slate-700"
                                    >Email:</span
                                >
                                {{
                                    activeAssignment.owner_operator
                                        .owner_email || 'N/A'
                                }}
                            </div>
                        </div>

                        <div
                            v-if="activeAssignment.third_party"
                            class="mt-4 grid grid-cols-1 gap-3 text-sm md:grid-cols-2 xl:grid-cols-3"
                        >
                            <div>
                                <span class="font-medium text-slate-700"
                                    >Company:</span
                                >
                                {{ activeAssignment.third_party.name || 'N/A' }}
                            </div>
                            <div>
                                <span class="font-medium text-slate-700"
                                    >DBA:</span
                                >
                                {{ activeAssignment.third_party.dba || 'N/A' }}
                            </div>
                            <div>
                                <span class="font-medium text-slate-700"
                                    >Contact:</span
                                >
                                {{
                                    activeAssignment.third_party.contact ||
                                    'N/A'
                                }}
                            </div>
                            <div>
                                <span class="font-medium text-slate-700"
                                    >Phone:</span
                                >
                                {{
                                    activeAssignment.third_party.phone || 'N/A'
                                }}
                            </div>
                            <div>
                                <span class="font-medium text-slate-700"
                                    >Email:</span
                                >
                                {{
                                    activeAssignment.third_party.email || 'N/A'
                                }}
                            </div>
                            <div>
                                <span class="font-medium text-slate-700"
                                    >FEIN:</span
                                >
                                {{ activeAssignment.third_party.fein || 'N/A' }}
                            </div>
                            <div class="md:col-span-2 xl:col-span-3">
                                <span class="font-medium text-slate-700"
                                    >Address:</span
                                >
                                {{
                                    activeAssignment.third_party.address ||
                                    'N/A'
                                }}
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    v-else
                    class="mt-5 rounded-2xl border border-dashed border-slate-300/80 bg-slate-50/70 p-6"
                >
                    <h3 class="text-lg font-semibold text-slate-800">
                        No Active Assignment
                    </h3>
                    <p class="mt-2 text-sm text-slate-500">
                        This driver does not currently have an assigned vehicle.
                        There are {{ availableVehiclesCount }} available
                        vehicles ready to assign.
                    </p>
                    <Link
                        :href="
                            route(
                                'admin.driver-types.assign-vehicle',
                                driver.id,
                            )
                        "
                        class="mt-4 inline-flex"
                    >
                        <Button variant="primary">Assign a Vehicle</Button>
                    </Link>
                </div>
            </div>
        </div>

        <div class="col-span-12 xl:col-span-4">
            <div class="box box--stacked p-6">
                <h2 class="text-lg font-semibold text-slate-800">
                    Recent Assignment History
                </h2>
                <p class="mt-1 text-sm text-slate-500">
                    Latest changes for this driver.
                </p>

                <div class="mt-5 space-y-4">
                    <div
                        v-for="assignment in recentAssignments"
                        :key="assignment.id"
                        class="rounded-2xl border border-slate-200 p-4"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <div class="font-semibold text-slate-800">
                                    {{
                                        assignment.vehicle?.unit || 'No vehicle'
                                    }}
                                </div>
                                <div class="mt-1 text-xs text-slate-500">
                                    {{
                                        assignment.vehicle?.title ||
                                        'Historical record'
                                    }}
                                </div>
                            </div>
                            <div
                                class="rounded-full bg-primary/10 px-3 py-1 text-[11px] font-semibold tracking-wide text-primary uppercase"
                            >
                                {{ assignment.status_label }}
                            </div>
                        </div>
                        <div class="mt-3 text-sm text-slate-600">
                            {{ assignment.driver_type_label }} ·
                            {{ assignment.start_date || 'N/A' }}
                            <template v-if="assignment.end_date">
                                to {{ assignment.end_date }}</template
                            >
                        </div>
                    </div>

                    <div
                        v-if="recentAssignments.length === 0"
                        class="rounded-2xl border border-dashed border-slate-300/80 bg-slate-50/70 p-5 text-sm text-slate-500"
                    >
                        No assignment history is available yet.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <Dialog :open="cancelModalOpen" @close="cancelModalOpen = false" size="lg">
        <Dialog.Panel class="w-full max-w-[620px] overflow-hidden">
            <div class="border-b border-slate-200/70 px-6 py-4">
                <h3 class="text-lg font-semibold text-slate-800">
                    Cancel Active Assignment
                </h3>
                <p class="mt-1 text-sm text-slate-500">
                    This will end the current relationship and return the
                    vehicle to pending status.
                </p>
            </div>

            <div class="space-y-5 px-6 py-5">
                <div>
                    <label
                        class="mb-1.5 block text-xs font-semibold tracking-wide text-slate-500 uppercase"
                        >Termination Date</label
                    >
                    <Litepicker
                        v-model="cancelForm.termination_date"
                        :options="pickerOptions"
                    />
                    <p
                        v-if="cancelForm.errors.termination_date"
                        class="mt-1 text-xs text-red-500"
                    >
                        {{ cancelForm.errors.termination_date }}
                    </p>
                </div>

                <div>
                    <label
                        class="mb-1.5 block text-xs font-semibold tracking-wide text-slate-500 uppercase"
                        >Reason</label
                    >
                    <textarea
                        v-model="cancelForm.termination_reason"
                        rows="4"
                        class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm"
                        placeholder="Explain why this assignment is being terminated..."
                    />
                    <p
                        v-if="cancelForm.errors.termination_reason"
                        class="mt-1 text-xs text-red-500"
                    >
                        {{ cancelForm.errors.termination_reason }}
                    </p>
                </div>
            </div>

            <div
                class="flex items-center justify-end gap-3 border-t border-slate-200/70 px-6 py-4"
            >
                <Button
                    variant="outline-secondary"
                    @click="cancelModalOpen = false"
                    >Close</Button
                >
                <Button
                    variant="primary"
                    :disabled="cancelForm.processing"
                    @click="submitCancellation"
                >
                    Confirm Cancellation
                </Button>
            </div>
        </Dialog.Panel>
    </Dialog>
</template>
