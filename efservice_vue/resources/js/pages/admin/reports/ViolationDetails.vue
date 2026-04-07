<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import RazeLayout from '@/layouts/RazeLayout.vue'
import ReportHero from './components/ReportHero.vue'
defineOptions({ layout: RazeLayout })
defineProps<{ violation: Record<string, any> }>()
</script>
<template>
    <Head :title="`Violation ${violation.id}`" />
    <div class="grid grid-cols-12 gap-6">
        <div class="col-span-12"><ReportHero :title="`Violation #${violation.id}`" subtitle="Detailed view of a specific HOS violation and its penalty context." icon="ShieldAlert" /></div>
        <div class="col-span-12"><div class="box box--stacked p-6"><div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3 text-sm"><div v-for="(value, key) in violation" :key="key"><div class="text-slate-500">{{ String(key).replace(/_/g, ' ') }}</div><div class="font-medium text-slate-800">{{ value ?? 'N/A' }}</div></div></div></div></div>
    </div>
</template>
