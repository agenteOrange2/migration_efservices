<script setup lang="ts">
import { Link } from '@inertiajs/vue3'

interface PaginationLink {
    url: string | null
    label: string
    active: boolean
}

defineProps<{
    links: PaginationLink[]
}>()
</script>

<template>
    <div class="flex flex-wrap items-center justify-end gap-2">
        <Link
            v-for="link in links"
            :key="`${link.label}-${link.url}`"
            :href="link.url || '#'"
            class="rounded-lg border px-3 py-2 text-sm transition"
            :class="[
                link.active
                    ? 'border-primary bg-primary text-white'
                    : 'border-slate-200 bg-white text-slate-600 hover:border-primary/40 hover:text-primary',
                !link.url ? 'pointer-events-none opacity-40' : '',
            ]"
            v-html="link.label"
        />
    </div>
</template>
