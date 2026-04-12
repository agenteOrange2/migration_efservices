<script setup lang="ts">
import Lucide from '@/components/Base/Lucide'

defineProps<{
    driver: {
        id: number
        name: string
        email?: string | null
        phone?: string | null
        date_of_birth?: string | null
        status?: string | null
        profile_photo_url?: string | null
        carrier?: { id: number; name: string } | null
    }
}>()
</script>

<template>
    <div class="box box--stacked p-6">
        <div class="flex flex-col gap-5 md:flex-row md:items-center">
            <div class="h-24 w-24 overflow-hidden rounded-2xl border border-slate-200 bg-slate-50">
                <img
                    :src="driver.profile_photo_url || '/build/default_profile.png'"
                    :alt="driver.name"
                    class="h-full w-full object-cover"
                >
            </div>

            <div class="flex-1">
                <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                    <div>
                        <h2 class="text-2xl font-semibold text-slate-800">{{ driver.name }}</h2>
                        <p class="mt-1 text-sm text-slate-500">{{ driver.email || 'No email available' }}</p>
                    </div>
                    <div v-if="driver.status" class="inline-flex rounded-full bg-primary/10 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-primary">
                        {{ driver.status }}
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <div class="rounded-xl border border-dashed border-slate-300/80 bg-slate-50/70 px-4 py-3">
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Phone</div>
                        <div class="mt-1 text-sm font-medium text-slate-800">{{ driver.phone || 'N/A' }}</div>
                    </div>

                    <div class="rounded-xl border border-dashed border-slate-300/80 bg-slate-50/70 px-4 py-3">
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Date of Birth</div>
                        <div class="mt-1 text-sm font-medium text-slate-800">{{ driver.date_of_birth || 'N/A' }}</div>
                    </div>

                    <div class="rounded-xl border border-dashed border-slate-300/80 bg-slate-50/70 px-4 py-3">
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Carrier</div>
                        <div class="mt-1 text-sm font-medium text-slate-800">{{ driver.carrier?.name || 'N/A' }}</div>
                    </div>

                    <div class="rounded-xl border border-dashed border-slate-300/80 bg-slate-50/70 px-4 py-3">
                        <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <Lucide icon="Truck" class="h-3.5 w-3.5" />
                            Driver Record
                        </div>
                        <div class="mt-1 text-sm font-medium text-slate-800">#{{ driver.id }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
