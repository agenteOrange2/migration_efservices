<script setup lang="ts">
import Lucide from '@/components/Base/Lucide'

defineProps<{
    title: string
    subtitle: string
    icon?: string
}>()
</script>

<template>
    <div class="box box--stacked p-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-center gap-4">
                <div class="rounded-2xl border border-primary/15 bg-primary/10 p-3">
                    <Lucide :icon="icon || 'BarChart3'" class="h-8 w-8 text-primary" />
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-slate-800">{{ title }}</h1>
                    <p class="mt-1 text-sm text-slate-500">{{ subtitle }}</p>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <slot name="actions" />
            </div>
        </div>
    </div>
</template>
