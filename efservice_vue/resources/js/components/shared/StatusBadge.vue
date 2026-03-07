<script setup lang="ts">
import { computed } from 'vue'

type Variant = 'success' | 'danger' | 'warning' | 'info' | 'neutral' | 'primary'

interface Props {
    status: string | number | boolean
    map?: Record<string | number, { label: string; variant: Variant }>
    label?: string
    variant?: Variant
    size?: 'sm' | 'md'
}

const props = withDefaults(defineProps<Props>(), {
    size: 'sm',
})

const resolved = computed(() => {
    if (props.map && (props.status as string | number) in props.map) {
        return props.map[props.status as string | number]
    }
    return {
        label: props.label ?? String(props.status),
        variant: props.variant ?? 'neutral',
    }
})

const variantClasses: Record<Variant, string> = {
    success: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
    danger: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
    warning: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
    info: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
    neutral: 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300',
    primary: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400',
}

const sizeClasses: Record<string, string> = {
    sm: 'px-2 py-0.5 text-xs',
    md: 'px-2.5 py-1 text-sm',
}
</script>

<template>
    <span
        class="inline-flex items-center rounded-full font-medium capitalize"
        :class="[variantClasses[resolved.variant], sizeClasses[size]]"
    >
        {{ resolved.label }}
    </span>
</template>
