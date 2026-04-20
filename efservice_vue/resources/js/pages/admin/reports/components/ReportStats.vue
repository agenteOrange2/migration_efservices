<script setup lang="ts">
import Lucide from '@/components/Base/Lucide'

interface StatCard {
    label: string
    value: string | number
    icon?: string
    hint?: string
    tone?: 'primary' | 'success' | 'warning' | 'danger' | 'info'
}

defineProps<{
    cards: StatCard[]
}>()

function cardClasses(tone?: StatCard['tone']) {
    switch (tone) {
        case 'success':
            return {
                wrapper: 'border-success/20 bg-success/5',
                iconWrap: 'bg-success/10',
                icon: 'text-success',
            }
        case 'warning':
            return {
                wrapper: 'border-warning/20 bg-warning/5',
                iconWrap: 'bg-warning/10',
                icon: 'text-warning',
            }
        case 'danger':
            return {
                wrapper: 'border-danger/20 bg-danger/5',
                iconWrap: 'bg-danger/10',
                icon: 'text-danger',
            }
        case 'info':
            return {
                wrapper: 'border-info/20 bg-info/5',
                iconWrap: 'bg-info/10',
                icon: 'text-info',
            }
        default:
            return {
                wrapper: 'border-primary/10 bg-primary/5',
                iconWrap: 'bg-white/80',
                icon: 'text-primary',
            }
    }
}
</script>

<template>
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div
            v-for="card in cards"
            :key="card.label"
            class="box box--stacked border p-5"
            :class="cardClasses(card.tone).wrapper"
        >
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ card.label }}</p>
                    <p class="mt-2 text-2xl font-semibold text-slate-800">{{ card.value }}</p>
                    <p v-if="card.hint" class="mt-2 text-xs text-slate-500">{{ card.hint }}</p>
                </div>
                <div class="rounded-xl p-3 shadow-sm" :class="cardClasses(card.tone).iconWrap">
                    <Lucide :icon="card.icon || 'BarChart3'" class="h-5 w-5" :class="cardClasses(card.tone).icon" />
                </div>
            </div>
        </div>
    </div>
</template>
