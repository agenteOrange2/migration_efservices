<script setup lang="ts">
import Lucide from '@/components/Base/Lucide'

interface StatCard {
    label: string
    value: string | number
    icon?: string
    hint?: string
}

defineProps<{
    cards: StatCard[]
}>()
</script>

<template>
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div
            v-for="card in cards"
            :key="card.label"
            class="box box--stacked border border-primary/10 bg-primary/5 p-5"
        >
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ card.label }}</p>
                    <p class="mt-2 text-2xl font-semibold text-slate-800">{{ card.value }}</p>
                    <p v-if="card.hint" class="mt-2 text-xs text-slate-500">{{ card.hint }}</p>
                </div>
                <div class="rounded-xl bg-white/80 p-3 shadow-sm">
                    <Lucide :icon="card.icon || 'BarChart3'" class="h-5 w-5 text-primary" />
                </div>
            </div>
        </div>
    </div>
</template>
