<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import Lucide from '@/components/Base/Lucide';
import Button from '@/components/Base/Button';
import { FormInput, FormSelect } from '@/components/Base/Form';
import RazeLayout from '@/layouts/RazeLayout.vue';
import { useDebounceFn } from '@vueuse/core';

declare function route(name: string, params?: any): string;

defineOptions({ layout: RazeLayout });

interface Driver {
    id: number;
    name: string;
    last_name: string;
    middle_name: string;
    email: string;
    phone: string;
    carrier_name: string;
    profile_photo: string | null;
    application_date: string;
    status: string;
    checklist_pct: number;
}

const props = defineProps<{
    drivers: {
        data: Driver[];
        links: { url: string | null; label: string; active: boolean }[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number;
        to: number;
    };
    carriers: { id: number; name: string }[];
    filters: {
        search: string;
        status: string;
        carrier: string;
        per_page: number;
    };
    stats: {
        total: number;
        pending: number;
        approved: number;
        rejected: number;
    };
    applicationStatuses: Record<string, string>;
}>();

const search = ref(props.filters.search ?? '');
const statusFilter = ref(props.filters.status ?? '');
const carrierFilter = ref(props.filters.carrier ?? '');

function applyFilters() {
    router.get(
        route('admin.driver-recruitment.index'),
        {
            search: search.value || undefined,
            status: statusFilter.value || undefined,
            carrier: carrierFilter.value || undefined,
        },
        { preserveState: true, replace: true },
    );
}

const debouncedSearch = useDebounceFn(applyFilters, 400);
watch(search, debouncedSearch);
watch(statusFilter, applyFilters);
watch(carrierFilter, applyFilters);

const statusConfig: Record<
    string,
    { label: string; classes: string; icon: string }
> = {
    draft: {
        label: 'Draft',
        classes: 'border border-slate-200 bg-slate-100 text-slate-600',
        icon: 'FileEdit',
    },
    pending: {
        label: 'Pending',
        classes: 'border border-warning/20 bg-warning/10 text-warning',
        icon: 'Clock',
    },
    approved: {
        label: 'Approved',
        classes: 'border border-success/20 bg-success/10 text-success',
        icon: 'CheckCircle',
    },
    rejected: {
        label: 'Rejected',
        classes: 'border border-danger/20 bg-danger/10 text-danger',
        icon: 'XCircle',
    },
};

function statusBadge(status: string) {
    return statusConfig[status] ?? statusConfig.draft;
}
</script>

<template>
    <Head title="Driver Recruitment" />

    <div class="grid grid-cols-12 gap-x-6 gap-y-8">
        <!-- Header -->
        <div class="col-span-12">
            <div class="box box--stacked p-6">
                <div
                    class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between"
                >
                    <div class="flex items-center gap-4">
                        <div
                            class="rounded-2xl border border-primary/20 bg-primary/10 p-3"
                        >
                            <Lucide
                                icon="ClipboardCheck"
                                class="h-8 w-8 text-primary"
                            />
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-slate-800">
                                Driver Recruitment
                            </h1>
                            <p class="text-sm text-slate-500">
                                Review and manage driver applications
                            </p>
                        </div>
                    </div>
                    <Link :href="route('admin.drivers.wizard.create')">
                        <Button
                            variant="primary"
                            class="inline-flex items-center gap-2"
                        >
                            <Lucide icon="UserPlus" class="h-4 w-4" />
                            Register New Driver
                        </Button>
                    </Link>
                </div>
            </div>
        </div>

        <!-- Stats -->
        <div class="col-span-12">
            <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                <div class="box box--stacked rounded-2xl p-5">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <div class="text-sm text-slate-500">
                                Total Applications
                            </div>
                            <div class="mt-1 text-2xl font-bold text-slate-800">
                                {{ stats.total }}
                            </div>
                        </div>
                        <div
                            class="rounded-xl border border-primary/20 bg-primary/10 p-2.5"
                        >
                            <Lucide icon="Users" class="h-4 w-4 text-primary" />
                        </div>
                    </div>
                    <div
                        class="mt-3 inline-flex items-center gap-1 rounded-full bg-primary/10 px-2.5 py-1 text-xs font-medium text-primary"
                    >
                        <Lucide icon="Users" class="h-3 w-3" /> All applications
                    </div>
                </div>
                <div class="box box--stacked rounded-2xl p-5">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <div class="text-sm text-slate-500">
                                Pending Review
                            </div>
                            <div class="mt-1 text-2xl font-bold text-slate-800">
                                {{ stats.pending }}
                            </div>
                        </div>
                        <div
                            class="rounded-xl border border-primary/20 bg-primary/10 p-2.5"
                        >
                            <Lucide
                                icon="Clock3"
                                class="h-4 w-4 text-primary"
                            />
                        </div>
                    </div>
                    <div
                        class="mt-3 inline-flex items-center gap-1 rounded-full bg-primary/10 px-2.5 py-1 text-xs font-medium text-primary"
                    >
                        <Lucide icon="Clock3" class="h-3 w-3" /> Awaiting review
                    </div>
                </div>
                <div class="box box--stacked rounded-2xl p-5">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <div class="text-sm text-slate-500">Approved</div>
                            <div class="mt-1 text-2xl font-bold text-slate-800">
                                {{ stats.approved }}
                            </div>
                        </div>
                        <div
                            class="rounded-xl border border-primary/20 bg-primary/10 p-2.5"
                        >
                            <Lucide
                                icon="BadgeCheck"
                                class="h-4 w-4 text-primary"
                            />
                        </div>
                    </div>
                    <div
                        class="mt-3 inline-flex items-center gap-1 rounded-full bg-primary/10 px-2.5 py-1 text-xs font-medium text-primary"
                    >
                        <Lucide icon="BadgeCheck" class="h-3 w-3" /> Verified
                        and approved
                    </div>
                </div>
                <div class="box box--stacked rounded-2xl p-5">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <div class="text-sm text-slate-500">Rejected</div>
                            <div class="mt-1 text-2xl font-bold text-slate-800">
                                {{ stats.rejected }}
                            </div>
                        </div>
                        <div
                            class="rounded-xl border border-primary/20 bg-primary/10 p-2.5"
                        >
                            <Lucide
                                icon="FileX2"
                                class="h-4 w-4 text-primary"
                            />
                        </div>
                    </div>
                    <div
                        class="mt-3 inline-flex items-center gap-1 rounded-full bg-primary/10 px-2.5 py-1 text-xs font-medium text-primary"
                    >
                        <Lucide icon="FileX2" class="h-3 w-3" /> Closed
                        applications
                    </div>
                </div>
            </div>
        </div>

        <!-- Table Box -->
        <div class="col-span-12">
            <div class="box box--stacked flex flex-col">
                <!-- Filters -->
                <div class="border-b border-slate-200/60 p-5">
                    <div class="mb-4 flex items-center gap-3">
                        <div class="rounded-lg bg-primary/10 p-2">
                            <Lucide
                                icon="Filter"
                                class="h-4 w-4 text-primary"
                            />
                        </div>
                        <h2 class="text-lg font-semibold text-slate-800">
                            Filters
                        </h2>
                    </div>
                    <div
                        class="grid grid-cols-1 gap-3 lg:grid-cols-[minmax(0,1.4fr)_180px_220px]"
                    >
                        <div class="relative">
                            <Lucide
                                icon="Search"
                                class="absolute inset-y-0 left-0 z-10 my-auto ml-3 h-4 w-4 text-slate-400"
                            />
                            <FormInput
                                v-model="search"
                                type="text"
                                placeholder="Search drivers..."
                                class="pl-9"
                            />
                        </div>
                        <FormSelect v-model="statusFilter">
                            <option value="">All statuses</option>
                            <option
                                v-for="(label, val) in applicationStatuses"
                                :key="val"
                                :value="val"
                            >
                                {{ label }}
                            </option>
                        </FormSelect>
                        <FormSelect v-model="carrierFilter">
                            <option value="">All carriers</option>
                            <option
                                v-for="c in carriers"
                                :key="c.id"
                                :value="c.id"
                            >
                                {{ c.name }}
                            </option>
                        </FormSelect>
                    </div>
                </div>

                <!-- Table -->
                <div class="overflow-auto xl:overflow-visible">
                    <table class="w-full border-b border-slate-200/60 text-sm">
                        <thead>
                            <tr>
                                <td
                                    class="border-t border-slate-200/60 bg-slate-50 px-4 py-4 font-medium text-slate-500"
                                >
                                    Driver
                                </td>
                                <td
                                    class="border-t border-slate-200/60 bg-slate-50 px-4 py-4 font-medium text-slate-500"
                                >
                                    Contact
                                </td>
                                <td
                                    class="border-t border-slate-200/60 bg-slate-50 px-4 py-4 font-medium text-slate-500"
                                >
                                    Carrier
                                </td>
                                <td
                                    class="border-t border-slate-200/60 bg-slate-50 px-4 py-4 text-center font-medium text-slate-500"
                                >
                                    Verification
                                </td>
                                <td
                                    class="border-t border-slate-200/60 bg-slate-50 px-4 py-4 text-center font-medium text-slate-500"
                                >
                                    Status
                                </td>
                                <td
                                    class="border-t border-slate-200/60 bg-slate-50 px-4 py-4 text-center font-medium text-slate-500"
                                >
                                    Actions
                                </td>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="drivers.data.length === 0">
                                <td
                                    colspan="6"
                                    class="py-12 text-center text-slate-500"
                                >
                                    <div
                                        class="flex flex-col items-center gap-2"
                                    >
                                        <Lucide
                                            icon="UserX"
                                            class="h-12 w-12 text-slate-300"
                                        />
                                        <p>No driver applications found</p>
                                    </div>
                                </td>
                            </tr>
                            <tr
                                v-for="driver in drivers.data"
                                :key="driver.id"
                                class="border-t border-slate-100 transition hover:bg-slate-50/50"
                            >
                                <!-- Driver -->
                                <td class="px-4 py-4">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="flex h-10 w-10 flex-shrink-0 items-center justify-center overflow-hidden rounded-full bg-slate-100"
                                        >
                                            <img
                                                v-if="driver.profile_photo"
                                                :src="driver.profile_photo"
                                                :alt="driver.name"
                                                class="h-full w-full object-cover"
                                            />
                                            <Lucide
                                                v-else
                                                icon="User"
                                                class="h-5 w-5 text-slate-400"
                                            />
                                        </div>
                                        <div>
                                            <div
                                                class="font-medium text-slate-800"
                                            >
                                                {{ driver.name }}
                                                {{ driver.last_name }}
                                            </div>
                                            <div class="text-xs text-slate-500">
                                                <span v-if="driver.middle_name"
                                                    >{{ driver.middle_name }} ·
                                                </span>
                                                Apply:
                                                {{ driver.application_date }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <!-- Contact -->
                                <td class="px-4 py-4">
                                    <div class="flex flex-col gap-0.5">
                                        <span class="text-slate-700">{{
                                            driver.email
                                        }}</span>
                                        <span class="text-xs text-slate-500">{{
                                            driver.phone
                                        }}</span>
                                    </div>
                                </td>
                                <!-- Carrier -->
                                <td class="px-4 py-4">
                                    <span class="font-medium text-slate-700">{{
                                        driver.carrier_name
                                    }}</span>
                                </td>
                                <!-- Verification progress -->
                                <td class="px-4 py-4 text-center">
                                    <div
                                        class="flex items-center justify-center"
                                    >
                                        <div
                                            class="flex h-14 w-14 items-center justify-center rounded-full"
                                            :style="`background: conic-gradient(rgb(3 4 94) ${driver.checklist_pct}%, rgb(241 245 249) 0)`"
                                        >
                                            <div
                                                class="flex h-10 w-10 items-center justify-center rounded-full bg-white text-xs font-semibold text-slate-700"
                                            >
                                                {{ driver.checklist_pct }}%
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <!-- Status -->
                                <td class="px-4 py-4 text-center">
                                    <div class="flex justify-center">
                                        <span
                                            class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium"
                                            :class="
                                                statusBadge(driver.status)
                                                    .classes
                                            "
                                        >
                                            <Lucide
                                                :icon="
                                                    statusBadge(driver.status)
                                                        .icon
                                                "
                                                class="h-3.5 w-3.5"
                                            />
                                            {{
                                                statusBadge(driver.status).label
                                            }}
                                        </span>
                                    </div>
                                </td>
                                <!-- Actions -->
                                <td class="px-4 py-4 text-center">
                                    <Link
                                        :href="
                                            route(
                                                'admin.driver-recruitment.show',
                                                driver.id,
                                            )
                                        "
                                        class="inline-flex items-center gap-1.5 rounded-lg bg-primary px-3 py-1.5 text-xs font-medium text-white transition hover:bg-primary/90"
                                    >
                                        <Lucide
                                            icon="ClipboardCheck"
                                            class="h-3.5 w-3.5"
                                        />
                                        Review
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div
                    v-if="drivers.last_page > 1"
                    class="flex items-center justify-between border-t border-slate-200/60 p-5"
                >
                    <div class="text-sm text-slate-500">
                        Showing {{ drivers.from }}–{{ drivers.to }} of
                        {{ drivers.total }} results
                    </div>
                    <div class="flex gap-1">
                        <template
                            v-for="link in drivers.links"
                            :key="link.label"
                        >
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                preserve-scroll
                                class="rounded border px-3 py-1 text-sm transition"
                                :class="
                                    link.active
                                        ? 'border-primary bg-primary text-white'
                                        : 'border-slate-200 text-slate-600 hover:border-primary/40 hover:text-primary'
                                "
                                v-html="link.label"
                            />
                            <span
                                v-else
                                class="cursor-default rounded border border-slate-100 px-3 py-1 text-sm text-slate-300"
                                v-html="link.label"
                            />
                        </template>
                    </div>
                </div>
                <div
                    v-else-if="drivers.total > 0"
                    class="border-t border-slate-200/60 p-5 text-sm text-slate-500"
                >
                    Showing {{ drivers.total }} result{{
                        drivers.total !== 1 ? 's' : ''
                    }}
                </div>
            </div>
        </div>
    </div>
</template>
