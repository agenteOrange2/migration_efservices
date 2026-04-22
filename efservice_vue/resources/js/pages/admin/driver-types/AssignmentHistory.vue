<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import RazeLayout from '@/layouts/RazeLayout.vue';
import Button from '@/components/Base/Button';
import DriverSummaryCard from './components/DriverSummaryCard.vue';

declare function route(name: string, params?: any): string;

defineOptions({ layout: RazeLayout });

interface AssignmentDetail {
    id: number;
    driver_type_label: string;
    start_date: string | null;
    end_date: string | null;
    status_label: string;
    notes: string | null;
    duration_label: string;
    assigned_by: string | null;
    vehicle: {
        id: number;
        unit: string;
        title: string;
        vin: string | null;
    } | null;
    third_party?: { name?: string | null } | null;
}

defineProps<{
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
    assignments: AssignmentDetail[];
    hasActiveAssignment: boolean;
}>();
</script>

<template>
    <Head :title="`Driver Types History - ${driver.name}`" />

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
                        Assignment History
                    </h1>
                    <p class="mt-1 text-sm text-slate-500">
                        Complete log of vehicle changes for this driver.
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <Link :href="route('admin.driver-types.show', driver.id)">
                        <Button variant="outline-secondary"
                            >Back to Driver</Button
                        >
                    </Link>
                    <Link
                        :href="
                            route(
                                hasActiveAssignment
                                    ? 'admin.driver-types.edit-assignment'
                                    : 'admin.driver-types.assign-vehicle',
                                driver.id,
                            )
                        "
                    >
                        <Button variant="primary">{{
                            hasActiveAssignment
                                ? 'Edit Active Assignment'
                                : 'Assign Vehicle'
                        }}</Button>
                    </Link>
                </div>
            </div>
        </div>

        <div class="col-span-12">
            <DriverSummaryCard :driver="driver" />
        </div>

        <div class="col-span-12">
            <div class="box box--stacked overflow-hidden">
                <div class="border-b border-slate-200/70 px-5 py-4">
                    <h2 class="text-base font-semibold text-slate-800">
                        Timeline
                    </h2>
                    <p class="mt-1 text-sm text-slate-500">
                        {{ assignments.length }} assignment record(s)
                    </p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50/80">
                            <tr>
                                <th
                                    class="px-5 py-3 text-left font-semibold text-slate-600"
                                >
                                    Vehicle
                                </th>
                                <th
                                    class="px-5 py-3 text-left font-semibold text-slate-600"
                                >
                                    Driver Type
                                </th>
                                <th
                                    class="px-5 py-3 text-left font-semibold text-slate-600"
                                >
                                    Dates
                                </th>
                                <th
                                    class="px-5 py-3 text-left font-semibold text-slate-600"
                                >
                                    Status
                                </th>
                                <th
                                    class="px-5 py-3 text-left font-semibold text-slate-600"
                                >
                                    Notes
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200/80 bg-white">
                            <tr
                                v-for="assignment in assignments"
                                :key="assignment.id"
                                class="align-top"
                            >
                                <td class="px-5 py-4">
                                    <div class="font-semibold text-slate-800">
                                        {{ assignment.vehicle?.unit || 'N/A' }}
                                    </div>
                                    <div class="mt-1 text-xs text-slate-500">
                                        {{
                                            assignment.vehicle?.title ||
                                            'Historical record'
                                        }}
                                    </div>
                                    <div
                                        v-if="assignment.vehicle?.vin"
                                        class="mt-1 text-xs text-slate-400"
                                    >
                                        VIN {{ assignment.vehicle.vin }}
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="font-medium text-slate-800">
                                        {{ assignment.driver_type_label }}
                                    </div>
                                    <div
                                        v-if="assignment.third_party?.name"
                                        class="mt-1 text-xs text-slate-500"
                                    >
                                        {{ assignment.third_party.name }}
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="text-slate-800">
                                        {{ assignment.start_date || 'N/A' }}
                                    </div>
                                    <div class="mt-1 text-xs text-slate-500">
                                        to
                                        {{ assignment.end_date || 'Present' }}
                                    </div>
                                    <div class="mt-1 text-xs text-slate-400">
                                        {{ assignment.duration_label }}
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <div
                                        class="inline-flex rounded-full bg-primary/10 px-3 py-1 text-xs font-semibold tracking-wide text-primary uppercase"
                                    >
                                        {{ assignment.status_label }}
                                    </div>
                                    <div class="mt-1 text-xs text-slate-500">
                                        By
                                        {{ assignment.assigned_by || 'System' }}
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-slate-600">
                                    {{ assignment.notes || 'No notes' }}
                                </td>
                            </tr>

                            <tr v-if="assignments.length === 0">
                                <td
                                    colspan="5"
                                    class="px-5 py-12 text-center text-sm text-slate-500"
                                >
                                    No assignment history is available yet.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>
